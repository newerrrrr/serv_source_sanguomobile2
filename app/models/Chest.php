<?php
/**
 * 转盘奖励
 *
 */
class Chest extends ModelBase{
	public function createCardOrder($level, &$cId){
		$re = $this->dicGetAll();
		shuffle($re);
		$chestId = 0;
		$chestArr = [];
		foreach($re as $v){
			if($v['lv_min']<=$level && $v['lv_max']>=$level){
				if($chestId==0){
					$chestId = $v['chest_id'];
				}
				if($chestId==$v['chest_id']){
					$chestArr[$v['id']] = $v['weight'];
				}
			}
		}
		$cId = $chestId;
		return $this->reOrder($chestArr);
	}

	public function reOrder($arr){
		$_arr = $arr;
		$result = [];
		while(!empty($_arr)){
			$key = getRandByArr($_arr);
			$result[] = $key;
			unset($_arr[$key]);
		}
		$lastChestId = $result[8];
		$chest = $this->dicGetOne($lastChestId);
		if($chest['type']==2){
			$result = $this->reOrder($arr);
		}
		return $result;
	}
}