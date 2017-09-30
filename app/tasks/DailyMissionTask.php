<?php
class DailyMissionTask extends \Phalcon\CLI\Task {
	/**
	 * 生成每日任务
	 */
    public function mainAction() {
		$Player = new Player;
		$PlayerMission = new PlayerMission;
		$mainMissionType = PlayerMission::getMainMissionTypeStr();
		//case a 把之前的每日任务置灰
		echo '[INFO]start-'.date('Y-m-d H:i:s');
		$currentDate = date('Y-m-d');
		$PlayerMission->sqlExec("update player_mission set status=" . PlayerMission::DAILY_EXPIRE . " where status=" . PlayerMission::START . " and mission_type not in ({$mainMissionType}) and date_limit <> '{$currentDate}';");//0=>4
		echo "ok\n";
    }
}