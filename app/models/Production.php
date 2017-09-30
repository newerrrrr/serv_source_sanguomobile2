<?php
/**
 * 建筑产出
 *
 */
class Production extends ModelBase{
    /**
     * 获取所有建筑产出
     * 
     * @return <type>
     */
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('build_id');
		});
		return $ret;
	}
	

}
