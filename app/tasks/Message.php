<?php
/**
 * 消息类
 */
class Message {
    public $data      = [];//需要处理的[Type, Data]
    public $returnMsg = [];//返回值，供processMsg返回

    /**
     * 处理聊天消息方法
     * @param  swoole_server $serv
     * @param  int        $fd
     * @param  int        $from_id
     * @param  array      $data
     * @return string
     */
    public function processMsg(swoole_server $serv, $fd, $from_id, $data){
        $content     = json_decode($data['content'], true);
        //初始化B
        $this->data[$fd] = [];
        $this->data[$fd]['contentType'] = $content['Type'];
        $this->data[$fd]['contentData'] = $content['Data'];
        //初始化E
        $returnMsg = '';
        switch ($content['Type']) {
            case 'invite_guild'://邀请加入联盟
                $this->inviteGuildMsg($serv, $fd);
                break;
            case 'round_message'://广播:走马灯
                $this->roundMessageMsg($serv, $fd);
                break;
            case 'appoint_king'://广播: 任命国王
                $this->appointKingMsg($serv, $fd);
                break;
            case 'world_chat'://广播：国家聊天
                $this->worldChatMsg($serv, $fd);
                break;
            case 'guild_chat'://联盟群聊
                $this->guildChatMsg($serv, $fd);
                break;
            case 'battle_fight'://跨服联盟聊天 含语音
                $this->guildCrossChatMsg($serv, $fd);
                break;
            case 'camp_chat'://跨服 阵营 聊天 #这里只负责存
                $this->guildCampChatMsg($serv, $fd, 0);
                break;
            case 'camp_chat_send'://跨服 阵营 聊天 #这里用脚本负责发
				$this->guildCampChatMsg($serv, $fd, 1);
				break;
            case 'city_battle_chat'://跨服 城战 聊天 #这里只负责存
                $this->cityBattleChatMsg($serv, $fd, 0);
                break;
            case 'city_battle_chat_send'://跨服 城战 聊天 #这里用脚本负责发
				$this->cityBattleChatMsg($serv, $fd, 1);
				break;
            case 'apply_guild'://申请联盟
                $this->applyGuildMsg($serv, $fd);
                break;
            case 'guild_help_add'://申请帮助时
                $this->guildHelpAddMsg($serv, $fd);
                break;
            case 'guild_help'://发送联盟帮助
                $this->guildHelpMsg($serv, $fd);
                break;
            case 'guild_accept'://发送同意入盟请求
                $this->guildAcceptMsg($serv, $fd);
                break;
			case 'citybattle_queue':
            case 'queue'://队列通知
                $this->queueMsg($serv, $fd);
                break;
            case 'mail'://邮件通知
                $this->mailMsg($serv, $fd);
				break;
			case 'pay_callback'://充值成功
                $this->payCallbackMsg($serv, $fd);
				break;
			case 'player_target'://新手任务通知
                $this->playerTargetMsg($serv, $fd);
				break;
			case 'fight'://战斗通知
            case 'attacked'://将要被攻打
			case 'city_attacked'://城被打
			case 'cross_city_attacked'://跨服战 城被攻击
			case 'citybattle_city_attacked'://跨服战 城被攻击
			case 'spyed'://被侦查
			case 'cancelattacked'://撤回被打
			case 'cross_cancelattacked'://撤回被打
			case 'citybattle_cancelattacked':
			case 'finishattacked'://完成被打
			case 'cross_finishattacked'://跨服站完成被打
			case 'citybattle_finishattacked'://城战完成被打
			case 'kingpoint'://王战积分发生变化
			case 'guild_science'://联盟科技升级
			case 'gather'://集合通知
            case 'send_army'://援军通知
            case 'cross_pk_result'://pk 跨服通知
			case 'cross'://跨服战
			case 'citybattle':
                $this->commonMsg($serv, $fd);
                break;
			case 'item'://道具通知
                $this->itemMsg($serv, $fd);
				break;
            case 'change_player_camp':
                $this->changePlayerCamp($fd);
                break;
            case 'all_conn_info'://获取所有连接fd信息
                $this->allConnInfoMsg($serv, $fd);
                break;
            default:
                break;
        }
        if(isset($this->returnMsg[$fd])) {
            $returnMsg = $this->returnMsg[$fd];
//            log4server(fdPrefix($fd)."返回客户端消息：" . $returnMsg);
            unset($this->returnMsg[$fd]);
        }
        if(isset($this->data[$fd])) {
            unset($this->data[$fd]);
        }
        return $returnMsg;
    }

