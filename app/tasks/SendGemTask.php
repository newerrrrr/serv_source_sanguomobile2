<?php
/**
 * 发送元宝
 */
class SendGemTask extends \Phalcon\CLI\Task
{
    public function mainAction() {
    	$Player = new Player;
    	$Player->updateAll(['gift_gem'=>"gift_gem+100000"], ['id <>'=>0]);
    	Cache::clearAllCache();
    	echo "ok\n";
    }
}