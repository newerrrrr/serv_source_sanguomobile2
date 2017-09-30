<?php
/**
 * 会话类
 */
class ServSession1 {
    const REDIS_KEY = 'ServSession';//大key
    /**
     * 获取所有fd
     * @return array fd link
     */
    public static function getAllFd(){
        $fd = [];
        foreach(Cache::db('server')->hGetAll(self::REDIS_KEY) as $v) {
            $fd[] = $v['fd'];
        }
        return $fd;
    }
    /**
     * 获取玩家会话信息
     * @param  int $playerId 
     * @return int 
     */
    public static function getFdByPlayerId($playerId){
        $re = Cache::db('server')->hGet(self::REDIS_KEY, $playerId);
        if($re && isset($re['fd'])) {
            return $re['fd'];
        }
        return $re;
    }

    /**
     * 由fd，获取playerId
     * @param  int $fd 
     * @return int     
     */
    public static function getPlayerIdByFd($fd){
        foreach(Cache::db('server')->hGetAll(self::REDIS_KEY) as $v) {
            if($v['fd']==$fd) {
                return $v['player_id'];
            }
        }
        return 0;
    }

    /**
     * 设置玩家会话信息
     * @param int $playerId 
     * @param int $fd       
     */
    public static function setFd($playerId, $fd){
        Cache::db('server')->hSet(self::REDIS_KEY, $playerId, ['fd'=>$fd, 'player_id'=>$playerId]);
    }

    /**
     * 删除fdTable中的玩家会话信息
     * @param  int $fd 
     */
    public static function delLink($fd){
        $allLink = Cache::db('server')->hGetAll(self::REDIS_KEY);
        if(empty($allLink)) return false;
        foreach($allLink as $v) {
            if(isset($v['fd']) && $v['fd']==$fd) {
                Cache::db('server')->hDel(self::REDIS_KEY, $v['player_id']);
                break;
            }
        }
    }
}
