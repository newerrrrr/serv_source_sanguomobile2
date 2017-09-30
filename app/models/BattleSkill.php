<?php
/**
 * 跨服战技能
 *
 */
class BattleSkill extends ModelBase{
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
		$_r['general_limit'] = parseArray($_r['general_limit']);
		return $_r;
	}
	
    /**
     * 获取技能池
	 * filter:0-所有，1：仅被动
     */
	public function getPoolByGeneralId($generalId, $filter){
		$cacheName1 = __CLASS__ .'1'.$filter;
		$cacheName2 = $generalId;
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$all = $this->dicGetAll();
			$ret = [];
			foreach($all as $_a){
				if($filter && $_a['if_active']) continue;
				if(!$_a['general_limit'] || in_array($generalId, $_a['general_limit'])){
					$ret[$_a['id']] = $_a['refresh_weight']*1;
				}
			}
			Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
		}
		return $ret;
	}
}
