<?php
//留存/付费快照
class StatSnapshot extends ModelBase{
	public function add($dt, $type, $channel, $data){
		$createData = [
			'dt'=>$dt,
			'type'=>$type,
			'channel'=>$channel
		];
		$createData = array_merge($createData, $data);
		$o = new self;
		$ret = $o->create($createData);
	}
		
}