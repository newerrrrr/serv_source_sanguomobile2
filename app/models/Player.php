<?php
/**
 * 玩家表-model
 */
class Player extends ModelBase{
    const FireTime = 1800;//着火持續時間
    const FireRate = 18;//着火時耐久損失速率

    public $blacklist = array('uuid', 'guild_id', 'step', 'step_set', 'master_power', 'general_power', 'army_power', 'build_power', 'science_power', 'trap_power');
    public $errCode = 0;//错误码

    public static $basicInfo = ['avatar_id', 'level', 'nick', 'guild_id'];//远程调用玩家基础信息
    /**
     * 获取玩家最后在线时间
     * @param  int $playerId 
     * @return int
     */
    public static function getPlayerOnlineInfo($playerId){
        $re = Cache::db('server')->hGet(REDIS_KEY_ONLINE, $playerId);
        if($re) {
            return $re;
        }
        return 0;
    }
    /**
     * 通过uuid获取玩家信息
     * @param  string $uuid uuid from frontend
     * @return array       data of player
     */
    public function getPlayerByUuid($uuid){//注册后，换手机登录，此时要修改这个cache
        $key = "uuid:{$uuid}";
        $cache = Cache::db();
        $playerId = $cache->get($key);
        $player = null;
        if($playerId) {
            $player = $this->getByPlayerId($playerId);
        } else {
            $re = self::findFirst(["uuid='{$uuid}'"]);
            if($re){
                $playerId = $re->id;
                $cache->set($key, $playerId);
                $cache->setTimeout($key, 3600);//过期  60*60  #1 hour
                $player = $this->getByPlayerId($playerId);
            }
        }
        return $player;
    }
    
    /**
     * 通过id获取玩家信息
     *     
     * @param  int  $id
     * @param  boolean $forDataFlag is or not for dataController
     * @return array formated data
     */
    public function getByPlayerId($id, $forDataFlag=false, $castleLvFlag=false){
		if(!$id){
			trace();
			exit("\n[ERROR]!!!NOT EXISTS Player. id=!!-> {$id} <-!! .[输入了不存在的玩家id]\n");
		}
        $player = Cache::getPlayer($id, __CLASS__);
        if(!$player) {
            try {
                $player = self::findFirst($id);
            } catch(PDOException $e) {
                $player = (new self)->findFirst($id);
            }
            if($player) {
                $player = $player->toArray();
                $player = $this->afterFindPlayer($player);
                Cache::setPlayer($id, __CLASS__, $player);
            } else {
                trace();
                exit("\n[ERROR]!!!NOT EXISTS Player. id=!!-> {$id} <-!! .[输入了不存在的玩家id]\n");
            }
        }
        $player['last_online_time'] = self::getPlayerOnlineInfo($id);
		if(!$player['last_online_time']){
			$player['last_online_time'] = $player['login_time'];
		}
		if($castleLvFlag) {
            $player['castle_lv'] = (new PlayerBuild)->getPlayerCastleLevel($id);
        }
        //勇士羽林军
        $CityBattleRank = new CityBattleRank;
        $player['rank_title'] = $CityBattleRank->getTitleByPlayerId($id);
        // $buff = (new PlayerBuff)->getPlayerBuff($id, 'avoid_battle');
        //$player['avoid_battle'] = 0;//免战

        if($forDataFlag) {
            return filterFields([$player], $forDataFlag, $this->blacklist)[0];
        } else {
            return $player;
        }
    }
    
    public function afterFindPlayer($player){
        $player = $this->adapter($player, true);
        $player['wall_durability'] = $this->inventoryWallDurability(null, false, $player);
		$player['push_tag'] = parseArray($player['push_tag']);
        $map = Map::findFirst("player_id={$player['id']} and map_element_origin_id=15");
        if($map) {
            $player['map_id']    = intval($map->id);
            $player['x']         = intval($map->x);
            $player['y']         = intval($map->y);
            $player['is_in_map'] = 1;//在地图里：1 不在地图里：0;
        } else {
            $player['map_id']    = 0;
            $player['x']         = $player['prev_x'];
            $player['y']         = $player['prev_y'];
            $player['is_in_map'] = 0;//在地图里：1 不在地图里：0;
        }
        if($player['step_set']) {
            $player['step_set'] = json_decode($player['step_set'], true);
        } else {
            $player['step_set'] = [];
        }

        //新手保护修正
		if($player['fresh_avoid_battle_time'] !== false && $player['fresh_avoid_battle_time'] > $player['avoid_battle_time']){
			$player['avoid_battle_time'] = $player['fresh_avoid_battle_time'];
		}
		//unset($player['fresh_avoid_battle_time']);
        return $player;
    }
    
    public function getByPlayerNick($name, $forDataFlag=false){
        $player = self::findFirst(['nick="'.$name.'"']);
        if(!$player)
            return false;
        return $this->getByPlayerId($player->id, $forDataFlag);
    }
	
	public function getByJob($job, $forDataFlag=false){
        $player = self::findFirst(['job="'.$job.'"']);
        if(!$player)
            return false;
        return $this->getByPlayerId($player->id, $forDataFlag);
    }
    
