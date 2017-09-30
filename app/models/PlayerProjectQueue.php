<?php
class PlayerProjectQueue extends ModelBase{
    public $blacklist = [];
    
	//增加类型需要在MapController的callbackStayQueueAction和callbackMoveQueueAction添加对应
	const TYPE_RETURN                = 1;//回城
	const TYPE_COLLECT_GOTO          = 101;//去采集
	const TYPE_COLLECT_ING           = 102;//采集中
	const TYPE_COLLECT_RETURN        = 103;//采集返回
	const TYPE_NPCBATTLE_GOTO        = 201;//去打野
	const TYPE_BOSSGATHER_GOTO		 = 202;//集结去攻BOSS（发起方）
	const TYPE_NPCBATTLE_RETURN      = 203;//打野返回
	const TYPE_CITYBATTLE_GOTO       = 301;//去攻城
	const TYPE_CITYBATTLE_RETURN     = 303;//攻城返回
	const TYPE_CITYASSIST_GOTO       = 401;//去援助
	const TYPE_CITYASSIST_ING        = 402;//援助中
	const TYPE_CITYASSIST_RETURN     = 403;//援助返回
	const TYPE_GATHER_WAIT           = 501;//集结中(发起方)
	const TYPE_GATHERBATTLE_GOTO     = 502;//集结去攻城(发起方)
	const TYPE_GATHER_GOTO           = 503;//集结中(援助方)
	const TYPE_GATHER_STAY           = 504;//集结中(援助方)
	const TYPE_GATHERDBATTLE_GOTO    = 505;//集结去攻城(援助方)
	const TYPE_GATHER_RETURN         = 506;//集结返回
	const TYPE_GATHERD_MIDRETURN     = 507;//撤回集结者家
	const TYPE_DETECT_GOTO           = 601;//侦查（去）
	const TYPE_DETECT_RETURN         = 602;//侦查（返）
	const TYPE_FETCHITEM_GOTO        = 603;//拿去物品（去）
	const TYPE_FETCHITEM_RETURN      = 604;//拿去物品（返）
	const TYPE_GUILDBASE_GOTO        = 701;//去堡垒
	const TYPE_GUILDBASE_BUILD       = 702;//建造堡垒
	const TYPE_GUILDBASE_REPAIR      = 703;//修理堡垒
	const TYPE_GUILDBASE_RETURN      = 704;//堡垒返回
	const TYPE_GUILDBASE_DEFEND      = 705;//驻守堡垒
	const TYPE_GUILDWAREHOUSE_GOTO   = 706;//去联盟仓库
	const TYPE_GUILDWAREHOUSE_RETURN = 707;//联盟仓库返回
	const TYPE_GUILDWAREHOUSE_FETCHGOTO   = 729;//去联盟仓库存取
	const TYPE_GUILDWAREHOUSE_FETCHRETURN = 730;//联盟仓库存取返回
	const TYPE_GUILDWAREHOUSE_BUILD  = 708;//建造联盟仓库
	const TYPE_GUILDTOWER_GOTO       = 709;//去联盟箭塔
	const TYPE_GUILDTOWER_RETURN     = 710;//联盟箭塔返回
	const TYPE_GUILDTOWER_BUILD      = 711;//建造联盟箭塔
	const TYPE_GUILDCOLLECT_GOTO     = 712;//去联盟采集场
	const TYPE_GUILDCOLLECT_RETURN   = 713;//联盟采集场返回
	const TYPE_GUILDCOLLECT_BUILD    = 714;//建造联盟采集场
	const TYPE_GUILDCOLLECT_ING      = 715;//联盟采集场采集
	const TYPE_ATTACKBASE_GOTO       = 716;//去攻堡垒
	const TYPE_ATTACKBASEGATHER_GOTO = 717;//集结去攻堡垒（发起方）
	const TYPE_ATTACKBASE_RETURN     = 718;//攻堡垒回
	const TYPE_KINGTOWN_GOTO		 = 720;//去王战城寨
	const TYPE_KINGTOWN_DEFENCE		 = 721;//王战城寨驻防
	const TYPE_KINGTOWN_RETURN		 = 722;//王战城寨回
	const TYPE_KINGGATHERBATTLE_GOTO = 725;//集结去攻城寨(发起方)
	const TYPE_KINGGATHERBATTLE_DEFENCE = 726;//王战城寨驻防(发起方)
	const TYPE_KINGGATHERBATTLE_DEFENCEASIST = 727;//王战城寨驻防（援助方）
	const TYPE_KINGNPCATTACK_GOTO 		= 728;//王战NPC去攻击
	const TYPE_HJNPCATTACK_GOTO 		= 731;//黄巾起义NPC去攻击
	
	public $stayTypes = array(
		self::TYPE_COLLECT_ING 			=>	self::TYPE_COLLECT_RETURN,
		self::TYPE_CITYASSIST_ING 		=>	self::TYPE_CITYASSIST_RETURN,
		self::TYPE_GATHER_STAY			=>	self::TYPE_GATHER_RETURN,
		self::TYPE_GUILDBASE_BUILD		=>	self::TYPE_GUILDBASE_RETURN,
		self::TYPE_GUILDBASE_REPAIR		=>	self::TYPE_GUILDBASE_RETURN,
		self::TYPE_GUILDBASE_DEFEND		=>	self::TYPE_GUILDBASE_RETURN,
		self::TYPE_GUILDWAREHOUSE_BUILD	=>	self::TYPE_GUILDWAREHOUSE_RETURN,
		self::TYPE_GUILDTOWER_BUILD		=>	self::TYPE_GUILDTOWER_RETURN,
		self::TYPE_GUILDCOLLECT_BUILD	=>	self::TYPE_GUILDCOLLECT_RETURN,
		self::TYPE_GUILDCOLLECT_ING		=>	self::TYPE_GUILDCOLLECT_RETURN,
		//self::TYPE_ATTACKBASE_ING		=>	self::TYPE_ATTACKBASE_RETURN,
		self::TYPE_KINGTOWN_DEFENCE		=>	self::TYPE_KINGTOWN_RETURN,
		self::TYPE_KINGGATHERBATTLE_DEFENCE=>	self::TYPE_KINGTOWN_RETURN,
		self::TYPE_KINGGATHERBATTLE_DEFENCEASIST=>self::TYPE_KINGTOWN_RETURN,
	);
	public $moveTypes = array(
		self::TYPE_COLLECT_GOTO 		=>	self::TYPE_COLLECT_RETURN,
		self::TYPE_NPCBATTLE_GOTO 		=>	self::TYPE_NPCBATTLE_RETURN,
		self::TYPE_CITYBATTLE_GOTO		=>	self::TYPE_CITYBATTLE_RETURN,
		self::TYPE_CITYASSIST_GOTO		=>	self::TYPE_CITYASSIST_RETURN,
		self::TYPE_GATHER_GOTO			=>	self::TYPE_GATHER_RETURN,
		self::TYPE_GATHERBATTLE_GOTO	=>	self::TYPE_GATHER_RETURN,
		//self::TYPE_GATHERDBATTLE_GOTO	=>	self::TYPE_GATHERD_MIDRETURN,
		self::TYPE_DETECT_GOTO			=>	self::TYPE_DETECT_RETURN,
		self::TYPE_GUILDBASE_GOTO		=>	self::TYPE_GUILDBASE_RETURN,
		self::TYPE_GUILDWAREHOUSE_GOTO	=>	self::TYPE_GUILDWAREHOUSE_RETURN,
		self::TYPE_GUILDWAREHOUSE_FETCHGOTO	=>	self::TYPE_GUILDWAREHOUSE_FETCHRETURN,
		self::TYPE_GUILDTOWER_GOTO		=>	self::TYPE_GUILDTOWER_RETURN,
		self::TYPE_GUILDCOLLECT_GOTO	=>	self::TYPE_GUILDCOLLECT_RETURN,
		self::TYPE_ATTACKBASE_GOTO		=>	self::TYPE_ATTACKBASE_RETURN,
		self::TYPE_KINGTOWN_GOTO		=>	self::TYPE_KINGTOWN_RETURN,
		self::TYPE_KINGGATHERBATTLE_GOTO=>	self::TYPE_KINGTOWN_RETURN,
		self::TYPE_ATTACKBASEGATHER_GOTO=>	self::TYPE_ATTACKBASE_RETURN,
		self::TYPE_BOSSGATHER_GOTO		=>	self::TYPE_GATHER_RETURN,
		self::TYPE_FETCHITEM_GOTO		=>	self::TYPE_FETCHITEM_RETURN,
	);
	public $gatherTypes = [
		self::TYPE_GATHERBATTLE_GOTO, 
		self::TYPE_KINGGATHERBATTLE_GOTO, 
		self::TYPE_ATTACKBASEGATHER_GOTO,
		self::TYPE_BOSSGATHER_GOTO,
	];
	public $npcTypes = [
		self::TYPE_KINGNPCATTACK_GOTO,
		self::TYPE_HJNPCATTACK_GOTO,
	];
	public static $attackType = [//攻打型，无法使用免战
		self::TYPE_CITYBATTLE_GOTO,
		self::TYPE_GATHER_WAIT,
		self::TYPE_GATHERBATTLE_GOTO,
		self::TYPE_GATHER_GOTO,
		self::TYPE_GATHER_STAY,
		self::TYPE_GATHERDBATTLE_GOTO,
		self::TYPE_DETECT_GOTO,
		self::TYPE_ATTACKBASE_GOTO,
		self::TYPE_ATTACKBASEGATHER_GOTO,
		self::TYPE_KINGTOWN_GOTO,
		self::TYPE_KINGTOWN_DEFENCE,
		self::TYPE_KINGGATHERBATTLE_GOTO,
		self::TYPE_KINGGATHERBATTLE_DEFENCE,
		self::TYPE_KINGGATHERBATTLE_DEFENCEASIST,
	];
	
