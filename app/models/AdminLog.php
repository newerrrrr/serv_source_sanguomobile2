<?php
/**
 * 管理端日志
 */
class AdminLog extends ModelBase{
	public function initialize(){
        $this->setConnectionService('db_login_server');
    }
	
	public function add($name, $type, $memo=''){
		$o = new self;
		$ret = $o->create(array(
			'name' => $name,
			'type' => $type,
			'memo' => $memo,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return true;
	}
}