    /**
     * 更改玩家阵营
     *
     * @param int $fd
     */
    public function changePlayerCamp($fd){
        $playerId = $this->data[$fd]['contentData']['player_id'];
        $campId = $this->data[$fd]['contentData']['camp_id'];
        $fd = ServSession::getFdByPlayerId($playerId);
        if($fd) {
            ServSession::setFd($playerId, $fd, $campId);
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  通用消息
     */
    public function commonMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        $contentType = $this->data[$fd]['contentType'];
        if(is_array($contentData['playerId']))
            $playerIds = $contentData['playerId'];
        else
            $playerIds = [$contentData['playerId']];
        foreach($playerIds as $_id){
            $toFd = ServSession::getFdByPlayerId($_id);
            if(!$toFd)
                continue;
            $msg = ['type'=>$contentType];
            if($contentType=='gather') {
                $msg['Data'] = $contentData;
            }elseif($contentType=='cross' || $contentType=='citybattle'){
				$msg['Data'] = $contentData;
				unset($msg['Data']['playerId']);
			}
            $retData = packData($msg, StaticData::$msgIds['DataResponse']);
            $serv->send($toFd, $retData);
        }
    }
    /**
     * 获取所有连接fd信息
     *
     * @param $serv
     * @param $fd
     */
    public function allConnInfoMsg($serv, $fd) {
        $conn = [];
//        $redis = Cache::db('server');

        foreach(ServSession::$table as $v) {
            $conn[] = $v;
        }
        $this->returnMsg[$fd] = json_encode($conn);
//        $redis->set('ServSession', $conn);
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     * 邀请加入联盟
     */
    public function inviteGuildMsg(swoole_server $serv, $fd) {
        $returnContent         = $this->data[$fd]['contentData'];
        $returnContent['type'] = $this->data[$fd]['contentType'];
        $packReturnContent     = packData($returnContent);
        $toFd                  = ServSession::getFdByPlayerId($this->data[$fd]['contentData']['to_player_id']);
        if ($toFd) {
            $serv->send($toFd, $packReturnContent);
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     * 广播:走马灯
     */
    public function roundMessageMsg(swoole_server $serv, $fd){
        $allConn = ServSession::getAllFd();
        $msg     = $this->data[$fd]['contentData']['content'];

        if ($msg) {
            $msg['type'] = $this->data[$fd]['contentType'];
            $packMsg     = packData($msg);
            foreach ($allConn as $conn) {
                $serv->send($conn, $packMsg);
            }
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  广播: 任命国王
     */
    public function appointKingMsg(swoole_server $serv, $fd){
        $allConn      = ServSession::getAllFd();
        $kingNick     = $this->data[$fd]['contentData']['king_nick'];
        $kingPlayerId = $this->data[$fd]['contentData']['king_player_id'];

        if ($kingNick) {
            $msg['king_nick']      = $kingNick;
            $msg['king_player_id'] = $kingPlayerId;
            $msg['type']           = $this->data[$fd]['contentType'];
            $packMsg               = packData($msg);
            foreach ($allConn as $conn) {
                $serv->send($conn, $packMsg);
            }
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  同意入盟消息
     */
    public function guildAcceptMsg(swoole_server $serv, $fd) {
        $returnContent = $this->data[$fd]['contentData'];
        $toPlayerId    = $returnContent['to_player_id'];
        unset($returnContent['to_player_id']);
        $returnContent['type'] = $this->data[$fd]['contentType'];
        $packReturnContent     = packData($returnContent);
        $toFd                  = ServSession::getFdByPlayerId($toPlayerId);
        if ($toFd) {
            $serv->send($toFd, $packReturnContent);
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  世界聊天
     */
    public function worldChatMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        $allConn     = ServSession::getAllFd();
        $playerId    = $contentData['player_id'];
        $msg         = $contentData['content'];

        $pushData = [];
        if (isset($contentData['pushData'])) {
            $pushData = $contentData['pushData'];
        }
        $WorldChat     = new ChatUtil;
        $returnContent = $WorldChat->saveWorldMsg($playerId, $msg, $pushData);
        log4server(fdPrefix($fd)."世界聊天信息=".arr2str($returnContent));
        if ($returnContent == -1 && empty($pushData)) {//禁言
            $arr             = ['flag' => 'banned'];
            $this->returnMsg[$fd] = json_encode($arr);
        } elseif ($returnContent == -2 && empty($pushData)) {//等级不足
            $arr             = ['flag' => 'low_level', 'level' => $WorldChat->transData['level']];
            $this->returnMsg[$fd] = json_encode($arr);
        } elseif ($returnContent) {
            $returnContent['type'] = $this->data[$fd]['contentType'];
            $packReturnContent     = packData($returnContent);
            foreach ($allConn as $conn) {
                $serv->send($conn, $packReturnContent);
            }
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  联盟聊天
     */
    public function guildChatMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        $playerId    = $contentData['player_id'];
        $msg         = $contentData['content'];

        $pushData = [];
        if (isset($contentData['userData'])) {
            $pushData['type']     = 5;
            $pushData['userData'] = $contentData['userData'];
        } elseif (isset($contentData['pushData'])) {
            $pushData = $contentData['pushData'];
        }
        $player  = (new Player)->getByPlayerId($playerId);
        $guildId = $player['guild_id'];
        if ($guildId) {
            $GuildChat     = new ChatUtil;
            $returnContent = $GuildChat->saveGuildMsg($playerId, $msg, $pushData);
            log4server(fdPrefix($fd)."联盟聊天信息=".arr2str($returnContent));
            if ($returnContent) {
                $returnContent['type'] = $this->data[$fd]['contentType'];
                $packReturnContent     = packData($returnContent);
                $allMember             = (new PlayerGuild)->getAllGuildMember($guildId, false);
                foreach ($allMember as $k => $v) {
                    $pid  = $v['player_id'];
                    $toFd = ServSession::getFdByPlayerId($pid);
                    if ($toFd)
                        $serv->send($toFd, $packReturnContent);
                }
            }
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  联盟聊天
     */
    public function guildCrossChatMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        $playerId    = $contentData['player_id'];
        unset($contentData['player_id']);
        $paraData         = $contentData;

        $player  = (new Player)->getByPlayerId($playerId);
        $guildId = $player['guild_id'];
        log4server(fdPrefix($fd)."guild_id={$guildId} 联盟战跨服聊天信息");
        if ($guildId) {
            $currentRoundId = (new CrossRound)->getCurrentRoundId();
            if(!$currentRoundId) {
                $arr             = ['flag' => 'no_cross_battle'];
                $this->returnMsg[$fd] = json_encode($arr);
            }
            $allJoinedMember = (new PlayerGuild)->getCrossJoinedMember($guildId);
            $inFlag = false;//是否参加
            foreach($allJoinedMember as $member) {
                if($member['player_id']==$playerId) {
                    $inFlag = true;
                    break;
                }
            }
            if(!$inFlag) {
                $arr             = ['flag' => 'not_join_cross_battle'];
                $this->returnMsg[$fd] = json_encode($arr);
                return;
            }

            $GuildChat     = new ChatUtil;
//            log4server($paraData);
            $returnContent = $GuildChat->saveGuildCrossMsg($playerId, $paraData);
            log4server(fdPrefix($fd)."联盟战跨服聊天信息=".arr2str($returnContent));
            if ($returnContent) {
                $returnContent['type'] = $this->data[$fd]['contentType'];
                $packReturnContent     = packData($returnContent);
//                log4server("回应fd={$this->fd}的数据:".$packReturnContent);
//                $serv->send($this->fd, $packReturnContent);

                foreach ($allJoinedMember as $k => $v) {
                    $pid = $v['player_id'];
                    if(($pid!=$playerId || !isset($paraData['paraData']))) {
                        $toFd = ServSession::getFdByPlayerId($pid);
                        if ($toFd)
                            $serv->send($toFd, $packReturnContent);
                    }
                }

            }
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  阵营聊天
     *  @param   $saveOrsendFlag 0: 存 1：发
     */
    public function guildCampChatMsg(swoole_server $serv, $fd, $saveOrsendFlag=0) {
        if($saveOrsendFlag==0) {
            $contentData   = $this->data[$fd]['contentData'];
            $playerId      = $contentData['player_id'];
            $msg           = $contentData['content'];
            $chat          = new ChatUtil;
            $returnContent = $chat->saveCampMsg($playerId, $msg);
            log4server(fdPrefix($fd)."---------阵营---发送---聊天信息=".arr2str($returnContent));
        } elseif($saveOrsendFlag==1) {
            $campMsg         = $this->data[$fd]['contentData']['content'];
            $campMsg['type'] = ChatUtil::TYPE_CAMP;
            $packReturnContent = packData($campMsg);
            $allCampConn       = ServSession::getAllFd($this->data[$fd]['contentData']['camp_id']);
            log4server(fdPrefix($fd)."---------阵营---推送给".arr2str($allCampConn)."---信息=".arr2str($campMsg));
            foreach ($allCampConn as $conn) {
                $serv->send($conn, $packReturnContent);
            }
            
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  城战聊天
     *  @param   $saveOrsendFlag 0: 存 1：发
     */
    public function cityBattleChatMsg(swoole_server $serv, $fd, $saveOrsendFlag=0) {
        if($saveOrsendFlag==0) {
            $contentData   = $this->data[$fd]['contentData'];
            $playerId      = $contentData['player_id'];
            $paraData      = $contentData;
            $chat          = new ChatUtil;
            $returnContent = $chat->saveCityBattleMsg($playerId, $paraData);
            log4server(fdPrefix($fd)."---------城战---发送---聊天信息=".arr2str($returnContent));
        } elseif($saveOrsendFlag==1) {
            $cityBattleMsg = $this->data[$fd]['contentData']['content'];
            $cityBattleMsg['type'] = ChatUtil::TYPE_CITY_BATTLE;
            $packReturnContent = packData($cityBattleMsg);
            $allCampConn       = ServSession::getAllCamp($this->data[$fd]['contentData']['camp_id']);
            $battlePlayerId = $this->data[$fd]['contentData']['battle_player_id'];
            log4server(fdPrefix($fd)."---------城战---推送给".arr2str($allCampConn)." in " . arr2str($battlePlayerId) ."---信息=".arr2str($cityBattleMsg));
            foreach ($allCampConn as $conn) {
                if(in_array($conn['player_id'], $battlePlayerId)) {
                    $serv->send($conn['fd'], $packReturnContent);
                }
            }

        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     * 申请入盟请求时，发送给管理员的长连接消息
     */
    public function applyGuildMsg(swoole_server $serv, $fd){
        $guildId       = $this->data[$fd]['contentData']['guild_id'];
        $returnContent = $this->data[$fd]['contentData'];
        unset($returnContent['guild_id']);
        $allMember             = (new PlayerGuild)->getAllGuildMember($guildId);
        $returnContent['type'] = $this->data[$fd]['contentType'];
        $packReturnContent     = packData($returnContent);
        log4server('---------申请联盟信息');
        foreach ($allMember as $k => $v) {
            if ($v['rank'] >= PlayerGuild::RANK_R4) {
                $pid = $v['player_id'];
                $toFd = ServSession::getFdByPlayerId($pid);
                if ($toFd) {
                    $serv->send($toFd, $packReturnContent);
                }
            }
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     * 申请帮助时请求盟里所有人
     */
    public function guildHelpAddMsg(swoole_server $serv, $fd){
        $guildId               = $this->data[$fd]['contentData']['guild_id'];
        $playerId              = $this->data[$fd]['contentData']['player_id'];
        $allMember             = (new PlayerGuild)->getAllGuildMember($guildId);
        $returnContent         = [];
        $returnContent['type'] = $this->data[$fd]['contentType'];
        $packReturnContent     = packData($returnContent);
        log4server('---------申请帮助时信息');
        foreach ($allMember as $k => $v) {
            $pid = $v['player_id'];
            if ($pid == $playerId) continue;
            $toFd = ServSession::getFdByPlayerId($pid);
            if ($toFd) {
                $serv->send($toFd, $packReturnContent);
            }
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     * 发送联盟帮助
     */
    public function guildHelpMsg(swoole_server $serv, $fd) {
        $returnContent         = $this->data[$fd]['contentData'];
        $returnContent['type'] = $this->data[$fd]['contentType'];
        $packReturnContent     = packData($returnContent);
        $toFd = ServSession::getFdByPlayerId($this->data[$fd]['contentData']['to_player_id']);
        if($toFd) {
            $serv->send($toFd, $packReturnContent);
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     * 新手任务通知
     */
    public function playerTargetMsg(swoole_server $serv, $fd) {
        $toFd = ServSession::getFdByPlayerId($this->data[$fd]['contentData']['playerId']);
        if(!$toFd)
            return;
        $msg = ['type'=>$this->data[$fd]['contentType']];
        $retData = packData($msg, StaticData::$msgIds['DataResponse']);
        $serv->send($toFd, $retData);
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  队列
     */
    public function queueMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        if(is_array($contentData['playerId']))
            $playerIds = $contentData['playerId'];
        else
            $playerIds = [$contentData['playerId']];
        foreach($playerIds as $_id){
            $toFd = ServSession::getFdByPlayerId($_id);
            if(!$toFd)
                continue;
            $msg = ['type'=>$this->data[$fd]['contentType'], 'msg'=>$contentData['msg']];
            $retData = packData($msg, StaticData::$msgIds['DataResponse']);
            $serv->send($toFd, $retData);
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  道具通知
     */
    public function itemMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        if(is_array($contentData['playerId']))
            $playerIds = $contentData['playerId'];
        else
            $playerIds = [$contentData['playerId']];
        foreach($playerIds as $_id){
            $toFd = ServSession::getFdByPlayerId($_id);
            if(!$toFd)
                continue;
            $msg = ['type'=>$this->data[$fd]['contentType']];
            $retData = packData($msg, StaticData::$msgIds['DataResponse']);
            $serv->send($toFd, $retData);
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  邮件
     */
    public function mailMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        foreach($contentData as $_playerId => $_d){
            $toFd = ServSession::getFdByPlayerId($_playerId);
            if(!$toFd)
                continue;
            $msg = ['type'=>$this->data[$fd]['contentType'], 'mail_id'=>$_d['mail_id'], 'cata_type'=>$_d['cata_type'], 'mail_type'=>$_d['type'], 'connect_id'=>$_d['connect_id']];
            $retData = packData($msg, StaticData::$msgIds['DataResponse']);
            $serv->send($toFd, $retData);
        }
    }
    /**
     * @param swoole_server $serv
     * @param int $fd
     *
     *  充值成功
     */
    public function payCallbackMsg(swoole_server $serv, $fd) {
        $contentData = $this->data[$fd]['contentData'];
        $toFd = ServSession::getFdByPlayerId($contentData['playerId']);
        if(!$toFd)
            return;
        $msg = ['type'=>$this->data[$fd]['contentType'], 'goods_type'=>$contentData['goods_type'], 'pricing'=>$contentData['pricing']];
        $retData = packData($msg, StaticData::$msgIds['DataResponse']);
        $serv->send($toFd, $retData);
    }
}
