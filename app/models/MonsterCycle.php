<?php
//建筑
class MonsterCycle extends ModelBase{

	public function getMonsterByDay($day){
		if($day>120){
			$day = 120;
		}
		$ret = $this->cache(__CLASS__."day_{$day}", function() use ($day) {
			return self::find(["day={$day}", "order"=>"id"])->toArray();
		});
		return $ret;
	}
}