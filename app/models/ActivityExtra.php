<?php
class ActivityExtra extends ModelBase{
	public function add($id, $openDate=0, $closeDate=0, $memo=''){
		$self = new self;
		$ret = $self->create(array(
			'id' => $id,
			'open_date' => $openDate,
			'close_date' => $closeDate,
			'memo' => $memo,
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
			return $this->updateAll(['open_date'=>$openDate, 'close_date'=>$closeDate], ['id'=>$id]);
		}
	}
	
	public function upMemo($id, $memo){
		if(!$this->find(['id='.$id])->toArray()){
			return $this->add($id, 0, 0, $memo);
		}else{
			return $this->updateAll(['memo'=>$memo], ['id'=>$id]);
		}
	}
}