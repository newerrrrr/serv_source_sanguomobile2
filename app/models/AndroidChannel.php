<?php

/**
 * ！！！游戏服这张表sql有更新,需要登录服也要更新一份！！！
 *
 * Class AndroidChannel
 */
class AndroidChannel extends ModelBase{
    public $cacheKeyByName = 'android_channel_map';
    /**
     * 通过channel_id获取一条记录
     * @param  string $channelId
     * @return array       
     */
    public function dicGetOneByChannelId($channelId){
        $class = $this->cacheKeyByName;
        $d = Cache::db(CACHEDB_STATIC)->hGet($class, $channelId);
        if(!$d) {
            $this->dicGetAllByName();
            //$d = $this->dicGetOneByName($name);
			$d = Cache::db(CACHEDB_STATIC)->hGet($class, $channelId);
        }
        return $d;
    }
    /**
     * 根据channel_id生成字典表
     * @return array
     */
    public function dicGetAllByName(){
        $ret = $this->cache($this->cacheKeyByName, function() {
            return $this->findList('channel_id');
        });
        return $ret;
    }
}