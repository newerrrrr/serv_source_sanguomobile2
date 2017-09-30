<?php
/**
 * 打怪
 */
class QueueNpc extends DispatcherTask{
	
    /**
     * npc
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _npcBattle($ppq){
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
			if($ppq['to_map_id'] != $map['id'] || !in_array($map['map_element_origin_id'], [14, 17])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				$this->sendNoTargetMail(array($ppq)+$otherPpqs);
				goto finishQueue;
			}
			if(!(new PlayerArmyUnit)->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			
			//获取npc信息
			$MapElement = new MapElement;
			$me = $MapElement->dicGetOne($map['map_element_id']);
			if(!$me){
				$this->createArmyReturn($ppq);
				foreach($otherPpqs as $_ppq){
					$this->createArmyReturn($_ppq);
				}
				goto finishQueue;
			}
			$Npc = new Npc;
			$npc = $Npc->dicGetOne($me['npc_id']);
			
			//battle
			$ppq1 = array_merge([$ppq], $otherPpqs);
			$ppq2 = ['npc_id'=>$npc['id']];
			$battleRet = $this->createArmyBattle(1, $ppq['player_id'], $npc['id'], $ppq1, $ppq2, $map, $npc);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			//建立回家队列
			$this->createArmyReturn($ppq, $battleRet['attackData'][$ppq['player_id']]);
			foreach($otherPpqs as $_ppq){
				$this->createArmyReturn($_ppq, $battleRet['attackData'][$_ppq['player_id']]);
			}
			
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
					//(new PlayerMission)->updateMissionNumber($ppq['player_id'], 25, $npc['id']);
					//增加怪物击杀数
					$Player->addMonsterKillCount($ppq['player_id'], 1);
					foreach($otherPpqs as $_ppq){
						$Player->addMonsterKillCount($_ppq['player_id'], 1);
					}
					
					//更新怪物等级
					if($map['map_element_origin_id'] == 14){
						$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
						$PlayerTimeLimitMatch->updateScore($ppq['player_id'], 6, $npc['monster_lv']);
						$player = $Player->getByPlayerId($ppq['player_id']);
						if($npc['monster_lv'] > $player['monster_lv']){
							$Player->alter($ppq['player_id'], ['monster_lv'=>$npc['monster_lv']]);
							(new PlayerTarget)->updateTargetCurrentValue($ppq['player_id'], 9, $npc['monster_lv'], false);
						}
						Cache::delPlayer($ppq['player_id'], 'findNpc');
					}
					
					//删除地图怪物
					if(!$Map->delMap($map['id'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}else{
					$battleFlag = 2;
					
					if($map['map_element_origin_id']==17){//boss战 更新血量
						$map['durability'] -= $battleRet['bossTotalTakeDamage'];
						if(!$Map->alter($map['id'], $map)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
				}
				if($map['map_element_origin_id']==17){
					$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
					if($map['durability']>$battleRet['bossTotalTakeDamage']){
						$d = $battleRet['bossTotalTakeDamage'];
					}else{
						$d = $map['durability'];
					}
					
					$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
					$addScore = $npc['monster_lv']*$d/$map['max_durability'];
					echo 'addScore:'.$addScore.'|';
					$PlayerTimeLimitMatch->updateScore($ppq['player_id'], 7, $addScore);
					foreach($otherPpqs as $_ppq){
						$PlayerTimeLimitMatch->updateScore($_ppq['player_id'], 7, $addScore);
					}
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
		}elseif(@$ppq['target_info']['quickMove']){
			$needTime = 1;
		}elseif($ppq['to_x'] != $player['x'] || $ppq['to_y'] != $player['y']){
			$needTime = PlayerProjectQueue::calculateMoveTime($ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], 2, $ppq['army_id']);
			/*if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
		}else{
			$needTime = 0;
		}
		
		if($ppq['type'] == PlayerProjectQueue::TYPE_NPCBATTLE_GOTO){
			$type = PlayerProjectQueue::TYPE_NPCBATTLE_RETURN;
		}else{
			$type = PlayerProjectQueue::TYPE_GATHER_RETURN;
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
		
		$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData);
		return true;
	}
	
	public function createArmyBattle($type, $attackerId, $defenderId, $ppq1s, $ppq2s, $map, $npc){
		$PlayerProjectQueue = new PlayerProjectQueue;
		$retData = [];
		$extraData = [];
		$attackPlayerList = [];
		$defendPlayerList = ['npc_id'=>$defenderId];
		foreach($ppq1s as $_ppq){
			$attackPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
		}
		
		$Battle = new Battle;
		try {
			if($map['map_element_origin_id'] == 17){
				$battleType = 8;
				$defendPlayerList['npc_hp'] = $map['durability'];
			}else{
				$battleType = 7;
			}
			$ret = $Battle->battleCore($attackPlayerList, $defendPlayerList, $battleType);

		}catch (Exception $e) {
			//if(!$e->getCode()){
				echo $e->getMessage();
//			}
			echo 'Exception:'.PHP_EOL;
			echo 'battleType:'.PHP_EOL;
			var_dump($battleType);
			echo 'attackPlayerList:'.PHP_EOL;
			var_dump($attackPlayerList);
			echo 'defendPlayerList:'.PHP_EOL;
			var_dump($defendPlayerList);
			echo 'ret:'.PHP_EOL;
			var_dump($ret);
		}
		if(!$ret)
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		
		
		//解析伤兵
		$retData['attackData'] = [];
		$carryItems = [];
		
		$Drop = new Drop;
		foreach($ret['aList'] as $_list){
			@$retData['attackData'][$_list['playerId']]['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
		}
		
		foreach($ppq1s as $_ppq){
			//打怪掉落/击杀奖励
			if($ret['win']){
				//$carryItem = [];
				//foreach($npc['drop'] as $_drop){
					$dropData = $Drop->rand($_ppq['player_id'], $npc['drop']);
					if($dropData === true){
						//return true;
					}else{
						if(false === $dropData){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						
						//组织carry_item
						foreach($dropData as $_dropData){
							//$carryItem[] = [$_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1];
							@$carryItems[$_ppq['player_id']][] = [$_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1];
						}
					}
				//}
				/*if($carryItem){
					$retData['attackData'][$_ppq['player_id']]['carry_item'] = $carryItem;
					$carryItems[$_ppq['player_id']] = $carryItem;
				}*/
				
