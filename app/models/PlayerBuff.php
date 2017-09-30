<?php
/**
 * 玩家buff表
 * 
 */
class PlayerBuff extends ModelBase{
    public $blacklist = array('player_id');
    
    public $buffTypeAfterCallGetPlayerBuff = 0;//调用完getPlayerBuff后的，当前的buff的buff_type

    private $guildId = false;
    /**
     * 设置guildId，为guildBuff准备
     * @param  int $guildId 
     */
    public function assignGuildId($guildId){
        $this->guildId = $guildId;
        return $this;
    }

    public function afterSave(){
        $this->clearDataCache();
    }
    /**
     * 生成新记录
     * @param  int $playerId 
     * @return bool           
     */
    public function newPlayerBuff($playerId){
        $self = new self;
        $self->player_id = $playerId;
        return $self->save();
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
     * 获取某一个buff值
     *
     * ```php
     * $PlayerBuff->getPlayerBuff(100029, 'avoid_battle');
     * ```
     * @param  int      $playerId  
     * @param  string   $buffName
     * @return int      $thisBuff  buff value
	 *!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 *! ps:如果修改这里，需要修改getBuffAction!!!!!!!!
	 *!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     */
    public function getPlayerBuff($playerId, $buffName, $position=0){
		if(!$playerId)
			return 0;
        $buff = (new Buff)->dicGetOneByName($buffName);
        if($buff) {
            //case a: basic buff
            $playerBuff = $this->getByPlayerId($playerId);
            $basicBuff  = @$playerBuff[$buffName];
			
			//case :general buff
			$playerGeneralBuff = (new PlayerGeneralBuff)->getByPlayerId($playerId);
			$basicGeneralBuff = @$playerGeneralBuff[$buffName];
			
            //case b: buff temp
            $buffTemp   = (new PlayerBuffTemp)->getNumByBuffName($playerId, $buffName, $position);
			
            $thisBuff   = $basicBuff + $basicGeneralBuff + $buffTemp;
            if($buff['buff_type']==1) {//万分比
                $thisBuff = floatval($thisBuff/DIC_DATA_DIVISOR);
            }
			//echo '['.$thisBuff.']';
            // case c1: general buff  NOTE:武将驻守的buff传过来的已经是万分比了
            $generalBuff = (new PlayerBuild)->calcGeneralBuff($playerId, $position);
            if($generalBuff) {
				$thisBuff += @$generalBuff['general'][$buffName];
                /*foreach($generalBuff['general'] as $k=>$v) {
                    if($buffName==$k) {
                        $thisBuff += $v;
                        break;
                    }
                }*/
            }
			//echo '['.$thisBuff.']';
            // case c2 NOTE:装备的buff传过来的已经是万分比了
            if($generalBuff) {
				$thisBuff += @$generalBuff['equip'][$buffName];
                /*foreach($generalBuff['equip'] as $k=>$v) {
                    if($buffName==$k) {
                        $thisBuff += $v;
                        break;
                    }
                }*/
            }

            $this->buffTypeAfterCallGetPlayerBuff = $buff['buff_type'];
			
			//guild buff
            if($this->guildId!==false) {
                $guildId = $this->guildId;
                $this->guildId = false;
            } else {
                $player = (new Player)->getByPlayerId($playerId);
                $guildId = $player['guild_id'];
            }
			if($guildId){
				$thisBuff += (new GuildBuff)->getGuildBuff($guildId, $buff['id']);
			}
			
            return $thisBuff;
        }
        return 0;
    }
	
	public function getPlayerBuffs($playerId, $buffVs, $position=0, $percentFlag = false){
		if(!$playerId)
			return [];
		//整理buff
		$buff = (new Buff)->dicGetAll();
		$buffs = [];
		$buffNames = [];
		foreach($buff as $_b){
			if(in_array($_b['name'], $buffVs) || in_array($_b['id'], $buffVs)){
				$buffs[$_b['id']] = $_b;
				$buffNames[] = $_b['name'];
			}
		}
		$buffIds = array_keys($buffs);
		
		$player = (new Player)->getByPlayerId($playerId);
		$guildId = $player['guild_id'];
		
		$playerBuff = $this->getByPlayerId($playerId);
		
		$playerGeneralBuff = (new PlayerGeneralBuff)->getByPlayerId($playerId);
		
		$buffTemps   = (new PlayerBuffTemp)->getNumByBuffNames($playerId, $buffNames, $position);
		
		$generalBuff = (new PlayerBuild)->calcGeneralBuff($playerId, $position);
		
		if($guildId)
			$guildBuffs = (new GuildBuff)->getGuildBuffs($guildId, $buffIds);
		
		$ret = [];
		foreach($buffs as $_id => $_b){
			$basicBuff = @$playerBuff[$_b['name']];
			$basicGeneralBuff = $playerGeneralBuff[$_b['name']];
			$buffTemp = @$buffTemps[$_b['name']];
			$thisBuff = $basicBuff + $basicGeneralBuff + $buffTemp;
			if($_b['buff_type']==1) {//万分比
				$thisBuff = floatval($thisBuff/DIC_DATA_DIVISOR);
            }
			if($generalBuff){
				$thisBuff += @$generalBuff['general'][$_b['name']] + @$generalBuff['equip'][$_b['name']];
			}
			if($guildId){
				$thisBuff += @$guildBuffs[$_id];
			}
			if($percentFlag && $_b['buff_type']==1){
				$thisBuff = 100*$thisBuff . '%';
			}
			$ret[$_b['name']] = $thisBuff;
		}
		
        return $ret;
    }

    /**
     * 供战斗使用buff
     * @param  int $playerId 
     * @return array   
     */
    public function getBattleBuff($playerId){
        $battleBuffName = array_unique([
'infantry_atk_plus',
'cavalry_atk_plus',
'archer_atk_plus',
'siege_atk_plus',
'infantry_life_plus',
'cavalry_life_plus',
'archer_life_plus',
'siege_life_plus',
'infantry_def_plus',
'cavalry_def_plus',
'archer_def_plus',
'siege_def_plus',
'citybattle_infantry_def_plus',
'citybattle_infantry_life_plus',
'citybattle_infantry_atk_plus',
'citybattle_cavalry_def_plus',
'citybattle_archer_atk_plus',
'citybattle_siege_def_plus',
'citybattle_siege_life_plus',
'citybattle_siege_atk_plus',
'citybattle_cavalry_life_plus',
'citybattle_cavalry_atk_plus',
'citybattle_archer_def_plus',
'citybattle_archer_life_plus',
'infantry_atk_reduce',
'cavalry_atk_reduce',
'archer_atk_reduce',
'siege_atk_reduce',
'infantry_life_reduce',
'cavalry_life_reduce',
'archer_life_reduce',
'siege_life_reduce',
'infantry_def_reduce',
'cavalry_def_reduce',
'archer_def_reduce',
'siege_def_reduce',
'infantry_reduce_infantry_damage',
'cavalry_reduce_infantry_damage',
'archer_reduce_infantry_damage',
'siege_reduce_infantry_damage',
'infantry_reduce_cavalry_damage',
'cavalry_reduce_cavalry_damage',
'archer_reduce_cavalry_damage',
'siege_reduce_cavalry_damage',
'infantry_reduce_archer_damage',
'cavalry_reduce_archer_damage',
'archer_reduce_archer_damage',
'siege_reduce_archer_damage',
'infantry_reduce_siege_damage',
'cavalry_reduce_siege_damage',
'archer_reduce_siege_damage',
'siege_reduce_siege_damage',
'arrow_atk_reduce',
'wood_atk_reduce',
'rock_atk_reduce',
'tower_atk_plus',
'pitfall_activated_probability',
'fieldbattle_infantry_atk_plus',
'fieldbattle_infantry_def_plus',
'fieldbattle_infantry_life_plus',
'fieldbattle_cavalry_atk_plus',
'fieldbattle_cavalry_def_plus',
'fieldbattle_cavalry_life_plus',
'fieldbattle_archer_atk_plus',
'fieldbattle_archer_def_plus',
'fieldbattle_archer_life_plus',
'fieldbattle_siege_atk_plus',
'fieldbattle_siege_def_plus',
'fieldbattle_siege_life_plus',
'positive_battle_dead_trans_wounded',
            ]);
        if($playerId==0) {
            foreach($battleBuffName as $v) {
                $battleBuff[$v] = 0;
            }
        } else {
            //$playerBuff = $this->getByPlayerId($playerId);
            //$battleBuff = [];
			$battleBuff = (new PlayerBuff)->getPlayerBuffs($playerId, $battleBuffName);
            /*foreach($battleBuffName as $buffName) {
                $battleBuff[$buffName] = $this->getPlayerBuff($playerId, $buffName);
            }*/
        } 
        return $battleBuff;
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
    		return $ret;
        }
        return false;
    }
	
