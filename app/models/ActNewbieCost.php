<?php
class ActNewbieCost extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				$_r = $this->parseColumn($_r);
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
	public function parseColumn($_r){
		$_r['drop'] = parseArray($_r['drop']);
		return $_r;
	}
	
	public function getMaxDay(){
		$cacheName1 = __CLASS__ .'MaxDay';
		$ret = Cache::db(CACHEDB_STATIC)->get($cacheName1);
		if(!$ret){
			$ret = $this->maximum(array('column'=>'close_date'));
			Cache::db(CACHEDB_STATIC)->set($cacheName1, $ret);
		}
		return $ret;
	}
	
	public function getMaxGem($period){
		$cacheName1 = __CLASS__ .'MaxGem:period='.$period;
		$ret = Cache::db(CACHEDB_STATIC)->get($cacheName1);
		if(!$ret){
			$ret = $this->maximum(array('column'=>'id', 'period='.$period));
			Cache::db(CACHEDB_STATIC)->set($cacheName1, $ret);
		}
		return $ret;
	}
	
	public function getPeriodByDay($day){
		$ret = self::findFirst(['open_date <='.$day.' and close_date >='.$day]);
		if(!$ret){
			return false;
		}
		return $ret->period;
	}
}