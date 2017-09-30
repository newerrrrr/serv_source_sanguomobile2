<?php
/**
 * 任务调度器
 *
 * ```php
 * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php dispatcher run battle
 * ```
 */
class DispatcherTask extends \Phalcon\CLI\Task{
    public $currentWorkerNum = 0;//当前剩余进程数量
    public $maxWorkerNum     = 64;//线上改为cpu*4;//子进程最大数量
    // public $workers          = [];//存放进程用的
    public $maxRunTimes      = 1999;//控制生成的子进程数总数，达到此数值后，关闭父进程 10000 //default:99999
    
    
    public $execStartTime    = 0;//执行开始时间
    public $taskName         = 'default';//任务进程名

    public $method = '';
    
    public $redis;
    /**
     * 子进程开始处理
     */
    public function begin($worker){
        global $redisSharedFlag, $config, $inDispWorker;
        $redisSharedFlag = true;//redis 开启share模式
		$inDispWorker = true;
        $swoolePort = $config->swoole->port;
        $config->redis->worker = $worker;

        $this->execStartTime = floor(microtime_float()*1000);
        $name                = "php_swoole_dispatcher_task_".$this->taskName."_".$swoolePort;
        $worker->name($name);
        $data                = json_decode($worker->read(), true);
        $ppq                 = $data['ppq'];

        if(!$ppq) {
            $worker->exit(0);
        }
		
		register_shutdown_function([$this, 'onshutdown'], $worker, $ppq);
        return $ppq;
    }
    /**
     * 映射关系结构
     * @var array
     */
    public $methodArr = [
                "gotoCollection"    => [
                    'type'=>[PlayerProjectQueue::TYPE_COLLECT_GOTO, 
                            //PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO
                    ]
                ],
                "doCollection"      => [
                    'type'=>[PlayerProjectQueue::TYPE_COLLECT_ING,
                            PlayerProjectQueue::TYPE_GUILDCOLLECT_ING,
                    ]
                ],
                "gotoNpcBattle" => [
                    'type'=>[PlayerProjectQueue::TYPE_NPCBATTLE_GOTO,
                            PlayerProjectQueue::TYPE_BOSSGATHER_GOTO
                    ]
                ],
                "gotoCityBattle"    => [
                    'type'=>[PlayerProjectQueue::TYPE_CITYBATTLE_GOTO]
                ],
                "gotoGather"        => [
                    'type'=>[PlayerProjectQueue::TYPE_GATHER_GOTO]
                ],
                "readyGather"       => [
                    'type'=>[PlayerProjectQueue::TYPE_GATHER_WAIT]
                ],
                "battleGather"      => [
                    'type'=>[PlayerProjectQueue::TYPE_GATHERBATTLE_GOTO]
                ],
                "backMidGather"     => [
                    'type'=>[PlayerProjectQueue::TYPE_GATHERD_MIDRETURN]
                ],
                "battleBase"            => [
                    'type'=>[PlayerProjectQueue::TYPE_ATTACKBASE_GOTO,
                            PlayerProjectQueue::TYPE_ATTACKBASEGATHER_GOTO,
                    ]
                ],
                //"resource"        => ['type'=>2],
                "gotoReinforce"         => [//增援
                    'type'=>PlayerProjectQueue::TYPE_CITYASSIST_GOTO
                ],
                "gotoSpy"               => [//侦查
                    'type'=>PlayerProjectQueue::TYPE_DETECT_GOTO
                ],
                "gotoGuildBuild" => [
                    'type'=>[
                        PlayerProjectQueue::TYPE_GUILDBASE_GOTO,//goto 堡垒
                        PlayerProjectQueue::TYPE_GUILDWAREHOUSE_GOTO,//goto 仓库
                        PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHGOTO,//goto 仓库
                        PlayerProjectQueue::TYPE_GUILDTOWER_GOTO,//goto 箭塔
                        PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO,//goto 超级矿
                    ]
                ],
                "gotoTown"           => [
                    'type'=>[PlayerProjectQueue::TYPE_KINGTOWN_GOTO,
                            PlayerProjectQueue::TYPE_KINGGATHERBATTLE_GOTO,
                    ]
                ],//去城寨
                "npcGotoTown"           => [
                    'type'=>[PlayerProjectQueue::TYPE_KINGNPCATTACK_GOTO,
                    ]
                ],//npc攻打城寨
                "gotoFetchItem" => [//拿去物品
                    'type'=>[PlayerProjectQueue::TYPE_FETCHITEM_GOTO, 
                    ]
                ],
				"hjnpcGotoBase"           => [
                    'type'=>[PlayerProjectQueue::TYPE_HJNPCATTACK_GOTO,
                    ]
                ],//npc攻打城寨
                "back"              => [
                    'type'=>[
                        PlayerProjectQueue::TYPE_CITYASSIST_ING, //援军返回
                        PlayerProjectQueue::TYPE_GUILDBASE_BUILD, 
                        PlayerProjectQueue::TYPE_GUILDBASE_REPAIR, 
                        PlayerProjectQueue::TYPE_GUILDBASE_DEFEND,
                        PlayerProjectQueue::TYPE_GUILDWAREHOUSE_BUILD,
                        PlayerProjectQueue::TYPE_GUILDTOWER_BUILD,
                        PlayerProjectQueue::TYPE_GUILDCOLLECT_BUILD,
                        PlayerProjectQueue::TYPE_GATHER_STAY,
                        PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE,
						PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE,
						PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST,
                    ],
                    //'worker_num' => 16
                ],
                "home"              => [
                    'type'=>[
                        PlayerProjectQueue::TYPE_RETURN,
                        PlayerProjectQueue::TYPE_COLLECT_RETURN, 
                        PlayerProjectQueue::TYPE_NPCBATTLE_RETURN, 
                        PlayerProjectQueue::TYPE_CITYBATTLE_RETURN,
                        PlayerProjectQueue::TYPE_CITYASSIST_RETURN,
                        PlayerProjectQueue::TYPE_GATHER_RETURN,
                        PlayerProjectQueue::TYPE_DETECT_RETURN,
                        PlayerProjectQueue::TYPE_GUILDBASE_RETURN,
                        PlayerProjectQueue::TYPE_GUILDWAREHOUSE_RETURN,
                        PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHRETURN,
                        PlayerProjectQueue::TYPE_GUILDTOWER_RETURN,
                        PlayerProjectQueue::TYPE_GUILDCOLLECT_RETURN,
                        PlayerProjectQueue::TYPE_ATTACKBASE_RETURN,
                        //PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE,
                        //PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST,
                        PlayerProjectQueue::TYPE_KINGTOWN_RETURN,
                        PlayerProjectQueue::TYPE_FETCHITEM_RETURN,
                    ],
                    //'worker_num' => 16
                ],
            ];
    /**
     * main方法
     */
    public function mainAction(){
        echo __METHOD__.PHP_EOL;
    }
    /**
     * 运行入口方法
     * @param  array  $params 
     */
    public function runAction(array $params){
		global $config;
        $config->redis->dispatcherFlag = true;
        $swoolePort = $config->swoole->port;
        //swoole_process::daemon(true);//设置为守护进程
        $method = $params[0];
        $this->method = $method;

        if(isset($this->methodArr[$method]['worker_num'])) {//优先使用自定义work_num
            $this->maxWorkerNum = $this->methodArr[$method]['worker_num'];
        }

        $processName = "php_swoole_dispatcher_task_{$method}_father_".$swoolePort;
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(empty($processExists)){

            cli_set_process_title($processName);//set process name
            $self   = new self;
            if(method_exists(__CLASS__, $method)) {
                $this->newSwooleProcess([$self, $method]);//创建子进程-执行任务
                //回收子进程-worker进程
                while(true){
                    if($this->maxRunTimes<=0 /* || self::db()->exists('restart_'.$method)*/) {//父进程不在生成子进程，启动守护进程
                        cli_set_process_title("php_swoole_dispatcher_end_task_{$method}_father_".$swoolePort);
						// self::db()->del('restart_'.$method);
						// (new PlayerCommonLog)->add(0, ['type'=>'重启Disp', 'method'=>$method]);
                        exec($config['daemonDispPath'].' '.$method);
                        while($this->currentWorkerNum>0){//回收子进程
                            $_workerProcess = swoole_process::wait();
                            if($_workerProcess) {
                                $pid = $_workerProcess['pid'];
                                log4cli("[INFO] _Worker Exit, PID=" . $pid);
                                $this->currentWorkerNum--;
                            }
                            if($this->currentWorkerNum==0) {
                                exit;
                            }
                        }
                        exit;
                    } else {
                        $workerProcess = swoole_process::wait();
                        if($workerProcess) {
                            $pid = $workerProcess['pid'];
                            log4cli("[INFO] Worker Exit, PID=" . $pid);
                            $this->currentWorkerNum--;
                            log4cli('-------maxRunTimes------'.$method);
                            log4cli($this->maxRunTimes);
                            $this->newSwooleProcess([$self, $method]);//创建子进程-执行任务
                        }
                    }
                }
            } else {
                log4cli("[INFO]not exists shell method");
            }
        } else {
            log4cli("[INFO]shell exists.");
        }
		exit;
    }
    /**
     * 强制关闭当前父进程
     * @return [type] [description]
     */
    private function forceClose(){
        global $config;
        $method = $this->method;
        cli_set_process_title("php_swoole_dispatcher_end_task_{$method}_father");
        exec($config['daemonDispPath'].' '.$method);
        exit;
    }
    public function db($pool=CACHEDB_PLAYER){
        $index = Cache::dbname2id($pool);
        global $config;
        if($this->redis) {
            $this->redis->close();
        }
		a:
        $r = $this->redis = getnewredisconnect('', $config);
        if($r===false) {
            $this->forceClose();
        }
        try{
            if(!$this->redis->select($index)){
				throw new Exception('');
			}
        } catch (Exception $e) {
            echo '[2]reconnection------------';
            try{
                $rr = $this->redis = getnewredisconnect('', $config);
                if($rr===false) {
                    $this->forceClose();
                }
                if(!$this->redis->select($index)){
					throw new Exception('');
				}
                echo '[2]reconnection okkkkkk------------';
            } catch (Exception $e) {
                echo '[2]22222222222222222222222';

				$this->redis = false;
				sleep(2);
				goto a;
            }
        }
        return $this->redis;
    }
    
