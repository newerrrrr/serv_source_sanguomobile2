<?php
//日志
class CityBattleCommonLog extends CityBattleModelBase{
	public function add($battleId, $playerId, $campId, $memo){
		if(is_array($memo)){
			$memo = json_encode($memo, JSON_UNESCAPED_UNICODE);
		}
		$o = new self;
		$ret = $o->create(array(
			'battle_id' => $battleId,
			'player_id' => $playerId,
			'camp_id' => $campId,
			'memo' => $memo,
			'create_time' => date('Y-m-d H:i:s'),
		));
	}
		
}