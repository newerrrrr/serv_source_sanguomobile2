<?php
class CrossPlayerGeneral extends CrossModelBase{
	public $blacklist = array('player_id', 'total_attr', 'create_time', 'update_time', 'rowversion');
	public $battleId;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	
	//获取指定玩家的指定武将
	public function getByGeneralId($playerId, $generalId){
		$ret = $this->findFirst(array('battle_id='.$this->battleId.' and player_id='.$playerId.' and general_id='.$generalId));
		$ret = ($ret ? $ret->toArray() : false);
		if(!$ret)
			return false;
		return $ret;
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
	public function add($data){
		//$totalAttr = (new PlayerGeneral)->getTotalAttr($data['player_id'], $data['general_id']);
		$serverId = CrossPlayer::parsePlayerId($data['player_id'])['server_id'];
		$totalAttr = (new ModelBase)->getByServer($serverId, 'PlayerGeneral', 'getTotalAttr', [$data['player_id'], $data['general_id']]);
		$data = $this->getSkillVal($data, $totalAttr);
		$o = new self;
		$ret = $o->create(array(
			'battle_id' => $this->battleId,
			'player_id' => $data['player_id'],
			'general_id' => $data['general_id'],
			'exp' => $data['exp'],
			'lv' => $data['lv'],
			'star_lv' => $data['star_lv'],
			'weapon_id' => $data['weapon_id'],
			'armor_id' => $data['armor_id'],
			'horse_id' => $data['horse_id'],
			'zuoji_id' => $data['zuoji_id'],
			'skill_lv' => $data['skill_lv'],
			'build_id' => $data['build_id'],
			'army_id' => 0,
			'force_rate' => $data['force_rate'],
			'intelligence_rate' => $data['intelligence_rate'],
			'governing_rate' => $data['governing_rate'],
			'charm_rate' => $data['charm_rate'],
			'political_rate' => $data['political_rate'],
			'stay_start_time' => $data['stay_start_time'],
			'cross_skill_id_1' => @$data['cross_skill_id_1']*1,
			'cross_skill_lv_1' => @$data['cross_skill_lv_1']*1,
			'cross_skill_v1_1' => @$data['cross_skill_v1_1']*1,
			'cross_skill_v2_1' => @$data['cross_skill_v2_1']*1,
			'cross_skill_id_2' => @$data['cross_skill_id_2']*1,
			'cross_skill_lv_2' => @$data['cross_skill_lv_2']*1,
			'cross_skill_v1_2' => @$data['cross_skill_v1_2']*1,
			'cross_skill_v2_2' => @$data['cross_skill_v2_2']*1,
			'cross_skill_id_3' => @$data['cross_skill_id_3']*1,
			'cross_skill_lv_3' => @$data['cross_skill_lv_3']*1,
			'cross_skill_v1_3' => @$data['cross_skill_v1_3']*1,
			'cross_skill_v2_3' => @$data['cross_skill_v2_3']*1,
			'status' => 0,
			'total_attr' => json_encode($totalAttr),
			'create_time' => date('Y-m-d H:i:s'),
			'update_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		$ret = $o->affectedRows();
		//(new Player)->refreshPower($playerId, 'general_power');
		//(new PlayerTarget)->refreshGeneralNum($playerId);
		//刷新新手任务
		//(new PlayerTarget)->refreshBlueEquipNum($playerId);
		//(new PlayerTarget)->refreshMaxStarEquipNum($playerId);
		return $ret;
	}
	
	public function getSkillVal($data, &$totalAttr){
		$first = true;
		$changeAttr = false;
		$BattleSkill = new BattleSkill;
		$redEquipBattleSkillAdd = [];
		foreach($totalAttr['Equip'] as $_equip){
			if($_equip['battle_skill_id']){
				@$redEquipBattleSkillAdd[$_equip['battle_skill_id']] += $_equip['skill_level'];
			}
		}
		again:
		foreach([1, 2, 3] as $_i){
			if(!$data['cross_skill_id_'.$_i]) continue;
			$star = $data['star_lv'];
			$lv = $data['lv'];
			$skill_lv = $data['cross_skill_lv_'.$_i];
			$attr = $totalAttr['attr'];
			
			if(@$redEquipBattleSkillAdd[$data['cross_skill_id_'.$_i]]){
				$skill_lv += $redEquipBattleSkillAdd[$data['cross_skill_id_'.$_i]];
			}
			
			/*
			$force = $attr['attr']['force'];
			$intelligence = $attr['attr']['intelligence'];
			$governing = $attr['attr']['governing'];
			$charm = $attr['attr']['charm'];
			$political = $attr['attr']['political'];
			*/
			$bs = $BattleSkill->dicGetOne($data['cross_skill_id_'.$_i]);
//$bs['value_formula'] = $bs['value_formula_2'] = '500 + $force * 1 + $skill_lv * 25 + floor($skill_lv/10) * 250';
			eval('$data["cross_skill_v1_".$_i] = '.$bs['value_formula'].';');
			eval('$data["cross_skill_v2_".$_i] = '.$bs['value_formula_2'].';');
			
			//属性修改
			if($first){
				switch($data['cross_skill_id_'.$_i]){
					case 4://智谋:该武将在城战时智力增加%
						$totalAttr['attr']['intelligence'] *= 1+$data["cross_skill_v1_".$_i];
						$totalAttr['attr']['intelligence'] = floor($totalAttr['attr']['intelligence']);
						$changeAttr = true;
					break;
					case 5://勇武:该武将在城战时武力增加%
						$totalAttr['attr']['force'] *= 1+$data["cross_skill_v1_".$_i];
						$totalAttr['attr']['force'] = floor($totalAttr['attr']['force']);
						$changeAttr = true;
					break;
					case 6://统御:该武将在城战时统御增加%
						$totalAttr['attr']['governing'] *= 1+$data["cross_skill_v1_".$_i];
						$totalAttr['attr']['governing'] = floor($totalAttr['attr']['governing']);
						$changeAttr = true;
					break;
					case 7://政治:该武将在城战时政治增加%
						$totalAttr['attr']['political'] *= 1+$data["cross_skill_v1_".$_i];
						$totalAttr['attr']['political'] = floor($totalAttr['attr']['political']);
						$changeAttr = true;
					break;
					case 8://魅力:该武将在城战时魅力增加%
						$totalAttr['attr']['charm'] *= 1+$data["cross_skill_v1_".$_i];
						$totalAttr['attr']['charm'] = floor($totalAttr['attr']['charm']);
						$changeAttr = true;
					break;
				}
				
			}
		}
		if($first && $changeAttr){
			$first = false;
			goto again;
		}
		return $data;
	}
		
	public function updateArmy($armyId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'army_id'=>$armyId, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
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
		), ['battle_id'=>$this->battleId, "player_id"=>$playerId, 'general_id'=>$generalIds, 'status'=>0]);
		if($ret != count($generalIds))
			return false;
		$this->clearDataCache($playerId, $generalIds);
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
		), ['battle_id'=>$this->battleId, "player_id"=>$playerId, 'general_id'=>$generalIds/*, 'status >'=>0*/]);
		if($ret != count($generalIds))
			return false;
		$this->clearDataCache($playerId, $generalIds);
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
		$num = $general['max_soldier'];
		
