<?php
/**
 * 采集队列
 */
class QueueCollection extends DispatcherTask{
	public $carryType = array(
		3 => 'gold',
		4 => 'food',
		5 => 'wood',
		6 => 'stone',
		7 => 'iron',
		9 => 'gold',
		10 => 'food',
		11 => 'wood',
		12 => 'stone',
		13 => 'iron',
		//22 => 'jn',
	);
	
	public function _goto($ppq){
		StaticData::$delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerProjectQueue = new PlayerProjectQueue;
			$Player = new Player;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];
			
			//判断终点是否为采矿点
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			if(!in_array($map['map_element_origin_id'], array(9, 10, 11, 12, 13, 22, 3, 4, 5, 6, 7))){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			if($map['map_element_origin_id'] == 22 && time() >= $map['build_time']){//超过活动时间遣返
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			if(!(new PlayerArmyUnit)->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			
			if(!in_array($map['map_element_origin_id'], array(3, 4, 5, 6, 7))){
				//判断是否有其他玩家单位占据
				$otherPpq = PlayerProjectQueue::findFirst(['status=1 and to_map_id='.$ppq['to_map_id'].' and to_map_id=from_map_id and id<>'.$ppq['id']]);
				if($otherPpq){
					$otherPpq = $PlayerProjectQueue->afterFindQueue(array($otherPpq->toArray()))[0];
					//如果对方不是盟友
					if(!(new PlayerGuild)->isSameGuild($ppq['player_id'], $otherPpq['player_id']) && $ppq['target_info']['fight']){
						//删除我方套子
						(new Player)->offAvoidBattle($ppq['player_id']);
						$battleRet = $this->createArmyBattle($ppq, $otherPpq, $map);
						if(!$battleRet){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}

						if($battleRet['result']){//win
							//$this->createArmyReturn($otherPpq, $battleRet['defenceData']);
							//goto finishQueue;
						}else{//lose
							$this->createArmyReturn($ppq, $battleRet['attackData']);
							$map['resource'] -= $battleRet['subResource'];//更新剩余资源数
							if(@$battleRet['overWeightBack'])
								$map['player_id'] = 0;
							if(!$Map->alter($map['id'], $map)){
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							}
							goto finishQueue;
						}
					}else{//如果对方是盟友则返回
						$this->createArmyReturn($ppq);
						goto finishQueue;
					}
					//$battleRet['stealResource']
					$map['resource'] -= $battleRet['subResource'];//更新剩余资源数
					//$battleRet['attackData'][$this->carryType[$map['map_element_origin_id']]] = $battleRet['stealResource'];
				}
				$queueType = PlayerProjectQueue::TYPE_COLLECT_ING;
			}else{
				if($map['guild_id'] != $ppq['guild_id']){
					$this->createArmyReturn($ppq);
					goto finishQueue;
				}
				
				//检查是否有队伍在矿内
				if(PlayerProjectQueue::findFirst(['player_id='.$ppq['player_id'].' and type='.PlayerProjectQueue::TYPE_GUILDCOLLECT_ING.' and status=1 and to_map_id='.$ppq['to_map_id']])){
					$this->createArmyReturn($ppq);
					goto finishQueue;
				}
				
				$queueType = PlayerProjectQueue::TYPE_GUILDCOLLECT_ING;
			}
			
			//获取资源点剩余资源
			/*if(in_array($map['map_element_origin_id'], array(3, 4, 5, 6, 7))){
				$map['resource'] = 999999999;
			}*/
			if(!$map['resource']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			if($map['map_element_origin_id'] == 22){//采锦囊
				(new Player)->offAvoidBattle($ppq['player_id']);
				//计算采集速度
				$MapElement = new MapElement;
				$me = $MapElement->dicGetOne($map['map_element_id']);
				if(!$me){
					$this->createArmyReturn($ppq);
					goto finishQueue;
				}
				$resPerMin = $me['collection'];
				
				//新建采集队列
				$extraData = array(
					'from_map_id' => $ppq['to_map_id'],
					'from_x' => $ppq['to_x'],
					'from_y' => $ppq['to_y'],
					'to_map_id' => $ppq['to_map_id'],
					'to_x' => $ppq['to_x'],
					'to_y' => $ppq['to_y'],
				);
				if(@$battleRet){
					$extraData = $extraData+$battleRet['attackData'];
				}
				$targetInfo = array(
					'resource'=>$map['resource'],
					'speed'=>$resPerMin,
					'weight'=>0,
					'carry'=>0,
					'element_id'=>$map['map_element_id'],
				);
				$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $queueType, ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>date('Y-m-d H:i:s', $map['build_time'])], $ppq['army_id'], $targetInfo, $extraData);
				
				//更新map
				$map['player_id'] = $ppq['player_id'];
				$map['guild_id'] = $ppq['guild_id'];
				if(!$Map->alter($map['id'], $map)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				//计算军团负重
				$PlayerArmyUnit = new PlayerArmyUnit;
				$weight = $PlayerArmyUnit->calculateWeight($ppq['player_id'], $ppq['army_id']);
				//如果捕获敌方资源，重算剩余负重
				if(isset($battleRet['restWeight'])){
					//计算捕获资源实际能携带多少
					/*$realStealResource = weightCarry(array($map['map_element_origin_id']=>$battleRet['stealResource']), $weight);
					$battleRet['stealResource'] = $realStealResource;
					$weight = max(0, $weight - resourceWeight($realStealResource));*/
					$weight = $battleRet['restWeight'];
				}
				
				if($weight){//有剩余负重
					//获取采集加成buff
					$PlayerBuff = new PlayerBuff;
					//$collectionBuff = $PlayerBuff->getPlayerBuff($ppq['player_id'], $this->carryType[$map['map_element_origin_id']].'_gathering_speed');
					$collectionBuff = $PlayerBuff->getCollectionBuff($ppq['player_id'], $ppq['army_id'], $this->carryType[$map['map_element_origin_id']]);
					if($Map->isInGuildArea($ppq['player_id'])){
						$collectionBuff += 0.15;
					}
					
					//计算采集速度
					$MapElement = new MapElement;
					$me = $MapElement->dicGetOne($map['map_element_id']);
					if(!$me){
						$this->createArmyReturn($ppq);
						goto finishQueue;
					}
					$resPerMin = $me['collection'] * (1+$collectionBuff);
					
					//计算采集时间
					$weightCarry = weightCarry(array($map['map_element_origin_id']=>$map['resource']), $weight)[$this->carryType[$map['map_element_origin_id']]];
					$second = ceil($weightCarry / ($resPerMin / 60));
					
					//新建采集队列
					$extraData = array(
						'from_map_id' => $ppq['to_map_id'],
						'from_x' => $ppq['to_x'],
						'from_y' => $ppq['to_y'],
						'to_map_id' => $ppq['to_map_id'],
						'to_x' => $ppq['to_x'],
						'to_y' => $ppq['to_y'],
					);
					if(@$battleRet){
						/*foreach($battleRet['attackData'] as $_k=>$_d){
							if(is_array($_d)){
								foreach($_d as $__k => $__d){
									$extraData[$_k][$__k] += $_d;
								}
							}else{
								$extraData[$_k] += $_d;
							}
						}*/
						$extraData = $extraData+$battleRet['attackData'];
					}
					$targetInfo = array(
						'resource'=>$map['resource'],
						'speed'=>$resPerMin,
						'weight'=>$weight,
						'carry'=>$weightCarry*1,
						'element_id'=>$map['map_element_id'],
					);
					$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $queueType, $second, $ppq['army_id'], $targetInfo, $extraData);
					
					//更新map
					if(!in_array($map['map_element_origin_id'], array(3, 4, 5, 6, 7))){
						$map['player_id'] = $ppq['player_id'];
						$map['guild_id'] = $ppq['guild_id'];
						if(!$Map->alter($map['id'], $map)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
					
				}else{//负重满，返回
					$this->createArmyReturn($ppq, $battleRet['attackData']);
					//更新map
					$map['player_id'] = 0;
					$map['guild_id'] = 0;
					if(!$Map->alter($map['id'], $map)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			
			
			finishQueue:
			$this->sendNotice($ppq['player_id'], 'arriveDest');
			

			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
				}else{
					$battleFlag = 2;
				}
			}

			//更新队列完成
			if(!isset($battleFlag))
				$battleFlag = 0;

			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id'], $battleFlag);
			
			dbCommit($db);
			flushSocketSend();
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

	public function _done($ppq){
		StaticData::$delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerProjectQueue = new PlayerProjectQueue;
			$Player = new Player;
			
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];
			
			//判断终点是否为采矿点
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
            // FIXME 验证是否此处取不到导致据点删除不了
            if(empty($map)) {
                $map = Map::findFirst(["x=:x: and y=:y:", 'bind'=>['x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]]);
                if($map) {
                    $map = $map->toArray();
					$map = $Map->adapter([$map])[0];
                }
            }
			if(!$map){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			$retData = [];
			//计算获取资源
			if($map['map_element_origin_id'] == 22){
				$hour = floor((time() - $ppq['create_time']) / 3600);
				$realResource = $resource = $ppq['target_info']['speed'] * $hour;
				if($resource)
					$retData['carry_item'] = [[1, 11300, $resource]];
			}else{
				$second = max(0, time() - $ppq['create_time']);
				$resource = min($map['resource'], floor(($ppq['target_info']['speed'] / 60) * $second));
				$realResource = weightCarry(array($map['map_element_origin_id']=>$resource), $ppq['target_info']['weight'])[$this->carryType[$map['map_element_origin_id']]];
				$retData['carry_'.$this->carryType[$map['map_element_origin_id']]] = $realResource;
				//计算额外道具
				$gainItem = $this->_gainItem($ppq['player_id'], $this->carryType[$map['map_element_origin_id']], $realResource);
				if(is_array($gainItem)){
					$retData['carry_item'] = $gainItem;
				}
				
			}
			
			//据点战结尾加积分
			if($map['map_element_origin_id'] == 22 && time() >= $map['build_time']){
				$player = $Player->getByPlayerId($ppq['player_id']);
				if($player['guild_id']){
					$aml = (new AllianceMatchList)->getLastMatch(4);
					if($aml)
						(new GuildMissionRank)->addScore($aml['round'], 4, $player['guild_id'], 650);
				}
			}
			
			//创建返回队列
			$this->createArmyReturn($ppq, $retData, $map);
			
			if($map['map_element_origin_id'] != 22){
				(new PlayerMission)->updateMissionNumber($ppq['player_id'], 8, $realResource*1);
				
				if(in_array($this->carryType[$map['map_element_origin_id']], ['food', 'gold'])){
					(new PlayerTarget)->updateTargetCurrentValue($ppq['player_id'], 11, $realResource);
				}
				//加锁，防止大召回并发 TODO 李多加 review下
                usleep(3000);//解释不能
                $lockKey = __CLASS__ . ':' . __METHOD__ . ':TimeLimit:playerId=' .$ppq['player_id'];
                Cache::lock($lockKey, 300, CACHEDB_PLAYER, 300);
                $this->_timeLimitAddScore($ppq['player_id'], $this->carryType[$map['map_element_origin_id']], $realResource, $ppq);
                Cache::unlock($lockKey);
			}
			
			//更新地图数据
			if(!in_array($map['map_element_origin_id'], array(3, 4, 5, 6, 7))){
				$map['player_id'] = 0;
				$map['guild_id'] = 0;
				$map['resource'] = max(0, $map['resource']-$realResource);
				if(!$Map->alter($map['id'], $map)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//$this->sendNotice($ppq['player_id'], 'arriveDest');
			
			finishQueue:
			//更新队列完成
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
			dbCommit($db);
			flushSocketSend();
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
     * 建立返回队列 todo
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function createArmyReturn($ppq, $data=array(), $map=false){
		//获取我的主城位置
		$Player = new Player;
		$player = $Player->getByPlayerId($ppq['player_id']);
		if(!$player){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//计算时间
		if(@$ppq['target_info']['backNow']){
			$needTime = 0;
		}else{
			$needTime = PlayerProjectQueue::calculateMoveTime($ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], 1, $ppq['army_id']);
		}
		/*if(!$needTime){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}*/
		
		$PlayerProjectQueue = new PlayerProjectQueue;
		$data = $PlayerProjectQueue->mergeExtraInfo($ppq, $data);
		//建立队列
		$extraData = [
			'from_map_id' => $ppq['to_map_id'],
			'from_x' => $ppq['to_x'],
			'from_y' => $ppq['to_y'],
			'to_map_id' => $player['map_id'],
			'to_x' => $player['x'],
			'to_y' => $player['y'],
			//'carry_gold' => $ppq['carry_gold']+@$data['carry_gold']+@$data['gold'],
			//'carry_food' => $ppq['carry_food']+@$data['carry_food']+@$data['food'],
			//'carry_wood' => $ppq['carry_wood']+@$data['carry_wood']+@$data['wood'],
			//'carry_stone' => $ppq['carry_stone']+@$data['carry_stone']+@$data['stone'],
			//'carry_iron' => $ppq['carry_iron']+@$data['carry_iron']+@$data['iron'],
			//'carry_soldier' => $ppq['carry_soldier'],
		];
		$extraData = $extraData + $data;
		/*if(@$data['carry_soldier']){
			foreach($data['carry_soldier'] as $_t => $_s){
				@$extraData['carry_soldier'][$_t] += $_s;
			}
		}*/
		if(@$ppq['target_info']['element_id']){
			$targetInfo = ['element_id'=>$ppq['target_info']['element_id']];
		}elseif(@$map['id']){
			$targetInfo = ['element_id'=>$map['map_element_id']];
		}else{
			$targetInfo = [];
		}
		$targetInfo['rob_gold'] = $ppq['carry_gold'];
		$targetInfo['rob_food'] = $ppq['carry_food'];
		$targetInfo['rob_wood'] = $ppq['carry_wood'];
		$targetInfo['rob_stone'] = $ppq['carry_stone'];
		$targetInfo['rob_iron'] = $ppq['carry_iron'];
		
		if(in_array($ppq['type'], [PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO, PlayerProjectQueue::TYPE_GUILDCOLLECT_ING])){
			$type = PlayerProjectQueue::TYPE_GUILDCOLLECT_RETURN;
		}else{
			$type = PlayerProjectQueue::TYPE_COLLECT_RETURN;
		}
		//$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], $targetInfo, $extraData);
		return true;
	}
	
	public function createArmyBattle($ppq, $otherPpq, $map){
		$retData = array();
		$extraData = ['attackData'=>[], 'defenceData'=>[]];
		$ex = [];
		if($map['map_element_origin_id'] == 22){//锦囊
			$battleType = 9;
		}else{
			$battleType = 2;
			
			$PlayerBuild = new PlayerBuild;
			//获取攻击方城堡等级
			$attackLv = $PlayerBuild->getPlayerCastleLevel($ppq['player_id']);
			//获取防守方城堡等级
			$defendLv = $PlayerBuild->getPlayerCastleLevel($otherPpq['player_id']);
			$ex['attackCastleLv'] = $attackLv;
			$ex['defendCastleLv'] = $defendLv;
			
			if($attackLv >= $defendLv){
				$powerBefore = (new Power)->getSoldier($otherPpq['player_id']);
			}
		}
		$Battle = new Battle;
		$ret = $Battle->battleCore([$ppq['player_id']=>$ppq['army_id']], [$otherPpq['player_id']=>$otherPpq['army_id']], $battleType, $ex);
		if(!$ret){
			var_dump([$ppq['player_id']=>$ppq['army_id']]);
			var_dump([$otherPpq['player_id']=>$otherPpq['army_id']]);
			var_dump($ret);
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		
		//解析伤兵
		foreach($ret['aList'] as $_list){
			@$retData['attackData']['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
		}
		foreach($ret['dList'] as $_list){
			@$retData['defenceData']['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
		}
		
		//已采集资源
		if($map['map_element_origin_id'] == 22){
			$hour = floor((time() - $otherPpq['create_time']) / 3600);
			$outResource = $otherPpq['target_info']['speed'] * $hour;
		}else{
			$second = max(0, time() - $otherPpq['create_time']);
			$outResource = min($map['resource'], $otherPpq['target_info']['carry'], floor(($otherPpq['target_info']['speed'] / 60) * $second));
		}
		$retData['subResource'] = 0;
		
		//if win
		if($ret['win']){
			//攻击方死兵，分的n%资源，占领矿，防守方带剩余资源回去
			$retData['result'] = true;//win
			if($map['map_element_origin_id'] == 22){
				$PlayerArmyUnit = new PlayerArmyUnit;
				$retData['restWeight'] = $PlayerArmyUnit->calculateWeight($ppq['player_id'], $ppq['army_id']);
				if($outResource)
					$retData['defenceData']['carry_item'] = [[1, 11300, $outResource]];
			}else{
				$stealRate = 1;
				//计算防守方已经采集数量
				$stealResource = floor($outResource * $stealRate);
				//计算部队最大负重
				$PlayerArmyUnit = new PlayerArmyUnit;
				$weight = $PlayerArmyUnit->calculateWeight($ppq['player_id'], $ppq['army_id']);
				//计算已有负重
				$hasWeight = resourceWeight([$map['map_element_origin_id']=>$stealResource]);
				//$hasWeight = resourceWeight(['wood'=>$ppq['carry_wood'], 'food'=>$ppq['carry_food'], 'gold'=>$ppq['carry_gold'], 'stone'=>$ppq['carry_stone'], 'iron'=>$ppq['carry_iron']]);
				
				//计算可再分的资源
				$canUseWeight = max(0, $weight - $hasWeight);
				//$stealResource = weightCarry([$map['map_element_origin_id']=>$stealResource], $canUseWeight);
				$stealResource = weightCarry([$map['map_element_origin_id']=>$stealResource], $weight);
				
				//计算剩余负重
				//$retData['restWeight'] = max(0, $weight - 	$hasWeight-resourceWeight($stealResource));
				$retData['restWeight'] = max(0, $weight - resourceWeight($stealResource));
				foreach($stealResource as $_r=>$_d){
					$retData['attackData']['carry_'.$_r] = $_d;
					$ret['resource'][$ppq['player_id']][$_r] = $_d;
				}
				foreach($stealResource as $_r=>$_d){
					$retData['defenceData']['carry_'.$_r] = $outResource - $retData['attackData']['carry_'.$_r];
				}
				//$retData['attackData'] = array_merge($retData['attackData'], $stealResource);
			}
			
			//遣返防守部队
			(new PlayerProjectQueue)->finishQueue($otherPpq['player_id'], $otherPpq['id'], 2);
			if(!$this->createArmyReturn($otherPpq, $retData['defenceData'], $map))
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			if($map['map_element_origin_id'] != 22){
				(new PlayerMission)->updateMissionNumber($ppq['player_id'], 7, $retData['attackData']['carry_'.$this->carryType[$map['map_element_origin_id']]]);
				(new PlayerMission)->updateMissionNumber($otherPpq['player_id'], 8, $retData['defenceData']['carry_'.$this->carryType[$map['map_element_origin_id']]]);
				
				if(in_array($this->carryType[$map['map_element_origin_id']], ['food', 'gold'])){
					(new PlayerTarget)->updateTargetCurrentValue($otherPpq['player_id'], 11, $retData['attackData']['carry_'.$this->carryType[$map['map_element_origin_id']]]);//采集
				}
				
				(new PlayerTarget)->updateTargetCurrentValue($ppq['player_id'], 15, $stealResource[$this->carryType[$map['map_element_origin_id']]]);//抢夺
				
				$this->_timeLimitAddScore($otherPpq['player_id'], $this->carryType[$map['map_element_origin_id']], $retData['attackData']['carry_'.$this->carryType[$map['map_element_origin_id']]], $otherPpq);
			}
			$retData['subResource'] = $outResource;
		}else{
			//if lose
			$retData['result'] = false;//lose
			
			if($map['map_element_origin_id'] == 22){
				
			}else{
				//重新计算防守方的采集数据
				$hasWeight = resourceWeight([$map['map_element_origin_id']=>$outResource]);
				$weight = (new PlayerArmyUnit)->calculateWeight($otherPpq['player_id'], $otherPpq['army_id']);
				$PlayerProjectQueue = new PlayerProjectQueue;
				if($hasWeight >= $weight){//超重返回
					$retData['defenceData']['carry_'.$this->carryType[$map['map_element_origin_id']]] = $outResource;
					(new PlayerProjectQueue)->finishQueue($otherPpq['player_id'], $otherPpq['id'], 1);
					$gainItem = $this->_gainItem($otherPpq['player_id'], $this->carryType[$map['map_element_origin_id']], $outResource);
					if(is_array($gainItem)){
						$retData['defenceData']['carry_item'] = $gainItem;
					}
					if(!$this->createArmyReturn($otherPpq, $retData['defenceData'], $map))
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					
					(new PlayerMission)->updateMissionNumber($otherPpq['player_id'], 8, $retData['defenceData']['carry_'.$this->carryType[$map['map_element_origin_id']]]);
					
					if(in_array($this->carryType[$map['map_element_origin_id']], ['food', 'gold']) && @$retData['attackData']['carry_'.$this->carryType[$map['map_element_origin_id']]]){
						(new PlayerTarget)->updateTargetCurrentValue($otherPpq['player_id'], 11, $retData['attackData']['carry_'.$this->carryType[$map['map_element_origin_id']]]);//采集
					}
					
					$this->_timeLimitAddScore($otherPpq['player_id'], $this->carryType[$map['map_element_origin_id']], $outResource, $otherPpq);
					
					$retData['subResource'] = $outResource;
					$retData['overWeightBack'] = true;
				}else{
					//计算采集时间
					$weightCarry = weightCarry(array($map['map_element_origin_id']=>$map['resource']), $weight)[$this->carryType[$map['map_element_origin_id']]];
					$second = ceil($weightCarry / ($otherPpq['target_info']['speed'] / 60));
					$targetInfo = $otherPpq['target_info'];
					$targetInfo['weight'] = $weight;
					$targetInfo['carry'] = $weightCarry;
					$extra = $PlayerProjectQueue->mergeExtraInfo($otherPpq, $retData['defenceData']);
					if(!$PlayerProjectQueue->assign($otherPpq)->updateQueue($second, $targetInfo, $extra)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
		}
		
		//顽强斗志buff
		$protectOpen = false;
		if($battleType == 2){
			$protectOpen = (new PlayerBuffTemp)->addWQDZbuff($otherPpq['player_id'], $powerBefore, $ret['dSoldierLosePower']/DIC_DATA_DIVISOR);
		}
		
		$extraData['aLosePower'] = $ret['aLosePower'];
		$extraData['dLosePower'] = $ret['dLosePower'];
		$extraData['battleLogId'] = $ret['battleLogId'];
		$extraData['godGeneralSkillArr'] = $ret['godGeneralSkillArr'];
		$extraData['noobProtect'] = $ret['noobProtect'];
		$extraData['protectOpen'] = $protectOpen;
		(new PlayerMail)->sendPVPBattleMail([$ppq['player_id']], [$otherPpq['player_id']], $ret['win'], $battleType, $ppq['to_x'], $ppq['to_y'], $ret['resource'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
		(new RoundMessage)->addNew($ppq['player_id'], ['type'=>1, 'battle_type'=>1, 'battle_defender_id'=>$otherPpq['player_id'], 'battle_win'=>$ret['win'], 'battle_attacker_power_loss'=>$ret['aLosePower'], 'battle_defender_power_loss'=>$ret['dLosePower']]);//走马灯公告

		return $retData;
	}
	
	public function _gainItem($playerId, $fieldType, $getResource){
		//折算负重
		$weight = resourceWeight([$fieldType=>$getResource]);
		
		//获取drop范围
		$CollectionDrop = new CollectionDrop;
		//echo 'weight:'.$weight."\r\n";
		$cd = $CollectionDrop->findFirst(['collection_min<='.$weight.' and collection_max>='.$weight]);
		if(!$cd)
			return true;
		$cd = $CollectionDrop->parseColumn($cd->toArray());
		//var_dump($cd);
		
		//获取drop
		$dropIds = $cd['collection_drop'];
		$carryItem = [];
		foreach($dropIds as $_dropId){
			$dropData = (new Drop)->rand($playerId, [$_dropId]);
			if($dropData === true){
				continue;
			}
			if(!$dropData){
				continue;
			}
			
			//组织carry_item
			foreach($dropData as $_dropData){
				$carryItem[] = [$_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1];
			}
			
		}
		return $carryItem;
		
	}

	public function _timeLimitAddScore($playerId, $type, $num, $ppq){
		$ar = ['gold'=>1, 'food'=>2, 'wood'=>3, 'stone'=>4, 'iron'=>5];
		$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
		$PlayerTimeLimitMatch->updateScore($playerId, $ar[$type], $num, ['资源类型'=>$type, 'beginTime'=>date('Y-m-d H:i:s', $ppq['create_time']), 'endTime'=>date('Y-m-d H:i:s')]);
	}
}
