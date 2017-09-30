<?php
/**
 * 清除过期邮件
 * 每天凌晨
 *
 *
 *
 */
class ClearTask extends \Phalcon\CLI\Task{
    /**
     * bootstrap
     * @return [type] [description]
     */
    public function mailAction($param=array()){
		
		$date = date('Y-m-d 00:00:00', time());
		
		$PlayerMail = new PlayerMail;
		//$PlayerMail->sqlExec('delete from '.$PlayerMail->getSource().' where expire_time<"'.$date.'"');
		$PlayerMail->sqlExec('delete from '.$PlayerMail->getSource().' where expire_time<"'.$date.'" and status < 1');
		
		$PlayerMailInfo = new PlayerMailInfo;
		//$PlayerMailInfo->sqlExec('delete from '.$PlayerMailInfo->getSource().' where expire_time<"'.$date.'"');
		$PlayerMailInfo->sqlExec('delete from '.$PlayerMailInfo->getSource().' where expire_time<"'.$date.'" and id not in (select mail_info_id from '.$PlayerMail->getSource().' where expire_time<"'.$date.'" and status = 1)');
    }
	
	public function queueAction($param=array()){
		
		$date = date('Y-m-d 00:00:00', time() - 3*24*3600);
		
		$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerProjectQueue->sqlExec('delete from '.$PlayerProjectQueue->getSource().' where status>1 and create_time<"'.$date.'"');
    }
	
	public function buffAction($param=array()){
		
		$date = date('Y-m-d 00:00:00', time());
		
		$PlayerBuffTemp = new PlayerBuffTemp;
		$PlayerBuffTemp->sqlExec('delete from '.$PlayerBuffTemp->getSource().' where expire_time<"'.$date.'"');
		
    }

    /**
     * 清除player_mission老数据
     *
     * #清理player_mission
     * 0 12 * * * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php clear mission
     */
    public function missionAction(){
        $date = date('Y-m-d 00:00:00', time()-604800);//7*24*60*60
        $PlayerMission = new PlayerMission;
        $FINISH     = PlayerMission::FINISH;
        $DAILY_EXPIRE = PlayerMission::DAILY_EXPIRE;
        $mainMissionType = PlayerMission::getMainMissionTypeStr();
        $sql = "delete from player_mission where (status={$FINISH} or status={$DAILY_EXPIRE}) and mission_type not in ({$mainMissionType}) and create_time <='{$date}'";
        $PlayerMission->sqlExec($sql);
    }
	
	public function commonlogAction($param=array()){
		
		$date = date('Y-m-d 00:00:00', time() - 2*30*24*3600);
		
		$PlayerCommonLog = new PlayerCommonLog;
		$PlayerCommonLog->sqlExec('delete from '.$PlayerCommonLog->getSource().' where create_time<"'.$date.'"');
    }
}
