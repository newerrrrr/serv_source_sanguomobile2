<?php
//黄巾起义
class GuildHuangjin extends ModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		//$this->clearDataCache();
	}

	public function add($guildId){
		$o = new self;
		$ret = $o->create(array(
			'guild_id' => $guildId,
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
	
	public function reset($guildId, $round, $rowversion){
		return $this->updateAll([
			'status'=>1, 
			'round'=>$round,
			'score'=>0,
			'lost_times'=>0,
			'last_wave'=>0,
			'last_win_wave'=>0,
			'history_top_wave'=>'greatest(history_top_wave,top_wave)',
			'top_wave'=>0,
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion' => "'".uniqid()."'",
		], ['guild_id'=>$guildId, 'rowversion'=>'"'.$rowversion.'"']);
	}
	
	public function updateWin($guildId, $wave, $addScore, $is100){
		$d = $this->findFirst(['guild_id='.$guildId])->toArray();
		return $this->updateAll([
			'score'=>'score+'.$addScore,
			'last_wave'=>$wave,
			'last_win_wave'=>$wave,
			'top_wave'=>($is100 ? $wave : 'top_wave'),
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion' => "'".uniqid()."'",
		], ['guild_id'=>$guildId, 'rowversion'=>'"'.$d['rowversion'].'"']);
	}
	
	public function updateLose($guildId, $wave, $addScore){
		$d = $this->findFirst(['guild_id='.$guildId])->toArray();
		$ret = $this->updateAll([
			'score'=>'score+'.$addScore,
			'lost_times'=>'lost_times+1',
			'last_wave'=>$wave,
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion' => "'".uniqid()."'",
		], ['guild_id'=>$guildId, 'rowversion'=>'"'.$d['rowversion'].'"']);
		if($d['lost_times'] >= 1){
			$ret = $this->end($guildId);
		}
		return $ret;
	}
	
	public function end($guildId){
		return $this->updateAll([
			'status'=>2, 
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion' => "'".uniqid()."'",
		], ['guild_id'=>$guildId]);
	}
}