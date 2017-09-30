<?php
class CrossPlayerMasterskill extends CrossModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public $battleId;
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function add($battleId, $playerId, $generalId, $data){
		$o = new self;
		$saveData = array(
            'battle_id'           => $battleId,
            'player_id'           => $playerId,
			'general_id'		  => $generalId,
            'create_time'         => date('Y-m-d H:i:s'),
		);
		$saveData = array_merge($saveData, $data);
		$ret = $o->create($saveData);
		if(!$ret)
			return false;
		return $o->affectedRows();
	}

	public function getBySkillId($playerId, $generalId, $skillId){
		$ret = $this->getByPlayerId($playerId);
		foreach ($ret as $key => $value) {
			if($generalId == $value['general_id'] && $skillId==$value['skill_id']){
				return $value;
			}
		}
		return false;
	}
	
	public function useTimes($playerId, $battleId, $generalId, $skillId, $needActive=0, $num=1){
		$ret = $this->updateAll([
			'rest_times'=>'rest_times-'.$num, 
			'active'=>$needActive,
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion'=>"'".uniqid()."'",
		], ['battle_id'=>$battleId, 'player_id'=>$playerId, 'general_id'=>$generalId, 'skill_id'=>$skillId, 'rest_times >='=>$num]);
		$this->clearDataCache($playerId);
		return $ret;
	}
	
	public function useActive($playerId, $battleId, $skillId, &$id=0){
		/*$ret = $this->updateAll([
			'active'=>0,
			'update_time'=>"'".date('Y-m-d H:i:s')."'",
			'rowversion'=>"'".uniqid()."'",
		], ['battle_id'=>$battleId, 'player_id'=>$playerId, 'skill_id'=>$skillId, 'active'=>1, '@id :='=>'id'], 'order by v1 desc limit 1');*/
		$ret = $this->sqlExec("UPDATE ".$this->getSource()." SET `active` = 0, `update_time` = '".date('Y-m-d H:i:s')."', `rowversion` = '".uniqid()."' WHERE `battle_id` = ".$battleId." AND `player_id` = ".$playerId." AND `skill_id` = ".$skillId." AND `active` = 1 AND @id := id order by v1 desc limit 1");
		$this->clearDataCache($playerId);
		if($ret){
			$id = $this->sqlGet('select @id')[0]['@id'];
		}
		return $ret;
	}
	
	public function reset(){
		return $this->updateAll(['rest_times'=>'all_times', 'active'=>0], ['battle_id'=>$this->battleId]);
	}
	
    /**
     * 先执行CrossPlayerGeneral的cpData
     * 
     * @param <type> $playerId 
     * @param <type> $battleId 
     * @param <type> $data [['generalId'=>xxx, 'skillId'=>yyy], ['generalId'=>xxx, 'skillId'=>yyy]...]
     * 
     * @return <type>
     */
	public function cpData($playerId, $battleId, $data){
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayerGeneral->battleId = $battleId;
		$this->find(["battle_id={$battleId} and player_id={$playerId}"])->delete();
		$this->clearDataCache($playerId);
		foreach($data as $_d){
			//获取武将
			$_general = $CrossPlayerGeneral->getByGeneralId($playerId, $_d['generalId']);
			if(!$_general) continue;
			$_skills = [$_general['cross_skill_id_1'], $_general['cross_skill_id_2'], $_general['cross_skill_id_3']];
			//锦囊:主动技可额外释放一次
			$_times = 1;
			if(in_array(1, $_skills)){
				$_times++;
				if($_d['generalId'] == 10110){//神诸葛亮可额外释放两次
					$_times++;
				}
			}
			foreach([1, 2, 3] as $_i){
				if($_d['skillId'] == $_general['cross_skill_id_'.$_i]){
					$this->add($battleId, $playerId, $_d['generalId'], [
						'skill_id'=>$_d['skillId'],
						'lv'=>$_general['cross_skill_lv_'.$_i],
						'all_times'=>$_times,
						'rest_times'=>$_times,
						'active'=>0,
						'v1'=>$_general['cross_skill_v1_'.$_i],
						'v2'=>$_general['cross_skill_v2_'.$_i],
					]);
				}
			}
		}
	}
}