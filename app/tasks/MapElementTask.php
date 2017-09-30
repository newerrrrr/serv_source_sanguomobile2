<?php
class MapElementTask extends \Phalcon\CLI\Task
{
    public function mainAction($para=[1])
    {
    	print_r("__BEGIN__");
    	$type = $para[0];
		set_time_limit(0);
		global $redisSharedFlag;
    	$redisSharedFlag = true;
		switch ($type) {
			case 0:
				$sqlList = $this->mapTest(1000);//测试
				break;
			case 1:
				$sqlList = $this->refreshResource();//生成野外资源
				break;
			case 2:
				$sqlList = $this->refreshMonster();//生成野外怪物
				break;
			case 3:
				$sqlList = $this->refreshBoss();//生成野外Boss
				break;
			case 4:
				$this->clearCastle();//删除烧毁城堡 
				return;
			case 5:
				$sqlList = $this->refreshActTag();//生成和氏璧
				break;
			case 6:
				$sqlList = $this->refreshFortress();//生成据点
				break;
			case 7:
				$this->clearFortress();//活动结束删除据点 
				return;
			case 8:
				$this->createRobot();//生成系统Robot
				return;
			default:
				exit;
		}
		global $config;
		$mysqli = @new mysqli($config->database->host, $config->database->username, $config->database->password, $config->database->dbname);
		if(mysqli_connect_errno()){
		    echo "ERROR:".mysqli_connect_error();
		    exit;
		    return false;
		}
		foreach($sqlList as $v) {
		    $re = $mysqli->query($v);
		    if(!$re){
		        echo "ERROR:".$mysqli->error.":".$v;
		        break;
		    }
		}
		Cache::db('map')->flushDB();
		print_r("__FINISH__\n");
    }

    //刷新矿区
    function refreshResource(){
    	$Map = new Map;
		$MapElement = new MapElement;
		$ResourceRefresh = new ResourceRefresh;
		$Configure = new Configure;

		$resourceList = $ResourceRefresh->getResourceList();
		
		$now = getdate();
		$t = $now['hours']*60+$now['minutes'];
		$p = (floor($t/30))%12;

		$pNum  = $Configure->getValueByKey("activity_player_count");
		if(empty($pNum)){
			$balancePara = 1;
		}else{
			$balancePara = ($pNum/1000>2)?2:(($pNum/1000<0.5)?0.5:$pNum/1000);
		}
		$maxCount = 2*$balancePara;

		for($blockX=1;$blockX<=101;$blockX++){
			for($blockY=1;$blockY<=101;$blockY++){
				if($blockX>=50 && $blockX<=52 && $blockY>=50 && $blockY<=52){
					continue;
				}else{
					//删除旧元素
					$blockId = $blockX+103*$blockY;
					if($blockId%12!=$p){
						continue;
					}
					$blockInfo = $Map->getAllByBlockId($blockId);
					$delArr = [];
					$count = 0;
					foreach ($blockInfo as $key => $value) {
						if(in_array($value['map_element_origin_id'], [9,10,11,12,13])){
							if(empty($value['player_id'])){
								$delArr[] = $value['id'];
							}else{
								$count++;
							}
						}
					}

					if(!empty($delArr)){
						$sqlStr = "delete from Map where id in (".implode(',', $delArr).");";
						yield $sqlStr;
					}

					//添加新元素
					$t = 0;
					$xyArr = [];
					while($count<$maxCount && $t<10){
						$x = mt_rand($blockX*12, $blockX*12+11);
						$y = mt_rand($blockY*12, $blockY*12+11);
						if(in_array($x*10000+$y, $xyArr)){
							continue;
						}
						$t++;
						$success = $Map->checkRandElementPosition([$x, $y]);
						if($success){
							$count++;
							$xyArr[] = $x*10000+$y;
							//实际上是618.5
							$r = ($x-618)*($x-618)+($y-618)*($y-618);
							foreach ($resourceList as $k => $v) {
                                if($k>$r){
                                    $elementIdList = $v;
                                    break;
                                }
                            }
							$elementId = getRandByArr($elementIdList);
							$element = $MapElement->dicGetOne($elementId);
							$originId = $element['origin_id'];
							$level = $element['level'];
							
							$sqlStr = "insert into Map (`x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`, `resource`, `create_time`) values ({$x},{$y},{$blockId},{$elementId},{$originId},{$level},{$element['max_res']},now());";
							yield $sqlStr;
						}
					}

				}
			}
		}
    }

