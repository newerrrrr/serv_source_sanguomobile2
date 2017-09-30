<?php
/**
 * 磨坊
 *
 */
class PlayerMill extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
    /**
     * 玩家酒馆数据
     * @param  int $playerId player id
     * @return bool           sucess or not
     */
    public function add($playerId){
        $this->player_id = $playerId;
		$this->num = 1;
		$this->item_ids = '';
		$this->begin_time = '0000-00-00 00:00:00';
        $this->create_time = date('Y-m-d H:i:s');
        return $this->save();
    }
	
	public function getByPlayerId($playerId, $forDataFlag=false){
        $playerMill = Cache::getPlayer($playerId, __CLASS__);
        if(!$playerMill) {
            $playerMill = self::findFirst(["player_id={$playerId}"]);
			if(!$playerMill){
				$this->add($playerId);
				$playerMill = self::findFirst(["player_id={$playerId}"]);
			}
			$playerMill = $playerMill->toArray();
            Cache::setPlayer($playerId, __CLASS__, $playerMill);
        }
		$playerMill = $this->adapter($playerMill, true);
		$playerMill['item_ids'] = parseGroup($playerMill['item_ids'], false);
        if($forDataFlag) {
            return filterFields(array($playerMill), $forDataFlag, $this->blacklist)[0];
        } else {
            return $playerMill;
        }
    }
	
	public function increaseNum($playerId, $num, $rowversion){
		$ret = $this->updateAll(array(
			'num'=>$num, 
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion'=>"'".uniqid()."'"
		), ["player_id"=>$playerId, 'rowversion'=>"'".$rowversion."'"]);
		$this->clearDataCache($playerId);
		return $ret;
	}
	
	public function updateItem($playerId, $itemIds, $beginTime, $rowversion){
		if($itemIds){
			foreach($itemIds as &$_it){
				$_it = join(',', $_it);
			}
			unset($_it);
		}else{
			$beginTime = '0000-00-00 00:00:00';
		}
		$itemIds = join(';', $itemIds);
		$ret = $this->updateAll(array(
			'item_ids'=>"'".$itemIds."'",
			'begin_time'=>"'".$beginTime."'",
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion'=>"'".uniqid()."'"
		), ["player_id"=>$playerId, 'rowversion'=>"'".$rowversion."'"]);
		$this->clearDataCache($playerId);
		return $ret;
	}
}
