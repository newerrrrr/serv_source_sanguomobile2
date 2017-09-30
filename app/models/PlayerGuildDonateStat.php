<?php
//玩家联盟捐献统计
class PlayerGuildDonateStat extends ModelBase{
	public $blacklist = array('player_id','update_time');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
    }
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId, $type, $date, $coin, $exp){
		$ret = $this->create(array(
			'player_id' => $playerId,
			'type' => $type,
			'date' => $date,
			'coin' => $coin,
			'exp' => $exp,
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
		
	public function updateData($playerId, $coin, $exp){
		$time = time();
		$type = 0;
		$now = date('Y-m-d H:i:s', $time);
		$ret = $this->updateAll(array(
			'coin' => 'coin+'.$coin,
			'exp' => 'exp+'.$exp,
			'update_time'=>"'".$now."'",
		), ["player_id"=>$playerId, 'type'=>$type]);
		if(!$ret || !$this->affectedRows()){
			$o = new self;
			$o->add($playerId, $type, '0000-00-00', $coin, $exp);
		}
		
		$type = 1;
		$w = date('w', $time);
		$date1 = date('Y-m-d', strtotime(date('Y-m-d', $time)) + 3600*24*(7-$w));
		$ret = $this->updateAll(array(
			'coin' => 'coin+'.$coin,
			'exp' => 'exp+'.$exp,
			'update_time'=>"'".$now."'",
		), ["player_id"=>$playerId, 'type'=>$type, 'date'=>"'".$date1."'"]);
		if(!$ret || !$this->affectedRows()){
			$o = new self;
			$o->add($playerId, $type, $date1, $coin, $exp);
		}
		
		$type = 2;
		$date2 = date('Y-m-d', $time);
		$ret = $this->updateAll(array(
			'coin' => 'coin+'.$coin,
			'exp' => 'exp+'.$exp,
			'update_time'=>"'".$now."'",
		), ["player_id"=>$playerId, 'type'=>$type, 'date'=>"'".$date2."'"]);
		if(!$ret || !$this->affectedRows()){
			$o = new self;
			$o->add($playerId, $type, $date2, $coin, $exp);
		}
		
		self::find("player_id={$playerId} and ((type=1 and date < '".$date1."') or (type=2 and date < '".$date2."'))")->delete();
		//$this->sqlExec('delete from '.$this->getSource().' where player_id='.$playerId.' and ((type=1 and date < "'.$date1.'") or (type=2 and date < "'.$date2.'"))');
		$this->clearDataCache($playerId);
		return true;
	}
	
	public function clearAll($playerId){
		$this->sqlExec('delete from '.$this->getSource().' where player_id='.$playerId);
		$this->clearDataCache($playerId);
		return true;
	}
}