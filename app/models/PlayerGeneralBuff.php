<?php
/**
 * 武将buff表
 * 
 */
class PlayerGeneralBuff extends ModelBase{
    public $blacklist = array('player_id');

    public function afterSave(){
        $this->clearDataCache();
    }
    /**
     * 生成新记录
     * @param  int $playerId 
     * @return bool           
     */
    public function newPlayerBuff($playerId, $data=[]){
		$self = new self;
		$data['player_id'] = $playerId;
		$ret = $self->create($data);
		return $ret;
    }
    /**
     * 通过playerId获取玩家buff信息
     * @param  int  $playerId    
     * @param  boolean $forDataFlag 
     * @return array         buff info
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
		if(!$playerId)
			return [];
        $playerBuff = Cache::getPlayer($playerId, __CLASS__);
        if(!$playerBuff) {
            $playerBuff = self::findFirst(["player_id={$playerId}"])->toArray();
            Cache::setPlayer($playerId, __CLASS__, $playerBuff);
        }
        if($forDataFlag) {
            return filterFields(array($playerBuff), $forDataFlag, $this->blacklist)[0];
        } else {
            return $playerBuff;
        }
    }
    /**
     * 设置buff值 
     * @param int  $playerId    
     * @param string  $buffField   field of player_buff
     * @param int  $buffValue   buff value
     */
    public function setPlayerBuff($playerId, $buffId, $buffValue, $minusFlag=false){
        $buff = (new Buff)->dicGetOne($buffId);
        if($buff){
            $name = $buff['name'];
            $buffValue = abs($buffValue);
            if($minusFlag) {
                $ret = $this->updateAll([$name=>"{$name}-{$buffValue}"], ['player_id'=>$playerId]);
            } else {
                $ret = $this->updateAll([$name=>"{$name}+{$buffValue}"], ['player_id'=>$playerId]);
            }
            $this->clearDataCache($playerId);
			
			if($name == 'wall_defense_limit_plus'){
				(new PlayerBuild)->refreshWallDurability($playerId);

			}
			if(in_array($name, ['general_force_inc','general_intelligence_inc','general_governing_inc','general_charm_inc','general_political_inc'])){
				$PlayerGeneral = new PlayerGeneral;
				$generalIds = $PlayerGeneral->getGeneralIds($playerId);
				$PlayerGeneral->_clearDataCache($playerId, $generalIds);
			}
    		return $ret;
        }
        return false;
    }
	
	public function refreshAll($playerId){
		
		//解析所有武将天赋
		$generals = (new General)->getAllByOriginId();
		$Buff = new Buff;
		foreach($generals as &$_general){
			$_buff = [];
			foreach($_general['general_talent_buff_id'] as $_buffId){
				$_buff[] = $Buff->dicGetOne($_buffId)['name'];
			}
			$_general['general_talent_buff_id'] = $_buff;
		}
		unset($_general);
		
		//查找所有武将
		$_generals = $this->sqlGet('select general_id,star_lv from player_general where player_id='.$playerId);
		
		//循环武将
		$buff = [];
		foreach($_generals as $_general){
			$_generalId = $_general['general_id'];
			if($generals[$_generalId]["general_talent_value"] === ''){
				echo '[PlayerGeneralBuff error]general_id:'.$_generalId.PHP_EOL;
				continue;
			}
			$_buffIds = $generals[$_generalId]['general_talent_buff_id'];
			$star = $_general['star_lv'];
			eval('$_buffValue = '.$generals[$_generalId]["general_talent_value"].';');
			foreach($_buffIds as $_buffId){
				@$buff[$_buffId] += $_buffValue;
			}
		}
		
		$pgb = $this->getByPlayerId($playerId);
		if($pgb){
			foreach($pgb as $_k => $_v){
				if(in_array($_k, ['id', 'player_id'])) continue;
				if(!isset($buff[$_k])){
					$buff[$_k] = 0;
				}
			}
			$ret = $this->updateAll($buff, ['player_id'=>$playerId]);
		}else{
			//增加buff
			$ret = $this->newPlayerBuff($playerId, $buff);
		}
		$this->clearDataCache($playerId);
		return $ret;
	}
}