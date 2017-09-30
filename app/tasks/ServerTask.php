<?php
class ServerTask extends \Phalcon\CLI\Task{
    public $timerArr     = [];//定时器时间存储
    public $startTimeArr = [];//在线时长
    public $fdMems       = [];//拆分暂存
    private $serv;
    private $message;
    private $startTime;
    public $adminMsgId = 9999;//admin msg id

    public $heartbeatWhiteList = [];//心跳白名单

    public $table = null;
    /**
     * bootstrap
     */
    public function mainAction(){
        $this->init();
    }
    /**
     * 模拟请求
     */
    public function fakeAction(){
        $this->config->swoole->port = 9502;
        $this->init();
    }
    /**
     * 打印信息前缀
     * @return string
     */
    public static function prefix(){
        $now = date('Y-m-d H:i:s');
        return "[INFO][{$now}] ";
    }
    /**
     * 连接初始化操作
     */
    public function init() {
        Cache::$tmpSwitch = false;
        //处理会话
        //Cache::db('server')->flushDB();
        //处理消息
        $this->message = new Message;
        // $this->message->session = ServSession::
        $swoole = $this->config->swoole;
        //init server
        $this->serv = new swoole_server($swoole->host, $swoole->port);
        $this->serv->set($swoole->server_setting->toArray());

        $this->serv->on('Start', [$this, 'onStart']);
        $this->serv->on('Connect', [$this, 'onConnect']);
        $this->serv->on('Receive', [$this, 'onReceive']);
        $this->serv->on('Close', [$this, 'onClose']);
        $this->serv->on('Shutdown', [$this, 'onShutdown']);

        $this->serv->on('Task', [$this, 'onTask']);
        $this->serv->on('Finish', [$this, 'onFinish']);

        global $config;
        $swoolePort = $config->swoole->port;
        cli_set_process_title('php_swoole_server_task_'.$swoolePort);//set process name

        //开辟内存块存fd和player_id的映射关系
        $table = new swoole_table(65536);
        $table->column('fd', swoole_table::TYPE_INT, 4);
        $table->column('player_id', swoole_table::TYPE_INT, 4);
        $table->column('camp_id', swoole_table::TYPE_INT, 4);
        $table->create();
        ServSession::$table = $table;

        $this->memCache = Cache::db('server');
        $this->serv->start();
    }
    /**
     * 服务器启动
     * @param  swoole_server $serv 
     */
    public function onStart($serv){
        // $masterPid = $serv->master_pid;
        // $managerPid = $serv->manager_pid;

        // file_put_contents(APP_PATH.'/app/tasks/swoole/swoole_master_pid.txt', $masterPid);
        // file_put_contents(APP_PATH.'/app/tasks/swoole/swoole_manager_pid.txt', $managerPid);
        
        echo self::prefix()."Start\n";
    }
    /**
     * worker进程开启
     * @param  swoole_server $serv     
     * @param  int        $workerId 
     */
    public function onWorkerStart(swoole_server $serv, $workerId){
        // echo self::prefix()."worker start\n";
    }
    /**
     * 客户端连接过来调用的函数
     * @param  swoole_server $serv    
     * @param  int $fd      
     * @param  int $from_id 
     */
    public function onConnect($serv, $fd, $from_id) {
        log4server(fdPrefix($fd)."BBBBBBBBBBBegin on connect++++++++++++");
        $this->startTimeArr[$fd] = time();
        //test
        // $conn_list = $serv->connection_list(0, 10);
        // print_r($conn_list);
        // echo self::prefix()."{$fd} connected\n";
        //$serv->send($fd, packData("hello {$fd}"));
    }
    /**
     * 从客户端接受数据包
     * @param  swoole_server $serv    
     * @param  int        $fd     
     * @param  int        $from_id 
     * @param  string        $data    
     */
    public function onReceive(swoole_server $serv, $fd, $from_id, $data) {
        $head = unpack('A4head/I1msgId/I1length', $data);
        /*log4server(fdPrefix($fd)."------------------>>>>>>>>包大小：".strlen($data));
        $_head = bin2hex($data);
        $_head = substr($_head, 0, 24);
        log4server(fdPrefix($fd)."------------------>>>>>>>>包头：".displayBinary(hex2bin($_head)));*/

        if(strlen($data)>0 && isset($this->timerArr[$fd])) $this->timerArr[$fd] = time();
        //心跳包白名单
        if(isset($head['msgId']) && $head['msgId']==10010) {//心跳白名单add
            $hbData = unpackData($data);
            $hbData = json_decode($hbData['content'], true);
            if($hbData['is_pause']==1) {
                $this->heartbeatWhiteList[$fd] = true;
                log4server(fdPrefix($fd)."加延时- 300s");
            } elseif(!empty($hbData['is_pause']) && $hbData['is_pause']==0 && isset($this->heartbeatWhiteList[$fd])) {
                unset($this->heartbeatWhiteList[$fd]);
                log4server(fdPrefix($fd)."去延时- 10s");
            }
        }
        if (isset($head['msgId']) && $head['msgId'] == 10000) {//登录
            $this->timerArr[$fd] = time();//初始化定时器时间
            $this->memCache      = Cache::db('server');
            $serv->after(5000, function() use ($serv, $fd){//延迟5秒执行心跳包检测定时器
                if(!$serv->exist($fd)) return;
                $this->timerArr[$fd] = time();
                log4server(fdPrefix($fd)."启动定时器检测心跳");
                $serv->tick(1000, function($id) use ($serv, $fd) {
                    if(!$serv->exist($fd)) {
                        $serv->clearTimer($id);
                        return;
                    }
                    if (!isset($this->timerArr[$fd])) {
                        $serv->clearTimer($id);
                        return;
                    }
                    $subTime = time() - $this->timerArr[$fd];
                    if(isset($this->heartbeatWhiteList[$fd])) {
                        $hbTime = 300;//5*60;5分钟
                    } else {
                        $hbTime = 10;//心跳检测时间
                        if($subTime>8) {
                            log4server(fdPrefix($fd)."[异常] 有{$subTime}s没发数据过来了(>8s检测)！！！！！！");
                        }
                    }
                    if ($subTime > $hbTime - 1) {
                        log4server(fdPrefix($fd)."关闭连接[{$subTime}s]------------心跳检测主动关关关关关关关关关关关关关 connection_info=".arr2str($serv->connection_info($fd)));
                        $serv->close($fd);
                        $serv->clearTimer($id);
                        return;
                    }
                });
            });
        }
        $receiveSize = strlen($data);//本次收到长度
        $keyMemCache = 'mem_cache_fd_'.$fd;
        if(isset($head['head']) && isset($head['msgId']) && $head['head']==SWOOLE_MSG_HEAD && in_array($head['msgId'], StaticData::$msgIds) && isset($head['length'])) {
            $totalSize = $head['length']+12;//应收长度
            if($totalSize>$receiveSize) {//等下个包
                $currentMem['data_flag']    = $keyMemCache;
                $currentMem['total_size']   = $totalSize;
                $currentMem['receive_size'] = $receiveSize;
                $this->fdMems[$fd]          = $currentMem;

                $this->memCache->setex($keyMemCache, 1800, $data);
                return null;
            } else {
                if(isset($this->fdMems[$fd])) {
                    unset($this->fdMems[$fd]);
                }
                if($totalSize>8192) {//大于等于8k，swoole限制
                    $this->memCache->setex($keyMemCache, 300, $data);
                    $memFlag = true;
                }
            }
        } else {//粘包
//            echo "temp: ".strlen($this->temp),PHP_EOL;
            $memData = $this->memCache->get($keyMemCache);
            $memData .= $data;
            $this->memCache->setex($keyMemCache, 3600, $memData);
            $this->fdMems[$fd]['receive_size'] += $receiveSize;
            if($this->fdMems[$fd]['receive_size']<$this->fdMems[$fd]['total_size']) {
                return null;
            }
        }
        if(!empty( $this->fdMems[$fd])) {
            $keyMemCache = 'mem_cache_fd_' . $fd;
            $data        = $this->memCache->get($keyMemCache);
            $head        = unpack('A4head/I1msgId/I1length', $data);
            $memFlag     = true;
        }

        if(!isset($head['head']) || !isset($head['msgId']) || $head['head']!=SWOOLE_MSG_HEAD || !in_array($head['msgId'], StaticData::$msgIds)) {
            echo "!非法连接!\n";
            if(isset($head['head'])) {
                echo "illegal head: ".$head['head'].PHP_EOL;
            }
            if(isset($head['msgId'])) {
                echo "illegalMsgId: ".$head['msgId'].PHP_EOL;
            }
            $serv->send($fd, "!非法连接!\n");
            $serv->close($fd);
            return false;
        }
//        if(is_string($data)) $data = trim($data);
        $param = ['fd'=>$fd, 'data'=>$data, 'mem_flag'=>0];
        if(isset($memFlag)) {
            $param['mem_flag'] = 1;
            $param['data'] = '';
        }
        $serv->task($param);//交给task进程
    }
    /**
     * Worker进程
     * @param  swoole_server $serv    
     * @param  int $task_id 
     * @param  int $from_id 
     * @param  array $data    
     * @return string        
     */
    public function onTask(swoole_server $serv,$task_id,$from_id, $taskData) {
        $fd = $taskData['fd'];
        if($taskData['mem_flag']==1) {
            $keyMemCache = 'mem_cache_fd_'.$fd;
            $tdata = Cache::db('server')->get($keyMemCache);
        } else {
            $tdata = $taskData['data'];
        }
        $tdata = unpackData($tdata);
        if((!in_array($tdata['msgId'], [10002,10010])  && $serv->exist($fd)) || $tdata['msgId']==10006) {
            log4server(fdPrefix($fd)."传入数据 = [msgId]:" . color($tdata['msgId'], 'purple', true) . ' --> [content]: ' . arr2str(json_decode($tdata['content'], true)));
        }
        $msgIds = StaticData::$msgIds;
        if($tdata['head']==SWOOLE_MSG_HEAD) {
            switch($tdata['msgId']) {
                case $msgIds['LoginRequest']://登陆
                    if(!$serv->exist($fd)) {
                        log4server(fdPrefix($fd).'连接已断');
                        return;
                    }
                    $loginData = json_decode($tdata['content'], true);
                    log4server(fdPrefix($fd)."登录数据=". arr2str($loginData));
                    if(isset($loginData['player_id']) && !empty($loginData['player_id'])) {
                        $playerId = $loginData['player_id'];
                        $hashCode = @$loginData['hash_code'];
                        if(hashMethod($playerId)!=$hashCode) {//验证不通过
                            log4server(fdPrefix($fd)."登录验证不通过");
                            $serv->close($fd);
                            return '';
                        }
                        $re     = iquery("select id, camp_id from player where id={$playerId}");
                        $player = [];
                        if($re) {
                            $player = $re[0];
                        }
                        log4server(fdPrefix($fd)."玩家数据=".arr2str($player));
                    }
                    if($player) {//不为空
                        ServSession::setFd($playerId, $fd, $player['camp_id']);
                        //记录开始登陆时间入表                        
                        $this->doRecord($playerId);
                    } else {//如果不存在，关闭长连接
                        log4server(fdPrefix($fd)."登录验证不通过-无玩家数据");
                        $serv->close($fd);
                        return '';
                    }
                    $msgId     = $msgIds['LoginResponse'];
                    $content   = '';
                    
                    $data      = packData($content, $msgId);
                    $serv->send($fd, $data);
                    break;
                case $msgIds['HeartBeatRequest']://心跳
                    if(!$serv->exist($fd)) {
                        log4server(fdPrefix($fd).'连接已断');
                        return;
                    }
                    $msgId   = $msgIds['HeartBeatResponse'];
                    $content = '';
                    $data    = packData($content, $msgId);
                    $serv->send($fd, $data);
                    break;
                case $msgIds['DataRequest']://消息
                    if(!$serv->exist($fd)) {
                        log4server(fdPrefix($fd).'连接已断');
                        return;
                    }
                    $msgId   = $msgIds['DataResponse'];
                    $content = $this->message->processMsg($serv, $fd, $from_id, $tdata);//处理数据包
                    if(!empty($content)) {
                        log4server(fdPrefix($fd) . "返回数据=" . arr2str($content));
                    }
                    $data    = packData($content, $msgId);
                    $serv->send($fd, $data);
                    break;
                case $msgIds['ChatSendRequest']://聊天
                    if(!$serv->exist($fd)) {
                        log4server(fdPrefix($fd).'连接已断');
                        return;
                    }
                    $msgId   = $msgIds['ChatSendResponse'];
                    $content = $this->message->processMsg($serv, $fd, $from_id, $tdata);//处理数据包
                    $data    = packData($content, $msgId);
                    $serv->send($fd, $data);
                    break;
                case $msgIds['WebServerRequest']://web服务器发来的
                    $msgId   = $msgIds['WebServerResponse'];
                    $data    = packData('', $msgId);
                    $serv->send($fd, $data);//返回验证信息，让web客户端关闭连接
                    //处理逻辑
                    $this->message->processMsg($serv, $fd, $from_id, $tdata);//处理数据包
                    break;
                case $msgIds['PauseServerHeartBeatReq']://心跳延时
                    break;
                default: 
                    $msgId   = 0;
                    $content = '';
                    $data    = packData($content, $msgId);
                    $serv->send($fd, $data);
            }//switch end
        }//if end
        return '';
    }
    /**
     * 接收到Task任务的处理结果$data
     * @param  swoole_server $serv    
     * @param  int $task_id 
     * @param  array $data    
     */
    public function onFinish($serv,$task_id, $data) {
        // echo self::prefix()."Task {$task_id} finish\n";
        // echo "Result: {$data}\n";
    }
    /**
     * 客户端连接关闭
     * @param  swoole_server $serv    
     * @param  int $fd      
     * @param  int $from_id 
     */
    public function onClose($serv, $fd, $from_id) {
        if(isset($this->startTimeArr[$fd])) {
            if (isset($this->timerArr[$fd])) {
                unset($this->timerArr[$fd]);
                //记录本次登陆总时长
                $totalTime = time() - $this->startTimeArr[$fd];
                if($totalTime>0)
                    $this->closeRecord($fd, $totalTime);
            }
            unset($this->startTimeArr[$fd]);
        }
        ServSession::delLink($fd);
        if(isset($this->fdMems[$fd])) {
            unset($this->fdMems[$fd]);
        }
        log4server(fdPrefix($fd)."EEEEEEEEnd on close++++++++++++");
        // echo self::prefix()."Client {$fd} close connection\n";
    }
    /**
     * server关闭 kill -15 swoole主线程 # ps -ejHF|grep php
     * @param  swoole_server $serv 
     */
    public function onShutdown($serv){
        global $di;
        $di['db']->close();
        // echo self::prefix()."Server: on shutdown\n";
        Cache::db('server')->flushDB();
    }
    /**
     * 记录连接时间
     * 
     */
    public function doRecord($playerId){
        $date = date("Y-m-d");
        $objOnline = new PlayerOnline();
        $res = $objOnline->getRecord($playerId, $date);
        if(!$res){
            $objOnline->setRecord($playerId);
        }
    }
    /**
     * 断开连接更新时间
     *
     */
    public function closeRecord($fd, $during){
        $playerId = ServSession::getPlayerIdByFd($fd);
        if(!$playerId){
            return;
        }   
        $date = date("Y-m-d");
        $objOnline = new PlayerOnline();        
        $res = $objOnline->getRecord($playerId, $date);
        if(!$res){
            $objOnline->setRecord($playerId);
            //非当日close
            if($during>86400){
                $fields = array();
                $fields['online'] = "online+{$during}";
                $objOnline->updateRecord($playerId, $date, $fields);
            }
            else {
                //只是隔日需要细分
                //昨天上线时间
                $duringYesterday = strtotime($date) - $this->startTime;
                $yesterDay = date("Y-m-d", (time()-86400));
                $fieldsYesterday = array();
                $fieldsYesterday['online'] = "online+{$duringYesterday}";
                $objOnline->updateRecord($playerId, $yesterDay, $fieldsYesterday);
                //今天上线时间
                $duringToday = time()- strtotime($date);
                $fieldsToday = array();
                $fieldsToday['online'] = "online+{$duringToday}";
                $objOnline->updateRecord($playerId, $date, $fieldsToday);
                                
            }

            
        }
        else{
            //当日close
            $fields = array();
            $fields['online'] = "online+{$during}";
            $objOnline->updateRecord($playerId, $date, $fields);
        }
       
        
     
    }
}
