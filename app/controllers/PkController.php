<?php
/**
 * 武斗控制器
 */
class PkController extends ControllerBase{
    /**
     * 是否内部调用
     * @var bool
     */
    public $innerCallFlag = false;
    /**
     * 内部控制器调用传参数
     * @var array
     */
    public $passedArgs = [];
    /**
     * 武将上阵-出阵
     *
     * ```php
     * pk/pkPosition
     * postData: {"general_1":999,"general_2":888,"general_3":777}
     * return: {PkPlayerInfo}
     * ```
     */
    public function pkPositionAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定
        $postData = getPost();
        $g1 = isset($postData['general_1']) ? $postData['general_1'] : -1;
        $g2 = isset($postData['general_2']) ? $postData['general_2'] : -1;
        $g3 = isset($postData['general_3']) ? $postData['general_3'] : -1;
        //判断武将是否重复
        $gRepeatArr = [];
        if($g1>0) $gRepeatArr[] = $g1;
        if($g2>0) $gRepeatArr[] = $g2;
        if($g3>0) $gRepeatArr[] = $g3;
        if(!empty($gRepeatArr)) {
            $gRepeatArr = array_count_values($gRepeatArr);
            asort($gRepeatArr);
            $head = array_pop($gRepeatArr);
            if ($head > 1) {
                $errCode = 10533;//[武将出阵]不能有重复武将
                goto sendErr;
            }
        } else {
            $errCode = 10534;//[武将出阵]没有传武将
            goto sendErr;
        }
        $PkPlayerInfo = new PkPlayerInfo;
        $ppi          = $PkPlayerInfo->getBasicInfo($serverId, $playerId);
        if($ppi) {
            $updateData = [];
            foreach(['general_1'=>$g1, 'general_2'=>$g2, 'general_3'=>$g3] as $k=>$v) {
                if($v==0) {
                    $errCode = 10535;//[武将出阵]不能有空武将
                    goto sendErr;
                } elseif ($v>0) {
                    $updateData[$k] = $v;
                }
            }
            $re = $PkPlayerInfo->alter($serverId, $playerId, $updateData);
            if($re==-1) {
                $errCode = 10536;//[武将出阵]武将不存在
                goto sendErr;
            }
        } else {//首次操作上阵武将
            $updateData               = [];
            $updateData['general_1']  = $g1==-1 ? 0 : $g1;
            $updateData['general_2']  = $g2==-1 ? 0 : $g2;
            $updateData['general_3']  = $g3==-1 ? 0 : $g3;
            $re = $PkPlayerInfo->addNew($serverId, $playerId, $updateData);
            if($re==-1) {
                $errCode = 10537;//[武将出阵]武将不存在
                goto sendErr;
            }
        }
        Cache::unlock($lockKey);
        (new PlayerCommonLog)->add($playerId, ['type'=>'武将上阵', 'memo'=>['general'=>$updateData]]);//日志
        echo $this->data->send();
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 匹配玩家
     *
     * ```php
     * pk/pkMatch
     * postData: {}
     * return: {...}
     * ```
     */
    public function pkMatchAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定

        $PkPlayerInfo    = new PkPlayerInfo;
        $Pk              = new Pk;
        $PlayerGeneral   = new PlayerGeneral;
        $DuelInitdata    = new DuelInitdata;
        $DuelRobot       = new DuelRobot;
        $PkGroup         = new PkGroup;
        $PlayerCommonLog = new PlayerCommonLog;

