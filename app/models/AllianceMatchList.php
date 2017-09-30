<?php
class AllianceMatchList extends ModelBase{
	const NOT_START = 0;//不显示
	const DOING = 1;//活动进行中
	const WAIT_REWARD = 2;//显示结束但未发奖
	const NO_ACTIVITY = 3;//没有相关活动

	/**
	 * 判断比赛状态
	 * @param  [type] $type [description]
	 * @param  string &$re  [description]
	 * @return [type]       [description]
	 */
	public function getAllianceMatchStatus($type, &$re=''){
		$re = $this->findFirst(["type={$type}", 'order'=>'id desc']);
		if($re){
			$re = $re->toArray();
			$re = $this->adapter($re, true);
		}
		if($re && $re['start_time']>time()){//活动不显示
			return self::NOT_START;
		}elseif($re && $re['start_time']<=time() && $re['end_time']>=time()){//活动进行中
			return self::DOING;
		}elseif($re && $re['calc_status']==0){//完成未计算奖励
			return self::WAIT_REWARD;
		}else{//没有活动
			return self::NO_ACTIVITY;
		}
	}

	/**
	 * 发完奖励后修改状态
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function finishReward($id){
		$re = $this->updateAll(['calc_status'=>1],['id'=>$id]);
	}

	/**
	 * 添加新记录
	 * @param [type] $data      [description]
	 */
	public function addNew($data){	
		$lastMatch = $this->getLastMatch($data['type']);
		$self = new self;
		$self->type          = $data['type'];
		$self->start_time    = $data['start_time'];
		$self->end_time      = $data['end_time'];
		$self->round         = empty($lastMatch)?1:$lastMatch['round']+1;
		$self->create_time   = date("H:i:s");
		$self->save();
		return true;
	}

	public function getLastMatch($type=0){
		if($type==0){
			$re = self::findFirst(['order'=>'end_time desc']);
		}else{
			$re = self::findFirst(["type={$type}", 'order'=>'end_time desc']);
		}

		if(!empty($re)){
			$re = $re->toArray();
			$re = $this->adapter($re, true);
		}
		
		return $re;
	}
}