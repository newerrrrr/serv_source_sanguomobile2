<?php
/**
 * 玩家控制器
 */
class PlayerController extends ControllerBase{
    /**
     * 聚宝盆
     *
     *  type:1 占星 2 天陨
     *  multi_flag: 0 单抽 1 十连
     *  free_flag: 0 收费 1 免费
     *  use_item_flag: 0 不用道具 1 使用道具
     * ```
     * player/treasureBowl
     * postData: {"type":1,"multi_flag":0, "free_flag":0, "use_item_flag":0}
     * return: {Drops}
     * ```
     */
    public function treasureBowlAction(){
        $playerId = $this->getCurrentPlayerId();
        $player   = $this->getCurrentPlayer();
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);//锁定
        //获取post数据
        $postData  = getPost();
        $type      = $postData['type'];
        $multiFlag = $postData['multi_flag'];
        $freeFlag  = $postData['free_flag'];
        $useItemFlag = $postData['use_item_flag'];
        //异常输入判断
        if(!in_array($type, [1,2]) || !in_array($multiFlag, [0,1]) || !in_array($freeFlag, [0,1]) || !in_array($useItemFlag, [0,1])) {
            $errCode = 10061;//程序异常
            goto sendErr;
        }
        //case 初始化 Class
        $PlayerInfo      = new PlayerInfo;
        $Cost            = new Cost;
        $General         = new General;
        $PlayerGeneral   = new PlayerGeneral;
        $PlayerItem      = new PlayerItem;
        $PlayerCommonLog = new PlayerCommonLog;
        $RoundMessage    = new RoundMessage;
        //case 初始化数据
        $isMulti           = $multiFlag == 1;//false:单抽 , true:连抽
        $times             = $isMulti ? 10 : 1;//抽奖次数
        $isLowFlag         = ($type == 1);//true:占星 , false:天陨
        $firstFlag         = false;//首次天陨判断
        $dropGroupMap      = [1 => 1, 2 => 10];//astrology表type对应的drop_group值，这里存放的是开始寻找的位置,每次随不到，改变该值为next_drop_group
        $awardDrop         = [];//抽到的内容
        $gainExtraDropFlag = false;//是否获得额外的奖励：武将信物
        $logTxt            = $isLowFlag ? ($isMulti ? '[聚宝盆]占星-连抽' : '[聚宝盆]占星-单抽') : ($isMulti ? '[聚宝盆]天陨-连抽' : '[聚宝盆]天陨-单抽');//log文字
        $logMemo           = [];//log记录
        $extraDropData     = [];//返回给前端的额外掉落记录
        $xdropLog          = [];//额外掉落log记录
        $data              = [];//返回给前端的内容
        //step 扣费逻辑
        $costId = 0;
        if(!$isMulti) {//单抽
            if ($freeFlag == 1) {//免费
                if (!$PlayerInfo->updateBowlFreeLastTime($playerId, $type)) {//免费使用失败
                    if($isLowFlag)
                        $errCode = 10523;//[占星单抽]免费使用时间未到
                    else
                        $errCode = 10524;//[天陨单抽]免费使用时间未到
                    goto sendErr;
                }
            }
            elseif($useItemFlag==1) {//特殊道具
                $itemId = $isLowFlag ? 52001 : 52002;
                if(!(new PlayerItem)->drop($playerId, $itemId, 1)) {
                    $errCode = 10527;//道具不足
                    goto sendErr;
                }
            }
            else {
                $costId = $isLowFlag ? 2 : 4;//2 占星, 4 天陨
            }
        } else {//连抽
            $costId = $isLowFlag ? 3 : 5;//3 占星, 5 天陨
        }
        if($costId && !$Cost->updatePlayer($playerId, $costId)) {
            $errCode = 10101;//元宝不足
            goto sendErr;
        }
        $playerInfo = $PlayerInfo->getByPlayerId($playerId);
        $bowlCounterDropGroup14Status = $playerInfo['bowl_counter_drop_group_14_status'];
        //step 抽盆逻辑---Begin
        do {
            $logTmp = [];//log记录初始化临时数据
            $times--;
            //首次天陨掉落判断-用于新手Begin
            if(!$isLowFlag && !$isMulti) {//天陨 单抽
                if($playerInfo['first_high_astrology_drop']==0) {
                    $firstDropId    = (new Starting)->getValueByKey('first_high_astrology_drop');
                    $firstDropIds   = parseArray($firstDropId);
                    $firstDropItems = (new Drop)->gain($playerId, $firstDropIds, 1, '[聚宝盆]天陨-首次抽');//获得
                    if(!$firstDropItems) {
                        $errCode = 10061;//程序异常
                        goto sendErr;
                    }
                    $firstDropItem  = $firstDropItems[0];
                    $awardDrop[]    = $firstDropItem;//发给前端数据
                    $logMemo[]           = [
                        'Data'    => $firstDropItem,
                        'Counter' => '新手首次天陨获得',
                        'Times'   => $times,
                    ];
                    $logMemo[]      = $logTmp;
                    $PlayerInfo->alter($playerId, ['first_high_astrology_drop'=>1]);
                    $firstFlag = true;
                }
            }
            if($firstFlag) {
                goto LastTime;
            }
            //首次天陨掉落判断-用于新手End
            /**
             * 0. Start
             * 1. 获取当前type（占星或天陨）计数器T，根据概率计算掉落，（概率100%为必掉），掉落Y 【跳2】，没掉落N 【跳3】
             * 2. 处理掉落相关逻辑（给奖励，特殊逻辑处理，记log，传值回前端显示等）， 掉落为已有，跳3， 当前计数器T=1，其他计数器分别+1(Tnext1+=1,Tnext2+=1,...Tnextn+=1)【跳4】
             * 3. 获取下一个计数器Tnext ，执行T=Tnext，【跳1】
             * 4. End
             */
            $dropGroup = $dropGroupMap[$type];//0.
            AstologyHere: {
                if($dropGroup==0) {
                    $dropGroup = $dropGroupMap[$type];
                }
                $currentAstology = $PlayerInfo->getbowlByCounter($playerId, $type, $dropGroup);//1.
                if($currentAstology['is_gain_flag']) {//2.
                    $preDropGroup = $dropGroup;//存起前一个drop_group
                    //特殊跳转逻辑
                    if($bowlCounterDropGroup14Status==0 && $currentAstology['Special_next_drop_group']>0) {
                        $dropGroup = $currentAstology['Special_next_drop_group'];
                    } else {
                        $dropGroup = $currentAstology['next_drop_group'];//下一个drop_group
                    }
                    $dropId    = $currentAstology['drop_id'];
                    $dropIds   = parseArray($dropId);
                    //S. drop掉落判断
                    $dropItems   = (new Drop)->gain($playerId, $dropIds, 1, $logTxt);//获得
                    if(!$dropItems) {
                        goto AstologyHere;
                    }
                    $dropItem   = $dropItems[0];
                    $dropItemId = $dropItem['id'];
                    //S. 特殊逻辑---Begin    //判断武将信物为神武将且武将信物碎片不满的情况下，就多给一个
                    if($dropItem['type']==2 && Item::isGodFragment($dropItemId) && !$gainExtraDropFlag) {//神武将碎片
                        if($preDropGroup==Astrology::$onceDropType) {//特殊
                            $PlayerInfo->alter($playerId, ['bowl_counter_drop_group_14_status'=>1, 'bowl_counter_drop_group_12'=>1]);
                        }
                        $newGodGeneralId = $General->findFirst(['piece_item_id='.$dropItemId])->general_original_id;
                        if(!in_array($newGodGeneralId, [10105, 10106])) {
                            $orangeGeneralId = 0;
                            $ids             = $General->getBySameRoot($newGodGeneralId);
                            foreach($ids as $_id){
                                if(!$General->isGod($_id)){
                                    $orangeGeneralId = $_id;
                                    break;
                                }
                            }
                            if($orangeGeneralId>0) {
                                $orangeGeneral = $General->getByGeneralId($orangeGeneralId);
                                $pg            = $PlayerGeneral->getGeneralIds($playerId);
                                $hasItemNum    = $PlayerItem->hasItemCount($playerId, $orangeGeneral['piece_item_id']);
                                if (!in_array($orangeGeneralId, $pg) && $hasItemNum < $orangeGeneral['piece_required']) {
                                    $pieceNum = $orangeGeneral['piece_required'] - $hasItemNum;
                                    if ($PlayerItem->add($playerId, $orangeGeneral['piece_item_id'], $pieceNum)) {
                                        $extraDropData     = ['type' => 2, 'id' => intval($orangeGeneral['piece_item_id']), 'num' => $pieceNum];
                                        $gainExtraDropFlag = true;
                                        $logMemo[]         = [
                                            'Data'    => $extraDropData,
                                            'Counter' => '获得神武将信物后附赠',
                                            'Times'   => $times,
                                        ];
                                    }
                                }
                            }
                        }
                    }//特殊逻辑---End
                    //S. 计数器
                    if($dropGroup>0) {
                        $field = $currentAstology['current_field']['name'];
                        $PlayerInfo->alter($playerId, [$field => 1]);//当前计数器+1
                        //记log
                        $logTmp['Counter'] = $currentAstology['current_field'];
                        $logTmp['Timer']   = $times;
                    } else {//记log
                        $logTmp['Counter'] = '必中类型-' . $currentAstology['drop_group'];
                        $logTmp['Timer']   = $times;
                    }
                    foreach($currentAstology['other_field'] as $k=>$v) {
                        $PlayerInfo->alter($playerId, [$k => $v + 1]);//其他计数器+1
                    }
                    //S. 特定drop发到走马灯里
                    if(Item::isGodFragment($dropItemId)) {
                        $rmdata['item_id']     = $dropItemId;
                        $rmdata['player_nick'] = $player['nick'];
                        $RoundMessage->addNew($playerId, ['type'=>8, 'data'=>$rmdata]);//走马灯公告
                    }
                    $awardDrop[]    = $dropItem;//发给前端数据
                    $logTmp['Data'] = $dropItem;//记log
                    $logMemo[]      = $logTmp;
                } else {//3.
                    //特殊跳转逻辑
                    if($bowlCounterDropGroup14Status==0 && $currentAstology['Special_next_drop_group']>0) {
                        $dropGroup = $currentAstology['Special_next_drop_group'];
                    } else {
                        $dropGroup = $currentAstology['next_drop_group'];//下一个drop_group
                    }
                    goto AstologyHere;
                }
            }//case 核心逻辑 ----End //4
            //给额外drop
            if(!$isLowFlag) {
                $xdropId    = 230009;
                $xdrop      = (new Drop)->gain($playerId, [$xdropId]);
                $xdropLog[] = $xdrop;
            }
            LastTime:
                //case 记录log相关
                if($times==0) {
                    $data = $awardDrop;
                    if($extraDropData) {
                        array_push($data, $extraDropData);
                    }
                    $PlayerCommonLog->add($playerId, ['type'=>$logTxt, 'memo'=>['awardDrop'=>$logMemo,'x_drop'=>$xdropLog]]);//日志
                }
        } while($times>0);
        //抽盆逻辑---End
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     * player/getSacrificeGM
     */
    public function getSacrificeGMAction(){
        $playerId = $this->getCurrentPlayerId();
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);//锁定
        //获取活动
        $activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivitySacrifice::ACTID);
        if(!$activityConfigure){
            $errCode = 10440;//活动尚未开始
            goto sendErr;
        }
        $activityConfigure   = $activityConfigure[0];
        $config              = json_decode($activityConfigure['activity_para'], true);
        foreach($config as $k=>&$v) {
            if($k=='wheel') {
                foreach($v as &$vw) {
                    $vw['rate'] = (int)$vw['rate'];
                    $vw['rate2'] = (int)$vw['rate2'];
                    $vw['drop'] = parseGroup($vw['drop'], false);
                }
                unset($vw);
            }
        }
        unset($v);
        $config['end_time'] = $activityConfigure['end_time'];
        Cache::unlock($lockKey);
        echo $this->data->send($config);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 祭天
     *
     *  multi_flag: 0 单抽 1 十连
     *  free_flag: 0 收费 1 免费
     *  camp_id: 1 魏 2蜀 3吴 4 群 0 活动
     *  use_item_flag: 0 无 1 使用道具
     * ```
     * player/sacrificeToHeaven
     * postData: {"multi_flag":0, "free_flag":0, "camp_id":1, "use_item_flag":0}
     * return: {Drops}
     * ```
     */
    public function sacrificeToHeavenAction(){
        $playerId = $this->getCurrentPlayerId();
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);//锁定
        //获取post数据
        $postData    = getPost();
        $campId      = intval($postData['camp_id']);
        $multiFlag   = $postData['multi_flag'];
        $freeFlag    = $postData['free_flag'];
        $useItemFlag = $postData['use_item_flag'];
        //case: judge exceptions
        //异常输入判断
        if(!in_array($multiFlag, [0,1]) || !in_array($freeFlag, [0,1]) || !in_array($campId, [0,1,2,3,4])) {
            $errCode = 10061;//程序异常
            goto sendErr;
        }
        //府衙等级判断
        $PlayerBuild  = new PlayerBuild;
        $playerBuild  = $PlayerBuild->getByOrgId($playerId, 1);
        $cityLv       = $playerBuild[0]['build_level'];
        $starupOpenLv = (new Starting)->getValueByKey('starup_open_lv');
        if($cityLv<$starupOpenLv) {
            $errCode = 10689;//[祭天抽奖]府衙等级未达到要求
            goto sendErr;
        }
        //case: init classes
        $PlayerInfo      = new PlayerInfo;
        $Cost            = new Cost;
        $PlayerCommonLog = new PlayerCommonLog;

        //case: prev init data
        $isMulti = $multiFlag == 1;//false:单抽 , true:连抽
        $times   = $isMulti ? 10 : 1;//抽奖次数
        $costId  = 0;//默认不使用元宝
        $data    = [];//返回给前端的内容
        $logTxt  = !$isMulti ? '[祭天抽奖]单抽' : '[祭天抽奖]连抽';//log文字
        $logMemo = [];//log记录

        if($campId===0) {//活动
            $PlayerActivitySacrifice = new PlayerActivitySacrifice;
            //获取活动
            $activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivitySacrifice::ACTID);
            if(!$activityConfigure){
                $errCode = 10440;//活动尚未开始
                goto sendErr;
            }
            $activityConfigure   = $activityConfigure[0];
            $activityConfigureId = $activityConfigure['id'];
            $config              = json_decode($activityConfigure['activity_para'], true);
            $wheel               = $config['wheel'];
            $xcounter            = $config['xcounter'];
            //case# 扣费
            if(!$isMulti) {//单抽
                if($useItemFlag==1) {//活动道具
                    $itemId = $config['itemId'];
                    if(!(new PlayerItem)->drop($playerId, $itemId, 1)) {
                        $errCode = 10527;//道具不足
                        goto sendErr;
                    }
                } else {//扣元宝
                    if(!(new Player)->updateGem($playerId, -$config['gem'], true, ['cost'=>10027])) {
                        $errCode = 10101;//元宝不足
                        goto sendErr;
                    }
                }
            } else {
                if(!(new Player)->updateGem($playerId, -$config['gemMulti'], true, ['cost'=>10028])) {
                    $errCode = 10101;//元宝不足
                    goto sendErr;
                }
            }
            //case# 抽奖
            do {
                $times--;
                $playerActivitySacrifice = $PlayerActivitySacrifice->getByActId($playerId, $activityConfigureId);
                $drawTimes               = $playerActivitySacrifice['times'];
                if($drawTimes<$xcounter) {
                    $drop = randomByField($wheel, 'rate');
                } else {
                    $drop = randomByField($wheel, 'rate2');
                }
                $drop      = array_pop($drop);
                $drop      = $drop['drop'];
                $dropItems = (new Drop)->gainFromDropStr($playerId, $drop, '[活动][祭天][activity_configure_id='.$activityConfigureId.']');
                $PlayerActivitySacrifice->incTimes($playerId, $activityConfigureId);

                $data[]    = $dropItems[0];
                $logMemo[] = $dropItems[0];

                if($times==0) {
                    $PlayerCommonLog->add($playerId, ['type'=>'[祭天活动]'.$logTxt, 'memo'=>['awardDrop'=>$logMemo]]);//日志
                }
            } while($times>0);
        } else {
            //case: init datas
            $dropIds       = (new GambleGeneralSoul)->getDropIds();
            $playerInfo    = $this->currentPlayerInfo;
            $sacrificeFlag = $playerInfo['sacrifice_flag'];//半价标识
            //case# 扣费
            if(!$isMulti) {//单抽
                if($freeFlag==1) {//免费
                    if(!$PlayerInfo->updateSacrificeTime($playerId)) {//免费使用失败
                        $errCode = 10690;//[祭天单抽]当天免费一次已使用
                        goto sendErr;
                    }
                }
                elseif($useItemFlag==1) {
                    $itemId = 52005;
                    if(!(new PlayerItem)->drop($playerId, $itemId, 1)) {
                        $errCode = 10527;//道具不足
                        goto sendErr;
                    }
                }
                elseif($playerInfo['sacrifice_free_flag']==0) {
                    $costId = ($sacrificeFlag==1) ? 22 : 23;
                } elseif($playerInfo['sacrifice_free_flag']==1) {
                    $errCode = 10691;//[祭天单抽]当天免费尚未使用
                    goto sendErr;
                }
            } else {
                $costId = 24;//祭天连抽
            }
            if($costId && !$Cost->updatePlayer($playerId, $costId)) {
                $errCode = 10101;//元宝不足
                goto sendErr;
            }
            if($costId==22 && $sacrificeFlag==1) {//半价标识
                $PlayerInfo->alter($playerId, ['sacrifice_flag'=>0]);//改半价标识
                $PlayerCommonLog->add($playerId, ['type'=>$logTxt, 'memo'=>['半价单抽标记']]);//日志
            }
            //case# 抽奖
            do {
                $times--;
                $dropId = $dropIds[$campId];
                if(!$isMulti && $campId==3 && $playerInfo['sacrifice_newbie_flag']==0) {//单抽 首次 吴国
                    $dropId = (new Starting)->getValueByKey('jitian_wu_first_drop');
                    $PlayerInfo->alter($playerId, ['sacrifice_newbie_flag'=>1]);
                    $PlayerCommonLog->add($playerId, ['type'=>$logTxt, 'memo'=>['祭天新手引导']]);//日志
                }

                $dropItems = (new Drop)->gain($playerId, [$dropId], 1);//获得
                $data[]    = $dropItems[0];
                $logMemo[] = $dropItems[0];

                if($times==0) {
                    $PlayerCommonLog->add($playerId, ['type'=>$logTxt, 'memo'=>['awardDrop'=>$logMemo]]);//日志
                }
            } while($times>0);
        }
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 查看玩家详情页
     *
     * 使用方法如下
     * ```php
     * player/playerInfoDetail
     * postData:{"player_id":100221}
     * return:{}
     * ```
     */
    public function playerInfoDetailAction(){
        $postData = getPost();
        if(empty($postData['player_id'])) {
            $playerId = $this->getCurrentPlayerId();
        } else {
            $playerId = $postData['player_id'];
        }
        
        $Player = new Player;
        $PlayerBuff = new PlayerBuff;

        $player = $Player->getByPlayerId($playerId);

        $data[1] = $player['power'];//总战斗力
        $data[2] = $player['master_power'];//主公战斗力
        $data[3] = $player['general_power'];//武将战斗力
        $data[4] = $player['army_power'];//部队战斗力
        $data[5] = $player['build_power'];//建筑战斗力
        $data[6] = $player['science_power'];//科技战斗力
        $data[7] = $player['trap_power'];//陷阱战斗力

        $data[8] = $Player->getQueueNum($playerId);//可出征军团数
        $data[9] = $Player->getArmyGeneralNum($playerId);//军团可配置武将数

        $buffArr = [10=>'train_troops_speed',
// 11=>'march_speed',
12=>'infantry_atk_plus',
13=>'infantry_def_plus',
14=>'infantry_life_plus',
15=>'cavalry_atk_plus',
16=>'cavalry_def_plus',
17=>'cavalry_life_plus',
18=>'archer_atk_plus',
19=>'archer_def_plus',
20=>'archer_life_plus',
21=>'siege_atk_plus',
22=>'siege_def_plus',
23=>'siege_life_plus',
24=>'fieldbattle_infantry_atk_plus',
25=>'fieldbattle_infantry_def_plus',
26=>'fieldbattle_infantry_life_plus',
27=>'fieldbattle_cavalry_atk_plus',
28=>'fieldbattle_cavalry_def_plus',
29=>'fieldbattle_cavalry_life_plus',
30=>'fieldbattle_archer_atk_plus',
31=>'fieldbattle_archer_def_plus',
32=>'fieldbattle_archer_life_plus',
33=>'fieldbattle_siege_atk_plus',
34=>'fieldbattle_siege_def_plus',
35=>'fieldbattle_siege_life_plus',
36=>'gold_income',
37=>'gold_gathering_speed',
38=>'wood_income',
39=>'wood_gathering_speed',
40=>'food_income',
41=>'food_gathering_speed',
42=>'stone_income',
43=>'stone_gathering_speed',
44=>'iron_income',
45=>'iron_gathering_speed',
46=>'protect_gold_plus',
47=>'build_speed',
48=>'science_research_speed',
49=>'food_out_debuff',
50=>'infantry_carry_plus',
51=>'hospital_amount_plus',
52=>'cure_speed',
53=>'cure_cost_minus',
54=>'march_speed',
55=>'move_to_npc_speed',
56=>'move_restore_speed',
57=>'wall_defense_limit_plus',
58=>'pitfall_atk_plus',
59=>'pitfall_train_speed',
60=>'pitfall_amount_plus',
61=>'pitfall_atk_plus',
62=>'pitfall_atk_plus',
63=>'pitfall_atk_plus',
64=>'citybattle_infantry_atk_plus',
65=>'citybattle_infantry_def_plus',
66=>'citybattle_infantry_life_plus',
67=>'citybattle_cavalry_atk_plus',
68=>'citybattle_cavalry_def_plus',
69=>'citybattle_cavalry_life_plus',
70=>'citybattle_archer_atk_plus',
71=>'citybattle_archer_def_plus',
72=>'citybattle_archer_life_plus',
73=>'citybattle_siege_atk_plus',
74=>'citybattle_siege_def_plus',
75=>'citybattle_siege_life_plus',];
            /*foreach($buffArr as $k=>$v) {

                $buffVal = $PlayerBuff->getPlayerBuff($playerId, $v);
                if($PlayerBuff->buffTypeAfterCallGetPlayerBuff==1) {
                    $data[$k] = 100*$buffVal.'%';
                } else {
                    $data[$k] = $buffVal;
                }
            }*/
		$ret = (new PlayerBuff)->getPlayerBuffs($playerId, $buffArr, 0, true);
		foreach($buffArr as $_k => $v){
			$data[$_k] = $ret[$v];
		}
        $this->data->noExecTimeFlag = true;//关闭php时间超时预警
        echo $this->data->send($data);
        exit;
    }
    /**
     * 查看其他玩家信息
     *
     * 使用方法如下
     * ```php
     * player/viewTargetPlayerInfo
     * postData: {"target_player_id":100029}
     * return: {Player}
     * ```
     */
    public function viewTargetPlayerInfoAction(){
        $postData       = getPost();
        $targetPlayerId = $postData['target_player_id'];
        if($targetPlayerId) {
            $Player            = new Player;
            $PlayerEquipMaster = new PlayerEquipMaster;
            $targetPlayer      = $Player->getByPlayerId($targetPlayerId);
			$targetPlayer = filterFields([$targetPlayer], true, ['uuid','levelup_time','talent_num_total','talent_num_remain','general_num_total','general_num_remain','army_num','army_general_num','queue_num','move','move_max','gold','food','wood','stone','iron','silver','point','rmb_gem','gift_gem','valid_code'])[0];
			
            $targetGuildId     = $targetPlayer['guild_id'];
            if($targetGuildId>0) {
                $guild     = (new Guild)->getGuildInfo($targetGuildId);
                $guildData = keepFields($guild, ['id', 'name', 'short_name', 'icon_id'], true);
            }
            $targetPlayerEquipMaster = $PlayerEquipMaster->getByPlayerId($targetPlayerId);
            $data = [];
            if($targetPlayer) {
                $data['Player'] = $targetPlayer;
                if($targetPlayerEquipMaster) {
                    $data['PlayerEquipMaster'] = $targetPlayerEquipMaster;
                }
            }
            if(isset($guildData)) {
                $data['Guild'] = $guildData;
            } else {
                $data['Guild'] = [];
            }
            echo $this->data->send($data);
            exit;
        } else {
             echo $this->data->sendErr('illegal');
             exit;
        }
    }
    /**
     * 查看攻击我的所有部队
     *
     * 使用方法如下：
     * ```php
     * player/viewAttackArmy
     * postData: {}
     * return: {PlayerProjectQueue}
     * ```
     */
    public function viewAttackArmyAction(){
        $playerId = $this->getCurrentPlayerId();
        $data     = (new PlayerProjectQueue)->getAttackArmy($playerId);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 查看侦查我的所有部队
     *
     * 使用方法如下：
     * ```php
     * player/viewSpyInfo
     * postData: {}
     * return: {}
     * ```
     */
    public function viewSpyInfoAction(){
        $playerId = $this->getCurrentPlayerId();
        $data = (new PlayerProjectQueue)->getSpyArmy($playerId);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 生成新玩家
     *
     * 使用方法如下
     *```php
     * player/newPlayer
     * postData: json={"nick":"xxx", "avatar_id":1}
     * return: {"code":10001,"data":[],"basic":[]}
     * ```
     */
    public function newPlayerAction(){
        echo $this->data->sendRaw([]);
        exit;
    }

    /**
     * 更新客户端推送标识
     * 
     * 
     * @return <type>
     */
	public function updateClientIdAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$deviceType = @$post['deviceType'];//1.ios,2.android
		$clientId = @$post['clientId'];
		$deviceToken = @$post['deviceToken'];
		if(!in_array($deviceType, [1, 2])/* || ($deviceType==1 && !$deviceToken)*/)
			exit;
		if(!$deviceToken){
			$deviceToken = '';
		}
		if(strstr($clientId, "'"))
			exit;
				
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		//dbBegin($db);

		try {
			$Player = new Player;
			if($deviceType == 2)
				$deviceToken = '';
			$data = [
				'device_type'=>$deviceType,
				'client_id'=>"'".$clientId."'",
				'badge'=>0,
			];
			if($deviceToken){
				$data['device_token'] = "'".$deviceToken."'";
			}
			$Player->alter($playerId, $data);
			
			$Player->sqlExec('update '.$Player->getSource().' set device_type=0,device_token="",client_id="" where device_type>0 and ('.($deviceToken ? 'device_token="'.$deviceToken.'" or ' : '').'client_id="'.$clientId.'") and id <> '.$playerId);
			/*$other = $Player->find(['device_token="'.$deviceToken.'" or client_id="'.$clientId.'"'])->toArray();
			foreach($other as $_o){
				if($_o['id'] == $playerId) continue;
				$Player->alter($_o['id'], [
					'device_type'=>0,
					'device_token'=>"''",
					'client_id'=>"''",
				]);
			}*/
			
			//dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

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
     * 更新推送开关
     * 
     * 
     * @return <type>
     */
	public function updatePushTagAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$pushTag = @$post['pushTag'];//[1,2,3,4]
		if(!is_array($pushTag) || array_diff($pushTag, [1, 2, 3, 4, 5]))
			exit;
				
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			global $config;
			(new Player)->alter($playerId, [
				'push_tag'=>"'".join(',', $pushTag)."'",
			]);
			
			/*require_once(APP_PATH . '/app/lib/igt/IGt.Push.php');
			
			$igt = new IGeTui($config['push']['host'],$config['push']['appkey'],$config['push']['mastersecret']);
			$rep = $igt->setClientTag($config['push']['appid'], $player['client_id'], $pushTag);
			if($rep['result'] != 'ok'){
				var_dump($rep);
				throw new Exception(10411);//设置推送失败
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
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
    /**
     * 发送侦查 主城，侦查联盟堡垒和侦查矿
     *
     * 使用方法如下
     * ```php
     * player/spy
     * postData: {"to_x":100,"to_y":200}
     * return: {PlayerProjectQueue}
     * ```
     */
    public function spyAction(){
        $playerId           = $this->getCurrentPlayerId();
        
        $lockKey            = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);//锁定
        $player             = $this->getCurrentPlayer();
        $postData           = getPost();

        $db                 = $this->di['db'];
        dbBegin($db);
        
        $PlayerProjectQueue = new PlayerProjectQueue;
        $Player             = new Player;
        $Map                = new Map;
        
        $toX                = $postData['to_x'];
        $toY                = $postData['to_y'];
        $toMap              = $Map->getByXy($toX, $toY);
        
        $data               = [];
        if($toMap['map_element_origin_id']==15) {//主堡
            $targetPlayerId = $toMap['player_id'];
            $targetPlayer   = $Player->getByPlayerId($targetPlayerId);
            if($player['guild_id']!=0 && $player['guild_id']==$targetPlayer['guild_id']) {
                $errCode = 10335;//侦查主堡-不能侦查本联盟的主堡
                goto sendErr;
            }
            //判断对方是否带套子
            if($Player->isAvoidBattle($targetPlayer)){
                $errCode = 10326;//对方正处于免战状态
                goto sendErr;
            }
            $targetPlayerBuff = (new PlayerBuff)->getPlayerBuff($targetPlayerId, 'anti_spy');//反侦查
            if($targetPlayerBuff==1) {
                $errCode = 10336;//侦查主堡-对方主堡开启反侦察
                goto sendErr;
            }
            $data['type'] = 1;
        } elseif($toMap['map_element_origin_id']==1){//堡垒
            if($player['guild_id']!=0 && $player['guild_id']==$toMap['guild_id']) {
                $errCode = 10337;//侦查联盟堡垒-不能侦查本联盟的主堡
                goto sendErr;
            }
            $targetPlayerId = 0;
            $data['type'] = 2;
        } elseif(in_array($toMap['map_element_origin_id'], [9,10,11,12,13])){//资源田
            $targetPlayerId = $toMap['player_id'];
            $targetPlayer = $Player->getByPlayerId($targetPlayerId);
            if($player['guild_id']!=0 && $player['guild_id']==$targetPlayer['guild_id']) {
                $errCode = 10338;//侦查资源田-不能侦查本联盟的主堡
                goto sendErr;
            }
            $data['type'] = 3;
        } elseif(in_array($toMap['map_element_origin_id'], [18,19])){//国王战-城寨
            if(!$player['guild_id']) {
                $errCode = 10339;//侦查国王战-城寨-玩家未加任何联盟
                goto sendErr;
            }
            if(!$toMap['guild_id']) {
                $errCode = 10340;//侦查国王战-城寨-城寨中无其他联盟玩家
                goto sendErr;
            }
            if($player['guild_id']==$toMap['guild_id']) {
                $errCode = 10341;//侦查国王战-城寨-不能侦查自己联盟玩家
                goto sendErr;
            }
            $targetPlayerId = 0;
            $data['type'] = 4;
        } elseif($toMap['map_element_origin_id']==22){//据点战
            $targetPlayerId = $toMap['player_id'];
            $targetPlayer = $Player->getByPlayerId($targetPlayerId);
            if($player['guild_id']!=0 && $player['guild_id']==$targetPlayer['guild_id']) {
                $errCode = 10451;//侦查据点-不能侦查本联盟的据点
                goto sendErr;
            }
            $data['type'] = 5;
        } 

        $currentPPQ = $PlayerProjectQueue->getByPlayerId($playerId);
        try{
            $Map->doBeforeGoOut($playerId, 0, (new Starting)->dicGetOne('energy_cost_spy'), ['ppq'=>$currentPPQ]);
        } catch (Exception $e) {
            list($errCode, $msg) = parseException($e);
            goto sendErr;
        }
        
        $type                     = PlayerProjectQueue::TYPE_DETECT_GOTO;
        $extraData                = [];
        $extraData['from_map_id'] = $player['map_id'];
        $extraData['from_x']      = $player['x'];
        $extraData['from_y']      = $player['y'];
        $extraData['to_map_id']   = $toMap['id'];
        $extraData['to_x']        = $toX;
        $extraData['to_y']        = $toY;
        $needTime                 = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $toX, $toY, 4, 0);
        $PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $targetPlayerId, $type, $needTime, 0, $data, $extraData);

        if($data['type']==1) {//侦查城池推送
            $pushId = (new PlayerPush)->add($targetPlayerId, 2, 400006, []);//被玩家侦查
        }
        if($data['type']==2) {//侦查堡垒邮件
            $guildId   = $player['guild_id'];
            if($guildId) {
                $guild          = (new Guild)->getGuildInfo($guildId);
                $guildName      = $guild['name'];
                $guildShortName = $guild['short_name'];
            } else {
                $guildName      = '';
                $guildShortName = '';
            }
            $allMember = (new PlayerGuild)->getAllGuildMember($toMap['guild_id']);
            $playerIds = array_keys($allMember);
            //发送邮件
            $mailData = [
                'x'            => $toX,
                'y'            => $toY,
                'playerNick'   => $player['nick'],
                'playerAvatar' => $player['avatar_id'],
                'guildId'      => $guildId,
                'guildName'    => $guildName,
                'guildShort'   => $guildShortName,
            ];
            if(!(new PlayerMail)->sendSystem($playerIds, PlayerMail::TYPE_SPYBASEWARN, '', '', 0, $mailData)){
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
            }
        }

        socketSend(['Type'=>'spyed', 'Data'=>['playerId'=>[$targetPlayerId]]]);

        (new PlayerTarget)->updateTargetCurrentValue($playerId, 24);//更新新手目标任务

        //删除我方套子
        (new Player)->offAvoidBattle($playerId);

        dbCommit($db);
        Cache::unlock($lockKey);
        $data                     = [];
        echo $this->data->send($data);
        exit;
        sendErr: {
            dbRollback($db);
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     * 穿宝物装备
     * 
     * 使用方法如下
     * ```php
     * player/equipMasterOn
     * postData: {"old_id":111, "new_id":222, "position":1}
     * return: {{"old":[...], "new":[...]}}
     * ```
     */
    public function equipMasterOnAction(){
        $playerId = $this->getCurrentPlayerId();
        $postData = getPost();
        $oldId = $postData['old_id'];
        $newId = $postData['new_id'];
        $position = $postData['position'];
        $PlayerEquipMaster = new PlayerEquipMaster;
        $PlayerEquipMaster->changeToNewEquipMaster($playerId, $oldId, $newId, $position);
        if($oldId) {
            $data['old'] = $PlayerEquipMaster->getSingleEquipMaster($playerId, $oldId);
        } else {
            $data['old']= [];
        }
        $data['new'] = $PlayerEquipMaster->getSingleEquipMaster($playerId, $newId);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 脱宝物装备
     *
     * 使用方法如下
     * ```php
     * player/equipMasterOff
     * postData: {"id":111}
     * return: {...}
     * ```
     */
    public function equipMasterOffAction(){
        $playerId = $this->getCurrentPlayerId();
        $postData = getPost();
        $id = $postData['id'];
        $PlayerEquipMaster = new PlayerEquipMaster;
        $PlayerEquipMaster->changeEquipMasterStatusAndPosition($playerId, $id, PlayerEquipMaster::STATUS_OFF, -1);
        $data = $PlayerEquipMaster->getSingleEquipMaster($playerId, $id);
        echo $this->data->send($data);
        exit;
    }

    /**
     * 出售主公宝物
     *
     * 使用方法如下
     * ```php
     *  player/sellEquipMaster
     * postData: {"id":111}
     * return: {...}
     * ```
     */
    public function sellEquipMasterAction(){
        $playerId = $this->getCurrentPlayerId();
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' . $playerId;//锁定
        Cache::lock($lockKey);
        $postData          = getPost();
        $id                = $postData['id'];
        $PlayerEquipMaster = new PlayerEquipMaster;
        $equip             = $PlayerEquipMaster->getSingleEquipMaster($playerId, $id);
        if(!$equip) {
            $errCode = 10473;//[出售主公宝物]宝物不存在
            goto sendErr;
        }
        if($equip['status']==1) {
            $errCode = 10474;//[出售主公宝物]宝物已装备
            goto sendErr;
        }
        //出售
        $PlayerEquipMaster->sellEquipMaster($playerId, $id);
        Cache::unlock($lockKey);
        echo $this->data->send();
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 修改玩家
     *
     * 使用方法如下
     * 
     * ```php
     * player/alterPlayer
     * postData:{"type":1}
     * return: {}
     * ```
     * <pre>
     * - "type":1 #昵称
     * - "nick":"修改昵称"
     *
     * - "type":2 #头像
     * - "avatar_id":2 #头像id
     * </pre>
     */   
    public function alterPlayerAction(){
        $playerId = $this->getCurrentPlayerId();
        //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);

        $player     = $this->getCurrentPlayer();
        $originNick = $player['nick'];
        $postData   = getPost();
        $type       = $postData['type'];

        $SensitiveWord = new SensitiveWord;
        $Cost          = new Cost;
        $Player        = new Player;
        $PlayerItem    = new PlayerItem;
        $PlayerInfo    = new PlayerInfo;

        global $config;
        $serverId = $config->server_id;
        switch($type) {
            case 1:
                $nick     = $postData['nick'];
                $hasEmoji = hasEmoji($nick);
                $nick     = trim($nick);
                //a 修改昵称，昵称非法
                if($hasEmoji || strlen($nick)<1 || $SensitiveWord->checkSensitiveContent($nick, 2)) {
                    $errCode = 10248;
                    goto sendErr;
                }
                //b 昵称重复，包括和原来一样
                if($Player->checkNickExists($nick)) {
                    $errCode = 10250;
                    goto sendErr;
                }
                //c 第一次修改昵称的判断
                $playerInfo = $PlayerInfo->getByPlayerId($playerId);
                if($playerInfo['first_nick']!=0) {//不是第一次修改昵称
                    if($PlayerItem->hasItemCount($playerId, 22700)) {//d 先用改名道具
                        $PlayerItem->drop($playerId, 22700);
                    } else {
                        //e 宝石不足: 200元宝是否满足-修改玩家名称
                        if(!$Cost->updatePlayer($playerId, 108)){//gem不足
                            $errCode = 10249;
                            goto sendErr;
                        }
                    }
                    $Player->alter($playerId, ['nick'=>q($nick)]);

                    (new PlayerServerList)->updateInfo($player['uuid'], $serverId, 'nick', $nick);//改昵称
                } else {
                    if($Player->alter($playerId, ['nick'=>q($nick)])) {
                        (new PlayerServerList)->updateInfo($player['uuid'], $serverId, 'nick', $nick);//改昵称
                        $PlayerInfo->alter($playerId, ['first_nick'=>1]);
                    }
                    
                }
                //日志
                $PlayerCommonLog = new PlayerCommonLog;
                $PlayerCommonLog->add($playerId, ['type'=>'更改昵称', 'fromNick'=>$originNick, 'toNick'=>$nick]);
                break;
            case 2://修改头像
                $avatarId = $postData['avatar_id'];
                //先用改头像道具
                if($PlayerItem->hasItemCount($playerId, 22800)) {//改名道具
                    $PlayerItem->drop($playerId, 22800);
                } else {
                     //宝石不足: 200元宝是否满足-修改玩家名称
                    if(!$Cost->updatePlayer($playerId, 109)){//gem不足
                        $errCode = 10251;
                        goto sendErr;
                    }
                }
                $Player->alter($playerId, ['avatar_id'=>$avatarId]);
                (new PlayerServerList)->updateInfo($player['uuid'], $serverId, 'avatar_id', $avatarId);//改头像
                break;
            break;

        }
        Cache::unlock($lockKey);
        $data = $Player->getByPlayerId($playerId);
        // $this->data->filterBasic(['Player'], true);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 根据昵称搜索玩家
     *
     * 使用方法如下
     * ```php
     * player/searchPlayer
     * postData: {"type":1,"nick":"zhangsan","from_page":1,"num_per_page":10}
     * return: {{Player}...}
     * ```
     */
    public function searchPlayerAction(){
        $playerId = $this->getCurrentPlayerId();
        $postData = getPost();
        $type     = $postData['type'];
        if($type==1) {//暂时这样，为之后留接口
            $nick                       = $postData['nick'];
            $searchData['from_page']    = $postData['from_page'];
            $searchData['num_per_page'] = $postData['num_per_page'];
            $SensitiveWord              = new SensitiveWord;
            $Player                     = new Player;
            //a 搜索关键字有敏感字
            if(strlen($nick)<1 || $SensitiveWord->checkSensitiveContent($nick)) {
                $errCode = 10143;
                goto sendErr;
            }
            $data = $Player->searchByNick($nick, $searchData);
            echo $this->data->send($data);
            exit;
        } else {
            $errCode = 1;
        }
        sendErr: {
            echo $this->data->sendErr($errCode);
            exit; 
        }
    }
    /**
     * 获取无联盟的玩家
     *
     * ```php
     * player/getPlayerNoGuild
     * postData: {"base_num":0}
     * return: {{Player}...}
     * ```
     */
    public function getPlayerNoGuildAction(){
        $playerId = $this->getCurrentPlayerId();
        $postData = getPost();
        $baseNum  = $postData['base_num'];
        $Player   = new Player;
        $data     = $Player->getPlayerNoGuild($baseNum);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 天赋加点
     * 
     * $_POST['talentTypeId'] 天赋类型
     * @return <type>
     */
	public function talentAddAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$talentTypeId = floor(@$post['talentTypeId']);
		if(!checkRegularNumber($talentTypeId))
			exit;
		
		$Player = new Player;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			
			//获取所有天赋
			$Talent = new Talent;
			$talents = $Talent->dicGetAll();
			
			//获取玩家天赋
			$PlayerTalent = new PlayerTalent;
			$playerTalent = $PlayerTalent->getByPlayerId($playerId);
			
			//检查输入天赋类型是否存在
			$theseTalents = array();
			foreach($talents as $_t){
				if($_t['talent_type_id'] == $talentTypeId){
					$theseTalents[] = $_t['id'];
				}
			}
			if(!$theseTalents){
				throw new Exception(10144);
			}
			
			//如果玩家存在该类型天赋且未满级，获得下一级天赋id
			$hasTalent = 0;
			foreach($playerTalent as $_t){
				if(in_array($_t['talent_id'], $theseTalents)){
					$hasTalent = $_t['talent_id'];
					$thisTalent = $_t;
					break;
				}
			}
			if($hasTalent){
				$nextTalent = $talents[$hasTalent]['next_talent'];
				if($nextTalent == -1){
					throw new Exception(10145);
				}
				$isNew = false;
			}else{//如果玩家不存在该类型天赋，获取一级天赋id
				foreach($theseTalents as $_t){
					if($talents[$_t]['level_id'] == 1){
						$nextTalent = $talents[$_t]['id'];
						break;
					}
				}
				$isNew = true;
			}
			
			//判断前置天赋条件
			if($talents[$nextTalent]['condition_talent']){
				$playerTalentIds = Set::extract('/talent_id', $playerTalent);
				$findFlag = false;
				foreach($talents[$nextTalent]['condition_talent'] as $_t){
					if(in_array($_t, $playerTalentIds)){
						$findFlag = true;
						break;
					}
				}
				if(!$findFlag){
					throw new Exception(10146);
				}
			}
			
			//判断玩家等级
			/*if($player['level'] < $talents[$nextTalent]['master_level']){
				throw new Exception(10147);
			}*/
			
			//获取已花费天赋点
			//$costPoint = PlayerTalent::getCostPoint($playerTalent);
			
			//判断天赋点足够
			/*$Master = new Master;
			$master = $Master->dicGetAll();
			$playerTalentNum = $master[$player['level']]['talent_num'];
			if($playerTalentNum < $costPoint + $talents[$nextTalent]['cost']){
				throw new Exception(10148);
			}*/
			
			if($player['talent_num_remain'] < $talents[$nextTalent]['cost']){
				throw new Exception(10149);
			}
			
			//更新天赋
			if($isNew){
				if(!$PlayerTalent->add($playerId, $nextTalent)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				if(!$PlayerTalent->assign($thisTalent)->lvup($nextTalent)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//更新天赋点
			//$costPoint += $talents[$nextTalent]['cost'];
			if(!$Player->updateAll(array('talent_num_remain'=>'talent_num_remain-'.$talents[$nextTalent]['cost']), array('id'=>$playerId))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//激活主动技能
			$MasterSkill = new MasterSkill;
			$masterSkill = $MasterSkill->dicGetOne($nextTalent);
			if($masterSkill){
				$PlayerMasterSkill = new PlayerMasterSkill;
				if(!$PlayerMasterSkill->enable($playerId, $nextTalent)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//buff
			$Drop = new Drop;
			foreach($talents[$nextTalent]['talent_drop'] as $_td){
				if(!$Drop->gain($playerId, array($_td), 1, 'addTalent:'.$nextTalent)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			$Player->clearDataCache($playerId);
			$retData = $PlayerTalent->getAllByTalentId($playerId, $nextTalent);
			
			$Player->refreshPower($playerId, 'master_power');
			
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
		
		//$data = DataController::get($playerId, array('PlayerTalent'));
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 重置天赋
     * 
     * 
     * @return <type>
     */
	public function talentResetAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		$Player = new Player;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$itemId = 23601;
			
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->drop($playerId, $itemId, 1)){
				
				//扣除gem
				$Cost = new Cost;
				if(!$Cost->updatePlayer($playerId, 306)){
					throw new Exception(10150);
				}
				
			}
			
			//获取所有talent配置
			$Talent = new Talent;
			$talent = $Talent->dicGetAll();
			
			//获取buff对照配置
			$Buff = new Buff;
			$buff = $Buff->dicGetAll();
			
			$PlayerTalent = new PlayerTalent;
			//获取所有天赋和前置等级，整理需要扣除的buff点
			$playerTalent = $PlayerTalent->getByPlayerId($playerId);
			if(!$playerTalent){
				throw new Exception(10151);
			}
			//整理talentType=>level
			$talentArr = array();
			foreach($playerTalent as $_t){
				$talentArr[$talent[$_t['talent_id']]['talent_type_id']] = $talent[$_t['talent_id']]['level_id'];
			}
			//循环talent，整理buff
			$PlayerBuff = new PlayerBuff;
			$Drop = new Drop;
			$drop = $Drop->dicGetAll();
			foreach($talent as $_talent_id => $_t){
				if(isset($talentArr[$_t['talent_type_id']]) && $_t['level_id'] <= $talentArr[$_t['talent_type_id']]){
					//$_drop = $Drop->dicGetOne($_t['talent_drop']);
					foreach($_t['talent_drop'] as $_drop){
						foreach($drop[$_drop]['drop_data'] as $_d){
						//foreach($_drop['drop_data'] as $_d){
							if($_d[0] != 5) continue;
							$PlayerBuff->setPlayerBuff($playerId, $_d[1], $_d[2], true);
						}
					}
				}
			}
			
			//删除所有talent
			$PlayerTalent->reset($playerId);
			
			//删除主动技能
			$PlayerMasterSkill = new PlayerMasterSkill;
			$PlayerMasterSkill->resetSkill($playerId);
			
			//重置点数
			if(!$Player->updateAll(array('talent_num_remain'=>'talent_num_total'), array('id'=>$playerId))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$Player->refreshPower($playerId, 'master_power');
			(new PlayerBuild)->refreshWallDurability($playerId);

			
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
     * 天赋主动技能使用
     * 
     * 
     * @return <type>
     */
	public function talentUseAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$talentId = floor(@$post['talentId']);
		if(!checkRegularNumber($talentId))
			exit;
		
		$Player = new Player;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$retData = array();
			//获取主动天赋配置
			$MasterSkill = new MasterSkill;
			$masterSkill = $MasterSkill->dicGetOne($talentId);
			if(!$masterSkill){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取玩家主动天赋
			$PlayerMasterSkill = new PlayerMasterSkill;
			$pms = $PlayerMasterSkill->getByTalentId($playerId, $talentId);
			if(!$pms){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断是否存在和激活
			if(!$pms['enable']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断使用时间
			if($pms['next_time'] > time()){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//使用效果
			$buffs = array();
			switch($talentId){
				case '100401'://强行军
					$buffs[] = array('id'=>101, 'time'=>3600);
					$buffs[] = array('id'=>102, 'time'=>3600);
                    $buffs[] = array('id'=>104, 'time'=>3600);//采矿
				break;
				case '101601'://战场救护
					$buffs[] = array('id'=>103, 'time'=>3600*24*365);
				break;
				case '102801'://临战状态
					$buffs[] = array('id'=>107, 'time'=>3600);
					$buffs[] = array('id'=>108, 'time'=>3600);
					$buffs[] = array('id'=>109, 'time'=>3600);
					$buffs[] = array('id'=>110, 'time'=>3600);
				break;
				case '203301'://税收
					$resource = (new PlayerBuild)->getTotalResourceIn($playerId);
					$flag = false;
					foreach($resource as &$_r){
						if($_r > 0)
							$flag = true;
						$_r *= 3;
					}
					unset($_r);
					if($flag){
						if(!(new Player)->updateResource($playerId, $resource)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
					$retData['203301'] = $resource;
				break;
				case '203901'://资源保护
					$buffs[] = array('id'=>111, 'time'=>3600*2);
				break;
				case '205001'://大丰收
					$buffs[] = array('id'=>112, 'time'=>3600*2);
					$buffs[] = array('id'=>113, 'time'=>3600*2);
					$buffs[] = array('id'=>114, 'time'=>3600*2);
					$buffs[] = array('id'=>115, 'time'=>3600*2);
					$buffs[] = array('id'=>116, 'time'=>3600*2);
				break;
				case '305501'://战术机动 todo
					//获取所有队列
					$PlayerProjectQueue = new PlayerProjectQueue;
					if(!$PlayerProjectQueue->callbackQueueNowByPlayerId($playerId)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				break;
				case '306301'://快马加鞭
					if(!$Player->updateMove($playerId, 50)){
						throw new Exception(10437);//行动力已满，无法使用主动技
					}
				break;
				case '307001'://金城汤池
					$buffs[] = array('id'=>10002, 'time'=>3600);
					$buffs[] = array('id'=>10003, 'time'=>3600);
					$buffs[] = array('id'=>10004, 'time'=>3600);
					$buffs[] = array('id'=>10005, 'time'=>3600);
					$buffs[] = array('id'=>10006, 'time'=>3600);
					$buffs[] = array('id'=>10007, 'time'=>3600);
					$buffs[] = array('id'=>10008, 'time'=>3600);
					$buffs[] = array('id'=>10009, 'time'=>3600);
				break;
				default:
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$effectSecond = 0;
			if($buffs){
				$PlayerBuffTemp = new PlayerBuffTemp;
				$BuffTemp = new BuffTemp;
				foreach($buffs as $_b){
					$effectSecond = $_b['time'];
					if(!$PlayerBuffTemp->up($playerId, $_b['id'], $_b['time'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			
			//更新使用时间
			if(!$PlayerMasterSkill->useSkill($playerId, $talentId, $masterSkill['cd'], $effectSecond)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerTarget)->updateTargetCurrentValue($playerId, 16, 1);
			
			if('205001' == $talentId){
				//重算采集速度
				/*$PlayerProjectQueue = new PlayerProjectQueue;
				if(!$PlayerProjectQueue->refreshCollection($playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}*/
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
		
		//$data = DataController::get($playerId, array('PlayerTalent'));
		if(!$err){
			echo $this->data->send($retData);
		}else{
			echo $this->data->sendErr($err);
		}

	}
	
	/**
     * 修复城墙
     *
     * 使用方法如下
     *```php
     * /player/restoreWallDurability
     * postData: json={}
     * return: {}
     * ```
     */
	public function restoreWallDurabilityAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$Player = new Player;
		$Player->restoreWallDurability($playerId);
		echo $this->data->send();
	}

	/**
     * 城墙灭火
     *
     * 使用方法如下
     *```php
     * /player/clearFire
     * postData: json={}
     * return: {}
     * ```
     */
	public function clearFireAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$Player = new Player;
		$Player->clearFire($playerId);
		echo $this->data->send();
	}

    /**
     * 刷新城墙耐久
     *
     * 使用方法如下
     *```php
     * /player/refreshWall/
     * postData: json={}
     * return: {}
     * ```
     */
    public function refreshWallAction(){
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $Player = new Player;
        $Player->inventoryWallDurability($playerId);
        echo $this->data->send();
    }

    /**
     * 商店购买
     * 
     * 
     * @return <type>
     */
	public function shopBuyAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$shopId = floor(@$post['shopId']);
		$itemNum = floor(@$post['itemNum']);
		$use = floor(@$post['use']);
		if(!checkRegularNumber($shopId) || !checkRegularNumber($itemNum))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取城池等级
			$PlayerBuild = new PlayerBuild;
			$playerBuild = $PlayerBuild->getByOrgId($playerId, 1);
			$cityLv = $playerBuild[0]['build_level'];
			
			
			//检查道具是否属于商店列表
			$Shop = new Shop;
			$shop = $Shop->dicGetOne($shopId);
			if(!$shop){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($shop['shop_type'] == 3){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断允许等级
			if($cityLv < $shop['min_level'] || $cityLv > $shop['max_level']){
				throw new Exception(10371);//无法购买该道具
			}
			
			//$itemId = $shop['item_id'];
			
			//获取当日购买记录
			$PlayerShop = new PlayerShop;
			$playerShop = $PlayerShop->getByShopId($playerId, $shopId);
			if($playerShop){
				//检查购买上限
				if($shop['buy_daily_limit'] != -1 && $playerShop['num'] + $itemNum > $shop['buy_daily_limit']){
					throw new Exception(10342);//购买数量超过上限
				}
			}
			
			//获取所有costId
			$Cost = new Cost;
			$costs = $Cost->getByCostId($shop['cost_id']);
			
			//var_dump($costs);
			$beginCt = $playerShop['num'] + 1;
			$endCt = $playerShop['num'] + $itemNum;
			$costList = [];
			$gem = 0;
			foreach($costs as $_cost){
				if($beginCt <= $_cost['min_count'] && $endCt >= $_cost['max_count']){
					$_num = $_cost['max_count'] - $_cost['min_count'] + 1;
				}elseif($beginCt >= $_cost['min_count'] && $endCt <= $_cost['max_count']){
					$_num = $endCt - $beginCt + 1;
				}elseif($beginCt <= $_cost['min_count'] && $endCt <= $_cost['max_count'] && $endCt >= $_cost['min_count']){
					$_num = $endCt - $_cost['min_count'] + 1;
				}elseif($beginCt >= $_cost['min_count'] && $beginCt <= $_cost['max_count'] && $endCt >= $_cost['max_count']){
					$_num = $_cost['max_count'] - $beginCt + 1;
				}else{
					continue;
				}
				$costList[$_cost['min_count']] = $_num;
				
				//消耗货币
				if(!$Cost->updatePlayer($playerId, $shop['cost_id'], $_cost['min_count'], $_num)){
					throw new Exception(10152);
				}
				
				if($_cost['cost_type'] == 7){
					$gem += $_cost['cost_num'] * $_num;
				}
			}
			
			
			
			/*$i = 1;
			$gem = 0;
			while($i <= $itemNum){
				//消耗货币
				if(!$Cost->updatePlayer($playerId, $shop['cost_id'], $playerShop['num']+$i, 1)){
					throw new Exception(10152);
				}
				
				$cost = $Cost->getByCostId($shop['cost_id'], $playerShop['num']+$i);
				$cost = current($cost);
				if($cost['cost_type'] == 7){
					$gem += $cost['cost_num'];
				}
				$i++;
			}*/
			
			//增加个人道具
			$Drop = new Drop;
			$dropItems = $Drop->gain($playerId, [$shop['commodity_data']], $itemNum, '商店购买'.$shopId);
			if(!$dropItems){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新购买记录
			if(!$PlayerShop->up($playerId, $shopId, $itemNum)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($gem){
				(new PlayerMission)->updateMissionNumber($playerId, 17, $gem);
			}
			
			if($use){
				$Item = new Item;
				foreach($dropItems as $_di){
					if($_di['type'] == 2){
						$_item = $Item->dicGetOne($_di['id']);
						if($_item['button_type']){
							(new ItemController)->useItem($player, $_di['id'], $_di['num']);
						}
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

	public function getBuffAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		$ret = $this->getBuff($playerId, $err);
		
		if(!$err){
			echo $this->data->send(array('PlayerBuff'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	function getBuff($playerId, &$err=''){
		$player = (new Player)->getByPlayerId($playerId);
		try {
			$ret = [];
			//获取固定buff
			$PlayerBuff = new PlayerBuff;
			$playerBuff = $PlayerBuff->getByPlayerId($playerId, true);
			foreach($playerBuff as $_bn => $_pb){
				if(in_array($_bn, ['id', 'player_id'])) continue;
				$ret[$_bn] = ['v'=>$_pb*1, 'tmp'=>[]];
			}
			
			$PlayerGeneralBuff = new PlayerGeneralBuff;
			$playerGeneralBuff = $PlayerGeneralBuff->getByPlayerId($playerId, true);
			unset($playerGeneralBuff['id'], $playerGeneralBuff['player_id']);
			$playerGeneralBuff = array_diff($playerGeneralBuff, [0]);
			foreach($playerGeneralBuff as $_bn => $_pb){
				if(in_array($_bn, ['id', 'player_id'])) continue;
				@$ret[$_bn]['v'] += $_pb;
			}
			
			//获取临时buff
			//$playerBuffTemp = [];
			$PlayerBuffTemp = new PlayerBuffTemp;
			$pbt = $PlayerBuffTemp->getByPlayerId($playerId, true);
			//$pbtRet = [];
			$now = time();
			foreach($pbt as $_pbt){
				if($_pbt['expire_time'] > $now){
					//$playerBuff[$_pbt['buff_name']] += $_pbt['buff_num'];
					//$playerBuffTemp[] = $_pbt;
					@$ret[$_pbt['buff_name']]['v'] += $_pbt['buff_num'];
					$ret[$_pbt['buff_name']]['tmp'][] = [
						'position'=>$_pbt['position'],
						'v'=>$_pbt['buff_num'],
						'expire_time'=>$_pbt['expire_time'],
					];
				}
			}
			
			//guild buff
			//$pg = (new PlayerGuild)->getByPlayerId($playerId);
			if($player['guild_id']){
				$gb = (new GuildBuff)->getByGuildId($player['guild_id']);
				foreach($gb as $_gb){
					$ret[$_gb['buff_name']]['v'] += $_gb['buff_num'];
				}
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			return $ret;
		}else{
			return false;
		}
	}
	
    /**
     * 获取道具buff
     * 
     * 
     * @return <type>
     */
	public function getItemBuffAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		
		try {
			$filter = range(1, 24);
			$ret = [];
			
			//获取临时buff
			$PlayerBuffTemp = new PlayerBuffTemp;
			$pbt = $PlayerBuffTemp->getByPlayerId($playerId);
			$now = time();
			foreach($pbt as $_pbt){
				if($_pbt['expire_time'] > $now && in_array($_pbt['buff_temp_id'], $filter)){
					$ret[$_pbt['buff_temp_id']] = [
						'num' => $_pbt['buff_num'],
						'begin_time' => $_pbt['create_time'],
						'expire_time' => $_pbt['expire_time'],
					];
				}
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('PlayerItemBuff'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 购买额外建造队列
     * 
     * 
     * @return <type>
     */
	public function buyExtraBuildQueueAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$itemNum = floor(@$post['itemNum']);
		if(!checkRegularNumber($itemNum))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//cost
			$costId = 307;
			$Cost = new Cost;
			if(!$Cost->updatePlayer($playerId, $costId, 0, $itemNum)){
				throw new Exception(10121);
			}
			
			//drop
			$dropId = 100100;
			$Drop = new Drop;
			if(!$Drop->gain($playerId, [$dropId], $itemNum, '建造队列x'.$itemNum)){
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
     * 武将buff
     *
     * 使用方法如下
     * ```php
     * player/getGeneralBuffByBuild
     * postData:{"position":[1,2,3,4]}
     * return:{}
     * ```
     */
    public function getGeneralBuffByBuildAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $post = getPost();
        $position = $post['position'];
        foreach ($position as $value) {
            if(!checkRegularNumber($value))
            exit;
            $PlayerBuild = new PlayerBuild;
            $generalBuff[$value] = $PlayerBuild->calcGeneralBuff($playerId, $value);
        }
       
        echo $this->data->send(['generalBuff'=>$generalBuff]);
    }

    /**
     * 排行榜
     * 
     * 
     * @return <type>
     */
	public function getRankAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$type = floor(@$post['type']);
		if(!checkRegularNumber($type))
			exit;
		if(!in_array($type, range(1, 6)))
			exit;

		try {
			$ret = Cache::db()->get('Rank:'.$type);
			if(!$ret){
				$Rank = new Rank;
				$ret = $Rank->find(['type='.$type, 'order'=>'rank'])->toArray();
				$ret = filterFields($ret, true, $Rank->blacklist);
				Cache::db()->set('Rank:'.$type, $ret);
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('Rank'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 检查教程中bug
     *
     * 使用方法如下
     * ```php
     * player/tutorialCheck
     * postData:{"type":1}
     * return:{}
     * ```
     */
    public function tutorialCheckAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $post = getPost();
        $type = floor(@$post['type']);
        $PlayerInfo = new PlayerInfo;
        $playerInfo = $PlayerInfo->getByPlayerId($playerId);
        if($playerInfo['skip_newbie']==0 && $player['step']>100701){
            return;
        }

        
        switch ($type) {
            case 1:
                $PlayerArmyUnit = new PlayerArmyUnit;
                $Player = new Player;
                $re = $PlayerArmyUnit->getByGeneralId($playerId, 20050);
                if($re['soldier_id']!=10001 || $re['soldier_num']<10){
                    $ret = $PlayerArmyUnit->updateAll(array(
                    'soldier_id' =>10001,
                    'soldier_num'=>10,
                    'update_time'=>qd(),
                    'rowversion'=>"'".uniqid()."'",
                    ), ["player_id"=>$playerId, 'general_id'=>20050]);
                    $PlayerArmyUnit->_clearDataCache($playerId);
                    $Player->refreshPower($playerId, 'army_power');
                }
                break;
            case 2:
                $PlayerSoldier = new PlayerSoldier;
                $re = $PlayerSoldier->getBySoldierId($playerId, 30001);
                if(empty($re) || $re['num']<10){
                    if(empty($re)){
                        $num = 10;
                    }else{
                        $num = 10-$re['num'];
                    }
                    $PlayerSoldier->updateSoldierNum($playerId, 30001, $num);
                }
                break;
            case 3:
                $PlayerArmyUnit = new PlayerArmyUnit;
                $Player = new Player;
                $re = $PlayerArmyUnit->getByGeneralId($playerId, 20050);
                if($re['soldier_id']!=10001 || $re['soldier_num']<10){
                    $ret = $PlayerArmyUnit->updateAll(array(
                    'soldier_id' =>10001,
                    'soldier_num'=>10,
                    'update_time'=>qd(),
                    'rowversion'=>"'".uniqid()."'",
                    ), ["player_id"=>$playerId, 'general_id'=>20050]);
                    $PlayerArmyUnit->_clearDataCache($playerId);
                    $Player->refreshPower($playerId, 'army_power');
                }
                $re = $PlayerArmyUnit->getByGeneralId($playerId, 20022);
                if($re['soldier_id']!=30001 || $re['soldier_num']<10){
                    $ret = $PlayerArmyUnit->updateAll(array(
                    'soldier_id' =>30001,
                    'soldier_num'=>10,
                    'update_time'=>qd(),
                    'rowversion'=>"'".uniqid()."'",
                    ), ["player_id"=>$playerId, 'general_id'=>20022]);
                    $PlayerArmyUnit->_clearDataCache($playerId);
                    $Player->refreshPower($playerId, 'army_power');
                }
                break;
            default:
                # code...
                break;
        }
        echo $this->data->send();
    }

	public function useCdkAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$cdk = @$post['cdk'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		$lockKey2 = __CLASS__ . ':' . __METHOD__ . ':cdk=' .$cdk;
		Cache::lock($lockKey);
		Cache::lock($lockKey2);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			if(!$cdk || strlen($cdk) != 12){
				throw new Exception(10422);//无效的兑换码
			}
			
			$PlayerInfo = new PlayerInfo;
			$playerInfo = $PlayerInfo->getByPlayerId($playerId);
			
			$Cdk = new Cdk;
			$time = date('Y-m-d H:i:s');
			if(!$k = $Cdk->findFirst(['cdk="'.$cdk.'" and status=0'])){
				throw new Exception(10423);//无效的兑换码
			}
			$k = $k->toArray();
			if($time < $k['begin_time'] || $time > $k['end_time']){
				throw new Exception(10715);//激活码已过期
			}
			//核对语言
			if($k['lang'] && strtolower($k['lang']) != strtolower($player['lang'])){
				throw new Exception(10424);//无效的兑换码
			}
			//核对渠道
			if($k['channel']){
				if($playerInfo['pay_channel'] == 'anysdk'){
					$payChannel = $playerInfo['login_channel'];
				}else{
					$payChannel = $playerInfo['pay_channel'];
				}
				if($payChannel != $k['channel']){
					throw new Exception(10425);//无效的兑换码
				}
			}
			//新增playercdk
			if(!(new PlayerCdk)->add($playerId, $cdk)){
				throw new Exception(10426);//无效的兑换码
			}
			
			//drop
			if(!$dropData = (new Drop)->gainFromDropStr($playerId, $k['drop'], '激活码礼包')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新cdk
			if(!$Cdk->updateUse($cdk, $playerId, $k['type'], $k['rowversion'])){
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
		Cache::unlock($lockKey2);
		
		if(!$err){
			echo $this->data->send(['dropData'=>$dropData]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 累计充值
     * 
     * 
     * @return <type>
     */
	public function activityChargeAction(){
		
	}
}