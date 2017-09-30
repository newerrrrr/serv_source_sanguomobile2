<?php
//联盟科技
class GuildScience extends ModelBase{
	public $blacklist = array('guild_id', 'create_time', 'update_time', 'rowversion');
	public $guildMapElementArr = [];//当前可造的联盟建筑信息
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearGuildCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$guildId = $player['guild_id'];
		return $this->getByGuildId($guildId, $forDataFlag);
    }
	
	public function getByGuildId($guildId, $forDataFlag=false){
        $data = Cache::getGuild($guildId, __CLASS__);
        if(!$data) {
            $data = self::find(["guild_id={$guildId}"])->toArray();

            Cache::setGuild($guildId, __CLASS__, $data);
        }
		$data = $this->adapter($data);
        if($forDataFlag) {
            return filterFields($data, $forDataFlag, $this->blacklist);
        } else {
            return $data;
        }
    }
	
	public function getByscienceType($playerId, $scienceType, $forDataFlag=false){
		$player = (new Player)->getByPlayerId($playerId);
		$guildId = $player['guild_id'];
		return $this->getByscienceTypeFromGuildId($guildId, $scienceType, $forDataFlag);
    }
	
	public function getByscienceTypeFromGuildId($guildId, $scienceType, $forDataFlag=false){
		$data = self::find(["guild_id={$guildId} and  science_type=".$scienceType])->toArray();
		if(!$data){
			if(!$this->add($guildId, $scienceType)){
				return false;
			}
			$data = self::find(["guild_id={$guildId} and  science_type=".$scienceType])->toArray();
		}
		$data = $this->adapter($data);
        if($forDataFlag) {
            return filterFields($data, $forDataFlag, $this->blacklist)[0];
        } else {
            return $data[0];
        }
    }
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($guildId, $scienceType){
		if($this->find(array('guild_id='.$guildId. ' and science_type='.$scienceType))->toArray()){
			return false;
		}
		//查找scienceType是否存在
		$AllianceScience = new AllianceScience;
		$allianceScience = $AllianceScience->getByScienceType($scienceType, 1);
		if(!$allianceScience)
			return false;
		
		$ret = $this->create(array(
			'guild_id' => $guildId,
			'science_type' => $scienceType,
			'science_level' =>0,
			'science_exp' => 0,
			'science_level_type' =>$allianceScience['level_type'],
			'finish_time' => '0000-00-00 00:00:00',
			'status'=>0,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}

	public function addExp($exp){
		//获取配置
		$AllianceScience = new AllianceScience;
		$allianceScience = $AllianceScience->getByScienceType($this->science_type, $this->science_level+1);
		
		$now = date('Y-m-d H:i:s');
		$data = array(
			'science_exp'=>'least('.$allianceScience['levelup_exp'].', science_exp+'.$exp.')', 
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'",
		);
		
		//判断是否经验满
		if($this->science_exp+$exp >= $allianceScience['levelup_exp']){
			if(!$allianceScience['up_time']){
				$data['status'] = 0;
			}else{
				$data['status'] = 1;
			}
			$data['science_level'] = 'science_level+1';
			$data['science_exp'] = 0;
			//增加guild buff
			$GuildBuff = new GuildBuff;
			foreach($allianceScience['buff'] as $_b){
				if(!$GuildBuff->setGuildBuff($this->guild_id, $_b[0], $_b[1])){
					return false;
				}
			}
			//通知所有成员
			$members = (new PlayerGuild)->getAllGuildMember($this->guild_id);
			if(!$members){
				return false;
			}
			$memberIds = Set::extract('/player_id', $members);
			socketSend(['Type'=>'guild_science', 'Data'=>['playerId'=>$memberIds]]);
		}

		$ret = $this->updateAll($data, ["id"=>$this->id, "rowversion"=>"'".$this->rowversion."'"]);
		$this->clearGuildCache($this->guild_id);
		if(!$ret)
			return false;
		return true;
	}
	
	public function levelup($needTime){
		$now = date('Y-m-d H:i:s');
		$data = array(
			'status'=>2,
			'finish_time' => date('Y-m-d H:i:s', time()+$needTime),
			'update_time'=>$now,
			'rowversion'=>uniqid(),
		);
		$ret = $this->saveAll($data, "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearGuildCache($this->guild_id);
		if(!$ret)
			return false;
		return true;
	}
	
	public function levelupFinish($guildId){
		$now = date('Y-m-d H:i:s');
		$data = array(
			//'science_level'=>'science_level+1',
			//'science_exp'=>0,
			'status'=>0,
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		);
		$d = $this->findFirst(["guild_id=".$guildId." and status=2 and finish_time <= '".$now."'"]);
		if($d){
			$ret = $this->updateAll($data, ["guild_id"=>$guildId, 'status'=>2, 'finish_time <='=>"'".$now."'"]);
			$this->clearGuildCache($guildId);
			if(!$ret)
				return false;
			
			//增加guild buff
			/*$AllianceScience = new AllianceScience;
			$allianceScience = $AllianceScience->getByScienceType($d->science_type, $d->science_level+1);
			$GuildBuff = new GuildBuff;
			foreach($allianceScience['buff'] as $_b){
				if(!$GuildBuff->setGuildBuff($guildId, $_b[0], $_b[1])){
					return false;
				}
			}
			*/
			return true;
		}
		return false;
	}

    /**
     * 检查层级是否开启
     * 
     * @param <type> $guildId 
     * @param <type> $levelType 
     * 
     * @return <type>
     */
	public function checkLevelType($guildId, $levelType, $needSum){
		if($levelType == 1)
			return true;
		$guildScience = $this->getByGuildId($guildId);
		if(!$guildScience){
			return false;
		}
		$sum = 0;
		foreach($guildScience as $_r){
			if($_r['science_level_type'] < $levelType){
				$sum += $_r['science_level'];
			}
		}
		if($sum >= $needSum)
			return true;
		return false;
	}
	
    /**
     * 判断是否可
     * 
     * 
     * @return <type>
     */
	public function checkMapElement($guildId, $mapElementId){
		$canBuildNum = 1;
		$ar = [101=>444, 201=>451];
		if($mapElementId == 101){//基地
			$canBuildNum = 1;
		}elseif($mapElementId == 201){//箭塔
			$canBuildNum = 0;
		}
		if(isset($ar[$mapElementId])){
			$canBuildNum += (new GuildBuff)->getGuildBuff($guildId, $ar[$mapElementId]);
		}
		/*$AllianceBuildDescription = new AllianceBuildDescription;
		$abd = $AllianceBuildDescription->dicGetAll();
		$types = [];
		foreach($abd as $_abd){
			if($_abd['need_alliance_science']){
				$types[$_abd['element_id']][$_abd['need_alliance_science']] = $_abd['count'];
			}
		}
		if(!isset($types[$mapElementId])){
			$canBuildNum = 1;
		}else{
			$guildScience = $this->getByGuildId($guildId);
			$myTypes = [];
			$AllianceScience = new AllianceScience;
			foreach($guildScience as $_gs){
				$tmp = $AllianceScience->getByScienceType($_gs['science_type'], $_gs['science_level']);
				$myTypes[] = $tmp['id'];
			}
			$inter = array_values(array_intersect($myTypes, array_keys($types[$mapElementId])));
			if(!$inter){
				$canBuildNum = 1;
			}else{
				$canBuildNum = $types[$mapElementId][$inter[0]]*1;
			}
		}*/
		//获取已经建造数量
		$Map = new Map;
		$hasBuildNum = Map::count(["guild_id={$guildId} and  map_element_id=".$mapElementId]);
		$this->guildMapElementArr[] = ['map_element_id'=>$mapElementId,'current'=>$hasBuildNum, 'max'=>$canBuildNum];//提供给前端消息用

		//判断可否继续建造
		if($hasBuildNum < $canBuildNum)
			return true;
		return false;
		
	}
}