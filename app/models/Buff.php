<?php
class Buff extends ModelBase{
    public static $buffIdForShow = [105,304,305,306,307,308,309,310,311,312,313,314,315,348,349,350,351,352,353,354,355,356,357,358,359,];
    public $cacheKeyByName = 'Buff->name';//cache key by name
    /**
     * 通过name获取一条记录
     * @param  string $name 
     * @return array       
     */
    public function dicGetOneByName($name){
        $class = $this->cacheKeyByName;
        $d = Cache::db(CACHEDB_STATIC)->hGet($class, $name);
        if(!$d) {
            $this->dicGetAllByName();
            //$d = $this->dicGetOneByName($name);
			$d = Cache::db(CACHEDB_STATIC)->hGet($class, $name);
        }
        return $d;
    }
    /**
     * 根据name生成字典表
     * @return array
     */
    public function dicGetAllByName(){
        $ret = $this->cache($this->cacheKeyByName, function() {
            return $this->findList('name');
        });
        return $ret;
    }
}