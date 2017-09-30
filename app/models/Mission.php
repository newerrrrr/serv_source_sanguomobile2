<?php
/**
 * mission 表
 */
class Mission extends ModelBase{
    const CacheKeyMain  = 'Mission_Main';
    const CacheKeyDaily = 'Mission_Daily_ByLevel';
    /**
     * 字典表获取所有主线任务
     * 
     * @return array
     */
    public function dicGetAllMainMission(){
        $ret = $this->cache(self::CacheKeyMain, function() {
            $mainMissionId = PlayerMission::getMainMissionTypeStr();
            $re = self::find("mission_type in ({$mainMissionId})")->toArray();
            $re = Set::combine($re, '{n}.id', '{n}');
            return $re;
        });
        return $ret;
    }
    /**
     * 获取单个id的主线任务
     * 
     * @param  int $id 
     * @return array     
     */
    public function dicGetOneMainMission($id){
        $d = Cache::db(CACHEDB_STATIC)->hGet(self::CacheKeyMain, $id);
        if(!$d) {
            $d = $this->dicGetAllMainMission()[$id];
        }
        return $d;
    }
    /**
     * 字典表获取所有每日任务
     * 
     * @param  int $level 
     * @return array        
     */
    public function dicGetDailyMissionByLevel($level){
        $re = Cache::db(CACHEDB_STATIC)->hGet(self::CacheKeyDaily, $level);
        if(!$re) {
            $mainMissionId = PlayerMission::getMainMissionTypeStr();
            $re = self::find("mission_type not in ({$mainMissionId}) and min_level<={$level} and max_level>={$level}")->toArray();
            if($re){
                $re = Set::combine($re, '{n}.id', '{n}', '{n}.mission_type');
                Cache::db(CACHEDB_STATIC)->hSet(self::CacheKeyDaily, $level, $re);
            }
        }
        return $re;
    }
}