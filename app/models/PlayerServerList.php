<?php
/**
 * 玩家登录过的服务器列表
 */
class PlayerServerList extends ModelBase{
    public function initialize(){
        $this->setConnectionService('db_login_server');
    }
    /**
     * 添加新记录
     */
    public function addNew($uuid, $serverId, $data=[]){
        $re        = self::findFirst(["uuid='{$uuid}' and server_id={$serverId}"]);
        $className = get_class($this);
        if(!$re) {
            $self              = new self;
            $self->uuid        = $uuid;
            $self->server_id   = $serverId;
            $self->nick        = $data['nick'];
            $self->avatar_id   = $data['avatar_id'];
            $self->level       = $data['level'];
            $self->update_time = $self->create_time = date('Y-m-d H:i:s');
            if($self->save()) {
                $key = $className.'--'.$uuid;
                Cache::db(CACHEDB_PLAYER, $className)->del($key);
            }
        }
    }
    /**
     * 更新记录
     */
    public function updateInfo($uuid, $serverId, $field, $value){
        $re        = self::findFirst(["uuid='{$uuid}' and server_id={$serverId}"]);
        $className = get_class($this);
        if($re) {
            $re->$field = $value;
            $re->save();
            $key = __CLASS__.'--'.$uuid;
            Cache::db(CACHEDB_PLAYER, $className)->del($key);
        }
    }
    /**
     * 获取所有公告
     */
    public function getByUuid($uuid){
        $className = get_class($this);
        $cache     = Cache::db(CACHEDB_PLAYER, $className);
        $key       = __CLASS__ . '--' . $uuid;
        $re        = $cache->get($key);
        if(!$re) {
            $re = self::find(["uuid='{$uuid}'"])->toArray();
            if($re)
                $cache->set($key, $this->adapter($re));
        }
        return $re;
    }
}