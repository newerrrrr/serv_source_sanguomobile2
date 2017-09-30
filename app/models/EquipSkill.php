<?php
/**
 * 装备附加属性字典表
 */
class EquipSkill extends ModelBase{
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
		$_r['skill_buff_id'] = parseArray($_r['skill_buff_id']);
		return $_r;
	}
}