<?php
use Phalcon\Mvc\View;
/**
 * 建筑相关业务逻辑
 */
class MapController extends ControllerBase{
	CONST EXTRASEC = 10;
	/**
	 * [initialize description]
	 * @return [type] [description]
	 */
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}

    /**
     * 收藏坐标
     * 
     * 
     * @return <type>
     */
	public function addCoordinateAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$type = floor(@$post['type']);
		$name = @$post['name'];
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($type, true)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236 || !$name)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//过滤名字
			$name = mb_substr($name, 0, 12);
			
			//删除坐标
			$PlayerCoordinate = new PlayerCoordinate;
			$PlayerCoordinate->drop($playerId, $x, $y);
				
			//新增坐标
			if(!$PlayerCoordinate->add($playerId, $x, $y, $type, $name)){
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
	
	public function dropCoordinateAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//删除坐标
			$PlayerCoordinate = new PlayerCoordinate;
			if(!$PlayerCoordinate->drop($playerId, $x, $y)){
				throw new Exception(10220);
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
     * showBlock和showQueue合成版
     * 
     * 
     * @return <type>
     */
	public function showBlockNQueueAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$blockList = @$post['blockList'];
		$queueList = @$post['queueList'];
		$result1 = $this->_showBlock($playerId, $blockList, $err1);
		$result2 = $this->_showQueue($player, $queueList, $err2);

		if(!$err1 && !$err2){
			echo $this->data->send(['block'=>$result1, 'queue'=>$result2]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	/**
     * 取块数据
     * 
	 * ```php
	 * /map/showBlock/
     * postData: json={"blockList":[]}
     * return: json{"Map":"", "Player":"", "Guild":""}
	 * ```
	 * 
     */
	 
	public function showBlockAction(){
		// debug('------------------B');
		//debug("ST-".time());

		$playerId = $this->getCurrentPlayerId();
		// debug('------player_id='.$playerId);
		$post = getPost();
		$blockList = $post['blockList'];
		$result = $this->_showBlock($playerId, $blockList, $err);
		
		if(!$err){
			echo $this->data->send($result);
			// debug('------------------E');
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	public function _showBlock($playerId, $blockList, &$err=0){

		$Map = new Map;
		$Player = new Player;
		$Guild = new Guild;
        $KingTown = new KingTown;
		
		$result = ['Map'=>[], 'Player'=>[], 'Guild'=>[]];
		$err = 0;
		foreach ($blockList as $blockId) {
			if($blockId>=0 && $blockId<103*103){
				$tmpList = $Map->getAllByBlockId($blockId);
				foreach ($tmpList as $key => $value) {
                    if($value['map_element_origin_id']!=20){
                        $result['Map'][$value['id']] = $value;
                        if(!empty($value['player_id']) && empty($result['Player'][$value['player_id']])){
                        	$whiteList = ["id", "user_code","server_id","nick","avatar_id","level","current_exp","next_exp","wall_durability","wall_durability_max","wall_intact","durability_last_update_time","last_repair_time","fire_end_time","food_out","move_in_time","food_out_time","login_time","study_pay_num","guild_coin","power","step","step_set","job","appointment_time","monster_lv","monster_kill_counter","avoid_battle","avoid_battle_time","fresh_avoid_battle_time","kill_soldier_num","vip_level","vip_exp","sign_date","sign_times","prev_x","prev_y","hsb","device_type","client_id","device_token","badge","push_tag","lang","map_id","x","y","is_in_map","last_online_time","is_in_map","last_online_time","is_in_cross","rank_title"];
                        	$tmpPlayerInfo = $Player->getByPlayerId($value['player_id']);
                            $result['Player'][$value['player_id']] = keepFields($tmpPlayerInfo, $whiteList, true);
                        }
                        if(!empty($value['guild_id']) && empty($result['Guild'][$value['guild_id']])){
                            $result['Guild'][$value['guild_id']] = $Guild->getGuildInfo($value['guild_id']);
                        }
                    }
                    if(in_array($value['map_element_origin_id'], [18,19]) && $value['player_id']==0) {//国王战营寨 NPC 信息
                        $kingTownInfo = $KingTown->getByXy($value['x'], $value['y']);
                        $result['Map'][$value['id']]['KingTown'] = [
                            'npc_id'  => intval($kingTownInfo['npc_id']),
                            'npc_num' => intval($kingTownInfo['npc_num'])
                        ];
                    }
                }
			}else{
				$err = 10416;//不存在的区块
				break;
			}
		}
		//debug("ET-".time());

		return $result;
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
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$blockList = @$post['blockList'];
		$result = $this->_showQueue($player, $blockList, $err);
		
		if(!$err){
			echo $this->data->send($result);
		}else{
			echo $this->data->sendErr($err);
		}

	}
	
	public function _showQueue($player, $blockList, &$err=0){
		$playerId = $player['id'];
		if(!is_array($blockList))
			exit;
		foreach($blockList as $_b){
			if(!checkRegularNumber($_b, true))
				exit;
		}
		
		try {
			$PlayerProjectQueue = new PlayerProjectQueue;
			$sortGatherReturn = [];
			//转化xy
			//$xys = array();
			$ret2 = array();
			foreach($blockList as $_b){
				$_xy = Map::calcXyByBlock($_b);
				$_xy = array(
					'from_x'=>max(0, $_xy['from_x']-12),
					'to_x'=>min(1236, $_xy['to_x']+12),
					'from_y'=>max(0, $_xy['from_y']-12),
					'to_y'=>min(1236, $_xy['to_y']+12),
				);
				$ret = $PlayerProjectQueue->find(['status=1 and (player_id='.$playerId.' or ((from_x>='.$_xy['from_x'].' or to_x>='.$_xy['from_x'].') and (from_x<='.$_xy['to_x'].' or to_x<='.$_xy['to_x'].') and (from_y>='.$_xy['from_y'].' or to_y>='.$_xy['from_y'].') and (from_y<='.$_xy['to_y'].' or to_y<='.$_xy['to_y'].')))'])->toArray();
				
				//过滤2
				$p3 = array('x'=>floor(($_xy['from_x'] + $_xy['to_x'])/2), 'y'=>floor(($_xy['from_y'] + $_xy['to_y'])/2));
				$r = sqrt(pow(floor(abs($_xy['from_x'] - $_xy['to_x'])/2), 2) + pow(floor(abs($_xy['from_y'] - $_xy['to_y'])/2), 2));
				foreach($ret as $_r){
					if($_r['player_id'] == $playerId && !in_array($_r['type'], $PlayerProjectQueue->npcTypes)){
						$ret2[$_r['id']] = $_r;
						//如果是集结子队伍，查找父队伍
						if($_r['type'] == PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO){
							$ret3 = $PlayerProjectQueue->findFirst(['id='.$_r['parent_queue_id'].' and status=1']);
							if($ret3){
								$ret3 = $ret3->toArray();
								$ret2[$ret3['id']] = $ret3;
							}
						}
					}elseif(in_array($_r['type'], [PlayerProjectQueue::TYPE_GATHERD_MIDRETURN, PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO])){
						
					}else{
						$dis = $this->GetNearestDistance(array('x'=>$_r['from_x'], 'y'=>$_r['from_y']), array('x'=>$_r['to_x'], 'y'=>$_r['to_y']), $p3);
						if($dis <= $r){
							$ret2[$_r['id']] = $_r;
						}
					}
					
					//整理集结大召回返回
					if($_r['type'] == PlayerProjectQueue::TYPE_GATHER_RETURN){
						$sortGatherReturn[$_r['from_map_id'].'_'.$_r['to_map_id'].'_'.$_r['end_time']]['main'] = [$_r['id'], $_r['player_id']];
					}elseif($_r['type'] == PlayerProjectQueue::TYPE_GATHERD_MIDRETURN){
						$sortGatherReturn[$_r['from_map_id'].'_'.$_r['to_map_id'].'_'.$_r['end_time']]['sub'] = [$_r['id'], $_r['player_id']];
					}
				}
			}
			
			foreach($sortGatherReturn as $_r){
				if(@$_r['main'] && @$_r['sub']){
					if($_r['sub'][1] == $playerId){
						unset($ret2[$_r['main'][0]]);
					}else{
						unset($ret2[$_r['sub'][0]]);
					}
				}
			}
			
			$queue = array();
			$playerIds = array();
			$mapXys = array();
			$PlayerArmyUnit = new PlayerArmyUnit;
			foreach($ret2 as $_r){
				if(!in_array($_r['type'], $PlayerProjectQueue->npcTypes)){
					//获取部队展现形式
					$_at = Cache::db()->get('queueSoldierType:'.$_r['id']);
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
						/*$_r['army_type'] = [0, 0, 0, 0];
						if(in_array($_r['type'], array_merge($PlayerProjectQueue->gatherTypes, [
								PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO,
							]))
						){//集结
							$_r['army_type'] = [1, 1, 1, 1];
						}elseif($_r['army_id'] && $_r['from_map_id'] != $_r['to_map_id'] && !in_array($_r['type'], [
								PlayerProjectQueue::TYPE_COLLECT_GOTO,
								PlayerProjectQueue::TYPE_COLLECT_RETURN,
							])
						){
							if($_pau = $PlayerArmyUnit->getByArmyId($_r['player_id'], $_r['army_id'])){
								foreach($_pau as $__pau){
									if(!$__pau['soldier_id']) continue;
									$_r['army_type'][substr($__pau['soldier_id'], 0, 1)-1] = 1;
								}
							}
							if(!$_r['army_type'][0] && !$_r['army_type'][1] && !$_r['army_type'][2] && !$_r['army_type'][3]){
								$_r['army_type'] = [1, 1, 1, 1];
							}
						}*/
						Cache::db()->set('queueSoldierType:'.$_r['id'], $_r['army_type']);
					}
					$playerIds[] = $_r['player_id'];
				}else{//npc队列
					//continue;
				}
				
				
				$queue[$_r['id']] = $_r;
				$mapXys[$_r['to_map_id']] = ['x'=>$_r['to_x'], 'y'=>$_r['to_y']];
				$mapXys[$_r['from_map_id']] = ['x'=>$_r['from_x'], 'y'=>$_r['from_y']];
			}
			$queue = filterFields($queue, true, ['carry_gold', 'carry_food', 'carry_wood', 'carry_stone', 'carry_iron', 'carry_soldier']);
			$queue = $PlayerProjectQueue->afterFindQueue($queue);
			
			//整理顺序，与我有关，有我盟友有关必发，截取100条
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
			
			$guildIds = array();
			//获取相关玩家信息
			$Player = new Player;
			$players = array();
			$playerIds = array_unique($playerIds);
			foreach($playerIds as $_playerId){
				$_player = $Player->getByPlayerId($_playerId);
				if(!$_player)
					continue;
				$players[$_playerId] = $_player;
				if($_player['guild_id']){
					$guildIds[] = $_player['guild_id'];
				}
			}
			$players = filterFields($players, true, ['uuid','levelup_time','talent_num_total','talent_num_remain','general_num_total','general_num_remain','army_num','army_general_num','queue_num','move','move_max','gold','food','wood','stone','iron','silver','point','rmb_gem','gift_gem','valid_code']);
			
			//获取相关联盟信息
			$Guild = new Guild;
			$guildIds = array_unique($guildIds);
			$guilds = array();
			foreach($guildIds as $_guildId){
				$_guild = $Guild->getGuildInfo($_guildId);
				if(!$_guild)
					continue;
				$guilds[$_guildId] = $_guild;
			}
			
			//获取map相关信息
			$Map = new Map;
			$mapElement = array();
			foreach($mapXys as $_mapId => $_mapXy){
				$_map = $Map->getByXy($_mapXy['x'], $_mapXy['y']);
				if(!$_map)
					continue;
				$mapElement[$_mapId] = [
					'map_element_id'=>$_map['map_element_id'],
					'player_id'=>$_map['player_id'],
					'guild_id'=>$_map['guild_id'],
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
     * 队列战斗结果
     * 
     * 
     * @return <type> 0：无数据，1：队列还未处理，2：无战斗，3：战斗胜利，4：战斗失败
     */
	public function queueBattleRetAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId))
			exit;
		
		try {
			$PlayerProjectQueue = new PlayerProjectQueue;
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
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;
		if(!in_array($type, array(1, 2, 3, 4, 5, 6)))
			exit;

		try {
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map)
				throw new Exception(10357);//目标不存在
						
			//获取军团
			$PlayerArmy = new PlayerArmy;
			$armies = $PlayerArmy->getByPlayerId($playerId);
			/*$playerArmy = $PlayerArmy->getByArmyId($playerId, $armyId);
			if(!$playerArmy)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						
			//检查army士兵数
			$Soldier = new Soldier;
			$PlayerArmyUnit = new PlayerArmyUnit;
			$pau = $PlayerArmyUnit->getByArmyId($playerId, $armyId);
			$findFlag = false;
			$generalIds = [];
			foreach($pau as $_pau){
				if(!$_pau['soldier_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($_pau['soldier_num'] > 0){
					$findFlag = true;
				}
				$generalIds[] = $_pau['general_id'];
			}
			if(!$findFlag){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
			
			//计算行军时间
			$needTime = [];
			$collection = [];
			$collectionType = 0;
			$PlayerArmyUnit = new PlayerArmyUnit;
			foreach($armies as $_army){
				$_needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, $type, $_army['id']);
				$needTime[$_army['id']] = $_needTime;
				
				//如果是采矿，计算最大采集数量
				if(in_array($map['map_element_origin_id'], [3, 4, 5, 6, 7, 9, 10, 11, 12, 13])){
					$weight = $PlayerArmyUnit->calculateWeight($playerId, $_army['id']);
					if($weight){
						switch($map['map_element_origin_id']){
							case 'wood':
							case 11:
							case 5:
								$_weight = WEIGHT_WOOD;
								$collectionType = 3;
							break;
							case 'food':
							case 10:
							case 4:
								$_weight = WEIGHT_FOOD;
								$collectionType = 2;
							break;
							case 'gold':
							case 9://矿类型
							case 3:
								$_weight = WEIGHT_GOLD;
								$collectionType = 1;
							break;
							case 'iron':
							case 13:
							case 7:
								$_weight = WEIGHT_IRON;
								$collectionType = 5;
							break;
							case 'stone':
							case 12:
							case 6:
								$_weight = WEIGHT_STONE;
								$collectionType = 4;
							break;
						}
						$num = floor($weight / $_weight);
					}else{
						$num = 0;
					}
					$collection[$_army['id']] = min($map['resource'], $num);
					
					if(in_array($map['map_element_origin_id'], [3, 4, 5, 6, 7]) && !$map['status']){//联盟矿是否造完
						$collectionType = 0;
					}
				}
			}
						
			//如果直接使用体力
			$distance = sqrt(pow($player['x'] - $x, 2) + pow($player['y'] - $y, 2));

			$needMove = distance2move($distance);
			
			//pvp体力增加
			if($type == 3 && $map['map_element_origin_id'] == 15 && (!$player['guild_id'] || !$map['guild_id'] || $player['guild_id'] != $map['guild_id'])){
				$needMove *= (new Starting)->dicGetOne('energy_cost_rate_castle');
			}
			
			//免费体力
			$PlayerInfo = new PlayerInfo;
			$playerInfo = $PlayerInfo->getByPlayerId($playerId);
			if(!$playerInfo['first_out']){
				$freeMove = 1;
			}else{
				$freeMove = 0;
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('time'=>$needTime, 'collection'=>$collection, 'collectionType'=>$collectionType, 'needMove'=>$needMove, 'freeMove'=>$freeMove));
		}else{
			echo $this->data->sendErr($err);
		}
	}	
		
    /**
     * 出征采集
     * 
     * 
     * @return <type>
     */
	public function gotoCollectionAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		$useMove = floor(@$post['useMove']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断是否采集点
			if(!in_array($map['map_element_origin_id'], array(9, 10, 11, 12, 13, 22 /*, 3, 4, 5, 6, 7*/))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($map['map_element_origin_id'] == 22 && time() >= $map['build_time']){
				throw new Exception(10452); //活动已经结束
			}
			
			//采集点是否被自己占领
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
			foreach($ppq as $_ppq){
				if($_ppq['to_x'] == $x && $_ppq['to_y'] == $y){
					throw new Exception(10375); //您已经前往该位置
				}
			}
			
			//如果不为联盟矿，是否被同盟人占领
			if(!in_array($map['map_element_origin_id'], array(3, 4, 5, 6, 7))){
				$otherPpq = PlayerProjectQueue::findFirst(['status=1 and to_map_id='.$map['id'].' and type='.PlayerProjectQueue::TYPE_COLLECT_ING]);
				if($otherPpq){
					if($otherPpq->guild_id && $player['guild_id'] && $otherPpq->guild_id == $player['guild_id']){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				$queueType = PlayerProjectQueue::TYPE_COLLECT_GOTO;
			}else{
				if($map['guild_id'] != $player['guild_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//判断矿是否属于自己盟
				$queueType = PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO;
			}
			
			if($map['map_element_origin_id'] == 22){
				$needMove = (new Starting)->dicGetOne('energy_cost_judian');
			}else{
				$needMove = (new Starting)->dicGetOne('energy_cost_collect');
			}
			
			if($useMove){
				$Map->doBeforeGoOut($playerId, $armyId, false, ['ppq'=>$ppq]);
				$distance = sqrt(pow($player['x'] - $x, 2) + pow($player['y'] - $y, 2));
				$this->useHpMove($player, $distance, $needMove);
				$needTime = self::EXTRASEC;
			}else{
				$Map->doBeforeGoOut($playerId, $armyId, $needMove, ['ppq'=>$ppq]);
				//计算行军时间
				$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, 1, $armyId);
				if(!$needTime){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//建立队列
			$pm = $Map->getByXy($player['x'], $player['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
			];
			$targetInfo = [];
			if($map['player_id']){
				$targetInfo['fight'] = $map['player_id'];
				//消除顽强斗志buff
				(new PlayerBuffTemp)->delByTempId($playerId, [123]);
			}else{
				$targetInfo['fight'] = 0;
			}
			if(!$PlayerProjectQueue->addQueue($playerId, $player['guild_id'], 0, $queueType, $needTime, $armyId, $targetInfo, $extraData)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($map['player_id']){
				$pushId = (new PlayerPush)->add($map['player_id'], 2, 400007, []);
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
     * 去攻城
     * 
     * 
     * @return <type>
     */
	public function gotoAttackCityAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		$useMove = floor(@$post['useMove']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断是否非同盟城堡
			$pvpExtra = false;
			if($map['map_element_origin_id'] == 15){//城堡
				if($map['player_id'] == $playerId){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($player['guild_id']){
					if($player['guild_id'] == $map['guild_id']){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				
				//判断对方是否带套子
				if((new Player)->isAvoidBattle((new Player)->getByPlayerId($map['player_id']))){
					throw new Exception(10326);//对方正处于免战状态
				}
				
				//消除顽强斗志buff
				(new PlayerBuffTemp)->delByTempId($playerId, [123]);
				
				$type = PlayerProjectQueue::TYPE_CITYBATTLE_GOTO;
				$targetInfo = [];
				$pvpExtra = true;
			}elseif($map['map_element_origin_id'] == 1){//堡垒
				$type = PlayerProjectQueue::TYPE_ATTACKBASE_GOTO;
				$targetInfo = ['to_guild_id'=>$map['guild_id']];
				
				//检查是否为我方堡垒
				if($map['guild_id'] == $player['guild_id']){
					throw new Exception(10327);//不能攻击己方堡垒
				}
				
				//预警邮件
				//获取我方联盟信息
				if($player['guild_id']){
					$guild = (new Guild)->getGuildInfo($player['guild_id']);
					$guildId = $player['guild_id'];
					$guildName = $guild['name'];
					$guildShort = $guild['short_name'];
				}else{
					throw new Exception(10402);//没有联盟不能攻击联盟堡垒 //没有联盟不能攻击联盟堡垒
					$guildId = 0;
					$guildName = '';
					$guildShort = '';
				}
				
				//获取对方联盟所有成员
				$PlayerGuild = new PlayerGuild;
				$players = $PlayerGuild->getAllGuildMember($map['guild_id']);
				if(!$players){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$playerIds = array_keys($players);
				
				//发送邮件
				$mailData = [
					'x'=>$x,
					'y'=>$y,
					'playerNick'=>$player['nick'],
					'playerAvatar'=>$player['avatar_id'],
					'guildId'=>$guildId,
					'guildName'=>$guildName,
					'guildShort'=>$guildShort,
				];
				if(!(new PlayerMail)->sendSystem($playerIds, PlayerMail::TYPE_ATTACKBASEWARN, '', '', 0, $mailData)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//删除我方套子
			(new Player)->offAvoidBattle($playerId);
			
			if($useMove){
				$Map->doBeforeGoOut($playerId, $armyId, false);
				$distance = sqrt(pow($player['x'] - $x, 2) + pow($player['y'] - $y, 2));
				$this->useHpMove($player, $distance, (new Starting)->dicGetOne('energy_cost_castle'), $pvpExtra);
				$needTime = self::EXTRASEC;
			}else{
				$Map->doBeforeGoOut($playerId, $armyId, (new Starting)->dicGetOne('energy_cost_castle'));
				
				//计算行军时间
				$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, 3, $armyId);
				if(!$needTime){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//建立队列
			$pm = $Map->getByXy($player['x'], $player['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
			];
			$PlayerProjectQueue = new PlayerProjectQueue;
			if(!$PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $map['player_id'], $type, $needTime, $armyId, $targetInfo, $extraData)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//通知我方
			if($player['guild_id']){
				$PlayerProjectQueue->noticeFight(2, $player['guild_id']);
			}else{
				$PlayerProjectQueue->noticeFight(1, $playerId);
			}
			
			//通知敌方
			if($map['guild_id']){
				$PlayerProjectQueue->noticeFight(2, $map['guild_id']);
			}else{
				$PlayerProjectQueue->noticeFight(1, $map['player_id']);
			}
			
			if($map['map_element_origin_id'] == 15){
				$pushId = (new PlayerPush)->add($map['player_id'], 2, 400007, []);
				socketSend(['Type'=>'attacked', 'Data'=>['playerId'=>[$map['player_id']]]]);
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
     * 出征打怪
     * 
     * 
     * @return <type>
     */
	public function gotoAttackNpcAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		$useMove = floor(@$post['useMove']);
		$quickMove = floor(@$post['quickMove']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map){
				Cache::delPlayer($playerId, 'findNpc');
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断是否非怪物
			if($map['map_element_origin_id'] != 14){
				Cache::delPlayer($playerId, 'findNpc');
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取npc信息
			$MapElement = new MapElement;
			$me = $MapElement->dicGetOne($map['map_element_id']);
			if(!$me){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$Npc = new Npc;
			$npc = $Npc->dicGetOne($me['npc_id']);
			if(!$npc){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查npc等级
			if($npc['monster_lv'] > $player['monster_lv']+1){
				throw new Exception(10328);//无法挑战该等级怪物
			}
			
			
			if($quickMove){//1秒往返
				$Map->doBeforeGoOut($playerId, $armyId, (new Starting)->dicGetOne('energy_cost_npc'));
				
				$PlayerInfo = new PlayerInfo;
				$playerInfo = $PlayerInfo->getByPlayerId($playerId);
				if(!$playerInfo['quick_out']){
					$PlayerInfo->alter($playerId, ['quick_out'=>1]);
				}else{
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$needTime = 1;
			}elseif($useMove){
				$Map->doBeforeGoOut($playerId, $armyId, false);
				
				$distance = sqrt(pow($player['x'] - $x, 2) + pow($player['y'] - $y, 2));
				$this->useHpMove($player, $distance, (new Starting)->dicGetOne('energy_cost_npc'));
				$needTime = self::EXTRASEC;
			}else{
				$Map->doBeforeGoOut($playerId, $armyId, (new Starting)->dicGetOne('energy_cost_npc'));
				
				//计算行军时间
				$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, 2, $armyId);
				if(!$needTime){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//建立队列
			$pm = $Map->getByXy($player['x'], $player['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
			];
			$targetInfo = [];
			if($quickMove){
				$targetInfo['quickMove'] = 1;
			}
			$PlayerProjectQueue = new PlayerProjectQueue;
			if(!$PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $map['player_id'], PlayerProjectQueue::TYPE_NPCBATTLE_GOTO, $needTime, $armyId, $targetInfo, $extraData)){
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
     * 出征拿去地图物品（和氏璧等）
     * 
     * 
     * @return <type>
     */
	public function gotoFetchItemAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断是否非怪物
			if($map['map_element_origin_id'] != 21){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$needMove = (new Starting)->dicGetOne('energy_cost_yuxi');
			$Map->doBeforeGoOut($playerId, 0, $needMove);
			
			//计算行军时间
			$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, 4, 0);
			if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//建立队列
			$pm = $Map->getByXy($player['x'], $player['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
				'origin_id'=>$map['map_element_origin_id'],
			];
			$targetInfo = [];
			$PlayerProjectQueue = new PlayerProjectQueue;
			if(!$PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $map['player_id'], PlayerProjectQueue::TYPE_FETCHITEM_GOTO, $needTime, 0, $targetInfo, $extraData)){
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
     * 召回静止队列
     * queueId： 队列id
     * 
     * @return <type>
     */
	public function callbackStayQueueAction(){
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
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取队列
			$PlayerProjectQueue = new PlayerProjectQueue;
			$types = $PlayerProjectQueue->stayTypes;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(10274);//未找到队列
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			if(!$PlayerProjectQueue->callbackQueue($ppq->id, $ppq->to_x, $ppq->to_y)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//判断队列类型
			/*if(!isset($types[$ppq->type])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取返回type
			$returnType = $types[$ppq->type];
			
			//修改结束时间
			if(!$ppq->updateEndtime(date('Y-m-d H:i:s'))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//王战集结驻防解散
			if($ppq->type == PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE){//大召回
				//查找子队列
				$otherPpqs = PlayerProjectQueue::find(['parent_queue_id='.$ppq->id.' and type='.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST.' and status=1']);
				foreach($otherPpqs as $_ppq){
					//撤销原有队列
					if(!$_ppq->updateEndtime(date('Y-m-d H:i:s'))){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
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
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取队列
			$PlayerProjectQueue = new PlayerProjectQueue;
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
			if(in_array($ppq->type, $PlayerProjectQueue->gatherTypes)){//大召回
				$itemId = 21600;
			}else{
				$itemId = 21500;
			}
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->drop($playerId, $itemId)){
				throw new Exception(10221);
			}
			
			if(!$PlayerProjectQueue->callbackQueue($ppq->id, $ppq->to_x, $ppq->to_y)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//计算已用时间
			/*$now = time();
			$ppq->accelerate_info = json_decode($ppq->accelerate_info, true);
			$_time = strtotime($ppq->create_time);
			$usedTime = 0;
			$v = 1;
			$immediat = false;
			if(@$ppq->accelerate_info['log']){
				foreach($ppq->accelerate_info['log'] as $_i => $_log){
					$s = ($_log['time'] - $_time);
					//$usedTime += pow(2, $_i) * $s;
					$usedTime += $v * $s;
					if($_log['itemId'] == -1){
						$immediat = true;
						$usedTime = $ppq->accelerate_info['second'];
						break;
					}else{
						$v *= $_log['v'];
					}
					$_time = $_log['time'];
				}
				//$usedTime += ($now - $_time) * pow(2, count($ppq->accelerate_info['log']));
				if(!$immediat){
					$usedTime += ($now - $_time) * $v;
				}
			}
			$restTime = $ppq->accelerate_info['second'] - $usedTime;
			//计算剩余时间
			$createTime = date('Y-m-d H:i:s', $now - $restTime);
			$endTime = date('Y-m-d H:i:s', $now + $usedTime);
			
			$returnType= $types[$ppq->type];
			$extraData = [
				'from_map_id' => $ppq->to_map_id,
				'from_x' => $ppq->to_x,
				'from_y' => $ppq->to_y,
				'to_map_id' => $ppq->from_map_id,
				'to_x' => $ppq->from_x,
				'to_y' => $ppq->from_y,
				'carry_gold' => $ppq->carry_gold,
				'carry_food' => $ppq->carry_food,
				'carry_wood' => $ppq->carry_wood,
				'carry_stone' => $ppq->carry_stone,
				'carry_iron' => $ppq->carry_iron,
			];
			
			//撤销原有队列
			if(!$PlayerProjectQueue->cancelQueue($playerId, $ppq->id)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//新增回家队列
			$newQueueId = $PlayerProjectQueue->addQueue($playerId, $ppq->guild_id, 0, $returnType, ['create_time'=>$createTime, 'end_time'=>$endTime], $ppq->army_id, [], $extraData);
			if(!$newQueueId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
				
			
			//集结子队列
			if(in_array($ppq->type, $PlayerProjectQueue->gatherTypes)){//大召回
				//查找子队列
				$otherPpqs = PlayerProjectQueue::find(['parent_queue_id='.$ppq->id.' and type='.PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO.' and status=1']);
				
				foreach($otherPpqs as $_ppq){
					//撤销原有队列
					if(!$PlayerProjectQueue->cancelQueue($_ppq->player_id, $_ppq->id)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					$_extraData = [
						'from_map_id' => $_ppq->to_map_id,
						'from_x' => $_ppq->to_x,
						'from_y' => $_ppq->to_y,
						'to_map_id' => $_ppq->from_map_id,
						'to_x' => $_ppq->from_x,
						'to_y' => $_ppq->from_y,
						'carry_gold' => $_ppq->carry_gold,
						'carry_food' => $_ppq->carry_food,
						'carry_wood' => $_ppq->carry_wood,
						'carry_stone' => $_ppq->carry_stone,
						'carry_iron' => $_ppq->carry_iron,
						'parent_queue_id' => $newQueueId,
					];
					
					//新增回集结者家队列
					if(!$PlayerProjectQueue->addQueue($_ppq->player_id, $_ppq->guild_id, 0, PlayerProjectQueue::TYPE_GATHERD_MIDRETURN, ['create_time'=>$createTime, 'end_time'=>$endTime], $_ppq->army_id, [], $_extraData)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
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
		$itemId = floor(@$post['itemId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}
		if(!in_array($itemId, array(21701, 21702, -1)))
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$queueId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取队列
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断是否为移动队列
			if($ppq->from_map_id == $ppq->to_map_id){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断是否属于无法加速的type
			if(in_array($ppq->type, array(PlayerProjectQueue::TYPE_GATHERDBATTLE_GOTO, PlayerProjectQueue::TYPE_GATHERD_MIDRETURN))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//如果是行动力加速
			$now = time();
			$Player = new Player;
			if($itemId == -1){
				$extraSec = self::EXTRASEC;
				//集结类无法使用
				if(in_array($ppq->type, $PlayerProjectQueue->gatherTypes)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//计算剩余距离
				$accelerateInfo = json_decode($ppq->accelerate_info, true);
				$_time = strtotime($ppq->create_time);
				$usedTime = 0;
				$cutSec = 0;
				$immediat = false;
				$v = 1;
				if(@$accelerateInfo['log']){
					foreach($accelerateInfo['log'] as $_i => $_log){
						$s = ($_log['time'] - $_time);
						$usedTime += $v * $s;
						if($_log['itemId'] == -1){
							$immediat = true;
							$usedTime = $ppq->accelerate_info['second'];
							break;
						}else{
							$v *= $_log['v'];
						}
						$_time = $_log['time'];
						$cutSec += $_log['cutsecond'];
					}
					if(!$immediat){
						$usedTime += ($now - $_time) * $v;
					}
				}else{
					$usedTime += $now - $_time;
				}
				$per = (1 - $usedTime / $accelerateInfo['second']);
				$distance = sqrt(pow($ppq->from_x - $ppq->to_x, 2) + pow($ppq->from_y - $ppq->to_y, 2)) * $per;
				
				if($accelerateInfo['second'] - $usedTime < $extraSec){
					throw new Exception(10329);//部队已经快要达到终点
				}
				
				//计算所需体力和元宝
				//体力消耗=max(int(（距离^0.911）*0.45),5)
				//$needMove = max(pow($distance, 0.911)*0.45, 5);
				/*$needMove = distance2move($distance);
				if($player['move'] < $needMove){
					//所需金币
					//1体力=2元宝
					$needGem = 2 * ($needMove - $player['move']);
					if(!$Player->updateGem($playerId, -$needGem, true, ['cost'=>0, 'memo'=>'快速行军'])){
						throw new Exception(10121);
					}
				}
				$subMove = min($needMove, $player['move']);
				if(!$Player->updateMove($playerId, -$subMove)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				*/
				if($ppq->type == PlayerProjectQueue::TYPE_CITYBATTLE_GOTO){
					$pvpExtra = true;
				}else{
					$pvpExtra = false;
				}
				$this->useHpMove($player, $distance, 0, $pvpExtra);
				
				$newSec = $now + $extraSec - strtotime($ppq->create_time);
				$cutSecond = max(0, $accelerateInfo['second'] - ($newSec + $cutSec));
				$newEndTime = date('Y-m-d H:i:s', $now + $extraSec);
				
				
				$v = 1;
			}else{
				//消耗道具
				if($itemId == 21701){//行军加速25%
					$accTimeRate = 0.25;
				}elseif($itemId == 21702){//行军加速50%
					$accTimeRate = 0.5;
				}else{
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$v = 1 / (1-$accTimeRate);
				$PlayerItem = new PlayerItem;
				if(!$PlayerItem->drop($playerId, $itemId)){
					throw new Exception(10222);
				}
				
				//重新计算end_time
				if(strtotime($ppq->end_time) < $now){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$accelerateInfo = json_decode($ppq->accelerate_info, true);
				$restSecond = max(0, strtotime($ppq->end_time) - $now);
				$cutSecond = floor($restSecond*$accTimeRate);
				$newEndTime = date('Y-m-d H:i:s', $now + ($restSecond - $cutSecond));
			}
			
			//更新end_time
			if(!isset($accelerateInfo['log'])){
				$accelerateInfo['log'] = [];
			}
			$accelerateInfo['log'][] = array('time'=>$now, 'itemId'=>$itemId, 'cutsecond'=>$cutSecond, 'v'=>$v);
			$accelerateInfo['log'] = array_slice($accelerateInfo['log'], -10);
			
			if(!$ppq->updateAcce($newEndTime, $accelerateInfo)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//子队列
			if(in_array($ppq->type, array_merge($PlayerProjectQueue->gatherTypes, [ PlayerProjectQueue::TYPE_GATHER_RETURN]))){//集结移动加速
				//查找子队列
				$otherPpqs = PlayerProjectQueue::find(['parent_queue_id='.$ppq->id.' and status=1']);
				
				foreach($otherPpqs as $_ppq){
					if(!$_ppq->updateAcce($newEndTime, $accelerateInfo)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
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
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
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
     * 发起集结（发起方）
     * 
     * time:集结时间：1.五分钟；2.10分钟；3.30分钟；4.60分钟
     * @return <type>
     */
	public function startGatherAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		$time = floor(@$post['time']);
        $info = [];//长连接推送信息

        $info['nick'] = $player['nick'];//发起人nick

		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId) || !checkRegularNumber($time)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;
		if(!in_array($time, array(1, 2, 3, 4)))
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取集结时间
			$needTime = (new Starting)->dicGetOne('war_house_masstime'.$time);
			if(!$needTime)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//检查联盟
			if(!$player['guild_id']){
				throw new Exception(10358);//未加入联盟
			}
			
			//获取地图点信息
			$Map = new Map;
            $Guild = new Guild;
			$map = $Map->getByXy($x, $y);
			if(!$map)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//检查大厅等级
			/*if(!(new PlayerBuild)->getMaxGatherNum($playerId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
			
			$needMove = (new Starting)->dicGetOne('energy_cost_npc_team');
			if($map['map_element_origin_id'] == 15){//集结打玩家
				$offProtect = true;
				//检查是否非盟友的城堡
				if(!$map['player_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				$Player = new Player;
				if(!($toPlayer = $Player->getByPlayerId($map['player_id']))){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if(!$player['guild_id'] || $toPlayer['guild_id'] == $player['guild_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($Player->isAvoidBattle($toPlayer)){
					throw new Exception(10326);
				}
				$toType = 'attackPlayer';
                //长连接推送信息
                $info['type']                    = $toType;
                $targetGuild                     = $Guild->getGuildInfo($toPlayer['guild_id']);
                $info['target_player_nick']      = $toPlayer['nick'];
                $info['target_guild_short_name'] = $targetGuild['short_name'];
			}elseif($map['map_element_origin_id'] == 1){//集结攻堡垒
				$offProtect = true;
				//判断堡垒是否属于自己公会
				if($player['guild_id'] && $player['guild_id'] == $map['guild_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$toType = 'attackBase';
                //长连接推送信息
                $info['type']                    = $toType;
                $targetGuild                     = $Guild->getGuildInfo($map['guild_id']);
                $info['target_guild_short_name'] = $targetGuild['short_name'];
			}elseif(in_array($map['map_element_origin_id'], [18, 19])){//集结王战
				$offProtect = true;
				//判断是否是王战状态
				$King = new King;
				$king = $King->getCurrentBattle();
				if(!$king)
				    //FIXME 暂时添加的errcode
                    throw new Exception(10492);//皇位战即将开启。请稍后重试
					//throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
				$KingTown = new KingTown;
				$town = $KingTown->getByXy($x, $y);
				if(!$town){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$toType = 'attackTown';
                //长连接推送信息
                $info['type']           = $toType;
                $info['map_element_id'] = $map['map_element_id'];
			}elseif(in_array($map['map_element_origin_id'], [17])){//集结boss
				$offProtect = false;
				
				//检查怪物通过等级
				$MapElement = new MapElement;
				$me = $MapElement->dicGetOne($map['map_element_id']);
				$Npc = new Npc;
				$npc = $Npc->dicGetOne($me['npc_id']);
				if($player['monster_lv'] < $npc['monster_lv']){
					throw new Exception(10359);//不满足挑战怪物等级
				}
				$toType = 'attackBoss';
                //长连接推送信息
                $info['type']           = $toType;
                $info['map_element_id'] = $map['map_element_id'];
			}else{
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			
			
			//判断是否已经发起集结
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
			foreach($ppq as $_ppq){
				if(
				(in_array($_ppq['type'], array_merge($PlayerProjectQueue->gatherTypes, [PlayerProjectQueue::TYPE_GATHER_WAIT])) && $map['id'] == $_ppq['to_map_id']) || 
				(in_array($_ppq['type'], [PlayerProjectQueue::TYPE_GATHER_RETURN]) && $x == $_ppq['from_x'] && $y == $_ppq['from_y'])
				){
					throw new Exception(10223);
				}
				
				if(in_array($map['map_element_origin_id'], [18, 19]) && $town['guild_id'] == $player['guild_id']){
					if(
						($_ppq['type'] == PlayerProjectQueue::TYPE_KINGTOWN_GOTO && $_ppq['to_x'] == $x && $_ppq['to_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE && $_ppq['to_x'] == $x && $_ppq['to_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_KINGTOWN_RETURN && $_ppq['from_x'] == $x && $_ppq['from_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_WAIT && $_ppq['target_info']['to_x'] == $x && $_ppq['target_info']['to_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_KINGGATHERBATTLE_GOTO && $_ppq['to_x'] == $x && $_ppq['to_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_ATTACKBASEGATHER_GOTO && $_ppq['to_x'] == $x && $_ppq['to_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_RETURN && $_ppq['from_x'] == $x && $_ppq['from_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_GOTO && $_ppq['target_info']['to_x'] == $x && $_ppq['target_info']['to_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_STAY && $_ppq['target_info']['to_x'] == $x && $_ppq['target_info']['to_y'] == $y) || 
						($_ppq['type'] == PlayerProjectQueue::TYPE_GATHERD_MIDRETURN && $_ppq['from_x'] == $x && $_ppq['from_y'] == $y) 
					){
						throw new Exception(10223);
					}
				}
			}
			
			$Map->doBeforeGoOut($playerId, $armyId, $needMove, ['ppq'=>$ppq]);
			
			//删除我方套子
			if($offProtect)
				(new Player)->offAvoidBattle($playerId);
			
			//建立队列
			$pm = $Map->getByXy($player['x'], $player['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $pm['id'],
				'to_x' => $player['x'],
				'to_y' => $player['y'],
			];
			$targetInfo = [
				'type'=>$toType,
				'to_x'=>$x,
				'to_y'=>$y,
				'to_map_id'=>$dm['id'],
				'to_player_id'=>$map['player_id'],
				'to_guild_id'=>$map['guild_id'],
				'element_id'=>$map['map_element_id'],
			];
			
			if(!$queueId = $PlayerProjectQueue->addQueue($playerId, $player['guild_id'], @$toPlayer['id'], PlayerProjectQueue::TYPE_GATHER_WAIT, $needTime, $armyId, $targetInfo, $extraData)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerGuild = new PlayerGuild;
			$members = $PlayerGuild->getAllGuildMember($player['guild_id']);
			$memberIds = array_keys($members);

			
			//通知我方
			//$queues = (new ArmyController)->gatherArmyInfo($player, true);
			//$queueInfo = @$queues[$queueId];
			//if($queueInfo){
            $data = ['playerId'=>array_diff($memberIds, [$playerId])];
            if(isset($info['type'])) {
                $data['info'] = $info;
            }
			socketSend(['Type'=>'gather', 'Data'=>$data]);
			//}
			
			//$PlayerProjectQueue->noticeFight(2, $player['guild_id']);
			
			//推送
			if(in_array($map['map_element_origin_id'], [15 /*, 1, 17*/])){
				$param = [];
				$param['playernameA'] = $player['nick'];
				switch($map['map_element_origin_id']){
					case 1:
						$guild = (new Guild)->getGuildInfo($map['guild_id']);
						$param['guildname'] = $guild['name'];
						$code = 400012;
					break;
					case 15:
						$param['playernameB'] = $toPlayer['nick'];
						$code = 400011;
					break;
					case 17:
						$param['bossname'] = $Npc['monster_name'];
						$code = 400013;
					break;
				}
				foreach($memberIds as $_id){
					(new PlayerPush)->add($_id, 5, $code, $param);
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
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 取消集结
     * 
     * 
     * @return <type>
     */
	public function cancelGatherAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//判断是否已经发起集结
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
			$findFlag = false;
			foreach($ppq as $_ppq){
				if($_ppq['id'] == $queueId && $_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_WAIT){
					$ppq = $_ppq;
					$findFlag = true;
					break;
				}
			}
			if(!$findFlag)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			$lockKey2 = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
			Cache::lock($lockKey2);
			
			
			if(!$PlayerProjectQueue->callbackQueue($ppq['id'], $ppq['to_x'], $ppq['to_y'])){
				throw new Exception(10435); //操作正在处理
			}
			//修改army状态
			//获取军团
			/*$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByArmyId($playerId, $ppq['army_id']);
			if(!$playerArmy)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			if(!$PlayerArmy->assign($playerArmy)->updateStatus(0)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//修改武将状态
			$PlayerArmyUnit = new PlayerArmyUnit;
			$pau = $PlayerArmyUnit->getByArmyId($playerId, $ppq['army_id']);
			$generalIds = [];
			foreach($pau as $_pau){
				$generalIds[] = $_pau['general_id'];
			}
			$PlayerGeneral = new PlayerGeneral;
			if(!$PlayerGeneral->updateReturnByGeneralIds($playerId, $generalIds)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//删除队列
			if(!$PlayerProjectQueue->cancelQueue($playerId, $ppq['id'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新前往集结盟友队列的结束时间
			$queues = $PlayerProjectQueue->find(["parent_queue_id=".$ppq['id']." and status=1 and type=".PlayerProjectQueue::TYPE_GATHER_STAY]);
			foreach($queues as $_q){
				if(!$_q->updateEndtime(date('Y-m-d H:i:s'))){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
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
		if(@$lockKey2)
			Cache::unlock($lockKey2);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
		
    /**
     * 踢出某个集结玩家
     * 
     * 
     * @return <type>
     */
	public function kickGatherAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$targetPlayerId = floor(@$post['targetPlayerId']);
		$parentQueueId = floor(@$post['parentQueueId']);
		if(!checkRegularNumber($targetPlayerId) || !checkRegularNumber($parentQueueId)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//判断是否已经发起集结
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
			$findFlag = false;
			foreach($ppq as $_ppq){
				if($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_WAIT && $_ppq['id'] == $parentQueueId){
					$ppq = $_ppq;
					$findFlag = true;
				}
			}
			if(!$findFlag)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						
			//查找踢出队列
			$queues = $PlayerProjectQueue->find(["parent_queue_id=".$ppq['id']." and status=1 and type=".PlayerProjectQueue::TYPE_GATHER_STAY]);
			$findFlag = false;
			foreach($queues as $_q){
				if($_q->player_id == $targetPlayerId){
					$targetPpq = $_q->toArray();
					$findFlag = true;
					break;
				}
			}
			if(!$findFlag)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			if(!$PlayerProjectQueue->finishQueue($targetPlayerId, $targetPpq['id'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//计算时间
			$Player = new Player;
			$toPlayer = $Player->getByPlayerId($targetPlayerId);
			if(!$toPlayer){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$needTime = PlayerProjectQueue::calculateMoveTime($targetPlayerId, $targetPpq['to_x'], $targetPpq['to_y'], $toPlayer['x'], $toPlayer['y'], 3, $targetPpq['army_id']);
			if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$extraData = [
				'from_map_id' => $targetPpq['to_map_id'],
				'from_x' => $targetPpq['to_x'],
				'from_y' => $targetPpq['to_y'],
				'to_map_id' => $toPlayer['map_id'],
				'to_x' => $toPlayer['x'],
				'to_y' => $toPlayer['y'],
			];
			if(!$PlayerProjectQueue->addQueue($targetPlayerId, $targetPpq['guild_id'], 0, PlayerProjectQueue::TYPE_GATHER_RETURN, $needTime, $targetPpq['army_id'], [], $extraData)){
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
     * 前往集结（援助方）
     * 
     * 
     * @return <type>
     */
	public function gotoGatherAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		$queueId = floor(@$post['queueId']);
		$useMove = floor(@$post['useMove']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId) || !checkRegularNumber($queueId)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查联盟
			if(!$player['guild_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//检查是否盟友的城堡
			if(!$map['player_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($map['map_element_origin_id'] != 15){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$Player = new Player;
			if(!($toPlayer = $Player->getByPlayerId($map['player_id']))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$toPlayer['guild_id'] || $toPlayer['guild_id'] != $player['guild_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			$PlayerProjectQueue = new PlayerProjectQueue;
			//检查对方是否发起集结
			$toPpq = $PlayerProjectQueue->getByPlayerId($toPlayer['id']);

			$findFlag = false;
			$gatherNum = 0;
			foreach($toPpq as $_ppq){
				if($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_WAIT && $_ppq['id'] == $queueId){
					$parentQueueId = $_ppq['id'];
					$parentQueue = $_ppq;
					$findFlag = true;
					//break;
				}
				if($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_STAY && $_ppq['id'] == $queueId){
					$gatherNum++;
				}
			}
			if(!$findFlag){
				throw new Exception(10360);//没有找到集结信息
			}
			
			//判断是否已经前往该集结
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
			foreach($ppq as $_ppq){
				if($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_STAY && $_ppq['parent_queue_id'] == $parentQueueId){
					throw new Exception(10225);
				}
			}
			//判断集结上限
			if($gatherNum+1 >= (new PlayerBuild)->getMaxGatherNum($toPlayer['id'])){
				throw new Exception(10226);
			}
			
			$pm = $Map->getByXy($parentQueue['target_info']['to_x'], $parentQueue['target_info']['to_y']);
			if(!$pm)
				throw new Exception(10361);//目标已经消失
			
			/*if(in_array($pm['map_element_origin_id'], [18, 19])){
				$needMove = false;
			}else{
				$needMove = (new Starting)->dicGetOne('energy_cost_castle');
			}*/
			$needMove = (new Starting)->dicGetOne('energy_cost_npc_teamaid');
			if($pm['map_element_origin_id'] == 15){//集结打玩家
				$offProtect = true;
			}elseif($pm['map_element_origin_id'] == 1){//集结攻堡垒
				$offProtect = true;
			}elseif(in_array($pm['map_element_origin_id'], [18, 19])){//集结王战
				$offProtect = true;
			}elseif(in_array($pm['map_element_origin_id'], [17])){//集结boss
				$offProtect = false;
				
				//检查怪物通过等级
				$MapElement = new MapElement;
				$me = $MapElement->dicGetOne($map['map_element_id']);
				$Npc = new Npc;
				$npc = $Npc->dicGetOne($me['npc_id']);
				if($player['monster_lv'] < $npc['monster_lv']){
					throw new Exception(10362);//不满足挑战怪物等级
				}
			}else{
				throw new Exception(10361);
			}
			//删除我方套子
			if($offProtect)
				(new Player)->offAvoidBattle($playerId);
			
			if($useMove){
				$Map->doBeforeGoOut($playerId, $armyId, false, ['ppq'=>$ppq]);
				
				$distance = sqrt(pow($player['x'] - $x, 2) + pow($player['y'] - $y, 2));
				$this->useHpMove($player, $distance, $needMove);
				$needTime = self::EXTRASEC;
			}else{
				$Map->doBeforeGoOut($playerId, $armyId, $needMove, ['ppq'=>$ppq]);
				
				//计算行军时间
				$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, 3, $armyId);
				if(!$needTime){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//建立队列
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $player['map_id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $dm['x'],
				'to_y' => $dm['y'],
				'parent_queue_id' => $parentQueueId,
				
			];
			$targetInfo = $parentQueue['target_info'];/*[
				'to_x'=>$x,
				'to_y'=>$y,
				'to_map_id'=>$dm['id'],
				'to_player_id'=>$map['player_id'],
			];*/
			if(!$PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $toPlayer['id'], PlayerProjectQueue::TYPE_GATHER_GOTO, $needTime, $armyId, $targetInfo, $extraData)){
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
     * 前往王战城寨
     * 
     * 
     * @return <type>
     */
	public function gotoTownAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		$useMove = floor(@$post['useMove']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//判断是否是王战状态
			$King = new King;
			$king = $King->getCurrentBattle();
			if(!$king)
				throw new Exception(10330);//当前没有王战
			
			
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断是否城寨
			if(!in_array($map['map_element_origin_id'], [18, 19])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//无盟玩家不能占领
			if(!$player['guild_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$KingTown = new KingTown;
			$town = $KingTown->getByXy($x, $y);
			if(!$town){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
			/*if($town['guild_id'] == $player['guild_id']){//已被我方占领
				//是否已有派往的队列
				foreach($ppq as $_ppq){
					if(
					($_ppq['type'] == PlayerProjectQueue::TYPE_KINGTOWN_GOTO && $_ppq['to_x'] == $x && $_ppq['to_y'] == $y) || 
					($_ppq['type'] == PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE && $_ppq['to_x'] == $x && $_ppq['to_y'] == $y) || 
					($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_WAIT && $_ppq['target_info']['to_x'] == $x && $_ppq['target_info']['to_y'] == $y) || 
					($_ppq['type'] == PlayerProjectQueue::TYPE_KINGGATHERBATTLE_GOTO && $_ppq['to_x'] == $x && $_ppq['to_y'] == $y) || 
					($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_GOTO && $_ppq['target_info']['to_x'] == $x && $_ppq['target_info']['to_y'] == $y) || 
					($_ppq['type'] == PlayerProjectQueue::TYPE_GATHER_STAY && $_ppq['target_info']['to_x'] == $x && $_ppq['target_info']['to_y'] == $y)
					){
						throw new Exception(10227);
					}
				}
				
			}*/
			
			//删除我方套子
			(new Player)->offAvoidBattle($playerId);
			
			if($useMove){
				$Map->doBeforeGoOut($playerId, $armyId, false, ['ppq'=>$ppq]);
				
				$distance = sqrt(pow($player['x'] - $x, 2) + pow($player['y'] - $y, 2));
				$this->useHpMove($player, $distance, (new Starting)->dicGetOne('energy_cost_castle'));
				$needTime = self::EXTRASEC;
			}else{
				$Map->doBeforeGoOut($playerId, $armyId, (new Starting)->dicGetOne('energy_cost_castle'), ['ppq'=>$ppq]);
				
				//计算行军时间
				$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, 3, $armyId);
				if(!$needTime){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//建立队列
			$pm = $Map->getByXy($player['x'], $player['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
			];
			$PlayerProjectQueue = new PlayerProjectQueue;
			if(!$PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $map['player_id'], PlayerProjectQueue::TYPE_KINGTOWN_GOTO, $needTime, $armyId, [], $extraData)){
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
	
	/*public function doBeforeGoOut($playerId, $armyId, $needMove = true, $data=array()){
		//扣除体力
		$Player = new Player;
		if($needMove){
			if(!$Player->updateMove($playerId, -5)){
				throw new Exception(10228);
			}
		}
		
		//判断队列数
		$maxQueueNum = $Player->getQueueNum($playerId);
		if(!@$data['ppq']){
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
		}else{
			$ppq = $data['ppq'];
		}

		if(count($ppq) >= $maxQueueNum){
			throw new Exception(10229);
		}
		
		
		//获取军团
		$PlayerArmy = new PlayerArmy;
		$playerArmy = $PlayerArmy->getByArmyId($playerId, $armyId);
		if(!$playerArmy)
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		
		//判断军团是否空闲
		if($playerArmy['status']){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//判断军团是否已经设置武将
		if(!$playerArmy['leader_general_id']){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//检查army士兵数
		$Soldier = new Soldier;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$pau = $PlayerArmyUnit->getByArmyId($playerId, $armyId);
		$findFlag = false;
		$generalIds = [];
		foreach($pau as $_pau){
			if($_pau['soldier_id'] && $_pau['soldier_num'] > 0){
				$findFlag = true;
			}
			$generalIds[] = $_pau['general_id'];
		}
		if(!$findFlag){
			throw new Exception(10275);//部队没有士兵
		}
		
		//修改army状态
		if(!$PlayerArmy->assign($playerArmy)->updateStatus(1)){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//修改武将状态
		$PlayerGeneral = new PlayerGeneral;
		if(!$PlayerGeneral->updateGooutByGeneralIds($playerId, $generalIds)){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		return true;
	}*/

	/**
     * 迁城
     * 
	 * ```php
	 * /map/changeCastleLocation/
     * postData: json={"type":"", "x":"", "y":""}
     * return: json={}
     * type 1 指定 2 随机 3 联盟
	 * ```
	 * 
     */
	public function changeCastleLocationAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$type = floor(@$post['type']);
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		if(isset($post['useGem'])){
			$useGem = floor(@$post['useGem']);
		}else{
			$useGem = 0;
		}
		$Player = new Player;
		$Map = new Map;
		$PlayerItem = new PlayerItem;
		$PlayerProjectQueue = new PlayerProjectQueue;
		$prq = $PlayerProjectQueue->getByPlayerId($playerId);
		
		//检查上次攻击时间
		if($player['is_in_map']==1 && in_array($type, [2, 3]) && time() < $player['attack_time'] + (new Starting)->dicGetOne('unable_protection') * 3600){
			$err = 10559; //发起攻击后2小时内不可使用随机迁城和联盟迁城
		}elseif(!empty($prq)){
			$err = 10401;//玩家有队列在外，不能迁城
		}elseif($type==1){//指定
			if($Map->checkCastlePosition([$x, $y], $playerId)){
				if( $PlayerItem->drop($playerId, 21300, 1) || $Player->updateGem($playerId, -2000, true, ['cost'=>40210, 'memo'=>'高级迁城']) ){
					$Map->changeCastleLocation($playerId, $x, $y);
					$err = 0;
				}else{
					$err = 10270;//道具不足
				}
			}else{
				$err = 10271;//坐标错误
			}
		}elseif($type==2){//随机
			$p = $Map->getNewCastlePosition($playerId);
			if($p){
				$Player = new Player;
				$player = $Player->getByPlayerId($playerId);
				if($player['is_in_map']==0){
					$Map->changeCastleLocation($playerId, $p[0], $p[1]);
				}elseif( $PlayerItem->drop($playerId, 21200, 1) || $Player->updateGem($playerId, -500, true, ['cost'=>40209, 'memo'=>'随机迁城']) ){
					$Map->changeCastleLocation($playerId, $p[0], $p[1]);
					$err = 0;
				}else{
					$err = 10272;//道具不足
				}
			}else{
				$err = 10273;//坐标错误
			}
		}elseif ($type==3) {//联盟
			//判断是否在盟主周围
			$Guild = new Guild;
			$guild = $Guild->getByPlayerId($playerId);
			$leaderId = $guild['leader_player_id'];
			$leader = $Player->getByPlayerId($leaderId);
			$distance = clacDistance([$x, $y], [$leader['x'], $leader['y']]);
			if($distance>50){
				$err = 10421;//不在盟主周围
			}elseif($Map->checkCastlePosition([$x, $y], $playerId)){
				if( $PlayerItem->drop($playerId, 21400, 1) ){
					$Map->changeCastleLocation($playerId, $x, $y);
					$err = 0;
				}else{
					$err = 10270;//道具不足
				}
			}else{
				$err = 10271;//坐标错误
			}
		}

		if(empty($err)){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 获取队伍信息
     * 
     * 
     * @return <type>
     */
	public function getQueueInfoAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}

		try {
			//获取队列
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getById($queueId);
			if(!$ppq){
				throw new Exception(10331);//找不到队列
			}
			//验证状态
			if($ppq['status'] != 1){
				throw new Exception(10332);//队列已经完成
			}
			
			//验证是否是我的队列
			if($ppq['guild_id'] != $player['guild_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//如果是集结主队列，获取子队列
			if(in_array($ppq['type'], array_merge([PlayerProjectQueue::TYPE_GATHER_WAIT], $PlayerProjectQueue->gatherTypes))){
				$otherPpq = PlayerProjectQueue::find(['parent_queue_id='.$ppq['id'].' and status=1'])->toArray();
				if($otherPpq){
					$ppqs = array_merge([$ppq], $otherPpq);
				}else{
					$ppqs = [$ppq];
				}
			}elseif($ppq['parent_queue_id']){
				//如果是分队列，获取主队列和其他分队列
				$mainPpq = PlayerProjectQueue::find(['id='.$ppq['parent_queue_id'].' and status=1'])->toArray();
				$otherPpq = PlayerProjectQueue::find(['parent_queue_id='.$ppq['parent_queue_id'].' and status=1'])->toArray();
				$ppqs = array_merge($mainPpq, $otherPpq);
			}else{
				$ppqs = [$ppq];
			}
			
			//获取军团信息
			$Player = new Player;
			$PlayerArmyUnit = new PlayerArmyUnit;
			$ret = [];
			foreach($ppqs as $_ppq){
				$_ret = [];
				$_player = $Player->getByPlayerId($_ppq['player_id']);
				$pau = $PlayerArmyUnit->getByArmyId($_ppq['player_id'], $_ppq['army_id']);
				if(!$pau){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$_ret['player_id'] = $_player['id'];
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
	 * 查找最近的资源矿
	 * ```php
	 * /map/getResourcePosition/
     * postData: json={}
     * return: json={}
     * 
	 */
	public function getResourcePositionAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$PlayerInfo = new PlayerInfo;
		$playerInfo = $PlayerInfo->getByPlayerId($playerId);
		if($playerInfo['skip_newbie']==0 && $player['step']>100701){
            return;
        }

		$Map = new Map;
		$MapElement = new MapElement;
		$postion = [];
		$t=2;
		while($t<100){
			$n = 0;
			while($n<$t*5){
				$x = mt_rand($player['x']-$t, $player['x']+$t);
				$y = mt_rand($player['y']-$t, $player['y']+$t);
				$f = $Map->checkRandElementPosition([$x, $y]);
				if($f==true){
					$postion = ['x'=>$x, 'y'=>$y];
					$originId = 9;
					$level = 1;
					$element = $MapElement->dicGetOneByOriginIdAndLevel($originId, $level);
					$blockId = $Map->calcBlockByXy($x, $y);
					$data = [
						'x'=>$x, 
						'y'=>$y, 
						'block_id'=>$blockId, 
						'map_element_id'=>$element['id'], 
						'map_element_origin_id'=>$element['origin_id'], 
						'map_element_level'=>$element['level'],
						'resource'=>100,
					];
					$Map->addNew($data);
					break(2);
				}
				$n++;
			}
			
			$t++;
		}
		echo $this->data->send($postion);
	}

	/**
	 * 查找最近的1级怪
	 * ```php
	 * /map/getNpcPosition/
     * postData: json={}
     * return: json={}
     * 
	 */
	public function getNpcPositionAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$PlayerInfo = new PlayerInfo;
		$playerInfo = $PlayerInfo->getByPlayerId($playerId);
		if($playerInfo['skip_newbie']==0 && $player['step']>100701){
            return;
        }
		$post = getPost();

		$Map = new Map;
		$MapElement = new MapElement;
		$postion = [];
		$t=2;
		$level = 1;
		$num = 0;
		while($t<20){
			$n = 0;
			while($n<$t*5){
				$x = mt_rand($player['x']-$t, $player['x']+$t);
				$y = mt_rand($player['y']-$t, $player['y']+$t);
				$f = $Map->checkRandElementPosition([$x, $y]);
				if($f==true){
					if($level==1){
						$postion = ['x'=>$x, 'y'=>$y];
					}
					$originId = 14;
					$element = $MapElement->dicGetOneByOriginIdAndLevel($originId, $level);
					$blockId = $Map->calcBlockByXy($x, $y);
					$data = [
						'x'=>$x, 
						'y'=>$y, 
						'block_id'=>$blockId, 
						'map_element_id'=>$element['id'], 
						'map_element_origin_id'=>$element['origin_id'], 
						'map_element_level'=>$element['level'],
						'resource'=>$element['max_res'],
					];
					$Map->addNew($data);
					if($num>=3){
						break(2);
					}else{
						$level = 2;
						$num++;
					}
				}
				$n++;
			}
			$t++;
		}
		echo $this->data->send($postion);
	}

	public function findNpcAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$blockId = floor(@$post['blockId']);
		$level = floor(@$post['level']);
		if(!checkRegularNumber($blockId, true))
			exit;
		if($blockId > 103*103)
			exit;
		
		try {
			//获得我可攻击怪物最大等级
			if(!$level){
				$npcLv = min($player['monster_lv'] + 1, 25);
			}else{
				$npcLv = $level;
			}
			
			//获取缓存
			$c = false;//Cache::getPlayer($playerId, 'findNpc');
			if(!$c || !isset($c[$npcLv])){
				$c = [];
			}
			if(!isset($c[$npcLv][$blockId])){
				/*$b = (new Map)->calcXyByBlock($blockId);
				$bx = floor(($b['from_x'] + $b['to_x'])/2);
				$by = floor(($b['from_y'] + $b['to_y'])/2);*/
				//计算block层次
				/*$arBlock = [
					[$blockId], 
					[$blockId-1, $blockId+1, $blockId-103, $blockId-102, $blockId-101, $blockId+101, $blockId+102, $blockId+103], 
					[$blockId-2, $blockId+2, $blockId-104, $blockId-100, $blockId+100, $blockId+104, $blockId-206, $blockId-205, $blockId-204, $blockId-203, $blockId-202, $blockId+202, $blockId+203, $blockId+204, $blockId+205, $blockId+206], 
				];*/
				
				//过滤坐标。防止跨行
				$blockC = $blockId;
				$arBlock = [[$blockC]];
				$per = 103;
				$d = 2;
				$xi = 1;
				$yi = 1;
				$filter = [0=>[$blockC], 1=>[], 2=>[]];
				while($yi <= 2){
					if($blockC - $yi*$per >= 0){
						$arBlock[$yi][] = $blockC - $yi*$per;
						$filter[$yi][] = $blockC - $yi*$per;
					}
					if($blockC + $yi*$per <= $per*$per){
						$arBlock[$yi][] = $blockC + $yi*$per;
						$filter[$yi][] = $blockC + $yi*$per;
					}
					$yi++;
				}
				//var_dump($filter);
				foreach($filter as $_yi => $_f){
					foreach($_f as $__f){
						$_line = floor($__f / $per);
						//echo '['.$__f.']line:'.$_line.'<br>';
						$xi = 1;
						while($xi <= 2){
							$__line = floor(($__f - $xi) / $per);
							//echo '_line:'.$_line.'<br>';
							if($_line == $__line && $__f - $xi >= 0){
								//echo $__f - $xi.'<br>';
								$arBlock[max($xi, $_yi)][] = $__f - $xi;
							}
							$__line = floor(($__f + $xi) / $per);
							if($_line == $__line){
								//echo $__f + $xi.'<br>';
								$arBlock[max($xi, $_yi)][] = $__f + $xi;
							}
							$xi++;
						}
					}
				}
				//$_block = array_merge($arBlock[0], $arBlock[1], $arBlock[2]);
				
				$ret = [];
				//循环查找
				$_npcLv = $npcLv;
				$needNum = 3;
				$Map = new Map;
				while(count($ret) < $needNum && $_npcLv > 0){
					foreach($arBlock as $_block){
						$elementId = 1400+$_npcLv;
						$_ret = $Map->find(["block_id in (".join(',', $_block).") and map_element_id=".$elementId/*, 'order'=>'rand()'*/])->toArray();
						shuffle($_ret);
						foreach($_ret as $_r){
							$ret[] = ['element_id'=>$_r['map_element_id'], 'x'=>$_r['x'], 'y'=>$_r['y']/*, 'd'=>floor($this->GetPointDistance(['x'=>$_r['x'], 'y'=>$_r['y']], ['x'=>$bx, 'y'=>$by]))*/];
							if(count($ret) >= $needNum || (!$level && $_npcLv == $npcLv && count($ret) >= floor($needNum/2))){
								break 2;
							}
						}
					}
					if($level)
						break;
					$_npcLv--;
				}
				
				//$c[$npcLv][$blockId] = $ret;
				//Cache::setPlayer($playerId, 'findNpc', $c);
			}else{
				$ret = $c[$npcLv][$blockId];
			}
			
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		
		if(!$err){
			echo $this->data->send(array('npc'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 搜寻物品
     * 
     * 
     * @return <type>
     */
	public function findItemAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$blockId = floor(@$post['blockId']);
		$elementId = floor(@$post['elementId']);
		if(!checkRegularNumber($blockId, true) || !checkRegularNumber($elementId))
			exit;
		if($blockId > 103*103)
			exit;
		if($elementId < 1000 || $elementId > 9999)
			exit;
		
		try {
			//$elementId = 1901;
			//获取缓存
			//$c = Cache::getPlayer($playerId, 'findNpc');
			/*if(!$c){
				$c = [];
			}*/
			//计算block层次
			$blockC = $blockId;
			$arBlock = [[$blockC]];
			$per = 103;
			$d = 2;
			if($elementId > 1700 && $elementId < 1800){//搜boss
				$d = 15;
			}
			$xi = 1;
			$yi = 1;
			$filter = [0=>[$blockC], 1=>[], 2=>[]];
			while($yi <= $d){
				if($blockC - $yi*$per >= 0){
					$arBlock[$yi][] = $blockC - $yi*$per;
					$filter[$yi][] = $blockC - $yi*$per;
				}
				if($blockC + $yi*$per <= $per*$per){
					$arBlock[$yi][] = $blockC + $yi*$per;
					$filter[$yi][] = $blockC + $yi*$per;
				}
				$yi++;
			}
			//var_dump($filter);
			foreach($filter as $_yi => $_f){
				foreach($_f as $__f){
					$_line = floor($__f / $per);
					//echo '['.$__f.']line:'.$_line.'<br>';
					$xi = 1;
					while($xi <= $d){
						$__line = floor(($__f - $xi) / $per);
						//echo '_line:'.$_line.'<br>';
						if($_line == $__line && $__f - $xi >= 0){
							//echo $__f - $xi.'<br>';
							$arBlock[max($xi, $_yi)][] = $__f - $xi;
						}
						$__line = floor(($__f + $xi) / $per);
						if($_line == $__line){
							//echo $__f + $xi.'<br>';
							$arBlock[max($xi, $_yi)][] = $__f + $xi;
						}
						$xi++;
					}
				}
			}
		
			$ret = [];
			//循环查找
			$needNum = 1;
			if($elementId == 2001){
				$needNum = 5;
			}
			$Map = new Map;
			$Guild = new Guild;
			$Player = new Player;
			//while(count($ret) < $needNum){
				foreach($arBlock as $_block){
					$_ret = $Map->find(["block_id in (".join(',', $_block).") and map_element_id=".$elementId, 'order'=>'rand()']);
					foreach($_ret as $_r){
						if($elementId == 2001 && $_r->player_id){
							$_player = $Player->getByPlayerId($_r->player_id);
							$nick = $_player['nick'];
						}else{
							$nick = '';
						}
						if($elementId == 2001 && $_r->guild_id){
							$guild = $Guild->getGuildInfo($_r->guild_id);
							$guildName = $guild['name'];
							$guildAvatar = $guild['icon_id'];
						}else{
							$guildName = '';
							$guildAvatar = 0;
						}
						if($_r->map_element_origin_id == 17){
							$durability = $_r->durability;
						}
						$ret[] = ['element_id'=>$_r->map_element_id, 'x'=>$_r->x, 'y'=>$_r->y, 'player_nick'=>$nick, 'guild_id'=>$_r->guild_id, 'guild_name'=>$guildName, 'guild_avatar'=>$guildAvatar, 'durability'=>@$durability];
						if(count($ret) >= $needNum){
							break 2;
						}
					}
				}
			//}
			
			//$c[$npcLv][$blockId] = $ret;
			//Cache::setPlayer($playerId, 'findNpc', $c);
			
			if($elementId > 1700 && $elementId < 1800 && !empty($ret)){//搜boss 并且不为空
				$gem = (new Starting)->dicGetOne('search_boss_cost');
				if(!(new Player)->updateGem($playerId, -$gem, true, ['cost'=>20701])){
					throw new Exception(10251);
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
			echo $this->data->send(array('npc'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	public function useHpMove($player, $distance, $extraMove=0, $pvpExtra=false){
		$Player = new Player;
		$playerId = $player['id'];
		$needMove = distance2move($distance);
		//pvp体力增加
		if($pvpExtra){
			$needMove *= (new Starting)->dicGetOne('energy_cost_rate_castle');
		}
		$needMove += $extraMove;
		
		//如果第一次，不消耗体力
		$PlayerInfo = new PlayerInfo;
		$playerInfo = $PlayerInfo->getByPlayerId($playerId);
		if(!$playerInfo['first_out']){
			$needMove = 0;
			$PlayerInfo->alter($playerId, ['first_out'=>1]);
		}
		
		if($needMove){
			$move = (new Player)->restorePlayerMove($playerId);
			if($move < $needMove){
				//所需金币
				//1体力=2元宝
				$needGem = 2 * ($needMove - $move);
				if(!$Player->updateGem($playerId, -$needGem, true, ['cost'=>10011, 'memo'=>'快速行军'])){
					throw new Exception(10121);
				}
			}
			$subMove = min($needMove, $move);
			if(!$Player->updateMove($playerId, -$subMove)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
		}
		
		(new PlayerTarget)->updateTargetCurrentValue($playerId, 10, 1);
		return true;
	}
	
    /**
     * 获取黄巾起义npc攻击队列
     * 
     * 
     * @return <type>
     */
	public function getHjNpcAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		
		try {
			if(!$player['guild_id']){
				throw new Exception(10469); //您当前没有公会，无法参加活动
			}
			$guildId = $player['guild_id'];
			
			//检查活动
			if(AllianceMatchList::DOING != (new AllianceMatchList)->getAllianceMatchStatus(3, $aml)){
				throw new Exception(10436); //黄巾起义还未开始
			}
			
			$GuildHuangjin = new GuildHuangjin;
			$gj = $GuildHuangjin->findFirst(['guild_id='.$guildId]);
			if(!$gj){
				$GuildHuangjin->add($guildId);
				$gj = $GuildHuangjin->findFirst(['guild_id='.$guildId]);
			}
			$gj = $gj->toArray();
			$gj = $GuildHuangjin->adapter($gj, true);
			$gj = filterFields([$gj], true, $GuildHuangjin->blacklist)[0];
			
			//获取所有建成堡垒位置
			$maps = (new Map)->find(['map_element_id=101 and guild_id='.$guildId.' and status=1'])->toArray();
			$mapIds = Set::extract('/id', $maps);
			
			//获取npc队列
			if($mapIds){
				$PlayerProjectQueue = new PlayerProjectQueue;
				$ret = $PlayerProjectQueue->find(['type='.PlayerProjectQueue::TYPE_HJNPCATTACK_GOTO.' and status=1 and to_map_id in ('.join(',', $mapIds).')'])->toArray();

				$ppqs = $PlayerProjectQueue->afterFindQueue($ret);
				$ret = [];
				if($ppqs){				
					foreach($ppqs as $_ppq){
						$ret[] = [
							'npcId' => $_ppq['player_id'],
							'arrive_time' => $_ppq['end_time'],
							'to_x' => $_ppq['to_x'],
							'to_y' => $_ppq['to_y'],
						];
					}
					
					//下一波
					$maxWave = (new HuangjinAttackMob)->getMaxWave();
					if($ppqs[0]['player_id'] < $maxWave){
						$hj = (new GuildHuangjin)->findFirst(['guild_id='.$guildId])->toArray();
						
						if($hj['history_top_wave'] >= $ppqs[0]['player_id']+1){
							$needTime = (new Starting)->getValueByKey("huangjin_time_faster");
						}else{
							$needTime = (new Starting)->getValueByKey("huangjin_time");
						}
						$ret[] = [
							'npcId' => $ppqs[0]['player_id']+1,
							'arrive_time' => $ppqs[0]['end_time']+$needTime,
							'to_x' => $ppqs[0]['to_x'],
							'to_y' => $ppqs[0]['to_y'],
						];
					}
					
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
			echo $this->data->send(array('guildHuangjin'=>$gj, 'npc'=>$ret, 'hasBase'=>($mapIds ? true:false), 'pos'=>($mapIds ? ['x'=>$maps[0]['x'],'y'=>$maps[0]['y']] : false)));
		}else{
			echo $this->data->sendErr($err);
		}
	}
}