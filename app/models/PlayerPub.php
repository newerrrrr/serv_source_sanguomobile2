<?php

class PlayerPub extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
    /**
     * 玩家酒馆数据
     * @param  int $playerId player id
     * @return bool           sucess or not
     */
    public function add($playerId, $buildId=14001){
        $this->player_id = $playerId;
		$this->luck_counter = 3;
		$this->pay_luck_counter = 0;
		$this->pay_day_counter = 0;
		$this->last_pay_reload_date = date('Y-m-d');
		$this->build_id = $buildId;
		$this->pay_build_id = 0;
		//读取pub数据
		$Pub = new Pub;
		$pub = $Pub->dicGetOne($buildId);
		if(!$pub)
			return false;
		//获取dropid
		$generals = (new Starting)->dicGetOne('default_general');//$this->getRandGeneral($playerId, $pub);
		//if(!$generals)
		//	return false;
		$this->generals = $generals;
		$this->prisoners = '';
		$this->next_free_time = date('Y-m-d H:i:s', time()+$pub['time']);
        $this->create_time = date('Y-m-d H:i:s');
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
        return $this->save();
    }
	
	public function updateGeneral($general, $data=array()){
		$now = date('Y-m-d H:i:s');
		$data = array_merge($data, array(
			'generals'=>join(',', $general), 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		));
		$ret = $this->saveAll($data, "id={$this->id}");
		$this->clearDataCache();
		if(!$ret || !$this->affectedRows())
			return false;
		return true;
	}
	
	public function resetPayReload($playerId){
		$today = date('Y-m-d');
		$ret = $this->saveAll(array(
			'pay_day_counter'=>0, 
			'last_pay_reload_date'=>$today,
			'rowversion'=>uniqid()
		), "player_id={$playerId} and last_pay_reload_date<>'{$today}'");
		$this->clearDataCache($playerId);
		if(!$ret || !$this->affectedRows())
			return false;
		return true;
	}

    /**
     * 通过id获取玩家信息
     *
     * @return $player array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $playerPub = Cache::getPlayer($playerId, __CLASS__);
        if(!$playerPub || $playerPub['last_pay_reload_date'] != date('Y-m-d')) {
            $playerPub = self::findFirst(["player_id={$playerId}"]);
			if(!$playerPub){
				$PlayerBuild = new PlayerBuild;
				if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 14)){
					return false;
				}
				if(!$this->add($playerId, $playerBuild[0]['build_id'])){
					return false;
				}
				$playerPub = self::findFirst(["player_id={$playerId}"])->toArray();
			}else{
				$playerPub = $playerPub->toArray();
				if($playerPub['last_pay_reload_date'] != date('Y-m-d')){
					$this->resetPayReload($playerId);
					$playerPub = self::findFirst(["player_id={$playerId}"])->toArray();
				}
			}
            Cache::setPlayer($playerId, __CLASS__, $playerPub);
        }
		$playerPub = $this->adapter($playerPub, true);
        if($forDataFlag) {
            return filterFields(array($playerPub), $forDataFlag, $this->blacklist)[0];
        } else {
            return $playerPub;
        }
    }
	
	public function getRandGeneral($playerId, $pub){
		//查找已有武将
		$PlayerGeneral = new PlayerGeneral;
		$generalIds = $PlayerGeneral->getGeneralIds($playerId);//getRandGeneral该函数已经废弃，如果启用需要修改这一行
		
		$dropIds = array($pub['first_drop'], $pub['ordinary_drop'], $pub['ordinary_drop']);
		
		$Drop = new Drop;
		$Drop->setExcept($playerId, array(3=>$generalIds));
		$generals = array();
		foreach($dropIds as $_id){
			$dropGeneral = $Drop->rand($playerId, array($_id));
			if(!$dropGeneral)
				continue;
			foreach($dropGeneral as $_r){
				$generalIds[] = $_r[1];
			}
			$Drop->setExcept($playerId, array(3=>$generalIds));
			$generals[] = $dropGeneral[0][1];
		}
		
		//补充
		while(count($generals) < 3){
			$ret = $Drop->rand($playerId, array($pub['ordinary_drop']));
			if(!$ret)
				return false;
			$generals[] = $ret[0][1];
			$generalIds[] = $ret[0][1];
			$Drop->setExcept(array(3=>$generalIds));
		}

		if(count($generals) != 3){
			return false;
		}
		shuffle($generals);
		return $generals;
	}

}
