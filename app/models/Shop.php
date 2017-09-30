<?php
/**
 * 商店
 *
 */
class Shop extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('id');
		});
		return $ret;
	}
	
	
}