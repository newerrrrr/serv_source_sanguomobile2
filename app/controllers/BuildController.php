<?php
use Phalcon\Mvc\View;
/**
 * 建筑相关业务逻辑
 */
class BuildController extends ControllerBase{
	/**
	 * [initialize description]
	 * @return [type] [description]
	 */
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
		
    /**
     * 建造
     * 
	 * ```php
	 * /build/construct/
     * postData: json={"buildId":"", "position":""}
     * return: json{"PlayerBuild":""}
	 * ```
	 * 
     */
	public function constructAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$buildId = intval($post['buildId']);
		$position = intval($post['position']);

		if(!$buildId || !$position){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		/******************建造前检测Begin****************/
		$PlayerBuild = new PlayerBuild;
		$BuildPosition = new BuildPosition;
		$Build = new Build;
		$Player = new Player;
		
		//结束所有已完成建筑
		$PlayerBuild->finishLvUp($playerId);

		//确认建筑和占位相对应
		if(!$BuildPosition->checkBuildPosition($buildId, $position)){
			$err = 10022;
			goto SendErr;
		}

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		//确认没有重复占位
		if(!empty($playerBuildInfo)){
			$err = 10023;
			goto SendErr;
		}
		
		$buildInfo = $Build->dicGetOne($buildId);
		//确认建筑存在
		if(empty($buildInfo) && $buildInfo['build_lv_sign']==1){
			$err = 10024;
			goto SendErr;
		}

		//确认城内建筑无重复存在
		if($buildInfo['build_type']==1 && $PlayerBuild->isBuildExist($playerId, $buildId)){
			$err = 10230;
			goto SendErr;
		}

		//确认前置条件符合
		$hasPreBuild = true;
		foreach ($buildInfo['pre_build_id'] as $value) {
			if(!$PlayerBuild->isBuildExist($playerId, $value)){
				$hasPreBuild = false;
			}
		}
		if(!$hasPreBuild){
			$err = 10025;
			goto SendErr;
		}
		//确认建造队列是否空闲
		$lvUpBuild = $PlayerBuild->getAllLvUpBuild($playerId);
		//读取玩家有几个可用的建筑队列
		$PlayerBuffTemp = new PlayerBuffTemp;
		$queueBuff = $PlayerBuffTemp->getPlayerBuff($playerId, "build_queue");
		$PlayerBuff = new PlayerBuff;
		$constructionTimeBuff = $PlayerBuff->getPlayerBuff($playerId, 'build_speed', $position);
		if(!empty($queueBuff) && $queueBuff[0]['expire_time']>=time()+$buildInfo['construction_time']/(1+$constructionTimeBuff)){
			$useableBuildQueue = 2;
		}else{
			$useableBuildQueue = 1;
		}
		if(count($lvUpBuild)>=$useableBuildQueue){
			$err = 10026;
			goto SendErr;
		}

		$needResource = array();
		$costBuff = $PlayerBuff->getPlayerBuff($playerId, "build_cost_reduce");
		foreach ($buildInfo['cost'] as $key=>$value) {
			$needResource[Build::$outputTypeArr[$key]] = $value*(1-$costBuff);
		}
		//确认资源充足
		if(!$Player->hasEnoughResource($playerId, $needResource)){
			$err = 10027;
			goto SendErr;
		}

		/******************建造前检测End****************/

		/******************建造Begin****************/
		//扣除资源
		
		if(empty($err)){
			if(!empty($needResource)){
				$conResource = array();
				foreach ($needResource as $key => $value) {
					$conResource[$key] = $value*(-1);
				}
				$Player->updateResource($playerId, $conResource);
			}
			$PlayerBuild->newBuild($playerId, $buildId, $position);
			$this->data->setBasic(['Player', 'PlayerHelp']);
			$err = 0;
		}
		/***************建造End************/
		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			$this->data->filterBasic(['PlayerBuild'], true);
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 升级
     * 
	 * ```php
	 * /build/lvUp/
     * postData: json={"position":"", "useGem":""}
     * return: json{"PlayerBuild":""}
	 * ```
	 * 
     */
	public function lvUpAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);

