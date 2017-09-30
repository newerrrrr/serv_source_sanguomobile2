<?php
/**
 * 管理端等级
 */
class AdminAuth extends ModelBase{
	public function initialize(){
        $this->setConnectionService('db_login_server');
    }
	
	public function add($name, $auth){
		$self = new self;
		$self->name = $name;
		$self->auth = join(',', $auth);
		return $self->save();
	}
}