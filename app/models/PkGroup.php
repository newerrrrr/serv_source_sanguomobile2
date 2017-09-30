<?php

/**
 * pk服务器组
 *
 */
class PkGroup extends PkModelBase{
    const START_TIME = '08:00:00';//赛季开始时间
    /**
     * @param $data
     */
    public function addNew($data){
        $self = new self;
        foreach($data as $k=>$v) {
            $self->{$k} = $v;
        }
        $self->update_time = date('Y-m-d H:i:s');
        $self->create_time = date('Y-m-d H:i:s');
        $self->save();
    }

    /**
     * 获取所有pk_group的数据
     *
     * @return array
     */
    public function getAllGroup(){
        $re = self::find()->toArray();
        foreach($re as $k=>&$v) {
            if(trim($v['server_ids'])) {
                $v['server_ids'] = explode(";", $v['server_ids']);
            } else {
                $v['server_ids'] = [];
            }
            if(trim($v['exec_server_ids'])) {
                $v['exec_server_ids'] = explode(";", $v['exec_server_ids']);
            } else {
                $v['exec_server_ids'] = [];
            }
        }
        unset($v);
        return $re;
    }
    public function getGroupById($id){
        $all = $this->getAllGroup();
        foreach($all as $v) {
            if($v['id']==$id) {
                return $v;
            }
        }
    }
    public function alter($id, $data){
        $re = self::findFirst($id);
        if($re) {
            foreach($data as $k=>$v) {
                if($k=='update_log') {
                    $re->$k .= $v;
                } else {
                    $re->$k = $v;
                }
            }
            $re->save();
        }
    }
    public function close($id){
        $re = self::findFirst($id);
        if($re) {
            $re->delete();
        }
    }
    public function getGroupsByServerId($serverId){
        $all = $this->getAllGroup();
        foreach($all as $v) {
            if(in_array($serverId, $v['server_ids'])) {
                return $v['server_ids'];
            }
        }
        return [$serverId];//没有被编组,只能搜到自己服的
    }
    public function getPkGroupByServerId($serverId){
        $all = $this->getAllGroup();
        foreach($all as $v) {
            if(in_array($serverId, $v['server_ids'])) {
                return $v;
            }
        }
        return null;
    }
}