<?php
/**
 * 武将战斗技能
 *
 */
class CombatSkill extends ModelBase{
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
		$_r['target'] = parseArray($_r['target']);
		return $_r;
	}
	
	public function getSkillValue($id, $skilllevel, $attr){
		$data = $this->dicGetOne($id);
		if(!$data)
			return ['type'=>0];
		$base = $data['base'];
		$para1 = $data['para1'];
		$para2 = $data['para2'];
		eval('$ret = '.$data['server_formula'].';');
		/*
		switch($data['type']){
			case 1:
				$ret = ($data['base'] + $skilllevel * $data['para1'] + max($attr['force'], $attr['intelligence']) * $data['para2']) / 10000;
			break;
			case 2:
				$ret = 1 - (1 - $skilllevel * $data['para1'] / 10000) * (1 - $attr['governing'] * $data['para2'] / 10000) * (1 - $data['base'] / 10000);
			break;
			case 3:
				$ret = $data['base'] + $skilllevel * max($attr['force'], $attr['intelligence']) * $data['para1'];
			break;
			case 4:
				$ret = 1 - (1 - $skilllevel * $data['para1'] / 10000) * (1 - $attr['governing'] * $data['para2'] / 10000) * (1 - $data['base'] / 10000);
			break;
			case 5:
				$ret = ($data['base'] + $skilllevel * $data['para1'] + max($attr['force'], $attr['intelligence']) * $data['para2']) / 10000;
			break;
			case 6:
				$ret = $data['base'] + $skilllevel * max($attr['force'], $attr['intelligence']) * $data['para1'];
			break;
			case 7:
				$ret = 1 - (1 - $skilllevel * $data['para1'] / 10000) * (1 - $attr['governing'] * $data['para2'] / 10000) * (1 - $data['base'] / 10000);
			break;
			case 8:
				$ret = 1 - (1 - $skilllevel * $data['para1'] / 10000) * (1 - $attr['governing'] * $data['para2'] / 10000) * (1 - $data['base'] / 10000);
			break;
			default:
				$ret = 0;
		}*/
		return ['type'=>$data['type'], 'target'=>$data['target'], 'value'=>$ret];
	}
}
