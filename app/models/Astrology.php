<?php
/**
 * 字典表 astrology
 */
class Astrology extends ModelBase{
    public static $onceDropType     = 14;//掉落过特殊道具后就跳过此type
    public        $dropGroupGain100 = [3, 13];//100必中的类型type
    public        $dropGroupType    = __CLASS__."_dropGroup_";
    /**
     * @param $dropGroup
     *
     * @return mixed
     *
     * 通过drop_group获取字典数据
     */
    public function dicGetByDropGroup($dropGroup){
        $re = $this->cache($this->dropGroupType.$dropGroup, function() use ($dropGroup) {
            $re = self::find("drop_group={$dropGroup}")->toArray();
            $re = Set::sort($re, '{n}.id', 'asc');
            return $re;
        });
        $re = Set::sort($re, '{n}.id', 'asc');
        return $re;
    }
    /**
     * @param int $dropGroup
     * @param int $counter
     *
     * @return mixed
     *
     * 通过计数器获取单条字典记录
     */
    public function getByCounter($dropGroup=1, $counter=1){
        $re = $this->dicGetByDropGroup($dropGroup);
        if(in_array($dropGroup, $this->dropGroupGain100)) {//必掉配的chance为100%
            $r                 = $re[0];
            $r['is_gain_flag'] = true;
            return $r;
        } else {
            foreach($re as $v) {
                if($v['min_count']<=$counter && $v['max_count']>=$counter) {
                    $v['is_gain_flag'] = lcg_value1()<=($v['chance']/DIC_DATA_DIVISOR);
                    return $v;
                }
            }
        }
        exit('传入错误值！');
    }
}