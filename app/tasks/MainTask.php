<?php
class MainTask extends \Phalcon\CLI\Task {
    private $map = null;
    /**
     * 模拟客户端请求
     */
    public function simulateClientRequestAction(){
            //post参数
            $data = ['combo'=>[
                ['url'=>'king/getinfo','field'=>[]],
                ['url'=>'guild/comboguildmemberinfo','field'=>[]],
                ['url'=>'lottery/checkplayerlotteryinfo','field'=>[]],
                ['url'=>'limit_match/showlimitmatch','field'=>[]],
                ['url'=>'player/getbuff','field'=>[]],
            ]];
            //请求链接url
            $url  = 'common/combo';
            $uuid = '1459665_dsuc';
            $re   = simulateClientPostRequest($uuid, $url, $data);
            dump($re, 1);
            exit;

    }

    /**
     * 模拟客户端请求，传参数
     * @param $argv
     */
    public function requestAction($argv=[]){
        if(count($argv)<3) {
            echo "需传入 data uuid url三个参数，用法如下\n";
            echo <<<EXAMPLE
 1>
 php cli.php main request data="php['a'=>'A','b'=>'B','combo'=>[ \
                ['url'=>'king/getinfo','field'=>[]],\
                ['url'=>'guild/comboguildmemberinfo','field'=>[]],\
                ['url'=>'lottery/checkplayerlotteryinfo','field'=>[]],\
                ['url'=>'limit_match/showlimitmatch','field'=>[]],\
                ['url'=>'player/getbuff','field'=>[]],\
            ]];" uuid=1459665_dsuc url=common/combo
 2>
 php cli.php main request data="" uuid=1459665_dsuc url=king/getinfo
EXAMPLE;
            echo PHP_EOL;
            exit;
        }
        $args = [];
        foreach($argv as $v) {
            $vv    = strpos($v, '=');
            $key   = substr($v, 0, $vv);
            $value = substr($v, $vv + 1);
            $isPhp = strpos($value, 'php') !== false;
            if($isPhp) {
                $value = substr($value, 3);
                eval('$value='.$value.';');
            }
            $args[$key] = $value;

        }

        extract($args);
        if(empty($data)) $data = [];
        $re   = simulateClientPostRequest($uuid, $url, $data);
        dump($re, 1);
        exit;

    }
	/**
	 * 安全清除cache脚本
	 * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php main clearDictAndPlayerCache
	 */
	public function clearDictAndPlayerCacheAction(){
		global $config;
		$redisIndex = $config->redis->index->toArray();
		$needClearIndex = ['cache', 'static', 'bufftemp', 'login_server'];
		foreach($redisIndex as $k=>$v) {
			if(in_array($k, $needClearIndex)) {
				echo "Clear cache[{$k}={$v}]: ";
				Cache::db($k)->flushDB();
				echo "ok!\n";
			}
		}
	}
    /**
     * 开服脚本
     * @return [type] [description]
     */
    public function mainAction() {
        global $redisSharedFlag;
        $redisSharedFlag = true;

		echo "玩家号自增\r\n";
		$this->alterPlayerAutoIncrement();
		echo "活动\r\n";
		$this->newActivity();

		//王战初始化
		echo "王战初始化\r\n";
		$this->initKing();
						
		$this->formatMap();

		echo "限时比赛\r\n";
        $this->console->handle(['task' => 'time_limit_match_list','action' => 'start']);//限时比赛
		
		//生成资源田和怪物
		// echo "生成资源田\r\n";
		// $this->console->handle(['task' => 'map_element','action' => 'main','1']);
		// echo "生成怪物\r\n";
		// $this->console->handle(['task' => 'map_element','action' => 'main','2']);
		
		//初始化充值礼包
		echo "初始化充值礼包\r\n";
		$this->console->handle(['task' => 'gift','action' => 'refresh']);
		
		//最后一步，刷新缓存
		echo "刷新缓存\r\n";
		Cache::clearAllCache();
    }
	/**
	 * 更改player表的auto_increments
	 */
	public function alterPlayerAutoIncrement(){
        global $config;
		$serverId        = $config->server_id;
		$serverStartTime = strtotime($config->server_start_time);
		$n = $serverId * 1000000 + 1;
		$db = $this->di['db'];
		
		//case a 更改auto_increment
		$sql1 = "alter table player AUTO_INCREMENT={$n}";
		$db->execute($sql1);
		
		/*
		$playerTables = [];
		$tables = (new ModelBase)->sqlGet('show tables');
		//过滤字典表
		foreach($tables as $_t){
			$_t['Tables_in_sanguo2'] = strtolower($_t['Tables_in_sanguo2']);
			if(substr($_t['Tables_in_sanguo2'], 0, 1) != '_' && 'player' != $_t['Tables_in_sanguo2']){
				$db->execute("alter table `".$_t['Tables_in_sanguo2']."` AUTO_INCREMENT={$serverId}000000000");
			}
		}
		*/

		$sql4 = "INSERT INTO `configure` (`key`, `value`) VALUES ('server_start_time', {$serverStartTime}); ";
		$db->execute($sql4);
		return $serverStartTime;
		// print_r($re);
	}

