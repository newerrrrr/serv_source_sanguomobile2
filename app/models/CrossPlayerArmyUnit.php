<?php
//军团配置
class CrossPlayerArmyUnit extends CrossModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public $battleId;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByArmyId($playerId, $armyId){
		$data = $this->getByPlayerId($playerId);
		$ret = array();
		foreach($data as $_pa){
			if($_pa['army_id'] == $armyId){
				$ret[] = $_pa;
			}
		}
		return $ret;
	}

	public function getByGeneralId($playerId, $generalId){
		$data = $this->getByPlayerId($playerId);
		$ret = array();
		foreach($data as $_pa){
			if($_pa['general_id'] == $generalId){
				$ret = $_pa;
			}
		}
		return $ret;
	}
		
    /**
     * 新增军团配置
     * 
     * @param <type> $playerId 
     * @param <type> $buildNextId 
     * @param <type> $originBuildId 
     * @param <type> $position 
     * @param <type> $resourceIn 
     * @param <type> $buildTime 
     * 
     * @return <type>
     */
	public function add($playerId, $armyId, $unit, $generalId, $soldierId, $soldierNum){
		if(!$soldierNum)
			$soldierId = 0;
		if($ret = $this->find(array('battle_id='.$this->battleId.' and player_id='.$playerId. ' and army_id='.$armyId. ' and unit='.$unit))->toArray()){
			return false;
		}
		$o = new self;
		$ret = $o->create(array(
			'battle_id' => $this->battleId,
			'player_id' => $playerId,
			'army_id' => $armyId,
			'unit' => $unit,
			'general_id' => $generalId,
			'soldier_id' => $soldierId,
			'soldier_num' => $soldierNum,
			'last_soldier_id' => $soldierId,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		$ret = $o->affectedRows();
		//(new Player)->refreshPower($playerId, 'army_power');
		return $ret;
	}
		
    /**
     * 更新配置
     * 
     * @param <type> $status 
     * 
     * @return <type>
     */
	public function updatePosition($generalId, $soldierId, $soldierNum){
		$lastSoldierId = $soldierId;
		if(!$soldierNum)
			$soldierId = 0;
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'general_id' => $generalId,
			'soldier_id' => $soldierId,
			'soldier_num' => $soldierNum,
			'last_soldier_id' => $lastSoldierId,
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		//(new Player)->refreshPower($this->player_id, 'army_power');
		return $ret;
	}
	
    /**
     * 扣除士兵
     * 
     * @param <type> $playerId 
     * @param <type> $armyId 
     * @param <type> $generalId 
     * @param <type> $subSoldierNum 
     * 
     * @return <type>
     */
	public function subSoldier($playerId, $generalId, $subSoldierNum){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'soldier_num' => 'greatest(0, soldier_num-'.$subSoldierNum.')',
			'soldier_id' => 'if(soldier_num=0, 0, soldier_id)',
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'",
		), ['battle_id'=>$this->battleId, "player_id"=>$playerId, 'general_id'=>$generalId]);
		$this->clearDataCache($playerId);
		//(new Player)->refreshPower($playerId, 'army_power');
		return $ret;
	}

	public function updateSoldier($playerId, $generalId, $soldierId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'soldier_id' => $soldierId,
			'last_soldier_id' => $soldierId,
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'",
		), ['battle_id'=>$this->battleId, "player_id"=>$playerId, 'general_id'=>$generalId]);
		$this->clearDataCache($playerId);
		//(new Player)->refreshPower($playerId, 'army_power');
		return $ret;
	}
	
    /**
     * 计算军团负重
     * 
     * @param <type> $playerId 
     * @param <type> $armyId 
     * 
     * @return <type>
     */
	/*
	public function calculateWeight($playerId, $armyId){
		$data = $this->getByArmyId($playerId, $armyId);
		if(!$data)
			return false;
		$weight = 0;
		$Soldier = new Soldier;
		$PlayerBuff = new PlayerBuff;
		$type2Buff = array(
			1 => 'infantry_carry_plus',
			2 => 'cavalry_carry_plus',
			3 => 'archer_carry_plus',
			4 => 'siege_carry_plus',
		);
		$buffArr = array();
		foreach($data as $_d){
			if(!$_d['soldier_id'] || !$_d['soldier_num']) continue;
			
			//计算士兵负重
			$_soldier = $Soldier->dicGetOne($_d['soldier_id']);
			//计算buff
			$_buff = 0;
			if(isset($buffArr[$_soldier['soldier_type']])){
				$_buff = $buffArr[$_soldier['soldier_type']];
			}else{
				//玩家buff
				$v = $PlayerBuff->getPlayerBuff($playerId, $type2Buff[$_soldier['soldier_type']]);
				
				$_buff = $buffArr[$_soldier['soldier_type']] = $v;
			}
			//武将buff
			$attr = (new PlayerGeneral)->getTotalAttr($playerId, $_d['general_id']);
			$_buff += @$attr['buff'][$type2Buff[$_soldier['soldier_type']]];
			$weight += floor(($_soldier['weight'] * $_d['soldier_num']) * (1+$_buff));
		}
		return $weight;
	}*/
	
	public function _clearDataCache($playerId=0){
		if(!$playerId){
			$playerId = $this->player_id;
		}
		$this->clearDataCache($playerId);
		$CrossPlayerArmy = new CrossPlayerArmy;
		$CrossPlayerArmy->battleId = $this->battleId;
		$CrossPlayerArmy->clearDataCache($playerId);
	}
	
	public function updateGeneral($playerId, $oldGeneralId, $newGeneralId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'general_id' => $newGeneralId,
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'",
		), ['battle_id'=>$this->battleId, "player_id"=>$playerId, 'general_id'=>$oldGeneralId]);
		$this->clearDataCache($playerId);
		//(new Player)->refreshPower($playerId, 'army_power');
		return $ret;
	}
	
	public function armyExist($playerId, $armyId){
		$pau = $this->getByArmyId($playerId, $armyId);
		$findFlag = false;
		foreach($pau as $_pau){
			if($_pau['soldier_id'] && $_pau['soldier_num'] > 0){
				$findFlag = true;
				break;
			}
		}
		if(!$findFlag){
			return false;
		}
		return true;
	}
	
	/**
     * 从当前服抓取数据复制到pk服
     * 
     * 
     * @return <type>
     */
	public function cpData($playerId, $server_id=0){
		global $config;
        if($server_id!=0) {
            $serverId = $server_id;
        } else {
            $serverId = $config->server_id;
        }
		
		$this->find(['battle_id='.$this->battleId.' and player_id='.$playerId])->delete();
		//$PlayerArmyUnit = new PlayerArmyUnit;
		//$data = $PlayerArmyUnit->getByPlayerId($playerId);
		$data = (new ModelBase)->getByServer($serverId, 'PlayerArmyUnit', 'getByPlayerId', [$playerId]);
		foreach($data as $_d){
			if(!$this->add($playerId, $_d['army_id'], $_d['unit'], $_d['general_id'], 0, 0)){
				return false;
			}
		}
		$this->_clearDataCache($playerId);
		return true;
	}
}