<?php
//新人玩家登陆活动
class PlayerNewbieActivityLogin extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		if(!checkNewbieActivityServer()){
			return false;
		}
		$ret = $this->findFirst(['player_id='.$playerId]);
		if(!$ret){
			$o = new self;
			$ret = $o->create(array(
				'player_id' => $playerId,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			$ret = $this->findFirst(['player_id='.$playerId]);
		}
		$ret = $ret->toArray();
		$ret = $this->adapter($ret, true);
		$ret['flag'] = parseArray($ret['flag']);
		return filterFields([$ret], $forDataFlag, $this->blacklist)[0];
	}
	
    /**
     * 新增天数
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	/*public function addDays($playerId, $addDay){
		$o = new self;
		$r = $this->getByPlayerId($playerId);
		if(in_array($addDay, $r['days'])){
			return false;
		}
		if(!$r){
			$ret = $o->create(array(
				'player_id' => $playerId,
				'days' => $addDay,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$r['days'][] = $addDay;
			$now = date('Y-m-d H:i:s');
			$ret = $o->updateAll(array(
				'days' => "'".join(',', $r['days'])."'",
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("player_id"=>$playerId));
		}
		//$o->clearDataCache($playerId);
		return $o->affectedRows();
	}*/
		
	public function setFlag($playerId, $flag, $rowversion){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'flag' => "'".$flag."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId, 'rowversion'=>"'".$rowversion."'"));
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
	
}