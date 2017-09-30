<?php
//军团
class CityBattlePlayerArmy extends CityBattleModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public $battleId;
	public $soldierTypeIds = [
		1 => [10019, 10020],
		2 => [20019, 20020],
		3 => [30019, 30020],
		4 => [40019, 40020],
	];
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByArmyId($playerId, $armyId){
		$playerArmy = $this->getByPlayerId($playerId);
		foreach($playerArmy as $_pa){
			if($_pa['id'] == $armyId){
				return $_pa;
			}
		}
		return false;
	}

	public function getByPositionId($playerId, $positionId){
		$playerArmy = $this->getByPlayerId($playerId);
		foreach($playerArmy as $_pa){
			if($_pa['position'] == $positionId){
				return $_pa;
			}
		}
		return false;
	}
		
    /**
     * 新增军团
     * 
     * @param <type> $playerId 
     * @param <type> $position 
     * 
     * @return <type>
     */
	public function add($playerId, $position,$leaderGeneralId=0, &$id=''){
		if($this->find(array("battle_id={$this->battleId} and player_id=".$playerId. ' and position='.$position))->toArray()){
			return false;
		}
		$o = new self;
		$ret = $o->create(array(
			//'id' => $id,
			'battle_id' => $this->battleId,
			'player_id' => $playerId,
			'position' => $position,
			'leader_general_id'=>$leaderGeneralId,
			'status' => 0,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		$this->id = $id = $o->id;
		return $o->affectedRows();
	}
		
    /**
     * 更新状态
     * 
     * @param <type> $status 
     * 
     * @return <type>
     */
	public function updateStatus($status){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'status'=>$status, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		return $ret;
	}
	
	public function updateGeneral($generalId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'leader_general_id'=>$generalId, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		return $ret;
	}
	
	public function updateFillTime(){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'fill_soldier_time'=>"'".$now."'", 
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), ["id"=>$this->id, "rowversion"=>"'".$this->rowversion."'"]);
		$this->clearDataCache();
		return $ret;
	}
	
    /**
     * 通过数据生成军团
     * 
     * @param <type> $playerId 
     * @param <type> $data example：[[generalId1,soldierId1,soldierNum1],[generalId2,soldierId2,soldierNum2]...]
     * 
     * @return <type>
     */
	public function addByData($playerId, $data){
		if(!$data || !@$data[0][0])
			return false;
		$leaderGeneralId = $data[0][0];
		$ret = $this->find(['player_id='.$playerId.' and battle_id='.$this->battleId])->toArray();
		$position = 0;
		foreach($ret as $_r){
			$position = max($position, $_r['position']);
		}
		$position++;
		$this->add($playerId, $position,$leaderGeneralId);
		$armyId = $this->id;
		
		$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
		$CityBattlePlayerArmyUnit->battleId = $this->battleId;
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$CityBattlePlayerGeneral->battleId = $this->battleId;
		$soldierTypeIds = $this->soldierTypeIds;
		$General = new General;
		foreach($data as $_i => $_d){
			$_playerGeneral = $CityBattlePlayerGeneral->getByGeneralId($playerId, $_d[0]);
			$_bringSoldierMax = $CityBattlePlayerGeneral->assign($_playerGeneral)->getMaxBringSoldier();
			$soldierType = $General->getByGeneralId($_d[0])['soldier_type'];
			$_soldierId = $soldierTypeIds[$soldierType][0];
			$CityBattlePlayerArmyUnit->add($playerId, $armyId, $_i+1, $_d[0], $_soldierId, $_bringSoldierMax, $_bringSoldierMax);
			$CityBattlePlayerGeneral->assign($_playerGeneral)->updateArmy($armyId);
		}
		return true;
	}
	
    /**
     * 从当前服抓取数据复制到pk服
     * 
     * 
     * @return <type>
     */
	 /*
	public function cpData($playerId, $server_id=0, $armyIds = null){
		global $config;
        if($server_id!=0) {
            $serverId = $server_id;
        } else {
            $serverId = $config->server_id;
        }
		//$PlayerArmy = new PlayerArmy;
		$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
		$CrossPlayerArmyUnit->battleId = $this->battleId;
		//$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerGeneral = new CrossPlayerGeneral;
		$PlayerGeneral->battleId = $this->battleId;
		$this->find(['battle_id='.$this->battleId.' and player_id='.$playerId])->delete();
		$CrossPlayerArmyUnit->find(['battle_id='.$this->battleId.' and player_id='.$playerId])->delete();
		//$data = $PlayerArmy->getByPlayerId($playerId);
		$data = (new ModelBase)->getByServer($serverId, 'PlayerArmy', 'getByPlayerId', [$playerId]);
		$pos = 1;
		foreach($data as $_d){
			if($armyIds !== null && !in_array($_d['id'], $armyIds)) continue;
			if(!$this->add($playerId, $pos, $_d['leader_general_id'], $id)){
				return false;
			}
			//$_pau = $PlayerArmyUnit->getByArmyId($playerId, $_d['id']);
			$_pau = (new ModelBase)->getByServer($serverId, 'PlayerArmyUnit', 'getByArmyId', [$playerId, $_d['id']]);
			if(!$_pau){
				$this->find(['player_id='.$playerId.' and position='.$_d['position']])->delete();
				continue;
			}else{
				foreach($_pau as $_p){
					$CrossPlayerArmyUnit->add($playerId, $id, $_p['unit'], $_p['general_id'], 0, 0);
					$_playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_p['general_id']);
					if(!$_playerGeneral){
						return false;
					}
					if(!$PlayerGeneral->assign($_playerGeneral)->updateArmy($id)){
						return false;
					}
				}
			}
			$pos++;
		}
		$this->clearDataCache($playerId);
		$CrossPlayerArmyUnit->_clearDataCache($playerId);
		return true;
	}*/
}