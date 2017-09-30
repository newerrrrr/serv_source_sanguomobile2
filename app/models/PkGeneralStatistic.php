<?php

/**
 * 武将胜率
 *
 */
class PkGeneralStatistic extends PkModelBase {
    /**
     * 保存数据
     *
     * @param $generalId
     * @param $data
     */
    public function saveData($generalId, $data){
        $exists = self::findFirst(['general_id=:general_id:', 'bind'=>['general_id'=>$generalId]]);
        $fields = ['win_times', 'lose_times'];
        if($exists) {
            foreach($fields as $v) {
                if(isset($data[$v])) {
                    $inc            = $data[$v];
                    $updateData[$v] = "{$v}+{$inc}";
                }
            }
            $this->updateAll($updateData, ['general_id'=>$generalId]);
        } else {
            $self = new self;
            $self->general_id = $generalId;
            foreach($fields as $v) {
                if(isset($data[$v])) {
                    $self->{$v} = $data[$v];
                }
            }
            $self->save();
        }
    }
}