<?php
/**
 * 联盟
 *
 */
class Guild extends ModelBase{
	public $blacklist = array('guild_id', 'create_time', 'update_time', 'rowversion');
    /**
     * 根据playerId查出该玩家的联盟数据
     * @param  int  $playerId    
     * @param  boolean $forDataFlag 
     * @return array   
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $Player = new Player;
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
    public function createGuild(array $data){
        $guildMaxNumInit = (new Starting)->dicGetOne('alliance_default_member_count');

        $self                         = new self;
        $self->camp_id                = $data['camp_id'];
        $self->leader_player_id       = $data['leader_player_id'];
        $self->founder                = $data['leader_player_id'];
        $self->name                   = $data['name'];
        $self->short_name             = $data['short_name'];
        $self->icon_id                = $data['icon_id'];
        $self->max_num                = $guildMaxNumInit;
        $self->need_check             = $data['need_check'];
        $self->desc                   = $data['desc'];
        $self->condition_fuya_level   = $data['condition_fuya_level'];
        $self->condition_player_power = $data['condition_player_power'];
        $self->create_time            = $self->update_time = $self->change_camp_time = date('Y-m-d H:i:s');
        $self->save();
        //联盟称谓
        (new GuildRankName)->addRankName($self->id);
        return $self->toArray();
    }
    /**
     * 获得guild
     * @param  int $id 
     * @param  bool $forDataFlag
     * @param  bool $assosiationFlag
     * @return array
     */
    public function getGuildInfo($id, $forDataFlag=false, $assosiationFlag=true){
        $re = Cache::getGuild($id, __CLASS__);
        if(!$re) {
            $guild = self::findFirst($id);
            if(!$guild) return [];
            $re = $this->adapter($guild->toArray(), true);
            $re['max_num'] += (new GuildBuff)->getGuildBuff($id, 443);//2是具体指
            if($forDataFlag) {
                $re = filterFields([$re], $forDataFlag, $this->blacklist)[0];
            }
            Cache::setGuild($id, __CLASS__, $re);
        }
        if($assosiationFlag) {
            $guildRankName            = (new GuildRankName)->getByGuildId($id);
            $guildRankName            = Set::sort($guildRankName, '{n}.rank', 'asc');
            $guildRankName            = Set::classicExtract($guildRankName, '{n}.name');
            $re['GuildRankName']      = $guildRankName;
            $re['leader_player_nick'] = (new Player)->getByPlayerId($re['leader_player_id'])['nick'];
        }
        return $re;
    }
	
