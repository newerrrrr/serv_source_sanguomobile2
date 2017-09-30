<?php

class MasterSkill extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('talent_id');
		});
		return $ret;
	} 
}
