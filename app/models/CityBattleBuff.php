<?php
/**
 * 联盟buff表
 * 
 */
class CityBattleBuff extends CityBattleModelBase{
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	public function afterSave(){
		$this->clearCampCache();
	}
    /**
     * 生成新记录
     * @param  int $guildId 
     * @return bool           
     */
    public function newCampBuff($campId, $buffId, $buffNum){
		$buff = (new Buff)->dicGetOne($buffId);
		if(!$buff)
			return false;
		$self = new self;
		$ret = $self->create(array(
			'camp_id' => $campId,
			'buff_id' => $buffId,
			'buff_name' => $buff['name'],
			'buff_num' => $buffNum,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		//$this->clearGuildCache($guildId);
		return true;
    }
	
	public function getByPlayerId($playerId, $forDataFlag=false){
        $Player = new Player;
        $player = $Player->getByPlayerId($playerId);
        $campId = $player['camp_id'];
        if($campId) {
            return $this->getByCampId($campId, $forDataFlag);
        } else {
            return [];
        }
    }
	
    /**
     * 通过playerId获取玩家buff信息
     * @param  int  $guildId    
     * @param  boolean $forDataFlag 
     * @return array         buff info
     */
    public function getByCampId($campId, $forDataFlag=false){
        $playerBuff = Cache::getCamp($campId, __CLASS__);
        if(!$playerBuff) {
            $playerBuff = self::find(["camp_id={$campId}"])->toArray();
            Cache::setCamp($campId, __CLASS__, $playerBuff);
        }
		$playerBuff = self::find(["camp_id={$campId}"])->toArray();
        if($forDataFlag) {
            return filterFields($playerBuff, $forDataFlag, $this->blacklist);
        } else {
            return $playerBuff;
        }
    }
    /**
     * 获取某一个buff值
     *
     * ```php
     * $PlayerBuff->getPlayerBuff(100029, 'avoid_battle');
     * ```
     * @param  int      $guildId  
     * @param  string   $buffName
     * @return int      $thisBuff  buff value
     */
    public function getCampBuff($campId, $buffId){
        $buff = (new Buff)->dicGetOne($buffId);
        if($buff) {
            $playerBuff = $this->getByCampId($campId);
			$buffNum = 0;
			foreach($playerBuff as $_b){
				if($_b['buff_id'] == $buffId){
					$buffNum = $_b['buff_num'];
					break;
				}
			}
            if($buff['buff_type']==1) {//万分比
                $buffNum = floatval($buffNum/DIC_DATA_DIVISOR);
            }
            return $buffNum;
        }
        return 0;
    }
	
	public function getCampBuffs($campId, $buffIds){
        $playerBuff = $this->getByCampId($campId);
        if(!empty($playerBuff)) {
            $buffs = (new Buff)->dicGetAll();
        }

        $ret = [];
		foreach($playerBuff as $_b){
			if(in_array($_b['buff_id'], $buffIds)){
				$buffNum = $_b['buff_num'];
				if($buffs[$_b['buff_id']]['buff_type']==1) {//万分比
					$buffNum = floatval($buffNum/DIC_DATA_DIVISOR);
				}
				$ret[$_b['buff_id']] = $buffNum;
			}
		}
		
        return $ret;
    }
    /**
     * 设置buff值 
     * @param int  $guildId    
     * @param string  $buffField   field of player_buff
     * @param int  $buffValue   buff value
     */
    public function setCampBuff($campId, $buffId, $buffValue){
		$buffValue = abs($buffValue);
		$ret = $this->updateAll(['buff_num'=>$buffValue, 'update_time' => "'".date('Y-m-d H:i:s')."'", 'rowversion'=>"'".uniqid()."'"], ['camp_id'=>$campId, 'buff_id'=>$buffId]);
		if(!$ret){
			$ret = $this->newCampBuff($campId, $buffId, $buffValue);
		}
		$this->clearCampCache($campId);
		return $ret;
    }
	
	public function resetData($campId){
		self::find("camp_id={$campId}")->delete();
		//$ret = $this->newGuildBuff($guildId);
		$this->clearCampCache($campId);
	}

	public function getJunziBuff($campId){
		return $this->getCampBuff($campId, 506);
	}
}