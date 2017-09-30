<?php
/**
 * 王战发礼包
 */
class KingPlayerReward extends ModelBase{
 	public $rewardNumArr = ['1'=>10,'2'=>20,'3'=>5];

 	public function delAll(){
		$Courses = self::find();
		$Courses->delete();
	}

	public function addNew($data){
		$self              = new self;
		$self->player_id   = $data['playerId'];
		$self->reward_type = $data['type'];
		$self->create_time = date('Y-m-d H:i:s');
        $self->save();
    }

    public function clacLeftGiftNum(){
    	$ret = self::find()->toArray();
    	$result = $this->rewardNumArr;
    	foreach ($ret as $key => $value) {
    		$result[$value['reward_type']] -= 1;
    	}
    	return $result;
    }

}