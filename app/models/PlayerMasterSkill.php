<?php
//玩家主动技能
class PlayerMasterSkill extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByTalentId($playerId, $talentId){
		$data = $this->getByPlayerId($playerId);
		foreach($data as $_data){
			if($_data['talent_id'] == $talentId){
				return $_data;
			}
		}
		return false;
	}
	
    /**
     * 开启
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function enable($playerId, $talentId){
		if(!$this->find(array('player_id='.$playerId. ' and talent_id='.$talentId))->toArray()){
			$ret = $this->create(array(
				'player_id' => $playerId,
				'talent_id' => $talentId,
				'next_time' => '1970-01-01 00:00:00',
				'use_time' => '1970-01-01 00:00:00',
				'enable' => 1,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$now = date('Y-m-d H:i:s');
			$ret = $this->updateAll(array(
				'enable' => 1,
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("player_id"=>$playerId, "talent_id"=>"'".$talentId."'"));
		}
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
	
	public function resetSkill($playerId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'enable' => 0,
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId));
		
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
		
    /**
     * 丢弃道具
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function useSkill($playerId, $talentId, $second, $effectSecond=0){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'next_time' => "'".date('Y-m-d H:i:s', time()+$second)."'",
			'effect_time' => "'".date('Y-m-d H:i:s', time()+$effectSecond)."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId, "talent_id"=>"'".$talentId."'"));
		
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
	
	public function upEffect($playerId, $talentId, $effectTime){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'effect_time' => "'".$effectTime."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId, "talent_id"=>"'".$talentId."'"));
		
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
}