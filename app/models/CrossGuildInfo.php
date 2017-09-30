<?php
/**
 * cross_guild_info
 *
 */
class CrossGuildInfo extends CrossModelBase{
    const Status_joined     = 1;//参加比赛
    const Status_not_joined = 0;//未参加比赛

    public $blacklist = array('match_score', 'create_time', 'update_time');
    /**
     * 添加 跨服联盟基础数据
     *
     * @param $originGuildId
     * @return int
     */
    public function addNew($originGuildId){
        global $config;
        $serverId = $config->server_id;
        $guildId  = CrossPlayer::joinGuildId($serverId, $originGuildId);
        $exists   = self::findFirst(['guild_id=:guildId:', 'bind'=>['guildId'=>$guildId]]);
        if(!$exists) {
            $self              = new self;
            $self->guild_id    = $guildId;
            $self->update_time = date('Y-m-d H:i:s');
            $self->create_time = date('Y-m-d H:i:s');
            $self->save();
            $re = self::findFirst($self->id);
            return $re;
        }
        return null;
    }

    /**
     * 更新cross_guild_info表
     *
     * @param       $originGuildId
     * @param array $fields
     * @param int $serverId
     * @return int
     */
    public function alter($originGuildId, array $fields, $serverId=0){
        if($serverId==0) {
            global $config;
            $serverId = $config->server_id;
        }
        $guildId  = CrossPlayer::joinGuildId($serverId, $originGuildId);

        $exists   = self::findFirst(['guild_id=:guildId:', 'bind'=>['guildId'=>$guildId]]);
        if(!$exists) {
            $this->addNew($originGuildId);
        }
        if(!isset($fields['update_time'])) {
            $fields['update_time'] = qd();
        }
        $re = $this->updateAll($fields, ['guild_id' => $guildId]);
        return $re;
    }

    /**
     * 获取单条记录
     *
     * @param $guildId
     *
     * @return array
     */
    public function getCrossGuildBasicInfo($guildId){
        if($guildId<=0) return [];
        BasicInfo:
        $re = self::findFirst(['guild_id=:guildId:', 'bind'=>['guildId'=>$guildId]]);
        if($re) {
            $re = $re->toArray();
            $r  = $this->adapter($re, true);
            return $r;
        } else {
            $originGuildId = CrossPlayer::parseGuildId($guildId)['guild_id'];
            $this->addNew($originGuildId);
            goto BasicInfo;
        }
        return [];
    }

    /**
     * 更改表中相关信息
     *
     * @param $guild1Id
     * @param $guild2Id
     * @param $win
     */
    public function changeInfo($guild1Id, $guild2Id, $win){
        $sql1 = "select * from cross_guild_info where guild_id in ({$guild1Id}, {$guild2Id})";
        $re   = $this->sqlGet($sql1);
        foreach($re as $r) {
            $isWin = ($r['guild_id']==$guild1Id && $win==1) || ($r['guild_id']==$guild2Id && $win==2) ? true : false;
            $fields = [];
            if($isWin) {//胜
                $fields['win_times']            = 'win_times+1';
                $fields['latest_battle_is_win'] = 1;
            } else {//败
                $fields['lose_times']           = 'lose_times+1';
                $fields['latest_battle_is_win'] = 0;
            }

            $this->updateAll($fields, ['id'=>$r['id']]);
        }
    }//changeInfo

    /**
     * 是否参加联盟战比赛 只是查看当前玩家所在的盟，即当前服
     *
     * ```php
     * (new CrossGuildInfo)->isJoined($guildId);
     * ```
     *
     * @param $guildId
     *
     * @return bool
     */
    public function isJoined($guildId){
        global $config;
        $serverId       = $config->server_id;
        $joinedGuildId  = CrossPlayer::joinGuildId($serverId, $guildId);
        $currentRoundId = (new CrossRound)->getCurrentRoundId();
        if($currentRoundId){
            $crossGuildInfo = $this->getCrossGuildBasicInfo($joinedGuildId);
            if($crossGuildInfo['status']==CrossGuildInfo::Status_joined) {
                return true;
            }
        }
        return false;
    }//isJoined
}