		if(!empty($post['useGem'])){
			$useGem = intval($post['useGem']);
		}else{
			$useGem = 0;
		}

		if(!$position){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		/******************建造前检测Begin****************/
		$PlayerBuild = new PlayerBuild;
		$Build = new Build;
		$Player = new Player;
		
		//结束所有已完成建筑
		$PlayerBuild->finishLvUp($playerId);

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		//确认位置上存在正确建筑
		if(empty($playerBuildInfo)){
			$err = 10028;
			goto SendErr;
		}

		//判断建筑状态正确
		if($playerBuildInfo['status']!=1){
			$err = 10029;
			goto SendErr;
		}

		$newBuildLevel = $playerBuildInfo['build_level']+1;
		$newBuildOrgId = $playerBuildInfo['origin_build_id'];

		$newBuildInfo = $Build->getOneByOrgIdAndLevel($newBuildOrgId, $newBuildLevel);		

		//确认建筑存在
		if(empty($newBuildInfo)){
			$err = 10030;
			goto SendErr;
		}
		$newBuildId = $newBuildInfo['id'];

		//确认前置条件符合
		$hasPreBuild = true;
		foreach ($newBuildInfo['pre_build_id'] as $value) {
			if(!$PlayerBuild->isBuildExist($playerId, $value)){
				$hasPreBuild = false;
			}
		}
		if(!$hasPreBuild){
			$err = 10031;
			goto SendErr;
		}

		
		//非直接购买 计算队列和所需资源
		if(!$useGem){
			//确认建造队列是否空闲
			$lvUpBuild = $PlayerBuild->getAllLvUpBuild($playerId);

			$queueIndex = 1;
			foreach ($lvUpBuild as $key => $value) {
				if($value['queue_index']==1){
					$queueIndex = 2;
				}
			}

			$PlayerBuffTemp = new PlayerBuffTemp;
			$PlayerBuff = new PlayerBuff;

			if(count($lvUpBuild)>=2){
				$err = 10032;
				goto SendErr;
			}elseif(count($lvUpBuild)==1 && $queueIndex==2){
				//读取玩家有几个可用的建筑队列
				$queueBuff = $PlayerBuffTemp->getPlayerBuff($playerId, "build_queue");
				$constructionTimeBuff = $PlayerBuff->getPlayerBuff($playerId, 'build_speed', $position);
				if(empty($queueBuff) || $queueBuff[0]['expire_time']<time()+$newBuildInfo['construction_time']/(1+$constructionTimeBuff)){
					$err = 10032;
					goto SendErr;
				}
			}
				

			if($newBuildInfo['cost_item_id']==0 && $newBuildInfo['cost_item_num']==0){
				$needItem = false;
			}else{
				$PlayerItem = new PlayerItem;
				$needItem = true;
				$needItemId = $newBuildInfo['cost_item_id'];
				$needItemNum = $newBuildInfo['cost_item_num'];
				$currentItemNum = $PlayerItem->hasItemCount($playerId, $newBuildInfo['cost_item_id']);
			}
			
			$needResource = array();
			$costBuff = $PlayerBuff->getPlayerBuff($playerId, "build_cost_reduce");
			foreach ($newBuildInfo['cost'] as $key=>$value) {
				$needResource[Build::$outputTypeArr[$key]] = $value*(1-$costBuff);
			}
		}
		//确认资源充足
		if(!$useGem && (!$Player->hasEnoughResource($playerId, $needResource) || ($needItem && $needItemNum>$currentItemNum))){
			$err = 10033;
			goto SendErr;
		}elseif($useGem){
			$point = $newBuildInfo['gem_cost'];
			$re = $Player->updateGem($playerId, (-1)*$point, true, ['cost'=>20501, 'memo'=>'购买建筑，ID='.$newBuildId]);
			if(!$re){
				$err = 10034;
				goto SendErr;
			}
		}

		/******************建造前检测End****************/

		/******************建造Begin****************/
		//扣除资源
		
		if(empty($err)){
			if($useGem){			
				$PlayerBuild->buyBuild($playerId, $position);
				$this->data->setBasic(['Player']);
				$err = 0;
			}else{
				$conResource = array();
				foreach ($needResource as $key => $value) {
					$conResource[$key] = $value*(-1);
				}
				$Player->updateResource($playerId, $conResource);
				if($needItem){
					$PlayerItem->drop($playerId, $needItemId, $needItemNum);
				}
				$PlayerBuild->lvUpBuild($playerId, $newBuildId, $position, $queueIndex);
				$this->data->setBasic(['Player', 'PlayerHelp']);
				$err = 0;
			}
		}
		/***************建造End************/
		//解锁
		SendErr: Cache::unlock($lockKey);		
		if(!$err){
            if($position!=1015){
                $this->data->filterBasic(['PlayerBuild'], true);
            }
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 清理升级
     * 
	 * ```php
	 * /build/reWriteBuildInfo/
     * postData: json={"position":""}
     * return: json{"PlayerBuild":}
	 * ```
	 * 
     */
	public function reWriteBuildInfoAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);

		if(!$position){
			exit;
		}

		$PlayerBuild = new PlayerBuild;
		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);

