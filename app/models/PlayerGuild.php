<?php
//建筑
class PlayerGuild extends ModelBase{
	public $blacklist = ['update_time'];
	const RANK_R5 = 5;
	const RANK_R4 = 4;
	const RANK_R3 = 3;
	const RANK_R2 = 2;
	const RANK_R1 = 1;

	public $campId = 0;
	

	/**
	 * 获取所有公会成员信息
	 * @param  int $guildId 帮会id
	 * @param  bool $associationFlag 是否返回关联表数据
	 * @return array
	 */
	public function getAllGuildMember($guildId, $associationFlag=true){
	    if(!$guildId) return [];
        $re = Cache::getGuild($guildId, __CLASS__);
        if(!$re) {
            $re = self::find(["guild_id={$guildId}"])->toArray();
            $re = $this->adapter($re, false, [
                ['field'=>'cross_army_info', 'fn'=>function($v){
                    if(empty($v)) return [];
                    return json_decode($v, true);
                }]
            ]);
            Cache::setGuild($guildId, __CLASS__, $re);
        }
        if($associationFlag) {
            $r           = [];
            $Player      = new Player;
            $PlayerBuild = new PlayerBuild;
            foreach ($re as $k => $v) {
                $pid                                   = $v['player_id'];
                $player                                = $Player->getByPlayerId($pid);
                $r[$pid]                               = $v;
                $r[$pid]['Player']['fuya_build_level'] = $PlayerBuild->getPlayerCastleLevel($pid);
                $r[$pid]['Player']['nick']             = $player['nick'];
                $r[$pid]['Player']['level']            = $player['level'];
                $r[$pid]['Player']['power']            = $player['power'];
                $r[$pid]['Player']['avatar_id']        = $player['avatar_id'];
                $r[$pid]['Player']['last_online_time'] = $player['last_online_time'];
                $r[$pid]['Player']['job']              = $player['job'];
                $r[$pid]['Player']['x']                = $player['x'];
                $r[$pid]['Player']['y']                = $player['y'];
                $r[$pid]['Player']['map_id']           = $player['map_id'];
            }
        } else {
            $r = $re;
        }
        return $r;
    }
    /**
     * 获取联盟内跨服战相关成员信息列表
     *
     * @param $guildId
     *
     * @return mixed
     */
    public function getCrossMembers($guildId){
        $members        = [];
        $joinedNumber   = 0;
        $allGuildMember = $this->getAllGuildMember($guildId);
        foreach($allGuildMember as $k=>$v) {
            $t['player_id']        = $v['player_id'];
            $t['nick']             = $v['Player']['nick'];
            $t['power']            = $v['Player']['power'];
            $t['avatar_id']        = $v['Player']['avatar_id'];
            $t['last_online_time'] = $v['Player']['last_online_time'];
            if($v['cross_application_flag']==1) {//申请者
                $t['application_flag'] = 1;
            } else {
                $t['application_flag'] = 0;
            }
            if($v['cross_joined_flag']==1) {//已选择
                $joinedNumber++;
                $t['read2join_flag'] = 1;
            } else {
                $t['read2join_flag'] = 0;
            }
            $members[] = $t;
        }
        $data['joined_number'] = $joinedNumber;
        $data['members']       = $members;
        return $data;
    }
    /**
     * 获取玩家player_guild数据
     * @param  int  $playerId   
     * @param  boolean $forDataFlag 是否用来返回Data接口数据
     * @return array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
    	$player = (new Player)->getByPlayerId($playerId);
		$guildId = $player['guild_id'];
		if($guildId) {
			$getAllGuildMember = $this->getAllGuildMember($guildId);
			$re = filterFields([$getAllGuildMember[$playerId]], $forDataFlag, $this->blacklist)[0];
		} else {
			$re = [];
		}
		return $re;
    }

    /**
     * set camp_id for method `addNew`
     * @param $campId
     *
     * @return $this
     */
    public function setCampId($campId) {
        $this->campId = $campId;
        return $this;
    }
	/**
	 * 添加新记录
	 * @param [type] $playerId [description]
	 * @param [type] $guildId  [description]
	 * @param [type] $rank     [description]
	 */
	public function addNew($playerId, $guildId, $rank=self::RANK_R1){
        $self              = new self;
        $self->player_id   = $playerId;
        $self->guild_id    = $guildId;
        $self->rank        = $rank;
        $self->create_time = date("Y-m-d H:i:s");
        $self->update_time = date("Y-m-d H:i:s");
        $self->save();
        $this->clearGuildCache($guildId);

        $Player = new Player;
        $Player->setGuildId($playerId, $guildId, $this->campId);
        $Guild = new Guild;
        $Guild->incGuildNum($guildId);
        //删除所有该玩家的入盟申请
        $re = PlayerGuildRequest::find("player_id={$playerId}");
        if($re->toArray()) {
            $PlayerGuildRequest = new PlayerGuildRequest;
            foreach($re->toArray() as $k=>$v) {//清除所有帮会的申请入盟信息
                $PlayerGuildRequest->clearCache($playerId, $v['guild_id']);
            }
            $re->delete();
        }
        //推送到联盟聊天
        if($rank!=self::RANK_R5) {
            $player = $Player->getByPlayerId($playerId);
            $pushData = [
                'type' => 6,
                'nick' => $player['nick'],
            ];
            $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
            socketSend($data);
        }
        (new PlayerCommonLog)->add($playerId, ['type'=>'加入联盟', 'memo'=>['playerId'=>$playerId, 'guildId'=>$guildId, 'rank'=>$rank]]);
        return $self->toArray();
	}
	/**
	 * 更变玩家阶级/删除记录
	 * 
	 * @param  [type] $playerId [description]
	 * @param  [type] $rank     阶级 1-4正式会员 5为会长 0即为删除记录
	 * 
	 * @return [type]           [description]
	 */
	public function updatePlayerRank($playerId, $rank){
		$ret = $this->getByPlayerId($playerId);
		if(!empty($ret)){
			$guildId = $ret['guild_id'];
			if($rank>0){
				$this->updateAll(['rank'=>$rank],['id'=>$ret['id']]);
			}else{
				$this->assign($ret)->delete();
				(new Player)->setGuildId($playerId, 0);
				(new Guild)->decGuildNum($guildId);
			}
			$this->clearGuildCache($guildId);
		}
	}
    /**
     * 解散player_guild
     * @param  int $guildId 联盟id
     */
    public function dismissPlayerGuild($guildId){
        $all                = $this->getAllGuildMember($guildId);
        $Player             = new Player;
        $PlayerGuildRequest = new PlayerGuildRequest;
		$PlayerGuildDonateStat = new PlayerGuildDonateStat;
        foreach($all as $k=>$v) {
            $Player->setGuildId($k, 0);
            $PlayerGuildRequest->clearCache($k, $guildId);//删除该玩家申请该联盟cache
			
			//清除贡献
			$PlayerGuildDonateStat->clearAll($k);
        }
        self::find("guild_id={$guildId}")->delete();
        $this->clearGuildCache($guildId);
        //删除所有申请该联盟的记录
        $re = PlayerGuildRequest::find("guild_id={$guildId}");
        if($re->toArray()) {
            $re->delete();
        }
    }

