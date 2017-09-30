<?php
use Phalcon\Mvc\View;
class ItemController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 背包使用
     * 
     * itemId: 道具id
	 * itemNum: 道具数量
     * @return <type>
     */
	public function useAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		$itemNum = floor(@$post['itemNum']);
		if(!checkRegularNumber($itemId) || !checkRegularNumber($itemNum))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$dropItems = $this->useItem($player, $itemId, $itemNum);
			
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
			echo $this->data->send(array('dropItems'=>$dropItems));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	public function useItem($player, $itemId, $itemNum){
		$playerId = $player['id'];
		//检查道具是否可以使用
		$Item = new Item;
		$item = $Item->dicGetOne($itemId);
		if(!$item['button_type']){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//检查道具使用等级
		$castleLevel = (new PlayerBuild)->getPlayerCastleLevel($playerId);
		if($castleLevel < $item['use_level']){
			throw new Exception(10123);
		}
		
		//特殊道具检查
		switch($item['id']){
			case 21802://免战
			case 21803:
			case 21804:
				//检查有没有出征攻打/援助他人
				$ppqs = (new PlayerProjectQueue)->find(['player_id = '.$playerId.' and type in ('.join(',', array_merge(PlayerProjectQueue::$attackType, [PlayerProjectQueue::TYPE_COLLECT_GOTO, PlayerProjectQueue::TYPE_COLLECT_ING, PlayerProjectQueue::TYPE_CITYASSIST_GOTO, PlayerProjectQueue::TYPE_CITYASSIST_ING])).') and status = 1'])->toArray();
				if($ppqs){
					$flag = false;
					$MapElement = new MapElement;
					foreach($ppqs as $_ppq){
						if(in_array($_ppq['type'], [
							PlayerProjectQueue::TYPE_GATHER_WAIT,
							PlayerProjectQueue::TYPE_GATHER_GOTO,
							PlayerProjectQueue::TYPE_GATHER_STAY,
							PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO,
						])){//排除集结boss的类型
							$targetInfo = json_decode($_ppq['target_info'], true);
							$me = $MapElement->dicGetOne($targetInfo['element_id']);
							if(@$me && $me['origin_id'] == 17){
								
							}else{
								$flag = true;
								break;
							}
						}elseif(in_array($_ppq['type'], [
							PlayerProjectQueue::TYPE_COLLECT_GOTO,
							PlayerProjectQueue::TYPE_COLLECT_ING,
						])){//检查据点战
							$map = (new Map)->getByXy($_ppq['to_x'], $_ppq['to_y']);
							if($map['map_element_origin_id'] == 22){
								throw new Exception(10420);//据点战中无法开启战争保护
							}
						}elseif(in_array($_ppq['type'], [
							PlayerProjectQueue::TYPE_CITYASSIST_GOTO,
							PlayerProjectQueue::TYPE_CITYASSIST_ING,
						])){//检查据点战
							$flag = true;
							throw new Exception(10450);//您正在援助盟友，无法使用战争守护
							break;
						}else{
							$flag = true;
							break;
						}
					}
					if($flag)
						throw new Exception(10316);//您正在侦查或攻击敌人，无法使用战争守护
				}
				
				//检查有没有和氏璧
				if($player['hsb']){
					throw new Exception(10353);//您拥有和氏璧，无法开启战争保护
				}
				
				//检查上次攻击时间
				if(time() < $player['attack_time'] + (new Starting)->dicGetOne('unable_protection') * 3600){
					throw new Exception(10558);//发起攻击后2小时内不可开启保护
				}
			
			break;
			case 22500:
			case 22501://体力药水
				$movePlusBuff = (new PlayerBuff)->getPlayerBuff($playerId, 'move_limit_plus_exact_value');//行动力上限buff
				$move = (new Player)->restorePlayerMove($playerId);
				if($move >= ($player['move_max']+$movePlusBuff)){
					throw new Exception(10354);//体力已满
				}
			break;
		}
		
		//检查并扣除道具
		$PlayerItem = new PlayerItem;
		if(!$PlayerItem->drop($playerId, $itemId, $itemNum)){
			throw new Exception(10124);
		}
		
		//获取drop
		$Drop = new Drop;
		$i = 0;
		$dropItems = array();		
		foreach($item['drop'] as $_d){
			$_dropItems = $Drop->gain($playerId, $_d, $itemNum, '使用道具:'.$item['desc1']);
			if(!$_dropItems){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$dropItems = array_merge($dropItems, $_dropItems);
		}

		//特殊道具使用
		switch($item['id']){
			case 21802://免战
			case 21803:
			case 21804:
				$ar = [21802=>8, 21803=>24, 21804=>72];
				if(!(new Player)->setAvoidBattleTime($playerId, $ar[$item['id']]*3600)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			break;
			case 22401://采集加成
			case 22402:
				//重算采集速度
				/*$PlayerProjectQueue = new PlayerProjectQueue;
				if(!$PlayerProjectQueue->refreshCollection($playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}*/
				/*$ppqs = PlayerProjectQueue::find(['player_id='.$playerId.' and status=1 and type in ('.join(',', [PlayerProjectQueue::TYPE_COLLECT_ING, PlayerProjectQueue::TYPE_GUILDCOLLECT_ING]).')'])->toArray();
				$ppq = $PlayerProjectQueue->afterFindQueue($ppqs);
				
				//重算采集速度
				$PlayerBuff = new PlayerBuff;
				$Map = new Map;
				$MapElement = new MapElement;
				foreach($ppq as $_i => $_ppq){
					//地图信息
					$map = $Map->getByXy($_ppq['to_x'], $_ppq['to_y']);
					if(!$map){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					if($map['map_element_origin_id'] == 22) continue;//屏蔽据点
					$targetInfo = $_ppq['target_info'];
					
					//剩余负重
					$_weight = $_ppq['target_info']['weight'];
					
					//获取采集加成buff
					//$collectionBuff = $PlayerBuff->getPlayerBuff($_ppq['player_id'], $carryType[$map['map_element_origin_id']].'_gathering_speed');
					$collectionBuff = $PlayerBuff->getCollectionBuff($_ppq['player_id'], $_ppq['army_id'], $carryType[$map['map_element_origin_id']]);
					if($Map->isInGuildArea($_ppq['player_id'])){
						$collectionBuff += 0.15;
					}
					
					//计算采集速度
					$me = $MapElement->dicGetOne($map['map_element_id']);
					if(!$me){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					$resPerMin = $me['collection'] * (1+$collectionBuff);
					$targetInfo['speed'] = $resPerMin;
					
					//计算采集时间
					$second = ceil($_ppq['target_info']['carry'] / ($resPerMin / 60));
					
					//更新queue
					if(!$PlayerProjectQueue->assign($ppqs[$_i])->updateQueue($second, $targetInfo)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}*/
				
			break;
		}
		return $dropItems;
	}
	
	/**
     * 道具合成
     * 
	 * $_POST['itemId'] 目标合成道具id
     * @return <type>
     */
	public function combineAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		$itemNum = floor(@$post['itemNum']);
		if(!checkRegularNumber($itemId) || !checkRegularNumber($itemNum))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$itemOut = array();
			
			//获取配置
			$Item = new Item;
			$item = $Item->dicGetOne($itemId);
			if(!$item){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($item['item_type'] != 2){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取合成配方
			$ItemCombine = new ItemCombine;
			$itemCombine = $ItemCombine->dicGetOne($itemId);
			if(!$itemCombine){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//获取需要道具
			$PlayerItem = new PlayerItem;
			foreach($itemCombine['consume'] as $_c){
				if($_c[0] == '1'){
					//消耗道具
					if(!$PlayerItem->drop($playerId, $_c[1], $_c[2]*$itemNum)){
						throw new Exception(10125);
					}
				}
			}
			
			//增加材料
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->add($playerId, $itemId, $itemCombine['count']*$itemNum)){
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
     * 设置背包道具已读（打开背包时调用）
     * 
     */
	public function setNewAction(){
		$playerId = $this->getCurrentPlayerId();
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerItem = new PlayerItem;
			$PlayerItem->setNew($playerId, 0);
			
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