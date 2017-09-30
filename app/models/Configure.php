<?php
/*
 * 配置
 *
 */
class Configure extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('key', 'value');
		});
		return $ret;
	}

	public function getValueByKey($key){
		$ret = $this->dicGetAll();
		if(isset($ret[$key]))
			return $ret[$key];
		return null;
	}

	public function countActivityPlayer($num){
		$Configure = new Configure;
		$c = Configure::findFirst("key='activity_player_count'");
		if($c){
            $c->value = $num;
            $c->save();
		}else{
			$Configure->key = 'activity_player_count';
			$Configure->value = $num;
			$Configure->save();
		}
		$cacheName = __CLASS__;
		$db = CACHEDB_STATIC;
		$ret = $this->findList('key', 'value');
		if(is_array($ret)){
			Cache::db($db)->hMset($cacheName, $ret);
		}
	}
}