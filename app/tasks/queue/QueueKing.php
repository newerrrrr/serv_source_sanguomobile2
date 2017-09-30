<?php
/**
 * 王战
 */
class QueueKing extends DispatcherTask{
	const DEFENCENUM = 10;//最大驻守队伍
    /**
     * 玩家前往城寨
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _gotoTown($ppq){
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
			$otherPpqs = [];
			$defenceType = PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE;//单队防守类型
			
			//集结队列进入，需要获取所有子队列
			if($ppq['type'] == PlayerProjectQueue::TYPE_KINGGATHERBATTLE_GOTO){
				//$defenceType = PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE;
				$otherPpqs = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find(['parent_queue_id='.$ppq['id'].' and type='.PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO.' and status=1'])->toArray());
			}

			//获取目标地图信息
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			if(!(new PlayerArmyUnit)->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//核对目标是否是城寨
			if(!in_array($map['map_element_origin_id'], [18, 19])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//检查王战状态
			$King = new King;
			$king = $King->getCurrentBattle();
			if(!$king){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//获取城寨信息KingTown
			$KingTown = new KingTown;
			$town = $KingTown->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$town){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//检查城寨占领情况
			if($town['guild_id'] == $ppq['guild_id']){//我方占领
				//获取所有已驻防队列
				$defencePpqs = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find(['to_map_id='.$ppq['to_map_id'].' and type in ('.PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST.') and status=1'])->toArray());
				$defencePlayerIds = [];
				$_defencePpqs = [];
				foreach($defencePpqs as $_ppq){
					$defencePlayerIds[] = $_ppq['player_id'];
					$_defencePpqs[$_ppq['player_id']] = $_ppq;
				}
				$defencePpqs = $_defencePpqs;
				$i = count($defencePpqs);//城内已有队伍数量
				
				//玩家如果有队伍在城内。踢掉城内队伍
				$returnPpq = [];
				if(in_array($ppq['player_id'], $defencePlayerIds)){
					$PlayerProjectQueue->finishQueue($defencePpqs[$ppq['player_id']]['player_id'], $defencePpqs[$ppq['player_id']]['id']);
					$returnPpq[] = $defencePpqs[$ppq['player_id']];
					$i--;
				}
				foreach($otherPpqs as $_ppq){
					if(in_array($_ppq['player_id'], $defencePlayerIds)){
						$PlayerProjectQueue->finishQueue($defencePpqs[$_ppq['player_id']]['player_id'], $defencePpqs[$_ppq['player_id']]['id']);
						$returnPpq[] = $defencePpqs[$_ppq['player_id']];
						$i--;
					}
				}
				
				//城内满额，所有队伍返回
				if($i >= self::DEFENCENUM){
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					//遣返不符合条件的队列
					foreach($returnPpq as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				
				//循环所有当次队列
				$extraData = [
					'from_map_id' => $ppq['to_map_id'],
					'from_x' => $ppq['to_x'],
					'from_y' => $ppq['to_y'],
					'to_map_id' => $ppq['to_map_id'],
					'to_x' => $ppq['to_x'],
					'to_y' => $ppq['to_y'],
				];
				$needTime = ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>'0000-00-00 00:00:00'];
				$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $defenceType, $needTime, $ppq['army_id'], [], $extraData);
				$i++;//集结者自身
				foreach($otherPpqs as $_ppq){
					//判断是否有该玩家队列在驻防，判断驻防上限
					if(/*!in_array($_ppq['player_id'], $defencePlayerIds) && */$i < self::DEFENCENUM){
						$PlayerProjectQueue->addQueue($_ppq['player_id'], $_ppq['guild_id'], 0, /*PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST*/PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE, $needTime, $_ppq['army_id'], [], $extraData);
						$i++;
					}else{
						$returnPpq[] = $_ppq;
					}
					
				}
				//遣返不符合条件的队列
				foreach($returnPpq as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				
			}elseif(!$town['guild_id']){//npc占领
                if($ppq['type'] == PlayerProjectQueue::TYPE_KINGTOWN_GOTO){//单人去城寨。不让攻击
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				//建立npc战斗
				$ppq1s = array_merge([$ppq], $otherPpqs);
				$battleRet = $this->createArmyBattle(2, $ppq['player_id'], $town['id'], $ppq1s);
				if(!$battleRet){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				if($battleRet['result']){//如果胜利，建立驻防队列
					$battleFlag = 1;
					
					//建立主出发队列
					$data = $PlayerProjectQueue->mergeExtraInfo($ppq, $battleRet['attackData'][$ppq['player_id']]);
					$extraData = [
						'from_map_id' => $ppq['to_map_id'],
						'from_x' => $ppq['to_x'],
						'from_y' => $ppq['to_y'],
						'to_map_id' => $ppq['to_map_id'],
						'to_x' => $ppq['to_x'],
						'to_y' => $ppq['to_y'],
					];
					$_extraData = $extraData + $data;
					$needTime = ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>'0000-00-00 00:00:00'];
					$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $defenceType, $needTime, $ppq['army_id'], [], $_extraData);
					
					//建立集结玩家出发队列
					$extraData['parent_queue_id'] = $PlayerProjectQueue->id;
					$i = 1;
					foreach($otherPpqs as $_q){
						if($i < self::DEFENCENUM && !$this->isArmyEmpty($_q)){//建立驻防队列
							$data = $PlayerProjectQueue->mergeExtraInfo($_q, $battleRet['attackData'][$_q['player_id']]);
							$_extraData = $extraData + $data;
							$PlayerProjectQueue->addQueue($_q['player_id'], $_q['guild_id'], 0, /*PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST*/PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE, $needTime, $_q['army_id'], [], $_extraData);
							$i++;
						}else{//遣返不符合条件的队列
							$this->createArmyReturn($_q, $battleRet['attackData'][$_q['player_id']]);
						}
					}
					
					//修改town属性
					if(!$KingTown->assign($town)->upOwner($king['id'], $ppq['guild_id'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					//修改map属性
					$map['guild_id'] = $ppq['guild_id'];
					if(!$Map->alter($map['id'], $map)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}else{//如果失败，建立遣返队列
					$battleFlag = 2;
					
					$this->createArmyReturn($ppq, $battleRet['attackData'][$ppq['player_id']]);
					
					//建立集结玩家出发队列
					foreach($otherPpqs as $_q){
						$this->createArmyReturn($_q, $battleRet['attackData'][$_q['player_id']]);
					}
				}
			}else{//其他联盟占领
				 if($ppq['type'] == PlayerProjectQueue::TYPE_KINGTOWN_GOTO){//单人去城寨。不让攻击
					$this->createArmyReturn($ppq);
					foreach($otherPpqs as $_ppq){
						$this->createArmyReturn($_ppq);
					}
					goto finishQueue;
				}
				//获取敌方部队
				$ppq2s = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find(['to_map_id='.$ppq['to_map_id'].' and type in ('.PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST.') and status=1', 'order'=>'id asc'])->toArray());
				//建立pvp战斗
				$ppq1s = array_merge([$ppq], $otherPpqs);
				if($ppq2s){
					$battleRet = $this->createArmyBattle(1, $ppq['player_id'], 0, $ppq1s, $ppq2s);
					if(!$battleRet){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}else{
					@$battleRet['result'] = true;
				}
				if($battleRet['result']){//如果胜利，建立驻防队列
					$battleFlag = 1;
					
					//建立主出发队列
					$extraData = [
						'from_map_id' => $ppq['to_map_id'],
						'from_x' => $ppq['to_x'],
						'from_y' => $ppq['to_y'],
						'to_map_id' => $ppq['to_map_id'],
						'to_x' => $ppq['to_x'],
						'to_y' => $ppq['to_y'],
					];
					$data = $PlayerProjectQueue->mergeExtraInfo($ppq, @$battleRet['attackData'][$ppq['player_id']]);
					$_extraData = $extraData + $data;
					$needTime = ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>'0000-00-00 00:00:00'];
					$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $defenceType, $needTime, $ppq['army_id'], [], $_extraData);
					
					//建立集结玩家出发队列
					$extraData['parent_queue_id'] = $PlayerProjectQueue->id;
					$i = 1;
					foreach($otherPpqs as $_q){
						if($i < self::DEFENCENUM && !$this->isArmyEmpty($_q)){//建立驻防队列
							$data = $PlayerProjectQueue->mergeExtraInfo($_q, @$battleRet['attackData'][$_q['player_id']]);
							$_extraData = $extraData + $data;
							$PlayerProjectQueue->addQueue($_q['player_id'], $_q['guild_id'], 0, /*PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST*/PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE, $needTime, $_q['army_id'], [], $_extraData);
							$i++;
						}else{//遣返不符合条件的队列
							$this->createArmyReturn($_q, @$battleRet['attackData'][$_q['player_id']]);
						}
					}
					
					//建立遣返原玩家队列
					foreach($ppq2s as $_ppq){
						$PlayerProjectQueue->finishQueue($_ppq['player_id'], $_ppq['id']);
						$this->createArmyReturn($_ppq, $battleRet['defenceData'][$_ppq['player_id']]);
					}
					
					//修改town属性
					if(!$KingTown->assign($town)->upOwner($king['id'], $ppq['guild_id'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					//修改map属性
					$map['guild_id'] = $ppq['guild_id'];
					if(!$Map->alter($map['id'], $map)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
				}else{//如果失败，建立遣返队列
					$battleFlag = 2;
					
					$this->createArmyReturn($ppq, $battleRet['attackData'][$ppq['player_id']]);
					
					//建立集结玩家出发队列
					foreach($otherPpqs as $_q){
						$this->createArmyReturn($_q, $battleRet['attackData'][$_q['player_id']]);
					}
					
					foreach($ppq2s as $_ppq){
						//遣返防守全灭军团
						if($this->isArmyEmpty($_ppq)){
							$PlayerProjectQueue->finishQueue($_ppq['player_id'], $_ppq['id']);
							$this->createArmyReturn($_ppq, $battleRet['defenceData'][$_ppq['player_id']]);
						}else{//更新伤员
							$extra = $PlayerProjectQueue->mergeExtraInfo($_ppq, $battleRet['defenceData'][$_ppq['player_id']]);
							if(!$PlayerProjectQueue->assign($_ppq)->updateQueue(false, false, $extra)){
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							}
						}
					}
				}
			}
			if(isset($battleRet) && $battleRet['result']){//如果胜利
				//通知所有参与王战联盟成员积分增长发生变化
				$this->noticeAllKingGuild();
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
     * npc攻打城寨
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _npcAttack($ppq){
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
				goto finishQueue;
			}
			
			//核对目标信息
			if(!in_array($map['map_element_origin_id'], [18, 19])){
				goto finishQueue;
			}
			
			//检查王战状态
			$King = new King;
			$king = $King->getCurrentBattle();
			if(!$king){
				goto finishQueue;
			}
			
			$KingTown = new KingTown;
            $town = $KingTown->getByXy($ppq['to_x'], $ppq['to_y']);
            if(!$town){
                goto finishQueue;
            }

            //检查城寨占领情况
			if(!$town['guild_id']){//npc占领
				goto finishQueue;
			}else{//其他联盟占领
				//获取敌方部队
				$ppq2s = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find(['to_map_id='.$ppq['to_map_id'].' and type in ('.PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST.') and status=1'])->toArray());
				//建立pvp战斗
				$battleRet = $this->createArmyBattle(3, $ppq['player_id'], ($ppq2s ? $ppq2s[0]['player_id'] : 0), [], $ppq2s, $map);
				if(!$battleRet){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($battleRet['result']){//如果胜利，遣返防守部队，重置npc防守部队
					$battleFlag = 1;
										
					//建立遣返原玩家队列
					foreach($ppq2s as $_ppq){
						$PlayerProjectQueue->finishQueue($_ppq['player_id'], $_ppq['id']);
						$this->createArmyReturn($_ppq, $battleRet['defenceData'][$_ppq['player_id']]);
					}
					
					//修改town属性
					if(!$KingTown->assign($town)->upOwner($king['id'], 0)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					//重置npc防守部队
					$KingTown->resetTown($town['id'], $ppq['player_id']);
					
					//修改map属性
					$map['guild_id'] = 0;
					if(!$Map->alter($map['id'], $map)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}else{//如果失败，
					$battleFlag = 2;
					
					foreach($ppq2s as $_ppq){
						//遣返防守全灭军团
						if($this->isArmyEmpty($_ppq)){
							$PlayerProjectQueue->finishQueue($_ppq['player_id'], $_ppq['id']);
							$this->createArmyReturn($_ppq, $battleRet['defenceData'][$_ppq['player_id']]);
						}else{//更新伤员
							$extra = $PlayerProjectQueue->mergeExtraInfo($_ppq, $battleRet['defenceData'][$_ppq['player_id']]);
							if(!$PlayerProjectQueue->assign($_ppq)->updateQueue(false, false, $extra)){
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							}
						}
					}
				}
			}
			
			
			finishQueue:
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
	
    /**
     * 是否为空军团
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function isArmyEmpty($ppq){
		$PlayerArmyUnit = new PlayerArmyUnit;
		$pau = $PlayerArmyUnit->getByArmyId($ppq['player_id'], $ppq['army_id']);
		$_die = true;
		foreach($pau as $_pau){
			if($_pau['soldier_id'] && $_pau['soldier_num']){
				$_die = false;
				break;
			}
		}
		if($_die)
			return true;
		else
			return false;
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
		if($ppq['to_x'] != $player['x'] || $ppq['to_y'] != $player['y']){
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
		];
		$extraData = $extraData + $data;
		
		/*if($isGather){
			$type = PlayerProjectQueue::TYPE_GATHER_RETURN;
		}else{*/
			$type = PlayerProjectQueue::TYPE_KINGTOWN_RETURN;
		//}
		$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData);
		return true;
	}
	
    /**
     * 
     * 
     * @param <type> $type 1.pvp,2.player=>npc,3.npc=>player
     * @param <type> $attackerId 
     * @param <type> $defenderId 
     * @param <type> $ppq1s 
     * @param <type> $ppq2s 
     * 
     * @return <type>
     */
	public function createArmyBattle($type, $attackerId, $defenderId, $ppq1s=[], $ppq2s=[], $map=[]){
		$extraData = [];
		$retData = ['attackData'=>[], 'defenceData'=>[]];
		$attackPlayerList = [];
		$defendPlayerList = [];
		
		$Battle = new Battle;
		if($type == 1){//pvp
			foreach($ppq1s as $_ppq){
				$attackPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
			}
			if($ppq2s){
				$Player = new Player;
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
				
			}
			var_dump($attackPlayerList);
			var_dump($defendPlayerList);
			$ret = $Battle->battleCore($attackPlayerList, $defendPlayerList, 6);
			var_dump($ret);
			
			//战报
			$extraData['aLosePower'] = $ret['aLosePower'];
			$extraData['dLosePower'] = $ret['dLosePower'];
			$extraData['battleLogId'] = $ret['battleLogId'];
			$extraData['godGeneralSkillArr'] = $ret['godGeneralSkillArr'];
			(new PlayerMail)->sendPVPBattleMail(array_keys($ret['aFormatList']), array_keys($defendPlayerList), $ret['win'], 4, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['resource'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
			
		}elseif($type == 2){//pve
			foreach($ppq1s as $_ppq){
				$attackPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
			}
			var_dump($attackPlayerList);
			var_dump($defenderId);
			$ret = $Battle->battleCore($attackPlayerList, ['town_id'=>$defenderId], 4);
			var_dump($ret);
			//$ret = ['win'=>true];
			
			//战报
			$extraData['aLosePower'] = $ret['aLosePower'];
			$extraData['dLosePower'] = $ret['dLosePower'];
			$extraData['battleLogId'] = $ret['battleLogId'];
			(new PlayerMail)->sendPVPBattleMail(array_keys($ret['aFormatList']), $defenderId, $ret['win'], 5, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['resource'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
		}else{//npc攻打
			if($ppq2s){
				$Player = new Player;
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
				if(!empty($maxPowerPlayer)){
					$_defendPlayerList = $defendPlayerList;
					unset($_defendPlayerList[$maxPowerPlayer]);
					$defendPlayerList = [$maxPowerPlayer=>$defendPlayerList[$maxPowerPlayer]] + $_defendPlayerList;
				}
			}
            

			if(!$defendPlayerList){
				$noDefender = true;
				$defendPlayerList = $map['guild_id'];
			}
			var_dump(['id'=>$attackerId]);
			var_dump($defendPlayerList);
			$ret = $Battle->battleCore(['npc_id'=>$attackerId], $defendPlayerList, 5);
			var_dump($ret);
			//$ret = ['win'=>false];
			
			//战报
			if(!@$noDefender){
				$extraData['aLosePower'] = $ret['aLosePower'];
				$extraData['dLosePower'] = $ret['dLosePower'];
				$extraData['battleLogId'] = $ret['battleLogId'];
				$extraData['godGeneralSkillArr'] = $ret['godGeneralSkillArr'];
				(new PlayerMail)->sendPVPBattleMail($attackerId, array_keys($defendPlayerList), $ret['win'], 6, $ppq2s[0]['to_x'], $ppq2s[0]['to_y'], $ret['resource'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
			}
		}

		if(!$ret)
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		
		//解析伤兵
		if($type != 3 && @$ret['aList']){
			foreach($ret['aList'] as $_list){
				@$retData['attackData'][$_list['playerId']]['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
			}
		}
		if($type != 2 && @$ret['dList']){
			foreach($ret['dList'] as $_list){
				@$retData['defenceData'][$_list['playerId']]['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
			}
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
			
			if(count($ppq1s) >= 3){
				foreach($ppq1s as $_ppq){
					(new PlayerMission)->updateMissionNumber($_ppq['player_id'], 15, 1);
				}
			}
			
			//sendNotice
			if($type == 1 || $type == 2){
				$this->sendNotice($attackerIds, 'battleWin');
			}
			if($type == 1 || $type == 3){
				$this->sendNotice($defenderIds, 'battleLose');
			}
			
		}else{
			//if lose
			$retData['result'] = false;//lose
			
			//sendNotice
			if($type == 1 || $type == 3){
				$this->sendNotice($defenderIds, 'battleWin');
			}
			if($type == 1 || $type == 2){
				$this->sendNotice($attackerIds, 'battleLose');
			}
		}
		
		return $retData;
	}

	public function noticeAllKingGuild(){
		$GuildKingPoint = new GuildKingPoint;
		$all = $GuildKingPoint->find()->toArray();
		$PlayerGuild = new PlayerGuild;
		$playerIds = [];
		foreach($all as $_a){
			$_playerIds = array_keys($PlayerGuild->getAllGuildMember($_a['guild_id']));
			$playerIds = array_merge($playerIds, $_playerIds);
		}
		socketSend(['Type'=>'kingpoint', 'Data'=>['playerId'=>$playerIds]]);
	}
	
}