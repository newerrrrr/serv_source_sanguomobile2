<?php
/**
 * 联盟
 *
 */
class CityBattleCamp extends CityBattleModelBase{
	public $blacklist = array('camp_id', 'create_time', 'update_time', 'rowversion');
	public $battleId;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
    /**
     * 根据playerId查出该玩家的联盟数据
     * @param  int  $playerId    
     * @param  boolean $forDataFlag 
     * @return array   
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $Player = new CityBattlePlayer;
		$Player->battleId = $this->battleId;
        $player = $Player->getByPlayerId($playerId);
        $campId = $player['camp_id'];
        if($campId) {
            return $this->getByCampId($campId, $forDataFlag);
        } else {
            return [];
        }
    }
    /**
     * 创建联盟
     * @param  array $data 
     */
    public function add($campId){
        $currentRoundId = (new CityBattleRound)->getCurrentRound();
        if(!$currentRoundId) return false;
		$o = new self;
		$ret = $o->create(array(
		    'round_id' => $currentRoundId,//轮次round_id
			'battle_id' => $this->battleId,
			'camp_id' => $campId,
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret)
			return false;
		return $o->affectedRows();
	}
    /**
     * 获得camp
     * @param  int $id 
     * @return array     
     */
    public function getByCampId($id, $forDataFlag=false){
        $re = Cache::getCamp($id, __CLASS__);
        if(!$re) {
            $camp = self::findFirst(["battle_id={$this->battleId} and camp_id={$id}"]);
            if(!$camp) return [];
            $re = $this->adapter($camp->toArray(), true);
            Cache::setCamp($id, __CLASS__, $re);
        }
		if($forDataFlag) {
			$re = filterFields([$re], $forDataFlag, $this->blacklist)[0];
			foreach($re as $_k => &$_r){
				if(substr($_k, 0, 5) == 'buff_' && substr($_k, -4) == '_ids'){
					$_r = json_decode($_r, true);
					if(!$_r) $r_r = [];
				}
			}
			unset($_r);
		}
        return $re;
    }
	
    /**
     * 更改guild表的值
     * @param  int $guildId 
     * @param  array  $fields  
     */
    public function alter($campId, array $fields){
        $this->updateAll($fields, ['battle_id'=>$this->battleId, 'camp_id'=>$campId]);
        $this->clearCampCache($campId);
    }
	
}
