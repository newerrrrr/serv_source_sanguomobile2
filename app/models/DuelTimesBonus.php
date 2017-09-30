<?php

/**
 * duel_times_bonus字典表
 *
 */
class DuelTimesBonus extends ModelBase{
    /**
     * 通过次数和已经获得的id 获取可以领奖的记录
     *
     * @param     $times
     * @param int $gainId
     *
     * @return array
     */
    public function getByTimes($times, $gainId=0){
        $all = $this->dicGetAll();
        $all = Set::sort($all, '{n}.id', 'asc');
        $re = [];
        foreach($all as $v) {
            if($v['times']<=$times && $v['id']>$gainId) {
                $re[] = $v;
            }
        }
        return $re;
    }

    /**
     * 获取第一条满足条件记录
     *
     * @param     $times
     * @param int $gainId
     *
     * @return array
     */
    public function getOneByTimes($times, $gainId=0){
        $all = $this->dicGetAll();
        $all = Set::sort($all, '{n}.id', 'asc');
        $re = [];
        foreach($all as $v) {
            if($v['times']<=$times && $v['id']>$gainId) {
                return $v;
            }
        }
        return $re;
    }
}