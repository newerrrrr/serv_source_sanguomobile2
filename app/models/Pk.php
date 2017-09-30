<?php

/**
 * pk信息
 *
 */
class Pk extends PkModelBase{
    //武斗状态
    const STATUS_START = 0;//匹配武斗开始
    const STATUS_END   = 1;//武斗结束
    //武斗对象类型
    const TYPE_NPC     = 0;//机器人 NPC
    const TYPE_PLAYER  = 1;//玩家
    //复仇状态
    const REVENGE_STATUS_ON  = 0;//可以复仇
    const REVENGE_STATUS_OFF = 1;//已复仇
    const REVENGE_STATUS_NO  = 2;//无复仇

    const PK_LIST_NUM  = 50;//显示pk列表的最大数
    /**
     * pk信息更改
     *
     * @param       $id
     * @param array $fields
     * @param array $condition
     *
     * @return int
     */
    public function alter($id, array $fields, array $condition=[]){
        $cond = ['id'=>$id];
        if($condition) {
            $cond = array_merge($cond, $condition);
        }
        $re = $this->updateAll($fields, $cond);
        return $re;
    }

    /**
     * 添加pk记录
     *
     * @param     $serverId
     * @param     $playerId
     * @param int $targetServerId
     * @param int $targetPlayerId
     * @param int $type
     * @param bool $isRevenge
     *
     * @return int|array
     */
    public function addNew($serverId, $playerId, $targetServerId=0, $targetPlayerId=0, $type=self::TYPE_PLAYER, $isRevenge=false)
    {
        $Player          = new Player;
        $Guild           = new Guild;
        $PkPlayerInfo    = new PkPlayerInfo;
        $self            = new self;
        //攻击方
        $self->server_id = $serverId;
        $self->player_id = $playerId;
        $player          = $Player->getByPlayerId($playerId);
        $aBasicInfo      = $PkPlayerInfo->getBasicInfo($serverId, $playerId);

        $self->avatar_id = $player['avatar_id'];
        $self->level     = $player['level'];
        $self->nick      = $player['nick'];
        if($player['guild_id']>0) {
            $guild                  = $Guild->getGuildInfo($player['guild_id']);
            $self->guild_name       = $guild['name'];
            $self->guild_short_name = $guild['short_name'];
        }

        $self->target_server_id = $targetServerId;
        $self->target_player_id = $targetPlayerId;
        $self->start_time       = date('Y-m-d H:i:s');
        $self->type             = $type;
        //防守方
        if($type==self::TYPE_NPC) {
            $self->revenge_status   = self::REVENGE_STATUS_NO;
            $pkWithNpcTimes         = $aBasicInfo['pk_with_npc_times'];
            $robot                  = (new DuelRobot)->getByCount($pkWithNpcTimes + 1);
            $self->target_nick      = $robot['nick'];
            $self->target_level     = $robot['level'];
            $self->target_avatar_id = $robot['avatar_id'];
        } elseif($type==self::TYPE_PLAYER) {//获取目标玩家信息
            $targetPlayer = $Player->getPlayerBasicInfoByServer($targetServerId, $targetPlayerId);
            if($targetPlayer) {
                $self->target_guild_name       = $targetPlayer['guild_name'];
                $self->target_guild_short_name = $targetPlayer['guild_short_name'];
                $self->target_avatar_id        = $targetPlayer['avatar_id'];
                $self->target_level            = $targetPlayer['level'];
                $self->target_nick             = $targetPlayer['nick'];
            } else {
                return -1;
            }
        }
        if($isRevenge) $self->revenge_status = self::REVENGE_STATUS_NO;
        //存双方比赛前积分,duel_rank_id
        $self->total_score        = $aBasicInfo['score'];
        $self->duel_rank_id       = $aBasicInfo['duel_rank_id'];
        $self->target_total_score = 0;
        if($type==self::TYPE_PLAYER) {
            $bBasicInfo                = $PkPlayerInfo->getBasicInfo($targetServerId, $targetPlayerId);
            $self->target_total_score  = $bBasicInfo['score'];
            $self->target_duel_rank_id = $bBasicInfo['duel_rank_id'];
        }
        $self->save();
        return $self->toArray();
    }
    /**
     * 获取最后50条pk记录
     *
     * @param $serverId
     * @param $playerId
     *
     * @return array
     */
    public function getLastList($serverId, $playerId){
        $pkListNum = self::PK_LIST_NUM;
        $sql1 = <<<SQL_STAT
SELECT * FROM pk 
WHERE ((server_id={$serverId} AND player_id={$playerId}) OR (target_server_id={$serverId} AND target_player_id={$playerId})) AND `status`=1 
ORDER BY id DESC
LIMIT {$pkListNum};
SQL_STAT;
        $re = $this->sqlGet($sql1);
        $r = [];
        foreach($re as $k=>$v) {
            $_r = [
                'id'             => (int)$v['id'],
                'type'           => (int)$v['type'],
                'win_player_id'  => (int)$v['win_player_id'],
                'start_time'     => (int)$v['start_time'],
                'end_time'       => strtotime($v['end_time']),
                'revenge_status' => (int)$v['revenge_status'],
            ];
            $meGeneralInfo = $v['general_info']
                ? keepFields(json_decode(gzuncompress(base64_decode($v['general_info'])), true), ['general_id', 'lv'])
                : [];
            $targetGeneralInfo = $v['target_general_info']
                ? keepFields(json_decode(gzuncompress(base64_decode($v['target_general_info'])), true), ['general_id', 'lv'])
                : [];
            $_r['me'] = [
                         'score'            => (int)$v['score'],
                         'duel_rank_id'     => (int)$v['duel_rank_id'],
                         'player_id'        => (int)$v['player_id'],
                         'server_id'        => (int)$v['server_id'],
                         'avatar_id'        => (int)$v['avatar_id'],
                         'level'            => (int)$v['level'],
                         'nick'             => $v['nick'],
                         'guild_name'       => $v['guild_name'],
                         'guild_short_name' => $v['guild_short_name'],
                         'total_score'      => (int)$v['total_score'],
                         'general_info'     => $meGeneralInfo,

            ];
            $_r['target'] = [
                         'score'            => (int)$v['target_score'],
                         'duel_rank_id'     => (int)$v['target_duel_rank_id'],
                         'player_id'        => (int)$v['target_player_id'],
                         'server_id'        => (int)$v['target_server_id'],
                         'avatar_id'        => (int)$v['target_avatar_id'],
                         'level'            => (int)$v['target_level'],
                         'nick'             => $v['target_nick'],
                         'guild_name'       => $v['target_guild_name'],
                         'guild_short_name' => $v['target_guild_short_name'],
                         'total_score'      => (int)$v['target_total_score'],
                         'general_info'     => $targetGeneralInfo,
            ];
            $r[] = $_r;
        }
        return $r;
    }
    /**
     * 通过id获取记录
     *
     * @param $id
     *
     * @return array
     */
    public function getById($id){
        $re = self::findFirst($id);
        if($re) {
            return $re->toArray();
        }
        return [];
    }