		if(empty($playerBuildInfo)){
			echo $this->data->send();
		}else{
			$buildId = $playerBuildInfo['build_id'];
			$PlayerBuild->finishLvUp($playerId, $position);
			$PlayerBuild->inventoryResource($playerId, $position);
            if($position!=1015){
                $this->data->filterBasic(['PlayerBuild'], true);
            }
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}
	}
	
    
    /**
     * 取消升级
     * 
	 * ```php
	 * /build/cancel/
     * postData: json={"position":""}
     * return: json{}
	 * ```
	 * 
     */
	public function cancelAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);

		if(!$position){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		/******************建造前检测Begin****************/
		$PlayerBuild = new PlayerBuild;
		$Build = new Build;
		$Player = new Player;
		
		//结束所有已完成建筑
		$PlayerBuild->finishLvUp($playerId);

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		//确认位置上存在正确建筑
		if(empty($playerBuildInfo)){
			$err = 10035;
			goto SendErr;
		}

		//判断建筑状态正确
		if($playerBuildInfo['status']!=2){
			$err = 10036;
			goto SendErr;
		}

		$newBuildLevel = $playerBuildInfo['build_level']+1;
		$newBuildOrgId = $playerBuildInfo['origin_build_id'];

		$newBuildInfo = $Build->getOneByOrgIdAndLevel($newBuildOrgId, $newBuildLevel);

		//确认建筑存在
		if(empty($newBuildInfo)){
			$err = 10037;
			goto SendErr;
		}
		$newBuildId = $newBuildInfo['id'];

		/******************建造前检测End****************/

		/******************建造Begin****************/
		//扣除资源
		
		if(empty($err)){
			$rebackResource = array();
			$PlayerBuff = new PlayerBuff;
			$costBuff = $PlayerBuff->getPlayerBuff($playerId, "build_cost_reduce");
			foreach ($newBuildInfo['cost'] as $key=>$value) {
				$rebackResource[Build::$outputTypeArr[$key]] = round($value*(1-$costBuff)*0.5);
			}
			$Player->updateResource($playerId, $rebackResource);
			$PlayerBuild->cancelLvUp($playerId, $position);
			$this->data->setBasic(['Player']);
			$err = 0;
		}
		/***************建造End************/
		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			$this->data->filterBasic(['PlayerBuild'], true);
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 加速升级
     * 
	 * ```php
	 * /build/accelerate/
     * postData: json={"position":"","type":"1-金币加速 2-免费加速 3-道具加速","itemList":['itemId'=>itemNum,'itemId'=>itemNum,'itemId'=>itemNum]}
     * return: json{}
	 * ```
	 * 
     */
	public function accelerateAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);
		$type = intval($post['type']);
		$itemList = isset($post['itemList'])? $post['itemList'] : array();

		if(!$position){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		/******************建造前检测Begin****************/
		$PlayerBuild = new PlayerBuild;
		$Build = new Build;
		$Player = new Player;
		$PlayerBuff = new PlayerBuff;
		
		//结束所有已完成建筑
		$PlayerBuild->finishLvUp($playerId);

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		//确认位置上存在正确建筑
		if(empty($playerBuildInfo)){
			$err = 10038;
			goto SendErr;
		}

		//判断建筑状态正确
		if($playerBuildInfo['status']!=2){
			$err = 10039;
			goto SendErr;
		}

		$newBuildLevel = $playerBuildInfo['build_level']+1;
		$newBuildOrgId = $playerBuildInfo['origin_build_id'];

		$newBuildInfo = $Build->getOneByOrgIdAndLevel($newBuildOrgId, $newBuildLevel);

		//确认建筑存在
		if(empty($newBuildInfo)){
			$err = 10040;
			goto SendErr;
		}

		//付费道具的使用以及确定减少时间
		switch ($type) {
			case 1:
                $totalSecond = $playerBuildInfo['build_finish_time']-time();
				$point = clacAccNeedGem($totalSecond);
				$re = $Player->updateGem($playerId, (-1)*$point, true, ['cost'=>30501, 'memo'=>'加速建筑升级，ID='.$newBuildInfo['id']]);
				if(!$re){
					$err = 10041;
					goto SendErr;
				}
				break;
			case 2:
				//TODO:VIP快速完成
				$rTime = $PlayerBuff->getPlayerBuff($playerId, "instant_building");
				if(!$rTime){
					$rTime = 300;
				}
				if($playerBuildInfo['build_finish_time']-time()>$rTime){
					$err = 3;//剩余时间太长
					goto SendErr;
				}else{
                    $totalSecond = $rTime;
				}
				break;
			case 3:
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
                        $second = $Item->getAcceSecond($itemId, 1);
                    }
                    if(empty($second)){
                        $err = 10453;//无效道具ID
                        goto SendErr;
                    }else{
                        $totalSecond += $second*$num;
                    }
                }
                foreach($itemList as $itemId=>$num) {
                    $PlayerItem->drop($playerId, $itemId, $num);
                }
				break;
		}
		

		
		
		/******************建造前检测End****************/

		/******************建造Begin****************/
		
		if(empty($err)){
			$PlayerBuild->quickenLvUp($playerId, $position, $totalSecond);
			$this->data->setBasic(['Player']);
			$err = 0;
		}
		/***************建造End************/
		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
            if($position!=1015){
                $this->data->filterBasic(['PlayerBuild'], true);
            }
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 增加资源产量
	 * 
	 * ```php
	 * /build/increaseProduce/
     * postData: json={"position":"", "useGem":""}
     * return: json{}
	 * ```
	 * 
	 * @return [type] [description]
	 */
	function increaseProduceAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);
		$useGem = intval($post['useGem']);

		if(!$position){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		/******************建造前检测Begin****************/
		$PlayerBuild = new PlayerBuild;
		$Build = new Build;
		
		//结束所有已完成建筑
		$PlayerBuild->finishLvUp($playerId);

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);

		//确认位置上存在建筑
		if(empty($playerBuildInfo)){
			$err = 10042;
			goto SendErr;
		}

		//判断建筑状态正确
		if($playerBuildInfo['status']!=1){
			$err = 10043;
			goto SendErr;
		}

		$buildId = $playerBuildInfo['build_id'];

		$buildInfo = $Build->dicGetOne($buildId);
		//确认建筑存在
		if(empty($buildInfo)){
			$err = 10044;
			goto SendErr;
		}

		//判断是否产出建筑
		array_walk($buildInfo['output'], function($v, $k) use(&$buildOutput, &$itemId, &$costId){
			if(in_array($k,[1,2,3,4,5])){
				$buildOutput = $v;
				$itemId = 22402+$k;
				$costId = 40185+$k;
			}
		});
		if(empty($buildOutput)){
			$err = 10045;
			goto SendErr;
		}

		//确认资源充足
		if(!$useGem){
			if(!(new PlayerItem)->drop($playerId, $itemId, 1)){//使用道具
				$err = 10046;
				goto SendErr;
			}
		}elseif($useGem){
			$item = (new Item)->dicGetOne($itemId);
			$point = $item['direct_price'];
			$Player = new Player;
			$re = $Player->updateGem($playerId, (-1)*$point, true, ['cost'=>$costId, 'memo'=>'增加资源产量']);
			if(!$re){
				$err = 10047;
				goto SendErr;
			}
		}
		
		/******************建造前检测End****************/

		/******************建造Begin****************/
	
		if(empty($err)){
			$PlayerBuild->increaseProduce($playerId, $position);
			$this->data->setBasic(['Player']);
			$err = 0;
		}

		/***************建造End************/
		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			$this->data->filterBasic(['PlayerBuild'], true);
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}else{
			echo $this->data->sendErr($err);
		}	
		
	}
	
     /**
     * 设置武将
     * 
	 * ```php
	 * /build/setGeneral/
     * postData: json={"position":"", "generalId":""}
     * return: json{}
	 * ```
	 * 
     */
	public function setGeneralAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = intval($post['position']);
		$generalId = intval($post['generalId']);

		if(!$position){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		/******************建造前检测Begin****************/
		$PlayerBuild = new PlayerBuild;
		$Build = new Build;
		$PlayerGeneral = new PlayerGeneral;
		
		//结束所有已完成建筑
		$PlayerBuild->finishLvUp($playerId);

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		//确认位置上存在正确建筑
		if(empty($playerBuildInfo)){
			$err = 10048;
			goto SendErr;
		}

		//判断建筑状态正确 TODO:建造中的建筑是否可以换武将
		if($playerBuildInfo['status']!=1){
			$err = 10049;
			goto SendErr;
		}

		//判断是否能换武将
		// if($playerBuildInfo['last_change_general_time']+12*3600>time()){
		// 	$err = 1111111111111111111;//换武将间隔时间未到
		// 	goto SendErr;
		// }

		if(!empty($generalId)){
			//确认武将是否存在
			$playerGeneralInfo = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(empty($playerGeneralInfo)){
				$err = 10050;
				goto SendErr;
			}

			if($playerGeneralInfo['build_id']!=0){
				$err = 10051;
				goto SendErr;
			}
		}
		
		/******************建造前检测End****************/

		/******************建造Begin****************/
	
		if(empty($err)){			
			$PlayerBuild->inventoryResource($playerId, $position);
			$PlayerBuild->updateGeneral($playerId, $position, $generalId);
			if(!empty($playerBuildInfo['general_id_1'])){
				$workingPlayerGeneralInfo = $PlayerGeneral->getByGeneralId($playerId, $playerBuildInfo['general_id_1']);
				$PlayerGeneral->assign($workingPlayerGeneralInfo)->updateBuild(0);
			}
			if(!empty($generalId)){
				$PlayerGeneral->assign($playerGeneralInfo)->updateBuild($playerBuildInfo['id']);
			}
			
			$this->data->setBasic(['Player']);
			$err = 0;
		}
		/***************建造End************/
		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!$err){
			$this->data->filterBasic(['PlayerBuild'], true);
			echo $this->data->send($PlayerBuild->getByPosition($playerId, $position));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
     /**
     * 收获资源
     * 
	 * ```php
	 * /build/gainResource/
     * postData: json={"position":""}
     * return: json{}
	 * ```
	 * 
     */
	public function gainResourceAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$positionArr = $post['position'];

		if(!$positionArr){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		
		$PlayerBuild = new PlayerBuild;
		$Build = new Build;
		
		//结束所有已完成建筑
		$PlayerBuild->finishLvUp($playerId);


		$result = [];
		foreach($positionArr as $position){
			/******************检测Begin****************/
			$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
			$buildInfo = $Build->dicGetOne($playerBuildInfo['build_id']);

			//确认位置上存在正确建筑
			if(empty($playerBuildInfo) || $playerBuildInfo['origin_build_id']!=$buildInfo['origin_build_id'] || $playerBuildInfo['build_level']!=$buildInfo['build_level']){
				$err = 10052;
				continue;
			}


			//判断建筑状态正确
			if($playerBuildInfo['status']!=1){
				$err = 10053;
				continue;
			}

			$buildId = $playerBuildInfo['build_id'];

			$buildInfo = $Build->dicGetOne($buildId);
			//确认建筑存在
			if(empty($buildInfo)){
				$err = 10054;
				continue;
			}

			//判断是否产出建筑
			array_walk($buildInfo['output'], function($v, $k) use(&$buildOutput){
				if(in_array($k,[1,2,3,4,5])){
					$buildOutput = $v;
				}
			});
			if(empty($buildOutput)){
				$err = 10055;
				continue;
			}
			
			/******************检测End****************/

			/******************收获Begin****************/
		
			if(empty($err)){
				$getResource = $PlayerBuild->gainResource($playerId, $position);
				$this->data->setBasic(['Player']);
				$err = 0;
			}

			/***************收获End************/

			$tmp['buildInfo'] = $PlayerBuild->getByPosition($playerId, $position);
			$tmp['getResource'] = $getResource;
			$result[] = $tmp;
		}


		
		//解锁
		SendErr: Cache::unlock($lockKey);
		
		if(!empty($result) || !$err){
			$this->data->filterBasic(['PlayerBuild'], true);
			echo $this->data->send(["PlayerBuild"=>$result]);
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 计算最大陷阱容量
	 * 
	 * ```php
	 * /build/calcTrapMaxnum/
     * postData: json={}
     * return: json{}
	 * ```
	 */
	public function calcTrapMaxNumAction(){
		$playerId = $this->getCurrentPlayerId();
		$PlayerBuild = new PlayerBuild;
		$result = $PlayerBuild->calcTrapMaxNum($playerId);
		echo $this->data->send(["trapMaxNum"=>$result]);
	}

	/**
	 * 计算资源产量[废除接口，前端计算]
	 * ```php
	 * /build/getResourceBuildInfo/
     * postData: json={}
     * return: json{}
	 */
	// public function getResourceBuildInfoAction(){
	// 	$playerId = $this->getCurrentPlayerId();
	// 	$PlayerBuild = new PlayerBuild;

	// 	$PlayerBuild->inventoryResource($playerId);
	// 	$ret = $PlayerBuild->getByPlayerId($playerId);

	// 	$data = [];
	// 	foreach($ret as $k=>$v){
	// 		if(in_array($v['origin_build_id'],[16,21,26,31,36])){//判断是资源建筑
	// 			$hour = $PlayerBuild->getBuildOutput($playerId, $v['position']);
	// 			$netHour = $PlayerBuild->getBuildOutput($playerId, $v['position'], false);
	// 			$data[$v['position']] = [
	// 				'origin_build_id' => $v['origin_build_id'],
	// 				'cur' => $v['resource'],
	// 				'hour' => $hour,
	// 				'net_hour' => $netHour,
	// 			];
	// 		}
	// 	}
	// 	echo $this->data->send($data);
	// }
}