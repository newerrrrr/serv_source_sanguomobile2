<?php
use Phalcon\Mvc\View;
class StudyController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
		
    /**
     * 设置武将
     * 
	 * generalId ：武将
	 * position ：位置
     * @return <type>
     */
	public function setGeneralAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$position = floor(@$post['position']);
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($position) || !checkRegularNumber($generalId, true))
			exit;
		
		$PlayerBuild = new PlayerBuild;
		//建造完成触发
		//$PlayerBuild->lvupFinish($playerId);
		//收取时产
		//$PlayerBuild->gainResource($playerId);
		//收取武将exp
		$PlayerGeneral = new PlayerGeneral;
		$PlayerGeneral->gainExp($playerId);
		$PlayerStudy = new PlayerStudy;
		
		//结算学习
		$PlayerStudy->finishStudy($playerId);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查书院是否建造
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 13)){
				throw new Exception(10199);
			}
			$playerBuild = $playerBuild[0];
			
			if($generalId){
				//获取武将
				$PlayerGeneral = new PlayerGeneral;
				$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
				if(!$playerGeneral){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//检查位置是否开启
				$Build = new Build;
				$studyNum0 = $Build->getStudyNum($playerBuild['build_id']);
				$studyNum = $studyNum0 + $player['study_pay_num'];
				if($position < 1 || $position > $studyNum){
					throw new Exception(10200);
				}
				
				//检查武将是否在其他学习位
				if($PlayerStudy->findFirst((array('player_id='.$playerId.' and general_id='.$generalId)))){
					throw new Exception(10201);
				}
				
				//检查武将是否空闲
				//检查武将状态
				if($playerGeneral['status'] != 0 || $playerGeneral['build_id'] != 0){
					throw new Exception(10202);
				}
				
				
				//检查武将经验是否已满
				/*$GeneralExp = new GeneralExp;
				$lvmax = min($GeneralExp->getMaxLv(), $player['level']+1);
				$expmax = $GeneralExp->lv2exp($lvmax)*1;
				if($playerGeneral['exp'] >= $expmax){
					throw new Exception(10203);
				}*/
				
				//获取学习位
				$playerStudy = $PlayerStudy->getByPosition($playerId, $position);
				if($playerStudy){
					//检查学习位是否占用且正在学习中
					if($playerStudy['type'] != 0 && $playerStudy['end_time'] > date('Y-m-d H:i:s')){
						throw new Exception(10204);
					}
					
					//更新原武将
					if($playerStudy['general_id']){
						$playerGeneral2 = $PlayerGeneral->getByGeneralId($playerId, $playerStudy['general_id']);
						if(!$playerGeneral2){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						if(!$PlayerGeneral->assign($playerGeneral2)->updateBuild(0)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
					
					//更新学习位
					if(!$PlayerStudy->assign($playerStudy)->updateGeneral($generalId)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}else{
					//增加学习位
					if(!$PlayerStudy->add($playerId, $position, $generalId)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				
				//更新武将位置
				//var_dump($playerGeneral);
				//$PlayerGeneral->reset();
				if(!$PlayerGeneral->assign($playerGeneral)->updateBuild($playerBuild['id'])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{//卸下
				$playerStudy = $PlayerStudy->getByPosition($playerId, $position);
				//获取武将
				$PlayerGeneral = new PlayerGeneral;
				$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $playerStudy['general_id']);
				if(!$playerGeneral){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//检查是否正在学习
				if($playerStudy['type']){
					throw new Exception(10205);
				}
				
				//更新位置
				if(!$PlayerStudy->assign($playerStudy)->updateGeneral(0)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//更新武将
				if(!$PlayerGeneral->assign($playerGeneral)->updateBuild(0)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
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
		
		//$data = DataController::get($playerId, array('PlayerStudy'));
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 开始学习
     * 
	 * position ：位置
	 * type ：学习类型，1-免费4小时，2-免费8小时，3-免费12小时，4-付费4小时，5-付费8小时，6-付费12小时
     * @return <type>
     */
	public function beginAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$position = floor(@$post['position']);
		$type = floor(@$post['type']);
		if(!checkRegularNumber($position) || !checkRegularNumber($type))
			exit;
		
		$PlayerBuild = new PlayerBuild;
		//建造完成触发
		//$PlayerBuild->lvupFinish($playerId);
		//收取时产
		//$PlayerBuild->gainResource($playerId);
		//收取武将exp
		$PlayerGeneral = new PlayerGeneral;
		$PlayerGeneral->gainExp($playerId);
		$PlayerStudy = new PlayerStudy;
		$Player = new Player;
		
		//结算学习
		$PlayerStudy->finishStudy($playerId);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查书院是否建造
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 13)){
				throw new Exception(10206);
			}
			$playerBuild = $playerBuild[0];
			
			//获取位置
			$playerStudy = $PlayerStudy->getByPosition($playerId, $position);
			if(!$playerStudy){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查位置是否空闲
			if($playerStudy['type']){
				throw new Exception(10207);
			}
			
			//检查位置是否有武将
			if(!$playerStudy['general_id']){
				throw new Exception(10208);
			}
			$generalId = $playerStudy['general_id'];
			
			//获取武将
			$PlayerGeneral = new PlayerGeneral;
			$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$playerGeneral){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			//检查武将是否空闲
			//检查武将状态
			if($playerGeneral['status'] != 0 || ($playerGeneral['build_id'] != 0 && $playerGeneral['build_id'] != $playerBuild['id']) || $playerGeneral['army_id'] != 0){
				throw new Exception(10209);
			}
			
			//检查武将经验是否已满
			$GeneralExp = new GeneralExp;
			$lvmax = min($GeneralExp->getMaxLv(), $player['level']+1);
			$expmax = $GeneralExp->lv2exp($lvmax)*1;
			if($playerGeneral['exp'] >= $expmax){
				throw new Exception(10210);
			}
			
			//分析价格和市场
			$Library = new Library;
			$library = $Library->dicGetAll();
			if(!isset($library[$type])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$c = $library[$type];
			$time = $c['time'];
			$sec = $time * 3600;
			$pay = $c['cost'];
			$mag = $c['rate'];
			
			//获得经验
			$Build = new Build;
			$build = $Build->dicGetOne($playerBuild['build_id']);
			$addexp = $build['general_exp'] * $mag * $time;
			
			//消费元宝
			if($pay){
				if(!$Player->updateGem($playerId, -$pay)){
					throw new Exception(10211);
				}
			}
			
			//更新学习位
			$startTime = date('Y-m-d H:i:s');
			$endTime = date('Y-m-d H:i:s', strtotime($startTime) + $sec);
			if(!$PlayerStudy->assign($playerStudy)->beginStudy($type, $addexp, $startTime, $endTime)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if(!$PlayerGeneral->addExp($playerId, array($generalId), $addexp)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新武将状态
			if(!$PlayerGeneral->assign($playerGeneral)->updateStudy()){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新建筑工作状态
			$PlayerStudy->refreshWork($playerId);
				
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
		
		//$data = DataController::get($playerId, array('PlayerStudy'));
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 完成学习
     * 
     * @return <type>
     */
	public function finishAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		
		$PlayerBuild = new PlayerBuild;
		//建造完成触发
		//$PlayerBuild->lvupFinish($playerId);
		//收取时产
		//$PlayerBuild->gainResource($playerId);
		//收取武将exp
		$PlayerGeneral = new PlayerGeneral;
		$PlayerGeneral->gainExp($playerId);
		$PlayerStudy = new PlayerStudy;
		
		//结算学习
		$PlayerStudy->finishStudy($playerId);
		
		//$data = DataController::get($playerId, array('PlayerStudy'));
		echo $this->data->send();
	}
	
	/**
     * 加速学习
     * 
	 * position ：位置
     * @return <type>
     */
	public function accelerateAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$position = floor(@$post['position']);
		if(!checkRegularNumber($position))
			exit;
		
		$PlayerBuild = new PlayerBuild;
		//建造完成触发
		//$PlayerBuild->lvupFinish($playerId);
		//收取时产
		//$PlayerBuild->gainResource($playerId);
		//收取武将exp
		$PlayerGeneral = new PlayerGeneral;
		$PlayerGeneral->gainExp($playerId);
		$PlayerStudy = new PlayerStudy;
		$Player = new Player;
		
		//结算学习
		$PlayerStudy->finishStudy($playerId);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取位置
			$playerStudy = $PlayerStudy->getByPosition($playerId, $position);
			if(!$playerStudy){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查位置是否学习中
			if(!$playerStudy['type']){
				throw new Exception(10212);
			}
			
			//检查位置是否有武将
			if(!$playerStudy['general_id']){
				throw new Exception(10213);
			}
			$generalId = $playerStudy['general_id'];
			
			//分析需要的元宝
			$Library = new Library;
			$library = $Library->dicGetAll();
			if(!isset($library[$playerStudy['type']])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$c = $library[$playerStudy['type']];
			$leftTime = $playerStudy['end_time'] - time();
			$pay = ceil($leftTime / $c['clear_time']);
			
			//消费元宝
			if($pay){
				if(!$Player->updateGem($playerId, -$pay)){
					throw new Exception(10214);
				}
			}
			
			//更新学习位
			if(!$PlayerStudy->assign($playerStudy)->accelerateStudy()){
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
		
		//结算学习
		$PlayerStudy->finishStudy($playerId);
		
		//$data = DataController::get($playerId, array('PlayerStudy'));
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 购买学习位
     * 
     * @return <type>
     */
	public function buyPositionAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$Player = new Player;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取位置购买配置
			$costId = 101;
			$Cost = new Cost;
			$cost = $Cost->getByCostId($costId);
			if(!$cost){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查已买位置
			if($player['study_pay_num'] >= count($cost)){
				throw new Exception(10215);
			}
			
			//分析需要元宝
			if(!$Cost->updatePlayer($playerId, $costId, $player['study_pay_num']+1)){
				throw new Exception(10216);
			}
			/*$libraryPosition = array_values($libraryPosition);
			$pay = $libraryPosition[$player['study_pay_num']]['cost'];
			if(!$pay){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//消费元宝
			if(!$Player->updateGem($playerId, -$pay)){
				throw new Exception(10217);
			}
			*/
			
			//更新玩家信息
			if(!$Player->updateAll(array('study_pay_num'=>'study_pay_num+1'), array('id'=>$playerId))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$Player->clearDataCache($playerId);
			
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

	
}