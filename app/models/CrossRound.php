<?php
/**
 * cross_round
 *
 */
class CrossRound extends CrossModelBase{
    const Status_sign        = 0;//报名
    const Status_match_start = 1;//匹配脚本运行开始
    const Status_match_end   = 2;//匹配脚本运行结束
    const Status_battle      = 3;//处于比赛中状态
    const Status_award       = 4;//待发奖状态
    const Status_battle_end  = 5;//整轮比赛结束

    public $current = null;//当前条数据
    /**
     * 新建数据
     */
    public function addNew(){
        $exists = self::find(['status<>:status:', 'bind'=>['status'=>self::Status_battle_end]])->toArray();
        if(empty($exists)) {
            $self              = new self;
            $self->create_time = date('Y-m-d H:i:s');
            $self->save();
            return $self->id;
        }
        return 0;
    }

    /**
     * 获取当前轮的id
     * 并将整个数组内容复制到属性current上
     */
    public function getCurrentRoundId(){
        $current = self::findFirst(['status<>:status:', 'bind'=>['status'=>self::Status_battle_end]]);
        if($current) {
            $this->current = $current->toArray();
            return $current->id;
        }
        return 0;//表示没有
    }

    /**
     * 更新当前进行中的行记录
     * e.g. alterCurrent(['status'=>3], ['status'=>2]);
     * @param array $fields
     * @param array $conditions
     *
     * @return int
     */
    public function alterCurrent(array $fields, array $conditions=[]){
        $current = self::findFirst(['status<>:status:', 'bind'=>['status'=>self::Status_battle_end]]);
        if($current) {
            if($conditions) {
                $conditions['id'] = $current->id;
            } else {
                $conditions = ['id'=>$current->id];
            }
            return $this->updateAll($fields, $conditions);
        }
        return 0;
    }
}
