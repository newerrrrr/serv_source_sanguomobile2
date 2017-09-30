<?php
/**
 * 激活码礼包
 */
class CdkDrop extends ModelBase{
	
	public function initialize(){
        $this->setConnectionService('db_login_server');
    }
	
	public function add($name, $drop, $memo=''){
		$o = new self;
		$ret = $o->create(array(
			'name' => $name,
			'drop' => $drop,
			'memo' => $memo,
			'create_time' => date('Y-m-d H:i:s'),
		));
	}
}