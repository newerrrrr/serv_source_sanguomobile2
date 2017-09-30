<?php
/**
 * 联盟
 *
 */
class CrossGuild extends CrossModelBase{
	public $blacklist = array('guild_id', 'create_time', 'update_time', 'rowversion');
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
        $Player = new CrossPlayer;
		$Player->battleId = $this->battleId;
        $player = $Player->getByPlayerId($playerId);
        $guildId = $player['guild_id'];
        if($guildId) {
            return $this->getGuildInfo($guildId, $forDataFlag);
        } else {
            return [];
        }
    }
    /**
     * 创建联盟
     * @param  array $data 
     */
    public function add(array $data, $serverId, $leaderNick){
        $currentRoundId = (new CrossRound)->getCurrentRoundId();
        if(!$currentRoundId) return false;
		$o = new self;
		$ret = $o->create(array(
		    'round_id' => $currentRoundId,//轮次round_id
			'battle_id' => $this->battleId,
			'guild_id' => CrossPlayer::joinGuildId($serverId, $data['id']),
			'leader_player_id' => $data['leader_player_id'],
			'leader_player_nick'=>$leaderNick,
			'founder'=>$data['founder'],
			'name'=>$data['name'],
			'short_name'=>$data['short_name'],
			'icon_id'=>$data['icon_id'],
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret)
			return false;
		return $o->affectedRows();
	}
    /**
     * 获得guild
     * @param  int $id 
     * @return array     
     */
    public function getGuildInfo($id, $forDataFlag=false){
        $re = Cache::getGuild($id, __CLASS__);
        if(!$re) {
            $guild = self::findFirst(["battle_id={$this->battleId} and guild_id={$id}"]);
            if(!$guild) return [];
            $re = $this->adapter($guild->toArray(), true);
            Cache::setGuild($id, __CLASS__, $re);
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
    public function alter($guildId, array $fields){
        $this->updateAll($fields, ['battle_id'=>$this->battleId, 'guild_id'=>$guildId]);
        $this->clearGuildCache($guildId);
    }
	
	/**
     * 从当前服抓取数据复制到pk服
     * 
     * 
     * @return <type>
     */
	public function cpData($guildId, $server_id=0){
		global $config;
        if($server_id!=0) {
            $serverId = $server_id;
        } else {
            $serverId = $config->server_id;
        }

		$_guildId = CrossPlayer::joinGuildId($serverId, $guildId);
		$this->find(["battle_id={$this->battleId} and guild_id={$_guildId}"])->delete();
		$guild = (new ModelBase)->getByServer($serverId, 'Guild', 'getGuildInfo', [$guildId]);
		if(!$this->add($guild, $serverId, $guild['leader_player_nick'])){
			return false;
		}
		$this->clearDataCache($guildId);
		return true;
	}
}
