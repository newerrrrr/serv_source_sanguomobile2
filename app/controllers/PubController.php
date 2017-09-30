<?php
//酒馆
use Phalcon\Mvc\View;
class PubController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 刷新(废弃)
     * 
     * $_POST['type'] 1：免费，2 : 道具/付费
     * @return <type>
     */
	public function reloadAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$type = floor(@$post['type']);
		if(!in_array($type, array(1, 2)))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//酒馆是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 14)){
				throw new Exception(10153);
			}
			
			//获取玩家酒馆
			$PlayerPub = new PlayerPub;
			$playerPub = $PlayerPub->getByPlayerId($playerId);
			if(!$playerPub){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//查找已有武将
			$PlayerGeneral = new PlayerGeneral;
			$generalIds = $PlayerGeneral->getGeneralIds($playerId);
			$General = new General;
			$_generalIds = [];
			foreach($generalIds as $_id){
				$_general = $General->getByGeneralId($_id);
				if(!$_general)
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				$_generalIds[] = $_general['id'];
			}
			$generalIds = $_generalIds;
			
			//读取pub数据
			$Pub = new Pub;
			$pub = $Pub->dicGetOne($playerBuild[0]['build_id']);
			
			$Drop = new Drop;
			$updatePub = array();
			$dropIds = array();
			$ordinaryDrop = 0;
			if(1 == $type){//免费
				//判断免费刷新时间是否到
				if($playerPub['next_free_time'] > time()){
					throw new Exception(10154);
				}
				
				//获取dropid
				$updatePub['luck_counter'] = $playerPub['luck_counter'] + 3;
				if($playerPub['build_id'] != $playerBuild[0]['build_id']){
					$updatePub['build_id'] = $playerBuild[0]['build_id'];
					$dropIds[] = $pub['first_drop'];
				}
				if($updatePub['luck_counter'] >= rand($pub['min'], $pub['max'])){
					$updatePub['luck_counter'] = 0;
					$dropIds[] = $pub['senior_drop'];
				}
				while(count($dropIds) < 3){
					$dropIds[] = $pub['ordinary_drop'];
				}
				
				//更新下一次免费时间
				$updatePub['next_free_time'] = date('Y-m-d H:i:s', time()+$pub['time']);
				$ordinaryDrop = $pub['ordinary_drop'];
			}else{//道具/付费
				//有道具消费道具
				$itemId = 22601;
				$PlayerItem = new PlayerItem;
				if($PlayerItem->drop($playerId, $itemId, 1)){	
					
				}else{
				//没有道具则付费
					$updatePub['pay_day_counter'] = $playerPub['pay_day_counter']+1;
					
					$costId = $pub['cost'];
					$Cost = new Cost;
					if(!$Cost->updatePlayer($playerId, $costId, $updatePub['pay_day_counter'])){
						throw new Exception(10155);
					}
				}
				
				$updatePub['pay_luck_counter'] = $playerPub['pay_luck_counter'] + 3;
				if($playerPub['pay_build_id'] != $playerBuild[0]['build_id']){
					$updatePub['pay_build_id'] = $playerBuild[0]['build_id'];
					$dropIds[] = $pub['gem_first_drop'];
				}
				if($updatePub['pay_luck_counter'] >= rand($pub['min'], $pub['max'])){
					$updatePub['pay_luck_counter'] = 0;
					$dropIds[] = $pub['gem_senior_drop'];
				}
				while(count($dropIds) < 3){
					$dropIds[] = $pub['gem_ordinary_drop'];
				}

				$ordinaryDrop = $pub['gem_ordinary_drop'];
			}
			
			//设置drop例外
			$Drop->setExcept($playerId, array(3=>$generalIds));
	
			//从drop获得武将
			$getGenerals = array();
			foreach($dropIds as $_k => $_did){
				$ret = $Drop->rand($playerId, array($_did));
				if(!$ret){
					//if($_k == 2){
						continue;
					/*}else{
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}*/
				}
				foreach($ret as $_r){
					$getGenerals[] = $_r[1];
					$generalIds[] = $_r[1];
				}
				$Drop->setExcept($playerId, array(3=>$generalIds));
			}
			//补充
			while(count($getGenerals) < 3){
				$ret = $Drop->rand($playerId, array($ordinaryDrop));
				if(!$ret)
					break;
				$getGenerals[] = $ret[0][1];
				$generalIds[] = $ret[0][1];
				$Drop->setExcept(array(3=>$generalIds));
			}

			if(count($getGenerals) == 0){
				throw new Exception(10295);//武将已经全部招募
			}
			shuffle($getGenerals);
			
			//更新酒馆武将
			//$updatePub['generals'] = join(',', $getGenerals);
			if(!$PlayerPub->assign($playerPub)->updateGeneral($getGenerals, $updatePub)){
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
     * 招武将（废弃）
     * 
     * 
     * @return <type>
     */
	public function buyAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//酒馆是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 14)){
				throw new Exception(10156);
			}			
			
			//获取酒馆
			$PlayerPub = new PlayerPub;
			$playerPub = $PlayerPub->getByPlayerId($playerId);
			if(!$playerPub){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断武将是否存在
			$PlayerGeneral = new PlayerGeneral;
			$generalIds = $PlayerGeneral->getGeneralIds($playerId);
			if(in_array($generalId, $generalIds)){
				throw new Exception(10157);
			}
			$hasGeneralNum = count($generalIds);
			
			//判断武将是否存在列表中
			if(!$playerPub['generals']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$generalIds = explode(',', $playerPub['generals']);
			if(!in_array($generalId, $generalIds)){
				throw new Exception(10158);
			}
			
			//判断武将个数上限
			$maxGeneralNum = $PlayerGeneral->getMaxGeneral($playerId, $playerBuild[0]['build_id']);
			/*$Build = new Build;
			$maxGeneralNum = $Build->getMaxGeneral($playerBuild[0]['build_id']);
			$Master = new Master;
			$player = $this->getCurrentPlayer();
			$maxGeneralNum = $Master->getMaxGeneral($player['level']);*/
			if($hasGeneralNum >= $maxGeneralNum){
				throw new Exception(10159);
			}
			
			//得到武将价格-消耗
			$General = new General;
			$general = $General->dicGetOne($generalId);
			if(!$general){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$gold = $general['cost_gold'];
			$Player = new Player;
			if(!$Player->hasEnoughResource($playerId, array('gold'=>$gold))){
				throw new Exception(10160);
			}
			if(!$Player->updateResource($playerId, array('gold'=>-$gold))){
				throw new Exception(10161);
			}
			
			//增加武将
			if(!$PlayerGeneral->add($playerId, $general['general_original_id'])){
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
		
		//如果首次招武将，编组
		/*if(!$hasGeneralNum){
			$post['position'] = 1;
			$PlayerSoldier = new PlayerSoldier;
			$playerSoldier = $PlayerSoldier->getByPlayerId($playerId);
			$post['unit'] = [1=>[$general['general_original_id'], $playerSoldier[0]['soldier_id'], $playerSoldier[0]['num']]];
			$_REQUEST['json'] = json_encode($post);
			$ArmyController = new ArmyController;
			$ArmyController->setUnitAction();
			return;
		}*/
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 招安
     * 
     * 
     * @return <type>
     */
	public function buyPrisonerAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//酒馆是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 14)){
				throw new Exception(10162);
			}			
			
			//获取酒馆
			$PlayerPub = new PlayerPub;
			$playerPub = $PlayerPub->getByPlayerId($playerId);
			if(!$playerPub){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断武将是否存在
			$PlayerGeneral = new PlayerGeneral;
			$generalIds = $PlayerGeneral->getGeneralIds($playerId);
			/*if(in_array($generalId, $generalIds)){
				throw new Exception(10163);
			}*/
			if($PlayerGeneral->hasSameGeneral($playerId, $generalId)){
				throw new Exception(10163);
			}
			
			if((new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断武将是否存在列表中
			/*if(!$playerPub['prisoners']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$generalIds = explode(',', $playerPub['prisoners']);
			if(!in_array($generalId, $generalIds)){
				throw new Exception(10164);
			}*/
			
			//判断武将个数上限
			/*$Master = new Master;
			$player = $this->getCurrentPlayer();
			$maxGeneralNum = $Master->getMaxGeneral($player['level']);*/
			$Build = new Build;
			$maxGeneralNum = $Build->getMaxGeneral($playerBuild[0]['build_id']);
			if(count($generalIds) >= $maxGeneralNum){
				throw new Exception(10165);
			}
			
			//得到武将价格-消耗
			$General = new General;
			$general = $General->getByGeneralId($generalId);
			if(!$general){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			/*$gold = $general['cost_gold'];
			$Player = new Player;
			if(!$Player->hasEnoughResource($playerId, array('gold'=>$gold))){
				throw new Exception(10166);
			}
			if(!$Player->updateResource($playerId, array('gold'=>-$gold))){
				throw new Exception(10167);
			}*/
			//消耗碎片
			if(!$general['piece_item_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->drop($playerId, $general['piece_item_id'], $general['piece_required'])){
				throw new Exception(10345);//武将信物不足
			}
			
			//增加武将
			if(!$PlayerGeneral->add($playerId, $generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($general['general_quality'] >= 4){
				(new RoundMessage)->addNew($playerId, ['type'=>2, 'general_id'=>$generalId]);//走马灯公告
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
     * 购买碎片/对酒
     * 
     * 
     * @return <type>
     */
	public function buyFragmentAction(){
		$player   = $this->getCurrentPlayer();
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$num = floor(@$post['num']);
		if(!checkRegularNumber($generalId) || !checkRegularNumber($num))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//酒馆是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 14)){
				throw new Exception(10156);
			}			
			
			//判断武将是否存在
			$PlayerGeneral = new PlayerGeneral;
			/*if($PlayerGeneral->getByGeneralId($playerId, $generalId)){
				throw new Exception(10157);
			}*/
			if($PlayerGeneral->hasSameGeneral($playerId, $generalId)){
				throw new Exception(10157);
			}
			
			if((new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断vip
			if($player['vip_level'] < (new Starting)->getValueByKey("buy_general_vip_lv_open")){
				throw new Exception(10485);//vip等级不足
			}
			if(!(new PlayerBuff)->getPlayerBuff($playerId, 'vip_active')){
				throw new Exception(10486);//vip未激活
			}
			
			//获取武将
			$general = (new General)->getByGeneralId($generalId);
			if(!$general){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$general['sell_price']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断信物数量
			$PlayerItem = new PlayerItem;
			$hasNum = $PlayerItem->hasItemCount($playerId, $general['piece_item_id']);
			if(!$hasNum){
				throw new Exception(10487);//请先获得该武将信物，方可使用对酒功能
			}
			if($hasNum >= $general['piece_required']){
				throw new Exception(10488);//碎片已满
			}
			
			//num不得高于差值
			if($num > $general['piece_required'] - $hasNum){
				throw new Exception(10489);//购买数量超过上限
			}
			
			//计算gem消耗
			$gem = $general['sell_price'] * $num;
			//消耗gem
			if(!(new Player)->updateGem($playerId, -$gem, true, ['cost'=>10017])){
				throw new Exception(10251);
			}
			
			//增加碎片
			if(!$PlayerItem->add($playerId, $general['piece_item_id'], $num)){
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
     * 化神
     * 
     * 
     * @return <type>
     */
	public function turnGodAction(){
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$General = new General;
			//判断武将是否存在
			$PlayerGeneral = new PlayerGeneral;
			$pg = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$pg){
				throw new Exception(10498);//武将不存在
			}
			
			//判断武将状态是否外出
			if($pg['status']){
				throw new Exception(10499);//武将出征中
			}
			
			//判断是否为橙色武将
			$general = $General->getByGeneralId($generalId);
			if($general['general_quality'] != 5){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断是否开启化神
			if(!$general['condition']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取神id
			$ids = $General->getBySameRoot($generalId);
			foreach($ids as $_id){
				if($General->isGod($_id)){
					$godGeneralId = $_id;
					break;
				}
			}
			if(!@$godGeneralId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$godGeneral = $General->getByGeneralId($godGeneralId);
			if(!$godGeneral){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$godGeneral['condition']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查神是否已经拥有
			if($PlayerGeneral->getByGeneralId($playerId, $godGeneralId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查化神条件
			$GeneralConditionType = new GeneralConditionType;
			$gct = $GeneralConditionType->dicGetOne($godGeneral['condition']);
			if(!$gct){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			switch($gct['type']){
				case 1:
					$equip = (new Equipment)->dicGetOne($pg['weapon_id']);
					if(!$equip){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					if($equip['star_level'] < $gct['para1']){
						throw new Exception(10500);//武器星级不足
					}
					break;
				case 2:
					if($PlayerGeneral->getGeneralIds($playerId) < $gct['para1']){
						throw new Exception(10501);//拥有武将数不足
					}
					break;
				case 3:
					if(!(new PlayerBuild)->isBuildExist($playerId, $gct['para1'])){
						throw new Exception(10502);//化神所需建筑等级不足
					}
					break;
				case 4:
					if(!(new PlayerScience)->isScienceExist($playerId, $gct['para1'])){
						throw new Exception(10503);//化神所需科技未研究
					}
					break;
				default:
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//consume
			if(!(new Consume)->del($playerId, $godGeneral['consume'])){
				throw new Exception(10504);//尚未拥有化神信物
			}
			
			//查找是否存在于军团队长，更新
			$PlayerArmy = new PlayerArmy;
			$pa = $PlayerArmy->getByPlayerId($playerId);
			foreach($pa as $_pa){
				if($_pa['leader_general_id'] == $generalId){
					if(!$PlayerArmy->assign($_pa)->updateGeneral($godGeneralId)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					break;
				}
			}
			
			//如果存在于军团，更新
			(new PlayerArmyUnit)->updateGeneral($playerId, $generalId, $godGeneralId);
			
			//如果pk，更新
			(new PkPlayerInfo)->updateGeneralId($playerId, $generalId, $godGeneralId);
			
			//修改player_general, general_id,技能等级，成长率
			if(!$PlayerGeneral->updateToGod($playerId, $generalId, $godGeneralId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerGuild)->updateCrossGeneralSkill($playerId, $generalId, $godGeneralId, 0);
			
			//修改驻守
			if($pg['build_id']){
				$PlayerBuild = new PlayerBuild;
				$pb = $PlayerBuild->findFirst(["id=".$pg['build_id']]);
				if(!$pb){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$pb = $pb->toArray();
				if(!$PlayerBuild->updateGeneral($playerId, $pb['position'], $godGeneralId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			/*if(!$PlayerGeneral->refreshBuild($playerId, $generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
            //S. 特定drop发到走马灯里
            $rmdata['general_id']  = $godGeneralId;
            $rmdata['player_nick'] = $player['nick'];
            (new RoundMessage)->addNew($playerId, ['type'=>9, 'data'=>$rmdata]);//走马灯公告
			
			(new PlayerCommonLog)->add($playerId, ['type'=>'化神', 'memo'=>['generalId'=>$godGeneralId]]);
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
     * 升星
     * 
     * 
     * @return <type>
     */
	public function starLvUpAction(){
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			if((new PlayerBuild)->getPlayerCastleLevel($playerId) < (new Starting)->dicGetOne('starup_open_lv')){
				throw new Exception(10692);//府衙未达到12级
			}
			
			$General = new General;
			//判断武将是否存在
			$PlayerGeneral = new PlayerGeneral;
			$pg = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$pg){
				throw new Exception(10498);//武将不存在
			}
			
			//判断武将状态是否外出
			if($pg['status']){
				throw new Exception(10499);//武将出征中
			}
			
			//判断是否为神武将
			$general = $General->getByGeneralId($generalId);
			if(!$General->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断下一星级是否存在
			$newStar = $pg['star_lv']+1;
			$generalStar = (new GeneralStar)->getByGeneralId($generalId, $newStar);
			if(!$generalStar){
				throw new Exception(10661);//星级已满
			}
			
			//consume
			$generalStarNew = (new GeneralStar)->getByGeneralId($generalId, $newStar);
			if(!$generalStarNew){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!(new Consume)->del($playerId, $generalStarNew['consume'])){
				throw new Exception(10662);//升星所需物品不足
			}
			
			
			//修改player_general, general_id,技能等级，成长率
			if($newStar == 5){
				$battleSkill = $general['general_battle_skill'];
				(new PlayerGuild)->updateCrossGeneralSkill($playerId, $generalId, $generalId, $battleSkill);
			}else{
				$battleSkill = 0;
			}
			if(!$PlayerGeneral->updateStar($playerId, $generalId, $newStar, $battleSkill)){
				throw new Exception(10663);//星级已满
			}
			
			//如果等于最大星级，转换剩余将魂
			$maxStar = (new GeneralStar)->maximum(array('column'=>'star', 'general_original_id='.$generalId));
			if($maxStar == $newStar){
				$consume = parseGroup($generalStarNew['consume'], false);
				$itemId = $consume[0][1];
				if(!(new PlayerItem)->transferSoul($playerId, $itemId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);

				}
			}
			
			
			(new PlayerCommonLog)->add($playerId, ['type'=>'升星', 'memo'=>['generalId'=>$generalId, 'newStar'=>$newStar]]);
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
     * 领取星级奖励
     * 
     * 
     * @return <type>
     */
	public function starRewardAction(){
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$id = floor(@$post['id']);
		if(!checkRegularNumber($id))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取奖项配置
			$GeneralTotalStars = new GeneralTotalStars;
			$gts = $GeneralTotalStars->dicGetOne($id);
			if(!$gts){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//查看是否领奖
			$PlayerInfo = new PlayerInfo;
			$playerInfo = $PlayerInfo->getByPlayerId($playerId);
			if(in_array($id, $playerInfo['general_star_reward'])){
				throw new Exception(10664);//奖励已领取
			}
			
			//获取所有武将
			$PlayerGeneral = new PlayerGeneral;
			$playerGenrals = $PlayerGeneral->getByPlayerId($playerId);
			
			//循环计算星级和
			$starTotal = 0;
			$General = new General;
			foreach($playerGenrals as $_g){
				if(!$General->isGod($_g['general_id'])){
					$starTotal++;
				}else{
					$starTotal += floor($_g['star_lv'] / 5) + 1;
				}
			}
			
			//星级是否达到标准
			if($starTotal < $gts['total_stars']){
				throw new Exception(10665);//武将星级未达到要求
			}
			
			//领奖
			if(!(new Drop)->gain($playerId, [$gts['drop_id']], 1, '武将星级奖励')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新flag
			$updateData = [];
			$updateData['general_star_reward'] = $playerInfo['general_star_reward'];
			$updateData['general_star_reward'][] = $id;
			if(!$PlayerInfo->alter($playerId, $updateData)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerCommonLog)->add($playerId, ['type'=>'武将星级奖励', 'memo'=>['id'=>$id]]);
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
     * 技能升级
     * 
     * @return <type>
     */
	public function upGodSkillAction(){
		$bookItemId = 51011;
		
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取武将
			$PlayerGeneral = new PlayerGeneral;
			$pg = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$pg){
				throw new Exception(10505);//武将不存在
			}
			
			//是否为神武将
			if(!(new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($pg['skill_lv'] >= $pg['lv']){
				throw new Exception(10529);//技能等级不能超过武将等级
			}
			
			//获取指定技能下一等级
			$GeneralSkillLevelup = new GeneralSkillLevelup;
			$nextLv = $pg['skill_lv']+1;
			$maxLv = $GeneralSkillLevelup->getMaxLv();
			if($nextLv > $maxLv){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取升级技能书数量
			$gsl = $GeneralSkillLevelup->dicGetOne($nextLv);
			if(!$gsl){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//消耗书
			if(!(new PlayerItem)->drop($playerId, $bookItemId, $gsl['general_skill_exp'])){
				throw new Exception(10506);//所需技能书数量不足
			}
			
			//更新技能等级
			if(!$PlayerGeneral->assign($pg)->updateSkill($nextLv)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerCommonLog)->add($playerId, ['type'=>'技能升级', 'memo'=>['generalId'=>$generalId, 'newLevel'=>$nextLv]]);
			
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
     * 升级城战技能
     * 
     * 
     * @return <type>
     */
	public function upBattleSkillAction(){
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$id		= floor(@$post['id']);
		if(!checkRegularNumber($generalId) || !checkRegularNumber($id) || !in_array($id, [1, 2, 3]))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$addNum = 0;
			
			//获取武将
			$PlayerGeneral = new PlayerGeneral;
			$pg = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$pg){
				throw new Exception(10505);//武将不存在
			}
			
			//是否为神武将
			if(!(new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查技能是否存在
			if(!$pg['cross_skill_id_'.$id]){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取指定技能下一等级
			$BattleSkillLevelup = new BattleSkillLevelup;
			$nextLv = $pg['cross_skill_lv_'.$id]+1;
			$bsl = $BattleSkillLevelup->dicGetOne($nextLv);
			if(!$bsl){
				throw new Exception(10666);//技能已经满级
			}
			
			//消耗
			if(!(new Consume)->del($playerId, $bsl['consume'], '城战技能升级')){
				throw new Exception(10667);//升级所需道具不足
			}
			
			//更新技能等级
			if(!$PlayerGeneral->assign($pg)->updateBattleSkill($id, $nextLv)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerCommonLog)->add($playerId, ['type'=>'城战技能升级', 'memo'=>['generalId'=>$generalId, 'skillId'=>$pg['cross_skill_id_'.$id], 'newLevel'=>$nextLv]]);
			
			(new CityBattleScience)->effectTianshu($playerId, $addNum);
			
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
			echo $this->data->send(['addNum'=>$addNum*1]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 武将吃书增加经验
     * 
     * 
     * @return <type>
     */
	public function generalAddExpAction(){
		/*$itemIds = [//todo
			20801 => 10,
		];*/
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$itemId = floor(@$post['itemId']);
		$num = floor(@$post['num']);
		if(!checkRegularNumber($generalId) || !checkRegularNumber($itemId) || !checkRegularNumber($num))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取武将
			$PlayerGeneral = new PlayerGeneral;
			$pg = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$pg){
				throw new Exception(10507);//武将不存在
			}
			//检查是否满级
			if($pg['lv'] >= (new GeneralExp)->getMaxLv()){
				throw new Exception(10528);//武将等级已满
			}
			
			//道具id验证
			$Item = new Item;
			$item = $Item->dicGetOne($itemId);
			if(!$item){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$drop = (new Drop)->dicGetOne($item['drop'][0][0]);
			if(!($drop['drop_data'][0][0] == 1 && $drop['drop_data'][0][1] == 11900)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$exp = $drop['drop_data'][0][2];
			/*if(!isset($itemIds[$itemId])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
			
			//是否为神武将
			if(!(new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//消耗书
			if(!(new PlayerItem)->drop($playerId, $itemId, $num)){
				throw new Exception(10508);//道具数量不足
			}
			
			//增加经验
			$exp = $exp*$num;
			if(!(new PlayerGeneral)->addExp($playerId, $generalId, $exp)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			(new PlayerCommonLog)->add($playerId, ['type'=>'武将增加经验', 'generalId'=>$generalId, 'itemId'=>$itemId, 'num'=>$num]);
			
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
			echo $this->data->send(['itemId'=>$itemId, 'num'=>$num]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 神盔甲合成
     * 
     * 
     * @return <type>
     */
	public function combineGodArmorAction(){
		$itemIds = [
			51001, 51002, 51003, 51004, 51005, 51006
		];
		$ret = [];
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerItem = new PlayerItem;
			$General = new General;
			$PlayerGeneral = new PlayerGeneral;
			//扣除一套神盔甲
			foreach($itemIds as $_itemId){
				if(!$PlayerItem->drop($playerId, $_itemId, 1)){
					throw new Exception(10509);//神盔甲不足
				}
			}
			$ret = [];
			
			$_ret = (new Drop)->gain($playerId, [230006]);
			if(!$_ret){
				throw new Exception(10519);//目前尚无可获取的神信物
			}
			$ret[] = [2, $_ret[0]['id']*1, 1];

			$newGodGeneralId = $General->findFirst(['piece_item_id='.$ret[0][1]])->general_original_id;
			/*
			//获取已开放神武将id
			$godGeneralIds = $General->getAllGodGeneralIds();
			
			//获取已有的神武将
			$pg = $PlayerGeneral->getGeneralIds($playerId);
			$myGodGeneralIds = [];
			foreach($pg as $_pg){
				if($General->isGod($_pg)){
					$myGodGeneralIds[] = $_pg;
				}
			}
			
			//获取已有的神信物
			$godPieceIds = array_keys($PlayerItem->findList('item_id', null, ['player_id='.$playerId.' and item_id > 41000 and item_id < 42000']));
			$myGodGeneralIds2 = [];
			if($godPieceIds){
				$myGodGeneralIds2 = array_keys($General->findList('general_original_id', null, ['piece_item_id in ('.join(',', $godPieceIds).')']));
			}
			$myGodGeneralIds = $myGodGeneralIds + $myGodGeneralIds2;
			
			//算出可获取的神信物
			$godGeneralPool = array_diff($godGeneralIds, $myGodGeneralIds);
			if(!$godGeneralPool){
				throw new Exception(10510);//目前尚无可获取的神信物
			}
			shuffle($godGeneralPool);
			$newGodGeneralId = $godGeneralPool[0];
			$newGeneral = $General->getByGeneralId($newGodGeneralId);
			if(!$newGeneral){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$newItemId = $newGeneral['piece_item_id'];
			$ret[] = $newItemId;
			
			//增加神信物
			if(!$PlayerItem->add($playerId, $newItemId, 1)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			*/
			//如果对应橙武将不存在 并且 信物未满，给予一个信物
			if(!in_array($newGodGeneralId, [10105, 10106])){
				$hasOrangeGeneral = false;
				$orangeGeneralId = 0;
				$ids = $General->getBySameRoot($newGodGeneralId);
				foreach($ids as $_id){
					if(!$General->isGod($_id)){
						$orangeGeneralId = $_id;
						break;
					}
				}
				$orangeGeneral = $General->getByGeneralId($orangeGeneralId);
				if(!$orangeGeneral){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$pg = $PlayerGeneral->getGeneralIds($playerId);
				$hasItemNum = $PlayerItem->hasItemCount($playerId, $orangeGeneral['piece_item_id']);
				if(!in_array($orangeGeneralId, $pg) && $hasItemNum < $orangeGeneral['piece_required']){
					$pieceNum = $orangeGeneral['piece_required'] - $hasItemNum;
					if(!$PlayerItem->add($playerId, $orangeGeneral['piece_item_id'], $pieceNum)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					$ret[] = [2, $orangeGeneral['piece_item_id']*1, $pieceNum];
				}
			}
            $rmdata['item_id']     = $_ret[0]['id'];
            $rmdata['player_nick'] = $player['nick'];
            (new RoundMessage)->addNew($playerId, ['type'=>8, 'data'=>$rmdata]);//走马灯公告
			
			(new PlayerCommonLog)->add($playerId, ['type'=>'合成神盔甲', 'memo'=>['itemId'=>$ret[0][1]]]);
			
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
			echo $this->data->send(['itemIds'=>$ret]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 技能洗炼
     * 
     * 
     * @return <type>
     */
	public function washBattleSkillAction(){
		$player   = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$id = floor(@$post['id']);
		if(!checkRegularNumber($generalId) || !checkRegularNumber($id) || !in_array($id, [1, 2, 3]))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$addNum = 0;
			$BattleSkill = new BattleSkill;
			//获取武将
			$PlayerGeneral = new PlayerGeneral;
			$pg = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$pg){
				throw new Exception(10505);//武将不存在
			}
			
			//是否为神武将
			if(!(new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查技能槽是否已经开启
			if($id > floor($pg['star_lv'] / 5)){
				throw new Exception(10710);//技能槽还未开启
			}
			
			$hasActive = 0;
			for($i=1;$i<=3;$i++){
				if($pg['cross_skill_id_'.$i] && $i != $id){
					if($BattleSkill->dicGetOne($pg['cross_skill_id_'.$i])['if_active']){
						$hasActive = 1;
					}
				}
			}

			//获取技能池
			$pool = (new BattleSkill)->getPoolByGeneralId($generalId, $hasActive);
			if(!$pool){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//技能池删减已有技能
			//$hasSkillIds = [];
			for($i=1;$i<=3;$i++){
				if($pg['cross_skill_id_'.$i]/* && $i != $id*/){
					//$hasSkillIds[] = $pg['cross_skill_id_'.$i]*1;
					unset($pool[$pg['cross_skill_id_'.$i]]);
				}
			}
			//$pool = array_diff($pool, $hasSkillIds);

			//随机技能
			//shuffle($pool);
			//$newSkillId = $pool[0];
			$newSkillId = random($pool);
			
			//如果技能槽已有技能。返还道具
			if($pg['cross_skill_id_'.$id]){
				$battleSkill = (new BattleSkill)->dicGetOne($pg['cross_skill_id_'.$id]);
				$j = $battleSkill['battle_skill_defalut_level'];
				$battleSkillLevelup = (new BattleSkillLevelup)->dicGetAll();
				$dropStr = [];
				while($j < $pg['cross_skill_lv_'.$id]){
					$dropStr[] = $battleSkillLevelup[$j+1]['consume'];
					$j++;
				}
				$dropStr = join(';', $dropStr);
				if($dropStr){
					if(!(new Drop)->gainFromDropStr($playerId, $dropStr, '技能洗炼返还')){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			
			//消耗洗练道具
			$today = date('Y-m-d');
			$PlayerInfo = new PlayerInfo;
			$playerInfo = $PlayerInfo->getByPlayerId($playerId);
			if($playerInfo['skill_wash_date'] == $today){
				$Cost = new Cost;
				if(!$Cost->updatePlayer($playerId, 26)){
					throw new Exception(10711);//洗炼所需玄铁不足
				}
			}else{
				if(!$PlayerInfo->updateWash($playerId, $today)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//更新技能
			if(!$PlayerGeneral->replaceBattleSkill($playerId, $generalId, $id, $newSkillId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerCommonLog)->add($playerId, ['type'=>'技能洗炼', 'memo'=>['generalId'=>$generalId, 'id'=>$id, 'oldSkillId'=>$pg['cross_skill_id_'.$id], 'oldSkillLv'=>$pg['cross_skill_lv_'.$id], 'newSkillId'=>$newSkillId]]);
			
			(new PlayerGuild)->updateCrossGeneralSkill($playerId, $generalId, $generalId, $newSkillId, $pg['cross_skill_id_'.$id]);
			
			(new CityBattleScience)->effectXuantie($playerId, $addNum);
			
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
			echo $this->data->send(['newSkillId'=>$newSkillId, 'addNum'=>$addNum*1]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	

	    
	/**
	 * 御龙盔甲熔炼
	 *
	 *
	 * @return <type>
	 */
	public function smeltingGodArmorAction(){
	    $itemIds = [
	        51001, 51002, 51003, 51004, 51005, 51006
	    ];

    
	    $player   = $this->getCurrentPlayer();
	    $playerId = $player['id'];
	    $post = getPost();
	    $itemList = isset($post['itemList'])? $post['itemList'] : array();
	    if(empty($itemList)){
	        throw new Exception(10712);//参数异常
	    }
	    $targetItem = array_keys($itemList);
	    $sameItem = array_intersect($itemIds, $targetItem);
	    if(empty($sameItem)){
	        throw new Exception(10713);//参数异常
	    }
	    //锁定
	    $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
	    Cache::lock($lockKey);
	    $db = $this->di['db'];
	    dbBegin($db);
	    
	    try {
	        $Item = new Item;
	        $PlayerItem = new PlayerItem;
	        
	        $Drop = new Drop();
	        $General = new General;
	        $PlayerGeneral = new PlayerGeneral;
	        
	        $result = [];
	        foreach($itemIds as $_itemId){
	            $dropNum = isset($itemList[$_itemId]) ? intval($itemList[$_itemId]) : 0;
	            if(!$dropNum){
	                continue;
	            }
	            if(!$PlayerItem->drop($playerId, $_itemId, $dropNum)){
	               throw new Exception(10714);//熔炼所需道具不足
	            }
	            //熔炼后产生的物品
	            $item = $Item->dicGetOne($_itemId);
	            $dropId = $item['decomposition'];
	            $dropItem = $Drop->gain($playerId,[$dropId], $dropNum, '御龙道具分解：'.$_itemId);
	            
	            if(!$dropItem){
	                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
	            }
	            $result[] = $dropItem;
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
	        echo $this->data->send(['itemIds'=>$result]);
	    }else{
	        echo $this->data->sendErr($err);
	    }
	}
	
	
	
	
}