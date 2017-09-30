<?php
/**
 * 激活码
 */
class PlayerCdk extends ModelBase{
	
	public function add($playerId, $cdk){
		if($this->findFirst(['player_id='.$playerId.' and cdk like "'.substr($cdk, 0, 2).'%"'])){
			return false;
		}
		$o = new self;
		$ret = $o->create(array(
			'player_id' => $playerId,
			'cdk' => $cdk,
			'create_time' => date('Y-m-d H:i:s'),
		));
		return true;
	}
	
}