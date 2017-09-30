<?php
//玩家抢购活动
class PlayerActivityPanicBuy extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	const ACTID = 1027;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByActId($playerId, $activityConfigureId, $payDay){
		$ret = $this->findFirst(['player_id='.$playerId.' and activity_configure_id='.$activityConfigureId.' and date='.q($payDay)]);
		if(!$ret)
			return false;
		$ret = $ret->toArray();
		$ret['flag'] = parseArray($ret['flag']);
		return $ret;
	}
	
	/**
     * 新增充值数
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function addGem($playerId, $addGem){
		//获取活动
		$activityConfigure = (new ActivityConfigure)->getCurrentActivity(self::ACTID);
		if(!$activityConfigure)
			return true;
		$activityConfigure = $activityConfigure[0];
		$activityConfigureId = $activityConfigure['id'];
		$para = json_decode($activityConfigure['activity_para'], true);
		$today = date('Y-m-d');
		$find = false;
		foreach($para['reward'] as $_p){
			if($_p['time'] == $today){
				$find = true;
				break;
			}
		}
		if(!$find)
			return true;
		
		$o = new self;
		if(!$o->find(array('player_id='.$playerId. ' and activity_configure_id='.$activityConfigureId.' and date="'.$today.'"'))->toArray()){
			$ret = $o->create(array(
				'player_id' => $playerId,
				'activity_configure_id' => $activityConfigureId,
				'date'=>$today,
				'gem' => $addGem,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$now = date('Y-m-d H:i:s');
			$ret = $o->updateAll(array(
				'gem' => 'gem+'.$addGem,
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("player_id"=>$playerId, "activity_configure_id"=>$activityConfigureId, 'date'=>"'".$today."'"));
		}
		$o->clearDataCache($playerId);
		return $o->affectedRows();
	}
	
    public function setFlag($playerId, $activityConfigureId, $payDay, $buyId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->getByActId($playerId, $activityConfigureId, $payDay);
		if(!$ret)
			return false;
		$ret['flag'][] = $buyId;
		$ret['flag'] = join(',', $ret['flag']);
		$ret = $this->updateAll(array(
			'flag' => "'".$ret['flag']."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId, "activity_configure_id"=>$activityConfigureId, 'date'=>"'".$payDay."'", 'rowversion'=>"'".$ret['rowversion']."'"));
		return $this->affectedRows();
	}
		
}