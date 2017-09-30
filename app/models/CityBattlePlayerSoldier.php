<?php
class CityBattlePlayerSoldier extends CityBattleModelBase{
	public $blacklist = array('player_id');
	public $battleId;
	
	public function afterSave(){
		$this->clearDataCache();
	}

	 /**
     * 获取玩家士兵信息
     * 
     * @param <type> $playerId 
     * @param <type> $soldierId 
     * 
     * @return <type>
     */
	public function getBySoldierId($playerId, $soldierId){
		$ret = $this->getByPlayerId($playerId);
		foreach ($ret as $key => $value) {
			if($soldierId==$value['soldier_id']){
				return $value;
			}
		}
		return false;
	}

	/**
	 * 改变士兵数量
	 * 
	 * @param [type] $playerId  [description]
	 * @param [type] $soldierId [description]
	 * @param [type] $num       [大于0为加，小于0为减]
	 * 
	 */
	public function updateSoldierNum($playerId, $soldierId, $num){
		$lockKey = __CLASS__.':playerId=' .$playerId;
		$result = false;
		Cache::lock($lockKey, 10, CACHEDB_PLAYER, 60, 'CityBattle');
		$playerSoldierInfo = $this->getBySoldierId($playerId, $soldierId);
		if($num>=0){
			if(!empty($playerSoldierInfo)){
				$this->updateAll(['num'=>"num+".$num], ['id'=>$playerSoldierInfo['id']]);
				$result = true;
			}else{
				$o = new self;
				$o->player_id = $playerId;
				$o->battle_id = $this->battleId;
				$o->soldier_id = $soldierId;
				$o->num = $num;
				$o->create_time = date('Y-m-d H:i:s');
				$o->save();
				$result = true;
			}
		}elseif($num<0){
			if(!empty($playerSoldierInfo) && $playerSoldierInfo['num']>=$num){
				$this->updateAll(['num'=>"GREATEST(0, num+(".$num."))"], ['id'=>$playerSoldierInfo['id']]);
				$result = true;
			}
		}
		if($result){
			$this->clearDataCache($playerId);
			//$Player = new Player;
			//$Player->refreshPower($playerId, 'army_power');
		}
		Cache::unlock($lockKey, CACHEDB_PLAYER, 'CityBattle');
		return $result;
	}
}