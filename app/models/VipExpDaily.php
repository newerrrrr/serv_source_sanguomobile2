<?php
/**
 * 每日签到：vip经验表
 */
class VipExpDaily extends ModelBase{
    const CacheKeySpecial = 'VipExpDaily-specialCondition';
    /**
     * 特殊条件的字典表cache
     * @param  int $vipLevel  
     * @param  int $vipActive 
     * @param  int $signTimes 
     * @return array            
     */
    public function dicGetBySpecialCondition($vipLevel, $vipActive, $signTimes){
        $key = $vipLevel . '_' . $vipActive . '_' . $signTimes;
        $re = Cache::db(CACHEDB_STATIC)->hGet(self::CacheKeySpecial, $key);
        if(!$re) {
            $re = self::find("vip_level={$vipLevel} and if_vip_actived={$vipActive} and continue_sign_days=if({$signTimes}>7, 7, {$signTimes})")->toArray();
            if($re){
                $re = $re[0];
                Cache::db(CACHEDB_STATIC)->hSet(self::CacheKeySpecial, $key, $re);
            }
        }
        return $re;
    }

}