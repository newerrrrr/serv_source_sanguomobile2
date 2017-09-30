<?php
class Talent extends ModelBase{
	//获取所有天赋
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				if($_r['condition_talent']){
					$_r['condition_talent'] = explode(';', $_r['condition_talent']);
				}else{
					$_r['condition_talent'] = array();
				}
				$_r = $this->parseColumn($_r);
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
	public function parseColumn($_r){
		$_r['talent_drop'] = parseArray($_r['talent_drop']);
		return $_r;
	}
}