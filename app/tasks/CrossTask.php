<?php
/**
 * 跨服战
 */
class CrossTask extends \Phalcon\CLI\Task
{
    public $CrossPlayer          = null;
    public $CrossGuild           = null;
    public $PlayerMail           = null;
    public $CrossRound           = null;
    public $CrossGuildInfo       = null;
    public $CrossBattle          = null;
    public $WarfareServiceConfig = null;

    public $switchMatchFlag = false;//匹配切换开关



    public $startTime   = '';//比赛开战时间
    public $teamJoinNum = 0;//每个公会的参加最大人数

    public $rewardItemArr = [];//奖励

    const Offset = 10;//偏移量
    /**
     * 攻城锤攻击城门
     * 
     * 
     * @return <type>
     */
	public function hammerAttackAction($param=[]){
		$taskId = @$param[0];
		
		$processName = "php_task_crosshammerattack";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		//$atkcdTime = (new WarfareServiceConfig)->dicGetOne('wf_warhammer_atkcolddown');

		$db = $this->di['db_cross_server'];
		
		//结束时间
		$endTime = date('Y-m-d 23:59:59');
		//查找进行中的battleIds
		$CrossBattle = new CrossBattle;
		$battleIds = $CrossBattle->getCurrentBattleIdList();
		if(!$battleIds){
			echo '['.date('Y-m-d H:i:s').']未找到比赛'.PHP_EOL;
		}
		
		//循环查找可出手的攻城锤
		$CrossPlayer = new CrossPlayer;
		$CrossBattle = new CrossBattle;
		$Map = new CrossMap;
		$CrossGuild = new CrossGuild;
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$DispatcherTask = new CrossDispatcherTask;
		$QueueCross = new QueueCross;
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$lockX = 0;
		$lockY = 0;
		$lockToX = 0;
		$lockToY = 0;
		$lockBattleId = 0;
		
		echo '['.date('Y-m-d H:i:s').']begin'.PHP_EOL;
		//try
		try {
			$j = 0;
			while(true){
				$j++;
				if($j % 60 == 0){
					$j %= 60;
					if(!$CrossBattle->getCurrentBattleIdList()){
						break;
					}
				}
				/*if(date('Y-m-d H:i:s') > $endTime){
					break;
				}*/
				$battleIds = Set::extract('/id', $CrossBattle->find(['status in ('.CrossBattle::STATUS_ATTACK.', '.CrossBattle::STATUS_DEFEND.')'])->toArray());
				if(!$battleIds){
					sleep(1);
					continue;
				}
				$maps = $Map->sqlGet('select * from '.$Map->getSource().' where battle_id in ('.join(',', $battleIds).') and map_element_origin_id = 301 and guild_id > 0 and (durability > 0 || (durability = 0 and recover_time <= now())) and attack_time <= FROM_UNIXTIME(UNIX_TIMESTAMP() - attack_cd) order by update_time asc limit 10');
				if(!$maps){
					sleep(1);
					continue;
				}

				foreach($maps as $map){
					$CrossPlayerGeneral->battleId = $CrossGuild->battleId = $Map->battleId = $map['battle_id'];
					$lockX = $map['x'];
					$lockY = $map['y'];
					$lockBattleId = $map['battle_id'];
					
					$Map->rebuildBuilding($map);
					
					//获取对应城门
					$doorMap = $Map->findFirst(['battle_id='.$map['battle_id'].' and area='.$map['area'].' and map_element_origin_id=302 and durability > 0']);
					if(!$doorMap){
						$Map->alter($map['id'], []);
						continue;
					}else{
						$doorMap = $doorMap->toArray();
						$lockToX = $doorMap['x'];
						$lockToY = $doorMap['y'];
						
						//begin
						dbBegin($db);
						//lock
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY)){
							dbRollback($db);
							$Map->alter($map['id'], []);
							continue;
						}
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY)){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
							$Map->alter($map['id'], []);
							continue;
						}
						
						//计算攻城锤攻击力
						$formula = (new WarfareServiceConfig)->getValueByKey('wf_warhammer_atkpower');
						$power = 0;
						$condition = ['type='.CrossPlayerProjectQueue::TYPE_HAMMER_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
						$ppqs = CrossPlayerProjectQueue::find($condition);
						if(!$ppqs->toArray()){
							dbRollback($db);
							$Map->alter($map['id'], []);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						foreach($ppqs as $_ppq){
							$power += $QueueCross->getArmyPower($map['battle_id'], $_ppq->player_id, $_ppq->army_id);
						}
						eval('$reduceDurability = '.$formula.';');
						
						$buff = 0;
						$addBuff = 0;
						//君临天下：若该武将的统御高于所有敌军武将，则所有本方器械的伤害增加%
						$buff += $CrossGuild->getGuildInfo($map['guild_id'])['buff_buildattack'];
						
						//攻城锤精通:驻守时增加攻城锤攻击伤害%
						$armyIds = array_unique(Set::extract('/army_id', $ppqs->toArray()));
						$buff += $CrossPlayerGeneral->getSkillsByArmies($armyIds, [21])[21][0];
						
						//攻城锤大师:每次攻击后，攻城锤的攻击力增加
						$addBuff += $CrossPlayerGeneral->getSkillsByArmies($armyIds, [22])[22][0] * $map['attack_times'];
						
						$reduceDurability *= 1 + $buff;
						$reduceDurability += $addBuff;
						$reduceDurability = floor($reduceDurability);
						//城门扣血
						$Map->alter($doorMap['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')']);
						$doorMap['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						(new CrossCommonLog)->add($map['battle_id'], 0, $map['guild_id'], '攻击城门['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $doorMap['durability']-$reduceDurability).'|by攻城锤');
						(new QueueCross)->crossNotice($map['battle_id'], 'hammerAttackDoor', ['reduce'=>$reduceDurability, 'rest'=>max(0, $doorMap['durability']-$reduceDurability), 'from_x'=>$map['x'], 'from_y'=>$map['y'], 'to_x'=>$doorMap['x'], 'to_y'=>$doorMap['y']]);
						
						//获取双方公会id
						$guilds = $CrossBattle->getADGuildId($map['battle_id']);
						
						//如果破门
						if($doorMap['durability'] <= $reduceDurability){
							//获取攻击方guildid
							$guildId = $guilds['attack'];
							
							//更新公会占领区域
							$CrossBattle->updateAttackArea($map['battle_id'], $doorMap['next_area']);
							/*$crossBattle = $CrossBattle->getBattle($map['battle_id']);
							$attackArea = parseArray($crossBattle['attack_area']);
							$attackArea[] = $doorMap['next_area'];
							$attackArea = join(',', array_unique($attackArea));
							$CrossBattle->alter($map['battle_id'], ['attack_area'=>$attackArea]);*/
							
							//撤离所有下一个区域的敌方占领投石车和床弩
							$PlayerProjectQueue->callbackCatapult($map['battle_id'], $doorMap['next_area']);
							$PlayerProjectQueue->callbackCrossbow($map['battle_id'], $doorMap['next_area']);
							
							//遣返本区攻城锤内部队
							$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area']);
							
							//日志
							(new CrossCommonLog)->add($map['battle_id'], 0, $map['guild_id'], '破门['.$doorMap['area'].']|by攻城锤');
							
							(new QueueCross)->crossNotice($map['battle_id'], 'doorBroken', ['x'=>$doorMap['x'], 'y'=>$doorMap['y']]);
							
						}
						
						//计算攻击时间
						//if($map['attack_time'] == '0000-00-00 00:00:00'){
							$attackTime = date('Y-m-d H:i:s');
						/*}else{
							$attackTime = date('Y-m-d H:i:s', strtotime($map['attack_time'])+$atkcdTime);
						}*/
						
						//更新攻城锤cd
						$Map->alter($map['id'], ['attack_time'=>"'".$attackTime."'", 'attack_times'=>'attack_times+1']);
						
						if(!(new CrossBattle)->isActivity($map['battle_id'])){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						
						//end
						dbCommit($db);
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
						
						//长连接通知
						//获取参战成员
						//(new QueueCross)->crossNotice($map['battle_id'], 'hammerAttack', ['reduce'=>$reduceDurability, 'rest'=>max(0, $doorMap['durability']-$reduceDurability), 'from_x'=>$map['x'], 'from_y'=>$map['y'], 'to_x'=>$doorMap['x'], 'to_y'=>$doorMap['y']]);
						/*$CrossPlayer->battleId = $map['battle_id'];
						foreach(['attack', 'defend'] as $_t){
							$playerIds = [];
							$serverId = CrossPlayer::parseGuildId($guilds[$_t])['server_id'];
							if($serverId){
								$members = $CrossPlayer->getByGuildId($guilds[$_t]);
								foreach($members as $_d){
									$playerIds[] = $_d['player_id'];
								}
								crossSocketSend($serverId, ['Type'=>'cross', 'Data'=>['playerId'=>$playerIds, 'type'=>'hammerAttack', 'reduce'=>$reduceDurability, 'rest'=>max(0, $doorMap['durability']-$reduceDurability), 'from_map_id'=>$map['id'], 'to_map_id'=>$doorMap['id']]]);
							}
							
						}*/
						
						echo '['.date('Y-m-d H:i:s').']hammerAttack(mapid='.$map['id'].')'.PHP_EOL;
					}
					
				}
			}
		} catch (Exception $e) {
			dbRollback($db);
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
		}	
			
		echo '['.date('Y-m-d H:i:s').']end'.PHP_EOL;
	}
	
    /**
     * 床弩攻击
     * 
     * 
     * @return <type>
     */
	public function crossbowAttackAction($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_crosscrossbowattack";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		//$atkcdTime = (new WarfareServiceConfig)->dicGetOne('wf_glaivethrower_atkcolddown');
		$db = $this->di['db_cross_server'];
		
		//结束时间
		$endTime = date('Y-m-d 23:59:59');
		//查找进行中的battleIds
		$CrossBattle = new CrossBattle;
		$battleIds = $CrossBattle->getCurrentBattleIdList();
		if(!$battleIds){
			echo '['.date('Y-m-d H:i:s').']未找到比赛'.PHP_EOL;
		}
		
		//循环查找可出手的攻城锤
		$CrossPlayer = new CrossPlayer;
		$CrossBattle = new CrossBattle;
		$Map = new CrossMap;
		$CrossGuild = new CrossGuild;
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$DispatcherTask = new CrossDispatcherTask;
		$QueueCross = new QueueCross;
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$lockX = 0;
		$lockY = 0;
		$lockToX = 0;
		$lockToY = 0;
		$lockBattleId = 0;
		
		echo '['.date('Y-m-d H:i:s').']begin'.PHP_EOL;
		//try
		try {
			$j = 0;
			while(true){
				$j++;
				if($j % 60 == 0){
					$j %= 60;
					if(!$CrossBattle->getCurrentBattleIdList()){
						break;
					}
				}
				/*if(date('Y-m-d H:i:s') > $endTime){
					break;
				}*/
				$battleIds = Set::extract('/id', $CrossBattle->find(['status in ('.CrossBattle::STATUS_ATTACK.', '.CrossBattle::STATUS_DEFEND.')'])->toArray());
				if(!$battleIds){
					sleep(1);
					continue;
				}
				$maps = $Map->sqlGet('select * from '.$Map->getSource().' where battle_id in ('.join(',', $battleIds).') and map_element_origin_id = 303 and player_id > 0 and attack_time <= FROM_UNIXTIME(UNIX_TIMESTAMP() - attack_cd) order by update_time asc limit 10');//FROM_UNIXTIME(UNIX_TIMESTAMP() - 30);
				if(!$maps){
					sleep(1);
					continue;
				}
				foreach($maps as $map){
					$CrossPlayer->battleId = $CrossPlayerGeneral->battleId = $CrossGuild->battleId = $Map->battleId = $map['battle_id'];
					$lockX = $map['x'];
					$lockY = $map['y'];
					$lockBattleId = $map['battle_id'];
					
					//获取对应攻城锤或云梯
					$targetMap = $Map->findFirst(['battle_id='.$map['battle_id'].' and area='.$map['target_area'].' and map_element_origin_id in (301, 304) and guild_id > 0']);
					if($targetMap){
						$targetMap = $targetMap->toArray();
						$lockToX = $targetMap['x'];
						$lockToY = $targetMap['y'];
						
						//begin
						dbBegin($db);
						//lock
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY)){
							dbRollback($db);
							$Map->alter($map['id'], []);
							continue;
						}
						
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY)){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
							$Map->alter($map['id'], []);
							continue;
						}
						
						$Map->rebuildBuilding($targetMap);
						
						//检查对象血
						if(!$targetMap['durability']){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
							$Map->alter($map['id'], []);
							continue;
						}
						
						//计算床弩攻击力
						$formula = (new WarfareServiceConfig)->getValueByKey('wf_glaivethrower_atkpower');
						$power = 0;
						$condition = ['type='.CrossPlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
						$ppqs = CrossPlayerProjectQueue::find($condition);
						if(!$ppqs->toArray()){
							dbRollback($db);
							$Map->alter($map['id'], []);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						foreach($ppqs as $_ppq){
							$power += $QueueCross->getArmyPower($map['battle_id'], $_ppq->player_id, $_ppq->army_id);
						}
						eval('$reduceDurability = '.$formula.';');
						
						$buff = 0;
						$addBuff = 0;
						//君临天下：若该武将的统御高于所有敌军武将，则所有本方器械的伤害增加%
						$buff += $CrossGuild->getGuildInfo($map['guild_id'])['buff_buildattack'];
						
						//床弩精通:驻守时增加床弩攻击伤害%
						$armyIds = array_unique(Set::extract('/army_id', $ppqs->toArray()));
						$buff += $CrossPlayerGeneral->getSkillsByArmies($armyIds, [15])[15][0];
						
						//床弩大师:每次攻击后，床弩的攻击力增加
						$addBuff += $CrossPlayerGeneral->getSkillsByArmies($armyIds, [16])[16][0] * $map['attack_times'];
						
						$reduceDurability *= 1 + $buff;
						$reduceDurability += $addBuff;
						$reduceDurability = floor($reduceDurability);
						
						if($targetMap['map_element_origin_id'] == 301){//攻城锤
							//扣血
							$recoverTime = (new WarfareServiceConfig)->dicGetOne('wf_warhammer_respawn_time');
							$Map->alter($targetMap['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
							$targetMap['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
							
							(new CrossCommonLog)->add($map['battle_id'], 0, $map['guild_id'], '攻击攻城锤['.$targetMap['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $targetMap['durability']-$reduceDurability).'|by床弩(mapId='.$map['id'].')');
							
							//如果攻城锤血0，遣返所有攻城锤部队
							if($targetMap['durability'] <= $reduceDurability){
								
								$PlayerProjectQueue->callbackHammer($map['battle_id'], $targetMap['area'], $targetMap['id']);
								
								//日志
								(new CrossCommonLog)->add($map['battle_id'], 0, $map['guild_id'], '攻城锤0血['.$targetMap['area'].']|by床弩(mapId='.$map['id'].')');
							}
							
							$targetType = 'Hammer';
						}else{//云梯
							//刷新云梯进度
							$condition = ['type='.CrossPlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$targetMap['id'].' and status=1'];
							$ppqs = $PlayerProjectQueue->find($condition)->toArray();
							(new QueueCross)->refreshLadder($ppqs[0], $ppqs, $targetMap, time());
							//扣血
							$recoverTime = (new WarfareServiceConfig)->dicGetOne('wf_ladder_respawn_time');
							
							//云梯修复
							$playerIds = Set::extract('/player_id', $CrossPlayer->getByGuildId($targetMap['guild_id']));
							$recoverTimeBuff = $CrossPlayerGeneral->getSkillsByPlayers($playerIds, [24])[24][0];
							$recoverTime -= $recoverTimeBuff;
							$recoverTime = floor($recoverTime);
							
							$Map->alter($targetMap['id'], ['durability'=>'GREATEST(0, durability-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
							
							(new CrossCommonLog)->add($map['battle_id'], 0, $map['guild_id'], '攻击云梯['.$targetMap['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $targetMap['durability']-$reduceDurability).'|by床弩(mapId='.$map['id'].')');
							
							//如果云梯血0，遣返所有云梯部队
							if($targetMap['durability'] <= $reduceDurability){
								
								$PlayerProjectQueue->callbackLadder($map['battle_id'], $targetMap['id']);
								
								//日志
								(new CrossCommonLog)->add($map['battle_id'], 0, $map['guild_id'], '天梯0血['.$targetMap['area'].']|by床弩(mapId='.$map['id'].')');
								
							}
							
							$targetType = 'Ladder';
						}
						
						//获取双方公会id
						//$guilds = $CrossBattle->getADGuildId($map['battle_id']);
						
						//计算攻击时间
						//if($map['attack_time'] == '0000-00-00 00:00:00'){
							$attackTime = date('Y-m-d H:i:s');
						/*}else{
							$attackTime = date('Y-m-d H:i:s', strtotime($map['attack_time'])+$atkcdTime);
						}*/
						
						//更新攻城锤cd
						$Map->alter($map['id'], ['attack_time'=>"'".$attackTime."'", 'attack_times'=>'attack_times+1']);
						
						if(!(new CrossBattle)->isActivity($map['battle_id'])){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						
						//end
						dbCommit($db);

						//unlock
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
						
						//长连接通知
						//获取参战成员
						(new QueueCross)->crossNotice($map['battle_id'], 'crossbowAttack'.$targetType, ['reduce'=>$reduceDurability, 'rest'=>max(0, $targetMap['durability']-$reduceDurability), 'from_x'=>$map['x'], 'from_y'=>$map['y'], 'to_x'=>$targetMap['x'], 'to_y'=>$targetMap['y']]);
						if($targetMap['durability'] <= $reduceDurability){
							if($targetMap['map_element_origin_id'] == 301){
								(new QueueCross)->crossNotice($map['battle_id'], 'hammerBroken', ['x'=>$targetMap['x'], 'y'=>$targetMap['y']]);
							}else{
								(new QueueCross)->crossNotice($map['battle_id'], 'ladderBroken', ['x'=>$targetMap['x'], 'y'=>$targetMap['y']]);
							}
						}
						/*$CrossPlayer->battleId = $map['battle_id'];
						foreach(['attack', 'defend'] as $_t){
							$playerIds = [];
							$serverId = CrossPlayer::parseGuildId($guilds[$_t])['server_id'];
							if($serverId){
								$members = $CrossPlayer->getByGuildId($guilds[$_t]);
								foreach($members as $_d){
									$playerIds[] = $_d['player_id'];
								}
								crossSocketSend($serverId, ['Type'=>'cross', 'Data'=>['playerId'=>$playerIds, 'type'=>'crossbowAttack', 'reduce'=>$reduceDurability, 'rest'=>max(0, $targetMap['durability']-$reduceDurability), 'from_map_id'=>$map['id'], 'to_map_id'=>$targetMap['id']]]);
							}
						}*/
						
						echo '['.date('Y-m-d H:i:s').']crossbowAttack(mapid='.$map['id'].')'.PHP_EOL;
					}
					
				}
			}
		} catch (Exception $e) {
			dbRollback($db);
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockToX, $lockToY);//unlock
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $lockBattleId, $lockX, $lockY);//unlock
		}
		
		
		echo '['.date('Y-m-d H:i:s').']end'.PHP_EOL;
	}
	
	public function testAction($param){
		Cache::db('cache', 'Cross')->flushDB();
		//Cache::db('static', 'Cross')->flushDB();
		Cache::db('dispatcher', 'Cross')->flushDB();
		
		$battleId = 383;
		$CrossGuild = new CrossGuild;
		$CrossPlayer = new CrossPlayer;
		$CrossPlayerArmy = new CrossPlayerArmy;
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayerSoldier = new CrossPlayerSoldier;
		$CrossGuild->battleId = $CrossPlayer->battleId = $CrossPlayerArmy->battleId = $CrossPlayerGeneral->battleId = $battleId;
		
		$CrossGuild->cpData(1, 1);
		$CrossPlayer->cpData(500397, 1);
		$CrossPlayerGeneral->cpData(500397, 1);//general必须在army之前拷贝
		$CrossPlayerArmy->cpData(500397, 1);
		//$CrossPlayerSoldier->find(['battle_id='.$this->battleId])->delete();
		
	}
    /**
     * test （该脚本用于测试）
     * 插入初始化数据
     */
    public function generateInfoDataAction(){
        global $config;
        $Guild          = new Guild;
        $CrossGuildInfo = new CrossGuildInfo;
        //$CrossGuildInfo->sqlExec("DELETE FROM cross_guild_info;");

        $re = $Guild->sqlGet("SELECT * FROM player_guild GROUP BY guild_id HAVING COUNT(*)>9");
        $idArr = Set::extract('/guild_id', $re);
        foreach($re as $k=>$v) {
            $idArr1 = $idArr;
            unset($idArr1[$k]);

            $info    = $CrossGuildInfo->addNew($v['guild_id']);
            $is_new  = mt_rand(0, 2);
            if($is_new!=0) {
                $info->win_times  = mt_rand(0, 20);
                $info->lose_times = mt_rand(0, 20);

                $info->latest_battle_is_win  = mt_rand(0, 1);
            }
            $info->save();
        }
    }

    /**
     * 开启一轮跨服战
     *
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php cross startRound
     */
    public function startRoundAction(){
        log4task("开始生成新一轮跨服战...");
        $CrossGuildInfo = new CrossGuildInfo;
        $CrossRound     = new CrossRound;
        //生成新一轮比赛
        $currentRoundId = $CrossRound->addNew();
        if($currentRoundId>0) {//success
            log4task("当前轮比赛表数据生成roundId={$currentRoundId}");
            //将所有联盟设为比赛状态
            $sql2 = "UPDATE cross_guild_info SET `status`=" . CrossGuildInfo::Status_not_joined . " AND update_time=NOW() WHERE `status`=". CrossGuildInfo::Status_joined.";";
            $CrossGuildInfo->sqlExec($sql2);
            log4task("将所有联盟更改为未参赛状态，namely: cross_guild_info.status=".CrossGuildInfo::Status_not_joined);
        } else {
            $currentRoundId = $CrossRound->getCurrentRoundId();
            log4task("比赛已经生成，无需重复执行。roundId={$currentRoundId}");
        }
    }

    /**
     * 实际匹配算法：
     * 从$in1中选一个,再从$in2中选一个，然后将匹配结果存到$out中
     *
     * @param $in1 guild1 集合
     * @param $in2 guild2 集合
     * @param $out 最终匹配集合
     * @param $label1  标记 for log
     * @param $label2  标记 for log
     * @param $type 1 随机匹配 2 两两匹配 3 固定匹配
     * @return bool
     */
    public function matchMethod(&$in1, &$in2, &$out, $label1, $label2, $type){
        if(empty($in1) || empty($in2)) return false;
        switch($type) {
            case 1://随机匹配
                $g1 = array_pop($in1);
                shuffle($in2);
                $g2 = array_pop($in2);
                break;
            case 2://两两匹配
                if($this->switchMatchFlag) {//栈顶出两个匹配
                    $g1 = array_pop($in1);
                    $g2 = array_pop($in2);
                } else {//栈底出两个匹配
                    $g1 = array_shift($in1);
                    $g2 = array_shift($in2);
                }
                $this->switchMatchFlag = !$this->switchMatchFlag;
                break;
            case 3://固定匹配
                $g1 = $in1;
                $g2 = $in2;
                break;
        }
        $g1['text'] = $label1;
        $g2['text'] = $label2;

        $out[] = [1 => $g1, 2 => $g2];
        return true;
    }

    /**
     * 技能武将相关数据copy
     *
     * @param $playerId
     * @param $serverId
     * @param $crossArmyInfo
     */
    public function cpDataWhenMatching($playerId, $serverId, $crossArmyInfo, $guildId){
        if(empty($crossArmyInfo)) {//自动填充
            $crossArmyInfo = (new ModelBase)->getByServer($serverId, 'PlayerGuild', 'updateDefaultCrossArmyInfo', [$playerId, $guildId]);
        }
        $generals = $crossArmyInfo['army'][0];
        if (!empty($crossArmyInfo['army'][1])) {
            $generals = array_merge($generals, $crossArmyInfo['army'][1]);
        }
        $army1CpData = $army2CpData = [];
        foreach ($crossArmyInfo['army'][0] as $v) {
            $army1CpData[] = [$v, 0, 0];
        }
        foreach ($crossArmyInfo['army'][1] as $v) {
            $army2CpData[] = [$v, 0, 0];
        }
        $battleId = $this->CrossGuild->battleId;
        $this->CrossPlayerArmy->find(['battle_id=' . $battleId . ' and player_id=' . $playerId])->delete();
        $this->CrossPlayerArmyUnit->find(['battle_id=' . $battleId . ' and player_id=' . $playerId])->delete();

        $this->CrossPlayer->cpData($playerId, $serverId);
        $this->CrossPlayerGeneral->cpData($playerId, $serverId, $generals);//general必须在army之前拷贝
        $this->CrossPlayerMasterSkill->cpData($playerId, $battleId, $crossArmyInfo['skill']);
        if (!empty($army1CpData))
            $this->CrossPlayerArmy->addByData($playerId, $army1CpData);
        if (!empty($army2CpData))
            $this->CrossPlayerArmy->addByData($playerId, $army2CpData);
    }
    /**
     * 初始化跨服战数据
     *
     * @param $battleId
     * @param $guildId
     */
    public function initCrossData($battleId, $guildId) {
        if(!$this->CrossGuild || !$this->CrossPlayer || !$this->PlayerMail) exit('异常!!!');

        $this->CrossGuild->battleId             = $battleId;
        $this->CrossPlayer->battleId            = $battleId;
        $this->CrossPlayerArmy->battleId        = $battleId;
        $this->CrossPlayerGeneral->battleId     = $battleId;
        $this->CrossPlayerArmyUnit->battleId    = $battleId;
        $this->CrossPlayerMasterSkill->battleId = $battleId;


        $guildIdInfo = CrossPlayer::parseGuildId($guildId);
        $guildId     = $guildIdInfo['guild_id'];//原始guild_id
        $serverId    = $guildIdInfo['server_id'];
        log4task('拷贝cross_guild数据');
        $this->CrossGuild->cpData($guildId, $serverId);

        log4task('拷贝cross_player数据');
        $allGuildMember = (new ModelBase)->getByServer($serverId, 'PlayerGuild', 'getAllGuildMember', [$guildId, false]);
        //先插选中加入的
        $i = 0;
        $battlePlayerId = [];
        foreach($allGuildMember as $k=>$v) {
            if($v['cross_joined_flag']==1) {
                log4task("=", false);
                $this->cpDataWhenMatching($v['player_id'], $serverId, $v['cross_army_info'], $v['guild_id']);
                $battlePlayerId[] = $v['player_id'];
                $i++;
                unset($allGuildMember[$k]);
                if($i>=$this->teamJoinNum) break;
            }
        }
        echo PHP_EOL;
        //不够再插入申请列表里的
        if($i<$this->teamJoinNum) {
            $allGuildMember = Set::sort($allGuildMember, '{n}.Player.power', 'desc');
            $extraBattlePlayerIds = [];
            foreach($allGuildMember as $k1=>$v1) {
                if($v1['cross_application_flag']==1) {
                    log4task('=', false);
                    $this->cpDataWhenMatching($v1['player_id'], $serverId, $v1['cross_army_info'], $v1['guild_id']);
                    $extraBattlePlayerIds[] = $v1['player_id'];
                    $battlePlayerId[]       = $v1['player_id'];
                    unset($allGuildMember[$k1]);
                    $i++;
                    if($i==$this->teamJoinNum) break;
                }
            }
            echo PHP_EOL;
            if(!empty($extraBattlePlayerIds)) {//更改标志为参赛
                (new ModelBase)->getByServer($serverId, 'PlayerGuild', 'changeCrossJoinedFlag', [$guildId, $extraBattlePlayerIds]);
            }
        }
        (new CrossCommonLog)->add($battleId, 0, $guildId, "参赛server_id={$serverId},player_id=".json_encode($battlePlayerId));
        log4task("当前盟最终参赛人数：[{$i}/".$this->teamJoinNum.']');
        //申请过，但没有入选的人发邮件
        log4task('给申请过但没入选的人发邮件');
        $notJoinedButApplicationPlayerIds = [];//申请参与却为被选中的成员
        foreach($allGuildMember as $k2=>$v2) {
            if($v2['cross_application_flag']==1) {
                log4task('=', false);
                $notJoinedButApplicationPlayerIds[] = $v2['player_id'];
            }
        }
        if(!empty($notJoinedButApplicationPlayerIds)) {
            $this->PlayerMail->sendSystem($notJoinedButApplicationPlayerIds, PlayerMail::TYPE_CROSS_FAILJOIN, '', '', 0);
        }
        echo PHP_EOL;

    }
    /**
     * cross服务器执行，匹配脚本
     *
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php cross match
     */
    public function matchAction(){
        set_time_limit(0);
        $startTimeExec = microtime_float();

        //class init
        $this->CrossRound           = $CrossRound           = new CrossRound;
        $this->CrossGuildInfo       = $CrossGuildInfo       = new CrossGuildInfo;
        $this->CrossBattle          = $CrossBattle          = new CrossBattle;
        $this->CrossGuild           = $CrossGuild           = new CrossGuild;
        $this->CrossPlayer          = $CrossPlayer          = new CrossPlayer;
        $this->PlayerMail           = $PlayerMail           = new PlayerMail;
        $this->WarfareServiceConfig = $WarfareServiceConfig = new WarfareServiceConfig;
        $this->CrossMap             = $CrossMap             = new CrossMap;

        $this->CrossPlayerArmy        = new CrossPlayerArmy;
        $this->CrossPlayerGeneral     = new CrossPlayerGeneral;
        $this->CrossPlayerArmyUnit    = new CrossPlayerArmyUnit;
        $this->CrossPlayerMasterSkill = new CrossPlayerMasterskill;

        //更改匹配状态
        $currentRoundId = $CrossRound->getCurrentRoundId();
        if(!$currentRoundId) log4task('当前轮次没有生成，cross startRound脚本没有执行成功');
        if(!$CrossRound->alterCurrent(['status'=>CrossRound::Status_match_start], ['status'=>CrossRound::Status_sign])) {
            log4task('匹配失败!更改pk_round状态失败,line='.__LINE__);
            exit;
        }

        //初始化变量
        $queue                       = [];
        $idQueue                     = [];
        $Only_Newbie_Cross_battle    = false;
        $Winner_Queue_Status_Waiting = false;//胜者组剩余一人
        $fields                      = "id, guild_id, match_score";
        $queueAlone                  = [];//轮空的队列
        $this->startTime             = $startTime = date('Y-m-d ') . $WarfareServiceConfig->getValueByKey('open_time');//比赛开打时间
        $this->teamJoinNum           = $WarfareServiceConfig->getValueByKey('wf_guild_num');//每一队最终参赛的最大人数
        $aloneLogTxt                 = '';//轮空log文字

        //case 已参加比赛联盟计算匹配积分
        log4task('已参加比赛联盟计算匹配积分Begin');
        $sql0               = "SELECT guild_id FROM cross_guild_info WHERE `status`=1;";
        $allJoinedGuildInfo = $CrossGuildInfo->sqlGet($sql0);
        $allJoinedGuildInfo = array_map(function($node){
            $parsedGuildInfo = CrossPlayer::parseGuildId($node['guild_id']);
            return ['guild_id'=>$parsedGuildInfo['guild_id'], 'server_id'=>$parsedGuildInfo['server_id']];
        }, $allJoinedGuildInfo);

        $sql0_1 = "SELECT SUM(general_power) match_score FROM (SELECT general_power FROM player WHERE guild_id=%d ORDER BY general_power DESC LIMIT ". $this->teamJoinNum . ") a;";
        foreach($allJoinedGuildInfo as $v) {
            $matchScore = (new ModelBase)->getByServer($v['server_id'], 'Player', 'sqlGet', [sprintf($sql0_1, $v['guild_id'])]);
            if($matchScore) {
                $CrossGuildInfo->alter($v['guild_id'], ['match_score'=>$matchScore[0]['match_score']], $v['server_id']);
            }
        }
        log4task('已参加比赛联盟计算匹配积分End');
        //case 新进联盟匹配
        log4task('匹配新进盟');
        $sql1        = "SELECT {$fields} FROM cross_guild_info WHERE latest_battle_is_win=-1 AND `status`=1 ORDER BY match_score ASC;";
        $newbieQueue = $CrossGuildInfo->sqlGet($sql1);
        //随机 两两相应匹配
        //matching rand...
        while(count($newbieQueue)>0) {
            log4task('=', false);
            if (count($newbieQueue) == 1) {//当前组 余一人
                log4task('新进盟有轮空,从败者组选积分最接近一人');
                $lastNewbie = array_pop($newbieQueue);
                $tmpScore   = $lastNewbie['match_score'];
                //先败者组匹配
                $sql1_1     = "SELECT {$fields} FROM cross_guild_info WHERE latest_battle_is_win=0 AND `status`=1 AND match_score<={$tmpScore} ORDER BY match_score DESC LIMIT 1;";
                $loserOne   = $CrossGuildInfo->sqlGet($sql1_1);
                if(empty($loserOne)) {
                    $sql1_2   = "SELECT {$fields} FROM cross_guild_info WHERE latest_battle_is_win=0 AND `status`=1 AND match_score>{$tmpScore} ORDER BY match_score ASC LIMIT 1;";
                    $loserOne = $CrossGuildInfo->sqlGet($sql1_2);
                }

                if(empty($loserOne)) {//仍然轮空，再去胜者组匹配
                    $sql1_3     = "SELECT {$fields} FROM cross_guild_info WHERE latest_battle_is_win=1 AND `status`=1 AND match_score<={$tmpScore} ORDER BY match_score DESC LIMIT 1;";
                    $winnerOne   = $CrossGuildInfo->sqlGet($sql1_3);
                    if(empty($winnerOne)) {
                        $sql1_4   = "SELECT {$fields} FROM cross_guild_info WHERE latest_battle_is_win=1 AND `status`=1 AND match_score>{$tmpScore} ORDER BY match_score ASC LIMIT 1;";
                        $winnerOne = $CrossGuildInfo->sqlGet($sql1_4);
                    }
                    if(empty($winnerOne)) {//只有新进盟的轮空
                        $Only_Newbie_Cross_battle = true;//第一轮比赛，没有有战败组，同样也没有战胜
                        $queueAlone[]             = $lastNewbie;
                        $aloneLogTxt              = '新进盟轮空';
                    } else {
                        $matchedId = $winnerOne[0]['id'];
                        $this->matchMethod($lastNewbie, $winnerOne[0], $queue, 'newbie', 'winner', 3);
                    }
                } else {
                    $matchedId = $loserOne[0]['id'];
                    $this->matchMethod($lastNewbie, $loserOne[0], $queue, 'newbie', 'loser', 3);
                }
            } else {
                $this->matchMethod($newbieQueue, $newbieQueue, $queue, 'newbie', 'newbie', 2);
            }
        }
        echo PHP_EOL;
        if(!$Only_Newbie_Cross_battle) {//不是只有新进盟的
            //case 胜者组
            log4task('匹配胜者组');
            if(isset($matchedId) && !empty($matchedId)) {//排除被新进盟匹配的败者组盟
                $excludeGuildIdSql = "AND id <> ".$matchedId;
            } else {
                $excludeGuildIdSql = '';
            }
            $sql2        = "SELECT {$fields} FROM cross_guild_info WHERE latest_battle_is_win=1 AND `status`=1 {$excludeGuildIdSql} order by match_score asc;";
            $winnerQueue = $CrossGuildInfo->sqlGet($sql2);
            //两头相应匹配
            while(count($winnerQueue)>0) {
                log4task('=', false);
                if (count($winnerQueue) == 1) {//当前组 余一人
                    log4task('胜者组有轮空,进入等待败者组轮空匹配 ');
                    $Winner_Queue_Status_Waiting = true;
                    $queueAlone[] = array_pop($winnerQueue);
                    $aloneLogTxt  = '胜者组轮空';
                } else {
                    $this->matchMethod($winnerQueue, $winnerQueue, $queue, 'winner', 'winner', 2);
                }
            }
            echo PHP_EOL;
            //case 败者组
            log4task('败者组匹配');
            $sql3       = "SELECT {$fields} FROM cross_guild_info WHERE latest_battle_is_win=0 AND `status`=1 {$excludeGuildIdSql} order by match_score asc;";
            $loserQueue = $CrossGuildInfo->sqlGet($sql3);
            //两头相应匹配
            while(count($loserQueue)>0) {
                log4task('=', false);
                if (count($loserQueue) == 1) {//当前组 余一人
                    log4task('败者组有轮空，开始匹配胜者组轮空');
                    //查看胜者组是否有一个Status_Waiting
                    if ($Winner_Queue_Status_Waiting) {//匹配之
                        $this->matchMethod($queueAlone, $loserQueue, $queue, 'winner', 'loser', 2);
                    } else {//直接进轮空
                        log4task('胜者组没有轮空');
                        $queueAlone[] = array_pop($loserQueue);
                        $aloneLogTxt  = '败者组轮空';
                    }
                } else {
                    $this->matchMethod($loserQueue, $loserQueue, $queue, 'loser', 'loser', 2);
                }
            }
        }
        echo PHP_EOL;
        log4task('匹配完成');
        log4task('两两匹配数据结果如下：');
        log4task(json_encode($queue));
        (new CrossCommonLog)->add(0, 0, 0, "本次参赛[cross_round.id={$currentRoundId}]所有两两对战联盟信息（不含轮空）".json_encode($queue));
//        dump($queue, 1);exit;
        //最后匹配检查
        if(!empty($queueAlone)) {//若有轮空者
            log4task('最终轮空者数据结果如下：');
            log4task(json_encode($queueAlone));
            (new CrossCommonLog)->add(0, 0, 0, "本次参赛[cross_round.id={$currentRoundId}] 轮空[{$aloneLogTxt}]联盟信息".json_encode($queueAlone));
            log4task('最终轮空者，先入表，对手为0');
            $alone         = array_pop($queueAlone);
            $idQueue[]     = $alone['id'];
            $aloneBattleId = $CrossBattle->add([
                    'round_id'            => $currentRoundId,
                    'guild_1_id'          => $alone['guild_id'],
                    'guild_2_id'          => 0,
                    'type'                => CrossMapConfig::getMapType(),
                    'start_time'          => $this->startTime
                ]);
            $this->initCrossData($aloneBattleId, $alone['guild_id']);
            //轮空不需要生成地图数据了
        }
        //数据插入
        log4task('两两匹配的数据依次开始初始化数据');
        if(!empty($queue)) {
            $queueFix = SplFixedArray::fromArray($queue);
            unset($queue);
            while($queueFix->valid()) {
                $this->queueInit($currentRoundId, ['queue'=>$queueFix->current()]);
                $queueFix->next();
            }
        }
//        CrossMatchResult::find(["status=1"])->delete();
        log4task('数据初始化完成！');
        //更改匹配状态
        if(!$CrossRound->alterCurrent(['status'=>CrossRound::Status_match_end], ['status'=>CrossRound::Status_match_start])) {
            log4task('匹配失败!更改pk_round状态失败,line='.__LINE__);
        }
        $subTimeExec = microtime_float() - $startTimeExec;
        log4task('脚本结束！耗时：'.$subTimeExec);

    }

    /**
     * 脚本运行
     *
     * @param $currentRoundId
     * @param $queueData
     */
    public function queueInit($currentRoundId, $queueData){
        echo "\n------B\n";
        $queue     = $queueData['queue'];
        $guildId1  = $queue[1]['guild_id'];
        $guildId2  = $queue[2]['guild_id'];
        $idQueue[] = $queue[1]['id'];
        $idQueue[] = $queue[2]['id'];

        if(CrossBattle::findFirst("round_id={$currentRoundId} and guild_1_id={$guildId1} and guild_2_id={$guildId2}")) {
            log4task('当前组数据已经生成cross_battle!!!!!!!!!!!!!!!!!!!');
            return;
        }
        //cross_battle
        $data['round_id']            = $currentRoundId;
        $data['guild_1_id']          = $guildId1;
        $data['guild_2_id']          = $guildId2;
        $data['type']                = CrossMapConfig::getMapType();
        $data['start_time']          = $this->startTime;
        $battleId                    = $this->CrossBattle->add($data);
        log4task("--------round_id={$currentRoundId}, battleId={$battleId}开始");
        log4task("生成地图");
        $this->CrossMap->initBattleMap($battleId);//生成地图
        log4task("生成guildId={$guildId1}的相关数据");
        $this->initCrossData($battleId, $guildId1);
        log4task("生成guildId={$guildId2}的相关数据");
        $this->initCrossData($battleId, $guildId2);
        log4task("--------round_id={$currentRoundId}, battleId={$battleId}结束");

        echo "\n------E\n";
//        (new CrossPlayerProjectQueue)->updateAll(['status'=>2, 'update_time'=>qd(), 'battle'=>0, 'rowversion'=>'rowversion*1+1'], ['id'=>$ppq['id'], 'status'=>1]);
    }
    /**
     * 更新比赛为上半场准备
     * 执行时间: 20:00
     * 
     * @return <type>
     */
	public function match1Action($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_crossmatch1";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CrossBattle = new CrossBattle;
		if(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			(new CrossRound)->updateAll(['status'=>CrossRound::Status_award], ['status'=>CrossRound::Status_match_end]);
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$WarfareServiceConfig = new WarfareServiceConfig;
		$readyTime = $WarfareServiceConfig->dicGetOne('ready_time');
		
		$db = $this->di['db_cross_server'];
		//while($ret = $CrossBattle->findFirst(['status='.CrossBattle::STATUS_READY])){
		while($ret = $CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status='.CrossBattle::STATUS_READY)){
			$ret = $ret[0];
			$battleId = $ret['id'];
			if(!$ret['guild_2_id']){
				$CrossBattle->updateAll(['status'=>CrossBattle::STATUS_DEFEND_CLAC, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_READY]);
				(new CrossCommonLog)->add($battleId, 0, 0, '轮空');
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']轮空'.PHP_EOL;
				continue;
			}
			//begin
			dbBegin($db);
			
			$this->updateGuildBuff($ret);
			
			//更新血量
			$this->updateElementDurability($ret, 1);
			
			if($CrossBattle->updateAll(['real_start_time'=>"'".date('Y-m-d H:i:s', time()+$readyTime*60)."'", 'status'=>CrossBattle::STATUS_ATTACK_READY, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_READY])){
				(new CrossCommonLog)->add($battleId, 0, 0, '上半场开始准备');
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']比赛进入上半场准备阶段'.PHP_EOL;
			}
			
			//commit
			dbCommit($db);
			
			Cache::db('cache', 'Cross')->flushDB();
		}
		(new CrossRound)->updateAll(['status'=>CrossRound::Status_battle], ['status'=>CrossRound::Status_match_end]);
	}
	
	/**
     * 更新比赛为上半场开战
     * 执行时间: 20:03
     * 
     * @return <type>
     */
	public function match2Action($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_crossmatch2";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CrossBattle = new CrossBattle;
		if(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		while(true){
			//$ret = $CrossBattle->findFirst(['status='.CrossBattle::STATUS_ATTACK_READY.' and real_start_time<="'.date('Y-m-d H:i:s').'"']);
			$ret = $CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status='.CrossBattle::STATUS_ATTACK_READY.' and real_start_time<="'.date('Y-m-d H:i:s').'"');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				if($CrossBattle->updateAll(['status'=>CrossBattle::STATUS_ATTACK, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_ATTACK_READY])){
					(new CrossCommonLog)->add($battleId, 0, 0, '上半场开战');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']比赛上半场开战'.PHP_EOL;
				}
			//}elseif(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_ATTACK])){
			}elseif(!$CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status<'.CrossBattle::STATUS_ATTACK)){
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 更新比赛为上半场结束
     * 执行时间: 20:18
     * 
     * @return <type>
     */
	public function match3Action($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_crossmatch3";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CrossBattle = new CrossBattle;
		if(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$fightTime = (new WarfareServiceConfig)->dicGetOne('fight_time');
		while(true){
			//$ret = $CrossBattle->findFirst(['status='.CrossBattle::STATUS_ATTACK.' and real_start_time<="'.date('Y-m-d H:i:s', time() - $fightTime*60).'"']);
			$ret = $CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status='.CrossBattle::STATUS_ATTACK.' and real_start_time<="'.date('Y-m-d H:i:s', time() - $fightTime*60).'"');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				if($CrossBattle->updateAll(['status'=>CrossBattle::STATUS_ATTACK_CLAC, 'guild_1_time'=>$fightTime*60, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_ATTACK])){
					(new CrossCommonLog)->add($battleId, 0, 0, '上半场结束[未攻破大本营]');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']比赛上半场结束'.PHP_EOL;
				}
			//}elseif(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_ATTACK_CLAC])){
			}elseif(!$CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status<'.CrossBattle::STATUS_ATTACK_CLAC)){
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 中场
     * 执行时间: 20:00-20:30
     * 
     * @return <type>
     */
	public function match4Action($param=[]){
		$taskId = $param[0];
		$processName = "php_task_crossmatch4";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CrossBattle = new CrossBattle;
		if(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$db = $this->di['db_cross_server'];
		$readyTime = (new WarfareServiceConfig)->dicGetOne('ready_time');
		
		$CrossPlayerProjectQueue = new CrossPlayerProjectQueue;
		$CrossMap = new CrossMap;
		
		while(true){
			//$ret = $CrossBattle->findFirst(['status='.CrossBattle::STATUS_ATTACK_CLAC]);
			$ret = $CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status='.CrossBattle::STATUS_ATTACK_CLAC);
			if($ret){
				sleep(2);//防止比赛结束和其他脚本操作并发
				Cache::db('cache', 'Cross')->flushDB();
				$ret = $ret[0];
				$battleId = $ret['id'];
				//begin
				dbBegin($db);
				
				//结束所有queue
				$CrossPlayerProjectQueue->updateAll(['status'=>3, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['battle_id'=>$battleId, 'status'=>1]);
				
				//结算积分todo
				
				//重置玩家士兵
				$CrossPlayer = new CrossPlayer;
				$CrossPlayer->battleId = $battleId;
				$CrossPlayerSoldier = new CrossPlayerSoldier;
				$CrossPlayerSoldier->battleId = $battleId;
				$CrossPlayerArmy = new CrossPlayerArmy;
				$CrossPlayerArmy->battleId = $battleId;
				$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
				$CrossPlayerArmyUnit->battleId = $battleId;
				$CrossPlayerGeneral = new CrossPlayerGeneral;
				$CrossPlayerGeneral->battleId = $battleId;
				$CrossPlayerMasterskill = new CrossPlayerMasterskill;
				$CrossPlayerMasterskill->battleId = $battleId;
				$soldierNum = (new WarfareServiceConfig)->dicGetOne('wf_soldier_count_start');
				$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
				//$playerIds = Set::extract('/player_id', $CrossPlayer->find(['battle_id='.$battleId.' and status>0'])->toArray());
				$playerIds = Set::extract('/player_id', $CrossPlayer->sqlGet('select * from '.$CrossPlayer->getSource().' where battle_id='.$battleId.' and status>0'));
				foreach($playerIds as $_playerId){
					$CrossPlayer->alter($_playerId, [
						'wall_durability'=>'wall_durability_max', 
						'prev_x'=>0, 
						'prev_y'=>0, 
						'is_dead'=>0, 
						'dead_time'=>"'0000-00-00 00:00:00'", 
						'dead_times'=>0, 
						'change_location_time'=>"'0000-00-00 00:00:00'", 
						'continue_kill'=>0, 
						'debuff_queuetime'=>0, 
						'skill_first_recover'=>0,
						'update_time'=>"'".date('Y-m-d H:i:s')."'", 
						'rowversion'=>"'".uniqid()."'"]);
					//$CrossPlayerSoldier->find(['battle_id='.$battleId.' and player_id='.$_playerId])->delete();
					$CrossPlayerSoldier->sqlExec('delete from '.$CrossPlayerSoldier->getSource().' where battle_id='.$battleId.' and player_id='.$_playerId);
					$CrossPlayerSoldier->clearDataCache($_playerId);
					$CrossPlayerSoldier->updateSoldierNum($_playerId, $soldierIdForall, $soldierNum);
					$CrossPlayerArmy->updateAll(['status'=>0, 'update_time'=>"'".date('Y-m-d H:i:s')."'", 'rowversion'=>"'".uniqid()."'"], ['battle_id'=>$battleId, 'player_id'=>$_playerId]);
					$CrossPlayerArmy->clearDataCache($_playerId);
					$CrossPlayerArmyUnit->updateAll(['soldier_id'=>0, 'soldier_num'=>0, 'update_time'=>"'".date('Y-m-d H:i:s')."'", 'rowversion'=>"'".uniqid()."'"], ['battle_id'=>$battleId, 'player_id'=>$_playerId]);
					$CrossPlayerArmyUnit->_clearDataCache($_playerId);
					$CrossPlayerGeneral->updateAll(['status'=>0, 'update_time'=>"'".date('Y-m-d H:i:s')."'", 'rowversion'=>"'".uniqid()."'"], ['battle_id'=>$battleId, 'player_id'=>$_playerId]);
					$CrossPlayerGeneral->clearDataCache($_playerId);
				}
				$CrossPlayerMasterskill->reset();
				
				//重置地图
				$CrossMap->initBattleMap($battleId);
				
				//更新血量
				$this->updateElementDurability($ret, 2);
				
				//更新状态,下半场开始
				if(!$CrossBattle->updateAll(['attack_area'=>'"1,2"', 'status'=>CrossBattle::STATUS_DEFEND_READY, 'change_time'=>"'".date('Y-m-d H:i:s', time()+$readyTime*60)."'", 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_ATTACK_CLAC])){
					dbRollback($db);
					continue;
				}
				
				(new CrossCommonLog)->add($battleId, 0, 0, '下半场开始准备');
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']下半场开始准备'.PHP_EOL;
				
				//end
				dbCommit($db);
				
				Cache::db('cache', 'Cross')->flushDB();
			//}elseif(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_DEFEND_READY])){
			}elseif(!$CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status<'.CrossBattle::STATUS_DEFEND_READY)){
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 下半场开战
     * 执行时间: 20:00-20:30
     * 
     * @return <type>
     */
	public function match5Action($param=[]){
		$taskId = $param[0];
		$processName = "php_task_crossmatch5";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CrossBattle = new CrossBattle;
		if(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		while(true){
			//$ret = $CrossBattle->findFirst(['status='.CrossBattle::STATUS_DEFEND_READY.' and change_time<="'.date('Y-m-d H:i:s').'"']);
			$ret = $CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status='.CrossBattle::STATUS_DEFEND_READY.' and change_time<="'.date('Y-m-d H:i:s').'"');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				//更新状态
				if($CrossBattle->updateAll(['status'=>CrossBattle::STATUS_DEFEND, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_DEFEND_READY])){
					(new CrossCommonLog)->add($battleId, 0, 0, '下半场开战');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']下半场开战'.PHP_EOL;
				}
			//}elseif(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_DEFEND])){
			}elseif(!$CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status<'.CrossBattle::STATUS_DEFEND)){
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 更新比赛为下半场结束
     * 执行时间: 20:00-21:00
     * 
     * @return <type>
     */
	public function match6Action($param=[]){
		$taskId = $param[0];
		$processName = "php_task_crossmatch6";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CrossBattle = new CrossBattle;
		if(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$fightTime = (new WarfareServiceConfig)->dicGetOne('fight_time');
		while(true){
			//$ret = $CrossBattle->findFirst(['status='.CrossBattle::STATUS_DEFEND.' and change_time<="'.date('Y-m-d H:i:s', time() - $fightTime*60).'"']);
			$ret = $CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status='.CrossBattle::STATUS_DEFEND.' and change_time<=FROM_UNIXTIME(UNIX_TIMESTAMP()-guild_1_time)');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				if($CrossBattle->updateAll(['status'=>CrossBattle::STATUS_DEFEND_CLAC, 'guild_2_time'=>'guild_1_time', 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_DEFEND])){
					(new CrossCommonLog)->add($battleId, 0, 0, '下半场结束[未攻破大本营]');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']比赛下半场结束'.PHP_EOL;
				}
			//}elseif(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_DEFEND_CLAC])){
			}elseif(!$CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status<'.CrossBattle::STATUS_DEFEND_CLAC)){
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 完场
     * 执行时间: 20:00-20:30
     * 
     * @return <type>
     */
	public function match7Action($param=[]){
		$taskId = $param[0];
		$ModelBase         = new ModelBase;
		$processName = "php_task_crossmatch7";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CrossBattle = new CrossBattle;
		if(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$db = $this->di['db_cross_server'];
		//$db2 = $this->di['db'];
		
		$CrossPlayerProjectQueue = new CrossPlayerProjectQueue;
		$CrossPlayer = new CrossPlayer;
		$Player = new Player;
		while(true){
			//$ret = $CrossBattle->findFirst(['status='.CrossBattle::STATUS_DEFEND_CLAC]);
			$ret = $CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status='.CrossBattle::STATUS_DEFEND_CLAC);
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				
				//begin
				dbBegin($db);
				//dbBegin($db2);
				
				//结束所有queue
				$CrossPlayerProjectQueue->updateAll(['status'=>3, 'update_time'=>"'".date('Y-m-d H:i:s')."'", 'rowversion'=>"rowversion+1"], ['battle_id'=>$battleId, 'status'=>1]);
				
				//结算胜者
				if($ret['guild_1_beat'] && !$ret['guild_2_beat']){//1破营
					$win = 1;
				}elseif($ret['guild_2_beat'] && !$ret['guild_1_beat']){//2破营
					$win = 2;
				}elseif($ret['guild_1_beat'] && $ret['guild_2_beat']){//都破营
					if($ret['guild_1_time'] < $ret['guild_2_time']){
						$win = 1;
					}elseif($ret['guild_1_time'] == $ret['guild_2_time']){
						if($ret['guild_1_kill'] >= $ret['guild_2_kill']){
							$win = 1;
						}else{
							$win = 2;
						}
					}else{
						$win = 2;
					}
				}elseif(!$ret['guild_1_beat'] && !$ret['guild_2_beat']){//都未破营
					if($ret['guild_1_kill'] >= $ret['guild_2_kill']){
						$win = 1;
					}else{
						$win = 2;
					}
				}
				
				//更新状态
				if(!$CrossBattle->updateAll(['status'=>CrossBattle::STATUS_FINISH, 'win'=>$win, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CrossBattle::STATUS_DEFEND_CLAC])){
					dbRollback($db);
					//dbRollback($db2);
					continue;
				}
				
				(new CrossGuildInfo)->changeInfo($ret['guild_1_id'], $ret['guild_2_id'], $win);
				
				(new CrossCommonLog)->add($battleId, 0, 0, '比赛结束');
				
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']比赛结束'.PHP_EOL;
				
				//end
				dbCommit($db);
				//dbCommit($db2);
				
				//解除罩子
				//$playerIds = Set::extract('/player_id', $CrossPlayer->find(['battle_id='.$battleId.' and status>0'])->toArray());
				$playerIds = Set::extract('/player_id', $CrossPlayer->sqlGet('select * from '.$CrossPlayer->getSource().' where battle_id='.$battleId.' and status>0'));
				foreach($playerIds as $_playerId){
					$parseInfo = CrossPlayer::parsePlayerId($_playerId);
					//$Player->alter($_playerId, ['is_in_cross'=>0]);
					$ModelBase->execByServer($parseInfo['server_id'], 'Player', 'alter', [$_playerId, ['is_in_cross'=>0]]);
				}
				
			//}elseif(!$CrossBattle->findFirst(['status<'.CrossBattle::STATUS_FINISH])){
			}elseif(!$CrossBattle->sqlGet('select * from '.$CrossBattle->getSource().' where status<'.CrossBattle::STATUS_FINISH)){
				//如果所有都结束，大状态改为比赛结束
				(new CrossRound)->alterCurrent(['status'=>CrossRound::Status_award]);
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	public function updateElementDurability($cb, $j){
		$CrossPlayer = new CrossPlayer;
		$WarfareServiceConfig = new WarfareServiceConfig;
		$CrossMap = new CrossMap;
		
		$battleId = $cb['id'];
		$CrossPlayer->battleId = $battleId;
		//$players = $CrossPlayer->find(['battle_id='.$battleId.' and guild_id='.$cb['guild_'.$j.'_id']])->toArray();
		if($j == 1){
			$a = 1;
			$d = 2;
		}else{
			$a = 2;
			$d = 1;
		}
		$players1 = $CrossPlayer->sqlGet('select * from '.$CrossPlayer->getSource().' where battle_id='.$battleId.' and guild_id='.$cb['guild_'.$a.'_id']);
		$attackLv = 0;
		$attackPlayerIds = [];
		foreach($players1 as $_player){
			$attackLv += $_player['castle_lv'];
			$attackPlayerIds[] = $_player['player_id'];
		}
		$players2 = $CrossPlayer->sqlGet('select * from '.$CrossPlayer->getSource().' where battle_id='.$battleId.' and guild_id='.$cb['guild_'.$d.'_id']);
		$defendLv = 0;
		$defendPlayerIds = [];
		foreach($players2 as $_player){
			$defendLv += $_player['castle_lv'];
			$defendPlayerIds[] = $_player['player_id'];
		}
		
		//更新玩家城墙血量
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayerGeneral->battleId = $battleId;
		if($j == 1){
			echo '更新玩家城墙血量'. PHP_EOL;
			$players = array_merge($players1, $players2);
			foreach($players as $_player){//高墙壁垒
				$buff = json_decode($_player['buff'], true);
				$addhpPercent = @$buff['wall_defense_limit_plus']/DIC_DATA_DIVISOR;
				$addhp = floor($CrossPlayerGeneral->getSkillsByPlayer($_player['player_id'], null, [10050])[10050][0]);
				$CrossPlayer->alter($_player['player_id'], ['wall_durability_max'=>'wall_durability_max*'.(1+$addhpPercent).'+'.$addhp, 'wall_durability'=>'wall_durability_max']);
			}
		}
		
		//固若金汤:增加所有城门及大本营的城防值|<#0,255,0#>%{num}|
		$addbuildhp = floor($CrossPlayerGeneral->getSkillsByPlayers($defendPlayerIds, [10095])[10095][0]);
		
		
		//更新城门血量
		echo '更新城门血量'. PHP_EOL;
		$lv = $defendLv;
		foreach([1, 2, 3] as $i){
			$formula = $WarfareServiceConfig->dicGetOne('wf_gate'.$i.'_hitpoint');
			eval('$door = '.$formula.';');
			$door += $addbuildhp;
			$CrossMap->updateAll(['durability'=>$door, 'max_durability'=>$door], ['battle_id'=>$battleId, 'map_element_id'=>(30200+$i)]);
			//$_map = $CrossMap->findFirst(['battle_id='.$battleId.' and map_element_id='.(30200+$i)]);
			//$CrossMap->clearMapCache($_map->x, $_map->y);
		}
		
		//更新攻城锤血量
		echo '更新攻城锤血量'. PHP_EOL;
		$lv = $attackLv;
		$formula = $WarfareServiceConfig->dicGetOne('wf_warhammer_hitpoint');
		eval('$hammer = '.$formula.';');
		$CrossMap->updateAll(['durability'=>$hammer, 'max_durability'=>$hammer], ['battle_id'=>$battleId, 'map_element_origin_id'=>301]);
		//$_map = $CrossMap->findFirst(['battle_id='.$battleId.' and map_element_origin_id=301']);
		//$CrossMap->clearMapCache($_map->x, $_map->y);
		
		//更新云梯血量
		echo '更新云梯血量'. PHP_EOL;
		
		//云梯耐久:云梯生命增加%
		$addLadderHpBuff = floor($CrossPlayerGeneral->getSkillsByPlayers($attackPlayerIds, [23])[23][0]);
		
		$lv = $attackLv;
		$formula = $WarfareServiceConfig->dicGetOne('wf_ladder_hitpoint');
		eval('$ladder = '.$formula.';');
		$ladder *= 1+$addLadderHpBuff;
		$ladder = floor($ladder);
		$CrossMap->updateAll(['durability'=>$ladder, 'max_durability'=>$ladder], ['battle_id'=>$battleId, 'map_element_origin_id'=>304]);
		//$_map = $CrossMap->findFirst(['battle_id='.$battleId.' and map_element_origin_id=304']);
		//$CrossMap->clearMapCache($_map->x, $_map->y);
		
		//更新大本营血量
		echo '更新大本营血量'. PHP_EOL;
		$lv = $defendLv;
		$formula = $WarfareServiceConfig->dicGetOne('wf_basecastle_hitpoint');
		eval('$base = '.$formula.';');
		$base += $addbuildhp;
		$CrossMap->updateAll(['durability'=>$base, 'max_durability'=>$base], ['battle_id'=>$battleId, 'map_element_origin_id'=>306]);
		//$_map = $CrossMap->findFirst(['battle_id='.$battleId.' and map_element_origin_id=306']);
		//$CrossMap->clearMapCache($_map->x, $_map->y);
	}
	
	public function updateGuildBuff($cb){
		$arr = [
			['id'=>10103, 'attr'=>'intelligence', 'buff'=>'buff_move'],//足智多谋
			['id'=>10106, 'attr'=>'force', 'buff'=>'buff_cityattack'],//力压群雄
			['id'=>11, 'attr'=>'governing', 'buff'=>'buff_buildattack'],//君临天下
			['id'=>12, 'attr'=>'political', 'buff'=>'buff_relocation'],//权术大师
			['id'=>13, 'attr'=>'charm', 'buff'=>'buff_enemyreturn'],//倾国倾城
		];
		//循环双方
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayer = new CrossPlayer;
		$CrossGuild = new CrossGuild;
		$BattleSkill = new BattleSkill;
		$battleId = $CrossGuild->battleId = $CrossPlayerGeneral->battleId = $cb['id'];
		foreach([1, 2] as $_i){
			$arrLimit = [];
			$buff = [];
			$skillIds = [];
			$_guildId = $cb['guild_'.$_i.'_id'];
			$_oppoGuildId = $cb['guild_'.(3-$_i).'_id'];
			
			//循环技能
			foreach($arr as $_ar){
				$_skillId = $_ar['id'];
				$_attr = $_ar['attr'];
				$_buff = $_ar['buff'];
				
				$skillIds[$_buff.'_ids'] = [];
				
				//获取公会成员拥有该被动的武将
				$_generals = $CrossPlayerGeneral->sqlGet('select * from '.$CrossPlayerGeneral->getSource().' where battle_id='.$battleId.' and player_id in (select player_id from '.$CrossPlayer->getSource().' where battle_id='.$battleId.' and guild_id='.$_guildId.') and (cross_skill_id_1='.$_skillId.' or cross_skill_id_2='.$_skillId.' or cross_skill_id_3='.$_skillId.')');
				
				//获取对方公会的武将的该属性最大值
				$_maxAttr = $CrossPlayerGeneral->getMaxAttrByGuild($_oppoGuildId, $_attr)['attr'];
				
				//循环武将
				foreach($_generals as $_g){
					$totalAttr = json_decode($_g['total_attr'], true);
					//判断武将的该属性是否大于对方公会最大值
					if($totalAttr['attr'][$_attr] > $_maxAttr){
						$_value = 0;
						if($_g['cross_skill_id_1'] == $_skillId){
							$_value = $_g['cross_skill_v1_1'];
						}elseif($_g['cross_skill_id_2'] == $_skillId){
							$_value = $_g['cross_skill_v1_2'];
						}elseif($_g['cross_skill_id_3'] == $_skillId){
							$_value = $_g['cross_skill_v1_3'];
						}
						//增加buff
						@$buff[$_buff] += $_value;
						if($_value > 0){
							@$skillIds[$_buff.'_ids'][$_skillId] += $_value;
						}
					}
				}
				if(@$buff[$_buff]){
					$bs = $BattleSkill->dicGetOne($_skillId);
					if($bs['value_max']){
						$arrLimit[$_buff] = $bs['value_max']*1;
					}
					if($bs['num_type'] == 2){
						$buff[$_buff] = floor($buff[$_buff]);
					}
				}
			}
			//写入buff值
			if($buff){
				foreach($skillIds as &$_ids){
					$_ids = "'".json_encode($_ids)."'";
				}
				unset($_ids);
				//上限
				foreach($arrLimit as $_name => $_limit){
					@$buff[$_name] = min($_limit, @$buff[$_name]*1);
				}
				$updateData = array_merge($buff, $skillIds);
				$CrossGuild->alter($_guildId, $updateData);
			}
		}
	}
	
    /**
     * test 充值匹配结果
     * php cli.php cross resetMatch
     */
    public function resetMatchAction(){
        $currentRoundId = (new CrossRound)->getCurrentRoundId();
        if($currentRoundId>0) {
            log4task('当前cross_round.id='.$currentRoundId);
            $CrossBattle = new CrossBattle;
            $battleIds = $CrossBattle->sqlGet("select id from cross_battle where round_id={$currentRoundId}");
            if(!empty($battleIds)) {
                $battleIds = Set::extract('/id', $battleIds);
                $battleIds = implode(",", $battleIds);
                (new CrossMap)->sqlExec("delete from cross_map where battle_id in ({$battleIds})");
                log4task("delete cross_map when battle_id in ({$battleIds})");
            }
            $CrossBattle->sqlExec("delete from cross_battle where round_id={$currentRoundId};");
            log4task('cross_battle reset');
            (new CrossGuild)->sqlExec("delete from cross_guild where round_id={$currentRoundId};");
            log4task('cross_guild reset');
            (new CrossPlayer)->sqlExec("delete from cross_player where round_id={$currentRoundId};");
            log4task('cross_player reset');
            (new CrossRound)->sqlExec("update cross_round set `status`=0 where id={$currentRoundId};");
            log4task('cross_round set status=0');

        }

    }

    /**
     * 匹配结果监听器
     */
/*    public function matchResultListenerAction($params){
        set_time_limit(0);
//        swoole_process::daemon(true);
        $n   = $params[0];
        log4task("共{$n}条数据");
        $n   = ceil($n/self::Offset);
        log4task("每".self::Offset."一组丢到子进程执行，共有{$n}个子进程将会生成执行。");
        //生成子进程
        for($i=0; $i<$n; $i++) {
            $callback = [new self, 'doMatchResult'];
            $process  = new swoole_process($callback, false, true);
            $process->write($i);
            $pid = $process->start();
            echo PHP_EOL;
            log4task("pid={$pid}的进程被创建-数据拷贝");
        }
        //回收子进程
        for($i=0; $i<$n; $i++) {
            $p = swoole_process::wait();
            echo PHP_EOL;
            log4task("pid={$p['pid']}进程被回收");
        }
        unset($process);
    }*/

    /**
     * 子进程具体处理拷贝数据相关逻辑
     *
     * @param $worker
     */
/*    public function doMatchResult($worker){
        $name = "_sub_sub_cross_task_match_result";
        $worker->name($name);
        $i = (int)$worker->read();
        $re = CrossMatchResult::find(["order"=>"id asc", "limit"=>[self::Offset, $i*self::Offset]])->toArray();
        if($re) {
            foreach($re as $r) {
                if($r['status']==0) {
                    $this->queueInit($r['round_id'], json_decode($r['info'], true));
                    (new CrossMatchResult)->updateAll(['status' => 1], ['id' => $r['id'], 'status' => 0]);
                }
            }
        }
        $worker->exit(0);
    }*/

    /**
     * 奖励结算
     *
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php cross reward
     */
    public function rewardAction(){
        set_time_limit(0);
        $startTimeExec = microtime_float();
        //class init
        $CrossRound           = new CrossRound;
        $CrossBattle          = new CrossBattle;

        //更改匹配状态
        $currentRoundId = $CrossRound->getCurrentRoundId();
        if(!$currentRoundId) log4task('当前无跨服战');
        $currentRound = $CrossRound->current;
        if($currentRound['status']!=CrossRound::Status_award) {
            log4task('当前不在发奖状态，当前cross_round.status='.$currentRound['status']);
            exit;
        }
        if(!$CrossRound->alterCurrent(['status'=>-1*CrossRound::Status_award], ['status'=>CrossRound::Status_award])) {
            log4task('匹配失败!更改pk_round状态失败,line='.__LINE__);
            exit;
        }
        $WarfareServiceConfig = new WarfareServiceConfig;
        $PlayerMail           = new PlayerMail;
        $wfWinnerReward       = $WarfareServiceConfig->getValueByKey('wf_winner_reward');
        $wfGuildWinnerReward  = $WarfareServiceConfig->getValueByKey('wf_guild_winner_reward');
        $wfLoserReward        = $WarfareServiceConfig->getValueByKey('wf_loser_reward');
        $wfGuildLoserReward   = $WarfareServiceConfig->getValueByKey('wf_guild_loser_reward');
        $this->rewardItemArr  = [
            'win' => [//胜
                'joinedItem' => $PlayerMail->newItemByDrop(0, [$wfWinnerReward]),
                'normalItem' => $PlayerMail->newItemByDrop(0, [$wfGuildWinnerReward]),
            ],
            'lose' => [//败
                'joinedItem' => $PlayerMail->newItemByDrop(0, [$wfLoserReward]),
                'normalItem' => $PlayerMail->newItemByDrop(0, [$wfGuildLoserReward]),
            ]
        ];
        $sql1 = "select id, guild_1_id, guild_2_id, win from cross_battle where round_id={$currentRoundId}";
        $all = $CrossBattle->sqlGet($sql1);
        if(!empty($all)) {
            $all = SplFixedArray::fromArray($all);
            while($all->valid()) {
                $cuBattle = $all->current();
                log4task('battle_id='.$cuBattle['id'].'开始发奖');
                if($cuBattle['guild_1_id']) {
                    log4task('联盟1：'.$cuBattle['guild_1_id']);
                    $this->doReward($cuBattle['guild_1_id'], ($cuBattle['win'] == 1));
                }
                if($cuBattle['guild_2_id']) {
                    log4task('联盟2：'.$cuBattle['guild_2_id']);
                    $this->doReward($cuBattle['guild_2_id'], ($cuBattle['win'] == 2));
                } else {
                    log4task('轮空');
                }
                if($CrossBattle->updateAll(['reward_flag'=>1], ['id'=>$cuBattle['id'], 'reward_flag'=>0])) {
                    log4task('battle_id=' . $cuBattle['id'] . '结束发奖');
                    $all->next();
                } else {
                    log4task('发奖异常！！！！！！！！！！！');
                    exit;
                }
            }
        }
        log4task('整轮发奖完成！');
        //更改匹配状态
        if(!$CrossRound->alterCurrent(['status'=>CrossRound::Status_battle_end], ['status'=>-1*CrossRound::Status_award])) {
            log4task('匹配失败!更改pk_round状态失败,line='.__LINE__);
        }
        $subTimeExec = microtime_float() - $startTimeExec;
        log4task('脚本结束！耗时：'.$subTimeExec);
    }

    /**
     * rewarding method
     *
     * @param     $guildId
     * @param int $win
     */
    public function doReward($guildId, $isWin=false){
        $ModelBase         = new ModelBase;
        $key               = $isWin ? 'win' : 'lose';
        $joinedItem        = $this->rewardItemArr[$key]['joinedItem'];
        $normalItem        = $this->rewardItemArr[$key]['normalItem'];
        $parsedGuildIdInfo = CrossPlayer::parseGuildId($guildId);
        $guildMember       = $ModelBase->getByServer($parsedGuildIdInfo['server_id'], 'PlayerGuild', 'getAllGuildMember', [$parsedGuildIdInfo['guild_id'], false]);
        if(!empty($guildMember)) {
            $playerIdsJoined = [];
            $playerIdsAll    = [];
            foreach($guildMember as $gm) {
                $playerIdsAll[] = $gm['player_id'];
                if($gm['cross_joined_flag']==1) {
                    $playerIdsJoined[] = $gm['player_id'];
                }
            }
            $isWinFlag = $isWin ? 1 : 0;
            //发放参与奖励
            $ModelBase->execByServer($parsedGuildIdInfo['server_id'], 'PlayerMail', 'sendSystem', [$playerIdsJoined, PlayerMail::TYPE_CROSS_AWARD_JOINED, '', '', 0, ['is_win' =>$isWinFlag], $joinedItem, '跨服战参与奖励']);
            //发放大锅饭
            $ModelBase->execByServer($parsedGuildIdInfo['server_id'], 'PlayerMail', 'sendSystem', [$playerIdsAll, PlayerMail::TYPE_CROSS_AWARD_NOTJOINED, '', '', 0, ['is_win' =>$isWinFlag], $normalItem, '跨服战大锅饭奖励']);
        }
    }

    /**
     * reset脚本 测试用
     */
    public function resetRewardAction(){
        $currentRoundId = (new CrossRound)->getCurrentRoundId();
        if($currentRoundId) {
            (new CrossRound)->sqlExec("update cross_round set `status`=4 where id={$currentRoundId};");
            log4task('cross_round set status=4');
            (new CrossRound)->sqlExec("update cross_battle set `reward_flag`=0 where round_id={$currentRoundId};");
            log4task('cross_battle set reward_flag=0');
        }
    }

	public function monitorAction(){
		$processName = "php_task_cross_monitor";
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$arrPs = [];
		while(true){
			$ps = shell_exec('ps -ef|grep php_swoole_crossdispatcher_task_');
			$ps = explode("\n", $ps);
			$thisPs = [];
			foreach($ps as $_ps){
				$_ps = trim($_ps);
				if(preg_match("/task_([a-zA-Z]*)_([0-9]*)_([0-9]*)$/", $_ps, $_match)){
					$type = $_match[1];
					$p1 = $_match[2];
					$p2 = $_match[3];
					$key = $type.'_'.$p1.'_'.$p2;
					$thisPs[$key] = time();
					if(isset($arrPs[$key])){
						if(time() - $arrPs[$key] > 10){
							$_ps = preg_replace('/\s+/', ' ', $_ps);
							$pid = explode(" ", $_ps)[1];
							echo '[0][beginTime='.$arrPs[$key].']'.$_ps.PHP_EOL;
							echo '[1]kill -15 '.$pid. PHP_EOL;
							shell_exec('kill -15 '.$pid);
							$this->restartParent($ps, $type);
							unset($arrPs[$key]);
							unset($thisPs[$key]);
							break;
						}
					}else{
						$arrPs[$key] = time();
					}
				}
			}
			$arrPs = array_intersect_key($arrPs, $thisPs);
			sleep(5);
		}
	}
	
	public function restartParent($ps, $type){
		global $config;
		foreach($ps as $_ps){
			$_ps = trim($_ps);
			if(preg_match("/task_".$type."_father_([0-9]*)$/", $_ps, $_match)){
				echo '[2]'.$_ps.PHP_EOL;
				//$p1 = $_match[1];
				$_ps = preg_replace('/\s+/', ' ', $_ps);
				$pid = explode(" ", $_ps)[1];
				//$key = $type.'_'.$p1;
				echo '[3]kill -15 '.$pid. PHP_EOL;
				shell_exec('kill -15 '.$pid);
				echo '[4]'.$config['daemonCrossDispPath'].' '.$type.PHP_EOL;
				shell_exec($config['daemonCrossDispPath'].' '.$type);
				//sleep(1);
				break;
			}
		}
	}
}