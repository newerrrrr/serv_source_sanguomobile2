<?php
/**
 * player_task 表 && 主线任务和每日任务逻辑
 */
class PlayerMission extends ModelBase{
    const START             = 0;//接任务
    const COMPLETE          = 1;//完成任务
    const FINISH            = 2;//领完奖，结束任务
    const TOTAL_FINISH      = 3;//所有主线任务做完
    const DAILY_EXPIRE      = 4;//每日任务过期失效
    const MAX_DAILY_MISSION = 5;//每日任务当天最大
    
    public $refreshPosition = false; //刷新任务的位置记录
    public $getFromDbFlag   = false;//重新读一次

    public static $mainMissionType = [1,21,22,/*23,24,*/25,26,27,28];//主线任务type
    
    public $blacklist       = ['create_time', 'update_time', 'memo'];
    /**
     * 获取mission_type字符串格式
     * @return string 
     */
    public static function getMainMissionTypeStr(){
        return join(',', self::$mainMissionType);
    }
    /**
     * 添加新记录
     * 
     * @param int $playerId 
     * @param array $data  
     */
    public function addNew($playerId, array $data){
        $self                         = new self;
        $self->player_id              = $playerId;
        $self->mission_type           = $data['mission_type'];
        $self->mission_id             = $data['mission_id'];
        $self->current_mission_number = 0;
        $self->max_mission_number     = $data['max_mission_number'];
        $self->position               = $data['position'];
        $self->date_limit             = date('Y-m-d');
        $self->create_time            = $self->update_time = date('Y-m-d H:i:s');
        if(isset($data['memo'])) {
            $self->memo = $data['memo'];
        }
        $self->save();
        $re                           = $self->toArray();
        $this->clearDataCache($playerId);
        return $re;
    }
    /**
     * 获取主线任务和每日任务
     * 
     * @param  int  $playerId    
     * @param  boolean $forDataFlag 
     * @return array               
     */
    public function getByPlayerId($playerId, $forDataFlag=false) {
        $mission = Cache::getPlayer($playerId, __CLASS__);
        if(!$mission) {
            $player  = (new Player)->getByPlayerId($playerId);
            $level   = $player['level'];
            $main    = $this->getAllMainMission($playerId);//主线
            $daily   = $this->getDailyMission($playerId, $level);//每日
            if($main && !isset($main['main_finish_flag'])) {
                $mission = array_merge($main, $daily);
            } else {
                $mission = $daily;
            }
            $mission = $this->adapter($mission);
            $mission = Set::sort($mission, '{n}.position', 'asc');
            Cache::setPlayer($playerId, __CLASS__, $mission);
        } elseif(!$this->getFromDbFlag) {//从缓存读的数据，需要检查时间
            //验证是否为当天每日任务
            foreach($mission as $k=>$v) {
                if($v['mission_type']!=1 && $v['date_limit']!=date('Y-m-d')) {//每日任务
                    $this->getFromDbFlag = true;
                    break;
                }
            }
            $this->clearDataCache($playerId);
            $mission = $this->getByPlayerId($playerId, $forDataFlag);
        }
        if($forDataFlag) {
            return filterFields($mission, $forDataFlag, $this->blacklist);
        } else {
            return $mission;
        }
    }
    /**
     * 根据id查找
     * @param  int $id 
     * @return array     
     */
    public function getById($playerId, $id){
        $all = $this->getByPlayerId($playerId);
        foreach($all as $k=>$v) {
            if($v['id']==$id) {
                return $v;
            }
        }
        return [];

    }
    /**
     * //第一个主线任务
     * @param  int $playerId 
     * @return array           
     */
    public function createFirstMainMission($playerId){
        $data['mission_id']         = 1;
        $mission                    = (new Mission)->dicGetOneMainMission($data['mission_id']);
        $data['mission_type']       = $mission['mission_type'];
        $data['max_mission_number'] = 0;
        $data['memo']               = $mission['mission_number'];
        $data['position']           = 0;
        $re                         = $this->addNew($playerId, $data);
        $this->updateMissionNumber($playerId, $mission['mission_type']);//立即检测下主线任务是否完成
        $re                         = self::findFirst($re['id'])->toArray();
        //同时生成每日任务
        
        return $re;
    }
    /**
     * 获取玩家当前的主线任务
     * 
     * @param  int $playerId 
     * @return array           
     */
    public function getCurrentMainMission($playerId){//获取主线任务
        $mainMissionType = self::getMainMissionTypeStr();
        $mainMission = self::findFirst("player_id={$playerId} and mission_type in ({$mainMissionType}) and status=".self::START);
        if(!$mainMission){//如果为空，则新建
            $re = self::findFirst("player_id={$playerId} and mission_type in ({$mainMissionType}) and status=".self::TOTAL_FINISH);
            if(!$re) {
                $re = $this->createFirstMainMission($playerId);
            } else {
                $re = ['main_finish_flag'=>true];
            }
        } else {
            $re = $mainMission->toArray();
            if($re['status']==self::TOTAL_FINISH) {
                $re = [];
            }
        }
        return $re;
    }
    /**
     * 获取玩家所有当前的主线任务
     * 
     * @param  int $playerId 
     * @return array           
     */
    public function getAllMainMission($playerId){//获取所有主线任务
        $mainMissionType = self::getMainMissionTypeStr();
        $mainMission = self::find("player_id={$playerId} and mission_type in ({$mainMissionType}) and (status=".self::START." or status=" . self::COMPLETE . " ) order by mission_id asc")->toArray();
        $Mission = new Mission;
        if(!$mainMission){//如果为空，则新建
            $re = self::findFirst("player_id={$playerId} and mission_type in ({$mainMissionType}) and status=".self::TOTAL_FINISH);
            if(!$re) {
                $re = [$this->createFirstMainMission($playerId)];
            } else {
                $re = ['main_finish_flag'=>true];
            }
        } else {
            $re = $mainMission;
        }
        return $re;
    }
    /**
     * 获取玩家当前的每日任务
     * 
     * @param  int $playerId 
     * @return array           
     */
    public function getDailyMission($playerId, $level){
        $start        = self::START;
        $complete     = self::COMPLETE;
        $dateLimit    = date('Y-m-d');
        $mainMissionType = self::getMainMissionTypeStr();
        //取之前没领奖的每日任务，和今天的所有每日任务
        $dailyMission = self::find("player_id={$playerId} and mission_type not in ({$mainMissionType}) and (status={$complete} or date_limit='{$dateLimit}') order by date_limit asc, position asc")->toArray();//每日任务
        $amount       = count($dailyMission);
        if($amount<self::MAX_DAILY_MISSION) {
            $subNum          = self::MAX_DAILY_MISSION-$amount;
            $dailyMissionExt = $this->getRandDailyMission($playerId, $dailyMission, $level, $subNum);
            $dailyMission    = array_merge($dailyMission, $dailyMissionExt);
        }
        return $dailyMission;
    }
    /**
     * 获取每日任务的最大position
     * @param  int $playerId 
     * @return int           
     */
    public function getMaxPosition($playerId){
        $maxPosition = 0;
        $dateLimit = date('Y-m-d');
        $mainMissionType = self::getMainMissionTypeStr();
        $playerMission = self::findFirst("player_id={$playerId} and mission_type not in ({$mainMissionType}) and date_limit={$dateLimit} order by position desc");
        if($playerMission) {
            $maxPosition = $playerMission->position;
        }
        return $maxPosition;
    }
    /**
     * 获取随机每日任务
     * 
     * @param  int  $playerId 
     * @param  int  $level    
     * @param  integer $num      
     * @return array            
     */
    public function getRandDailyMission($playerId, $existsDailyMission, $level, $num=1, $useGem=false){
        $re = [];
        $Mission = new Mission;
        $dailyMissionByLevel = $Mission->dicGetDailyMissionByLevel($level);
        //去掉已有的每日任务
        if($existsDailyMission) {
            foreach($existsDailyMission as $k=>$v) {
                unset($dailyMissionByLevel[$v['mission_type']][$v['mission_id']]);
            }
        }
        $i=0;
        $startPosition = $this->getMaxPosition($playerId);
        while($i<$num) {
            // var_dump($dailyMissionByLevel);
            $single = $dailyMissionByLevel[array_rand($dailyMissionByLevel)];
            if(count($single)>1) {
                if($useGem) {//如果是用的宝石
                    $single = randomByField($single, 'probability_yb');
                } else {
                    $single = randomByField($single, 'probability');
                }
            }
            // dump($single);
            if($single) {
                $id                         = key($single);
                $single                     = current($single);
                $data['mission_type']       = $single['mission_type'];
                $data['mission_id']         = $single['id'];
                $data['max_mission_number'] = $single['mission_number'];
                if($this->refreshPosition) {
                    $data['position']       = $this->refreshPosition;
                    $this->refreshPosition  = false;
                } else {
                    $data['position']       = $startPosition+1;
                    $startPosition++;
                }
                // $re[] = $single;
                $re[] = $this->addNew($playerId, $data);
                unset($dailyMissionByLevel[$single['mission_type']][$id]);
            } else {
                continue;
            }
            $i++;
        }
        return $re;
    }
    /**
     * 刷新新的每日任务
     * @param  int $playerId               
     * @param  int $currentPlayerMissionId 
     * @param  int $useGem                 
     * @return array                         
     */
    public function refreshDailyMissionWithGem($playerId, $currentPlayerMissionId){
        $single = self::findFirst($currentPlayerMissionId);
        if($single && !in_array($single->mission_type, self::$mainMissionType) && ($single->status==self::START || ($single->status==self::COMPLETE&&$single->date_limit==date('Y-m-d')))) {//不能是主线任务
            $this->refreshPosition = $single->position;
            $new = $this->getRandDailyMission($playerId, [$single->toArray()], true);
            $single->delete();
            $this->clearDataCache($playerId);
            return $new;
        } else {
            return false;
        }
    }
    /**
     * 获取任务奖励
     * @param  int $playerId               
     * @param  int $currentPlayerMissionId 
     * @return                          
     */
    public function getMissionReward($playerId, $currentPlayerMissionId){
        $playerMission = $this->getById($playerId, $currentPlayerMissionId);
        if($playerMission) {
            $Mission       = new Mission;
            $Drop          = new Drop;

            $mission = $Mission->dicGetOne($playerMission['mission_id']);
            if($playerMission['status']==self::COMPLETE) {//只有完成了才能领奖
                $this->updateAll(['status'=>self::FINISH, 'update_time'=>qd()], ['id'=>$currentPlayerMissionId, 'status'=>self::COMPLETE]);//设为完成状态
                $this->clearDataCache($playerId);
                //领奖
                $dropIds = parseArray($mission['drop']);
                $Drop->gain($playerId, $dropIds, 1, '每日任务或主线任务');
                if(in_array($mission['mission_type'], self::$mainMissionType)) {//主线任务
                    if($mission['next_mission_id']==0) {//最后一个
                        $this->updateAll(['status'=>self::TOTAL_FINISH, 'update_time'=>qd()], ['id'=>$currentPlayerMissionId, 'status'=>self::FINISH]);
                        $this->clearDataCache($playerId);
                    }
                }
                return true;
            }
        }
        return false;
    }
    /**
     * 生成下一次的主线任务
     * @param  int $playerId  
     * @param  int $missionId 
     */
    public function genNextMainMission($playerId, $missionId){
        //生成下一个主线任务，并判断下一个任务是否完成
        $Mission            = new Mission;
        $data['mission_id'] = $missionId;
        $newMission         = $Mission->dicGetOneMainMission($data['mission_id']);

        switch($newMission['mission_type']) {
            case 21://主线-训练步兵
            case 22://主线-训练骑兵
                $data['max_mission_number'] = $newMission['mission_number'];
                break;
            case 25:
            case 26://打怪主线任务
                $data['max_mission_number'] = 1;
                break;
            case 27://打怪2次
                $data['max_mission_number'] = 2;
                break;
            case 28://打怪4次
                $data['max_mission_number'] = 4;
                break;
            default:
                $data['max_mission_number'] = 0;
                $data['memo']               = $newMission['mission_number'];
        }
        $data['mission_type'] = $newMission['mission_type'];
        $data['position']     = 0;

        if($missionId!=0) {
            $exists = self::findFirst("mission_id={$missionId} and mission_type={$data['mission_type']} and player_id={$playerId}");
            if (!$exists) {
                $this->addNew($playerId, $data);
                $this->clearDataCache($playerId);
                $this->updateMissionNumber($playerId, $newMission['mission_type']);//立即检测下主线任务是否完成
            }
        }
    }
    /**
     * 每日任务完成 //NOTICE:所有Case后面都加了清Cache——张董琪
     * 
     *
     * 使用方法如下
     * ```php
     * (new PlayerMission)->updateMissionNumber($playerId, $missionType, 123);
     * ```
     * @param  int $playerId      
     * @param  int $missionType   
     * @param  int $missionNumber 
     */
    public function updateMissionNumber($playerId, $missionType, $missionNumber=0){
        $Mission     = new Mission;
        $Build       = new Build;
        $PlayerBuild = new PlayerBuild;
        switch($missionType) {
            case 1://1 主线任务：主线-建造或升级
                $mainMission      = $this->getCurrentMainMission($playerId);
                if(isset($mainMission['main_finish_flag'])) break;//主线任务已做完
                $missionId        = $mainMission['mission_id'];
                $mission          = $Mission->dicGetOne($missionId);
                $buildId          = $mission['mission_number'];
                if($mission['mission_type']!=$missionType) break;
                if($PlayerBuild->isBuildExist($playerId, $buildId)) {
                    $this->updateAll(['status'=>self::COMPLETE, 'update_time'=>qd()], ['id'=>$mainMission['id'], 'status'=>self::START, 'mission_type' =>$missionType,]);
                    $this->genNextMainMission($playerId, $mission['next_mission_id']);//生成下一个主线任务，并判断下一个任务是否完成
                }
                $this->clearDataCache($playerId);
                break;
            case 21://主线-训练步兵
            case 22://主线-训练骑兵
            // case 23://主线-训练弓兵
            // case 24://主线-训练车兵
                if($missionNumber) {
                    $mainMission      = $this->getCurrentMainMission($playerId);
                    if(isset($mainMission['main_finish_flag'])) break;//主线任务已做完
                    $missionId        = $mainMission['mission_id'];
                    $mission          = $Mission->dicGetOne($missionId);
                    if($mission['mission_type']!=$missionType) {
                        break;
                    }
                    $affectedRows = $this->updateAll(['current_mission_number'=>"current_mission_number+{$missionNumber}", 'update_time'=>qd()], 
                        [
                        'id'                     => $mainMission['id'], 
                        'player_id'              => $playerId,
                        'status'                 => self::START,
                        'max_mission_number  >'  => "current_mission_number+{$missionNumber}",
                        'mission_type'           =>$missionType,
                        ]);
                    if($affectedRows<1) {//超过最大
                        if($this->updateAll(['memo'=>q("last:{$missionNumber}"), 'current_mission_number'=>'max_mission_number', 'update_time'=>qd(), 'status'=>self::COMPLETE], 
                            [
                            'id'                   => $mainMission['id'], 
                            'status'               => self::START,
                            'max_mission_number <=' => "current_mission_number+{$missionNumber}",
                            'mission_type'          =>$missionType,
                            ])) {
                            $this->genNextMainMission($playerId, $mission['next_mission_id']);//生成下一个主线任务，并判断下一个任务是否完成
                        }
                    }
                }
                $this->clearDataCache($playerId);
                break;
            case 25://主线-野外打怪
                $player      = (new Player)->getByPlayerId($playerId);
                $monsterLv   = $player['monster_lv'];
                $mainMission = $this->getCurrentMainMission($playerId);
                if(isset($mainMission['main_finish_flag'])) break;//主线任务已做完
                $missionId   = $mainMission['mission_id'];
                $mission     = $Mission->dicGetOne($missionId);
                if($mission['mission_type']!=$missionType) break;
                $npc         = (new Npc)->dicGetOne($mission['mission_number']);
                if($monsterLv>=$npc['monster_lv']) {
                    if($this->updateAll(['status'=>self::COMPLETE, 'update_time'=>qd(), 'current_mission_number'=>1], ['id'=>$mainMission['id'], 'status'=>self::START, 'mission_type'=>$missionType])) {
                        $this->genNextMainMission($playerId, $mission['next_mission_id']);//生成下一个主线任务，并判断下一个任务是否完成
                    }
                } elseif($missionNumber) {//npc id
                    if($missionNumber==$mission['mission_number']) {
                        if($this->updateAll(['status'=>self::COMPLETE, 'update_time'=>qd(), 'current_mission_number'=>1], ['id'=>$mainMission['id'], 'status'=>self::START, 'mission_type'=>$missionType])) {
                            $this->genNextMainMission($playerId, $mission['next_mission_id']);//生成下一个主线任务，并判断下一个任务是否完成
                        }
                    }
                }
                $this->clearDataCache($playerId);
                break;
            case 27://主线-打怪2次
            case 28://主线-打怪4次
                $mainMission = $this->getCurrentMainMission($playerId);
                if(isset($mainMission['main_finish_flag'])) break;//主线任务已做完
                $missionId   = $mainMission['mission_id'];
                $mission     = $Mission->dicGetOne($missionId);
                if($mission['mission_type']!=$missionType) break;
                $npc         = (new Npc)->dicGetOne($mission['mission_number']);
                if($npc && $missionNumber==$mission['mission_number']) {//npc id
                    $affectedRows = $this->updateAll(['current_mission_number'=>"current_mission_number+1", 'update_time'=>qd()], 
                        [
                        'id'                     => $mainMission['id'], 
                        'player_id'              => $playerId,
                        'status'                 => self::START,
                        'max_mission_number  >'  => "current_mission_number+1",
                        'mission_type'           => $missionType,
                        ]);
                    if($affectedRows<1) {//超过最大
                        if($this->updateAll(['memo'=>q("last:{$missionNumber}"), 'current_mission_number'=>'max_mission_number', 'update_time'=>qd(), 'status'=>self::COMPLETE], 
                            [
                            'id'                    => $mainMission['id'], 
                            'status'                => self::START,
                            'max_mission_number <=' => "current_mission_number+1",
                            'mission_type'          => $missionType,
                            ])) {
                            $this->genNextMainMission($playerId, $mission['next_mission_id']);//生成下一个主线任务，并判断下一个任务是否完成
                        }
                    }
                }
                break;
            case 26://主线-研究指定科技  
                $mainMission = $this->getCurrentMainMission($playerId);
                if(isset($mainMission['main_finish_flag'])) break;//主线任务已做完
                $missionId   = $mainMission['mission_id'];
                $mission     = $Mission->dicGetOne($missionId);
                if($mission['mission_type']!=$missionType) break;
                if($missionNumber==$mission['mission_number'] || $missionNumber==0) {
                    $PlayerScience = new PlayerScience;

                    $scienceIds = $this->sqlGet("select id from science where science_type_id={$mission['mission_number']}");
                    foreach($scienceIds as $v) {
                        $exists = $PlayerScience->findFirst("player_id={$playerId} and (science_id={$v['id']} or next_id={$v['id']})");
                        if($exists) {//已经研究过了
                            if($this->updateAll(['status'=>self::COMPLETE, 'update_time'=>qd(), 'current_mission_number'=>1], ['id'=>$mainMission['id'], 'status'=>self::START, 'mission_type'=>$missionType])) {
                                $this->genNextMainMission($playerId, $mission['next_mission_id']);//生成下一个主线任务，并判断下一个任务是否完成
                            }
                            break;
                        }
                    }
                }
                $this->clearDataCache($playerId);
                break;
            // case 2://2 武将学习：在学院中为3名武将进行学习 //e.g.暂时没有
            case 3://3 研究科技：研究任意科技1个
            case 4://4 训练部队：训练指定步兵/骑兵/弓兵/车兵
            case 5://5 击杀怪物：击杀指定怪物/任意怪物
            case 6://6 攻击玩家：攻击其他玩家获胜n次
            case 7://7 掠夺资源：掠夺其他玩家n资源（黄金、粮草、木
            case 8://8 采集资源：在世界地图中采集n资源
            case 9://9 奋勇杀敌：击杀其他玩家n民士兵
            case 10://10 防御玩家：抵御其他玩家攻击n次
            case 11://11 治愈伤兵：治愈n名伤兵
            case 12://12 联盟捐献：获得联盟n点贡献值
            case 13://13 合成材料：合成n装备进阶材料
            // case 14://14 收缴兵器：获得n件武将
            case 15://15 众志成城：集结消灭n名敌军
            case 16://16 招兵买马：招募20名雇佣军
            case 17://17 商城购物：在商城中消费元宝
            // case 18://18 世界发言：在世界频道中发言1次
            case 19://19 联盟兑换：在联盟中兑换1次物品
            case 20://20 收获资源：在主城中收获n资源
                $playerMission = $this->getByPlayerId($playerId);
                $currentMissions = Set::combine($playerMission, '{n}.id', '{n}' , '{n}.mission_type');
                if(isset($currentMissions[$missionType])) {
                    $currentMissions = $currentMissions[$missionType];
                    foreach($currentMissions as $k=>$v) {
                        if($v['current_mission_number']==$v['max_mission_number']) continue;
                        $affectedRows = $this->updateAll(['current_mission_number'=>"current_mission_number+{$missionNumber}", 'update_time'=>qd()], 
                            [
                            'id'                    => $v['id'], 
                            'player_id'             => $v['player_id'],
                            'status'                => self::START,
                            'max_mission_number  >' => "current_mission_number+{$missionNumber}",
                            'mission_type'          =>$missionType,
                            ]);
                        if($affectedRows<1) {//超过最大
                            $this->updateAll(['memo'=>q("last:{$missionNumber}"), 'current_mission_number'=>'max_mission_number', 'update_time'=>qd(), 'status'=>self::COMPLETE], 
                                [
                                'id'                    => $v['id'], 
                                'status'                => self::START,
                                'max_mission_number <=' => "current_mission_number+{$missionNumber}",
                                'mission_type'          =>$missionType,
                                ]);
                        }
                    }
                    $this->clearDataCache($playerId);
                }
                break;
        }
    }
}