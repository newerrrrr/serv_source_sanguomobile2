<?php
class Activity extends ModelBase{
	var $hsbDrop = [1, 11700, 1];
	//获取所有科技
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				$_r = $this->parseColumn($_r);
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
	public function parseColumn($_r){
		$_r['drop'] = parseArray($_r['drop']);
		return $_r;
	}
	
    /**
     * 联盟任务-捐献
     * 
     * 
     * @return <type>
     */
	public function addGuildMissionScore($playerId, $score){
		//$arScore = [1=>1, 2=>3, 3=>5];
		//检查是否在活动期内
		/*$ActivityConfigure = new ActivityConfigure;
		$activity = $ActivityConfigure->getCurrentActivity(1);
		if(!$activity)
			return true;*/
		$score = floor($score / 2);
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus(1) != AllianceMatchList::DOING){
			return true;
		}
			
		if(!(new PlayerGuild)->addMissionScore($playerId, $score)){
			return false;
		}
		return true;
	}
	
	public function robHsb($fromPlayerId){
		/*$ActivityConfigure = new ActivityConfigure;
		$activity = $ActivityConfigure->getCurrentActivity(2);
		if(!$activity)
			return true;*/
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus(2) != AllianceMatchList::DOING){
			return false;
		}
		
		$Player = new Player;
		return $Player->updateHsb($fromPlayerId, -5);
		/*$fromPlayer = $Player->getByPlayerId($fromPlayerId);
		if($fromPlayer['hsb']){
			$num = $Player->updateHsb($fromPlayerId, -5);
			return $num;
			//$Player->updateHsb($toPlayerId, 1);
		}*/
		//return false;
	}
}