<?php
class CrossPlayerProjectQueue extends CrossModelBase{
    public $blacklist = [];
    public $battleId;
	//增加类型需要在MapController的callbackStayQueueAction和callbackMoveQueueAction添加对应
	const TYPE_RETURN                = 1;//回城
	const TYPE_CITYBATTLE_GOTO		 = 101;//攻城
	const TYPE_CITYBATTLE_RETURN	 = 102;//攻城回
	const TYPE_ATTACKDOOR_GOTO		 = 201;//去攻城门
	const TYPE_ATTACKDOOR_RETURN	 = 202;//去攻城门回
	const TYPE_HAMMER_GOTO			 = 301;//去攻城锤
	const TYPE_HAMMER_ING			 = 302;//占领攻城锤
	const TYPE_HAMMER_RETURN		 = 303;//攻城锤回
	//const TYPE_ATTACKHAMMER_GOTO	 = 304;//去打攻城锤
	//const TYPE_ATTACKHAMMER_RETURN	 = 305;//打攻城锤回
	const TYPE_CATAPULT_GOTO		 = 401;//去投石车
	const TYPE_CATAPULT_ING			 = 402;//占领投石车
	const TYPE_CATAPULT_RETURN		 = 403;//投石车回
	const TYPE_LADDER_GOTO			 = 501;//去云梯
	const TYPE_LADDER_ING			 = 502;//占领云梯
	const TYPE_LADDER_RETURN		 = 503;//云梯回
	const TYPE_CROSSBOW_GOTO		 = 601;//去床弩
	const TYPE_CROSSBOW_ING			 = 602;//占领床弩
	const TYPE_CROSSBOW_RETURN		 = 603;//床弩回
	const TYPE_ATTACKBASE_GOTO		 = 701;//去攻大本营
	const TYPE_ATTACKBASE_RETURN	 = 702;//攻大本营回


    const TYPE_CITYSPY_GOTO       = 801;//侦查城堡
    const TYPE_CITYSPY_RETURN     = 802;//侦查城堡回
    const TYPE_CATAPULTSPY_GOTO   = 901;//侦查投石车
    const TYPE_CATAPULTSPY_RETURN = 902;//侦查投石车回

	public $stayTypes = array(
		self::TYPE_HAMMER_ING		=>	self::TYPE_HAMMER_RETURN,
		self::TYPE_CATAPULT_ING		=>	self::TYPE_CATAPULT_RETURN,
		self::TYPE_LADDER_ING		=>	self::TYPE_LADDER_RETURN,
		self::TYPE_CROSSBOW_ING		=>	self::TYPE_CROSSBOW_RETURN,
	);
	public $moveTypes = array(
		self::TYPE_CITYBATTLE_GOTO 		=>	self::TYPE_CITYBATTLE_RETURN,
		self::TYPE_ATTACKDOOR_GOTO		=>	self::TYPE_ATTACKDOOR_RETURN,
		self::TYPE_HAMMER_GOTO			=>	self::TYPE_HAMMER_RETURN,
		//self::TYPE_ATTACKHAMMER_GOTO	=>	self::TYPE_ATTACKHAMMER_RETURN,
		self::TYPE_CATAPULT_GOTO		=>	self::TYPE_CATAPULT_RETURN,
		self::TYPE_LADDER_GOTO			=>	self::TYPE_LADDER_RETURN,
		self::TYPE_CROSSBOW_GOTO		=>	self::TYPE_CROSSBOW_RETURN,
		self::TYPE_ATTACKBASE_GOTO		=>	self::TYPE_ATTACKBASE_RETURN,
        self::TYPE_CITYSPY_GOTO         => self::TYPE_CITYSPY_RETURN,
        self::TYPE_CATAPULTSPY_GOTO     => self::TYPE_CATAPULTSPY_RETURN,
	);
	
