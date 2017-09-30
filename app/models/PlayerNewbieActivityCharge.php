<?php
//新人玩家累计充值活动
class PlayerNewbieActivityCharge extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByPeriodId($playerId, $period){
		$ret = $this->findFirst(['player_id='.$playerId.' and period='.$period]);
		if(!$ret)
			return false;
		$ret = $ret->toArray();
		$ret['flag'] = parseArray($ret['flag']);
		return $ret;
	}
	
	public function getCurrentPeriod($playerId){
		$player = (new Player)->getByPlayerId($playerId);
		$createDate = $player['create_time'];
		$today = time();
		$maxDay = (new ActNewbieRecharge)->getMaxDay();
		$day = floor(($today - $createDate) / (3600*24))+1;
		if($day > $maxDay)
			return false;
		
		return (new ActNewbieRecharge)->getPeriodByDay($day);
		
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
		if(!checkNewbieActivityServer()){
			return true;
		}
		$period = $this->getCurrentPeriod($playerId);
		if(!$period)
			return true;
		
		$o = new self;
		if(!$o->find(array('player_id='.$playerId. ' and period='.$period))->toArray()){
			$ret = $o->create(array(
				'player_id' => $playerId,
				'period' => $period,
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
			), array("player_id"=>$playerId, "period"=>$period));
		}
		$o->clearDataCache($playerId);
		return $o->affectedRows();
	}
		
	public function setFlag($playerId, $period, $flag, $rowversion){
		$now = date('Y-m-d H:i:s');
		/*$ret = $this->getByPeriodId($playerId, $period);
		if(!$ret)
			return false;
		if(in_array($gem, $ret['flag'])){
			return false;
		}
		$ret['flag'][] = $gem;
		$ret['flag'] = join(',', $ret['flag']);*/
		$ret = $this->updateAll(array(
			'flag' => "'".$flag."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId, "period"=>$period, 'rowversion'=>"'".$rowversion."'"));
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
	
}