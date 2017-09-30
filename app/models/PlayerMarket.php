<?php
//玩家集市
class PlayerMarket extends ModelBase{
	CONST NUM = 6;
	CONST FREE_TIMES = 3;
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		$ret = parent::getByPlayerId($playerId);
		$today = date('Y-m-d');
		if(!$ret || $ret[0]['last_date'] != $today){
			$PlayerBuild = new PlayerBuild;
			$playerBuild = $PlayerBuild->getByOrgId($playerId, 1);
			$Market = new Market;
			$spId = $Market->getRandSp($playerBuild[0]['build_level']);
			$ids = $Market->rand(self::NUM, $spId, $playerBuild[0]['build_level']);
			if(!$ret){
				$r = $this->add($playerId, $today, $ids, $spId);
			}else{
				$r = $this->up($playerId, $today, 0, $ids, $spId, $ret[0]['rowversion']);
			}
			if(!$r)
				return false;
			$ret = parent::getByPlayerId($playerId);
		}
		$ret[0]['market_ids'] = array_combine(range(1, self::NUM), array_map('intval', parseArray($ret[0]['market_ids'])));
		$ret = filterFields($ret, $forDataFlag, $this->blacklist);
		return $ret[0];
    }
	
	public function add($playerId, $lastDate, $ids, $spId){
        $this->player_id = $playerId;
		$this->last_date = $lastDate;
		$this->counter = 0;
		$this->market_ids = join(',', $ids);
		$this->special_id = $spId;
        $this->create_time = date('Y-m-d H:i:s');
        return $this->save();
    }
	
	public function up($playerId, $lastDate, $counter, $ids, $spId, $rowversion){
		$data = [
			'last_date'=>"'".$lastDate."'",
			'counter'=>$counter,
			'market_ids'=>"'".join(',', $ids)."'",
			'special_id'=>$spId,
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion'=>"'".uniqid()."'"
		];
		$ret = $this->updateAll($data, ['player_id'=>$playerId, 'rowversion'=>"'".$rowversion."'"]);
		$this->clearDataCache($playerId);
		return $ret;
	}
}