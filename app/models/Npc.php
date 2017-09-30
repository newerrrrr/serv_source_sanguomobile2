<?php
/**
 * NPC表
 */
class Npc extends ModelBase{
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
		$_r['drop'] = parseArray($_r['drop']);
		return $_r;
	}
}