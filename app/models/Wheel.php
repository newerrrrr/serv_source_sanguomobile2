<?php
/**
 * 转盘奖励
 *
 */
class Wheel extends ModelBase{
	public function getByGridAndLv($levelId, $gridId){
		$re = $this->dicGetAll();
		foreach ($re as $value) {
			if($value['grid_id']==$gridId && $value['lv_min']<=$levelId && $value['lv_max']>=$levelId){
				return $value;
			}
		}
		return false;
	}
}