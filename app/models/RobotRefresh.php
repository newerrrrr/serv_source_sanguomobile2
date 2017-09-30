<?php
/**
 * 玩家表-model
 */
class RobotRefresh extends ModelBase{
	public function getRobotByDay($day){
		$ret = $this->cache(__CLASS__."day_{$day}", function() use ($day) {
			return self::find(["day_start<={$day} and day_end>={$day}","order"=>"id"])->toArray();
		});
		return $ret;
	}
}