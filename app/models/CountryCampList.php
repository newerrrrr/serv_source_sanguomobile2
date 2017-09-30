<?php
class CountryCampList extends ModelBase{
	public $cacheKeyByName = 'CountryCampList->ids';//cache key by name
    /**
     * 获取所有阵营id
     * @return array
     */
    public function dicGetAllId(){
		return [1, 2, 3];
        $ret = $this->cache($this->cacheKeyByName, function() {
            return $this->findList('id');
        });
        return $ret;
    }
}