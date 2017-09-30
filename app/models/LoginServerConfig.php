<?php
/*
 * 配置
 *
 */
class LoginServerConfig extends ModelBase{
    public function initialize(){
        $this->setConnectionService('db_login_server');
    }
	public function dicGetAll(){
        $re        = $this->findList('key', 'value');
        $className = get_class($this);
        if($re) {
            $cache = Cache::db(CACHEDB_STATIC, $className);
            $cache->set($className, $re);
        }
		return $re;
	}

	public function getValueByKey($key){
		$ret = $this->dicGetAll();
		if(isset($ret[$key]))
			return $ret[$key];
		return null;
	}

    /**
     * @param $key
     * @param $value
     *
     * 保存key value对
     */
	public function saveData($key, $value){
        $className = get_class($this);
        $re        = self::findFirst(["key=:key:", 'bind'=>['key'=>$key]]);
        if($re) {
            $re->key   = $key;
            $re->value = $value;
            $re->save();
        } else {
            $self        = new self;
            $self->key   = $key;
            $self->value = $value;
            $self->save();
        }
        Cache::db(CACHEDB_STATIC, $className)->del($className);
    }
}