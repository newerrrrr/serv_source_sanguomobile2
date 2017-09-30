<?php
/**
 * 打怪
 */
class QueueFetchItem extends DispatcherTask{
	
    /**
     * 拿取东西
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function _fetchitem($ppq){
		StaticData::$delaySocketSendFlag = true;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$ppq['id'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerProjectQueue = new PlayerProjectQueue;
			$Player = new Player;
			$ppq = $PlayerProjectQueue->afterFindQueue(array($ppq))[0];
			
			//获取目标地图信息
			$Map = new Map;
			$map = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
			if(!$map){
				$this->createArmyReturn($ppq);
				//$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
			//检查坐标是否对应玩家
			if($ppq['to_map_id'] != $map['id'] || !in_array($map['map_element_origin_id'], [21])){
				$this->createArmyReturn($ppq);
				//$this->sendNoTargetMail(array($ppq));
				goto finishQueue;
			}
			
						
			//建立回家队列
			$this->createArmyReturn($ppq, ['carry_item'=>[(new Activity)->hsbDrop]]);
			
			if($map['map_element_origin_id'] == 21){//和氏璧
				//删除和氏璧
				if(!$Map->delMap($map['id'])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			finishQueue:
			//更新队列完成
						
			$PlayerProjectQueue->finishQueue($ppq['player_id'], $ppq['id']);
			
						
			dbCommit($db);
			flushSocketSend();
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
			
			//$return = false;
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		
		echo $err."\r\n";
		return true;
	}
	
    /**
     * 建立返回队列 todo
     * 
     * @param <type> $ppq 
     * 
     * @return <type>
     */
	public function createArmyReturn($ppq, $data=array()){
		//获取我的主城位置
		$Player = new Player;
		$player = $Player->getByPlayerId($ppq['player_id']);
		if(!$player){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//计算时间
		if(@$ppq['target_info']['backNow']){
			$needTime = 0;
		}elseif($ppq['to_x'] != $player['x'] || $ppq['to_y'] != $player['y']){
			$needTime = PlayerProjectQueue::calculateMoveTime($ppq['player_id'], $ppq['to_x'], $ppq['to_y'], $player['x'], $player['y'], 4, $ppq['army_id']);
			/*if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
		}else{
			$needTime = 0;
		}
		
		//建立队列
		$PlayerProjectQueue = new PlayerProjectQueue;
		$data = $PlayerProjectQueue->mergeExtraInfo($ppq, $data);
		$extraData = [
			'from_map_id' => $ppq['to_map_id'],
			'from_x' => $ppq['to_x'],
			'from_y' => $ppq['to_y'],
			'to_map_id' => $player['map_id'],
			'to_x' => $player['x'],
			'to_y' => $player['y'],
		];
		$extraData = $extraData + $data;
		
		$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerProjectQueue->addQueue($ppq['player_id'], $ppq['guild_id'], 0, PlayerProjectQueue::TYPE_FETCHITEM_RETURN, $needTime, $ppq['army_id'], [], $extraData);
		return true;
	}
	
}