<?php
/**
 * 磨坊
 *
 *
 *
 *
 */
class Mill extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('item');
		});
		return $ret;
	} 
}
