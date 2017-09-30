<?php
class HuangjinAttackMob extends ModelBase{
	//获取所有波次数据
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
		$_r['type_and_count'] = parseGroup($_r['type_and_count'], false);
		return $_r;
	}
	
	public function getMaxWave(){
		$data = $this->dicGetAll();
		return max(array_keys($data));
	}
	
}