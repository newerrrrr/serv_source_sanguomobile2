<?php

class PlayerShop extends ModelBase{
	public $blacklist = array('player_id', 'update_time', 'rowversion');
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
    public function add($playerId, $shopId, $num){
        $this->player_id = $playerId;
		$this->shop_id = $shopId;
		$this->num = $num;
		$this->date = date('Y-m-d');
        $this->create_time = date('Y-m-d H:i:s');
        return $this->save();
    }
	
	public function up($playerId, $shopId, $num){
		if(!self::findFirst(['player_id='.$playerId.' and shop_id='.$shopId])){
			$ret = $this->add($playerId, $shopId, $num);
		}else{
			$now = date('Y-m-d H:i:s');
			$data = [
				'num'=>'num+'.$num,
				'update_time'=>"'".date('Y-m-d H:i:s')."'",
				'rowversion'=>"'".uniqid()."'"
			];
			$ret = $this->updateAll($data, ['player_id'=>$playerId, 'shop_id'=>$shopId]);
			$this->clearDataCache($playerId);
		}
		return $ret;
	}
	
	public function reset($playerId){
		$date = date('Y-m-d');
		$fields = [
			'num'=>0,
			'date'=>"'".$date."'",
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion'=>"'".uniqid()."'"
		];
		if($this->updateAll($fields, ['player_id'=>$playerId, 'date <>'=>"'".$date."'"])){
			$this->clearDataCache($playerId);
		}
		return true;
	}
	
    /**
     * 通过id获取玩家信息
     *
     * @return $player array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
		$this->reset($playerId);
		return parent::getByPlayerId($playerId, $forDataFlag);
    }

	public function getByShopId($playerId, $shopId){
		$data = $this->getByPlayerId($playerId);
		if(!$data)
			return false;
		foreach($data as $_d){
			if($_d['shop_id'] == $shopId){
				return $_d;
			}
		}
		return false;
	}
}
