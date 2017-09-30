<?php
class ActivityPlayerTask extends \Phalcon\CLI\Task{
    public function mainAction(){
    	$Player = new Player;
    	$Configure = new Configure;
    	$aPNum = Player::count(['uuid not like "Robot-%" and login_time >="'.date('Y-m-d 00:00:00').'"']);
		$Configure->countActivityPlayer($aPNum);
    }
}