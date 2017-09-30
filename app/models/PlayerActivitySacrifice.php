<?php
//大转盘
class PlayerActivitySacrifice extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time');
	const ACTID = 1028;
	
	public function getByActId($playerId, $activityConfigureId){
        $ret = $this->findFirst(['player_id='.$playerId.' and activity_configure_id='.$activityConfigureId]);
		if(!$ret) {
            (new self)->save(['player_id'=>$playerId, 'activity_configure_id'=>$activityConfigureId, 'times'=>0, 'create_time'=>date('Y-m-d H:i:s'), 'update_time'=>date('Y-m-d H:i:s')]);
            $ret = $this->findFirst(['player_id='.$playerId.' and activity_configure_id='.$activityConfigureId]);
        }
		$ret = $ret->toArray();
		return $ret;
	}

    /**
     * 增加权重次数
     * @param $playerId
     * @param $activityConfigureId
     */
	public function incTimes($playerId, $activityConfigureId){
        $this->updateAll(['times'=>'times+1'], ['player_id'=>$playerId, 'activity_configure_id'=>$activityConfigureId]);
    }
	
}