	public function updateCoin($id, $coin){
		$now = date('Y-m-d H:i:s');
		$data = array(
			'coin'=>'coin+('.$coin.')',
			'update_time'=>"'".$now."'",
		);
		$where = ["id"=>$id];
		if($coin < 0){
			$where['coin >='] = abs($coin);
		}
		$ret = $this->updateAll($data, $where);
		$this->clearGuildCache($id);
		if(!$ret)
			return false;
		return true;
	}
    /**
     * 检查联盟名称是否重复  
     * @param  string $name 
     * @return bool true:存在重复 false：不存在       
     */
    public function checkNameExists($name){
        $re = self::findFirst(["name=:name:", 'bind'=>['name'=>$name]]);
        return !empty($re);

    }
    /**
     * 检查联盟短名称是否重复  
     * @param  string $name 
     * @return bool true:存在重复 false：不存在       
     */
    public function checkShortNameExists($shortName){
        $re = self::findFirst(["short_name=:short_name:", 'bind'=>['short_name'=>$shortName]]);
        return !empty($re);

    }
    /**
     * 删除联盟
     * @param  int $guildId 
     */
    public function dismissGuild($guildId){
        self::findFirst($guildId)->delete();
        $this->clearGuildCache($guildId);
    }
    /**
     * 搜索联盟
     * @param  array  $searchData 搜索条件
     */
    public function search(array $searchData){
        $Player     = new Player;
        $GuildBuff  = new GuildBuff;
        $q          = $this->query();
        $fromPage   = $searchData['from_page'];
        $numPerPage = $searchData['num_per_page'];

        $bindParam = [];
        if(isset($searchData['name']) && $searchData['name']) {
            $name = $searchData['name'];
            $q->where("name like :name: or short_name like :name:");
            $bindParam['name'] = "%{$name}%";
        }
        if(isset($searchData['num']) && $searchData['num']) {
            $q->andWhere("num >= :num:");
            $bindParam['num'] = $searchData['num'];
        }
        if(isset($searchData['guild_power']) && $searchData['guild_power']) {
            $q->andWhere("guild_power >= :guild_power:");
            $bindParam['guild_power'] = $searchData['guild_power'];
        }
        if(isset($searchData['need_check']) && $searchData['need_check']!=-1) {
            $q->andWhere("need_check = :need_check:");
            $bindParam['need_check'] = $searchData['need_check'];
        }
        if(!empty($bindParam)) {
            $q->bind($bindParam);
        }
        $q->order('(max_num-num) desc, rand()');//jira2732

        $re = $q->limit($numPerPage, $fromPage*$numPerPage)->execute();
        $r = $this->adapter($re->toArray());
        foreach($r as &$v) {
            $leader = $Player->getByPlayerId($v['leader_player_id']);
            $v['leader_player_nick'] = $leader['nick'];
            $v['max_num'] += $GuildBuff->getGuildBuff($v['id'], 443);//2是具体指
        }
        unset($v);
        // dump($r);
        return $r;
    }
    /**
     * 增加现有人数
     * @param  int  $guildId 帮会id
     * @param  integer $num  
     */
    public function incGuildNum($guildId, $num=1){
        $buff = (new GuildBuff)->getGuildBuff($guildId, 443);//2是具体指
        $this->updateAll(['num'=>'num+1'], ['id'=>$guildId, 'num <'=>"max_num+{$buff}"]);
        $this->clearGuildCache($guildId);
    }
    /**
     * 减少联盟现有人数
     * @param  int  $guildId 
     * @param  integer $num     
     */
    public function decGuildNum($guildId, $num=1) {
        $this->updateAll(['num'=>'num-1'], ['id'=>$guildId, 'num >'=>0]);
        $this->clearGuildCache($guildId);
    }
    /**
     * 更改guild表的值
     * @param  int $guildId 
     * @param  array  $fields  
     */
    public function alter($guildId, array $fields){
        $this->updateAll($fields, ['id'=>$guildId]);
        $this->clearGuildCache($guildId);
    }
    
