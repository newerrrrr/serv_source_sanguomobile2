<?php
//日志
class PlayerCommonLog extends ModelBase{
	public function add($playerId, $memo){
		$_memo = $memo;
		if(isset($_memo['type']))
			unset($_memo['type']);
		$o = new self;
		$ret = $o->create(array(
			'player_id' => $playerId,
			'type' => @$memo['type'],
			'memo' => json_encode($_memo, JSON_UNESCAPED_UNICODE),
			'create_time' => date('Y-m-d H:i:s'),
		));
	}
		
}