    /**
     * 取 玩家当日储存数量
     * @param  [type] $playerId [description]
     * @return [type]           [description]
     */
    public function getTodayStoreResouce($playerId){
        $ret = $this->getByPlayerId($playerId);
        if($ret['last_store_time']<strtotime(date("Y-m-d"))){
            return 0;
        }else{
            return $ret['last_day_store'];
        }
    }

    /**
     * 存 玩家当日储存数量
     * @param  [type] $playerId    [description]
     * @param  [type] $dayStoreNum [description]
     * @return [type]              [description]
     */
    public function setTodayStoreResouce($playerId, $dayStoreNum){
        $ret = $this->getByPlayerId($playerId);
        if($ret['last_store_time']<strtotime(date("Y-m-d"))){
            $this->updateAll(['last_store_time'=>qd(), "last_day_store"=>$dayStoreNum], ['id'=>$ret['id']]);
        }else{
            $this->updateAll(["last_day_store"=>$dayStoreNum], ['id'=>$ret['id']]);
        }
		$this->clearGuildCache($ret['guild_id']);
    }

    /**
     * 检查资源是否足够
     * @param  [type]  $playerId        [description]
     * @param  array   $needResourceArr 所需资源数量 ex: array('gold'=>10000, 'wood'=>10000, 'iron'=>500)
     * @return boolean                  [description]
     */
    function hasEnoughStoreResource($playerId, array $needResourceArr){
        $result = true;
        $playerGuildInfo = $this->getByPlayerId($playerId);
        foreach ($needResourceArr as $key=>$value) {
            if($playerGuildInfo["store_".$key]<$value){
                $result = false;
            }
        }
        return $result;
    }

