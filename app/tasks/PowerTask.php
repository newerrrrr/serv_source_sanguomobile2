<?php
/**
 * 重算玩家战力
 * 手动执行
 *
 *
 *
 */
class PowerTask extends \Phalcon\CLI\Task{
    /**
     * bootstrap
     * @return [type] [description]
     */
    public function resetAction($param=array()){
		set_time_limit(0);
		$time = time();
		
		$_playerId = 0;
		$row = 10;
		$Player = new Player;
		$Power = new Power;
		while($_data = $Player->sqlGet('select id from player where id>'.$_playerId.' order by id limit '.$row)){
			foreach($_data as $_d){
				$playerId = $_d['id'];
				$Player->sqlExec('update player set
					master_power='.$Power->getMaster($playerId).', 
					general_power='.$Power->getGeneral($playerId).',
					army_power='.$Power->getSoldier($playerId).',
					build_power='.$Power->getBuilding($playerId).',
					science_power='.$Power->getScience($playerId).',
					trap_power='.$Power->getTrap($playerId).',
					power=(@power2:=master_power+general_power+army_power+build_power+science_power+trap_power)
					where id='.$_d['id'].' and @power:=power'
				);
				$r = $Player->sqlGet('select @power, @power2');
				$changePower = $r[0]['@power2'] - $r[0]['@power'];
				$Player->clearDataCache($playerId);
				
				if($changePower>0){
					$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
					$PlayerTimeLimitMatch->updateScore($playerId, 8, $changePower);
					$PlayerTarget = new PlayerTarget;
					$PlayerTarget->updateTargetCurrentValue($playerId, 7, $r[0]['@power2'], false);
				}
				echo $playerId.':'.$r[0]['@power'].'+'.$changePower.'='.$r[0]['@power2'].PHP_EOL;
			}
			$_playerId = $_data[count($_data)-1]['id'];
		}
		echo 'ok:'.(time() - $time).'s.';
    }
	
}
