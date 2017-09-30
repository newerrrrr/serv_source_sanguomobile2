<?php
//玩家联盟捐献
class PlayerCitybattleDonate extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	
	public $buttonLimit = [1=>10, 2=>10, 3=>4];
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		$setCache = false;
		$playerGuildDonate = Cache::getPlayer($playerId, __CLASS__);
        if(!$playerGuildDonate) {
            $playerGuildDonate = self::findFirst(["player_id={$playerId}"]);
			if(!$playerGuildDonate){
				if(!$this->add($playerId))
					return false;
				$playerGuildDonate = self::findFirst(["player_id={$playerId}"]);
			}
			$playerGuildDonate = $playerGuildDonate->toArray();
			$setCache = true;
        }
		$today = date('Y-m-d');
		if($today != $playerGuildDonate['last_donate_time']){
			$now = date('Y-m-d H:i:s');
			$this->updateAll(['last_donate_time'=>"'".$today."'", 'button1_counter'=>0, 'button2_counter'=>0, 'button3_counter'=>0, 'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"], ['id'=>$playerGuildDonate['id'], 'last_donate_time <> '=>"'".$today."'"]);
			$playerGuildDonate = self::findFirst(["player_id={$playerId}"]);
			$playerGuildDonate = $playerGuildDonate->toArray();
			$setCache = true;
		}
		if($setCache){
            Cache::setPlayer($playerId, __CLASS__, $playerGuildDonate);
		}
		$playerGuildDonate = $this->adapter($playerGuildDonate, true);
        if($forDataFlag) {
            return filterFields(array($playerGuildDonate), $forDataFlag, $this->blacklist)[0];
        } else {
            return $playerGuildDonate;
        }
    }
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId){
		if($this->find(array('player_id='.$playerId))->toArray()){
			return false;
		}
		$o = new self;
		$ret = $o->create(array(
			'player_id' => $playerId,
			'last_donate_time' => date('Y-m-d'),
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $o->affectedRows();
	}
		
	public function updateData($button, $times=1){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'button'.$button.'_counter' => 'button'.$button.'_counter+'.$times,
			'last_donate_time' => "'".date('Y-m-d')."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), ["id"=>$this->id, "rowversion"=>"'".$this->rowversion."'", 'button'.$button.'_counter <='=>$this->buttonLimit[$button]-$times]);
		$this->clearDataCache();
		if(!$ret || !$this->affectedRows())
			return false;
		return true;
	}
	
}