    public function afterSave(){
		//$this->clearDataCache();
    }
	
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = isset($this->rowversion) ? $this->rowversion+1 : 1;
	}
    /**
     * 返回一条ppqilu
     * @param  int $id 
     * @return array     
     */
    public function getById($id){
        $ppq = self::findFirst($id);
        if($ppq) {
            return $ppq->toArray();
        }
        return null;
    }

    /**
     * 通过id获取玩家计划列表信息
     *
     * @return $ret array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $ret = self::find("player_id={$playerId} and battle_id={$this->battleId} and status=1")->toArray();
        if($forDataFlag) {
            $ret = filterFields($ret, $forDataFlag, $this->blacklist);
        }
		$ret = $this->afterFindQueue($ret);
		return $ret;
    }
	
	public function afterFindQueue($data){
		$data = $this->adapter($data);
		foreach($data as &$_r){
			if(@$_r['target_info'])
				$_r['target_info'] = json_decode($_r['target_info'], true);
			if(@$_r['accelerate_info'])
				$_r['accelerate_info'] = json_decode($_r['accelerate_info'], true);
			if(@$_r['carry_soldier'])
				$_r['carry_soldier'] = json_decode($_r['carry_soldier'], true);
			if(@$_r['carry_item'])
				$_r['carry_item'] = json_decode($_r['carry_item'], true);
		}
		unset($_r);
		return $data;
	}

    /**
     * 添加新的队列数据
     * 
     * @param playerId int 玩家
     * @param targetPlayerId int 目标玩家
     * @param type int 队列类型
     * @param needTime int 需要时间
     * @param targetInfo array 目标信息
     * @param extraData array 额外信息
     */
    public function addQueue($playerId, $guildId, $targetPlayerId, $type, $needTime, $armyId=0, $targetInfo=[], $extraData=[]){
		$accelerate_info = [];
		if(!is_array($needTime)){
			$accelerate_info['second'] = $needTime;
		}else{
			$accelerate_info['second'] = max(0, strtotime($needTime['end_time']) - strtotime($needTime['create_time']));
		}
        $self = new self;
		$self->battle_id 		= $this->battleId;
        $self->player_id        = $playerId;
        $self->target_player_id = $targetPlayerId;
        $self->type             = $type;
        $self->target_info      = json_encode($targetInfo);
        $self->status           = 1;
		if(is_array($needTime)){
			$self->create_time      = $needTime['create_time'];
			$self->end_time         = $needTime['end_time'];
		}else{
			$self->create_time      = date("Y-m-d H:i:s");
			$self->end_time         = date("Y-m-d H:i:s", time()+$needTime);
		}
		$self->update_time      = date("Y-m-d H:i:s");
		$self->accelerate_info = json_encode($accelerate_info);
		$self->army_id = $armyId;
		if(false !== $guildId){
			$self->guild_id = $guildId;
		}else{
			$Player = new CrossPlayer;
			$player = $Player->getByPlayerId($playerId);
			$self->guild_id = $player['guild_id'];
		}
        if(array_key_exists('from_map_id', $extraData)) {
            $self->from_map_id = $extraData['from_map_id'];
            $self->from_x      = $extraData['from_x'];
            $self->from_y      = $extraData['from_y'];
			//$mapConfigType = (new CrossBattle)->getByBattleId($this->battleId)['map_type'];//todo
			//$self->area = (new CrossMapConfig)->getAreaByXy($mapConfigType, $extraData['from_x'], $extraData['from_y']);
			$self->area = $extraData['area'];
        }
        if(array_key_exists('to_map_id', $extraData)) {
            $self->to_map_id = $extraData['to_map_id'];
            $self->to_x = $extraData['to_x'];
            $self->to_y = $extraData['to_y'];
        }
		if(array_key_exists('parent_queue_id', $extraData)) {
            $self->parent_queue_id = $extraData['parent_queue_id'];
        }
		if(array_key_exists('carry_gold', $extraData)) {
            $self->carry_gold = $extraData['carry_gold'];
        }
		if(array_key_exists('carry_food', $extraData)) {
            $self->carry_food = $extraData['carry_food'];
        }
		if(array_key_exists('carry_wood', $extraData)) {
            $self->carry_wood = $extraData['carry_wood'];
        }
		if(array_key_exists('carry_stone', $extraData)) {
            $self->carry_stone = $extraData['carry_stone'];
        }
		if(array_key_exists('carry_iron', $extraData)) {
            $self->carry_iron = $extraData['carry_iron'];
        }
		if(array_key_exists('carry_item', $extraData)) {
			if($extraData['carry_item']){
				$self->carry_item = json_encode((array)$extraData['carry_item']);
			}else{
				$self->carry_item = json_encode([]);
			}
        }else{
			$self->carry_item = json_encode([]);
		}
		if(array_key_exists('carry_soldier', $extraData)) {
			if($extraData['carry_soldier']){
				$self->carry_soldier = json_encode((array)$extraData['carry_soldier']);
			}else{
				$self->carry_soldier = json_encode([]);
			}
        }else{
			$self->carry_soldier = json_encode([]);
		}

		$this->rowversion = 1;
        $self->save();
		$this->id = $self->id;
        return $self->id;
    }


    /**
     * 取消进行中的队列
     * 
     * @param playerId int 玩家
     * @param id int 记录编号
     *
     * @return boolean 是否成功 
     */
    public function cancelQueue($playerId, $id, $battle=0){
        $re = self::findFirst($id);
		if(!$re)
			return false;
		
        if($re->player_id==$playerId/* && strtotime($re->end_time)>time()*/){
            $ret = $this->updateAll(['status'=>3, 'update_time'=>"'".date("Y-m-d H:i:s")."'", 'battle'=>$battle, 'rowversion'=>'rowversion*1+1'], ['id'=>$id, 'status'=>1]);
            $this->clearDataCache($playerId);//清缓存
            return $ret;
        }else{
            return false;
        }
    }


    /**
     * 标记队列完成
     * 
     * @param  playerId int 玩家
     * @param  id int 记录编号 
     * 
     * @return boolean 是否成功 
     */
    public function finishQueue($playerId, $id, $battle=0){
        $re = self::findFirst($id);
		if(!$re)
			return false;
        if($re->player_id==$playerId /* && strtotime($re->end_time)<=time()*/){
            $ret = $this->updateAll(['status'=>2, 'update_time'=>"'".date("Y-m-d H:i:s")."'", 'battle'=>$battle, 'rowversion'=>'rowversion*1+1'], ['id'=>$id, 'status'=>1]);
            $this->clearDataCache($playerId);//清缓存
            return $ret;
        }else{
            return false;
        }
    }
	
	public function updateQueue($needTime, $targetInfo=[], $extraData=[]){
		$updateData = array();
		if($targetInfo !== false){
			$updateData['target_info']      = json_encode($targetInfo);
		}
		if(is_array($needTime)){
			if(@$needTime['end_time']){
				$updateData['end_time']     =  $needTime['end_time'];
			}
		}elseif($needTime !== false){
			$updateData['end_time']         =  date("Y-m-d H:i:s", (is_numeric($this->create_time) ? $this->create_time : strtotime($this->create_time))+$needTime);
		}
		$updateData['update_time']      = date("Y-m-d H:i:s");
		$updateData['rowversion']      = $this->rowversion+1;
		if($extraData){
			if(array_key_exists('carry_gold', $extraData)) {
				$updateData['carry_gold'] = $extraData['carry_gold'];
			}
			if(array_key_exists('carry_food', $extraData)) {
				$updateData['carry_food'] = $extraData['carry_food'];
			}
			if(array_key_exists('carry_wood', $extraData)) {
				$updateData['carry_wood'] = $extraData['carry_wood'];
			}
			if(array_key_exists('carry_stone', $extraData)) {
				$updateData['carry_stone'] = $extraData['carry_stone'];
			}
			if(array_key_exists('carry_iron', $extraData)) {
				$updateData['carry_iron'] = $extraData['carry_iron'];
			}
			if(array_key_exists('carry_item', $extraData)) {
				if($extraData['carry_item']){
					$updateData['carry_item'] = json_encode((array)$extraData['carry_item']);
				}else{
					$updateData['carry_item'] = json_encode([]);
				}
			}
			if(array_key_exists('carry_soldier', $extraData)) {
				if($extraData['carry_soldier']){
					$updateData['carry_soldier'] = json_encode((array)$extraData['carry_soldier']);
				}else{
					$updateData['carry_soldier'] = json_encode([]);
				}
			}
		}
		if(@$updateData['end_time']){
			$accelerate_info['second'] = strtotime($updateData['end_time']) - strtotime($this->create_time);
			$updateData['accelerate_info'] = json_encode($accelerate_info);
		}

		$ret = $this->saveAll($updateData, "id={$this->id} and rowversion='{$this->rowversion}'");
		//$this->clearDataCache();
		return $ret;
	}
	
	public function mergeExtraInfo($ppq, $extra){
		$ret = ['carry_gold'=>@$ppq['carry_gold'], 'carry_food'=>@$ppq['carry_food'], 'carry_wood'=>@$ppq['carry_wood'], 'carry_stone'=>@$ppq['carry_stone'], 'carry_iron'=>@$ppq['carry_iron'], 'carry_soldier'=>@$ppq['carry_soldier'], 'carry_item'=>@$ppq['carry_item']];
		if(!is_array($extra))
			return $ret;
		foreach($extra as $_k=>$_d){
			if(is_array($_d)){
				if($_k == 'carry_soldier'){
					foreach($_d as $__k => $__d){
						@$ret[$_k][$__k] += $__d;
					}
				}elseif($_k == 'carry_item'){
					foreach($_d as $__k => $__d){
						if($ret[$_k]){
							$_findFlag = false;
							foreach($ret[$_k] as $_retk => &$_retv){
								if($_retv[0] == $__d[0] && $_retv[1] == $__d[1]){
									$_retv[2] += $__d[2];
									$_findFlag = true;
									break;
								}
							}
							unset($_retv);
							if(!$_findFlag){
								$ret[$_k][] = $__d;
							}
						}else{
							$ret[$_k][] = $__d;
						}
					}
				}
			}else{
				@$ret[$_k] += $_d;
			}
		}
		return $ret;
	}
	
	public function updateEndtime($endtime, $backNow = false, $option=[]){
		$condition = array(
			'end_time'=>$endtime,
			'update_time'=>date("Y-m-d H:i:s"),
			'rowversion'      => $this->rowversion+1,
		);
		if($backNow){
			$targetInfo = json_decode($this->target_info, true);
			$targetInfo['backNow'] = 1;
			if(@$option['playerCallBack']){
				$targetInfo['playerCallBack'] = 1;
			}
			$condition['target_info'] = json_encode($targetInfo);
		}
		$ret = $this->saveAll($condition, "id={$this->id} and rowversion='{$this->rowversion}'");
		//$this->clearDataCache();
		return $ret;
	}
	
	public function updateAcce($endtime, $accelerateInfo){
		$ret = $this->saveAll(array(
			'end_time'=>$endtime,
			'accelerate_info'=>json_encode($accelerateInfo),
			'update_time'=>date("Y-m-d H:i:s"),
			'rowversion'      => $this->rowversion+1,
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		//$this->clearDataCache();
		return $ret;
	}

    /**
     * 获取行军时间
     * 
     * @param <type> $playerId 
     * @param <type> $fromX 
     * @param <type> $fromY 
     * @param <type> $toX 
     * @param <type> $toY 
     * @param <type> $type 行军种类：1.采集，2.打怪，3.出征，4.侦查/获取物品，5.搬运资源,6.集结
     * @param <type> $soldierSpeed  
     * 
     * @return <type>
     */
	public static function calculateMoveTime($battleId, $playerId, $fromX, $fromY, $toX, $toY, $type, $armyId=0, $debuff=0, $buff=0){
        /*$buffType[] = 'march_speed';
		switch($type){
			case 1://采集
				$k = 0.015;//速度基数
				$buffType[] = 'moving_speed';
			break;
			case 2://打怪
				$k = 0.015;//速度基数
				$buffType[] = 'move_to_npc_speed';
			break;
			case 6:
				$buffType[] = 'aggregation_legion_march_speed';
			//no break;
			case 3://出征
				$k = 0.0125*3;//速度基数
			break;
			case 4://侦查
				$k = 0.04;//速度基数
            break;
			case 5://搬运资源
				$k = 0.02;//搬运资源
			break;
			default:
				return false;
		}*/
		$k = 0.0125*3;//速度基数
		//获取行军加成buff
		/*if($buffType){
			$speedBuff = 0;
			$PlayerBuff = new PlayerBuff;
			foreach($buffType as $_b){
				$speedBuff += $PlayerBuff->getPlayerBuff($playerId, $_b);
			}
		}else{
			$speedBuff = 0;
		}
		*/
		$speedBuff = 0;
		//敌人侦查速度降低
		/*if($type == 4){
			//获取目标
			$map = (new Map)->getByXy($toX, $toY);
			if($map && $map['map_element_origin_id'] == 15){//判断是否是玩家城堡
				//判断目标玩家buff
				$speedDebuff = (new PlayerBuff)->getPlayerBuff($map['player_id'], 'inspection_speed_reduce');
			}
		}
		*/
		
		//计算距离
		$distance = sqrt(pow($fromX - $toX, 2) + pow($fromY - $toY, 2));
		
		if(!in_array($type, [4, 5])){
			if(!$armyId)
				return false;
			//获取兵种最慢移动速度
			$Soldier = new Soldier;
			$PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			$pau = $PlayerArmyUnit->getByArmyId($playerId, $armyId);
			$slowSpeed = false;//最慢行军速度
			foreach($pau as $_pau){
				if($_pau['soldier_num'] > 0){
					$_soldier = $Soldier->dicGetOne($_pau['soldier_id']);
					if(!$_soldier){
						return false;
					}
					$_speed = $_soldier['speed'];
					if(!$slowSpeed || $_speed < $slowSpeed){
						$slowSpeed = $_speed;
					}
				}
			}
			if(!$slowSpeed){
				//$slowSpeed = 5;
				return 1;
			}
		}else{
			$slowSpeed = 12;
		}
		
		//足智多谋:本方所有部队行军速度增加|<#0,255,0#>%{num}|%
		$CrossGuild = new CrossGuild;
		$CrossGuild->battleId = $battleId;
		$speedBuff += $CrossGuild->getByPlayerId($playerId)['buff_move'];
		
		$speedBuff -= $debuff;
		
		$speedBuff += $buff;
		
		//计算行军时间
		//return floor($distance / ($slowSpeed * (1+$speedBuff-$speedDebuff) * $k));
		return floor($distance / ($slowSpeed * $k) / (1 + $speedBuff));
	}
	
    
    /**
     * 召回队伍
     * 
     * @param <type> $id 
     * @param <type> $option 
     * 
     * @return <type>
     */
	public function callbackQueue($id, $toX, $toY, $option=[]){
		global $inDispWorker;
		$perTry = 1;
		$tryLimit = 5;
		$i = 0;
		$DispatcherTask = new CrossDispatcherTask;
		if(!@$inDispWorker){
			while(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $toX, $toY)){
				sleep($perTry);
				$i++;
				if($i >= $tryLimit){
					return false;
				}
			}
		}
		
		$ppq = $this->findFirst($id);
		if(!$ppq || $ppq->status != 1){
			if(!@$inDispWorker)
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
			return false;
		}
		
		$clearXyCache = true;
		if($ppq->type == self::TYPE_LADDER_ING && @$option['ladder']){
			$returnType = $this->stayTypes[$ppq->type];
			$this->battleId = $ppq->battle_id;
			
			//获取我的主城位置
			$Player = new CrossPlayer;
			$Player->battleId = $ppq->battle_id;
			$player = $Player->getByPlayerId($ppq->player_id);
			if(!$player){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			$extraData = [
				'area'		=> $ppq->area,
				'from_map_id' => $ppq->to_map_id,
				'from_x' => $ppq->to_x,
				'from_y' => $ppq->to_y,
				'to_map_id' => $player['map_id'],
				'to_x' => $player['x'],
				'to_y' => $player['y'],
			];
			
			//撤销原有队列
			if(!$this->cancelQueue($ppq->player_id, $ppq->id)){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//更新占领人数
			$Map = new CrossMap;
			$Map->alter($ppq->to_map_id, ['player_num'=>'player_num-1']);
			
			//新增回家队列
			$targetInfo = [];
			if(@$option['rightnow']){
				$now = time();
				$createTime = date('Y-m-d H:i:s', $now);
				$endTime = date('Y-m-d H:i:s', $now);
				$needTime = ['create_time'=>$createTime, 'end_time'=>$endTime];
				$targetInfo['backNow'] = 1;
			}else{
				$needTime = CrossPlayerProjectQueue::calculateMoveTime($ppq->battle_id, $ppq->player_id, $extraData['from_x'], $extraData['from_y'], $extraData['to_x'], $extraData['to_y'], 3, $ppq->army_id);
			}
			$newQueueId = $this->addQueue($ppq->player_id, $ppq->guild_id, 0, $returnType, $needTime, $ppq->army_id, $targetInfo, $extraData);
			if(!$newQueueId){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//如果最后一个撤离队伍
			$condition = ['type='.$ppq->type.' and battle_id='.$ppq->battle_id.' and area='.$ppq->area.' and to_map_id='.$ppq->to_map_id.' and status=1'];
			$ppqs = CrossPlayerProjectQueue::find($condition)->toArray();
			if(!$ppqs || (count($ppqs) == 1 && $ppqs[0]['id'] == $ppq->id)){
				$Map = new CrossMap;
				$map = $Map->getByXy($ppq->battle_id, $ppq->to_x, $ppq->to_y);
				if($map){
					if(!$Map->alter($map['id'], ['guild_id'=>0])){
						if(!@$inDispWorker)
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
						return false;
					}
				}
			}
		}elseif(in_array($ppq->type, array_keys($this->stayTypes))){
			if(!@$inDispWorker)
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
			$clearXyCache = false;
			//获取返回type
			$returnType = $this->stayTypes[$ppq->type];
			
			//修改结束时间
			if(!$ppq->updateEndtime(date('Y-m-d H:i:s'), @$option['rightnow'], ['playerCallBack'=>@$option['playerCallBack']])){
				return false;
			}

			//检查是否处理完成
			if(@$option['waitStayQ']){
				while($this->findFirst(['id='.$id.' and status=1'])){
					sleep($perTry);
					$i++;
					if($i >= $tryLimit){
						return false;
					}
				}
			}

		}elseif(in_array($ppq->type, array_keys($this->moveTypes))){
			
			//获取返回type
			$returnType = $this->moveTypes[$ppq->type];
			
			$now = time();
			
			$targetInfo = [];
			if(@$option['rightnow']){
				$createTime = date('Y-m-d H:i:s', $now);
				$endTime = date('Y-m-d H:i:s', $now);
				$targetInfo['backNow'] = 1;
			}else{
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
					if(!$immediat){
						$usedTime += ($now - $_time) * $v;
					}
				}
				
				$restTime = $ppq->accelerate_info['second'] - $usedTime;
				//计算剩余时间
				$createTime = date('Y-m-d H:i:s', $now - $restTime);
				$endTime = date('Y-m-d H:i:s', $now + $usedTime);
			}
			
			$extraData = [
				'area'		=> $ppq->area,
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
				'carry_item' => $ppq->carry_item,
			];
			
			//撤销原有队列
			if(!$this->cancelQueue($ppq->player_id, $ppq->id)){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			//新增回家队列
			$newQueueId = $this->addQueue($ppq->player_id, $ppq->guild_id, 0, $returnType, ['create_time'=>$createTime, 'end_time'=>$endTime], $ppq->army_id, $targetInfo, $extraData);
			if(!$newQueueId){
				if(!@$inDispWorker)
					$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
				return false;
			}
			
			if(in_array($ppq->type, [self::TYPE_CITYBATTLE_GOTO])){
				crossSocketSend(CrossPlayer::parsePlayerId($ppq->target_player_id)['server_id'], ['Type'=>'cross_cancelattacked', 'Data'=>['playerId'=>[$ppq->target_player_id]]]);//todo
			}
			
		}
		
		if($clearXyCache){
			if(!@$inDispWorker)
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'Cross'), $this->battleId, $ppq->to_x, $ppq->to_y);
		}
		
		return true;
	}
	
	public function callbackPlayerQueue($battleId, $playerId){
		$condition = ['battle_id='.$battleId.' and player_id='.$playerId.' and status=1'];
		$ppqs = self::find($condition);
		foreach($ppqs as $_ppq){
			if(in_array($_ppq->type, array_merge($this->moveTypes, $this->stayTypes))){//如果是回城队列，修改结束时间
				$_ppq->updateEndtime(date('Y-m-d H:i:s'), true);
			}else{
				$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y, ['rightnow'=>true]);
				(new CrossCommonLog)->add($battleId, $_ppq->player_id, $_ppq->guild_id, '部队遣返[queueId='.$_ppq->id.']');
			}
		}
		return true;
	}

	public function callbackCatapult($battleId, $area, $guildId=0, $stopIfFail=false, &$playerIds=[]){
		$playerIds = [];
		$condition = ['type='.self::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and area='.$area.' and status=1'.($guildId ? ' and guild_id='.$guildId : '')];
		$ppqs = self::find($condition);
		foreach($ppqs as $_ppq){
			$_ret = $this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y);
			if($stopIfFail && !$_ret){
				return false;
			}
			(new CrossCommonLog)->add($battleId, $_ppq->player_id, $_ppq->guild_id, '投石车部队遣返[queueId='.$_ppq->id.']');
			$playerIds[] = $_ppq->player_id;
		}
		$playerIds = array_unique($playerIds);
		return true;
	}
	
	public function callbackCrossbow($battleId, $area, $stopIfFail=false, &$playerIds=[]){
		$playerIds = [];
		$condition = ['type='.self::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and area='.$area.' and status=1'];
		$ppqs = self::find($condition);
		foreach($ppqs as $_ppq){
			$_ret = $this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y);
			if($stopIfFail && !$_ret){
				return false;
			}
			(new CrossCommonLog)->add($battleId, $_ppq->player_id, $_ppq->guild_id, '床弩部队遣返[queueId='.$_ppq->id.']');
			$playerIds[] = $_ppq->player_id;
		}
		$playerIds = array_unique($playerIds);
		return true;
	}
	
	public function callbackHammer($battleId, $area, $mapId=0){
		$condition = ['type='.self::TYPE_HAMMER_ING.' and battle_id='.$battleId.' and area='.$area.($mapId ? ' and to_map_id='.$mapId : '').' and status=1'];
		$ppqs = self::find($condition);
		foreach($ppqs as $_ppq){
			$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y);
			(new CrossCommonLog)->add($battleId, $_ppq->player_id, $_ppq->guild_id, '攻城锤部队遣返[queueId='.$_ppq->id.']');
		}
		return true;
	}
	
	public function callbackLadder($battleId, $mapId){
		$condition = ['type='.self::TYPE_LADDER_ING.' and battle_id='.$battleId.' and to_map_id='.$mapId.' and status=1'];
		$ppqs = self::find($condition);
		foreach($ppqs as $_ppq){
			$this->callbackQueue($_ppq->id, $_ppq->to_x, $_ppq->to_y);
			(new CrossCommonLog)->add($battleId, $_ppq->player_id, $_ppq->guild_id, '云梯部队遣返[queueId='.$_ppq->id.']');
		}
		return true;
	}

	public function getPlayerUseQueueNum($battleId, $playerId){
        $condition = ['player_id='.$playerId.' and battle_id='.$battleId.' and status=1'];
        $ppqs = self::find($condition);
        if(empty($ppqs)){
            return 0;
        }else{
            $ppqs = $ppqs->toArray();
            return count($ppqs);
        }
    }
}