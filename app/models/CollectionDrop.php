<?php
/**
 * 采集掉落道具
 *
 */
class CollectionDrop extends ModelBase{
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
		$_r['collection_drop'] = parseArray($_r['collection_drop']);
		return $_r;
	}
}
