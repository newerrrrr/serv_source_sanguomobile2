<?php
//玩家累计充值活动
class PlayerActivityLogin extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	const ACTID = 1017;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByActId($playerId, $activityConfigureId){
		$ret = $this->findFirst(['player_id='.$playerId.' and activity_configure_id='.$activityConfigureId]);
		if(!$ret)
			return false;
		$ret = $ret->toArray();
		$ret['flag'] = parseArray($ret['flag']);
		return $ret;
	}
	
    /**
     * 新增天数
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function addDays($playerId, $addDay){
		//获取活动
		$activityConfigure = (new ActivityConfigure)->getCurrentActivity(self::ACTID);
		if(!$activityConfigure)
			return true;
		$activityConfigure = $activityConfigure[0];
		$activityConfigureId = $activityConfigure['id'];
		
		$o = new self;
		if(!$o->find(array('player_id='.$playerId. ' and activity_configure_id='.$activityConfigureId))->toArray()){
			$ret = $o->create(array(
				'player_id' => $playerId,
				'activity_configure_id' => $activityConfigureId,
				'days' => $addDay,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$now = date('Y-m-d H:i:s');
			$ret = $o->updateAll(array(
				'days' => 'days+'.$addDay,
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("player_id"=>$playerId, "activity_configure_id"=>$activityConfigureId));
		}
		$o->clearDataCache($playerId);
		return $o->affectedRows();
	}
		
	public function setFlag($playerId, $activityConfigureId, $day){
		$now = date('Y-m-d H:i:s');
		$ret = $this->getByActId($playerId, $activityConfigureId);
		if(!$ret)
			return false;
		if(in_array($day, $ret['flag'])){
			return false;
		}
		$ret['flag'][] = $day;
		$ret['flag'] = join(',', $ret['flag']);
		$ret = $this->updateAll(array(
			'flag' => "'".$ret['flag']."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId, "activity_configure_id"=>$activityConfigureId, 'rowversion'=>"'".$ret['rowversion']."'"));
		return $this->affectedRows();
	}
	
}