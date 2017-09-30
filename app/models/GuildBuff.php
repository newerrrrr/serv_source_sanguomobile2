<?php
/**
 * 联盟buff表
 * 
 */
class GuildBuff extends ModelBase{
	public $blacklist = array('guild_id');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	public function afterSave(){
		$this->clearGuildCache();
	}
    /**
     * 生成新记录
     * @param  int $guildId 
     * @return bool           
     */
    public function newGuildBuff($guildId, $buffId, $buffNum){
		$buff = (new Buff)->dicGetOne($buffId);
		if(!$buff)
			return false;
		$self = new self;
		$ret = $self->create(array(
			'guild_id' => $guildId,
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
    /**
     * 通过playerId获取玩家buff信息
     * @param  int  $guildId    
     * @param  boolean $forDataFlag 
     * @return array         buff info
     */
    public function getByGuildId($guildId, $forDataFlag=false){
        $playerBuff = Cache::getGuild($guildId, __CLASS__);
        if(!$playerBuff) {
            $playerBuff = self::find(["guild_id={$guildId}"])->toArray();
            Cache::setGuild($guildId, __CLASS__, $playerBuff);
        }
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
    public function getGuildBuff($guildId, $buffId){
        $buff = (new Buff)->dicGetOne($buffId);
        if($buff) {
            $playerBuff = $this->getByGuildId($guildId);
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
	
	public function getGuildBuffs($guildId, $buffIds){
        $playerBuff = $this->getByGuildId($guildId);
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
    public function setGuildBuff($guildId, $buffId, $buffValue){
		$buffValue = abs($buffValue);
		$ret = $this->updateAll(['buff_num'=>"buff_num+{$buffValue}", 'update_time' => "'".date('Y-m-d H:i:s')."'", 'rowversion'=>"'".uniqid()."'"], ['guild_id'=>$guildId, 'buff_id'=>$buffId]);
		if(!$ret){
			$ret = $this->newGuildBuff($guildId, $buffId, $buffValue);
		}
		$this->clearGuildCache($guildId);
		return $ret;
    }
	
	public function resetData($guildId){
		self::find("guild_id={$guildId}")->delete();
		//$ret = $this->newGuildBuff($guildId);
		$this->clearGuildCache($guildId);
	}

}