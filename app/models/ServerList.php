<?php
/**
 * 服务器列表
 */
class ServerList extends ModelBase{
    public function initialize(){
        $this->setConnectionService('db_login_server');
    }
    /**
     * 字典表获取所有
     */
    public function dicGetAll(){
        $className = get_class($this);
        $ret       = Cache::db(CACHEDB_STATIC, $className)->hGetAll(__CLASS__);
        if(!$ret){
            $ret = $this->adapter($this->findList('id'));
            Cache::db(CACHEDB_STATIC, $className)->hMset(__CLASS__, $ret);
        }

        $ret = Set::sort($ret, '{n}.id', 'asc');
        return $ret;
    }
    /**
     * 根据serverId返回game_server_ip
     * @param $serverId
     *
     * @return string
     */
    public function getGameServerIpByServerId($serverId){
        $allServerList = $this->dicGetAll();
        $gameServerIp = '';
        foreach($allServerList as $v) {
            if($v['id']==$serverId) {
                $gameServerIp = trim($v['game_server_ip']);
                if(empty($gameServerIp)) {//ip不存在的情况下用 host，确保不出错
                    $gameServerIp = $v['gameServerHost'];
                }
                break;
            }
        }
        return $gameServerIp;
    }
    /**
     * 根据serverId返回gameServerHost
     * @param $serverId
     *
     * @return string
     */
    public function getGameServerHostByServerId($serverId){
        $allServerList = $this->dicGetAll();
        $gameServerHost = '';
        foreach($allServerList as $v) {
            if($v['id']==$serverId) {
                $gameServerHost = $v['gameServerHost'];
                break;
            }
        }
        return $gameServerHost;
    }
    /**
     * @param $id
     * @param $field
     * @param $value
     *
     * @return bool
     *
     * 更改字段
     */
    public function alterServerList($id, $field, $value){
        $re = self::findFirst($id);
        if($re) {
            $re->{$field} = $value;
            $re->save();
            Cache::db(CACHEDB_STATIC, get_class($this))->del(__CLASS__);
            return true;
        }
        return false;
    }
    /**
     * @param $id
     *
     * @return bool
     *
     * 更改维护状态
     */
    public function alterDefaultEnter($id){
        $re = self::findFirst($id);
        if($re) {
            $this->updateAll(['default_enter'=>0], [1=>1]);
            $re->default_enter = 1;
            $re->save();
            Cache::db(CACHEDB_STATIC, get_class($this))->del(__CLASS__);
            return true;
        }
        return false;
    }

    /**
     * 更改所有服状态
     * @param $status
     */
    public function alterAllStatus($status) {
        $this->updateAll(['status'=>$status], [1=>1]);
        Cache::db(CACHEDB_STATIC, get_class($this))->del(__CLASS__);
    }
}