<?php
/**
 * 限时比赛 字典表
 */
class TimeLimitMatchConfig extends ModelBase{
    /**
     * 新建记录
     */
    public function addNew(){
        $self              = new self;
        $self->create_time = date('Y-m-d H:i:s');
        $self->save();
        return $self->id;
    }
    /**
     * 获得当前轮的限时比赛config表
     * @return array 
     */
    public function getCurrentRound(){
        $re = self::findFirst("status=0");
        if($re) {
            $r = $this->adapter($re->toArray(), true);
        } else {
            $r = false;
        }
        return $r;
    }
    /**
     * 获取当前限时比赛的id
     */
    public function getCurrentRoundId($forAwardFlag=false){
        $re = $this->getCurrentRound();
        if($re) {
            if($forAwardFlag) {
                return ($re['end_time']<time())? $re['id']: false;
            } else {
                return $re['id'];
            }
        }
        return false;
    }
    public function getLastRound(){
        $re = self::findFirst(["order"=>"id desc"]);
        $r = $this->adapter($re->toArray(), true);
        return $r;
    }
}