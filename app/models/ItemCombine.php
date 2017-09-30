<?php
/**
 * 道具合成配方
 */
class ItemCombine extends ModelBase{
	//获取所有天赋
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				$_r['consume'] = explode(';', $_r['consume']);
				foreach($_r['consume'] as &$__r){
					$__r = explode(',', $__r);
				}
				unset($__r);
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
}