    /**
     * 生成玩家后的初始化操作，建筑，兵等
     * @param  int $playerId 
     */
    public function initAfterNewPlayer($playerId){
        $Starting = new Starting;
        //map表记录
        $data['map_element_id']        = 1501;
        $data['map_element_origin_id'] = 15;
        $data['map_element_level']     = 1;
        $data['player_id']             = $playerId;
        $Map                           = new Map;
        list($data['x'], $data['y'])   = $Map->getNewCastlePosition();
        $Map->addNew($data);

        $map = Map::findFirst("player_id={$playerId} and map_element_origin_id=15");
        $this->updateAll(['prev_x'=>$map->x, 'prev_y'=>$map->y], ['id'=>$playerId]);
        $this->clearDataCache($playerId);
        //更新prev_x

        //建造官府
        $PlayerBuild = new PlayerBuild;
        $PlayerBuild->newBuild($playerId, 1001, 1015);
        $PlayerBuild->newBuild($playerId, 2001, 1016);
        $PlayerBuild->newBuild($playerId, 12001, 1010);

        $PlayerBuild->newBuild($playerId, 6001, 1002);
        $PlayerBuild->newBuild($playerId, 4001, 1003);
        $PlayerBuild->newBuild($playerId, 41001, 1005);
        $PlayerBuild->newBuild($playerId, 14001, 1006);
        $PlayerBuild->newBuild($playerId, 8001, 1014);
        $PlayerBuild->newBuild($playerId, 42001, 1013);
        $PlayerBuild->newBuild($playerId, 26001, 3001);
        $PlayerBuild->newBuild($playerId, 16001, 5001);
        $PlayerBuild->newBuild($playerId, 44001, 1012);
        $PlayerBuild->newBuild($playerId, 45001, 1019);
        $PlayerBuild->newBuild($playerId, 46001, 1020);
        $PlayerBuild->newBuild($playerId, 47001, 1021);
        $PlayerBuild->newBuild($playerId, 48001, 1022);
        $PlayerBuild->newBuild($playerId, 49001, 1023);
        
        //创建士兵
        $PlayerSoldier = new PlayerSoldier;
        $PlayerSoldier->updateSoldierNum($playerId, 30001, 10);
		
		//增加军团
		$generalId = $Starting->getValueByKey("default_army_general");
		$soldier = $Starting->getValueByKey("default_army_soldier");
		list($soldierId, $soldierNum) = explode(',', $soldier);
		$PlayerArmy = new PlayerArmy;
		$PlayerArmy->add($playerId, 1, $generalId);
		(new PlayerArmyUnit)->add($playerId, $PlayerArmy->id, 1, $generalId, $soldierId, $soldierNum);
		(new PlayerSoldier)->updateSoldierNum($playerId, $soldierId, 0);
		
		//增加武将
		$PlayerGeneral = new PlayerGeneral;
		$PlayerGeneral->add($playerId, $generalId, $PlayerArmy->id);
		
        //默认道具
        $PlayerItem = new PlayerItem;
        $str = $Starting->getValueByKey("default_item");
        $itemList = sanguoDecodeStr($str);
        foreach ($itemList as $key => $value) {
            $PlayerItem->add($playerId, $key, $value);
        }

        //新手保护
        $buffTime = $Starting->getValueByKey("avoid_battle_default_time");
		$this->setFreshAvoidBattleTime($playerId, $buffTime);
        
        //创建酒馆
        $PlayerPub = new PlayerPub;
        $PlayerPub->add($playerId);

        (new PlayerTarget)->getByPlayerId($playerId);//初始化新手目标

        $RefundInfo = new RefundInfo;//封测返利
        $player = $this->getByPlayerId($playerId);
        $uuid = $player['uuid'];
        $gemNum = $RefundInfo->getGemNum($uuid);
        if($gemNum>0){
            $PlayerMail = new PlayerMail;
            $item = $PlayerMail->newItem(1, 10700, $gemNum, []);
            $PlayerMail->sendSystem($playerId, PlayerMail::TYPE_PAY_RETURN, '', '', 0, [], $item);
        }

        //clear cache here
        $this->clearDataCache($playerId);
    }
    /**
     * 检查是否存在相同nick
     * @param  string $nick 昵称
     * @return bool 
     */
    public function checkNickExists($nick){
        $re = self::findFirst(["nick=:nick:", 'bind'=>['nick'=>$nick]]);
        if($re) {
            return true;
        }
        return false;
    }
    /**
     * 根据nick搜索玩家
     * @param  string $nick 昵称
     * @return array       
     */
    public function searchByNick($nick, $searchData=[]){
        $nick       = addslashes($nick);
        $q          = $this->query();
        $fromPage   = $searchData['from_page'];
        $numPerPage = $searchData['num_per_page'];
        $re         = $q->where("nick like :nick: and uuid not like :uuid:")->bind(['nick'=>"%{$nick}%", 'uuid'=>"%{Robot}%"])->columns(['id', 'nick', 'level', 'user_code', 'avatar_id', 'power','camp_id'])->limit($numPerPage, $fromPage*$numPerPage)->execute();
        $r          = $this->adapter($re->toArray());
        if($r) {
            return $r;
        } else {
            return [];
        }
    }
    /**
     * 查找无联盟玩家
     * @param  int $baseNum 基数
     * @return array          
     */
    public function getPlayerNoGuild($baseNum=0){
        $re = self::find(["guild_id=0","limit"=>['number'=>PAGE_ITEM_NUM, 'offset'=>$baseNum*PAGE_ITEM_NUM]]);
        if($re) {
            return filterFields($this->adapter($re->toArray()), true, $this->blacklist);
        }
        return [];
    }
    /**
     * 生成唯一标志码
     * @return string 
     */
    public function getRandomString() {
        while(true){
            $s          = "";
            $characters = "23456789ABCDEFGHJKMNPQRSTUVWXYZ";
            for($i=0; $i<6; $i++) {
                $s .= $characters[mt_rand(0, strlen($characters)-1)];
            }
            $player = self::findFirst("user_code='{$s}'");
            if(!$player) break;
        }
        
        return $s;
    }
    /**
     * 生成新玩家
     * 
     * ```php
     *$postData: ['uuid'=>'uuid', 'avatar_id'=>1, 'nick'=>'nick']
     * ```
     * @param  array $postData post data for create a player
     */
    public function newPlayer($postData){
        // $nick = $postData['nick'];
        randNick:
        $nick = 'sg'.uniqid();
        if($this->checkNickExists($nick)) {//名字重复
            goto randNick;
            // $this->errCode = 10285;//创建角色-昵称已存在
            // return null;
        }

        global $config;

        $self                    = new self;
        $starting                = (new Starting)->dicGetAll();//获取玩家初始化配置数据

        $self->server_id         = $config->server_id;
        $self->user_code         = $this->getRandomString();
        $self->uuid              = $postData['uuid'];
        $self->lang              = $postData['lang'];
        $self->avatar_id         = 1;//$postData['avatar_id']; 这里写死
        $self->nick              = $nick;
        
        $self->level             = $starting['player_level'];
        $self->current_exp       = $starting['current_exp'];
        $self->next_exp          = (new Master)->dicGetOne($starting['player_level']+1)['exp'];
        $self->talent_num_total  = $self->talent_num_remain = $starting['talent_max_num'];
        $self->general_num_total = $self->general_num_remain = $starting['general_num'];
        $self->army_num          = $starting['army_num'];
        $self->army_general_num  = $starting['army_general_num'];
        $self->move              = $starting['move_starting'];
        $self->move_max          = $starting['move_max'];
        $self->gold              = $starting['gold_starting'];
        $self->food              = $starting['food_starting'];
        $self->wood              = $starting['wood_starting'];
        $self->stone             = $starting['stone_starting'];
        $self->iron              = $starting['iron_starting'];
        $self->silver            = $starting['silver_starting'];
        $self->food_out          = $starting['food_out'];
        $self->gift_gem          = $starting['gift_gem'];
        $self->power             = $starting['power'];
        $self->login_time        = $self->create_time = date('Y-m-d H:i:s');
        
        $self->save();
        
        $playerId                = $self->id;
        $ret                     = self::findFirst($playerId)->toArray();

        (new PlayerInfo)->newPlayerInfo($playerId, $postData);//创建玩家静态表
        (new PlayerBuff)->newPlayerBuff($playerId);//playerBuff表记录
		(new PlayerGeneralBuff)->newPlayerBuff($playerId);//playerBuff表记录
        //$this->study_num = $starting['study_num'];
        $this->initAfterNewPlayer($playerId);
        //login server
        (new PlayerServerList)->addNew($postData['uuid'], $config->server_id, ['nick'=>$self->nick, 'avatar_id'=>$self->avatar_id, 'level'=>$self->level]);//添加到玩家服务器列表
        (new PlayerLastServer)->saveLast($postData['uuid'], $config->server_id);//最后一次登录服务器时间

        return $ret;
    }
    /**
     * 更改player表的值
     * @param  int $playerId 
     * @param  array  $fields  
     */
    public function alter($playerId, array $fields){
        $ret = $this->updateAll($fields, ['id'=>$playerId]);
        $this->clearDataCache($playerId);
		return $ret;
    }
    /**
     * 获得玩家战力
     * @param  int $id 
     * @return int 战力
     */
    public function getPower($id){
        $player = $this->getByPlayerId($id);
        $power = $player['power'];
        return $power;
    }
    /**
     * 刷新战斗力
     *
     * 使用方法如下
     * <pre>
     * $Player = new Player;
     * $Player->refreshPower(100029, 'trap_power');
     * 
     * 总战斗力：主公战斗力+武将战斗力+部队战斗力+建筑战斗力+科技战斗力+陷阱战斗力  
     * 主公战斗力：主公宝物+主公等级战斗力+天赋    $Player->refreshPower(100029, 'master_power'); 
     * 武将战斗力：武将本身+武将装备             $Player->refreshPower(100029, 'general_power'); 
     * 部队战斗力：兵种等级*数量               $Player->refreshPower(100029, 'army_power'); 
     * 建筑战斗力：建筑物默认战斗力              $Player->refreshPower(100029, 'build_power'); 
     * 科技战斗力：科技战斗力                  $Player->refreshPower(100029, 'science_power');      
     * 陷阱战斗力：陷阱等级*数量               $Player->refreshPower(100029, 'trap_power');
     * </pre>
     * @param  int $playerId   
     * @param  string $powerField 字段
     */
    public function refreshPower($playerId, $powerField){
        $Player = new Player;
        $Power = new Power;
        switch($powerField) {
            case 'master_power':
                $power = $Power->getMaster($playerId);
                break;
            case 'general_power':
                $power = $Power->getGeneral($playerId);
                break;
            case 'army_power':
                $power = $Power->getSoldier($playerId);
                break;
            case 'build_power':
                $power = $Power->getBuilding($playerId);
                break;
            case 'science_power':
                $power = $Power->getScience($playerId);
                break;
            case 'trap_power':
                $power = $Power->getTrap($playerId);
                break;
            default:
                return false;
        }
        $p1 = $Player->getByPlayerId($playerId);
        $power1 = $p1['power'];

		//echo "$powerField=>$power";
        $this->updateAll([$powerField=>$power, 'power'=>'master_power+general_power+army_power+build_power+science_power+trap_power'], ['id'=>$playerId]);
        $this->clearDataCache($playerId);


        $p2 = $Player->getByPlayerId($playerId);
        $power2 = $p2['power'];
        
        if($power2-$power1>0){
            $PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
            $PlayerTimeLimitMatch->updateScore($playerId, 8, $power2-$power1);
            $PlayerTarget = new PlayerTarget;
            $PlayerTarget->updateTargetCurrentValue($playerId, 7, $power2, false);
        }

        return;
    }
    /**
     * 侦查邮件
     * @param  array $playerProjectQueue 队列表单条记录
     * @return bool
     */
    public function spy($ppq){
        if($ppq['status']==1) {
            $playerId           = $ppq['player_id'];
            $PlayerProjectQueue = new PlayerProjectQueue;
            $Player             = new Player;
            $PlayerMail         = new PlayerMail;
            $Map         = new Map;
            //case: finish current queue
            $PlayerProjectQueue->finishQueue($playerId, $ppq['id']);
            //case: send email with spy info
            $targetInfo = json_decode($ppq['target_info'], true);
            $dataType = $targetInfo['type'];
            if($dataType==1) {//主堡
                $targetPlayerId     = $ppq['target_player_id'];
                $targetPlayer       = $Player->getByPlayerId($targetPlayerId);
                if($ppq['to_x']==$targetPlayer['x'] && $ppq['to_y']==$targetPlayer['y']) {//玩家仍在该点位置上，发侦查邮件
                    $spyInfo     = $this->getSpyInfo($playerId, $targetPlayerId);
                    //send mail here
                    $toPlayerIds = [$playerId];
                    $type        = PlayerMail::TYPE_DETECT;
                    $title       = 'system email';
                    $msg         = '';
                    $time        = 0;
                    $data        = $spyInfo;
                    $PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data);
                    //发给被侦查者
                    $player      = $Player->getByPlayerId($playerId);
                    $player      = keepFields($player, ['nick', 'avatar_id'], true);
                    $PlayerMail->sendSystem([$targetPlayerId], PlayerMail::TYPE_DETECTED, $title, '', 0, ['target_player'=>$player, 'type'=>$dataType]);
                }
            } elseif($dataType==2) {//堡垒
                $targetPlayerId = 0;
                $toMapId        = $ppq['to_map_id'];
                $toMap          = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
                if($toMap['map_element_origin_id']==1) {
                    $spyInfo     = $this->getGuildBaseSpyInfo($playerId, $toMap);
                    //send mail here
                    $toPlayerIds = [$playerId];
                    $type        = PlayerMail::TYPE_DETECT;
                    $title       = 'system email';
                    $msg         = '';
                    $time        = 0;
                    $data        = $spyInfo;
                    $PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data);
                }
            } elseif($dataType==3) {//资源田
                $targetPlayerId = $ppq['target_player_id'];
                $targetPlayer   = $Player->getByPlayerId($targetPlayerId);
                $toMapId        = $ppq['to_map_id'];
                $toMap          = $Map->getByXy($ppq['to_x'], $ppq['to_y']);

                if(in_array($toMap['map_element_origin_id'], [9,10,11,12,13])) {
                    //判断资源田是否有该玩家
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and player_id={$targetPlayerId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                    if($queue) {
                        $spyInfo     = $this->getResourceSpyInfo($playerId, $toMap);
                        //send mail here
                        $toPlayerIds = [$playerId];
                        $type        = PlayerMail::TYPE_DETECT;
                        $title       = 'system email';
                        $msg         = '';
                        $time        = 0;
                        $data        = $spyInfo;
                        $PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data);
                        //发给被侦查者
                        $player      = $Player->getByPlayerId($playerId);
                        $player      = keepFields($player, ['nick', 'avatar_id'], true);
                        $PlayerMail->sendSystem([$targetPlayerId], PlayerMail::TYPE_DETECTED, $title, '', 0, ['target_player'=>$player,'type'=>$dataType]);
                    }
                }
            } elseif($dataType==4) {//国王战-城寨
                $targetPlayerId = 0;
                $toMapId        = $ppq['to_map_id'];
                $toMap          = $Map->getByXy($ppq['to_x'], $ppq['to_y']);
                if(in_array($toMap['map_element_origin_id'], [18,19])) {
                    $spyInfo     = $this->getKingBaseSpyInfo($playerId, $toMap);
                    //send mail here
                    $toPlayerIds = [$playerId];
                    $type        = PlayerMail::TYPE_DETECT;
                    $title       = 'system email';
                    $msg         = '';
                    $time        = 0;
                    $data        = $spyInfo;
                    $PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data);
                }
            } elseif($dataType==5) {
                $targetPlayerId = $ppq['target_player_id'];
                $targetPlayer   = $Player->getByPlayerId($targetPlayerId);
                $toMapId        = $ppq['to_map_id'];
                $toMap          = $Map->getByXy($ppq['to_x'], $ppq['to_y']);

                if($toMap['map_element_origin_id']==22) {
                    //判断资源田是否有该玩家
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and player_id={$targetPlayerId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                    if($queue) {
                        $spyInfo     = $this->getStrongholdSpyInfo($playerId, $toMap);
                        //send mail here
                        $toPlayerIds = [$playerId];
                        $type        = PlayerMail::TYPE_DETECT;
                        $title       = 'system email';
                        $msg         = '';
                        $time        = 0;
                        $data        = $spyInfo;
                        $PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data);
                        //发给被侦查者
                        $player      = $Player->getByPlayerId($playerId);
                        $player      = keepFields($player, ['nick', 'avatar_id'], true);
                        $PlayerMail->sendSystem([$targetPlayerId], PlayerMail::TYPE_DETECTED, $title, '', 0, ['target_player'=>$player,'type'=>$dataType]);
                    }
                }
            }
            //case: add new queue 
            $type                     = PlayerProjectQueue::TYPE_DETECT_RETURN;//增援中
            $needTime                 = PlayerProjectQueue::calculateMoveTime($playerId, $ppq['to_x'], $ppq['to_y'], $ppq['from_x'], $ppq['from_y'], 4, 0);
            $extraData                = [];
            $extraData['from_map_id'] = $ppq['to_map_id'];
            $extraData['from_x']      = $ppq['to_x'];
            $extraData['from_y']      = $ppq['to_y'];
            $extraData['to_map_id']   = $ppq['from_map_id'];
            $extraData['to_x']        = $ppq['from_x'];
            $extraData['to_y']        = $ppq['from_y'];
            $PlayerProjectQueue->addQueue($playerId, $ppq['guild_id'], 0, $type, $needTime, 0, [], $extraData);
            echo "ok\n";
            return true;
        }
        echo "status != 1\n";
        return false;
    }
    /**
     * 获取侦查信息
     * @param  int  $playerId       
     * @param  int  $targetPlayerId 
     */
    public function getSpyInfo($playerId, $targetPlayerId=0){
        $Player                        = new Player;
        $PlayerBuild                   = new PlayerBuild;
        $PlayerSoldier                 = new PlayerSoldier;
        $Build                         = new Build;
        $PlayerArmyUnit                = new PlayerArmyUnit;
        $PlayerTrap                    = new PlayerTrap;
        $PlayerArmy                    = new PlayerArmy;
        $PlayerGeneral                 = new PlayerGeneral;

        $player                        = $Player->getByPlayerId($playerId);
        $targetPlayer                  = $Player->getByPlayerId($targetPlayerId);
        $playerBuild                   = $PlayerBuild->getByOrgId($playerId, 12)[0];//获取玩家哨塔
        if(!$playerBuild) exit('illegal! Not exists tower!');

        $targetPlayerBuff              = (new PlayerBuff)->getPlayerBuff($targetPlayerId, 'pretend_skill');
        
        $build                         = $Build->dicGetOne($playerBuild['build_id']);
        $buildLevel                    = $build['build_level'];
        
        
        $targetPlayerInfo['nick']      = $targetPlayer['nick'];
        $targetPlayerInfo['avatar_id'] = $targetPlayer['avatar_id'];
        if($targetPlayer['guild_id']>0) {//如果有联盟,显示联盟short_name
            $guildInfo                            = (new Guild)->getGuildInfo($targetPlayer['guild_id']);
            $guildShortName                       = $guildInfo['short_name']; 
            $targetPlayerInfo['guild_short_name'] = $guildShortName;
        } else {
            $targetPlayerInfo['guild_short_name'] = '';
        }
        $data['target_player']         = $targetPlayerInfo;
        $data['build_level']           = $buildLevel;
        $data['x']                     = $targetPlayer['x'];
        $data['y']                     = $targetPlayer['y'];

        //满足条件，则剩余所有的信息都发送
        switch(TRUE) {
            case ($buildLevel>=50)://主动技能是否在冷却中
                if(!isset($data['master_skill'])) {
                    $pms = (new PlayerMasterSkill)->getByPlayerId($targetPlayerId);
                    $data['master_skill'] = $pms;
                }
            case ($buildLevel>=44)://所有属性增益
                if(!isset($data['buff'])) {
                    $Buff       = new Buff;
                    $PlayerBuff = new PlayerBuff;
                    $buffIds    = Buff::$buffIdForShow;
					$data['buff'] = (new PlayerBuff)->getPlayerBuffs($targetPlayerId, $buffIds);
                    /*foreach($buffIds as $buffId) {
                        $buff                        = $Buff->dicGetOne($buffId);
                        $data['buff'][$buff['name']] = $PlayerBuff->getPlayerBuff($targetPlayerId, $buff['name']);
                    }*/
					//ksort($data['buff']);
                }
            case ($buildLevel>=39)://援军兵种类型和准确数量
                $helpSoldierInfoFlag = true;
            case ($buildLevel>=35)://城防设施类型以及各自准确数量
                $trapFullInfoFlag = true;
                $targetPlayerTrap          = $PlayerTrap->getByPlayerId($targetPlayerId);
                $data['trap']['detail']    = $targetPlayerTrap;
                $data['trap']['total_num'] = array_sum(Set::extract('/num', $targetPlayerTrap));
            case ($buildLevel>=31)://预备役部队类型、准确数量和城墙驻守武将
                $data['troop']  = [];
                $remainSoldier  = $PlayerSoldier->getByPlayerId($targetPlayerId);
                $remainSoldier1 = [];
                foreach($remainSoldier as $k=>$v) {
                    if($v['num']>0) {
                        $remainSoldier1[] = $v;
                    }
                }
                $data['troop']['remain_army'] = $remainSoldier1;

                $pb = $PlayerBuild->getByOrgId($targetPlayerId, 2);
                $generalId = $pb[0]['general_id_1'];//驻守的武将
                if($generalId) {
                    $data['troop']['wall_general_id'] = $generalId;
                    $wallPlayerGeneral = $PlayerGeneral->getByGeneralId($targetPlayerId, $generalId);
                    $data['troop']['general_star'] = intval($wallPlayerGeneral['star_lv']);
                } else {
                    $data['troop']['wall_general_id'] = 0;
                    $data['troop']['general_star'] = 0;
                }
            case ($buildLevel>=27)://防守部队类型和准确数量
                $targetArmySoldierInfoFlag = true;
            case ($buildLevel>=25)://防守部队武将信息
                $targetArmyGeneralInfoFlag = true;
            case ($buildLevel>=23)://援军兵种类型和大致数量
            case ($buildLevel>=19)://援军武将信息
                $helpGeneralInfoFlag = true;
            case ($buildLevel>=16)://援军领主的名称与等级
                if(!isset($data['help_army'])) {
                    $helpQueue = PlayerProjectQueue::find("target_player_id={$targetPlayerId} and status=1 and end_time='0000-00-00 00:00:00' and type=" . PlayerProjectQueue::TYPE_CITYASSIST_ING . "")->toArray();
                    $data['help_army']              = [];
                    $data['help_army']['total_num'] = 0;
                    $data['help_army']['detail']    = [];
                    foreach($helpQueue as $v) {
                        //case 援军领主的名称与等级
                        $tmpPlayerId                               = intval($v['player_id']);
                        $tmpPlayer                                 = $Player->getByPlayerId($tmpPlayerId);
                        $_detail = [];

                        /*$data['help_army']['detail'][$tmpPlayerId]['info'] = */$_detail = ['player_id'=>$tmpPlayerId,'nick'=>$tmpPlayer['nick'], 'level'=>$tmpPlayer['level'], 'avatar_id'=>$tmpPlayer['avatar_id']]; 
                        //case 援军武将信息
                        if(true||isset($helpGeneralInfoFlag)) {//19和23显示给前端的信息一致
                            $pau = $PlayerArmyUnit->getByArmyId($tmpPlayerId, $v['army_id'], true);
                            $pau = keepFields($pau, ['general_id', 'soldier_id', 'soldier_num', 'general_star']);
                            foreach($pau as $vv) {
                                if($vv['soldier_id']) {
                                    $_detail['army'][] = $vv;
                                    // $data['help_army']['detail'][$tmpPlayerId]['army'][] = $vv;
                                    if(isset($helpSoldierInfoFlag)) {//精确
                                        $data['help_army']['total_num'] += $vv['soldier_num'];
                                    }
                                }
                            }
                        }
                        $data['help_army']['detail'][] = $_detail;
                    }
                    // if(!empty($data['help_army']['detail'])) {
                    //     $data['help_army']['detail'] = array_values($data['help_army']['detail']);
                    // } else {
                    //     $data['help_army']['detail'] = [];
                    // }
                }
            case ($buildLevel>=13)://防守部队类型和各自大致数量(士兵类型)
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                
                //兵的数量和
                if(isset($targetArmyGeneralInfoFlag)) {
                    //case a 军团里的兵
                    $targetPlayerArmy = $PlayerArmy->getByPlayerId($targetPlayerId);

                    $detail = ['player_id'=>$targetPlayerId,'nick'=>$targetPlayer['nick'], 'level'=>$targetPlayer['level'], 'avatar_id'=>$targetPlayer['avatar_id']];
                    $data['troop']['army'] = ['player_detail'=>$detail];//, 'player_army'=>$pau

                    foreach($targetPlayerArmy as $v) {
                        if($v['status']==1) continue;
                        $pau = $PlayerArmyUnit->getByArmyId($targetPlayerId, $v['id'], true);
                        foreach($pau as $v) {
                            //case 防守部队武将信息
                            if($v['soldier_id']) {
                                $data['troop']['army']['player_army'][] = keepFields($v, ['general_id', 'soldier_id', 'soldier_num', 'general_star'], true);
                            }
                        }
                    }

                    if(isset($targetArmySoldierInfoFlag)) {
                        $data['troop']['total_num'] = array_sum(Set::extract('/soldier_num', $data['troop']['army']['player_army']));
                    }
                } else {//模糊
                    $playerSoldier = $PlayerSoldier->getByPlayerId($targetPlayerId);
                    $data['troop']['soldier'] = [];
                    foreach($playerSoldier as $v) {
                        $soldierId = $v['soldier_id'];
                        if($v['num']==0) continue;
                        if(!isset($data['troop']['soldier'][$soldierId])) {
                            $data['troop']['soldier'][$soldierId]['soldier_id'] = $soldierId;
                            $data['troop']['soldier'][$soldierId]['soldier_num'] = $v['num'];
                        } else {
                            $data['troop']['soldier'][$soldierId]['soldier_num'] += $v['num'];
                        }
                    }
                    if(!empty($data['troop']['soldier'])) {
                        $data['troop']['total_num'] = $this->getFuzzyNumber(array_sum(Set::extract('/soldier_num', $data['troop']['soldier'])));
                        $data['troop']['soldier'] = array_values($data['troop']['soldier']) ?: [];
                    }
                }
                if(!isset($targetArmySoldierInfoFlag)) {
                    $data['troop']['is_fuzzy'] = 1;
                } else {
                    $data['troop']['is_fuzzy'] = 0;
                }
            case ($buildLevel>=10)://援军大致数量（士兵数量1000~2000）
                if(!isset($helpSoldierInfoFlag)) {
                    $helpQueue = PlayerProjectQueue::find("target_player_id={$targetPlayerId} and status=1 and end_time='0000-00-00 00:00:00' and type=" . PlayerProjectQueue::TYPE_CITYASSIST_ING . "")->toArray();
                    $data['help_army']['total_num'] = 0;
                    foreach($helpQueue as $v) {
                        $data['help_army']['total_num'] += array_sum(Set::extract('/soldier_num', $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id'])));
                    }
                    $data['help_army']['total_num'] = $this->getFuzzyNumber($data['help_army']['total_num']);
                }
            case ($buildLevel>=7)://城防设施大致数量（陷阱数量1000~2000）
                if(!isset($trapFullInfoFlag)) {
                    $data['trap'] = [];
                    $targetPlayerTrap = $PlayerTrap->getByPlayerId($targetPlayerId);
                    $data['trap']['total_num'] = array_sum(Set::extract('/num', $targetPlayerTrap));
                    //模糊数字
                    $data['trap']['total_num'] = $this->getFuzzyNumber($data['trap']['total_num']);
                }
            case ($buildLevel>=4)://防守部队大致数量（士兵数量1000~2000）
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($data['troop']['total_num'])) {//不精确
                    //case a 军团里的兵
                    $targetPlayerArmy = $PlayerArmy->getByPlayerId($targetPlayerId);
                    $data['troop']['total_num'] = 0;
                    foreach($targetPlayerArmy as $k=>$v) {
                        if($v['status']==1) continue;
                        $data['troop']['total_num'] += array_sum(Set::extract('/soldier_num', $PlayerArmyUnit->getByArmyId($targetPlayerId, $v['id'])));
                    }
                    //case b 未编队的兵
                    $data['troop']['total_num'] += array_sum(Set::extract('/num', $PlayerSoldier->getByPlayerId($targetPlayerId)));
                    //模糊数字
                    $data['troop']['total_num'] = $this->getFuzzyNumber($data['troop']['total_num']);
                }
            case ($buildLevel>=1)://各资源拥有数量
                if(!isset($data['resource'])) {
                    $data['resource']['owned']         = [  
                                                            'gold'  => $targetPlayer['gold'],
                                                            'food'  => $targetPlayer['food'],
                                                            'wood'  => $targetPlayer['wood'],
                                                            'stone' => $targetPlayer['stone'],
                                                            'iron'  => $targetPlayer['iron'],
                                                        ];
                    $data['resource']['no_collection'] = $PlayerBuild->getResourceNoCollection($targetPlayerId);
                }
                $data['wall'] = ['current'=>$targetPlayer['wall_durability'], 'max'=>$targetPlayer['wall_durability_max']];

                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if($targetPlayerBuff==1) {//伪装术
                    $data['troop']['multi_number'] = 2;
                } else {
                    $data['troop']['multi_number'] = 1;
                }

                $data['type'] = 1;
                break;
            default: break;
        }
        return $data;
    }
    /**
     * 获取侦查联盟堡垒信息
     * @param  int  $playerId       
     * @param  int  $targetPlayerId 
     */
    public function getGuildBaseSpyInfo($playerId, $toMap){
        $Player         = new Player;
        $PlayerBuild    = new PlayerBuild;
        $Build          = new Build;
        $PlayerArmyUnit = new PlayerArmyUnit;
        $PlayerArmy     = new PlayerArmy;
        
        $player         = $Player->getByPlayerId($playerId);
        $playerBuild    = $PlayerBuild->getByOrgId($playerId, 12)[0];//获取玩家哨塔
        if(!$playerBuild) exit('illegal! Not exists tower!');
        
        $build          = $Build->dicGetOne($playerBuild['build_id']);
        $buildLevel     = intval($build['build_level']);

        $toMapId        = $toMap['id'];

        $data           = ['build_level'=>$buildLevel];

        $guildId        = $toMap['guild_id'];
        if($guildId) {
            $guildInfo                = (new Guild)->getGuildInfo($guildId);
            $data['guild_name']       = $guildInfo['name'];
            $data['map_element_id']   = $toMap['map_element_id'];
            $data['x']                = $toMap['x'];
            $data['y']                = $toMap['y'];
            $data['guild_short_name'] = $guildInfo['short_name'];
        }
        //满足条件，则剩余所有的信息都发送
        switch(TRUE) {
            case ($buildLevel>=50):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and (type=" . PlayerProjectQueue::TYPE_GUILDBASE_BUILD . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_REPAIR . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_DEFEND . ") order by id asc")->toArray();
                }

                if(count($queue)>0) {
                    $maxPowerPlayerId = 0;
                    $maxPower         = 0;

                    foreach($queue as $k=>$v) {//取最大战力的玩家id
                        $_player = $Player->getByPlayerId($v['player_id']);
                        $power   = $_player['power'];
                        if($power>$maxPower) {
                            $maxPower         = $power;
                            $maxPowerPlayerId = $v['player_id'];
                        }
                    }

                    $pms                   = (new PlayerMasterSkill)->getByPlayerId($maxPowerPlayerId);
                    $data['target_player'] = $Player->getByPlayerId($maxPowerPlayerId);
                    $data['master_skill']  = $pms;
                } else {
                    $data['master_skill'] = [];
                }
            case ($buildLevel>=44):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and (type=" . PlayerProjectQueue::TYPE_GUILDBASE_BUILD . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_REPAIR . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_DEFEND . ") order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $maxPowerPlayerId = 0;
                    $maxPower         = 0;

                    foreach($queue as $k=>$v) {//取最大战力的玩家id
                        $_player = $Player->getByPlayerId($v['player_id']);
                        $power   = $_player['power'];
                        if($power>$maxPower) {
                            $maxPower         = $power;
                            $maxPowerPlayerId = $v['player_id'];
                        }
                    }

                    $buffIds      = Buff::$buffIdForShow;
                    $data['buff'] = (new PlayerBuff)->getPlayerBuffs($maxPowerPlayerId, $buffIds);
                    /*foreach($buffIds as $buffId) {
                        $buff = $Buff->dicGetOne($buffId);
                        $data['buff'][$buff['name']] = $PlayerBuff->getPlayerBuff($firstPlayerId, $buff['name']);
                    }*/
                } else {
                    $data['buff'] = [];
                }
            case ($buildLevel>=39):
            case ($buildLevel>=35):
            case ($buildLevel>=31):
            case ($buildLevel>=27)://防守部队类型和准确数量
                $soldierFullInfoFlag = true;
            case ($buildLevel>=25)://防守部队武将信息
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and (type=" . PlayerProjectQueue::TYPE_GUILDBASE_BUILD . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_REPAIR . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_DEFEND . ")")->toArray();
                }
                if(count($queue)>0) {
                    $_army = [];
                    $_army['army_num'] = 0;
                    foreach($queue as $v) {
                        $tmpPlayerId = intval($v['player_id']);
                        $tmpPlayer   = $Player->getByPlayerId($tmpPlayerId);
                        $detail      = ['player_id' => $tmpPlayerId, 'nick' => $tmpPlayer['nick'], 'level' => $tmpPlayer['level'], 'avatar_id' => $tmpPlayer['avatar_id']];
                        $pau         = $PlayerArmyUnit->getByArmyId($tmpPlayerId, $v['army_id'], true);
                        $pau         = keepFields($pau, ['general_id', 'soldier_id', 'soldier_num', 'general_star']);

                        $_army['player_detail'][] = $detail;
                        foreach($pau as $v) {
                            if($v['soldier_id']) {
                                $_army['player_army'][]   = $v;
                                $_army['army_num'] += $v['soldier_num'];
                            }
                        }
                    }
                    if(isset($soldierFullInfoFlag)) {
                        $data['troop']['total_num'] = $_army['army_num'];
                    }
                    $data['troop']['army'] = ['player_detail'=>$_army['player_detail'], 'player_army'=>$_army['player_army']];
                } else {
                    $data['troop']['army'] = [];
                }
            case ($buildLevel>=23):
            case ($buildLevel>=19):
            case ($buildLevel>=16)://防守部队的准确军团数
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and (type=" . PlayerProjectQueue::TYPE_GUILDBASE_BUILD . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_REPAIR . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_DEFEND . ")")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num'] = count($queue);
                } else {
                    $data['troop']['army_num'] = 0;
                }
            case ($buildLevel>=13)://防守部队类型和各自大致数量(士兵类型)
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and (type=" . PlayerProjectQueue::TYPE_GUILDBASE_BUILD . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_REPAIR . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_DEFEND . ")")->toArray();
                }
                if(count($queue)>0) {
                    foreach($queue as $v) {
                        $pau = $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id']);
                        foreach($pau as $vv) {
                            $soldierId = $vv['soldier_id'];
                            if(!isset($data['troop']['soldier'][$soldierId])) {
                                $data['troop']['soldier'][$soldierId]['soldier_id'] = $soldierId;
                                $data['troop']['soldier'][$soldierId]['soldier_num'] = $vv['soldier_num'];
                            } else {
                                $data['troop']['soldier'][$soldierId]['soldier_num'] += $vv['soldier_num'];
                            }
                        }
                    }
                    if(!empty($data['troop']['soldier']))
                        $data['troop']['soldier'] = array_values($data['troop']['soldier']) ?: [];
                    else
                        $data['troop']['soldier'] = [];
                } else {
                    $data['troop']['soldier'] = [];
                }
            case ($buildLevel>=10):
            case ($buildLevel>=7)://防守部队大致军团（几个军团）
                $data['troop']['army_num_fuzzy'] = 0;
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and (type=" . PlayerProjectQueue::TYPE_GUILDBASE_BUILD . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_REPAIR . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_DEFEND . ")")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num_fuzzy'] = count($queue);
                }
                $data['troop']['army_num_fuzzy'] = $this->getFuzzyArmyNumber($data['troop']['army_num_fuzzy']);//模糊数字
            case ($buildLevel>=4)://防守部队大致数量（士兵数量1000~2000）
                if(!isset($soldierFullInfoFlag)) {
                    $data['troop']['total_num'] = 0;
                    if(!isset($queue)) {
                        $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and (type=" . PlayerProjectQueue::TYPE_GUILDBASE_BUILD . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_REPAIR . " or type=" . PlayerProjectQueue::TYPE_GUILDBASE_DEFEND . ")")->toArray();
                    }
                    foreach($queue as $v) {
                        $data['troop']['total_num'] += array_sum(Set::extract('/soldier_num', $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id'])));
                    }
                    //模糊数字
                    $data['troop']['total_num'] = $this->getFuzzyNumber($data['troop']['total_num']);
                }
                    
            case ($buildLevel>=1)://堡垒的城防值
                $data['durability'] = $toMap['durability'];
                $data['type'] = 2;
                break;
            default: break;
        }
        return $data;
    }
    /**
     * 获取侦查国王战-城寨的信息
     * @param  int  $playerId       
     * @param  int  $targetPlayerId 
     */
    public function getKingBaseSpyInfo($playerId, $toMap){
        $Player         = new Player;
        $PlayerBuild    = new PlayerBuild;
        $Build          = new Build;
        $PlayerArmyUnit = new PlayerArmyUnit;
        $PlayerArmy     = new PlayerArmy;
        
        $player         = $Player->getByPlayerId($playerId);
        $playerBuild    = $PlayerBuild->getByOrgId($playerId, 12)[0];//获取玩家哨塔
        if(!$playerBuild) exit('illegal! Not exists tower!');
        
        $build          = $Build->dicGetOne($playerBuild['build_id']);
        $buildLevel     = intval($build['build_level']);

        $toMapId        = $toMap['id'];

        $data           = ['build_level'=>$buildLevel];

        $guildId        = $toMap['guild_id'];
        if($guildId) {
            $guildInfo                = (new Guild)->getGuildInfo($guildId);
            $data['guild_name']       = $guildInfo['name'];
            $data['map_element_id']   = $toMap['map_element_id'];
            $data['x']                = $toMap['x'];
            $data['y']                = $toMap['y'];
            $data['guild_short_name'] = $guildInfo['short_name'];
        }
        $typeArr = [PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE, PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE, PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST];
        $typeSql = "(type=" . implode(" or type=", $typeArr) . ")";
        //满足条件，则剩余所有的信息都发送
        switch(TRUE) {
            case ($buildLevel>=50):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and {$typeSql} order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $maxPowerPlayerId = 0;
                    $maxPower         = 0;

                    foreach($queue as $k=>$v) {//取最大战力的玩家id
                        $_player = $Player->getByPlayerId($v['player_id']);
                        $power   = $_player['power'];
                        if($power>$maxPower) {
                            $maxPower         = $power;
                            $maxPowerPlayerId = $v['player_id'];
                        }
                    }

                    $pms                   = (new PlayerMasterSkill)->getByPlayerId($maxPowerPlayerId);
                    $data['target_player'] = $Player->getByPlayerId($maxPowerPlayerId);
                    $data['master_skill']  = $pms;
                } else {
                    $data['master_skill'] = [];
                }
            case ($buildLevel>=44):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and {$typeSql} order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $maxPowerPlayerId = 0;
                    $maxPower         = 0;

                    foreach($queue as $k=>$v) {//取最大战力的玩家id
                        $_player = $Player->getByPlayerId($v['player_id']);
                        $power   = $_player['power'];
                        if($power>$maxPower) {
                            $maxPower         = $power;
                            $maxPowerPlayerId = $v['player_id'];
                        }
                    }

                    $buffIds    = Buff::$buffIdForShow;
					$data['buff'] = (new PlayerBuff)->getPlayerBuffs($maxPowerPlayerId, $buffIds);
                    /*foreach($buffIds as $buffId) {
                        $buff = $Buff->dicGetOne($buffId);
                        $data['buff'][$buff['name']] = $PlayerBuff->getPlayerBuff($firstPlayerId, $buff['name']);
                    }*/
                } else {
                    $data['buff'] = [];
                }
            case ($buildLevel>=39):
            case ($buildLevel>=35):
            case ($buildLevel>=31):
            case ($buildLevel>=27)://防守部队类型和准确数量
            case ($buildLevel>=25)://防守部队武将信息
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and {$typeSql}")->toArray();
                }
                if(count($queue)>0) {
                    $_army = [];
                    $_army['army_num'] = 0;
                    foreach($queue as $v) {
                        $tmpPlayerId = intval($v['player_id']);
                        $tmpPlayer   = $Player->getByPlayerId($tmpPlayerId);
                        $detail      = ['player_id' => $tmpPlayerId, 'nick' => $tmpPlayer['nick'], 'level' => $tmpPlayer['level'], 'avatar_id' => $tmpPlayer['avatar_id']];
                        $pau         = $PlayerArmyUnit->getByArmyId($tmpPlayerId, $v['army_id'], true);
                        $pau         = keepFields($pau, ['general_id', 'soldier_id', 'soldier_num', 'general_star']);

                        $_army['player_detail'][] = $detail;
                        foreach($pau as $v) {
                            if($v['soldier_id']) {
                                $_army['player_army'][]   = $v;
                                $_army['army_num'] += $v['soldier_num'];
                            }
                        }
                    }
                    if(isset($soldierFullInfoFlag)) {
                        $data['troop']['total_num'] = $_army['army_num'];
                    }
                    $data['troop']['army'] = ['player_detail'=>$_army['player_detail'], 'player_army'=>$_army['player_army']];
                } else {
                    $data['troop']['army'] = [];
                }
            case ($buildLevel>=23):
            case ($buildLevel>=19):
            case ($buildLevel>=16)://防守部队的准确军团数
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and {$typeSql}")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num'] = count($queue);
                } else {
                    $data['troop']['army_num'] = 0;
                }
            case ($buildLevel>=13)://防守部队类型和各自大致数量(士兵类型)
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and {$typeSql}")->toArray();
                }
                if(count($queue)>0) {
                    foreach($queue as $v) {
                        $pau = $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id']);
                        foreach($pau as $vv) {
                            $soldierId = $vv['soldier_id'];
                            if(!isset($data['troop']['soldier'][$soldierId])) {
                                $data['troop']['soldier'][$soldierId]['soldier_id'] = $soldierId;
                                $data['troop']['soldier'][$soldierId]['soldier_num'] = $vv['soldier_num'];
                            } else {
                                $data['troop']['soldier'][$soldierId]['soldier_num'] += $vv['soldier_num'];
                            }
                        }
                    }
                    if(!empty($data['troop']['soldier']))
                        $data['troop']['soldier'] = array_values($data['troop']['soldier']) ?: [];
                    else
                        $data['troop']['soldier'] = [];
                } else {
                    $data['troop']['soldier'] = [];
                }
            case ($buildLevel>=10):
            case ($buildLevel>=7):
            case ($buildLevel>=4)://城防设施大致数量（陷阱数量1000~2000）  //e.g.7级的已经做好
                $data['troop']['army_num_fuzzy'] = 0;
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and {$typeSql}")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num_fuzzy'] = count($queue);
                }
                $data['troop']['army_num_fuzzy'] = $this->getFuzzyArmyNumber($data['troop']['army_num_fuzzy']);//模糊数字
            case ($buildLevel>=1)://防守部队大致数量（士兵数量1000~2000）
                $data['troop']['total_num'] = 0;
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and {$typeSql}")->toArray();
                }
                foreach($queue as $v) {
                    $data['troop']['total_num'] += array_sum(Set::extract('/soldier_num', $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id'])));
                }
                //模糊数字
                $data['troop']['total_num'] = $this->getFuzzyNumber($data['troop']['total_num']);
                $data['type'] = 4;
                break;
            default: break;
        }
        return $data;
    }
    /**
     * 获取侦查联盟堡垒信息
     * @param  int  $playerId       
     * @param  int  $targetPlayerId 
     */
    public function getResourceSpyInfo($playerId, $toMap){
        $Player         = new Player;
        $PlayerBuild    = new PlayerBuild;
        $Build          = new Build;
        $PlayerArmyUnit = new PlayerArmyUnit;
        $PlayerArmy     = new PlayerArmy;
        
        $player         = $Player->getByPlayerId($playerId);
        $playerBuild    = $PlayerBuild->getByOrgId($playerId, 12)[0];//获取玩家哨塔
        if(!$playerBuild) exit('illegal! Not exists tower!');
        
        $build          = $Build->dicGetOne($playerBuild['build_id']);
        $buildLevel     = intval($build['build_level']);

        $toMapId        = $toMap['id'];

        $data           = ['build_level'=>$buildLevel];

        $data['map_element_id']   = $toMap['map_element_id'];
        $data['x']                = $toMap['x'];
        $data['y']                = $toMap['y'];
        //满足条件，则剩余所有的信息都发送
        switch(TRUE) {
            case ($buildLevel>=50):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $firstPlayerQueue = $queue[0];
                    $firstPlayerId = $firstPlayerQueue['player_id'];
                    $pms = (new PlayerMasterSkill)->getByPlayerId($firstPlayerId);
                    $data['target_player'] = $Player->getByPlayerId($firstPlayerId);
                    $data['master_skill'] = $pms;
                    // foreach($pms as $v) {
                    //     $data['master_skill'][] = ['talent_id'=>$v['talent_id'], 'enable'=>$v['enable']];
                    // }
                } else {
                    $data['master_skill'] = [];
                }
            case ($buildLevel>=44):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $firstPlayerQueue = $queue[0];
                    $firstPlayerId = $firstPlayerQueue['player_id'];
                    $Buff       = new Buff;
                    $PlayerBuff = new PlayerBuff;
                    $buffIds    = Buff::$buffIdForShow;
					$data['buff'] = (new PlayerBuff)->getPlayerBuffs($firstPlayerId, $buffIds);
                    /*foreach($buffIds as $buffId) {
                        $buff = $Buff->dicGetOne($buffId);
                        $data['buff'][$buff['name']] = $PlayerBuff->getPlayerBuff($firstPlayerId, $buff['name']);
                    }*/
                } else {
                    $data['buff'] = [];
                }
            case ($buildLevel>=39):
            case ($buildLevel>=35):
            case ($buildLevel>=31):
            case ($buildLevel>=27)://防守部队类型和准确数量
            case ($buildLevel>=25)://防守部队武将信息
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    foreach($queue as $v) {
                        $tmpPlayerId            = intval($v['player_id']);
                        $tmpPlayer              = $Player->getByPlayerId($tmpPlayerId);
                        $detail                 = ['player_id' => $tmpPlayerId, 'nick' => $tmpPlayer['nick'], 'level' => $tmpPlayer['level'], 'avatar_id' => $tmpPlayer['avatar_id']];
                        $pau                    = $PlayerArmyUnit->getByArmyId($tmpPlayerId, $v['army_id'], true);
                        $pau                    = keepFields($pau, ['general_id', 'soldier_id', 'soldier_num', 'general_star']);
                        $_army                  = [];
                        $_army['player_detail'] = $detail;
                        $_army['army_num']      = 0;
                        foreach($pau as $v) {
                            if($v['soldier_id']) {
                                $_army['player_army'][] = $v;
                                $_army['army_num'] += $v['soldier_num'];
                            }
                        }
                        $data['troop']['army'] = ['player_detail'=>$detail, 'player_army'=>$_army['player_army']];
                        $data['troop']['total_num'] = $_army['army_num'];
                        break;//只能有一个部队在采集野外
                    }
                } else {
                    $data['troop']['army'] = [];
                }
            case ($buildLevel>=23):
            case ($buildLevel>=19):
            case ($buildLevel>=16)://防守部队的准确军团数
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num'] = count($queue);
                } else {
                    $data['troop']['army_num'] = 0;
                }
            case ($buildLevel>=13)://防守部队类型和各自大致数量(士兵类型)
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    foreach($queue as $v) {
                        $pau = $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id']);
                        foreach($pau as $vv) {
                            $soldierId = $vv['soldier_id'];
                            if(!isset($data['troop']['soldier'][$soldierId])) {
                                $data['troop']['soldier'][$soldierId]['soldier_id'] = $soldierId;
                                $data['troop']['soldier'][$soldierId]['soldier_num'] = $vv['soldier_num'];
                            } else {
                                $data['troop']['soldier'][$soldierId]['soldier_num'] += $vv['soldier_num'];
                            }
                        }
                    }
                    if(!empty($data['troop']['soldier']))
                        $data['troop']['soldier'] = array_values($data['troop']['soldier']) ?: [];
                    else
                        $data['troop']['soldier'] = [];
                } else {
                    $data['troop']['soldier'] = [];
                }
            case ($buildLevel>=10):
            case ($buildLevel>=7)://城防设施大致数量（陷阱数量1000~2000）  //e.g.7级的已经做好
                $data['troop']['army_num_fuzzy'] = 0;
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num_fuzzy'] = count($queue);
                }
                $data['troop']['army_num_fuzzy'] = $this->getFuzzyArmyNumber($data['troop']['army_num_fuzzy']);//模糊数字
            case ($buildLevel>=4)://防守部队大致数量（士兵数量1000~2000）
                if(!isset($data['troop']['total_num'])) {
                    if(!isset($queue)) {
                        $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                    }
                    foreach($queue as $v) {
                        $data['troop']['total_num'] += array_sum(Set::extract('/soldier_num', $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id'])));
                    }
                    //模糊数字
                    $data['troop']['total_num'] = $this->getFuzzyNumber($data['troop']['total_num']);
                }
            case ($buildLevel>=1)://各资源拥有数量
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                $targetInfo = json_decode($queue[0]['target_info'], true);
                $collectedResource = ceil(min((time() - strtotime($queue[0]['create_time'])) * ($targetInfo['speed']/60), $targetInfo['resource']));
                $resourceMap = [9=>'gold',10=>'food',11=>'wood',12=>'stone',13=>'iron'];
                $data['resource']['owned'] = ['gold' => 0,'food' => 0,'wood' => 0,'stone' => 0,'iron' => 0,];
                $data['resource']['no_collection'] = ['gold' => 0,'food' => 0,'wood' => 0,'stone' => 0,'iron' => 0,];
                $data['resource']['owned'][$resourceMap[$toMap['map_element_origin_id']]] = $collectedResource;
                $data['resource']['no_collection'][$resourceMap[$toMap['map_element_origin_id']]] = $targetInfo['resource'] - $collectedResource;
                $data['type'] = 3;
                break;
            default: break;
        }
        return $data;
    }
    /**
     * 获取侦查联盟堡垒信息
     * @param  int  $playerId       
     * @param  int  $targetPlayerId 
     */
    public function getStrongholdSpyInfo($playerId, $toMap){
        $Player         = new Player;
        $PlayerBuild    = new PlayerBuild;
        $Build          = new Build;
        $PlayerArmyUnit = new PlayerArmyUnit;
        $PlayerArmy     = new PlayerArmy;
        
        $player         = $Player->getByPlayerId($playerId);
        $playerBuild    = $PlayerBuild->getByOrgId($playerId, 12)[0];//获取玩家哨塔
        if(!$playerBuild) exit('illegal! Not exists tower!');
        
        $build          = $Build->dicGetOne($playerBuild['build_id']);
        $buildLevel     = intval($build['build_level']);

        $toMapId        = $toMap['id'];

        $data           = ['build_level'=>$buildLevel];

        $data['map_element_id']   = $toMap['map_element_id'];
        $data['x']                = $toMap['x'];
        $data['y']                = $toMap['y'];
        //满足条件，则剩余所有的信息都发送
        switch(TRUE) {
            case ($buildLevel>=50):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $firstPlayerQueue = $queue[0];
                    $firstPlayerId = $firstPlayerQueue['player_id'];
                    $pms = (new PlayerMasterSkill)->getByPlayerId($firstPlayerId);
                    $data['target_player'] = $Player->getByPlayerId($firstPlayerId);
                    $data['master_skill'] = $pms;
                } else {
                    $data['master_skill'] = [];
                }
            case ($buildLevel>=44):
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $firstPlayerQueue = $queue[0];
                    $firstPlayerId = $firstPlayerQueue['player_id'];
                    $Buff       = new Buff;
                    $PlayerBuff = new PlayerBuff;
                    $buffIds    = Buff::$buffIdForShow;
					$data['buff'] = (new PlayerBuff)->getPlayerBuffs($firstPlayerId, $buffIds);
                    /*foreach($buffIds as $buffId) {
                        $buff = $Buff->dicGetOne($buffId);
                        $data['buff'][$buff['name']] = $PlayerBuff->getPlayerBuff($firstPlayerId, $buff['name']);
                    }*/
                } else {
                    $data['buff'] = [];
                }
            case ($buildLevel>=39):
            case ($buildLevel>=35):
            case ($buildLevel>=31):
            case ($buildLevel>=27)://防守部队类型和准确数量
            case ($buildLevel>=25)://防守部队武将信息
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    foreach($queue as $v) {
                        $tmpPlayerId            = intval($v['player_id']);
                        $tmpPlayer              = $Player->getByPlayerId($tmpPlayerId);
                        $detail                 = ['player_id' => $tmpPlayerId, 'nick' => $tmpPlayer['nick'], 'level' => $tmpPlayer['level'], 'avatar_id' => $tmpPlayer['avatar_id']];
                        $pau                    = $PlayerArmyUnit->getByArmyId($tmpPlayerId, $v['army_id'], true);
                        $pau                    = keepFields($pau, ['general_id', 'soldier_id', 'soldier_num', 'general_star']);
                        $_army                  = [];
                        $_army['player_detail'] = $detail;
                        $_army['army_num']      = 0;
                        foreach($pau as $v) {
                            if($v['soldier_id']) {
                                $_army['player_army'][] = $v;
                                $_army['army_num'] += $v['soldier_num'];
                            }
                        }
                        $data['troop']['army'] = ['player_detail'=>$detail, 'player_army'=>$_army['player_army']];
                        $data['troop']['total_num'] = $_army['army_num'];
                        break;//只能有一个部队在采集野外
                    }
                } else {
                    $data['troop']['army'] = [];
                }
            case ($buildLevel>=23):
            case ($buildLevel>=19):
            case ($buildLevel>=16)://防守部队的准确军团数
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num'] = count($queue);
                } else {
                    $data['troop']['army_num'] = 0;
                }
            case ($buildLevel>=13)://防守部队类型和各自大致数量(士兵类型)
                if(!isset($data['troop'])) {
                    $data['troop'] = [];
                }
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    foreach($queue as $v) {
                        $pau = $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id']);
                        foreach($pau as $vv) {
                            $soldierId = $vv['soldier_id'];
                            if(!isset($data['troop']['soldier'][$soldierId])) {
                                $data['troop']['soldier'][$soldierId]['soldier_id'] = $soldierId;
                                $data['troop']['soldier'][$soldierId]['soldier_num'] = $vv['soldier_num'];
                            } else {
                                $data['troop']['soldier'][$soldierId]['soldier_num'] += $vv['soldier_num'];
                            }
                        }
                    }
                    if(!empty($data['troop']['soldier']))
                        $data['troop']['soldier'] = array_values($data['troop']['soldier']) ?: [];
                    else
                        $data['troop']['soldier'] = [];
                } else {
                    $data['troop']['soldier'] = [];
                }
            case ($buildLevel>=10):
            case ($buildLevel>=7)://城防设施大致数量（陷阱数量1000~2000）  //e.g.7级的已经做好
                $data['troop']['army_num_fuzzy'] = 0;
                if(!isset($queue)) {
                    $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                }
                if(count($queue)>0) {
                    $data['troop']['army_num_fuzzy'] = count($queue);
                }
                $data['troop']['army_num_fuzzy'] = $this->getFuzzyArmyNumber($data['troop']['army_num_fuzzy']);//模糊数字
            case ($buildLevel>=4)://防守部队大致数量（士兵数量1000~2000）
                if(!isset($data['troop']['total_num'])) {
                    if(!isset($queue)) {
                        $queue = PlayerProjectQueue::find("to_map_id={$toMapId} and status=1 and type=" . PlayerProjectQueue::TYPE_COLLECT_ING . " order by id asc")->toArray();
                    }
                    foreach($queue as $v) {
                        $data['troop']['total_num'] += array_sum(Set::extract('/soldier_num', $PlayerArmyUnit->getByArmyId($v['player_id'], $v['army_id'])));
                    }
                    //模糊数字
                    $data['troop']['total_num'] = $this->getFuzzyNumber($data['troop']['total_num']);
                }
            case ($buildLevel>=1)://各资源拥有数量
                $data['type'] = 5;
                break;
            default: break;
        }
        return $data;
    }
    /**
     * 取模糊数字
     * @param  int $number e.g. 12345, 1234
     * @return int   10000, 1000
     */
    private function getFuzzyNumber($number){    
        $numeric = strval($number);
        if(strlen($numeric)==1) return 10;
        return intval($numeric[0].str_repeat('0', strlen($numeric)-1));
    }
    /**
     * 获取军团的模糊数字
     * @param  int $number 
     * @return int         
     */
    private function getFuzzyArmyNumber($number){
        if(in_array($number, range(0,3))) return '1~3';
        if(in_array($number, range(4,7))) return '4~7';
        if(in_array($number, range(8,11))) return '8~11';
        if(in_array($number, range(12, 16))) return '12~16';
        if(in_array($number, range(17, 25))) return '17~25';
    }
    /**
     * 玩家升级操作方法
     * 
     * ```php
     * 升级相关：1天赋，2可携带最大武将数，3行动力回满 4notification
     * ```
     * @param  int $playerId 
     */
    public function levelUp($playerId) {
        $re = self::findFirst($playerId);
        $prevLevel = $re->level;
        if($re->level >= PLAYER_MAX_LEVEL) return;//已经升级到最大，不作升级处理
        if($re->current_exp >= $re->next_exp) {//玩家升级到下一级
            $Master = new Master;
            $nextLevelMaster = $Master->dicGetOneByExp($re->current_exp);
            $nextExp = $nextLevelMaster['current']['exp'];
            if($nextLevelMaster['current']['level']<PLAYER_MAX_LEVEL) {
                $nextExp = $nextLevelMaster['next']['exp'];
            }

            $buff2 = (new PlayerBuff)->getPlayerBuff($playerId, 'move_limit_plus_exact_value');//行动力上限buff

            $nextLevel = $re->level              = $nextLevelMaster['current']['level'];
			$re->levelup_time = date('Y-m-d H:i:s');
            $re->next_exp           = $nextExp;
            $re->talent_num_remain  += ($nextLevelMaster['current']['talent_num']-$re->talent_num_total);//剩余可用天赋值
            $re->talent_num_total   = $nextLevelMaster['current']['talent_num'];
            $re->general_num_remain += ($nextLevelMaster['current']['max_general']-$re->general_num_total);
            $re->general_num_total  = $nextLevelMaster['current']['max_general'];

			if($re->move< ($re->move_max + $buff2)) {//行动力回满
				$re->move = $re->move_max + $buff2;
	            $re->move_in_time       = 0;
			}

            $affectedRows = $this->updateAll([
                'level'              => $re->level,
                'levelup_time'       => q($re->levelup_time),
                'next_exp'           => $re->next_exp,
                'talent_num_remain'  => $re->talent_num_remain,
                'talent_num_total'   => $re->talent_num_total,
                'general_num_remain' => $re->general_num_remain,
                'general_num_total'  => $re->general_num_total,
                'move'               => $re->move,
                'move_in_time'       => $re->move_in_time,
            ], ['id'=>$playerId]);
            
            if($affectedRows>0) {//升级成功
                global $config;
                $serverId = $config->server_id;
                (new PlayerServerList)->updateInfo($re->uuid, $serverId, 'level', $nextLevel);//等级更改

                (new PlayerTarget)->updateTargetCurrentValue($playerId, 3, $nextLevel-$prevLevel);//新手目标
                $this->refreshPower($playerId, 'master_power');
                $this->clearDataCache($playerId);//清缓存

                //升级奖励
                foreach(range($prevLevel+1, $nextLevel) as $v) {
                    $master = $Master->dicGetOne($v);
                    $drop[] = $master['drop'];
                }
                // $drop = $nextLevelMaster['current']['drop'];
                (new Drop)->gain($playerId, $drop, 1, '玩家升级');
                
                // (new PlayerNotification)->addPlayerNotification($playerId, (new Notification)->dicGetOne('player_levelup'));
                (new PlayerInfo)->alter($playerId, ['level_up_animation'=>1]);//动画 //TODO放完动画后需要改为0
				
				//府衙等级12、22、37开启的礼包
				/*if(($nextLevel >= 12 && $prevLevel < 12) ||
					($nextLevel >= 22 && $prevLevel < 22) ||
					($nextLevel >= 37 && $prevLevel < 37)
				){
					if($nextLevel >= 37){
						$l = 37;
					}elseif($nextLevel >= 22){
						$l = 22;
					}else{
						$l = 12;
					}
					(new PlayerInfo)->updateGiftBeginTime($playerId, 'gift_lv'.$l.'_begin_time');
				}*/
            }
        }
    }
    /**
     * 增加主公经验, 策划：主公加经验和升级是分开操作
     *
     * @param  int $playerId 
     * @param  int $exp 
     */
    public function addExp($playerId, $exp){
        $master = (new Master)->dicGetOne(PLAYER_MAX_LEVEL);
        $maxExp = $master['exp'];
        if($this->updateAll(['current_exp'=>"LEAST(current_exp+{$exp}, {$maxExp})"], ['id'=>$playerId, 'level <'=>PLAYER_MAX_LEVEL])) {
            $this->clearDataCache($playerId);//清缓存
            $this->levelUp($playerId);
            return true;
        }
        return false;
    }
    /**
     * 玩家回复行动力,每次更改行动力前，调用此方法回复玩家行动力
     * 
     * @param  int $playerId 
     */
    public function restorePlayerMove($playerId){
        $re         = self::findFirst($playerId);
        if(!$re) return false;
        $moveInTime = $re->move_in_time;
        $move       = $re->move;

        if($moveInTime && $moveInTime<=time()) {
            $Starting             = new Starting;
            $PlayerBuff           = new PlayerBuff;
            $playerMoveInDuration = $Starting->getValueByKey("move_in_time");
            
            $buff                 = $PlayerBuff->getPlayerBuff($playerId, 'move_restore_speed');
            $playerMoveInDuration = $playerMoveInDuration/(1+$buff);
            $subTime              = time() - $moveInTime;
            $movePlus             = ceil($subTime/$playerMoveInDuration);
            
            $buff2                = $PlayerBuff->getPlayerBuff($playerId, 'move_limit_plus_exact_value');//行动力上限buff
            //正常更新,move不会超过move_max
            $affectedRows         = $this->updateAll(['move'=>'move+'.$movePlus,'move_in_time'=>time()+$playerMoveInDuration],['id'=> $playerId,'move_max >='=>"move+{$movePlus}-{$buff2}"]);
            //如果没成功，则超过move_max，直接move=move_max
            if($affectedRows==0) {
                $this->updateAll(['move'=>"move_max+{$buff2}",'move_in_time'=>0],['id'=> $playerId,'move_max <'=>"move+{$movePlus}-{$buff2}"]);
            }
            $this->clearDataCache($playerId);//清缓存
            //再取一次
            $r    = self::findFirst($playerId);
            $move = $r->move;
        }
        return $move;
    }
    /**
     * 更新玩家行动力
     *
     * @param  int $playerId 
     * @param  int $move 
     */
    public function updateMove($playerId, $move=0){
        $this->restorePlayerMove($playerId);//恢复行动力first
        
        $re                   = self::findFirst($playerId);
        //case a 根据move_in_time回行动力
        $moveInTime           = $re->move_in_time;
        
        $Starting             = new Starting;
        $playerMoveInDuration = $Starting->getValueByKey("move_in_time");
        
        $buff                 = (new PlayerBuff)->getPlayerBuff($playerId, 'move_restore_speed');
        $playerMoveInDuration = $playerMoveInDuration/(1+$buff);
        
        $buff2                = (new PlayerBuff)->getPlayerBuff($playerId, 'move_limit_plus_exact_value');//行动力上限buff
        if($moveInTime!=0 && $moveInTime<=time()) {//需要恢复行动力
            $subTime = time()-$moveInTime;
            $movePlus = floor($subTime/$playerMoveInDuration);
            if($movePlus+$re->move >= $re->move_max+$buff2) {
                $re->move = $re->move_max+$buff2;
                $re->move_in_time = 0;
            } else {
                $re->move += $movePlus;
                $re->move_in_time = time() + $playerMoveInDuration;
            }
            $this->updateAll(['move'=>$re->move, 'move_in_time'=>$re->move_in_time], ['id'=>$playerId]);
            $this->clearDataCache($playerId);//清缓存
        }
        //case b 如果有行动力改变，再继续相关操作 默认$move=0情况，不进行此步
        if($move < 0) {//扣行动力
            if($re->move<abs($move)) {//超过剩余行动力
                return false;
            }
            $re->move -= abs($move);
            if($re->move<$re->move_max+$buff2) {
                $re->move_in_time = time() + $playerMoveInDuration;
            } else {
                $re->move_in_time = 0;
            }
            $this->updateAll(['move'=>$re->move, 'move_in_time'=>$re->move_in_time], ['id'=>$playerId]);
            $this->clearDataCache($playerId);//清缓存
            
        } elseif ($move > 0) {//增加行动力
            if($re->move<$re->move_max+$buff2) {
                $re->move += $move;
                if($re->move >= $re->move_max+$buff2) {
                    $re->move_in_time = 0;
                }
                $this->updateAll(['move'=>$re->move, 'move_in_time'=>$re->move_in_time], ['id'=>$playerId]);
                $this->clearDataCache($playerId);//清缓存
            } else {//超过最大move_max
                return false;
            }
        }
        return true;
    }
    /**
     * 设置联盟id 以及camp_id
     * @param  int $playerId
     * @param  int $campId
     * @param  int $guildId  联盟id
     */
    public function setGuildId($playerId, $guildId, $campId=0) {
        $PlayerInfo = new PlayerInfo;
        $playerInfo = $PlayerInfo->getByPlayerId($playerId);
        if($guildId!=0 && $playerInfo['first_join_guild']!=1) {//第一次加入联盟,发放奖励
            $guild = (new Guild)->getGuildInfo($guildId);
            if($playerId!=$guild['leader_player_id']) {//不是帮主
                $PlayerMail = new PlayerMail;
                $item0      = (new Starting)->getValueByKey('jion_alliance_mail_drop');
                $item0      = explode(',', $item0);
                $item       = $PlayerMail->newItem($item0[0], $item0[1], $item0[2]);
                $PlayerMail->sendSystem($playerId, PlayerMail::TYPE_FIRST_JOIN_GUILD, 'system mail', '', 0, [], $item);
                $PlayerInfo->alter($playerId, ['first_join_guild'=>1]);
            }
        }
        $updateData['guild_id'] = $guildId;
        if($campId!=0) {//更改阵营
            $CityBattleCampNumber = new CityBattleCampNumber;
            $CityBattleCampNumber->inc($campId);
            if($playerInfo['camp_id']!=0) {
                $CityBattleCampNumber->dec($playerInfo['camp_id']);
            }
            $updateData['camp_id'] = $campId;
            socketSend(['Type'=>'change_player_camp', 'Data'=>['player_id'=>$playerId, 'camp_id'=>$campId]]);
        }
        $this->updateAll($updateData, ['id'=>$playerId]);
        $this->clearDataCache($playerId);

        (new PlayerTarget)->updateTargetCurrentValue($playerId, 33, 1, false);//新手目标

        //更改map中的guild_id信息
        $Map = new Map;
        $Map->changePlayerGuildId($playerId, $guildId);
        //更改Queue中guild_id
        $PlayerProjectQueue = new PlayerProjectQueue;
        $PlayerProjectQueue->upGuildId($playerId, $guildId);
        // $player = $this->getByPlayerId($playerId);
        // $Map->updateAll(['guild_id'=>$guildId], ['id'=>$player['map_id']]);
        // $Map->clearMapCache($player['x'], $player['y']);
    }
    /**
     * 删除玩家 //TEST
     * 
     * @param  int $playerId 
     */
    public function deletePlayer($id){
        $re = self::findFirst($id);
        $re->delete();
        $this->clearDataCache($id);//清缓存
        Cache::db()->delete("uuid:".$re->uuid);
        return $re->toArray();
    }
    /**
     * 检查资源是否足够
     * @param  [type]  $playerId        [description]
     * @param  array   $needResourceArr 所需资源数量 ex: array('gold'=>10000, 'wood'=>10000, 'iron'=>500)
     * @return boolean                  [description]
     */
    function hasEnoughResource($playerId, array $needResourceArr){
        $result = true;
        $playerInfo = $this->getByPlayerId($playerId);
        foreach ($needResourceArr as $key=>$value) {
            if($playerInfo[$key]<abs($value)){
                $result = false;
                break;
            }
        }
        return $result;
    }
    
    /**
     * 资源变更
     * @param  int $playerId 
     * @param  array  $resource ['gold'=>9, 'food'=>-10, 'wood'=>11, 'stone'=>12, 'iron'=>13]
     */
    public function updateResource($playerId, array $resource){
        if(empty($resource)) return false;

		$condition = ['id'=>$playerId];
        foreach($resource as $k=>&$v) {
            if($v==0)  {
                unset($resource[$k]);
                continue;
            }
            elseif($v>0) {
                $v = $k . '+' . abs($v);
            } 
            else {
				$condition[$k.' >='] = abs($v);
                $v = $k . '-' . abs($v);
                $v = "IF({$v}<0,0,{$v})";
            }
        }
        unset($v);
		
        $this->affectedRows = $this->updateAll($resource, $condition);
        if($this->affectedRows>0) {
            $this->clearDataCache($playerId);//清缓存
            return true;
        }
        return false;
    }
    /**
     * 元宝变更
     * 
     * @param <type> $playerId 
     * @param <type> $point 增加/扣除元宝
     * @param <type> $giftFlag  true：gift；false：rmb
     * @param <type> $recordVars  元宝日志
     * 
     * @return bool
     */
    
    public function updateGem($playerId, $point, $giftFlag = true, $recordVars = array(), $dropId=0){
        if($point < 0){
            $_point = abs($point);
            if(false === $giftFlag){
                $sql = 'UPDATE player SET
                `rmb_gem` = `rmb_gem` - @sub_rmb_gem := IF(`rmb_gem` >= '.$_point.', '.$_point.', `rmb_gem`),
                `gift_gem` = `gift_gem` - (@sub_gift_gem := '.$_point.' - @sub_rmb_gem)
                WHERE `id` = '.$playerId;
            }else{
                $sql = 'UPDATE player SET
                `gift_gem` = `gift_gem` - @sub_gift_gem := IF(`gift_gem` >= '.$_point.', '.$_point.', `gift_gem`),
                `rmb_gem` = `rmb_gem` - ('.$_point.' - @sub_gift_gem)
                WHERE `id` = '.$playerId;
            }
            $sql .= ' AND `rmb_gem`+`gift_gem` >='.$_point;
        }else{
            $sql = 'UPDATE player SET '.
            ($giftFlag ? '`gift_gem` = `gift_gem` + '.$point : '`rmb_gem` = `rmb_gem` + '.$point).'
            WHERE `id` = '.$playerId;
        }
        if(!$this->sqlExec($sql)){
            return false;
        }
        //增加/消费记录
        if($point < 0 && false !== $recordVars){
            $d = $this->sqlGet('select @sub_gift_gem');
            $subGiftPoint = $d[0]['@sub_gift_gem']*1;
            $subRmbPoint = $_point - $subGiftPoint*1;
            if(is_array($recordVars)){
                $cost = @$recordVars['cost']*1;
                $memo = @$recordVars['memo'].'';
            }else{
                $cost = 0;
                $memo = @$recordVars;
            }
            (new PlayerConsumeLog)->add($playerId, $subRmbPoint, $subGiftPoint, $cost, $memo);
        }elseif($point > 0 && false !== $recordVars){
            $addRmbPoint = 0;
            $addGiftPoint = 0;
            if($giftFlag){
                $addGiftPoint = $point;
            }else{
                $addRmbPoint = $point;
            }
            (new PlayerGemLog)->add($playerId, $addRmbPoint, $addGiftPoint, $recordVars, $dropId);
        }
		
		if($point < 0){
			if(!(new PlayerActivityConsume)->addGem($playerId, $_point)){
				return false;
			}
			
			//新人消耗活动
			if(!(new PlayerNewbieActivityConsume)->addGem($playerId, $_point)){
				return false;
			}
		}
        
        $this->clearDataCache($playerId);//清缓存
        return true;
    }
    /**
     * 获取最大建造队列数量
     * 
     * 
     * @return <type>
     */
    public function getMaxQueueNum(){
        return 1;
    }
    /**
     * 开始或延长燃烧状态
     * 
     * @param [type] $playerId [description]
     *
     * @return boolean
     */
    public function setFireStatus($playerId){
        $this->inventoryWallDurability($playerId);//清算城墙耐久情况

        $second = self::FireTime;
        $player = $this->getByPlayerId($playerId);

        if(!empty($player)){
            if($player['fire_end_time']<=time()){
                $newFireEndTime = date("Y-m-d H:i:s", time()+$second);
            }elseif($player['fire_end_time']+$second>time()+36000){
                $newFireEndTime = date("Y-m-d H:i:s", time()+36000);
            }else{
                $newFireEndTime = date("Y-m-d H:i:s", $player['fire_end_time']+$second);
            }

            $this->updateAll(['durability_last_update_time'=>qd(), 'fire_end_time'=>q($newFireEndTime)], ['id'=>$playerId]);
            $this->clearDataCache($playerId);//清缓存
            return true;
        }else{
            return false;
        }
    }
    /**
     * 清算城墙耐久
     * 返回城墙实际耐久度
     * 
     * @param  [type] $playerId [description]
     */
    public function inventoryWallDurability($playerId, $updateData=true, $player=[]){
        if(empty($player)){
            $player = $this->getByPlayerId($playerId);
        }else{
            $playerId = $player['id'];
        }
        if(!empty($player) && !empty($player['durability_last_update_time']) && !empty($player['fire_end_time'])){
            $durability = $player['wall_durability'];
            
            if($player['durability_last_update_time']<time() && time()<$player['fire_end_time']){
                $newDurability = floor($durability-(time()-$player['durability_last_update_time'])/self::FireRate);
            }elseif($player['durability_last_update_time']<$player['fire_end_time'] && $player['fire_end_time']<time()){
                $newDurability = floor($durability-($player['fire_end_time']-$player['durability_last_update_time'])/18);
            }else{
                $newDurability = $durability;
            }

            if($newDurability<0){
                $newDurability = 0;
            }elseif($newDurability>=$player['wall_durability_max']){
                $newDurability = $player['wall_durability_max'];
            }

            if($updateData){
                $updateArr = ['wall_durability'=>$newDurability, 'durability_last_update_time'=>qd()];
                $this->updateAll($updateArr, ['id'=>$playerId]);
                $this->clearDataCache($playerId);//清缓存
            }

            return $newDurability*1;
        }else{
            return $player['wall_durability']*1;
        }
    }

    /**
     * 灭火
     * 
     * @param  [type] $playerId [description]
     */
    public function clearFire($playerId){
        $player = $this->getByPlayerId($playerId);
        $PlayerBuff = new PlayerBuff;
        $wallDurabilityBuff = $PlayerBuff->getPlayerBuff($playerId, "wall_defense_limit_plus");
        if(!empty($player) && !empty($player['durability_last_update_time']) && !empty($player['fire_end_time']) 
            && $player['durability_last_update_time']<time() && time()<$player['fire_end_time'] && $this->updateGem($playerId, -50, true, ['cost'=>10301, 'memo'=>'城墙灭火'])){
            $durability = $player['wall_durability'];
            $newDurability = floor($durability-(time()-$player['durability_last_update_time'])/18);
            if($newDurability<0){
                $newDurability = 0;
            }elseif($newDurability>=$player['wall_durability_max']){
                $newDurability = $player['wall_durability_max'];
            }
            $updateArr = ['wall_durability'=>$newDurability, 'durability_last_update_time'=>"'".date("Y-m-d H:i:s")."'", 'fire_end_time'=>"'".date("Y-m-d H:i:s", time())."'"];
            $this->updateAll($updateArr, ['id'=>$playerId]);
            $this->clearDataCache($playerId);//清缓存
            return true;
        }
        return false;
    }

    /**
     * 修理
     * 
     * @param  [type] $playerId [description]
     */
    public function restoreWallDurability($playerId, $restoreNum=0){
        $Starting = new Starting;
        if($restoreNum==0){
            $restoreNum = $Starting->getValueByKey("wall_hp_add_num");
            $itemRestoreFlag = false;   
        }else{
            $itemRestoreFlag = true;
        }
        $restoreCD = $Starting->getValueByKey("wall_hp_add_time");
        $this->inventoryWallDurability($playerId);

        $player = $this->getByPlayerId($playerId);
        if(!empty($player) && ($itemRestoreFlag || $player['last_repair_time']+$restoreCD<time()) ){
            $PlayerBuff = new PlayerBuff;
            $durability = $player['wall_durability'];
            $newDurability = $durability+$restoreNum;
            if($newDurability>=$player['wall_durability_max']){
                $newDurability = $player['wall_durability_max'];
                $updateArr = ['wall_durability'=>$newDurability, 'last_repair_time'=>"'".date("Y-m-d H:i:s")."'", 'fire_end_time'=>qd()];
            }else{
                $updateArr = ['wall_durability'=>$newDurability, 'last_repair_time'=>"'".date("Y-m-d H:i:s")."'"];
            }
            $this->updateAll($updateArr, ['id'=>$playerId]);
            $this->clearDataCache($playerId);//清缓存
            return true;
        }
        return false;
    }

    /**
     * 获取最大队列数
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
    public function getQueueNum($playerId){
        $num = 1;
        //buff
        $num += (new PlayerBuff)->getPlayerBuff($playerId, 'army_queue_num');
        return $num;
    }
	
	/**
     * 获取最大队列数
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
    public function getMaxArmyNum($playerId){
       $num = (new Starting)->dicGetOne('army_num');
        //buff
        $num += (new PlayerBuff)->getPlayerBuff($playerId, 'corps_in_control');
		
		//vip等级
		$player = $this->getByPlayerId($playerId);
		if($player['vip_level'] >= 6){
			$num++;
		}
        return $num;
    }

    /**
     * 军团最大武将数
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function getArmyGeneralNum($playerId){
        $num = (new Starting)->dicGetOne('army_general_num');
        //todo 判断vip
        
        //buff
        $num += (new PlayerBuff)->getPlayerBuff($playerId, 'deputy_per_corp');
        return $num;
    }
	
    /**
     * 新手保护
     * 
     * @param <type> $playerId 
     * @param <type> $second 
     * 
     * @return <type>
     */
	public function setFreshAvoidBattleTime($playerId, $second){
		$time = time() + $second;
		return $this->alter($playerId, ['fresh_avoid_battle_time'=>"'".date('Y-m-d H:i:s', $time)."'"]);
	}
	
	public function offFreshAvoidBattle($playerId){
		return $this->alter($playerId, ['fresh_avoid_battle_time'=>"'0000-00-00 00:00:00'"]);
	}

	public function setAvoidBattleTime($playerId, $second){
		//$player = $this->getByPlayerId($playerId);
		/*if($player['avoid_battle_time'] < time()){
			$time = time() + $second;
		}else{
			$time = $player['avoid_battle_time'] + $second;
		}*/
		$time = time() + $second;
		return $this->alter($playerId, ['avoid_battle_time'=>"'".date('Y-m-d H:i:s', $time)."'"]);
	}
	
	public function setAvoidBattle($playerId, $flag){
		$data = ['avoid_battle'=>$flag];
		if(!$flag){
			$data['avoid_battle_time'] = "'0000-00-00 00:00:00'";
			
			$ret = $this->updateAll($data, ['id'=>$playerId, 'avoid_battle_time >'=>q(date('Y-m-d H:i:s'))]);
			$this->clearDataCache($playerId);
			if($ret)
				(new PlayerCommonLog)->add($playerId, ['type'=>'玩家保护罩解除']);
			return $ret;
		}else{
			return $this->alter($playerId, $data);
			
		}
	}

	public function isAvoidBattle($player){
		if($player['avoid_battle'] || $player['is_in_cross']){
			return true;
		}
		if(is_numeric($player['avoid_battle_time'])){
			$abt = $player['avoid_battle_time'];
		}else{
			$abt = strtotime($player['avoid_battle_time']);
		}
		if($abt >= time()){
			return true;
		}
		return false;
	}
	
	public function offAvoidBattle($playerId){
		$this->offFreshAvoidBattle($playerId);
		return $this->setAvoidBattle($playerId, 0);
	}

    public function addKillSoldierNum($playerId, $num){
        return $this->alter($playerId, ['kill_soldier_num'=>'kill_soldier_num+'.$num]);
    }
	
	public function addJiangyinNum($playerId, $num){
        return $this->alter($playerId, ['jiangyin'=>'jiangyin+'.$num]);
    }

	public function addMonsterKillCount($playerId, $num){
		(new PlayerTarget)->updateTargetCurrentValue($playerId, 8, $num);
		return $this->alter($playerId, ['monster_kill_counter'=>'monster_kill_counter+'.$num]);
	}
	
	public function addVipExp($playerId, $exp, &$lvup=false){
		//取出数据
		$player = $this->getByPlayerId($playerId);
		if(!$player)
			return false;
		
		//计算新exp
		$newExp = $player['vip_exp'] + $exp;
		$nextLv = $player['vip_level'] + 1;
		if($player['vip_level'] >= PLAYER_MAX_VIPLEVEL)
			return true;
		
		//判断升级
		$Vip = new Vip;
		$vip = $Vip->dicGetAll();
		
		$vip = Set::sort($vip, '{n}.vip_level', 'asc');
		
		$newLv = $player['vip_level'];
		foreach($vip as $_vlv){
			if($_vlv['vip_level'] == $nextLv){
				if($newExp >= $_vlv['vip_exp']){
					$newLv = $_vlv['vip_level'];
					$nextLv = $newLv+1;
					$newExp -= $_vlv['vip_exp'];
				}
			}elseif($_vlv['vip_level'] > $nextLv){
				break;
			}
		}
		
		
		if($newLv == PLAYER_MAX_VIPLEVEL){
			$newExp = 0;
		}
		
		if($this->alter($playerId, ['vip_exp'=>$newExp, 'vip_level'=>$newLv])){
			$ret = true;
			if($newLv > $player['vip_level']){
				(new PlayerTarget)->updateTargetCurrentValue($playerId, 4, $newLv, false);
				
				$lvup = true;
				
				//给与一天vip
				$Item = new Item;
				$item = $Item->dicGetOne(23703);//道具id为vip一天
				$Drop = new Drop;
				$num = $newLv - $player['vip_level'];
				
				//获取原vip buff
				$PlayerBuffTemp = new PlayerBuffTemp;
				$pbt = $PlayerBuffTemp->getPlayerBuff($playerId, 'vip_active');
				//计算vip剩余时间
				if($pbt){
					$addTime = $pbt[0]['expire_time'] - time();
				}else{
					$addTime = 0;
				}

				//删除原vip buff
				$PlayerBuffTemp->find(['player_id='.$playerId.' and buff_temp_id >= 11001 and buff_temp_id <= 13000'])->delete();
				$PlayerBuffTemp->_clearDataCache($playerId);
				
				foreach($item['drop'] as $_drop){
					$ret = $Drop->gain($playerId, $_drop, $num, '', ['second'=>$addTime]);
					if(!$ret)
						return false;
				}
			}
			return $ret;
		}else{
			return false;
		}
		
	}

    /**
     * 增加和氏璧
     * 
     * 
     * @return <type>
     */
	public function updateHsb($playerId, $num=5){
		//检查活动是否开启
		/*$ActivityConfigure = new ActivityConfigure;
		$activity = $ActivityConfigure->getCurrentActivity(2);
		if(!$activity)
			return true;
		$activity = $activity[0];*/
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus(2, $activity) != AllianceMatchList::DOING){
			return 0;
		}
		
		//关闭罩子
		if($num>0)
			$this->offAvoidBattle($playerId);
		
		//增加和氏璧
		//$this->alter($playerId, ['hsb'=>'hsb+('.$num.')']);
		/*$where = ['id'=>$playerId];
		if($num < 0){
			$where['hsb >='] = abs($num);
		}*/
		if($num < 0){
			$this->sqlExec('UPDATE player SET
					`hsb` = `hsb` - @subHsb := IF(`hsb` >= '.abs($num).', '.abs($num).', `hsb`) 
					WHERE `id` = '.$playerId);
			$d = $this->sqlGet('select @subHsb');
			$retNum = $d[0]['@subHsb']*1;
		}else{
			$this->updateAll(['hsb'=>'greatest(0, hsb+('.$num.'))'], ['id'=>$playerId]);
			$retNum = $num;
			
		}
        $this->clearDataCache($playerId);
		$player = $this->getByPlayerId($playerId);
		
		$PlayerBuffTemp = new PlayerBuffTemp;
		//reset buff
		$PlayerBuffTemp->clearHsbBuff($playerId);
		
		//获取buff配置
		$TreasureBuff = new TreasureBuff;
		$tb = $TreasureBuff->findFirst(['count_min <='.$player['hsb'].' and count_max>='.$player['hsb']]);
		if($tb){
			$tb = $TreasureBuff->parseColumn($tb->toArray());
			
			//计算时间
			$second = $activity['end_time'] - time();
			
			//更新buff
			foreach($tb['buff_temp_id'] as $_tmpid){
				$PlayerBuffTemp->up($playerId, $_tmpid, $second);
			}
		}
		return $retNum;
	}

    function createRobot($day, $num){
        $RobotRefresh = new RobotRefresh;
        $Build = new Build;
        $PlayerBuild = new PlayerBuild;
        $PlayerGeneral = new PlayerGeneral;
        $PlayerArmy = new PlayerArmy;
        $rList = $RobotRefresh->getRobotByDay($day);
        if(empty($rList)){
            exit;
        }
        $i = 0;
        while($i<$num){
            $rIndex = array_rand($rList);
            $uuid = "Robot-".$rList[$rIndex]['id']."-".getRandString(9);
            $postData = ['uuid'=>$uuid, 'lang'=>'zhcn', 'login_channel'=>'Robot', 'download_channel'=>'Robot', 'pay_channel'=>'Robot', 'platform'=>'Robot', 'device_mode'=>'Robot', 'system_version'=>'Robot'];
            $re = $this->newPlayer($postData);
            $playerId = $re['id'];
            $level = $rList[$rIndex]['build_level'];
            
            $bArr = [1,2,16,26];
            foreach ($bArr as $orgId) {
                $newBuildInfo = $Build->getOneByOrgIdAndLevel($orgId, $level);
                $pb = $PlayerBuild->getByOrgId($playerId, $orgId);

                $newBuildOutput = 0;
                array_walk($newBuildInfo['output'], function($v, $k) use(&$newBuildOutput){
                    if(in_array($k,[1,2,3,4,5])){
                        $newBuildOutput = $v;
                    }
                });

                if(!empty($pb)){
                    $PlayerBuild->updateAll(['build_id'=>$newBuildInfo['id'], 'build_level'=>$newBuildInfo['build_level'], 'resource_in'=>$newBuildOutput, 'storage_max'=>$newBuildInfo['storage_max']],['id'=>$pb[0]['id']]);
                    $PlayerBuild->dealAfterBuild($playerId, $pb[0]['position']);
                }
                if($orgId==16){
                    for($p=5002;$p<=5005;$p++){
                        $PlayerBuild = new PlayerBuild;
                        $PlayerBuild->player_id = $playerId;
                        $PlayerBuild->build_id = $newBuildInfo['id'];
                        $PlayerBuild->origin_build_id = $newBuildInfo['origin_build_id'];
                        $PlayerBuild->build_level = $newBuildInfo['build_level'];
                        $PlayerBuild->general_id_1 = 0;
                        $PlayerBuild->position = $p;
                        $PlayerBuild->resource_in = $newBuildOutput;
                        $PlayerBuild->storage_max = $newBuildInfo['storage_max'];
                        $PlayerBuild->resource_start_time = date("Y-m-d H:i:s");
                        $PlayerBuild->create_time = date("Y-m-d H:i:s");
                        $PlayerBuild->save();
                        $PlayerBuild->dealAfterBuild($playerId, $p);
                    }
                }elseif($orgId==26){
                    for($p=3002;$p<=3005;$p++){
                        $PlayerBuild = new PlayerBuild;
                        $PlayerBuild->player_id = $playerId;
                        $PlayerBuild->build_id = $newBuildInfo['id'];
                        $PlayerBuild->origin_build_id = $newBuildInfo['origin_build_id'];
                        $PlayerBuild->build_level = $newBuildInfo['build_level'];
                        $PlayerBuild->general_id_1 = 0;
                        $PlayerBuild->position = $p;
                        $PlayerBuild->resource_in = $newBuildOutput;
                        $PlayerBuild->storage_max = $newBuildInfo['storage_max'];
                        $PlayerBuild->resource_start_time = date("Y-m-d H:i:s");
                        $PlayerBuild->create_time = date("Y-m-d H:i:s");
                        $PlayerBuild->save();
                        $PlayerBuild->dealAfterBuild($playerId, $p);
                    }
                }
            }
            $arr = explode(";", $rList[$rIndex]['troop']);
            $army = [];
            foreach ($arr as $key => $value) {
                $tArr = explode(",", $value);
                $army[] = [$tArr[0], $tArr[1], mt_rand(100,500)];
                $PlayerGeneral->add($playerId, $tArr[0]);
            }
            $PlayerArmy->addByData($playerId, $army);
            $this->offFreshAvoidBattle($playerId);
            $this->clearDataCache($playerId);
            $i++;
        }
    }

    /**
     * 获取其他服务器玩家信息
     * ```php
     * $re = (new Player)->getPlayerBasicInfoByServer(1, 500061);
     * dump($re);
     * ```
     * @param $targetServerId
     * @param $targetPlayerId
     *
     * @return array|string
     */
    public function getPlayerBasicInfoByServer($targetServerId, $targetPlayerId){
        global $config;
        $targetPlayer = [];
        if($config->server_id==$targetServerId) {//本服
            $targetPlayer = keepFields($this->getByPlayerId($targetPlayerId), self::$basicInfo, true);
            if($targetPlayer['guild_id']>0) {
                $guild                            = (new Guild)->getGuildInfo($targetPlayer['guild_id']);
                $targetPlayer['guild_name']       = $guild['name'];
                $targetPlayer['guild_short_name'] = $guild['short_name'];
            } else {
                $targetPlayer['guild_name'] = $targetPlayer['guild_short_name'] = '';
            }
        } else {//他服
            $targetGameServerHost = (new ServerList)->getGameServerHostByServerId($targetServerId);
            if ($targetGameServerHost) {
                $url          = $targetGameServerHost . '/api/getPlayerBasicInfo';
                $field        = ['player_id' => iEncrypt($targetPlayerId, 'Player')];
                $targetPlayer = curlPost($url, $field);
                $targetPlayer = iDecrypt($targetPlayer);
            }
        }
        return $targetPlayer;
    }

    public function correctPlayerBuildAndSoldier($playerId){
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $PlayerSoldier = new PlayerSoldier;
        $PlayerSoldier->getByPlayerId($playerId);
        $PlayerBuild = new PlayerBuild;
        $playerBuildList = $PlayerBuild->getByPlayerId($playerId);
        $Build = new Build;
        $playerCastleLevel = $PlayerBuild->getPlayerCastleLevel($playerId);
        $resourceBuildOrgIdList = ['16','21','26','31','36'];
        $soldierBuildOrgIdList = ['4','5','6','7'];
        $PlayerArmyUnit = new PlayerArmyUnit;
        $PlayerSoldier = new PlayerSoldier;
        $PlayerSoldierInjured = new PlayerSoldierInjured;
        foreach($playerBuildList as $value){
            if( in_array($value['origin_build_id'],$resourceBuildOrgIdList) && $value['build_level']<$playerCastleLevel ){
                $newBuildInfo = $Build->getOneByOrgIdAndLevel($value['origin_build_id'], $playerCastleLevel);
                $newBuildOutput = 0;
                array_walk($newBuildInfo['output'], function($v, $k) use(&$newBuildOutput){
                    if(in_array($k,[1,2,3,4,5])){
                        $newBuildOutput = $v;
                    }
                });
                $PlayerBuild->updateAll(['build_id'=>$newBuildInfo['id'], 'build_level'=>$newBuildInfo['build_level'], 'resource_in'=>$newBuildOutput, 'storage_max'=>$newBuildInfo['storage_max'], 'status'=>1, 'queue_index'=>0],['id'=>$value['id']]);
                $PlayerBuild->dealAfterBuild($playerId, $value['position']);
            }
            if( in_array($value['origin_build_id'],$soldierBuildOrgIdList)){
                for($level=1; $level<=$value['build_level']; $level++){
                    $newBuildInfo = $Build->getOneByOrgIdAndLevel($value['origin_build_id'], $level);
                    if(!empty($newBuildInfo['upgrade_soldier_id']) && !empty($newBuildInfo['original_soldier_id'])){
                        $PlayerArmyUnit->replaceSoldier($playerId, $newBuildInfo['original_soldier_id'], $newBuildInfo['upgrade_soldier_id'], true);
                        $PlayerSoldier->replaceSoldier($playerId, $newBuildInfo['original_soldier_id'], $newBuildInfo['upgrade_soldier_id'], true);
                        $PlayerBuild->levelUpCuringSoldier($playerId, $newBuildInfo['original_soldier_id'], $newBuildInfo['upgrade_soldier_id']);
                        $PlayerSoldierInjured->lvUpInjuredSoldier($playerId, $newBuildInfo['original_soldier_id'], $newBuildInfo['upgrade_soldier_id']);
                    }
                }
            }
        }
        $this->refreshPower($playerId, 'army_power');
        $this->updateAll(['has_corrected'=>'1'], ['id'=>$playerId]);
        $this->clearDataCache($playerId);
        Cache::unlock($lockKey);
    }

    /**
     * 更改联盟内玩家阵营
     * @param $guildId
     * @param $campId
     * @param $fromCampId
     *
     */
    public function updateAllCampId($guildId, $campId, $fromCampId){
        $PlayerGuild          = new PlayerGuild;
        $CityBattleRank       = new CityBattleRank;
        $PlayerBuffTemp       = new PlayerBuffTemp;
        $CountryBattleTitle   = new CountryBattleTitle;
        $Drop                 = new Drop;
        $CityBattleCampNumber = new CityBattleCampNumber;

        //清除羽林军称号
        $allTitle    = $CountryBattleTitle->dicGetAll();
        $allBuffId   = Set::extract('/buff_id', $allTitle);
        $buffTempIds = [];
        foreach($allBuffId as $v) {
            if(!empty($v)) {
                $drop        = $Drop->dicGetOne($v);
                $dropData    = $drop['drop_data'];
                $cuBuffTempIds = Set::extract('/1', $dropData);
                $buffTempIds = array_merge($buffTempIds, $cuBuffTempIds);
            }
        }
        $buffTempIds = array_unique($buffTempIds);

        $allMember = $PlayerGuild->getAllGuildMember($guildId, false);
        $CityBattleCampNumber->inc($campId, count($allMember));
        $CityBattleCampNumber->dec($fromCampId, count($allMember));
        foreach($allMember as $k=>$v) {
            $_playerId = $v['player_id'];
            $titleRank = $CityBattleRank->getRankPlayerId($_playerId);
            if($titleRank>0) {//清除
                $PlayerBuffTemp->clearTitleBuff($_playerId, $buffTempIds);
                $CityBattleRank->delPlayerTitle($_playerId);
            }
            $this->alter($_playerId, ['camp_id'=>$campId]);
            socketSend(['Type'=>'change_player_camp', 'Data'=>['player_id'=>$_playerId, 'camp_id'=>$campId]]);
        }
    }
}
