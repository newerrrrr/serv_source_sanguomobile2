<?php
/**
 *武将星级
 *
 */
class GeneralStar extends ModelBase{
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
		//$_r['consume'] = parseGroup($_r['consume'], false);
		return $_r;
	}

	public function getByGeneralId($id, $level=0){
		$data['id'] = $id;
		$data['star'] = $level;
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = $id.'_'.$level;
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->findFirst(array('general_original_id='.$data['id'].' and star='.$data['star']));
			if($ret){
				$ret = $ret->toArray();
				$ret = $this->parseColumn($ret);
				Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
			}
		}
		return $ret;
	}
}