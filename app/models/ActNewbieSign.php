<?php
class ActNewbieSign extends ModelBase{
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
			$ret = $this->maximum(array('column'=>'id'));
			Cache::db(CACHEDB_STATIC)->set($cacheName1, $ret);
		}
		return $ret;
	}
}