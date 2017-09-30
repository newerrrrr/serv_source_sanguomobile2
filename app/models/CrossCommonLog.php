<?php
//日志
class CrossCommonLog extends CrossModelBase{
	public function add($battleId, $playerId, $guildId, $memo){
		if(is_array($memo)){
			$memo = json_encode($memo, JSON_UNESCAPED_UNICODE);
		}
		$o = new self;
		$ret = $o->create(array(
			'battle_id' => $battleId,
			'player_id' => $playerId,
			'guild_id' => $guildId,
			'memo' => $memo,
			'create_time' => date('Y-m-d H:i:s'),
		));
	}
		
}