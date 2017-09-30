<?php
/**
 * 玩家登录过的服务器列表
 */
class RefundInfo extends ModelBase{
    public function initialize(){
        $this->setConnectionService('db_login_server');
    }

    /**
     * 获取封测返利数据
     * @return  array
     */
    public function getByUuid($uuid){
        $re = self::findFirst(["uuid='{$uuid}'"]);
        if($re) {
            $re = $this->adapter($re->toArray(), true);
        }else{
            $re = [];
        }
        return $re;
    }

    public function getGemNum($uuid){
        $re = $this->getByUuid($uuid);
        if(!empty($re) && $re['status']==0){
            $this->updateAll(['status'=>1], ['id'=>$re['id']]);
            return $re['gem']*3;
        }else{
            return 0;
        }
    }
}