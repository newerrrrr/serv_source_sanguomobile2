<?php
/**
 * 更新联盟战力脚本
 * php cli.php guild_power
 */
class GuildPowerTask extends \Phalcon\CLI\Task {
	/**
	 * main action
	 */
    public function mainAction(){
		$Guild = new Guild;
		$db = $this->di['db'];
		$sql = "select id from guild";
		$result = $db->query($sql);
		foreach($result->fetchAll() as $v) {
			$guildId = $v['id'];
			$re = $db->query("select sum(power) guild_power, sum(kill_soldier_num) kill_soldier_num from player where guild_id={$guildId}");
			$re = $re->fetch();
			if(!is_null($re['guild_power'])) {
				$Guild->alter($guildId, ['guild_power'=>$re['guild_power'], 'kill_soldier_num'=>$re['kill_soldier_num']]);
				echo ".";
			} else {
				continue;
			}
		}
		echo "\nok!\n";
    }
}