<?php
class PlayerStudy extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
    /**
     * 获取指定玩家的指定学习位
     * 
     * @param <type> $playerId 
     * @param <type> $position 
     * 
     * @return <type>
     */
	public function getByPosition($playerId, $position){
		$ret = $this->findFirst(array('player_id='.$playerId.' and position='.$position));
		return ($ret ? $ret->toArray() : false);
	}
	
	//新增学习
	public function add($playerId, $position, $generalId){
		if($this->getByPosition($playerId, $position))
			return false;
		$ret = $this->create(array(
			'player_id' => $playerId,
			'general_id' => $generalId,
			'position' => $position,
			'type' => 0,
			'gain_exp' => 0,
			'start_time' => '0000-00-00 00:00:00',
			'end_time' => '0000-00-00 00:00:00',
			'create_time' => date('Y-m-d H:i:s'),
			'update_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
	
	public function updateGeneral($generalId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'general_id'=>$generalId,
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		return $ret;
	}
	
	//开始学习
	public function beginStudy($type, $gainExp, $startTime, $endTime){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'type'=>$type, 
			'gain_exp' => $gainExp,
			'start_time' => $startTime,
			'end_time'=>$endTime,
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		return $ret;
	}
	
	public function finishStudy($playerId){
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$GeneralExp = new GeneralExp;
		$lvmax = min($GeneralExp->getMaxLv(), $player['level']+1);
		$expmax = $GeneralExp->lv2exp($lvmax)*1;
		//获得所有可完成位置
		$ret = $this->find(array('player_id='.$playerId.' and type > 0 and end_time <= "'.date('Y-m-d H:i:s').'"'))->toArray();
		$ids = array();
		$generalids = array();
		//$PlayerGeneral = new PlayerGeneral;
		foreach($ret as $_r){
			$ids[] = $_r['id'];
			$generalids[] = $_r['general_id'];
		}
		//更新位置
		if($ids){
			$sql = "UPDATE player_study set 
				type = 0,
				gain_exp = 0,
				start_time = '0000-00-00 00:00:00',
				end_time = '0000-00-00 00:00:00',
				update_time = '".date('Y-m-d H:i:s')."',
				rowversion = '".uniqid()."'
			WHERE id in (".join(',', $ids).")";
			
			$this->sqlExec($sql);
			
			$sql = "UPDATE player_general set 
				status = 0,
				update_time = '".date('Y-m-d H:i:s')."',
				rowversion = '".uniqid()."'
			WHERE player_id = {$playerId} and general_id in (".join(',', $generalids).")";
			
			$this->sqlExec($sql);
		}
		//$this->gainResource($playerId);
		$this->clearDataCache($playerId);
		
		//更新建筑工作状态
		$this->refreshWork($playerId);
		//return $ret;
	}
	
	public function accelerateStudy(){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'end_time'=>$now,
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		return $ret;
	}
	
	public function refreshWork($playerId){
		$PlayerBuild = new PlayerBuild;
		if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 13)){
			return false;
		}
		$playerBuild = $playerBuild[0];
		$d = $PlayerBuild->sqlGet('select max(end_time) from player_study where player_id='.$playerId.' and type>0');
		if($d && $d[0]['max(end_time)']){
			$PlayerBuild->startWork($playerId, $playerBuild['position'], $d[0]['max(end_time)']);
		}else{
			$PlayerBuild->startWork($playerId, $playerBuild['position'], date('Y-m-d H:i:s'));
			$PlayerBuild->endWork($playerId, $playerBuild['position']);
		}
	}
	
	/*public function clearDataCache(){
		$ret = $this->toArray();
		Cache::delPlayer($ret['player_id'], __CLASS__);
	}*/
}