	public function newActivity(){
		global $config;
		$serverStartTime = strtotime($config->server_start_time);
		
		$AllianceMatchList = new AllianceMatchList;
    	$ActivityConfigure = new ActivityConfigure;

    	$data1 = array('type'=>1, 'start_time'=>date("Y-m-d 08:00:00", $serverStartTime+3600*24*11), 'end_time'=>date("Y-m-d 21:59:59", $serverStartTime+3600*24*12));
    	$AllianceMatchList->addNew($data1);

    	$data3 = array('type'=>3, 'start_time'=>date("Y-m-d 08:00:00", $serverStartTime+3600*24*13), 'end_time'=>date("Y-m-d 21:59:59", $serverStartTime+3600*24*13));
        $AllianceMatchList->addNew($data3);

        $data4 = array('type'=>4, 'start_time'=>date("Y-m-d 08:00:00", $serverStartTime+3600*24*14), 'end_time'=>date("Y-m-d 21:59:59", $serverStartTime+3600*24*14));
        $AllianceMatchList->addNew($data4);

        $data2 = array('type'=>2, 'start_time'=>date("Y-m-d 08:00:00", $serverStartTime+3600*24*15), 'end_time'=>date("Y-m-d 21:59:59", $serverStartTime+3600*24*15));
    	$AllianceMatchList->addNew($data2);

    	$ActivityConfigure->openActivity(1003, date("Y-m-d 00:00:00", $serverStartTime+3600*24*11), date("Y-m-d 08:00:00", $serverStartTime+3600*24*11), date("Y-m-d 21:59:59", $serverStartTime+3600*24*15), []);

	}


	public function formatMap(){
        log4task('formatMap开始。。。');
        $startTime = microtime_float();
		$sqlList = $this->getSqlStr();
		global $config;
		$mysqli = @new mysqli($config->database->host, $config->database->username, $config->database->password, $config->database->dbname);
		if(mysqli_connect_errno()){
		    echo "ERROR:".mysqli_connect_error();
		    exit;
		    return false;
		}
		$k = 0;
		foreach($sqlList as $v) {
            $k++;
		    $re = $mysqli->query($v);
		    if(!$re){
		        echo "ERROR:".$mysqli->error.":".$v;
		        break;
		    }
		    if($k%5000==0) {
                echo ".";
            }
		}
		Cache::db('map')->flushDB();
        $subTime = microtime_float() - $startTime;
        echo "ok!耗时：". $subTime,PHP_EOL;
        log4task('formatMap结束');
	}

	public function initKing(){
		//获取开服时间
		$Configure = new Configure;
		$startTimestamp = $Configure->getValueByKey('server_start_time');
		$Map = new Map;
		$King = new King;
		$d = 17;
		/*$King->guild_id    = 0;
		$King->player_id   = 0;
		$King->start_time = date("Y-m-d 19:00:00", $startTimestamp+$d*24*3600);
		$King->end_time = date("Y-m-d 21:00:00", $startTimestamp+$d*24*3600);
		$King->create_time = date("Y-m-d H:i:s");
		$King->update_time = date("Y-m-d H:i:s");
		$King->rowversion = uniqid();
		$King->save();*/
		$Map->addNew(['x'=>619, 'y'=>619, 'map_element_id'=>1601, 'map_element_origin_id'=>16, 'map_element_level'=>1, 'status'=>1]);
		$Map->addNew(['x'=>626, 'y'=>626, 'map_element_id'=>1602, 'map_element_origin_id'=>18, 'map_element_level'=>1, 'status'=>1]);
		$Map->addNew(['x'=>626, 'y'=>610, 'map_element_id'=>1603, 'map_element_origin_id'=>19, 'map_element_level'=>1, 'status'=>1]);
		$Map->addNew(['x'=>610, 'y'=>610, 'map_element_id'=>1604, 'map_element_origin_id'=>18, 'map_element_level'=>1, 'status'=>1]);
		$Map->addNew(['x'=>610, 'y'=>626, 'map_element_id'=>1605, 'map_element_origin_id'=>19, 'map_element_level'=>1, 'status'=>1]);
		$this->console->handle(['task' => 'king','action' => 'init']);
		$king = $King->findFirst();
		$king->start_time = date("Y-m-d 19:00:00", $startTimestamp+$d*24*3600);
		$king->end_time = date("Y-m-d 20:00:00", $startTimestamp+$d*24*3600);
		$king->save();
	}