    /**
     * 前端需要的格式化武将数据
     *
     * @param $generalInfo
     *
     * @return array
     */
    public static function formatGeneralInfo($generalInfo){
        $generalInfoFormater = [];
        if($generalInfo) {
            $generalInfoFormater['general_id'] = (int)$generalInfo['general_id'];
            $generalInfoFormater['lv']         = (int)$generalInfo['lv'];
            $generalInfoFormater['star_lv']    = (int)$generalInfo['star_lv'];
            $generalInfoFormater['weapon_id']  = (int)$generalInfo['weapon_id'];
            $generalInfoFormater['armor_id']   = (int)$generalInfo['armor_id'];
            $generalInfoFormater['horse_id']   = (int)$generalInfo['horse_id'];
            $generalInfoFormater['zuoji_id']   = (int)$generalInfo['zuoji_id'];
            $generalInfoFormater['skill_lv']   = (int)$generalInfo['skill_lv'];

            $generalInfoFormater['force_rate']        = (int)$generalInfo['force_rate'];
            $generalInfoFormater['intelligence_rate'] = (int)$generalInfo['intelligence_rate'];
            $generalInfoFormater['governing_rate']    = (int)$generalInfo['governing_rate'];
            $generalInfoFormater['charm_rate']        = (int)$generalInfo['charm_rate'];
            $generalInfoFormater['political_rate']    = (int)$generalInfo['political_rate'];
        }

        return $generalInfoFormater;
    }
    /**
     * 获取最近一条pk记录
     *
     * @param $serverId
     * @param $playerId
     * @return array
     */
    public function getLastPk($serverId, $playerId){
        $re = self::find([
            "server_id=:server_id: AND player_id=:player_id: AND status=:status:",
            'bind'=>[
                'server_id' => $serverId,
                'player_id' => $playerId,
                'status'    => self::STATUS_START,
            ],
            'order' => 'id desc',
            'limit' => 1
                   ])->toArray();
        $r = [];
        if($re) {
            $r = $re[0];
        }
        return $r;
    }
    /**
     * 获取其他服务器上的  (new Pk)->getPlayerPkBuff($targetServerId, $playerId);
     *
     * @param $targetServerId
     * @param $targetPlayerId
     *
     * @return array|string
     */
    public function getPlayerPkBuff($targetServerId, $targetPlayerId){
        $buff = [];
        $targetGameServerHost = (new ServerList)->getGameServerIpByServerId($targetServerId);
        if ($targetGameServerHost) {
            $url          = $targetGameServerHost . '/api/getPlayerPkBuff';
            $field        = ['player_id' => iEncrypt($targetPlayerId, 'PlayerPkBuff')];
            $buff = curlPost($url, $field);
            $buff = iDecrypt($buff);
        }
        return $buff;
    }
}