<?php
/**
 * 士兵相关业务逻辑
 */
use Phalcon\Mvc\View;
class SoldierController extends ControllerBase{
	//士兵类型与建筑对应关系
	static $soldierTypeToBuildArr = ['1'=>'4', '2'=>'6', '3'=>'5', '4'=>'7'];

	/**
	 * [initialize description]
	 * @return [type] [description]
	 */
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}

	/**
	 * 招募士兵开始
	 *
	 * ```php
	 * /soldier/recruit/
     * postData: json={"soldierId":"", "position":"", "num":"", "useGem":""}
     * return: json{"PlayerSoldier":""}
	 * ```
	 */
	public function recruitAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$soldierId = intval($post['soldierId']);
		$position = intval($post['position']);
		$num = intval($post['num']);
		if(!empty($post['useGem'])){
			$useGem = intval($post['useGem']);
		}else{
			$useGem = 0;
		}
		

		if(!$soldierId || !$num || !$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Soldier = new Soldier;
		$Player = new Player;
		$PlayerBuild = new PlayerBuild;
		$PlayerSoldier = new PlayerSoldier;
		$Build = new Build;
		$PlayerBuff = new PlayerBuff;

		$soldierInfo = $Soldier->dicGetOne($soldierId);
		
		//确认士兵存在
		if(empty($soldierInfo)){
			$err = 10231;
			goto SendErr;
		}

		//确认兵营存在
		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo) || $playerBuildInfo['status']!=1 || $playerBuildInfo['origin_build_id']!=self::$soldierTypeToBuildArr[$soldierInfo['soldier_type']]){
			$err = 10232;
			goto SendErr;
		}

		
		
		//确认招募条件符合
		$targetBuildId = $PlayerBuild->isBuildExist($playerId, $soldierInfo['need_build_id']);
		if(!$targetBuildId || $targetBuildId!=$playerBuildInfo['build_id']){
			$err = 10233;
			goto SendErr;
		}

		$needResource = array();
		foreach ($soldierInfo['cost'] as $key=>$value) {
			$needResource[Build::$outputTypeArr[$key]] = $value*$num;
		}

		//最大数量不能超过上限
		
		
		$build = $Build->dicGetOne($targetBuildId);
		foreach($build['output'] as $k=>$v){
			if(in_array($k, [27,28,29,30])){
				switch ($k) {
					case 27:
						$soldeirType = 1;
						$numBuff = $PlayerBuff->getPlayerBuff($playerId, "training_infantry_num_plus", $position);
						$maxNum = $v+$numBuff;
						break;
					
					case 28:
						$soldeirType = 2;
						$numBuff = $PlayerBuff->getPlayerBuff($playerId, "training_cavalry_num_plus", $position);
						$maxNum = $v+$numBuff;
						break;

					case 29:
						$soldeirType = 3;
						$numBuff = $PlayerBuff->getPlayerBuff($playerId, "training_archer_num_plus", $position);
						$maxNum = $v+$numBuff;
						break;

					case 30:
						$soldeirType = 4;
						$numBuff = $PlayerBuff->getPlayerBuff($playerId, "training_siege_num_plus", $position);
						$maxNum = $v+$numBuff;
						break;
				}
				break;
			}
		}
		if($num>$maxNum){
			$err = 10466;//建造数量超出额定范围
			goto SendErr;
		}

		//确认粮食产量允许
		$resourceIn = $PlayerBuild->getTotalResourceIn($playerId, false);
		$foodIn = $resourceIn['food'];
		$foodOut = $PlayerSoldier->getPlayerFoodOut($playerId);
		$PlayerBuff = new PlayerBuff;
		$foodOutBuff = $PlayerBuff->getPlayerBuff($playerId, 'food_out_debuff');//影响粮食消耗的buff
		
		if($foodIn-$foodOut<$soldierInfo['consumption']*(1-$foodOutBuff)*$num/10000){
			$err = 10234;
			goto SendErr;
		}


		//确认资源充足
		if(!$useGem && !$Player->hasEnoughResource($playerId, $needResource)){
			$err = 10196;
			goto SendErr;
		}elseif($useGem){
			$point = ceil($soldierInfo['gem_cost']/10000*$num);
			$re = $Player->updateGem($playerId, (-1)*$point, true, ['cost'=>20201, 'memo'=>'购买士兵，数量='.$num.'，ID='.$soldierId]);
			if(!$re){
				$err = 10197;
				goto SendErr;
			}
		}

		

		if(empty($err)){
			if($useGem){
				$PlayerSoldier->updateSoldierNum($playerId, $soldierId, $num);
				$PlayerMission = new PlayerMission;
				$PlayerMission->updateMissionNumber($playerId, 4, $num);
				if($soldeirType==1){
					$PlayerMission->updateMissionNumber($playerId, 21, $num);
				}
				if($soldeirType==2){
					$PlayerMission->updateMissionNumber($playerId, 22, $num);
				}
				$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
				$PlayerTimeLimitMatch->updateScore($playerId, 11, floor($num*$soldierInfo['power']/10000));
				$PlayerTarget = new PlayerTarget;
				$PlayerTarget->updateTargetCurrentValue($playerId, 12, $num);
				$this->data->setBasic(['Player']);
				$err = 0;
			}else{
				if(!empty($needResource)){
					$conResource = array();
					foreach ($needResource as $key => $value) {
						$conResource[$key] = $value*(-1);
					}
					$Player->updateResource($playerId, $conResource);
				}
				$workArr = ['soldierId'=>$soldierId, 'num'=>$num];
				$trainSpeedBuff = $PlayerBuff->getPlayerBuff($playerId, 'train_troops_speed', $position);
				$workFinishTime = date("Y-m-d H:i:s", time()+$soldierInfo['train_time']*$num/(1+$trainSpeedBuff));
				$PlayerBuild->startWork($playerId, $position, $workFinishTime, $workArr);
				$this->data->setBasic(['Player', 'PlayerBuild']);
				$err = 0;
			}
		}

		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 解雇士兵
	 * 
	 * ```php
	 * /soldier/dismissSoldier/
     * postData: json={"soldierId":"", "num":""}
     * return: json{"PlayerSoldier":""}
	 * ```
	 */
	public function dismissSoldierAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$soldierId = intval($post['soldierId']);
		$num = intval($post['num']);

		$PlayerSoldier = new PlayerSoldier;
		$re = $PlayerSoldier->getBySoldierId($playerId, $soldierId);

		if(!empty($re) && $re['num']>=$num){
			$PlayerSoldier->updateSoldierNum($playerId, $soldierId, $num*(-1));
			$this->data->setBasic(['Player', 'PlayerSoldier']);
			echo $this->data->send($PlayerSoldier->getBySoldierId($playerId, $soldierId));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	/**
	 * 升级士兵
	 * 
	 * ```php
	 * /soldier/lvUpSoldier/
     * postData: json={"soldierId":"", "num":""}
     * return: json{"PlayerSoldier":""}
	 * ```
	 */
	public function lvUpSoldierAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$soldierId = intval($post['soldierId']);
		$num = intval($post['num']);

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$PlayerSoldier = new PlayerSoldier;
		$re = $PlayerSoldier->getBySoldierId($playerId, $soldierId);
		if(empty($re) || $re['num']<$num){
			$err = 10231;
			goto SendErr;
		}

		$Soldier = new Soldier;
		$soldierInfo = $Soldier->dicGetOne($soldierId);
		$targetSoldierId = $soldierInfo['upgrade_id'];
		$targetSoldierInfo = $Soldier->dicGetOne($targetSoldierId);

		//确认粮食产量允许
		$PlayerBuild = new PlayerBuild;
		$resourceIn = $PlayerBuild->getTotalResourceIn($playerId, false);
		$foodIn = $resourceIn['food'];
		$foodOut = $PlayerSoldier->getPlayerFoodOut($playerId);
		$PlayerBuff = new PlayerBuff;
		$foodOutBuff = $PlayerBuff->getPlayerBuff($playerId, 'food_out_debuff');//影响粮食消耗的buff
		
		if($foodIn-$foodOut<($targetSoldierInfo['consumption']-$soldierInfo['consumption'])*(1-$foodOutBuff)*$num/10000){
			$err = 10234;
			goto SendErr;
		}

		//确认招募条件符合
		$targetBuildId = $PlayerBuild->isBuildExist($playerId, $targetSoldierInfo['need_build_id']);
		if(!$targetBuildId){
			$err = 10233;
			goto SendErr;
		}


		$needResource = array();
		foreach ($soldierInfo['upgrade_cost'] as $key=>$value) {
			$needResource[Build::$outputTypeArr[$key]] = $value*$num;
		}
		//确认资源充足
		$Player = new Player;
		if(!$Player->hasEnoughResource($playerId, $needResource)){
			$err = 10196;
			goto SendErr;
		}

		if(empty($err)){
			if(!empty($needResource)){
				$conResource = array();
				foreach ($needResource as $key => $value) {
					$conResource[$key] = $value*(-1);
				}
				$Player->updateResource($playerId, $conResource);
			}
			$PlayerSoldier->updateSoldierNum($playerId, $soldierId, $num*(-1));
			$PlayerSoldier->updateSoldierNum($playerId, $targetSoldierId, $num);
			$this->data->setBasic(['Player', 'PlayerSoldier']);
			$err = 0;
		}

		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	/**
	 * 领取招募完成的士兵
	 *
	 * ```php
	 * /soldier/finishRecruit/
     * postData: json={"position":""}
     * return: json{"PlayerSoldier":""}
	 * ```
	 */
	public function finishRecruitAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);

		if(!$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Soldier = new Soldier;
		$Player = new Player;
		$PlayerBuild= new PlayerBuild;
		$PlayerSoldier = new PlayerSoldier;

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo) || $playerBuildInfo['status']!=3){
			$err = 10235;
			goto SendErr;
		}

		$workArr = $playerBuildInfo['work_content'];
		$soldierId = $workArr['soldierId'];
		$num = $workArr['num'];

		$soldierInfo = $Soldier->dicGetOne($soldierId);	

		if(empty($err)){
			$PlayerSoldier->updateSoldierNum($playerId, $soldierId, $num);
			$PlayerMission = new PlayerMission;
			$PlayerMission->updateMissionNumber($playerId, 4, $num);
			if($soldierInfo['soldier_type']==1){
				$PlayerMission->updateMissionNumber($playerId, 21, $num);
			}
			if($soldierInfo['soldier_type']==2){
				$PlayerMission->updateMissionNumber($playerId, 22, $num);
			}
			$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
			$PlayerTimeLimitMatch->updateScore($playerId, 11, floor($num*$soldierInfo['power']/10000));
			$PlayerTarget = new PlayerTarget;
			$PlayerTarget->updateTargetCurrentValue($playerId, 12, $num);
			$PlayerBuild->endWork($playerId, $position);
			$this->data->setBasic(['Player', 'PlayerBuild']);
			$err = 0;
		}

		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send($PlayerSoldier->getBySoldierId($playerId, $soldierId));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 取消招募士兵
	 *
	 * ```php
	 * /soldier/cancelRecruit/
     * postData: json={"position":""}
     * return: json{"PlayerSoldier":""}
	 * ```
	 */
	public function cancelRecruitAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);

		if(!$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Soldier = new Soldier;
		$Player = new Player;
		$PlayerBuild= new PlayerBuild;
		$PlayerSoldier = new PlayerSoldier;

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo) || $playerBuildInfo['status']!=3){
			$err = 10236;
			goto SendErr;
		}

		$workArr = $playerBuildInfo['work_content'];
		$soldierId = $workArr['soldierId'];
		$num = $workArr['num'];

		$soldierInfo = $Soldier->dicGetOne($soldierId);

		$backResource = array();
		foreach ($soldierInfo['cost'] as $key=>$value) {
			$backResource[Build::$outputTypeArr[$key]] = floor($value*$num*0.5);
		}

		if(empty($err)){
			if(!empty($backResource)){
				$Player->updateResource($playerId, $backResource);
			}
			$PlayerBuild->endWork($playerId, $position);
			$this->data->setBasic(['Player', 'PlayerBuild']);
			$err = 0;
		}

		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send($PlayerSoldier->getBySoldierId($playerId, $soldierId));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 加速招募士兵
	 *
	 * ```php
	 * /soldier/accelerateRecruit/
     * postData: json={"position":"","type":"1-金币加速 2-道具加速","itemList":['itemId'=>itemNum,'itemId'=>itemNum,'itemId'=>itemNum]}
     * return: json{"PlayerSoldier":""}
	 * ```
	 */
	public function accelerateRecruitAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);
		$type = intval($post['type']);
		$itemList = isset($post['itemList'])? $post['itemList']: array();


		if(!$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Soldier = new Soldier;
		$Player = new Player;
		$PlayerBuild= new PlayerBuild;
		$PlayerSoldier = new PlayerSoldier;

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo) || $playerBuildInfo['status']!=3){
			$err = 10237;
			goto SendErr;
		}

		$workArr = $playerBuildInfo['work_content'];
		$soldierId = $workArr['soldierId'];
		$num = $workArr['num'];

		$soldierInfo = $Soldier->dicGetOne($soldierId);

		//付费道具的使用以及确定减少时间
		switch ($type) {
			case 1:
				$second = $playerBuildInfo['work_finish_time']-time();
				$point = clacAccNeedGem($second);
				$re = $Player->updateGem($playerId, (-1)*$point, true, ['cost'=>30201, 'memo'=>'加速士兵建造，数量='.$num.'，ID='.$soldierId]);
				if(!$re){
					$err = 10198;
					goto SendErr;
				}
				break;
			case 2:	
			    if(empty($itemList)){
			        $err = 10453;//无效道具ID
			        goto SendErr;
			    }
			    $PlayerItem = new PlayerItem;
			    $Item = new Item;
			    $totalSecond = 0;
			    foreach($itemList as $itemId=>$num){
			        $maxNum = $PlayerItem->hasItemCount($playerId, $itemId);
			        if($maxNum<$num){
			            $err = 10454;//道具不足
			            goto SendErr;
			        }
			        if($itemId>0){
			            $second = $Item->getAcceSecond($itemId, 2);
			        }
			        if(empty($second)){
			            $err = 10453;//无效道具ID
			            goto SendErr;
			        }else{
			            $totalSecond += $second*$num;
			        }
			        
			    }
                $second = $totalSecond;
			    foreach($itemList as $itemId=>$num) {
			        $PlayerItem->drop($playerId, $itemId, $num);
			    }
			    break;

		}

		if(empty($err)){
			$PlayerBuild->QuickenWork($playerId, $position, $second);
			$this->data->setBasic(['Player', 'PlayerBuild']);
			$err = 0;
		}

		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 治疗受伤士兵
	 *
	 * 使用方法如下
	 * ```php
	 * soldier/cureInjuredSoldier
	 * postData: {"gem_flag":0,"soldier_injured":[{"id":1,"soldier_id":1001,"num":2},{"id":2,"soldier_id":1002,"num":1}]}
	 * return {PlayerSoldierInjured}
	 * ````
	 */
	public function cureInjuredSoldierAction(){
		$playerId    = $this->getCurrentPlayerId();
		$lockKey     = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		$PlayerBuild = new PlayerBuild;
		$Player 	 = new Player;
		$pb          = $PlayerBuild->getByOrgId($playerId, 42)[0];//获取医院
		if($pb['status']!=1 || ($pb['status']==1 && empty((new PlayerSoldierInjured)->getByPlayerId($playerId)))) {
			$errCode = 10277;//在医疗中，或者是没有伤兵可治
			goto sendErr;
		}
		$postData          = getPost();
		$gemFlag           = $postData['gem_flag'];
		$injuredSoldierArr = $postData['soldier_injured'];
		if(empty($injuredSoldierArr)) {
			$errCode = 10279;//治疗伤兵，资源不足
			goto sendErr;
		}
		//case a:消耗
		if($gemFlag==1) {//花钱
			$rescueCostGem = (new Soldier)->getSumOfRescueCostGem($playerId, $injuredSoldierArr);
			if(!(new Player)->updateGem($playerId, -$rescueCostGem, true, ['cost'=>20601, 'memo'=>'治疗伤兵'])){//gem不足
				$errCode = 10278;//治疗伤兵，宝石不足
				goto sendErr;
			} else {
				(new PlayerSoldierInjured)->cure($playerId, $injuredSoldierArr, true);
			}
		} else {//消耗资源
			$rescueData = (new Soldier)->getSumOfRescueCostAndTime($playerId, $injuredSoldierArr);
			//step1: 扣资源
			if(!$Player->hasEnoughResource($playerId, $rescueData['cost'])) {
				$errCode = 10279;//治疗伤兵，资源不足
				goto sendErr;
			}
			//step2: 进build work
			$Player->updateResource($playerId, $rescueData['cost']);
			$PlayerBuild    = new PlayerBuild;
			$pb             = $PlayerBuild->getByOrgId($playerId, 42);//获取医院
			$workFinishTime = date('Y-m-d H:i:s', time()+$rescueData['duration']);

			$pushId = (new PlayerPush)->add($playerId, 1, 400005, [], '', $workFinishTime);//伤病治疗完成

			$workContent    = ['soldier'=>$injuredSoldierArr, 'cost'=>$rescueData['cost'], 'pushId'=>$pushId];
			$PlayerBuild->startWork($playerId, $pb[0]['position'], $workFinishTime, $workContent);

		}
		
		Cache::unlock($lockKey);
		$data = DataController::get($playerId, ['PlayerSoldierInjured']);
		echo $this->data->send($data);
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}

	/**
	 * 治疗 时间到，治疗完毕,手动点击收兵
	 *
	 * 使用方法如下
	 * ```php
	 * soldier/doCureInjuredSoldier
	 * postData: {}
	 * return:
	 * ```
	 */
	public function doCureInjuredSoldierAction(){
		$playerId    = $this->getCurrentPlayerId();
		$lockKey     = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		$PlayerBuild = new PlayerBuild;
		$pb          = $PlayerBuild->getByOrgId($playerId, 42)[0];//获取医院
		if($pb['status']!=3) {
			$errCode = 10297;//治疗完成去收治伤兵-医院建筑状态！=3
			goto sendErr;
		}
		$workContent    = $pb['work_content'];
		$workFinishTime = $pb['work_finish_time'];
		$cost           = $workContent['cost'];
		$soldier        = $workContent['soldier'];
		if($workFinishTime<time()) {//结束治疗
			//伤兵归位
			(new PlayerSoldierInjured)->cure($playerId, $soldier, true);
			//结束工作
			$PlayerBuild->endWork($playerId, $pb['position']);
		}
		Cache::unlock($lockKey);
		echo $this->data->send($soldier);
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 宝石或者道具完成治疗
	 * 
	 * 使用方法如下
	 * ```php
	 * soldier/doCureInjuredSoldierWithGemOrItem
	 * postData: {"itemList":['itemId'=>itemNum,'itemId'=>itemNum,'itemId'=>itemNum]}
	 * return: {}
	 * ```
	 */
	public function doCureInjuredSoldierWithGemOrItemAction(){
		$playerId = $this->getCurrentPlayerId();
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定

		$PlayerBuild = new PlayerBuild;
		$pb          = $PlayerBuild->getByOrgId($playerId, 42)[0];//获取医院
		if($pb['status']!=3) {
			$errCode = 10298;//宝石或者道具立刻完成-医院建筑状态！=3
			goto sendErr;
		}
		$postData = getPost();
		$data = [];
		$workContent    = $pb['work_content'];
		$workFinishTime = $pb['work_finish_time'];
		$cost           = $workContent['cost'];
		$soldier        = $workContent['soldier'];
		$pushId 	    = $workContent['pushId'];
		
		if(isset($postData['itemList'])) {
		    $itemList = $postData['itemList'];			    
		    $PlayerItem = new PlayerItem;
		    $Item = new Item;
		    $totalSecond = 0;
		    foreach($itemList as $itemId=>$num){
		        $maxNum = $PlayerItem->hasItemCount($playerId, $itemId);
		        if($maxNum<$num){
		            $err = 10454;//道具不足
		            goto sendErr;
		        }
		        if($itemId>0){
		            $second = $Item->getAcceSecond($itemId, 3);
		        }
		        if(empty($second)){
		            $err = 10453;//无效道具ID
		            goto sendErr;
		        }else{
		            $totalSecond += $second*$num;
		        }
		    }
		    foreach($itemList as $itemId=>$num) {
		        $PlayerItem->drop($playerId, $itemId, $num);
		    }
		    //加速时间
		    $PlayerBuild->QuickenWork($playerId, $pb['position'], $totalSecond);
		    $data = $PlayerBuild->getByPosition($playerId, $pb['position']);
		    
		    $newFinishTime = date("Y-m-d H:i:s", $workFinishTime-$totalSecond);
		    (new PlayerPush)->updateSendTime($pushId, $newFinishTime);
		
		} else {//宝石秒时间
			$timeCost = (new Starting)->getValueByKey('time_cost');
			// $costGem = ceil(($workFinishTime-time())/$timeCost); //之前的公式
			$costGem = clacAccNeedGem($workFinishTime-time());
			if(!(new Player)->updateGem($playerId, -$costGem, true, ['cost'=>10015, 'memo'=>'加速完成治疗伤兵所需要的时间'])){//gem不足 算出来的时间消耗宝石，cost=0
				$errCode = 10300;//宝石完成治疗伤兵-gem不足
				goto sendErr;
			}
			$PlayerBuild->QuickenWork($playerId, $pb['position'], $workFinishTime-time());
			(new PlayerPush)->del($pushId);
			$data = $PlayerBuild->getByPosition($playerId, $pb['position']);
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
	 * 取消治疗伤兵
	 * 
	 * 使用方法如下
	 * ```php
	 * soldier/doCureInjuredSoldier
	 * postData: {}
	 * return:
	 */
	public function cancelInjuredSoldierAction(){
		$playerId    = $this->getCurrentPlayerId();
		$lockKey     = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		$PlayerBuild = new PlayerBuild;
		$pb          = $PlayerBuild->getByOrgId($playerId, 42)[0];//获取医院
		if($pb['status']!=3) {
			$errCode = 10301;//取消治疗伤兵-医院建筑状态！=3
			goto sendErr;
		}
		$workContent    = $pb['work_content'];
		$workFinishTime = $pb['work_finish_time'];
		$cost           = $workContent['cost'];
		$soldier        = $workContent['soldier'];
		$pushId         = $workContent['pushId'];
		if($workFinishTime>=time()) {//时间未到才可以取消治疗
			//归还资源
			(new Player)->updateResource($playerId, $cost);
			//结束工作
			$PlayerBuild->endWork($playerId, $pb['position']);
			(new PlayerPush)->del($pushId);
		}
		Cache::unlock($lockKey);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 解雇伤兵
	 * 
	 * 使用方法如下
	 * ```php
	 * soldier/fireInjuredSoldier
	 * postData: {"soldier_injured":[{"id":1,"soldier_id":1001,"num":2},{"id":2,"soldier_id":1002,"num":1}]}
	 * return {PlayerSoldierInjured}
	 * ````
	 */
	public function fireInjuredSoldierAction(){
		$playerId    = $this->getCurrentPlayerId();
		$lockKey     = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		$PlayerBuild = new PlayerBuild;
		$pb          = $PlayerBuild->getByOrgId($playerId, 42)[0];//获取屯所
		if($pb['status']!=1) {
			$errCode = 10302;//解雇伤兵-医院建筑状态！=1
			goto sendErr;
		}
		$postData = getPost();
		$injuredSoldierArr = $postData['soldier_injured'];
		(new PlayerSoldierInjured)->fire($playerId, $injuredSoldierArr);
		Cache::unlock($lockKey);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
}	