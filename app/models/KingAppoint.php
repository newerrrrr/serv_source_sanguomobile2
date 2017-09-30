<?php
/**
 * 官职
 *
 */
class KingAppoint extends ModelBase{
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
		$_r['add_buff'] = parseArray($_r['add_buff']);
		return $_r;
	}
	
	public function cancelAppoint($player){
		$Drop = new Drop;
		//解析原job的drop的buffTempId
		$_kingAppoint = $this->dicGetOne($player['job']);
		if(!$_kingAppoint){
			return false;
		}
		$_dropId = $_kingAppoint['add_buff'];
		
		//删除bufftemp
		$buffTempIds = [];
		foreach($_dropId as $__dropId){
			$_drop = $Drop->dicGetOne($__dropId);
			foreach($_drop['drop_data'] as $_d){
				$buffTempIds[] = $_d[1];
			}
		}
		if($buffTempIds){
			(new PlayerBuffTemp)->delByTempId($player['id'], $buffTempIds);
		}
			//删除job
		(new Player)->alter($player['id'], ['job'=>0]);
		return true;
	}
}
