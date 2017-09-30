<?php

/**
 * duel_rank字典表
 *
 */
class DuelRank extends ModelBase{
    /**
     * 通过积分获取对应阶段的记录
     *
     * @param $score
     *
     * @return array
     */
    public function getOneByScore($score){
        $all = $this->dicGetAll();
        $re = [];
        foreach($all as $v) {
            if($v['min_point']<=$score && $v['max_point']>=$score) {
                $re = $v;
                break;
            }
        }
        return $re;
    }

    /**
     * 返回所有rank段位
     * @return array
     */
    public function getAllRank(){
        $all = $this->dicGetAll();
        $all = Set::combine($all, '{n}.id', ['{1}:{0}', '{n}.sub_rank' , '{n}.rank_desc'], '{n}.rank');
        $rank = array_keys($all);
        sort($rank);
        return $rank;
    }
}