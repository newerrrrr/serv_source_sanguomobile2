<?php
/**
 * 陷阱相关业务逻辑
 */
use Phalcon\Mvc\View;
class TrapController extends ControllerBase{

	/**
	 * [initialize description]
	 * @return [type] [description]
	 */
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}

	/**
	 * 生产陷阱开始
	 *
	 * ```php
	 * /trap/produce/
     * postData: json={"trapId":"", "position":"", "num":"", "useGem":""}
     * return: json{"PlayerTrap":""}
	 * ```
	 */
	public function produceAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$trapId = floor($post['trapId']);
		$position = floor($post['position']);
		$num = floor($post['num']);
		$useGem = floor($post['useGem']);

		if(!$trapId || !$num || !$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Trap = new Trap;
		$Player = new Player;
		$PlayerBuild = new PlayerBuild;
		$PlayerTrap = new PlayerTrap;
		$Build = new Build;

		$trapInfo = $Trap->dicGetOne($trapId);
		
		//确认陷阱存在
		if(empty($trapInfo)){
			$err = 10376;//陷阱不存在
			goto SendErr;
		}

		//确认招募条件符合
		$targetBuildId = $PlayerBuild->isBuildExist($playerId, $trapInfo['need_build_id']);
		if(!$targetBuildId){
			$err = 10377;//招募条件不符合
			goto SendErr;
		}

		//确认兵营存在
		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo) || $playerBuildInfo['build_id']!=$targetBuildId){
			$err = 10035;//建筑错误
			goto SendErr;
		}

		$needResource = array();
		foreach ($trapInfo['cost'] as $key=>$value) {
			$needResource[Build::$outputTypeArr[$key]] = $value*$num;
		}

		//确认陷阱容量足够		
		$maxNum = $PlayerBuild->calcTrapMaxNum($playerId);
		$currentNum = $PlayerTrap->getTotalNum($playerId);
		if($maxNum-$currentNum<$num){
			$err = 10378;//陷阱容量已满
			goto SendErr;
		}

		//确认建造时间小于4小时
		$build = $Build->dicGetOne($playerBuildInfo['build_id']);
		$buildSpeedUp = $build['output'][32];
		$PlayerBuff = new PlayerBuff;
		$pitfallTrainSpeedBuff = $PlayerBuff->getPlayerBuff($playerId, 'pitfall_train_speed', $position);
		$t = $trapInfo['train_time']*$num/(1+$buildSpeedUp/10000+$pitfallTrainSpeedBuff);
		if($t>3600*4){
			$err = 10379;//陷阱建造时间过长
			goto SendErr;
		}

		//确认资源充足
		if(!$useGem && !$Player->hasEnoughResource($playerId, $needResource)){
			$err = 10218;
			goto SendErr;
		}elseif($useGem){
			$point = ceil($trapInfo['cost_gem']/10000*$num);
			$re = $Player->updateGem($playerId, (-1)*$point, true, ['cost'=>20301, 'memo'=>'购买陷阱，数量='.$num.'，ID='.$trapId]);
			if(!$re){
				$err = 10219;
				goto SendErr;
			}
		}

		if(empty($err)){
			if($useGem){
				$PlayerTrap->updateTrapNum($playerId, $trapId, $num);
				$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
				$PlayerTimeLimitMatch->updateScore($playerId, 12, $num*$trapInfo['power']);
				$PlayerTarget = new PlayerTarget;
				$PlayerTarget->updateTargetCurrentValue($playerId, 27, $num);
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
				$workArr = ['trapId'=>$trapId, 'num'=>$num];
				
				$workFinishTime = date("Y-m-d H:i:s", time()+$t);
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
	 * 拆除陷阱
	 * ```php
	 * /trap/removeTrap/
     * postData: json={"trapId":"", "num":""}
     * return: json{"PlayerTrap":""}
	 */
	public function removeTrapAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$trapId = intval($post['trapId']);
		$num = intval($post['num']);

		$PlayerTrap = new PlayerTrap;
		$re = $PlayerTrap->getByTrapId($playerId, $trapId);
		$err = 0;

		if(!empty($re) && $re['num']>=$num){
			$success = $PlayerTrap->updateTrapNum($playerId, $trapId, $num*(-1));
			if($success){
				$this->data->setBasic(['Player', 'PlayerBuild']);
				echo $this->data->send($PlayerTrap->getByTrapId($playerId, $trapId));
			}else{
				$err = 10419;//陷阱不够
				echo $this->data->sendErr($err);
			}
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 收获生产完成的陷阱
	 *
	 * ```php
	 * /trap/finishProduce/
     * postData: json={"position":""}
     * return: json{"PlayerTrap":""}
	 * ```
	 */
	public function finishProduceAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = floor($post['position']);

		if(!$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Trap = new Trap;
		$Player = new Player;
		$PlayerBuild= new PlayerBuild;
		$PlayerTrap = new PlayerTrap;

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo)){
			$err = 10035;//建筑不存在
			goto SendErr;
		}

		$workArr = $playerBuildInfo['work_content'];
		$trapId = $workArr['trapId'];
		$num = $workArr['num'];

		$trapInfo = $Trap->dicGetOne($trapId);		

		if(empty($err)){
			$PlayerTrap->updateTrapNum($playerId, $trapId, $num);
			$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
			$PlayerTimeLimitMatch->updateScore($playerId, 12, $num*$trapInfo['power']);
			$PlayerTarget = new PlayerTarget;
			$PlayerTarget->updateTargetCurrentValue($playerId, 27, $num);
			$PlayerBuild->endWork($playerId, $position);
			$this->data->setBasic(['Player', 'PlayerBuild']);
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
	 * 取消生产陷阱
	 *
	 * ```php
	 * /trap/cancelProduce/
     * postData: json={"position":""}
     * return: json{"PlayerTrap":""}
	 * ```
	 */
	public function cancelProduceAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = floor($post['position']);

		if(!$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Trap = new Trap;
		$Player = new Player;
		$PlayerBuild= new PlayerBuild;

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo)){
			$err = 10035;//建筑不存在
			goto SendErr;
		}

		$workArr = $playerBuildInfo['work_content'];
		$trapId = $workArr['trapId'];
		$num = $workArr['num'];

		$trapInfo = $Trap->dicGetOne($trapId);

		$backResource = array();
		foreach ($trapInfo['cost'] as $key=>$value) {
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
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 加速招募陷阱
	 *
	 * ```php
	 * /trap/accelerateProduce/
     * postData: json={"position":"","type":"1-金币加速 2-道具加速","itemList":['itemId'=>itemNum,'itemId'=>itemNum,'itemId'=>itemNum]}
     * return: json{"PlayerTrap":""}
	 * ```
	 */
	public function accelerateProduceAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$position = floor($post['position']);
		$type = intval($post['type']);
		$itemList = isset($post['itemList'])? $post['itemList'] : array();

		if(!$position){
			exit;
		}

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Trap = new Trap;
		$Player = new Player;
		$PlayerBuild= new PlayerBuild;

		$playerBuildInfo = $PlayerBuild->getByPosition($playerId, $position);
		if(empty($playerBuildInfo)){
			$err = 10035;//建筑不存在
			goto SendErr;
		}

		$workArr = $playerBuildInfo['work_content'];
		$trapId = $workArr['trapId'];
		$num = $workArr['num'];

		$trapInfo = $Trap->dicGetOne($trapId);

		//付费道具的使用以及确定减少时间
		switch ($type) {
			case 1:
				$second = $playerBuildInfo['work_finish_time']-time();
				$point = clacAccNeedGem($second);
				$re = $Player->updateGem($playerId, (-1)*$point, true, ['cost'=>30301, 'memo'=>'加速陷阱建造，数量='.$num.'，ID='.$trapId]);
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
				        $second = $Item->getAcceSecond($itemId, 5);
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
}