<?php
class CrossPlayer extends CrossModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	public $battleId;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache($this->id);
	}
	
	public function getByPlayerId($id, $forDataFlag=false){
		if(!$id){
			trace();
			exit("\n[ERROR]!!!NOT EXISTS Player. id=!!-> {$id} <-!! .[输入了不存在的玩家id]\n");
		}
        $player = Cache::getPlayer($id.'_'.$this->battleId, __CLASS__);
        if(!$player) {
            $player = self::findFirst(["battle_id={$this->battleId} and player_id={$id}"]);
            if($player) {
                $player = $player->toArray();
				$player = $this->afterFindPlayer($player);
                Cache::setPlayer($id, __CLASS__, $player);
            } else {
                trace();
                exit("\n[ERROR]!!!NOT EXISTS Player. id=!!-> {$id} <-!! .[输入了不存在的玩家id]\n");
            }
        }

        //羽林军勇士称号显示
        $CityBattleRank = new CityBattleRank;
        $player['rank_title'] = $CityBattleRank->getTitleByPlayerId($player['id']);

        if($forDataFlag) {
            return filterFields([$player], $forDataFlag, $this->blacklist)[0];
        } else {
            return $player;
        }
    }
	
	public function afterFindPlayer($player){
        $player = $this->adapter($player, true);
		$player['buff'] = json_decode($player['buff'], true);
		$player['buff'] = array_map("intval", $player['buff']);
        $map = CrossMap::findFirst(["battle_id={$this->battleId} and player_id={$player['player_id']} and map_element_origin_id=15"]);
        if($map) {
            $player['map_id']    = intval($map->id);
            $player['x']         = intval($map->x);
            $player['y']         = intval($map->y);
            $player['is_in_map'] = 1;//在地图里：1 不在地图里：0;
			$player['area'] = intval($map->area);
        } else {
            $player['map_id']    = 0;
            $player['x']         = $player['prev_x'];
            $player['y']         = $player['prev_y'];
            $player['is_in_map'] = 0;//在地图里：1 不在地图里：0;
			$player['area'] = null;
        }

        return $player;
    }
	
	public function alter($playerId, array $fields){
        $ret = $this->updateAll($fields, ['player_id'=>$playerId, 'battle_id'=>$this->battleId]);
		if(isset($fields['wall_durability'])){
			$CrossMap = new CrossMap;
			$CrossMap->updateAll(['rowversion'=>'rowversion+1'], ['battle_id'=>$this->battleId, 'player_id'=>$playerId, 'map_element_origin_id'=>15]);
			$CrossMap->clearDataCache();
		}
        $this->clearDataCache($playerId);
		return $ret;
    }
	
    /**
     * 新增军团
     * 
     * @param <type> $playerId 
     * @param <type> $position 
     * 
     * @return <type>
     */
	public function add($id, $serverId, $data, $extra=[]){
        $currentRoundId = (new CrossRound)->getCurrentRoundId();
        if(!$currentRoundId) return false;
		if($this->findFirst(["battle_id={$this->battleId} and player_id={$id}"])){
			return false;
		}
		$lv = $data['castle_lv'];
		$formula = (new WarfareServiceConfig)->dicGetOne('wf_playercastle_hitpoint');
		eval('$wall = '.$formula.';');
		$o = new self;
		$saveData = array(
		    'round_id'            => $currentRoundId,//轮次round_id
            'battle_id'           => $this->battleId,
            'player_id'           => $id,
            'server_id'           => $serverId,
            'nick'                => $data['nick'],
            'avatar_id'           => $data['avatar_id'],
            'level'               => $data['level'],
			'castle_lv'           => $data['castle_lv'],
            'guild_id'            => self::joinGuildId($serverId, $data['guild_id']),
            'wall_durability'     => $wall,
            'wall_durability_max' => $wall,
			'buff'				  => $data['buff'],
            'create_time'         => date('Y-m-d H:i:s'),
		);
		if($extra){
			$saveData = array_merge($saveData, $extra);
		}
		$ret = $o->create($saveData);
		if(!$ret)
			return false;
		return $o->affectedRows();
	}
	
	public function getQueueNum($playerId=0){
        return (new WarfareServiceConfig)->dicGetOne('wf_legion_count_limit');
    }
	
	public function getMaxArmyNum($playerId=0){
       return (new WarfareServiceConfig)->dicGetOne('wf_legion_count_limit');
    }
	
	public function getArmyGeneralNum($playerId){
        return 6;
    }
	
	public function getByGuildId($guildId){
		return self::find(["battle_id={$this->battleId} and guild_id={$guildId}"])->toArray();
	}
	
    /**
     * 从当前服抓取数据复制到pk服
     * 
     * 
     * @return <type>
     */
	public function cpData($playerId, $server_id=0){
		global $config;
        if($server_id!=0) {
            $serverId = $server_id;
        } else {
            $serverId = $config->server_id;
        }
		$this->find(["battle_id={$this->battleId} and player_id={$playerId}"])->delete();
		//$Player = new Player;
		//$player = $Player->getByPlayerId($playerId);
		$player = (new ModelBase)->getByServer($serverId, 'Player', 'getByPlayerId', [$playerId, false, true]);
		$playerGeneralBuff = (new ModelBase)->getByServer($serverId, 'PlayerGeneralBuff', 'getByPlayerId', [$playerId]);
		unset($playerGeneralBuff['id'], $playerGeneralBuff['player_id']);
		$playerGeneralBuff = array_diff($playerGeneralBuff, [0]);
		$player['buff'] = json_encode($playerGeneralBuff);
		
		if(!$this->add($playerId, $serverId, $player)){
			return false;
		}
		$this->clearDataCache($playerId);
		return true;
	}
	
	public static function joinGuildId($serverId, $guildId){
		return $serverId * 1000000 + $guildId;
	}
	
	public static function parseGuildId($guildId){
		$len = strlen($guildId);
		return ['server_id'=>substr($guildId, 0, $len-6)*1, 'guild_id'=>substr($guildId, -6)*1];
	}
	
	public static function parsePlayerId($playerId){
        $len = strlen($playerId);
        return ['server_id'=>substr($playerId, 0, $len-6)*1, 'player_id'=>substr($playerId, -6)*1];
	}

	public function addContinueKill($playerId, $player, $cb){
		$ret = $this->alter($playerId, ['continue_kill'=>'@ck := continue_kill+1']);
		$ck = $this->sqlGet('select @ck')[0]['@ck'];
		if($ret){
			$guilds = (new CrossBattle)->getADGuildId($cb);
			foreach(['attack', 'defend'] as $_t){
				$playerIds = [];
				$serverId = self::parseGuildId($guilds[$_t])['server_id'];
				if($serverId){
					$members = $this->getByGuildId($guilds[$_t]);
					foreach($members as $_d){
						if(!$_d['status']) continue;
						$playerIds[] = $_d['player_id'];
					}
					crossSocketSend($serverId, ['Type'=>'cross', 'Data'=>['playerId'=>$playerIds, 'type'=>'continuekill', 'nick'=>$player['nick'], 'avatar'=>$player['avatar_id'], 'num'=>$ck]]);
				}
			}
		}
	}
}