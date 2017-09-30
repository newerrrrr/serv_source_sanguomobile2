<?php
/**
 * 充值配置
 *
 */
class Pricing extends ModelBase{
	
	public function getByPaymentCode($paymentCode){
		$ret = $this->findFirst(['payment_code="'.$paymentCode.'"']);
		if(!$ret)
			return false;
		return $ret->toArray();
	}
}