				//打怪活动
				if($map['map_element_origin_id'] == 17){
					$dropData = (new ActivityConfigure)->getNpcDrop(2);
				}else{
					$dropData = (new ActivityConfigure)->getNpcDrop(1);
				}
				if(is_array($dropData)){
					foreach($dropData as $_dropData){
						@$carryItems[$_ppq['player_id']][] = [$_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1];
					}
				}
			}
			
			//打血奖励
			if($map['map_element_origin_id'] == 17){
				$BossNpcDrop = new BossNpcDrop;
				$bossNpcDrop = $BossNpcDrop->getByDamage($defenderId, $ret['bossTotalTakeDamage']);
				if($bossNpcDrop){
					//$carryItem = [];
					$dropData = $Drop->rand($_ppq['player_id'], $bossNpcDrop['boss_drop']);
					if($dropData === true){
						//return true;
					}else{
						if(false === $dropData){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						
						//组织carry_item
						foreach($dropData as $_dropData){
							//$carryItem[] = [$_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1];
							@$carryItems[$_ppq['player_id']][] = [$_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1];
						}
					}
					/*if($carryItem){
						$retData['attackData'][$_ppq['player_id']]['carry_item'] = array_merge(isset($retData['attackData'][$_ppq['player_id']]['carry_item']) ? $retData['attackData'][$_ppq['player_id']]['carry_item'] : [], $carryItem);
						$carryItems[$_ppq['player_id']] = array_merge(isset($carryItems[$_ppq['player_id']]) ? $carryItems[$_ppq['player_id']] : [], $carryItem);
					}*/
				}
			}
			@$retData['attackData'][$_ppq['player_id']]['carry_item'] = @$carryItems[$_ppq['player_id']] ? $carryItems[$_ppq['player_id']] : [];
		}
		
		//战报
		$defendPlayerList = $defenderId;
		$retData['bossTotalTakeDamage'] = $extraData['boss_lost_hp'] = floor(min($map['durability'], $ret['bossTotalTakeDamage']));
		$extraData['boss_left_hp'] = $map['durability'] - $extraData['boss_lost_hp'];
		$extraData['item'] = $carryItems;
		$extraData['aLosePower'] = $ret['aLosePower'];
		$extraData['dLosePower'] = $ret['dLosePower'];
		$extraData['battleLogId'] = $ret['battleLogId'];
		(new PlayerMail)->sendPVPBattleMail(array_keys($ret['aFormatList']), $map['map_element_id'], $ret['win'], $battleType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], [], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
		
		
		//if win
		$attackerIds = [];
		foreach($ppq1s as $_ppq){
			$attackerIds[] = $_ppq['player_id'];
		}
		if($ret['win']){
			$retData['result'] = true;//win
			//解析资源
			foreach($ret['resource'] as $_pid => $_r){
				foreach($_r as $_type => $_num){
					$retData['attackData'][$_pid]['carry_'.$_type] += $_num;
				}
			}
			
			if(count($ppq1s) >= 3){
				foreach($ppq1s as $_ppq){
					(new PlayerMission)->updateMissionNumber($_ppq['player_id'], 15, 1);
				}
			}
			
			//sendNotice
			$this->sendNotice($attackerIds, 'battleWin');
			
			if($map['map_element_origin_id'] == 17){
				(new RoundMessage)->addNew($attackerId, ['type'=>3, 'boss_player_num'=>count($ppq1s), 'boss_npc_id'=>$defenderId]);//走马灯公告
			}
			
		}else{
			//if lose
			$retData['result'] = false;//lose
			
			//sendNotice
			$this->sendNotice($attackerIds, 'battleLose');
		}
		
		
		return $retData;
	}
	
	public function sendNoTargetMail($ppqs){
		
	}
}