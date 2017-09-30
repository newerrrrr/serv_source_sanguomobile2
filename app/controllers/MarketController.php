<?php
//集市
use Phalcon\Mvc\View;
class MarketController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 刷新
     * 
     * @return <type>
     */
	public function reloadAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//判断开启等级
			$PlayerBuild = new PlayerBuild;
			$playerBuild = $PlayerBuild->getByOrgId($playerId, 1);
			if($playerBuild[0]['build_level'] < (new Starting)->dicGetOne('system_opentask_lv_market')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取玩家集市
			$PlayerMarket = new PlayerMarket;
			$pm = $PlayerMarket->getByPlayerId($playerId);
			if(!$pm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断cost
			$counter = $pm['counter']+1;
			$freeTimes = (new Starting)->dicGetOne('market_refresh_free');
			if($counter > $freeTimes){
				$_counter = $counter - $freeTimes;
				$Cost = new Cost;
				if(!$Cost->updatePlayer($playerId, 4040, $_counter)){
					throw new Exception(10107);
				}
			}
			
			//刷新
			$ids = (new Market)->rand(PlayerMarket::NUM, $pm['special_id'], $playerBuild[0]['build_level']);
			if(!$PlayerMarket->up($playerId, $pm['last_date'], $counter, $ids, $pm['special_id'], $pm['rowversion'])){
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
     * 购买
     * 
     * 
     * @return <type>
     */
	public function buyAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$id = floor(@$post['id']);
		if(!checkRegularNumber($id))
			exit;
		if(!in_array($id, range(1, PlayerMarket::NUM)))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//判断开启等级
			$PlayerBuild = new PlayerBuild;
			$playerBuild = $PlayerBuild->getByOrgId($playerId, 1);
			if($playerBuild[0]['build_level'] < (new Starting)->dicGetOne('system_opentask_lv_market')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取玩家集市
			$PlayerMarket = new PlayerMarket;
			$pm = $PlayerMarket->getByPlayerId($playerId);
			if(!$pm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			
			//获取配置
			$marketId = $pm['market_ids'][$id];
			$Market = new Market;
			$market = $Market->dicGetOne($marketId);
			if(!$market)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//cost
			if(!(new Cost)->updatePlayer($playerId, $market['cost_id'])){
				throw new Exception(10333);//资源不足
			}
			
			//drop
			if(!(new Drop)->gain($playerId, [$market['commodity_data']], 1, '市场购买')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//计算替代物品
			$except = $pm['market_ids'];
			unset($except[$id]);
			$newMarketId = $Market->rand(1, $pm['special_id'], $playerBuild[0]['build_level'], $except);
			if(!$newMarketId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$pm['market_ids'][$id] = $newMarketId[0];
			
			//更新集市
			if(!$PlayerMarket->up($playerId, $pm['last_date'], $pm['counter'], $pm['market_ids'], $pm['special_id'], $pm['rowversion'])){
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
}