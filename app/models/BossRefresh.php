<?php
//建筑
class BossRefresh extends ModelBase{

	public function getBossByDay($day){
		if($day>130){
			$day = 130;
		}
		$ret = $this->cache(__CLASS__."day_{$day}", function() use ($day) {
			return self::find(["day={$day}"])->toArray();
		});
		return $ret;
	}
}