<?php
/**
 * 活动配置-model
 */
class ActivityConfigure extends ModelBase{
	public $activityTypeArr = ["1"=>"公会捐献", "2"=>"传国玉玺", "3"=>"据点战"];
	public $acts = [1017, 1018, 1019, 1022, 1023, 1026, 1027, 1028];

	/**
	 * 开启新活动
	 * @param  [type] $activityId      [description]
	 * @param  [type] $showTime  [description]
	 * @param  [type] $startTime [description]
	 * @param  [type] $endTime   [description]
	 * @param  [type] $para      [description]
	 * @return [type]            [description]
	 */
	public function openActivity($activityId, $showTime, $startTime, $endTime, $para, &$id=0){
		$re = $this->getLastActivityByActivityId($activityId);
		if(!empty($re) && ($re['end_time']>=strtotime($startTime))){
			return false;
		}else{
			$self = new self;
			$self->activity_id   = $activityId;
			$self->activity_name = "联盟活动";
			$self->activity_para = json_encode($para);
			$self->show_time     = $showTime;
			$self->start_time    = $startTime;
			$self->end_time      = $endTime;
			$self->create_time   = date("Y-m-d H:i:s");
			if(in_array($activityId, $this->acts)){
				$self->status = 0;
			}
			$self->save();
			$id = $self->id;
			if($activityId==1003){
				(new GuildHuangjin)->updateAll(['status'=>0], []);
			}
			return true;
		}
	}

	/**
	 * 获取当前需要显示的所有活动
	 * @param  integer $activityId [description]
	 * @return [type]        [description]
	 */
	public function getCurrentActivity($activityId=0){
		if($activityId==0){
			$re = self::find(["show_time<=now() and end_time>=now()"])->toArray();
		}elseif(in_array($activityId, $this->acts)){
			$re = self::find(["activity_id={$activityId} and status=1 and show_time<=now() and end_time>=now()"])->toArray();
		}else{
			$re = self::find(["activity_id={$activityId} and show_time<=now() and end_time>=now()"])->toArray();
		}
		$re = $this->adapter($re, false);
		return $re;
	}

	/**
	 * 获得某类活动的最后一条记录
	 */
	public function getLastActivityByActivityId($activityId){
		$re = self::find(["activity_id={$activityId}", "order"=>"id desc"])->toArray();
		if(!empty($re)){
			$re = $this->adapter($re, false);
			return $re[0];
		}else{
			return false;
		}
	}

	/**
	 * 关闭活动
	 * @param  [type] $activityId [description]
	 * @return [type]       [description]
	 */
	public function closeActivity($activityId){
		$re = self::find(["end_time>=now()"])->toArray();
		$re = $this->adapter($re, false);
		foreach ($re as $key => $value) {
			if($value['activity_id']==$activityId){
				$id = $value['id'];
				$this->updateAll(["end_time"=>qd()], ["id"=>$id]);
				return true;
			}
		}
		return false;
	}

    /**
     * 打怪掉落活动
     * 
     * type:1.小怪，2.boss
     * @return <type>
     */
	public function getNpcDrop($type){
		$activityConfigure = $this->getCurrentActivity(1019);
		if(!$activityConfigure){
			return true;
		}
		$activityConfigure = $activityConfigure[0];
		$config = json_decode($activityConfigure['activity_para'], true);
		if($type == 1){
			$reward = $config['npc']['drop'];
			$rate = $config['npc']['rate'];
		}else{
			$reward = $config['boss']['drop'];
			$rate = $config['boss']['rate'];
		}
		if(lcg_value1() <= $rate){
			return parseGroup($reward, false);
		}
		return true;
	}
}