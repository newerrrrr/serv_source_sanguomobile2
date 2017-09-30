<?php
class CountryScience extends ModelBase{
	//获取所有科技
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
		$_r['buff'] = parseGroup($_r['buff']);
		//$_r['drop'] = parseArray($_r['drop']);
		//$_r['exp'] = parseArray($_r['exp']);
		//$_r['honor'] = parseArray($_r['honor']);
		return $_r;
	}
	
	//根据type和level获取
	public function getByScienceType($type, $level=1){
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = $type.'_'.$level;
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->findFirst(array('science_type='.$type.' and level='.$level));
			if($ret){
				$ret = $ret->toArray();
				$ret = $this->parseColumn($ret);
				Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
			}
		}
		return $ret;
	}
}