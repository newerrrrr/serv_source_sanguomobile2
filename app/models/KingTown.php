<?php
/**
 * 王战
 *
 */
class KingTown extends ModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	const TYPE_BIG = 2;
	const TYPE_SMALL = 1;
	const POINT_BY_BIGTOWN = 1;
	const POINT_BY_SMALLTOWN = 1;
	const MAX_RATE = 2;
	
	public function getByXy($x, $y){
		$ret = self::findFirst(['x='.$x.' and y='.$y]);
		if(!$ret)
			return false;
		return $ret->toArray();
	}

	public function getById($id){
		$ret = self::findFirst(['id='.$id]);
		if(!$ret)
			return false;
		return $ret->toArray();
	}

	public function updateSoldierNum($id, $num){
		$town = $this->getById($id);
		$this->updateAll(['npc_num'=>'npc_num-'.$num], ['id'=>$town['id']]);
	}
	
	public function upOwner($kingId, $guildId){
		$time = time();
		$king = (new King)->findFirst($kingId)->toArray();
		$lastTime = strtotime($king['end_time']);
		$time = min($time, $lastTime);
		$now = date('Y-m-d H:i:s', $time);
		
		if(!$this->calculate($kingId, $time)){
			return false;
		}
		/*if($this->guild_id && $this->guild_id != $guildId){
			
			$GuildKingPoint = new GuildKingPoint;
			if($this->type == self::TYPE_BIG){
				$perPoint = self::POINT_BY_BIGTOWN;
			}else{
				$perPoint = self::POINT_BY_SMALLTOWN;
			}
			$second = max(0, strtotime($now) - strtotime($this->point_start_time));
			$point = $second * $perPoint;
			if(!$GuildKingPoint->add($this->guild_id, $point)){
				return false;
			}
		}
		*/
		$ret = $this->updateAll(array(
			'guild_id'=>$guildId, 
			'point_start_time'=>"'".$now."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), ["id"=>$this->id, "rowversion"=>"'".$this->rowversion."'"]);
	
		return $ret;
	}
	
	public function calculate($kingId, $time=0){
		if(!$time){
			$time = time();
		}
		$king = (new King)->findFirst($kingId)->toArray();
		$lastTime = strtotime($king['end_time']);
		$time = min($time, $lastTime);
		
		//加锁
		$lockKey = __CLASS__ . ':' . __METHOD__ ;//锁定
		Cache::lock($lockKey);
		
		$KingTown = new KingTown;
		$GuildKingPoint = new GuildKingPoint;
		$kingtown = KingTown::find(['guild_id > 0']);
		$townNum = [];
		foreach($kingtown as $_kingtown){
			@$townNum[$_kingtown->guild_id]++;
		}
		foreach($kingtown as $_kingtown){
			if($_kingtown->type == self::TYPE_BIG){
				$perPoint = KingTown::POINT_BY_BIGTOWN * min(self::MAX_RATE, $townNum[$_kingtown->guild_id]);
			}else{
				$perPoint = KingTown::POINT_BY_SMALLTOWN * min(self::MAX_RATE, $townNum[$_kingtown->guild_id]);
			}
			$second = max(0, $time - strtotime($_kingtown->point_start_time));
			$point = $second * $perPoint;
			//更新所有联盟积分
			if($point){
				if(!$GuildKingPoint->add($_kingtown->guild_id, $point)){
					//解锁
					Cache::unlock($lockKey);
					return false;
				}
				$_rowversion = uniqid();
				if(!$KingTown->updateAll([
					'point_start_time'=>"'".date('Y-m-d H:i:s', $time)."'",
					'update_time'=>"'".date('Y-m-d H:i:s', $time)."'",
					'rowversion'=>"'".$_rowversion."'"
				], ["id"=>$_kingtown->id, "rowversion"=>"'".$_kingtown->rowversion."'"])){
					//解锁
					Cache::unlock($lockKey);
					return false;
				}
				if(isset($this->id) && $this->id == $_kingtown->id){
					$this->rowversion = $_rowversion;
				}
			}
		}
		
		//解锁
		Cache::unlock($lockKey);
		return true;
	}

	public function resetTown($tId, $npcId=0){
		$town = $this->getById($tId);
		if(!$town){
			$config = [
				1 => ['x'=>610, 'y'=>626, 'type'=>2],//1602
				2 => ['x'=>610, 'y'=>610, 'type'=>2],//1604
				3 => ['x'=>626, 'y'=>610, 'type'=>1],//1603
				4 => ['x'=>626, 'y'=>626, 'type'=>1],//1605
			];
			$o = new self;
			$o->id = $tId;
			$o->type = $config[$tId]['type'];
			$o->guild_id = 0;
			$o->status = 0;
			$o->x = $config[$tId]['x'];
			$o->y = $config[$tId]['y'];
			$o->point_start_time = '0000-00-00 00:00:00';
			$o->create_time = date('Y-m-d H:i:s');
			$o->update_time = date('Y-m-d H:i:s');
			$o->rowversion = uniqid();
			$o->save();
			$town = $this->getById($tId);
		}
		if($npcId==0){
			$npcId = ($town['type']==self::TYPE_SMALL)?21000:31000;
		}else{
			$npcId += 1000;
		}
		$Npc = new Npc;
		$npc = $Npc->dicGetOne($npcId);
		$this->updateAll(['guild_id'=>0, 'status'=>0, 'npc_id'=>$npc['id'], 'npc_num'=>$npc['number'], 'update_time'=>qd(),'rowversion'=>"'".uniqid()."'"],['id'=>$tId]);
	}
}
