<?php
//磨坊
use Phalcon\Mvc\View;
class MillController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 解锁栏位
     * 
     * @return <type>
     */
	public function buyPositionAction(){
		$freeNum = 1;
		$costId = 308;
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$num = floor(@$post['num']);
		if(!checkRegularNumber($num)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取玩家磨坊数据
			$PlayerMill = new PlayerMill;
			$pm = $PlayerMill->getByPlayerId($playerId);
			
			//获取购买栏位配置
			$cost = Cost::find(['cost_id="'.$costId.'"'])->toArray();
			$maxPos = 0;
			foreach($cost as $_c){
				$maxPos = max($maxPos, $_c['max_count']);
			}
			$maxPos += $freeNum;
			
			//检查已购买栏位是否达到上限
			if($pm['num']+$num > $maxPos){
				throw new Exception(10403);//队列已经达到上限
			}
			
			$i = 1;
			while($i <= $num){
				//计算下一个栏位号
				$nextNum = $pm['num']+$i;
				
				//cost
				if(!(new Cost)->updatePlayer($playerId, $costId, $nextNum-$freeNum)){
					throw new Exception('10034');//元宝不足
				}
				$i++;
			}
			
			//更新栏位
			if(!$PlayerMill->increaseNum($playerId, $nextNum, $pm['rowversion'])){
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
     * 增加材料
     * 
     * 
     * @return <type>
     */
	public function addItemAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		if(!checkRegularNumber($itemId)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取道具配置
			$Mill = new Mill;
			$mill = $Mill->dicGetOne($itemId);
			if(!$mill){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查府衙等级
			$castleLv = (new PlayerBuild)->getPlayerCastleLevel($playerId);
			if($castleLv < $mill['level_min'] || $castleLv > $mill['level_max']){
				throw new Exception(10404);//府衙等级不足
			}
			
			//获取玩家磨坊数据
			$PlayerMill = new PlayerMill;
			$pm = $PlayerMill->getByPlayerId($playerId);
			
			//获取可放置栏位id
			$currentNum = count($pm['item_ids']);
			$itemIds = $pm['item_ids'];
			
			//检查可放置栏位是否达到上限
			$processNum = 0;
			if($currentNum >= $pm['num']){
				//检查已经完成的道具，向前推队列
				$beginTime = $pm['begin_time'];
				$now = time();
				foreach($itemIds as $_k => &$_it){
					if($beginTime + $_it[1] <= $now){//生产完
						$processNum = $_k;
						//$_status = 1;
					}elseif($beginTime <= $now && ($beginTime + $_it[1]) >= $now){//正在生产
						$processNum = $_k;
						//$_status = 2;
					}else{//将要生产
						if(isset($itemIds[$_k+1])){
							$_it = $itemIds[$_k+1];
							$processNum = $_k;
						}else{
							unset($itemIds[$_k]);
						}
						//$_status = 0;
					}
					$beginTime += $_it[1];
					/*$_it = [
						'item_id'=>$_it[0]*1,
						'second'=>$_it[1]*1,
						'status'=>$_status,
					];*/
				}
				unset($_it);
				$currentNum--;
				if($processNum == $currentNum){
					throw new Exception(10405);//队列不足，请先领取道具
				}
			}
			
			//获取生产时间
			$itemIds[$currentNum] = [$itemId,$mill['time']];
			
			if(!$pm['begin_time']){
				$beginTime = date('Y-m-d H:i:s');
			}else{
				$beginTime = date('Y-m-d H:i:s', $pm['begin_time']);
			}
			
			//更新磨坊
			if(!$PlayerMill->updateItem($playerId, $itemIds, $beginTime, $pm['rowversion'])){
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
     * 删除道具
     * 
     * num: 第几个道具,1开始
     * @return <type>
     */
	public function delItemAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$num = floor(@$post['num']);
		if(!checkRegularNumber($num, true)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取玩家磨坊数据
			$PlayerMill = new PlayerMill;
			$pm = $PlayerMill->getByPlayerId($playerId);
			
			//获取可放置栏位id
			$itemIds = $pm['item_ids'];
			
			if($num > count($itemIds)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查可放置栏位是否达到上限
			$processNum = 0;
			//检查已经完成的道具，向前推队列
			$beginTime = $pm['begin_time'];
			$now = time();
			foreach($itemIds as $_k => &$_it){
				if($beginTime + $_it[1] <= $now){//生产完
					$processNum = $_k;
					//$_status = 1;
				}elseif($beginTime <= $now && ($beginTime + $_it[1]) >= $now){//正在生产
					$processNum = $_k;
					//$_status = 2;
				}else{
					
					//$_status = 0;
				}
				$beginTime += $_it[1];
				/*$_it = [
					'item_id'=>$_it[0]*1,
					'second'=>$_it[1]*1,
					'status'=>$_status,
				];*/
			}
			unset($_it);
			$processNum++;
			if($num <= $processNum){
				throw new Exception(10406);//该道具正在生产
			}
			array_splice($itemIds, $num-1, 1);
						
			//更新磨坊
			if(!$pm['begin_time']){
				$beginTime = date('Y-m-d H:i:s');
			}else{
				$beginTime = date('Y-m-d H:i:s', $pm['begin_time']);
			}
			if(!$PlayerMill->updateItem($playerId, $itemIds, $beginTime, $pm['rowversion'])){
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
     * 加速
     * 
     * 
     * @return <type>
     */
	public function acceItemAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		if(!checkRegularNumber($itemId)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			
			//获取玩家磨坊数据
			$PlayerMill = new PlayerMill;
			$pm = $PlayerMill->getByPlayerId($playerId);
			
			$itemIds = $pm['item_ids'];
			
			//查找正在生产的道具
			$processItem = false;
			//检查已经完成的道具，向前推队列
			$beginTime = $pm['begin_time'];
			$now = time();
			foreach($itemIds as $_k => &$_it){
				if($beginTime + $_it[1] <= $now){//已经生产完
					//$_status = 1;
				}elseif($beginTime <= $now && ($beginTime + $_it[1]) >= $now){//正在生产
					$processItem = $_it;
					break;
					//$_status = 2;
				}else{
					//$_status = 0;
				}
				$beginTime += $_it[1];
				/*$_it = [
					'item_id'=>$_it[0]*1,
					'second'=>$_it[1]*1,
					'status'=>$_status,
				];*/
			}
			unset($_it);
			if(!$processItem || $processItem[0] != $itemId){
				throw new Exception(10407);//没有可以加速的道具
			}
			
			//计算加速时间
			$second = $beginTime + $processItem[1] - $now;
			$gem = clacAccNeedGem($second);
			
			//消费元宝
			if(!(new Player)->updateGem($playerId, -$gem, true, ['cost'=>10016])){
				throw new Exception(10034);
			}
			
			//获得道具
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->add($playerId, $itemId, 1)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			
			//更新磨坊
			array_splice($itemIds, $_k, 1);
			
			if(!$itemIds){
				$beginTime = '0000-00-00 00:00:00';
			}else{
				$beginTime = date('Y-m-d H:i:s');
			}
			if(!$PlayerMill->updateItem($playerId, $itemIds, $beginTime, $pm['rowversion'])){
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
     * 收取材料
     * 
     * 
     * @return <type>
     */
	public function gainAction(){
		$playerId = $this->getCurrentPlayerId();
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取玩家磨坊数据
			$PlayerMill = new PlayerMill;
			$pm = $PlayerMill->getByPlayerId($playerId);
			
			$itemIds = [];
			$gainIds = [];
			$_beginTime = $beginTime = $pm['begin_time'];
			$now = time();
			foreach($pm['item_ids'] as $_k => $_it){
				if($beginTime + $_it[1] <= $now){//已经生产完
					$gainIds[] = $_it[0]*1;
					$_beginTime += $_it[1];
				}else{//正在生产或将要生产
					$itemIds[] = $_it;
				}
				$beginTime += $_it[1];
			}
			if(!$itemIds){
				$_beginTime = '0000-00-00 00:00:00';
			}else{
				$_beginTime = date('Y-m-d H:i:s', $_beginTime);
			}

			//更新磨坊
			if(!$PlayerMill->updateItem($playerId, $itemIds, $_beginTime, $pm['rowversion'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerItem = new PlayerItem;
			foreach($gainIds as $_itemId){
				if(!$PlayerItem->add($playerId, $_itemId, 1)){
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
		
		if(!$err){
			echo $this->data->send(['items'=>$gainIds]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
}