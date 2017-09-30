<?php
class CityBattlePlayer extends CityBattleModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	public $battleId;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache($this->id);
	}
	
	public function getByPlayerId($id, $forDataFlag=false, $sysFlag=false){
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
            } elseif($sysFlag){
                return false;
            } else{
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
		$Map = new CityBattleMap;
        $map = CityBattleMap::findFirst(["battle_id={$this->battleId} and status=1 and player_id={$player['player_id']} and map_element_origin_id=406"]);
        if($map) {
            $player['map_id']    = intval($map->id);
            $player['x']         = intval($map->x);
            $player['y']         = intval($map->y);
            $player['is_in_map'] = 1;//在地图里：1 不在地图里：0;
			$player['area'] = intval($map->area);
			$player['section'] = intval($map->section);
        } else {
            $player['map_id']    = 0;
            $player['x']         = $player['prev_x'];
            $player['y']         = $player['prev_y'];
            $player['is_in_map'] = 0;//在地图里：1 不在地图里：0;
			$player['area'] = null;
			$player['section'] = null;
        }
        return $player;
    }
	
	public function getCurrentBattleId($playerId, &$ret=''){
		$currentRoundId = (new CityBattleRound)->getCurrentRound();
		if(!$currentRoundId || !$ret = $this->findFirst(["round_id={$currentRoundId} and player_id={$playerId}"])){
			return false;
		}
		$ret = $ret->toArray();
		$this->battleId = $ret['battle_id'];
		$ret = $this->afterFindPlayer($ret);
		return $ret['battle_id'];
	}
	
	public function alter($playerId, array $fields){
        $ret = $this->updateAll($fields, ['player_id'=>$playerId, 'battle_id'=>$this->battleId]);
		if(isset($fields['wall_durability'])){
			$CityBattleMap = new CityBattleMap;
			$CityBattleMap->battleId = $this->battleId;
			$CityBattleMap->updateAll(['rowversion'=>'rowversion+1'], ['battle_id'=>$this->battleId, 'player_id'=>$playerId, 'map_element_origin_id'=>406]);
			$CityBattleMap->clearDataCache();
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
        //$currentRoundId = (new CityBattleRound)->getCurrentRound();
        //if(!$currentRoundId) return false;
		if($this->findFirst(["battle_id={$this->battleId} and player_id={$id}"])){
			return false;
		}
		$lv = $data['castle_lv'];
		//$formula = (new CountryBasicSetting)->dicGetOne('wf_playercastle_hitpoint');
		//eval('$wall = '.$formula.';');
		$o = new self;
		$saveData = array(
		    'round_id'            => $extra['round_id'],//轮次round_id
            'battle_id'           => $this->battleId,
            'player_id'           => $id,
            'server_id'           => $serverId,
			'camp_id'         	  => $data['camp_id'],
            'nick'                => $data['nick'],
            'avatar_id'           => $data['avatar_id'],
            'level'               => $data['level'],
			'castle_lv'           => $data['castle_lv'],
            'guild_id'            => self::joinGuildId($serverId, $data['guild_id']),
			'guild_name'		  => @$extra['guild_name'],
            'wall_durability'     => 0,
            'wall_durability_max' => 0,
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
        return (new CountryBasicSetting)->dicGetOne('wf_legion_count_limit');
    }
	
	public function getMaxArmyNum($playerId=0){
       return (new CountryBasicSetting)->dicGetOne('wf_legion_count_limit');
    }
	
	public function getArmyGeneralNum($playerId){
        return 6;
    }
	
	public function getByCampId($campIds){
		if(!is_array($campIds)){
			$campIds = [$campIds];
		}
		return self::find(["battle_id={$this->battleId} and camp_id in (".join(',', $campIds).")"])->toArray();
	}
	
	public function getGuildMemberNumByCampId($campId){
		$ret = self::find(["battle_id={$this->battleId} and camp_id={$campId}"])->toArray();
		$group = [];
		foreach($ret as $_r){
			if(!$_r['status']) continue;
			@$group[$_r['guild_id']]++;
		}
		return $group;
	}
	
    /**
     * 从当前服抓取数据复制到pk服
     * 
     * 
     * @return <type>
     */
	public function cpData($playerId, $roundId, $server_id=0){
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
		$extra = [];
		if($player['guild_id']){
			$extra['guild_name'] = (new ModelBase)->getByServer($serverId, 'Guild', 'getGuildInfo', [$player['guild_id'], true, false])['short_name'];
		}
		$playerGeneralBuff = (new ModelBase)->getByServer($serverId, 'PlayerGeneralBuff', 'getByPlayerId', [$playerId]);
		unset($playerGeneralBuff['id'], $playerGeneralBuff['player_id']);
		//获取城战科技
		$cbb = (new CityBattleBuff)->getByCampId($player['camp_id']);
		foreach($cbb as $_cbb){
			@$playerGeneralBuff[$_cbb['buff_name']] += $_cbb['buff_num'];
		}
		//获取令箭加成
		$cbs = (new CityBattleSign)->getPlayerSign($playerId, $roundId);
		if($cbs){
			//$signBuff = json_decode((new CountryBasicSetting)->dicGetOne('sign_up_buff'), true);
			$signBuff = [
				1=>[
					'infantry_life_plus'=>5000,
					'infantry_atk_plus'=>5000,
					'cavalry_def_plus'=>5000,
					'cavalry_life_plus'=>5000,
					'cavalry_atk_plus'=>5000,
					'archer_def_plus'=>5000,
					'archer_life_plus'=>5000,
					'archer_atk_plus'=>5000,
					'siege_def_plus'=>5000,
					'siege_life_plus'=>5000,
					'siege_atk_plus'=>5000,
				],
				2=>[
					'infantry_life_plus'=>5000,
					'cavalry_def_plus'=>5000,
					'cavalry_life_plus'=>5000,
					'archer_def_plus'=>5000,
					'archer_life_plus'=>5000,
					'siege_def_plus'=>5000,
					'siege_life_plus'=>5000,
				],
			];
			if(isset($signBuff[$cbs['sign_type']])){
				foreach($signBuff[$cbs['sign_type']] as $_name => $_sbb){
					@$playerGeneralBuff[$_name] += $_sbb;
				}
			}
		}
		
		$playerGeneralBuff = array_diff($playerGeneralBuff, [0]);
		$player['buff'] = json_encode($playerGeneralBuff);
				
		$extra['round_id'] = $roundId;
		if(!$this->add($playerId, $serverId, $player, $extra)){
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
			$members = $this->find(['battle_id='.$this->battleId.' and status>0'])->toArray();
			$playerIds = [];
			foreach($members as $_d){
				$_serverId = self::parsePlayerId($_d['player_id'])['server_id'];
				$playerIds[$_serverId][] = $_d['player_id'];
			}
			foreach($playerIds as $_serverId => $_playerIds){
				crossSocketSend($_serverId, ['Type'=>'citybattle', 'Data'=>['playerId'=>$_playerIds, 'type'=>'continuekill', 'nick'=>$player['nick'], 'avatar'=>$player['avatar_id'], 'num'=>$ck]]);
			}
		}
	}
}