<?php
/**
 * 激活码
 */
class Cdk extends ModelBase{
	
	public function initialize(){
        $this->setConnectionService('db_login_server');
    }
	
	public function add($cdk, $type, $lang, $channel, $drop, $beginTime, $endTime, $memo){
		$o = new self;
		$ret = $o->create(array(
			'cdk' => $cdk,
			'type' => $type,
			'lang' => $lang,
			'channel' => $channel,
			'drop' => $drop,
			'count' => 0,
			'memo' => $memo,
			'status' => 0,
			'begin_time' => $beginTime,
			'end_time' => $endTime,
			'create_time' => date('Y-m-d H:i:s'),
			'update_time' => date('Y-m-d H:i:s'),
			'rowversion' => uniqid(),
		));
	}
	
	public function generateCdk($pre){
		$ar = ['1','2','3','4','5','6','7','8','9','q','w','e','r','t','y','u','p','a','s','d','f','g','h','j','k','z','x','c','v','b','n','m','Q','W','E','R','T','Y','U','P','A','S','D','F','G','H','J','K','Z','X','C','V','B','N','M'];
		$len = count($ar);
		$i = 0;
		$str = $pre;
		while($i < 10){
			$str .= $ar[rand(0, $len-1)];
			$i++;
		}
		return $str;
	}
	
	public function updateUse($cdk, $playerId, $type, $rowversion){
		$data = [
			'count'=>'count+1',
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion'=>"'".uniqid()."'",
		];
		$condition = [
			'cdk'=>"'".$cdk."'",
			'rowversion'=>"'".$rowversion."'",
		];
		if($type){//非通用
			$data['status'] = 1;
			$data['player_id'] = $playerId;
		}
		return $this->updateAll($data, $condition);
	}
}