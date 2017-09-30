<?php
/**
 * 玩家时效性buff表
 * 
 */
class PlayerBuffTemp extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	public function afterSave(){
		$this->_clearDataCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
        $this->condition = 'player_id in (0, '.$playerId.')';
		return parent::getByPlayerId($playerId, $forDataFlag);
    }
	
    /**
     * 生成新记录
     * @param  int $playerId 
     * @return bool           
     */
    public function newPlayerBuff($playerId, $buffTempId, $buffId, $buffName, $buffNum, $second, $position=0){
       $o = new self;
		$ret = $o->create(array(
			'player_id' => $playerId,
			'buff_temp_id' => $buffTempId,
			'position' => $position,
			'buff_id' => $buffId,
			'buff_name' => $buffName,
			'buff_num' => $buffNum,
			'expire_time' => date('Y-m-d H:i:s', time()+$second),
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret || !$o->affectedRows())
			return false;
        return true;
    }

    /**
     * 获取某一个buff值
     *
     * @param  int $playerId  
     * @param  int $buffId
     * @return int            value
     */
    public function getNumByBuffId($playerId, $buffId, $position=0){
        return self::sum(["player_id in(0, {$playerId}) and buff_id={$buffId} and position in (0, {$position}) and expire_time >='".date('Y-m-d H:i:s')."'", "column"=>"buff_num"]);
    }

    public function getPlayerBuff($playerId, $buffName){
    	$re = self::find(["player_id in(0, {$playerId}) and buff_name='{$buffName}'"]);
    	if(!$re){
    		return false;
    	}else{
    		return $this->adapter($re->toArray());
    	}

    }
	
	/**
     * 获取某一个buff值by名字
     *
     * @param  int $playerId  
     * @param  int $buffId
     * @return int            value
     */
    public function getNumByBuffName($playerId, $buffName, $position=0){
		$modelClassName = $this->getCacheKey($buffName, $position);
		//$data = Cache::getPlayer($playerId, $modelClassName);
		$data = Cache::db('bufftemp')->hGet('PlayerBuffTemp2:'.$playerId, $modelClassName);
		$sum = 0;
		if(false !== $data){
			if(strtotime($data['expire_time']) < time()){
				$data = false;
			}else{
				$sum = $data['value'];
			}
		}
		if(false === $data){
			$_data = $this->sqlGet('select sum(buff_num), min(expire_time) from player_buff_temp where player_id in(0, '.$playerId.') and buff_name="'.$buffName.'" and position in (0, '.$position.') and expire_time >="'.date('Y-m-d H:i:s').'"');
			$_data = $_data[0];
			if($_data['sum(buff_num)']){
				//Cache::setPlayer($playerId, $modelClassName, array('value'=>$_data['sum(buff_num)'], 'expire_time'=>$_data['min(expire_time)']));
				Cache::db('bufftemp')->hSet('PlayerBuffTemp2:'.$playerId, $modelClassName, array('value'=>$_data['sum(buff_num)'], 'expire_time'=>$_data['min(expire_time)']));
			}else{
				//Cache::setPlayer($playerId, $modelClassName, array('value'=>0, 'expire_time'=>date('Y-m-d H:i:s', time()+3600*24*7)));
				Cache::db('bufftemp')->hSet('PlayerBuffTemp2:'.$playerId, $modelClassName, array('value'=>0, 'expire_time'=>date('Y-m-d H:i:s', time()+3600*24*7)));
			}
			$sum = @$_data['sum(buff_num)']*1;
			/*
			$ret = self::find(["player_id={$playerId} and buff_name='{$buffName}' and position={$position} and expire_time >='".date('Y-m-d H:i:s')."'"])->toArray();
			$vals = Set::extract('/buff_num', $ret);
			$sum = array_sum($vals);
			Cache::setPlayer($playerId, $modelClassName, $ret);
			*/
		}
		/*if(!$ret) {
			$ret = self::sum(["player_id={$playerId} and buff_name='{$buffName}' and position={$position} and expire_time >='".date('Y-m-d H:i:s')."'", "column"=>"buff_num"]);
			Cache::setPlayer($playerId, $modelClassName, $ret);
		}*/
		return $sum*1;
    }
	
	public function getNumByBuffNames($playerId, $buffNames, $position=0){
		$data = $this->sqlGet('select buff_name, sum(buff_num) from player_buff_temp where player_id in(0, '.$playerId.') and buff_name in ("'.join('","', $buffNames).'") and position in (0, '.$position.') and expire_time >="'.date('Y-m-d H:i:s').'" group by buff_name');
		$ret = [];
		foreach($data as $_d){
			$ret[$_d['buff_name']] = $_d['sum(buff_num)']*1;
		}
		return $ret;
    }
	
	public function getCacheKey($buffName, $position=0){
		return $buffName.':'.$position;
	}
	
    /**
     * 增加buff
     * 
     * @param <type> $playerId 
     * @param <type> $buffTempId 
     * @param <type> $buffId 
     * @param <type> $buffNum 
     * 
     * @return <type>
     */
    public function up($playerId, $buffTempId, $second, $position=0){
		$buffTemp = (new BuffTemp)->dicGetOne($buffTempId);
		if(!$buffTemp)
			return false;
		$buff = (new Buff)->dicGetOne($buffTemp['buff_id']);
		if(!$buff)
			return false;
		//获取是否有buff
		$ret = self::find(["player_id={$playerId} and buff_temp_id={$buffTempId} and position={$position}"])->toArray();
		if(!$ret){
			return $this->newPlayerBuff($playerId, $buffTempId, $buffTemp['buff_id'], $buff['name'], $buffTemp['buff_num'], $second, $position);
		}else{
			$now = date('Y-m-d H:i:s');
			if($ret[0]['expire_time'] >= $now){
				if(!$this->updateAll(array(
					'expire_time'=>"'".date('Y-m-d H:i:s', strtotime($ret[0]['expire_time'])+$second)."'",
					'update_time'=>"'".$now."'",
					'rowversion'=>"'".uniqid()."'"
				), array("player_id"=>$playerId, "buff_temp_id"=>"'".$buffTempId."'", 'position'=>$position))){
					return false;
				}
			}else{
				if(!$this->updateAll(array(
					'create_time'=>"'".$now."'",
					'expire_time'=>"'".date('Y-m-d H:i:s', strtotime($now)+$second)."'",
					'update_time'=>"'".$now."'",
					'rowversion'=>"'".uniqid()."'"
				), array("player_id"=>$playerId, "buff_temp_id"=>"'".$buffTempId."'", 'position'=>$position))){
					return false;
				}
			}
		}
		$this->_clearDataCache($playerId, $buff['name'], $position);
		return true;
    }

    /**
     * 清除玩家城战称号buff
     *
     * @param $playerId
     * @param $buffTempIds
     */
	public function clearTitleBuff($playerId, $buffTempIds){
        $this->find(["buff_temp_id in (:buffTempIds:)", 'bind'=>['buffTempIds'=>implode(",", $buffTempIds)]])->delete();
        $this->_clearDataCache($playerId);
    }
	public function _clearDataCache($playerId=0, $buffName='', $position=0){
		if(!$playerId){
			$playerId = $this->player_id;
		}
		$this->clearDataCache($playerId);
		if(@$this->buff_name && !$buffName){
			$buffName = $this->buff_name;
			$position = $this->position;
		}
		/*if($buffName){
			$modelClassName = $this->getCacheKey($buffName, $position);
			Cache::db('bufftemp')->hDel('PlayerBuffTemp2:'.$playerId, $modelClassName);
		}else{*/
			Cache::db('bufftemp')->del('PlayerBuffTemp2:'.$playerId);
		//}
		//Cache::delPlayer($playerId, $modelClassName);
	}
	
	public function delByTempId($playerId, $buffTempIds){
		$this->find(['player_id='.$playerId.' and buff_temp_id in ('.join(',', $buffTempIds).')'])->delete();
		$this->_clearDataCache($playerId);
	}
	
	public function delByBuffName($playerId, $buffNames){
		$this->find(['player_id='.$playerId.' and buff_name in ("'.join('","', $buffNames).'")'])->delete();
		$this->_clearDataCache($playerId);
	}
	
	/**
     * 顽强斗志buff
     * 
     */
	public function addWQDZbuff($playerId, $beforePower, $losePower){
		$level = (new PlayerBuild)->getPlayerCastleLevel($playerId);
		$configRate = min(max($level*2.5-25,25),75) / 100;
		if($losePower / $beforePower < $configRate)
			return false;
		if((new Player)->getByPlayerId($playerId)['hsb'])
			return false;
		if(!(new Player)->setAvoidBattleTime($playerId, 8*3600)){
			return false;
		}
		/*$buffTempId = 123;
		$max = 9900;
		$addBuffNum = min($max, floor($losePower / $beforePower * 100) * 4 * 100);
		$date = date('Y-m-d H:i:s', time());
		$this->sqlExec('delete from '.$this->getSource().' where player_id='.$playerId.' and expire_time<"'.$date.'"');

		if(!$this->up($playerId, $buffTempId, 8*3600)){
			return false;
		}
		if(!$this->updateAll(['buff_num'=>'LEAST('.$max.', buff_num+'.$addBuffNum.')', 'expire_time'=>"'".date('Y-m-d H:i:s', time()+8*3600)."'"], ['player_id'=>$playerId, 'buff_temp_id'=>$buffTempId])){
			return false;
		}
		$this->_clearDataCache($playerId);*/
		return true;
	}
	
	public function clearHsbBuff($playerId){
		$this->find(['player_id='.$playerId.' and buff_temp_id>=1000 and buff_temp_id <= 2000'])->delete();
		$this->_clearDataCache($playerId);
	}
}