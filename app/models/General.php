<?php
class General extends ModelBase{
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
		//$_r['general_combat_skill'] = parseArray($_r['general_combat_skill']);
		$_r['general_duel_skill'] = parseArray($_r['general_duel_skill']);
		$_r['general_talent_buff_id'] = parseArray($_r['general_talent_buff_id']);
		return $_r;
	}
	//根据general_original_id和level获取武将
	public function getByGeneralId($id, $level=1){
		$level = 1;
		$data['id'] = $id;
		$data['level'] = $level;
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = $id.'_'.$level;
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->findFirst(array('general_original_id='.$data['id'].' and general_level_id='.$data['level']));
			if($ret){
				$ret = $ret->toArray();
				$ret = $this->parseColumn($ret);
				Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
			}
		}
		return $ret;
	}
	
	public function getAllByOriginId(){
		$ret = $this->dicGetAll();
		$data = [];
		foreach($ret as $_r){
			$data[$_r['general_original_id']] = $_r;
		}
		return $data;
	}
	
	//获取所有武将ids
	public function getAllOriginalIds(){
		$ret = Set::extract('/id', $this->dicGetAll());
		return $ret;
	}

	public function isGod($generalId){
		if($generalId < 20000)
			return true;
		return false;
	}
	
	public function getBySameRoot($generalId){
		$general = $this->getByGeneralId($generalId);
		$ids = [];
		$ret = $this->find(['root_id='.$general['root_id']])->toArray();
		foreach($ret as $_r){
			$ids[] = $_r['general_original_id'];
		}
		return $ids;
	}
	
	public function getRootId($generalId){
		return substr($generalId, 1)*1;
	}
	
	public function getAllGodGeneralIds(){
		return array_keys($this->findList('general_original_id', null, ['general_quality=6 and condition>0']));
	}
}