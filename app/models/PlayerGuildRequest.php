<?php
//建筑
class PlayerGuildRequest extends ModelBase{
	public $blacklist = ['create_time', 'update_time'];
    /**
     * 获取该联盟的新申请成员
     * @param  int $playerId 
     * @return array     
     */
    public function getAllGuildRequest($guildId){
        if(!$guildId) return [];
        $re = Cache::getGuild($guildId, __CLASS__);
        if(!$re) {
            $re = self::find("guild_id={$guildId}")->toArray();
            $re = $this->adapter($re);
            Cache::setGuild($guildId, __CLASS__, $re);
        }
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
            $r[$pid]['Player']['avatar_id']        = $player['avatar_id'];
            $r[$pid]['Player']['power']            = $player['power'];
            $r[$pid]['Player']['job']              = $player['job'];
            $r[$pid]['Player']['last_online_time'] = $player['last_online_time'];
        }
        return $r;
    }
    /**
     * 获取玩家player_guild_request数据
     * @param  int  $playerId   
     * @param  boolean $forDataFlag 是否用来返回Data接口数据
     * @return array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $re = Cache::getPlayer($playerId, __CLASS__);
        if(!$re) {
            $re = self::find("player_id={$playerId}")->toArray();
            $re = Set::combine($re, '{n}.guild_id', '{n}');
            $re = filterFields($this->adapter($re), $forDataFlag, $this->blacklist);
            Cache::setPlayer($playerId, __CLASS__, $re);
        }
        return $re;
    }
    /**
     * 接受申请
     * @param  int $playerId 
     * @param  int $guildId  
     * @param  int $campId
     */
    public function accept($playerId, $guildId, $campId=0){
        $re = self::find("player_id={$playerId}");
        if($re->toArray()) {
            $re->delete();
            $PlayerGuild = new PlayerGuild;
            $Player = new Player;
            $PlayerGuild->setCampId($campId)->addNew($playerId, $guildId);
            $Player->updateAll(['guild_id'=>$guildId], ['id'=>$playerId]);
            $Player->clearDataCache($playerId);
            $this->clearCache($playerId, $guildId);
            return true;
        } else {
            return false;
        }
    }
    /**
     * 拒绝申请
     * @param  int $playerId 
     * @param  int $guildId  
     */
    public function refuse($playerId, $guildId){
        $re = self::find("player_id={$playerId} and guild_id={$guildId}");
        if($re->toArray()){
            $re->delete();
            $this->clearCache($playerId, $guildId);
            return true;
        } else {
            return false;
        }
    }
    /**
     * 申请联盟请求
     * @param  int $playerId 
     * @param  int $guildId  
     */
    public function apply($playerId, $guildId){
        $exists = self::find("player_id={$playerId} and guild_id={$guildId}")->toArray();
        if(!$exists) {
            $self = new self;
            $self->player_id = $playerId;
            $self->guild_id = $guildId;
            $self->create_time = date('Y-m-d H:i:s');
            $self->update_time = date('Y-m-d H:i:s');
            $self->save();
        }
        $this->clearCache($playerId, $guildId);
    }
    /**
     * 该方法统一清cache方法
     * @param  int $playerId 
     * @param  int $guildId  
     */
    public function clearCache($playerId, $guildId){
        if($playerId)
            $this->clearDataCache($playerId);
        if($guildId)
            $this->clearGuildCache($guildId);
        $this->getDI()->get('data')->filterBasic([__CLASS__], true);
    }
}