<?php
/**
 * 任务调度器
 *
 * ```php
 * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php cross_dispatcher run battle
 * ```
 */
class CrossDispatcherTask extends \Phalcon\CLI\Task{
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
        $data                = json_decode($worker->read(), true);
        $ppq                 = $data['ppq'];

        if(!$ppq) {
            $worker->exit(0);
        }
        $name                = "php_swoole_crossdispatcher_task_".$this->taskName."_".$swoolePort.'_'.$ppq['id'];
        $worker->name($name);
		
		register_shutdown_function([$this, 'onshutdown'], $worker, $ppq);
        return $ppq;
    }
    /**
     * 映射关系结构
     * @var array
     */
    public $methodArr = [
                "gotoCityBattle"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_CITYBATTLE_GOTO]
                ],
				"attackDoor"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_ATTACKDOOR_GOTO]
                ],
				"gotoHammer"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_HAMMER_GOTO]
                ],
				/*"attackHammer"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_ATTACKHAMMER_GOTO]
                ],*/
				"gotoLadder"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_LADDER_GOTO]
                ],
				"doneLadder"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_LADDER_ING]
                ],
				"gotoCrossbow"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_CROSSBOW_GOTO]
                ],
				"gotoCatapult"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_CATAPULT_GOTO]
                ],
				"attackBase"    => [
                    'type'=>[CrossPlayerProjectQueue::TYPE_ATTACKBASE_GOTO]
                ],
				"back"              => [
                    'type'=>[
                        CrossPlayerProjectQueue::TYPE_HAMMER_ING,
						CrossPlayerProjectQueue::TYPE_CROSSBOW_ING,
						CrossPlayerProjectQueue::TYPE_CATAPULT_ING,
                    ],
                    //'worker_num' => 16
                ],
                "home"              => [
                    'type'=>[
                        CrossPlayerProjectQueue::TYPE_RETURN,
						CrossPlayerProjectQueue::TYPE_ATTACKDOOR_RETURN,
                        CrossPlayerProjectQueue::TYPE_CITYBATTLE_RETURN,
						CrossPlayerProjectQueue::TYPE_HAMMER_RETURN,
						//CrossPlayerProjectQueue::TYPE_ATTACKHAMMER_RETURN,
						CrossPlayerProjectQueue::TYPE_LADDER_RETURN,
						CrossPlayerProjectQueue::TYPE_CROSSBOW_RETURN,
						CrossPlayerProjectQueue::TYPE_CATAPULT_RETURN,
						CrossPlayerProjectQueue::TYPE_ATTACKBASE_RETURN,
                        CrossPlayerProjectQueue::TYPE_CITYSPY_RETURN,
                        CrossPlayerProjectQueue::TYPE_CATAPULTSPY_RETURN,
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

        $processName = "php_swoole_crossdispatcher_task_{$method}_father_".$swoolePort;
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(empty($processExists)){

            cli_set_process_title($processName);//set process name
            $self   = new self;
            if(method_exists(__CLASS__, $method)) {
                $this->newSwooleProcess([$self, $method]);//创建子进程-执行任务
                //回收子进程-worker进程
                while(true){
                    if($this->maxRunTimes<=0 /* || self::db()->exists('restart_'.$method)*/) {//父进程不在生成子进程，启动守护进程
                        cli_set_process_title("php_swoole_crossdispatcher_end_task_{$method}_father_".$swoolePort);
						// self::db()->del('restart_'.$method);
						// (new PlayerCommonLog)->add(0, ['type'=>'重启Disp', 'method'=>$method]);
                        exec($config['daemonCrossDispPath'].' '.$method);
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
                            //log4cli("[INFO] Worker Exit, PID=" . $pid);
                            $this->currentWorkerNum--;
                            //log4cli('-------maxRunTimes------'.$method);
                            //log4cli($this->maxRunTimes);
							log4cli("[INFO1] Worker Exit, PID=" . $pid . ', currentWorkerNum=' . $this->currentWorkerNum . ', maxRunTimes=' . $this->maxRunTimes);
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
        cli_set_process_title("php_swoole_crossdispatcher_end_task_{$method}_father");
        exec($config['daemonCrossDispPath'].' '.$method);
        exit;
    }
    public function db($pool=CACHEDB_PLAYER){
        $index = Cache::dbname2id($pool, 'Cross');
		global $config;
        if($this->redis) {
            $this->redis->close();
        }
		a:
        $r = $this->redis = getnewredisconnect('Cross', $config);
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
                $rr = $this->redis = getnewredisconnect('Cross', $config);
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
                    //echo $this->maxRunTimes." Worker[{$pid}] created![queueID]=" . $ppq['id'] . '!';
					log4cli("[INFO0] Worker CREATED, PID=" . $pid . ', currentWorkerNum=' . $this->currentWorkerNum . ', maxRunTimes=' . $this->maxRunTimes);
                } else {
					// $this->maxRunTimes--;
                    //立即回收
                    $workerProcess = swoole_process::wait();
                    if($workerProcess) {
                        $pid = $workerProcess['pid'];
                        //log4cli("[INFO] Worker Exit, PID=" . $pid);
                        $this->currentWorkerNum--;
                        //log4cli('-------maxRunTimes------'.$method);
                        //log4cli($this->maxRunTimes);
						log4cli("[INFO2] Worker Exit, PID=" . $pid . ', currentWorkerNum=' . $this->currentWorkerNum . ', maxRunTimes=' . $this->maxRunTimes);
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
    public function cacheAddXY($cache, $battleId, $x, $y){
        if($x&&$y) {
            $key = $battleId . '_' . $x."_".$y;
            if($cache->setnx($key, ['battle'=>$battleId, 'x'=>$x, 'y'=>$y])) {
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
    public function cacheRemoveXY($cache=null, $battleId, $x, $y){
        if(is_null($cache)) $cache = $this->db('dispatcher');
        $cache->del($battleId .'_' . $x."_".$y);
    }
    /**
     * 处理mysql分发
     * @return array {PlayerProjectQueue}
     */
    public function processMysql($method){
        global $config;
        $re         = [];
        $type       = $this->methodArr[$method]['type'];//战斗,资源等对应的type
        $sql        = $sqlxy = '';
        $typeSql    = '';
//        if($method!='execTask') {
            $dispatcher = $this->db('dispatcher');
            $cacheXY = $dispatcher->keys('*');

            $prefix = $config->redis->prefix;
            $len    = strlen($prefix);
            foreach ($cacheXY as $k => $v) {
                $xy      = $dispatcher->get(substr($v, $len));
                $_battle = $xy['battle'];
                $_x      = $xy['x'];
                $_y      = $xy['y'];
                if (!empty($_x) && !empty($_y)) {
                    $sqlxy .= " and (battle_id!={$_battle} or to_x!={$_x} or to_y!={$_y})";
                }
            }

            if (is_array($type)) {
                if (count($type) == 1) {
                    $typeSql = "`type`=" . current($type);
                } else {
                    $typeSql = '(`type`=';
                    $typeSql .= join(' or `type`=', $type) . ')';
                }
            } else {
                $typeSql = "type={$type}";
            }
//        }
        $sql = "select * from cross_player_project_queue where `status`=1 and end_time!='0000-00-00 00:00:00' and {$typeSql} {$sqlxy} and end_time<=now() order by end_time asc limit 1";

        $ret = iquery($sql, false, false, 'cross');
        if($ret) {
            $re = $ret[0];
			$battleId = $re['battle_id'];
            $tox = $re['to_x'];
            $toy = $re['to_y'];

            if(!$this->cacheAddXY($dispatcher, $battleId, $tox, $toy)) {//不满足条件的
                return [];
            }
        }
        return $re;
    }

    public function gotoCityBattle(swoole_process $worker) {
        $this->taskName = 'gotocitybattle';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_cityBattle($ppq);
    }
	
	public function attackDoor(swoole_process $worker) {
        $this->taskName = 'attackDoor';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_doorBattle($ppq);
    }
	
	public function gotoHammer(swoole_process $worker) {
        $this->taskName = 'gotoHammer';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_gotoHammer($ppq);
    }
	
	public function attackHammer(swoole_process $worker) {
        $this->taskName = 'attackHammer';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_hammerBattle($ppq);
    }
	
	public function gotoLadder(swoole_process $worker) {
        $this->taskName = 'gotoLadder';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_gotoLadder($ppq);
    }
	
	public function doneLadder(swoole_process $worker) {
        $this->taskName = 'doneLadder';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_doneLadder($ppq);
    }
	
	public function gotoCrossbow(swoole_process $worker) {
        $this->taskName = 'gotoCrossbow';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_gotoCrossbow($ppq);
    }
	
	public function gotoCatapult(swoole_process $worker) {
        $this->taskName = 'gotoCatapult';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_gotoCatapult($ppq);
    }
	
	public function attackBase(swoole_process $worker) {
        $this->taskName = 'attackBase';
        $ppq = $this->begin($worker);
        
        $QueueCross = new QueueCross;
        $QueueCross->_baseBattle($ppq);
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
        $db = $this->di['db_cross_server'];
        dbBegin($db);

        try {
			$battleId = $ppq['battle_id'];
            $PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
            $Player = new CrossPlayer;
			$Player->battleId = $battleId;
            $ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

            //新建返回队列
            $player = $Player->getByPlayerId($ppq['player_id']);
            if(!$player)
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
            $extraData = array(
				'area' =>$ppq['area'],
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
			switch($ppq['type']){
				case CrossPlayerProjectQueue::TYPE_HAMMER_ING:
				case CrossPlayerProjectQueue::TYPE_CROSSBOW_ING:
				case CrossPlayerProjectQueue::TYPE_CATAPULT_ING:
					//判断是否为最后一个撤离队伍
					$condition = ['type='.$ppq['type'].' and battle_id='.$battleId.' and area='.$ppq['area'].' and to_map_id='.$ppq['to_map_id'].' and status=1'];
					$ppqs = CrossPlayerProjectQueue::find($condition)->toArray();
					if(count($ppqs) == 1 && $ppqs[0]['id'] == $ppq['id']){
						$Map = new CrossMap;
						$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
						if($map){
							if(!$Map->alter($map['id'], ['guild_id'=>0, 'player_id'=>0])){
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							}
						}
					}
					//攻城锤人数减
					$desc = [
						CrossPlayerProjectQueue::TYPE_HAMMER_ING=>'攻城锤',
						CrossPlayerProjectQueue::TYPE_CROSSBOW_ING=>'床弩',
						CrossPlayerProjectQueue::TYPE_CATAPULT_ING=>'投石车',
					];
					(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], $desc[$ppq['type']].'撤离[queueId='.$ppq['id'].']');
				break;
				default:
			}
            if(@$ppq['target_info']['backNow']){
                $needTime = 0;
            }else{
				$timeType = 3;
                $needTime = CrossPlayerProjectQueue::calculateMoveTime($battleId, $ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], $timeType, $ppq['army_id']);
                if(!$needTime){
                    throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
                }
            }
            if(!@$PlayerProjectQueue->stayTypes[$ppq['type']]){
                $backType = CrossPlayerProjectQueue::TYPE_RETURN;
            }else{
                $backType = $PlayerProjectQueue->stayTypes[$ppq['type']];
            }
            $PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $backType, $needTime, $ppq['army_id'], [], $extraData);
            
            finishQueue:
            //更新队列完成
            $PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
            //操作类型逻辑
            switch($ppq['type']){
				case CrossPlayerProjectQueue::TYPE_HAMMER_ING:
					$Map = new CrossMap;
					$Map->alter($ppq['to_map_id'], ['player_num'=>'player_num-1']);
				break;
            }
			
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10668); //比赛已经结束
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
        
        //echo $err."\r\n";
		log4cli('[RET][queueId='.$ppq['id'].']'.$err);
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
        $db = $this->di['db_cross_server'];
        dbBegin($db);

        try {
			$battleId = $ppq['battle_id'];
            $PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
            $Player = new Player;
			$Player->battleId = $battleId;
            $ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];
            $memo = '';
            
            //操作类型逻辑
            switch($ppq['type']){
            }
            
            //设置army状态
            if($ppq['army_id']){
                $PlayerArmy = new CrossPlayerArmy;
				$PlayerArmy->battleId = $battleId;
                $playerArmy = $PlayerArmy->getByArmyId($ppq['player_id'], $ppq['army_id']);
                if($playerArmy){
                    if(!$PlayerArmy->assign($playerArmy)->updateStatus(0)){
                        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
                    }
                }
                
                //设置武将状态
                $PlayerGeneral = new CrossPlayerGeneral;
				$PlayerGeneral->battleId = $battleId;
                $generalIds = $PlayerGeneral->getGeneralIdsByArmyId($ppq['player_id'], $ppq['army_id']);
                $PlayerGeneral->updateReturnByGeneralIds($ppq['player_id'], $generalIds);
            }
        
            //更新队列完成
            $PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
            
            //发送notice
            $this->sendNotice($ppq['player_id'], 'backHome');
			
			//$pushId = (new PlayerPush)->add($ppq['player_id'], 4, 400010, []);
            
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10669); //比赛已经结束
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
        
        //echo $err."\r\n";
		log4cli('[RET][queueId='.$ppq['id'].']'.$err);
        return true;
    }
    public function sendNotice($playerId, $type){
		if(!is_array($playerId)){
			$playerId = [$playerId];
		}
		foreach($playerId as $_playerId){
			$serverId = CrossPlayer::parsePlayerId($_playerId)['server_id'];
			if($serverId)
				crossSocketSend($serverId, ['Type'=>'queue', 'Data'=>['playerId'=>$_playerId, 'msg'=>$type]]);
		}
    }

    public function afterThing($worker, $ppq){
        $dispatcher = $this->db('dispatcher');
        $this->cacheRemoveXY($dispatcher, $ppq['battle_id'], $ppq['to_x'], $ppq['to_y']);

        log4cli("Worker Time, PID=" . $worker->pid . '[queueId='.$ppq['id'].']exectime:'.(floor(microtime_float()*1000)-$this->execStartTime).'ms');
        Cache::close();
    }
	
	public function onshutdown($worker, $ppq){
		$this->afterThing($worker, $ppq);
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