		//buff
		$CrossPlayer = new CrossPlayer;
		$CrossPlayer->battleId = $this->battle_id;
		$player = $CrossPlayer->getByPlayerId($this->player_id);
		$buffNum = @$player['buff']['troop_max_plus']*1;
		$num += $buffNum;
		$num *= (1+@$player['buff']['troop_max_plus_percent']*1/DIC_DATA_DIVISOR);
		
		return $num;
	}
	
	public function getTotalAttrCacheKey($generalId){
		return 'CrossPlayerGeneralAttr:'.$generalId;
	}
	
	public function getTotalAttr($playerId, $generalId){
		if(!$playerId || !$generalId){
			return $this->_getTotalAttr0();
		}
		//获得玩家该武将
		$playerGeneral = $this->getByGeneralId($playerId, $generalId);
		if(!$playerGeneral)
			return false;
		$playerGeneral['total_attr'] = json_decode($playerGeneral['total_attr'], true);
		return $playerGeneral['total_attr'];
	}
	
	public function _getTotalAttr0(){
		$ret = ['buff'=>[], 'attr'=>['force'=>0, 'intelligence'=>0, 'governing'=>0, 'charm'=>0, 'political'=>0, 'power'=>0]];
		return $ret;
	}
	
	public function getFitableSoldier($playerId, $generalId){
		//获取最大带兵数
		$playerGeneral = $this->getByGeneralId($playerId, $generalId);
		$bringSoldierMax = $this->assign($playerGeneral)->getMaxBringSoldier();
		
		//获取所有预备役
		$PlayerSoldier = new CrossPlayerSoldier;
		$PlayerSoldier->battleId = $this->battleId;
		$playerSoldier = $PlayerSoldier->getByPlayerId($playerId);
		if(!$playerSoldier || !$playerSoldier[0]['num'])
			return false;
		$num = max(0, $playerSoldier[0]['num']);
		
		//过滤武将优势兵种
		$soldierType = (new General)->getByGeneralId($generalId)['soldier_type'];
		return ['soldier_id'=>(new CrossController)->soldierTypeIds[$soldierType][0], 'num'=>min($bringSoldierMax, $num)];
	}
	
	public function getSkillsByPlayer($playerId, $playerGenerals, $skillIds){
		if(!$playerGenerals && $playerId){
			$playerGenerals = $this->getByPlayerId($playerId);
		}
		$ret = array_combine($skillIds, array_fill(0, count($skillIds), [0, 0]));
		if(!$playerGenerals)
			$playerGenerals = [];
		foreach($playerGenerals as $_pg){
			foreach([1, 2, 3] as $_i){
				if(in_array($_pg['cross_skill_id_'.$_i], $skillIds)){
					@$ret[$_pg['cross_skill_id_'.$_i]][0] += $_pg['cross_skill_v1_'.$_i];
					@$ret[$_pg['cross_skill_id_'.$_i]][1] += $_pg['cross_skill_v2_'.$_i];
				}
			}
		}
		$BattleSkill = new BattleSkill;
		foreach($ret as $_k => &$_r){
			$maxValue = $BattleSkill->dicGetOne($_k)['value_max']*1;
			if($maxValue){
				$_r[0] = min($_r[0], $maxValue);
			}
		}
		unset($_r);
		return $ret;
	}
	
	public function getSkillsByPlayers($playerIds, $skillIds){
		$method = 'sum';
		if($method == 'sum'){
			$playerGenerals = [];
			foreach($playerIds as $_playerId){
				$playerGenerals = array_merge($playerGenerals, $this->getByPlayerId($_playerId));
			}
			$ret = $this->getSkillsByPlayer(null, $playerGenerals, $skillIds);
		}else{
			$ret = array_combine($skillIds, array_fill(0, count($skillIds), [0, 0]));
			foreach($playerIds as $_playerId){
				$_ret = $this->getSkillsByPlayer($_playerId, null, $skillIds);
				foreach($_ret as $_id => $_v){
					if(isset($ret[$_id])){
						$ret[$_id][0] = max($ret[$_id][0], $_v[0]);
						$ret[$_id][1] = max($ret[$_id][1], $_v[1]);
					}else{
						@$ret[$_id] = $_v;
					}
				}
			}
		}
		$BattleSkill = new BattleSkill;
		foreach($ret as $_k => &$_r){
			$maxValue = $BattleSkill->dicGetOne($_k)['value_max']*1;
			if($maxValue){
				$_r[0] = min($_r[0], $maxValue);
			}
		}
		unset($_r);
		return $ret;
	}
	
	public function getSkillsByArmies($armyIds, $skillIds){
		$method = 'sum';
		$ret1 = $this->find(['battle_id='.$this->battleId.' and army_id in ('.join(',', $armyIds).')'])->toArray();
		if($method == 'sum'){
			$ret = $this->getSkillsByPlayer(null, $ret1, $skillIds);
		}else{
			$ret2 = [];
			foreach($ret1 as $_r){
				$ret2[$_r['army_id']][] = $_r;
			}
			$ret = array_combine($skillIds, array_fill(0, count($skillIds), [0, 0]));
			foreach($ret2 as $_r){
				$_ret = $this->getSkillsByPlayer(null, $_r, $skillIds);
				foreach($_ret as $_id => $_v){
					if(isset($ret[$_id])){
						$ret[$_id][0] = max($ret[$_id][0], $_v[0]);
						$ret[$_id][1] = max($ret[$_id][1], $_v[1]);
					}else{
						@$ret[$_id] = $_v;
					}
				}
			}
		}
		$BattleSkill = new BattleSkill;
		foreach($ret as $_k => &$_r){
			$maxValue = $BattleSkill->dicGetOne($_k)['value_max']*1;
			if($maxValue){
				$_r[0] = min($_r[0], $maxValue);
			}
		}
		unset($_r);
		return $ret;
	}
	
    /**
     * 获取公会中某项属性最高值
     * 
     * @param <type> $guildId 
     * @param <type> $attr 
     * @param <type> $generalId 如果指定，则在该武将范围内查找
     * 
     * @return <type>
     */
	public function getMaxAttrByGuild($guildId, $attr, $generalId=0){
		$generals = $this->sqlGet('select player_id, general_id, total_attr from '.$this->getSource().' where battle_id='.$this->battleId.' and player_id in (select player_id from '.(new CrossPlayer)->getSource().' where battle_id='.$this->battleId.' and guild_id='.$guildId.')'.($generalId ? ' and general_id='.$generalId : '').' order by star_lv desc');
		$maxAttr = 0;
		$maxPlayerId = 0;
		$maxGeneralId = 0;
		foreach($generals as $_g){
			$v = json_decode($_g['total_attr'], true)['attr'][$attr];
			if($v > $maxAttr){
				$maxAttr = $v;
				$maxPlayerId = $_g['player_id'];
				$maxGeneralId = $_g['general_id'];
			}
			//$maxAttr = max($maxAttr, json_decode($_g['total_attr'], true)['attr'][$attr]);
		}
		return ['player_id'=>$maxPlayerId, 'general_id'=>$maxGeneralId, 'attr'=>$maxAttr];
	}
	
	/**
     * 从当前服抓取数据复制到pk服
     * 
     * 
     * @return <type>
     */
	public function cpData($playerId, $server_id=0, $generalIds=null){
		global $config;
        if($server_id!=0) {
            $serverId = $server_id;
        } else {
            $serverId = $config->server_id;
        }
		
		$this->find(['battle_id='.$this->battleId.' and player_id='.$playerId])->delete();
		//$PlayerGeneral = new PlayerGeneral;
		//$data = $PlayerGeneral->getByPlayerId($playerId);
		$data = (new ModelBase)->getByServer($serverId, 'PlayerGeneral', 'getByPlayerId', [$playerId]);
		foreach($data as $_d){
			if($generalIds !== null && !in_array($_d['general_id'], $generalIds)) continue;
			if(!$this->add($_d)){
				return false;
			}
		}
		$this->clearDataCache($playerId);
		return true;
	}
}