    /**
     * 更改储存在仓库的资源
     * @param  int $playerId 
     * @param  array  $resource ['gold'=>9, 'food'=>-10, 'wood'=>11, 'stone'=>12, 'iron'=>13]
     */
    public function updateStoreResource($playerId, array $resource){
        if(empty($resource)) return false;

        $Master = new Master;
        $Player = new Player;
        $player = $Player->getByPlayerId($playerId);
        $playerGuildInfo = $this->getByPlayerId($playerId);
        
        $maxStoreArr = $Master->getMaxStoreNum($playerId, $player['level']);
        $unitResource0 = $this->getTodayStoreResouce($playerId);
        $unitResource1 = $resource['food']+$resource['gold']*1+$resource['wood']*4+$resource['stone']*12+$resource['iron']*32;
        $unitResource2 = $playerGuildInfo['store_food']+$playerGuildInfo['store_gold']*1+$playerGuildInfo['store_wood']*4+$playerGuildInfo['store_stone']*12+$playerGuildInfo['store_iron']*32;
        
        if($unitResource1>0){
            if($unitResource0+$unitResource1>$maxStoreArr['day']){
                goto SendErr;
            }

            if($unitResource1+$unitResource2>$maxStoreArr['all']){
                goto SendErr;
            } 
        }        

        
        $re = [];
        array_walk($resource, function($v, $k) use(&$resource, &$re){
            if($v>=0) {
                $re["store_".$k] = "store_".$k . '+' . abs($v);
            } elseif($v<0) {
                $v = "store_".$k . '-' . abs($v);
                $re["store_".$k] = "IF({$v}<0,0,{$v})";
            }
        });
        $this->affectedRows = $this->updateAll($re, ['id'=>$playerGuildInfo['id']]);
        if($this->affectedRows>0) {
            $this->clearGuildCache($playerGuildInfo['guild_id']);//清缓存
            if($unitResource1>0){
                $this->setTodayStoreResouce($playerId, $unitResource0+$unitResource1);
            }
            return true;
        }
        SendErr:
        return false;
    }

    /**
     * 取出所有联盟仓库储存资源
     * @param  [type] $playerId [description]
     * @param  [type] $targetPlayerId 取出资源玩家id，为0则全公会取出资源
     * @return [type]           [description]
     */
    public function takeOutAllResource($guildId, $targetPlayerId=0){
        $Player = new Player;
        $Map = new Map;
        $PlayerProjectQueue = new PlayerProjectQueue;
        $playerGuildList = $this->getAllGuildMember($guildId);
        $target = $Map->getGuildMapElement($guildId, 801);//获取联盟仓库
        if(empty($target)){
            return false;
        }

        
        foreach($playerGuildList as $playerGuildInfo){
            $playerId = $playerGuildInfo['player_id'];
            if(!empty($targetPlayerId) && $targetPlayerId!=$playerId){
                continue;
            }
            $player = $Player->getByPlayerId($playerId);
            $from = $target[0];

            if(empty($playerGuildInfo['store_gold']) && empty($playerGuildInfo['store_food']) && empty($playerGuildInfo['store_wood']) && empty($playerGuildInfo['store_stone']) && empty($playerGuildInfo['store_iron'])){
                continue;
            }

            $reduceResource = ['gold'=>(-1)*$playerGuildInfo['store_gold'], 'food'=>(-1)*$playerGuildInfo['store_food'], 'wood'=>(-1)*$playerGuildInfo['store_wood'], 'stone'=>(-1)*$playerGuildInfo['store_stone'], 'iron'=>(-1)*$playerGuildInfo['store_iron']];
                    $extraData    = [
                        'from_map_id' => $from['id'],
                        'from_x'      => $from['x'],
                        'from_y'      => $from['y'],
                        'to_map_id'   => $player['id'],
                        'to_x'        => $player['x'],
                        'to_y'        => $player['y'],
                        'carry_gold'  => $playerGuildInfo['store_gold'],
                        'carry_food'  => $playerGuildInfo['store_food'],
                        'carry_wood'  => $playerGuildInfo['store_wood'],
                        'carry_stone' => $playerGuildInfo['store_stone'],
                        'carry_iron'  => $playerGuildInfo['store_iron']
                    ];

            $this->updateStoreResource($playerId, $reduceResource);
            $needTime = $PlayerProjectQueue->calculateMoveTime($playerId, $from['x'], $from['y'], $player['x'], $player['y'], 5, 0);
            $PlayerProjectQueue->addQueue($playerId, $guildId, 0, PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHRETURN, $needTime, 0, [], $extraData);
        }


        
        return true;
       
    }

