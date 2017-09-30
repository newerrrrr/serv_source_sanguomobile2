<?php
class AllianceShop extends ModelBase{
	//获取所有科技
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('item_id');
		});
		return $ret;
	}
	
}