    //刷新怪物
    function refreshMonster(){
    	$Map = new Map;
		$MapElement = new MapElement;
		$Configure = new Configure;
		$sTime = $Configure->getValueByKey("server_start_time");
		$MonsterCycle = new MonsterCycle;
		$day = ceil((time()-$sTime)/3600/24);
		$monsterCycle = $MonsterCycle->getMonsterByDay($day);

		$monsterList = [];
		$noResetList = [];
		foreach ($monsterCycle as $key => $value) {
			$monsterList[$value['monster_id']] = $value['weight'];
			if($value['ifrespawn']==0){
				$noResetList[] = $value['monster_id'];
			}
		}

		$pNum  = $Configure->getValueByKey("activity_player_count");
		if(empty($pNum)){
			$balancePara = 1;
		}else{
			$balancePara = ($pNum/1000>2)?2:(($pNum/1000<0.5)?0.5:$pNum/1000);
		}
		$maxCount = ceil(10*$balancePara);

		$now = getdate();
		$t = $now['hours']*60+$now['minutes'];
		$p = (floor($t/30))%12;
		for($blockX=1;$blockX<=101;$blockX++){
			for($blockY=1;$blockY<=101;$blockY++){
				if($blockX>=50 && $blockX<=52 && $blockY>=50 && $blockY<=52){
					continue;
				}else{
					//删除旧元素
					$blockId = $blockX+103*$blockY;
					if($blockId%12!=$p){
						continue;
					}
					$blockInfo = $Map->getAllByBlockId($blockId);
					$delArr = [];
					$count = 0;
					foreach ($blockInfo as $key => $value) {
						if($value['map_element_origin_id']==14){
							if(!in_array($value['map_element_level'], $noResetList)){
								$delArr[] = $value['id'];
							}else{
								$count++;
							}
						}
					}
					if(!empty($delArr)){
						$sqlStr = "delete from Map where id in (".implode(',', $delArr).");";
						yield $sqlStr;
					}

					//添加新元素
					$t=0;
					$xyArr = [];
					while($count<$maxCount && $t<20){
						$x = mt_rand($blockX*12, $blockX*12+11);
						$y = mt_rand($blockY*12, $blockY*12+11);
						if(in_array($x*10000+$y, $xyArr)){
							continue;
						}
						$t++;
						$success = $Map->checkRandElementPosition([$x, $y]);
						if($success){
							$count++;
							$xyArr[] = $x*10000+$y;
							$originId = 14;
							$level = getRandByArr($monsterList);
							$element = $MapElement->dicGetOneByOriginIdAndLevel($originId, $level);
							$sqlStr = "insert into Map (`x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`, `resource`, `create_time`) values ({$x},{$y},{$blockId},{$element['id']},{$originId},{$level},{$element['max_res']},now());";
							yield $sqlStr;
						}
					}					
				}
			}
		}
    }

