<?php
/**
 * 数据回传类
 */
class ChatUtil {
    const MAX_SAVE_WORLD_LENGTH = 3000;//世界聊天最多存条目
    const MAX_DISPLAY_LENGTH    = 100;//消息里传值最长
    const TYPE_WORLD            = 'world_chat';//世界聊天
    const TYPE_GUILD            = 'guild_chat';//公会聊天
    const TYPE_GUILD_CROSS      = 'battle_fight';//公会聊天
    const TYPE_CAMP             = 'camp_chat';//阵营聊天
    const TYPE_CITY_BATTLE      = 'city_battle_chat';//城战聊天
    const CHAT_LEVEL_LIMIT      = 10;//聊天等级限制

    public $transData = [];//传输相关值

    /**
 * 获取所有世界聊天信息
 * @param $campId
 * @param $filterFlag
 * @return array
 */
    public function getCampMsg($campId, $filterFlag=false){
        $cacheChat = Cache::db('chat', 'CityBattle');
        $serverCache = Cache::db('cache');
        $campKey = 'CampChat-'.$campId;
        $size = $cacheChat->lsize($campKey);
        $start = 0;
        $max = self::MAX_DISPLAY_LENGTH;
        if($size>$max) {
            $start = $size - $max;
        }
        $allMsg = $cacheChat->lRange($campKey, $start, $size);//self::MAX_DISPLAY_LENGTH);
        if(!$allMsg) {
            $allMsg = [];
        }
        //用于过滤信息用
        if($filterFlag) {
            $_allMsg = [];
            $lastMsgTime = 0;
            $campLastMsgTime = $serverCache->get('Camp_last_msg_time_campId='.$campId);
            if(empty($campLastMsgTime)) {
                $campLastMsgTime = 0;
            }
        }
        foreach($allMsg as $k=>$v) {
            if(!$v) unset($allMsg[$k]);
            if(empty($v['content'])) continue;
            $allMsg[$k]['content'] = $v['content'];//deEmoji($v['content']);
            if($filterFlag && $v['time'] > $campLastMsgTime) {
                $lastMsgTime = $v['time'];
                $_allMsg[] = $v;
            }
        }
        if($filterFlag) {
            $allMsg = $_allMsg;
            if(!empty($_allMsg)) {
                $serverCache->set('Camp_last_msg_time_campId='.$campId, $lastMsgTime);
                $serverCache->set('Human-Camp_last_msg_time_campId='.$campId, date('Y-m-d H:i:s', $lastMsgTime));
            }
        }
        return $allMsg;
    }
    /**
     * 获取所有世界聊天信息
     * @param $roundId
     * @param $battleId
     * @param $campId
     * @param $filterFlag
     *
     * @return array
     */
    public function getCityBattleMsg($roundId, $battleId, $campId, $filterFlag=false){
        $cacheChat = Cache::db('chat', 'CityBattle');
        $serverCache = Cache::db('cache');
        $cityBattleKey = 'CityBattleChat-roundId='.$roundId.'-battleId='.$battleId.'-campId='.$campId;
        $size = $cacheChat->lsize($cityBattleKey);
        $start = 0;
        $max = self::MAX_DISPLAY_LENGTH;
        if($size>$max) {
            $start = $size - $max;
        }
        $allMsg = $cacheChat->lRange($cityBattleKey, $start, $size);//self::MAX_DISPLAY_LENGTH);
        if(!$allMsg) {
            $allMsg = [];
        }
        //用于过滤信息用
        if($filterFlag) {
            $_allMsg = [];
            $lastMsgTime = 0;
            $cityBattleLastMsgTime = $serverCache->get("CityBattle_last_msg_time_{$roundId}:{$battleId}:{$campId}");
            if(empty($cityBattleLastMsgTime)) {
                $cityBattleLastMsgTime = 0;
            }
        }
        foreach($allMsg as $k=>$v) {
            unset($allMsg[$k]['memo']);
            if(!$v) unset($allMsg[$k]);
            if($filterFlag && $v['time'] > $cityBattleLastMsgTime) {
                $lastMsgTime = $v['time'];
                $_allMsg[] = $v;
            }
        }
        if($filterFlag) {
            $allMsg = $_allMsg;
            if(!empty($_allMsg)) {
                $serverCache->set("CityBattle_last_msg_time_{$roundId}:{$battleId}:{$campId}", $lastMsgTime);
                $serverCache->set("Humman-CityBattle_last_msg_time_{$roundId}:{$battleId}:{$campId}", date('Y-m-d H:i:s', $lastMsgTime));
            }
        }
        return $allMsg;
    }
    /**
     * 获取所有世界聊天信息
     * @return array 
     */
    public function getAllWorldMsg($systemFlag=false){
        $cacheChat = Cache::db(CACHEDB_CHAT);
        $worldKey = 'WorldChat';
        $size = $cacheChat->lsize($worldKey);
        $start = 0;
        $max = self::MAX_DISPLAY_LENGTH;
        if($systemFlag) {
            $max = self::MAX_SAVE_WORLD_LENGTH;
        }
        if($size>$max) {
            $start = $size - $max;
        }
        $allMsg = $cacheChat->lRange($worldKey, $start, $size);//self::MAX_DISPLAY_LENGTH);
        if(!$allMsg) {
            $allMsg = [];
        }
        foreach($allMsg as $k=>$v) {
            if(!$v) unset($allMsg[$k]);
            $allMsg[$k]['content'] = json_decode($v['content'], true);//deEmoji($v['content']);
        }
        return $allMsg;
    }
    /**
     * 获取所有世界聊天信息
     * @return array 
     */
    public function getAllGuildMsg($guildId){
        $cacheChat = Cache::db(CACHEDB_CHAT);
        $guildKey = 'GuildChat-'.$guildId;
        $allMsg = $cacheChat->lRange($guildKey, 0, self::MAX_DISPLAY_LENGTH);
        if(!$allMsg) {
            $allMsg = [];
        }
        foreach($allMsg as $k=>$v) {
            if(!$v) unset($allMsg[$k]);
            $allMsg[$k]['content'] = json_decode($v['content'], true);//deEmoji($v['content']);
        }
        return $allMsg;
    }
    /**
     * 获取所有联盟战聊天信息
     *
     * @param $guildId
     * @return array
     */
    public function getAllGuildCrossMsg($guildId){
        $cacheChat = Cache::db(CACHEDB_CHAT);
        $guildKey = 'GuildCrossChat-'.$guildId;
        $allMsg = $cacheChat->lRange($guildKey, 0, self::MAX_DISPLAY_LENGTH);
        if(!$allMsg) {
            $allMsg = [];
        }
        foreach($allMsg as $k=>$v) {
            if(!$v) unset($allMsg[$k]);
            $allMsg[$k]['content'] = json_decode($v['content'], true);//deEmoji($v['content']);
        }
        return $allMsg;
    }
    /**
     * 世界聊天
     * @param  int $playerId 
     * @param  string $msg    
     * @param  array [type]
     *   世界聊天
     *   type
     *   1 击杀BOSS
     *   2 招募武将
     *   3 武器进阶
     *   4 皇陵探宝
     *
     *   11 聚宝盆-神武将碎片
     *   12 化神
     * @return array
     */
    public function saveWorldMsg($playerId, $msg, $pushData=[]){
        $Player      = new Player;
        $Guild       = new Guild;
        $player      = $Player->getByPlayerId($playerId);

        if(empty($pushData)) {//非系统消息需过滤器
            $PlayerInfo = new PlayerInfo;
            $banMsgTime = $PlayerInfo->getBanMsgTime($playerId);
            if($banMsgTime) {//禁言
                return -1;
            }

            $msg = (new SensitiveWord)->filterWord($msg);//敏感字
            $msg = strtr($msg, "\n", ' ');//换行变空格
            
            //x级以下不能发言
            if($player['level'] < self::CHAT_LEVEL_LIMIT) {
                $this->transData['level'] = self::CHAT_LEVEL_LIMIT;
                return -2;
            }
        }



        $guildId     = $player['guild_id'];
        if($guildId) {
            $guildInfo      = $Guild->getGuildInfo($guildId);
            
            $guildShortName = $guildInfo['short_name']; 
            $guildShortName = empty($guildShortName)? '': $guildShortName;
        } else {
            $guildShortName = '';
        }
        $worldKey = 'WorldChat';
        $msgData = [
            'type'             => self::TYPE_WORLD,
            'player_id'        => $playerId,
            'nick'             => $player['nick'],
            'avatar_id'        => $player['avatar_id'],
            'guild_short_name' => empty($guildShortName)? '': $guildShortName,
            'content'          => $msg, 
            'data'             => $pushData,
            'date'             => date('Y-m-d H:i:s'), 
            'time'             => time()
            ];
        $cacheChat = Cache::db(CACHEDB_CHAT);
        $msgData1 = $msgData;
        $msgData1['content'] = json_encode($msg);//enEmoji($msg);
        $cacheChat->rPush($worldKey, $msgData1);
        sizeFlag://pop多余的聊天记录
        $size = $cacheChat->lsize($worldKey);
        if($size>self::MAX_SAVE_WORLD_LENGTH) {//去掉多余的
            $cacheChat->lPop($worldKey);
            goto sizeFlag;
        }
        return $msgData;
    }
    /**
     * 联盟聊天
     *
     * @param  int $playerId
     * @param  string $msg   
     * @param  array [type]
     *    联盟聊天
     *    type
     *    5  联盟邮件
     *    10 联盟商店买东西
     *    13 帮主弹劾
     *
     *    14 放置 联盟建筑
     *    15 拆除 联盟建筑
     *    16 完成 联盟建筑
     *
     * @return array
     */
    public function saveGuildMsg($playerId, $msg, $pushData=[]){
        if(empty($pushData)) {//非系统消息需过滤器
            $msg = (new SensitiveWord)->filterWord($msg);//敏感字
            $msg = strtr($msg, "\n", ' ');//换行变空格
        }

        $Player      = new Player;
        $Guild       = new Guild;
        $PlayerGuild = new PlayerGuild;
        $player      = $Player->getByPlayerId($playerId);

        $guildId     = $player['guild_id'];
        if($guildId) {
            $playerGuild = $PlayerGuild->getByPlayerId($playerId);
            $guildInfo = $Guild->getGuildInfo($guildId);

            $guildRankName = $guildInfo['GuildRankName'][$playerGuild['rank']-1];
            if(!$guildRankName) {
                $guildRankName = '';
            }
        } else {
            return [];
        }
        $guildKey = 'GuildChat-'.$guildId;
        //case a: 聊天
        $msgData = [
            'type'            => self::TYPE_GUILD,
            'player_id'       => $playerId,
            'guild_id'        => $guildId,
            'nick'            => $player['nick'],
            'avatar_id'       => $player['avatar_id'],
            'guild_rank_name' => $guildRankName,
            'content'         => $msg, 
            'data'            => $pushData,
            'date'            => date('Y-m-d H:i:s'), 
            'time'            => time()];
        $cacheChat = Cache::db(CACHEDB_CHAT);
        $msgData1 = $msgData;
        $msgData1['content'] = json_encode($msg);//enEmoji($msg);
        $cache = $cacheChat->rPush($guildKey, $msgData1);
        sizeFlag://pop多余的聊天记录
        $size = $cacheChat->lsize($guildKey);
        if($size>self::MAX_DISPLAY_LENGTH) {//去掉多余的
            $cacheChat->lPop($guildKey);
            goto sizeFlag;
        }
        return $msgData;
    }
    /**
     * 联盟聊天
     *
     * @param  int $playerId
     * @param  string $data
     * @param  array [type]
     * @return array
     */
    public function saveGuildCrossMsg($playerId, $data, $pushData=[]){
        $msg = $data['content'];
        if(empty($pushData)&&!empty($msg)) {//非系统消息需过滤器
            $msg = (new SensitiveWord)->filterWord($msg);//敏感字
            $msg = strtr($msg, "\n", ' ');//换行变空格
        }
        if(isset($data['paraData'])) {
            $paraData = $data['paraData'];
            $paraData = $paraData;
        } else {
            $paraData = [];
        }

        $Player      = new Player;
        $Guild       = new Guild;
        $PlayerGuild = new PlayerGuild;
        $player      = $Player->getByPlayerId($playerId);

        $guildId     = $player['guild_id'];
        if($guildId) {
            $playerGuild = $PlayerGuild->getByPlayerId($playerId);
            $guildInfo = $Guild->getGuildInfo($guildId);

            $guildRankName = $guildInfo['GuildRankName'][$playerGuild['rank']-1];
            if(!$guildRankName) {
                $guildRankName = '';
            }
        } else {
            return [];
        }
        $guildKey = 'GuildCrossChat-'.$guildId;
        //case a: 聊天
        $msgData = [
            'type'            => self::TYPE_GUILD_CROSS,
            'player_id'       => $playerId,
            'guild_id'        => $guildId,
            'nick'            => $player['nick'],
            'avatar_id'       => $player['avatar_id'],
            'guild_rank_name' => $guildRankName,
            'content'         => $msg,
            'data'            => $pushData,
            'date'            => date('Y-m-d H:i:s'),
            'time'            => time()];
        if(empty($msg) && !empty($paraData)) {
            $msgData['paraData'] = $paraData;
        }
        $cacheChat = Cache::db(CACHEDB_CHAT);
        $msgData1 = $msgData;
        $msgData1['content'] = json_encode($msg);//enEmoji($msg);
        $cache = $cacheChat->rPush($guildKey, $msgData1);
        sizeFlag://pop多余的聊天记录
        $size = $cacheChat->lsize($guildKey);
        if($size>self::MAX_DISPLAY_LENGTH) {//去掉多余的
            $cacheChat->lPop($guildKey);
            goto sizeFlag;
        }
        return $msgData;
    }
    /**
     * 阵营聊天
     *
     * @param  int $playerId
     * @param  string $msg
     * @return array
     */
    public function saveCampMsg($playerId, $msg){
        if(!empty($msg)) {//非系统消息需过滤器
            $msg = (new SensitiveWord)->filterWord($msg);//敏感字
            $msg = strtr($msg, "\n", ' ');//换行变空格
        }

        $Player      = new Player;
        $Guild       = new Guild;
        $player      = $Player->getByPlayerId($playerId);

        $campId      = $player['camp_id'];
        if(!$campId) return [];//无阵营
        $guildKey = 'CampChat-'.$campId;
        $userTitle = (new CityBattleRank)->getRankPlayerId($playerId);

        $guildId     = $player['guild_id'];
        if($guildId) {
            $guildInfo      = $Guild->getGuildInfo($guildId);

            $guildShortName = $guildInfo['short_name'];
            $guildShortName = empty($guildShortName)? '': $guildShortName;
        } else {
            $guildShortName = '';
        }

        //case a: 聊天
        $msgData = [
            'server_id'        => $player['server_id'],
            'type'             => self::TYPE_CAMP,
            'player_id'        => intval($playerId),
            'guild_id'         => $guildId,
            'nick'             => $player['nick'],
            'user_title'       => $userTitle,
            'avatar_id'        => $player['avatar_id'],
            'guild_short_name' => empty($guildShortName) ? '' : $guildShortName,
            'content'          => (empty($msg)) ? '' : $msg,
            'data'             => [],
            'date'             => date('Y-m-d H:i:s'),
            'time'             => time()];
        if(empty($msg) && !empty($paraData)) {
            $msgData['paraData'] = $paraData;
        }
        $cacheChat           = Cache::db('chat', 'CityBattle');
        $msgData1            = $msgData;
        $msgData1['content'] = $msg;//enEmoji($msg);
        $cache               = $cacheChat->rPush($guildKey, $msgData1);
        sizeFlag://pop多余的聊天记录
        $size = $cacheChat->lsize($guildKey);
        if($size>self::MAX_DISPLAY_LENGTH) {//去掉多余的
            $cacheChat->lPop($guildKey);
            goto sizeFlag;
        }
        return $msgData;
    }
    /**
     * 城战聊天
     *
     * @param  int $playerId
     * @param  string $data
     * @return array
     */
    public function saveCityBattleMsg($playerId, $data){
        $msg = $data['content'];
        if(!empty($msg)) {//非系统消息需过滤器
            $msg = (new SensitiveWord)->filterWord($msg);//敏感字
            $msg = strtr($msg, "\n", ' ');//换行变空格
        }
        if(isset($data['paraData'])) {
            $paraData = $data['paraData'];
            $paraData = $paraData;
        } else {
            $paraData = [];
        }

        $Player      = new Player;
        $Guild       = new Guild;
        $CityBattleRound = new CityBattleRound;
        $CityBattlePlayer = new CityBattlePlayer;

        do {
            if(!$playerId) {
                log4cli('not exists playerId='.$playerId);
                break;
            }
            $roundId = $CityBattleRound->getCurrentRound();
            if(!$roundId) {
                log4cli('not exists roundId='.$roundId);
                break;
            }//round not exists
            $battleId = $CityBattlePlayer->getCurrentBattleId($playerId);
            if(!$battleId) {
                log4cli('not exists battleId='.$battleId);
                break;
            }//not join battle or not exists round

            $player = $Player->getByPlayerId($playerId);

            $guildId = $player['guild_id'];
            if($guildId) {
                $guildInfo      = $Guild->getGuildInfo($guildId);

                $guildShortName = $guildInfo['short_name'];
                $guildShortName = empty($guildShortName)? '': $guildShortName;
            } else {
                $guildShortName = '';
            }
            $playerCampId = $player['camp_id'];
            $cityBattleKey = 'CityBattleChat-roundId='.$roundId.'-battleId='.$battleId.'-campId='.$playerCampId;
            //case a: 聊天
            $msgData = [
                'server_id'        => $player['server_id'],
                'type'             => self::TYPE_CITY_BATTLE,
                'player_id'        => $playerId,
                'guild_id'         => $guildId,
                'nick'             => $player['nick'],
                'avatar_id'        => $player['avatar_id'],
                'guild_short_name' => $guildShortName,
                'content'          => $msg,
                'data'             => [],
                'date'             => date('Y-m-d H:i:s'),
                'time'             => time(),
                'memo'             => ['camp_id'=>$playerCampId, 'round_id'=>$roundId, 'battle_id'=>$battleId],
            ];
            if(empty($msg) && !empty($paraData)) {
                $msgData['paraData'] = $paraData;
            }
            $cacheChat           = Cache::db('chat', 'CityBattle');
            $msgData1            = $msgData;
            $msgData1['content'] = $msg;
            $cacheChat->rPush($cityBattleKey, $msgData1);
            sizeFlag://pop多余的聊天记录
            $size = $cacheChat->lsize($cityBattleKey);
            if($size>self::MAX_DISPLAY_LENGTH) {//去掉多余的
                $cacheChat->lPop($cityBattleKey);
                goto sizeFlag;
            }
            return $msgData;
        } while(false);

        return [];
    }
}