<?php
/**
 * BOSS 血量掉落
 *
 *
 *
 *
 */
class BossNpcDrop extends ModelBase{
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
	
	public function getByDamage($npcId, $damage){
		$d = $this->findFirst(['npc_id='.$npcId.' and damage_min <='.$damage.' and damage_max>='.$damage]);
		if(!$d)
			return false;
		return $this->parseColumn($d->toArray());
	}
	
	public function parseColumn($_r){
		$_r['boss_drop'] = parseArray($_r['boss_drop']);
		return $_r;
	}
}