    //刷新怪物
    function refreshBoss(){
    	$Map = new Map;
		$MapElement = new MapElement;
		$Configure = new Configure;
		$sTime = $Configure->getValueByKey("server_start_time");
		$BossRefresh = new BossRefresh;
		$day = ceil((time()-$sTime)/3600/24);
		$monsterCycle = $BossRefresh->getBossByDay($day);

		$monsterList = [];
		$noResetList = [];
		foreach ($monsterCycle as $key => $value) {
			$monsterList[$value['boss_id']] = $value['weight'];
			if($value['ifrespawn']==0){
				$noResetList[] = $value['boss_id'];
			}
		}

		$pNum  = $Configure->getValueByKey("activity_player_count");
		if(empty($pNum)){
			$balancePara = 1;
		}else{
			$balancePara = ($pNum/1000>2)?2:(($pNum/1000<0.5)?0.5:$pNum/1000);
		}
		$prob = 0.2*$balancePara;

		$now = getdate();
		$t = $now['hours']*60+$now['minutes'];
		$p = (floor($t/30))%12;
		for($blockX=11;$blockX<=91;$blockX++){
			for($blockY=11;$blockY<=91;$blockY++){
				if($blockX>=50 && $blockX<=52 && $blockY>=50 && $blockY<=52){
					continue;
				}else{
					//删除旧元素
					$blockId = $blockX+103*$blockY;
					if($blockId%12!=$p){
						continue;
					}
					$blockInfo = $Map->getAllByBlockId($blockId);
					$delArr = [];
					foreach ($blockInfo as $key => $value) {
						if($value['map_element_origin_id']==17 && !in_array($value['map_element_level'], $noResetList)){
							$delArr[] = $value['id'];
						}
					}
					if(!empty($delArr)){
						$sqlStr = "delete from Map where id in (".implode(',', $delArr).");";
						yield $sqlStr;
					}
					if(lcg_value()<$prob){
						//添加新元素
						$t=0;
						$xyArr = [];
						while($t<10){
							$x = mt_rand($blockX*12, $blockX*12+11);
							$y = mt_rand($blockY*12, $blockY*12+11);
							if(in_array($x*10000+$y, $xyArr)){
								continue;
							}
							$success = $Map->checkRandElementPosition([$x, $y]);
							$t++;
							if($success){
								$xyArr[] = $x*10000+$y;
								$originId = 17;
								$level = getRandByArr($monsterList);
								$element = $MapElement->dicGetOneByOriginIdAndLevel($originId, $level); 
								$sqlStr = "insert into Map (`x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`, `durability`, `max_durability`, `create_time`) values ({$x},{$y},{$blockId},{$element['id']},{$originId},{$level},{$element['max_res']},{$element['max_res']},now());";
								yield $sqlStr;
								break;
							}
						}
					}
				}
			}
		}
    }

    //刷新玉玺
    function refreshActTag(){
    	$AllianceMatchList = new AllianceMatchList;
    	$Configure = new Configure;
		$reStatus = $AllianceMatchList->getAllianceMatchStatus(2);
		$re = $AllianceMatchList->getLastMatch(2);
		if($reStatus!=AllianceMatchList::DOING || $re['start_time']+3600*10<time()){
			$sqlStr = "delete from Map where map_element_origin_id=21";
			yield $sqlStr;
			return;
		}
    	$Map = new Map;
    	$i = 0;
    	$j = 0;
    	$xyArr = [];

    	$pNum  = $Configure->getValueByKey("activity_player_count");
		if(empty($pNum)){
			$balancePara = 1;
		}else{
			$balancePara = ($pNum/1000>2)?2:(($pNum/1000<0.5)?0.5:$pNum/1000);
		}
		$maxNum = 28*$balancePara;
    	while($i<$maxNum && $j<10000){
    		$x = mt_rand(12, 1223);
    		$y = mt_rand(12, 1223);
    		if(in_array($x*10000+$y, $xyArr)){
				continue;
			}
			
			$j++;
    		$success = $Map->checkRandElementPosition([$x, $y]);
    		
    		if($success){
				$xyArr[] = $x*10000+$y;
				$blockId = $Map->calcBlockByXy($x, $y);
				$sqlStr = "insert into Map (`x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`, `create_time`) values ({$x},{$y},{$blockId},1901,21,1,now());";
				yield $sqlStr;
				$i++;
			}
    	}
    }

    //刷新据点
    function refreshFortress(){
    	$AllianceMatchList = new AllianceMatchList;
    	$MapElement = new MapElement;
    	$Configure = new Configure;
		$reStatus = $AllianceMatchList->getAllianceMatchStatus(4);
		$re = $AllianceMatchList->getLastMatch(4);
		if($reStatus!=AllianceMatchList::DOING){
			$sqlStr = "delete from Map where map_element_origin_id=21";
			yield $sqlStr;
			return;
		}
    	$Map = new Map;
    	$i = 0;
    	$j = 0;
    	$xyArr = [];
    	$endTime = date("Y-m-d 22:00:00");
    	$elementId = 2001;
		$originId = 22; 
		$level = 1;
		$element = $MapElement->dicGetOneByOriginIdAndLevel($originId, $level); 

		$pNum  = $Configure->getValueByKey("activity_player_count");
		if(empty($pNum)){
			$balancePara = 1;
		}else{
			$balancePara = ($pNum/1000>2)?2:(($pNum/1000<0.5)?0.5:$pNum/1000);
		}
		$maxNum = 500*$balancePara;
    	while($i<$maxNum && $j<10000){
    		$x = mt_rand(162, 1073);
    		$y = mt_rand(162, 1073);
    		if(in_array($x*10000+$y, $xyArr)){
				continue;
			}
			
			$j++;
    		$success = $Map->checkRandElementPosition([$x, $y]);
    		
    		if($success){
				$xyArr[] = $x*10000+$y;
				$blockId = $Map->calcBlockByXy($x, $y);

				$sqlStr = "insert into Map (`x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`, `resource`, `create_time`, `build_time`) values ({$x},{$y},{$blockId},{$elementId},{$originId},{$level},{$element['max_res']},now(), '{$endTime}');";
				yield $sqlStr;
				$i++;
			}
    	}
    }

