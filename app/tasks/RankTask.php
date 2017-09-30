<?php
/**
 * 排行榜
 * 每天凌晨
 *
 *
 *
 */
class RankTask extends \Phalcon\CLI\Task{
    /**
     * bootstrap
     * @return [type] [description]
     */
    public function mainAction($param=array()){
		echo "playerPower:\r\n";
		$t1 = time();
		$d1 = $this->playerPowerAction();
		echo (time()-$t1)."s\r\n";
		$t1 = time();
		echo "playerLevel:\r\n";
		$d2 = $this->playerLevelAction();
		echo (time()-$t1)."s\r\n";
		$t1 = time();
		echo "playerCity:\r\n";
		$d3 = $this->playerCityAction();
		echo (time()-$t1)."s\r\n";
		$t1 = time();
		echo "playerKill:\r\n";
		$d4 = $this->playerKillAction();
		echo (time()-$t1)."s\r\n";
		$t1 = time();
		echo "guildPower:\r\n";
		$d5 = $this->guildPowerAction();
		echo (time()-$t1)."s\r\n";
		$t1 = time();
		echo "guildKill:\r\n";
		$d6 = $this->guildKillAction();
		echo (time()-$t1)."s\r\n";
		$t1 = time();
		
		//放入缓存
		/*$Rank = new Rank;
		Cache::db()->set('RankPlayerPower', $d1);
		Cache::db()->set('RankPlayerLevel', $d2);
		Cache::db()->set('RankPlayerCity', $d3);
		Cache::db()->set('playerKill', $d1);
		Cache::db()->set('RankGuildPower', $d5);
		Cache::db()->set('guildKill', $d6);*/
    }
	
	public function playerPowerAction(){
		$type = 1;
		$Rank = new Rank;
		$Rank->sqlExec('delete from '.$Rank->getSource().' where type = '.$type);
		
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$Rank->sqlExec('set @rowNum:=0');
		$Rank->sqlExec('insert into '.$Rank->getSource().' (gpd, type, rank, value, camp_id, create_time) (select id, '.$type.', @rowNum:=@rowNum+1, power, camp_id, now() from player order by power desc limit 50)');
		$Rank->sqlExec('update '.$Rank->getSource().' r set name=(select nick from player p where p.id=r.gpd),avatar=(select avatar_id from player p where p.id=r.gpd),guild_id=(select guild_id from player_guild p where p.player_id=r.gpd)*1 where type = '.$type);
		$Rank->sqlExec('update '.$Rank->getSource().' r set guild_name=(select short_name from guild p where p.id=r.guild_id) where type = '.$type.' and guild_id > 0');
		//$Player = new Player;
		//$Player->find(['order'=>'power desc', 'limit'=>50])->toArray();
		
		//commit
		dbCommit($db);
		Cache::db()->delete('Rank:'.$type);
		//return $Rank->find(['type='.$type])->toArray();
    }

	public function playerLevelAction(){
		$type = 2;
		$Rank = new Rank;
		$Rank->sqlExec('delete from '.$Rank->getSource().' where type = '.$type);
		
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$Rank->sqlExec('set @rowNum:=0');
		$Rank->sqlExec('insert into '.$Rank->getSource().' (gpd, type, rank, value, camp_id, create_time) (select id, '.$type.', @rowNum:=@rowNum+1, level, camp_id, now() from player order by level desc,current_exp desc,levelup_time asc limit 50)');
		$Rank->sqlExec('update '.$Rank->getSource().' r set name=(select nick from player p where p.id=r.gpd),avatar=(select avatar_id from player p where p.id=r.gpd),guild_id=(select guild_id from player_guild p where p.player_id=r.gpd)*1 where type = '.$type);
		$Rank->sqlExec('update '.$Rank->getSource().' r set guild_name=(select short_name from guild p where p.id=r.guild_id) where type = '.$type.' and guild_id > 0');
		
		//commit
		dbCommit($db);
		Cache::db()->delete('Rank:'.$type);
		//return $Rank->find(['type='.$type])->toArray();
    }
	
