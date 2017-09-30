<?php
/**
 * 玩家登录过的服务器列表
 */
class PlayerLastServer extends ModelBase{
    public function initialize(){
        $this->setConnectionService('db_login_server');
    }
    /**
     * 保存最后一次服务器记录
     */
    public function saveLast($uuid, $serverId){
        $re        = self::findFirst(["uuid='{$uuid}'"]);
        $className = get_class($this);
        if(!$re) {
            $self                 = new self;
            $self->uuid           = $uuid;
            $self->last_server_id = $serverId;
            $self->login_time     = date('Y-m-d H:i:s');
            if($self->save()) {
                $key = $className.'--'.$uuid;
                Cache::db(CACHEDB_PLAYER, $className)->del($key);
            }
        } else {
            if($re->last_server_id!=$serverId) {
                $re->last_server_id = $serverId;
                $re->login_time     = date('Y-m-d H:i:s');
                if($re->save()) {
                    $key = $className.'--'.$uuid;
                    Cache::db(CACHEDB_PLAYER, $className)->del($key);
                }
            }
        }
    }
    /**
     * 获取所有公告
     * @return  array
     */
    public function getByUuid($uuid){
        $className = get_class($this);
        $cache     = Cache::db(CACHEDB_PLAYER, $className);
        $key       = $className . '--' . $uuid;
        $re        = $cache->get($key);
        if(!$re) {
            $re = self::findFirst(["uuid='{$uuid}'"]);
            if($re) {
                $re = $this->adapter($re->toArray(), true);
                $cache->set($key, $re);
            }
        }
        if(!$re) $re = [];
        return $re;
    }
}