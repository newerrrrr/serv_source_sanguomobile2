<?php
/**
 *武将经验等级
 *
 */
class GeneralSkillLevelup extends ModelBase{
	public function getMaxExp(){
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = 'generalSkillMaxExp';
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->maximum(array('column'=>'general_skill_exp'));
			Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
		}
		return $ret;
	}
	
	public function getMaxLv(){
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = 'generalSkillMaxLv';
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->maximum(array('column'=>'id'));
			Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
		}
		return $ret;
	}
}