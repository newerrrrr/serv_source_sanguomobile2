<?php
//陷阱
class PlayerTrap extends ModelBase{
	public $blacklist = array('player_id');


	 /**
     * 获取玩家陷阱信息
     * 
     * @param <type> $playerId 
     * @param <type> $trapId 
     * 
     * @return <type>
     */
	public function getByTrapId($playerId, $trapId){
		$ret = $this->getByPlayerId($playerId);
		foreach ($ret as $key => $value) {
			if($trapId==$value['trap_id']){
				return $value;
			}
		}
	}

	/**
	 * 计算陷阱总数量
	 * @param  [type] $playerId [description]
	 * @return [type]           [description]
	 */
	public function getTotalNum($playerId){
		$result = 0;
		$ret = $this->getByPlayerId($playerId);
		foreach ($ret as $key => $value) {
			$result += $value['num'];
		}
		return $result;
	}

	/**
	 * 改变陷阱数量
	 * 
	 * @param [type] $playerId  [description]
	 * @param [type] $trapId [description]
	 * @param [type] $num       [大于0为加，小于0为减]
	 * 
	 */
	public function updateTrapNum($playerId, $trapId, $num){
		$lockKey = __CLASS__.':playerId=' .$playerId;
		$result = false;
		Cache::lock($lockKey);
		$playerTrapInfo = $this->getByTrapId($playerId, $trapId);
		if($num>0){
			if(!empty($playerTrapInfo)){
				$this->updateAll(['num'=>"num+".$num], ['id'=>$playerTrapInfo['id']]);
				$result = true;
			}else{
				$PlayerTrap = new PlayerTrap;
				$PlayerTrap->player_id = $playerId;
				$PlayerTrap->trap_id = $trapId;
				$PlayerTrap->num = $num;
				$PlayerTrap->save();
				$result = true;
			}
		}elseif($num<0){
			if(!empty($playerTrapInfo) && $playerTrapInfo['num']>=abs($num)){
				$this->updateAll(['num'=>"num+(".$num.")"], ['id'=>$playerTrapInfo['id']]);
				$result = true;
			}
		}
		if($result){
			$this->clearDataCache($playerId);
			$Player = new Player;
			$Player->refreshPower($playerId, 'trap_power');
		}
		Cache::unlock($lockKey);
		return $result;
	}
}