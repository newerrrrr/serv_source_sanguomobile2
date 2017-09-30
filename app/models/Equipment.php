<?php
class Equipment extends ModelBase{
	//获取所有天赋
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				$_r = $this->parseColumn($_r);
				/*
				$_r['recast'] = explode(',', $_r['recast']);
				$_r['decomposition'] = explode(',', $_r['decomposition']);
				$_r['equip_skill_id'] = explode(',', $_r['equip_skill_id']);
				$_r['consume'] = explode(';', $_r['consume']);
				foreach($_r['consume'] as &$__r){
					$__r = explode(',', $__r);
				}
				unset($__r);*/
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
	//根据general_original_id和level获取
	public function getByOriginId($id, $level=0){
		$data['id'] = $id;
		$data['level'] = $level;
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = $id.'_'.$level;
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->findFirst(array('item_original_id='.$data['id'].' and star_level='.$data['level']));
			if($ret){
				$ret = $ret->toArray();
				$ret = $this->parseColumn($ret);
				Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
			}
		}
		return $ret;
	}
	
	public function parseColumn($_r){
		$_r['equip_skill_id'] = parseArray($_r['equip_skill_id']);
		$_r['recast'] = parseArray($_r['recast']);
		$_r['decomposition'] = parseArray($_r['decomposition']);
		$_r['consume'] = explode(';', $_r['consume']);
		foreach($_r['consume'] as &$__r){
			$__r = explode(',', $__r);
		}
		unset($__r);
		return $_r;
	}
}