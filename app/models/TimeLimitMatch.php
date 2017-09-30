<?php
/**
 * 限时比赛 字典表
 */
class TimeLimitMatch extends ModelBase{
	
	public function parseColumn($_r){
		$_r['drop_id'] = parseArray($_r['drop_id']);
		$_r['rank_drop_id'] = parseArray($_r['rank_drop_id']);
		unset($__r);
		return $_r;
	}
}