    public function afterSave(){
		//$this->clearDataCache();
    }
	
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = isset($this->rowversion) ? $this->rowversion+1 : 1;
	}
    /**
     * 踢回某一支援助部队
     * @param  int $playerId 
     * @param  array $ppq      
     * @return array
     */
    public function letHelpArmyBackHome($playerId, $ppqId){
        $re = [];
        if($this->updateAll(['end_time'=>qd(), 'rowversion'=>'rowversion*1+1'], ['id'=>$ppqId, 'target_player_id'=>$playerId, 'type'=>self::TYPE_CITYASSIST_ING, 'status'=>1, 'end_time'=>"'0000-00-00 00:00:00'"])) {
            $re = self::findFirst($ppqId)->toArray();
        }
        return $re;
    }
    /**
     * 返回一条ppqilu
     * @param  int $id 
     * @return array     
     */
    public function getById($id){
        $ppq = self::findFirst($id);
        if($ppq) {
            return $ppq->toArray();
        }
        return null;
    }
    /**
     * 获取建造联盟队列
     * @param  int $mapId 
     * @param  int $type
     * <pre>
     * - "type":1 #堡垒
     * - "type":2 #箭塔
     * - "type":3 #矿场 (1金矿，2：粮矿，3：木矿，4：石矿，5：铁矿)
     * - "type":4 #仓库
     * - 
     * </pre> 
     * @return array        
     */
    public function getConstructGuildBuild($map){
        $mapId        = $map['id'];
        $mapElementId = $map['map_element_id'];
        $typeArr      = [101=>self::TYPE_GUILDBASE_BUILD,201=>self::TYPE_GUILDTOWER_BUILD,801=>self::TYPE_GUILDWAREHOUSE_BUILD];
        if(in_array($mapElementId,[301,401,501,601,701])) {
            $type = self::TYPE_GUILDCOLLECT_RETURN;
        } else {
            $type = $typeArr[$mapElementId];
        }
        $ppq = $this->afterFindQueue(self::find("to_map_id={$mapId} and status=1 and type={$type}")->toArray());
        return $ppq;
    }
    /**
     * 获取联盟建筑内的军团
     * @param  int $guildId 
     * @return array          
     */
    public function getGuildBuildArmy($map){
        $PlayerArmyUnit = new PlayerArmyUnit;
        $Player         = new Player;
        $PlayerArmy     = new PlayerArmy;
        $PlayerGuild    = new PlayerGuild;

        $typeArr = [
            //堡垒
            self::TYPE_GUILDBASE_BUILD,
            self::TYPE_GUILDBASE_REPAIR,
            self::TYPE_GUILDBASE_DEFEND,
            //矿场
            self::TYPE_GUILDCOLLECT_BUILD,
            self::TYPE_GUILDCOLLECT_ING,
            //仓库
            self::TYPE_GUILDWAREHOUSE_BUILD,
            //箭塔
            self::TYPE_GUILDTOWER_BUILD,
			//城寨
			self::TYPE_KINGTOWN_DEFENCE,
			self::TYPE_KINGGATHERBATTLE_DEFENCE,
			self::TYPE_KINGGATHERBATTLE_DEFENCEASIST,
        ];
        $typeSql = '(type=' . join(' or type=', $typeArr) . ')';
        $mapId = $map['id'];

        $ret = [];

        $re = $this->afterFindQueue(self::find("to_map_id={$mapId} and status=1 and {$typeSql} ")->toArray());
        foreach($re as $k=>$v) {
            $targetPlayer                = $Player->getByPlayerId($v['player_id']);
            $playerGuild                 = $PlayerGuild->getByPlayerId($v['player_id']);
            $pau                         = keepFields($PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id']), ['id', 'player_id', 'general_id', 'soldier_id', 'soldier_num']);
            $tmpArr['player_id']         = $targetPlayer['id'];
            $tmpArr['player_nick']       = $targetPlayer['nick'];
            $tmpArr['avatar_id']         = $targetPlayer['avatar_id'];
            $tmpArr['level']             = $targetPlayer['level'];
            $tmpArr['total_power']       = $PlayerArmy->getPower($v['player_id'], $v['army_id']);
            $tmpArr['total_soldier_num'] = array_sum(Set::extract('/soldier_num', $pau));
            $tmpArr['ppq_id']            = $v['id'];
            $tmpArr['army']              = $pau;
            $tmpArr['rank']              = $playerGuild['rank'];
            $ret[]                       = $tmpArr;
        }
        return $ret;
    }
    /**
     * 获取玩家军团详细信息 for method `getAttackArmy`
     * @param  int $playerId 
     * @param  int $armyId   
     * @return array           
     */
    private function getPlayerArmyDetail($playerId, $armyId, $endTime, $ppqId){
        $PlayerArmyUnit = new PlayerArmyUnit;
        $PlayerArmy     = new PlayerArmy;
        $Player         = new Player;
        $PlayerArmy     = new PlayerArmy;
        $Buff           = new Buff;
        $PlayerBuff     = new PlayerBuff;

        $targetPlayer                = $Player->getByPlayerId($playerId);
        $pau                         = keepFields($PlayerArmyUnit->getByArmyId($playerId, $armyId), ['general_id', 'soldier_id', 'soldier_num']);
        $tmpArr['player_nick']       = $targetPlayer['nick'];
        $tmpArr['avatar_id']         = $targetPlayer['avatar_id'];
        $tmpArr['level']             = $targetPlayer['level'];
        $tmpArr['total_power']       = $PlayerArmy->getPower($playerId, $armyId);
        $tmpArr['x']                 = $targetPlayer['x'];
        $tmpArr['y']                 = $targetPlayer['y'];
        $tmpArr['end_time']          = $endTime;
        $tmpArr['total_soldier_num'] = array_sum(Set::extract('/soldier_num', $pau));
        $tmpArr['army'][]            = $pau;
        $tmpArr['ppq_id']            = $ppqId;
        
        $buffIds                     = Buff::$buffIdForShow;
        foreach($buffIds as $buffId) {
            $buff = $Buff->dicGetOne($buffId);
            $buffValue = $PlayerBuff->getPlayerBuff($playerId, $buff['name']);
            // if($buff['buff_type']==1) {
            //     $buffValue = ($buffValue/100) . '%';
            // }
            $tmpArr['buff'][] = [
                'id'    => $buffId,
                'value' => $buffValue,
            ];
        }
        return $tmpArr;
    }
    /**
     * 获取所有来攻击我的援军信息
     * @param  int $playerId 
     * @return array           
     */
    public function getAttackArmy($playerId){
        //攻城信息
        $ppq1 = $this->afterFindQueue(self::find("target_player_id={$playerId} and status=1 and type=".self::TYPE_CITYBATTLE_GOTO)->toArray());
        $re   = [];
        foreach($ppq1 as $v) {
            $re[][] = $this->getPlayerArmyDetail($v['player_id'], $v['army_id'], $v['end_time'], $v['id']);
        }
        //集结信息
        $ppq2 = $this->afterFindQueue(self::find("target_player_id={$playerId} and status=1 and type=".self::TYPE_GATHERBATTLE_GOTO)->toArray());
        foreach($ppq2 as $v) {
            $ree[]          = $this->getPlayerArmyDetail($v['player_id'], $v['army_id'], $v['end_time'], $v['id']);
            $parentQueueId = $v['id'];
            //集结援军信息
            $ppq3          = $this->afterFindQueue(self::find("status=1 and parent_queue_id={$parentQueueId}")->toArray());
            foreach($ppq3 as $vv) {
                $ree[] = $this->getPlayerArmyDetail($vv['player_id'], $vv['army_id'], $vv['end_time'], $vv['id']);
            }
            $re[] = $ree;
        }
        //pr($re);
        return $re;
    }
    /**
     * 获取所有侦查我的人的信息
     * @param  int $playerId 
     * @return array           
     */
    public function getSpyArmy($playerId){
        $ppq = $this->afterFindQueue(self::find("target_player_id={$playerId} and status=1 and type=".self::TYPE_DETECT_GOTO)->toArray());
        $re = [];
        $Player = new Player;
        foreach($ppq as $k=>$v) {
            $p = $Player->getByPlayerId($v['player_id']);
            $re[] = [
                'player_nick' => $p['nick'],
                'x'           => $p['x'],
                'y'           => $p['y'],
                'end_time'    => $v['end_time'],//到达时间
                'avatar_id'   => $p['avatar_id'],
                'level'       => $p['level'],
            ];
        }
        return $re;
    }
    /**
     * 获取所有来帮助我的援军信息
     * @param  int $playerId 
     * @return array           
     */
    public function getHelpArmy($playerId, $currentPlayerId=0){
        $PlayerArmyUnit         = new PlayerArmyUnit;
        $Player                 = new Player;
        $PlayerArmy             = new PlayerArmy;
        $PlayerBuild            = new PlayerBuild;
        if($currentPlayerId) {
            $ppq = $this->afterFindQueue(self::find("player_id <> {$currentPlayerId} and target_player_id={$playerId} and status=1 and end_time='0000-00-00 00:00:00' and type=".self::TYPE_CITYASSIST_ING)->toArray());
        } else {
            $ppq = $this->afterFindQueue(self::find("target_player_id={$playerId} and status=1 and end_time='0000-00-00 00:00:00' and type=".self::TYPE_CITYASSIST_ING)->toArray());
        }
        $re                     = [];
        $re['current_help_num'] = count($ppq);
        $re['max_help_num']     = $PlayerBuild->getMaxHelpArmyNum($playerId);
        foreach($ppq as $v) {
            $pau                         = $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id']);
            $vPlayer                     = $Player->getByPlayerId($v['player_id']);
            $tmpArr['player_nick']       = $vPlayer['nick'];
            $tmpArr['player_avatar_id']  = $vPlayer['avatar_id'];
            $tmpArr['total_soldier_num'] = array_sum(Set::extract('/soldier_num', $pau));
            $tmpArr['total_power']       = $PlayerArmy->getPower($v['player_id'], $v['army_id']);
            $tmpArr['army']              = $pau;
            $tmpArr['ppq_id']            = $v['id'];
            $re[]                        = $tmpArr;
        }
        return $re;
    }
    
	public function getGatherArmy($playerId){
        $PlayerArmyUnit         = new PlayerArmyUnit;
        $Player                 = new Player;
        $PlayerArmy             = new PlayerArmy;
        $PlayerBuild            = new PlayerBuild;
        
        $ppq                    = $this->afterFindQueue(self::find("target_player_id={$playerId} and status=1 and type=".self::TYPE_GATHER_STAY)->toArray());
        $re                     = [];
        
        $re['current_help_num'] = count($ppq);
        $re['max_help_num']     = $PlayerBuild->getMaxHelpArmyNum($playerId);
        foreach($ppq as $v) {
            $pau                         = $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id']);
            $tmpArr['player_nick']       = $Player->getByPlayerId($v['player_id'])['nick'];
            $tmpArr['total_soldier_num'] = array_sum(Set::extract('/soldier_num', $pau));
            $tmpArr['total_power']       = $PlayerArmy->getPower($v['player_id'], $v['army_id']);
            $tmpArr['army']              = $pau;
            $tmpArr['ppq_id']            = $v['id'];
            // $tmpArr['target_queue_num']  = $PlayerBuild->getMaxHelpArmyNum($v['player_id']);
            $re[]                        = $tmpArr;
        }
        return $re;
    }
    /**
     * 通过id获取玩家计划列表信息
     *
     * @return $ret array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $ret = self::find("player_id={$playerId} and status=1")->toArray();
        if($forDataFlag) {
            $ret = filterFields($ret, $forDataFlag, $this->blacklist);
        }
		$ret = $this->afterFindQueue($ret);
		return $ret;
    }
	
	public function afterFindQueue($data){
		$data = $this->adapter($data);
		foreach($data as &$_r){
			if(@$_r['target_info'])
				$_r['target_info'] = json_decode($_r['target_info'], true);
			if(@$_r['accelerate_info'])
				$_r['accelerate_info'] = json_decode($_r['accelerate_info'], true);
			if(@$_r['carry_soldier'])
				$_r['carry_soldier'] = json_decode($_r['carry_soldier'], true);
			if(@$_r['carry_item'])
				$_r['carry_item'] = json_decode($_r['carry_item'], true);
		}
		unset($_r);
		return $data;
	}

    /**
     * @param $ppqId
     *
     * @return array
     *  获取堡垒中已经驻守的一支部队
     */
	public function getDefendArmyFromGuildBase($ppqId){
	    $type = self::TYPE_GUILDBASE_DEFEND;
        $re = $this->afterFindQueue(self::find("id={$ppqId} and status=1 and type={$type}")->toArray());
        if($re) {
            return $re[0];
        }
        return [];
    }
    /**
     * 添加新的队列数据
     * 
     * @param playerId int 玩家
     * @param targetPlayerId int 目标玩家
     * @param type int 队列类型
     * @param needTime int 需要时间
     * @param targetInfo array 目标信息
     * @param extraData array 额外信息
     */
    public function addQueue($playerId, $guildId, $targetPlayerId, $type, $needTime, $armyId=0, $targetInfo=[], $extraData=[]){
		$accelerate_info = [];
		if(!is_array($needTime)){
			$accelerate_info['second'] = $needTime;
		}else{
			$accelerate_info['second'] = max(0, strtotime($needTime['end_time']) - strtotime($needTime['create_time']));
		}
        $self = new self;
        $self->player_id        = $playerId;
        $self->target_player_id = $targetPlayerId;
        $self->type             = $type;
        $self->target_info      = json_encode($targetInfo);
        $self->status           = 1;
		if(is_array($needTime)){
			$self->create_time      = $needTime['create_time'];
			$self->end_time         = $needTime['end_time'];
		}else{
			$self->create_time      = date("Y-m-d H:i:s");
			$self->end_time         = date("Y-m-d H:i:s", time()+$needTime);
		}
		$self->update_time      = date("Y-m-d H:i:s");
		$self->accelerate_info = json_encode($accelerate_info);
		$self->army_id = $armyId;
		if(false !== $guildId){
			$self->guild_id = $guildId;
		}else{
			$Player = new Player;
			$player = $Player->getByPlayerId($playerId);
			$self->guild_id = $player['guild_id'];
		}
        if(array_key_exists('from_map_id', $extraData)) {
            $self->from_map_id = $extraData['from_map_id'];
            $self->from_x      = $extraData['from_x'];
            $self->from_y      = $extraData['from_y'];
        }
        if(array_key_exists('to_map_id', $extraData)) {
            $self->to_map_id = $extraData['to_map_id'];
            $self->to_x = $extraData['to_x'];
            $self->to_y = $extraData['to_y'];
        }
		if(array_key_exists('parent_queue_id', $extraData)) {
            $self->parent_queue_id = $extraData['parent_queue_id'];
        }
		if(array_key_exists('carry_gold', $extraData)) {
            $self->carry_gold = $extraData['carry_gold'];
        }
		if(array_key_exists('carry_food', $extraData)) {
            $self->carry_food = $extraData['carry_food'];
        }
		if(array_key_exists('carry_wood', $extraData)) {
            $self->carry_wood = $extraData['carry_wood'];
        }
		if(array_key_exists('carry_stone', $extraData)) {
            $self->carry_stone = $extraData['carry_stone'];
        }
		if(array_key_exists('carry_iron', $extraData)) {
            $self->carry_iron = $extraData['carry_iron'];
        }
		if(array_key_exists('carry_item', $extraData)) {
			if($extraData['carry_item']){
				$self->carry_item = json_encode((array)$extraData['carry_item']);
			}else{
				$self->carry_item = json_encode([]);
			}
        }else{
			$self->carry_item = json_encode([]);
		}
		if(array_key_exists('carry_soldier', $extraData)) {
			if($extraData['carry_soldier']){
				$self->carry_soldier = json_encode((array)$extraData['carry_soldier']);
			}else{
				$self->carry_soldier = json_encode([]);
			}
        }else{
			$self->carry_soldier = json_encode([]);
		}

		$this->rowversion = 1;
        $self->save();
		$this->id = $self->id;
        return $self->id;
    }


    /**
     * 取消进行中的队列
     * 
     * @param playerId int 玩家
     * @param id int 记录编号
     *
     * @return boolean 是否成功 
     */
    public function cancelQueue($playerId, $id, $battle=0){
        $re = self::findFirst($id);
		if(!$re)
			return false;
		
        if($re->player_id==$playerId/* && strtotime($re->end_time)>time()*/){
            $ret = $this->updateAll(['status'=>3, 'update_time'=>"'".date("Y-m-d H:i:s")."'", 'battle'=>$battle, 'rowversion'=>'rowversion*1+1'], ['id'=>$id, 'status'=>1]);
            $this->clearDataCache($playerId);//清缓存
            return $ret;
        }else{
            return false;
        }
    }


    /**
     * 标记队列完成
     * 
     * @param  playerId int 玩家
     * @param  id int 记录编号 
     * 
     * @return boolean 是否成功 
     */
    public function finishQueue($playerId, $id, $battle=0){
        $re = self::findFirst($id);
		if(!$re)
			return false;
        if($re->player_id==$playerId /* && strtotime($re->end_time)<=time()*/){
            $ret = $this->updateAll(['status'=>2, 'update_time'=>"'".date("Y-m-d H:i:s")."'", 'battle'=>$battle, 'rowversion'=>'rowversion*1+1'], ['id'=>$id, 'status'=>1]);
            $this->clearDataCache($playerId);//清缓存
            return $ret;
        }else{
            return false;
        }
    }
	
	public function updateQueue($needTime, $targetInfo=[], $extraData=[]){
		$updateData = array();
		if($targetInfo !== false){
			$updateData['target_info']      = json_encode($targetInfo);
		}
		if($needTime !== false){
			$updateData['end_time']         =  date("Y-m-d H:i:s", (is_numeric($this->create_time) ? $this->create_time : strtotime($this->create_time))+$needTime);
		}
		$updateData['update_time']      = date("Y-m-d H:i:s");
		$updateData['rowversion']      = $this->rowversion+1;
		if($extraData){
			if(array_key_exists('carry_gold', $extraData)) {
				$updateData['carry_gold'] = $extraData['carry_gold'];
			}
			if(array_key_exists('carry_food', $extraData)) {
				$updateData['carry_food'] = $extraData['carry_food'];
			}
			if(array_key_exists('carry_wood', $extraData)) {
				$updateData['carry_wood'] = $extraData['carry_wood'];
			}
			if(array_key_exists('carry_stone', $extraData)) {
				$updateData['carry_stone'] = $extraData['carry_stone'];
			}
			if(array_key_exists('carry_iron', $extraData)) {
				$updateData['carry_iron'] = $extraData['carry_iron'];
			}
			if(array_key_exists('carry_item', $extraData)) {
				if($extraData['carry_item']){
					$updateData['carry_item'] = json_encode((array)$extraData['carry_item']);
				}else{
					$updateData['carry_item'] = json_encode([]);
				}
			}
			if(array_key_exists('carry_soldier', $extraData)) {
				if($extraData['carry_soldier']){
					$updateData['carry_soldier'] = json_encode((array)$extraData['carry_soldier']);
				}else{
					$updateData['carry_soldier'] = json_encode([]);
				}
			}
		}

		$ret = $this->saveAll($updateData, "id={$this->id} and rowversion='{$this->rowversion}'");
		//$this->clearDataCache();
		return $ret;
	}
	
	public function mergeExtraInfo($ppq, $extra){
		$ret = ['carry_gold'=>@$ppq['carry_gold'], 'carry_food'=>@$ppq['carry_food'], 'carry_wood'=>@$ppq['carry_wood'], 'carry_stone'=>@$ppq['carry_stone'], 'carry_iron'=>@$ppq['carry_iron'], 'carry_soldier'=>@$ppq['carry_soldier'], 'carry_item'=>@$ppq['carry_item']];
		if(!is_array($extra))
			return $ret;
		foreach($extra as $_k=>$_d){
			if(is_array($_d)){
				if($_k == 'carry_soldier'){
					foreach($_d as $__k => $__d){
						@$ret[$_k][$__k] += $__d;
					}
				}elseif($_k == 'carry_item'){
					foreach($_d as $__k => $__d){
						if($ret[$_k]){
							$_findFlag = false;
							foreach($ret[$_k] as $_retk => &$_retv){
								if($_retv[0] == $__d[0] && $_retv[1] == $__d[1]){
									$_retv[2] += $__d[2];
									$_findFlag = true;
									break;
								}
							}
							unset($_retv);
							if(!$_findFlag){
								$ret[$_k][] = $__d;
							}
						}else{
							$ret[$_k][] = $__d;
						}
					}
				}
			}else{
				@$ret[$_k] += $_d;
			}
		}
		return $ret;
	}
	
	public function updateEndtime($endtime, $backNow = false){
		$condition = array(
			'end_time'=>$endtime,
			'update_time'=>date("Y-m-d H:i:s"),
			'rowversion'      => $this->rowversion+1,
		);
		if($backNow){
			$targetInfo = json_decode($this->target_info, true);
			$targetInfo['backNow'] = 1;
			$condition['target_info'] = json_encode($targetInfo);
		}
		$ret = $this->saveAll($condition, "id={$this->id} and rowversion='{$this->rowversion}'");
		//$this->clearDataCache();
		return $ret;
	}
	
	public function updateAcce($endtime, $accelerateInfo){
		$ret = $this->saveAll(array(
			'end_time'=>$endtime,
			'accelerate_info'=>json_encode($accelerateInfo),
			'update_time'=>date("Y-m-d H:i:s"),
			'rowversion'      => $this->rowversion+1,
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		//$this->clearDataCache();
		return $ret;
	}

    /**
     * 获取行军时间
     * 
     * @param <type> $playerId 
     * @param <type> $fromX 
     * @param <type> $fromY 
     * @param <type> $toX 
     * @param <type> $toY 
     * @param <type> $type 行军种类：1.采集，2.打怪，3.出征，4.侦查/获取物品，5.搬运资源,6.集结
     * @param <type> $soldierSpeed  
     * 
     * @return <type>
     */
	public static function calculateMoveTime($playerId, $fromX, $fromY, $toX, $toY, $type, $armyId=0){
        $buffType[] = 'march_speed';
		switch($type){
			case 1://采集
				$k = 0.015;//速度基数
				$buffType[] = 'moving_speed';
			break;
			case 2://打怪
				$k = 0.015;//速度基数
				$buffType[] = 'move_to_npc_speed';
			break;
			case 6:
				$buffType[] = 'aggregation_legion_march_speed';
			//no break;
			case 3://出征
				$k = 0.0125;//速度基数
			break;
			case 4://侦查
				$k = 0.04;//速度基数
            break;
			case 5://搬运资源
				$k = 0.02;//搬运资源
			break;
			default:
				return false;
		}
		
		//获取行军加成buff
		if($buffType){
			$speedBuff = 0;
			$PlayerBuff = new PlayerBuff;
			foreach($buffType as $_b){
				$speedBuff += $PlayerBuff->getPlayerBuff($playerId, $_b);
			}
		}else{
			$speedBuff = 0;
		}
		
		//敌人侦查速度降低
		/*if($type == 4){
			//获取目标
			$map = (new Map)->getByXy($toX, $toY);
			if($map && $map['map_element_origin_id'] == 15){//判断是否是玩家城堡
				//判断目标玩家buff
				$speedDebuff = (new PlayerBuff)->getPlayerBuff($map['player_id'], 'inspection_speed_reduce');
			}
		}
		*/
		
		//计算距离
		$distance = sqrt(pow($fromX - $toX, 2) + pow($fromY - $toY, 2));
		
		if(!in_array($type, [4, 5])){
			if(!$armyId)
				return false;
			//获取兵种最慢移动速度
			$Soldier = new Soldier;
			$PlayerArmyUnit = new PlayerArmyUnit;
			$pau = $PlayerArmyUnit->getByArmyId($playerId, $armyId);
			$slowSpeed = false;//最慢行军速度
			foreach($pau as $_pau){
				if($_pau['soldier_num'] > 0){
					$_soldier = $Soldier->dicGetOne($_pau['soldier_id']);
					if(!$_soldier){
						return false;
					}
					$_speed = $_soldier['speed'];
					if(!$slowSpeed || $_speed < $slowSpeed){
						$slowSpeed = $_speed;
					}
				}
			}
			if(!$slowSpeed){
				//$slowSpeed = 5;
				return 1;
			}
		}else{
			$slowSpeed = 12;
		}
		
		//计算行军时间
		//return floor($distance / ($slowSpeed * (1+$speedBuff-$speedDebuff) * $k));
		return floor($distance / ($slowSpeed * $k) / (1 + $speedBuff));
	}
	
    /**
     * 计算建造时间
     * 
     * @param <type> $armies 
     * @param <type> $buildValue 
     * 
     * @return <type>
     */
	public function calculateBuildTime($armies, $buildValue){
		$buildSpeed = 0;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$Soldier = new Soldier;
		$soldierPower = [];
		foreach($armies as $_playerId=>$_armyId){
			//获取军团
			$pau = $PlayerArmyUnit->getByArmyId($_playerId, $_armyId);
			foreach($pau as $_pau){
				if($_pau['soldier_id'] && $_pau['soldier_num']){
					if(!@$soldierPower[$_pau['soldier_id']]){
						//获取士兵战力
						$_soldier = $Soldier->dicGetOne($_pau['soldier_id']);
						if(!$_soldier)
							return false;
						$soldierPower[$_pau['soldier_id']] = $_soldier['power'];
					}
					$buildSpeed += $soldierPower[$_pau['soldier_id']] * $_pau['soldier_num'];
				}
			}
		}
		
		//获取联盟buff todo
		
		
		if(!$buildSpeed)
			return 0;
		return ['speed'=>$buildSpeed, 'time'=>ceil($buildValue / ($buildSpeed / DIC_DATA_DIVISOR / 3600))];
	}
	
    /**
     * 计算攻击造成的堡垒损伤
     * 
     * 
     * @return <type>
     */
	public function calculcateBaseAttackValue($armies, $map){
		$MapElement = new MapElement;
		$mapElement = $MapElement->dicGetOne($map['map_element_id']);
		$maxValue = floor($mapElement['max_num'] / 100);
		
		$buildSpeed = 0;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$Soldier = new Soldier;
		$soldierPower = [];
		foreach($armies as $_playerId=>$_armyId){
			//获取军团
			$pau = $PlayerArmyUnit->getByArmyId($_playerId, $_armyId);
			$_buildSpeed = 0;
			foreach($pau as $_pau){
				if($_pau['soldier_id'] && $_pau['soldier_num']){
					if(!@$soldierPower[$_pau['soldier_id']]){
						//获取士兵战力
						$_soldier = $Soldier->dicGetOne($_pau['soldier_id']);
						if(!$_soldier)
							return false;
						$soldierPower[$_pau['soldier_id']] = $_soldier['power'];
					}
					$_buildSpeed += $soldierPower[$_pau['soldier_id']] * $_pau['soldier_num'];
				}
			}
			$_buildSpeed = min($_buildSpeed / DIC_DATA_DIVISOR, $maxValue);
			$buildSpeed += $_buildSpeed;
		}
		
		//获取联盟buff todo
		
		if(!$buildSpeed)
			return 0;
		
		return floor($buildSpeed);
	}
    /**
     * 更新堡垒建造或修复的结束时间
     *
     * 使用方法如下
     * ```php
     * (new PlayerProjectQueue)->updateGuildBaseEndTime($ppq);
     * ```
     * @param  array $ppq 
     */
    public function updateGuildBaseEndTime($ppq, $backFlag=false){
        $typeArr = [
            //堡垒
            self::TYPE_GUILDBASE_BUILD,
            self::TYPE_GUILDBASE_REPAIR,
            //矿场
            self::TYPE_GUILDCOLLECT_BUILD,
            //仓库
            self::TYPE_GUILDWAREHOUSE_BUILD,
            //箭塔
            self::TYPE_GUILDTOWER_BUILD,
        ];
        $Map        = new Map;
        $MapElement = new MapElement;
        
        $type       = $ppq['type'];
        if(in_array($type, $typeArr)) {
            $toMapId    = $ppq['to_map_id'];
            $map        = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
            $mapElement = $MapElement->dicGetOne($map['map_element_id']);
            $ppqId = $ppq['id'];
            if($backFlag) {//返回，排除当前
                $re = self::find("to_map_id={$toMapId} and status=1 and type={$type} and id <> {$ppqId} and (end_time>now() or end_time='0000-00-00 00:00:00')")->toArray();
            } else {
                $re = self::find("to_map_id={$toMapId} and status=1 and type={$type} and (end_time>now() or end_time='0000-00-00 00:00:00')")->toArray();
            }
            if($re) {
                $armies            = Set::combine($re, "{n}.player_id", "{n}.army_id");
                $subConstructValue = floor($map['max_durability']-$map['durability']);
                $needTime          = $this->calculateBuildTime($armies, $subConstructValue);
                $needTime          = $needTime['time'];
                // $this->updateAll(['end_time'=>q(date('Y-m-d H:i:s', time()+$needTime)), 'rowversion'=>'rowversion*1+1', 'update_time'=>qd()], ['to_map_id'=>$toMapId, 'status'=>1, 'type'=>$type]);
                $endTime = q(date('Y-m-d H:i:s', time()+$needTime));
                $sql = "update player_project_queue set end_time={$endTime}, rowversion=rowversion*1+1, update_time=now() where to_map_id={$toMapId} and status=1 and type={$type} and (end_time>now() or end_time='0000-00-00 00:00:00')";
                $this->sqlExec($sql);
                $Map->updateAll(['build_time'=>qd()], ['id'=>$toMapId]);
                $Map->clearMapCache($ppq['to_x'], $ppq['to_y']);
                return true;
            }
        }
        return false;
    }
    /**
     * 更新堡垒城防值
     *
     * 使用方法如下
     * ```php
     * (new PlayerProjectQueue)->calculateGuildBaseConstructValue($ppq);
     * ```
     * @param  array $ppq 
     * @return bool   
     */
    public function calculateGuildBaseConstructValue($ppq, $backFlag=false, $inFlag=false){
        $Map        = new Map;
        $MapElement = new MapElement;

        $type       = $ppq['type'];
        $toMapId    = $ppq['to_map_id'];
        $map        = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
        $subConstructValue = $map['max_durability']-$map['durability'];
        $mapElement = $MapElement->dicGetOne($map['map_element_id']);

        $createTime = $map['build_time'];
        $endTime    = $ppq['end_time'];
        $now        = time();

        $ppqId      = $ppq['id'];
        $currentConstructValue = 0;
        if($backFlag) {//返回
            $re = self::find("id={$ppqId} or (to_map_id={$toMapId} and status=1 and type={$type})")->toArray();
        } elseif($inFlag) {//刚刚进来的部队
            $re = self::find("to_map_id={$toMapId} and id<>$ppqId and status=1 and type={$type}")->toArray();
        } else {
            $re = self::find("to_map_id={$toMapId} and status=1 and type={$type}")->toArray();
        }
        if($re) {
            $armies    = Set::combine($re, "{n}.player_id", "{n}.army_id");
            $needTime  = $this->calculateBuildTime($armies, $subConstructValue);
            $needSpeed = $needTime['speed'];
            $needTime  = $needTime['time'];
            $currentConstructValue = floor(($now-$createTime)*($needSpeed/DIC_DATA_DIVISOR/3600));//现在要加的城防值
        }
        if($currentConstructValue>0) {
            //更新值
            $Map->updateAll(['durability'=>"LEAST(durability+{$currentConstructValue}, max_durability)", 'update_time'=>qd(), 'rowversion'=>'rowversion*1+1', 'build_time'=>qd()], ['id'=>$toMapId/*, 'durability'=>$map['durability']*/]);
            $Map->clearMapCache($ppq['to_x'], $ppq['to_y']);
            $re = Map::findFirst($toMapId);
            if($re->durability==$re->max_durability) {

                $_cuPlayer = Player::findFirst($ppq['player_id']);
                //联盟聊天推送
                $pushData = [
                    'type'           => 16,
                    'map_element_id' => $re->map_element_id,
                    'x'              => $re->x,
                    'y'              => $re->y,
                    'nick'           => $_cuPlayer->nick,

                ];
                $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$ppq['player_id'], 'content'=>'', 'pushData'=>$pushData]];
                socketSend($data);


                $re->status = 1;
                $re->save();
                $Map->clearMapCache($re->x, $re->y);
            }
            return true;
        } else {
            return false;
        }
        return false;
    }

    /**
     * 召回联盟相关队列
     * 
     * @param <type> $guildId 
     * @param <type> $type 0.所有，1.堡垒，2.矿场，3.仓库，4.箭塔，5.援助
	 * @param <type> $mapId 指定点
	 * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function callbackGuildQueue($guildId, $type=0, $mapId=0, $playerId=0){
		$now = time();
		$condition = ['guild_id='.$guildId.' and status=1'];
		if($playerId){
			$condition[0] .= ' and player_id='.$playerId;
		}
		if($mapId){
			$condition[0] .= ' and to_map_id='.$mapId;
		}
		$ppqs = self::find($condition);
		$Player = new Player;
		$players = [];
		foreach($ppqs as $_ppq){
			$playerId = $_ppq->player_id;
			if(!isset($players[$playerId])){
				$player = $Player->getByPlayerId($playerId);
				if(!$player)
					return false;
				$players[$playerId] = $player;
			}else{
				$player = $players[$playerId];
			}
			if(!$type){
				//排除非联盟队列
				if(!in_array($_ppq->type, [
					self::TYPE_CITYASSIST_ING,
					self::TYPE_GATHER_WAIT,
					self::TYPE_GATHERBATTLE_GOTO,
					self::TYPE_GATHER_GOTO,
					self::TYPE_GATHER_STAY,
					self::TYPE_GATHERDBATTLE_GOTO,
					self::TYPE_GUILDBASE_GOTO,
					self::TYPE_GUILDBASE_BUILD,
					self::TYPE_GUILDBASE_REPAIR,
					self::TYPE_GUILDBASE_DEFEND,
					self::TYPE_GUILDWAREHOUSE_GOTO,
					self::TYPE_GUILDWAREHOUSE_FETCHGOTO,
					self::TYPE_GUILDWAREHOUSE_BUILD,
					self::TYPE_GUILDTOWER_GOTO,
					self::TYPE_GUILDTOWER_BUILD,
					self::TYPE_GUILDCOLLECT_GOTO,
					self::TYPE_GUILDCOLLECT_BUILD,
					self::TYPE_GUILDCOLLECT_ING,
				])) continue;
			}elseif($type == 1){//堡垒
				if(!in_array($_ppq->type, [
					self::TYPE_GUILDBASE_GOTO,
					self::TYPE_GUILDBASE_BUILD,
					self::TYPE_GUILDBASE_REPAIR,
					self::TYPE_GUILDBASE_DEFEND,
				])) continue;
			}elseif($type == 2){//矿场
				if(!in_array($_ppq->type, [
					self::TYPE_GUILDCOLLECT_GOTO,
					self::TYPE_GUILDCOLLECT_BUILD,
					self::TYPE_GUILDCOLLECT_ING,
				])) continue;
			}elseif($type == 3){//仓库
				if(!in_array($_ppq->type, [
					self::TYPE_GUILDWAREHOUSE_GOTO,
					self::TYPE_GUILDWAREHOUSE_FETCHGOTO,
					self::TYPE_GUILDWAREHOUSE_BUILD,
				])) continue;
			}elseif($type == 4){//箭塔
				if(!in_array($_ppq->type, [
					self::TYPE_GUILDTOWER_GOTO,
					self::TYPE_GUILDTOWER_BUILD,
				])) continue;
			}elseif($type == 5){//援助
				if(!in_array($_ppq->type, [
					self::TYPE_CITYASSIST_ING,
				])) continue;
			}
			//排除集结援助方去攻城的
			if($_ppq->type == PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO){
				continue;
			}elseif(in_array($_ppq->type, array_keys($this->moveTypes))){
			//外出移动队列，撤销队列，建立返回队列
				$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y);
				/*if(!$this->cancelQueue($playerId, $_ppq->id)){
					return false;
				}
				$_ppq->accelerate_info = json_decode($_ppq->accelerate_info, true);
				$_time = strtotime($_ppq->create_time);
				$usedTime = 0;
				$immediat = false;
				$v = 1;
				if(@$_ppq->accelerate_info['log']){
					foreach($_ppq->accelerate_info['log'] as $_i => $_log){
						$s = ($_log['time'] - $_time);
						//$usedTime += pow(2, $_i) * $s;
						$usedTime += $v * $s;
						if($_log['itemId'] == -1){
							$immediat = true;
							$usedTime = $ppq->accelerate_info['second'];
							break;
						}else{
							$v *= $_log['v'];
						}
						$_time = $_log['time'];
					}
					//$usedTime += ($now - $_time) * pow(2, count($_ppq->accelerate_info['log']));
					if(!$immediat){
						$usedTime += ($now - $_time) * $v;
					}
				}
				$restTime = $_ppq->accelerate_info['second'] - $usedTime;
				//计算剩余时间
				$createTime = date('Y-m-d H:i:s', $now - $restTime);
				$endTime = date('Y-m-d H:i:s', $now + $usedTime);
				
				$extraData = [
					'from_map_id' => $_ppq->to_map_id,
					'from_x' => $_ppq->to_x,
					'from_y' => $_ppq->to_y,
					'to_map_id' => $player['map_id'],
					'to_x' => $player['x'],
					'to_y' => $player['y'],
					'carry_gold' => $_ppq->carry_gold,
					'carry_food' => $_ppq->carry_food,
					'carry_wood' => $_ppq->carry_wood,
					'carry_stone' => $_ppq->carry_stone,
					'carry_iron' => $_ppq->carry_iron,
				];
				$newQueueId = $this->addQueue($playerId, $_ppq->guild_id, 0, $this->moveTypes[$_ppq->type], ['create_time'=>$createTime, 'end_time'=>$endTime], $_ppq->army_id, [], $extraData);
				
				//如果是集结主队列，解散集结队列
				if(in_array($_ppq->type, $this->gatherTypes)){
					$otherPpqs = self::find(['parent_queue_id='.$_ppq->id.' and type='.self::TYPE_GATHERDBATTLE_GOTO.' and status=1']);
					$_now = time();
					foreach($otherPpqs as $_p){
						//撤销原有队列
						if(!$this->cancelQueue($_p->player_id, $_p->id)){
							return false;
						}
						$_extraData = [
							'from_map_id' => $_p->to_map_id,
							'from_x' => $_p->to_x,
							'from_y' => $_p->to_y,
							'to_map_id' => $player['map_id'],
							'to_x' => $player['x'],
							'to_y' => $player['y'],
							'carry_gold' => $_p->carry_gold,
							'carry_food' => $_p->carry_food,
							'carry_wood' => $_p->carry_wood,
							'carry_stone' => $_p->carry_stone,
							'carry_iron' => $_p->carry_iron,
							'parent_queue_id' => $newQueueId,
						];
						//新增回集结者家队列
						$this->addQueue($_p->player_id, $_p->guild_id, 0, self::TYPE_GATHERD_MIDRETURN, ['create_time'=>$createTime, 'end_time'=>$endTime], $_p->army_id, [], $_extraData);
					}
				}*/
			}elseif(in_array($_ppq->type, array_keys($this->stayTypes))){//如果是固定队列，修改结束时间，添加立即回城标志
				$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y);
				//修改结束时间
				/*if(!$_ppq->updateEndtime(date('Y-m-d H:i:s'))){
					return false;
				}
				
				//王战集结驻防解散
				if($_ppq->type == self::TYPE_KINGGATHERBATTLE_DEFENCE){//大召回
					//查找子队列
					$otherPpqs = self::find(['parent_queue_id='.$_ppq->id.' and type='.self::TYPE_KINGGATHERBATTLE_DEFENCEASIST.' and status=1']);
					foreach($otherPpqs as $_p){
						//撤销原有队列
						if(!$_p->updateEndtime(date('Y-m-d H:i:s'))){
							return false;
						}
					}
				}*/
			}elseif($_ppq->type == self::TYPE_GATHER_WAIT){//如果是集结等待中，参考Map::cancelGather
				$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y);
				//获取军团
				/*$PlayerArmy = new PlayerArmy;
				$playerArmy = $PlayerArmy->getByArmyId($playerId, $_ppq->army_id);
				if(!$playerArmy)
					return false;
				
				if(!$PlayerArmy->assign($playerArmy)->updateStatus(0)){
					return false;
				}
				
				//修改武将状态
				$PlayerArmyUnit = new PlayerArmyUnit;
				$pau = $PlayerArmyUnit->getByArmyId($playerId, $_ppq->army_id);
				$generalIds = [];
				foreach($pau as $_pau){
					$generalIds[] = $_pau['general_id'];
				}
				$PlayerGeneral = new PlayerGeneral;
				if(!$PlayerGeneral->updateReturnByGeneralIds($playerId, $generalIds)){
					return false;
				}
				
				//删除队列
				if(!$this->cancelQueue($playerId, $_ppq->id)){
					return false;
				}
				
				//更新前往集结盟友队列的结束时间
				$queues = $this->find(["parent_queue_id=".$_ppq->id." and status=1 and type=".self::TYPE_GATHER_STAY]);
				foreach($queues as $_q){
					if(!$_q->updateEndtime(date('Y-m-d H:i:s'))){
						return false;
					}
				}*/
			}else{
				return false;
			}
		}
				
		return true;
	}
	
    /**
     * 是不是在集结中
     * @return boolean [description]
     */
	function isGather($playerId){
        $re = $this->getByPlayerId($playerId);
        $gatherArr = array_merge($this->gatherTypes, [self::TYPE_GATHERDBATTLE_GOTO]);
        $result = false;
        foreach ($re as $key => $value) {
            if(in_array($value['type'], $gatherArr)){
                $result = true;
            }
        }
        return $result;
    }
	
	function upGuildId($playerId, $guildId=0){
		$ret = $this->updateAll(['guild_id'=>$guildId, 'update_time'=>"'".date("Y-m-d H:i:s")."'", 'rowversion'=>'rowversion*1+1'], ['player_id'=>$playerId, 'status'=>1]);
		$this->clearDataCache($playerId);//清缓存
		return true;
	}

    /**
     * 战斗通知
     * 
     * @param <type> $type 1.玩家号；2.联盟号
     * @param <type> $id 根据type传入内容
     * 
     * @return <type>
     */
	public function noticeFight($type, $id){
		if($type == 1){
			socketSend(['Type'=>'fight', 'Data'=>['playerId'=>[$id]]]);
		}elseif($type == 2){
			//获取公会所有成员
			$PlayerGuild = new PlayerGuild;
			$members = $PlayerGuild->getAllGuildMember($id);
			$ids = array_keys($members);
			socketSend(['Type'=>'fight', 'Data'=>['playerId'=>$ids]]);
		}
	}

    /**
     * 召回队伍
     * 
     * @param <type> $id 
     * @param <type> $option 
     * 
     * @return <type>
     */
	public function callbackQueue($id, $toX, $toY, $option=[]){
		global $inDispWorker;
		$perTry = 1;
		$tryLimit = 5;
		//$cache = Cache::db('dispatcher');
		$i = 0;
		$DispatcherTask = new DispatcherTask;
		if(!@$inDispWorker){
			while(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher'), $toX, $toY)){
				sleep($perTry);
				$i++;
				if($i >= $tryLimit){
					return false;
				}
			}
		}
		
		$ppq = $this->findFirst($id);
		if(!$ppq || $ppq->status != 1){
			if(!@$inDispWorker)
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
			return false;
		}
		
		$clearXyCache = true;
		$relateQueue = [];
		if(in_array($ppq->type, array_keys($this->stayTypes))){
			if(!@$inDispWorker)
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
			$clearXyCache = false;
			//获取返回type
			$returnType = $this->stayTypes[$ppq->type];
			
			//修改结束时间
			if(!$ppq->updateEndtime(date('Y-m-d H:i:s'), @$option['rightnow'])){
				return false;
			}
			
			//王战集结驻防解散
			if($ppq->type == self::TYPE_KINGGATHERBATTLE_DEFENCE){//大召回
				//查找子队列
				$otherPpqs = self::find(['parent_queue_id='.$ppq->id.' and type='.self::TYPE_KINGGATHERBATTLE_DEFENCEASIST.' and status=1']);
				foreach($otherPpqs as $_ppq){
					//撤销原有队列
					if(!$_ppq->updateEndtime(date('Y-m-d H:i:s'), @$option['rightnow'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			
			//检查是否处理完成
			if(@$option['waitStayQ']){
				while($this->findFirst(['id='.$id.' and status=1'])){
					sleep($perTry);
					$i++;
					if($i >= $tryLimit){
						return false;
					}
				}
			}
		}elseif(in_array($ppq->type, array_keys($this->moveTypes))){
			
			//获取返回type
			$returnType = $this->moveTypes[$ppq->type];
			
			$now = time();
			
			if(@$option['rightnow']){
				$createTime = date('Y-m-d H:i:s', $now);
				$endTime = date('Y-m-d H:i:s', $now);
			}else{
				$ppq->accelerate_info = json_decode($ppq->accelerate_info, true);
				$_time = strtotime($ppq->create_time);
				$usedTime = 0;
				$v = 1;
				$immediat = false;
				if(@$ppq->accelerate_info['log']){
					foreach($ppq->accelerate_info['log'] as $_i => $_log){
						$s = ($_log['time'] - $_time);
						//$usedTime += pow(2, $_i) * $s;
						$usedTime += $v * $s;
						if($_log['itemId'] == -1){
							$immediat = true;
							$usedTime = $ppq->accelerate_info['second'];
							break;
						}else{
							$v *= $_log['v'];
						}
						$_time = $_log['time'];
					}
					if(!$immediat){
						$usedTime += ($now - $_time) * $v;
					}
				}
				
				$restTime = $ppq->accelerate_info['second'] - $usedTime;
				//计算剩余时间
				$createTime = date('Y-m-d H:i:s', $now - $restTime);
				$endTime = date('Y-m-d H:i:s', $now + $usedTime);
			}
			
			$extraData = [
				'from_map_id' => $ppq->to_map_id,
				'from_x' => $ppq->to_x,
				'from_y' => $ppq->to_y,
				'to_map_id' => $ppq->from_map_id,
				'to_x' => $ppq->from_x,
				'to_y' => $ppq->from_y,
				'carry_gold' => $ppq->carry_gold,
				'carry_food' => $ppq->carry_food,
				'carry_wood' => $ppq->carry_wood,
				'carry_stone' => $ppq->carry_stone,
				'carry_iron' => $ppq->carry_iron,
				'carry_item' => $ppq->carry_item,
			];
			
			//撤销原有队列
			if(!$this->cancelQueue($ppq->player_id, $ppq->id)){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//新增回家队列
			$newQueueId = $this->addQueue($ppq->player_id, $ppq->guild_id, 0, $returnType, ['create_time'=>$createTime, 'end_time'=>$endTime], $ppq->army_id, [], $extraData);
			if(!$newQueueId){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//集结子队列
			if(in_array($ppq->type, $this->gatherTypes)){//大召回
				//查找子队列
				$otherPpqs = self::find(['parent_queue_id='.$ppq->id.' and type='.self::TYPE_GATHERDBATTLE_GOTO.' and status=1']);
				
				foreach($otherPpqs as $_ppq){
					//撤销原有队列
					if(!$this->cancelQueue($_ppq->player_id, $_ppq->id)){
						if(!@$inDispWorker)
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
						return false;
					}
					$_extraData = [
						'from_map_id' => $_ppq->to_map_id,
						'from_x' => $_ppq->to_x,
						'from_y' => $_ppq->to_y,
						'to_map_id' => $_ppq->from_map_id,
						'to_x' => $_ppq->from_x,
						'to_y' => $_ppq->from_y,
						'carry_gold' => $_ppq->carry_gold,
						'carry_food' => $_ppq->carry_food,
						'carry_wood' => $_ppq->carry_wood,
						'carry_stone' => $_ppq->carry_stone,
						'carry_iron' => $_ppq->carry_iron,
						'carry_item' => $_ppq->carry_item,
						'parent_queue_id' => $newQueueId,
					];
					
					//新增回集结者家队列
					if(!$this->addQueue($_ppq->player_id, $_ppq->guild_id, 0, self::TYPE_GATHERD_MIDRETURN, ['create_time'=>$createTime, 'end_time'=>$endTime], $_ppq->army_id, [], $_extraData)){
						if(!@$inDispWorker)
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
						return false;
					}
				}
			}
			
			if(in_array($ppq->type, [self::TYPE_CITYBATTLE_GOTO,self::TYPE_GATHERBATTLE_GOTO])){
				socketSend(['Type'=>'cancelattacked', 'Data'=>['playerId'=>[$ppq->target_player_id]]]);
			}
			
		}elseif($ppq->type == self::TYPE_GATHER_WAIT){//如果是集结等待中，参考Map::cancelGather
			
			//获取军团
			$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByArmyId($ppq->player_id, $ppq->army_id);
			if(!$playerArmy){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			if(!$PlayerArmy->assign($playerArmy)->updateStatus(0)){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//修改武将状态
			$PlayerArmyUnit = new PlayerArmyUnit;
			$pau = $PlayerArmyUnit->getByArmyId($ppq->player_id, $ppq->army_id);
			$generalIds = [];
			foreach($pau as $_pau){
				$generalIds[] = $_pau['general_id'];
			}
			$PlayerGeneral = new PlayerGeneral;
			if(!$PlayerGeneral->updateReturnByGeneralIds($ppq->player_id, $generalIds)){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//删除队列
			if(!$this->cancelQueue($ppq->player_id, $ppq->id)){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//更新前往集结盟友队列的结束时间
			$queues = $this->find(["parent_queue_id=".$ppq->id." and status=1 and type=".self::TYPE_GATHER_STAY]);
			foreach($queues as $_q){
				if(!$_q->updateEndtime(date('Y-m-d H:i:s'))){
					if(!@$inDispWorker)
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
					return false;
				}
			}
			
			$queues = $this->find(["parent_queue_id=".$ppq->id." and status=1 and type=".self::TYPE_GATHER_GOTO])->toArray();
			$relateQueue = array_merge($relateQueue, $queues);
			
			
		}
		
		if($clearXyCache){
			if(!@$inDispWorker)
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher'), $ppq->to_x, $ppq->to_y);
		}
		
		foreach($relateQueue as $_q){
			$this->callbackQueue($_q['id'], $_q['to_x'], $_q['to_y']);
		}
		
		return true;
	}

    /**
     * 秒回所有队伍（除了已经出发的集结子队）
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function callbackQueueNowByPlayerId($playerId){
		$ppqs = self::find(['player_id='.$playerId.' and status=1']);
		$now = date('Y-m-d H:i:s');
		foreach($ppqs as $_ppq){
			//排除集结援助方去攻城的
			if($_ppq->type == self::TYPE_GATHERDBATTLE_GOTO){
				continue;
			}elseif(in_array($_ppq->type, array_keys($this->moveTypes))){
			//外出移动队列，撤销队列，建立返回队列
				$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y, ['rightnow'=>true]);
			}elseif(in_array($_ppq->type, array_keys($this->stayTypes))){//如果是固定队列，修改结束时间，添加立即回城标志
				$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y, ['rightnow'=>true]);
				//修改结束时间
			}elseif(in_array($_ppq->type, array_merge($this->moveTypes, $this->stayTypes))){//如果是回城队列，修改结束时间
				$_ppq->updateEndtime(date('Y-m-d H:i:s'), true);
			}elseif($_ppq->type == self::TYPE_GATHERD_MIDRETURN){//如果是撤回集结者家的队列，修改结束时间，添加立即回城标志
				$_ppq->updateEndtime(date('Y-m-d H:i:s'), true);
			}elseif($_ppq->type == self::TYPE_GATHER_WAIT){//如果是集结等待中，参考Map::cancelGather
				$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y, ['rightnow'=>true]);
			}else{
				return false;
			}
		}
		return true;
	}

	public function refreshCollection($playerId){
		$carryType = array(
			3 => 'gold',
			4 => 'food',
			5 => 'wood',
			6 => 'stone',
			7 => 'iron',
			9 => 'gold',
			10 => 'food',
			11 => 'wood',
			12 => 'stone',
			13 => 'iron',
		);
		$ppqs = self::find(['player_id='.$playerId.' and status=1 and type in ('.join(',', [self::TYPE_COLLECT_ING, self::TYPE_GUILDCOLLECT_ING]).')'])->toArray();
		$ppq = $this->afterFindQueue($ppqs);
		
		//重算采集速度
		$PlayerBuff = new PlayerBuff;
		$Map = new Map;
		$MapElement = new MapElement;
		foreach($ppq as $_i => $_ppq){
			//地图信息
			$map = $Map->getByXy($_ppq['to_x'], $_ppq['to_y']);
			if(!$map){
				return false;
			}
			if($map['map_element_origin_id'] == 22) continue;//屏蔽据点
			$targetInfo = $_ppq['target_info'];
			
			//剩余负重
			$_weight = $_ppq['target_info']['weight'];
			
			//获取采集加成buff
			//$collectionBuff = $PlayerBuff->getPlayerBuff($_ppq['player_id'], $carryType[$map['map_element_origin_id']].'_gathering_speed');
			$collectionBuff = $PlayerBuff->getCollectionBuff($_ppq['player_id'], $_ppq['army_id'], $carryType[$map['map_element_origin_id']]);
			if($Map->isInGuildArea($_ppq['player_id'])){
				$collectionBuff += 0.15;
			}
			
			//计算采集速度
			$me = $MapElement->dicGetOne($map['map_element_id']);
			if(!$me){
				return false;
			}
			$resPerMin = $me['collection'] * (1+$collectionBuff);
			$targetInfo['speed'] = $resPerMin;
			
			//计算采集时间
			$second = ceil($_ppq['target_info']['carry'] / ($resPerMin / 60));
			
			//更新queue
			if(!$this->assign($ppqs[$_i])->updateQueue($second, $targetInfo)){
				return false;
			}
		}
		return true;
	}
}