    function clearFortress(){
    	$sqlStr = "SELECT * FROM Map WHERE map_element_origin_id=22;";
    	$sqlStr2 = "SELECT * FROM Map WHERE map_element_origin_id=22 AND player_id>0;";
    	$sqlStr3 = "DELETE FROM Map WHERE map_element_origin_id=22";
    	global $config;
		$mysqli = @new mysqli($config->database->host, $config->database->username, $config->database->password, $config->database->dbname);
		if(mysqli_connect_errno()){
		    echo "ERROR:".mysqli_connect_error();
		    exit;
		}
		$re = $mysqli->query($sqlStr);
		if(mysqli_num_rows($re)>0){
			while(true){
				$re2 = $mysqli->query($sqlStr2);
				if(mysqli_num_rows($re2)==0){
					$mysqli->query($sqlStr3);
					Cache::db('map')->flushDB();
					print_r("__Success__\n");
					break;
				}
				sleep(10);
				print_r("__Fail__\n");
			}
		}
		print_r("__Finish__\n");
		
		//重算据点排名
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus(4) == AllianceMatchList::WAIT_REWARD){
			(new GuildMissionTask)->inventoryGuildScore(3);
		}
    }

    //清理玩家城堡
    function clearCastle(){
		$now = getdate();
		$sqlStr = "SELECT id FROM player WHERE unix_timestamp(NOW()) <=  unix_timestamp(`fire_end_time`) AND FLOOR(  `wall_durability` - ( unix_timestamp(NOW()) -  unix_timestamp(`durability_last_update_time`) ) /18 ) <=100;";

		global $config;
		$mysqli = @new mysqli($config->database->host, $config->database->username, $config->database->password, $config->database->dbname);
		if(mysqli_connect_errno()){
		    echo "ERROR:".mysqli_connect_error();
		    exit;
		}
		$re = $mysqli->query($sqlStr);
		$delArr = [];
		$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerCommonLog = new PlayerCommonLog;
		$Map = new Map;

		while($row = $re->fetch_row()){
			$delArr[] = $row[0];
			$PlayerProjectQueue->callbackQueueNowByPlayerId($row[0]);
			if($Map->delPlayerCastle($row[0])){
				$PlayerCommonLog->add($row[0], ['type'=>'城池烧毁']);//日志记录
			}
		}
		print_r("__FINISH__\n");
    }

    function createRobot(){
    	$Configure = new Configure;
		$sTime = $Configure->getValueByKey("server_start_time");
		$day = ceil((time()-$sTime)/3600/24);

    	$Player = new Player;
		$Player->createRobot($day, 1);
    }
    function mapTest($blockId){
    	$MapElement = new MapElement;
    	$Map = new Map;
    	for($x=($blockId%103)*12;$x<=($blockId%103)*12+12;$x++){
    		for($y=floor($blockId / 103)*12;$y<=floor($blockId / 103)*12+12;$y++){
    			$success = $Map->checkRandElementPosition([$x, $y]);
				if($success){
					$originId = 14;
					$level = 10;
					$element = $MapElement->dicGetOneByOriginIdAndLevel($originId, $level);
					$sqlStr = "insert into Map (`x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`, `resource`, `create_time`) values ({$x},{$y},{$blockId},{$element['id']},{$originId},{$level},{$element['max_res']},now());";
					yield $sqlStr;
				}
    		}
    	}
    }
}