    /**
     * 主进程-创建子进程
     * @param  string or array $callback callback function
     */
    public function newSwooleProcess($callback){
        global $config;
        //redis 开启share模式
        global $redisSharedFlag;
        $redisSharedFlag = true;

        $method = $callback[1];

        Cache::$tmpSwitch = false;
        while($this->maxRunTimes>0 && $this->currentWorkerNum<$this->maxWorkerNum){
            try {
				// if(self::db()->exists('restart_'.$method)){
				// 	break;
				// }
                $ppq = $this->processMysql($method);
                if($ppq) {
                    $process                           = new swoole_process($callback, false, true);
                    $process->write(json_encode(['ppq' =>$ppq]));
                    $pid                               = $process->start();
                    // $this->workers[$pid]               = $process;
                    $this->currentWorkerNum++;
					$this->maxRunTimes--;
                    echo $this->maxRunTimes." Worker[{$pid}] created![queueID]=" . $ppq['id'] . '!';
                } else {
					// $this->maxRunTimes--;
                    //立即回收
                    $workerProcess = swoole_process::wait();
                    if($workerProcess) {
                        $pid = $workerProcess['pid'];
                        log4cli("[INFO] Worker Exit, PID=" . $pid);
                        $this->currentWorkerNum--;
                        log4cli('-------maxRunTimes------'.$method);
                        log4cli($this->maxRunTimes);

                    }
                    sleep(1);
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        
    }
    /**
     * 将xy塞入redis中
     * @param  string $method battle resource等
     * @param  int $x      pos x
     * @param  int $y      pos y
     */
    public function cacheAddXY($cache, $x, $y){
        if($x&&$y) {
            $key = $x."_".$y;
            if($cache->setnx($key, ['x'=>$x, 'y'=>$y])) {
                $cache->setTimeout($key, 10);//过期
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
    /**
     * 将xy移出redis
     * @param  string $method battle等
     * @param  int $x      pos x
     * @param  int $y      pos y
     */
    public function cacheRemoveXY($cache=null, $x, $y){
        if(is_null($cache)) $cache = $this->db('dispatcher');
        $cache->del($x."_".$y);
    }
    /**
     * 处理mysql分发
     * @return array {PlayerProjectQueue}
     */
    public function processMysql($method){
        global $config;
        $dispatcher = $this->db('dispatcher');
        $re         = [];
        $type       = $this->methodArr[$method]['type'];//战斗,资源等对应的type
        $sql        = $sqlxy = '';
        $typeSql    = '';
        
        $cacheXY    = $dispatcher->keys('*');
        
        $prefix     = $config->redis->prefix;
        $len        = strlen($prefix);
        foreach($cacheXY as $k=>$v) {
            $xy    = $dispatcher->get(substr($v, $len));
            $_x    = $xy['x'];
            $_y    = $xy['y'];
            if(!empty($_x)&&!empty($_y)) {
                $sqlxy .= " and (to_x!={$_x} or to_y!={$_y})";      
            }
        }

        if(is_array($type)) {
            if(count($type)==1) {
                $typeSql = "`type`=".current($type);
            } else {
                $typeSql = '(`type`=';
                $typeSql .= join(' or `type`=', $type) . ')';
            }
        } else {
            $typeSql = "type={$type}";
        }

        $sql = "select * from player_project_queue where `status`=1 and end_time!='0000-00-00 00:00:00' and {$typeSql} {$sqlxy} and end_time<=now() order by end_time asc limit 1";

        $ret = iquery($sql);
        if($ret) {
            $re = $ret[0];
            $tox = $re['to_x'];
            $toy = $re['to_y'];

            if($method=='readyGather') {
                $sql2 = "select * from player_project_queue where parent_queue_id={$re['id']} and status=1 and type=" . PlayerProjectQueue::TYPE_GATHER_GOTO . " and end_time<='{$re['end_time']}'";
                if(iquery($sql2)){
                    return [];
                }
            }
            if(!$this->cacheAddXY($dispatcher, $tox, $toy)) {//不满足条件的
                return [];
            }
        }
        return $re;
    }

    /**
     * 子进程-处理野外资源
     * @param  swoole_process $worker 
     */
    public function gotoCollection(swoole_process $worker) {
        $this->taskName = 'gotocollection';
        $ppq = $this->begin($worker);

        $QueueCollection = new QueueCollection;
        $QueueCollection->_goto($ppq);
    }
    public function doCollection(swoole_process $worker) {
        $this->taskName = 'docollection';
        $ppq = $this->begin($worker);
        
        $QueueCollection = new QueueCollection;
        $QueueCollection->_done($ppq);
    }
    public function gotoNpcBattle(swoole_process $worker) {
        $this->taskName = 'gotoNpcbattle';
        $ppq = $this->begin($worker);
        
        $QueueNpc = new QueueNpc;
        $QueueNpc->_npcBattle($ppq);
    }
    public function gotoCityBattle(swoole_process $worker) {
        $this->taskName = 'gotocitybattle';
        $ppq = $this->begin($worker);
        
        $QueueGather = new QueueGather;
        $QueueGather->_cityBattle($ppq);
    }
    public function gotoGather(swoole_process $worker) {
        $this->taskName = 'gotogather';
        $ppq = $this->begin($worker);
        
        $QueueGather = new QueueGather;
        $QueueGather->_goto($ppq);
    }
    public function readyGather(swoole_process $worker) {
        $this->taskName = 'readygather';
        $ppq = $this->begin($worker);
        
        $QueueGather = new QueueGather;
        $QueueGather->_ready($ppq);
    }
    public function battleGather(swoole_process $worker) {
        $this->taskName = 'battlegather';
        $ppq = $this->begin($worker);
        
        $QueueGather = new QueueGather;
        $QueueGather->_battle($ppq);
    }
    public function backMidGather(swoole_process $worker) {
        $this->taskName = 'backmidgather';
        $ppq = $this->begin($worker);
        
        $QueueGather = new QueueGather;
        $QueueGather->_backMid($ppq);
    }
    
    public function battleBase(swoole_process $worker) {
        $this->taskName = 'battlebase';
        $ppq = $this->begin($worker);
        
        $QueueGather = new QueueGather;
        $QueueGather->_baseBattle($ppq);
    }
    
    /**
     * //屯所-援军
     * @param  swoole_process $worker 
     * php cli.php dispatcher run gotoReinforce
     */
    public function gotoReinforce(swoole_process $worker){
        $this->taskName = 'gotoReinforce';
        $ppq = $this->begin($worker);
        
        (new PlayerHelp)->reinforce($ppq);//process logic here
    }
    /**
     * 哨塔侦查
     * @param  swoole_process $worker 
     * php cli.php dispatcher run gotoSpy
     */
    public function gotoSpy(swoole_process $worker){
        $this->taskName = 'gotoSpy';
        $ppq = $this->begin($worker);

        (new Player)->spy($ppq);//process logic here
    }
    /**
     * 去联盟堡垒,联盟仓库,箭塔,超级矿
     * @param  swoole_process $worker 
     */
    public function gotoGuildBuild(swoole_process $worker){
        $this->taskName = 'gotoGuildbase';
        $ppq = $this->begin($worker);
        
        (new Guild)->processGotoGuildBuild($ppq);//process logic here
    }
    public function gotoTown(swoole_process $worker){
        $this->taskName = 'gototown';
        $ppq = $this->begin($worker);
        
        $QueueKing = new QueueKing;
        $QueueKing->_gotoTown($ppq);
    }

    public function npcGotoTown(swoole_process $worker){
        $this->taskName = 'npcgototown';
        $ppq = $this->begin($worker);
        
        $QueueKing = new QueueKing;
        $QueueKing->_npcAttack($ppq);
    }
	
	public function hjnpcGotoBase(swoole_process $worker){
        $this->taskName = 'hjnpcGotoBase';
        $ppq = $this->begin($worker);
        
        $QueueHuangjin = new QueueHuangjin;
        $QueueHuangjin->_battle($ppq);
    }
    
    public function gotoFetchItem(swoole_process $worker){
        $this->taskName = 'gotofetchitem';
        $ppq = $this->begin($worker);
        
        $QueueFetchItem = new QueueFetchItem;
        $QueueFetchItem->_fetchitem($ppq);
    }
    
    public function back(swoole_process $worker){
        $this->taskName = 'back';
        $ppq = $this->begin($worker);
        
        $this->doBack($ppq);
    }
    
    public function home(swoole_process $worker){
        $this->taskName = 'home';
        $ppq = $this->begin($worker);
        
        $this->doHome($ppq);
    }
    
    /**
     * 处理召回
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
    public function doBack($ppq){
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
        Cache::lock($lockKey);
        $db = $this->di['db'];
        dbBegin($db);

        try {
            $PlayerProjectQueue = new PlayerProjectQueue;
            $Player = new Player;
            $ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

            //新建返回队列
            $player = $Player->getByPlayerId($ppq['player_id']);
            if(!$player)
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
            $extraData = array(
                'from_map_id' => $ppq['to_map_id'],
                'from_x' => $ppq['to_x'],
                'from_y' => $ppq['to_y'],
                'to_map_id' => $player['map_id'],
                'to_x' => $player['x'],
                'to_y' => $player['y'],
                'carry_gold' => $ppq['carry_gold'],
                'carry_food' => $ppq['carry_food'],
                'carry_wood' => $ppq['carry_wood'],
                'carry_stone' => $ppq['carry_stone'],
                'carry_iron' => $ppq['carry_iron'],
                'carry_soldier' => $ppq['carry_soldier'],
                'carry_item' => $ppq['carry_item'],
            );
            if(@$ppq['target_info']['backNow']){
                $needTime = 0;
            }else{
                switch($ppq['type']){
                    case PlayerProjectQueue::TYPE_COLLECT_ING://采集
                    case PlayerProjectQueue::TYPE_GUILDCOLLECT_ING:
                        $timeType = 1;
                    break;
                    case PlayerProjectQueue::TYPE_NPCBATTLE_GOTO://打怪
                        $timeType = 2;
                    break;
                    case PlayerProjectQueue::TYPE_GUILDWAREHOUSE_GOTO://仓库
                    case PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHGOTO://仓库
                    case PlayerProjectQueue::TYPE_GUILDWAREHOUSE_BUILD:
                        $timeType = 5;
                    break;
                    default:
                        $timeType = 3;
                }
                $needTime = PlayerProjectQueue::calculateMoveTime($ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], $timeType, $ppq['army_id']);
                if(!$needTime){
                    throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
                }
            }
            $backTypes = [
                PlayerProjectQueue::TYPE_COLLECT_ING            =>  PlayerProjectQueue::TYPE_COLLECT_RETURN,
                PlayerProjectQueue::TYPE_CITYASSIST_ING         =>  PlayerProjectQueue::TYPE_CITYASSIST_RETURN,
                PlayerProjectQueue::TYPE_GATHER_STAY            =>  PlayerProjectQueue::TYPE_GATHER_RETURN,
                PlayerProjectQueue::TYPE_GUILDBASE_BUILD        =>  PlayerProjectQueue::TYPE_GUILDBASE_RETURN,
                PlayerProjectQueue::TYPE_GUILDBASE_REPAIR       =>  PlayerProjectQueue::TYPE_GUILDBASE_RETURN,
                PlayerProjectQueue::TYPE_GUILDBASE_DEFEND       =>  PlayerProjectQueue::TYPE_GUILDBASE_RETURN,
                PlayerProjectQueue::TYPE_GUILDWAREHOUSE_BUILD   =>  PlayerProjectQueue::TYPE_GUILDWAREHOUSE_RETURN,
                PlayerProjectQueue::TYPE_GUILDTOWER_BUILD       =>  PlayerProjectQueue::TYPE_GUILDTOWER_RETURN,
                PlayerProjectQueue::TYPE_GUILDCOLLECT_BUILD     =>  PlayerProjectQueue::TYPE_GUILDCOLLECT_RETURN,
                PlayerProjectQueue::TYPE_GUILDCOLLECT_ING       =>  PlayerProjectQueue::TYPE_GUILDCOLLECT_RETURN,
                PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE       =>  PlayerProjectQueue::TYPE_KINGTOWN_RETURN,
                PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE=> PlayerProjectQueue::TYPE_KINGTOWN_RETURN,
            ];
            if(!@$backTypes[$ppq['type']]){
                $backType = PlayerProjectQueue::TYPE_RETURN;
            }else{
                $backType = $backTypes[$ppq['type']];
            }
            $PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $backType, $needTime, $ppq['army_id'], [], $extraData);
            ;
            
            finishQueue:
            //更新队列完成
            $PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
            
            //操作类型逻辑
            switch($ppq['type']){
                case PlayerProjectQueue::TYPE_GUILDBASE_BUILD://造堡垒
                case PlayerProjectQueue::TYPE_GUILDBASE_REPAIR://修堡垒
                case PlayerProjectQueue::TYPE_GUILDWAREHOUSE_BUILD://造仓库
                case PlayerProjectQueue::TYPE_GUILDTOWER_BUILD://造箭塔
                case PlayerProjectQueue::TYPE_GUILDCOLLECT_BUILD://造超级矿
                    //结算已经建造或修复值，并且更改结束时间
                    if(Map::findFirst($ppq['to_map_id'])) {//非拆除
                        $PlayerProjectQueue->calculateGuildBaseConstructValue($ppq, true);
                        $PlayerProjectQueue->updateGuildBaseEndTime($ppq, true);
                    }
                    break;
            }
            
            dbCommit($db);
            $err = 0;
            //$return = true;
        } catch (Exception $e) {
            list($err, $msg) = parseException($e);
            dbRollback($db);

            //清除缓存
            
            //$return = false;
        }
        $this->afterCommit();
        //解锁
        Cache::unlock($lockKey);
        
        echo $err."\r\n";
        return true;
    }
    
    /**
     * 处理到家
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
    public function doHome($ppq){
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
        Cache::lock($lockKey);
        $db = $this->di['db'];
        dbBegin($db);

        try {
            $PlayerProjectQueue = new PlayerProjectQueue;
            $Player = new Player;
            $ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];
            $memo = '';
            
            //操作类型逻辑
            switch($ppq['type']){
                case PlayerProjectQueue::TYPE_COLLECT_RETURN:
                case PlayerProjectQueue::TYPE_GUILDCOLLECT_RETURN:
                    if(!isset($ppq['target_info']['element_id'])){
                        break;
                    }
                    $memo = '采集';
                    //发送采集邮件
                    $PlayerMail = new PlayerMail;
                    $toPlayerIds = [$ppq['player_id']];
                    $type = PlayerMail::TYPE_COLLECTIONREPORT;
                    $title = '';
                    $msg = '';
                    $time = 0;
                    $data = [
                        'x'=>$ppq['from_x'], 
                        'y'=>$ppq['from_y'], 
                        'element_id'=>$ppq['target_info']['element_id'],
                        'resource'=>[
                            'wood'=>$ppq['carry_wood']-@$ppq['target_info']['rob_wood']*1,
                            'food'=>$ppq['carry_food']-@$ppq['target_info']['rob_food']*1,
                            'gold'=>$ppq['carry_gold']-@$ppq['target_info']['rob_gold']*1,
                            'stone'=>$ppq['carry_stone']-@$ppq['target_info']['rob_stone']*1,
                            'iron'=>$ppq['carry_iron']-@$ppq['target_info']['rob_iron']*1,
                        ], 
                        'drop'=>$ppq['carry_item'],
                    ];
                    $PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data);
                    /*$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
                    $PlayerTimeLimitMatch->updateScore($ppq['player_id'], 1, $ppq['carry_gold']);
                    $PlayerTimeLimitMatch->updateScore($ppq['player_id'], 2, $ppq['carry_food']);
                    $PlayerTimeLimitMatch->updateScore($ppq['player_id'], 3, $ppq['carry_wood']);
                    $PlayerTimeLimitMatch->updateScore($ppq['player_id'], 4, $ppq['carry_stone']);
                    $PlayerTimeLimitMatch->updateScore($ppq['player_id'], 5, $ppq['carry_iron']);*/
                break;
				case PlayerProjectQueue::TYPE_NPCBATTLE_RETURN:
					$memo = '打怪';
				break;
				case PlayerProjectQueue::TYPE_CITYBATTLE_RETURN:
					$memo = '攻城';
				break;
				case PlayerProjectQueue::TYPE_GATHER_RETURN:
					$memo = '集结攻城';
				break;
            }
            
            //增加资源
            if($ppq['carry_gold'] || $ppq['carry_food'] || $ppq['carry_wood'] || $ppq['carry_stone'] || $ppq['carry_iron']){
                $resource = array(
                    'gold'=>$ppq['carry_gold'],
                    'food'=>$ppq['carry_food'],
                    'wood'=>$ppq['carry_wood'],
                    'stone'=>$ppq['carry_stone'],
                    'iron'=>$ppq['carry_iron'],
                );
                if(!$Player->updateResource($ppq['player_id'], $resource)){
                    throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
                }
            }
            
            //处理伤兵
            if($ppq['carry_soldier']){
                $PlayerSoldierInjured = new PlayerSoldierInjured;
                $injureData = [];
                $injureNum = 0;
                foreach($ppq['carry_soldier'] as $_soldier => $_num){
                    $injureData[] = ["playerId" => $ppq['player_id'],"soldierId"  => $_soldier,"num"=> $_num];
                    if($_num){
                        $injureNum += $_num;
                    }
                }
                if($injureNum){
                    if(!$PlayerSoldierInjured->receive($injureData)){
                        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
                    }
                }
            }
            
            //处理道具
            if($ppq['carry_item']){
                $gainItems = array();
                foreach($ppq['carry_item'] as $_dropData){
                    list($_type, $_itemId, $_num) = $_dropData;
                    @$gainItems[$_type][$_itemId] += $_num;
                }
                (new Drop)->_gain($ppq['player_id'], $gainItems, $memo);
            }
            
            
            //设置army状态
            if($ppq['army_id']){
                $PlayerArmy = new PlayerArmy;
                $playerArmy = $PlayerArmy->getByArmyId($ppq['player_id'], $ppq['army_id']);
                if($playerArmy){
                    if(!$PlayerArmy->assign($playerArmy)->updateStatus(0)){
                        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
                    }
                }
                
                //设置武将状态
                $PlayerGeneral = new PlayerGeneral;
                $generalIds = $PlayerGeneral->getGeneralIdsByArmyId($ppq['player_id'], $ppq['army_id']);
                $PlayerGeneral->updateReturnByGeneralIds($ppq['player_id'], $generalIds);
            }
        
            //更新队列完成
            $PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
            
            //发送notice
            $this->sendNotice($ppq['player_id'], 'backHome');
			
			$pushId = (new PlayerPush)->add($ppq['player_id'], 4, 400010, []);
            
            dbCommit($db);
            $err = 0;
            //$return = true;
        } catch (Exception $e) {
            list($err, $msg) = parseException($e);
            dbRollback($db);

            //清除缓存
            
            //$return = false;
        }
        $this->afterCommit();
        //解锁
        Cache::unlock($lockKey);
        
        echo $err."\r\n";
        return true;
    }
	
    public function sendNotice($playerId, $type){
        socketSend(['Type'=>'queue', 'Data'=>['playerId'=>$playerId, 'msg'=>$type]]);
    }

    public function afterThing($ppq){
        $tox        = $ppq['to_x'];
        $toy        = $ppq['to_y'];
        $dispatcher = $this->db('dispatcher');
        $this->cacheRemoveXY($dispatcher, $ppq['to_x'], $ppq['to_y']);

        log4cli('exectime:'.(floor(microtime_float()*1000)-$this->execStartTime).'ms');
        Cache::close();
    }
	
	public function onshutdown($worker, $ppq){
		$this->afterThing($ppq);
		$worker->exit(1);
	}
    
    public function afterCommit(){
        foreach($this->getDI()->get('data')->datas as $_playerId => $_d){
            $_d = array_unique($_d);
            foreach($_d as $__d){
                Cache::delPlayer($_playerId, $__d);
            }
        }
    }   
}