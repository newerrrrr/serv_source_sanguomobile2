<?php
/**
 * 屯所
 */
class PlayerHelp extends ModelBase{
    public $blacklist               = ['create_time', 'update_time'];
    
    const HELP_PROCEEDING           = 1;//进行中的帮助
    const HELP_FINISH               = 0;//已经完成的，不需要帮助
    
    const HELP_TYPE_BUILD           = 1;//建造 升级
    const HELP_TYPE_SCIENCE         = 2;//科技研究
    const HELP_TYPE_SOLDIER_INJURED = 3;//伤兵
    /**
     * 增援到达执行
     * @param  int $playerProjectQueueId    
     * @return [type] [description]
     */
    public function reinforce($playerProjectQueue){
        $PlayerProjectQueue = new PlayerProjectQueue;
        $ppq = (object)$playerProjectQueue;

        if($ppq) {
            // $targetInfo = json_decode($ppq->target_info, true);
            $playerId = $ppq->player_id;
            $targetPlayerId = $ppq->target_player_id;

            //case: finish current queue
            $PlayerProjectQueue->finishQueue($playerId, $ppq->id);

            //case: add new queue 
            $Player = new Player;
            $player = $Player->getByPlayerId($playerId);
            $targetPlayer = $Player->getByPlayerId($targetPlayerId);
            $endTime = '0000-00-00 00:00:00';
            //case 1: 判断是否同盟
            if($targetPlayer['guild_id']!=$player['guild_id']) {//非同盟
                $endTime = date('Y-m-d H:i:s');
            }
            //case 1.1: 判断是否飞走
            if($targetPlayer['x']!=$ppq->to_x || $targetPlayer['y']!=$ppq->to_y) {//目标不对
                $endTime = date('Y-m-d H:i:s');
            }
            //case 2: 判断对方援军数是否达到最大
            $toPPQ = $PlayerProjectQueue->getHelpArmy($targetPlayerId, $playerId);
            if($toPPQ['current_help_num']>=$toPPQ['max_help_num']) {
                $endTime = date('Y-m-d H:i:s');
            }
            //case 3: 自己已经有一支援军,则当前部队撤回
            $ppqAlready = PlayerProjectQueue::find("player_id={$playerId} and target_player_id={$targetPlayerId} and status=1 and end_time='0000-00-00 00:00:00' and type=".PlayerProjectQueue::TYPE_CITYASSIST_ING)->toArray();
            if($ppqAlready) {
                $endTime = date('Y-m-d H:i:s');
            }

            $type                     = PlayerProjectQueue::TYPE_CITYASSIST_ING;//增援中
            
            $extraData                = [];
            $extraData['from_map_id'] = $ppq->to_map_id;
            $extraData['from_x']      = $ppq->to_x;
            $extraData['from_y']      = $ppq->to_y;
            $extraData['to_map_id']   = $ppq->to_map_id;
            $extraData['to_x']        = $ppq->to_x;
            $extraData['to_y']        = $ppq->to_y;
            $PlayerProjectQueue->addQueue($playerId, $ppq->guild_id, $targetPlayerId, $type, ['create_time'=>date('Y-m-d H:i:s'),'end_time'=>$endTime], $ppq->army_id, [], $extraData);
            return true;
        }
        return false;

    }
    /**
     * 获取帮助信息
     * 
     * @param   int    $playerId    player id
     * @return  array    description
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $Player      = new Player;
        $player      = $Player->getByPlayerId($playerId);
        $guildId     = $player['guild_id'];
        $re          = Cache::getGuild($guildId, __CLASS__);
        if(!$re) {
            $re = self::find(["guild_id={$guildId} and status=1"])->toArray();
            $re = $this->adapter($re);
            foreach($re as $k=>&$v) {
                $vPlayer     = $Player->getByPlayerId($v['player_id']);

                $v['player_avatar_id'] = $vPlayer['avatar_id'];
                $v['player_nick']      = $vPlayer['nick'];
                $v['helper_ids']       = array_map('intval', explode(',', $v['helper_ids']));
                if($forDataFlag) {
                    unset($v['create_time']);
                    unset($v['update_time']);
                }
            }
            unset($v);
            Cache::setGuild($guildId, __CLASS__, $re);
        }
        return $re;

    }
    /**
     * 删除所有玩家帮助信息
     * @param int guildId [<description>]
     * @param int playerId [<description>]
     */
    public function delAllPlayerHelp($guildId, $playerId){
        self::find(["player_id = {$playerId} and guild_id={$guildId} and status=1"])->delete();
        $this->clearGuildCache($guildId);//清缓存
    }
    /**
     * 添加一条帮助记录
     * @param int $playerId       
     * @param int $position 建筑的position，据此推算出help_resource_id
     */
    public function addPlayerHelp($playerId, $position){
        $returnFlag     = false;
        $Player         = new Player;
        $PlayerBuild    = new PlayerBuild;
        $Build          = new Build;
        $PlayerBuff     = new PlayerBuff;
        
        $player         = $Player->getByPlayerId($playerId);
        $playerBuild    = $PlayerBuild->getByOrgId($playerId, 11);//获取玩家屯所
        if($playerBuild) {
            $playerBuild = $playerBuild[0];
            $build       = $Build->dicGetOne($playerBuild['build_id']);
        } else {//没有屯所则按照一级屯所的规则来
            $build = $Build->getOneByOrgIdAndLevel(11, 1);
            $playerBuild['position'] = 0;
        }
        $helpBuild      = $PlayerBuild->getByPosition($playerId, $position);//需要帮助的建筑
        $helpResourceId = $helpType = 0;
        if($helpBuild['need_help']!=0) {//还未发送过帮助请求
            if($helpBuild['status']==2) {//升级
                $returnFlag     = true;
                $helpType       = self::HELP_TYPE_BUILD;
                $helpResourceId = $helpBuild['build_id'];
            } elseif($helpBuild['status']==3) {//工作中
                if($helpBuild['origin_build_id']==10) {//研究所
                    $returnFlag     = true;
                    $helpType       = self::HELP_TYPE_SCIENCE;
                    $helpResourceId = $helpBuild['work_content'];//science_id
                } elseif($helpBuild['origin_build_id']==42) {//医院
                    $returnFlag = true;
                    $helpType   = self::HELP_TYPE_SOLDIER_INJURED;
                }
            }
            if($returnFlag) {
                $self                   = new self;
                $self->player_id        = $playerId;
                $self->help_num         = 0;
                $self->help_num_max     = $build['output'][10] + $PlayerBuff->getPlayerBuff($playerId, 'help_num_plus', $playerBuild['position']);
                $self->guild_id         = $player['guild_id'];
                $self->help_type        = $helpType;
                $self->build_position   = $position;
                $self->help_resource_id = $helpResourceId;
                $self->create_time      = date('Y-m-d H:i:s', time());
                $self->update_time      = date('Y-m-d H:i:s', time());

                $self->save();
                //改回不能帮助状态
                $PlayerBuild->updateAll(['need_help'=>0], ['id'=>$helpBuild['id']]);
                $PlayerBuild->clearDataCache($playerId);
                $this->clearGuildCache($player['guild_id']);//清缓存
                //长连接推送给盟里其他人
                socketSend(['Type'=>'guild_help_add', 'Data'=>['player_id'=>$playerId,'guild_id'=>$player['guild_id']]]);
            }
        }
    }
    /**
     * 帮助联盟内所有玩家 
     * 
     * @param   int    $playerId    player id
     * @return  array    description
     */
    public function updateAllHelpNum($playerId, $guildId){
        $PlayerBuild  = new PlayerBuild;
        $Build        = new Build;
        $PlayerTarget = new PlayerTarget;
        $PlayerBuff   = new PlayerBuff;
        $Player       = new Player;
        
        $player       = $Player->getByPlayerId($playerId);
        $playerHelp   = $this->getByPlayerId($playerId);

        $originBuild = $Build->getOneByOrgIdAndLevel(11, 1);
        foreach($playerHelp as $k=>$v) {
            $pid       = $v['player_id'];
            $helperIds = $v['helper_ids'];
            if ($pid==$playerId || $v['status']!=self::HELP_PROCEEDING || $v['help_num']>=$v['help_num_max'] || in_array($playerId, $helperIds)) {//自己 || 帮助到最大 || 帮助过的人
                continue;
            }
            //step 1 减时间
            $pb     = $PlayerBuild->getByOrgId($pid, 11);
            if($pb) {
                $pb    = $pb[0];
                $build = $Build->dicGetOne($pb['build_id']);
            } else {//没有屯所则按照一级屯所的规则来
                $build = $originBuild;
                $pb['position'] = 0;
            }
            $second = $build['output'][11];
            $second += $PlayerBuff->getPlayerBuff($pid, 'help_time_plus', $pb['position']);//buff
            //step a 加帮助次数
            
            //正常更新
            $affectedRows = $this->updateAll(
                [
                'help_num'    => 'help_num+1',
                'helper_ids'  => "concat(helper_ids, ',', {$playerId})",
                'update_time' => qd(),
                ], 
                [
                'id'                  => $v['id'],
                'helper_ids not like' => "'%,{$playerId}%'",
                'status'              => self::HELP_PROCEEDING,
                'help_num <'          => 'help_num_max',//加buff,
                'guild_id'            => $guildId,
                ]);

            if($affectedRows>0) {
                $this->clearGuildCache($guildId);//清缓存
                if($v['help_type']==self::HELP_TYPE_BUILD) {
                    $PlayerBuild->quickenLvUp($pid, $v['build_position'], $second);
                } else {
                    $PlayerBuild->QuickenWork($pid, $v['build_position'], $second);
                }
                $PlayerTarget->updateTargetCurrentValue($playerId, 23);//更新新手目标任务
                //step b 发送swoole消息
                $data = [
                    'Type'  => 'guild_help',
                    'Data'  => [
                        'from_player_id'   => $playerId,
                        'from_player_nick' => $player['nick'],
                        'to_player_id'     => $pid,
                        'position'         => $v['build_position'],
                        'help_type'        => $v['help_type'],
                        'help_resource_id' => $v['help_resource_id'],
                        'second'           => $second,
                    ]
                ];
                socketSend($data);//发送联盟帮助
            }
        }
    }
    /**
     * 帮助改为完成状态 
     * or
     * 取消建造，研究，恢复伤兵后，删相关记录
     * @param  int $playerId      
     * @param  int $helpResourceId
     */
    public function endPlayerHelp($playerId, $position){
		//将player_build的need_help改为0
		$PlayerBuild = new PlayerBuild;
		$PlayerBuild->updateAll(['need_help'=>0], ['player_id'=>$playerId, 'position'=>$position]);
		$PlayerBuild->clearDataCache($playerId);
		
        $re = self::findFirst("player_id={$playerId} and build_position={$position} and status=1");
        if($re) {
            $player  = (new Player)->getByPlayerId($playerId);
            $guildId = $player['guild_id'];
            $affectedRows = $this->updateAll(['status'=>self::HELP_FINISH], ['player_id'=>$playerId, 'build_position'=>$position, 'status'=>self::HELP_PROCEEDING]);
            if($guildId && $affectedRows) {
                $this->clearGuildCache($guildId);//清缓存
            }
        }
    }
    
}