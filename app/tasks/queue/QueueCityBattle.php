<?php
/**
 * 城战队列
 */
class QueueCityBattle extends CityBattleDispatcherTask{
	
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
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CityBattle)->isActivity($battleId, $cb)){
				throw new Exception(10670); //比赛已经结束
			}
			
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否对应玩家
			if($ppq['target_player_id'] != $map['player_id'] || $map['map_element_origin_id'] != 406){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查是否同盟
			if($ppq['camp_id'] == $map['camp_id']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
						
			//保护机制，部队是否存在
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			if(!$CityBattlePlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
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
			$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
			$CityBattlePlayerGeneral->battleId = $battleId;
			if($CityBattlePlayerGeneral->getSkillsByArmies([$ppq['army_id']], [10097])[10097][0]){
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
			include APP_PATH . "/app/controllers/CityBattleController.php";
			try{
				(new CityBattleController)->catapultAttack($targetPlayer, $targetPlayer['camp_id'], $battleId, $ppq['from_x'], $ppq['from_y'], $cb, true);
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
					$this->useSkill($battleId, 10058, $ppq['player_id'], $ppq['army_id'], $ppq['target_player_id'], ['durability'=>$battleRet['reduceDurabilityBase'], 'campId'=>$ppq['camp_id']]);
					
					//公会增加积分todo
					
					//玩家扣血(移到createArmyBattle)
					$cityBattlePlayer = $battleRet['cityBattlePlayer'];
					//$crossPlayer = $Player->getByPlayerId($ppq['target_player_id']);
					//$Player->alter($ppq['target_player_id'], ['wall_durability'=>'GREATEST(0, wall_durability-'.$battleRet['reduceDurability'].')']);
					
					//日志
					(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '攻击玩家胜利'.(@$battleRet['KO'] ? '(秒杀)':'').'[defend='.$cityBattlePlayer['player_id'].'('.$cityBattlePlayer['guild_id'].')]|扣血-'.$battleRet['reduceDurability'].',剩余'.max(0, $cityBattlePlayer['wall_durability']-$battleRet['reduceDurability']).'|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
					if(@$battleRet['KO']){
						$player = $Player->getByPlayerId($ppq['player_id']);
						$this->crossNotice($battleId, 'skill_10111', ['fromNick'=>$player['nick'], 'toNick'=>$cityBattlePlayer['nick']]);
					}
					
					//如果玩家血0，删除城堡
					if($cityBattlePlayer['wall_durability'] <= $battleRet['reduceDurability']){
						//不屈之力：城防首次被击破时可恢复|<#72,255,98#>%{num}||<#3,169,235#>%{numnext}|城防值
						if(!$cityBattlePlayer['skill_first_recover']){
							$recoverhp = $CityBattlePlayerGeneral->getSkillsByPlayer($ppq['target_player_id'], null, [10089])[10089][0];
							if($recoverhp){
								$Player->alter($ppq['target_player_id'], ['wall_durability'=>'wall_durability+'.$recoverhp, 'skill_first_recover'=>1]);
								(new CityBattleCommonLog)->add($battleId, $ppq['target_player_id'], $cityBattlePlayer['camp_id'], '玩家发动不屈之力|加血+'.$recoverhp);
								$this->crossNotice($battleId, 'skill_10089', ['nick'=>$cityBattlePlayer['nick']]);
								goto a;
							}
						}

						$Map->delPlayerCastle($battleId, $cityBattlePlayer['player_id']);
						
						//日志
						(new CityBattleCommonLog)->add($battleId, $cityBattlePlayer['player_id'], $cityBattlePlayer['camp_id'], '玩家扑街|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
						
						$player = $Player->getByPlayerId($ppq['player_id']);
						$this->crossNotice($battleId, 'playerDead', ['from_nick'=>$player['nick'], 'to_nick'=>$cityBattlePlayer['nick']]);
						
						//一血通知
						$cb = (new CityBattle)->getBattle($battleId);
						(new CityBattle)->updateFirstBlood($cb, $player, $cityBattlePlayer);
						
						//连杀
						$Player->addContinueKill($ppq['player_id'], $player, $cityBattlePlayer);
						
						(new CityBattleGuildMission)->addCountByGuildType($player['guild_id'], 7, 1);//任务：联盟成员在跨服战中击破敌方城池%{num}次
					}
					a:
					
				}else{
					$battleFlag = 2;
					$targetCityBattlePlayer = $Player->getByPlayerId($ppq['target_player_id']);
					(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '攻击玩家失败[defend='.$targetCityBattlePlayer['player_id'].'('.$targetCityBattlePlayer['camp_id'].')]|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
				}
			}
			
			finishQueue:
			//更新队列完成
			
			if(!isset($battleFlag))
				$battleFlag = 0;
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id'], $battleFlag);
									
			crossSocketSend(CityBattlePlayer::parsePlayerId($map['player_id'])['server_id'], ['Type'=>'citybattle_finishattacked', 'Data'=>['playerId'=>[$map['player_id']]]]);
			
			if(!(new CityBattle)->isActivity($battleId)){
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
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CityBattle)->isActivity($battleId, $cb)){
				throw new Exception(10672); //比赛已经结束
			}
			
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为城门
			if($map['map_element_origin_id'] != 401){
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
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			if(!$CityBattlePlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
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
					
					
					(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '攻击城门['.$map['area'].']|扣血-'.$battleRet['reduceDurability'].',剩余'.max(0, $map['durability']-$battleRet['reduceDurability']).'|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
					$player = $Player->getByPlayerId($ppq['player_id']);
					$this->crossNotice($battleId, 'playerAttackDoor', ['nick'=>$player['nick'], 'reduce'=>$battleRet['reduceDurability'], 'rest'=>max(0, $map['durability']-$battleRet['reduceDurability']), 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
					
					//更新英勇值
					$addScore = floor(min($map['durability'], $battleRet['reduceDurability']) * (new CountryBasicSetting)->getValueByKey('damage_gate_score') / 100);
					$Player->alter($ppq['player_id'], ['score'=>'score+'.$addScore]);
					(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '更新英勇值+'.$addScore.'|by攻击城门');
					
					//如果城门血0，破门逻辑
					if($map['durability'] <= $battleRet['reduceDurability']){
						
						//更新公会占领区域
						(new CityBattle)->updateDoor($battleId, $player['camp_id']);
						
						//撤离所有下一个区域的敌方占领投石车和床弩
						$PlayerProjectQueue->callbackCatapult($battleId, $map['next_area']);
						$PlayerProjectQueue->callbackCrossbow($battleId, $map['next_area']);
						
						//遣返本区攻城锤内部队
						if(!$cb['defend_camp']){
							$PlayerProjectQueue->callbackHammer($battleId, $map['area']);
							$PlayerProjectQueue->callbackLadder($battleId, $map['area']);
						}
						
						//任务：联盟成员在跨服战中参与击破城门%{num}次
						$guildMemberNum = $Player->getGuildMemberNumByCampId($ppq['camp_id']);
						foreach($guildMemberNum as $_guildId=>$_num){
							(new CityBattleGuildMission)->addCountByGuildType($_guildId, 5, $_num);
						}
						
						//日志
						(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '破门['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
						
						$this->crossNotice($battleId, 'doorBroken', ['x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
						
						(new CityBattle)->endBattle($battleId);
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
				
			if(!(new CityBattle)->isActivity($battleId)){
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
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CityBattle)->isActivity($battleId)){
				throw new Exception(10674); //比赛已经结束
			}
			
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为攻城锤
			if($map['map_element_origin_id'] != 402){
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
			$doorMap = $Map->findFirst(['battle_id='.$battleId.' and status=1 and area='.$map['area'].' and map_element_origin_id=401 and durability > 0']);
			if(!$doorMap){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否有自己部队驻守
			$findMe = false;
			$condition = ['player_id='.$ppq['player_id'].' and type='.CityBattlePlayerProjectQueue::TYPE_HAMMER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1'];
			if($hasPpq = $PlayerProjectQueue->findFirst($condition)){
				$PlayerProjectQueue->callbackQueue($hasPpq->id, $hasPpq->to_x, $hasPpq->to_y);
				$findMe = true;
			}
			
			//检查队伍上限
			if(!$findMe && $map['player_num'] >= 10){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			if(!$CityBattlePlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
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
			$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['camp_id'], 0, CityBattlePlayerProjectQueue::TYPE_HAMMER_ING, $needTime, $ppq['army_id'], [], $extraData);
			
			//计算间隔时间
			$atkcdTime = (new CountryBasicSetting)->dicGetOne('wf_warhammer_atkcolddown');
			//攻城锤填充:驻守时减少攻城锤攻击间隔时间
			$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_HAMMER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
			$ppqs = CityBattlePlayerProjectQueue::find($condition)->toArray();
			if(!$ppqs){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$armyIds = array_unique(Set::extract('/army_id', $ppqs));
			$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
			$CityBattlePlayerGeneral->battleId = $battleId;
			$atkcdTime -= $CityBattlePlayerGeneral->getSkillsByArmies($armyIds, [20])[20][0];
			$atkcdTime = max(0, $atkcdTime);
			
			//目标更新公会
			if(!$Map->alter($map['id'], ['camp_id'=>$ppq['camp_id'], 'player_num'=>'player_num+1', 'attack_cd'=>$atkcdTime])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '入驻攻城锤['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
			
			$player = $Player->getByPlayerId($ppq['player_id']);
			$this->crossNotice($battleId, 'hammerTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
			if(!(new CityBattle)->isActivity($battleId)){
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
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$ladderMaxProgress = (new CountryBasicSetting)->dicGetOne('wf_ladder_max_progress');
			
			//查看battle状态
			if(!(new CityBattle)->isActivity($battleId)){
				throw new Exception(10678); //比赛已经结束
			}
			
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为云梯
			if($map['map_element_origin_id'] != 403){
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
			$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1'];
			$ppqs = $PlayerProjectQueue->find($condition)->toArray();
			$otherPpqs = [];
			$findMe = false;
			foreach($ppqs as $_i => $_p){
				if($_p['player_id'] == $ppq['player_id']){
					$PlayerProjectQueue->callbackQueue($_p['id'], $_p['to_x'], $_p['to_y']);
					$findMe = true;
					unset($ppqs[$_i]);
				}else{
					$otherPpqs[] = $_p;
				}
			}
			
			//检查队伍上限
			if(!$findMe && $map['player_num'] >= 10){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			if(!$CityBattlePlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			$now = time();
			$this->refreshLadder($ppq, $otherPpqs, $map, $now, $finishBuild, $finishBattle);
			if($finishBuild){
				$this->createArmyReturn($ppq);
			}else{
				//计算云梯完成时间
				list($speed, $second) = $this->refreshLadder2(array_merge([$ppq], $ppqs), $otherPpqs, $map, $ladderMaxProgress, $now);

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
				$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['camp_id'], 0, CityBattlePlayerProjectQueue::TYPE_LADDER_ING, $needTime, $ppq['army_id'], $targetInfo, $extraData);
				
				//目标更新公会
				if(!$Map->alter($map['id'],['camp_id'=>$ppq['camp_id'], 'player_num'=>'player_num+1'])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '入驻云梯['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
				
				$player = $Player->getByPlayerId($ppq['player_id']);
				$this->crossNotice($battleId, 'ladderTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
			}
			
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);

			if(!$finishBattle){
				if(!(new CityBattle)->isActivity($battleId)){
					throw new Exception(10679); //比赛已经结束
				}
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
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);
		
		try {
			$finishBuild = false;
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$ladderMaxProgress = (new CountryBasicSetting)->dicGetOne('wf_ladder_max_progress');
			
			//查看battle状态
			if(!(new CityBattle)->isActivity($battleId)){
				throw new Exception(10680); //比赛已经结束
			}
			
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为云梯
			if($map['map_element_origin_id'] != 403){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查血
			$Map->rebuildBuilding($map);
			if(!$map['durability']){
				//$this->createArmyReturn($ppq);
				$PlayerProjectQueue->callbackQueue($ppq['id'], $ppq['to_x'], $ppq['to_y'], ['ladder'=>true]);
				(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '云梯部队撤离[queueId='.$ppq['id'].']');
				goto finishQueue2;
			}
			
			//检查进度
			//$MapElement = new MapElement;
			//$me = $MapElement->dicGetOne($map['map_element_id']);
			if($map['resource'] >= $ladderMaxProgress){
				//$this->createArmyReturn($ppq);
				$PlayerProjectQueue->callbackQueue($ppq['id'], $ppq['to_x'], $ppq['to_y'], ['ladder'=>true]);
				(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '云梯部队撤离[queueId='.$ppq['id'].']');
				
				goto finishQueue2;
			}
			
			//检查是否有自己部队驻守,并读取其他驻守部队
			$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1'];
			$ppqs = $PlayerProjectQueue->find($condition)->toArray();
			$otherPpqs = [];
			foreach($ppqs as $_p){
				if($_p['player_id'] == $ppq['player_id'] && $_p['army_id'] == $ppq['army_id']){
					
				}else{
					$otherPpqs[] = $_p;
				}
			}
			
			$now = min(time(), $ppq['end_time']);
			$this->refreshLadder($ppq, $ppqs, $map, $now, $finishBuild, $finishBattle);
			
			if(!$finishBuild){
				if($otherPpqs){
					$this->refreshLadder2($otherPpqs, $otherPpqs, $map, $ladderMaxProgress, $now);
					//目标更新公会
					if(!$Map->alter($map['id'],['camp_id'=>$ppq['camp_id']])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}else{
					//目标更新公会
					if(!$Map->alter($map['id'],['camp_id'=>0])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				
				//更新英勇值
				$addScore = max(0, floor(($now - $ppq['create_time']) * (new CountryBasicSetting)->getValueByKey('get_ladder_score') / 100));
				if($addScore){
					$Player->alter($ppq['player_id'], ['score'=>'score+'.$addScore]);
					(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '更新英勇值+'.$addScore.'|by占领云梯');
				}
				
				//部队返回
				$this->createArmyReturn($ppq);
				(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '云梯部队撤离['.$ppq['id'].']');
			}
			
			finishQueue:
			//更新队列完成
			if(!$finishBuild){
				$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
				//更新占领人数
				$Map->alter($ppq['to_map_id'], ['player_num'=>'player_num-1']);
			}
			
			finishQueue2:
								
			if(!$finishBattle){
				if(!(new CityBattle)->isActivity($battleId)){
					throw new Exception(10681); //比赛已经结束
				}
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
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			if(!(new CityBattle)->isActivity($battleId, $cb)){
				throw new Exception(10682); //比赛已经结束
			}
			
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为床弩
			if($map['map_element_origin_id'] != 405){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否有部队驻守
			if($map['player_id']){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否区域已破
			if($cb['door'.$map['target_area']]){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//检查是否已经占领其他床弩
			$condition = ['player_id='.$ppq['player_id'].' and type='.CityBattlePlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and status=1'];
			if($PlayerProjectQueue->findFirst($condition)){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
			//保护机制，部队是否存在
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			if(!$CityBattlePlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
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
			$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, CityBattlePlayerProjectQueue::TYPE_CROSSBOW_ING, $needTime, $ppq['army_id'], [], $extraData);
			
			//计算间隔时间
			$atkcdTime = (new CountryBasicSetting)->dicGetOne('wf_glaivethrower_atkcolddown');
			//床弩填充:驻守时减少床弩攻击间隔时间
			$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
			$ppqs = CityBattlePlayerProjectQueue::find($condition)->toArray();
			if(!$ppqs){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$armyIds = array_unique(Set::extract('/army_id', $ppqs));
			$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
			$CityBattlePlayerGeneral->battleId = $battleId;
			$atkcdTime -= $CityBattlePlayerGeneral->getSkillsByArmies($armyIds, [14])[14][0];
			$atkcdTime = max(0, $atkcdTime);
			
			//目标更新公会
			if(!$Map->alter($map['id'], ['player_id'=>$ppq['player_id'], 'camp_id'=>$ppq['camp_id'], 'attack_cd'=>$atkcdTime, 'attack_times'=>0])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '入驻床弩['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
			
			$player = $Player->getByPlayerId($ppq['player_id']);
			$this->crossNotice($battleId, 'crossbowTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);

			if(!(new CityBattle)->isActivity($battleId)){
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
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		dbBegin($db);
		//dbBegin($db2);

		try {
			$battleId = $ppq['battle_id'];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			
			//查看battle状态
			$CityBattle = new CityBattle;
			if(!(new CityBattle)->isActivity($battleId, $cb)){
				throw new Exception(10684); //比赛已经结束
			}
			
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];

			//获取目标地图信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否为投石车
			if($map['map_element_origin_id'] != 404){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}
			
							
			//保护机制，部队是否存在
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			if(!$CityBattlePlayerArmyUnit->armyExist($ppq['player_id'], $ppq['army_id'])){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}

			//检查是否已经占领其他投石车
			$condition = ['player_id='.$ppq['player_id'].' and type='.CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and status=1'];
			if($PlayerProjectQueue->findFirst($condition)){
				$this->createArmyReturn($ppq);
				goto finishQueue;
			}

			
			//检查是否有部队驻守
			$otherPpq = CityBattlePlayerProjectQueue::findFirst(['battle_id='.$battleId.' and type='.CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING.' and status=1 and to_map_id='.$ppq['to_map_id'].' and end_time="0000-00-00 00:00:00"']);
			
			if($otherPpq){
				$otherPpq = $otherPpq->toArray();
				//检查是否是同公会
				if($ppq['camp_id'] == $otherPpq['camp_id']){
					$this->createArmyReturn($ppq);
					goto finishQueue;
				}else{//如果不是同公会，发生战斗
					$ppq1 = [$ppq];
					$ppq2 = [$otherPpq];
					$battleRet = $this->createArmyBattle(4, $battleId, $ppq['player_id'], $otherPpq['player_id'], $ppq1, $ppq2, $map);
					if(!$battleRet){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
								
					if(isset($battleRet)){
						if($battleRet['result']){
							$battleFlag = 1;
							
							//公会增加积分todo
							
							//原驻守玩家被遣返
							$PlayerProjectQueue->callbackQueue($otherPpq['id'], $otherPpq['to_x'], $otherPpq['to_y']);
							if(!$Map->alter($map['id'], ['player_id'=>0, 'camp_id'=>0])){
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							}
							
							//日志
							(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '攻击投石车胜利[defend='.$otherPpq['player_id'].'('.$otherPpq['camp_id'].')]|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
							
							$player = $Player->getByPlayerId($ppq['player_id']);
							$this->crossNotice($battleId, 'catapultBroken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
							
							goto createStayQueue;
						}else{
							$this->createArmyReturn($ppq);
							
							$battleFlag = 2;
							
							(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '攻击投石车失败[defend='.$otherPpq['player_id'].'('.$otherPpq['camp_id'].')]|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
						}
					}
				}
			}else{
				createStayQueue:
								
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
				$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['camp_id'], 0, CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING, $needTime, $ppq['army_id'], [], $extraData);
				
				//计算间隔时间
				$atkcdTime = (new CountryBasicSetting)->dicGetOne('wf_catapult_atkcolddown');
				
				//投石填充:驻守时减少投石车攻击间隔时间
				/*$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
				$ppqs = CityBattlePlayerProjectQueue::find($condition)->toArray();
				if(!$ppqs){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$armyIds = array_unique(Set::extract('/army_id', $ppqs));*/
				$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
				$CityBattlePlayerGeneral->battleId = $battleId;
				$atkcdTime -= $CityBattlePlayerGeneral->getSkillsByArmies([$ppq['army_id']], [17])[17][0];
				$atkcdTime = max(0, $atkcdTime);
				
				//目标更新公会
				if(!$Map->alter($map['id'], ['player_id'=>$ppq['player_id'], 'camp_id'=>$ppq['camp_id'], 'attack_cd'=>$atkcdTime, 'attack_times'=>0])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '入驻投石车[mapId='.$map['id'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
				
				$player = $Player->getByPlayerId($ppq['player_id']);
				$this->crossNotice($battleId, 'catapultTaken', ['nick'=>$player['nick'], 'x'=>$ppq['to_x'], 'y'=>$ppq['to_y']]);
				
			}
			
			
			finishQueue:
			//更新队列完成
			
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);

			if(!(new CityBattle)->isActivity($battleId)){
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
     * 建立返回队列
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function createArmyReturn($ppq, $data=array(), $extra=[]){
		//获取我的主城位置
		$Player = new CityBattlePlayer;
		$Player->battleId = $ppq['battle_id'];
		$player = $Player->getByPlayerId($ppq['player_id']);
		if(!$player){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//计算时间
		if(@$ppq['target_info']['backNow']){
			$needTime = 0;
		}elseif($ppq['to_x'] != $player['x'] || $ppq['to_y'] != $player['y']){
			$needTime = CityBattlePlayerProjectQueue::calculateMoveTime($ppq['battle_id'], $ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], 3, $ppq['army_id']);
			/*if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
			
			//倾国倾城:若该武将的魅力高于所有敌军武将，攻击本方城门的部队返回时间增加
			if(@$extra['attackDoor']){
				$CityBattle = new CityBattle;
				$CityBattleCamp = new CityBattleCamp;
				$CityBattleCamp->battleId = $ppq['battle_id'];
				$needTime += $CityBattleCamp->getByCampId($ppq['camp_id'])['buff_enemyreturn'];
			}
		}else{
			$needTime = 0;
		}
		
		//建立队列
		$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
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
			$type = CityBattlePlayerProjectQueue::TYPE_RETURN;
		}
		
		$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['camp_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData);
		return true;
	}
	
	public function createArmyBattle($type, $battleId, $attackerId, $defenderId, $ppq1s, $ppq2s, $map, $extra=[]){
		//$PlayerProjectQueue = new CrossPlayerProjectQueue;
		//$PlayerProjectQueue->battleId = $_ppq['battle_id'];
		$retData = [];
		$extraData = [];
		$attackPlayerList = [];
		$defendPlayerList = [];
		$Player = new CityBattlePlayer;
		$Player->battleId = $battleId;
		$CityBattleCamp = new CityBattleCamp;
		$CityBattleCamp->battleId = $battleId;
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$CityBattlePlayerGeneral->battleId = $battleId;
		

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
				$battleType = 12;
				$ret = $Battle->battleCore($attackPlayerList, $defendPlayerList, $battleType, $ex);
				//var_dump($ret);
				$isPvp = true;
				
				$extraData = [];
				if($type == 1 && $ret['win']){
					$wsc = (new CountryBasicSetting)->dicGetOne('wf_atkcastle_hitpointlost');
					$ret['power'] = $power = $this->getArmyPower($battleId, $attackerId, $attackPlayerList[$attackerId]);
					if(@$extra['powerBeforeBattle']){//破城先锋
						$power = $extra['powerBeforeBattle'];
					}
					eval('$reduceDurability = '.$wsc.';');
					
					//加成
					$addDurability = 0;
					//嗜血,自身伤害加成
					$addDurability += $CityBattlePlayerGeneral->getSkillsByPlayer($defenderId, null, [10058])[10058][1];
					
					//力压群雄：本方所有的攻城伤害增加|<#0,255,0#>%{num}|%
					$addDurability += $CityBattleCamp->getByPlayerId($attackerId)['buff_cityattack'];
					
					//城战科技：机关秘术：城战时每一组车兵增加军团攻城伤害|<#72,255,153#>%{num}%%|
					if($buff502 = (new CityBattleBuff)->getCampBuff($ppq1s[0]['camp_id'], 502)){
						$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
						$CityBattlePlayerArmyUnit->battleId = $battleId;
						$carGroupNum = $CityBattlePlayerArmyUnit->getGroupNumByType($ppq1s[0]['player_id'], $ppq1s[0]['army_id'], 4);
						$addDurability += $carGroupNum * $buff502;
					}
					
					//御驾亲征
					$addDurability += @$extra['cityAttckBuff']*1;
					
					$reduceDurability *= 1+$addDurability;
					
					//铁骑突击:对敌方城池造成伤害时，有x%几率造成双倍伤害
					if(lcg_value1() < $CityBattlePlayerGeneral->getSkillsByArmies([$attackPlayerList[$attackerId]], [10107])[10107][0]){
						$reduceDurability *= 2;
					}
					
					$reduceDurability = floor($reduceDurability);
					$ret['reduceDurabilityBase'] = $ret['reduceDurability'] = $reduceDurability;
					
					//石破天惊:攻城获胜或攻击城墙后，额外附加目标当前城防值%的伤害
					$addDurability2 = $CityBattlePlayerGeneral->getSkillsByArmies([$attackPlayerList[$attackerId]], [10100])[10100][0];
					
					//城池扣血
					$ret['cityBattlePlayer'] = $cityBattlePlayer = $Player->getByPlayerId($defenderId);
					
					//无双乱舞
					if(lcg_value1() < $CityBattlePlayerGeneral->getSkillsByArmies([$attackPlayerList[$attackerId]], [10111])[10111][0]){
						$Player->alter($defenderId, ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-@reduce:=(wall_durability))']);
						
						@$retData['KO'] = true;
					}else{
						//buff结构：(v+绝对值)*(1+百分比)+(自身百分比*self)
						$Player->alter($defenderId, ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-@reduce:=('.$reduceDurability.'+floor(wall_durability*'.$addDurability2.')))']);
					}
					$cityBattlePlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
										
					$ret['reduceDurability'] = $reduceDurability = $Player->sqlGet('select @reduce')[0]['@reduce'];
					if($ret['reduceDurabilityBase'] > $cityBattlePlayer['wall_durability']){
						$ret['reduceDurabilityBase'] = $cityBattlePlayer['wall_durability'];
					}
										
					$extraData['oldDurability'] = $cityBattlePlayer['wall_durability'];
					$extraData['newDurability'] = max(0, $cityBattlePlayer['wall_durability']-$reduceDurability);
					$extraData['durabilityMax'] = $cityBattlePlayer['wall_durability_max'];
				}else{
					$cityBattlePlayer = $Player->getByPlayerId($defenderId);
					$extraData['newDurability'] = $extraData['oldDurability'] = $cityBattlePlayer['wall_durability'];
					$extraData['durabilityMax'] = $cityBattlePlayer['wall_durability_max'];
				}
				
				$killSoldierScorePer = (new CountryBasicSetting)->getValueByKey('kill_soldier_score');
				foreach($ret['aFormatList'] as $_playerId => $_l){
					$_num = 0;
					foreach($_l['unit'] as $_unit){
						$_num += $_unit['kill_num'];
					}
					if($_num){
						$addScore = floor($_num * $killSoldierScorePer / 100);//更新英勇值
						$Player->alter($_playerId, ['kill_soldier'=>'kill_soldier+'.$_num, 'score'=>'score+'.$addScore]);
						(new CityBattleCommonLog)->add($battleId, $_playerId, $ppq1s[0]['camp_id'], '更新英勇值+'.$addScore.'|by杀敌');
					}
				}
				
				foreach($ret['dFormatList'] as $_playerId => $_l){
					$_num = 0;
					foreach($_l['unit'] as $_unit){
						$_num += $_unit['kill_num'];
					}
					$addScore = floor($_num * $killSoldierScorePer / 100);//更新英勇值
					$Player->alter($_playerId, ['kill_soldier'=>'kill_soldier+'.$_num, 'score'=>'score+'.$addScore]);
					(new CityBattleCommonLog)->add($battleId, $_playerId, $cityBattlePlayer['camp_id'], '更新英勇值+'.$addScore.'|by杀敌');
				}
				
				//增加杀敌数
				(new CityBattle)->addKill($battleId, $ppq1s[0]['camp_id'], $ret['dSoldierLoseNum']);
				(new CityBattle)->addKill($battleId, $cityBattlePlayer['camp_id'], $ret['aSoldierLoseNum']);
				$campDefendId = $cityBattlePlayer['camp_id'];
				
			}elseif($type == 2){//攻击城门
				$mailType = 13;
				$wsc = (new CountryBasicSetting)->dicGetOne('wf_atkgate_hitpointlost');
				$ret['power'] = $power = $this->getArmyPower($battleId, $attackerId, $attackPlayerList[$attackerId], $unit);
				eval('$reduceDurability = '.$wsc.';');
				$ret['win'] = true;
				
				//加成
				$addDurability = 0;
				
				//城战科技：机关秘术：城战时每一组车兵增加军团攻城伤害|<#72,255,153#>%{num}%%|
				if($buff502 = (new CityBattleBuff)->getCampBuff($ppq1s[0]['camp_id'], 502)){
					$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
					$CityBattlePlayerArmyUnit->battleId = $battleId;
					$carGroupNum = $CityBattlePlayerArmyUnit->getGroupNumByType($ppq1s[0]['player_id'], $ppq1s[0]['army_id'], 4);
					$addDurability += $carGroupNum * $buff502;
				}
				
				//御驾亲征
				$addDurability += @$extra['cityAttckBuff']*1;
				
				$reduceDurability *= 1+$addDurability;
					
				$reduceDurability = floor($reduceDurability);
				$ret['reduceDurabilityBase'] = $ret['reduceDurability'] = $reduceDurability;
				//石破天惊:攻城获胜或攻击城墙后，额外附加目标当前城防值|<#0,255,0#>%{num}|%的伤害
				$addDurability2 = $CityBattlePlayerGeneral->getSkillsByArmies([$attackPlayerList[$attackerId]], [10100])[10100][0];
				
				//城门扣血
				$Map = new CityBattleMap;
				$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-@reduce:=('.$reduceDurability.'+floor(durability*'.$addDurability2.')))']);
				$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
				$ret['reduceDurability'] = $reduceDurability = $Map->sqlGet('select @reduce')[0]['@reduce'];
				
				$extraData['oldDurability'] = $map['durability'];
				$extraData['newDurability'] = max(0, $map['durability']-$reduceDurability);
				$extraData['durabilityMax'] = $map['max_durability'];
				
				$campDefendId = (new CityBattle)->getBattle($battleId)['camp_id'];
				
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
			$retData['cityBattlePlayer'] = @$ret['cityBattlePlayer'];
			
			//攻击方信息
			$playerMailAr = ['attack'=>['camp_id'=>$ppq1s[0]['camp_id'], 'player'=>array_keys($ret['aFormatList']), 'ids'=>[]], 'defend'=>['camp_id'=>$campDefendId, 'player'=>array_keys($defendPlayerList), 'ids'=>[]]];
			foreach($playerMailAr as $_side => &$_ar){
				$extraData['camp_'.$_side] = $_ar['camp_id'];
				foreach($_ar['player'] as $_playerId){
					$_player = $Player->getByPlayerId($_playerId);
					if(!$_player) continue;
					$ret[$_side[0].'FormatList'][$_playerId]['nick'] = $_player['nick'];
					$ret[$_side[0].'FormatList'][$_playerId]['avatar'] = $_player['avatar_id'];
					$ret[$_side[0].'FormatList'][$_playerId]['x'] = $_player['x'];
					$ret[$_side[0].'FormatList'][$_playerId]['y'] = $_player['y'];
					@$_ar['ids'][$_player['server_id']][] = $_playerId;
				}
			}
			unset($_ar);
			
			$extraData['battleId'] = $battleId;
			$PlayerMail = new PlayerMail;
			$ModelBase = new ModelBase;
			foreach($playerMailAr as $_side => &$_ar){
				foreach($_ar['ids'] as $_serverId => $_playerId){
					$ModelBase->execByServer($_serverId, 'PlayerMail', 'sendCityBattleMail', [$_side, array_keys($ret['aFormatList']), array_keys($defendPlayerList), $ret['win'], $mailType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['aFormatList'], $ret['dFormatList'], $ret['allDead'], $extraData]);
				}
			}
		}else{
			$retData['attackData'] = [];
			$retData['defenceData'] = [];
			
			//攻击方信息
			$extraData['camp_attack'] = $ppq1s[0]['camp_id'];
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
				$cpg = $CityBattlePlayerGeneral->getByGeneralId($attackerId, $_u['general_id']);
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
			$extraData['camp_defend'] = $campDefendId;
			
			$extraData['battleId'] = $battleId;
			$extraData['element_id'] = $map['map_element_id'];
			
			$PlayerMail = new PlayerMail;
			(new ModelBase)->execByServer($_player['server_id'], 'PlayerMail', 'sendCityBattleMail', ['attack', [$attackerId], [], $ret['win'], $mailType, $ppq1s[0]['to_x'], $ppq1s[0]['to_y'], $ret['aFormatList'], [], false, $extraData]);
			
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
			crossSocketSend(CityBattlePlayer::parsePlayerId($defenderId)['server_id'], ['Type'=>'citybattle_city_attacked', 'Data'=>['playerId'=>$defenderId]]);
		}
		
		return $retData;
	}
	
	public function refreshLadder($ppq, $ppqs, &$map, $now, &$finishLadder = false, &$finishBattle = false){
		if(!$ppqs)
			return true;
		$battleId = $ppq['battle_id'];
		$ladderMaxProgress = (new CountryBasicSetting)->dicGetOne('wf_ladder_max_progress');
		
		$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		$_ppqs = $PlayerProjectQueue->afterFindQueue($ppqs);
		$oldSpeed = $_ppqs[0]['target_info']['speed'];
		$sec = $now - (is_numeric($map['build_time']) ? $map['build_time'] : strtotime($map['build_time']));
		$buildValue = $sec * $oldSpeed;
		
		//更新云梯进度和build_time
		if($map['resource'] + $buildValue >= $ladderMaxProgress){
			$finishLadder = true;
		}
		$Map= new CityBattleMap;
		$Map->battleId = $battleId;
		$Map->alter($map['id'], ['resource'=>'LEAST(resource+'.$buildValue.', '.$ladderMaxProgress.')', 'build_time'=>"'".date('Y-m-d H:i:s', $now)."'"]);
		$map['resource'] = min($map['resource'] + $buildValue, $ladderMaxProgress);
		$map['build_time'] = date('Y-m-d H:i:s', $now);
		
		(new CityBattleCommonLog)->add($battleId, 0, 0, '更新云梯进度['.$map['area'].']='.min($map['resource'] + $buildValue, $ladderMaxProgress));
	
		if($finishLadder){
			
			//更新公会占领区域
			$CityBattle = new CityBattle;
			$CityBattle->updateDoor($battleId, $ppqs[0]['camp_id']);
			
			//遣返所有队伍
			foreach($ppqs as $_p){
				//if($now < strtotime($_p['end_time'])){
					$PlayerProjectQueue->callbackQueue($_p['id'], $_p['to_x'], $_p['to_y'], ['ladder'=>true]);
					(new CityBattleCommonLog)->add($battleId, $_p['player_id'], $_p['camp_id'], '云梯部队遣返[queueId='.$_p['id'].']');
				//}
			}
			
			//$this->createArmyReturn($ppq);
			
			$PlayerProjectQueue->callbackHammer($battleId, $map['area']);
			
			//撤离所有下一个区域的敌方占领投石车
			$PlayerProjectQueue->callbackCatapult($battleId, $map['next_area']);
			$PlayerProjectQueue->callbackCrossbow($battleId, $map['next_area']);
			
			//任务：联盟成员在跨服战中参与击破城门%{num}次
			$CityBattlePlayer = new CityBattlePlayer;
			$CityBattlePlayer->battleId = $battleId;
			$guildMemberNum = $CityBattlePlayer->getGuildMemberNumByCampId($ppq['camp_id']);
			foreach($guildMemberNum as $_guildId=>$_num){
				(new CityBattleGuildMission)->addCountByGuildType($_guildId, 5, $_num);
			}
			
			//更新map占领公会
			if(!$Map->alter($map['id'], ['camp_id'=>0])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$map['camp_id'] = 0;
			
			//日志
			(new CityBattleCommonLog)->add($battleId, $ppq['player_id'], $ppq['camp_id'], '云梯建造完成['.$map['area'].']|byPlayerId='.$ppq['player_id'].'('.$ppq['camp_id'].')');
			
			$this->crossNotice($battleId, 'ladderDone', ['x'=>$map['x'], 'y'=>$map['y']]);
			
			if($CityBattle->endBattle($battleId)){
				$finishBattle = true;
			}
		}
		return true;
	}
	
	public function refreshLadder2($allPpqs, $updatePpqs, $map, $ladderMaxProgress, $now, $speed=null){
		if(!$allPpqs)
			return;
		$battleId = $allPpqs[0]['battle_id'];
		$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		
		$restRes = $ladderMaxProgress - $map['resource'];
		$formula = (new CountryBasicSetting)->getValueByKey('wf_ladder_progress');
		$power = 0;
		$armies = [];
		foreach($allPpqs as $_ppq){
			$power += $this->getArmyPower($map['battle_id'], $_ppq['player_id'], $_ppq['army_id']);
			$armies[] = $_ppq['army_id'];
		}
		eval('$speed = '.$formula.';');
		
		//暗渡陈仓:驻守云梯时，云梯建造速度增加|<#0,255,0#>%{num}|%
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$CityBattlePlayerGeneral->battleId = $battleId;
		$speedBuff = $CityBattlePlayerGeneral->getSkillsByArmies($armies, [10093])[10093][0];
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
		$Map = new CityBattleMap;
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

	public function getArmyPower($battleId, $playerId, $armyId=0, &$unit=[]){
		$PlayerGeneral = new CityBattlePlayerGeneral;
		$PlayerGeneral->battleId = $battleId;
		$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
		$CityBattlePlayerArmyUnit->battleId = $battleId;
		$Soldier = new Soldier;
		$General = new General;
		$data = $CityBattlePlayerArmyUnit->getByPlayerId($playerId);
		$power = 0;
		$unit = [];
		foreach($data as $_k => &$_data){
			if($armyId && $_data['army_id'] != $armyId){
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
		$CityBattlePlayer = new CityBattlePlayer;
		$CityBattlePlayer->battleId = $battleId;
		
		$members = $CityBattlePlayer->find(['battle_id='.$battleId.' and status>0'])->toArray();
		$playerIds = [];
		foreach($members as $_d){
			$_serverId = CityBattlePlayer::parsePlayerId($_d['player_id'])['server_id'];
			$playerIds[$_serverId][] = $_d['player_id'];
		}
		foreach($playerIds as $_serverId => $_playerIds){
			$_data = $data;
			$_data['playerId'] = $_playerIds;
			$_data['type'] = $type;
			crossSocketSend($_serverId, ['Type'=>'citybattle', 'Data'=>$_data]);
		}
		
	}
	
	public function useSkill($battleId, $skillId, $fromPlayerId, $fromArmy=0, $toPlayerId=0, $data=[]){
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$CityBattlePlayerGeneral->battleId = $battleId;
		if($fromArmy){
			$values = $CityBattlePlayerGeneral->getSkillsByArmies([$fromArmy], [$skillId])[$skillId];
		}else{
			$values = $CityBattlePlayerGeneral->getSkillsByPlayer($fromPlayerId, null, [$skillId])[$skillId];
		}
		if(!$values[0] && !$values[1])
			return false;
		switch($skillId){
			case 10053://缓兵之计
				$CityBattlePlayer = new CityBattlePlayer;
				$CityBattlePlayer->battleId = $battleId;
				$values[0] = floor($values[0]);
				$CityBattlePlayer->alter($toPlayerId, ['debuff_queuetime'=>'GREATEST(debuff_queuetime, '.$values[0].')']);
			break;
			case 10058://嗜血
				$CityBattlePlayer = new CityBattlePlayer;
				$CityBattlePlayer->battleId = $battleId;
				$addHp = floor($data['durability'] * $values[0]);
				$CityBattlePlayer->alter($fromPlayerId, ['wall_durability'=>'@durability:=(LEAST(wall_durability_max, wall_durability+'.$addHp.'))']);
				$restHp = $CityBattlePlayer->sqlGet('select @durability')[0]['@durability'];
				(new CityBattleCommonLog)->add($battleId, $fromPlayerId, $data['campId'], '技能:嗜血|回血+'.$addHp.',剩余'.$restHp);
			break;
		}
		return $values;
	}
}