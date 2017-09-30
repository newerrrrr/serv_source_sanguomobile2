<?php
/**
 *武将经验等级
 *
 */
class GeneralExp extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('general_level', 'general_exp');
		});
		return $ret;
	}
	
	public function exp2lv($exp){
		$data = $this->dicGetAll();
		$lastLevel = 1;
		foreach($data as $_level => $_exp){
			if($exp > $_exp){
				$lastLevel = $_level;
			}elseif($exp == $_exp){
				$level = $_level;
				break;
			}else{
				$level = $lastLevel;
				break;
			}
		}
		if(!isset($level)){
			$level = $lastLevel;
		}
		return $level;
	}
	
	public function lv2exp($lv){
		$data = $this->dicGetAll();
		return $data[$lv];
	}
	
	public function getMaxExp(){
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = 'generalMaxExp';
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->maximum(array('column'=>'general_exp'));
			Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
		}
		return $ret;
	}
	
	public function getMaxLv(){
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = 'generalMaxLv';
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->maximum(array('column'=>'general_level'));
			Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
		}
		return $ret;
	}
}