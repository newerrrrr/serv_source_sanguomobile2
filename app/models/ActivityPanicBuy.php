<?php
//秒杀活动商品
class ActivityPanicBuy extends ModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	const ACTID = 1027;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function getByActId($activityConfigureId, $buyId){
		$ret = $this->findFirst(['activity_configure_id='.$activityConfigureId." and buy_id=".$buyId]);
		if(!$ret)
			return false;
		$ret = $ret->toArray();
		return $ret;
	}
	
	public function getByActIdNow($activityConfigureId, $buyId){
	    $ret = $this->findFirst(['activity_configure_id='.$activityConfigureId." and buy_id=".$buyId." and begin_time<=now() and end_time>=now()"]);
	    if(!$ret)
	        return false;
	        $ret = $ret->toArray();
	        return $ret;
	}
	
	public function add($actId, $buyId, $price, $limit, $drop, $payDay, $beginTime, $endTime){
		$o = new self;
		$ret = $o->create(array(
			'activity_configure_id' => $actId,
			'buy_id' => $buyId,
			'price' => $price,
			'num' => 0,
			'limit' => $limit,
			'drop' => $drop,
			'pay_day' => $payDay,
			'begin_time' => $beginTime,
			'end_time' => $endTime,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		return $ret;
	}
	
    /**
     * 新增秒杀到的商品数量
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function addCount($activityConfigureId, $buyId, $num=1, $rowversion){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'num' => 'num+'.$num,
			'update_time'=>q($now),
			'rowversion'=>"'".uniqid()."'"
		), array("activity_configure_id"=>$activityConfigureId, "buy_id"=>$buyId, "limit >="=>'num+'.$num, 'begin_time <='=>q($now), 'end_time >='=>q($now), "rowversion"=>q($rowversion)));
		
		return $this->affectedRows();
	}
		
}