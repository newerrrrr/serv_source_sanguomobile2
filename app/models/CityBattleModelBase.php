<?php

/**
 * CityBattle的model base类，继承自ModelBase
 *
 */
class CityBattleModelBase extends ModelBase{
    /**
     * 武库的初始化指向
     */
    public function initialize(){
        $this->setConnectionService('db_citybattle_server');
		self::setup(['notNullValidations'=>false]);
    }
		
	public function getByPlayerId($playerId, $forDataFlag=false){
        $modelClassName = get_class($this);
        $re = Cache::getPlayer($playerId.'_'.@$this->battleId, $modelClassName);
        if(!$re) {
            if($this->condition) {
                $re = self::find([$this->condition])->toArray();
                $this->condition = '';
			}elseif(@$this->battleId){
				$re = self::find(["battle_id={$this->battleId} and player_id={$playerId}"])->toArray();
            } else {
                $re = self::find(["player_id={$playerId}"])->toArray();
            }
            $re = $this->adapter($re);
            Cache::setPlayer($playerId.'_'.@$this->battleId, $modelClassName, $re);
        }
        return filterFields($re, $forDataFlag, $this->blacklist);
    }
	
	public function clearDataCache($playerId=0, $basicFlag=true){
		if(!$playerId){
			$playerId = $this->player_id;
		}
		$class = get_class($this);
		Cache::delPlayer($playerId.'_'.$this->battleId, $class);
		$this->getDI()->get('data')->datas[$playerId][] = $class;
        if($basicFlag) {//如果为false则不会进basic
    		$this->getDI()->get('data')->setBasic([$class]);
        }
	}
	
	public function clearCampCache($campId=0){
		if(!$campId){
			$campId = $this->camp_id;
		}
		Cache::delcamp($campId, get_class($this));
		$this->getDI()->get('data')->setBasic([get_class($this)]);
	}
}