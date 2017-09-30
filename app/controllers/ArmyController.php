<?php
use Phalcon\Mvc\View;
class ArmyController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
	public function setUnitAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$position = floor(@$post['position']);
		$unit = @$post['unit'];
		if(!is_array($unit) || !checkRegularNumber($position))
			exit;
	
		$PlayerGeneral = new PlayerGeneral;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerSoldier = new PlayerSoldier;
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取校场是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10000);
			}

			//检查军团是否空闲
			$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByPlayerId($playerId);
			$isNewArmy = true;
			$armyId = 0;
			foreach($playerArmy as $_pa){
				if($_pa['position'] == $position){
					if($_pa['status']){
						throw new Exception(10001);
					}
					$playerArmy = $_pa;
					$armyId = $_pa['id'];
					$isNewArmy = false;
				}
			}
			
			//检查position槽是否开启
			if(!$PlayerArmy->getByPositionId($playerId, $position)){
				$armyNum = (new Player)->getMaxArmyNum($playerId);
				if($position > $armyNum){
					throw new Exception(10002);
				}
			}
			
			//检查武将数量是否超过上限
			if(count($unit) > (new Player)->getArmyGeneralNum($playerId)){
				throw new Exception(10003);
			}
			
			//unset所有武将士兵
			$playerArmyUnit = $PlayerArmyUnit->getByPlayerId($playerId);
			foreach($playerArmyUnit as $_pau){
				if($_pau['army_id'] != $armyId) continue;
				if($_pau['general_id']){//修改武将所属军团
					$_playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_pau['general_id']);
					if(!$_playerGeneral){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					if(!$PlayerGeneral->assign($_playerGeneral)->updateArmy(0)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				if($_pau['soldier_id'] && $_pau['soldier_num']){//归还空闲士兵
					if(!$PlayerSoldier->updateSoldierNum($playerId, $_pau['soldier_id'], $_pau['soldier_num'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				$PlayerArmyUnit->assign($_pau)->delete();
			}
			$PlayerArmyUnit->_clearDataCache($playerId);
			
			$generalIds = array();
			$generals = array();
			$soldiers = array();
			$leaderGeneralId = 0;
			ksort($unit);
			foreach($unit as &$_unit){
				list($_generalId, $_soldierId, $_soldierNum) = $_unit;
				if(!checkRegularNumber($_generalId, true) || !checkRegularNumber($_soldierId, true) || !checkRegularNumber($_soldierNum, true)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if(!$_generalId){
					$_soldierId = 0;
					$_soldierNum = 0;
				}
				if(!$_soldierId){
					$_soldierNum = 0;
				}
				if($_generalId){
					//检查传入武将是否重复
					if(in_array($_generalId, $generalIds)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					$generalIds[] = $_generalId;
					if(!$leaderGeneralId)
						$leaderGeneralId = $_generalId;
					
					$_playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_generalId);
					if(!$_playerGeneral){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					//检查武将是否在其他军团
					if($_playerGeneral['army_id']){
						throw new Exception(10004);
					}
					//检查武将学习或其他
					if($_playerGeneral['status']){
						throw new Exception(10005);
					}
					if($_soldierId && $_soldierNum){
						//检查带兵上限
						$_bringSoldierMax = $PlayerGeneral->assign($_playerGeneral)->getMaxBringSoldier();
						if(!$_bringSoldierMax){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						$_playerSoldier = $PlayerSoldier->getBySoldierId($playerId, $_soldierId);
						if($_playerSoldier){
							$_currentSoldierNum = $_playerSoldier['num'];
						}else{
							$_currentSoldierNum = 0;
						}
						$_soldierNum = min($_soldierNum, $_bringSoldierMax, $_currentSoldierNum);
						//更新剩余士兵
						if(!$PlayerSoldier->updateSoldierNum($playerId, $_soldierId, -$_soldierNum)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
					$generals[] = $_playerGeneral;
				}
				//汇总士兵
				/*if($_soldierId){
					$soldiers[$_soldierId] = @$soldiers[$_soldierId] + $_soldierNum;
				}*/
				$_unit = array($_generalId, $_soldierId, $_soldierNum);
			}
			unset($_unit);
			
			
			if(!$isNewArmy){
				if($leaderGeneralId != $playerArmy['leader_general_id']){
					if(!$PlayerArmy->assign($playerArmy)->updateGeneral($leaderGeneralId)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}else{
				if(!$PlayerArmy->add($playerId, $position, $leaderGeneralId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$armyId = $PlayerArmy->id;
			}
			
			//更新军团单位
			foreach($unit as $_position => $_unit){
				list($_generalId, $_soldierId, $_soldierNum) = $_unit;
				if(!(new PlayerArmyUnit)->add($playerId, $armyId, $_position, $_generalId, $_soldierId, $_soldierNum)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}

			foreach($generals as $_playerGeneral){
				//更新军团
				if(!$PlayerGeneral->assign($_playerGeneral)->updateArmy($armyId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
							
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		//$data = DataController::get($playerId, array('PlayerArmyUnit'));
		//echo retMsg($err, @$data, @$msg);
		//$this->data->setBasic(['PlayerArmy', 'PlayerArmyUnit']);
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
		
    /**
     * 设置武将
     * 
     * 
     * @return <type>
     */
	public function setGeneralAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		$unitPosition = floor(@$post['unitPosition']);
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId, true) || !checkRegularNumber($armyPosition) || !checkRegularNumber($unitPosition))
			exit;
	
		$PlayerGeneral = new PlayerGeneral;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerSoldier = new PlayerSoldier;

	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取校场是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10006);
			}

			//检查军团是否空闲
			$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByPlayerId($playerId);
			$isNewArmy = true;
			$armyId = 0;
			foreach($playerArmy as $_pa){
				if($_pa['position'] == $armyPosition){
					if($_pa['status']){
						throw new Exception(10007);
					}
					$playerArmy = $_pa;
					$armyId = $_pa['id'];
					$isNewArmy = false;
				}
			}
			
			//检查position槽是否开启
			if(!$PlayerArmy->getByPositionId($playerId, $armyPosition)){
				$armyNum = (new Player)->getMaxArmyNum($playerId);
				if($armyPosition > $armyNum){
					throw new Exception(10008);
				}
			}
			
			//检查武将数量是否超过上限
			if($unitPosition > (new Player)->getArmyGeneralNum($playerId)){
				throw new Exception(10009);
			}
			
			//unset该武将士兵
			$soldierId = 0;
			$soldierNum = 0;
			$playerArmyUnit = $PlayerArmyUnit->getByPlayerId($playerId);
			$leaderGeneralId = 0;
			$minUnit = false;
			foreach($playerArmyUnit as $_pau){
				if($_pau['army_id'] != $armyId) continue;
				if($_pau['unit'] != $unitPosition){
					if((false === $minUnit || $minUnit > $_pau['unit']) && $_pau['general_id']){
						$leaderGeneralId = $_pau['general_id'];
						$minUnit = $_pau['unit'];
					}
				}else{
					if($_pau['general_id']){//修改武将所属军团
						$_playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_pau['general_id']);
						if(!$_playerGeneral){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						if(!$PlayerGeneral->assign($_playerGeneral)->updateArmy(0)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
					if($_pau['soldier_id'] && $_pau['soldier_num']){//归还空闲士兵
						if(!$PlayerSoldier->updateSoldierNum($playerId, $_pau['soldier_id'], $_pau['soldier_num'])){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					
						$soldierId = $_pau['soldier_id'];
						$soldierNum = $_pau['soldier_num'];
					}
					$PlayerArmyUnit->assign($_pau)->delete();
				}
			}
			$PlayerArmyUnit->_clearDataCache($playerId);
			if((false === $minUnit || $minUnit > $unitPosition) && $generalId){
				$leaderGeneralId = $generalId;
				//$minUnit = $_pau['unit'];
			}

			if(!$isNewArmy){
				if($leaderGeneralId != $playerArmy['leader_general_id']){
					if(!$PlayerArmy->assign($playerArmy)->updateGeneral($leaderGeneralId)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}else{
				if(!$PlayerArmy->add($playerId, $armyPosition, $leaderGeneralId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$armyId = $PlayerArmy->id;
			}

			
			if($generalId){
				$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
				if(!$playerGeneral){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//检查武将是否在其他军团
				if($playerGeneral['army_id']){
					throw new Exception(10010);
				}
				//检查武将学习或其他
				if($playerGeneral['status']){
					throw new Exception(10011);
				}
				
				if($soldierId){
					//检查带兵上限
					$bringSoldierMax = $PlayerGeneral->assign($playerGeneral)->getMaxBringSoldier();
					if(!$bringSoldierMax){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					//获得现有兵数
					$playerSoldier = $PlayerSoldier->getBySoldierId($playerId, $soldierId);
					if($playerSoldier){
						$currentSoldierNum = $playerSoldier['num'];
					}else{
						$currentSoldierNum = 0;
					}
					$soldierNum = min($soldierNum, $bringSoldierMax, $currentSoldierNum);
					//更新士兵数
					if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierId, -$soldierNum)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				
				//更新军团单位
				if(!(new PlayerArmyUnit)->add($playerId, $armyId, $unitPosition, $generalId, $soldierId, $soldierNum)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}

				//更新军团
				if(!$PlayerGeneral->assign($playerGeneral)->updateArmy($armyId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
			}
			
							
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 设置士兵
     * 
     * 
     * @return <type>
     */
	public function setSoldierAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		$unitPosition = floor(@$post['unitPosition']);
		$soldierId = @$post['soldierId'];
		$soldierNum = @$post['soldierNum'];
		if(!checkRegularNumber($soldierId, true) || !checkRegularNumber($soldierNum, true) || !checkRegularNumber($armyPosition) || !checkRegularNumber($unitPosition))
			exit;
	
		$PlayerGeneral = new PlayerGeneral;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerSoldier = new PlayerSoldier;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取校场是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10012);
			}

			//检查军团是否空闲
			$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByPlayerId($playerId);
			$isNewArmy = true;
			$armyId = 0;
			foreach($playerArmy as $_pa){
				if($_pa['position'] == $armyPosition){
					if($_pa['status']){
						throw new Exception(10013);
					}
					$playerArmy = $_pa;
					$armyId = $_pa['id'];
					$isNewArmy = false;
				}
			}
			if($isNewArmy){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查position槽是否开启
			if(!$PlayerArmy->getByPositionId($playerId, $armyPosition)){
				$armyNum = (new Player)->getMaxArmyNum($playerId);
				if($armyPosition > $armyNum){
					throw new Exception(10014);
				}
			}
			
			//检查武将数量是否超过上限
			if($unitPosition > (new Player)->getArmyGeneralNum($playerId)){
				throw new Exception(10015);
			}
			
			//unset该武将士兵
			$playerArmyUnit = $PlayerArmyUnit->getByPlayerId($playerId);
			foreach($playerArmyUnit as $_pau){
				if($_pau['army_id'] != $armyId) continue;
				if($_pau['unit'] != $unitPosition) continue;
				if(!$_pau['general_id']){
					throw new Exception(10016);
				}
				$generalId = $_pau['general_id'];
				$pau = $_pau;
				if($_pau['soldier_id'] && $_pau['soldier_num']){//归还空闲士兵
					if(!$PlayerSoldier->updateSoldierNum($playerId, $_pau['soldier_id'], $_pau['soldier_num'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			$PlayerArmyUnit->_clearDataCache($playerId);
			if(!@$generalId){
				throw new Exception(10017);
			}
			
			if($soldierId){
				//检查soldierId
				$Soldier = new Soldier;
				if(!$Soldier->dicGetOne($soldierId)){
					throw new Exception(10018);
				}
				//检查带兵上限
				$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
				$bringSoldierMax = $PlayerGeneral->assign($playerGeneral)->getMaxBringSoldier();
				if(!$bringSoldierMax){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//获得现有兵数
				$playerSoldier = $PlayerSoldier->getBySoldierId($playerId, $soldierId);
				if($playerSoldier){
					$currentSoldierNum = $playerSoldier['num'];
				}else{
					$currentSoldierNum = 0;
				}
				if($soldierNum > $currentSoldierNum){
					throw new Exception(10019);
				}
				$soldierNum = min($soldierNum, $bringSoldierMax, $currentSoldierNum);
				//更新士兵数
				if($soldierNum){
					if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierId, -$soldierNum)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}else{
				$soldierNum = 0;
			}
				
			//更新军团单位
			if(!$PlayerArmyUnit->assign($pau)->updatePosition($generalId, $soldierId, $soldierNum)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
							
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	public function fullfillSoldierAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		if(!checkRegularNumber($armyPosition))
			exit;
	
		$PlayerGeneral = new PlayerGeneral;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerSoldier = new PlayerSoldier;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取校场是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10020);
			}

			//检查军团是否空闲
			$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByPlayerId($playerId);
			$isNewArmy = true;
			$armyId = 0;
			foreach($playerArmy as $_pa){
				if($_pa['position'] == $armyPosition){
					if($_pa['status']){
						throw new Exception(10021);
					}
					$playerArmy = $_pa;
					$armyId = $_pa['id'];
					$isNewArmy = false;
				}
			}
			if($isNewArmy){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$playerArmyUnit = $PlayerArmyUnit->getByPlayerId($playerId);
			$playerArmyUnit = Set::sort($playerArmyUnit, '{n}.unit', 'asc');
			foreach($playerArmyUnit as $_pau){
				if($_pau['army_id'] != $armyId) continue;
				//if(!$_pau['general_id'] || !$_pau['soldier_id']) continue;
				if(!$_pau['general_id']) continue;
				if($_pau['soldier_id']){
					//检查带兵上限
					$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_pau['general_id']);
					$_bringSoldierMax = $PlayerGeneral->assign($playerGeneral)->getMaxBringSoldier();
					if(!$_bringSoldierMax){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					//获得现有兵数
					$playerSoldier = $PlayerSoldier->getBySoldierId($playerId, $_pau['soldier_id']);
					if($playerSoldier){
						$currentSoldierNum = $playerSoldier['num'];
					}else{
						$currentSoldierNum = 0;
					}
					$soldierNum = min($_pau['soldier_num']+$currentSoldierNum, $_bringSoldierMax);
					$subSoldierNum = $soldierNum - $_pau['soldier_num'];
					//更新士兵数
					if($subSoldierNum){
						if(!$PlayerSoldier->updateSoldierNum($playerId, $_pau['soldier_id'], -$subSoldierNum)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
					
					//更新军团单位
					$PlayerArmyUnit->assign($_pau)->updatePosition($_pau['general_id'], $_pau['soldier_id'], $soldierNum);
				}else{
					$fitableSoldier = $PlayerGeneral->getFitableSoldier($playerId, $_pau['general_id']);
					if(!$fitableSoldier || !$fitableSoldier['num'])
						continue;
					//更新士兵数
					if(!$PlayerSoldier->updateSoldierNum($playerId, $fitableSoldier['soldier_id'], -$fitableSoldier['num'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					//更新军团单位
					$PlayerArmyUnit->assign($_pau)->updatePosition($_pau['general_id'], $fitableSoldier['soldier_id'], $fitableSoldier['num']);
				}
			}
			$PlayerArmyUnit->_clearDataCache($playerId);
							
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 获得我的援军信息
     * 
     * 
     * @return <type>
     */
	public function assistArmyInfoAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		
		try {
			//获取援助我的所有队列
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getHelpArmy($playerId);
			
			//获取power
			/*
			if($ppq){
				$PlayerArmy = new PlayerArmy;
				foreach($ppq as $_k => &$_ppq){
					if(in_array($_k, ['current_help_num', 'max_help_num'])) continue;
					$_ppq['power'] = $PlayerArmy->_getPower($_ppq['army']);
				}
				unset($_ppq);
			}*/
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		
		if(!$err){
			echo $this->data->send(array('assistArmy'=>$ppq));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 攻击/防守队列信息
     * 
     * 
     * @return <type>
     */
	public function warArmyInfoAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$justCounter = floor(@$post['justCounter']);//是否仅显示数量，用于轮询红点
		
		try {
			$Player = new Player;
			$Guild = new Guild;
			//获取同工会成员map_id
			$mapIds = [];
			if($player['guild_id']){
				$members = (new PlayerGuild)->getAllGuildMember($player['guild_id']);
				foreach($members as $_m){
					$mapIds[] = $_m['Player']['map_id'];
				}
				
				//获取我方堡垒map_id
				$Map = new Map;
				$map = $Map->find('guild_id='.$player['guild_id'].' and map_element_origin_id=1')->toArray();
				foreach($map as $_m){
					$mapIds[] = $_m['id'];
				}
			}else{
				$mapIds = [$player['map_id']];
			}
			
			//获取攻击我方（城堡/堡垒）的所有敌方
			$PlayerProjectQueue = new PlayerProjectQueue;
			$types = [PlayerProjectQueue::TYPE_CITYBATTLE_GOTO, PlayerProjectQueue::TYPE_GATHERBATTLE_GOTO, PlayerProjectQueue::TYPE_ATTACKBASE_GOTO, PlayerProjectQueue::TYPE_ATTACKBASEGATHER_GOTO];
			
			if($justCounter){
				$sql1 = "to_map_id in (".join(',', $mapIds).") and type in (".join(',', $types).")";
				$types[] = PlayerProjectQueue::TYPE_GATHER_WAIT;
				if($player['guild_id']){
					$sql2 = "guild_id=".$player['guild_id']." and type in (".join(',', $types).")";
				}else{
					$sql2 = "player_id=".$playerId." and type in (".join(',', $types).")";
				}
				//echo "((".$sql1.") or (".$sql2.")) and status=1 and (end_time='0000-00-00 00:00:00' or end_time>'".date('Y-m-d H:i:s')."')";
				$num = PlayerProjectQueue::count("((".$sql1.") or (".$sql2.")) and status=1 and (end_time='0000-00-00 00:00:00' or end_time>'".date('Y-m-d H:i:s')."')");
				echo $this->data->send(['num'=>$num]);
				exit;
			}
			
			$ppq1 = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find("to_map_id in (".join(',', $mapIds).") and status=1 and type in (".join(',', $types).") and (end_time='0000-00-00 00:00:00' or end_time>'".date('Y-m-d H:i:s')."')")->toArray());
			
			//获取我方攻击的所有队列
			if($player['guild_id']){
				$ppq2 = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find("guild_id=".$player['guild_id']." and status=1 and type in (".join(',', $types).") and (end_time='0000-00-00 00:00:00' or end_time>'".date('Y-m-d H:i:s')."')")->toArray());
			}else{
				$ppq2 = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find("player_id=".$playerId." and status=1 and type in (".join(',', $types).") and (end_time='0000-00-00 00:00:00' or end_time>'".date('Y-m-d H:i:s')."')")->toArray());
			}
			
			$ppq = array_merge($ppq1, $ppq2);
			$playerInfo = [];
			$guildInfo = [];
			$ret = [];
			foreach($ppq as $_ppq){
				$_ret = [];
				//攻击方信息
				if(!isset($playerInfo[$_ppq['player_id']])){
					$_player = $Player->getByPlayerId($_ppq['player_id']);
					$playerInfo[$_ppq['player_id']] = $_player;
				}
				$_ret['attackerNick'] = $playerInfo[$_ppq['player_id']]['nick'];
				$_ret['attackerAvatar'] = $playerInfo[$_ppq['player_id']]['avatar_id'];
				$_ret['attackerX'] = $playerInfo[$_ppq['player_id']]['x'];
				$_ret['attackerY'] = $playerInfo[$_ppq['player_id']]['y'];
				$_attackGuildId = $playerInfo[$_ppq['player_id']]['guild_id'];
				if($playerInfo[$_ppq['player_id']]['guild_id']){
					if(!isset($guildInfo[$playerInfo[$_ppq['player_id']]['guild_id']])){
						$_guild = $Guild->getGuildInfo($playerInfo[$_ppq['player_id']]['guild_id']);
						$guildInfo[$playerInfo[$_ppq['player_id']]['guild_id']] = $_guild['short_name'];
					}
					$_ret['attackerGuild'] = $guildInfo[$playerInfo[$_ppq['player_id']]['guild_id']];
				}else{
					$_ret['attackerGuild'] = '';
				}
				
				//防守方信息
				if($_ppq['target_player_id']){
					if(!isset($playerInfo[$_ppq['target_player_id']])){
						$_player = $Player->getByPlayerId($_ppq['target_player_id']);
						$playerInfo[$_ppq['target_player_id']] = $_player;
					}
					$_ret['defenderNick'] = $playerInfo[$_ppq['target_player_id']]['nick'];
					$_ret['defenderAvatar'] = $playerInfo[$_ppq['target_player_id']]['avatar_id'];
					$_ret['defenderX'] = $playerInfo[$_ppq['target_player_id']]['x'];
					$_ret['defenderY'] = $playerInfo[$_ppq['target_player_id']]['y'];
					$_guildId = $playerInfo[$_ppq['target_player_id']]['guild_id'];
				}else{
					$_ret['defenderNick'] = '';
					$_ret['defenderAvatar'] = 0;
					$_ret['defenderX'] = $_ppq['to_x'];
					$_ret['defenderY'] = $_ppq['to_y'];
					$_guildId = $_ppq['target_info']['to_guild_id'];
				}
				$_defendGuildId = $_guildId;
				
				//if($playerInfo[$_ppq['player_id']]['guild_id']){
				if(isset($_guildId) && $_guildId){
					if(!isset($guildInfo[$_guildId])){
						$_guild = $Guild->getGuildInfo($_guildId);
						$guildInfo[$_guildId] = $_guild['short_name'];
					}
					$_ret['defenderGuild'] = $guildInfo[$_guildId];
				}else{
					$_ret['defenderGuild'] = '';
				}
				
				//是否为攻击方
				if($_ppq['player_id'] == $playerId || ($_ppq['guild_id'] && $_ppq['guild_id'] == $player['guild_id'])){
					$_ret['isAttacker'] = true;
				}else{
					$_ret['isAttacker'] = false;
				}
				
				//攻击目标类型
				if($_ppq['type'] == PlayerProjectQueue::TYPE_CITYBATTLE_GOTO){
					$_ret['type'] = 'city';
				}else{
					$_ret['type'] = 'base';
				}
				
				$_ret['create_time'] = $_ppq['create_time'];
				$_ret['end_time'] = $_ppq['end_time'];
				
				if($_attackGuildId == $_defendGuildId){
					continue;
				}
				$ret[$_ppq['id']] = $_ret;
			}
			$attackArmy = array_values($ret);
			$gatherArmy = $this->gatherArmyInfo($player);
			
			$retData = ['attackArmy'=>$attackArmy, 'gatherArmy'=>$gatherArmy, 'maxGatherNum'=>(new PlayerBuild)->getMaxGatherNum($playerId)];
			
			
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		
		if(!$err){
			echo $this->data->send($retData);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 集结队伍信息
     * 
     * 
     * @return <type>
     */
	public function gatherArmyInfo($player, $keepKey = false){
		//$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		//$post = getPost();
		
		//try {
			$Player = new Player;
			$PlayerArmy = new PlayerArmy;
			$Guild = new Guild;
			$Map = new Map;
			$PlayerBuild = new PlayerBuild;
			//获取我发起的集结队列
			if(!$player['guild_id']){
				return [];
			}
			
			$PlayerProjectQueue = new PlayerProjectQueue;
			$types = array_merge($PlayerProjectQueue->gatherTypes, [
				PlayerProjectQueue::TYPE_GATHER_WAIT, 
				PlayerProjectQueue::TYPE_GATHER_STAY, 
				PlayerProjectQueue::TYPE_GATHER_GOTO,
				PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO,
			]);
			$ppq = $PlayerProjectQueue->afterFindQueue(PlayerProjectQueue::find("guild_id={$player['guild_id']} and status=1 and type in (".join(',', $types).") and (end_time='0000-00-00 00:00:00' or end_time>'".date('Y-m-d H:i:s')."')")->toArray());
			
			//获取联盟成员
			$members = (new PlayerGuild)->getAllGuildMember($player['guild_id']);
			$memberIds = array_keys($members);
			
			$ppq1 = [];
			$ppq2 = [];
			$guildShortName = [];
			$ppq = filterFields($ppq, true, $PlayerProjectQueue->blacklist);
			$filter = ['id', 'player_id', 'target_info', 'create_time', 'end_time', 'player_nick', 'player_avatar', 'guild_name', 'leader_general_id', 'maxGatherNum', 'from_x', 'from_y', 'arrived'];
			foreach($ppq as &$_ppq){
				//获取玩家名字，头像
				$_player = $Player->getByPlayerId($_ppq['player_id']);
				$_ppq['player_nick'] = $_player['nick'];
				$_ppq['player_avatar'] = $_player['avatar_id'];
				if(!isset($guildShortName[$_ppq['guild_id']])){
					$guild = $Guild->getGuildInfo($_ppq['guild_id']);
					$guildShortName[$_ppq['guild_id']] = $guild['short_name'];
				}
				$_ppq['guild_name'] = $guildShortName[$_ppq['guild_id']];
				//获取带队武将id
				$_army = $PlayerArmy->getByArmyId($_ppq['player_id'], $_ppq['army_id']);
				$_ppq['leader_general_id'] = $_army['leader_general_id'];
				//获取最大可集结数
				$_ppq['maxGatherNum'] = $PlayerBuild->getMaxGatherNum($_ppq['player_id']);
				if(in_array($_ppq['type'], array_merge($PlayerProjectQueue->gatherTypes, [PlayerProjectQueue::TYPE_GATHER_WAIT]))){
					$_ppq['target_info']['to_player_nick'] = '';
					$_ppq['target_info']['to_player_avatar'] = 0;
					$_ppq['target_info']['guild_name'] = '';
					if('attackPlayer' == $_ppq['target_info']['type']){
						//获取目标头像，名字
						$_player = $Player->getByPlayerId($_ppq['target_info']['to_player_id']);
						$_ppq['target_info']['to_player_nick'] = $_player['nick'];
						$_ppq['target_info']['to_player_avatar'] = $_player['avatar_id'];
						$_ppq['target_info']['guild_name'] = '';
						if($_player['guild_id']){
							if(!isset($guildShortName[$_player['guild_id']])){
								$guild = $Guild->getGuildInfo($_player['guild_id']);
								$guildShortName[$_player['guild_id']] = $guild['short_name'];
							}
							$_ppq['target_info']['guild_name'] = $guildShortName[$_player['guild_id']];
						}
					}elseif('attackBase' == $_ppq['target_info']['type']){
						$_ppq['target_info']['to_player_nick'] = '';
						$_ppq['target_info']['to_player_avatar'] = 1;
						$_ppq['target_info']['guild_name'] = '';
						if($_ppq['target_info']['to_guild_id']){
							if(!isset($guildShortName[$_ppq['target_info']['to_guild_id']])){
								$guild = $Guild->getGuildInfo($_ppq['target_info']['to_guild_id']);
								$guildShortName[$_ppq['target_info']['to_guild_id']] = $guild['short_name'];
							}
							$_ppq['target_info']['guild_name'] = $guildShortName[$_ppq['target_info']['to_guild_id']];
						}
						$_ppq['target_info']['to_player_nick'] = $_ppq['target_info']['guild_name'];
					}elseif('attackTown' == $_ppq['target_info']['type']){//todo
				
					}elseif('attackBoss' == $_ppq['target_info']['type']){//todo
						
					}else{
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					//$_map = $Map->getByXy($_ppq['target_info']['to_x'], $_ppq['target_info']['to_y']);
					//$_ppq['target_info']['element_id'] = $_map['map_element_id'];
					if($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_WAIT){
						$_ppq['arrived'] = 1;//主队还未出发
					}else{
						$_ppq['arrived'] = 2;//主队已出发
					}
					$ppq1[$_ppq['id']] = $_ppq;
				}else{
					if($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_STAY){
						$_ppq['arrived'] = 1;//副队已达到
					}elseif($_ppq['type'] == PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO){
						$_ppq['arrived'] = 2;//出发
					}else{
						$_ppq['arrived'] = 0;//副队在路上
					}
					if($_ppq['parent_queue_id']){
						$ppq2[$_ppq['parent_queue_id']][] = $_ppq;
					}
				}
			}
			unset($_ppq);
			
			$ppq3 = [];
			foreach($ppq1 as &$_ppq){
				$ppq3[$_ppq['id']] = [$_ppq];
				if(isset($ppq2[$_ppq['id']])){
					$ppq3[$_ppq['id']] = array_merge($ppq3[$_ppq['id']], $ppq2[$_ppq['id']]);
				}
				$ppq3[$_ppq['id']] = keepFields($ppq3[$_ppq['id']], $filter);
				//过滤可邀请
				if($_ppq['player_id'] == $playerId){
					if(isset($ppq2[$_ppq['id']])){
						$ids = Set::extract('/player_id', $ppq2[$_ppq['id']]);
					}else{
						$ids = [];
					}
					$_ids = array_diff($memberIds, $ids, [$playerId]);
					$invite = [];
					foreach($_ids as $_id){
						$invite[] = [
							'player_id'        => $_id, 
							'nick'             => $members[$_id]['Player']['nick'], 
							'power'            => $members[$_id]['Player']['power'], 
							'last_online_time' => $members[$_id]['Player']['last_online_time'],
							'avatar_id'        => $members[$_id]['Player']['avatar_id'],
						];
					}
					$ppq3[$_ppq['id']][0]['invite'] = $invite;
				}
			}
			unset($_ppq);
			
			
			//$err = 0;
		/*} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}*/
		//解锁
		
		/*if(!$err){
			echo $this->data->send(array('gatherArmy'=>array_values($ppq3)));
		}else{
			echo $this->data->sendErr($err);
		}*/
		if(!$keepKey){
			$ppq3 = array_values($ppq3);
		}
		return $ppq3;
	}
	
    /**
     * 战争记录
     * 
     * 
     * @return <type>
     */
	public function getBattleLogAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$type = floor(@$post['type']);
		
		try {
			$GuildBattleLog = new GuildBattleLog;
			
			//没有联盟
			/*$ts = date('Y-m-d H:i:s', time()-24*3600);
			if(!$player['guild_id']){
				$data = $GuildBattleLog->find(["(attack_player_id={$playerId} or defend_player_id={$playerId}) and create_time >= '".$ts."'", "order"=>"id desc"])->toArray();
			}else{
				$data = $GuildBattleLog->find(["(attack_guild_id={$player['guild_id']} or defend_guild_id={$player['guild_id']}) and create_time >= '".$ts."'", "order"=>"id desc"])->toArray();
			}*/
			if($type){
				$data = $GuildBattleLog->getByPlayerId($playerId, false, 20, [$type]);
			}else{
				$data = $GuildBattleLog->getByPlayerId($playerId, false, 20);
			}
			$ts = time() - 24*3600;
			$data2 = [];
			if($data){
				foreach($data as $_d){
					if($_d['create_time'] < $ts)
						break;
					$data2[] = $_d;
				}
			}
			$Player = new Player;
			$Guild = new Guild;
			$guildShortName = [];
			$names = [];
			$avatars = [];
			foreach($data2 as &$_d){
				//名字
				if($_d['attack_player_id']){
					if(!isset($names[$_d['attack_player_id']])){
						$_player = $Player->getByPlayerId($_d['attack_player_id']);
						$names[$_d['attack_player_id']] = $_player['nick'];
						$avatars[$_d['attack_player_id']] = $_player['avatar_id'];
					}
					$_d['attack_player_name'] = $names[$_d['attack_player_id']];
					$_d['attack_avatar_id'] = $avatars[$_d['attack_player_id']];
				}
				
				if($_d['defend_player_id']){
					if(!isset($names[$_d['defend_player_id']])){
						$_player = $Player->getByPlayerId($_d['defend_player_id']);
						$names[$_d['defend_player_id']] = $_player['nick'];
						$avatars[$_d['defend_player_id']] = $_player['avatar_id'];
					}
					$_d['defend_player_name'] = $names[$_d['defend_player_id']];
					$_d['defend_avatar_id'] = $avatars[$_d['defend_player_id']];
				}
				
				//公会
				$_d['attack_guild_name'] = '';
				if($_d['attack_guild_id']){
					if(!isset($guildShortName[$_d['attack_guild_id']])){
						$guild = $Guild->getGuildInfo($_d['attack_guild_id']);
						$guildShortName[$_d['attack_guild_id']] = (!empty($guild['short_name']))?$guild['short_name']:"";
					}
					$_d['attack_guild_name'] = $guildShortName[$_d['attack_guild_id']];
				}
				
				$_d['defend_guild_name'] = '';
				if($_d['defend_guild_id']){
					if(!isset($guildShortName[$_d['defend_guild_id']])){
						$guild = $Guild->getGuildInfo($_d['defend_guild_id']);
						$guildShortName[$_d['defend_guild_id']] = (!empty($guild['short_name']))?$guild['short_name']:"";
					}
					$_d['defend_guild_name'] = $guildShortName[$_d['defend_guild_id']];
				}
				unset($_d['detail']);
			}
			unset($_d);
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		
		if(!$err){
			echo $this->data->send(array('log'=>$data2));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 战报详情
     * 
     * 
     * @return <type>
     */
	public function getBattleLogDetailAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$battleLogId = floor(@$post['battleLogId']);//0-撤命
		if(!checkRegularNumber($battleLogId))
			exit;
		
		try {
			$GuildBattleLog = new GuildBattleLog;
			$ret = $GuildBattleLog->findFirst($battleLogId);
			if($ret){
				$ret = $ret->toArray();
				$ret['detail'] = json_decode(gzuncompress($ret['detail']), true);
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		
		if(!$err){
			echo $this->data->send(array('guildBattleLogDetail'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
}