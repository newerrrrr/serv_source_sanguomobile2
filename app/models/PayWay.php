<?php
class PayWay extends ModelBase{
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
		$_r['pay_way'] = parseArray($_r['pay_way']);
		$_r['pay_way_lv'] = parseArray($_r['pay_way_lv']);
		return $_r;
	}
	
	public function getChannelByPayway($payWay, $downloadChannel){
		$data = $this->dicGetAll();
		foreach($data as $_d){
			if(in_array($payWay, $_d['pay_way']) && $downloadChannel == $_d['channel']){
				return $_d['id'];
			}
		}
		return false;
	}
	
}