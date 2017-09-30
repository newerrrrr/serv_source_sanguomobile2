<?php
/**
 * 通用需求
 */
class CommonController extends ControllerBase{
    /**
     * 合并请求
     * common/combo
     * postData: {"combo":[{"url":..."field":...},{...}]}
     */
    public function comboAction(){
        $player = $this->currentPlayer;
        $uuid = $player['uuid'];
        $postData = getPost();
//        $postData['combo'] = [
//            ['url'=>'King/getInfo', 'field'=>['a'=>['A'=>'AAA']]],
//            ['url'=>'Lottery/checkPlayerLotteryInfo'],
//            ['url'=>'data/index', 'field'=>['name'=>['Player', 'PlayerInfo']]]
//        ];
        //debug($postData, 1);
        $nodes = $postData['combo'];
        unset($postData['combo']);
        if(QA) {
            $postData['uuid'] = $uuid;
            $postData['hashCode'] = hashMethod($uuid);
        }
        $data = comboRequest($nodes, $postData, $uuid);
        if(empty($data)) {
            $errCode = 10557;//网络不稳定
            echo $this->data->sendErr($errCode);
        } else {
            echo encodeResponseData(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        exit;
    }

    /**
     * 前端同步服务器时间的请求
     *
     * ```php
     * url: common/ntpdate
     * return: 1446795169 (时间戳)
     * ```
     * @return int ntp date
     */
    public function ntpdateAction(){
        $Z = intval(date('Z'));
        echo $this->data->send(['Time'=>time(), 'Time_Zone'=>$Z]);
        exit;
    }
    /**
     * 获取公告
     *
     * ```php
     * url: common/getAllNotice
     * return: [...]
     * ```
     */
    public function getAllNoticeAction(){
        $Notice = new Notice;
        $data = $Notice->getAll();
        echo $this->data->send($data);
        exit;
    }
    /**
     * 获取验证信息
     *
     * 使用方法如下
     *
     * ```php
     * url: common/getValidCode
     * return: ["valid_code":3333_saf242]
     * ```
     */
    public function getValidCodeAction(){
        $playerId = $this->getCurrentPlayerId();
        $Player    = new Player;
        $validCode = $playerId.'_'.uniqid();
        $Player->alter($playerId, ['valid_code'=>q($validCode)]);
        $data      = ['valid_code'=>$validCode];
        echo $this->data->send($data);
        exit;
    }
    /**
     * short for checkPlayer validation
     */
    private function judgePost($postData, $pi, $field){
        return ( isset($postData[$field]) && $postData[$field]!=$pi[$field] );
    }
    /**
     * 检测玩家是否存在
     *
     * ```php
     * url: common/checkPlayer
     * postData: ["valid_code":32342_asdf234]
     * return: {"code":0,"data":{"checkPlayer":1},"basic":[]}
     * ```
     */
    public function checkPlayerAction(){
        //到这里，说明玩家是存在的
        $playerId = $this->getCurrentPlayerId();
        //设备信息检测
        $postData   = getPost();
        $PlayerInfo = new PlayerInfo;
        $Player     = new Player;
        $pi         = $PlayerInfo->getByPlayerId($playerId);
        $player     = $Player->getByPlayerId($playerId);
        $serverId   = $player['server_id'];
        //武斗中途退出判断，直接判输
        $Pk = new Pk;
        $lastPk = $Pk->getLastPk($serverId, $playerId);
        if(!empty($lastPk)) {
            $pkCtrl                             = new PkController;
            $pkCtrl->currentPlayer              = $player;
            $pkCtrl->innerCallFlag              = true;
            $pkCtrl->initFlag                   = false;
            $innerPost['pk_id']                 = intval($lastPk['id']);
            $innerPost['win_player_id']         = intval($lastPk['target_player_id']);
            $innerPost['pk_result']             = '';
            $innerPost['self_general_result']   = [
                "general_1_is_win" => 0,
                "general_2_is_win" => 0,
                "general_3_is_win" => 0];
            $innerPost['target_general_result'] = [
                "general_1_is_win" => 1,
                "general_2_is_win" => 1,
                "general_3_is_win" => 1];
            $pkCtrl->passedArgs['postData']     = $innerPost;
            $pkCtrl->pkResultAction();
            $pkCtrl->innerCallFlag = false;
            $pkCtrl->initFlag      = true;
            $pkCtrl->passedArgs    = [];
        }
        //登录相关
        if(!$this->userCodeLoginFlag) {
            if(isset($postData['valid_code']) && $postData['valid_code']==$player['valid_code']) {//验证通过后，登录之
                $Player->alter($playerId, ['valid_code'=>q("Login")]);
            } elseif(isset($postData['valid_code']) && $postData['valid_code']!='new_new_new') {
                $errCode = 9999;//'该帐号在其他设备上登录';
                echo $this->data->sendErr($errCode);
                exit;
            }
            //记录最后登录服server_id
            global $config;
            (new PlayerLastServer)->saveLast($player['uuid'], $config->server_id);//最后一次登录服务器时间

            $updateData = [];
            if($this->judgePost($postData, $pi, 'login_channel')) {
                $updateData['login_channel'] = $postData['login_channel'];
            }
            if($this->judgePost($postData, $pi, 'download_channel')) {
                $updateData['download_channel'] = $postData['download_channel'];
            }
            if($this->judgePost($postData, $pi, 'pay_channel')) {
                $updateData['pay_channel'] = $postData['pay_channel'];
            }
            if($this->judgePost($postData, $pi, 'platform')) {
                $updateData['platform'] = $postData['platform'];
            }
            if($this->judgePost($postData, $pi, 'device_mode')) {
                $updateData['device_mode'] = $postData['device_mode'];
            }
            if($this->judgePost($postData, $pi, 'system_version')) {
                $updateData['system_version'] = $postData['system_version'];
            }
            if($this->judgePost($postData, $pi, 'af_uid')) {
                $updateData['af_uid'] = $postData['af_uid'];
            }
            if($this->judgePost($postData, $pi, 'af_media_source')) {
                $updateData['af_media_source'] = $postData['af_media_source'];
            }
            
            if(isset($postData['lang']) && $postData['lang']!=$player['lang']) {//语言
               $Player->alter($playerId, ['lang'=>q($postData['lang'])]);
            }

            $updateData['login_hashcode'] = loginHashMethod($playerId);//单设备登录

            $updateData['login_ip'] = (new Phalcon\Http\Request)->getClientAddress();//客户端地址
                
            $PlayerInfo->alter($playerId, $updateData);

            echo $this->data->sendRaw(['checkPlayer'=>1, 'login_hashcode'=>$updateData['login_hashcode']]);
            exit;
        } else {
            echo $this->data->sendRaw(['checkPlayer'=>1, 'login_hashcode'=>$pi['login_hashcode']]);
            exit;
        }
    }

    /**
     * 最后一条世界聊天消息
     * ```php
     *  common/lastWorldChatMsg
     * ```
     */
    public function lastWorldChatMsgAction(){
        $cache = Cache::db(CACHEDB_CHAT);
        $data = $cache->lRange('WorldChat', -1, -1);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 合并消息
     *
     * ```
     *  common/viewAllWorldMsg
     *  common/viewAllGuildMsg
     *  data/index [ChatBlackList]
     * ```
     *  common/comboChat
     *  postData:{}
     *  return:{[World],[Guild],[ChatBlackList]}
     */
    public function comboChatAction(){
        $playerId = $this->getCurrentPlayerId();
        $player   = $this->getCurrentPlayer();
        $guildId  = $player['guild_id'];
        $campId   = $player['camp_id'];

        $ChatUtil = new ChatUtil;

        $cityBattleMsg = [];
        $campMsg       = [];

        $worldMsg = $ChatUtil->getAllWorldMsg();
        if(!$guildId) {
            $guildMsg = [];
            $guildCrossMsg = [];
        } else {
            $guildMsg = $ChatUtil->getAllGuildMsg($guildId);
            $guildCrossMsg = $ChatUtil->getAllGuildCrossMsg($guildId);
        }
        if($campId>0) {
            do {
                $roundId = (new CityBattleRound)->getCurrentRound();
                if(!$roundId) break;//round not exists
                $battleId = (new CityBattlePlayer)->getCurrentBattleId($playerId);
                if(!$battleId) break;//not join battle or not exists round
                $cityBattleMsg = $ChatUtil->getCityBattleMsg($roundId, $battleId, $campId);
            } while(false);
            $campMsg = $ChatUtil->getCampMsg($campId);
        }
        $data['World']         = $worldMsg;
        $data['Guild']         = $guildMsg;
        $data['GuildCross']    = $guildCrossMsg;
        $data['CityBattle']    = $cityBattleMsg;
        $data['Camp']          = $campMsg;
        $data['ChatBlackList'] = (new ChatBlackList)->getByPlayerId($playerId, true);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 查看世界聊天信息
     *
     * ```php
     * common/viewAllWorldMsg
     * postData:{}
     * return:{}
     * ```
     */
    public function viewAllWorldMsgAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $worldMsg = (new ChatUtil)->getAllWorldMsg();
        echo $this->data->send($worldMsg);
        exit;
    }
    /**
     * 查看联盟聊天信息
     *
     * ```php
     * common/viewAllGuildMsg
     * postData:{}
     * return:{}
     * ```
     */
    public function viewAllGuildMsgAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $guildId = $player['guild_id'];
        if(!$guildId) {
            $errCode = 10305;//查看联盟聊天-玩家没有入盟
            goto sendErr;
        }
        $guildMsg = (new ChatUtil)->getAllGuildMsg($guildId);
        echo $this->data->send($guildMsg);
        exit;
        sendErr: {
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 查看阵营聊天信息
     *
     * ```php
     * common/viewAllCampMsg
     * postData:{}
     * return:{}
     * ```
     */
    public function viewAllCampMsgAction(){
        $player = $this->getCurrentPlayer();
        $campId = $player['camp_id'];
        if(!$campId) {
            $errCode = 10769;//查看阵营聊天-玩家没有阵营
            goto sendErr;
        }
        if($campId>0) {
            $campMsg = (new ChatUtil)->getCampMsg($campId);
        } else {
            $campMsg = [];
        }
        echo $this->data->send($campMsg);
        exit;
        sendErr: {
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 添加一个黑名单
     *
     * 使用方法如下
     * ```php
     * common/addChatBlack
     * postData:{"black_player_id":222}
     * returnData:{}
     * ```
     */
    public function addChatBlackAction(){
        $playerId      = $this->getCurrentPlayerId();
        $ChatBlackList = new ChatBlackList;
        $postData      = getPost();
        $blackPlayerId = $postData['black_player_id'];
        $flag          = $ChatBlackList->addNew($playerId, $blackPlayerId);
        if($flag) {
            $data['ChatBlackList'] = $ChatBlackList->getByPlayerId($playerId, true);
            echo $this->data->send($data);
            exit;
        } else {
            $errCode = 10306;//添加聊天黑名单:已经加过该玩家
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 删除一个黑名单
     *
     * 使用方法如下
     * ```php
     * common/removeChatBlack
     * postData: {"black_player_ids":[11,22]}
     * returnData:{}
     * ```
     */
    public function removeChatBlackAction(){
        $playerId       = $this->getCurrentPlayerId();
        $ChatBlackList  = new ChatBlackList;
        $postData       = getPost();
        $blackPlayerIds = $postData['black_player_ids'];
        $flag           = $ChatBlackList->removeBlack($playerId, $blackPlayerIds);
        if($flag) {
            $data['ChatBlackList'] = $ChatBlackList->getByPlayerId($playerId, true);
            echo $this->data->send($data);
            exit;
        } else {
            $errCode = 10307;//删除聊天黑名单:不存在该玩家
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 设置在线离线
     *
     * 使用方法如下
     * ```php
     * common/setOnlineTimestamp
     * postData: {}
     * returnData:{}
     * ```
     */
    public function setOnlineTimestampAction(){
        $playerId = $this->getCurrentPlayerId();
        $c = Cache::db('server');
        $re = $c->hGet(REDIS_KEY_ONLINE, $playerId);
        $now = time();
        $c->hSet(REDIS_KEY_ONLINE, $playerId, $now);
        echo $this->data->send();
        exit;
    }
}