	public function isSameGuild($playerId1, $playerId2){
        $Player = new Player;
        $p1     = $Player->getByPlayerId($playerId1);
        $p2     = $Player->getByPlayerId($playerId2);
		if(!$p1 || !$p2)
			return false;
		if(!$p1['guild_id'] || !$p2['guild_id'] || $p1['guild_id'] != $p2['guild_id']){
			return false;
		}
		return true;
	}

	public function addMissionScore($playerId, $score){
		$ret = $this->getByPlayerId($playerId);
		//增加个人积分
		if(!$this->updateAll(['guild_mission_score'=>'guild_mission_score+'.$score],['id'=>$ret['id']])){
			return false;
		}
		
		//增加联盟积分
		(new Guild)->alter($ret['guild_id'], ['mission_score'=>'mission_score+'.$score]);

		$this->clearGuildCache($ret['guild_id']);
		return true;
	}
	
	public function resetMissionScore($playerId){
		$ret = $this->getByPlayerId($playerId);

		//增加个人积分
		if(!$this->updateAll(['guild_mission_score'=>0],['id'=>$ret['id']])){
			return false;
		}
		
		//增加联盟积分
		(new Guild)->alter($ret['guild_id'], ['mission_score'=>'mission_score-'.$ret['guild_mission_score']]);
		
		$this->clearGuildCache($ret['guild_id']);
		return true;
	}

    /**
     * 更新参赛标志
     *
     * @param $guildId
     * @param $playerIds
     */
	public function changeCrossJoinedFlag($guildId, $playerIds){
        $this->updateAll(['cross_joined_flag'=>1], ['guild_id'=>$guildId, 'cross_joined_flag'=>0,'player_id'=>$playerIds]);
        $this->clearGuildCache($guildId);
    }

    /**
     * 获取所有参赛成员
     *
     * @param $guildId
     *
     * @return array
     */
    public function getCrossJoinedMember($guildId) {
        $all = $this->getAllGuildMember($guildId, false);
        $re = [];
        foreach($all as $v) {
            if($v['cross_joined_flag']==1) {
                $re[] = $v;
            }
        }
        return $re;
    }

    /**
     * 更新默认军团
     *
     * @param $playerId
     * @param $guildId
     */
    public function updateDefaultCrossArmyInfo($playerId, $guildId){
        $crossArmyInfo = $this->getDefaultCrossArmyInfo($playerId);
        $this->updateAll(['cross_army_info'=>q(json_encode($crossArmyInfo))], ['guild_id'=>$guildId, 'player_id'=>$playerId]);
        $this->clearGuildCache($guildId);
        (new PlayerCommonLog)->add($playerId, ['type'=>'[跨服战军团]首次by脚本', 'cross_army_info'=>$crossArmyInfo]);//日志记录
        return $crossArmyInfo;
    }
    /**
     * 获取默认12武将信息
     *
     * @param       $playerId
     *
     * @return array
     */
    public function getDefaultCrossArmyInfo($playerId){
            $sql = <<<SQL_STAT
SELECT 
  b.power + (a.lv - 1) * 95+ c1.power + IF(c2.power is not null, c2.power, 0) + IF(c3.power is not null, c3.power, 0) + IF(c4.power is not null, c4.power, 0) allpower,
  a.general_id,
  a.cross_skill_id_1,
  a.`cross_skill_lv_1`,
  a.cross_skill_id_2,
  a.`cross_skill_lv_2`,
  a.cross_skill_id_3,
  a.`cross_skill_lv_3`,
  if(d1.if_active=1, d1.if_active, 0) s1,
  IF(d2.if_active=1, d2.if_active, 0) s2,
  IF(d3.if_active=1, d3.if_active, 0) s3
FROM player_general a 
  LEFT JOIN general b ON a.`general_id` = b.general_original_id 
  LEFT JOIN equipment c1 ON a.`weapon_id` = c1.id 
  LEFT JOIN equipment c2 ON a.`armor_id` = c2.id 
  LEFT JOIN equipment c3 ON a.`horse_id` = c3.id 
  LEFT JOIN equipment c4 ON a.`zuoji_id` = c4.id 
  
  LEFT JOIN battle_skill d1 ON a.cross_skill_id_1=d1.id
  LEFT JOIN battle_skill d2 ON a.cross_skill_id_2=d2.id
  LEFT JOIN battle_skill d3 ON a.cross_skill_id_3=d3.id
WHERE a.player_id = {$playerId}
ORDER BY b.general_quality DESC,
  a.star_lv DESC,
  allpower DESC 
LIMIT 12;
SQL_STAT;
        $re = $this->sqlGet($sql);
        $r = ['army' => [0 => [], 1 => []], 'skill' => [], 'total_skill'=>[]];
        if($re) {
            $skill = [];
            $totalSkill = [];
            array_walk($re, function(&$v) use (&$skill, &$totalSkill) {
                if($v) {
                    if(count($skill)<2) {
                        if ($v['s1'] == 1) $skill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_1'], 'lv'=>(int)$v['cross_skill_lv_1']];
                        if ($v['s2'] == 1) $skill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_2'], 'lv'=>(int)$v['cross_skill_lv_2']];
                        if ($v['s3'] == 1) $skill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_3'], 'lv'=>(int)$v['cross_skill_lv_3']];
                    }
                    if ($v['s1'] == 1) $totalSkill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_1'], 'lv'=>(int)$v['cross_skill_lv_1']];
                    if ($v['s2'] == 1) $totalSkill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_2'], 'lv'=>(int)$v['cross_skill_lv_2']];
                    if ($v['s3'] == 1) $totalSkill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_3'], 'lv'=>(int)$v['cross_skill_lv_3']];
                    unset($v['allpower']);
                    $v = (int)$v['general_id'];
                }
            });
            $r['skill'] = $skill;
            $r['total_skill'] = $totalSkill;
            if(count($re)>6) {
                $r['army'][0] = array_splice($re, 0, 6);
                $r['army'][1] = $re;
            } else {
                $r['army'][0] = $re;
            }
        }

//        dump($r);
        return $r;
    }

