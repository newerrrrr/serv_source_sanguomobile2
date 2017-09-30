<?php
class ActivityOrder extends ModelBase{
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
		$_r['series'] = parseArray($_r['series']);
		return $_r;
	}
	
	public function getNext($id){
		if(!$id){
			$ret = $this->find(['order'=>'id'])->toArray();
		}else{
			$ret = $this->find(['id='.($id+1)])->toArray();
			if(!$ret){
				$ret = $this->find(['if_circle=1', 'order'=>'id'])->toArray();
			}
		}
		$ret = $ret[0];
		$ret = $this->parseColumn($ret);
		return $ret;
	}
}