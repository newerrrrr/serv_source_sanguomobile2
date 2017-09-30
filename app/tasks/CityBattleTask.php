<?php
/**
 * 城战
 */
class CityBattleTask extends \Phalcon\CLI\Task
{
    private $ModelBase                   = null;
    private $CityBattlePlayer            = null;
    private $CityBattlePlayerArmy        = null;
    private $CityBattlePlayerArmyUnit    = null;
    private $CityBattlePlayerGeneral     = null;
    private $CityBattlePlayerMasterskill = null;

    public function newRoundAction(){
        $CityBattleRound = new CityBattleRound;
        $CityBattle = new CityBattle;
        $CityBattleMap = new CityBattleMap;
        $CityBattleCamp = new CityBattleCamp;
        $cbr = $CityBattleRound->getCurrentRoundInfo();
        if(empty($cbr) || $cbr['status']==CityBattleRound::FINISH){
            $roundId = $CityBattleRound->addNew();
            $CityBattle->createCityBattle($roundId);
            $cbList = $CityBattle->getRoundBattleList($roundId);
            foreach ($cbList as $cb){
                $battleId = $cb['id'];
                $CityBattleMap->initBattleMap($battleId);
                $CityBattleCamp->battleId = $battleId;
                for($campId=1;$campId<4;$campId++){
                    $CityBattleCamp->add($campId);
                }
            }
        }else{
            exit;
        }
    }

    public function sendTokenMailAction(){
        //发邮件给令箭：
        $Player = new Player;
        $playerList = $Player->find(["total_rmb >=5000"])->toArray();
        $PlayerMail = new PlayerMail;
        $CountryBasicSetting = new CountryBasicSetting;
        $itemId = $CountryBasicSetting->getValueByKey("vip_sign_up_condition_item");
        $item = $PlayerMail->newItem(1, $itemId, 1);
        $type        = PlayerMail::TYPE_CB_TOKEN;
        $title       = 'token email';
        $msg         = '';
        $time        = 0;

        foreach ($playerList as $v){
            $playerId = $v['id'];
            $PlayerMail->sendSystem($playerId, $type, $title, $msg, $time, [], $item, []);
        }

    }
	
