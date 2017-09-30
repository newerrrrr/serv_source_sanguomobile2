<?php
//蓬莱兑换活动
class PlayerActivityExchange extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	const ACTID = 1026;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByActId($playerId, $activityConfigureId, $exchangeId){
		$ret = $this->findFirst(['player_id='.$playerId.' and activity_configure_id='.$activityConfigureId.' and exchange_id='.$exchangeId]);
		if(!$ret)
			return false;
		$ret = $ret->toArray();
		return $ret;
	}
	
    /**
     * 新增兑换记录
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function addCount($playerId, $activityConfigureId, $exchangeId, $num=1){
		$o = new self;
		if(!$o->find(array('player_id='.$playerId. ' and activity_configure_id='.$activityConfigureId.' and exchange_id='.$exchangeId))->toArray()){
			$ret = $o->create(array(
				'player_id' => $playerId,
				'activity_configure_id' => $activityConfigureId,
				'exchange_id' => $exchangeId,
				'num' => $num,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$now = date('Y-m-d H:i:s');
			$ret = $o->updateAll(array(
				'num' => 'num+'.$num,
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("player_id"=>$playerId, "activity_configure_id"=>$activityConfigureId, "exchange_id"=>$exchangeId));
		}
		$o->clearDataCache($playerId);
		return $o->affectedRows();
	}
		
}