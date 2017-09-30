<?php
/**
 * 管理端用户
 */
class AdminUser extends ModelBase{
	const AUTH_SECRET = 'sg2deadminsecret';
	
	public function initialize(){
        $this->setConnectionService('db_login_server');
    }
	
	public function add($name, $auth){
		if($this->findFirst(['name="'.$name.'"']))
			return false;
		$self = new self;
		$self->name = $name;
		$self->password = $this->encodePassword('123456');
		$self->auth = $auth;
		$self->pwd_status = 0;
		$self->status = 0;
		$self->create_time   = date("Y-m-d H:i:s");
		return $self->save();
	}
	
	public function checkUser($name, $password){
		$au = self::findFirst(['name="'.$name.'" and password="'.$this->encodePassword($password).'"']);
		if(!$au){
			return false;
		}
		return true;
	}
	
	public function getUser($name){
		$au = self::findFirst(['name="'.$name.'" and status=0']);
		if(!$au){
			return false;
		}
		$au = $au->toArray();
		$AdminAuth = new AdminAuth;
		$auth = $AdminAuth->findFirst($au['auth']);
		if($auth){
			$au['auth'] = $auth->toArray();
			if($au['auth']['auth'] == '0'){
				$au['auth']['auth'] = true;
			}elseif($au['auth']['auth'] == ''){
				$au['auth']['auth'] = [];
			}else{
				$au['auth']['auth'] = explode(',', $au['auth']['auth']);
			}
		}else{
			$au['auth'] = [];
		}
		return $au;
	}
	
	public function modifyPwd($name, $oldpassword, $password){
		$au = $this->updateAll(['password'=>'"'.$this->encodePassword($password).'"', 'pwd_status'=>1], ['name'=>"'".$name."'", 'password'=>"'".$this->encodePassword($oldpassword)."'"]);
		if(!$au){
			return false;
		}
		return true;
	}
	
	public function encodePassword($password){
		return md5($password . self::AUTH_SECRET);
	}
}