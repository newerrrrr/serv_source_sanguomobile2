<?php

/**
 * duel_rank字典表
 *
 */
class DuelRobot extends ModelBase{
    /**
     * 通过次数获取数据
     *
     * @param $count
     *
     * @return array
     */
    public function getByCount($count){
        $all = $this->dicGetAll();
        foreach($all as $v) {
            if($v['count']==$count) {
                return $v;
            }
        }
        return [];
    }
}