<?php
/**
 * 新手目标 玩家表
 */
class PlayerTarget extends ModelBase{
    public $blacklist   = ['create_time', 'update_time'];
    const TARGET_NUMBER = 4;
    const EQUIP_BLUE    = 3;
    /**
     * 时效判断
     * @param  int $playerId 
     */
    public function beforeFilter($playerId){
        $PlayerInfo = new PlayerInfo;
        $playerInfo = $PlayerInfo->getByPlayerId($playerId);
        if(!$playerInfo['target_end_time'] || $playerInfo['target_end_time']>=time()) return true;
        return false;
    }
    /**
     * 新建一条记录
     * @param  $data 
     */
    public function addNew($data){
        $self                = new self;
        $playerId            =  $self->player_id    = $data['player_id'];

        if(!$this->beforeFilter($playerId)) return false;
        if($data['target_id']==0) return false;
        $self->target_id     = $data['target_id'];
        $self->current_value = 0;
        $self->target_type   = $data['target_type'];
        $targetValue         = $self->target_value = $data['target_value'];
        $self->date_start    = date('Y-m-d H:i:s');
        $self->date_end      = date('Y-m-d H:i:s', time()+$data['time']);
        $self->create_time   = $self->update_time = date('Y-m-d H:i:s');
        if(isset($data['position'])) {
            $self->position      = $data['position'];
        }
        $self->rowversion    = uniqid();
        //接受新手任务的时候就需要判断，在此处
        switch($data['target_type']) {
            case 1://府衙等级
                $PlayerBuild         = new PlayerBuild;
                $castleLevel         = $PlayerBuild->getPlayerCastleLevel($playerId);
                $self->current_value = min($castleLevel, $targetValue);
                break;
            case 3://主公等级
                $Player              = new Player;
                $player              = $Player->getByPlayerId($playerId);
                $self->current_value = min($player['level'], $targetValue);
                break;
            case 4://VIP等级
				$Player              = new Player;
                $player              = $Player->getByPlayerId($playerId);
				$self->current_value = min($player['vip_level'], $targetValue);
                break;
            case 5://拥有武将个数
				$PlayerGeneral = new PlayerGeneral;
				$ids = $PlayerGeneral->getGeneralIds($playerId);
				$self->current_value = min(count($ids), $targetValue);
                break;
            case 7://最高战力
                $Player              = new Player;
                $player              = $Player->getByPlayerId($playerId);
                $self->current_value = min($player['power'], $targetValue);
                break;
			case 8://击杀野怪次数
				/*$Player              = new Player;
                $player              = $Player->getByPlayerId($playerId);
				$self->current_value = $player['monster_kill_counter'];*/
				break;
            case 9://击杀最高野怪等级
				$Player              = new Player;
                $player              = $Player->getByPlayerId($playerId);
				$self->current_value = min($player['monster_lv'], $targetValue);
                break;
            case 14://拥有蓝装数量
				$num = $this->getBlueEquipNum($playerId);
				$self->current_value = min($num, $targetValue);
                break;
				
            case 17://穿戴宝物数量
                $re = (new PlayerEquipMaster)->getByPlayerId($playerId);
                foreach($re as $k=>$v) {
                    if($v['status']==1) {
                        $self->current_value++;
                    }
                }
                break;
			case 20://装备最高进阶数（拥有即视为已完成）	
            case 29:
			case 30:
			case 31:
			case 32:
				$ar = [20=>1, 29=>2, 30=>3, 31=>4, 32=>5];
				$num = $this->getMaxStarEquipNum($playerId, $ar[$data['target_type']]);
				$self->current_value = min($num, $targetValue);
                break;
            case 33://加入公会
                $Player  = new Player;
                $player  = $Player->getByPlayerId($playerId);
                $guildId = $player['guild_id'];
                if($guildId>0) {
                   $self->current_value = 1; 
                }
                break;
        }

        $self->save();
        $this->clearDataCache($data['player_id']);
		socketSend(['Type'=>'player_target', 'Data'=>['playerId'=>$playerId]]);
        return $self->id;
    }
    /**
     * 初始化新手目标
     */
    private function initTarget($playerId){
        if(!$this->beforeFilter($playerId)) return [];

        $Target = new Target;
        $target = $Target->dicGetOne(1);
        $startNum = self::TARGET_NUMBER;
        $i = 1;
        while($target['id']<=$startNum) {
            $data                 = [];
            $data['player_id']    = $playerId;
            $data['target_id']    = $target['id'];
            $data['target_type']  = $target['type'];
            $data['target_value'] = $target['target_value'];
            $data['time']         = $target['time'];
            $data['position']     = $i++;
            $this->addNew($data);
            $target               = $Target->dicGetOne($target['next_target_id']);
        }
        (new PlayerInfo)->alter($playerId, ['target_end_time'=>date('Y-m-d H:i:s', time()+7*24*60*60)]);
    }
    /**
     * 是否存在未完成的任务
     * @param  int  $playerId    
     * @param  array   $targetTypes 
     * @return boolean              
     */
    public function isTargetTypeExists($playerId, array $targetTypes){
        $all = $this->getByPlayerId($playerId);
        foreach($all as $v) {
            if(in_array($v['target_type'], $targetTypes) && $v['current_value']<$v['target_value'] && $v['award_status']==0) {
                return true;
            }
        }
        return false;
    }
    /**
     * 获取玩家的新手目标
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        if(!$this->beforeFilter($playerId)) return [];
        $Target = new Target;

        FetchData:
        $r = Cache::getPlayer($playerId, __CLASS__);
        if(!$r) {
            $re = self::find("player_id={$playerId} and award_status=0 and date_end>=now()");
            $r  = $re->toArray();

            if(!$r) {//如果为空
                $lastTarget   = $Target->getLast();
                $lastTargetId = $lastTarget['id'];
                $lastRe       = self::findFirst("player_id={$playerId} and target_id={$lastTargetId}");
                if($lastRe) {//最后一个新手目标已经完成
                    return []; 
                }
            } else {
                $r = $this->adapter($r);
                $r = filterFields($r, $forDataFlag, $this->blacklist);
                Cache::setPlayer($playerId, __CLASS__, $r);
            }
        }
        //验证时间是否到了
        foreach($r as $k=>$v) {
            if($v['date_end']<time()) {
                $this->clearDataCache($playerId);
                goto FetchData;
                break;
            }
        }
        $length    = count($r);
        $position  = Set::extract($r, '/position');
        $originPos = [1,2,3,4];
        $subPos    = array_diff($originPos, $position);
        if($length<self::TARGET_NUMBER) {//不足则补全
            //case a: 获取最大的那个
            $last = self::findFirst(["player_id={$playerId}", 'order'=>'target_id desc', 'limit'=>1]);
            if($last) {
                $lastPlayerTargetId = $last->target_id;

                if($lastPlayerTargetId) {
                    $target               = $Target->dicGetOne($lastPlayerTargetId);
                    if($target['next_target_id']) {
                        $nextTarget         = $Target->dicGetOne($target['next_target_id']);

                        $startTimestamp     = (new Configure)->getValueByKey('server_start_time');
                        $startTimestampZero = strtotime(date('Y-m-d 00:00:00', $startTimestamp));
                        $subDay             = ceil((time()-$startTimestampZero)/(24*60*60));

                        //jira2863 1.关羽任务增加开启天数字段open_time，根据服务器开启时间分批开启。
                        if($nextTarget['open_time']<=$subDay) {//判断服务器开启时间
                            $data                 = [];
                            $data['player_id']    = $playerId;
                            $data['target_id']    = $nextTarget['id'];
                            $data['target_type']  = $nextTarget['type'];
                            $data['target_value'] = $nextTarget['target_value'];
                            $data['time']         = $nextTarget['time'];
                            $data['position']     = array_pop($subPos);
                            $this->addNew($data);
                            goto FetchData;
                        }
                    }
                }
            } else {//尚未开始
                $this->initTarget($playerId);
                goto FetchData;
            }
        }
        return $r;
    }
    /**
     * 获取奖励
     */
    public function getTargetAward($playerId, $currentPlayerTargetId){
        if(!$this->beforeFilter($playerId)) return [];

        $Target = new Target;
        $Drop   = new Drop;
        $re     = self::findFirst("id={$currentPlayerTargetId} and current_value>=target_value and award_status=0 and date_end>=now()");//获取该条满足要求的记录
        if($re) {//满足要求
            $targetId = $re->target_id;
            $target = $Target->dicGetOne($targetId);
            if($this->updateAll(['award_status'=>1, 'update_time'=>qd()], ['id'=>$currentPlayerTargetId, 'award_status'=>0])) {
                $this->clearDataCache($playerId);
                //jira2863 2.关羽任务增加后进玩家补偿机制，根据玩家进入服务器的天数读取不同的奖励drop，第二天进入服务器的玩家读取drop_2，以此类推
                //判断服务器开启时间
                $startTimestamp     = (new Configure)->getValueByKey('server_start_time');
                $startTimestampZero = strtotime(date('Y-m-d 00:00:00', $startTimestamp));
                $subDay             = ceil((time()-$startTimestampZero)/(24*60*60));
                if($subDay<=7) {
                    $targetDropField = [1=>'drop',2=>'drop_2',3=>'drop_3',4=>'drop_4',5=>'drop_5',6=>'drop_6',7=>'drop_7'];
                    $targetDropField = $targetDropField[$subDay];
                } else {
                    $targetDropField = 'drop_7';
                }
                //领奖
                $dropIds = parseArray($target[$targetDropField]);
                $Drop->gain($playerId, $dropIds, 1, '新手目标');

                //新建记录
                $last = self::findFirst(["player_id={$playerId}", 'order'=>'target_id desc', 'limit'=>1]);
                $count = self::count("player_id={$playerId} and award_status=0 and date_end>=now()");
                if($last && $count<self::TARGET_NUMBER) {
                    $lastPlayerTargetId = $last->target_id;
                    if($lastPlayerTargetId) {
                        $target = $Target->dicGetOne($lastPlayerTargetId);
                        if($target['next_target_id']) {
                            $nextTarget = $Target->dicGetOne($target['next_target_id']);
                            if($nextTarget['open_time']<=$subDay) {// jira2863 1.关羽任务增加开启天数字段open_time，根据服务器开启时间分批开启。
                                $data                 = [];
                                $data['player_id']    = $playerId;
                                $data['target_id']    = $nextTarget['id'];
                                $data['target_type']  = $nextTarget['type'];
                                $data['target_value'] = $nextTarget['target_value'];
                                $data['time']         = $nextTarget['time'];
                                $data['position']     = $re->position;
                                $id                   = $this->addNew($data);
                                return $this->adapter(self::findFirst($id)->toArray(), true);
                            }
                        } else {
                            return -1;
                        }
                    }
                }
                socketSend(['Type'=>'player_target', 'Data'=>['playerId'=>$playerId]]);//以防万一
                return 1;
            }
        }
        return 0;//未达到领奖要求
    }
    /*
     * 1- 府衙等级
     * 2- 从资源田获得资源
     * 3- 主公等级
     * 4- VIP等级
     * 5- 拥有武将数量
     * 6- 建筑升级次数
     * 7- 最高战力
     * 8- 击杀野怪次数
     * 9- 击杀最高野怪等级
     * 10-出征加速次数
     * 11-采集资源量
     * 12-训练士兵数
     * 13-科技研发次数
     * 14-拥有蓝装数量
     * 15-抢夺采集资源量
     * 16-主动技能使用次数
     * 17-穿戴宝物数量
     * 18-分解白银数
     * 19-装备进阶次数
     * 20-装备最高进阶数（拥有即视为已完成）
     * 21-联盟捐献次数
     * 22-花费个人荣誉
     * 23-联盟帮助次数
     * 24-侦查次数
     * 25-攻城次数
     * 26-治疗兵数
     * 27-陷阱制造数
     * 28-攻城掠夺资源数
     *
     * 使用如下：
     *
     * ```php
     * (new PlayerTarget)->updateTargetCurrentValue($playerId, $targetType, $targetValue);
     * ```
     */
    public function updateTargetCurrentValue($playerId, $targetType, $targetValue=1, $incFlag=true){
        if(!$this->beforeFilter($playerId)) return false;
        if(!self::findFirst("player_id={$playerId}")) {//未有数据
            return false;
        }
        switch($targetType) {
            case 1:  //1-府衙等级
            case 2:  //2-从资源田获得资源
            case 3:  //3-主公等级
            case 4:  //4-VIP等级
            case 5:  //5-拥有武将数量
            case 6:  //6-建筑升级次数
            case 7:  //7-最高战力
            case 8:  //8-击杀野怪次数
            case 9:  //9-击杀最高野怪等级
            case 10: //10-出征加速次数
            case 11: //11-采集资源量
            case 12: //12-训练士兵数
            case 13: //13-科技研发次数
            case 14: //14-拥有蓝装数量
            case 15: //15-抢夺采集资源量
            case 16: //16-主动技能使用次数
            case 17: //17-穿戴宝物数量
            case 18: //18-分解白银数
            case 19: //19-装备进阶次数
            case 20: //20-装备最高进阶数+1（拥有即视为已完成）
            case 21: //21-联盟捐献次数
            case 22: //22-花费个人荣誉
            case 23: //23-联盟帮助次数
            case 24: //24-侦查次数
            case 25: //25-攻城次数
            case 26: //26-治疗兵数
            case 27: //27-陷阱制造数
            case 28: //28-攻城掠夺资源数
			case 29: //20-装备最高进阶数+2（拥有即视为已完成）
			case 30: //20-装备最高进阶数+3（拥有即视为已完成）
			case 31: //20-装备最高进阶数+4（拥有即视为已完成）
			case 32: //20-装备最高进阶数+5（拥有即视为已完成）
            case 33: //33-加入公会（判断玩家当前是否已经加入公会，已有公会即视为已完成）
            $all = $this->getByPlayerId($playerId);
            $currentTarget = [];
            foreach($all as $k=>$v) {
                if($v['target_type']==$targetType) {
                    $currentTarget[] = $v;
                }
            }
            foreach($currentTarget as $k=>$v) {
                if($v['current_value']<$v['target_value']) {
                    $updateArr = ['update_time'=>qd(), 'rowversion'=>q(uniqid())];
                    $conditionArr = [
                                'id'           => $v['id'], 
                                'player_id'    => $v['player_id'],
                                'award_status' => 0,
                                'rowversion'   => q($v['rowversion'])
                                ];
                    if($incFlag) {//增量
                        $updateArr['current_value']      = "current_value+{$targetValue}";
                        $conditionArr['target_value  >'] = "current_value+{$targetValue}";
                    } else {//直接赋值
                        $updateArr['current_value']      = $targetValue;
                        $conditionArr['target_value  >'] = $targetValue;
                    }
                    $affectedRows = $this->updateAll($updateArr, $conditionArr);
                    if($affectedRows<1) {
                        $conditionArr2 = [
                                'id'           => $v['id'],
                                'player_id'    => $v['player_id'],
                                'award_status' => 0,
                                'rowversion'   => q($v['rowversion'])
                            ];
                        if($incFlag) {
                            $conditionArr2['target_value <='] = "current_value+{$targetValue}";
                        } else {
                            $conditionArr2['target_value <='] = $targetValue;
                        }
                        $this->updateAll(['current_value'=>"target_value", 'update_time'=>qd(), 'rowversion'=>q(uniqid())], $conditionArr2);
                    }
                    $this->clearDataCache($playerId);
                }
            }
            break;
        }
		socketSend(['Type'=>'player_target', 'Data'=>['playerId'=>$playerId]]);
        return true;
    }
	