        //初始化数据
        $data         = [];
        $initData     = $DuelInitdata->get();
        $pkPlayerInfo = $PkPlayerInfo->getBasicInfo($serverId, $playerId);
        //赛季判断
        $pkgroup   = $PkGroup->getPkGroupByServerId($serverId);
        $closeTime = intval($initData['duel_close_time']);
        if(is_null($pkgroup)) {
            $errCode = 10538;//[武斗匹配]武斗暂未开始
            goto sendErr;
        }
        $crst = $pkgroup['current_round_start_time'];
        $nrst = $pkgroup['next_round_start_time'];
        if(time()>strtotime($nrst) || time()<strtotime($crst)) {
            $errCode = 10539;//[武斗匹配]武斗暂未开始
            goto sendErr;
        }
        if(time()>(strtotime($nrst)-$closeTime*3600) && time()<strtotime($nrst)){
            $errCode = 10540;//[武斗匹配]赛季结算期间不能匹配
            goto sendErr;
        }
        //判断是否有未结束武斗
        $lastPk = $Pk->getLastPk($serverId, $playerId);
        if(!empty($lastPk)){
            $errCode = 10541;//[武斗匹配]前一场匹配武斗尚未结束
            goto sendErr;
        }
        if(!$pkPlayerInfo['general_1'] || !$pkPlayerInfo['general_2'] || !$pkPlayerInfo['general_3']) {
            $errCode = 10542;//[武斗匹配]武将不能留空
            goto sendErr;
        }
        $robotCount = $initData['robot_count'];
        if($pkPlayerInfo['pk_with_npc_times']<$robotCount) {//首次 不扣次数 匹配NPC
            $pk = $Pk->addNew($serverId, $playerId, 0, -1, Pk::TYPE_NPC);
            $data['pk_id'] = (int)$pk['id'];
            $PkPlayerInfo->alter($serverId, $playerId, ['pk_with_npc_times'=>'pk_with_npc_times+1']);//更改首次标记
            $npcFlag = true;
            $data['is_npc'] = 1;
        } else {
            $data['is_npc'] = 0;
            //case 1 扣次数或元宝
            //a 先扣免费次数
            $freeTimes = $pkPlayerInfo['free_search_times_per_day'];
            if($freeTimes>0) {
//                $freeTimesFlag = true;
                $PkPlayerInfo->alter($serverId, $playerId, ['free_search_times_per_day'=>"GREATEST(free_search_times_per_day-1, 0)"]);
                $PlayerCommonLog->add($playerId, ['type'=>'武斗免费次数', 'memo'=>['free_times'=>$pkPlayerInfo['free_search_times_per_day']]]);//日志
            } else {
                //b 再扣元宝
                $buyTimes = $pkPlayerInfo['current_day_buy_times'];
                $costId   = $initData['battle_cost'];
                $Cost     = new Cost;
                if($Cost->updatePlayer($playerId, $costId, $buyTimes+1)){
//                    $buyTimesFlag = true;
                    $PkPlayerInfo->alter($serverId, $playerId, ['current_day_buy_times'=>"current_day_buy_times+1"]);
                    $PlayerCommonLog->add($playerId, ['type'=>'武斗元宝购买', 'memo'=>['current_day_buy_times'=>$pkPlayerInfo['current_day_buy_times']]]);//日志
                } else {
                    $errCode = 10543;//[武斗匹配]元宝不足
                    goto sendErr;
                }
            }
            //case 2 匹配 ===
            $matchResult = $PkPlayerInfo->match($serverId, $playerId);

            $targetServerId = $matchResult['server_id'];
            $targetPlayerId = $matchResult['player_id'];
            $PlayerCommonLog->add($playerId, ['type'=>'武斗匹配', 'memo'=>['server_id'=>$targetServerId, 'player_id'=>$targetPlayerId]]);//日志
            //case 3 生成pk
            $pk = $Pk->addNew($serverId, $playerId, $targetServerId, $targetPlayerId);
            if(is_int($pk) && $pk == -1) {
                $errCode = 10061;//程序异常
                goto sendErr;
            }
            $data['pk_id'] = (int)$pk['id'];
        }
        //组装前端数据
        //己方
        $meGeneral           = $PlayerGeneral->getByPlayerId($playerId, true);
        $meGeneral           = Set::combine($meGeneral, '{n}.general_id', '{n}');
        $me                  = keepFields($pkPlayerInfo, ['server_id', 'player_id', 'score', 'duel_rank_id'], true);
        $me['nick']          = $player['nick'];
        $me['general_1']     = Pk::formatGeneralInfo($meGeneral[$pkPlayerInfo['general_1']]);
        $me['general_2']     = Pk::formatGeneralInfo($meGeneral[$pkPlayerInfo['general_2']]);
        $me['general_3']     = Pk::formatGeneralInfo($meGeneral[$pkPlayerInfo['general_3']]);