	public function playerKillAction(){
		$type = 3;
		$Rank = new Rank;
		$Rank->sqlExec('delete from '.$Rank->getSource().' where type = '.$type);
		
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$Rank->sqlExec('set @rowNum:=0');
		$Rank->sqlExec('insert into '.$Rank->getSource().' (gpd, type, rank, value, camp_id, create_time) (select id, '.$type.', @rowNum:=@rowNum+1, kill_soldier_num, camp_id, now() from player order by kill_soldier_num desc limit 50)');
		$Rank->sqlExec('update '.$Rank->getSource().' r set name=(select nick from player p where p.id=r.gpd),avatar=(select avatar_id from player p where p.id=r.gpd),guild_id=(select guild_id from player_guild p where p.player_id=r.gpd)*1 where type = '.$type);
		$Rank->sqlExec('update '.$Rank->getSource().' r set guild_name=(select short_name from guild p where p.id=r.guild_id) where type = '.$type.' and guild_id > 0');
		
		//commit
		dbCommit($db);
		Cache::db()->delete('Rank:'.$type);
		//return $Rank->find(['type='.$type])->toArray();
    }
	
	public function playerCityAction(){
		$type = 4;
		$Rank = new Rank;
		$Rank->sqlExec('delete from '.$Rank->getSource().' where type = '.$type);
		
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$Rank->sqlExec('set @rowNum:=0');
		$Rank->sqlExec('insert into '.$Rank->getSource().' (gpd, type, rank, value, create_time) (select player_id, '.$type.', @rowNum:=@rowNum+1, build_level, now() from player_build where origin_build_id=1 order by build_level desc,build_finish_time asc limit 50)');
		$Rank->sqlExec('update '.$Rank->getSource().' r set name=(select nick from player p where p.id=r.gpd),avatar=(select avatar_id from player p where p.id=r.gpd),guild_id=(select guild_id from player_guild p where p.player_id=r.gpd)*1 where type = '.$type);
		$Rank->sqlExec('update '.$Rank->getSource().' r set guild_name=(select short_name from guild p where p.id=r.guild_id), camp_id=(select camp_id from guild p2 where p2.id=r.guild_id) where type = '.$type.' and guild_id > 0');
		
		//commit
		dbCommit($db);
		Cache::db()->delete('Rank:'.$type);
		//return $Rank->find(['type='.$type])->toArray();
    }
	
	public function guildPowerAction(){
		$type = 5;
		$Rank = new Rank;
		$Rank->sqlExec('delete from '.$Rank->getSource().' where type = '.$type);
		
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$Rank->sqlExec('set @rowNum:=0');
		$Rank->sqlExec('insert into '.$Rank->getSource().' (gpd, type, rank, value, camp_id, create_time) (select id, '.$type.', @rowNum:=@rowNum+1, guild_power, camp_id, now() from guild order by guild_power desc limit 50)');
		$Rank->sqlExec('update '.$Rank->getSource().' r set name=(select name from guild p where p.id=r.gpd),avatar=(select icon_id from guild p where p.id=r.gpd) where type = '.$type);
		
		//commit
		dbCommit($db);
		Cache::db()->delete('Rank:'.$type);
		//return $Rank->find(['type='.$type])->toArray();
    }
	
	public function guildKillAction(){
		$type = 6;
		$Rank = new Rank;
		$Rank->sqlExec('delete from '.$Rank->getSource().' where type = '.$type);
		
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$Rank->sqlExec('set @rowNum:=0');
		$Rank->sqlExec('insert into '.$Rank->getSource().' (gpd, type, rank, value, camp_id, create_time) (select id, '.$type.', @rowNum:=@rowNum+1, kill_soldier_num, camp_id, now() from guild order by kill_soldier_num desc limit 50)');
		$Rank->sqlExec('update '.$Rank->getSource().' r set name=(select name from guild p where p.id=r.gpd),avatar=(select icon_id from guild p where p.id=r.gpd) where type = '.$type);
		
		//commit
		dbCommit($db);
		Cache::db()->delete('Rank:'.$type);
		//return $Rank->find(['type='.$type])->toArray();
    }
}
