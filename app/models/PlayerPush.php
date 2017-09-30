<?php
//推送
class PlayerPush extends ModelBase{
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId, $type, $code=0, $param=[], $txt='', $sendTime=''){
		if(!$sendTime)
			$sendTime = date('Y-m-d H:i:s');
		$o = new self;
		$ret = $o->create(array(
			'player_id' => $playerId,
			'type' => $type,
			'txt' => $txt,
			'code' => $code,
			'param' => json_encode($param),
			'send_time' => $sendTime,
			'create_time' => date('Y-m-d H:i:s'),
		));
		return $o->id;
	}
	
	public function updateSendTime($id, $sendTime){
		return $this->updateAll(['send_time'=>"'".$sendTime."'"], ["id"=>$id]);
	}

	public function del($id){
		$this->sqlExec('delete from '.$this->getSource().' where id='.$id);
	}
}