<?php
/**
 * 地图元素表
 */
class MapElement extends ModelBase{
    const CacheKey = "MapElement_by_level";
    /**
     * 通过origin_id和level写入redis
     * @return array 
     */
    public function dicGetAllByOriginIdAndLevel(){
        $ret = $this->cache(self::CacheKey, function() {
            $re = self::find()->toArray();
            $re = Set::combine($re, ['{0}_{1}','{n}.origin_id', '{n}.level'], '{n}');
            return $re;
        });
        return $ret;
    }
    /**
     * 通过origin_id和level获取一条记录
     * @param  int $originId 
     * @param  int $level    
     * @return array           
     */
    public function dicGetOneByOriginIdAndLevel($originId, $level){
        $class = self::CacheKey;
        $key = $originId.'_'.$level;
        $d = Cache::db(CACHEDB_STATIC)->hGet($class, $key);
        if(!$d) {
            $this->dicGetAllByOriginIdAndLevel();
            $d = Cache::db(CACHEDB_STATIC)->hGet($class, $key);
        }
        return $d;
    }
}