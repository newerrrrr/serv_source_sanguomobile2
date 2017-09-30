<?php
/**
 * 新手目标 字典表
 */
class Target extends ModelBase{
    /**
     * 获取最后一个新手目标
     * @return array 
     */
    public function getLast(){
        $static = Cache::db(CACHEDB_STATIC);
        $key = __CLASS__."-LAST";
        $r = $static->get($key);
        if(!$r) {
            $re  = self::findFirst("next_target_id=0");
            if($re) {
                $r = $re->toArray();
                $static->set($key, $r);
                return $r;
            }
        }
        return $r;
    }
}