<?php
//天赋
class PlayerTalent extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	//获取指定玩家的指定天赋
	public function getAllByTalentId($playerId, $talentId){
		$ret = $this->findFirst(array('player_id='.$playerId.' and talent_id='.$talentId));
		return ($ret ? $ret->toArray() : false);
	}
	
	//新增天赋
	public function add($playerId, $talentId){
		if($this->getAllByTalentId($playerId, $talentId))
			return false;
		$ret = $this->create(array(
			'player_id' => $playerId,
			'talent_id' => $talentId,
			'create_time' => date('Y-m-d H:i:s'),
			'update_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
	
	public function lvup($newTalentId){
		$ret = $this->saveAll(array('talent_id'=>$newTalentId, 'update_time'=>date('Y-m-d H:i:s'), 'rowversion'=>uniqid()), "player_id={$this->player_id} and talent_id={$this->talent_id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		return $ret;
	}
	
	//计算已花费天赋点
	public static function getCostPoint($playerTalents){
		$Talent = new Talent;
		$talents = $Talent->dicGetAll();
		//归类整理
		$_playerTalents = array();
		foreach($playerTalents as $_t){
			$_type = $talents[$_t['talent_id']]['talent_type_id'];
			$_level = $talents[$_t['talent_id']]['level_id'];
			$_playerTalents[$_type] = $_level;
		}
		
		$point = 0;
		foreach($talents as $_t){
			if(!@$_playerTalents[$_t['talent_type_id']]) continue;
			if($_t['level_id'] > $_playerTalents[$_t['talent_type_id']]) continue;
			$point += $_t['cost'];
		}
		return $point;
	}
	
	public function reset($playerId){
		 self::find("player_id={$playerId}")->delete();
		 $this->clearDataCache($playerId);
	}
	/*public function clearDataCache(){
		$ret = $this->toArray();
		//Cache::db()->delete(getDataCacheKey($ret['player_id'], 'Talent'));
		Cache::delPlayer($ret['player_id'], __CLASS__);
	}*/
}