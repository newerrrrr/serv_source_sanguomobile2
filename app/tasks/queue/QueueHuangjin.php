<?php
/**
 * 黄巾起义队列
 */
class QueueHuangjin extends DispatcherTask{
	
    /**
     * 攻击堡垒
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
			$guildId = $ppq['target_info']['target_guild_id'];
			
			//获取目标地图信息
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				(new GuildHuangjin)->end($guildId);
				goto finishQueue;
			}
			
			//检查坐标是否对应联盟
			if($guildId != $map['guild_id'] || $map['map_element_origin_id'] != 1){
				(new GuildHuangjin)->end($guildId);
				goto finishQueue;
			}
			
			//获得敌方援助队列
			$enemyPpqs = PlayerProjectQueue::find(['to_map_id='.$map['id'].' and type in ('.PlayerProjectQueue::TYPE_GUILDBASE_BUILD.', '.PlayerProjectQueue::TYPE_GUILDBASE_REPAIR.', '.PlayerProjectQueue::TYPE_GUILDBASE_DEFEND.') and status=1 and (end_time > now() or end_time = "0000-00-00 00:00:00")'])->toArray();
			$enemyPpqs = $PlayerProjectQueue->afterFindQueue($enemyPpqs);
						
			//battle
			$ppq1 = $ppq;
			$ppq2 = $enemyPpqs;
			$battleRet = $this->createArmyBattle($ppq1, $ppq2, $map, $guildId);
			//$battleRet = ['result'=>true, 'killPercent'=>60];
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			//计算增加积分todo
			$HuangjinAttackMob = new HuangjinAttackMob;
			$ham = $HuangjinAttackMob->dicGetOne($ppq['player_id']);
			$addScore = floor($battleRet['aLosePower'] / $ham['power_score_rate']);
			
			if(isset($battleRet)){
				if(!$battleRet['result']){//npc输，就是玩家赢
					$battleFlag = 2;
					
					//更新黄巾数据
					(new GuildHuangjin)->updateWin($guildId, $ppq['player_id'], $addScore, ($battleRet['killPercent'] == 100 ? true : false));
				}else{
					$battleFlag = 1;
					//更新黄巾数据
					(new GuildHuangjin)->updateLose($guildId, $ppq['player_id'], $addScore);
				}
			}
			
			//如果不为第二次战败，且不为最后一波，生成新一波
			$maxWave = (new HuangjinAttackMob)->getMaxWave();
			$guildHuangjin = (new GuildHuangjin)->findFirst(['guild_id='.$guildId])->toArray();
			if($ppq['player_id'] < $maxWave && $guildHuangjin['lost_times'] < 2){
				$newWave = $ppq['player_id']+1;
				$hj = (new GuildHuangjin)->findFirst(['guild_id='.$guildId])->toArray();
				if($hj['history_top_wave'] >= $newWave){
					$needTime = (new Starting)->getValueByKey("huangjin_time_faster");
				}else{
					$needTime = (new Starting)->getValueByKey("huangjin_time");
				}
				$extraData = [
					'from_map_id' => $ppq['from_map_id'],
					'from_x' => $ppq['from_x'],
					'from_y' => $ppq['from_y'],
					'to_map_id' => $ppq['to_map_id'],
					'to_x' => $ppq['to_x'],
					'to_y' => $ppq['to_y'],
				];
				if(!(new PlayerProjectQueue)->addQueue($newWave, 0, 0, PlayerProjectQueue::TYPE_HJNPCATTACK_GOTO, $needTime, 0, ['target_guild_id'=>$guildId], $extraData)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				
				//如果最后波
				(new GuildHuangjin)->end($guildId);
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
	
	public function createArmyBattle($ppq1s, $ppq2s, $map, $dGuildId=0){
		$PlayerProjectQueue = new PlayerProjectQueue;
		$retData = [];
		$extraData = [];
		$attackPlayerList = ['wave_id'=>$ppq1s['player_id']];
		$defendPlayerList = [];
		if($ppq2s){
			$maxPowerPlayer = 0;
			$maxPower = 0;
			$Player = new Player;
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
		if(!$defendPlayerList){
			$defendPlayerList = $dGuildId;
		}
		
		$battleType = 10;
		$Battle = new Battle;
		try {
			$ex = [];
			$ex['basePosition'] = ['dx'=>$map['x'], 'dy'=>$map['y'], 'ax'=>$ppq1s['from_x'], 'ay'=>$ppq1s['from_y']];
			$ret = $Battle->battleCore($attackPlayerList, $defendPlayerList, $battleType, $ex);
		}catch (Exception $e) {
			//if(!$e->getCode()){
				echo $e->getMessage();
				var_dump($attackPlayerList);
				var_dump($defendPlayerList);
				//var_dump($ret);
//			}
		}
		if(!$ret)
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				
		
		$retData['attackData'] = [];
		$retData['defenceData'] = [];
		
		$retData['aLosePower'] = $extraData['aLosePower'] = $ret['aLosePower'];
		$retData['dLosePower'] = $extraData['dLosePower'] = $ret['dLosePower'];
		$extraData['battleLogId'] = $ret['battleLogId'];
		$extraData['godGeneralSkillArr'] = $ret['godGeneralSkillArr'];
		$retData['killPercent'] = $extraData['killPercent'] = $ret['aLosePowerRate']*100;
		(new PlayerMail)->sendPVPBattleMail($ppq1s, $dGuildId, $ret['win'], $battleType, $map['x'], $map['y'], $ret['resource'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
		
		//解析伤兵
		/*
		foreach($ret['aList'] as $_list){
			@$retData['attackData'][$_list['playerId']]['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
		}
		foreach($ret['dList'] as $_list){
			@$retData['defenceData'][$_list['playerId']]['carry_soldier'][$_list['soldierId']] += $_list['injuredNum'];
		}
		*/
		
		//if win
		$defenderIds = [];
		foreach($ppq2s as $_ppq){
			$defenderIds[] = $_ppq['player_id'];
		}
		if($extraData['killPercent'] < 50 /*$ret['win']*/){
			$retData['result'] = true;//win，npc赢，就是玩家输
			
			//sendNotice
			if($defenderIds)
				$this->sendNotice($defenderIds, 'battleLose');
			
		}else{
			//if lose
			$retData['result'] = false;//lose
			
			if($defenderIds)
				$this->sendNotice($defenderIds, 'battleWin');
		}
		
		return $retData;
	}
}