	/**
     * 科技自动增长脚本【每小时】
     * 
     * @param <type> $param 
     * 
     * @return <type>
     */
	public function scienceIncreaceAction($param=[]){
		//读取周
		$week = (new CityBattleRound)->getCurrentWeek();
		if(!$week){
			echo "周数不符合";
			exit;
		}
		
		//获取增量
		$CountryScienceExp = new CountryScienceExp;
		$countryScienceExp = $CountryScienceExp->dicGetOne($week);
		$exp = $countryScienceExp['autoexp_per_hour'];
		
		//读取所有科技
		$CountryScience = new CountryScience;
		$cs = $CountryScience->sqlGet('select distinct science_type,max_level from '.$CountryScience->getSource().' where science_type not in (21, 22)');
		
		$campIds = (new CountryCampList)->dicGetAllId();
		$CityBattleScience = new CityBattleScience;
		$CountryScience = new CountryScience;
		//循环阵营
		foreach($campIds as $_campid){
			//循环科技
			foreach($cs as $_cs){
				$_type = $_cs['science_type'];
				$_maxlevel = $_cs['max_level'];
				
				//锁定
				$lockKey = __CLASS__ . ':' . __METHOD__ . ':campId=' .$_campid.':scienceType='.$_type;
				Cache::lock($lockKey);
				
				//开始
				$db = $this->di['db_citybattle_server'];
				dbBegin($db);
				
				try {
					$try = 0;
					retry:
					$try++;
					if($try > 3){
						goto r;
					}
					
					//获取当前
					$cityBattleScience = $CityBattleScience->getForUpdate($_campid, $_type);
					if(!$cityBattleScience){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					//检查是否master
					if($cityBattleScience['science_level'] >= $_maxlevel){
						goto r;
					}
			
					//增加研究值
					if(!$CityBattleScience->assign($cityBattleScience)->addExp($exp)){
						goto retry;
					}
					
					//提交
					dbCommit($db);
				} catch (Exception $e) {
					list($err, $msg) = parseException($e);
					echo $err.":".$msg;
					r:
					dbRollback($db);
				}
				
				//解锁
				Cache::unlock($lockKey);
			}
			
		}
		echo 'finish';
	}
	
    /**
     * 赛前准备
     * 
     * @param <type> $param 
     * 
     * @return <type>
     */
	public function match0(){
		$db = $this->di['db_citybattle_server'];
		
		$CityBattle = new CityBattle;
		$battleIds = $CityBattle->getCurrentBattleIdList();
		$CityBattleMap = new CityBattleMap;
		foreach($battleIds as $battleId){
			$cb = $CityBattle->getBattle($battleId);
			//begin
			dbBegin($db);
			
			$this->updateCampBuff($cb);
			
			//更新血量
			$this->updateElementDurability($cb);
			
			//更新城门战map=1
			$CityBattleMap->updateAll(['status'=>1], ['battle_id'=>$cb['id'], 'part'=>1]);
			
			//commit
			dbCommit($db);
		}
		
		Cache::db('cache', 'CityBattle')->flushDB();
		return true;
	}
	
	public function updateElementDurability($cb){
		$CityBattlePlayer = new CityBattlePlayer;
		$CountryBasicSetting = new CountryBasicSetting;
		$CityBattleMap = new CityBattleMap;
		
		$battleId = $cb['id'];
		$CityBattlePlayer->battleId = $battleId;
		//$players = $CrossPlayer->find(['battle_id='.$battleId.' and guild_id='.$cb['guild_'.$j.'_id']])->toArray();
		$camps = (new CountryCampList)->dicGetAllId();
		$a = array_diff($camps, [$cb['camp_id']]);
		$d = $cb['camp_id'];

		$players = [];
		$powerByCamp = [0=>0];//$castleLvs = [0=>0];
		$powerByPlayer = [];
		$playerIds = [0=>[]];
		foreach($camps as $_campId){
			$players[$_campId] = $CityBattlePlayer->sqlGet('select * from '.$CityBattlePlayer->getSource().' where battle_id='.$battleId.' and camp_id='.$_campId);
			if(!$players[$_campId]){
				@$powerByCamp[$_campId] = 0;
				@$playerIds[$_campId] = [];
			}
			foreach($players[$_campId] as $_player){
				$powerByPlayer[$_player['player_id']] = (new QueueCityBattle)->getArmyPower($battleId, $_player['player_id']);
				@$powerByCamp[$_campId] += $powerByPlayer[$_player['player_id']];
				@$playerIds[$_campId] [] = $_player['player_id'];
			}
			
		}
		
		//更新玩家城墙血量
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$CityBattlePlayerGeneral->battleId = $battleId;
		echo '更新玩家城墙血量'. PHP_EOL;
		$formula = (new CountryBasicSetting)->dicGetOne('wf_playercastle_hitpoint');
		//$players = array_merge($players1, $players2);
		foreach($players as $_campId => $_players){//高墙壁垒
			//城战科技：联盟城防：增加城池和城门的生命值|<#72,255,164#>%{num}%%|
			$buff503 = (new CityBattleBuff)->getCampBuff($_campId, 503);
			foreach($_players as $_player){
				$buff = json_decode($_player['buff'], true);
				$addhpPercent = @$buff['wall_defense_limit_plus']/DIC_DATA_DIVISOR + $buff503;
				$addhp = floor($CityBattlePlayerGeneral->getSkillsByPlayer($_player['player_id'], null, [10050])[10050][0]);
				$power = $powerByPlayer[$_player['player_id']];
				eval('$wall = '.$formula.';');
				$wall = $wall * (1+$addhpPercent) + $addhp;
				$CityBattlePlayer->alter($_player['player_id'], ['wall_durability_max'=>$wall, 'wall_durability'=>'wall_durability_max']);
			}
		}
		
		
		
		//更新城门血量
		echo '更新城门血量'. PHP_EOL;
		//$lv = $castleLvs[$d];
		//foreach([1, 2, 3] as $i){
		$power = $powerByCamp[$d];
		if($power){
			$formula = $CountryBasicSetting->dicGetOne('wf_gate1_hitpoint');
		}else{
			$formula = $CountryBasicSetting->dicGetOne('wf_gate2_hitpoint');
		}
		eval('$door = '.$formula.';');
		$addhpPercent = 0;
		$addbuildhp = 0;
		if($d){
			//城战科技：联盟城防：增加城池和城门的生命值|<#72,255,164#>%{num}%%|
			$addhpPercent += (new CityBattleBuff)->getCampBuff($d, 503);
			
			//固若金汤:增加所有城门及大本营的城防值|<#0,255,0#>%{num}|
			$addbuildhp = floor($CityBattlePlayerGeneral->getSkillsByPlayers($playerIds[$d], [10095])[10095][0]);
		}
		$door *= (1+$addhpPercent);
		$door += $addbuildhp;
		$CityBattleMap->updateAll(['durability'=>$door, 'max_durability'=>$door], ['battle_id'=>$battleId, 'map_element_origin_id'=>401]);
		//}
		
		//更新攻城锤血量
		echo '更新攻城锤血量'. PHP_EOL;
		foreach($a as $_campId){
			$power = $powerByCamp[$_campId];
			//$lv = $castleLvs[$_campId];
			$formula = $CountryBasicSetting->dicGetOne('wf_warhammer_hitpoint');
			eval('$hammer = '.$formula.';');
			$CityBattleMap->updateAll(['durability'=>$hammer, 'max_durability'=>$hammer], ['battle_id'=>$battleId, 'area'=>$_campId, 'map_element_origin_id'=>402]);
		}
		
		//更新云梯血量
		echo '更新云梯血量'. PHP_EOL;
		foreach($a as $_campId){
			//云梯耐久:云梯生命增加%
			$addLadderHpBuff = floor($CityBattlePlayerGeneral->getSkillsByPlayers($playerIds[$_campId], [23])[23][0]);
			
			$buff505 = (new CityBattleBuff)->getCampBuff($_campId, 505);
			
			//$lv = $castleLvs[$_campId];
			$power = $powerByCamp[$_campId];
			$formula = $CountryBasicSetting->dicGetOne('wf_ladder_hitpoint');
			eval('$ladder = '.$formula.';');
			$ladder *= 1+$addLadderHpBuff+$buff505;
			$ladder = floor($ladder);
			$CityBattleMap->updateAll(['durability'=>$ladder, 'max_durability'=>$ladder], ['battle_id'=>$battleId, 'area'=>$_campId, 'map_element_origin_id'=>403]);
		}
	}
		
	public function updateCampBuff($cb){
		$arr = [
			['id'=>10103, 'attr'=>'intelligence', 'buff'=>'buff_move'],//足智多谋
			['id'=>10106, 'attr'=>'force', 'buff'=>'buff_cityattack'],//力压群雄
			['id'=>11, 'attr'=>'governing', 'buff'=>'buff_buildattack'],//君临天下
			['id'=>12, 'attr'=>'political', 'buff'=>'buff_relocation'],//权术大师
			['id'=>13, 'attr'=>'charm', 'buff'=>'buff_enemyreturn'],//倾国倾城
		];
		//循环双方
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$CityBattleCamp = new CityBattleCamp;
		$CityBattlePlayer = new CityBattlePlayer;
		$BattleSkill = new BattleSkill;
		$battleId = $CityBattleCamp->battleId = $CityBattlePlayerGeneral->battleId = $cb['id'];
		$campList = (new CountryCampList)->dicGetAllId();
		foreach($campList as $_i){
			$arrLimit = [];
			$buff = [];
			$skillIds = [];
			$_campId = $_i;
			$_oppoCampIds = array_diff($campList, [$_campId]);
			
			$oppoMaxAttrs = $CityBattlePlayerGeneral->getMaxAttrsByCamps($_oppoCampIds);
			
			//循环技能
			foreach($arr as $_ar){
				$_skillId = $_ar['id'];
				$_attr = $_ar['attr'];
				$_buff = $_ar['buff'];
				
				$skillIds[$_buff.'_ids'] = [];
				
				//获取阵营成员拥有该被动的武将
				$_generals = $CityBattlePlayerGeneral->sqlGet('select * from '.$CityBattlePlayerGeneral->getSource().' where battle_id='.$battleId.' and player_id in (select player_id from '.$CityBattlePlayer->getSource().' where battle_id='.$battleId.' and camp_id='.$_campId.') and (cross_skill_id_1='.$_skillId.' or cross_skill_id_2='.$_skillId.' or cross_skill_id_3='.$_skillId.')');
								
				//循环武将
				foreach($_generals as $_g){
					$totalAttr = json_decode($_g['total_attr'], true);
					//判断武将的该属性是否大于对方公会最大值
					if($totalAttr['attr'][$_attr] > $oppoMaxAttrs[$_attr]){
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
				$CityBattleCamp->alter($_campId, $updateData);
			}
		}
	}

	/**
     * 更新比赛为城门战准备
     * 执行时间: 20:00
     * 
     * @return <type>
     */
	public function match1Action($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_citybattlematch1";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CityBattle = new CityBattle;
		if(!$CityBattle->findFirst(['status<'.CityBattle::STATUS_FINISH])){
			(new CityBattleRound)->alterCurrent(['status'=>CityBattleRound::CLAC_REWARD], ['status'=>CityBattleRound::SELECT_PLAYER_FINISH]);
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		(new CityBattleRound)->alterCurrent(['status'=>CityBattleRound::DOING], ['status'=>CityBattleRound::SELECT_PLAYER_FINISH]);
		
		$CountryBasicSetting = new CountryBasicSetting;
		$readyTime = $CountryBasicSetting->dicGetOne('match_gate_ready');
		
		$db = $this->di['db_citybattle_server'];
		$Player = new CityBattlePlayer;
		$camps = (new CountryCampList)->dicGetAllId();
		while($ret = $CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status='.CityBattle::STATUS_DEFAULT.' and start_time<="'.date('Y-m-d H:i:s').'"')){
			$ret = $ret[0];
			$battleId = $ret['id'];
			//如果没有进攻方
			if(!$Player->findFirst(['battle_id='.$battleId.' and camp_id in('.join(',', array_diff($camps, [$ret['camp_id']])).')'])){
				$CityBattle->updateAll(['status'=>CityBattle::STATUS_CLAC_MELEE, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_DEFAULT]);
				(new CityBattleCommonLog)->add($battleId, 0, 0, '无进攻方');
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']无进攻方'.PHP_EOL;
				continue;
			}
			//begin
			dbBegin($db);
			
			//$this->updateGuildBuff($ret);
			
			//更新血量
			//$this->updateElementDurability($ret, 1);
			
			if($CityBattle->updateAll(['real_start_time'=>"'".date('Y-m-d H:i:s', time()+$readyTime*60)."'", 'status'=>CityBattle::STATUS_READY_SEIGE, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_DEFAULT])){
				(new CityBattleCommonLog)->add($battleId, 0, 0, '城门战开始准备');
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']比赛进入城门战准备阶段'.PHP_EOL;
			}
			
			//commit
			dbCommit($db);
			
			Cache::db('cache', 'CityBattle')->flushDB();
		}
		(new CityBattleRound)->updateAll(['status'=>CityBattleRound::DOING], ['status'=>CityBattleRound::SELECT_PLAYER_FINISH]);
		echo 'finish';
	}
	
	/**
     * 更新比赛为城门战开战
     * 执行时间: 20:03
     * 
     * @return <type>
     */
	public function match2Action($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_citybattlematch2";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CityBattle = new CityBattle;
		if(!$CityBattle->findFirst(['status<'.CityBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		while(true){
			$ret = $CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status='.CityBattle::STATUS_READY_SEIGE.' and real_start_time<="'.date('Y-m-d H:i:s').'"');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				if($CityBattle->updateAll(['status'=>CityBattle::STATUS_SEIGE, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_READY_SEIGE])){
					(new CityBattleCommonLog)->add($battleId, 0, 0, '城门战开战');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']城门战开战'.PHP_EOL;
				}
			}elseif(!$CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status<'.CityBattle::STATUS_SEIGE)){
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 更新比赛为城门战结束
     * 执行时间: 20:18
     * 
     * @return <type>
     */
	public function match3Action($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_citybattlematch3";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CityBattle = new CityBattle;
		if(!$CityBattle->findFirst(['status<'.CityBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$fightTime = (new CountryBasicSetting)->dicGetOne('match_gate_duration');
		while(true){
			$ret = $CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status='.CityBattle::STATUS_SEIGE.' and real_start_time<="'.date('Y-m-d H:i:s', time() - $fightTime*60).'"');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				if($CityBattle->updateAll(['status'=>CityBattle::STATUS_CLAC_SEIGE, 'door_battle_time'=>$fightTime*60, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_SEIGE])){
					(new CityBattleCommonLog)->add($battleId, 0, 0, '城门战结束[无需内城战]');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']城门战结束'.PHP_EOL;
				}
			}elseif(!$CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status<'.CityBattle::STATUS_CLAC_SEIGE)){
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
		$taskId = @$param[0];
		$processName = "php_task_citybattlematch4";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CityBattle = new CityBattle;
		if(!$CityBattle->findFirst(['status<'.CityBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$db = $this->di['db_citybattle_server'];
		$readyTime = (new CountryBasicSetting)->dicGetOne('match_fight_ready');
		
		$CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
		//$CityBattleMap = new CityBattleMap;
		
		while(true){
			$ret = $CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status='.CityBattle::STATUS_CLAC_SEIGE);
			if($ret){
				sleep(2);//防止比赛结束和其他脚本操作并发
				Cache::db('cache', 'CityBattle')->flushDB();
				$ret = $ret[0];
				$battleId = $ret['id'];
				//begin
				dbBegin($db);
				
				$ladderPpqs = $CityBattlePlayerProjectQueue->find([
				    "battle_id=:battleId: and status=1 and type=:type:",
                    'bind'=>[
                        'battleId'=>$battleId,
                        'type' => CityBattlePlayerProjectQueue::TYPE_LADDER_ING
                    ]
                ])->toArray();
				//结束所有queue
				$CityBattlePlayerProjectQueue->updateAll(['status'=>3, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['battle_id'=>$battleId, 'status'=>1]);
				$now = strtotime($ret['real_start_time']) + $ret['door_battle_time'];
				$ladderScorePer = (new CountryBasicSetting)->getValueByKey('get_ladder_score');
				$CityBattlePlayer = new CityBattlePlayer;
				$CityBattlePlayer->battleId = $battleId;
				foreach($ladderPpqs as $_ppq){
					$addScore = max(0, floor(($now - strtotime($_ppq['create_time'])) * $ladderScorePer));
					if($addScore)
						$CityBattlePlayer->alter($_ppq['player_id'], ['score'=>'score+'.$addScore]);
				}
				
				
				//判断直接结束或者开始内城战
				if(!($ret['attack_camp'] && $ret['defend_camp'])){
					//更新状态,跳过内城战
					if(!$CityBattle->updateAll(['status'=>CityBattle::STATUS_CLAC_MELEE, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_CLAC_SEIGE])){
						dbRollback($db);
						continue;
					}
					
					(new CityBattleCommonLog)->add($battleId, 0, 0, '跳过内城战');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']跳过内城战'.PHP_EOL;
				}else{
				
					//结算积分todo
					
					//重置玩家士兵
					$CityBattlePlayer = new CityBattlePlayer;
					$CityBattlePlayer->battleId = $battleId;
					$CityBattlePlayerArmy = new CityBattlePlayerArmy;
					$CityBattlePlayerArmy->battleId = $battleId;
					$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
					$CityBattlePlayerArmyUnit->battleId = $battleId;
					$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
					$CityBattlePlayerGeneral->battleId = $battleId;
					$CityBattlePlayerMasterskill = new CityBattlePlayerMasterskill;
					$CityBattlePlayerMasterskill->battleId = $battleId;
					$playerIds = Set::extract('/player_id', $CityBattlePlayer->sqlGet('select * from '.$CityBattlePlayer->getSource().' where battle_id='.$battleId.' and status>0'));
					foreach($playerIds as $_playerId){
						$CityBattlePlayer->alter($_playerId, [
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
						$CityBattlePlayerArmy->updateAll([
							'status'=>0, 
							'fill_soldier_time'=>"'0000-00-00 00:00:00'",
							'update_time'=>"'".date('Y-m-d H:i:s')."'", 
							'rowversion'=>"'".uniqid()."'"], ['battle_id'=>$battleId, 'player_id'=>$_playerId]);
						$CityBattlePlayerArmy->clearDataCache($_playerId);
						$CityBattlePlayerArmyUnit->updateAll([
							'soldier_id'=>'if(soldier_id=0, last_soldier_id, soldier_id)', 
							'soldier_num'=>'max_soldier_num', 
							'update_time'=>"'".date('Y-m-d H:i:s')."'", 
							'rowversion'=>"'".uniqid()."'"], ['battle_id'=>$battleId, 'player_id'=>$_playerId]);
						$CityBattlePlayerArmyUnit->_clearDataCache($_playerId);
						$CityBattlePlayerGeneral->updateAll(['status'=>0, 'update_time'=>"'".date('Y-m-d H:i:s')."'", 'rowversion'=>"'".uniqid()."'"], ['battle_id'=>$battleId, 'player_id'=>$_playerId]);
						$CityBattlePlayerGeneral->clearDataCache($_playerId);
					}
					$CityBattlePlayerMasterskill->reset();
					
					//更新城门战map=1
					$CityBattleMap = new CityBattleMap;
					$CityBattleMap->updateAll(['status'=>0], ['battle_id'=>$battleId, 'part'=>1]);
					$CityBattleMap->updateAll(['status'=>1], ['battle_id'=>$battleId, 'part'=>2]);
					//$CrossMap->initBattleMap($battleId);
					
					//更新血量
					//$this->updateElementDurability($ret, 2);
					
					//更新状态,内城战开始
					if(!$CityBattle->updateAll([
						'status'=>CityBattle::STATUS_READY_MELEE, 
						'melee_time'=>"'".date('Y-m-d H:i:s', time()+$readyTime*60)."'",
						'score_time'=>'melee_time',
						'update_time'=>"'".date('Y-m-d H:i:s')."'"
						], ['id'=>$battleId, 'status'=>CityBattle::STATUS_CLAC_SEIGE])){
						dbRollback($db);
						continue;
					}
					
					(new CityBattleCommonLog)->add($battleId, 0, 0, '内城战开始准备');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']内城战开始准备'.PHP_EOL;
				}
				//end
				dbCommit($db);
				
				Cache::db('cache', 'CityBattle')->flushDB();
			}elseif(!$CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status<'.CityBattle::STATUS_READY_MELEE)){
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
		$taskId = @$param[0];
		$processName = "php_task_citybattlematch5";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CityBattle = new CityBattle;
		if(!$CityBattle->findFirst(['status<'.CityBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		while(true){
			$ret = $CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status='.CityBattle::STATUS_READY_MELEE.' and melee_time<="'.date('Y-m-d H:i:s').'"');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				//更新状态
				if($CityBattle->updateAll(['status'=>CityBattle::STATUS_MELEE, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_READY_MELEE])){
					(new CityBattleCommonLog)->add($battleId, 0, 0, '内城战开战');
					echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']内城战开战'.PHP_EOL;
				}
			}elseif(!$CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status<'.CityBattle::STATUS_MELEE)){
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 更新比赛为内城战结束
     * 执行时间: 20:00-21:00
     * 
     * @return <type>
     */
	public function match6Action($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_citybattlematch6";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CityBattle = new CityBattle;
		if(!$CityBattle->findFirst(['status<'.CityBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$db = $this->di['db_citybattle_server'];
		$fightTime = (new CountryBasicSetting)->dicGetOne('match_fight_duration');
		while(true){
			$ret = $CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status='.CityBattle::STATUS_MELEE.' and melee_time<="'.date('Y-m-d H:i:s', time() - $fightTime*60).'"');
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				
				//begin
				dbBegin($db);
				
				//结算积分todo
				
				if(!$CityBattle->updateAll(['melee_end_time'=>'FROM_UNIXTIME(melee_time+'.($fightTime*60).')', 'status'=>CityBattle::STATUS_CLAC_MELEE, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_MELEE])){
					dbRollback($db);
					continue;
				}
				(new CityBattleCommonLog)->add($battleId, 0, 0, '内城战结束[时间到]');
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']内城战结束'.PHP_EOL;
				
				//end
				dbCommit($db);
				
			}elseif(!$CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status<'.CityBattle::STATUS_CLAC_MELEE)){
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
		$taskId = @$param[0];
		$ModelBase         = new ModelBase;
		$processName = "php_task_citybattlematch7";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$CityBattle = new CityBattle;
		if(!$CityBattle->findFirst(['status<'.CityBattle::STATUS_FINISH])){
			echo '['.date('Y-m-d H:i:s').']无比赛'.PHP_EOL;
			exit;
		}
		
		$db = $this->di['db_citybattle_server'];
		//$db2 = $this->di['db'];
		
		$CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
		$CityBattlePlayer = new CityBattlePlayer;
		$Player = new Player;
		while(true){
			$ret = $CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status='.CityBattle::STATUS_CLAC_MELEE);
			if($ret){
				$ret = $ret[0];
				$battleId = $ret['id'];
				
				//begin
				dbBegin($db);
				//dbBegin($db2);
				
				//结束所有queue
				$CityBattlePlayerProjectQueue->updateAll(['status'=>3, 'update_time'=>"'".date('Y-m-d H:i:s')."'", 'rowversion'=>"rowversion+1"], ['battle_id'=>$battleId, 'status'=>1]);
				
				//结算胜者
				if(!($ret['attack_camp'] && $ret['defend_camp'])){//无内城战
					$win = $ret['defend_camp'];
				}elseif($ret['attack_score'] > $ret['defend_score'] ){//攻方胜
					$win = $ret['attack_camp'];
				}else{//守方胜
					$win = $ret['defend_camp'];
				}
				
				//更新状态
				if(!$CityBattle->updateAll(['status'=>CityBattle::STATUS_FINISH, 'win_camp'=>$win, 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>CityBattle::STATUS_CLAC_MELEE])){
					dbRollback($db);
					//dbRollback($db2);
					continue;
				}
				
				//(new CrossGuildInfo)->changeInfo($ret['guild_1_id'], $ret['guild_2_id'], $win);
				
				//更新城市归属
				(new City)->alter($ret['city_id'], ['camp_id'=>$win]);
				
				(new CityBattleGuildMission)->addCountByCamp($win, 3, $ret['city_id']);//任务:本方阵营攻占xx
				(new CityBattleGuildMission)->addCountByCamp($win, 4, 0, 1);//任务：联盟成员参加跨服战并获胜%{num}次
				
				(new CityBattleCommonLog)->add($battleId, 0, 0, '比赛结束');
				
				echo '['.date('Y-m-d H:i:s').'][battle_id='.$battleId.']比赛结束'.PHP_EOL;
				
				//end
				dbCommit($db);
				//dbCommit($db2);
				
				//解除罩子
				//$playerIds = Set::extract('/player_id', $CrossPlayer->find(['battle_id='.$battleId.' and status>0'])->toArray());
				$playerIds = Set::extract('/player_id', $CityBattlePlayer->sqlGet('select * from '.$CityBattlePlayer->getSource().' where battle_id='.$battleId.' and status>0'));
				foreach($playerIds as $_playerId){
					$parseInfo = CityBattlePlayer::parsePlayerId($_playerId);
					//$Player->alter($_playerId, ['is_in_cross'=>0]);
					$ModelBase->execByServer($parseInfo['server_id'], 'Player', 'alter', [$_playerId, ['is_in_cross'=>0]]);
				}
				
			}elseif(!$CityBattle->sqlGet('select * from '.$CityBattle->getSource().' where status<'.CityBattle::STATUS_FINISH)){
				//如果所有都结束，大状态改为比赛结束
				(new CityBattleRound)->alterCurrent(['status'=>CityBattleRound::CLAC_REWARD], ['status'=>CityBattleRound::DOING]);
				break;
			}else{
				sleep(1);
			}
		}
	}
	
	/**
     * 攻城锤攻击城门
     * 
     * 
     * @return <type>
     */
	public function hammerAttackAction($param=[]){
		$taskId = @$param[0];
		
		$processName = "php_task_citybattlehammerattack";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$db = $this->di['db_citybattle_server'];
		
		//结束时间
		$endTime = date('Y-m-d 23:59:59');
		//查找进行中的battleIds
		$CityBattle = new CityBattle;
		$battleIds = $CityBattle->getCurrentBattleIdList();
		if(!$battleIds){
			echo '['.date('Y-m-d H:i:s').']未找到比赛'.PHP_EOL;
		}
		
		//循环查找可出手的攻城锤
		$CityBattlePlayer = new CityBattlePlayer;
		$Map = new CityBattleMap;
		$CityBattleCamp = new CityBattleCamp;
		$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
		$DispatcherTask = new CityBattleDispatcherTask;
		$QueueCityBattle = new QueueCityBattle;
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$lockX = 0;
		$lockY = 0;
		$lockToX = 0;
		$lockToY = 0;
		$lockBattleId = 0;
		global $inDispWorker;
		$inDispWorker = true;
		
		echo '['.date('Y-m-d H:i:s').']begin'.PHP_EOL;
		//try
		try {
			$j = 0;
			while(true){
				$j++;
				if($j % 60 == 0){
					$j %= 60;
					if(!$CityBattle->getCurrentBattleIdList()){
						break;
					}
				}
				/*if(date('Y-m-d H:i:s') > $endTime){
					break;
				}*/
				$battleIds = Set::extract('/id', $CityBattle->find(['status='.CityBattle::STATUS_SEIGE])->toArray());
				if(!$battleIds){
					sleep(1);
					continue;
				}
				$maps = $Map->sqlGet('select * from '.$Map->getSource().' where battle_id in ('.join(',', $battleIds).') and map_element_origin_id = 402 and camp_id > 0 and (durability > 0 || (durability = 0 and recover_time <= now())) and attack_time <= FROM_UNIXTIME(UNIX_TIMESTAMP() - attack_cd) order by update_time asc limit 10');
				if(!$maps){
					sleep(1);
					continue;
				}

				foreach($maps as $map){
					CityBattle::$endBattle = false;
					$CityBattlePlayer->battleId = $CityBattlePlayerGeneral->battleId = $CityBattleCamp->battleId = $Map->battleId = $map['battle_id'];
					$lockX = $map['x'];
					$lockY = $map['y'];
					$lockBattleId = $map['battle_id'];
					
					$Map->rebuildBuilding($map);
					
					//获取对应城门
					$doorMap = $Map->findFirst(['battle_id='.$map['battle_id'].' and area='.$map['area'].' and map_element_origin_id=401 and durability > 0']);
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
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY)){
							dbRollback($db);
							$Map->alter($map['id'], []);
							continue;
						}
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY)){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
							$Map->alter($map['id'], []);
							continue;
						}
						
						//计算攻城锤攻击力
						$formula = (new CountryBasicSetting)->getValueByKey('wf_warhammer_atkpower');
						$power = 0;
						$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_HAMMER_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
						$ppqs = CityBattlePlayerProjectQueue::find($condition);
						if(!$ppqs->toArray()){
							dbRollback($db);
							$Map->alter($map['id'], []);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						foreach($ppqs as $_ppq){
							$power += $QueueCityBattle->getArmyPower($map['battle_id'], $_ppq->player_id, $_ppq->army_id);
						}
						eval('$reduceDurability = '.$formula.';');
						
						$buff = 0;
						$addBuff = 0;
						//君临天下：若该武将的统御高于所有敌军武将，则所有本方器械的伤害增加%
						$buff += $CityBattleCamp->getByCampId($map['camp_id'])['buff_buildattack'];
						
						//攻城锤精通:驻守时增加攻城锤攻击伤害%
						$armyIds = array_unique(Set::extract('/army_id', $ppqs->toArray()));
						$buff += $CityBattlePlayerGeneral->getSkillsByArmies($armyIds, [21])[21][0];
						
						//攻城锤大师:每次攻击后，攻城锤的攻击力增加
						$addBuff += $CityBattlePlayerGeneral->getSkillsByArmies($armyIds, [22])[22][0] * $map['attack_times'];
						
						$reduceDurability *= 1 + $buff;
						$reduceDurability += $addBuff;
						$reduceDurability = floor($reduceDurability);
						//城门扣血
						$Map->alter($doorMap['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')']);
						$doorMap['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						(new CityBattleCommonLog)->add($map['battle_id'], 0, $map['camp_id'], '攻击城门['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $doorMap['durability']-$reduceDurability).'|by攻城锤');
						(new QueueCityBattle)->crossNotice($map['battle_id'], 'hammerAttackDoor', ['reduce'=>$reduceDurability, 'rest'=>max(0, $doorMap['durability']-$reduceDurability), 'from_x'=>$map['x'], 'from_y'=>$map['y'], 'to_x'=>$doorMap['x'], 'to_y'=>$doorMap['y']]);
						
						//更新英勇值
						$addScore = floor(min($doorMap['durability'], $reduceDurability) * (new CountryBasicSetting)->getValueByKey('damage_gate_score') / (100*count($ppqs)));
						foreach($ppqs as $_ppq){
							$CityBattlePlayer->alter($_ppq->player_id, ['score'=>'score+'.$addScore]);
							(new CityBattleCommonLog)->add($_ppq->battle_id, $_ppq->player_id, $_ppq->camp_id, '更新英勇值+'.$addScore.'|by攻城锤攻击城门');
						}
						
						//如果破门
						if($doorMap['durability'] <= $reduceDurability){
							//获取攻击方guildid
							//$guildId = $guilds['attack'];
							
							//更新城门状况
							$CityBattle->updateDoor($map['battle_id'], $map['camp_id']);
							
							//撤离所有下一个区域的敌方占领投石车和床弩
							$PlayerProjectQueue->callbackCatapult($map['battle_id'], $doorMap['next_area']);
							$PlayerProjectQueue->callbackCrossbow($map['battle_id'], $doorMap['next_area']);
							
							//遣返本区攻城锤内部队
							$cb = $CityBattle->getBattle($map['battle_id']);
							if(!$cb['attack_camp']){
								$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area']);
								$PlayerProjectQueue->callbackLadder($map['battle_id'], $map['area']);
							}
							
							//任务：联盟成员在跨服战中参与击破城门%{num}次
							$guildMemberNum = $CityBattlePlayer->getGuildMemberNumByCampId($map['camp_id']);
							foreach($guildMemberNum as $_guildId=>$_num){
								(new CityBattleGuildMission)->addCountByGuildType($_guildId, 5, $_num);
							}
							
							//日志
							(new CityBattleCommonLog)->add($map['battle_id'], 0, $map['camp_id'], '破门['.$doorMap['area'].']|by攻城锤');
							
							(new QueueCityBattle)->crossNotice($map['battle_id'], 'doorBroken', ['x'=>$doorMap['x'], 'y'=>$doorMap['y']]);
							
							$CityBattle->endBattle($map['battle_id']);
						}
						
						//计算攻击时间
						//if($map['attack_time'] == '0000-00-00 00:00:00'){
							$attackTime = date('Y-m-d H:i:s');
						/*}else{
							$attackTime = date('Y-m-d H:i:s', strtotime($map['attack_time'])+$atkcdTime);
						}*/
						
						//更新攻城锤cd
						$Map->alter($map['id'], ['attack_time'=>"'".$attackTime."'", 'attack_times'=>'attack_times+1']);
						
						if(!(new CityBattle)->isActivity($map['battle_id'])){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						
						//end
						dbCommit($db);
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
												
						echo '['.date('Y-m-d H:i:s').']hammerAttack(mapid='.$map['id'].')'.PHP_EOL;
					}
					
				}
			}
		} catch (Exception $e) {
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
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
		$processName = "php_task_citybattlecrossbowattack";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		$db = $this->di['db_citybattle_server'];
		
		//结束时间
		$endTime = date('Y-m-d 23:59:59');
		//查找进行中的battleIds
		$CityBattle = new CityBattle;
		$battleIds = $CityBattle->getCurrentBattleIdList();
		if(!$battleIds){
			echo '['.date('Y-m-d H:i:s').']未找到比赛'.PHP_EOL;
		}
		
		//循环查找可出手的攻城锤
		$CityBattlePlayer = new CityBattlePlayer;
		$Map = new CityBattleMap;
		$CityBattleCamp = new CityBattleCamp;
		$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
		$DispatcherTask = new CityBattleDispatcherTask;
		$QueueCityBattle = new QueueCityBattle;
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$lockX = 0;
		$lockY = 0;
		$lockToX = 0;
		$lockToY = 0;
		$lockBattleId = 0;
		global $inDispWorker;
		$inDispWorker = true;
		
		//准备数据
		$allCamp  = (new CountryCampList)->dicGetAllId();
		$CityBattleBuff = new CityBattleBuff;
		$buff504s = [];
		foreach($allCamp as $_camp){
			//城战科技：弹道学：提升投石车和床弩伤害|<#72,255,164#>%{num}%%|
			$buff504s[$_camp] = $CityBattleBuff->getCampBuff($_camp, 504);
		}
		
		echo '['.date('Y-m-d H:i:s').']begin'.PHP_EOL;
		//try
		try {
			$j = 0;
			while(true){
				$j++;
				if($j % 60 == 0){
					$j %= 60;
					if(!$CityBattle->getCurrentBattleIdList()){
						break;
					}
				}
				/*if(date('Y-m-d H:i:s') > $endTime){
					break;
				}*/
				$battleIds = Set::extract('/id', $CityBattle->find(['status='.CityBattle::STATUS_SEIGE])->toArray());
				if(!$battleIds){
					sleep(1);
					continue;
				}
				$maps = $Map->sqlGet('select * from '.$Map->getSource().' where battle_id in ('.join(',', $battleIds).') and map_element_origin_id = 405 and player_id > 0 and attack_time <= FROM_UNIXTIME(UNIX_TIMESTAMP() - attack_cd) order by update_time asc limit 10');//FROM_UNIXTIME(UNIX_TIMESTAMP() - 30);
				if(!$maps){
					sleep(1);
					continue;
				}
				foreach($maps as $map){
					CityBattle::$endBattle = false;
					$CityBattlePlayer->battleId = $CityBattlePlayerGeneral->battleId = $CityBattleCamp->battleId = $Map->battleId = $map['battle_id'];
					$lockX = $map['x'];
					$lockY = $map['y'];
					$lockBattleId = $map['battle_id'];
					
					//获取对应攻城锤或云梯
					$targetMap = $Map->findFirst(['battle_id='.$map['battle_id'].' and area='.$map['target_area'].' and map_element_id='.$map['target_map_element_id'].' and camp_id > 0']);
					if($targetMap){
						$targetMap = $targetMap->toArray();
						$lockToX = $targetMap['x'];
						$lockToY = $targetMap['y'];
						
						//begin
						dbBegin($db);
						//lock
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY)){
							dbRollback($db);
							$Map->alter($map['id'], []);
							continue;
						}
						if(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY)){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
							$Map->alter($map['id'], []);
							continue;
						}
						
						$Map->rebuildBuilding($targetMap);
						
						//检查对象血
						if(!$targetMap['durability']){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
							$Map->alter($map['id'], []);
							continue;
						}
						
						//计算床弩攻击力
						$formula = (new CountryBasicSetting)->getValueByKey('wf_glaivethrower_atkpower');
						$power = 0;
						$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$map['id'].' and status=1 and end_time="0000-00-00 00:00:00"'];
						$ppqs = CityBattlePlayerProjectQueue::find($condition);
						if(!$ppqs->toArray()){
							dbRollback($db);
							$Map->alter($map['id'], []);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						foreach($ppqs as $_ppq){
							$power += $QueueCityBattle->getArmyPower($map['battle_id'], $_ppq->player_id, $_ppq->army_id);
						}
						eval('$reduceDurability = '.$formula.';');
						
						$buff = 0;
						$addBuff = 0;
						//君临天下：若该武将的统御高于所有敌军武将，则所有本方器械的伤害增加%
						$buff += $CityBattleCamp->getByCampId($map['camp_id'])['buff_buildattack'];
						
						//床弩精通:驻守时增加床弩攻击伤害%
						$armyIds = array_unique(Set::extract('/army_id', $ppqs->toArray()));
						$buff += $CityBattlePlayerGeneral->getSkillsByArmies($armyIds, [15])[15][0];
						
						//城战科技：弹道学：提升投石车和床弩伤害|<#72,255,164#>%{num}%%|
						$buff += $buff504s[$map['camp_id']];
						
						//床弩大师:每次攻击后，床弩的攻击力增加
						$addBuff += $CityBattlePlayerGeneral->getSkillsByArmies($armyIds, [16])[16][0] * $map['attack_times'];
						
						$reduceDurability *= 1 + $buff;
						$reduceDurability += $addBuff;
						$reduceDurability = floor($reduceDurability);
						
						if($targetMap['map_element_origin_id'] == 402){//攻城锤
							//扣血
							$recoverTime = (new CountryBasicSetting)->dicGetOne('wf_warhammer_respawn_time');
							$Map->alter($targetMap['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
							$targetMap['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
							
							(new CityBattleCommonLog)->add($map['battle_id'], 0, $map['camp_id'], '攻击攻城锤['.$targetMap['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $targetMap['durability']-$reduceDurability).'|by床弩(mapId='.$map['id'].')');
							
							//如果攻城锤血0，遣返所有攻城锤部队
							if($targetMap['durability'] <= $reduceDurability){
								
								$PlayerProjectQueue->callbackHammer($map['battle_id'], $targetMap['area'], $targetMap['id']);
								
								//日志
								(new CityBattleCommonLog)->add($map['battle_id'], 0, $map['camp_id'], '攻城锤0血['.$targetMap['area'].']|by床弩(mapId='.$map['id'].')');
							}
							
							$targetType = 'Hammer';
						}else{//云梯
							//刷新云梯进度
							$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$targetMap['id'].' and status=1'];
							$ppqs = $PlayerProjectQueue->find($condition)->toArray();
							(new QueueCityBattle)->refreshLadder($ppqs[0], $ppqs, $targetMap, time(), $finishLadder, $finishBattle);
							if($finishLadder){
								dbCommit($db);
								$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
								$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
								continue;
							}
							//扣血
							$recoverTime = (new CountryBasicSetting)->dicGetOne('wf_ladder_respawn_time');
							
							//云梯修复
							$playerIds = Set::extract('/player_id', $CityBattlePlayer->getByCampId([$targetMap['camp_id']]));
							$recoverTimeBuff = $CityBattlePlayerGeneral->getSkillsByPlayers($playerIds, [24])[24][0];
							$recoverTime -= $recoverTimeBuff;
							$recoverTime = floor($recoverTime);
							
							$Map->alter($targetMap['id'], ['durability'=>'GREATEST(0, durability-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
							
							(new CityBattleCommonLog)->add($map['battle_id'], 0, $map['camp_id'], '攻击云梯['.$targetMap['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $targetMap['durability']-$reduceDurability).'|by床弩(mapId='.$map['id'].')');
							
							//如果云梯血0，遣返所有云梯部队
							if($targetMap['durability'] <= $reduceDurability){
								
								$PlayerProjectQueue->callbackLadder($map['battle_id'], $targetMap['area'], $targetMap['id']);
								
								//日志
								(new CityBattleCommonLog)->add($map['battle_id'], 0, $map['camp_id'], '天梯0血['.$targetMap['area'].']|by床弩(mapId='.$map['id'].')');
								
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
						
						if(!(new CityBattle)->isActivity($map['battle_id'])){
							dbRollback($db);
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
							continue;
						}
						
						//end
						dbCommit($db);

						//unlock
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
						
						//长连接通知
						//获取参战成员
						(new QueueCityBattle)->crossNotice($map['battle_id'], 'crossbowAttack'.$targetType, ['reduce'=>$reduceDurability, 'rest'=>max(0, $targetMap['durability']-$reduceDurability), 'from_x'=>$map['x'], 'from_y'=>$map['y'], 'to_x'=>$targetMap['x'], 'to_y'=>$targetMap['y']]);
						if($targetMap['durability'] <= $reduceDurability){
							if($targetMap['map_element_origin_id'] == 402){
								(new QueueCityBattle)->crossNotice($map['battle_id'], 'hammerBroken', ['x'=>$targetMap['x'], 'y'=>$targetMap['y']]);
							}else{
								(new QueueCityBattle)->crossNotice($map['battle_id'], 'ladderBroken', ['x'=>$targetMap['x'], 'y'=>$targetMap['y']]);
							}
						}
						
						echo '['.date('Y-m-d H:i:s').']crossbowAttack(mapid='.$map['id'].')'.PHP_EOL;
					}
					
				}
			}
		} catch (Exception $e) {
			dbRollback($db);
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockToX, $lockToY);//unlock
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $lockBattleId, $lockX, $lockY);//unlock
		}
		
		
		echo '['.date('Y-m-d H:i:s').']end'.PHP_EOL;
	}
	
	/**
     * 刷新内城战分数
     * 
     * 
     * @return <type>
     */
	public function refreshScoreAction($param=[]){
		$taskId = @$param[0];
		$processName = "php_task_citybattlerefreshscore";
		if($taskId){
			$processName .= '_'.$taskId;
		}
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		//查找进行中的battleIds
		$CityBattle = new CityBattle;
		$battleIds = $CityBattle->getCurrentBattleIdList();
		if(!$battleIds){
			echo '['.date('Y-m-d H:i:s').']未找到比赛'.PHP_EOL;
		}
		
		try {
			$j = 0;
			while(true){
				$j++;
				if($j % 60 == 0){
					$j %= 60;
					if(!$CityBattle->getCurrentBattleIdList()){
						break;
					}
				}
				$battleIds = Set::extract('/id', $CityBattle->find(['status='.CityBattle::STATUS_MELEE])->toArray());
				if(!$battleIds){
					sleep(8);
					continue;
				}
				foreach($battleIds as $_battleId){
					$this->_refreshScore($_battleId, $taskId);
				}
				sleep(8);
			}
		} catch (Exception $e) {
		}
		
		
		echo '['.date('Y-m-d H:i:s').']end'.PHP_EOL;
	}
	
	public function _refreshScore($battleId, $taskId=''){
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ .':'. $taskId;
		Cache::lock($lockKey, 10, CACHEDB_PLAYER, 60, 'CityBattle');
		
		//begin
		$db = $this->di['db_citybattle_server'];
		//dbBegin($db);
		
		try {
			CityBattle::$endBattle = false;
			$now = time();
			//获取cb
			$CityBattle = new CityBattle;
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$second = max(0, $now - strtotime($cb['score_time']));
			if(!$second){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$updateData = ['attack_score'=>0, 'defend_score'=>0];
			$campScore = [$cb['attack_camp']=>&$updateData['attack_score'], $cb['defend_camp']=>&$updateData['defend_score']];
			
			//检查status
			if($cb['status'] != CityBattle::STATUS_MELEE){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取地图数据
			$CityBattleMap = new CityBattleMap;
			$maps = $CityBattleMap->find(['battle_id='.$battleId.' and status=1 and map_element_origin_id=406'])->toArray();
			//计算双方增加积分
			$ar = [0=>0, 1=>0.25, 2=>0.5, 3=>1.125, 4=>1.875, 5=>2.5];//占领区块数=>每秒增加积分
			$count = [
				1=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				2=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				3=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				4=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				5=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
			];
			//统计每个区块占领数量
			foreach($maps as $_map){
				if(!$_map['section']) continue;
				if(in_array($_map['section'], [6, 7])) continue;
				$count[$_map['section']][$_map['camp_id']]++;
			}
			//统计每个阵营占领数量
			foreach($count as $_c){
				if($_c[$cb['attack_camp']] > $_c[$cb['defend_camp']]){
					$campScore[$cb['attack_camp']]++;
				}elseif($_c[$cb['attack_camp']] < $_c[$cb['defend_camp']]){
					$campScore[$cb['defend_camp']]++;
				}
			}
			/*foreach([1, 2, 3, 4, 5] as $_i){
				if($cb['section'.$_i.'_camp_id']){
					$campScore[$cb['section'.$_i.'_camp_id']]++;
				}
			}*/
			
			//统计分数
			$campScore[$cb['attack_camp']] = $ar[$campScore[$cb['attack_camp']]] * $second;
			$campScore[$cb['defend_camp']] = $ar[$campScore[$cb['defend_camp']]] * $second;
			
			//如果城市无主，分数到阈值且相同，随机扣掉一分
			if(!$cb['camp_id'] && floor($campScore[$cb['attack_camp']]) == floor($campScore[$cb['defend_camp']]) && $campScore[$cb['attack_camp']] >= $CityBattle->winScore){
				if($campScore[$cb['attack_camp']] > $campScore[$cb['defend_camp']]){
					$campScore[$cb['defend_camp']]--;
				}elseif($campScore[$cb['attack_camp']] < $campScore[$cb['defend_camp']]){
					$campScore[$cb['attack_camp']]--;
				}else{
					if(lcg_value1() < 0.5){
						$campScore[$cb['defend_camp']]--;
					}else{
						$campScore[$cb['attack_camp']]--;
					}
				}
			}
			
			//更新积分
			$CityBattle->alter($battleId, [
				'attack_score'=>'attack_score+'.$updateData['attack_score'],
				'defend_score'=>'defend_score+'.$updateData['defend_score'],
				'score_time'=>date('Y-m-d H:i:s', $now),
			], ['status'=>CityBattle::STATUS_MELEE]);
			
			//检查战斗结束
			$CityBattle->endBattle($battleId);
			
			//commit
			//dbCommit($db);
		} catch (Exception $e) {
			//dbRollback($db);
		}
		//解锁
		Cache::unlock($lockKey, CACHEDB_PLAYER, 'CityBattle');
	}
	
	public function _refreshSectionStat($battleId, $taskId=''){
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ .':'. $taskId;
		Cache::lock($lockKey, 10, CACHEDB_PLAYER, 60, 'CityBattle');
		
		//begin
		$db = $this->di['db_citybattle_server'];
		//dbBegin($db);
		
		try {
			$now = time();
			//获取cb
			$CityBattle = new CityBattle;
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查status
			if($cb['status'] != CityBattle::STATUS_MELEE){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取地图数据
			$CityBattleMap = new CityBattleMap;
			$maps = $CityBattleMap->find(['battle_id='.$battleId.' and status=1 and map_element_origin_id=406'])->toArray();

			$updateData = [
				'section1_camp_id'=>'section1_camp_id',
				'section2_camp_id'=>'section2_camp_id',
				'section3_camp_id'=>'section3_camp_id',
				'section4_camp_id'=>'section4_camp_id',
				'section5_camp_id'=>'section5_camp_id',
			];
			$count = [
				1=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				2=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				3=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				4=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
				5=>[$cb['attack_camp']=>0, $cb['defend_camp']=>0],
			];
			//统计每个区块占领数量
			foreach($maps as $_map){
				if(!$_map['section']) continue;
				if(in_array($_map['section'], [6, 7])) continue;
				$count[$_map['section']][$_map['camp_id']]++;
			}
			//统计每个阵营占领数量
			foreach($count as $_k => $_c){
				if($_c[$cb['attack_camp']] > $_c[$cb['defend_camp']]){
					$updateData['section'.$_k.'_camp_id'] = $cb['attack_camp'];
				}elseif($_c[$cb['attack_camp']] < $_c[$cb['defend_camp']]){
					$updateData['section'.$_k.'_camp_id'] = $cb['defend_camp'];
				}
			}
			
			//更新积分
			$CityBattle->alter($battleId, $updateData, ['status'=>CityBattle::STATUS_MELEE]);
			
			//commit
			//dbCommit($db);
		} catch (Exception $e) {
			//dbRollback($db);
		}
		//解锁
		Cache::unlock($lockKey, CACHEDB_PLAYER, 'CityBattle');
	}
	
    /**
     * 清除羽林军称号buff
     */
     private function clearTitle(){
        log4task("Start clear title & title buff ...");

        $CountryBattleTitle = new CountryBattleTitle;
        $CityBattleRank     = new CityBattleRank;
        $Drop               = new Drop;
        $ModelBase          = new ModelBase;

        $allTitle    = $CountryBattleTitle->dicGetAll();
        $allBuffId   = Set::extract('/buff_id', $allTitle);
        $buffTempIds = [];
        foreach($allBuffId as $v) {
            if(!empty($v)) {
                $drop        = $Drop->dicGetOne($v);
                $dropData    = $drop['drop_data'];
                $cuBuffTempIds = Set::extract('/1', $dropData);
                $buffTempIds = array_merge($buffTempIds, $cuBuffTempIds);
            }
        }
        $buffTempIds     = array_unique($buffTempIds);
        $allTitlePlayer = $CityBattleRank->getAllTitle();
        foreach(array_keys($allTitlePlayer) as $titlePlayerId) {
            $titleServerId = CityBattlePlayer::parsePlayerId($titlePlayerId)['server_id'];
            echo " server_id=".$titleServerId," player_id=".$titlePlayerId,PHP_EOL;
            $ModelBase->execByServer($titleServerId, 'PlayerBuffTemp', 'clearTitleBuff', [$titlePlayerId, $buffTempIds]);
        }
        //清空city_battle_rank表
        $CityBattleRank->clearRankCache();
        CityBattleRank::find()->delete();
        $CityBattleRank->clearRankCache();

        log4task("End clear title & title buff !");
    }
    /**
     * 跨服务器执行，选人脚本
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php city_battle elect
     */
    public function electAction(){
        set_time_limit(0);
        log4task('Electing...');
        $startTimeExec = microtime_float();

        $this->ModelBase                   = new ModelBase;
        $this->CityBattlePlayer            = new CityBattlePlayer;
        $this->CityBattlePlayerArmy        = new CityBattlePlayerArmy;
        $this->CityBattlePlayerArmyUnit    = new CityBattlePlayerArmyUnit;
        $this->CityBattlePlayerGeneral     = new CityBattlePlayerGeneral;
        $this->CityBattlePlayerMasterskill = new CityBattlePlayerMasterskill;

        $CityBattleRound  = new CityBattleRound;
        $CityBattleSign   = new CityBattleSign;
        $CityBattle       = new CityBattle;
        $currentRoundInfo = $CityBattleRound->getCurrentRoundInfo();

        if(!$currentRoundInfo || $currentRoundInfo['status']!=CityBattleRound::SIGN_NORMAL) {
            log4task('当前无城战或city_battle_round.status状态不为'.CityBattleRound::SIGN_NORMAL.",当前状态为 ".@$currentRoundInfo['status']);
            return;
        } else {
            $roundId = $currentRoundInfo['id'];
            $CityBattleRound->setRoundStatus(CityBattleRound::SIGN_NORMAL, CityBattleRound::SELECT_PLAYER);
            log4task('关闭报名！状态从'.CityBattleRound::SIGN_NORMAL.'改到'.CityBattleRound::SELECT_PLAYER);
        }

        //获取所有battleId
        $sql1 = "SELECT DISTINCT battle_id FROM city_battle_sign WHERE round_id={$roundId};";
        log4task($sql1);
        $battleIdArr = $CityBattleSign->sqlGet($sql1);
        foreach($battleIdArr as $battle) {//循环次数等于城池的数
            $_battleId = $battle['battle_id'];
            $cityBattle = $CityBattle->getBattle($_battleId);
            $limitNum = $cityBattle['max_num'];

            $this->CityBattlePlayer->battleId            = $_battleId;
            $this->CityBattlePlayerArmy->battleId        = $_battleId;
            $this->CityBattlePlayerArmyUnit->battleId    = $_battleId;
            $this->CityBattlePlayerGeneral->battleId     = $_battleId;
            $this->CityBattlePlayerMasterskill->battleId = $_battleId;

            log4task('START-------battle_id='.$_battleId);
            $allCamp  = (new CountryCampList)->dicGetAllId();
            foreach($allCamp as $campId) {//阵营数
                log4task('###camp_id='.$campId." Data");
                $sql2 = "SELECT player_id, server_id FROM city_battle_sign WHERE round_id={$roundId} AND battle_id={$_battleId} AND camp_id={$campId} ORDER BY sign_type ASC, general_power DESC, id ASC LIMIT {$limitNum};";
                log4cli($sql2);
                $thisCampSignedPlayers = $CityBattleSign->sqlGet($sql2);
                (new CityBattleCommonLog)->add($_battleId, 0, $campId, "[round_id={$roundId}]".json_encode($thisCampSignedPlayers));
                log4task('copying...');
                foreach($thisCampSignedPlayers as $signedPlayer) {//每场报名数-固定 <=30人
                	echo "<", $signedPlayer['player_id'];
                    $this->cpDataWhenMatching($signedPlayer['player_id'], $signedPlayer['server_id'], $_battleId, $roundId);
                    echo "> ";
                }
                log4task('copying...End');
                log4task('###camp_id='.$campId." End");
            }
            log4task('END-------battle_id='.$_battleId);
        }
        $CityBattleRound->setRoundStatus(CityBattleRound::SELECT_PLAYER, CityBattleRound::SELECT_PLAYER_FINISH);
        log4task('脚本结束！状态从'.CityBattleRound::SELECT_PLAYER.'改到'.CityBattleRound::SELECT_PLAYER_FINISH);
        $subTimeExec = microtime_float() - $startTimeExec;
        log4task('Electing...End！exec_time:'.$subTimeExec);

        log4task('Start Match0 ...');
        $this->match0();
        log4task('End Match0 !');

        $this->clearTitle();//清除羽林军称号buff


    }
    /**
     * 相关数据copy
     *
     * @param $playerId
     * @param $serverId
     * @param $battleId
     */
    public function cpDataWhenMatching($playerId, $serverId, $battleId, $roundId){
        $generalIdList = $this->ModelBase->getByServer($serverId, 'PlayerInfo', 'getGeneralIdList', [$playerId, true]);
        $generals      = $generalIdList['army'][0];
        if (!empty($generalIdList['army'][1])) {
            $generals = array_merge($generals, $generalIdList['army'][1]);
        }
        $army1CpData = $army2CpData = [];
        foreach ($generalIdList['army'][0] as $v) {
            $army1CpData[] = [$v, 0, 0];
        }
        foreach ($generalIdList['army'][1] as $v) {
            $army2CpData[] = [$v, 0, 0];
        }
        $this->CityBattlePlayerArmy->find(['battle_id=' . $battleId . ' and player_id=' . $playerId])->delete();
        $this->CityBattlePlayerArmyUnit->find(['battle_id=' . $battleId . ' and player_id=' . $playerId])->delete();
        $this->CityBattlePlayer->find(['battle_id=' . $battleId . ' and player_id=' . $playerId])->delete();
        $this->CityBattlePlayerGeneral->find(['battle_id=' . $battleId . ' and player_id=' . $playerId])->delete();
        $this->CityBattlePlayerMasterskill->find(['battle_id=' . $battleId . ' and player_id=' . $playerId])->delete();

        $this->CityBattlePlayer->cpData($playerId, $roundId, $serverId);
        $this->CityBattlePlayerGeneral->cpData($playerId, $serverId, $generals);//general必须在army之前拷贝
        $this->CityBattlePlayerMasterskill->cpData($playerId, $battleId, $generalIdList['skill']);
        if (!empty($army1CpData))
            $this->CityBattlePlayerArmy->addByData($playerId, $army1CpData);
        if (!empty($army2CpData))
            $this->CityBattlePlayerArmy->addByData($playerId, $army2CpData);
    }

    /**
     * 城战奖励 only for roundAwardAction
     *
     * @param        $ModelBase
     * @param        $serverId
     * @param        $playerId
     * @param        $normalItem
     * @param        $data
     *                   $data['in_flag'] = 1;//1：城内 0：城外
     *                   $data['camp_id'] = 1;//阵营
     *                   $data['city_id'] = 1;//城池
     *                   $data['is_win'] = 1;//1：胜利 0：失败
     * @param string $log
     */
    private function sendAward($ModelBase, $serverId, $playerId, $normalItem, $data, $log=''){
        $ModelBase->execByServer($serverId, 'PlayerMail', 'sendSystem', [$playerId, PlayerMail::TYPE_CB_AWARD_NORMAL, '', '', 0, $data, $normalItem, '城战奖励-' . $log]);

    }
    /**
     * 城战奖励结算
     * php cli.php city_battle roundAward
     */
    public function roundAwardAction(){
        set_time_limit(0);
        log4task('Awarding...');
        $startTimeExec = microtime_float();

        $CityBattleRound  = new CityBattleRound;
        $CityBattle       = new CityBattle;
        $CityBattlePlayer = new CityBattlePlayer;
        $ModelBase        = new ModelBase;
        $PlayerMail       = new PlayerMail;
        $CountryCityMap   = new CountryCityMap;

        $currentRoundInfo = $CityBattleRound->getCurrentRoundInfo();
        if(!$currentRoundInfo || $currentRoundInfo['status']!=CityBattleRound::CLAC_REWARD) {
            log4task('当前无城战或city_battle_round.status状态不为'.CityBattleRound::CLAC_REWARD);
            return;
        }
        $roundId           = $currentRoundInfo['id'];
        $battleList        = $CityBattle->getRoundBattleList($roundId);
        $CountryBattleDrop = new CountryBattleDrop;
        $allBasicDrop      = $CountryBattleDrop->dicGetAll();
        $allBasicDrop      = Set::combine($allBasicDrop, '{n}.id', '{n}');
        /*奖励类型：
        1、城门战奖励（失败阵营获得）
        2、城内战奖励胜利（攻击方获得）
        3、城内战奖励失败（攻击方获得）
        4、城内战奖励胜利（防守方获得）
        5、城内战奖励失败（防守方获得）
        6、羽林军称号奖励
        */
        $defeatedDrop_1st     = $allBasicDrop[1]['drop'];
        $atk_winnerDrop_2nd   = $allBasicDrop[2]['drop'];
        $atk_defeatedDrop_2nd = $allBasicDrop[3]['drop'];
        $def_winnerDrop_2nd   = $allBasicDrop[4]['drop'];
        $def_defeatedDrop_2nd = $allBasicDrop[5]['drop'];

        $normalItem_defeatedDrop_1st     = $PlayerMail->newItemByDrop(0, [$defeatedDrop_1st]);
        $normalItem_atk_winnerDrop_2nd   = $PlayerMail->newItemByDrop(0, [$atk_winnerDrop_2nd]);
        $normalItem_atk_defeatedDrop_2nd = $PlayerMail->newItemByDrop(0, [$atk_defeatedDrop_2nd]);
        $normalItem_def_winnerDrop_2nd   = $PlayerMail->newItemByDrop(0, [$def_winnerDrop_2nd]);
        $normalItem_def_defeatedDrop_2nd = $PlayerMail->newItemByDrop(0, [$def_defeatedDrop_2nd]);

        $allCamp = (new CountryCampList)->dicGetAllId();//所有阵营
        log4task('城战奖励发放...');
        foreach($battleList as $cityBattle) {
            $battleId          = (int)$cityBattle['id'];
            $cityId            = (int)$cityBattle['city_id'];
            log4task("city_id=".$cityId."开始。。。");
            $cityCampId        = $cityBattle['camp_id'];
            $attackCampId      = $cityBattle['attack_camp'];
            $defendCampId      = $cityBattle['defend_camp'];
            $defeated1stCampId = array_values(array_diff($allCamp, [$attackCampId, $defendCampId]))[0];

            $winCampId = $cityBattle['win_camp'];
            //阵营积分结算
            log4task('阵营积分结算...');
            $cityMap = $CountryCityMap->dicGetOne($cityId);
            $point   = $cityMap['point'];
            (new Camp)->updateAll(['camp_score'=>"camp_score+{$point}"], ['id'=>$winCampId]);
            log4task("camp_id={$winCampId},camp_score+={$point}");
            log4task('阵营积分结算 ok');

            //case 失败方的奖励
            $sql0 = "select server_id, player_id from city_battle_player where round_id={$roundId} and camp_id={$defeated1stCampId} and battle_id={$battleId} and score<>0;";
            $defeatedPlayers_1st = $CityBattlePlayer->sqlGet($sql0);
            log4task('失败方的奖励');
            log4task($defeatedPlayers_1st);
            foreach($defeatedPlayers_1st as $v) {
                $mailData = [
                    'in_flag'     => 0,
                    'city_id'     => $cityId,
                    'is_win'      => 0,
                    'is_attacker' => 1,
                ];
                $this->sendAward($ModelBase, $v['server_id'], $v['player_id'], $normalItem_defeatedDrop_1st, $mailData, "城门战失败方奖励[round_id={$roundId} and camp_id={$defeated1stCampId}]");
            }

            if($cityCampId==0) {//无防守方
                //获胜攻击方
                $sql1_1 = "select server_id, player_id from city_battle_player where round_id={$roundId} and camp_id={$winCampId} and battle_id={$battleId}  and score<>0 ;";
                $winner_2nd = $CityBattlePlayer->sqlGet($sql1_1);
                log4task('无防守-获胜攻击方的奖励');
                log4task($winner_2nd);
                foreach($winner_2nd as $v) {
                    $mailData = [
                        'in_flag'     => 1,
                        'city_id'     => $cityId,
                        'is_win'      => 1,
                        'is_attacker' => 1,
                    ];
                    $this->sendAward($ModelBase, $v['server_id'], $v['player_id'], $normalItem_atk_winnerDrop_2nd, $mailData, "空城-城内战获胜方奖励[round_id={$roundId} and camp_id={$winCampId}]");
                }
                $defeated2ndCampId = ($winCampId==$attackCampId) ? $defendCampId : $attackCampId;
                //获败攻击方
                $sql1_2 = "select server_id, player_id from city_battle_player where round_id={$roundId} and camp_id={$defeated2ndCampId} and battle_id={$battleId}  and score<>0 ;";
                $defeated_2nd = $CityBattlePlayer->sqlGet($sql1_2);
                log4task('无防守-获败攻击方的奖励');
                log4task($defeated_2nd);
                foreach($defeated_2nd as $v) {
                    $mailData = [
                        'in_flag'     => 1,
                        'city_id'     => $cityId,
                        'is_win'      => 0,
                        'is_attacker' => 1,
                    ];
                    $this->sendAward($ModelBase, $v['server_id'], $v['player_id'], $normalItem_atk_defeatedDrop_2nd, $mailData, "空城-城内战获败方奖励[round_id={$roundId} and camp_id={$defeated2ndCampId}]");
                }
            } else {
                if($winCampId==$attackCampId) {//攻击方获胜
                    $sql2_1 = "select server_id, player_id from city_battle_player where round_id={$roundId} and camp_id={$attackCampId} and battle_id={$battleId}  and score<>0 ;";//胜
                    $winner_2nd = $CityBattlePlayer->sqlGet($sql2_1);
                    log4task('获胜攻击方的奖励');
                    log4task($winner_2nd);
                    foreach($winner_2nd as $v) {
                        $mailData = [
                            'in_flag'     => 1,
                            'city_id'     => $cityId,
                            'is_win'      => 1,
                            'is_attacker' => 1,
                        ];
                        $this->sendAward($ModelBase, $v['server_id'], $v['player_id'], $normalItem_atk_winnerDrop_2nd, $mailData, "非空城-城内战获胜方奖励[round_id={$roundId} and camp_id={$attackCampId}]");
                    }
                    $sql2_2 = "select server_id, player_id from city_battle_player where round_id={$roundId} and camp_id={$defendCampId} and battle_id={$battleId}  and score<>0 ;";//败
                    $defeated_2nd = $CityBattlePlayer->sqlGet($sql2_2);
                    log4task('获败防守方的奖励');
                    log4task($defeated_2nd);
                    foreach($defeated_2nd as $v) {
                        $mailData = [
                            'in_flag'     => 1,
                            'city_id'     => $cityId,
                            'is_win'      => 0,
                            'is_attacker' => 0,
                        ];
                        $this->sendAward($ModelBase, $v['server_id'], $v['player_id'], $normalItem_def_defeatedDrop_2nd, $mailData, "非空城-城内战获败方奖励[round_id={$roundId} and camp_id={$defendCampId}]");
                    }
                } elseif($winCampId==$defendCampId) {
                    $sql3_1 = "select server_id, player_id from city_battle_player where round_id={$roundId} and camp_id={$defendCampId} and battle_id={$battleId}  and score<>0 ;";//胜
                    $winner_2nd = $CityBattlePlayer->sqlGet($sql3_1);
                    log4task('获胜防守方的奖励');
                    log4task($winner_2nd);
                    foreach($winner_2nd as $v) {
                        $mailData = [
                            'in_flag'     => 1,
                            'city_id'     => $cityId,
                            'is_win'      => 1,
                            'is_attacker' => 0,
                        ];
                        $this->sendAward($ModelBase, $v['server_id'], $v['player_id'], $normalItem_def_winnerDrop_2nd, $mailData, "非空城-城内战获胜方奖励[round_id={$roundId} and camp_id={$defendCampId}]");
                    }
                    $sql3_2 = "select server_id, player_id from city_battle_player where round_id={$roundId} and camp_id={$attackCampId} and battle_id={$battleId}  and score<>0 ;";//败
                    $defeated_2nd = $CityBattlePlayer->sqlGet($sql3_2);
                    log4task('获败攻击方的奖励');
                    log4task($defeated_2nd);
                    foreach($defeated_2nd as $v) {
                        $mailData = [
                            'in_flag'     => 1,
                            'city_id'     => $cityId,
                            'is_win'      => 0,
                            'is_attacker' => 1,
                        ];
                        $this->sendAward($ModelBase, $v['server_id'], $v['player_id'], $normalItem_atk_defeatedDrop_2nd, $mailData, "非空城-城内战获胜方奖励[round_id={$roundId} and camp_id={$attackCampId}]");
                    }
                }
            }
        }
        log4task('城战奖励发放 ok');
        log4task('羽林军奖励发放...');
        //羽林军称号奖励
        $CountryBattleTitle = new CountryBattleTitle;
        $CityBattleRank     = new CityBattleRank;

        $allTitle           = $CountryBattleTitle->dicGetAll();
        $allTitleMap        = Set::combine($allTitle, '{n}.rank', '{n}');
        $MAX_AMOUNT         = 10;

        //清空city_battle_rank表
        $CityBattleRank->clearRankCache();
        CityBattleRank::find()->delete();

        foreach($allCamp as $vcampId) {
            $sql4 = "select player_id, server_id, score,nick,guild_name from city_battle_player where round_id={$roundId} and camp_id={$vcampId}  and score<>0 order by score desc limit {$MAX_AMOUNT}";
            log4task($sql4);
            $titlePlayer = $CityBattlePlayer->sqlGet($sql4);
            log4task('羽林军camp_id='.$vcampId);
            log4task(json_encode($titlePlayer));
            $amount = ($MAX_AMOUNT>count($titlePlayer)) ? count($titlePlayer) : $MAX_AMOUNT;
            for($i=0; $i<$amount; $i++) {
                $_rank = $i+1;
                //称号
                $rankData['round_id']   = $roundId;
                $rankData['camp_id']    = $vcampId;
                $rankData['player_id']  = $titlePlayer[$i]['player_id'];
                $rankData['server_id']  = $titlePlayer[$i]['server_id'];
                $rankData['rank']       = $_rank;
                $rankData['score']      = $titlePlayer[$i]['score'];
                $rankData['nick']       = $titlePlayer[$i]['nick'];
                $rankData['guild_name'] = $titlePlayer[$i]['guild_name'];
                $CityBattleRank->addNew($rankData);
                //buff
                $buffId      = $allTitleMap[$_rank]['buff_id'];
                if($buffId){
                    $ModelBase->execByServer($titlePlayer[$i]['server_id'], 'Drop', 'gain', [$titlePlayer[$i]['player_id'], $buffId, 1, '羽林军rank='.$_rank]);
                }
                //mail
                $normalItem =  $PlayerMail->newItemByDrop(0, [$allTitleMap[$_rank]['drop']]);
                $ModelBase->execByServer($titlePlayer[$i]['server_id'], 'PlayerMail', 'sendSystem', [$titlePlayer[$i]['player_id'], PlayerMail::TYPE_CB_AWARD_YU_LIN_JUN, '', '', 0, ['rank' =>$_rank, 'camp_id' =>$vcampId, 'score' => $titlePlayer[$i]['score']], $normalItem, '羽林军奖励rank=' . $_rank]);
            }

        }
        log4task('羽林军奖励发放 ok');
        log4task('更改状态到 '.CityBattleRound::FINISH);
        $CityBattleRound->setRoundStatus(CityBattleRound::CLAC_REWARD, CityBattleRound::FINISH);
        $subTimeExec = microtime_float() - $startTimeExec;
        $CityBattleRank->clearRankCache();
//        log4task('缓存里的数据为'.arr2str($CityBattleRank->getAllTitle()));
        log4task('Awarding...End! exec_time:'.$subTimeExec);
    }

    //改变城战状态脚本
    function changeRoundStatusAction($para = [0]){
        $to = $para[0];
        $from = $to-1;
        $CityBattleRound = new CityBattleRound;
        $CityBattleRound->setRoundStatus($from, $to);
    }

    //自动报名脚本 Test
    function autoSignAction($para=[1, 1]){
        $cityId = $para[0];
        $serverId = 1;
        $maxLimit = 10;

        $CityBattleSign = new CityBattleSign;
        $CityBattleRound = new CityBattleRound;
        $CityBattle = new CityBattle;
        $roundInfo = $CityBattleRound->getCurrentRoundInfo();
        $roundId = $roundInfo['id'];
        $roundStatus = $roundInfo['status'];
        $cityBattle = $CityBattle->getBattleByCityId($cityId);
        $cityBattleId = $cityBattle['id'];

        if($roundStatus==CityBattleRound::SIGN_FIRST){
            for($campId=1;$campId<5;$campId++){
                $ModelBase = new ModelBase;
                $playerListObj = $ModelBase->getByServer($serverId, 'Player', 'Find', [["camp_id=".$campId]]);
                $playerList = $playerListObj->toArray();
                $limit = 0;
                foreach($playerList as $player){
                    $re = $CityBattleSign->sign($player['id'], $cityBattleId, $roundId, $player['camp_id'], 1, $player['general_power']);
                    if($re){
                        echo "成功:".$player['nick']."\n";
                        if($limit>=$maxLimit){
                            break;
                        }else{
                            $limit++;
                        }
                    }else{
                        echo "失败:".$player['nick']."\n";
                    }
                }
            }
        }
    }
}
?>