	public function resetData($playerId){
		self::find("player_id={$playerId}")->delete();
		$ret = $this->newPlayerBuff($playerId);
		$this->clearDataCache($playerId);
		return $ret;
	}

    /**
     * 重新计算buff
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function recalculate($playerId){
		$this->resetData($playerId);
		//todo buff
		//重算天赋
		
		//重算科技
		
		//重算联盟科技
		
		$this->clearDataCache($playerId);
		return true;
	}
	
    /**
     * 是否免战
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	/*public function isAvoidBattle($playerId){
		if($this->getPlayerBuff($playerId, 'avoid_battle')){
			return true;
		}
		return false;
	}
	
	public function offAvoidBattle($playerId){
		$buffName = 'avoid_battle';
		(new PlayerBuffTemp)->delByBuffName($playerId, [$buffName]);
		$this->updateAll([$buffName=>0], ['player_id'=>$playerId]);
		$this->clearDataCache($playerId);
	}*/

    /**
     * 科技研究速度增加
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function getScienceBuff($playerId){
		return $this->getPlayerBuff($playerId, 'science_research_speed');
	}
	
	/**
     * 升级装备白银消耗减少
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function getEquipLvSilverBuff($playerId){
		$num = $this->getPlayerBuff($playerId, 'silver_reduce');
		$PlayerBuild = new PlayerBuild;
		if($playerBuild = $PlayerBuild->getByOrgId($playerId, 9)){
			$Build = new Build;
			$build = $Build->dicGetOne($playerBuild[0]['build_id']);
			$num += $build['output']['17']/DIC_DATA_DIVISOR;
		}
		return $num;
	}
	
	/**
     * 分解白银增加
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function getEquipDecompositionBuff($playerId){
		$num = $this->getPlayerBuff($playerId, 'decomposition_equipment_silver_plus');
		$PlayerBuild = new PlayerBuild;
		if($playerBuild = $PlayerBuild->getByOrgId($playerId, 9)){
			$Build = new Build;
			$build = $Build->dicGetOne($playerBuild[0]['build_id']);
			$num += $build['output']['18']/DIC_DATA_DIVISOR;
		}
		return $num;
	}
	
	public function getCollectionBuff($playerId, $armyId, $type){
		$val = $this->getPlayerBuff($playerId, $type.'_gathering_speed');
		$pau = (new PlayerArmyUnit)->getByArmyId($playerId, $armyId);
		if($pau){
			$PlayerGeneral = new PlayerGeneral;
			foreach($pau as $_pau){
				if($_pau['general_id']){
					$pgAttr = $PlayerGeneral->getTotalAttr($playerId, $_pau['general_id']);
					if(@$pgAttr['buff'][$type.'_gathering_speed']){
						$val += $pgAttr['buff'][$type.'_gathering_speed'];
					}
				}
			}
		}
		return $val;
	}
	
    /**
     * 死转伤主动使用
     * 
     * 
     * @return <type>
     */
	public function useDeadtoWound($playerId){
		$ret = $this->getPlayerBuff($playerId, 'start_positive_battle_dead_trans_wounded');
		if($ret){
			(new PlayerBuffTemp)->delByTempId($playerId, [103]);
			//更新主动技能effecttime
			(new PlayerMasterSkill)->upEffect($playerId, 101601, '0000-00-00 00:00:00');
		}
		return $ret;
	}
}