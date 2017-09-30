<?php
//建筑
class PlayerSoldier extends ModelBase{
	public $blacklist = array('player_id');


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
	 * 计算玩家士兵粮食消耗
	 * 
	 * @param  [type] $playerId [description]
	 * 
	 * @return int [description]
	 */
	public function getPlayerFoodOut($playerId){
		$ret = $this->getByPlayerId($playerId);
		$foodOut = 0;
		$Soldier = new Soldier;
		foreach ($ret as $key => $value) {
			$tmpSoldier = $Soldier->dicGetOne($value['soldier_id']);
			$foodOut += $tmpSoldier['consumption']*$value['num'];
		}
		//获取army中士兵
		$PlayerArmyUnit = new PlayerArmyUnit;
		$pau = $PlayerArmyUnit->getByPlayerId($playerId);
		foreach($pau as $_pau){
			if($_pau['soldier_id'] && $_pau['soldier_num']){
				$tmpSoldier = $Soldier->dicGetOne($_pau['soldier_id']);
				$foodOut += $tmpSoldier['consumption']*$_pau['soldier_num'];
			}
		}
		
		$PlayerBuff = new PlayerBuff;
		$foodOutBuff = $PlayerBuff->getPlayerBuff($playerId, 'food_out_debuff');//影响粮食消耗的buff
		return $foodOut*(1-$foodOutBuff)/10000;
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
		Cache::lock($lockKey);
		$playerSoldierInfo = $this->getBySoldierId($playerId, $soldierId);
		if($num>=0){
			if(!empty($playerSoldierInfo)){
				$this->updateAll(['num'=>"num+".$num], ['id'=>$playerSoldierInfo['id']]);
				$result = true;
			}else{
				$PlayerSoldier = new PlayerSoldier;
				$PlayerSoldier->player_id = $playerId;
				$PlayerSoldier->soldier_id = $soldierId;
				$PlayerSoldier->num = $num;
				$PlayerSoldier->create_time = date('Y-m-d H:i:s');
				$PlayerSoldier->save();
				$result = true;
			}
		}elseif($num<0){
			if(!empty($playerSoldierInfo)){
				$this->updateAll(['num'=>"GREATEST(0, num+(".$num."))"], ['id'=>$playerSoldierInfo['id']]);
				$result = true;
			}
		}
		if($result){
			$this->clearDataCache($playerId);
			$Player = new Player;
			$Player->refreshPower($playerId, 'army_power');
		}
		Cache::unlock($lockKey);
		return $result;
	}

	public function replaceSoldier($playerId, $soldierId1, $soldierId2, $noPowerFlag=false){
        $lockKey = __CLASS__.':playerId=' .$playerId;
        $result = false;
        Cache::lock($lockKey);
        $playerSoldierInfo1 = $this->getBySoldierId($playerId, $soldierId1);
        $playerSoldierInfo2 = $this->getBySoldierId($playerId, $soldierId2);
        if(!empty($playerSoldierInfo1) && empty($playerSoldierInfo2)){
            $this->updateAll(['soldier_id'=>$soldierId2], ['id'=>$playerSoldierInfo1['id']]);
            $result = true;
        }elseif(!empty($playerSoldierInfo1) && !empty($playerSoldierInfo2)){
            $this->updateAll(['num'=>0], ['id'=>$playerSoldierInfo1['id']]);
            $this->updateAll(['num'=>$playerSoldierInfo2['num']+$playerSoldierInfo1['num']], ['id'=>$playerSoldierInfo2['id']]);
            $result = true;
        }
        if($result){
            $this->clearDataCache($playerId);
            if(!$noPowerFlag) {
                $Player = new Player;
                $Player->refreshPower($playerId, 'army_power');
            }
        }
        Cache::unlock($lockKey);
        return $result;
    }
}