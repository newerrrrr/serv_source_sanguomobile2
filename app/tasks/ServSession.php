<?php
/**
 * 会话类
 */
class ServSession {
    public static $table = null;

    /**
     * for test convert $table to json string
     * @return string
     */
    public static function toJSON(){
        $arr = [];
        foreach(self::$table as $v) {
            $arr[] = $v;
        }
        return json_encode($arr);
    }
    /**
     * 获取所有fd
     * @param $campId
     * @return array fd link
     */
    public static function getAllFd($campId=-1){
        if(!self::$table) return [];
        $fd = [];
        foreach(self::$table as $v) {
            if($campId==-1) {
                $fd[] = $v['fd'];
            } elseif($v['camp_id']==$campId) {
                $fd[] = $v['fd'];
            }
        }
        return array_unique($fd);
    }

    /**
     * 获取所有camp的在线数据
     *
     * @param $campId
     *
     * @return array
     */
    public static function getAllCamp($campId){
        if(!self::$table) return [];
        $all = [];
        foreach(self::$table as $v) {
            if($v['camp_id']==$campId) {
                $all[] = $v;
            }
        }
        return $all;
    }
    /**
     * 获取玩家会话信息
     * @param  int $playerId 
     * @return int 
     */
    public static function getFdByPlayerId($playerId){
        if(!self::$table) return 0;
        $conn = self::$table->get($playerId);
        if($conn) {
            return $conn['fd'];
        }
        return 0;

    }

    /**
     * 由fd，获取playerId
     * @param  int $fd 
     * @return int     
     */
    public static function getPlayerIdByFd($fd){
        if(!self::$table) return 0;
        foreach(self::$table as $v) {
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
    public static function setFd($playerId, $fd, $campId=0){
        if(self::$table) {
            self::$table->set($playerId, ['fd' => $fd, 'player_id' => $playerId, 'camp_id'=>$campId]);
        }
    }

    /**
     * 删除fdTable中的玩家会话信息
     * @param  int $fd 
     */
    public static function delLink($fd){
        $playerId = self::getPlayerIdByFd($fd);
        if($playerId>0) {
            self::$table->del($playerId);
        }
    }
}