        $me['buff']          = array_map('intval', $Pk->getPlayerPkBuff($serverId, $playerId));
        //对方
        if(isset($npcFlag)) {//NPC信息
            $robot                  = $DuelRobot->getByCount($pkPlayerInfo['pk_with_npc_times'] + 1);
            $target['nick']         = $robot['nick'];
            $target['server_id']    = 0;
            $target['player_id']    = -1;
            $target['score']        = 0;
            $target['duel_rank_id'] = 1;
            $npcGeneralBasicInfo    = [
                'lv'                => intval($robot['lv']),
                'star_lv'           => intval($robot['star_lv']),
                'weapon_id'         => intval($robot['weapon_id']),
                'armor_id'          => intval($robot['armor_id']),
                'horse_id'          => intval($robot['horse_id']),
                'zuoji_id'          => intval($robot['zuoji_id']),
                'skill_lv'          => intval($robot['skill_lv']),
                'force_rate'        => 0,
                'intelligence_rate' => 0,
                'governing_rate'    => 0,
                'charm_rate'        => 0,
                'political_rate'    => 0,
            ];
            $target['general_1']    = array_merge(['general_id'=>intval($robot['general_1'])], $npcGeneralBasicInfo);
            $target['general_2']    = array_merge(['general_id'=>intval($robot['general_2'])], $npcGeneralBasicInfo);
            $target['general_3']    = array_merge(['general_id'=>intval($robot['general_3'])], $npcGeneralBasicInfo);
            $target['buff'] = [
                'general_force_inc'        => 0,
                'general_intelligence_inc' => 0,
                'general_governing_inc'    => 0,
                'general_charm_inc'        => 0,
                'general_political_inc'    => 0,
            ];
        } else {
            $general1 = $PlayerGeneral->getPkGeneralBasicInfo($targetServerId, $targetPlayerId, $matchResult['general_1'])['PlayerGeneral'];
            $general2 = $PlayerGeneral->getPkGeneralBasicInfo($targetServerId, $targetPlayerId, $matchResult['general_2'])['PlayerGeneral'];
            $general3 = $PlayerGeneral->getPkGeneralBasicInfo($targetServerId, $targetPlayerId, $matchResult['general_3'])['PlayerGeneral'];

            $target              = keepFields($matchResult, ['server_id', 'player_id', 'score', 'duel_rank_id'], true);
            $target['nick']      = $pk['target_nick'];
            $target['general_1'] = Pk::formatGeneralInfo($general1);
            $target['general_2'] = Pk::formatGeneralInfo($general2);
            $target['general_3'] = Pk::formatGeneralInfo($general3);
            $target['buff']      = array_map('intval', $Pk->getPlayerPkBuff($targetServerId, $targetPlayerId));
        }
        $pkUpdateData['general_info'] = q(base64_encode(gzcompress(json_encode([
            'general_1'=>$me['general_1'],
            'general_2'=>$me['general_2'],
            'general_3'=>$me['general_3'],
        ]))));
        $pkUpdateData['target_general_info'] = q(base64_encode(gzcompress(json_encode([
            'general_1'=>$target['general_1'],
            'general_2'=>$target['general_2'],
            'general_3'=>$target['general_3'],
        ]))));
        $Pk->alter($data['pk_id'], $pkUpdateData);
        $data['me']          = $me;
        $data['target']      = $target;

        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            //恢复数据
            /*if(isset($freeTimesFlag)) {//免费次数
                $PkPlayerInfo->alter($serverId, $playerId, ['free_search_times_per_day'=>"free_search_times_per_day+1"]);
            }
            if(isset($buyTimesFlag)) {//元宝
                $PkPlayerInfo->alter($serverId, $playerId, ['current_day_buy_times'=>"GREATEST(current_day_buy_times-1, 0)"]);
                $cost = $Cost->getCostByCount($costId, $buyTimes+1);
                if($cost['cost_type']==7) {//元宝退还
                    (new Player)->updateGem($playerId, $cost['cost_num'], true, ['memo'=>"武斗退还:".$cost['desc1']]);
                }
            }*/
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 复仇匹配数据
     *
     * ```php
     * pk/revenge
     * postData: {pk_id}
     * return:{}
     */
    public function revengeAction(){
        exit('本次更新关闭复仇接口');
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定

        $PkPlayerInfo  = new PkPlayerInfo;
        $Pk            = new Pk;
        $PlayerGeneral = new PlayerGeneral;
        $PkGroup       = new PkGroup;
        $DuelInitdata  = new DuelInitdata;

        $postData = getPost();
        $pkId     = $postData['pk_id'];

        $pkBefore       = $Pk->getById($pkId);
        $targetServerId = $pkBefore['server_id'];
        $targetPlayerId = $pkBefore['player_id'];

        $data             = [];
        $pkPlayerInfo     = $PkPlayerInfo->getBasicInfo($serverId, $playerId);
        $targetPlayerInfo = $PkPlayerInfo->getBasicInfo($targetServerId, $targetPlayerId);
        //赛季判断
        $pkgroup   = $PkGroup->getPkGroupByServerId($serverId);
        $initData  = $DuelInitdata->get();
        $closeTime = intval($initData['duel_close_time']);
        if(is_null($pkgroup)) {
            $errCode = 10544;//[武斗匹配]武斗暂未开始
            goto sendErr;
        }
        $crst = $pkgroup['current_round_start_time'];
        $nrst = $pkgroup['next_round_start_time'];
        if(time()>strtotime($nrst) || time()<strtotime($crst)) {
            $errCode = 10545;//[武斗匹配]武斗暂未开始
            goto sendErr;
        }
        if(time()>(strtotime($nrst)-$closeTime*3600) && time()<strtotime($nrst)){
            $errCode = 10546;//[武斗匹配]赛季结算期间不能匹配
            goto sendErr;
        }

        $lastPk = $Pk->getLastPk($serverId, $playerId);
        if(!empty($lastPk)){
            $errCode = 10547;//[武斗匹配]前一场匹配武斗尚未结束
            goto sendErr;
        }
        if(!$pkPlayerInfo['general_1'] || !$pkPlayerInfo['general_2'] || !$pkPlayerInfo['general_3']) {
            $errCode = 10548;//[武斗复仇]武将不能留空
            goto sendErr;
        }
        //case 3 生成pk
        $Pk->alter($pkId, ['revenge_status'=>Pk::REVENGE_STATUS_OFF]);//设置为已经复仇过
        $pk = $Pk->addNew($serverId, $playerId, $targetServerId, $targetPlayerId, true);
        if(is_int($pk) && $pk == -1) {
            $errCode = 10061;//程序异常
            goto sendErr;
        }
        $data['pk_id'] = $pk['id'];
        //组装前端数据
        //己方
        $meGeneral           = $PlayerGeneral->getByPlayerId($playerId, true);
        $meGeneral           = Set::combine($meGeneral, '{n}.general_id', '{n}');
        $me                  = keepFields($pkPlayerInfo, ['server_id', 'player_id', 'score', 'duel_rank_id'], true);
        $me['nick']          = $player['nick'];
        $me['general_1']     = Pk::formatGeneralInfo($meGeneral[$pkPlayerInfo['general_1']]);
        $me['general_2']     = Pk::formatGeneralInfo($meGeneral[$pkPlayerInfo['general_2']]);
        $me['general_3']     = Pk::formatGeneralInfo($meGeneral[$pkPlayerInfo['general_3']]);
        //对方
        $target         = keepFields($targetPlayerInfo, ['server_id', 'player_id', 'score', 'duel_rank_id'], true);
        $target['nick'] = $pk['target_nick'];
        $general1       = $PlayerGeneral->getPkGeneralBasicInfo($targetServerId, $targetPlayerId, $targetPlayerInfo['general_1'])['PlayerGeneral'];
        $general2       = $PlayerGeneral->getPkGeneralBasicInfo($targetServerId, $targetPlayerId, $targetPlayerInfo['general_2'])['PlayerGeneral'];
        $general3       = $PlayerGeneral->getPkGeneralBasicInfo($targetServerId, $targetPlayerId, $targetPlayerInfo['general_3'])['PlayerGeneral'];

        $target['general_1'] = Pk::formatGeneralInfo($general1);
        $target['general_2'] = Pk::formatGeneralInfo($general2);
        $target['general_3'] = Pk::formatGeneralInfo($general3);

        $data['me']          = $me;
        $data['target']      = $target;

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
     * 领取武斗每日奖励
     * ```php
     * pk/getDailyAward
     * postData: {}
     * return:{}
     * ```
     */
    public function getDailyAwardAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定

        $PkPlayerInfo = new PkPlayerInfo;
        $DuelRank     = new DuelRank;

        $ppi              = $PkPlayerInfo->getBasicInfo($serverId, $playerId);
        $dailyAwardStatus = intval($ppi['daily_award_status']);
        $dailyScore       = intval($ppi['daily_score']);
        if($dailyScore==-1){
            $errCode = 10552;//[武斗每日结算奖励]第一天
            goto sendErr;
        }

        if($dailyAwardStatus==PkPlayerInfo::DAILY_AWARD_STATUS_CAN_GET) {//可以领
            $currentDuelRank = $DuelRank->getOneByScore($dailyScore);
            if($currentDuelRank) {//领奖
                if($PkPlayerInfo->alter($serverId, $playerId, [
                    'daily_award_status'    => PkPlayerInfo::DAILY_AWARD_STATUS_GAINED,
                    'gain_daily_award_date' => qd(),
                ], ['daily_award_status'=>PkPlayerInfo::DAILY_AWARD_STATUS_CAN_GET])) {
                    $dropIds = parseArray($currentDuelRank['daily_drop'], true);
                    (new Drop)->gain($playerId, $dropIds, 1, "武斗每日奖励:daily_score={$dailyScore}");
                }
            } else {
                $errCode = 10549;//[武斗每日结算奖励]积分不够领奖
                goto sendErr;
            }
        } elseif($dailyAwardStatus==PkPlayerInfo::DAILY_AWARD_STATUS_GAINED) {
            $errCode = 10550;//[武斗每日结算奖励]已经领过奖
            goto sendErr;
        } elseif($dailyAwardStatus==PkPlayerInfo::DAILY_AWARD_STATUS_SHELL_RUNNING) {
            $errCode = 10551;//[武斗每日结算奖励]结算期间
            goto sendErr;
        }
        Cache::unlock($lockKey);
        $data = [];
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 武斗参与奖励领取
     *
     * ```php
     *  pk/getTimesBonus
     *  postData: {}
     *  return: {}
     * ```
     */
    public function getTimesBonusAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定

        $PkPlayerInfo   = new PkPlayerInfo;
        $DuelTimesBonus = new DuelTimesBonus;

        $ppi           = $PkPlayerInfo->getBasicInfo($serverId, $playerId);
        $times         = $ppi['current_day_match_times'];
        $currentGainId = $ppi['current_day_gain_id'];
        $timesAward    = $DuelTimesBonus->getOneByTimes($times, $currentGainId);
        if ($timesAward) {
            $dropIds = parseArray($timesAward['drops'], true);
            (new Drop)->gain($playerId, $dropIds, 1, "武斗次数奖励times={$timesAward['times']}");
            $PkPlayerInfo->alter($serverId, $playerId, ['current_day_gain_id' => $timesAward['id']], ['current_day_gain_id' => $currentGainId]);
        } else {
            $errCode = 10553;//[武斗参与奖励]次数不够
            goto sendErr;
        }
        Cache::unlock($lockKey);
        $data = [];
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 保存武斗结果,计算积分等
     * ```php
     * pk/pkResult
     * win_player_id 0: 平 999:胜利方的player_id
     *  general_n_is_win 0:负 1：胜 2：平
     * postData: {"pk_id":999,"pk_result":"33242432"} #
     *   { win_player_id = 0,
     *     self_general_result = {  general_1_is_win = 0,general_2_is_win = 0,general_3_is_win = 0} ,
     *     target_general_result = {  general_1_is_win = 0,general_2_is_win = 0,general_3_is_win = 0} }
 * return:{}
     * ```
     */
    public function pkResultAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定
        //post数据
        if($this->innerCallFlag) {
            $postData = $this->passedArgs['postData'];
        } else {
            $postData = getPost();
        }
        $pkId                = $postData['pk_id'];
        $pkResult            = $postData['pk_result'];
        $winPlayerId         = $postData['win_player_id'];//胜利方id
        $selfGeneralResult   = $postData['self_general_result'];
        $targetGeneralResult = $postData['target_general_result'];
        //初始化类
        $Pk                 = new Pk;
        $PkPlayerInfo       = new PkPlayerInfo;
        $PkPlayerGeneral    = new PkPlayerGeneral;
        $PkGeneralStatistic = new PkGeneralStatistic;
        $DuelRank           = new DuelRank;
        $DuelRobot          = new DuelRobot;
        $DuelInitdata       = new DuelInitdata;

        $lastPk = $Pk->getLastPk($serverId, $playerId);
        if(!$this->innerCallFlag && (empty($lastPk) || $lastPk['id']!=$pkId)){
            $errCode = 10061;//程序异常
            goto sendErr;
        }
        $pk       = $Pk->getById($pkId);
        $initData = $DuelInitdata->get();
        if($pk) {
            $isDrawPk        = $winPlayerId == 0;//是否平手
            $isWin           = $winPlayerId == $playerId;//当前玩家是否胜利
            $isTypePlayer    = $pk['type'] == Pk::TYPE_PLAYER;//对战目标是否为玩家
            $dropIds         = [];

            $selfPpi         = $PkPlayerInfo->getBasicInfo($serverId, $playerId);
            $currentDuelRank = $DuelRank->getOneByScore($selfPpi['score']);
            $canGetAwardFlag = $currentDuelRank ? true : false;
            //预判
            if($isTypePlayer) {
                $targetPpi = $PkPlayerInfo->getBasicInfo($pk['target_server_id'], $pk['target_player_id']);
            }
            if($canGetAwardFlag) {
                $dropIds = $isWin ? parseArray($currentDuelRank['win_drop'], true) : parseArray($currentDuelRank['lose_drop'], true);
            }
            if($isDrawPk) {
                $scoreA  = 0;
                goto saveDataSegment;
            }
            if($pk['type']==Pk::TYPE_NPC) {//对阵npc
                $robot  = $DuelRobot->getByCount($selfPpi['pk_with_npc_times']);
                $scoreA = intval($robot['score']);
                if($scoreA>0) {
                    $PkPlayerInfo->alter($serverId, $playerId, ['score'=>"score+{$scoreA}"]);
                }
                goto saveDataSegment;
            }
            if($isTypePlayer) {//玩家
                {//积分规则
                    $Ra            = $selfPpi['score'];
                    $Rb            = $targetPpi['score'];
                    $Sa            = $isWin ? 1 : 0;
                    $Sb            = $isWin ? 0 : 1;
                    $Ea            = 1 / (1 + pow(10, ($Rb - $Ra) / 400));
                    $Eb            = 1 / (1 + pow(10, ($Ra - $Rb) / 400));
                    $protect_score = $initData['protect_score'];
                    $Kbase         = $initData['base_rank_point'];
                    if ($Sa == 1) {//$Sb==0
                        $R_a = $Kbase * ($Sa - $Ea);
                        $R_b = $Kbase * min(1, ($Rb / $protect_score)) * ($Sb - $Eb);
                    } elseif ($Sa == 0) {//$Sb==1
                        $R_a = $Kbase * min(1, ($Ra / $protect_score)) * ($Sa - $Ea);
                        $R_b = $Kbase * ($Sb - $Eb);
                    }
                    $scoreA = round($R_a);//四舍五入 取整
                    $scoreB = round($R_b);//四舍五入 取整
                }
                $scoreA1 = $selfPpi['score']+$scoreA;
                $scoreB1 = $targetPpi['score']+$scoreB;
                //限制个最低分
                if($scoreA1<1) $scoreA1 = 1;
                if($scoreB1<1) $scoreB1 = 1;
                //限制个最高分
                if($scoreA1>4000) $scoreA1 = 4000;
                if($scoreB1>4000) $scoreB1 = 4000;
                $nextDuelRankA = $DuelRank->getOneByScore($scoreA1);
                if($nextDuelRankA) {
                    $ppiUpDataA = [
                        'duel_rank_id' => $nextDuelRankA['id'],
                        'duel_rank'    => $nextDuelRankA['rank'],
                    ];
                }
                $ppiUpDataA['score'] = "GREATEST(score+{$scoreA}, 0)";
                $nextDuelRankB = $DuelRank->getOneByScore($scoreB1);
                if($nextDuelRankB) {
                    $ppiUpDataB = [
                        'duel_rank_id' => $nextDuelRankB['id'],
                        'duel_rank'    => $nextDuelRankB['rank'],
                    ];
                }
                $ppiUpDataB['lock_status'] = PkPlayerInfo::LOCK_STATUS_OFF;
                $ppiUpDataB['score']       = "GREATEST(score+{$scoreB}, 0)";
                if($isWin) {//A赢
                    $ppiUpDataA['win_times']          = 'win_times+1';
                    $ppiUpDataA['continue_win_times'] = 'continue_win_times+1';
                    $ppiUpDataB['continue_win_times'] = 0;
                } else {//A输
                    $ppiUpDataA['continue_win_times'] = 0;
                    $ppiUpDataB['win_times']          = 'win_times+1';
                    $ppiUpDataB['continue_win_times'] = 'continue_win_times+1';
                }
                $PkPlayerInfo->alter($serverId, $playerId, $ppiUpDataA);
                $PkPlayerInfo->alter($targetPpi['server_id'], $targetPpi['player_id'], $ppiUpDataB);
                //长连接跨服推送
                if($scoreB!=0) {
                    $crossData['Type'] = 'cross_pk_result';
                    $crossData['Data'] = ['playerId' => $targetPpi['player_id']];
                    crossSocketSend($targetPpi['server_id'], $crossData);
                }
            }
            //保存数据
            saveDataSegment:
            if($pk['status']==Pk::STATUS_START) {
                if($isTypePlayer) {//武将胜负
                    $selfGeneralInfo = json_decode(gzuncompress(base64_decode($pk['general_info'])), true);
                    $targetGeneralInfo = json_decode(gzuncompress(base64_decode($pk['target_general_info'])), true);
                    foreach ([1, 2, 3] as $v) {
                        //自己方武将
                        if($selfGeneralResult["general_{$v}_is_win"]!=2) {
                            $selfUpdateData = ($selfGeneralResult["general_{$v}_is_win"] == 1) ? ['win_times' => 1] : ['lose_times' => 1];
                            if(isset($selfGeneralInfo["general_{$v}"]['general_id'])) {
                                $gid = $selfGeneralInfo["general_{$v}"]['general_id'];
                                $PkPlayerGeneral->saveData($serverId, $playerId, $gid, $selfUpdateData);
                                $PkGeneralStatistic->saveData($gid, $selfUpdateData);
                            }
                        }
                        //目标方武将
                        if($targetGeneralResult["general_{$v}_is_win"]!=2) {
                            $targetUpdateData = ($targetGeneralResult["general_{$v}_is_win"] == 1) ? ['win_times' => 1] : ['lose_times' => 1];
                            if(isset($targetGeneralInfo["general_{$v}"]['general_id'])) {
                                $tgid = $targetGeneralInfo["general_{$v}"]['general_id'];
                                $PkPlayerGeneral->saveData($pk['target_server_id'], $pk['target_player_id'], $tgid, $targetUpdateData);
                                $PkGeneralStatistic->saveData($tgid, $targetUpdateData);
                            }
                        }
                    }
                }
                //奖励结算
                if ($canGetAwardFlag && !empty($dropIds)) {
                    (new Drop)->gain($playerId, $dropIds, 1, "武斗胜负奖励winPlayerId={$winPlayerId}");
                }
                //武斗回放
                if($pkResult) {
                    $pkResult = q(base64_encode(gzcompress($pkResult)));
                } else {
                    $pkResult = q();
                }
                $pkUpdateData = [
                    'pk_result'           => $pkResult,
                    'status'              => Pk::STATUS_END,
                    'end_time'            => qd(),
                    'win_player_id'       => $winPlayerId,
                ];
                if(isset($scoreA)) {
                    $pkUpdateData['score'] = $scoreA;
                }
                if(isset($scoreB)) {
                    $pkUpdateData['target_score'] = $scoreB;
                }
                $Pk->alter($pkId, $pkUpdateData, ['status' => Pk::STATUS_START]);
                if(QA) {
                    $qaLog = "dropIds:". @json_encode($dropIds) ."
                    ,isWin:".@$isWin."
                    ,Ra=".@$Ra."
                    ,Rb=".@$Rb."
                    ,Sa=".@$Sa."
                    ,Sb=".@$Sb."
                    ,Ea=".@$Ea."
                    ,Eb=".@$Eb."
                    ,protect_score=".@$protect_score."
                    ,Kbase=".@$Kbase."
                    .scoreA=".@$scoreA."
                    ,scoreB=".@$scoreB."
                    ";
                    $Pk->alter($pkId, ['qa_log'=>q($qaLog)]);
                }
            }
            $data = ['drop_id'=>$dropIds, 'score'=>$scoreA];
        }
        Cache::unlock($lockKey);
        if($this->innerCallFlag) return;
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 获取武斗结果，用于回放
     * ```php
     * pk/getPkResult
     * postData: {"id":999}
     * return:{}
     * ```
     */
    public function getPkResultAction() {
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定

        $postData          = getPost();
        $id                = $postData['id'];
        $Pk                = new Pk;
        $pk                = $Pk->getById($id);
        $data['pk_result'] = '';
        if($pk['status']==Pk::STATUS_END && $pk['pk_result']) {
            $data['pk_result'] = gzuncompress(base64_decode($pk['pk_result']));
        }
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 获取武斗结果列表
     *
     * ```php
     * pk/getPkList
     * postData: {}
     * return:{}
     * ```
     */
    public function getPkListAction() {
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定
        $Pk   = new Pk;
        $data = $Pk->getLastList($serverId, $playerId);
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 获取排行榜数据
     *
     * ```php
     * pk/pkRankList
     * postData: {}
     * return:{}
     */
    public function pkRankListAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定
        $PkRank  = new PkRank;
        $allRank = $PkRank->getAllRank($serverId);
        $data    = $allRank;
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 同步prev_duel_rank_id 和duel_rank_id的值
     *  pk/syncDuelRankId
     *  postData: {}
     *  return: {}
     */
    public function syncDuelRankIdAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定
        (new PkPlayerInfo)->alter($serverId, $playerId, ['prev_duel_rank_id'=>'duel_rank_id']);
        Cache::unlock($lockKey);
        echo $this->data->send();
        exit;
    }

    /**
     * 盟友匹配
     *
     * ```php
     *  pk/getGuildPlayerGeneralInfo
     *  postData: {'target_player_id':999}
     *  return: {...}
     * ```
     */
    public function getGuildPlayerGeneralInfoAction(){
        $player   = $this->currentPlayer;
        $playerId = $player['id'];
        $serverId = $player['server_id'];
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':server_id='.$serverId;
        Cache::lock($lockKey);//锁定

        $postData       = getPost();
        $targetPlayerId = $postData['target_player_id'];
        $targetPlayer   = (new Player)->getByPlayerId($targetPlayerId);

        if($targetPlayer['guild_id'] && $targetPlayer['guild_id']!=$player['guild_id']) {
            $errCode = 10560;//[盟友匹配]不是同阵营玩家
            goto sendErr;
        }
        $PkPlayerInfo  = new PkPlayerInfo;
        $PlayerGeneral = new PlayerGeneral;

        $mePkPlayerInfo = $PkPlayerInfo->getBasicInfo($serverId, $playerId);

        if(!$mePkPlayerInfo['general_1'] || !$mePkPlayerInfo['general_2'] || !$mePkPlayerInfo['general_3']) {
            $errCode = 10561;//[盟友匹配]自己武将不能留空
            goto sendErr;
        }
        $matchResult = PkPlayerInfo::findFirst(['server_id=:serverId: and player_id=:playerId: and general_1<>0 and general_2<>0 and general_3<>0','bind'=>['serverId'=>$serverId, 'playerId'=>$targetPlayerId]]);
        if($matchResult) {
            $matchResult = $PkPlayerInfo->getBasicInfo($serverId, $targetPlayerId);
        } else {
            $errCode = 10562;//[盟友匹配]盟友武将有留空
            goto sendErr;
        }
        //self
        $meGeneral           = $PlayerGeneral->getByPlayerId($playerId, true);
        $meGeneral           = Set::combine($meGeneral, '{n}.general_id', '{n}');
        $me                  = keepFields($mePkPlayerInfo, ['server_id', 'player_id', 'score', 'duel_rank_id'], true);
        $me['nick']          = $player['nick'];
        $me['general_1']     = Pk::formatGeneralInfo($meGeneral[$mePkPlayerInfo['general_1']]);
        $me['general_2']     = Pk::formatGeneralInfo($meGeneral[$mePkPlayerInfo['general_2']]);
        $me['general_3']     = Pk::formatGeneralInfo($meGeneral[$mePkPlayerInfo['general_3']]);
        $me['buff']          = array_map('intval', (new Pk)->getPlayerPkBuff($serverId, $playerId));
        //alliance
        $targetGeneral       = $PlayerGeneral->getByPlayerId($targetPlayerId, true);
        $targetGeneral       = Set::combine($targetGeneral, '{n}.general_id', '{n}');
        $target              = keepFields($matchResult, ['server_id', 'player_id', 'score', 'duel_rank_id'], true);
        $target['nick']      = $targetPlayer['nick'];
        $target['general_1'] = Pk::formatGeneralInfo($targetGeneral[$matchResult['general_1']]);
        $target['general_2'] = Pk::formatGeneralInfo($targetGeneral[$matchResult['general_2']]);
        $target['general_3'] = Pk::formatGeneralInfo($targetGeneral[$matchResult['general_3']]);
        $target['buff']      = array_map('intval', (new Pk)->getPlayerPkBuff($serverId, $targetPlayerId));

        Cache::unlock($lockKey);
        $data['is_npc'] = 1;
        $data['pk_id']  = -1;
        $data['me']     = $me;
        $data['target'] = $target;
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
}

