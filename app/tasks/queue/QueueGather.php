<?php
/**
 * 集结队列
 */
class QueueGather extends DispatcherTask{
    /**
     * 集合完毕
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _ready($ppq){
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

			//获取集结玩家队列
			$otherPpqs = PlayerProjectQueue::find(['parent_queue_id='.$ppq['id'].' and type='.PlayerProjectQueue::TYPE_GATHER_STAY.' and status=1'])->toArray();
			
			//检查是否有集结玩家
			/*if(!$otherPpqs){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}*/
			
			//核对终点信息
			$Map = new Map;
			$toMap = $Map->getByXy($ppq['target_info']['to_x'], $ppq['target_info']['to_y']);
			if(!$toMap){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			$moveType = 6;
			if($toMap['map_element_origin_id'] == 15){//玩家集结
				if($toMap['id'] != $ppq['target_info']['to_map_id'] || $toMap['player_id'] != $ppq['target_info']['to_player_id']){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				
				//检查对手玩家是否敌对
				$player = $Player->getByPlayerId($ppq['player_id']);
				$player2 = $Player->getByPlayerId($toMap['player_id']);
				if(!$player || !$player2){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				if(!$player['guild_id'] || $ppq['guild_id'] != $player['guild_id'] || $player['guild_id'] == $player2['guild_id']){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				$gotoType = PlayerProjectQueue::TYPE_GATHERBATTLE_GOTO;
				
				//通知
				if($player2['guild_id']){
					$PlayerProjectQueue->noticeFight(2, $player2['guild_id']);
				}else{
					$PlayerProjectQueue->noticeFight(1, $player2['id']);
				}
				$pushId = (new PlayerPush)->add($toMap['player_id'], 2, 400007, []);
				
				socketSend(['Type'=>'attacked', 'Data'=>['playerId'=>[$player2['id']]]]);
			}elseif(in_array($toMap['map_element_origin_id'], [1])){//集结攻堡垒
				//判断是否为指定联盟
				if($toMap['guild_id'] != $ppq['target_info']['to_guild_id']){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				$gotoType = PlayerProjectQueue::TYPE_ATTACKBASEGATHER_GOTO;
				
				
				//预警邮件
				$player = $Player->getByPlayerId($ppq['player_id']);
				//获取我方联盟信息
				if($player['guild_id']){
					$guild = (new Guild)->getGuildInfo($player['guild_id']);
					$guildId = $player['guild_id'];
					$guildName = $guild['name'];
					$guildShort = $guild['short_name'];
				}else{
					$guildId = 0;
					$guildName = '';
					$guildShort = '';
				}
				
				//获取对方联盟所有成员
				$PlayerGuild = new PlayerGuild;
				$players = $PlayerGuild->getAllGuildMember($toMap['guild_id']);
				if(!$players){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				$playerIds = array_keys($players);
				
				//发送邮件
				$mailData = [
					'x'=>$toMap['x'],
					'y'=>$toMap['y'],
					'playerNick'=>$player['nick'],
					'playerAvatar'=>$player['avatar_id'],
					'guildId'=>$guildId,
					'guildName'=>$guildName,
					'guildShort'=>$guildShort,
				];
				if(!(new PlayerMail)->sendSystem($playerIds, PlayerMail::TYPE_ATTACKBASEWARN, '', '', 0, $mailData)){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				
				//通知
				$PlayerProjectQueue->noticeFight(2, $toMap['guild_id']);
			}elseif(in_array($toMap['map_element_origin_id'], [18, 19])){//王战集结
				//判断是否是王战状态
				$King = new King;
				$king = $King->getCurrentBattle();
				if(!$king){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
			
				$KingTown = new KingTown;
				$town = $KingTown->getByXy($ppq['target_info']['to_x'], $ppq['target_info']['to_y']);
				if(!$town){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				$gotoType = PlayerProjectQueue::TYPE_KINGGATHERBATTLE_GOTO;
			}elseif(in_array($toMap['map_element_origin_id'], [17])){//boss
				$gotoType = PlayerProjectQueue::TYPE_BOSSGATHER_GOTO;
				$moveType = 2;
			}else{
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			//计算所有部队最慢的
			$needTime = PlayerProjectQueue::calculateMoveTime($ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $ppq['target_info']['to_x'], $ppq['target_info']['to_y'], $moveType, $ppq['army_id']);
			if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			foreach($otherPpqs as $_q){
				$_needTime = PlayerProjectQueue::calculateMoveTime($_q['player_id'], $_q['to_x'], $_q['to_y'], $ppq['target_info']['to_x'], $ppq['target_info']['to_y'], $moveType, $_q['army_id']);
				if(!$_needTime){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$needTime = max($needTime, $_needTime);
			}
			
			//建立主出发队列
			$extraData = [
				'from_map_id' => $ppq['to_map_id'],
				'from_x' => $ppq['to_x'],
				'from_y' => $ppq['to_y'],
				'to_map_id' => $ppq['target_info']['to_map_id'],
				'to_x' => $ppq['target_info']['to_x'],
				'to_y' => $ppq['target_info']['to_y'],
			];
			$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], $toMap['player_id'], $gotoType, $needTime, $ppq['army_id'], $ppq['target_info'], $extraData);
			
			//建立集结玩家出发队列
			$extraData['parent_queue_id'] = $PlayerProjectQueue->id;
			foreach($otherPpqs as $_q){
				$PlayerProjectQueue->addQueue($_q['player_id'], $_q['guild_id'], $toMap['player_id'], PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO, $needTime, $_q['army_id'], $ppq['target_info'], $extraData);
			}
			
			
			finishQueue:
			//更新队列完成
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
			//更新其他玩家完成
			foreach($otherPpqs as $_q){
				$PlayerProjectQueue->finishQueue($_q['player_id'], $_q['id']);
			}
			
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
     * 前往集结玩家
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
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

			//获取目标地图信息
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//核对目标玩家信息
			if($map['player_id'] != $ppq['target_player_id'] || $map['map_element_origin_id'] != 15){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否为盟友
			/*$player = $Player->getByPlayerId($ppq['player_id']);
			$player2 = $Player->getByPlayerId($ppq['target_player_id']);
			if(!$player || !$player2){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			if($player['guild_id'] != $player2['guild_id']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}*/
			if(!(new PlayerGuild)->isSameGuild($ppq['player_id'], $ppq['target_player_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查集结玩家数量
			$playerNum = PlayerProjectQueue::count(['parent_queue_id='.$ppq['parent_queue_id'].' and type='.PlayerProjectQueue::TYPE_GATHER_STAY.' and status=1']);
			$maxNum = (new PlayerBuild)->getMaxGatherNum($ppq['target_player_id']);
			if($playerNum+1 >= $maxNum){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//查看是否已经有该玩家的子队伍
			$alreadyStayNum = PlayerProjectQueue::count(['player_id='.$ppq['player_id'].' and parent_queue_id='.$ppq['parent_queue_id'].' and type='.PlayerProjectQueue::TYPE_GATHER_STAY.' and status=1']);
			if($alreadyStayNum){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查父队列是否存在
			if(!($parentPpq = PlayerProjectQueue::findFirst(['id='.$ppq['parent_queue_id'].' and status=1']))){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			$parentPpq = $parentPpq->toArray();
			
			//新建集结中队列
			$extraData = array(
				'from_map_id' => $ppq['to_map_id'],
				'from_x' => $ppq['to_x'],
				'from_y' => $ppq['to_y'],
				'to_map_id' => $ppq['to_map_id'],
				'to_x' => $ppq['to_x'],
				'to_y' => $ppq['to_y'],
				'parent_queue_id' => $ppq['parent_queue_id'],
			);
			$second = ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>'0000-00-00 00:00:00'];//max(0, strtotime($parentPpq['end_time']) - time());
			$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, PlayerProjectQueue::TYPE_GATHER_STAY, $second, $ppq['army_id'], $ppq['target_info'], $extraData);
			
			
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
     * 集结攻城
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _battle($ppq){
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

			//获得子队列
			$otherPpqs = PlayerProjectQueue::find(['parent_queue_id='.$ppq['id'].' and type='.PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO.' and status=1'])->toArray();
			$otherPpqs = $PlayerProjectQueue->afterFindQueue($otherPpqs);
			
			//获取目标地图信息
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				$this->sendNoTargetMail(array($ppq)+$otherPpqs);
				goto finishQueue;
			}
			
			//检查坐标是否对应玩家
			if($ppq['target_player_id'] != $map['player_id'] || $map['map_element_origin_id'] != 15){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				$this->sendNoTargetMail(array($ppq)+$otherPpqs);
				goto finishQueue;
			}
			
			//检查是否同盟
			if((new PlayerGuild)->isSameGuild($ppq['player_id'], $ppq['target_player_id'])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//检查敌方是否开保护
			if($Player->isAvoidBattle($Player->getByPlayerId($ppq['target_player_id']))){
			//if((new PlayerBuff)->isAvoidBattle($ppq['target_player_id'])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				$this->sendProtectMail(array($ppq)+$otherPpqs);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			if(!(new PlayerArmyUnit)->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//获得敌方援助队列
			$enemyPpqs = PlayerProjectQueue::find(['target_player_id='.$ppq['target_player_id'].' and type='.PlayerProjectQueue::TYPE_CITYASSIST_ING.' and status=1'])->toArray();
			$enemyPpqs = $PlayerProjectQueue->afterFindQueue($enemyPpqs);
			
			//battle
			$ppq1 = array_merge([$ppq], $otherPpqs);
			$ppq2 = $enemyPpqs;
			$battleRet = $this->createArmyBattle(2, $ppq['player_id'], $ppq['target_player_id'], $ppq1, $ppq2, $map);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			/*if(isset($battleRet) && $battleRet['result']){
				//抢夺和氏璧
				if((new Activity)->robHsb($ppq['target_player_id'])){
					@$battleRet['attackData'][$ppq['player_id']]['carry_item'][] = (new Activity)->hsbDrop;
				}
			}*/
						
			//建立回家队列
			$this->createArmyReturn($ppq, $battleRet['attackData'][$ppq['player_id']]);
			foreach($otherPpqs as $_ppq){
				$this->createArmyReturn($_ppq, $battleRet['attackData'][$_ppq['player_id']]);
			}
						
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
				}else{
					$battleFlag = 2;
				}
			}
			
			(new PlayerTarget)->updateTargetCurrentValue($ppq['player_id'], 25, 1);
			
			finishQueue:
			//更新队列完成
			
			if(!isset($battleFlag))
				$battleFlag = 0;
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id'], $battleFlag);
			
			//更新其他玩家完成
			foreach($otherPpqs as $_q){
				$PlayerProjectQueue->finishQueue($_q['player_id'], $_q['id']);
			}
			
			//分析驻守部队，死完的回家
			$this->createDieEnemyReturn($enemyPpqs, $battleRet);
			
			socketSend(['Type'=>'finishattacked', 'Data'=>['playerId'=>[$map['player_id']]]]);
			
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
     * 单人攻城战
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _cityBattle($ppq){
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

			//获取目标地图信息
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否对应玩家
			if($ppq['target_player_id'] != $map['player_id'] || $map['map_element_origin_id'] != 15){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查是否同盟
			if((new PlayerGuild)->isSameGuild($ppq['player_id'], $ppq['target_player_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查敌方是否开保护
			if($Player->isAvoidBattle($Player->getByPlayerId($ppq['target_player_id']))){
			//if((new PlayerBuff)->isAvoidBattle($ppq['target_player_id'])){
				$this->createArmyReturn($ppq);
				$this->sendProtectMail(array($ppq));
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			if(!(new PlayerArmyUnit)->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//获得敌方援助队列
			$enemyPpqs = PlayerProjectQueue::find(['target_player_id='.$ppq['target_player_id'].' and type='.PlayerProjectQueue::TYPE_CITYASSIST_ING.' and status=1'])->toArray();
			$enemyPpqs = $PlayerProjectQueue->afterFindQueue($enemyPpqs);
			
			//battle
			$ppq1 = [$ppq];
			$ppq2 = $enemyPpqs;
			$battleRet = $this->createArmyBattle(1, $ppq['player_id'], $ppq['target_player_id'], $ppq1, $ppq2, $map);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			/*if(isset($battleRet) && $battleRet['result']){
				//抢夺和氏璧
				if((new Activity)->robHsb($ppq['target_player_id'])){
					@$battleRet['attackData'][$ppq['player_id']]['carry_item'][] = (new Activity)->hsbDrop;
				}
			}*/
						
			//建立回家队列
			$this->createArmyReturn($ppq, $battleRet['attackData'][$ppq['player_id']]);
			
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
				}else{
					$battleFlag = 2;
				}
			}
			
			(new PlayerTarget)->updateTargetCurrentValue($ppq['player_id'], 25, 1);
			
			finishQueue:
			//更新队列完成
			
			if(!isset($battleFlag))
				$battleFlag = 0;
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id'], $battleFlag);
			
			//分析驻守部队，死完的回家
			if($enemyPpqs)
				$this->createDieEnemyReturn($enemyPpqs, $battleRet);
						
			socketSend(['Type'=>'finishattacked', 'Data'=>['playerId'=>[$map['player_id']]]]);
			
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
     * 攻击堡垒
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _baseBattle($ppq){
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

			//如果是集结，获取子队列
			$otherPpqs = [];
			if($ppq['type'] == PlayerProjectQueue::TYPE_ATTACKBASEGATHER_GOTO){
				$otherPpqs = PlayerProjectQueue::find(['parent_queue_id='.$ppq['id'].' and type='.PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO.' and status=1'])->toArray();
				$otherPpqs = $PlayerProjectQueue->afterFindQueue($otherPpqs);
			}
			
			//获取目标地图信息
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否对应联盟
			if($ppq['target_info']['to_guild_id'] != $map['guild_id'] || $map['map_element_origin_id'] != 1){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查是否同盟
			$pg = $Player->getByPlayerId($ppq['player_id']);
			if($pg){
				if($pg['guild_id'] == $map['guild_id']){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
			}
			
			//保护机制，部队是否存在
			if(!(new PlayerArmyUnit)->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//获得敌方援助队列
			$enemyPpqs = PlayerProjectQueue::find(['to_map_id='.$map['id'].' and type in ('.PlayerProjectQueue::TYPE_GUILDBASE_BUILD.', '.PlayerProjectQueue::TYPE_GUILDBASE_REPAIR.', '.PlayerProjectQueue::TYPE_GUILDBASE_DEFEND.') and status=1 and (end_time > now() or end_time = "0000-00-00 00:00:00")'])->toArray();
			$enemyPpqs = $PlayerProjectQueue->afterFindQueue($enemyPpqs);
			
			//清算耐久值
			if($enemyPpqs){
				$PlayerProjectQueue->calculateGuildBaseConstructValue($enemyPpqs[0]);//更新堡垒城防值
			}
			
			//battle
			$ppq1 = array_merge([$ppq], $otherPpqs);
			$ppq2 = $enemyPpqs;
			$battleRet = $this->createArmyBattle(3, $ppq['player_id'], $map['guild_id'], $ppq1, $ppq2, $map);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			//建立回家队列
			$this->createArmyReturn($ppq, $battleRet['attackData'][$ppq['player_id']]);
			foreach($otherPpqs as $_ppq){
				$this->createArmyReturn($_ppq, $battleRet['attackData'][$_ppq['player_id']]);
			}
			
			//清算防御部队
			if($enemyPpqs){
				$PlayerProjectQueue->updateGuildBaseEndTime($enemyPpqs[0]);//更新堡垒建造或修复的结束时间
			}
			
			//分析驻守部队，死完的回家
			$this->createDieEnemyReturn($enemyPpqs, $battleRet);
									
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
					//胜利，堡垒扣除耐久值
					/*$armies = [];
					foreach($ppq1 as $_ppq){
						$armies[$_ppq['player_id']] = $_ppq['army_id'];
					}
					$subHp = $PlayerProjectQueue->calculcateBaseAttackValue($armies);
					*/
					//扣除耐久值todo
					if(@$battleRet['subHp']){
						$map['durability'] = max(0, $map['durability'] - $battleRet['subHp']);
						if(!$Map->alter($map['id'], $map)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
				}else{
					$battleFlag = 2;
				}
			}
			
			finishQueue:
			//更新队列完成
			
			if(!isset($battleFlag))
				$battleFlag = 0;
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id'], $battleFlag);
			
			//更新其他玩家完成
			foreach($otherPpqs as $_q){
				$PlayerProjectQueue->finishQueue($_q['player_id'], $_q['id']);
			}
			

						
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
     * 召回中转集结者家
     * 
     * 
     * @return <type>
     */
	public function _backMid($ppq){
		StaticData::$delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			$this->createArmyReturn($ppq);
			
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
	public function createArmyReturn($ppq, $data=array()){
		//获取我的主城位置
		$Player = new Player;
		$player = $Player->getByPlayerId($ppq['player_id']);
		if(!$player){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//计算时间
		if(@$ppq['target_info']['backNow']){
			$needTime = 0;
		}elseif($ppq['to_x'] != $player['x'] || $ppq['to_y'] != $player['y']){
			$needTime = PlayerProjectQueue::calculateMoveTime($ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], 3, $ppq['army_id']);
			/*if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
		}else{
			$needTime = 0;
		}
		
		//建立队列
		$PlayerProjectQueue = new PlayerProjectQueue;
		$data = $PlayerProjectQueue->mergeExtraInfo($ppq, $data);
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
		/*if(@$data['carry_soldier']){
			foreach($data['carry_soldier'] as $_t => $_s){
				@$extraData['carry_soldier'][$_t] += $_s;
			}
		}*/
		$extraData = $extraData + $data;
		
		if($ppq['type'] == PlayerProjectQueue::TYPE_CITYBATTLE_GOTO){
			$type = PlayerProjectQueue::TYPE_CITYBATTLE_RETURN;
		}elseif($ppq['type'] == PlayerProjectQueue::TYPE_CITYASSIST_ING){
			$type = PlayerProjectQueue::TYPE_CITYASSIST_RETURN;
		}elseif($ppq['type'] == PlayerProjectQueue::TYPE_ATTACKBASE_GOTO){
			$type = PlayerProjectQueue::TYPE_ATTACKBASE_RETURN;
		}elseif(in_array($ppq['type'], [PlayerProjectQueue::TYPE_GUILDBASE_BUILD, PlayerProjectQueue::TYPE_GUILDBASE_REPAIR, PlayerProjectQueue::TYPE_GUILDBASE_DEFEND])){
			$type = PlayerProjectQueue::TYPE_GUILDBASE_RETURN;
		}else{
			$type = PlayerProjectQueue::TYPE_GATHER_RETURN;
		}
		$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData);
		return true;
	}
	
	public function createDieEnemyReturn($ppqs, $battleRet){
		$PlayerArmyUnit = new PlayerArmyUnit;
		foreach($ppqs as $_ppq){
			$pau = $PlayerArmyUnit->getByArmyId($_ppq['player_id'], $_ppq['army_id']);
			$_die = true;
			foreach($pau as $_pau){
				if($_pau['soldier_id'] && $_pau['soldier_num']){
					$_die = false;
					break;
				}
			}
			if($_die){//死完兵，回家
				(new PlayerProjectQueue)->finishQueue($_ppq['player_id'], $_ppq['id']);
				$this->createArmyReturn($_ppq, $battleRet['defenceData'][$_ppq['player_id']]);
			}
		}
	}
	
	public function createArmyBattle($type, $attackerId, $defenderId, $ppq1s, $ppq2s, $map){
		$PlayerProjectQueue = new PlayerProjectQueue;
		$retData = [];
		$extraData = [];
		$attackPlayerList = [];
		$defendPlayerList = [];
		$Player = new Player;
		if($type == 3){//堡垒
			//$defendPlayerList = $defenderId;
			$battleType = 3;
		}else{//攻城
			$defendPlayerList = [$defenderId=>0];
			$battleType = 1;
		}
		foreach($ppq1s as $_ppq){
			$attackPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
		}
		if($type==3 && $ppq2s){
			$maxPowerPlayer = 0;
			$maxPower = 0;
			foreach($ppq2s as $_ppq){
				$defendPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
				$_power = $Player->getByPlayerId($_ppq['player_id'])['power'];
				if($_power > $maxPower){
					$maxPower = $_power;
					$maxPowerPlayer = $_ppq['player_id'];
				}
			}
			//power最强玩家排在在前，buff取该玩家
			$_defendPlayerList = $defendPlayerList;
			unset($_defendPlayerList[$maxPowerPlayer]);
			$defendPlayerList = [$maxPowerPlayer=>$defendPlayerList[$maxPowerPlayer]] + $_defendPlayerList;
		}else{
			foreach($ppq2s as $_ppq){
				$defendPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
			}
		}
		
		if($type == 3 && !$defendPlayerList){
			$defendPlayerList = $defenderId;
		}
		
		if($battleType == 1){
			//获取攻击方最大城堡等级
			$PlayerBuild = new PlayerBuild;
			$maxLevel = 0;
			foreach($ppq1s as $_ppq){
				$_lv = $PlayerBuild->getPlayerCastleLevel($_ppq['player_id']);
				if($_lv > $maxLevel){
					$maxLevel = $_lv;
				}
			}
			//获取防守城等级
			$defendLv = $PlayerBuild->getPlayerCastleLevel($defenderId);
			
			if($maxLevel >= $defendLv){
				$powerBefore = (new Power)->getSoldier($defenderId);
			}
		}
		/*$retData['defenceData'] = array(
			$defenderId=>[
				'carry_soldier'=>['10001'=>1],
			],
		);*/
		/*foreach($ppq2s as $_ppq){
			$retData['defenceData'][$_ppq['player_id']] = ['carry_soldier'=>['10001'=>1]];
		}
		foreach($ppq1s as $_ppq){
			$retData['attackData'][$_ppq['player_id']] = ['carry_soldier'=>['10001'=>2], 'carry_wood'=>100];
		}*/
		/*$retData['attackData'] = array(
			$attackerId=>[
				'carry_soldier'=>['10001'=>1],
			],
		);*/
		
		
		$Battle = new Battle;
		try {
			$ex = [];
			if($battleType == 3){
				$ex['basePosition'] = ['dx'=>$map['x'], 'dy'=>$map['y']];
			}
			if($battleType == 1){
				$ex['attackCastleLv'] = $maxLevel;
				$ex['defendCastleLv'] = $defendLv;
			}
			$ret = $Battle->battleCore($attackPlayerList, $defendPlayerList, $battleType, $ex);
		}catch (Exception $e) {
			//if(!$e->getCode()){
				echo $e->getMessage();
				echo 'type:'.$battleType;
				var_dump($attackPlayerList);
				var_dump($defendPlayerList);
				//var_dump($ret);
//			}
		}
		if(!$ret)
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		
		if($type == 3 && $ret['win']){
			//胜利，堡垒扣除耐久值
			$armies = [];
			foreach($ppq1s as $_ppq){
				$armies[$_ppq['player_id']] = $_ppq['army_id'];
			}
			$retData['subHp'] = $PlayerProjectQueue->calculcateBaseAttackValue($armies, $map);
			$extraData['oldDurability'] = $map['durability'];
			$extraData['newDurability'] = max(0, $map['durability'] - $retData['subHp']);
			
		}
		
		
		//战报
		if($type == 3){
			$defendPlayerList = $defenderId;
		}
		
		$retData['attackData'] = [];
		$retData['defenceData'] = [];
		
		//抢夺和氏璧
		if(in_array($type, [1, 2]) && $ret['win']){
			$hsbNum = (new Activity)->robHsb($defenderId);
			if($hsbNum){
				$_hsbDrop = (new Activity)->hsbDrop;
				$_hsbDrop[2] = $hsbNum;
				@$retData['attackData'][$attackerId]['carry_item'][] = $_hsbDrop;
				$extraData['item'][$attackerId] = $retData['attackData'][$attackerId]['carry_item'];
			}
		}
				
		//顽强斗志buff
		$protectOpen = false;
		if($battleType == 1){
			$protectOpen = (new PlayerBuffTemp)->addWQDZbuff($defenderId, $powerBefore, $ret['dSoldierLosePower']/DIC_DATA_DIVISOR);
		}

		$extraData['aLosePower'] = $ret['aLosePower'];
		$extraData['dLosePower'] = $ret['dLosePower'];
		$extraData['battleLogId'] = $ret['battleLogId'];
		$extraData['godGeneralSkillArr'] = $ret['godGeneralSkillArr'];
		$extraData['noobProtect'] = $ret['noobProtect'];
		$extraData['protectOpen'] = $protectOpen;
		(new PlayerMail)->sendPVPBattleMail(array_keys($ret['aFormatList']), is_array($defendPlayerList) ? array_keys($defendPlayerList) : $defendPlayerList, $ret['win'], $battleType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['resource'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
		
		//解析伤兵
		foreach($ret['aList'] as $_list){
			@$retData['attackData'][$_list['playerId']]['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
		}
		foreach($ret['dList'] as $_list){
			@$retData['defenceData'][$_list['playerId']]['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
		}
		
		//if win
		$attackerIds = [];
		foreach($ppq1s as $_ppq){
			$attackerIds[] = $_ppq['player_id'];
		}
		$defenderIds = [$defenderId];
		foreach($ppq2s as $_ppq){
			$defenderIds[] = $_ppq['player_id'];
		}
		if($ret['win']){
			$retData['result'] = true;//win
			//解析资源
			foreach($ret['resource'] as $_pid => $_r){
				foreach($_r as $_type => $_num){
					@$retData['attackData'][$_pid]['carry_'.$_type] += $_num;
				}
				if($_pid){
					(new PlayerMission)->updateMissionNumber($_pid, 7, array_sum($_r));
					(new PlayerTarget)->updateTargetCurrentValue($_pid, 28, array_sum($_r));
				}
			}
			
			if(count($ppq1s) >= 3){
				foreach($ppq1s as $_ppq){
					(new PlayerMission)->updateMissionNumber($_ppq['player_id'], 15, 1);
				}
			}
			
			//sendNotice
			$this->sendNotice($attackerIds, 'battleWin');
			$this->sendNotice($defenderIds, 'battleLose');
			
		}else{
			//if lose
			$retData['result'] = false;//lose
			
			//sendNotice
			$this->sendNotice($defenderIds, 'battleWin');
			$this->sendNotice($attackerIds, 'battleLose');
		}
		
		//更新防守方的队列信息
		foreach($ppq2s as $_ppq){
			if(!isset($retData['defenceData'][$_ppq['player_id']])) continue;
			$extra = $PlayerProjectQueue->mergeExtraInfo($_ppq, $retData['defenceData'][$_ppq['player_id']]);
			if(!$PlayerProjectQueue->assign($_ppq)->updateQueue(false, false, $extra)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
		}
		
		//走马灯公告
		$data = ['type'=>1, 'battle_win'=>$ret['win'], 'battle_attacker_power_loss'=>$ret['aLosePower'], 'battle_defender_power_loss'=>$ret['dLosePower']];
		if($type == 3){
			$data['battle_type'] = 3;
			$data['battle_defender_guild_id'] = $map['guild_id'];
		}else{
			$data['battle_type'] = 2;
			$data['battle_defender_id'] = $defenderId;
			$data['battle_hsb_num'] = @$hsbNum*1;
		}
		(new RoundMessage)->addNew($attackerId, $data);
		
		if($battleType == 1){//守城死伤礼包
			if(@$ret['dFormatList'][$defenderId]['losePower'] / DIC_DATA_DIVISOR >= 1000){
				(new PlayerInfo)->updateGiftBeginTime($defenderId, 'gift_lose_power_begin_time');
			}
		}
		
		//通知被攻城方
		if(in_array($battleType, [1, 2])){
			socketSend(['Type'=>'city_attacked', 'Data'=>['playerId'=>$defenderId]]);
		}
		
		return $retData;
	}
	
	public function sendNoTargetMail($ppqs){
		
	}
	
	public function sendProtectMail($ppqs){
		
	}
}