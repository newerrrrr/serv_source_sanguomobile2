<?php
//联盟科技
class CityBattleScience extends CityBattleModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		//$this->clearGuildCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$campId = $player['camp_id'];
		if(!$campId)
			return false;
		return $this->getByCampId($campId, $forDataFlag);
    }
	
	public function getByCampId($campId, $forDataFlag=false){
		if(!$campId)
			return false;
        $data = self::find(["camp_id={$campId}"])->toArray();
		$data = $this->adapter($data);
        if($forDataFlag) {
            return filterFields($data, $forDataFlag, $this->blacklist);
        } else {
            return $data;
        }
    }
	
	public function getByscienceType($playerId, $scienceType, $forDataFlag=false){
		$player = (new Player)->getByPlayerId($playerId);
		$campId = $player['camp_id'];
		return $this->getByscienceTypeFromCampId($campId, $scienceType, $forDataFlag);
    }
	
	public function getByscienceTypeFromCampId($campId, $scienceType, $forDataFlag=false){
		if(!$campId)
			return false;
		$data = self::find(["camp_id={$campId} and science_type=".$scienceType])->toArray();
		if(!$data){
			if(!$this->add($campId, $scienceType)){
				return false;
			}
			$data = self::find(["camp_id={$campId} and science_type=".$scienceType])->toArray();
		}
		$data = $this->adapter($data);
        if($forDataFlag) {
            return filterFields($data, $forDataFlag, $this->blacklist)[0];
        } else {
            return $data[0];
        }
    }
	
	public function getForUpdate($campId, $scienceType){
		$data = $this->sqlGet('select * from '.$this->getSource().' where camp_id='.$campId.' and science_type='.$scienceType.' for update');
		if(!$data){
			if(!$this->add($campId, $scienceType)){
				return false;
			}
			$data = $this->sqlGet('select * from '.$this->getSource().' where camp_id='.$campId.' and science_type='.$scienceType.' for update');
		}
		$data = $this->adapter($data);
        return $data[0];
	}
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($campId, $scienceType){
		if($this->find(array('camp_id='.$campId. ' and science_type='.$scienceType))->toArray()){
			return false;
		}
		//查找scienceType是否存在
		$CountryScience = new CountryScience;
		$countryScience = $CountryScience->getByScienceType($scienceType, 1);
		if(!$countryScience)
			return false;
		$o = new self;
		$ret = $o->create(array(
			'camp_id' => $campId,
			'science_type' => $scienceType,
			'science_level' =>0,
			'science_exp' => 0,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $o->affectedRows();
	}

	public function addExp($exp){
		//获取配置
		$CountryScience = new CountryScience;
		$countryScience = $CountryScience->getByScienceType($this->science_type, $this->science_level+1);
		if(!$countryScience['levelup_exp'])
			return false;
		
		$now = date('Y-m-d H:i:s');

		$ret = $this->sqlExec('update '.$this->getSource().' set 
			science_exp = science_exp+'.$exp.',
			science_level = @slevel := if(science_exp>='.$countryScience['levelup_exp'].', science_level+1, science_level),
			science_exp = if(science_exp>='.$countryScience['levelup_exp'].', science_exp-'.$countryScience['levelup_exp'].', science_exp),
			science_exp = if(science_level>='.$countryScience['max_level'].', 0, science_exp),
			update_time="'.$now.'",
			rowversion="'.uniqid().'"
		where id='.$this->id.' and rowversion="'.$this->rowversion.'"');
		if(!$ret)
			return false;
		$newLevel = $this->sqlGet('select @slevel')[0]['@slevel'];
		if($newLevel > $this->science_level){
			$CityBattleBuff = new CityBattleBuff;
			foreach($countryScience['buff'] as $_b){
				if(!$CityBattleBuff->setCampBuff($this->camp_id, $_b[0], $_b[1])){
					return false;
				}
			}
		}
		return true;
	}
	
	//觉醒:洗练城战技能时有|<#72,255,186#>%{num}%%|几率返还玄铁
	public function effectXuantie($playerId, &$addNum=0){
		$type = 21;
		$cbs = $this->getByscienceType($playerId, $type);
		if(!$cbs)
			return false;
		$cs = (new CountryScience)->getByScienceType($type, $cbs['science_level']);
		$rate = $cs['num_value'] / DIC_DATA_DIVISOR;
		if(lcg_value() < $rate){
			$num = (new CountryBasicSetting)->getValueByKey('battle_skill_refresh_res_return_value');
			if(!(new Player)->updateResource($playerId, ['xuantie'=>$num])){
				return false;
			}
			$addNum = $num;
		}
		return true;
	}
	
	//顿悟:升级城战技能时有|<#72,255,201#>%{num}%%|几率返还无字天书
	public function effectTianshu($playerId, &$addNum=0){
		$type = 22;
		$cbs = $this->getByscienceType($playerId, $type);
		if(!$cbs)
			return false;
		$cs = (new CountryScience)->getByScienceType($type, $cbs['science_level']);
		$rate = $cs['num_value'] / DIC_DATA_DIVISOR;
		if(lcg_value() < $rate){
			$num = (new CountryBasicSetting)->getValueByKey('battle_skill_upgrade_res_return_value');
			if(!(new PlayerItem)->add($playerId, 51012, $num)){
				return false;
			}
			$addNum = $num;
		}
		return true;
	}
}