    /**
     * 获取输入武将的技能信息
     *
     * @param $playerId
     * @param $generalIds
     *
     * @return array|bool
     */
    public function getGeneralCrossSkill($playerId, $generalIds){
        if(empty($generalIds) || count($generalIds)>6) return false;
        $sqlGeneralIds = implode(",", $generalIds);

        $sql = <<<SQL_STAT
SELECT 
  a.general_id,
  a.cross_skill_id_1,
  a.`cross_skill_lv_1`,
  a.cross_skill_id_2,
  a.`cross_skill_lv_2`,
  a.cross_skill_id_3,
  a.`cross_skill_lv_3`,
  IF(d1.if_active, d1.if_active, 0) s1,
  IF(d2.if_active, d2.if_active, 0) s2,
  IF(d3.if_active, d3.if_active, 0) s3
FROM player_general a 
  LEFT JOIN battle_skill d1 ON a.cross_skill_id_1=d1.id
  LEFT JOIN battle_skill d2 ON a.cross_skill_id_2=d2.id
  LEFT JOIN battle_skill d3 ON a.cross_skill_id_3=d3.id
WHERE a.player_id = {$playerId} AND a.general_id IN ({$sqlGeneralIds})
SQL_STAT;
        $re = $this->sqlGet($sql);
        $skill = [];
        array_walk($re, function($v) use (&$skill) {
            if($v) {
                if ($v['s1'] == 1) $skill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_1']];
                if ($v['s2'] == 1) $skill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_2']];
                if ($v['s3'] == 1) $skill[] = ['generalId' => (int)$v['general_id'], 'skillId' => (int)$v['cross_skill_id_3']];
            }
        });
        return $skill;
    }

