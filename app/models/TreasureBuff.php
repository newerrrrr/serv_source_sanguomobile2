<?php
/**
 * 和氏璧buff
 */
class TreasureBuff extends ModelBase{
	public function parseColumn($_r){
		$_r['buff_temp_id'] = parseArray($_r['buff_temp_id']);
		return $_r;
	}
}