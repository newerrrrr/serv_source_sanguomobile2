<?php
//科技
class PlayerScience extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	//获取指定玩家的指定天赋
	public function getAllByScienceId($playerId, $scienceId){
		$ret = $this->findFirst(array('player_id='.$playerId.' and science_id='.$scienceId));
		return ($ret ? $ret->toArray() : false);
	}
	
	//新增
	public function add($playerId, $scienceId, $studyTime, $status, $pushId=0){
		if($this->getAllByScienceId($playerId, $scienceId))
			return false;
		$ret = $this->create(array(
			'player_id' => $playerId,
			'science_id' => 0,
			'start_time'=>date('Y-m-d H:i:s'),
			'end_time'=>date('Y-m-d H:i:s', time()+$studyTime),
			'next_id'=>$scienceId,
			'push_id'=>$pushId,
			'status'=>$status,
			'create_time' => date('Y-m-d H:i:s'),
			'update_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
	
	public function lvupBegin($newScienceId, $studyTime, $pushId=0){
		$ret = $this->saveAll(array(
			'next_id'=>$newScienceId, 
			'start_time'=>date('Y-m-d H:i:s'), 
			'end_time'=>date('Y-m-d H:i:s', time()+$studyTime), 
			'status'=>1, 
			'push_id'=>$pushId,
			'update_time'=>date('Y-m-d H:i:s'), 
			'rowversion'=>uniqid()
		), 
		"player_id={$this->player_id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		return $ret;
	}
	
	public function accelerate($second){
		$now = date('Y-m-d H:i:s');
		$endTime = (is_numeric($this->end_time) ? $this->end_time : strtotime($this->end_time));
		$ret = $this->saveAll(array(
			'end_time'=>date('Y-m-d H:i:s', $endTime - $second), 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		
		//更新推送
		(new PlayerPush)->updateSendTime($this->push_id, date('Y-m-d H:i:s', $endTime - $second));
		
		$this->clearDataCache();
		return $ret;
	}
	
	/**
     * 升级完成
     * 
     * @return <type>
     */
	public function lvupFinish($playerId){ 
		$PlayerBuild = new PlayerBuild;
		if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 10)){
			return false;
		}
		$r = $PlayerBuild->endWork($playerId, $playerBuild[0]['position']);
		if(!$r){
			if($playerBuild[0]['status'] == 1 && $playerBuild[0]['work_content'] && $playerBuild[0]['work_finish_time'] < time()){//防止2边数据不一致造成无法完成科技
				$nextId = $playerBuild[0]['work_content'];
				$ret = $this->find(array('player_id='.$playerId.' and next_id="'.$nextId.'" and status=1'))->toArray();
			}else{
				return true;
			}
		}else{
			$nextId = $playerBuild[0]['work_content'];
			$ret = $this->find(array('player_id='.$playerId.' and next_id="'.$nextId.'"'))->toArray();
			
		}
		//结算
		if(!$ret)
			return false;
		$id = $ret[0]['id'];
		//todo 处理buff
		$Science = new Science;
		$science = $Science->dicGetOne($ret[0]['next_id']);
		if(!$science)
			return false;
		$Drop = new Drop;
		if(!$Drop->gain($playerId, [$science['science_drop']], 1, 'addScience:'.$id)){
			return false;
		}
		
		/*foreach($ret as $_r){
			$ids[] = $_r['id'];
			(new PlayerHelp)->endPlayerHelp($playerId, PlayerHelp::HELP_TYPE_SCIENCE, $_r['id']);
		}*/
		(new PlayerPush)->del($ret[0]['push_id']);
		
		$this->updateAll(array('science_id'=>'next_id', 'next_id'=>0, 'status'=>0, 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'push_id'=>0, 'rowversion'=>'"'.uniqid().'"'), array("id"=>$id));
		
		
		$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
		$addPower = $science['power'];
		if($ret[0]['science_id']){
			$science0 = $Science->dicGetOne($ret[0]['science_id']);
			$addPower -= $science0['power'];
		}
		$PlayerTimeLimitMatch->updateScore($playerId, 10, $addPower, ['科技id'=>$science['id'], '科技名字'=>$science['desc1']]);
		$this->clearDataCache($playerId);
		(new Player)->refreshPower($playerId, 'science_power');
		//return $ret;
		return true;
	}
	
    /**
     * 是否正在研究
     * 
     * 
     * @return <type>
     */
	public function isInStudy($playerId){
		$this->lvupFinish($playerId);
		if($this->find(array('player_id='.$playerId.' and status > 0'))->toArray()){
			return true;
		}
		return false;
	}
	
	public function isScienceExist($playerId, $scienceId){
		$Science = new Science;
		$science = $Science->dicGetOne($scienceId);
		
		$sciences = $Science->find(['science_type_id='.$science['science_type_id']])->toArray();
		$ids = [];
		foreach($sciences as $_s){
			$ids[] = $_s['id'];
		}
		
		$ret = $this->getByPlayerId($playerId);
		$myScienceId = false;
		foreach($ret as $_r){
			if(in_array($_r['science_id'], $ids)){
				$myScienceId = $_r['science_id'];
				break;
			}
		}
		if(!$myScienceId){
			return false;
		}
		$myScience = $Science->dicGetOne($myScienceId);
		if($myScience['level_id'] < $science['level_id'])
			return false;
		return true;
	}
}