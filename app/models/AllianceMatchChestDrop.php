<?php
/**
 * 限时比赛排名公会礼包
 */
class AllianceMatchChestDrop extends ModelBase{
	
	public function getByRank($rank){
		$ret = $this->find(['rank='.$rank*1])->toArray();
		$_ret = [];
		foreach($ret as $_r){
			$_ret[] = ['gift'=>$_r['item_id'], 'num'=>$_r['max_count']];
		}
		return $_ret;
	}
	
	public function getMaxRank(){
		return $this->maximum(array('column'=>'rank'));
	}
}