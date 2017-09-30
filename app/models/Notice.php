<?php
/**
 * 公告
 */
class Notice extends ModelBase{
    public function initialize(){
        $this->setConnectionService('db_login_server');
    }
    /**
     * 添加新记录
     * 
     * @param int $playerId 
     * @param array $data  
     */
    public function addNew(array $data){
        $self              = new self;
        $self->title       = $data['title'];
        $self->content     = $data['content'];
        $self->begin_time  = $data['begin_time'];
        $self->end_time    = $data['end_time'];
        $self->channel     = $data['channel'];
        $self->save();
        $r                 = self::findFirst($self->id);
        return $r->toArray();
    }
    /**
     * 获取所有公告
     * @return  array
     */
    public function getAll($gmFlag=false){
        $now = date('Y-m-d H:i:s');
        if($gmFlag) {
            $re = self::find(['order'=>'begin_time desc'])->toArray();
        } else {
            $re = self::find(["begin_time<='{$now}' and end_time>='{$now}'", 'order'=>'begin_time desc'])->toArray();
        }
        return $re;
    }
    public function del($id){
        self::findFirst($id)->delete();
    }

    /**
     * 渠道公告
     *
     * @param $channel
     *
     * @return mixed
     */
    public function getAllByChannel($channel=''){
        $channel = trim($channel);
        $now = date('Y-m-d H:i:s');
        if(empty($channel)) {
            $re = self::find(["channel='all_channel' and begin_time<='{$now}' and end_time>='{$now}'", 'order' => 'begin_time desc'])->toArray();
        } else {
            $re = self::find(["(channel like '%{$channel}%' or channel='all_channel') and begin_time<='{$now}' and end_time>='{$now}'", 'order' => 'begin_time desc'])->toArray();
        }
        return $re;
    }
}