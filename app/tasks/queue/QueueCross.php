<?php
/**
 * 跨服队列
 */
class QueueCross extends CrossDispatcherTask{
	
    /**
     * 单人攻城战
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _cityBattle($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId, $crossBattle)){
				throw new Exception(10670); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
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
			if($ppq['guild_id'] == $map['guild_id']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
						
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//获得敌方援助队列
			//$enemyPpqs = CrossPlayerProjectQueue::find(['target_player_id='.$ppq['target_player_id'].' and type='.PlayerProjectQueue::TYPE_CITYASSIST_ING.' and status=1'])->toArray();
			//$enemyPpqs = $PlayerProjectQueue->afterFindQueue($enemyPpqs);
			
			//battle
			$ppq1 = [$ppq];
			$ppq2 = [];
			$extra = [];
			//破城先锋:攻城获胜后，按照进攻军团战前战力计算城防损失。
			$CrossPlayerGeneral = new CrossPlayerGeneral;
			$CrossPlayerGeneral->battleId = $battleId;
			if($CrossPlayerGeneral->getSkillsByArmies([$ppq['army_id']], [10097])[10097][0]){
				$extra['powerBeforeBattle'] = $this->getArmyPower($battleId, $ppq['player_id'], $ppq['army_id']);
			}
			//御驾亲征
			if(@$ppq['target_info']['skill'][10054]){
				@$extra['cityAttckBuff'] += $ppq['target_info']['skill'][10054];
			}
			$battleRet = $this->createArmyBattle(1, $battleId, $ppq['player_id'], $ppq['target_player_id'], $ppq1, $ppq2, $map, $extra);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//反戈一击
			$targetPlayer = $Player->getByPlayerId($ppq['target_player_id']);
			include APP_PATH . "/app/controllers/ControllerBase.php";
			include APP_PATH . "/app/controllers/CrossController.php";
			try{
				(new CrossController)->catapultAttack($targetPlayer, $targetPlayer['guild_id'], $battleId, $ppq['from_x'], $ppq['from_y'], $crossBattle, true);
			} catch (Exception $e) {
				list($err, $msg) = parseException($e);
			}
						
			//建立回家队列
			$this->createArmyReturn($ppq/*, $battleRet['attackData'][$ppq['player_id']]*/);
			
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
					
					//缓兵之计:攻城获胜后，敌军下次出征行军时间延长|<#0,255,0#>%{num}|秒
					$this->useSkill($battleId, 10053, $ppq['player_id'], $ppq['army_id'], $ppq['target_player_id']);
					
					//嗜血:攻城获胜后，为自己的城堡恢复伤害值|<#0,255,0#>%{num}|%的城防值，但受到的攻城伤害增加|<#0,255,0#>%{num1}|%
					$this->useSkill($battleId, 10058, $ppq['player_id'], $ppq['army_id'], $ppq['target_player_id'], ['durability'=>$battleRet['reduceDurabilityBase'], 'guildId'=>$ppq['guild_id']]);
					
					//公会增加积分todo
					
					//玩家扣血(移到createArmyBattle)
					$crossPlayer = $battleRet['crossPlayer'];
					//$crossPlayer = $Player->getByPlayerId($ppq['target_player_id']);
					//$Player->alter($ppq['target_player_id'], ['wall_durability'=>'GREATEST(0, wall_durability-'.$battleRet['reduceDurability'].')']);
					
					//日志
					(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻击玩家胜利'.(@$battleRet['KO'] ? '(秒杀)':'').'[defend='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')]|扣血-'.$battleRet['reduceDurability'].',剩余'.max(0, $crossPlayer['wall_durability']-$battleRet['reduceDurability']).'|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
					if(@$battleRet['KO']){
						$player = $Player->getByPlayerId($ppq['player_id']);
						$this->crossNotice($battleId, 'skill_10111', ['fromNick'=>$player['nick'], 'toNick'=>$crossPlayer['nick']]);
					}
					
					//如果玩家血0，删除城堡
					if($crossPlayer['wall_durability'] <= $battleRet['reduceDurability']){
						//不屈之力：城防首次被击破时可恢复|<#72,255,98#>%{num}||<#3,169,235#>%{numnext}|城防值
						if(!$crossPlayer['skill_first_recover']){
							$recoverhp = $CrossPlayerGeneral->getSkillsByPlayer($ppq['target_player_id'], null, [10089])[10089][0];
							if($recoverhp){
								$Player->alter($ppq['target_player_id'], ['wall_durability'=>'wall_durability+'.$recoverhp, 'skill_first_recover'=>1]);
								(new CrossCommonLog)->add($battleId, $ppq['target_player_id'], $crossPlayer['guild_id'], '玩家发动不屈之力|加血+'.$recoverhp);
								$this->crossNotice($battleId, 'skill_10089', ['nick'=>$crossPlayer['nick']]);
								goto a;
							}
						}

						$Map->delPlayerCastle($battleId, $crossPlayer['player_id']);
						
						//日志
						(new CrossCommonLog)->add($battleId, $crossPlayer['player_id'], $crossPlayer['guild_id'], '玩家扑街|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
						
						$player = $Player->getByPlayerId($ppq['player_id']);
						$this->crossNotice($battleId, 'playerDead', ['from_nick'=>$player['nick'], 'to_nick'=>$crossPlayer['nick']]);
						
						//一血通知
						$crossBattle = (new CrossBattle)->getBattle($battleId);
						(new CrossBattle)->updateFirstBlood($crossBattle, $player, $crossPlayer);
						
						//连杀
						$Player->addContinueKill($ppq['player_id'], $player, $crossBattle);
					}
					a:
					
				}else{
					$battleFlag = 2;
					$targetCrossPlayer = $Player->getByPlayerId($ppq['target_player_id']);
					(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻击玩家失败[defend='.$targetCrossPlayer['player_id'].'('.$targetCrossPlayer['guild_id'].')]|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
				}
			}
			
			finishQueue:
			//更新队列完成
			
			if(!isset($battleFlag))
				$battleFlag = 0;
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id'], $battleFlag);
									
			crossSocketSend(CrossPlayer::parsePlayerId($map['player_id'])['server_id'], ['Type'=>'cross_finishattacked', 'Data'=>['playerId'=>[$map['player_id']]]]);
			
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10671); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 城门战
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _doorBattle($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10672); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为城门
			if($map['map_element_origin_id'] != 302){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查城门血
			if(!$map['durability']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
						
			//battle
			$ppq1 = [$ppq];
			$ppq2 = [];
			$extra = [];
			//御驾亲征
			if(@$ppq['target_info']['skill'][10054]){
				@$extra['cityAttckBuff'] += $ppq['target_info']['skill'][10054];
			}
			
			$battleRet = $this->createArmyBattle(2, $battleId, $ppq['player_id'], 0, $ppq1, $ppq2, $map, $extra);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			//建立回家队列
			$this->createArmyReturn($ppq, [], ['attackDoor'=>true]);
			
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
					
					//公会增加积分todo
					
					
					(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻击城门['.$map['area'].']|扣血-'.$battleRet['reduceDurability'].',剩余'.max(0, $map['durability']-$battleRet['reduceDurability']).'|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
					$player = $Player->getByPlayerId($ppq['player_id']);
					$this->crossNotice($battleId, 'playerAttackDoor', ['nick'=>$player['nick'], 'reduce'=>$battleRet['reduceDurability'], 'rest'=>max(0, $map['durability']-$battleRet['reduceDurability']), 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
					
					//如果城门血0，破门逻辑
					if($map['durability'] <= $battleRet['reduceDurability']){
						
						//更新公会占领区域
						(new CrossBattle)->updateAttackArea($map['battle_id'], $map['next_area']);
						/*$crossBattle = (new CrossBattle)->getBattle($map['battle_id']);
						$attackArea = parseArray($crossBattle['attack_area']);
						$attackArea[] = $map['next_area'];
						$attackArea = join(',', array_unique($attackArea));
						(new CrossBattle)->alter($map['battle_id'], ['attack_area'=>$attackArea]);*/
						
						//撤离所有下一个区域的敌方占领投石车和床弩
						$PlayerProjectQueue->callbackCatapult($battleId, $map['next_area']);
						$PlayerProjectQueue->callbackCrossbow($battleId, $map['next_area']);
						
						//遣返本区攻城锤内部队
						$PlayerProjectQueue->callbackHammer($battleId, $map['area']);
						
						//日志
						(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '破门['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
						
						$this->crossNotice($battleId, 'doorBroken', ['x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
						
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
				
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10673); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 占领攻城锤
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _gotoHammer($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10674); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为攻城锤
			if($map['map_element_origin_id'] != 301){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查攻城锤血
			$Map->rebuildBuilding($map);
			if(!$map['durability']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查城门血
			$doorMap = $Map->findFirst(['battle_id='.$battleId.' and area='.$map['area'].' and map_element_origin_id=302 and durability > 0']);
			if(!$doorMap){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否有自己部队驻守
			$condition = ['player_id='.$ppq['player_id'].' and type='.CrossPlayerProjectQueue::TYPE_HAMMER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1'];
			if($hasPpq = $PlayerProjectQueue->findFirst($condition)){
				$PlayerProjectQueue->callbackQueue($hasPpq->id, $hasPpq->to_x, $hasPpq->to_y);
			}
			
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}

			//建立占领队列
			$extraData = [
				'from_map_id' => $ppq['to_map_id'],
				'from_x' => $ppq['to_x'],
				'from_y' => $ppq['to_y'],
				'to_map_id' => $ppq['to_map_id'],
				'to_x' => $ppq['to_x'],
				'to_y' => $ppq['to_y'],
				'area' => $ppq['area'],
			];
			$needTime = ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>'0000-00-00 00:00:00'];
			$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, CrossPlayerProjectQueue::TYPE_HAMMER_ING, $needTime, $ppq['army_id'], [], $extraData);
			
			//计算间隔时间
			$atkcdTime = (new WarfareServiceConfig)->dicGetOne('wf_warhammer_atkcolddown');
			//攻城锤填充:驻守时减少攻城锤攻击间隔时间
			$condition = ['type='.CrossPlayerProjectQueue::TYPE_HAMMER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
			$ppqs = CrossPlayerProjectQueue::find($condition)->toArray();
			if(!$ppqs){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$armyIds = array_unique(Set::extract('/army_id', $ppqs));
			$CrossPlayerGeneral = new CrossPlayerGeneral;
			$CrossPlayerGeneral->battleId = $battleId;
			$atkcdTime -= $CrossPlayerGeneral->getSkillsByArmies($armyIds, [20])[20][0];
			$atkcdTime = max(0, $atkcdTime);
			
			//目标更新公会
			if(!$Map->alter($map['id'], ['guild_id'=>$ppq['guild_id'], 'player_num'=>'player_num+1', 'attack_cd'=>$atkcdTime])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '入驻攻城锤['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
			
			$player = $Player->getByPlayerId($ppq['player_id']);
			$this->crossNotice($battleId, 'hammerTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10675); //比赛已经结束
			}
												
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 打攻城锤(废弃)
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _hammerBattle($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10676); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为攻城锤
			if($map['map_element_origin_id'] != 301){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查血
			$Map->rebuildBuilding($map);
			if(!$map['durability']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
						
			//battle
			$ppq1 = [$ppq];
			$ppq2 = [];
			$battleRet = $this->createArmyBattle(3, $battleId, $ppq['player_id'], 0, $ppq1, $ppq2, $map);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			//建立回家队列
			$this->createArmyReturn($ppq/*, $battleRet['attackData'][$ppq['player_id']]*/);
			
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
					
					//公会增加积分todo
					
					//攻城锤扣血
					$recoverTime = (new WarfareServiceConfig)->dicGetOne('wf_warhammer_respawn_time');
					$Map->alter($map['id'], ['durability'=>'GREATEST(0, durability-'.$battleRet['reduceDurability'].')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
					(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻击攻城锤['.$map['area'].']|扣血-'.$battleRet['reduceDurability'].',剩余'.max(0, $map['durability']-$battleRet['reduceDurability']).'|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
					
					//如果攻城锤血0，遣返所有攻城锤部队
					if($map['durability'] <= $battleRet['reduceDurability']){
						
						$PlayerProjectQueue->callbackHammer($battleId, $ppq['area'], $ppq['to_map_id']);
						
						//日志
						(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻城锤0血['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
						
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
			
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10677); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 占领云梯
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _gotoLadder($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$ladderMaxProgress = (new WarfareServiceConfig)->dicGetOne('wf_ladder_max_progress');
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10678); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为云梯
			if($map['map_element_origin_id'] != 304){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查血
			$Map->rebuildBuilding($map);
			if(!$map['durability']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查进度
			$MapElement = new MapElement;
			$me = $MapElement->dicGetOne($map['map_element_id']);
			if($map['resource'] >= $ladderMaxProgress){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否有自己部队驻守,并读取其他驻守部队
			$condition = ['type='.CrossPlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1'];
			$ppqs = $PlayerProjectQueue->find($condition)->toArray();
			$otherPpqs = [];
			foreach($ppqs as $_i => $_p){
				if($_p['player_id'] == $ppq['player_id']){
					$PlayerProjectQueue->callbackQueue($_p['id'], $_p['to_x'], $_p['to_y']);
					unset($ppqs[$_i]);
				}else{
					$otherPpqs[] = $_p;
				}
			}
			
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			$now = time();
			$this->refreshLadder($ppq, $otherPpqs, $map, $now, $finishBuild);
			//更新进度
			/*if($otherPpqs){
				$_otherPpqs = $PlayerProjectQueue->afterFindQueue($otherPpqs);
				$oldSpeed = $_otherPpqs[0]['target_info']['speed'];
				$sec = $now - $map['build_time'];
				$buildValue = $sec * $oldSpeed;
				
				//更新云梯进度和build_time
				if($map['resource'] + $buildValue >= $me['max_res']){
					$finishBuild = true;
				}
				$Map->alter($map['id'], ['resource'=>min($map['resource'] + $buildValue, $me['max_res']), 'build_time'=>"'".date('Y-m-d H:i:s', $now)."'"]);
				(new CrossCommonLog)->add($battleId, $ppq['player_id'], '更新云梯进度['.$map['area'].']='.min($map['resource'] + $buildValue, $me['max_res']));
			}
			*/
			if($finishBuild){
				$this->createArmyReturn($ppq);
			}else{
				//计算云梯完成时间
				list($speed, $second) = $this->refreshLadder2(array_merge([$ppq], $ppqs), $otherPpqs, $map, $ladderMaxProgress, $now);
				/*$restRes = $ladderMaxProgress - $map['resource'];
				$formula = (new WarfareServiceConfig)->getValueByKey('wf_ladder_progress');
				$power = 0;
				foreach(array_merge([$ppq], $ppqs) as $_ppq){
					$power += $this->getArmyPower($map['battle_id'], $_ppq['player_id'], $_ppq['army_id']);
				}
				eval('$speed = '.$formula.';');
				//$speed = count($otherPpqs)+1;//debug todo
				$second = ceil(round($restRes / $speed, 5));
				
				//更新原部队时间
				$needTime = ['end_time'=>date('Y-m-d H:i:s', $now+$second)];
				$targetInfo = ['speed'=>$speed];
				foreach($otherPpqs as $_p){
					$PlayerProjectQueue->assign($_p)->updateQueue($needTime, $targetInfo);
				}
				
				//更新云梯build_time
				if(!$Map->alter($map['id'], ['build_time'=>"'".date('Y-m-d H:i:s', $now)."'"])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}*/

				//建立占领队列
				$extraData = [
					'from_map_id' => $ppq['to_map_id'],
					'from_x' => $ppq['to_x'],
					'from_y' => $ppq['to_y'],
					'to_map_id' => $ppq['to_map_id'],
					'to_x' => $ppq['to_x'],
					'to_y' => $ppq['to_y'],
					'area' => $ppq['area'],
				];
				$targetInfo = [
					'speed'=>$speed,
				];
				$needTime = ['create_time'=>date('Y-m-d H:i:s', $now), 'end_time'=>date('Y-m-d H:i:s', $now+$second)];
				$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, CrossPlayerProjectQueue::TYPE_LADDER_ING, $needTime, $ppq['army_id'], $targetInfo, $extraData);
				
				//目标更新公会
				if(!$Map->alter($map['id'],['guild_id'=>$ppq['guild_id'], 'player_num'=>'player_num+1'])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '入驻云梯['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
				
				$player = $Player->getByPlayerId($ppq['player_id']);
				$this->crossNotice($battleId, 'ladderTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
			}
			
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);

			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10679); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 云梯修建完成
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _doneLadder($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$ladderMaxProgress = (new WarfareServiceConfig)->dicGetOne('wf_ladder_max_progress');
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10680); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为云梯
			if($map['map_element_origin_id'] != 304){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查血
			$Map->rebuildBuilding($map);
			if(!$map['durability']){
				//$this->createArmyReturn($ppq);
				$PlayerProjectQueue->callbackQueue($ppq['id'], $ppq['to_x'], $ppq['to_y'], ['ladder'=>true]);
				(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '云梯部队撤离[queueId='.$ppq['id'].']');
				goto finishQueue2;
			}
			
			//检查进度
			//$MapElement = new MapElement;
			//$me = $MapElement->dicGetOne($map['map_element_id']);
			if($map['resource'] >= $ladderMaxProgress){
				//$this->createArmyReturn($ppq);
				$PlayerProjectQueue->callbackQueue($ppq['id'], $ppq['to_x'], $ppq['to_y'], ['ladder'=>true]);
				(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '云梯部队撤离[queueId='.$ppq['id'].']');
				goto finishQueue2;
			}
			
			//检查是否有自己部队驻守,并读取其他驻守部队
			$condition = ['type='.CrossPlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1'];
			$ppqs = $PlayerProjectQueue->find($condition)->toArray();
			$otherPpqs = [];
			foreach($ppqs as $_p){
				if($_p['player_id'] == $ppq['player_id'] && $_p['army_id'] == $ppq['army_id']){
					
				}else{
					$otherPpqs[] = $_p;
				}
			}
			
			$now = min(time(), $ppq['end_time']);
			$this->refreshLadder($ppq, $ppqs, $map, $now, $finishBuild);
			
			if(!$finishBuild){
				if($otherPpqs){
					$this->refreshLadder2($otherPpqs, $otherPpqs, $map, $ladderMaxProgress, $now);
					//重新计算云梯完成时间
					/*$restRes = $ladderMaxProgress - $map['resource'];
					$formula = (new WarfareServiceConfig)->getValueByKey('wf_ladder_progress');
					$power = 0;
					foreach($otherPpqs as $_ppq){
						$power += $this->getArmyPower($map['battle_id'], $_ppq['player_id'], $_ppq['army_id']);
					}
					eval('$speed = '.$formula.';');
					//$speed = count($otherPpqs);//debug todo
					$second = ceil(round($restRes / $speed, 5));
					
					//更新原部队时间
					$needTime = ['end_time'=>date('Y-m-d H:i:s', $now+$second)];
					//$targetInfo = ['speed'=>$speed];
					foreach($otherPpqs as $_p){
						$targetInfo = $PlayerProjectQueue->afterFindQueue($_p)['target_info'];
						$targetInfo['speed'] = $speed;
						if(@$targetInfo['playerCallBack']){
							$PlayerProjectQueue->assign($_p)->updateQueue(false, $targetInfo);
						}else{
							$PlayerProjectQueue->assign($_p)->updateQueue($needTime, $targetInfo);
						}
						
					}
					//更新云梯build_time
					if(!$Map->alter($map['id'], ['build_time'=>"'".date('Y-m-d H:i:s', $now)."'"])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					*/
					//目标更新公会
					if(!$Map->alter($map['id'],['guild_id'=>$ppq['guild_id']])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}else{
					//目标更新公会
					if(!$Map->alter($map['id'],['guild_id'=>0])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			
			//部队返回
			$this->createArmyReturn($ppq);
			(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '云梯部队撤离['.$ppq['id'].']');
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
			//更新占领人数
			$Map->alter($ppq['to_map_id'], ['player_num'=>'player_num-1']);
			
			finishQueue2:
												
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10681); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 去床弩
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _gotoCrossbow($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId, $crossBattle)){
				throw new Exception(10682); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为床弩
			if($map['map_element_origin_id'] != 303){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否有部队驻守
			if($map['player_id']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否区域已破
			$attackArea = parseArray($crossBattle['attack_area']);
			if(in_array($map['area'], $attackArea)){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否已经占领其他床弩
			$condition = ['player_id='.$ppq['player_id'].' and type='.CrossPlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and status=1'];
			if($PlayerProjectQueue->findFirst($condition)){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}

			//建立占领队列
			$extraData = [
				'from_map_id' => $ppq['to_map_id'],
				'from_x' => $ppq['to_x'],
				'from_y' => $ppq['to_y'],
				'to_map_id' => $ppq['to_map_id'],
				'to_x' => $ppq['to_x'],
				'to_y' => $ppq['to_y'],
				'area' => $ppq['area'],
			];
			$needTime = ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>'0000-00-00 00:00:00'];
			$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, CrossPlayerProjectQueue::TYPE_CROSSBOW_ING, $needTime, $ppq['army_id'], [], $extraData);
			
			//计算间隔时间
			$atkcdTime = (new WarfareServiceConfig)->dicGetOne('wf_glaivethrower_atkcolddown');
			//床弩填充:驻守时减少床弩攻击间隔时间
			$condition = ['type='.CrossPlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
			$ppqs = CrossPlayerProjectQueue::find($condition)->toArray();
			if(!$ppqs){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$armyIds = array_unique(Set::extract('/army_id', $ppqs));
			$CrossPlayerGeneral = new CrossPlayerGeneral;
			$CrossPlayerGeneral->battleId = $battleId;
			$atkcdTime -= $CrossPlayerGeneral->getSkillsByArmies($armyIds, [14])[14][0];
			$atkcdTime = max(0, $atkcdTime);
			
			//目标更新公会
			if(!$Map->alter($map['id'], ['player_id'=>$ppq['player_id'], 'guild_id'=>$ppq['guild_id'], 'attack_cd'=>$atkcdTime, 'attack_times'=>0])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '入驻床弩['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
			
			$player = $Player->getByPlayerId($ppq['player_id']);
			$this->crossNotice($battleId, 'crossbowTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);

			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10683); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 去投石车
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _gotoCatapult($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			$CrossBattle = new CrossBattle;
			if(!(new CrossBattle)->isActivity($battleId, $crossBattle)){
				throw new Exception(10684); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为床弩
			if($map['map_element_origin_id'] != 305){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
							
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}

			
			//检查是否有部队驻守
			$otherPpq = CrossPlayerProjectQueue::findFirst(['battle_id='.$battleId.' and type='.CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and status=1 and to_map_id='.$ppq['to_map_id'].' and end_time="0000-00-00 00:00:00"']);
			
			if($otherPpq){
				$otherPpq = $otherPpq->toArray();
				//检查是否是同公会
				if($ppq['guild_id'] == $otherPpq['guild_id']){
					$this->createArmyReturn($ppq);
					goto finishQueue;
				}else{//如果不是同公会，发生战斗
					$ppq1 = [$ppq];
					$ppq2 = [$otherPpq];
					$battleRet = $this->createArmyBattle(4, $battleId, $ppq['player_id'], $otherPpq['player_id'], $ppq1, $ppq2, $map);
					if(!$battleRet){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
								
					//建立回家队列
					$this->createArmyReturn($ppq);
					
					if(isset($battleRet)){
						if($battleRet['result']){
							$battleFlag = 1;
							
							//公会增加积分todo
							
							//原驻守玩家被遣返
							$PlayerProjectQueue->callbackQueue($otherPpq['id'], $otherPpq['to_x'], $otherPpq['to_y']);
							if(!$Map->alter($map['id'], ['player_id'=>0, 'guild_id'=>0])){
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							}
							
							//日志
							(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻击投石车胜利[defend='.$otherPpq['player_id'].'('.$otherPpq['guild_id'].')]|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
							
							$player = $Player->getByPlayerId($ppq['player_id']);
							$this->crossNotice($battleId, 'catapultBroken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
							
						}else{
							$battleFlag = 2;
							
							(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻击投石车失败[defend='.$otherPpq['player_id'].'('.$otherPpq['guild_id'].')]|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
						}
					}
				}
			}else{
				//检查是否有该区域投石车控制权
				$ad = $CrossBattle->getADGuildId($crossBattle);
				$crossBattle = $CrossBattle->getBattle($battleId);
				$attackArea = parseArray($crossBattle['attack_area']);
				
				if((in_array($map['area'], $attackArea) && $ad['attack'] == $ppq['guild_id'])
					|| 
				(!in_array($map['area'], $attackArea) && $ad['defend'] == $ppq['guild_id'])){
					
				}else{
					$this->createArmyReturn($ppq);
					goto finishQueue;
				}
				
				//检查是否已经占领其他投石车
				$condition = ['player_id='.$ppq['player_id'].' and type='.CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and status=1'];
				if($PlayerProjectQueue->findFirst($condition)){
					$this->createArmyReturn($ppq);
					goto finishQueue;
				}

				//建立占领队列
				$extraData = [
					'from_map_id' => $ppq['to_map_id'],
					'from_x' => $ppq['to_x'],
					'from_y' => $ppq['to_y'],
					'to_map_id' => $ppq['to_map_id'],
					'to_x' => $ppq['to_x'],
					'to_y' => $ppq['to_y'],
					'area' => $ppq['area'],
				];
				$needTime = ['create_time'=>date('Y-m-d H:i:s'), 'end_time'=>'0000-00-00 00:00:00'];
				$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, CrossPlayerProjectQueue::TYPE_CATAPULT_ING, $needTime, $ppq['army_id'], [], $extraData);
				
				//计算间隔时间
				$atkcdTime = (new WarfareServiceConfig)->dicGetOne('wf_catapult_atkcolddown');
				
				//投石填充:驻守时减少投石车攻击间隔时间
				$condition = ['type='.CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
				$ppqs = CrossPlayerProjectQueue::find($condition)->toArray();
				if(!$ppqs){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$armyIds = array_unique(Set::extract('/army_id', $ppqs));
				$CrossPlayerGeneral = new CrossPlayerGeneral;
				$CrossPlayerGeneral->battleId = $battleId;
				$atkcdTime -= $CrossPlayerGeneral->getSkillsByArmies($armyIds, [17])[17][0];
				$atkcdTime = max(0, $atkcdTime);
				
				//目标更新公会
				if(!$Map->alter($map['id'], ['player_id'=>$ppq['player_id'], 'guild_id'=>$ppq['guild_id'], 'attack_cd'=>$atkcdTime, 'attack_times'=>0])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '入驻投石车[mapId='.$map['id'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
				
				$player = $Player->getByPlayerId($ppq['player_id']);
				$this->crossNotice($battleId, 'catapultTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
				
			}
			
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);

			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10685); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 攻击大本营
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _baseBattle($ppq){
		StaticData::$delaySocketSendFlag = true;
		ModelBase::$_delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$thisEnd = false;
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10686); //比赛已经结束
			}
			
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为大本营
			if($map['map_element_origin_id'] != 306){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查城门血
			if(!$map['durability']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			if(!$CrossPlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
						
			//battle
			$ppq1 = [$ppq];
			$ppq2 = [];
			$battleRet = $this->createArmyBattle(5, $battleId, $ppq['player_id'], 0, $ppq1, $ppq2, $map);
			if(!$battleRet){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			//建立回家队列
			$this->createArmyReturn($ppq/*, $battleRet['attackData'][$ppq['player_id']]*/);
			
			if(isset($battleRet)){
				if($battleRet['result']){
					$battleFlag = 1;
					
					//公会增加积分todo
					
					//扣血
					$Map->alter($map['id'], ['durability'=>'GREATEST(0, durability-'.$battleRet['reduceDurability'].')']);
					$player = $Player->getByPlayerId($ppq['player_id']);
					$this->crossNotice($battleId, 'playerAttackDoor', ['nick'=>$player['nick'], 'reduce'=>$battleRet['reduceDurability'], 'rest'=>max(0, $map['durability']-$battleRet['reduceDurability']), 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
					(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻击大本营|扣血-'.$battleRet['reduceDurability'].',剩余'.max(0, $map['durability']-$battleRet['reduceDurability']).'|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
					
					//如果血0，结束战斗
					if($map['durability'] <= $battleRet['reduceDurability']){
						$thisEnd = true;
						
						//更新公会占领区域
						if(!(new CrossBattle)->endBattle($battleId)){
							throw new Exception(10687); //比赛已经结束2
						}
						
						$this->crossNotice($battleId, 'baseBroken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
						
						//日志
						(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '攻破大本营|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
						
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

			if(!$thisEnd && !(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10688); //比赛已经结束
			}
			
			dbCommit($db);
			//dbCommit($db2);
			flushSocketSend();
			(new ModelBase)->flushCrossExec();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//dbRollback($db2);

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
     * 建立返回队列
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function createArmyReturn($ppq, $data=array(), $extra=[]){
		//获取我的主城位置
		$Player = new CrossPlayer;
		$Player->battleId = $ppq['battle_id'];
		$player = $Player->getByPlayerId($ppq['player_id']);
		if(!$player){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//计算时间
		if(@$ppq['target_info']['backNow']){
			$needTime = 0;
		}elseif($ppq['to_x'] != $player['x'] || $ppq['to_y'] != $player['y']){
			$needTime = CrossPlayerProjectQueue::calculateMoveTime($ppq['battle_id'], $ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], 3, $ppq['army_id']);
			/*if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
			
			//倾国倾城:若该武将的魅力高于所有敌军武将，攻击本方城门的部队返回时间增加
			if(@$extra['attackDoor']){
				$CrossBattle = new CrossBattle;
				$guildId = $CrossBattle->getADGuildId($ppq['battle_id'])['defend'];
				$CrossGuild = new CrossGuild;
				$CrossGuild->battleId = $ppq['battle_id'];
				$needTime += $CrossGuild->getGuildInfo($guildId)['buff_enemyreturn'];
			}
		}else{
			$needTime = 0;
		}
		
		//建立队列
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$PlayerProjectQueue->battleId = $ppq['battle_id'];
		$data = $PlayerProjectQueue->mergeExtraInfo($ppq, $data);
		$extraData = [
			'from_map_id' => $ppq['to_map_id'],
			'from_x' => $ppq['to_x'],
			'from_y' => $ppq['to_y'],
			'to_map_id' => $player['map_id'],
			'to_x' => $player['x'],
			'to_y' => $player['y'],
			'area' => $player['area'],
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
		if(isset($PlayerProjectQueue->moveTypes[$ppq['type']])){
			$type = $PlayerProjectQueue->moveTypes[$ppq['type']];
		}elseif(isset($PlayerProjectQueue->stayTypes[$ppq['type']])){
			$type = $PlayerProjectQueue->stayTypes[$ppq['type']];
		}else{
			$type = CrossPlayerProjectQueue::TYPE_RETURN;
		}
		
		$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData);
		return true;
	}
	
	public function createDieEnemyReturn($ppqs, $battleRet){
		$PlayerArmyUnit = new CrossPlayerArmyUnit;
		$CrossPlayerProjectQueue = new CrossPlayerProjectQueue;
		foreach($ppqs as $_ppq){
			$PlayerArmyUnit->battleId = $_ppq['battle_id'];
			$CrossPlayerProjectQueue->battleId = $_ppq['battle_id'];
			$pau = $PlayerArmyUnit->getByArmyId($_ppq['player_id'], $_ppq['army_id']);
			$_die = true;
			foreach($pau as $_pau){
				if($_pau['soldier_id'] && $_pau['soldier_num']){
					$_die = false;
					break;
				}
			}
			if($_die){//死完兵，回家
				$CrossPlayerProjectQueue->finishQueue($_ppq['player_id'], $_ppq['id']);
				$this->createArmyReturn($_ppq/*, $battleRet['defenceData'][$_ppq['player_id']]*/);
			}
		}
	}
	
	public function createArmyBattle($type, $battleId, $attackerId, $defenderId, $ppq1s, $ppq2s, $map, $extra=[]){
		//$PlayerProjectQueue = new CrossPlayerProjectQueue;
		//$PlayerProjectQueue->battleId = $_ppq['battle_id'];
		$retData = [];
		$extraData = [];
		$attackPlayerList = [];
		$defendPlayerList = [];
		$Player = new CrossPlayer;
		$Player->battleId = $battleId;
		$CrossGuild = new CrossGuild;
		$CrossGuild->battleId = $battleId;
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayerGeneral->battleId = $battleId;

		if($type == 1){//攻城
			$defendPlayerList = [$defenderId=>0];
		}
		foreach($ppq1s as $_ppq){
			$attackPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
		}

		foreach($ppq2s as $_ppq){
			$defendPlayerList[$_ppq['player_id']] = $_ppq['army_id'];
		}
		
		$Battle = new Battle;
		$isPvp = false;
		try {
			if($type == 1 || $type == 4){//打城，打投石车
				if($type == 1){
					$mailType = 11;
				}else{
					$mailType = 12;
				}
				$ex = ['battleId'=>$battleId];
				$battleType = 11;
				$ret = $Battle->battleCore($attackPlayerList, $defendPlayerList, $battleType, $ex);
				//var_dump($ret);
				$isPvp = true;
				
				$extraData = [];
				if($type == 1 && $ret['win']){
					$wsc = (new WarfareServiceConfig)->dicGetOne('wf_atkcastle_hitpointlost');
					$ret['power'] = $power = $this->getArmyPower($battleId, $attackerId, $attackPlayerList[$attackerId]);
					if(@$extra['powerBeforeBattle']){//破城先锋
						$power = $extra['powerBeforeBattle'];
					}
					eval('$reduceDurability = '.$wsc.';');
					
					//加成
					$addDurability = 0;
					//嗜血,自身伤害加成
					$addDurability += $CrossPlayerGeneral->getSkillsByPlayer($defenderId, null, [10058])[10058][1];
					
					//力压群雄：本方所有的攻城伤害增加|<#0,255,0#>%{num}|%
					$addDurability += $CrossGuild->getByPlayerId($attackerId)['buff_cityattack'];
					
					//御驾亲征
					$addDurability += @$extra['cityAttckBuff']*1;
					
					$reduceDurability *= 1+$addDurability;
					
					$reduceDurability = floor($reduceDurability);
					$ret['reduceDurabilityBase'] = $ret['reduceDurability'] = $reduceDurability;
					
					//石破天惊:攻城获胜或攻击城墙后，额外附加目标当前城防值%的伤害
					$addDurability2 = $CrossPlayerGeneral->getSkillsByArmies([$attackPlayerList[$attackerId]], [10100])[10100][0];
					
					//城池扣血
					$ret['crossPlayer'] = $crossPlayer = $Player->getByPlayerId($defenderId);
					
					//无双乱舞
					if(lcg_value1() < $CrossPlayerGeneral->getSkillsByArmies([$attackPlayerList[$attackerId]], [10111])[10111][0]){
						$Player->alter($defenderId, ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-@reduce:=(wall_durability))']);
						
						@$retData['KO'] = true;
					}else{
						//buff结构：(v+绝对值)*(1+百分比)+(自身百分比*self)
						$Player->alter($defenderId, ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-@reduce:=('.$reduceDurability.'+floor(wall_durability*'.$addDurability2.')))']);
					}
					$crossPlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
										
					$ret['reduceDurability'] = $reduceDurability = $Player->sqlGet('select @reduce')[0]['@reduce'];
					if($ret['reduceDurabilityBase'] > $crossPlayer['wall_durability']){
						$ret['reduceDurabilityBase'] = $crossPlayer['wall_durability'];
					}
										
					$extraData['oldDurability'] = $crossPlayer['wall_durability'];
					$extraData['newDurability'] = max(0, $crossPlayer['wall_durability']-$reduceDurability);
					$extraData['durabilityMax'] = $crossPlayer['wall_durability_max'];
				}else{
					$crossPlayer = $Player->getByPlayerId($defenderId);
					$extraData['newDurability'] = $extraData['oldDurability'] = $crossPlayer['wall_durability'];
					$extraData['durabilityMax'] = $crossPlayer['wall_durability_max'];
				}
				
				foreach($ret['aFormatList'] as $_playerId => $_l){
					$_num = 0;
					foreach($_l['unit'] as $_unit){
						$_num += $_unit['kill_num'];
					}
					$Player->alter($_playerId, ['kill_soldier'=>'kill_soldier+'.$_num]);
				}
				
				foreach($ret['dFormatList'] as $_playerId => $_l){
					$_num = 0;
					foreach($_l['unit'] as $_unit){
						$_num += $_unit['kill_num'];
					}
					$Player->alter($_playerId, ['kill_soldier'=>'kill_soldier+'.$_num]);
				}
				
				//增加杀敌数
				(new CrossBattle)->addKill($battleId, $ppq1s[0]['guild_id'], $ret['dSoldierLoseNum'], $ret['aSoldierLoseNum']);
				
			}elseif($type == 2){//攻击城门
				$mailType = 13;
				$wsc = (new WarfareServiceConfig)->dicGetOne('wf_atkgate_hitpointlost');
				$ret['power'] = $power = $this->getArmyPower($battleId, $attackerId, $attackPlayerList[$attackerId], $unit);
				eval('$reduceDurability = '.$wsc.';');
				$ret['win'] = true;
				
				//加成
				$addDurability = 0;
				
				//御驾亲征
				$addDurability += @$extra['cityAttckBuff']*1;
				
				$reduceDurability *= 1+$addDurability;
					
				$reduceDurability = floor($reduceDurability);
				$ret['reduceDurabilityBase'] = $ret['reduceDurability'] = $reduceDurability;
				//石破天惊:攻城获胜或攻击城墙后，额外附加目标当前城防值|<#0,255,0#>%{num}|%的伤害
				$addDurability2 = $CrossPlayerGeneral->getSkillsByArmies([$attackPlayerList[$attackerId]], [10100])[10100][0];
				
				//城门扣血
				$Map = new CrossMap;
				$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-@reduce:=('.$reduceDurability.'+floor(durability*'.$addDurability2.')))']);
				$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
				$ret['reduceDurability'] = $reduceDurability = $Map->sqlGet('select @reduce')[0]['@reduce'];
				
				$extraData['oldDurability'] = $map['durability'];
				$extraData['newDurability'] = max(0, $map['durability']-$reduceDurability);
				$extraData['durabilityMax'] = $map['max_durability'];
				
			/*}elseif($type == 3){//攻击攻城锤
				$wsc = (new WarfareServiceConfig)->dicGetOne('wf_ladder_max_progress');//todo
				$power = $this->getArmyPower($battleId, $attackerId, $attackPlayerList[$attackerId]);
				eval('$reduceDurability = '.$wsc['data'].';');
				$ret['reduceDurability'] = $reduceDurability;
				$ret['win'] = true;*/
			}elseif($type == 5){//攻击大本营
				$mailType = 14;
				$wsc = (new WarfareServiceConfig)->dicGetOne('wf_atkbasecastle_hitpointlost');
				$ret['power'] = $power = $this->getArmyPower($battleId, $attackerId, $attackPlayerList[$attackerId], $unit);
				eval('$reduceDurability = '.$wsc.';');
				$ret['reduceDurability'] = $reduceDurability;
				$ret['win'] = true;
				
				$extraData['oldDurability'] = $map['durability'];
				$extraData['newDurability'] = max(0, $map['durability']-$reduceDurability);
				$extraData['durabilityMax'] = $map['max_durability'];
			}
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
		
		/*if($type == 3 && $ret['win']){
			//胜利，堡垒扣除耐久值
			$armies = [];
			foreach($ppq1s as $_ppq){
				$armies[$_ppq['player_id']] = $_ppq['army_id'];
			}
			$retData['subHp'] = $PlayerProjectQueue->calculcateBaseAttackValue($armies, $map);
			$extraData['oldDurability'] = $map['durability'];
			$extraData['newDurability'] = max(0, $map['durability'] - $retData['subHp']);
			
		}*/
		
		if($isPvp){
			$retData['attackData'] = [];
			$retData['defenceData'] = [];
			
			$extraData['aLosePower'] = $ret['aLosePower'];
			$extraData['dLosePower'] = $ret['dLosePower'];
			$extraData['godGeneralSkillArr'] = $ret['godGeneralSkillArr'];
			$retData['reduceDurability'] = @$ret['reduceDurability'];
			$retData['reduceDurabilityBase'] = @$ret['reduceDurabilityBase'];
			$retData['crossPlayer'] = @$ret['crossPlayer'];
			
			//攻击方信息
			$guildId1 = $ppq1s[0]['guild_id'];
			$guild1 = $CrossGuild->getGuildInfo($guildId1);
			$extraData['guild_1_name'] = $guild1['name'];
			$extraData['guild_1_short'] = $guild1['short_name'];
			foreach($ret['aFormatList'] as $_playerId => &$_l){
				$_player = $Player->getByPlayerId($_playerId);
				if(!$_player) continue;
				$_l['nick'] = $_player['nick'];
				$_l['avatar'] = $_player['avatar_id'];
				$_l['x'] = $_player['x'];
				$_l['y'] = $_player['y'];
			}
			unset($_l);
			//防守方信息
			if($type == 1){
				$guildId2 = $map['guild_id'];
			}else{
				$guildId2 = $ppq2s[0]['guild_id'];
			}
			$guild2 = $CrossGuild->getGuildInfo($guildId2);
			$extraData['guild_2_name'] = $guild2['name'];
			$extraData['guild_2_short'] = $guild2['short_name'];
			foreach($defendPlayerList as $_playerId=>$_armyId){
				$_l = &$ret['dFormatList'][$_playerId];
				$_player = $Player->getByPlayerId($_playerId);
				if(!$_player) continue;
				$_l['nick'] = $_player['nick'];
				$_l['avatar'] = $_player['avatar_id'];
				$_l['x'] = $_player['x'];
				$_l['y'] = $_player['y'];
			}
			unset($_l);
			$extraData['battleId'] = $battleId;
			$PlayerMail = new PlayerMail;
			(new ModelBase)->execByServer(CrossPlayer::parseGuildId($guildId1)['server_id'], 'PlayerMail', 'sendCrossBattleMail', ['attack', array_keys($ret['aFormatList']), array_keys($defendPlayerList), $ret['win'], $mailType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData]);
			//$PlayerMail->sendCrossBattleMail('attack', array_keys($ret['aFormatList']), array_keys($defendPlayerList), $ret['win'], $mailType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData);
			(new ModelBase)->execByServer(CrossPlayer::parseGuildId($guildId2)['server_id'], 'PlayerMail', 'sendCrossBattleMail', ['defend', array_keys($ret['aFormatList']), array_keys($defendPlayerList), $ret['win'], $mailType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData]);
		}else{
			$retData['attackData'] = [];
			$retData['defenceData'] = [];
			
			//攻击方信息
			$guildId1 = $ppq1s[0]['guild_id'];
			$guild1 = $CrossGuild->getGuildInfo($guildId1);
			$extraData['guild_1_name'] = $guild1['name'];
			$extraData['guild_1_short'] = $guild1['short_name'];
			$_player = $Player->getByPlayerId($attackerId);
			$ret['aFormatList'] = [];
			$ret['aFormatList'][$attackerId] = [
				'nick' => $_player['nick'],
				'avatar' => $_player['avatar_id'],
				'x' => $_player['x'],
				'y' => $_player['y'],
				'power' => $ret['power']*DIC_DATA_DIVISOR,
				'losePower' => 0,
				'killWeight' => 0,
				'weight' => 0,
				'unit' => [],
			];
			foreach($unit as $_u){
				$cpg = $CrossPlayerGeneral->getByGeneralId($attackerId, $_u['general_id']);
				$ret['aFormatList'][$attackerId]['unit'][] = [
					'general_id' => $_u['general_id'],
					'general_star' => $cpg['star_lv'],
					'soldier_id' => $_u['soldier_id'],
					'attack' => 0,
					'defend' => 0,
					'life' => 0,
					'soldier_num' => $_u['soldier_num'],
					'kill_num' => 0,
					'killed_num' => 0,
					'injure_num' => 0,
					'revive_num' => 0,
					'live_num' => 0,
					'takeDamage' => 0,
					'doDamage' => 0,
				];
			}

			//防守方信息
			$AD = (new CrossBattle)->getADGuildId($battleId);
			$guildId2 = $AD['defend'];
			$guild2 = $CrossGuild->getGuildInfo($guildId2);
			$extraData['guild_2_name'] = $guild2['name'];
			$extraData['guild_2_short'] = $guild2['short_name'];
			
			$extraData['battleId'] = $battleId;
			$extraData['element_id'] = $map['map_element_id'];
			$PlayerMail = new PlayerMail;
			(new ModelBase)->execByServer(CrossPlayer::parseGuildId($guildId1)['server_id'], 'PlayerMail', 'sendCrossBattleMail', ['attack', [$attackerId], [], $ret['win'], $mailType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['aFormatList'], [], false, $extraData]);
			
			$retData['reduceDurability'] = $ret['reduceDurability'];
		}
		
				

		
		
		//if win
		/*$attackerIds = [];
		foreach($ppq1s as $_ppq){
			$attackerIds[] = $_ppq['player_id'];
		}
		$defenderIds = [$defenderId];
		foreach($ppq2s as $_ppq){
			$defenderIds[] = $_ppq['player_id'];
		}*/
		if($ret['win']){
			$retData['result'] = true;//win
			
			//sendNotice
			//$this->sendNotice($attackerIds, 'battleWin');
			//$this->sendNotice($defenderIds, 'battleLose');
			
		}else{
			//if lose
			$retData['result'] = false;//lose
			
			//sendNotice
			//$this->sendNotice($defenderIds, 'battleWin');
			//$this->sendNotice($attackerIds, 'battleLose');
		}
		
		
		//通知被攻城方
		if(in_array($type, [1])){
			//socketSend(['Type'=>'city_attacked', 'Data'=>['playerId'=>$defenderId]]);
			crossSocketSend(CrossPlayer::parsePlayerId($defenderId)['server_id'], ['Type'=>'cross_city_attacked', 'Data'=>['playerId'=>$defenderId]]);
		}
		
		return $retData;
	}
	
	public function refreshLadder($ppq, $ppqs, &$map, $now, &$finishLadder = false){
		if(!$ppqs)
			return true;
		$battleId = $ppq['battle_id'];
		$ladderMaxProgress = (new WarfareServiceConfig)->dicGetOne('wf_ladder_max_progress');
		
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		$_ppqs = $PlayerProjectQueue->afterFindQueue($ppqs);
		$oldSpeed = $_ppqs[0]['target_info']['speed'];
		$sec = $now - (is_numeric($map['build_time']) ? $map['build_time'] : strtotime($map['build_time']));
		$buildValue = $sec * $oldSpeed;
		
		//更新云梯进度和build_time
		if($map['resource'] + $buildValue >= $ladderMaxProgress){
			$finishLadder = true;
		}
		$Map= new CrossMap;
		$Map->battleId = $battleId;
		$Map->alter($map['id'], ['resource'=>'LEAST(resource+'.$buildValue.', '.$ladderMaxProgress.')', 'build_time'=>"'".date('Y-m-d H:i:s', $now)."'"]);
		$map['resource'] = min($map['resource'] + $buildValue, $ladderMaxProgress);
		$map['build_time'] = date('Y-m-d H:i:s', $now);
		
		(new CrossCommonLog)->add($battleId, 0, 0, '更新云梯进度['.$map['area'].']='.min($map['resource'] + $buildValue, $ladderMaxProgress));
	
		if($finishLadder){
			//遣返所有队伍
			foreach($ppqs as $_p){
				if($now < strtotime($_p['end_time'])){
					$PlayerProjectQueue->callbackQueue($_p['id'], $_p['to_x'], $_p['to_y'], ['ladder'=>true]);
					(new CrossCommonLog)->add($battleId, $_p['player_id'], $_p['guild_id'], '云梯部队遣返[queueId='.$_p['id'].']');
				}
			}
			
			//$this->createArmyReturn($ppq);
			
			//更新map占领公会
			if(!$Map->alter($map['id'], ['guild_id'=>0])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$map['guild_id'] = 0;
			
			//更新公会占领区域
			$crossBattle = (new CrossBattle)->getBattle($battleId);
			$attackArea = parseArray($crossBattle['attack_area']);
			$attackArea[] = $map['next_area'];
			$attackArea = join(',', array_unique($attackArea));
			(new CrossBattle)->alter($battleId, ['attack_area'=>$attackArea]);
			
			//撤离所有下一个区域的敌方占领投石车
			$PlayerProjectQueue->callbackCatapult($battleId, $map['next_area']);
			$PlayerProjectQueue->callbackCrossbow($battleId, $map['next_area']);
			
			//日志
			(new CrossCommonLog)->add($battleId, $ppq['player_id'], $ppq['guild_id'], '云梯建造完成['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['guild_id'].')');
			
			$this->crossNotice($battleId, 'ladderDone', ['x'=>$map['x'], 'y'=>$map['y']]);
		}
		return true;
	}
	
	public function refreshLadder2($allPpqs, $updatePpqs, $map, $ladderMaxProgress, $now, $speed=null){
		if(!$allPpqs)
			return;
		$battleId = $allPpqs[0]['battle_id'];
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		
		$restRes = $ladderMaxProgress - $map['resource'];
		$formula = (new WarfareServiceConfig)->getValueByKey('wf_ladder_progress');
		$power = 0;
		$armies = [];
		foreach($allPpqs as $_ppq){
			$power += $this->getArmyPower($map['battle_id'], $_ppq['player_id'], $_ppq['army_id']);
			$armies[] = $_ppq['army_id'];
		}
		eval('$speed = '.$formula.';');
		
		//暗渡陈仓:驻守云梯时，云梯建造速度增加|<#0,255,0#>%{num}|%
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayerGeneral->battleId = $battleId;
		$speedBuff = $CrossPlayerGeneral->getSkillsByArmies($armies, [10093])[10093][0];
		$speed *= 1+$speedBuff;
		//$speed = count($otherPpqs);//debug todo
		$second = ceil(round($restRes / $speed, 5));

		//更新原部队时间
		$needTime = ['end_time'=>date('Y-m-d H:i:s', $now+$second)];
		//$targetInfo = ['speed'=>$speed];
		foreach($updatePpqs as $_p){
			$targetInfo = $PlayerProjectQueue->afterFindQueue([$_p])[0]['target_info'];
			$targetInfo['speed'] = $speed;
			if(@$targetInfo['playerCallBack']){
				$PlayerProjectQueue->assign($_p)->updateQueue(false, $targetInfo);
			}else{
				$PlayerProjectQueue->assign($_p)->updateQueue($needTime, $targetInfo);
			}
			
		}
		//更新云梯build_time
		$Map = new CrossMap;
		$Map->battleId = $battleId;
		if(!$Map->alter($map['id'], ['build_time'=>"'".date('Y-m-d H:i:s', $now)."'"])){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		return [$speed, $second];
	}
	
	public function sendNoTargetMail($ppqs){
		
	}
	
	public function sendProtectMail($ppqs){
		
	}

	public function getArmyPower($battleId, $playerId, $armyId, &$unit=[]){
		$PlayerGeneral = new CrossPlayerGeneral;
		$PlayerGeneral->battleId = $battleId;
		$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
		$CrossPlayerArmyUnit->battleId = $battleId;
		$Soldier = new Soldier;
		$General = new General;
		$data = $CrossPlayerArmyUnit->getByPlayerId($playerId);
		$power = 0;
		$unit = [];
		foreach($data as $_k => &$_data){
			if($_data['army_id'] != $armyId){
				continue;
			}
			//获取power
			$_power = 0;
			if($_data['soldier_id'] && $_data['soldier_num']){
				$general = $PlayerGeneral->getTotalAttr($playerId, $_data['general_id']);
				$_soldier = $Soldier->dicGetOne($_data['soldier_id']);
				if($_soldier){
					$_power += ($_soldier['power'] * $_data['soldier_num']) / DIC_DATA_DIVISOR;
				}
				
				$_power *= $general['soldierPower'][$_soldier['soldier_type']]['powerK'];
			}
			$unit[] = $_data;
			

			$power += floor($_power);
		}
		return $power;
	}

	//通知
	public function crossNotice($battleId, $type, $data){
		//获取双方公会id
		$CrossBattle = new CrossBattle;
		$CrossBattle->battleId = $battleId;
		$guilds = $CrossBattle->getADGuildId($battleId);
		if(!$guilds)
			return false;
		$CrossPlayer = new CrossPlayer;
		$CrossPlayer->battleId = $battleId;
		foreach(['attack', 'defend'] as $_t){
			$playerIds = [];
			$serverId = CrossPlayer::parseGuildId($guilds[$_t])['server_id'];
			if($serverId){
				$members = $CrossPlayer->getByGuildId($guilds[$_t]);
				$members = Set::extract('/.[status>0]', $members);
				$playerIds = Set::extract('/player_id', $members);
				$_data = $data;
				$_data['playerId'] = $playerIds;
				$_data['type'] = $type;
				crossSocketSend($serverId, ['Type'=>'cross', 'Data'=>$_data]);
			}
		}
	}
	
	public function useSkill($battleId, $skillId, $fromPlayerId, $fromArmy=0, $toPlayerId=0, $data=[]){
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayerGeneral->battleId = $battleId;
		if($fromArmy){
			$values = $CrossPlayerGeneral->getSkillsByArmies([$fromArmy], [$skillId])[$skillId];
		}else{
			$values = $CrossPlayerGeneral->getSkillsByPlayer($fromPlayerId, null, [$skillId])[$skillId];
		}
		if(!$values[0] && !$values[1])
			return false;
		switch($skillId){
			case 10053://缓兵之计
				$CrossPlayer = new CrossPlayer;
				$CrossPlayer->battleId = $battleId;
				$values[0] = floor($values[0]);
				$CrossPlayer->alter($toPlayerId, ['debuff_queuetime'=>'GREATEST(debuff_queuetime, '.$values[0].')']);
			break;
			case 10058://嗜血
				$CrossPlayer = new CrossPlayer;
				$CrossPlayer->battleId = $battleId;
				$addHp = floor($data['durability'] * $values[0]);
				$CrossPlayer->alter($fromPlayerId, ['wall_durability'=>'@durability:=(LEAST(wall_durability_max, wall_durability+'.$addHp.'))']);
				$restHp = $CrossPlayer->sqlGet('select @durability')[0]['@durability'];
				(new CrossCommonLog)->add($battleId, $fromPlayerId, $data['guildId'], '技能:嗜血|回血+'.$addHp.',剩余'.$restHp);
			break;
		}
		return $values;
	}
}