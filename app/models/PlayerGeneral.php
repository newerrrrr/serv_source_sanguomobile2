<?php
class PlayerGeneral extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->_clearDataCache();
	}
	
	
	//获取指定玩家的指定武将
	public function getByGeneralId($playerId, $generalId){
		$ret = $this->findFirst(array('player_id='.$playerId.' and general_id='.$generalId));
		return ($ret ? $ret->toArray() : false);
	}
	
    /**
     * 获得所有武将ids
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function getGeneralIds($playerId){
		$playerGeneral = $this->getByPlayerId($playerId);
		return Set::extract('/general_id', $playerGeneral);
	}
	
	public function getGeneralIdsByArmyId($playerId, $armyId){
		$playerGeneral = $this->getByPlayerId($playerId);
		$ids = [];
		foreach($playerGeneral as $_d){
			if($_d['army_id'] == $armyId && $_d['general_id'])
				$ids[] = $_d['general_id'];
		}
		return $ids;
	}
	
	public function hasSameGeneral($playerId, $generalId){
		$ids = (new General)->getBySameRoot($generalId);
		$myIds = $this->getGeneralIds($playerId);
		if(array_intersect($myIds, $ids))
			return true;
		return false;
	}
	
    /**
     * 新增武将
     * 
     * @param <type> $playerId 
     * @param <type> $generalId 
     * 
     * @return <type>
     */
	public function add($playerId, $generalId, $armyId=0){
		if($this->getByGeneralId($playerId, $generalId))
			return false;
		$General = new General;
		$generalInfo = $General->getByGeneralId($generalId);
		$Equipment = new Equipment;
		$equip = $Equipment->getByOriginId($generalInfo['general_item_id']);
		$o = new self;
		$ret = $o->create(array(
			'player_id' => $playerId,
			'general_id' => $generalId,
			'exp' => 0,
			'lv' => 1,
			'weapon_id' => $equip['id'],
			'armor_id' => '0',
			'horse_id' => '0',
			'build_id' => '0',
			'army_id' => $armyId,
			'stay_start_time' => '0000-00-00 00:00:00',
			'status' => '0',
			'create_time' => date('Y-m-d H:i:s'),
			'update_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		$ret = $o->affectedRows();
		(new Player)->refreshPower($playerId, 'general_power');
		(new PlayerTarget)->refreshGeneralNum($playerId);
		//刷新新手任务
		(new PlayerTarget)->refreshBlueEquipNum($playerId);
		(new PlayerTarget)->refreshMaxStarEquipNum($playerId);
		(new PlayerGeneralBuff)->refreshAll($playerId);
		return $ret;
	}
	
	public function updateToGod($playerId, $generalId, $godGeneralId){
		$gs = (new GeneralStar)->getByGeneralId($godGeneralId);
		if(!$gs){
			return false;
		}
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'general_id'=>$godGeneralId,
			'skill_lv'=>1,
			'force_rate'=>$gs['general_force_growth'],
			'intelligence_rate'=>$gs['general_intelligence_growth'],
			'governing_rate'=>$gs['general_governing_growth'],
			'charm_rate'=>$gs['general_charm_growth'],
			'political_rate'=>$gs['general_political_growth'],
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), ["player_id"=>$playerId, 'general_id'=>$generalId, 'status'=>0]);
		$this->_clearDataCache($playerId, [$generalId,$godGeneralId]);
		(new PlayerGeneralBuff)->refreshAll($playerId);
		return $ret;
	}
	
	public function updateStar($playerId, $generalId, $star, $battleSkill=0){
		$gs = (new GeneralStar)->getByGeneralId($generalId, $star);
		if(!$gs){
			return false;
		}
		$now = date('Y-m-d H:i:s');
		$updateData = [
			'star_lv'=>$star,
			'force_rate'=>$gs['general_force_growth'],
			'intelligence_rate'=>$gs['general_intelligence_growth'],
			'governing_rate'=>$gs['general_governing_growth'],
			'charm_rate'=>$gs['general_charm_growth'],
			'political_rate'=>$gs['general_political_growth'],
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'",
		];
		if($battleSkill){
			$updateData['cross_skill_id_1'] = $battleSkill;
			$BattleSkill = new BattleSkill;
			$updateData['cross_skill_lv_1'] = $BattleSkill->dicGetOne($battleSkill)['battle_skill_defalut_level'];
		}
		$ret = $this->updateAll($updateData, ["player_id"=>$playerId, 'general_id'=>$generalId, 'status'=>0]);
		$this->_clearDataCache($playerId, [$generalId]);
		(new PlayerGeneralBuff)->refreshAll($playerId);
		return $ret;
	}
	
	public function replaceBattleSkill($playerId, $generalId, $id, $skillId){
		$now = date('Y-m-d H:i:s');
		$updateData = [
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'",
		];
		$updateData['cross_skill_id_'.$id] = $skillId;
		$BattleSkill = new BattleSkill;
		$updateData['cross_skill_lv_'.$id] = $BattleSkill->dicGetOne($skillId)['battle_skill_defalut_level'];
		$ret = $this->updateAll($updateData, ["player_id"=>$playerId, 'general_id'=>$generalId]);
		$this->_clearDataCache($playerId, [$generalId]);
		return $ret;
	}
	
    /**
     * 升级
     * 
     * @param <type> $lv 
     * 
     * @return <type>
     */
	/*public function lvup($lv){
		$this->update(array('lv'=>$lv));
		(new Player)->refreshPower($this->id, 'general_power');
		if($this->affectedRows())
			return true;
		return false;
	}
	*/
    /**
     * 更新装备
     * 
     * @param <type> $type 
     * @param <type> $equipId 
     * 
     * @return <type>
     */
	public function updateEquip($type, $equipId=0){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			$type=>$equipId, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->_clearDataCache();
		if($this->affectedRows())
			return true;
		return false;
	}
	
    /**
     * 增加exp
     * 
     * 
     * @return <type>
     */
	public function addExp($playerId, $generalId, $exp){
		$maxExp = (new GeneralExp)->getMaxExp();
		$ret = $this->sqlExec('update '.$this->getSource().' set exp=LEAST(exp+'.$exp.', '.$maxExp.'),lv=(select general_level from general_exp where general_exp<=exp order by id desc limit 1), update_time="'.date('Y-m-d H:i:s').'", rowversion="'.uniqid().'" where player_id='.$playerId.' and general_id='.$generalId);
		$this->_clearDataCache($playerId, [$generalId]);
		return $ret;
	}
	/*public function addExp($playerId, $generalIds, $exp, $expmax=0){
		$now = date('Y-m-d H:i:s');
		if(!$expmax){
			$Player = new Player;
			$player = $Player->getByPlayerId($playerId);
			$GeneralExp = new GeneralExp;
			$lvmax = min($GeneralExp->getMaxLv(), $player['level']+1);
			$expmax = $GeneralExp->lv2exp($lvmax)*1;
		}
		$ret = $this->sqlExec('UPDATE player_general set 
		exp= least('.$expmax.', exp+ '.$exp.'), 
		update_time="'.date('Y-m-d H:i:s').'",
		rowversion="'.uniqid().'"  
		where player_id='.$playerId.' and general_id in ('.join(',', $generalIds).')');
		$this->_clearDataCache($playerId, $generalIds);
		return $ret;
	}
	*/
	/*
	public function gainExp($playerId, $generalId=0, $time=0){
		if(!$time){
			$time = date('Y-m-d H:i:s');
		}
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$GeneralExp = new GeneralExp;
		$lvmax = min($GeneralExp->getMaxLv(), $player['level']+1);
		$expmax = $GeneralExp->lv2exp($lvmax)*1;
		$ret = $this->sqlExec('UPDATE player_general a set stay_start_time=date_add(stay_start_time, interval (@m:=GREATEST(0, TIMESTAMPDIFF(MINUTE, stay_start_time, "'.$time.'"))) MINUTE), 
		exp= least('.$expmax.', exp+@m*
			(select general_exp from build c where c.id=
				(select b.build_id from player_build b where id=a.build_id)
			)
		), 
		update_time="'.date('Y-m-d H:i:s').'",
		rowversion="'.uniqid().'"  
		where player_id='.$playerId.' '.($generalId ? ' and general_id='.$generalId : '').' and build_id > 0 and build_id not between 13001 and 13099');
		$this->_clearDataCache($playerId, ($generalId ? array($generalId) : $this->getGeneralIds($playerId)));
		return $ret;
	}
	*/
	public function gainExpFromStudy($playerId, $generalIds, $exp){
		if(!$time){
			$time = date('Y-m-d H:i:s');
		}
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$GeneralExp = new GeneralExp;
		$lvmax = min($GeneralExp->getMaxLv(), $player['level']+1);
		$expmax = $GeneralExp->lv2exp($lvmax)*1;
		$ret = $this->sqlExec('UPDATE player_general a set 
		exp= least('.$expmax.', exp+'.$exp.'), 
		update_time="'.date('Y-m-d H:i:s').'",
		rowversion="'.uniqid().'"  
		where player_id='.$playerId.' and general_id in ('.join(',', $generalIds).') ');
		if(!$ret)
			return false;
		$this->clearDataCache($playerId);
		return true;
	}
	
	public function updateBuild($buildId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'build_id'=>$buildId, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->_clearDataCache();
		return $ret;
	}
	
	public function updateSkill($nextLv){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'skill_lv'=>$nextLv, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->_clearDataCache();
		return $ret;
	}
	
	public function updateBattleSkill($id, $nextLv){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'cross_skill_lv_'.$id=>$nextLv, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->_clearDataCache();
		return $ret;
	}
	
	public function updateArmy($armyId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'army_id'=>$armyId, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->_clearDataCache();
		return $ret;
	}
	
	public function updateStudy(){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'status'=>2, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and status=0");
		$this->_clearDataCache();
		return $ret;
	}
	
    /**
     * 修改武将出征状态
     * 
     * @param <type> $playerId 
     * @param <type> $generalIds 
     * 
     * @return <type>
     */
	public function updateGooutByGeneralIds($playerId, $generalIds){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'status'=>1, 
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), ["player_id"=>$playerId, 'general_id'=>$generalIds, 'status'=>0]);
		if($ret != count($generalIds))
			return false;
		$this->_clearDataCache($playerId, $generalIds);
		return $ret;
	}
	
	/**
     * 修改武将回城状态
     * 
     * @param <type> $playerId 
     * @param <type> $generalIds 
     * 
     * @return <type>
     */
	public function updateReturnByGeneralIds($playerId, $generalIds){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'status'=>0, 
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), ["player_id"=>$playerId, 'general_id'=>$generalIds/*, 'status >'=>0*/]);
		if($ret != count($generalIds))
			return false;
		$this->_clearDataCache($playerId, $generalIds);
		return $ret;
	}
	
    /**
     * 获得最大出兵数
     * 
     * @return <type>
	 * todo 增加额外加成
     */
	public function getMaxBringSoldier(){
		$General = new General;
		$general = $General->getByGeneralId($this->general_id, $this->lv);
		if(!$general)
			return 0;
		$origin = $general['max_soldier'];
		$num = $origin;
		
		$position = 0;
		//校场等级增加带兵数
		$PlayerBuild = new PlayerBuild;
		if($playerBuild = $PlayerBuild->getByOrgId($this->player_id, 41)){
			$Build = new Build;
			$build = $Build->dicGetOne($playerBuild[0]['build_id']);
			$num += $build['output']['14'];
			$position = $playerBuild[0]['position'];
			//驻守武将加成
			/*$bb = $PlayerBuild->calcGeneralBuff($this->player_id, $playerBuild[0]['position']);
			$num += @$bb['general']['troop_max_plus']+@$bb['equip']['troop_max_plus'];
			$tmpPer = @$bb['general']['troop_max_plus_percent']+@$bb['equip']['troop_max_plus_percent'];
			*/
		}
		
		
		//buff
		$buffNum = (new PlayerBuff)->getPlayerBuff($this->player_id, 'troop_max_plus', $position);
		$num += $buffNum;
		
		$buffPercent = (new PlayerBuff)->getPlayerBuff($this->player_id, 'troop_max_plus_percent', $position);
		$num *= (1+$buffPercent/*+$tmpPer*/);
		
		//武将驻守技能
		/*$pb = $PlayerBuild->stayGeneralBuff($this->player_id, $playerBuild[0]['build_id']);
		if(is_array($pb)){
			$num += $pb['14'];
		}*/
		
		return ceil(round($num, 5));
	}

	/**
     * 获得最大武将招募数
     * 
     * @return <type>
	 * todo 增加额外加成
     */
	 public function getMaxGeneral($playerId, $buildId){
		$build = (new Build)->dicGetOne($buildId);
		$num = $build['output'][33];
		
		$num += (new PlayerBuff)->getPlayerBuff($playerId, 'recruit_general_limit_plus');
		
		return $num;
	}
	
	public function _clearDataCache($playerId=0, $generalIds=array()){
		if(!$playerId){
			$playerId = $this->player_id;
		}
		if(!$generalIds){
			$generalIds = array($this->general_id);
		}
		$this->clearDataCache($playerId);
		foreach($generalIds as $_id){
			$modelClassName = $this->getTotalAttrCacheKey($_id);
			Cache::delPlayer($playerId, $modelClassName);
		}
	}

    /**
     * 刷新驻守建筑数据
     * 
     * 
     * @return <type>
     */
	public function refreshBuild($playerId, $generalId){
		$data = $this->getByGeneralId($playerId, $generalId);
		if(!$data)
			return false;
		$PlayerBuild = new PlayerBuild;
		$ret = $PlayerBuild->findFirst(["id=".$data['build_id']]);
		if(!$ret)
			return false;
		$ret = $ret->toArray();
		//var_dump($ret);
		return $PlayerBuild->updateGeneral($ret['player_id'], $ret['position'], $ret['general_id_1']);
	}
	
	public function getTotalAttrCacheKey($generalId){
		return 'PlayerGeneralAttr:'.$generalId;
	}
	
	public function getTotalAttr($playerId, $generalId){
		if(!$playerId || !$generalId){
			return $this->_getTotalAttr0();
		}
		$modelClassName = $this->getTotalAttrCacheKey($generalId);
		$ret = Cache::getPlayer($playerId, $modelClassName);
		if(!$ret) {
			$ret = [];
			//获得玩家该武将
			$playerGeneral = $this->getByGeneralId($playerId, $generalId);
			if(!$playerGeneral)
				return false;
			$ret['PlayerGeneral'] = $playerGeneral;
			
			//获得武将字典信息
			$general = (new General)->getByGeneralId($generalId, $playerGeneral['lv']);
			if(!$general)
				return false;
			$ret['General'] = $general;
			
			//获得装备字典信息
			$ret['Equip'] = [];
			$equipArr = ['weapon', 'armor', 'horse', 'zuoji'];
			$Equipment = new Equipment;
			$redEquipSkillLvAdd = 0;//红色武器加成
			foreach($equipArr as $_i => $_ar){
				$_equip = null;
				if($playerGeneral[$_ar.'_id']){
					$_equip = $Equipment->dicGetOne($playerGeneral[$_ar.'_id']);
					if($_equip['combat_skill_id'] == $ret['General']['general_combat_skill']){
						$redEquipSkillLvAdd += $_equip['skill_level'];
					}
				}
				if(@$_equip){
					$ret['Equip'][] = $_equip;
				}else{
					$ret['Equip'][] = null;
				}
			}

			//获得装备buff
			$ret['buff'] = [];
			$buffs = [];
			$EquipSkill = new EquipSkill;
			foreach($ret['Equip'] as $_r){
				if(!$_r) continue;
				foreach($_r['equip_skill_id'] as $__r){
					$_equipSkill = $EquipSkill->dicGetOne($__r);
					if($_equipSkill){
						foreach($_equipSkill['skill_buff_id'] as $_sbi){
							$buffs[] = ['id'=>$_sbi, 'num'=>$_equipSkill['num']];
						}
					}
				}
			}

			//获得武将技能
			/*$GeneralSkill = new GeneralSkill;
			foreach($ret['General']['general_skill'] as $_r){
				$_generalSkill = $GeneralSkill->dicGetOne($_r);
				if($_generalSkill){
					$buffs[] = ['id'=>$_generalSkill['buff_id'], 'num'=>$_generalSkill['num']];
				}
			}
			*/
			//获得技能buff
			$Buff = new Buff;
			foreach($buffs as $_b){
				$_buff = $Buff->dicGetOne($_b['id']);
				if($_buff){
					if($_buff['buff_type'] == 1){
						$_val = floatval($_b['num']/DIC_DATA_DIVISOR);
					}else{
						$_val = $_b['num'];
					}
					@$ret['buff'][$_buff['name']] +=$_val;
				}
			}

			//计算属性和
			$attr = [];
			$attrArr = ['force', 'intelligence', 'governing', 'charm', 'political'];
			
			//计算等级星级属性加成
			foreach($attrArr as $_a){
				$ret['General']['general_'.$_a] += $ret['PlayerGeneral'][$_a.'_rate']*($ret['PlayerGeneral']['lv']-1);
				//属性+成长*（lv-1）
			}
			
			foreach($attrArr as $_a){
				$attr[$_a] = $ret['General']['general_'.$_a];
			}
			$attr['power'] = $ret['General']['power'];
			$attr['power'] += ($ret['PlayerGeneral']['lv']-1) * 95;
			foreach($ret['Equip'] as $_r){
				if(!$_r) continue;
				foreach($attrArr as $_a){
					$attr[$_a] += $_r[$_a];
				}
				$attr['power'] +=$_r['power'];
			}
			
			//武将天赋属性加成
			$generalTalentBuff = (new PlayerBuff)->getPlayerBuffs($playerId, ['general_force_inc','general_intelligence_inc','general_governing_inc','general_charm_inc','general_political_inc']);
			foreach($attrArr as $_a){
				$attr[$_a] += @$generalTalentBuff['general_'.$_a.'_inc'];
			}
			
			$ret['attr'] = $attr;
			
			//兵种power加成
			$soldierPower = [];
			if((new General)->isGod($generalId)){
				$a = 0.001;
			}else{
				$a = 0.0002;
			}
			$attackBuff = ($ret['attr']['force']>$ret['attr']['intelligence']?$ret['attr']['force']:$ret['attr']['intelligence'])*$a;
			$defendBuff = $ret['attr']['governing']*$a;
			$lifeBuff = $ret['attr']['governing']*$a;
			$soldierTypes = [1=>'infantry', 2=>'cavalry', 3=>'archer', 4=>'siege'];
			foreach($soldierTypes as $_k => $_t){
				$_attackBuff = $attackBuff+(empty($ret['buff']["{$_t}_atk_plus"])?0:$ret['buff']["{$_t}_atk_plus"]);
				$_defendBuff = $defendBuff+(empty($ret['buff']["{$_t}_def_plus"])?0:$ret['buff']["{$_t}_def_plus"]);
				$_lifeBuff = $lifeBuff+(empty($ret['buff']["{$_t}_life_plus"])?0:$ret['buff']["{$_t}_life_plus"]);
				$powerK = (1+$_attackBuff)*(1+($_defendBuff+$_lifeBuff)/2);
				$soldierPower[$_k] = [
					'attackBuff'=>$_attackBuff,
					'defendBuff'=>$_defendBuff,
					'lifeBuff'=>$_lifeBuff,
					'powerK'=>$powerK,
				];
			}
			$ret['soldierPower'] = $soldierPower;
			
			$CombatSkill = new CombatSkill;
			$skill = [];
			$_ret = $CombatSkill->getSkillValue($ret['General']['general_combat_skill'], $ret['PlayerGeneral']['skill_lv']+$redEquipSkillLvAdd, $ret['attr']);
			$ret['skill']['combat'] = $_ret;
			
			
			Cache::setPlayer($playerId, $modelClassName, $ret);
		}
		return $ret;
	}
	
	public function _getTotalAttr0(){
		$ret = ['buff'=>[], 'attr'=>['force'=>0, 'intelligence'=>0, 'governing'=>0, 'charm'=>0, 'political'=>0, 'power'=>0]];
		return $ret;
	}

	public function del($playerId, $generalId){
		$this->find(['player_id='.$playerId.' and general_id='.$generalId])->delete();
		$this->_clearDataCache($playerId, [$generalId]);
		(new Player)->refreshPower($playerId, 'general_power');
		(new PlayerTarget)->refreshGeneralNum($playerId);
		(new PlayerTarget)->refreshBlueEquipNum($playerId);
		(new PlayerTarget)->refreshMaxStarEquipNum($playerId);
		return $this->affectedRows();
	}

    /**
     * 获取其他服务器上的  (new PlayerGeneral)->getTotalAttr($playerId, $generalId);
     *
     * @param $targetServerId
     * @param $targetPlayerId
     * @param $targetGeneralId
     *
     * @return array|string
     */
	public function getPkGeneralBasicInfo($targetServerId, $targetPlayerId, $targetGeneralId){
        $targetPlayerGeneral = [];
        $targetGameServerHost = (new ServerList)->getGameServerIpByServerId($targetServerId);
        if ($targetGameServerHost) {
            $url          = $targetGameServerHost . '/api/getPlayerGeneralBasicInfo';
            $field        = ['player_id' => iEncrypt($targetPlayerId, 'PlayerGeneral'),'general_id' => iEncrypt($targetGeneralId, 'PlayerGeneral')];
            $targetPlayerGeneral = curlPost($url, $field);
            $targetPlayerGeneral = iDecrypt($targetPlayerGeneral);
        }
        return $targetPlayerGeneral;
    }
	
	public function getFitableSoldier($playerId, $generalId){
		//获取最大带兵数
		$playerGeneral = $this->getByGeneralId($playerId, $generalId);
		$bringSoldierMax = $this->assign($playerGeneral)->getMaxBringSoldier();
		
		//获取所有预备役
		$PlayerSoldier = new PlayerSoldier;
		$playerSoldier = $PlayerSoldier->getByPlayerId($playerId);
		
		//过滤武将优势兵种
		$soldierTypeFilter = [];
		$soldierType = (new General)->getByGeneralId($generalId)['soldier_type'];
		foreach($playerSoldier as $_ps){
			if(substr($_ps['soldier_id'], 0, 1) == $soldierType && $_ps['num'] > 0){
				$soldierTypeFilter[] = $_ps;
			}
		}
		if(!$soldierTypeFilter){
			return false;
		}
		
		//如果只有一种优势兵种
		if(count($soldierTypeFilter) == 1){
			return ['soldier_id'=>$soldierTypeFilter[0]['soldier_id'], 'num'=>min($bringSoldierMax, $soldierTypeFilter[0]['num'])];
		}
		
		//如果2种优势兵种
		$Soldier = new Soldier;
		$soldier1 = $Soldier->dicGetOne($soldierTypeFilter[0]['soldier_id']);
		$soldier2 = $Soldier->dicGetOne($soldierTypeFilter[1]['soldier_id']);
		if($soldier1['arm_type'] > $soldier2['arm_type']){
			$soldierTypeFilter = [$soldierTypeFilter[1], $soldierTypeFilter[0]];//把优先兵种放到前面
		}
		
		//如果2种都>=30%带兵数,选择数量多的士兵
		$limit = floor(0.3 * $bringSoldierMax);
		if($soldierTypeFilter[0]['num'] >= $limit && $soldierTypeFilter[1]['num'] >= $limit){
			if($soldierTypeFilter[0]['num'] >= $soldierTypeFilter[1]['num']){
				return ['soldier_id'=>$soldierTypeFilter[0]['soldier_id'], 'num'=>min($bringSoldierMax, $soldierTypeFilter[0]['num'])];
			}else{
				return ['soldier_id'=>$soldierTypeFilter[1]['soldier_id'], 'num'=>min($bringSoldierMax, $soldierTypeFilter[1]['num'])];
			}
		}elseif($soldierTypeFilter[0]['num'] >= $limit && $soldierTypeFilter[1]['num'] < $limit){//如果一种>=30%
			return ['soldier_id'=>$soldierTypeFilter[0]['soldier_id'], 'num'=>min($bringSoldierMax, $soldierTypeFilter[0]['num'])];
		}elseif($soldierTypeFilter[1]['num'] >= $limit && $soldierTypeFilter[0]['num'] < $limit){//如果一种>=30%
			return ['soldier_id'=>$soldierTypeFilter[1]['soldier_id'], 'num'=>min($bringSoldierMax, $soldierTypeFilter[1]['num'])];
		}else{//如果2种都小于30%
			return ['soldier_id'=>$soldierTypeFilter[0]['soldier_id'], 'num'=>min($bringSoldierMax, $soldierTypeFilter[0]['num'])];
		}
	}
}