    /**
     * 获取蓝装持有数量
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function getBlueEquipNum($playerId){
		$Equipment = new Equipment;
		//获取武将身上符合条件的装备数量
		$PlayerGeneral = new PlayerGeneral;
		$ret1 = $PlayerGeneral->sqlGet('select count(a.id) from '.$PlayerGeneral->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and a.weapon_id=b.id and b.quality_id>='.self::EQUIP_BLUE);
		$ret2 = $PlayerGeneral->sqlGet('select count(a.id) from '.$PlayerGeneral->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and a.armor_id=b.id and b.quality_id>='.self::EQUIP_BLUE);
		$ret3 = $PlayerGeneral->sqlGet('select count(a.id) from '.$PlayerGeneral->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and a.horse_id=b.id and b.quality_id>='.self::EQUIP_BLUE);
		//获取背包符合条件的装备数量
		$PlayerEquipment = new PlayerEquipment;
		$ret4 = $PlayerEquipment->sqlGet('select count(a.id) from '.$PlayerEquipment->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and a.item_id=b.id and b.quality_id>='.self::EQUIP_BLUE);
		return $ret1[0]['count(a.id)']+$ret2[0]['count(a.id)']+$ret3[0]['count(a.id)']+$ret4[0]['count(a.id)'];
	}
	
	public function refreshBlueEquipNum($playerId, $itemId=0){
		if(!$this->isTargetTypeExists($playerId, [14]))
			return;
		if($itemId){
			$Equipment = new Equipment;
			$item = $Equipment->dicGetOne($itemId);
		}
		if(!$itemId || $item['quality_id'] >= self::EQUIP_BLUE){
			$num = (new PlayerTarget)->getBlueEquipNum($playerId);
			$this->updateTargetCurrentValue($playerId, 14, $num, false);
		}
	}
	
	public function refreshGeneralNum($playerId){
		if(!$this->isTargetTypeExists($playerId, [5]))
			return;
		$PlayerGeneral = new PlayerGeneral;
		$ids = $PlayerGeneral->getGeneralIds($playerId);
		(new PlayerTarget)->updateTargetCurrentValue($playerId, 5, count($ids), false);
	}
	
    /**
     * 获取最高级装备持有数量(蓝色及以上)
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function getMaxStarEquipNum($playerId, $starLevel){
		$Equipment = new Equipment;
		//获取武将身上符合条件的装备数量
		$PlayerGeneral = new PlayerGeneral;
		$ret1 = $PlayerGeneral->sqlGet('select count(a.id) from '.$PlayerGeneral->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and a.weapon_id=b.id and b.quality_id >='.self::EQUIP_BLUE.' and b.star_level>='.$starLevel);
		$ret2 = $PlayerGeneral->sqlGet('select count(a.id) from '.$PlayerGeneral->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and a.armor_id=b.id and b.quality_id >='.self::EQUIP_BLUE.' and b.star_level>='.$starLevel);
		$ret3 = $PlayerGeneral->sqlGet('select count(a.id) from '.$PlayerGeneral->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and a.horse_id=b.id and b.quality_id >='.self::EQUIP_BLUE.' and b.star_level>='.$starLevel);
		//获取背包符合条件的装备数量
		$PlayerEquipment = new PlayerEquipment;
		$ret4 = $PlayerEquipment->sqlGet('select count(a.id) from '.$PlayerEquipment->getSource().' a, '.$Equipment->getSource().' b where a.player_id='.$playerId.' and b.quality_id >='.self::EQUIP_BLUE.' and a.item_id=b.id and b.star_level>='.$starLevel);
		return $ret1[0]['count(a.id)']+$ret2[0]['count(a.id)']+$ret3[0]['count(a.id)']+$ret4[0]['count(a.id)'];
	}
	
	public function refreshMaxStarEquipNum($playerId, $itemId=0){
		if(!$this->isTargetTypeExists($playerId, [20, 29, 30, 31, 32]))
			return;
		if($itemId){
			$Equipment = new Equipment;
			$item = $Equipment->dicGetOne($itemId);
		}
		/*if(!$itemId || $item['star_level'] == $item['max_star_level']){
			$num = (new PlayerTarget)->getMaxStarEquipNum($playerId);
			$this->updateTargetCurrentValue($playerId, 20, $num, false);
		}
		*/
		if(!$itemId || $item['star_level'] >= 5){
			$num = (new PlayerTarget)->getMaxStarEquipNum($playerId, 5);
			$this->updateTargetCurrentValue($playerId, 32, $num, false);
		}
		if(!$itemId || $item['star_level'] >= 4){
			$num = (new PlayerTarget)->getMaxStarEquipNum($playerId, 4);
			$this->updateTargetCurrentValue($playerId, 31, $num, false);
		}
		if(!$itemId || $item['star_level'] >= 3){
			$num = (new PlayerTarget)->getMaxStarEquipNum($playerId, 3);
			$this->updateTargetCurrentValue($playerId, 30, $num, false);
		}
		if(!$itemId || $item['star_level'] >= 2){
			$num = (new PlayerTarget)->getMaxStarEquipNum($playerId, 2);
			$this->updateTargetCurrentValue($playerId, 29, $num, false);
		}
		if(!$itemId || $item['star_level'] >= 1){
			$num = (new PlayerTarget)->getMaxStarEquipNum($playerId, 1);
			$this->updateTargetCurrentValue($playerId, 20, $num, false);
		}
	}
}