    /**
     * 更新武将信息或技能信息
     *
     * @param $playerId
     * @param $oldGeneralId
     * @param $newGeneralId
     * @param $skillId              old
     * @param $fromSkillId //后补条件 new
     * @return bool
     */
    public function updateCrossGeneralSkill($playerId, $oldGeneralId, $newGeneralId, $skillId, $fromSkillId=0){
        $oldGeneralId       = (int)$oldGeneralId;
        $newGeneralId       = (int)$newGeneralId;
        $skillId            = (int)$skillId;
        $notActiveSkillFlag = false;//主动技标记

        if($skillId!=0) {
            $BattleSkill = new BattleSkill;
            $skill = $BattleSkill->dicGetOne($skillId);
            if ($skill['if_active']==0) {//非主动技
                if($fromSkillId==0)
                    return;
                else {
                    $fromSkill = $BattleSkill->dicGetOne($fromSkillId);
                    if($fromSkill['if_active']==0) return;
                    else
                        $notActiveSkillFlag = true;
                }
            }
        }

        for($i=1; $i<=2; $i++):
          if($i==1) {//更新跨服战武将信息
            $info = $this->getByPlayerId($playerId);
            if(!$info) continue;
            $crossArmyInfo = $info['cross_army_info'];
          }
          elseif($i==2) {//更新城战武将信息
            $info = (new PlayerInfo)->getByPlayerId($playerId);
            $crossArmyInfo = json_decode($info['general_id_list'], true);
          }

          $replaceSkillFlag      = false;//当前技能是否替换
          $replaceTotalSkillFlag = false;//总技能是否替换

          if(!empty($crossArmyInfo)) {
              $generalIds = array_merge($crossArmyInfo['army'][0], $crossArmyInfo['army'][1]);
              if(in_array($oldGeneralId, $generalIds)) {
                  //case total_skill里的信息
                  if($fromSkillId!=0) {//洗技能
                      //case skill里的信息
                      foreach($crossArmyInfo['skill'] as $k1=>$v1) {
                          if($v1['generalId']==$oldGeneralId && $v1['skillId']==$fromSkillId) {
                              if(!$notActiveSkillFlag) {//从主动技修成非主动技
                                  $crossArmyInfo['skill'][$k1] = ['generalId' => (int)$newGeneralId, 'skillId' => (int)$skillId];
                              } else {
                                  unset($crossArmyInfo['skill'][$k1]);
                              }
                              $replaceSkillFlag = true;
                              break;
                          }
                      }

                      foreach ($crossArmyInfo['total_skill'] as $k2 => $v2) {
                          if ($v2['generalId'] == $oldGeneralId && $v2['skillId'] == $fromSkillId) {
                              if(!$notActiveSkillFlag) {
                                  $crossArmyInfo['total_skill'][$k2] = ['generalId' => (int)$newGeneralId, 'skillId' => (int)$skillId];
                              } else {
                                  unset($crossArmyInfo['total_skill'][$k2]);
                              }
                              if(!$replaceTotalSkillFlag) $replaceTotalSkillFlag = true;
                          }
                      }
                  }
                  //case 武将id有更改，则更改相应武将id
                  if($oldGeneralId!=$newGeneralId) {
                      //case 军团里的武将id
                      $key1 = array_search($oldGeneralId, $crossArmyInfo['army'][0]);//是否在军团1中
                      if ($key1 !== false) {
                          $crossArmyInfo['army'][0][$key1] = $newGeneralId;
                      } else {
                          $key2 = array_search($oldGeneralId, $crossArmyInfo['army'][1]);//是否在军团2中
                          if ($key2 !== false) {
                              $crossArmyInfo['army'][1][$key2] = $newGeneralId;
                          }
                      }
                  }
                  //case 武将技能未修满,补满当前
                  if(count($crossArmyInfo['skill'])<2 && $skillId!=0 && !$replaceSkillFlag){
                      $crossArmyInfo['skill'][] = ['generalId' => (int)$newGeneralId, 'skillId' => (int)$skillId];
                  }
                  //case 武将技能未修满,total技能补上
                  if($skillId!=0 && !$replaceTotalSkillFlag){
                      $crossArmyInfo['total_skill'][] = ['generalId' => (int)$newGeneralId, 'skillId' => (int)$skillId];
                  }
                  //更改数组下标以适应前端
                  if(!empty($crossArmyInfo['skill']))
                      $crossArmyInfo['skill'] = array_values($crossArmyInfo['skill']);
                  if(!empty($crossArmyInfo['total_skill']))
                      $crossArmyInfo['total_skill'] = array_values($crossArmyInfo['total_skill']);

                  if($i==1) {
                    $this->updateAll(['cross_army_info'=>q(json_encode($crossArmyInfo))], ['guild_id'=>$info['guild_id'], 'player_id'=>$playerId]);
                    $this->clearGuildCache($info['guild_id']);
                    (new PlayerCommonLog)->add($playerId, ['type'=>'[跨服战军团]更新武将信息或技能信息', 'memo'=>[$playerId, $oldGeneralId, $newGeneralId, $skillId, $fromSkillId]]);//日志记录
                  }
                  elseif($i==2) {
                    (new PlayerInfo)->alter($playerId, ['general_id_list'=>json_encode($crossArmyInfo)]);
                    (new PlayerCommonLog)->add($playerId, ['type'=>'[城战武将设置]更新武将信息或技能信息', 'memo'=>$crossArmyInfo]);//日志记录
                  }

              }
          }
        endfor;
    }
}