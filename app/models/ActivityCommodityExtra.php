<?php
/**
 * 礼包配置
 */
class ActivityCommodityExtra extends ModelBase{
	public function add($id, $openDate=0, $closeDate=0){
		$self = new self;
		$ret = $self->create(array(
			'id' => $id,
			'open_time' => $openDate,
			'close_time' => $closeDate,
		));
		if(!$ret)
			return false;
		//$this->clearGuildCache($guildId);
		return true;
	}
	
	public function updateDate($id, $openDate, $closeDate){
		if(!$this->find(['id='.$id])->toArray()){
			return $this->add($id, $openDate, $closeDate);
		}else{
			return $this->updateAll(['open_time'=>$openDate, 'close_time'=>$closeDate], ['id'=>$id]);
		}
	}
}