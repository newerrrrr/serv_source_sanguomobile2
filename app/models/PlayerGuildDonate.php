<?php
//玩家联盟捐献
class PlayerGuildDonate extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		$setCache = false;
		$playerGuildDonate = Cache::getPlayer($playerId, __CLASS__);
        if(!$playerGuildDonate || ($playerGuildDonate['status'] && strtotime($playerGuildDonate['finish_time']) <= time())) {
            $playerGuildDonate = self::findFirst(["player_id={$playerId}"]);
			if(!$playerGuildDonate)
				return false;
			if($playerGuildDonate->status && strtotime($playerGuildDonate->finish_time) <= time()){
				if(!$this->saveAll(array(
					'status' => 0,
					'update_time'=>date('Y-m-d H:i:s'),
					'rowversion'=>uniqid()
				), "id={$playerGuildDonate->id} and rowversion='{$playerGuildDonate->rowversion}'")){
					return false;
				}
				$playerGuildDonate = self::findFirst(["player_id={$playerId}"]);
			}
			$playerGuildDonate = $playerGuildDonate->toArray();
			$setCache = true;
        }
		$today = date('Y-m-d');
		if($today != $playerGuildDonate['reward_time']){
			$this->updateDonateReward($playerId, [], $today);
			$playerGuildDonate['donate_reward'] = '';
			$playerGuildDonate['reward_time'] = $today;
			$setCache = true;
		}
		if($setCache){
			//$playerGuildDonate['button'] = json_decode($playerGuildDonate['button'], true);
            Cache::setPlayer($playerId, __CLASS__, $playerGuildDonate);
			
		}
		if($playerGuildDonate['donate_reward']){
			$playerGuildDonate['donate_reward'] = explode(',', $playerGuildDonate['donate_reward']);
		}else{
			$playerGuildDonate['donate_reward'] = [];
		}
		$playerGuildDonate = $this->adapter($playerGuildDonate, true);
        if($forDataFlag) {
            return filterFields(array($playerGuildDonate), $forDataFlag, $this->blacklist)[0];
        } else {
            return $playerGuildDonate;
        }
    }
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId, $button, $status, $finishTime){
		if($this->find(array('player_id='.$playerId))->toArray()){
			return false;
		}
		$ret = $this->create(array(
			'player_id' => $playerId,
			//'button' => json_encode($button),
			'status' => $status,
			'finish_time' => $finishTime,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
		
	public function updateData($status, $finishTime){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			//'button' => json_encode($button),
			'status' => $status,
			'finish_time' => $finishTime,
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		if(!$ret || !$this->affectedRows())
			return false;
		return true;
	}
	
	public function updateDonateTime($playerId, $date){
		$ret = $this->updateAll(['last_donate_time'=>"'".$date."'"], ['player_id'=>$playerId]);
		$this->clearDataCache($playerId);
		return $ret;
	}
	
	public function updateDonateReward($playerId, $reward, $date){
		$ret = $this->updateAll(['reward_time'=>"'".$date."'", 'donate_reward'=>"'".join(',', $reward)."'"], ['player_id'=>$playerId]);
		$this->clearDataCache($playerId);
		return $ret;
	}
}