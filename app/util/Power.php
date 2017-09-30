<?php
/**
 * 力量计算类
 */
class Power {
    //总战斗力：主公战斗力+武将战斗力+部队战斗力+建筑战斗力+科技战斗力+陷阱战斗力
	public function getTotal($playerId, $fromCache=true){
		$modelClassName = get_class($this);
		$ret = Cache::getPlayer($playerId, $modelClassName);
		if(!$ret || !$fromCache){
			$ret = $this->getMaster($playerId)+$this->getGeneral($playerId)+$this->getSoldier($playerId)+$this->getBuilding($playerId)+$this->getScience($playerId)+$this->getTrap($playerId);
			Cache::setPlayer($playerId, $modelClassName, $ret);
		}
		return $ret;
	}
	//主公战斗力：主公宝物+主公等级战斗力+天赋
	public function getMaster($playerId){
		$power = 0;
		//获取主公等级
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player)
			return false;
		
		//获取等级配置
		$Master = new Master;
		$master = $Master->dicGetOne($player['level']);
		if(!$master)
			return false;
		$power += $master['power'];
		
		//获取主公宝物
		$PlayerEquipMaster = new PlayerEquipMaster;
		$pem = $PlayerEquipMaster->getByPlayerId($playerId);
		$EquipMaster = new EquipMaster;
		if($pem){
			//获取宝物配置
			foreach($pem as $_pem){
				if($_pem['status'] != PlayerEquipMaster::STATUS_ON) continue;
				$em = $EquipMaster->dicGetOne($_pem['equip_master_id']);
				if(!$em)
					return false;
				$power += $em['power'];
			}
		}
		
		//获取主公天赋
		$PlayerTalent = new PlayerTalent;
		$pt = $PlayerTalent->getByPlayerId($playerId);
		$Talent = new Talent;
		if($pt){
			foreach($pt as $_pt){
				//获取天赋配置
				$talent = $Talent->dicGetOne($_pt['talent_id']);
				if(!$talent)
					return false;
				$power += $talent['power'];
			}
		}
		return $power;
	}
	
	//武将战斗力：武将本身+武将装备
	public function getGeneral($playerId){
		$power = 0;
		//获取武将
		$PlayerGeneral = new PlayerGeneral;
		$pg = $PlayerGeneral->getByPlayerId($playerId);
		if($pg){
			foreach($pg as $_pg){
				$totalAttr = $PlayerGeneral->getTotalAttr($playerId, $_pg['general_id']);
				if(!$totalAttr)
					return false;
				$power += $totalAttr['attr']['power'];
			}
		}
		return $power;
	}
	
	//士兵战斗力：兵种等级*数量
	public function getSoldier($playerId){
		$power = 0;
		$soldiers = [];
		//获取散兵
		$PlayerSoldier = new PlayerSoldier;
		$ps = $PlayerSoldier->getByPlayerId($playerId);
		foreach($ps as $_ps){
			@$soldiers[$_ps['soldier_id']] += $_ps['num'];
		}
		
		//获取军团中
		$PlayerArmyUnit = new PlayerArmyUnit;
		$pau = $PlayerArmyUnit->getByPlayerId($playerId);
		foreach($pau as $_pau){
			if($_pau['soldier_id'] && $_pau['soldier_num']){
				@$soldiers[$_pau['soldier_id']] += $_pau['soldier_num'];
			}
		}
		
		//获取士兵配置
		$Soldier = new Soldier;
		foreach($soldiers as $_k => $_s){
			$soldier = $Soldier->dicGetOne($_k);
			if(!$soldier)
				return false;
			$power += $soldier['power'] * $_s;
		}
		return floor($power / DIC_DATA_DIVISOR);
	}
	
	//建筑战斗力：建筑物默认战斗力
	public function getBuilding($playerId){
		$power = 0;
		//获取所有建筑
		$PlayerBuild = new PlayerBuild;
		$pb = $PlayerBuild->getByPlayerId($playerId);
		$Build = new Build;
		if($pb){
			foreach($pb as $_pb){
				//获取建筑配置
				$build = $Build->dicGetOne($_pb['build_id']);
				if(!$build)
					return false;
				$power += $build['power'];
			}
		}
		return $power;
	}
	
	//科技战斗力：科技战斗力
	public function getScience($playerId){
		$power = 0;
		//获取玩家科技
		$PlayerScience = new PlayerScience;
		$ps = $PlayerScience->getByPlayerId($playerId);
		$Science = new Science;
		if($ps){
			foreach($ps as $_ps){
				if(!$_ps['science_id'])
					continue;
				//获取科技配置
				$science = $Science->dicGetOne($_ps['science_id']);
				if(!$science)
					return false;
				$power += $science['power'];
			}
		}
		return $power;
	}
	
	//陷阱战斗力：陷阱等级*数量
	public function getTrap($playerId){
		$power = 0;
		//获取玩家科技
		$PlayerTrap = new PlayerTrap;
		$pt = $PlayerTrap->getByPlayerId($playerId);
		$Trap = new Trap;
		if($pt){
			foreach($pt as $_pt){
				//获取科技配置
				$trap = $Trap->dicGetOne($_pt['trap_id']);
				if(!$trap)
					return false;
				$power += $trap['power']*$_pt['num'];
			}
		}
		return $power;
	}

}