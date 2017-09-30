<?php
/**
 * 跨服配置
 *
 */
class WarfareServiceConfig extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('name', 'data');
		});
		return $ret;
	}

	public function getValueByKey($key){
		$ret = $this->dicGetAll();
		return $ret[$key];
	}
}