    /**
     * 处理goto堡垒队列
     * @param  array $ppq 
     * @return bool
     */
    public function processGotoGuildBuild($ppq){
        if($ppq['status']!=1) {
            echo "status != 1\n";
            return false;
        }
        $Map                = new Map;
        $MapElement         = new MapElement;
        $PlayerProjectQueue = new PlayerProjectQueue;
        $playerId           = $ppq['player_id'];
        // finish current queue
        $PlayerProjectQueue->finishQueue($playerId, $ppq['id']);

        $toMapId            = $ppq['to_map_id'];
        $toMap              = $Map->getByXy($ppq['to_x'], $ppq['to_y']);

        if(!$toMap) {//如果目标不存在，直接返回
            backNow:
            $return_extra_data = [
                'from_map_id' => $ppq['to_map_id'],
                'from_x'      => $ppq['to_x'],
                'from_y'      => $ppq['to_y'],
                'to_map_id'   => $ppq['from_map_id'],
                'to_x'        => $ppq['from_x'],
                'to_y'        => $ppq['from_y'],
            ];
            $returnTypeArr  = [
                PlayerProjectQueue::TYPE_GUILDBASE_GOTO      => PlayerProjectQueue::TYPE_GUILDBASE_RETURN,
                PlayerProjectQueue::TYPE_GUILDWAREHOUSE_GOTO => PlayerProjectQueue::TYPE_GUILDWAREHOUSE_RETURN,
                PlayerProjectQueue::TYPE_GUILDTOWER_GOTO     => PlayerProjectQueue::TYPE_GUILDTOWER_RETURN,
                PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO   => PlayerProjectQueue::TYPE_GUILDCOLLECT_RETURN,
            ];
            $return_type = $returnTypeArr[$ppq['type']];
            $needTime = PlayerProjectQueue::calculateMoveTime($playerId, $ppq['to_x'], $ppq['to_y'], $ppq['from_x'], $ppq['from_y'], 3, $ppq['army_id']);
            $PlayerProjectQueue->addQueue($playerId, $ppq['guild_id'], 0, $return_type, $needTime, $ppq['army_id'], [], $return_extra_data);
            return false;
        }

        //data for queue
        $extraData['from_map_id'] = $toMapId;
        $extraData['from_x']      = $ppq['to_x'];
        $extraData['from_y']      = $ppq['to_y'];
        $extraData['to_map_id']   = $toMapId;
        $extraData['to_x']        = $ppq['to_x'];
        $extraData['to_y']        = $ppq['to_y'];
        $needTime                 = ['end_time'=>'0000-00-00 00:00:00', 'create_time'=>date('Y-m-d H:i:s')];

        $guildBaseElement = $MapElement->dicGetOne($toMap['map_element_id']);//联盟建筑
        $maxStationed     = $guildBaseElement['max_stationed'];//最大驻守
        $maxConstruction  = $guildBaseElement['max_construction'];//最大建造
        if($toMap['map_element_id']==101) {//堡垒
            if($toMap['status']==0) {//1建造堡垒
                $type     = PlayerProjectQueue::TYPE_GUILDBASE_BUILD;
                $newPpqId = $PlayerProjectQueue->addQueue($playerId, $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData); 
                $newPpq   = PlayerProjectQueue::findFirst($newPpqId)->toArray();
                $newPpq   = $PlayerProjectQueue->afterFindQueue([$newPpq])[0];
                $PlayerProjectQueue->calculateGuildBaseConstructValue($newPpq, false, true);
                $PlayerProjectQueue->updateGuildBaseEndTime($newPpq);//更新时间

                //检测是否超过最大建造数
                $count = PlayerProjectQueue::count([
                                                       "to_map_id=:toMapId: and type=:type: and status=1",
                                                       'bind' => [
                                                           'toMapId' => $toMapId,
                                                           'type'    => $type,
                                                       ],
                                                   ]);
                if($count>=($maxConstruction+1)) {
                    $PlayerProjectQueue->updateAll(['end_time'=>qd(), 'update_time'=>qd(), 'rowversion'=>'rowversion*1+1'], ['id'=>$newPpqId, 'status'=>1, 'type'=>$type]);
                } else {
                    $PlayerProjectQueue->updateAll(['end_time' => qd(), 'update_time' => qd(), 'rowversion' => 'rowversion*1+1'], ['player_id' => $playerId, 'status' => 1, 'type' => [PlayerProjectQueue::TYPE_GUILDBASE_BUILD, PlayerProjectQueue::TYPE_GUILDBASE_REPAIR, PlayerProjectQueue::TYPE_GUILDBASE_DEFEND], 'to_map_id' => $ppq['to_map_id'], 'id <>' => $newPpqId]);
                }

            }elseif($toMap['status']==1){
                if($toMap['durability']<$toMap['max_durability']) {//2修建堡垒
                    $type     = PlayerProjectQueue::TYPE_GUILDBASE_REPAIR;
                    $newPpqId = $PlayerProjectQueue->addQueue($playerId, $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData);
                    $newPpq   = PlayerProjectQueue::findFirst($newPpqId)->toArray();
                    $newPpq   = $PlayerProjectQueue->afterFindQueue([$newPpq])[0];
                    $PlayerProjectQueue->calculateGuildBaseConstructValue($newPpq, false, true);
                    $PlayerProjectQueue->updateGuildBaseEndTime($newPpq);//更新时间

                    //检测是否超过最大建造数
                    $count = PlayerProjectQueue::count([
                                                           "to_map_id=:toMapId: and type=:type: and status=1",
                                                           'bind' => [
                                                               'toMapId' => $toMapId,
                                                               'type'    => $type,
                                                           ],
                                                       ]);
                    if($count>=($maxConstruction+1)) {
                        $PlayerProjectQueue->updateAll(['end_time'=>qd(), 'update_time'=>qd(), 'rowversion'=>'rowversion*1+1'], ['id'=>$newPpqId, 'status'=>1, 'type'=>$type]);
                    } else {
                        $PlayerProjectQueue->updateAll(['end_time' => qd(), 'update_time' => qd(), 'rowversion' => 'rowversion*1+1'], ['player_id' => $playerId, 'status' => 1, 'type' => [PlayerProjectQueue::TYPE_GUILDBASE_BUILD, PlayerProjectQueue::TYPE_GUILDBASE_REPAIR, PlayerProjectQueue::TYPE_GUILDBASE_DEFEND], 'to_map_id' => $ppq['to_map_id'], 'id <>' => $newPpqId]);
                    }
                } else {//驻守城堡
                    $type     = PlayerProjectQueue::TYPE_GUILDBASE_DEFEND;
                    $newPpqId = $PlayerProjectQueue->addQueue($playerId, $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData);
                    //检测是否超过最大驻守
                    $count = PlayerProjectQueue::count([
                                                           "to_map_id=:toMapId: and type=:type: and status=1",
                                                           'bind' => [
                                                               'toMapId' => $toMapId,
                                                               'type'    => $type,
                                                           ],
                                                       ]);
                    if($count>=($maxStationed+1)) {
                        $PlayerProjectQueue->updateAll(['end_time'=>qd(), 'update_time'=>qd(), 'rowversion'=>'rowversion*1+1'], ['id'=>$newPpqId, 'status'=>1, 'type'=>$type]);
                    } else {
                        $PlayerProjectQueue->updateAll(['end_time' => qd(), 'update_time' => qd(), 'rowversion' => 'rowversion*1+1'], ['player_id' => $playerId, 'status' => 1, 'type' => [PlayerProjectQueue::TYPE_GUILDBASE_BUILD, PlayerProjectQueue::TYPE_GUILDBASE_REPAIR, PlayerProjectQueue::TYPE_GUILDBASE_DEFEND], 'to_map_id' => $ppq['to_map_id'], 'id <>' => $newPpqId]);
                    }
                }
            }
            return true;
        } else {//处理goto仓库 箭塔 超级矿
            if($toMap['status']==0) {//去建造
                $typeArr  = [
                    PlayerProjectQueue::TYPE_GUILDWAREHOUSE_GOTO => PlayerProjectQueue::TYPE_GUILDWAREHOUSE_BUILD,
                    PlayerProjectQueue::TYPE_GUILDTOWER_GOTO     => PlayerProjectQueue::TYPE_GUILDTOWER_BUILD,
                    PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO   => PlayerProjectQueue::TYPE_GUILDCOLLECT_BUILD,
                ];
                $type     = $typeArr[$ppq['type']];
                $newPpqId = $PlayerProjectQueue->addQueue($playerId, $ppq['guild_id'], 0, $type, $needTime, $ppq['army_id'], [], $extraData); 
                $newPpq   = PlayerProjectQueue::findFirst($newPpqId)->toArray();
                $newPpq   = $PlayerProjectQueue->afterFindQueue([$newPpq])[0];
                $PlayerProjectQueue->calculateGuildBaseConstructValue($newPpq, false, true);
                $PlayerProjectQueue->updateGuildBaseEndTime($newPpq);//更新时间

                $PlayerProjectQueue->updateAll(['end_time'=>qd(), 'update_time'=>qd(),'rowversion'=>'rowversion*1+1'], ['player_id'=>$playerId, 'status'=>1, 'type'=>$type, 'to_map_id'=>$ppq['to_map_id'], 'id <>'=>$newPpqId]);
                //检测是否超过最大建造数
                $count = PlayerProjectQueue::count([
                                                       "to_map_id=:toMapId: and type=:type: and status=1",
                                                       'bind' => [
                                                           'toMapId' => $toMapId,
                                                           'type'    => $type,
                                                       ],
                                                   ]);
                if($count>=($maxConstruction+1)) {
                    $PlayerProjectQueue->updateAll(['end_time'=>qd(), 'update_time'=>qd(), 'rowversion'=>'rowversion*1+1'], ['id'=>$newPpqId, 'status'=>1, 'type'=>$type]);
                }
            } else {
                if($ppq['type']==PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO) {//超级矿的采集
                    (new QueueCollection)->_goto($ppq);
                }elseif($ppq['type']==PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHGOTO){//联盟仓库 存资源 
                    $this->storeResource($ppq);
                }
                else {
                    goto backNow;
                }
            }
            return true;
        }
        return false;
    }

