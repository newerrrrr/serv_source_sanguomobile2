<?php
use Phalcon\Mvc\View;
class CrossController extends ControllerBase
{
	public $mapXBegin = 12;
	public $mapXEnd = 72;
	public $mapXBorderEnd = 84;
	public $mapYBegin = 12;
	public $mapYEnd = 72;
	public $mapYBorderEnd = 84;
	public $soldierTypeIds = [
		1 => [10019, 10020],
		2 => [20019, 20020],
		3 => [30019, 30020],
		4 => [40019, 40020],
	];
	public $catapultDistance = 30;//投石车攻击半径
	
	private $bnqcache = [];//blocknqueue的缓存
	private $viewareacache = [];//视野缓存
	
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}

	public function battleInfoAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		
		//获取battleId
		$CrossBattle = new CrossBattle;
		//判断是否跨服战中
		$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		$roundId = (new CrossRound)->getCurrentRoundId();
		if(!$roundId){
			$errCode = 10563;//当前没有战斗
            goto sendErr;
		}
		$cb = CrossBattle::findFirst(["(guild_1_id={$guildId} or guild_2_id={$guildId}) and round_id=".$roundId]);
		if(!$cb){
			$errCode = 10564;//当前没有战斗
            goto sendErr;
		}
		$crossBattle = $cb->toArray();
		$battleId = $crossBattle['id'];
		/*$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
		if(!$battleId){
			$errCode = 10565;//当前没有战斗
            goto sendErr;*/
		$crossBattle = $CrossBattle->adapter([$crossBattle])[0];
		$crossBattle['attack_area'] = parseArray($crossBattle['attack_area']);
		$crossBattle['attack_area'] = array_map('intval', $crossBattle['attack_area']);
		$CrossGuild = new CrossGuild;
		$CrossGuild->battleId = $battleId;
		$guild1 = $CrossGuild->getGuildInfo($crossBattle['guild_1_id']);
		$crossBattle['guild_1_name'] = $guild1['name'];
		$crossBattle['guild_1_avatar'] = $guild1['icon_id'];
		$guild2 = $CrossGuild->getGuildInfo($crossBattle['guild_2_id']);
		$crossBattle['guild_2_name'] = $guild2['name'];
		$crossBattle['guild_2_avatar'] = $guild2['icon_id'];
		//$crossBattle['start_time'] = strtotime($crossBattle['start_time']);
		//$crossBattle['change_time'] = strtotime($crossBattle['change_time']);
		$topPlayer = [];
		if(in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_CLAC, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND_CLAC, CrossBattle::STATUS_FINISH])){
			if(in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_CLAC, CrossBattle::STATUS_DEFEND_READY])){
				$ad = [1=>'attack', 2=>'defend'];
			}else{
				$ad = [2=>'attack', 1=>'defend'];
			}
			foreach($ad as $_i => $_d){
				$topPlayer[$_d] = [];
				$ret = CrossPlayer::find(['battle_id='.$battleId.' and guild_id='.$crossBattle['guild_'.$_i.'_id'].' and status>0 and kill_soldier>0', 'order'=>'kill_soldier desc', 'limit'=>5])->toArray();
				foreach($ret as $_r){
					$topPlayer[$_d][] = [
						'player_id'=>$_r['player_id'],
						'nick'=>$_r['nick'],
						'avatar_id'=>$_r['avatar_id'],
						'kill_soldier'=>$_r['kill_soldier'],
					];
				}
			}
		}
		
		echo $this->data->send(['battleInfo'=>$crossBattle, 'topPlayer'=>$topPlayer]);
		exit;
		sendErr:
		echo $this->data->sendErr($errCode);
		exit;
	}
	
	public function setUnitAction(){
		return;
		global $config;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$position = floor(@$post['position']);
		$unit = @$post['unit'];
		if(!is_array($unit) || !checkRegularNumber($position))
			exit;
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
				throw new Exception(10591);//比赛已经结束
			}
			
			//检查玩家状态
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerGeneral = new CrossPlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
			$PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			$PlayerSoldier = new CrossPlayerSoldier;
			$PlayerSoldier->battleId = $battleId;
			$PlayerArmy = new CrossPlayerArmy;
			$PlayerArmy->battleId = $battleId;
			$General = new General;
			
			//获取校场是否建造
			/*$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10000);
			}*/

			//检查军团是否空闲
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
				$armyNum = (new CrossPlayer)->getMaxArmyNum($playerId);
				if($position > $armyNum){
					throw new Exception(10002);
				}
			}
			
			//检查武将数量是否超过上限
			if(count($unit) > (new CrossPlayer)->getArmyGeneralNum($playerId)){
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
					if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, $_pau['soldier_num'])){
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
					
					$_general = $General->getByGeneralId($_generalId);
					if($_soldierId && $_soldierNum){
						//检查soldierId todo
						if(!in_array($_soldierId, $this->soldierTypeIds[$_general['soldier_type']])){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						
						//检查带兵上限
						$_bringSoldierMax = $PlayerGeneral->assign($_playerGeneral)->getMaxBringSoldier();
						if(!$_bringSoldierMax){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						$_playerSoldier = $PlayerSoldier->getBySoldierId($playerId, $soldierIdForall);
						if($_playerSoldier){
							$_currentSoldierNum = $_playerSoldier['num'];
						}else{
							$_currentSoldierNum = 0;
						}
						$_soldierNum = min($_soldierNum, $_bringSoldierMax, $_currentSoldierNum);
						//更新剩余士兵
						if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, -$_soldierNum)){
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
				$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
				$CrossPlayerArmyUnit->battleId = $battleId;
				if(!$CrossPlayerArmyUnit->add($playerId, $armyId, $_position, $_generalId, $_soldierId, $_soldierNum)){
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
		global $config;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		$unitPosition = floor(@$post['unitPosition']);
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId, true) || !checkRegularNumber($armyPosition) || !checkRegularNumber($unitPosition))
			exit;
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
				throw new Exception(10592);//比赛已经结束
			}
			
			//检查玩家状态
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerGeneral = new CrossPlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
			$PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			$PlayerSoldier = new CrossPlayerSoldier;
			$PlayerSoldier->battleId = $battleId;
			$PlayerArmy = new CrossPlayerArmy;
			$PlayerArmy->battleId = $battleId;
			//获取校场是否建造
			/*$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10006);
			}*/

			//检查军团是否空闲
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
				$armyNum = (new CrossPlayer)->getMaxArmyNum($playerId);
				if($armyPosition > $armyNum){
					throw new Exception(10008);
				}
			}
			
			//检查武将数量是否超过上限
			if($unitPosition > (new CrossPlayer)->getArmyGeneralNum($playerId)){
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
						if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, $_pau['soldier_num'])){
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
				
				/*if($soldierId){
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
				}*/
				
				//更新军团单位
				$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
				$CrossPlayerArmyUnit->battleId = $battleId;
				if(!$CrossPlayerArmyUnit->add($playerId, $armyId, $unitPosition, $generalId, 0, 0)){
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
		global $config;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		$unitPosition = floor(@$post['unitPosition']);
		$soldierId = @$post['soldierId'];
		$soldierNum = @$post['soldierNum'];
		if(!checkRegularNumber($soldierId, true) || !checkRegularNumber($soldierNum, true) || !checkRegularNumber($armyPosition) || !checkRegularNumber($unitPosition))
			exit;
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
				throw new Exception(10593);//比赛已经结束
			}
			
			//检查玩家状态
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerGeneral = new CrossPlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
			$PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			$PlayerSoldier = new CrossPlayerSoldier;
			$PlayerSoldier->battleId = $battleId;
			$PlayerArmy = new CrossPlayerArmy;
			$PlayerArmy->battleId = $battleId;
			//获取校场是否建造
			/*$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10012);
			}*/

			//检查军团是否空闲
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
				$armyNum = (new CrossPlayer)->getMaxArmyNum($playerId);
				if($armyPosition > $armyNum){
					throw new Exception(10014);
				}
			}
			
			//检查武将数量是否超过上限
			if($unitPosition > (new CrossPlayer)->getArmyGeneralNum($playerId)){
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
					if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, $_pau['soldier_num'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			$PlayerArmyUnit->_clearDataCache($playerId);
			if(!@$generalId){
				throw new Exception(10017);
			}
			
			$general = (new General)->getByGeneralId($generalId);
			
			if($soldierId){
				//检查soldierId
				if(!in_array($soldierId, $this->soldierTypeIds[$general['soldier_type']])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//检查带兵上限
				$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
				$bringSoldierMax = $PlayerGeneral->assign($playerGeneral)->getMaxBringSoldier();
				if(!$bringSoldierMax){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//获得现有兵数
				$playerSoldier = $PlayerSoldier->getBySoldierId($playerId, $soldierIdForall);
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
					if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, -$soldierNum)){
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
		global $config;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		if(!checkRegularNumber($armyPosition))
			exit;
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
				throw new Exception(10594);//比赛已经结束
			}
			
			//检查玩家状态
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerGeneral = new CrossPlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
			$PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			$PlayerSoldier = new CrossPlayerSoldier;
			$PlayerSoldier->battleId = $battleId;
			$PlayerArmy = new CrossPlayerArmy;
			$PlayerArmy->battleId = $battleId;
			//获取校场是否建造
			/*$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 41)){
				throw new Exception(10020);
			}*/

			//检查军团是否空闲
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
				if(!$_pau['general_id']) continue;
				if($_pau['soldier_id'] || $_pau['last_soldier_id']){
					if(!$_pau['soldier_id'] && $_pau['last_soldier_id']){
						$_pau['soldier_id'] = $_pau['last_soldier_id'];
					}
					//检查带兵上限
					$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_pau['general_id']);
					$_bringSoldierMax = $PlayerGeneral->assign($playerGeneral)->getMaxBringSoldier();
					if(!$_bringSoldierMax){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					//获得现有兵数
					$playerSoldier = $PlayerSoldier->getBySoldierId($playerId, $soldierIdForall);
					if($playerSoldier){
						$currentSoldierNum = $playerSoldier['num'];
					}else{
						$currentSoldierNum = 0;
					}
					$soldierNum = min($_pau['soldier_num']+$currentSoldierNum, $_bringSoldierMax);
					$subSoldierNum = $soldierNum - $_pau['soldier_num'];
					//更新士兵数
					if($subSoldierNum){
						if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, -$subSoldierNum)){
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
					if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, -$fitableSoldier['num'])){
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
     * 购买士兵
     * type，1.元宝，2.贡献
     * 
     * @return <type>
     */
	public function buySoldierAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$num = floor(@$post['num']);
		$type = floor(@$post['type']);
		
		if(!checkRegularNumber($num) || !checkRegularNumber($type))
			exit;
		if(in_array($type, [1, 2]))
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		$db2 = $this->di['db'];
		dbBegin($db);
		dbBegin($db2);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
				throw new Exception(10595);//比赛已经结束
			}
			
			//检查玩家状态
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
			$soldierCountUnit = (new WarfareServiceConfig)->dicGetOne('wf_reinforcement_soldier_count');
			$soldierCountLimit = (new WarfareServiceConfig)->dicGetOne('wf_soldier_count_limit');
			if($type == 2){
				$soldierCost = (new WarfareServiceConfig)->dicGetOne('wf_reinforcement_soldier_price');
			}else{
				$soldierCost = (new WarfareServiceConfig)->dicGetOne('wf_reinforcement_soldier_price_gem');
			}
			
			//获取当前数量
			$currentNum = 0;
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			$crossPlayerArmyUnit = $CrossPlayerArmyUnit->getByPlayerId($playerId);
			foreach($crossPlayerArmyUnit as $_u){
				$currentNum += $_u['soldier_num'];
			}
			
			$CrossPlayerSoldier = new CrossPlayerSoldier;
			$CrossPlayerSoldier->battleId = $battleId;
			$crossPlayerSoldier = $CrossPlayerSoldier->getBySoldierId($playerId, $soldierIdForall);
			if($crossPlayerSoldier){
				$currentNum += $crossPlayerSoldier['num'];
			}
			
			//检查购买后的上限值
			$soldierNumAdd = $num*$soldierCountUnit;
			if($currentNum >= $soldierCountLimit){
				throw new Exception(10596);//士兵数超过上限
			}
			
			//cost
			$Cost = new Cost;
			if(!$Cost->updatePlayer($playerId, $soldierCost, 0, $num)){
				if($type == 2){
					throw new Exception(10597);//荣誉不足
				}else{
					throw new Exception(10598);//元宝不足
				}
			}
			
			//增加士兵
			if(!$CrossPlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, $soldierNumAdd)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CrossCommonLog)->add($battleId, $playerId, $guildId, '购买士兵[type='.$type.']');
			
			dbCommit($db);
			dbCommit($db2);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			dbRollback($db2);

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
	
	public function showBlockNQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		//$areaList = @$post['areaList'];
		$queueList = @$post['queueList'];
		
		//获取battleId
		$CrossBattle = new CrossBattle;
		//判断是否跨服战中
		$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
		if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
			$this->battleInfoAction();
			exit;
		}
		
		$ad = (new CrossBattle)->getADGuildId($crossBattle);
		$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		$areas = $this->getViewArea($guildId, $ad, $crossBattle);
		
		$result1 = $this->_showArea($battleId, $areas, $guildId, $err1);
		$result2 = $this->_showQueue($crossBattle, $player, $queueList, $err2);
		$catapult = $this->_showCatapultTarget($player, $crossBattle);
		
		$crossBattle = $CrossBattle->adapter([$crossBattle])[0];
		$crossBattle['attack_area'] = parseArray($crossBattle['attack_area']);
		$crossBattle['attack_area'] = array_map('intval', $crossBattle['attack_area']);
		//$CrossGuild = new CrossGuild;
		//$CrossGuild->battleId = $battleId;
		//$guild1 = $CrossGuild->getGuildInfo($crossBattle['guild_1_id']);
		$guild1 = $this->getBnqCache('guild', $battleId, $crossBattle['guild_1_id']);
		$crossBattle['guild_1_name'] = $guild1['name'];
		$crossBattle['guild_1_avatar'] = $guild1['icon_id'];
		//$guild2 = $CrossGuild->getGuildInfo($crossBattle['guild_2_id']);
		$guild2 = $this->getBnqCache('guild', $battleId, $crossBattle['guild_2_id']);
		$crossBattle['guild_2_name'] = $guild2['name'];
		$crossBattle['guild_2_avatar'] = $guild2['icon_id'];
		
		//$CrossPlayer = new CrossPlayer;
		//$CrossPlayer->battleId = $battleId;
		//$crossPlayer = $CrossPlayer->getByPlayerId($playerId, true);
		$crossPlayer = $this->getBnqCache('player', $battleId, $playerId);
		//$crossPlayer = $CrossPlayer->adapter([$crossPlayer])[0];
		
		//$result3 = $this->_getSpBuild($battleId);

		if(!$err1 && !$err2){
			echo $this->data->send(['block'=>$result1, 'queue'=>$result2, 'battleInfo'=>$crossBattle, 'catapult'=>$catapult, 'crossPlayer'=>$crossPlayer]);
		}else{
			if($err1)
				echo $this->data->sendErr($err1);
			else
				echo $this->data->sendErr($err2);
		}
	}
	
	/**
     * 取队列
     * 
	 * ```php
	 * /map/showQueue/
     * postData: json={"blockList":[]}
     * return: json{"Map":""}
	 * ```
	 * 
     */
	public function showQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$blockList = @$post['blockList'];
		
		//获取battleId
		$CrossBattle = new CrossBattle;
		//判断是否跨服战中
		$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
		if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
			$errCode = 10566;//比赛已经结束
			echo $this->data->sendErr($errCode);
			exit;
		}
		
		$result = $this->_showQueue($crossBattle, $player, $blockList, $err);
		
		if(!$err){
			echo $this->data->send($result);
		}else{
			echo $this->data->sendErr($err);
		}

	}
	
	public function _showQueue($crossBattle, $player, $blockList, &$err=0){
		global $config;
		$playerId = $player['id'];
		if(!is_array($blockList))
			exit;
		foreach($blockList as $_b){
			if(!checkRegularNumber($_b, true))
				exit;
		}
		
		try {
			$battleId = $crossBattle['id'];
			
			//根据battleId查找map_type
			$mapType = $crossBattle['map_type'];
			
			//获取我的坐标
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//计算area
			$ad = (new CrossBattle)->getADGuildId($crossBattle);
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$areas = $this->getViewArea($guildId, $ad, $crossBattle);
			/*if($crossPlayer['is_in_map']){
				$areaId = (new CrossMap)->getByXy($battleId, $crossPlayer['x'], $crossPlayer['y'])['area'];
			}elseif($crossPlayer['prev_x'] && $crossPlayer['prev_y']){
				$areaId = (new CrossMapConfig)->getAreaByXy($crossBattle['map_type'], $crossPlayer['prev_x'], $crossPlayer['prev_y']);
			}else{
				$areaId = 1;
			}*/
			
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$sortGatherReturn = [];
			//转化xy
			//$xys = array();
			$ret2 = array();
			foreach($blockList as $_b){
				$_xy = CrossMap::calcXyByBlock($_b);
				$_xy = array(
					'from_x'=>max(0, $_xy['from_x']-12),
					'to_x'=>min($this->mapXBorderEnd, $_xy['to_x']+12),
					'from_y'=>max(0, $_xy['from_y']-12),
					'to_y'=>min($this->mapYBorderEnd, $_xy['to_y']+12),
				);
				//$ret = $PlayerProjectQueue->find(['status=1 and battle_id='.$battleId.' and area in ('.join(',', $areas).') and (player_id='.$playerId.' or ((from_x>='.$_xy['from_x'].' or to_x>='.$_xy['from_x'].') and (from_x<='.$_xy['to_x'].' or to_x<='.$_xy['to_x'].') and (from_y>='.$_xy['from_y'].' or to_y>='.$_xy['from_y'].') and (from_y<='.$_xy['to_y'].' or to_y<='.$_xy['to_y'].')))'])->toArray();//todo
				$ret = $PlayerProjectQueue->find(['battle_id='.$battleId.' and status=1 and area in ('.join(',', $areas).')'])->toArray();//todo
				//过滤2
				$p3 = array('x'=>floor(($_xy['from_x'] + $_xy['to_x'])/2), 'y'=>floor(($_xy['from_y'] + $_xy['to_y'])/2));
				$r = sqrt(pow(floor(abs($_xy['from_x'] - $_xy['to_x'])/2), 2) + pow(floor(abs($_xy['from_y'] - $_xy['to_y'])/2), 2));
				foreach($ret as $_r){
					if($_r['player_id'] == $playerId){
						$ret2[$_r['id']] = $_r;
					}else{
						$dis = $this->GetNearestDistance(array('x'=>$_r['from_x'], 'y'=>$_r['from_y']), array('x'=>$_r['to_x'], 'y'=>$_r['to_y']), $p3);
						if($dis <= $r){
							$ret2[$_r['id']] = $_r;
						}
					}
				}
			}
			
			$queue = array();
			$playerIds = array();
			$mapXys = array();
			$PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			foreach($ret2 as $_r){
				//获取部队展现形式
				$_at = Cache::db(CACHEDB_PLAYER, 'Cross')->get('queueSoldierType:'.$_r['id']);
				if($_at){
					$_r['army_type'] = $_at;
				}else{
					$_r['army_type'] = [];
					if($_r['army_id']){
						if($_pau = $PlayerArmyUnit->getByArmyId($_r['player_id'], $_r['army_id'])){
							foreach($_pau as $__pau){
								if(!$__pau['soldier_id']) continue;
								$_r['army_type'][$__pau['general_id']] = substr($__pau['soldier_id'], 0, 1)*1;
							}
						}
						if(!$_r['army_type']){//无兵队列不显示
							continue;
						}
					}
					Cache::db(CACHEDB_PLAYER, 'Cross')->set('queueSoldierType:'.$_r['id'], $_r['army_type']);
				}
				$playerIds[] = $_r['player_id'];
				
				
				$queue[$_r['id']] = $_r;
				$mapXys[$_r['to_map_id']] = ['x'=>$_r['to_x'], 'y'=>$_r['to_y']];
				$mapXys[$_r['from_map_id']] = ['x'=>$_r['from_x'], 'y'=>$_r['from_y']];
			}
			$queue = filterFields($queue, true, ['carry_gold', 'carry_food', 'carry_wood', 'carry_stone', 'carry_iron', 'carry_soldier']);
			$queue = $PlayerProjectQueue->afterFindQueue($queue);
			
			//整理顺序，与我有关，有我盟友有关必发，截取100条
			/*
			$myQ = [];
			$otherQ = [];
			foreach($queue as $_k => $_q){
				if($_q['player_id'] == $playerId ||
				$_q['guild_id'] == $player['guild_id'] ||
				$_q['target_player_id'] == $playerId
				){
					$myQ[$_k] = $_q;
				}else{
					$otherQ[$_k] = $_q;
				}
			}
			$otherQ = array_slice($otherQ, 0, 70-count($myQ), true);
			$queue = $myQ + $otherQ;
			*/
			
			$guildIds = array();
			//获取相关玩家信息
			$players = array();
			$playerIds = array_unique($playerIds);
			foreach($playerIds as $_playerId){
				//$_player = $CrossPlayer->getByPlayerId($_playerId);
				$_player = $this->getBnqCache('player', $battleId, $_playerId);
				if(!$_player)
					continue;
				$players[$_playerId] = $_player;
				if($_player['guild_id']){
					$guildIds[] = $_player['guild_id'];
				}
			}
			//$players = filterFields($players, true, ['uuid','levelup_time','talent_num_total','talent_num_remain','general_num_total','general_num_remain','army_num','army_general_num','queue_num','move','move_max','gold','food','wood','stone','iron','silver','point','rmb_gem','gift_gem','valid_code']);
			
			//获取相关联盟信息
			$Guild = new CrossGuild;
			$Guild->battleId = $battleId;
			$guildIds = array_unique($guildIds);
			$guilds = array();
			foreach($guildIds as $_guildId){
				//$_guild = $Guild->getGuildInfo($_guildId);
				$_guild = $this->getBnqCache('guild', $battleId, $_guildId);
				if(!$_guild)
					continue;
				$guilds[$_guildId] = $_guild;
			}
			
			//获取map相关信息
			$Map = new CrossMap;
			$mapElement = array();
			foreach($mapXys as $_mapId => $_mapXy){
				//$_map = $Map->getByXy($battleId, $_mapXy['x'], $_mapXy['y']);
				$_map = $this->getBnqCache('map', $battleId, ['x'=>$_mapXy['x'], 'y'=>$_mapXy['y']]);
				if(!$_map)
					continue;
				$mapElement[$_mapId] = [
					'map_element_id'=>$_map['map_element_id']*1,
					'player_id'=>$_map['player_id']*1,
					'guild_id'=>$_map['guild_id']*1,
				];
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		if(!$err){
			return array('Queue'=>$queue, 'Player'=>$players, 'Guild'=>$guilds, 'MapElement'=>$mapElement);
		}else{
			return false;
		}
	}
	
	/**
     * 获取队伍信息
     * 
     * 
     * @return <type>
     */
	public function getQueueInfoAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}

		try {
			//获取battleId
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
				throw new Exception(10599);//比赛已经结束
			}
		
			//获取队列
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getById($queueId);
			if(!$ppq){
				throw new Exception(10331);//找不到队列
			}
			//验证状态
			if($ppq['status'] != 1){
				throw new Exception(10332);//队列已经完成
			}
			
			//验证是否是我的队列
			if($ppq['guild_id'] != $guildId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$ppqs = [$ppq];
			
			//获取军团信息
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			$ret = [];
			foreach($ppqs as $_ppq){
				$_ret = [];
				$_player = $Player->getByPlayerId($_ppq['player_id']);
				$pau = $PlayerArmyUnit->getByArmyId($_ppq['player_id'], $_ppq['army_id']);
				if(!$pau){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$_ret['player_id'] = $_player['player_id'];
				$_ret['player_nick'] = $_player['nick'];
				$_ret['army'] = [];
				foreach($pau as $_pau){
					$_tmp = [];
					$_tmp['general_id'] = $_pau['general_id'];
					$_tmp['soldier_id'] = $_pau['soldier_id'];
					$_tmp['soldier_num'] = $_pau['soldier_num'];
					$_ret['army'][] = $_tmp;
				}
				$ret[] = $_ret;
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('armyInfo'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 获取公会成员位置
     * 
     * 
     * @return <type>
     */
	public function getGuildPositionAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];

		try {
			//获取battleId
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
				throw new Exception(10600);//比赛已经结束
			}
			
			
			$CrossMap = new CrossMap;
			$result = $CrossMap->find(['battle_id='.$battleId.' and guild_id='.$guildId.' and map_element_origin_id=15'])->toArray();
			$ret = [];
			foreach($result as $_r){
				$ret[] = ['x'=>$_r['x'], 'y'=>$_r['y'], 'area'=>$_r['area']];
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('guildPosition'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 队列战斗结果
     * 
     * 
     * @return <type> 0：无数据，1：队列还未处理，2：无战斗，3：战斗胜利，4：战斗失败
     */
	public function queueBattleRetAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId))
			exit;
		
		try {
			//判断是否跨服战中
			$CrossBattle = new CrossBattle;
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10601);//比赛已经结束
			}
			
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$queue = $PlayerProjectQueue->getById($queueId);
			
			$battleFlag = 0;
			if(!$queue){
				//无数据
			}else{
				if($queue['status'] == 1){
					$battleFlag = 1;
				}else{
					$battleFlag = $queue['battle']+2;
				}
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		
		if(!$err){
			echo $this->data->send(array('battle'=>$battleFlag));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 获取去往坐标时间
     * 
     * @param <type> $type 行军种类：1.采集，2.打怪，3.出征，4.侦查，5.搬运资源,6.集结
     * @return <type>
     */
	public function getGotoTimeAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		//$armyId = floor(@$post['armyId']);
		$type = floor(@$post['type']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			exit;
		}
		if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
			exit;
		if(!in_array($type, array(1, 2, 3, 4, 5, 6)))
			exit;

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10602);//比赛已经结束
			}
			
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取地图点信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $x, $y);
			if(!$map)
				throw new Exception(10357);//目标不存在
						
			//获取军团
			$PlayerArmy = new CrossPlayerArmy;
			$PlayerArmy->battleId = $battleId;
			$armies = $PlayerArmy->getByPlayerId($playerId);
			
			//计算行军时间
			$needTime = [];
			foreach($armies as $_army){
				$_needTime = CrossPlayerProjectQueue::calculateMoveTime($battleId, $playerId, $crossPlayer['x'], $crossPlayer['y'], $x, $y, $type, $_army['id']);
				$needTime[$_army['id']] = $_needTime;
			}
						
			//如果直接使用体力
			$distance = sqrt(pow($crossPlayer['x'] - $x, 2) + pow($crossPlayer['y'] - $y, 2));

			$needMove = distance2move($distance);
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('time'=>$needTime, 'needMove'=>$needMove));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 召回静止队列
     * queueId： 队列id
     * 
     * @return <type>
     */
	public function callbackStayQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$queueId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10603);//比赛已经结束
			}
			
			//获取队列
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$types = $PlayerProjectQueue->stayTypes;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(10274);//未找到队列
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			if(!$PlayerProjectQueue->callbackQueue($ppq->id, $ppq->to_x, $ppq->to_y, ['playerCallBack'=>true])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			$i = 0;
			while($i < 10){
				if(!$PlayerProjectQueue->findFirst(['id='.$queueId.' and status=1'])){
					break;
				}
				usleep(500000);
				$i++;
			}
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 召回移动队列
     * queueId： 队列id
     * 
     * @return <type>
     */
	public function callbackMoveQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$queueId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10604);//比赛已经结束
			}
			
			//获取队列
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$types = $PlayerProjectQueue->moveTypes;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断队列类型
			if(!isset($types[$ppq->type])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取返回type
			$returnType = $types[$ppq->type];
			
			//消耗召回道具
			$itemId = 21500;
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->drop($playerId, $itemId)){
				throw new Exception(10221);
			}
			
			if(!$PlayerProjectQueue->callbackQueue($ppq->id, $ppq->to_x, $ppq->to_y)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
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
     * 加速队列
     * queueId： 队列id
     * 
     * @return <type>
     */
	public function acceQueueAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}
		$itemId = 52119;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$queueId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		$db2 = $this->di['db'];
		dbBegin($db);
		dbBegin($db2);

		try {
			//获取队列
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(10636);//无法加速，队列已经达到目的地
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断是否为移动队列
			if($ppq->from_map_id == $ppq->to_map_id){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//如果是行动力加速
			$now = time();
			$Player = new Player;
			//消耗道具
			$accTimeRate = 0.5;
			$v = 1 / (1-$accTimeRate);
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->drop($playerId, $itemId)){
				throw new Exception(10222);
			}
			
			//重新计算end_time
			if(strtotime($ppq->end_time) < $now){
				throw new Exception(10636);//无法加速，队列已经达到目的地
			}
			$accelerateInfo = json_decode($ppq->accelerate_info, true);
			$restSecond = max(0, strtotime($ppq->end_time) - $now);
			$cutSecond = floor($restSecond*$accTimeRate);
			$newEndTime = date('Y-m-d H:i:s', $now + ($restSecond - $cutSecond));
			
			//更新end_time
			if(!isset($accelerateInfo['log'])){
				$accelerateInfo['log'] = [];
			}
			$accelerateInfo['log'][] = array('time'=>$now, 'itemId'=>$itemId, 'cutsecond'=>$cutSecond, 'v'=>$v);
			$accelerateInfo['log'] = array_slice($accelerateInfo['log'], -10);
			
			if(!$ppq->updateAcce($newEndTime, $accelerateInfo)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			dbCommit($db);
			dbCommit($db2);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			dbRollback($db2);

			//清除缓存
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
     * 进入战场
     * 
     * 
     * @return <type>
     */
	public function enterBattlefieldAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		/*$post = getPost();
		$armyIds = @$post['armyIds'];
		if(!is_array($armyIds) || count($armyIds)>(new CrossPlayer)->getMaxArmyNum()){
			exit;
		}*/

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		$db2 = $this->di['db'];
		dbBegin($db);
		dbBegin($db2);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$crossBattle || $crossBattle['status'] >= CrossBattle::STATUS_DEFEND_CLAC){
				throw new Exception(10605);//比赛已经结束
			}
			if($crossBattle['status'] == CrossBattle::STATUS_READY){
				throw new Exception(10646);//比赛还未开始
			}
			
			//检查玩家状态
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($crossPlayer['status'] > 0){
				throw new Exception(10606);//玩家已经进入场地
			}
			
			//查看是否有在野外的部队
			if((new PlayerProjectQueue)->findFirst(['player_id='.$playerId.' and status=1'])){
				throw new Exception(10607);//请召回所有野外部队
			}
			
			//查找army是否存在
			/*
			$PlayerArmy = new PlayerArmy;
			$CrossPlayerArmy = new CrossPlayerArmy;
			$CrossPlayerArmy->battleId = $battleId;
			foreach($armyIds as $_armyId){
				$_army = $PlayerArmy->getByArmyId($playerId, $_armyId);
				if(!$_army)
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$CrossPlayer->cpData($playerId, $config->server_id);
			
			$CrossPlayerGeneral = new CrossPlayerGeneral;
			$CrossPlayerGeneral->battleId = $battleId;
			if(!$CrossPlayerGeneral->cpData($playerId, $config->server_id)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//cp army
			if(!$CrossPlayerArmy->cpData($playerId, $config->server_id, $armyIds)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			*/
			//初始化士兵
			$CrossPlayerSoldier = new CrossPlayerSoldier;
			$CrossPlayerSoldier->battleId = $battleId;
			$soldierNum = (new WarfareServiceConfig)->dicGetOne('wf_soldier_count_start');
			$CrossPlayerSoldier->find(['battle_id='.$battleId.' and player_id='.$playerId])->delete();
			$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
			if(!$CrossPlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, $soldierNum)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新玩家状态
			$CrossPlayer->alter($playerId, ['status'=>1]);
			
			//增加罩子
			if(!(new Player)->alter($playerId, ['is_in_cross'=>1])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CrossCommonLog)->add($battleId, $playerId, $guildId, '进入战场');

			dbCommit($db);
			dbCommit($db2);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			dbRollback($db2);

			//清除缓存
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
     * 去攻城
     * 
     * 
     * @return <type>
     */
	public function gogogoAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId)){
			exit;
		}
		if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10608);//尚不在比赛中
			}
			
			//获取地图点信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $x, $y);
			if(!$map)
				throw new Exception(10626);//目标未找到
			
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['is_in_map'] || !$crossPlayer['status']){
				throw new Exception(10609);//正在复活中
			}
			
			//判断目标是否是同area
			$playerMap = $Map->getByXy($battleId, $crossPlayer['x'], $crossPlayer['y']);
			if(!$playerMap)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			if($playerMap['area'] != $map['area']){
				throw new Exception(10610);//目标不在同个区域
			}
			
			
			//判断是否非同盟城堡
			$ad = $CrossBattle->getADGuildId($crossBattle);
			if($map['map_element_origin_id'] == 15){//城堡
				if($map['player_id'] == $playerId){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($crossPlayer['guild_id']){
					if($crossPlayer['guild_id'] == $map['guild_id']){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				
				$type = CrossPlayerProjectQueue::TYPE_CITYBATTLE_GOTO;
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 302){//城门
				//判断是否为攻击方
				if($ad['attack'] != $crossPlayer['guild_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			
				//判断城门血
				if(!$map['durability']){
					throw new Exception(10611);//城门已经攻破
				}
			
				$type = CrossPlayerProjectQueue::TYPE_ATTACKDOOR_GOTO;
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 301){//攻城锤
				
				$Map->rebuildBuilding($map);
				
				//判断血
				if(!$map['durability']){
					throw new Exception(10612);//攻城锤正在修复中，无法入驻
				}
				if($ad['attack'] == $crossPlayer['guild_id']){//攻击方
					
					$type = CrossPlayerProjectQueue::TYPE_HAMMER_GOTO;
				}else{//防守方
					//$type = CrossPlayerProjectQueue::TYPE_ATTACKHAMMER_GOTO;
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 304){//云梯
				$Map->rebuildBuilding($map);
				
				//判断血
				if(!$map['durability']){
					throw new Exception(10613);//云梯正在修复中，无法入驻
				}
				//判断进度
				//$MapElement = new MapElement;
				//$me = $MapElement->dicGetOne($map['map_element_id']);
				if($map['resource'] >= (new WarfareServiceConfig)->dicGetOne('wf_ladder_max_progress')){
					throw new Exception(10614);//天梯建造已经完成
				}
				
				if($ad['attack'] == $crossPlayer['guild_id']){//攻击方
					$type = CrossPlayerProjectQueue::TYPE_LADDER_GOTO;
				}else{//防守方
					//$type = CrossPlayerProjectQueue::TYPE_ATTACKLADDER_GOTO;
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 303){//床弩
				if($ad['defend'] != $crossPlayer['guild_id']){//防守方
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//检查是否有驻守其他床弩
				$condition = ['player_id='.$playerId.' and type='.CrossPlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and status=1'];
				if((new CrossPlayerProjectQueue)->findFirst($condition)){
					throw new Exception(10615);//每人只能同时占领一个床弩
				}
				
				if($map['player_id']){
					throw new Exception(10616);//该床弩已经被盟友占领
				}
				
				$type = CrossPlayerProjectQueue::TYPE_CROSSBOW_GOTO;
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 305){//投石车
				
				//获取攻击方的占领区
				/*$crossBattle = $CrossBattle->getBattle($battleId);
				$attackArea = parseArray($crossBattle['attack_area']);
				
				if((in_array($map['area'], $attackArea) && $ad['attack'] == $crossPlayer['guild_id'])
					|| 
				(!in_array($map['area'], $attackArea) && $ad['defend'] == $crossPlayer['guild_id'])){
					$type = CrossPlayerProjectQueue::TYPE_CATAPULT_GOTO;
				}else{
					throw new Exception(10617);//已经失去该区域的投石车控制权
				}*/
				
				//检查是否有驻守其他
				$condition = ['player_id='.$playerId.' and type='.CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and status=1'];
				if((new CrossPlayerProjectQueue)->findFirst($condition)){
					throw new Exception(10618);//每人只能同时占领一个投石车
				}
				
				if($map['guild_id'] == $guildId && $map['player_id']){
					throw new Exception(10619);//该投石车已经被盟友占领
				}
				
				$type = CrossPlayerProjectQueue::TYPE_CATAPULT_GOTO;
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 306){//大本营
				//判断是否为攻击方
				if($ad['attack'] != $crossPlayer['guild_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			
				//判断血
				if(!$map['durability']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			
				$type = CrossPlayerProjectQueue::TYPE_ATTACKBASE_GOTO;
				$targetInfo = [];
			}else{
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$Map->doBeforeGoOut($battleId, $playerId, $armyId, false);
			
			//计算行军时间
			$CrossPlayerGeneral = new CrossPlayerGeneral;
			$CrossPlayerGeneral->battleId = $battleId;
			//御驾亲征:主动技：所在军团下次攻击城池或城墙时出征时伤害增加|<#0,255,0#>%{num}|%，但行军速度降低|<#0,255,0#>%{num1}|%。
			$moveDebuff = 0;
			if(in_array($type, [CrossPlayerProjectQueue::TYPE_CITYBATTLE_GOTO, CrossPlayerProjectQueue::TYPE_ATTACKDOOR_GOTO])){
				$skillId = 10054;
				if($CrossPlayerGeneral->getSkillsByArmies([$armyId], [$skillId])[$skillId][0]){//有该技能的武将在当前军团内
					$CrossPlayerMasterskill = new CrossPlayerMasterskill;
					$CrossPlayerMasterskill->battleId = $battleId;
					if($CrossPlayerMasterskill->useActive($playerId, $battleId, $skillId, $cpmsId)){
						$cpms = $CrossPlayerMasterskill->findFirst($cpmsId)->toArray();
						$moveDebuff = $cpms['v2'];
						@$targetInfo['skill'][$skillId] += $cpms['v1'];
					}
					
				}
			}
			
			//快马加鞭:军团出发时减少%
			$moveBuff = $CrossPlayerGeneral->getSkillsByArmies([$armyId], [3])[3][0];
			
			$needTime = CrossPlayerProjectQueue::calculateMoveTime($battleId, $playerId, $crossPlayer['x'], $crossPlayer['y'], $x, $y, 3, $armyId, $moveDebuff, $moveBuff);
			if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($crossPlayer['debuff_queuetime']){//缓兵之计
				$needTime += $crossPlayer['debuff_queuetime'];
				$CrossPlayer->alter($playerId, ['debuff_queuetime'=>0]);
			}
			
			//急行军:军团出发时减少
			$needTime -= $CrossPlayerGeneral->getSkillsByArmies([$armyId], [2])[2][0];
			
			
			//建立队列
			$pm = $Map->getByXy($battleId, $crossPlayer['x'], $crossPlayer['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $crossPlayer['x'],
				'from_y' => $crossPlayer['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
				'area' => $pm['area'],
			];
			$PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$needTime = floor(max($needTime, 0));
			if(!$PlayerProjectQueue->addQueue($playerId, $crossPlayer['guild_id'], $map['player_id'], $type, $needTime, $armyId, $targetInfo, $extraData)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			/*
			//通知我方
			if($crossPlayer['guild_id']){
				$PlayerProjectQueue->noticeFight(2, $crossPlayer['guild_id']);
			}else{
				$PlayerProjectQueue->noticeFight(1, $playerId);
			}
			
			//通知敌方
			if($map['guild_id']){
				$PlayerProjectQueue->noticeFight(2, $map['guild_id']);
			}else{
				$PlayerProjectQueue->noticeFight(1, $map['player_id']);
			}*/
			
			/*if($map['map_element_origin_id'] == 15){
				$pushId = (new PlayerPush)->add($map['player_id'], 2, 400007, []);
				socketSend(['Type'=>'attacked', 'Data'=>['playerId'=>[$map['player_id']]]]);
			}*/
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
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
     * 去侦查
     *
     *
     * @return <type>
     */
	public function spyAction(){
		global $config;
		$player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $post     = getPost();
        $x        = floor(@$post['x']);
        $y        = floor(@$post['y']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			exit;
		}
		if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10620);//尚不在比赛中
			}

			//获取地图点信息
			$Map = new CrossMap;
			$map = $Map->getByXy($battleId, $x, $y);
			if(!$map) {
                throw new Exception(10357);//目标不存在
            }

			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['is_in_map'] || !$crossPlayer['status']){
                throw new Exception(10357);//目标不存在
			}

			//判断目标是否是同area
			$playerMap = $Map->getByXy($battleId, $crossPlayer['x'], $crossPlayer['y']);
			if(!$playerMap)
                throw new Exception(10357);//目标不存在
//			if($playerMap['area'] != $map['area']){
//				throw new Exception(10621);//目标不在同个区域
//			}
            $CrossPlayerSoldier = new CrossPlayerSoldier;
            $targetPlayerId = $map['player_id'];
            if($targetPlayerId) {
                $targetCrossPlayer = $CrossPlayer->getByPlayerId($targetPlayerId);
                $data['nick'] = $targetCrossPlayer['nick'];
            }
            //init
            $armyIdArr           = [];
            $data['battle_army'] = [];

			if($map['map_element_origin_id'] == 15){//城堡
                $_army = (new CrossPlayerArmy)->adapter(CrossPlayerArmy::find(["player_id={$targetPlayerId} and status=0 and battle_id={$battleId}"])->toArray());
                if($_army) {
                    foreach($_army as $a) {
                        $armyIdArr[] = $a['id'];
                    }
                }
                $targetCrossPlayer = $CrossPlayer->getByPlayerId($map['player_id']);
                //预备役部队类型、准确数量
                $data['durability']   = (int)$targetCrossPlayer['wall_durability'];//城防值
                $data['reserve_army'] = [];
                $reserveArmy = $CrossPlayerSoldier->adapter($CrossPlayerSoldier->sqlGet("select soldier_id, num from cross_player_soldier where battle_id={$battleId} and player_id={$targetPlayerId}"));
                if($reserveArmy) {
                    $data['reserve_army'] = $reserveArmy;
                }
			}
			elseif($map['map_element_origin_id'] == 305){//投石车catapult
                $_q = CrossPlayerProjectQueue::findFirst("player_id={$targetPlayerId} and type=".CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and status=1');
                if($_q) {
                    $armyIdArr = [$_q->army_id];
                }
                $data['durability'] = (int)$map['durability'];//城防值
            }
            elseif($map['map_element_origin_id'] == 303){//床弩
                $_q = CrossPlayerProjectQueue::findFirst("player_id={$targetPlayerId} and type=".CrossPlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and status=1');
                if($_q) {
                    $armyIdArr = [$_q->army_id];
                }
            } else {
                throw new Exception(10357);//目标不存在
            }
            $CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
            $CrossPlayerGeneral = new CrossPlayerGeneral;
            //防守部队类型、准确数量
            //防守部队武将信息
            foreach($armyIdArr as $amId) {
                $_info = $CrossPlayerArmyUnit->adapter($CrossPlayerArmyUnit->sqlGet("select army_id, general_id, soldier_id, soldier_num from cross_player_army_unit where battle_id={$battleId} and army_id={$amId}"));
                $_generalInfo = $CrossPlayerGeneral->sqlGet("SELECT general_id, star_lv FROM cross_player_general WHERE battle_id={$battleId} AND army_id={$amId};");
                $_generalInfo = Set::combine($_generalInfo, '{n}.general_id', '{n}.star_lv');
                if($_info) {
                    foreach($_info as &$v) {
                        $v['general_star'] = intval(@$_generalInfo[$v['general_id']]);
                    }
                    $data['battle_army'][] = $_info;
                    unset($v);
                }
            }

			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);

		if(!$err){
			echo $this->data->send($data);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 主动技使用
     * 
     * 
     * @return <type>
     */
	public function useSkillAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$skillId = floor(@$post['skillId']);
		if(!checkRegularNumber($generalId) || !checkRegularNumber($skillId))
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10608);//尚不在比赛中
			}
			
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['is_in_map'] || !$crossPlayer['status']){
				throw new Exception(10609);//正在复活中
			}
			
			//检查技能存在
			$CrossPlayerMasterskill = new CrossPlayerMasterskill;
			$CrossPlayerMasterskill->battleId = $battleId;
			$cpms = $CrossPlayerMasterskill->getBySkillId($playerId, $generalId, $skillId);
			if(!$cpms){
				throw new Exception(10647);//技能不存在
			}
			//检查技能次数
			if(!$cpms['rest_times']){
				throw new Exception(10648);//技能次数已经用完
			}
			
			//具体效果
			$ret = [];
			switch($skillId){
				case 10054://御驾亲征:所在军团下次攻击城池或城墙时出征时伤害增加|<#0,255,0#>%{num}|%，但行军速度降低|<#0,255,0#>%{num1}|%。
					
					$needActive = 1;
					$skillNotice = [];
					
				break;
				case 10098://业火冲天:对城门，攻城锤或云梯造成|<#0,255,0#>%{num}|伤害
					/*$x = floor(@$post['x']);
					$y = floor(@$post['y']);
					if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					*/
					$attackBuildRet = $this->skillAttackBuild($crossBattle, $guildId, $crossPlayer, $cpms);
					
					//判断对象器械是否属于
					$needActive = 0;
					
					$skillNotice = [];
					$ret = $attackBuildRet['notices'];
				break;
				case 10105://破胆怒吼
					$skillRet = $this->skillRoar($crossBattle, $guildId, $crossPlayer, $cpms);
					if(!$skillRet){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					$needActive = 0;
					
					$ret = $skillNotice = ['fromNick'=>$crossPlayer['nick'], 'originIds'=>$skillRet['originIds'], 'toArea'=>$skillRet['area'], 'toPlayerIds'=>$skillRet['toPlayerIds']];
				break;
				case 10110://五雷轰顶:敌军所有下次出征行军时间增加|<#0,255,0#>%{num}|秒
					$values = $cpms['v1'];
					if($crossBattle['guild_1_id'] == $guildId){
						$enemyGuildId = $crossBattle['guild_2_id'];
					}else{
						$enemyGuildId = $crossBattle['guild_1_id'];
					}
					$members = $CrossPlayer->getByGuildId($enemyGuildId);
					$playerIds = [];
					foreach($members as $_m){
						$CrossPlayer->alter($_m['player_id'], ['debuff_queuetime'=>'GREATEST(debuff_queuetime, '.$values.')']);
						$playerIds[] = $_m['player_id'];
					}
					
					$needActive = 0;
					
					$ret = $skillNotice = ['fromNick'=>$crossPlayer['nick'], 'second'=>$values, 'toPlayerIds'=>$playerIds];
				break;
			}
			$ret['type'] = 'skill_'.$skillId;

			//使用技能
			if(!$CrossPlayerMasterskill->useTimes($playerId, $battleId, $generalId, $skillId, $needActive)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($skillNotice)
				(new QueueCross)->crossNotice($battleId, 'skill_'.$skillId, $skillNotice);
			
			if(!(new CrossBattle)->isActivity($battleId)){
				throw new Exception(10608);//尚不在比赛中
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send(['notice'=>$ret]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 主动技：业火冲天
     * 
     * @param <type> $crossBattle 
     * @param <type> $guildId 
     * @param <type> $crossPlayer 
     * @param <type> $cpms 
     * 
     * @return <type>
     */
	public function skillAttackBuild($crossBattle, $guildId, $crossPlayer, $cpms){
		/*$arr = [
			1 => [
				'attack'=>[
					1 => [[1], 302],
					2 => [[2], 0],
					3 => [[3], 302],
					4 => [[4], 302],
					5 => [[5], 0],
				],
				'defend'=>[
					1 => [[1], 301],
					2 => [[2], 304],
					3 => [[3, 1], 301],
					4 => [[4], 0],
					5 => [[5, 2], 304],
				],
			],
		];*/
		$playerId = $crossPlayer['player_id'];
		$battleId = $crossBattle['id'];
		$area = $crossPlayer['area'];
		
		$CrossBattle = new CrossBattle;
		$ad = $CrossBattle->getADGuildId($crossBattle);
		
		if($guildId == $ad['attack']){
			$side = ActiveSkillTarget::SIDE_ATTACK;
		}else{
			$side = ActiveSkillTarget::SIDE_DEFEND;
		}
		
		$ActiveSkillTarget = new ActiveSkillTarget;
		$ast = $ActiveSkillTarget->getTarget(ActiveSkillTarget::SCENE_CROSS, 10098, $side, $area);
		if(!$ast){
			throw new Exception(10649);//未找到可攻击目标
		}
		
		$target = $ast['target'];
		$notices = [];
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		$Map = new CrossMap;
		$DispatcherTask = new CrossDispatcherTask;
		
		foreach($target as $_t){
			$_targetArea = $_t[0];
			$_targetOriginIds = $_t[1];
			
			//查找地图元素
			$maps = $Map->find(['battle_id='.$battleId.' and area='.$_targetArea.' and map_element_origin_id in ('.join(',', $_targetOriginIds).') and guild_id <> '.$crossPlayer['guild_id']])->toArray();
			if($_targetOriginIds[0] == 15){
				shuffle($maps);
			}

			foreach($maps as $map){
				$x = $map['x'] = $map['x']*1;
				$y = $map['y'] = $map['y']*1;
				
				//lock
				$perTry = 1;
				$tryLimit = 5;
				$i = 0;
				global $inDispWorker;
				$inDispWorker = true;
				while(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y)){
					sleep($perTry);
					$i++;
					if($i >= $tryLimit){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				if($i){
					$map = $Map->getByXy($battleId, $x, $y);
				}
				
				//不同器械
				switch($map['map_element_origin_id']){
					case 302://城门
						//判断城门血
						if(!$map['durability']){
							goto unlock;
							break;
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//城门扣血
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						(new CrossCommonLog)->add($map['battle_id'], $playerId, $guildId, '攻击城门['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$crossPlayer['nick'], 'toNick'=>'', 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCross)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果破门
						if($map['durability'] <= $reduceDurability){
							//更新公会占领区域
							$CrossBattle->updateAttackArea($map['battle_id'], $map['next_area']);
							
							//撤离所有下一个区域的敌方占领投石车和床弩
							$PlayerProjectQueue->callbackCatapult($map['battle_id'], $map['next_area']);
							$PlayerProjectQueue->callbackCrossbow($map['battle_id'], $map['next_area']);
							
							//遣返本区攻城锤内部队
							$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area']);
							
							//日志
							(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '破门['.$map['area'].']|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')');
							
							(new QueueCross)->crossNotice($map['battle_id'], 'doorBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
							
						}
					break;
					case 301://攻城锤
						//判断是否占领
						if(!$map['guild_id']){
							goto unlock;
							//throw new Exception(10652);//目标攻城锤未处于可攻击状态
							break;
						}
						
						//修复
						$Map->rebuildBuilding($map);
						
						//检查血
						if(!$map['durability']){
							goto unlock;
							//throw new Exception(10653);//攻城锤处于修理状态
							break;
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//扣血
						$recoverTime = (new WarfareServiceConfig)->dicGetOne('wf_warhammer_respawn_time');
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						
						(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻击攻城锤['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$crossPlayer['nick'], 'toNick'=>'', 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCross)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果攻城锤血0，遣返所有攻城锤部队
						if($map['durability'] <= $reduceDurability){
							$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area'], $map['id']);
							
							//日志
							(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻城锤0血['.$map['area'].']|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')');
							(new QueueCross)->crossNotice($map['battle_id'], 'hammerBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
						}
					break;
					case 304://云梯
						//判断是否占领
						if(!$map['guild_id']){
							goto unlock;
							//throw new Exception(10654);//目标云梯未处于可攻击状态
							break;
						}
						
						//刷新云梯进度
						$condition = ['type='.CrossPlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$map['id'].' and status=1'];
						$ppqs = $PlayerProjectQueue->find($condition)->toArray();
						if($ppqs){
							(new QueueCross)->refreshLadder($ppqs[0], $ppqs, $map, time(), $finishLadder);
							if($finishLadder){
								goto unlock;
							}
						}
						
						//检查进度
						$ladderMaxProgress = (new WarfareServiceConfig)->dicGetOne('wf_ladder_max_progress');
						if($map['resource'] >= $ladderMaxProgress){
							goto unlock;
							//throw new Exception(10655);//目标云梯已经建造完成
							break;
						}
						
						//修复
						$Map->rebuildBuilding($map);
						
						//检查血
						if(!$map['durability']){
							goto unlock;
							//throw new Exception(10656);//云梯处于修理状态
							break;
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//扣血
						$recoverTime = (new WarfareServiceConfig)->dicGetOne('wf_ladder_respawn_time');
						$Player = new CrossPlayer;
						$Player->battleId = $battleId;
						$playerIds = Set::extract('/player_id', $Player->getByGuildId($map['guild_id']));
						$CrossPlayerGeneral = new CrossPlayerGeneral;
						$CrossPlayerGeneral->battleId = $battleId;
						$recoverTimeBuff = $CrossPlayerGeneral->getSkillsByPlayers($playerIds, [24])[24][0];
						$recoverTime -= $recoverTimeBuff;
						$recoverTime = floor($recoverTime);
						
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						
						(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻击云梯['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$crossPlayer['nick'], 'toNick'=>'', 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCross)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果云梯血0，遣返所有云梯部队
						if($map['durability'] <= $reduceDurability){
							$PlayerProjectQueue->callbackLadder($map['battle_id'], $map['id']);
							
							//日志
							(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '天梯0血['.$map['area'].']|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')');
							(new QueueCross)->crossNotice($map['battle_id'], 'ladderBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
							
						}
					break;
					case 15:
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//玩家扣血
						$Player = new CrossPlayer;
						$Player->battleId = $battleId;
						$targetPlayer = $Player->getByPlayerId($map['player_id']);
						if(!$targetPlayer || !$targetPlayer['is_in_map'] || !$targetPlayer['status']){
							goto unlock;
							break;
						}
						if(!$Player->alter($map['player_id'], ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-'.$reduceDurability.')'])){
							goto unlock;
							break;
						}
						$crossPlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
						
						//日志
						(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻击玩家[defend='.$targetPlayer['player_id'].'('.$targetPlayer['guild_id'].')]|扣血-'.$reduceDurability.',剩余'.max(0, $targetPlayer['wall_durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$crossPlayer['nick'], 'toNick'=>$targetPlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $targetPlayer['wall_durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCross)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果玩家血0，删除城堡
						if($targetPlayer['wall_durability'] <= $reduceDurability){
							
							//不屈之力：城防首次被击破时可恢复|<#72,255,98#>%{num}||<#3,169,235#>%{numnext}|城防值
							if(!$targetPlayer['skill_first_recover']){
								$CrossPlayerGeneral = new CrossPlayerGeneral;
								$CrossPlayerGeneral->battleId = $battleId;
								$recoverhp = $CrossPlayerGeneral->getSkillsByPlayer($targetPlayer['player_id'], null, [10089])[10089][0];
								if($recoverhp){
									$Player->alter($targetPlayer['player_id'], ['wall_durability'=>'wall_durability+'.$recoverhp, 'skill_first_recover'=>1]);
									(new CrossCommonLog)->add($battleId, $targetPlayer['player_id'], $targetPlayer['guild_id'], '玩家发动不屈之力|加血+'.$recoverhp);
									(new QueueCross)->crossNotice($battleId, 'skill_10089', ['nick'=>$targetPlayer['nick']]);
									goto attackPlayerEnd;
								}
							}
										
							$Map->delPlayerCastle($battleId, $targetPlayer['player_id']);
							
							//一血通知
							$CrossBattle->updateFirstBlood($crossBattle, $crossPlayer, $targetPlayer);
							
							//连杀
							$Player->addContinueKill($playerId, $crossPlayer, $crossBattle);
							
							//日志
							(new CrossCommonLog)->add($battleId, $targetPlayer['player_id'], $targetPlayer['guild_id'], '玩家扑街|byPlayerId='.$playerId.'('.$targetPlayer['guild_id'].')');
							
							(new QueueCross)->crossNotice($battleId, 'playerDead', ['from_nick'=>$crossPlayer['nick'], 'to_nick'=>$targetPlayer['nick']]);
						}
						attackPlayerEnd:
					break;
				}
				
				//unlock
				unlock:
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
				
				//攻击城池仅随机一个
				if($notices && $map['map_element_origin_id'] == 15){
					break;
				}
			}
			
			if($notices){
				break;
			}
		}
		
		if(!$notices){
			throw new Exception(10650);//未找到目标
		}
		
		return ['notices'=>$notices];
		
		
		/*
		$build = @$arr[$crossBattle['map_type']][$side][$area];
		if(!$build){
			throw new Exception(10649);//未找到可攻击目标
		}
		$targetAreas = $build[0];
		$originId = $build[1];
		$notice = [];
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$Map = new CrossMap;
		
		foreach($targetAreas as $targetArea){
			if($originId){
				//查找地图元素
				$map = $Map->findFirst(['battle_id='.$battleId.' and area='.$targetArea.' and map_element_origin_id='.$originId]);

				if(!$map){
					throw new Exception(10650);//未找到目标
				}
				$map = $map->toArray();
				$x = $map['x'] = $map['x']*1;
				$y = $map['y'] = $map['y']*1;
				
				$DispatcherTask = new CrossDispatcherTask;
				//lock
				$perTry = 1;
				$tryLimit = 5;
				$i = 0;
				while(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y)){
					sleep($perTry);
					$i++;
					if($i >= $tryLimit){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				if($i){
					$map = $Map->getByXy($battleId, $x, $y);
				}
				
				//不同器械
				switch($originId){
					case 302://城门
						
						//判断城门血
						if(!$map['durability']){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
							//throw new Exception(10651);//城门已被攻破
							break;
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//城门扣血
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						(new CrossCommonLog)->add($map['battle_id'], $playerId, $guildId, '攻击城门['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
						$notice = ['nick'=>$crossPlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCross)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果破门
						if($map['durability'] <= $reduceDurability){
							
							//更新公会占领区域
							$CrossBattle->updateAttackArea($map['battle_id'], $map['next_area']);
							
							//撤离所有下一个区域的敌方占领投石车和床弩
							$PlayerProjectQueue->callbackCatapult($map['battle_id'], $map['next_area']);
							$PlayerProjectQueue->callbackCrossbow($map['battle_id'], $map['next_area']);
							
							//遣返本区攻城锤内部队
							$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area']);
							
							//日志
							(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '破门['.$map['area'].']|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')');
							
							(new QueueCross)->crossNotice($map['battle_id'], 'doorBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
							
						}
						
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
					break;
					case 301://攻城锤
						//判断是否占领
						if(!$map['guild_id']){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
							//throw new Exception(10652);//目标攻城锤未处于可攻击状态
							break;
						}
						
						//修复
						$Map->rebuildBuilding($map);
						
						//检查血
						if(!$map['durability']){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
							//throw new Exception(10653);//攻城锤处于修理状态
							break;
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//扣血
						$recoverTime = (new WarfareServiceConfig)->dicGetOne('wf_warhammer_respawn_time');
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						
						(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻击攻城锤['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
						$notice = ['nick'=>$crossPlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCross)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果攻城锤血0，遣返所有攻城锤部队
						if($map['durability'] <= $reduceDurability){
							
							$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area'], $map['id']);
							
							//日志
							(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻城锤0血['.$map['area'].']|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')');
							(new QueueCross)->crossNotice($map['battle_id'], 'hammerBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
						}
						
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
					break;
					case 304://云梯
						//判断是否占领
						if(!$map['guild_id']){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
							//throw new Exception(10654);//目标云梯未处于可攻击状态
							break;
						}
						
						//刷新云梯进度
						$condition = ['type='.CrossPlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$map['id'].' and status=1'];
						$ppqs = $PlayerProjectQueue->find($condition)->toArray();
						if($ppqs)
							(new QueueCross)->refreshLadder($ppqs[0], $ppqs, $map, time());
						
						//检查进度
						$ladderMaxProgress = (new WarfareServiceConfig)->dicGetOne('wf_ladder_max_progress');
						if($map['resource'] >= $ladderMaxProgress){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
							//throw new Exception(10655);//目标云梯已经建造完成
							break;
						}
						
						//修复
						$Map->rebuildBuilding($map);
						
						//检查血
						if(!$map['durability']){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
							//throw new Exception(10656);//云梯处于修理状态
							break;
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//扣血
						$recoverTime = (new WarfareServiceConfig)->dicGetOne('wf_ladder_respawn_time');
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						
						(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻击云梯['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
						$notice = ['nick'=>$crossPlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCross)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果云梯血0，遣返所有云梯部队
						if($map['durability'] <= $reduceDurability){
							
							$PlayerProjectQueue->callbackLadder($map['battle_id'], $map['id']);
							
							//日志
							(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '天梯0血['.$map['area'].']|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')');
							(new QueueCross)->crossNotice($map['battle_id'], 'ladderBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
							
						}
						
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
					break;
					default:
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
						throw new Exception(10657);//未找到可攻击目标
				}
				
			}
			
			castle:
			if(!$notice){
				$DispatcherTask = new CrossDispatcherTask;
				$maps = $Map->find(['battle_id='.$battleId.' and area='.$targetArea.' and map_element_origin_id=15 and guild_id <> '.$crossPlayer['guild_id']])->toArray();
				shuffle($maps);
				foreach($maps as $map){
					$x = $map['x'] = $map['x']*1;
					$y = $map['y'] = $map['y']*1;
					
					//lock
					$perTry = 1;
					$tryLimit = 5;
					$i = 0;
					while(!($r = $DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y))){
						sleep($perTry);
						$i++;
						if($i >= $tryLimit){
							break;
						}
					}
					if(!$r && $i >= $tryLimit){
						continue;
					}
					if($i){
						$map = $Map->getByXy($battleId, $x, $y);
					}
					//计算攻击力
					$reduceDurability = $cpms['v1'];
					
					//玩家扣血
					$Player = new CrossPlayer;
					$Player->battleId = $battleId;
					$targetPlayer = $Player->getByPlayerId($map['player_id']);
					if(!$targetPlayer || !$targetPlayer['is_in_map'] || !$targetPlayer['status']){
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
						continue;
					}
					if(!$Player->alter($map['player_id'], ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-'.$reduceDurability.')'])){
						$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
						continue;
					}
					$crossPlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
					
					//日志
					(new CrossCommonLog)->add($map['battle_id'], $playerId, $crossPlayer['guild_id'], '攻击玩家[defend='.$targetPlayer['player_id'].'('.$targetPlayer['guild_id'].')]|扣血-'.$reduceDurability.',剩余'.max(0, $targetPlayer['wall_durability']-$reduceDurability).'|byPlayerId='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')|bySkill='.$cpms['skill_id']);
					$notice = ['fromNick'=>$crossPlayer['nick'], 'toNick'=>$targetPlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $targetPlayer['wall_durability']-$reduceDurability), 'from_x'=>$crossPlayer['x'], 'from_y'=>$crossPlayer['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
					
					//如果玩家血0，删除城堡
					if($targetPlayer['wall_durability'] <= $reduceDurability){
						
						//不屈之力：城防首次被击破时可恢复|<#72,255,98#>%{num}||<#3,169,235#>%{numnext}|城防值
						if(!$targetPlayer['skill_first_recover']){
							$CrossPlayerGeneral = new CrossPlayerGeneral;
							$CrossPlayerGeneral->battleId = $battleId;
							$recoverhp = $CrossPlayerGeneral->getSkillsByPlayer($targetPlayer['player_id'], null, [10089])[10089][0];
							if($recoverhp){
								$Player->alter($targetPlayer['player_id'], ['wall_durability'=>'wall_durability+'.$recoverhp, 'skill_first_recover'=>1]);
								(new CrossCommonLog)->add($battleId, $targetPlayer['player_id'], $targetPlayer['guild_id'], '玩家发动不屈之力|加血+'.$recoverhp);
								(new QueueCross)->crossNotice($battleId, 'skill_10089', ['nick'=>$targetPlayer['nick']]);
								goto a;
							}
						}
									
						$Map->delPlayerCastle($battleId, $targetPlayer['player_id']);
						
						//一血通知
						$CrossBattle->updateFirstBlood($crossBattle, $crossPlayer, $targetPlayer);
						
						//连杀
						$Player->addContinueKill($playerId, $crossPlayer, $crossBattle);
						
						//日志
						(new CrossCommonLog)->add($battleId, $targetPlayer['player_id'], $targetPlayer['guild_id'], '玩家扑街|byPlayerId='.$playerId.'('.$targetPlayer['guild_id'].')');
						
						(new QueueCross)->crossNotice($battleId, 'playerDead', ['from_nick'=>$crossPlayer['nick'], 'to_nick'=>$targetPlayer['nick']]);
					}
					a:
					//unlock
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
					break;
					
				}
			}
			
			if($notice){
				break;
			}
		}
		
		
		if(!$notice){
			throw new Exception(10650);//未找到目标
		}
		
		return ['notice'=>$notice];*/
	}
	
    /**
     * 主动技：破胆怒吼
     * 
     * 
     * @return <type>
     */
	public function skillRoar($crossBattle, $guildId, $crossPlayer, $cpms){
		$playerId = $crossPlayer['player_id'];
		$battleId = $crossBattle['id'];
		$area = $crossPlayer['area'];
		
		$CrossBattle = new CrossBattle;
		$ad = $CrossBattle->getADGuildId($crossBattle);
		
		if($guildId == $ad['attack']){
			$side = ActiveSkillTarget::SIDE_ATTACK;
			$targetGuildId = $ad['defend'];
		}else{
			$side = ActiveSkillTarget::SIDE_DEFEND;
			$targetGuildId = $ad['attack'];
		}
		
		$ActiveSkillTarget = new ActiveSkillTarget;
		$ast = $ActiveSkillTarget->getTarget(ActiveSkillTarget::SCENE_CROSS, 10105, $side, $area);
		if(!$ast){
			throw new Exception(10649);//未找到可攻击目标
		}
		
		$target = $ast['target'];
		$notices = [];
		$PlayerProjectQueue = new CrossPlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		$Map = new CrossMap;
		$DispatcherTask = new CrossDispatcherTask;
		$playerIds = [];
		
		foreach($target as $_t){
			$_targetArea = $_t[0];
			$_targetOriginIds = $_t[1];
						
			foreach($_targetOriginIds as $_originId){
				
				//不同器械
				switch($_originId){
					case 303://床弩
						if(!$CrossPlayerProjectQueue->callbackCrossbow($battleId, $_targetArea, true, $_playerIds)){
							throw new Exception(10659);//操作超时
						}
					break;
					case 305://投石车
						if(!$CrossPlayerProjectQueue->callbackCatapult($battleId, $_targetArea, $targetGuildId, true, $_playerIds)){
							throw new Exception(10660);//操作超时
						}
					break;
				}
				$playerIds = array_merge($playerIds, $_playerIds);
			}
			
			if($playerIds){
				break;
			}
		}
		
		if(!$playerIds){
			throw new Exception(10650);//未找到目标
		}
		
		return ['originIds'=>$_targetOriginIds, 'area'=>$_targetArea, 'toPlayerIds'=>$playerIds];
		/*
		$arr = [
			1 => [
				'attack'=>[
					1 => [3, [303, 305]],//床弩及投石车
					2 => [5, [303]],//床弩
					3 => [4, [305]],//投石车
				],
				'defend'=>[
					1 => [1, [305]],//投石车
					3 => [3, [305]],//投石车
					4 => [4, [305]],//投石车
				],
			],
		];
		
		$playerId = $crossPlayer['player_id'];
		$battleId = $crossBattle['id'];
		$area = $crossPlayer['area'];
		
		$CrossBattle = new CrossBattle;
		$ad = $CrossBattle->getADGuildId($crossBattle);
		
		if($guildId == $ad['attack']){
			$side = 'attack';
			$targetGuildId = $ad['defend'];
		}else{
			$side = 'defend';
			$targetGuildId = $ad['attack'];
		}
		$builds = @$arr[$crossBattle['map_type']][$side][$area];
		if(!$builds){
			throw new Exception(10658);//未找到可攻击目标
		}
		$targetArea = $builds[0];
		
		$DispatcherTask = new CrossDispatcherTask;
		$CrossPlayerProjectQueue = new CrossPlayerProjectQueue;
		$CrossPlayerProjectQueue->battleId = $battleId;
		$playerIds = [];
		foreach($builds[1] as $_build){
			$originId = $_build;
			$_playerIds = [];
			switch($originId){
				case 303://床弩
					if(!$CrossPlayerProjectQueue->callbackCrossbow($battleId, $targetArea, true, $_playerIds)){
						throw new Exception(10659);//操作超时
					}
				break;
				case 305://投石车
					if(!$CrossPlayerProjectQueue->callbackCatapult($battleId, $targetArea, $targetGuildId, true, $_playerIds)){
						throw new Exception(10660);//操作超时
					}
				break;
				default:
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				break;
			}
			$playerIds = array_merge($playerIds, $_playerIds);
		}
		
		return ['originIds'=>$builds[1], 'area'=>$targetArea, 'toPlayerIds'=>$playerIds];*/
	}
	
    /**
     * 控制投石车
     * 
     * 
     * @return <type>
     */
	public function useCatapultAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			exit;
		}
		if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
			exit;

		//锁定
		$DispatcherTask = new CrossDispatcherTask;
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_cross_server'];
		dbBegin($db);

		try {
			$CrossBattle = new CrossBattle;
			//判断是否跨服战中
			$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
			if(!$CrossBattle->isActivity($crossBattle)){
				throw new Exception(10622);//尚不在比赛中
			}
			
			//检查玩家状态
			$CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$crossPlayer = $CrossPlayer->getByPlayerId($playerId);
			if(!$crossPlayer || !$crossPlayer['is_in_map'] || !$crossPlayer['status']){
				throw new Exception(10609);//正在复活中
			}
			
			$perTry = 1;
			$tryLimit = 5;
			$i = 0;
			global $inDispWorker;
			$inDispWorker = true;
			while(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y)){
				sleep($perTry);
				$i++;
				if($i >= $tryLimit){
					throw new Exception(10623);//请稍后重试
				}
			}
			
			$this->catapultAttack($crossPlayer, $guildId, $battleId, $x, $y, $crossBattle);
			//查找我占领的投石车
			/*
			$condition = ['player_id='.$playerId.' and type='.CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and end_time="0000-00-00 00:00:00" and status=1'];
			$ppq = CrossPlayerProjectQueue::findFirst($condition);
			if(!$ppq){
				throw new Exception(10624);//尚未占领投石车
			}
			
			$Map = new CrossMap;
			$catapultMap = $Map->getByXy($battleId, $ppq->to_x, $ppq->to_y);
			if(!$catapultMap || $catapultMap['map_element_origin_id'] != 305)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//检查冷却时间
			$atkcdTime = (new WarfareServiceConfig)->dicGetOne('wf_catapult_atkcolddown');
			if(time() < strtotime($catapultMap['attack_time']) + $atkcdTime){
				throw new Exception(10625);//投石车正在冷却中
			}
			
			//查找目标
			$map = $Map->getByXy($battleId, $x, $y);
			if(!$map || $map['map_element_origin_id'] != 15)
				throw new Exception(10626);//目标未找到
			
			//判断目标是否为敌方城堡
			if($map['guild_id'] == $guildId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断区域是否可见
			$guilds = $CrossBattle->getADGuildId($crossBattle);
			$areas = $this->getViewArea($guildId, $guilds, $crossBattle);
			if(!in_array($map['area'], $areas)){
				throw new Exception(10627);//敌方城堡不在视野范围内
			}
			
			//计算投石车和目标的距离
			$distance = sqrt(pow($ppq->to_x - $x, 2) + pow($ppq->to_y - $y, 2));
			if($distance > $this->catapultDistance){
				throw new Exception(10629);//敌方城堡不在投石车攻击范围内
			}
			
			//计算投石车攻击力
			$formula = (new WarfareServiceConfig)->getValueByKey('wf_catapult_atkpower');
			$power = (new QueueCross)->getArmyPower($battleId, $playerId, $ppq->army_id);
			eval('$reduceDurability = '.$formula.';');
			if(!$reduceDurability){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//玩家扣血
			$Player = new CrossPlayer;
			$Player->battleId = $battleId;
			$crossPlayer = $Player->getByPlayerId($map['player_id']);
			if(!$crossPlayer || !$crossPlayer['is_in_map'] || !$crossPlayer['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$reduceDurability = max(1, $reduceDurability);
			if(!$Player->alter($map['player_id'], ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-'.$reduceDurability.')'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$crossPlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
			
			//日志
			(new CrossCommonLog)->add($battleId, $playerId, $guildId, '投石车攻击玩家[defend='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')]|扣血-'.$reduceDurability.',剩余'.max(0, $crossPlayer['wall_durability']-$reduceDurability).'|byPlayerId='.$playerId.'('.$guildId.')');
			
			//如果玩家血0，删除城堡
			if($crossPlayer['wall_durability'] <= $reduceDurability){
				$Map->delPlayerCastle($battleId, $crossPlayer['player_id']);
				
				//一血通知
				$CrossBattle->updateFirstBlood($crossBattle, $player, $crossPlayer);
				
				//连杀
				$Player->addContinueKill($playerId, $player, $crossBattle);
				
				//日志
				(new CrossCommonLog)->add($battleId, $crossPlayer['player_id'], $crossPlayer['guild_id'], '玩家扑街|byPlayerId='.$playerId.'('.$guildId.')');
				
				(new QueueCross)->crossNotice($battleId, 'playerDead', ['from_nick'=>$player['nick'], 'to_nick'=>$crossPlayer['nick']]);
			}
			
			//更新投石车攻击时间
			$attackTime = date('Y-m-d H:i:s');
			$Map->alter($catapultMap['id'], ['attack_time'=>"'".$attackTime."'"]);
			
			dbCommit($db);
			
			//长连接通知
			foreach(['attack', 'defend'] as $_t){
				$playerIds = [];
				$serverId = CrossPlayer::parseGuildId($guilds[$_t])['server_id'];
				if($serverId){
					$members = $Player->getByGuildId($guilds[$_t]);
					foreach($members as $_d){
						if(!$_d['status']) continue;
						$playerIds[] = $_d['player_id'];
					}
					crossSocketSend($serverId, ['Type'=>'cross', 'Data'=>['playerId'=>$playerIds, 'type'=>'catapultAttack', 'fromNick'=>$player['nick'], 'toNick'=>$crossPlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $crossPlayer['wall_durability']-$reduceDurability), 'from_x'=>$catapultMap['x'], 'from_y'=>$catapultMap['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']]]);
				}
			}
			*/
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		if(@$battleId)
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $battleId, $x, $y);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	public function catapultAttack($player, $guildId, $battleId, $x, $y, $crossBattle, $counterAttack=false){
		$playerId = $player['player_id'];
		$CrossPlayerGeneral = new CrossPlayerGeneral;
		$CrossPlayerGeneral->battleId = $battleId;
		
		//查找我占领的投石车
		$condition = ['player_id='.$playerId.' and type='.CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and end_time="0000-00-00 00:00:00" and status=1'];
		$ppq = CrossPlayerProjectQueue::findFirst($condition);
		if(!$ppq){
			throw new Exception(10624);//尚未占领投石车
		}
		//反戈一击:驻守投石车时，若自己的城池遭到攻击，投石车会立即额外反击一次，造成|<#0,255,0#>%{num}|%的投石伤害。
		if($counterAttack){
			$rate = $CrossPlayerGeneral->getSkillsByArmies([$ppq->army_id], [10102])[10102][0];
			if(!$rate){
				return false;
			}
		}else{
			$rate = 1;
		}
		
		$Map = new CrossMap;
		$catapultMap = $Map->getByXy($battleId, $ppq->to_x, $ppq->to_y);
		if(!$catapultMap || $catapultMap['map_element_origin_id'] != 305)
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		
		//检查冷却时间
		if(!$counterAttack){
			//$atkcdTime = (new WarfareServiceConfig)->dicGetOne('wf_catapult_atkcolddown');
			if(time() < strtotime($catapultMap['attack_time']) + $catapultMap['attack_cd']){
				throw new Exception(10625);//投石车正在冷却中
			}
		}
		
		//查找目标
		$map = $Map->getByXy($battleId, $x, $y);
		if(!$map || $map['map_element_origin_id'] != 15)
			throw new Exception(10626);//目标未找到
		
		//判断目标是否为敌方城堡
		if($map['guild_id'] == $guildId){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//判断区域是否可见
		$CrossBattle = new CrossBattle;
		$guilds = $CrossBattle->getADGuildId($crossBattle);
		if(!$counterAttack){
			$areas = $this->getViewArea($guildId, $guilds, $crossBattle);
			if(!in_array($map['area'], $areas)){
				throw new Exception(10627);//敌方城堡不在视野范围内
			}
		}
		
		//计算投石车和目标的距离
		if(!$counterAttack){
			$distance = sqrt(pow($ppq->to_x - $x, 2) + pow($ppq->to_y - $y, 2));
			if($distance > $this->catapultDistance){
				throw new Exception(10629);//敌方城堡不在投石车攻击范围内
			}
		}
		
		//计算投石车攻击力
		$formula = (new WarfareServiceConfig)->getValueByKey('wf_catapult_atkpower');
		$power = (new QueueCross)->getArmyPower($battleId, $playerId, $ppq->army_id);
		eval('$reduceDurability = '.$formula.';');
		if(!$reduceDurability){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		//攻击加成
		$buff = 0;
		$addBuff = 0;
		
		//君临天下：若该武将的统御高于所有敌军武将，则所有本方器械的伤害增加%
		$CrossGuild = new CrossGuild;
		$CrossGuild->battleId = $battleId;
		$buff += $CrossGuild->getByPlayerId($playerId)['buff_buildattack'];
		
		//投石精通：驻守时增加投石车攻击伤害%
		$buff += $CrossPlayerGeneral->getSkillsByArmies([$ppq->army_id], [18])[18][0];
		
		//床弩大师:每次攻击后，床弩的攻击力增加
		$addBuff += $CrossPlayerGeneral->getSkillsByArmies([$ppq->army_id], [19])[19][0] * $catapultMap['attack_times'];
		
		$reduceDurability *= 1+$buff;
		$reduceDurability += $addBuff;
		//反击百分比
		$reduceDurability = max(1, floor($reduceDurability*$rate));
		
		//玩家扣血
		$Player = new CrossPlayer;
		$Player->battleId = $battleId;
		$crossPlayer = $Player->getByPlayerId($map['player_id']);
		if(!$crossPlayer || !$crossPlayer['is_in_map'] || !$crossPlayer['status']){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		if(!$Player->alter($map['player_id'], ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-'.$reduceDurability.')'])){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		$crossPlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
		
		//日志
		(new CrossCommonLog)->add($battleId, $playerId, $guildId, '投石车'.($counterAttack ? '反':'攻').'击玩家[defend='.$crossPlayer['player_id'].'('.$crossPlayer['guild_id'].')]|扣血-'.$reduceDurability.',剩余'.max(0, $crossPlayer['wall_durability']-$reduceDurability).'|byPlayerId='.$playerId.'('.$guildId.')');
		
		//如果玩家血0，删除城堡
		if($crossPlayer['wall_durability'] <= $reduceDurability){
			
			//不屈之力：城防首次被击破时可恢复|<#72,255,98#>%{num}||<#3,169,235#>%{numnext}|城防值
			if(!$crossPlayer['skill_first_recover']){
				$recoverhp = $CrossPlayerGeneral->getSkillsByPlayer($crossPlayer['player_id'], null, [10089])[10089][0];
				if($recoverhp){
					$Player->alter($crossPlayer['player_id'], ['wall_durability'=>'wall_durability+'.$recoverhp, 'skill_first_recover'=>1]);
					(new CrossCommonLog)->add($battleId, $crossPlayer['player_id'], $crossPlayer['guild_id'], '玩家发动不屈之力|加血+'.$recoverhp);
					(new QueueCross)->crossNotice($battleId, 'skill_10089', ['nick'=>$crossPlayer['nick']]);
					goto a;
				}
			}
						
			$Map->delPlayerCastle($battleId, $crossPlayer['player_id']);
			
			//一血通知
			$CrossBattle->updateFirstBlood($crossBattle, $player, $crossPlayer);
			
			//连杀
			$Player->addContinueKill($playerId, $player, $crossBattle);
			
			//日志
			(new CrossCommonLog)->add($battleId, $crossPlayer['player_id'], $crossPlayer['guild_id'], '玩家扑街|byPlayerId='.$playerId.'('.$guildId.')');
			
			(new QueueCross)->crossNotice($battleId, 'playerDead', ['from_nick'=>$player['nick'], 'to_nick'=>$crossPlayer['nick']]);
		}
		a:
		
		//更新投石车攻击时间
		if(!$counterAttack){
			$attackTime = date('Y-m-d H:i:s');
			$Map->alter($catapultMap['id'], ['attack_time'=>"'".$attackTime."'", 'attack_times'=>'attack_times+1']);
		}
		
		//长连接通知
		if(!$counterAttack){
			$msgType = 'catapultAttack';
		}else{
			$msgType = 'catapultCounterAttack';
		}
		foreach(['attack', 'defend'] as $_t){
			$playerIds = [];
			$serverId = CrossPlayer::parseGuildId($guilds[$_t])['server_id'];
			if($serverId){
				$members = $Player->getByGuildId($guilds[$_t]);
				foreach($members as $_d){
					if(!$_d['status']) continue;
					$playerIds[] = $_d['player_id'];
				}
				crossSocketSend($serverId, ['Type'=>'cross', 'Data'=>['playerId'=>$playerIds, 'type'=>$msgType, 'fromNick'=>$player['nick'], 'toNick'=>$crossPlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $crossPlayer['wall_durability']-$reduceDurability), 'from_x'=>$catapultMap['x'], 'from_y'=>$catapultMap['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']]]);
			}
		}
		return true;
	}
	
	function GetPointDistance($p1, $p2){
		return sqrt(($p1['x']-$p2['x'])*($p1['x']-$p2['x'])+($p1['y']-$p2['y'])*($p1['y']-$p2['y']));
	}
	function GetNearestDistance($PA, $PB, $P3){

		$a=$this->GetPointDistance($PB,$P3);
		if($a<=0)
			return 0;
		$b=$this->GetPointDistance($PA,$P3);
		if($b<=0)
			return 0;
		$c=$this->GetPointDistance($PA,$PB);
		if($c<=0)
			return $a;//如果PA和PB坐标相同，则退出函数，并返回距离

		if($a*$a>=$b*$b+$c*$c)
			return $b;      //如果是钝角返回b
		if($b*$b>=$a*$a+$c*$c)
			return $a;      //如果是钝角返回a

		$l=($a+$b+$c)/2;
		$s=sqrt($l*($l-$a)*($l-$b)*($l-$c));
		return 2*$s/$c;
	}

    /**
     * 取块数据
     *
     * ```php
     * /Cross/showArea/
     * postData: json={"AreaList":[]}
     * return: json{"Map":"", "Player":"", "Guild":""}
     * ```
     *
     */
    public function showAreaAction(){
		global $config;
        // debug('------------------B');
        //debug("ST-".time());

		$player = $this->getCurrentPlayer();
        $playerId = $this->getCurrentPlayerId();
        // debug('------player_id='.$playerId);
        //$post = getPost();
        //$areaList = $post['areaList'];
		
		$CrossBattle = new CrossBattle;
		$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		$battleId = $CrossBattle->getBattleIdByGuildId($guildId, $crossBattle);
		if(!in_array($crossBattle['status'], [CrossBattle::STATUS_ATTACK_READY, CrossBattle::STATUS_ATTACK, CrossBattle::STATUS_DEFEND_READY, CrossBattle::STATUS_DEFEND])){
			exit;
		}
		
		$ad = (new CrossBattle)->getADGuildId($crossBattle);
		$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		$areas = $this->getViewArea($guildId, $ad, $crossBattle);
        /*$CrossPlayer = new CrossPlayer;
        $player = $CrossPlayer->getByPlayerId($playerId);
        $guildId = $player['guild_id'];
        $CrossBattle = new CrossBattle;
        $battleId = $CrossBattle->getBattleIdByGuildId($guildId);*/
        $result = $this->_showArea($battleId, $areas, $guildId, $err);

        if(!$err){
            echo $this->data->send($result);
            // debug('------------------E');
        }else{
            echo $this->data->sendErr($err);
        }
    }

    public function _showArea($battleId, $areaList, $guildId, &$err=0){
        $Map = new CrossMap;
        $Player = new CrossPlayer;
		$Player->battleId = $battleId;
        $Guild = new CrossGuild;
		$Guild->battleId = $battleId;

        $result = ['Map'=>[], 'Player'=>[], 'Guild'=>[]];
		if(!$areaList)
			return $result;
        $err = 0;
        //foreach ($areaList as $area) {
            //$tmpList = $Map->getAllByArea($battleId, $area);
			$tmpList = $Map->find("battle_id={$battleId}")->toArray();
			$tmpList = $Map->adapter($tmpList);
            foreach ($tmpList as $key => $value) {
				//过滤非视野内的敌方玩家
				if($value['map_element_origin_id'] == 15 && $value['guild_id'] != $guildId && !in_array($value['area'], $areaList)) continue;
                $result['Map'][$value['id']] = $value;
                if(!empty($value['player_id']) && empty($result['Player'][$value['player_id']])){
                    $whiteList = ["id", "user_code","server_id","nick","avatar_id","level","wall_durability","wall_durability_max","prev_x","prev_y","map_id","x","y","is_in_map","is_in_map","rowversion","rank_title"];
                    //$tmpPlayerInfo = $Player->getByPlayerId($value['player_id']);
					$tmpPlayerInfo = $this->getBnqCache('player', $battleId, $value['player_id']);
                    $result['Player'][$value['player_id']] = keepFields($tmpPlayerInfo, $whiteList, true);
                }
                if(!empty($value['guild_id']) && empty($result['Guild'][$value['guild_id']])){
                    $result['Guild'][$value['guild_id']] = $this->getBnqCache('guild', $battleId, $value['guild_id']);//$Guild->getGuildInfo($value['guild_id']);
                }
            }
        //}
        //debug("ET-".time());
        return $result;
    }

	public function _showCatapultTarget($player, $crossBattle){
		global $config;
		$playerId = $player['id'];
		$battleId = $crossBattle['id'];
		$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		
		//查找我占领的投石车
		$condition = ['player_id='.$playerId.' and type='.CrossPlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and end_time="0000-00-00 00:00:00" and status=1'];
		$ppq = CrossPlayerProjectQueue::findFirst($condition);
		if(!$ppq){
			return false;
		}
		
		$Map = new CrossMap;
		//$catapultMap = $Map->getByXy($battleId, $ppq->to_x, $ppq->to_y);
		$catapultMap = $this->getBnqCache('map', $battleId, ['x'=>$ppq->to_x, 'y'=>$ppq->to_y]);
		if(!$catapultMap || $catapultMap['map_element_origin_id'] != 305)
			return false;
		
		if($crossBattle['guild_1_id'] == $guildId){
			$guildId2 = $crossBattle['guild_2_id'];
		}else{
			$guildId2 = $crossBattle['guild_1_id'];
		}
		//查找所有可见区域内的敌方城堡
		//判断区域是否可见
		$guilds = (new CrossBattle)->getADGuildId($crossBattle);
		$areas = $this->getViewArea($guildId, $guilds, $crossBattle);
		/*$crossBattle['attack_area'] = parseArray($crossBattle['attack_area']);
		if($guilds['attack'] == $guildId){//攻击方，检查区域是否开启
			$areas = $crossBattle['attack_area'];
		}else{//防守方，检查是否有工会成员在此区域
			$playerMap = $Map->find("battle_id={$battleId} and guild_id={$guildId} and map_element_origin_id=15")->toArray();
			$areas = Set::extract("/area", $playerMap);
			$areas = array_unique(array_merge([3, 4, 5], $areas));
		}*/
		if($areas){
			$maps = $Map->find(['battle_id='.$battleId.' and area in ('.join(',', $areas).') and map_element_origin_id=15 and guild_id='.$guildId2])->toArray();
		}else{
			$maps = $Map->find(['battle_id='.$battleId.' and map_element_origin_id=15 and guild_id='.$guildId2])->toArray();
		}
		
		//遍历是否在半径内
		$target = [];
		$CrossPlayer = new CrossPlayer;
		$CrossPlayer->battleId = $battleId;
		foreach($maps as $_m){
			$distance = sqrt(pow($_m['x'] - $catapultMap['x'], 2) + pow($_m['y'] - $catapultMap['y'], 2));
			if($distance <= $this->catapultDistance){
				//$_player = $CrossPlayer->getByPlayerId($_m['player_id']);
				$_player = $this->getBnqCache('player', $battleId, $_m['player_id']);
				$target[] = [
					'player_id' => $_m['player_id'],
					'nick' => $_player['nick'],
					'x'=> $_m['x'],
					'y'=> $_m['y'],
					'wall_durability' => $_player['wall_durability'],
					'wall_durability_max' => $_player['wall_durability_max'],
				];
			}
		}
		
		return ['attack_time'=>strtotime($catapultMap['attack_time']), 'attack_cd'=>$catapultMap['attack_cd'], 'target'=>$target];
	}
	
    /**
     * 获取视野区域
     * 
     * 
     * @return <type>
     */
	public function getViewArea($guildId, $ad, $crossBattle){
		if(isset($this->viewareacache[$guildId])){
			return $this->viewareacache[$guildId];
		}
		$crossBattle['attack_area'] = parseArray($crossBattle['attack_area']);
		if($ad['attack'] == $guildId){//攻击方，检查区域是否开启
			$areas = $crossBattle['attack_area'];
		}else{//防守方，检查是否有工会成员在此区域
			$playerMap = (new CrossMap)->find("battle_id=".$crossBattle['id']." and guild_id={$guildId} and map_element_origin_id=15")->toArray();
			$areas = Set::extract("/area", $playerMap);
			$areas = array_merge([3, 4, 5], $areas);
		}
		$this->viewareacache[$guildId] = $areas;
		return $areas;
	}
	
    /**
     * 获取参赛相关信息
     *
     * url: cross/basicInfo
     * postData: {}
     * return:{...}
     */
    public function basicInfoAction(){
        $player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $PlayerGuild          = new PlayerGuild;
        $CrossGuildInfo       = new CrossGuildInfo;
        $CrossRound           = new CrossRound;
        $Guild                = new Guild;
        $WarfareServiceConfig = new WarfareServiceConfig;
        $ModelBase            = new ModelBase;

        //判断first king battle
        $firstKingFlag             = (new King)->hasFirstKing();

        $data                      = [];//integrate the infos
        $data['first_king_status'] = $firstKingFlag ? 1 : 0;
        //judge guild is or not
        $guildId                   = $player['guild_id'];
        if($guildId==0) {
            $No_Guild_Flag = true;
            goto TopInfo;
        }
        $guild                 = $Guild->getGuildInfo($guildId);
        $currentRoundId        = $CrossRound->getCurrentRoundId();
        $joinGuildId         = CrossPlayer::joinGuildId($serverId, $guildId);
        $currentCrossGuildInfo = $CrossGuildInfo->getCrossGuildBasicInfo($joinGuildId);

        $openTime = $wfEnrollStart = $wfMatchStart = $wfAwardStart = $nextRoundCreateTimeDate = $wfCloseTime = 0;
        if($firstKingFlag) {
            $lastRound = CrossRound::findFirst(['order'=>'id desc', 'limit'=>1]);
            if($lastRound) {
                $lastRoundCreateTime = substr($lastRound->create_time, 0, 10) . '00:00:00';
                if($lastRound->status==CrossRound::Status_battle_end) {
                    $nextRoundCreateTime = strtotime($lastRoundCreateTime) + 7 * 24 * 60 * 60;
                } else {
                    $nextRoundCreateTime = strtotime($lastRoundCreateTime);
                }
                $nextRoundCreateTimeDate = date('Y-m-d', $nextRoundCreateTime);
            } else {
                $currentWeekDay = date('w');//今天星期几
                $nextRoundCreateTimeDate = date('Y-m-d', time() + (6-$currentWeekDay) * 24 * 60 * 60);

            }
            $openTime      = $nextRoundCreateTimeDate . ' ' . $WarfareServiceConfig->getValueByKey('open_time');
            $wfEnrollStart = $nextRoundCreateTimeDate . ' ' . $WarfareServiceConfig->getValueByKey('wf_enroll_start');
            $wfMatchStart  = $nextRoundCreateTimeDate . ' ' . $WarfareServiceConfig->getValueByKey('wf_match_start');
            $wfAwardStart  = $nextRoundCreateTimeDate . ' ' . $WarfareServiceConfig->getValueByKey('wf_award_start');
            $wfCloseTime   = $nextRoundCreateTimeDate . ' ' . $WarfareServiceConfig->getValueByKey('wf_close_time');
        }
        //case current guild info inlucde round_status
        $currentGuildInfo = [
            'current_round_id'            => 0,//当前轮次
            'round_status'                => -1,//当前轮状态
            'guild_status'                => $currentCrossGuildInfo['status'],
            'battle_status'               => 0, //战斗状态，是否可以领奖等
            'joined_round'                => (int)$currentCrossGuildInfo['joined_times'],//参加轮次
            'guild_name'                  => $guild['name'],//联盟名字
            'win_times'                   => (int)$currentCrossGuildInfo['win_times'],//获胜次数
            'wf_close_time'               => $wfCloseTime ? strtotime($wfCloseTime):$wfCloseTime,
            'luck_round'                  => 0,//是否轮空
            'open_time'                   => $openTime ? strtotime($openTime):$openTime,
            'wf_enroll_start_finish_time' => $wfEnrollStart ? strtotime($wfEnrollStart):$wfEnrollStart,
            'wf_match_start_finish_time'  => $wfMatchStart ? strtotime($wfMatchStart):$wfMatchStart,
            'wf_award_start_finish_time'  => $wfAwardStart ? strtotime($wfAwardStart):$wfAwardStart,

        ];

        $currentGuildInfo['current_round_id'] = CrossRound::count();//当前轮次
        if($currentRoundId>0) {
            $roundInfo                            = $CrossRound->current;
            $currentGuildInfo['round_status']     = (int)$roundInfo['status'];//当前轮状态
            $battle                               = CrossBattle::findFirst("round_id=".$roundInfo['id']." and (guild_1_id={$joinGuildId} or guild_2_id={$joinGuildId})");
            if($battle) {
                $battle = $battle->toArray();
            }
            if(!empty($battle)) {
                $currentGuildInfo['battle_status'] = (int)$battle['status'];//战斗状态，是否可以领奖等
                if($battle['guild_2_id']==0) {
                    $currentGuildInfo['luck_round'] = 1;
                }
            }
        }

        //case top info 随机三个
        // 第一轮 则选择报名的三个
        TopInfo: {
            $cacheKey = __METHOD__ . "_topInfo";
            $topInfo  = Cache::db('cache', 'Cross')->get($cacheKey);
            $TOP_NUM  = 3;
            if (empty($topInfo) && count($topInfo) < $TOP_NUM) {
                $tops1 = CrossGuildInfo::find(["latest_battle_is_win=1", "columns" => ['guild_id'], "order" => "rand()", "limit" => $TOP_NUM])->toArray();//上轮获胜者
                if (!empty($tops1)) {
                    $tops1 = CrossGuildInfo::find(["status=1", "columns" => ['guild_id'], "order" => "rand()", "limit" => $TOP_NUM])->toArray();//本轮报名的
                }
                foreach ($tops1 as $v) {
                    $topGuildId         = $v['guild_id'];
                    $parsedTopGuildInfo = CrossPlayer::parseGuildId($topGuildId);
                    $topGuildInfo       = $ModelBase->getByServer($parsedTopGuildInfo['server_id'], 'Guild', 'getGuildInfo', [$parsedTopGuildInfo['guild_id'], false, false]);
                    $topInfo[]          = [
                        'guild_name' => $topGuildInfo['name'],
                        'icon_id'    => $topGuildInfo['icon_id']
                    ];
                }
                Cache::db('cache', 'Cross')->setex($cacheKey, 3600, $topInfo);
            }
            if(empty($topInfo)) $topInfo = [];
            if(isset($No_Guild_Flag) && $No_Guild_Flag) {
                $data['top_info'] = $topInfo;
                goto sendingData;
            }
        }
        //case member list
        $members                               = [];
        $joinedNumber                          = 0;
        $playerGuild                           = $PlayerGuild->getByPlayerId($playerId);
        $currentGuildInfo['cross_joined_flag'] = $playerGuild['cross_joined_flag'];
        if($currentCrossGuildInfo['status']==CrossGuildInfo::Status_joined) {
            $crossMember  = $PlayerGuild->getCrossMembers($guildId);
            $members      = $crossMember['members'];
            $joinedNumber = $crossMember['joined_number'];
        }
        //case  history data
        $history = [];
        $historyList = CrossBattle::find(["(guild_1_id={$joinGuildId} or guild_2_id={$joinGuildId}) and status=7", 'columns'=>['round_id', 'guild_1_id', 'guild_2_id', 'win'], 'order'=>'id desc'])->toArray();
        if($historyList) {
            foreach($historyList as $v) {
                $_tmp['joined_round_id'] = (int)$v['round_id'];
                if($v['guild_1_id']==$joinGuildId) {
                    $_tmp['target_guild_id'] = (int)$v['guild_2_id'];
                } elseif($v['guild_2_id']==$joinGuildId) {
                    $_tmp['target_guild_id'] = (int)$v['guild_1_id'];
                }
                if(($v['win']==1 && $v['guild_1_id']==$joinGuildId) || ($v['win']==2 && $v['guild_2_id']==$joinGuildId)) {
                    $_tmp['is_win'] = 1;
                } else {
                    $_tmp['is_win'] = 0;
                }
                $parsedHistoryGuildInfo = CrossPlayer::parseGuildId($_tmp['target_guild_id']);
                $historyGuildInfo = $ModelBase->getByServer($parsedHistoryGuildInfo['server_id'], 'Guild', 'getGuildInfo', [$parsedHistoryGuildInfo['guild_id'], false, false]);
                if($historyGuildInfo) {
                    $_tmp['target_guild_name'] = $historyGuildInfo['name'];
                    $_tmp['target_guild_icon_id'] = (int)$historyGuildInfo['icon_id'];
                }
                $history[] = $_tmp;
            }
        }
        $data['current_guild_info'] = $currentGuildInfo;
        $data['top_info']           = $topInfo;
        $data['history']            = $history;
        $data['members']            = $members;
        $data['joined_number']      = $joinedNumber;
        $data['total_number']       = (int)$WarfareServiceConfig->getValueByKey('wf_guild_num');
        sendingData:
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     * 报名参赛
     *
     * url: cross/joinBattle
     * postData: {}
     * return: {}
     */
    public function joinBattleAction(){
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $guildId = $player['guild_id'];

        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':guildId=' .$guildId;
        Cache::lock($lockKey);

        if($guildId==0) {
            $errCode = 10567;//玩家未入盟
            goto sendErr;
        }
        $PlayerGuild = new PlayerGuild;
        $CrossGuildInfo = new CrossGuildInfo;
        $CrossRound = new CrossRound;
        $WarfareServiceConfig = new WarfareServiceConfig;
        //参赛前提 judgment
        //国王战状态
        $hasFirstKingFlag = (new King)->hasFirstKing();
        if(!$hasFirstKingFlag){
            $errCode = 10568;//[报名参赛]当前服没有参加过皇位战，不能进行跨服战比赛
            goto sendErr;
        }
        //case 比赛状态
        $currentRoundId = $CrossRound->getCurrentRoundId();
        if($currentRoundId) {
            $currentRound = $CrossRound->current;
            if($currentRound['status']!=CrossRound::Status_sign) {
                $errCode = 10569;//[报名参赛]当前比赛不在报名阶段
                goto sendErr;
            }
        } else {
            $errCode = 10570;//[报名参赛]当前比赛尚未开启
            goto sendErr;
        }
        //case 参赛联盟状态
        $joinGuildId = CrossPlayer::joinGuildId($serverId, $guildId);
        $crossGuildInfo = $CrossGuildInfo->getCrossGuildBasicInfo($joinGuildId);
        if($crossGuildInfo['status']==CrossGuildInfo::Status_joined) {
            $errCode = 10571;//[报名参赛]已报名参赛，重开页面可查看最新状态
            goto sendErr;
        }

        $playerGuild = $PlayerGuild->getByPlayerId($playerId);
        if($playerGuild['rank'] < PlayerGuild::RANK_R4){
            $errCode = 10572;//[报名参赛]请通知联盟管理层进行报名操作
            goto sendErr;
        }

        $allGuildMember    = $PlayerGuild->getAllGuildMember($guildId);
        $wfFuyaBuildLevel  = $WarfareServiceConfig->getValueByKey('wf_guild_city_level');
        $allHighFuyaMember = array_filter($allGuildMember, function($v) use ($wfFuyaBuildLevel){
            return $v['Player']['fuya_build_level']>=$wfFuyaBuildLevel;
        });
        $wfGuildNum = $WarfareServiceConfig->getValueByKey('wf_guild_num');
        if(count($allHighFuyaMember)<$wfGuildNum) {
            $errCode = 10573;//[报名参赛]府衙等级达标人数不足十人，无法参加比赛
            goto sendErr;
        }
        //提交逻辑
        //更改 cross_guild_info状态和参赛次数
        $CrossGuildInfo->alter($guildId, ['status'=>CrossGuildInfo::Status_joined, 'joined_times'=>'joined_times+1']);
        //更改 player_guild中申请状态和参战状态
        $PlayerGuild->sqlExec("update player_guild set cross_application_flag=0 where guild_id={$guildId} and cross_application_flag=1");
        $PlayerGuild->sqlExec("update player_guild set cross_joined_flag=0 where guild_id={$guildId} and cross_joined_flag=1");
        $PlayerGuild->clearGuildCache($guildId);
        //发送邮件
        $data['round'] = CrossRound::count();//当前轮次
        (new PlayerMail)->sendSystem(Set::extract('/player_id', $allGuildMember), PlayerMail::TYPE_CROSS_SIGN, '', '', 0, $data, [], ['exec_flag'=>0]);
        (new PlayerCommonLog)->add($playerId, ['type'=>'跨服战申请参战', 'from'=>['player_id'=>$playerId, 'guild_id'=>$guildId], 'current_round_id'=>@$currentRoundId]);//日志记录
        $data = [];
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     * 提交参赛成员列表
     *
     * url: cross/commitBattleMemberList
     * postData: {List:...}
     * return: {}
     */
    public function commitBattleMemberListAction(){
        $player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $guildId  = $player['guild_id'];
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':guildId=' .$guildId;
        Cache::lock($lockKey);
        if($guildId==0) {
            $errCode = 10574;//玩家未入盟
            goto sendErr;
        }
        //case 比赛状态
        $CrossRound = new CrossRound;
        $currentRoundId = $CrossRound->getCurrentRoundId();
        if($currentRoundId) {
            $currentRound = $CrossRound->current;
            if($currentRound['status']!=CrossRound::Status_sign) {
                $errCode = 10575;//[提交参赛成员列表]当前比赛不在报名阶段
                goto sendErr;
            }
        } else {
            $errCode = 10576;//[提交参赛成员列表]当前比赛尚未开启
            goto sendErr;
        }
        //case 参赛联盟状态
        $joinGuildId  = CrossPlayer::joinGuildId($serverId, $guildId);
        $crossGuildInfo = (new CrossGuildInfo)->getCrossGuildBasicInfo($joinGuildId);
        if($crossGuildInfo['status']==CrossGuildInfo::Status_not_joined) {
            $errCode = 10577;//[提交参赛成员列表]已报名参赛，重开页面可查看最新状态
            goto sendErr;
        }

        $postData        = getPost();
        $targetPlayerIds = $postData['List'];
        $PlayerGuild     = new PlayerGuild;

        $playerGuild = $PlayerGuild->getByPlayerId($playerId);
        if($playerGuild['rank'] < PlayerGuild::RANK_R4){
            $errCode = 10578;//[提交参赛成员列表]当前等级不足R4
            goto sendErr;
        }
        //提交逻辑
        if(!is_array($targetPlayerIds)) {//非数组
            $errCode = 10061;
            goto sendErr;
        }
        $allGuildMember    = $PlayerGuild->getAllGuildMember($guildId);
        $allGuildMemberPid = Set::extract("/player_id", $allGuildMember);
        foreach($targetPlayerIds as $pid) {
            if(!in_array($pid, $allGuildMemberPid)) {//非当前盟成员
                $errCode = 10061;
                goto sendErr;
            }
        }
        $PlayerGuild->sqlExec("update player_guild set cross_joined_flag=0 where guild_id={$guildId}");
        if(!empty($targetPlayerIds)) {
            $playerIdSql = implode(",", $targetPlayerIds);
            $PlayerGuild->sqlExec("update player_guild set cross_joined_flag=1 where guild_id={$guildId} and player_id in ({$playerIdSql})");
        }
        $PlayerGuild->clearGuildCache($guildId);

        $crossMember  = $PlayerGuild->getCrossMembers($guildId);
        $members      = $crossMember['members'];
        $joinedNumber = $crossMember['joined_number'];

        $data                 = [];
        $data['members']      = $members;
        $data['joinedNumber'] = $joinedNumber;
        (new PlayerCommonLog)->add($playerId, ['type'=>'跨服战提交参赛名单,guild_id='.$guildId, 'current_round_id'=>$currentRoundId, 'guild_id'=>$guildId, 'data'=>$data]);//日志记录
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     * 申请加入跨服战
     *
     * url: cross/applyToJoinBattle
     * postData: {}
     * return: {}
     */
    public function applyToJoinBattleAction(){
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $guildId = $player['guild_id'];
        if($guildId==0) {
            $errCode = 10579;//[跨服战联盟成员出战申请]你当前没有加入联盟，不能提交出战申请
            goto sendErr;
        }

        $postData = getPost();
        if (isset($postData['mail_id'])) {
            $mailId = $postData['mail_id'];

            $PlayerMail = new PlayerMail;
            $mailInfo   = $PlayerMail->getMailInfo($mailId);
            $execFlag   = json_decode($mailInfo['memo'], true)['exec_flag'];

            if ($execFlag == 1) {//已经操作过该邮件
                $errCode = 10084;
                goto sendErr;
            }
        }

        //case 比赛状态
        $CrossRound = new CrossRound;
        $currentRoundId = $CrossRound->getCurrentRoundId();
        if($currentRoundId) {
            $currentRound = $CrossRound->current;
            if($currentRound['status']!=CrossRound::Status_sign) {
                $errCode = 10634;//[跨服战联盟成员出战申请]不在申请时间内
                goto sendErr;
            }
        } else {
            $errCode = 10634;//[跨服战联盟成员出战申请]不在申请时间内
            goto sendErr;
        }



        $CrossGuildInfo = new CrossGuildInfo;
        $PlayerGuild    = new PlayerGuild;

        $joinGuildId    = CrossPlayer::joinGuildId($serverId, $guildId);
        $crossGuildInfo = $CrossGuildInfo->getCrossGuildBasicInfo($joinGuildId);
        if($crossGuildInfo['status']==CrossGuildInfo::Status_not_joined) {
            $errCode = 10580;//[跨服战联盟成员出战申请]当前不是比赛报名阶段，不能提交出战申请
            goto sendErr;
        }
        $playerGuild     = $PlayerGuild->getByPlayerId($playerId);
        $applicationFlag = $playerGuild['cross_application_flag'];
        if($applicationFlag==1) {
            $errCode = 10581;//[跨服战联盟成员出战申请]已经提交过申请，不能重复提交出战申请
            goto sendErr;
        }

        $PlayerGuild->sqlExec("update player_guild set cross_application_flag=1 where guild_id={$guildId} and player_id={$playerId};");
        if(isset($mailId)) {
            $PlayerMail->updateMemosByMailId($playerId, $mailId, ['exec_flag' => 1]);
        }
        $PlayerGuild->clearGuildCache($guildId);

        (new PlayerCommonLog)->add($playerId, ['type'=>'[跨服战申请]', 'current_round_id'=>$currentRoundId, 'guild_id'=>$guildId]);//日志记录

        $data = [];
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
 *  迁城
 *
 * url: cross/changeLocation
 * postData: {areaId:}
 * return: {}
 */
    public function changeLocationAction(){
        $post = getPost();
        $areaId = floor(@$post['areaId']);
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];

        global $config;
        $serverId = $config->server_id;
        $guildId = CrossPlayer::joinGuildId($serverId, $player['guild_id']);

        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $CrossBattle = new CrossBattle;
        $battleId = $CrossBattle->getBattleIdByGuildId($guildId);
        $battle = $CrossBattle->getBattle($battleId);
        $mapType = $battle['map_type'];

        $CrossPlayer = new CrossPlayer;
        $CrossPlayer->battleId = $battleId;
        $cpInfo = $CrossPlayer->getByPlayerId($playerId);
        $CrossMapConfig = new CrossMapConfig();
        $currentAreaId = $CrossMapConfig->getAreaByXy($mapType, $cpInfo['x'], $cpInfo['y']);

        //确定不再同一区域内
        if($currentAreaId==$areaId){
            $errCode = 10582;//不能飞往当前区域
            goto sendErr;
        }

        //确定是否控制该区
        $cArea = $CrossBattle->getPlayerControlArea($guildId, $battleId);
        if(!in_array($areaId, $cArea)){
            $errCode = 10583;//不能飞到该位置
            goto sendErr;
        }

        //确定是否有部队出征
        $CrossPlayerProjectQueue = new CrossPlayerProjectQueue;
        $qNum = $CrossPlayerProjectQueue->getPlayerUseQueueNum($battleId, $playerId);
        if($qNum>0){
            $errCode = 10584;//有部队出征中
            goto sendErr;
        }

        $CrossMap = new CrossMap;
        $p = $CrossMap->getNewCastlePosition($playerId, $guildId, $battleId, $mapType, $areaId);
        $changeLocationCd = (new WarfareServiceConfig)->dicGetOne('wf_castle_teleport_colddown');
        //权术大师：若该武将的政治高于所有敌军武将，本方迁城cd时间降低
        $CrossGuild = new CrossGuild;
        $CrossGuild->battleId = $battleId;
        $changeLocationCd -= $CrossGuild->getByPlayerId($playerId)['buff_relocation'];
        if($changeLocationCd<1){
            $changeLocationCd = 1;
        }
        if($p){
            if($cpInfo['is_dead']==1){
                $errCode = 10585;//玩家已死亡
            }elseif(empty($cpInfo['change_location_time'])){
                $CrossMap->changeCastleLocation($battleId, $playerId, $p[0], $p[1], $areaId);
                $CrossPlayer->updateAll(['change_location_time'=>qd(time()-$changeLocationCd)], ['id'=>$cpInfo['id']]);
                $errCode = 0;
            }elseif($cpInfo['change_location_time']<=time()-$changeLocationCd){//可以迁城
                $CrossMap->changeCastleLocation($battleId, $playerId, $p[0], $p[1], $areaId);
                $CrossPlayer->updateAll(['change_location_time'=>qd()], ['id'=>$cpInfo['id']]);
                $errCode = 0;
            }else{
                $errCode = 10586;//时间未到
            }
        }else{
            $errCode = 10587;//没有可用位置
        }

        sendErr:
        Cache::unlock($lockKey);
        if(!empty($errCode)){
            echo $this->data->sendErr($errCode);
        }else{
            echo $this->data->send();
        }
        exit;
    }

    /**
     *  复活
     *
     * url: cross/revive
     * postData: {}
     * return: {}
     */
    public function reviveAction(){
        $post = getPost();
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];
        global $config;
        $serverId = $config->server_id;
        $guildId = CrossPlayer::joinGuildId($serverId, $player['guild_id']);

        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $CrossBattle = new CrossBattle;
        $battleId = $CrossBattle->getBattleIdByGuildId($guildId);
        $battle = $CrossBattle->getBattle($battleId);
        $mapType = $battle['map_type'];

        $CrossPlayer = new CrossPlayer;
        $CrossPlayer->battleId = $battleId;
        $cpInfo = $CrossPlayer->getByPlayerId($playerId);

        $CrossMapConfig = new CrossMapConfig;
        $areaId = $CrossMapConfig->getAreaByXy($mapType, $cpInfo['prev_x'], $cpInfo['prev_y']);


        //确定是否控制该区
        $cArea = $CrossBattle->getPlayerControlArea($guildId, $battleId);
        if(!in_array($areaId, $cArea)){
            $errCode = 10588;//不能飞到该位置
            goto sendErr;
        }

        $isAttack = $CrossBattle->isAttack($guildId, $battleId);
        $WarfareServiceConfig = new WarfareServiceConfig;
        if($isAttack){
            $reviveCdBasic = $WarfareServiceConfig->dicGetOne('wf_attacker_respawn_time');
            $reviveCDAdd = $WarfareServiceConfig->dicGetOne('wf_attacker_respawn_add_time');
        }else{
            $reviveCdBasic = $WarfareServiceConfig->dicGetOne('wf_defender_respawn_time');
            $reviveCDAdd = $WarfareServiceConfig->dicGetOne('wf_defender_respawn_add_time');
        }
        $reviveCd = $reviveCdBasic + $reviveCDAdd*$cpInfo['dead_times'];

        $CrossMap = new CrossMap;
        $p = $CrossMap->getNewCastlePosition($playerId, $guildId, $battleId, $mapType, $areaId);


        if($p){
            if($cpInfo['is_dead']==1 && $cpInfo['dead_time']<=time()-$reviveCd){//可以复活
                $CrossMap->changeCastleLocation($battleId, $playerId, $p[0], $p[1], $areaId);
                $CrossPlayer->updateAll(['is_dead'=>0, 'dead_times'=>$cpInfo['dead_times']+1], ['id'=>$cpInfo['id']]);
                $errCode = 0;
            }else{
                $errCode = 10635;//'复活时间未到';
                /*$cost = $reviveCost*(time()-$cpInfo['dead_time'])/$reviveCd;
                $re = (new Player)->updateGem($playerId, (-1)*$cost, true, ['cost'=>10021, 'memo'=>'跨服战复活']);
                if($re){
                    $CrossMap->changeCastleLocation($battleId, $playerId, $p[0], $p[1], $areaId);
                    $CrossPlayer->updateAll(['is_dead'=>0], ['id'=>$cpInfo['id']]);
                    $errCode = 0;
                }else{
                    $errCode = 10589;//元宝不足
                }*/
            }
        }else{
            $errCode = 10590;//没有可用位置
        }

        sendErr:
        Cache::unlock($lockKey);
        if(!empty($errCode)){
            echo $this->data->sendErr($errCode);
        }else{
            echo $this->data->send();
        }
        exit;
    }

    /**
     * 查看特殊建筑情报
     * url: cross/getSpBuild
     * postData: {}
     * return: {}
     */
    public function getSpBuildAction(){
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];
        global $config;
        $serverId = $config->server_id;
        $guildId = CrossPlayer::joinGuildId($serverId, $player['guild_id']);
        $CrossBattle = new CrossBattle;
        $battleId = $CrossBattle->getBattleIdByGuildId($guildId);
		$result = $this->_getSpBuild($battleId);
        echo $this->data->send($result);
    }
	
	public function _getSpBuild($battleId){
        $CrossMap = new CrossMap;
        $re = $CrossMap->getSpBuild($battleId);
        $result = [];
        $result['Map'] = Set::combine($re, '{n}.id', '{n}');
        $Player = new CrossPlayer;
        $Player->battleId = $battleId;
        $Guild = new CrossGuild;
        $Guild->battleId = $battleId;
        foreach ($re as $key => $value) {
            $result['Map'][$value['id']] = $value;
            if(!empty($value['player_id']) && empty($result['Player'][$value['player_id']])){
                $whiteList = ["id", "user_code","server_id","nick","avatar_id","level","current_exp","next_exp","wall_durability","wall_durability_max","wall_intact","durability_last_update_time","last_repair_time","fire_end_time","food_out","move_in_time","food_out_time","login_time","study_pay_num","guild_coin","power","step","step_set","job","appointment_time","monster_lv","monster_kill_counter","avoid_battle","avoid_battle_time","fresh_avoid_battle_time","kill_soldier_num","vip_level","vip_exp","sign_date","sign_times","prev_x","prev_y","hsb","device_type","client_id","device_token","badge","push_tag","lang","map_id","x","y","is_in_map","last_online_time","is_in_map","last_online_time"];
                $tmpPlayerInfo = $Player->getByPlayerId($value['player_id']);
                $result['Player'][$value['player_id']] = keepFields($tmpPlayerInfo, $whiteList, true);
            }
            if(!empty($value['guild_id']) && empty($result['Guild'][$value['guild_id']])){
                $result['Guild'][$value['guild_id']] = $Guild->getGuildInfo($value['guild_id']);
            }
        }
		return $result;
	}

    /**
     * 查看比赛双方人员
     * url: cross/getAllPlayerList/
     * postData: {}
     * return: {}
     */
    public function getAllPlayerListAction(){
        $player = $this->getCurrentPlayer();
        global $config;
        $serverId = $config->server_id;
        $guildId = CrossPlayer::joinGuildId($serverId, $player['guild_id']);
        $CrossBattle = new CrossBattle;
        $battleId = $CrossBattle->getBattleIdByGuildId($guildId);
        if(!empty($battleId)){
            $re = $CrossBattle->getBattle($battleId);
            $CrossPlayer = new CrossPlayer;
            $CrossPlayer->battleId = $battleId;
            $playerList1 = $CrossPlayer->getByGuildId($re['guild_1_id']);
            $playerList1 = $CrossPlayer->adapter($playerList1);
            $playerList2 = $CrossPlayer->getByGuildId($re['guild_2_id']);
            $playerList2 = $CrossPlayer->adapter($playerList2);
        }else{
            $playerList1 = [];
            $playerList2 = [];
        }
        echo $this->data->send(['1'=>$playerList1, '2'=>$playerList2]);
    }

	public function getBnqCache($type, $battleId, $para){
		if(is_array($para)){
			$_para = join('_', $para);
		}else{
			$_para = $para;
		}
		if(isset($this->bnqcache[$type][$_para])){
			return $this->bnqcache[$type][$_para];
		}else{
			switch($type){
				case 'player':
					$CrossPlayer = new CrossPlayer;
					$CrossPlayer->battleId = $battleId;
					$ret = $CrossPlayer->getByPlayerId($para);
				break;
				case 'guild':
					$Guild = new CrossGuild;
					$Guild->battleId = $battleId;
					$ret = $Guild->getGuildInfo($para);
				break;
				case 'map':
					$Map = new CrossMap;
					$ret = $Map->getByXy($battleId, $para['x'], $para['y']);
				break;
			}
			if($ret)
				$this->bnqcache[$type][$_para] = $ret;
			return $ret;
		}
	}
    /**
     * 获取跨服战军团
     * url: cross/crossArmyInfo/
     * postData: {}
     * ["army":["index":1,"general_ids":[20017,20025,20018,20022,20021]]]
     * ["skill":[["generalId":11,"skillId":22],["generalId":111,"skillId":222]]]
     * return: {}
     */
	public function crossArmyInfoAction(){
        $player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $guildId = $player['guild_id'];
        if($guildId==0) {
            $errCode = 10637;//[跨服战军团]你当前没有加入联盟，不能提交出战申请
            goto sendErr;
        }
        //case 比赛状态
        $CrossRound     = new CrossRound;
        $PlayerGuild    = new PlayerGuild;
        $currentRoundId = $CrossRound->getCurrentRoundId();
        $playerGuild    = $PlayerGuild->getByPlayerId($playerId);
        $crossArmyInfo  = $playerGuild['cross_army_info'];

        if(!empty($crossArmyInfo['skill'])) {
	        $crossArmyInfo['skill'] = array_values($crossArmyInfo['skill']);
	    }
	    if(!empty($crossArmyInfo['total_skill'])) {
	        $crossArmyInfo['total_skill'] = array_values($crossArmyInfo['total_skill']);
	    }

        if(empty($crossArmyInfo)) {//如果无，则生成一次默认的
            $crossArmyInfo = $PlayerGuild->getDefaultCrossArmyInfo($playerId);
            $PlayerGuild->updateAll(['cross_army_info'=>q(json_encode($crossArmyInfo))], ['guild_id'=>$guildId, 'player_id'=>$playerId]);
            $PlayerGuild->clearGuildCache($guildId);
            (new PlayerCommonLog)->add($playerId, ['type'=>'[跨服战军团]首次', 'current_round_id'=>$currentRoundId, 'cross_army_info'=>$crossArmyInfo]);//日志记录
        }
//        dump($playerGuild['cross_army_info']);
        $postData = getPost();
        if(isset($postData['army']) || isset($postData['skill'])) {//更改的判断
            if($currentRoundId) {
                $currentRound = $CrossRound->current;
                if($currentRound['status']!=CrossRound::Status_sign) {
                    $errCode = 10638;//[跨服战军团]不在报名时间内
                    goto sendErr;
                }
            } else {
                $errCode = 10639;//[跨服战军团]不在报名时间内
                goto sendErr;
            }

            $CrossGuildInfo = new CrossGuildInfo;
            $joinGuildId    = CrossPlayer::joinGuildId($serverId, $guildId);
            $crossGuildInfo = $CrossGuildInfo->getCrossGuildBasicInfo($joinGuildId);
            if($crossGuildInfo['status']==CrossGuildInfo::Status_not_joined) {
                $errCode = 10640;//[跨服战军团]当前不是比赛报名阶段
                goto sendErr;
            }
        }
//        $postData['army'] = ['index'=>1, 'general_ids'=>[20017,20025,20018,20022,20021/*,20026*/]];
//        $postData['skill'] = [['generalId'=>20080, 'skillId'=>10006], ['generalId'=>20079, 'skillId'=>10007]];
        if(isset($postData['army'])) {//更换军团武将信息
            $PlayerGeneral = new PlayerGeneral;
            $armyData      = $postData['army'];
            $armyIndex     = $armyData['index'];//第几军团
            if(!in_array($armyIndex, [0,1])) {
                $errCode = 10641;//[跨服战军团]传入军团信息有误
                goto sendErr;
            }
            $generalIds = (Array)$armyData['general_ids'];
            if(count($generalIds)==0 || count($generalIds)>6) {
                $errCode = 10642;//[跨服战军团]武将数据有误-传入数量不对
                goto sendErr;
            }
            //case 验证武将准确性
            foreach($generalIds as $gid) {
                if(!$PlayerGeneral->getByGeneralId($playerId, $gid)) {
                    $errCode = 10643;//[跨服战军团]武将数据有误-传入玩家不存在的武将
                    goto sendErr;
                }
            }
            //case 当前技能更改：删除已有武将的技能（判断当前武将中是否有已选技能），添加新增武将的技能
            $needRemoveGeneralIds = array_diff($crossArmyInfo['army'][$armyIndex], $generalIds);
            //检查已选
            foreach($crossArmyInfo['skill'] as $k=>$v) {
                if(in_array($v['generalId'], $needRemoveGeneralIds)) {
                    unset($crossArmyInfo['skill'][$k]);
                }
            }
            //检查总技能
            foreach($crossArmyInfo['total_skill'] as $k=>$v) {
                if(in_array($v['generalId'], $needRemoveGeneralIds)) {
                    unset($crossArmyInfo['total_skill'][$k]);
                }
            }
            $needJoinGeneralIds = array_diff($generalIds, $crossArmyInfo['army'][$armyIndex]);
            if(count($needJoinGeneralIds)>0) {
                $generalCrossSkills = $PlayerGuild->getGeneralCrossSkill($playerId, $needJoinGeneralIds);//技能映射
                $crossArmyInfo['total_skill'] = array_merge($crossArmyInfo['total_skill'], $generalCrossSkills);
            }
            //入库
            $crossArmyInfo['army'][$armyIndex] = $generalIds;//替换掉新的武将
            $PlayerGuild->updateAll(['cross_army_info'=>q(json_encode($crossArmyInfo))], ['guild_id'=>$guildId, 'player_id'=>$playerId]);
            $PlayerGuild->clearGuildCache($guildId);
            (new PlayerCommonLog)->add($playerId, ['type'=>'[跨服战军团]修改军团武将', 'current_round_id'=>$currentRoundId, 'cross_army_info'=>$crossArmyInfo]);//日志记录
        } elseif (isset($postData['skill'])) {//更改技能
            //case 检查技能是否存在
            $totalSkill = $crossArmyInfo['total_skill'];
            $newSkill = $postData['skill'];
            if(count($newSkill)>2) {
                $errCode = 10644;//[跨服战军团]技能数量超过2个
                goto sendErr;
            }
            $totalLength = count($totalSkill);
            foreach($newSkill as $v1) {
                $i = 0;
                foreach($totalSkill as $v2) {
                    if($v2['generalId']==$v1['generalId'] && $v2['skillId']==$v1['skillId']) break;
                    $i++;
                }
                if($i==$totalLength) {
                    $errCode = 10645;//[跨服战军团]技能不存在
                    goto sendErr;
                }
            }
            $crossArmyInfo['skill'] = $newSkill;
            //入库
            $PlayerGuild->updateAll(['cross_army_info'=>q(json_encode($crossArmyInfo))], ['guild_id'=>$guildId, 'player_id'=>$playerId]);
            $PlayerGuild->clearGuildCache($guildId);
            (new PlayerCommonLog)->add($playerId, ['type'=>'[跨服战军团]修改主动技能', 'current_round_id'=>$currentRoundId, 'cross_army_info'=>$crossArmyInfo]);//日志记录
        }
        $data = [];
        $data['cross_army_info'] = $crossArmyInfo;
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     *  上一届杀敌排名
     * ```php
     * cross/rankList
     * ```
     */
    public function rankListAction(){
        $player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);

        $data['rank_list'] = [];
        $round = CrossRound::findFirst(['status=5','columns'=>['id'], 'order'=>'id desc']);
        if($round) {
            $roundId  = intval($round->id);
            $list     = CrossPlayer::find(['round_id=:roundId:', 'order' => 'kill_soldier desc', 'bind' => ['roundId' => $roundId], 'limit' => 30])->toArray();
            $rankList = [];
            $i        = 0;
            foreach($list as $v) {
                $rankList_                 = [];
                $rankList_['rank']         = ++$i;
                $cguild                    = CrossGuild::findFirst(["round_id=:roundId: and guild_id=:guildId:", 'bind' => ['roundId' => $roundId, 'guildId' => $v['guild_id']]]);
                $rankList_['guild_name']   = $cguild->name;
                $rankList_['avatar_id']    = intval($v['avatar_id']);
                $rankList_['nick']         = $v['nick'];
                $rankList_['kill_soldier'] = intval($v['kill_soldier']);

                $rankList[] = $rankList_;
            }
            $data['rank_list'] = $rankList;
        }
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     * 上一届联盟对战结果
     * ```php
     * cross/resultList
     * ```
     */
    public function resultListAction(){
        $player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);

        $data['result_list'] = [];
        $round = CrossRound::findFirst(['status=5','columns'=>['id'], 'order'=>'id desc']);
        if($round) {
            $roundId    = intval($round->id);
            $list       = CrossBattle::find(["round_id={$roundId}", 'columns' => ['guild_1_id', 'guild_2_id', 'win']])->toArray();
            $resultList = [];
            foreach($list as $v) {
                $resultList_                    = [];
                $resultList_['guild_1_id']      = intval($v['guild_1_id']);
                $resultList_['guild_2_id']      = intval($v['guild_2_id']);
                $cguild1                        = CrossGuild::findFirst(["round_id=:roundId: and guild_id=:guildId:", 'bind' => ['roundId' => $roundId, 'guildId' => $v['guild_1_id']]]);
                $cguild2                        = CrossGuild::findFirst(["round_id=:roundId: and guild_id=:guildId:", 'bind' => ['roundId' => $roundId, 'guildId' => $v['guild_2_id']]]);
                $resultList_['guild_1_name']    = $cguild1->name;
                $resultList_['guild_1_icon_id'] = intval($cguild1->icon_id);
                $resultList_['guild_2_name']    = $cguild2->name;
                $resultList_['guild_2_icon_id'] = intval($cguild2->icon_id);
                $resultList_['win']             = intval($v['win']);

                $resultList[] = $resultList_;
            }
            $data['result_list'] = $resultList;
        }

        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
}