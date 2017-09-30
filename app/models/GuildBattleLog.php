<?php
class GuildBattleLog extends ModelBase{
    /**
     * 获取帮助信息
     * 
     * @param   int    $playerId    player id
     * @return  array    description
     */
    public function getByPlayerId($playerId, $forDataFlag=false, $num=20, $types=[1, 2, 3, 6]){
        $player = (new Player)->getByPlayerId($playerId);
        $guildId = $player['guild_id'];
		//$types = [1, 2, 3, 6];
        if($guildId>0){
            $re = Cache::getGuild($guildId, __CLASS__);
			if(!$re)
				$re = [];
            if(!$re || !isset($re[join('_', $types)])) {
                $ret = self::find([
					"(attack_guild_id={$guildId} or defend_guild_id={$guildId}) and type in (".join(',', $types).")",
					'order'=>'id desc', 
					'limit'=>$num
				])->toArray();
                $ret = $this->adapter($ret);
				$re[join('_', $types)] = $ret;
                Cache::setGuild($guildId, __CLASS__, $re);
            }
        }else{
            $re = Cache::getPlayer($playerId, __CLASS__);
			if(!$re)
				$re = [];
            if(!$re || !isset($re[join('_', $types)])) {
                $ret = self::find([
					"(attack_player_id={$playerId} or defend_player_id={$playerId}) and type in (".join(',', $types).")", 
					'order'=>'id desc', 
					'limit'=>$num
				])->toArray();
                $ret = $this->adapter($ret);
				$re[join('_', $types)] = $ret;
                Cache::setPlayer($playerId, __CLASS__, $re);
            }
        }
        $re = @$re[join('_', $types)];
        return filterFields($re, $forDataFlag, $this->blacklist);
    }

    public function add($aPId, $aGId, $dPId, $dGId, $isWin, $battleType, $xyArr, $battleInfo){
        $Player = new Player;        
    	$GuildBattleLog = new GuildBattleLog;
		$GuildBattleLog->attack_player_id = $aPId;
		$GuildBattleLog->attack_guild_id = $aGId;
        $GuildBattleLog->attack_x = $xyArr['ax'];
        $GuildBattleLog->attack_y = $xyArr['ay'];
        $GuildBattleLog->defend_x = $xyArr['dx'];
        $GuildBattleLog->defend_y = $xyArr['dy'];
		$GuildBattleLog->defend_player_id = $dPId;
		$GuildBattleLog->defend_guild_id = $dGId;
		$GuildBattleLog->is_win = $isWin;

		$GuildBattleLog->a_list = implode("|", $battleInfo['aList']);
		$GuildBattleLog->a_lost_power = $battleInfo['aLostPower'];

		$GuildBattleLog->d_list = implode("|", $battleInfo['dList']);
		$GuildBattleLog->d_lost_power = $battleInfo['dLostPower'];

		$GuildBattleLog->rob_resouce = json_encode($battleInfo['robResource']);
		// $GuildBattleLog->a_list = "";
		// $GuildBattleLog->a_lost_power = 0;

		// $GuildBattleLog->d_list = "";
		// $GuildBattleLog->d_lost_power = 0;

		// $GuildBattleLog->rob_resouce = "";

		$GuildBattleLog->create_time = date("Y-m-d H:i:s");
        $GuildBattleLog->type = $battleType;
		//$GuildBattleLog->is_new = 1;
		$GuildBattleLog->save();

		Cache::delGuild($aGId, __CLASS__);
		Cache::delGuild($dGId, __CLASS__);
        Cache::delPlayer($aPId, __CLASS__);
        Cache::delPlayer($dPId, __CLASS__);

        return $GuildBattleLog->id;
    }
	
	public function updateDetail($id, $data){
		/*$ret = $this->updateAll(array(
			'detail'=>"'".gzcompress(json_encode($data))."'", 
		), ["id"=>$id]);*/
		$re = self::findFirst("id=$id");
        if(!$re)
			return false;
		$fields = [
			'detail'=>gzcompress(json_encode($data))
		];
        $re->save($fields);
		if($re->attack_player_id)
			Cache::delPlayer($re->attack_player_id, __CLASS__);
		if($re->defend_player_id)
			Cache::delPlayer($re->defend_player_id, __CLASS__);
		if($re->attack_guild_id)
			Cache::delGuild($re->attack_guild_id, __CLASS__);
		if($re->defend_guild_id)
			Cache::delGuild($re->defend_guild_id, __CLASS__);
		return true;
	}
	
	/*public function setNew($playerId, $isNew){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'is_new' => $isNew,
		), array("player_id"=>$playerId));
		$this->clearDataCache($playerId);
		return true;
	}*/
}