    public function storeResource($ppq){
        $guildId = $ppq['guild_id'];
        $playerId = $ppq['player_id'];
        $Player = new Player;
        $player = $Player->getByPlayerId($playerId);
        $PlayerGuild = new PlayerGuild;
        $PlayerProjectQueue = new PlayerProjectQueue;

        $tInfo = json_decode($ppq['target_info'], true);
        if(!empty($tInfo)){
            if( $PlayerGuild->hasEnoughStoreResource($playerId, $tInfo) ){
                $resource = ['gold'=>(-1)*$tInfo['gold'], 'food'=>(-1)*$tInfo['food'], 'wood'=>(-1)*$tInfo['wood'], 'stone'=>(-1)*$tInfo['stone'], 'iron'=>(-1)*$tInfo['iron']];
                $PlayerGuild->updateStoreResource($playerId, $resource);
                $extraData = ['carry_gold'=>$tInfo['gold'], 'carry_food'=>$tInfo['food'], 'carry_wood'=>$tInfo['wood'], 'carry_stone'=>$tInfo['stone'], 'carry_iron'=>$tInfo['iron']];
            }
        }else{
            $resource = ['gold'=>$ppq['carry_gold'], 'food'=>$ppq['carry_food'], 'wood'=>$ppq['carry_wood'], 'stone'=>$ppq['carry_stone'], 'iron'=>$ppq['carry_iron']];
            if($PlayerGuild->updateStoreResource($playerId, $resource)){
                $extraData = [];
            }else{
                $extraData = ['carry_gold'=>$ppq['carry_gold'], 'carry_food'=>$ppq['carry_food'], 'carry_wood'=>$ppq['carry_wood'], 'carry_stone'=>$ppq['carry_stone'], 'carry_iron'=>$ppq['carry_iron']];
            }
        }
       

        $extraData['from_map_id'] = $ppq['to_map_id'];
        $extraData['from_x']      = $ppq['to_x'];
        $extraData['from_y']      = $ppq['to_y'];
        $extraData['to_map_id']   = $ppq['from_map_id'];
        $extraData['to_x']        = $ppq['from_x'];
        $extraData['to_y']        = $ppq['from_y'];
        $needTime = $PlayerProjectQueue->calculateMoveTime($playerId, $ppq['to_x'], $ppq['to_y'], $ppq['from_x'], $ppq['from_y'], 5, 0);

        $PlayerProjectQueue->addQueue($playerId, $guildId, 0, PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHRETURN, $needTime, 0, [], $extraData);

    }
    /**
     * 查找未入盟玩家who 符合当前盟入盟条件
     * @param  array $guild       
     * @param  array $playerGuild 
     * @return array  玩家id
     */
    public function searchRandPlayers($guild, $playerGuild){
        $Player = new Player;

        $guildId              = $guild['id'];
        $guildCampId          = $guild['camp_id'];
        $conditionFuyaLevel   = $guild['condition_fuya_level'];
        $conditionPlayerPower = $guild['condition_player_power'];
        
        //1 invite_end_time = now+4hour
        $this->alter($guildId, ['invite_end_time'=>q(date('Y-m-d H:i:s', time()+4*60*60))]);
        //2 invite 30 players whom not have guild
        //长连接来实现
        $data        = ['Type' => 'all_conn_info', 'Data' => []];
        $data['Msg'] = 'DataRequest';
        $re          = socketSend($data);
        $re          = json_decode($re['content'], true);
        // sleep(1);
        if(empty($re)) {//如果在线人数为空
            return false;
        }
        $playerIdOnline = Set::extract('/player_id', $re);
        $playerIdOnlineStr = join(',', $playerIdOnline);

        $sql = <<<CONDITION
        SELECT a.id FROM player a LEFT JOIN player_build b
        ON a.id=b.player_id
        WHERE b.origin_build_id=1 AND a.guild_id=0 AND b.build_level >= {$conditionFuyaLevel} AND a.power >= {$conditionPlayerPower} and a.id in ({$playerIdOnlineStr})
        LIMIT 50;
CONDITION;
        $players = $Player->sqlGet($sql);
        if(count($players)>30) {
            ashuffle($players);
            $players = array_splice($players, 0, 30);
        }
        $guildShortInfo = keepFields($guild, ['id', 'name', 'num', 'max_num', 'guild_power', 'leader_player_nick'], true);
        //发长连接消息
        foreach($players as $player) {
            $data = ['Type'=>'invite_guild', 'Data'=>['camp_id'=>$guildCampId,'to_player_id'=>intval($player['id']), 'guild'=>$guildShortInfo]];
            socketSend($data);
        }
    }

	public function addDonateCount($guildId, $date, $add){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll([
			'donate_counter'=>'if(donate_date="'.$date.'", donate_counter+'.$add.', '.$add.')', 
			'donate_date'=>'"'.$date.'"',
			'update_time'=>"'".$now."'",
		], ['id'=>$guildId]);
		$this->clearGuildCache($guildId);
		if(!$ret)
			return false;
		return true;
	}
}
