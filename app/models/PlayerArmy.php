<?php
//军团
class PlayerArmy extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
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
	public function add($playerId, $position,$leaderGeneralId=0){
		if($this->find(array('player_id='.$playerId. ' and position='.$position))->toArray()){
			return false;
		}
		$ret = $this->create(array(
			'player_id' => $playerId,
			'position' => $position,
			'leader_general_id'=>$leaderGeneralId,
			'status' => 0,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
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
	
	public function getPower($playerId, $armyId){
		$data = (new PlayerArmyUnit)->getByArmyId($playerId, $armyId);
		return $this->_getPower($data);
	}
	
	public function _getPower($armyData){
		//var_dump($armyData);
		$PlayerGeneral = new PlayerGeneral;
		$Soldier = new Soldier;
		$power = 0;
		foreach($armyData as $_d){
			//获取power
			$_power = 0;
			$_ret = $PlayerGeneral->getTotalAttr($_d['player_id'], $_d['general_id']);
			/*if($_ret){
				$power += $_ret['attr']['power'];
			}*/
			if($_d['soldier_num']){
				$_soldier = $Soldier->dicGetOne($_d['soldier_id']);
				if($_soldier){
					$_power += ($_soldier['power'] * $_d['soldier_num']) / DIC_DATA_DIVISOR;
				}
				$_power *= $_ret['soldierPower'][$_soldier['soldier_type']]['powerK'];
			}
			$power += $_power;
		}
		return floor($power);
		
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
		$ret = $this->find(['player_id='.$playerId])->toArray();
		$position = 1;
		foreach($ret as $_r){
			$position = max($position, $_r['position']);
		}
		$position++;
		$this->add($playerId, $position,$leaderGeneralId);
		$armyId = $this->id;
		
		$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerGeneral = new PlayerGeneral;
		foreach($data as $_i => $_d){
			$PlayerArmyUnit->add($playerId, $armyId, $_i+1, $_d[0], $_d[1], $_d[2]);
			$_playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_d[0]);
			$PlayerGeneral->assign($_playerGeneral)->updateArmy($armyId);
		}
		return true;
	}
}