    /**
     * 检测是否可容下一般地图随机元素
     * @param  [type] $position [description]
     * @return [type]          [description]
     */
    public function checkRandElementPosition($position){
        if($this->map->checkCenterPosition($position[0], $position[1])){
            return false;
        }
        for($x=$position[0]; $x<=$position[0]+1; $x++){
            for($y=$position[1]; $y<=$position[1]+1; $y++){
                $tmpRe = $this->map->sqlGet("select map_element_origin_id from map where x={$x} and y={$y} limit 1;");
                if(empty($tmpRe)) {
                    continue;
                } else {
                    $tmpRe = $tmpRe[0];
                }
                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在大型单位
                    if(!empty($tmpRe) && in_array($tmpRe['cross_map_element_id'], Map::$largeElementIdList)){
                        return false;
                    }
                }else{//中心，不能存在任何
                    if(!empty($tmpRe)){
                        return false;
                    }
                }
            }
        }
        return true;
    }

	function getSqlStr(){
		$Map = new Map;
    	$i = 0;
    	$j = 0;
    	$xyArr = [];
    	while($i<50000 && $j<500000){
    		$x = mt_rand(12, 1223);
    		$y = mt_rand(12, 1223);
//    		if(in_array($x*10000+$y, $xyArr)){
//				continue;
//			}
			
			$j++;
    		$success = $this->checkRandElementPosition([$x, $y]);
    		
    		if($success){
//				$xyArr[] = $x*10000+$y;
				$blockId = $Map->calcBlockByXy($x, $y);
				$seed = lcg_value();
				if($seed<0.1){
					$orgId = 14;
					$level = 1;
					$id = 1401;
					$res = 0;
				}elseif($seed<0.2){
					$orgId = 14;
					$level = 2;
					$id = 1402;
					$res = 0;
				}elseif($seed<0.3){
					$orgId = 14;
					$level = 3;
					$id = 1403;
					$res = 0;
				}elseif($seed<0.4){
					$orgId = 14;
					$level = 4;
					$id = 1404;
					$res = 0;
				}elseif($seed<0.5){
					$orgId = 14;
					$level = 5;
					$id = 1405;
					$res = 0;
				}elseif($seed<0.75){
					$orgId = 9;
					$level = 1;
					$id = 901;
					$res = 21000;
				}else{
					$orgId = 10;
					$level = 1;
					$id = 1001;
					$res = 21000;
				}
				

				$sqlStr = "insert into Map (`x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`,`resource`, `create_time`) values ({$x},{$y},{$blockId},{$id},{$orgId},{$level},{$res},now());";
				$i++;
				yield $sqlStr;
			}
    	}
	}

	public function createMongoIndex(){
		$mongodb = $this->di['mongo'];
		$collection = $mongodb->selectCollection('player_common_log');
		$collection->ensureIndex(["player_id"=>1]);
	}
	
	public function startServerAction(){
        $this->map = new Map;
		set_time_limit(0);
		global $config;
		echo "当前的server_id为 " . color($config->server_id, 'red', 1) . ", 请确认app.ini里的server_id已经正确更改过了?" . color("[输入yes继续/输入no退出]", 'brown'). PHP_EOL;		
		$remFlag = trim(fgets(STDIN));
		if($remFlag=='no') {
			echo "还好客官记起了,这就给您退了开服程序\n";
			exit;
		} elseif($remFlag=='yes') {
			echo "您记性真好!\n";
		} else {
			echo "输入非法:{$remFlag},开服不能!\n";
			exit;
		}

		//1 执行sql清空数据
		echo "开始清空表\r\n";
		$sqlPath = APP_PATH.'/app/tools/ResetAllPlayerData_BackupFirst.sql';
		$f = file_get_contents($sqlPath);
		$fs = explode(';', $f);
		$ModelBase = new ModelBase;
		foreach($fs as $_f){
			if(trim($_f) == '') continue;
			echo $_f."\r\n";
			$ModelBase->sqlExec($_f);
		}
		
		Cache::clearAllCache();
		
		//2 map表数据添加
		echo "map表数据添加\r\n";
		include(APP_PATH.'/app/tools/map_generate/map_shell.php');
		// system("php ".str_replace(['\\', 'tasks'], ['/', ''], __DIR__)."tools/map_generate/map_shell.php", $ret);
		
		//3 player_buff表从buff表导入字段
		echo "player_buff表从buff表导入字段\r\n";
		$re = (new Buff)->dicGetAll();
        $re = Set::sort($re, "{n}.id", "asc");

        $re1= (new PlayerBuff)->sqlGet('DESC `player_buff`');
        $re1 = Set::combine($re1, '{n}.Field', '{n}.Type');
        //$sql2 = '';            
        foreach($re as $k=>$v) {
            if($v['name'] && !array_key_exists($v['name'], $re1)) {
                $sql2 = "ALTER TABLE  `player_buff` ADD  `{$v['name']}` INT( 11 ) DEFAULT  '{$v['starting_num']}' COMMENT '{$v['desc1']}'";
				$ModelBase->sqlExec($sql2);
            }
        }
		
		//创建mongo索引
		//$this->createMongoIndex();
		
		//执行初始化脚本
		echo "执行初始化脚本\r\n";
		$this->mainAction();
	}
	public function crossMapAction(){
        log4task('生成跨服战地图');
        include(APP_PATH.'/app/tools/map_generate/cross_map/cross_map_shell.php');
    }

    public function correctPlayerBuildAndSoldierAction(){
        $Player = new Player;
        $idList = $Player->find(['columns' => 'id'])->toArray();
        foreach($idList as $id){
            $Player->correctPlayerBuildAndSoldier($id['id']);
            echo "修正完成，玩家id：".$id['id']."\n";
        }
    }
    /**
     * 每3秒获取一次联盟聊天
     * php cli.php main fetchCampChatMsg
     */
    public function fetchCampChatMsgAction(){
        set_time_limit(0);
        global $config;
        cli_set_process_title('php_swoole_camp_chat_msg_'.$config->swoole->port);//set process name
        $interval = 3000;
        $allCamp  = (new CountryCampList)->dicGetAllId();
        $ChatUtil = new ChatUtil;
        swoole_timer_tick($interval, function() use ($ChatUtil, $allCamp){
            foreach($allCamp as $campId) {
                try{
                    $campMsg = $ChatUtil->getCampMsg($campId, true);
                    if(!empty($campMsg)) {
                        log4cli(json_encode($campMsg, JSON_UNESCAPED_UNICODE), 1);
                        $data = [
                            'Type'=> 'camp_chat_send',
                            'Data'=> ['content'=>['data'=>$campMsg], 'camp_id'=>$campId]
                        ];
                        socketSend($data);
                    }
                } catch(Exception $e) {
                    log4task("Exception:".dump($e, 1));
                }
            }
        });
    }
    /**
     * 每3秒获取一次联盟聊天
     * php cli.php main fetchCityBattleChatMsg
     */
    public function fetchCityBattleChatMsgAction($param=[]){
        set_time_limit(0);
        global $config;
        cli_set_process_title('php_swoole_citybattle_chat_msg_'.$config->swoole->port);//set process name
        $interval = 3000;
        $ChatUtil = new ChatUtil;
        $CityBattleRound = new CityBattleRound;
        $CityBattlePlayer = new CityBattlePlayer;
        $roundId = $CityBattleRound->getCurrentRound();
        if(!$roundId) {
            log4task('roundId not exists!');
            return;
        }
        $battleIdList = (new CityBattle)->getRoundBattleList($roundId);
        if(!$roundId) {
            log4task('battleIdList not exists!');
            return;
        }
        $battleIdList = Set::extract('/id', $battleIdList);
        $allCamp  = (new CountryCampList)->dicGetAllId();

        $startTime = time();
        $during = 4200;
        if(!empty($param)) {
            $during = $during * $param[0];
        }
        log4task("roundId={$roundId},battleIdList=".arr2str($battleIdList));
        log4task("本脚本持续时间为：".$during."秒=约".($during/4200)."小时");
        swoole_timer_tick($interval, function($id) use ($ChatUtil, $roundId, $battleIdList, $allCamp, $startTime, $during){
            //1*60*60+10*60 1hour 10minute
            if(time()-$startTime>$during) {
                swoole_timer_clear($id);
                exit;
            }
            foreach($battleIdList as $battleId) {
                foreach ($allCamp as $campId) {
//                    log4cli("----------------->请注意场次--------->roundId={$roundId},battleId={$battleId},campId={$campId}");
                    //cache player
                    {
                        $playerIdsInCurrentRoundBattleCamp =  Cache::db('chat')->get("PlayersInCityBattle-{$roundId}:{$battleId}:{$campId}");
                        if(!$playerIdsInCurrentRoundBattleCamp) {
                            $playerIdsInCurrentRoundBattleCamp = CityBattlePlayer::find([
                                "round_id=:roundId: and battle_id=:battleId: and camp_id=:campId:",
                                'bind'   => compact('roundId', 'battleId', 'campId'),
                                'columns'=> 'player_id'
                             ])->toArray();
                            if($playerIdsInCurrentRoundBattleCamp) {
                                $playerIdsInCurrentRoundBattleCamp = Set::extract('/player_id', $playerIdsInCurrentRoundBattleCamp);
                            } else {
                                continue;
                            }
                            log4cli("roundId={$roundId},battleId={$battleId},campId={$campId}, ".arr2str($playerIdsInCurrentRoundBattleCamp));
                            Cache::db('chat')->set("PlayersInCityBattle-{$roundId}:{$battleId}:{$campId}", $playerIdsInCurrentRoundBattleCamp);
                        }
                    }
                    try {
                        $cityBattleMsg = $ChatUtil->getCityBattleMsg($roundId, $battleId, $campId, true);
                        if (!empty($cityBattleMsg)) {
                            log4cli("roundId={$roundId},battleId={$battleId},campId={$campId}, ".json_encode($cityBattleMsg, JSON_UNESCAPED_UNICODE), 1);
                            $data = [
                                'Type' => 'city_battle_chat_send',
                                'Data' => ['content' => ['data' => $cityBattleMsg], 'camp_id' => $campId,'battle_player_id'=>$playerIdsInCurrentRoundBattleCamp]
                            ];
                            socketSend($data);
                        }
                    } catch (Exception $e) {
                        log4task("Exception:" . dump($e, 1));
                    }
                }
            }
        });
    }
    /**
     * 模拟客户端长连
     *
     * ```
     *  php cli.php main client 1000234
     * ```
     * @param $params
     */
    public function clientAction($params){
        if(count($params)<1) {
            echo "msgId:" . var_export(StaticData::$msgIds, true),PHP_EOL;
            log4cli('Please Input playerId:');
            $playerId = fgets(STDIN);
        } else {
            $playerId = $params[0];
        }
        if(!iquery("select id from player where id={$playerId}")) {//Not Exists!
            exit("playerId={$playerId} Not Exists!");
        }

        //connect first
        global $config;
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, $config->swoole->host, $config->swoole->port);

        //case a :登录
        $data = ['player_id'=>$playerId, 'hash_code'=>hashMethod($playerId)];
        $sendData = packData($data, 10000);
        socket_write($socket, $sendData, strlen($sendData));

        log4task('Login...');
        sleep(1);
        while($loginResp=socket_read($socket, 12)) {
            $loginResp = unpackData($loginResp);
            if($loginResp['msgId']!=10001) {
                log4task("login fail");
                return;
            }
            log4task("login success");
            break;
        }
        log4task('receiving data...');
        //每秒接受数据
        while(true) {
            try {
                //case b: 心跳包
                $hbData = packData([], 10002);
                socket_write($socket, packData([], 10002), strlen($hbData));

                //case c: receive数据
                $recvDataOrigin = socket_read($socket, 12);//3字节数据
                if(strlen($recvDataOrigin)>0) {//收到数据
                    $recvData = unpackData($recvDataOrigin);
                    $length   = $recvData['length'];
                    if ($length > 12) {
                        $_buf = $recvDataOrigin;
                        $_buf     .= socket_read($socket, $length);
                        $recvData = unpackData($_buf);
                        $log      = 'response msgId:' . $recvData['msgId'] . ' ---> content:' . arr2str($recvData['content']);
                        log4task($log);
                    }
                }
                sleep(1);
            } catch(Exception $e) {
                log4task($e, 1);
                break;
            }
        }
        socket_close($socket);
    }
}