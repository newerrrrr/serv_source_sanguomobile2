<?php
/**
 * 玩家抽奖
 *
 */
class PlayerQuickMoney extends ModelBase{
	function countByPlayer($playerId){
		$re = $this->getByPlayerId($playerId);
		if(empty($re)){
			return 0;
		}else{
			return count($re);
		}
	}

	function createRecord($playerId, $gemNum){
		$self = new self;
		$self->player_id = $playerId;
		$self->gem_num = $gemNum;
		$self->create_time = date('Y-m-d H:i:s');
		$self->save();
		$self->clearDataCache($playerId);
		return true;
	}
}