<?php
/**
 * 更新限时比赛列表
 * php cli.php time_limit_match_list start [force]
 */
class TimeLimitMatchListTask extends \Phalcon\CLI\Task {
    public function mainAction(){
    }

    /**
     * 强制今天开始比赛
     */
    public function forceStartAction(){
        log4task('删除前场比赛');
        $TimeLimitMatchConfig = new TimeLimitMatchConfig;
        $Configure            = new Configure;
        $TimeLimitMatchList   = new TimeLimitMatchList;

        $time                 = strtotime("-20 day");
        $today                = date('Y-m-d 00:00:00');

        $sql1                 = "update configure set `value`= {$time} where `key`='last_time_limit_match_date';";
        $sql2                 = "UPDATE time_limit_match_config SET status=1;";
        $sql3                 = "delete from time_limit_match_list where match_date_end>='{$today}'";

        $Configure->sqlExec($sql1);
        $TimeLimitMatchConfig->sqlExec($sql2);
        $TimeLimitMatchList->sqlExec($sql3);
        $TimeLimitMatchList->clearTimeLimitMatchCache();
        log4task('生成当前场比赛');
        $this->startAction();
    }
    /**
     *
     * @param  array $args 传入任何值强制生成新数据
     * @return [type]       [description]
     */
    public function startAction($args=null){
        global $config;

        $d               = (new Starting)->getValueByKey('activity_service_time');//开服后第d天开始
        $serverStartTime = strtotime($config->server_start_time) + ($d-1)*24*60*60;//开服后第五天开始

        $TimeLimitMatch       = new TimeLimitMatch;
        $TimeLimitMatchConfig = new TimeLimitMatchConfig;
        $TimeLimitMatchList   = new TimeLimitMatchList;
        // $ActivityConfigure    = new ActivityConfigure;
        $Configure            = new Configure;

        if($TimeLimitMatchConfig::findFirst('status=0')) {
            exit(qd()."限时比赛脚本中断，排行榜脚本未运行！\n");
        }

        //是否到期再执行
        $today = date('Y-m-d 00:00:00');
        $re = TimeLimitMatchList::find("match_date_end>='{$today}'")->toArray();
        if($re) {
            if(is_null($args)) {
                echo qd()."[INFO]Already Exists!!\n";
                exit;
            }
        }

        //判断限时比赛时间
        $c = Configure::findFirst("key='last_time_limit_match_date'");
        $firstFlag = false;
        if(!$c) {//不存在，第一次执行限时比赛
            $firstFlag = true;
            $Configure->key = 'last_time_limit_match_date';
            $Configure->value = strtotime(date('Y-m-d 00:00:00', $serverStartTime));
            $Configure->save();
        } else {//判断时间是否过了14天
            if((strtotime(date("Y-m-d 00:00:00")) - $c->value)>=14*24*60*60) {//超过14天
                $c->value = strtotime(date('Y-m-d 00:00:00'));
                $c->update();
            } else {
                $errStr = qd().'限时比赛脚本中断，时间未到！前次限时比赛的时间为：'.date('Y-m-d H:i:s', $c->value) . PHP_EOL;
                exit($errStr);
            }
        }

        //新建配置表
        $timeLimitMatchConfigId = $TimeLimitMatchConfig->addNew();
        //生成list表
        $timeLimitMatch = $TimeLimitMatch->dicGetAll();
        $timeLimitMatch = Set::sort($timeLimitMatch, '{n}.id', 'asc');
        $d = 0;
        if($firstFlag) {
            $TimeLimitMatchConfig->updateAll(['start_time'=>q(date('Y-m-d 00:00:00', $serverStartTime))], ['id'=>$timeLimitMatchConfigId]);
        } else {
            $TimeLimitMatchConfig->updateAll(['start_time'=>q(date('Y-m-d 00:00:00'))], ['id'=>$timeLimitMatchConfigId]);
        }

        if($firstFlag) {
            $startTime = $serverStartTime;
        } else {
            $startTime = time();
        }
        $langArr = [
            1=>373001,
            2=>373002,
            3=>373003,
            4=>373004,
            5=>373005,
            6=>373006,
            7=>373007,
            8=>373008,
            9=>373009,
            10=>373010,
            11=>373011,
        ];
        foreach($timeLimitMatch as $v) {
            $matchTypeArr = parseArray($v['match_type']);
            $matchType = $matchTypeArr[array_rand($matchTypeArr)];
            if(in_array($matchType, [9,10,11])) {//杀人活动
                $killFlag = true;
            } else {
                $killFlag = false;
            }
            $data['time_limit_match_config_id'] = $timeLimitMatchConfigId;
            $data['match_type']                 = $matchType;
            $data['time_limit_match_id']        = $v['id'];
            if($killFlag) {
                $data['match_date_start'] = date('Y-m-d 13:00:00', $startTime + $d * 24 * 60 * 60);
            } else {
                $data['match_date_start'] = date('Y-m-d 00:00:00', $startTime + $d * 24 * 60 * 60);
            }
            $d0 = $d;
            $d  = $d + $v['time'];//结束时间 以及下个限时比赛的开始时间
            if($killFlag) {
                $data['match_date_end'] = date('Y-m-d 22:00:00', $startTime + $d0 * 24 * 60 * 60);
            } else {
                $data['match_date_end'] = date('Y-m-d 00:00:00', $startTime + $d * 24 * 60 * 60);
            }
            $TimeLimitMatchList->addNew($data);
            $pushId = (new PlayerPush)->add(0, 3, 400008, ['activityname'=>$langArr[$matchType]], '', $data['match_date_start']);
            echo ".";
            // usleep(100000);//0.1 second
        }
        $TimeLimitMatchConfig->updateAll(['end_time'=>q(date('Y-m-d 00:00:00', $startTime+$d*24*60*60))], ['id'=>$timeLimitMatchConfigId]);

        echo qd()."ok\n";
    }

    public function rankRewardAction($args=null){
        //begin
        $db = $this->di['db'];
        dbBegin($db);

        $TimeLimitMatchList = new TimeLimitMatchList;
        //查找阶段排名id
        $listId = $TimeLimitMatchList->getPrevDayListId(); #获取前一天的listId，没有或者已经领过奖则返回false
        var_dump($listId);
        if($listId){
            echo qd()."限时阶段排名开始发奖[listId=$listId]》》\r\n";
            //获取matchType
            $tlml = $TimeLimitMatchList->findFirst($listId);
            if(!$tlml){
                echo qd()."找不到记录listId=".$listId."\r\n";
                exit;
            }
            $matchType = $tlml->match_type;
            //var_dump($tlml->toArray());

            //获取rank drop
            $TimeLimitMatch = new TimeLimitMatch;
            $tlm = $TimeLimitMatch->findFirst(['match_type='.$matchType]);
            if(!$tlm){
                echo qd()."找不到记录TimeLimitMatch:type=".$matchType."\r\n";
                exit;
            }
            $tlm = $tlm->toArray();
            $tlm = $TimeLimitMatch->parseColumn($tlm);
            $pointDropIds = $tlm['rank_drop_id'];
            //var_dump($pointDropIds);

            //获取发奖的最大排名数
            $TimeLimitMatchPointDrop = new TimeLimitMatchPointDrop;
            $pointDrop = $TimeLimitMatchPointDrop->find(['type=2 and id in ('.join(',', $pointDropIds).')', 'order'=>'min_point'])->toArray();
            $maxScore = 0;
            foreach($pointDrop as $_pd){
                $maxScore = max($maxScore, $_pd['max_point']);
            }
            //var_dump($maxScore);

            //获取前n排名玩家
            $PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
            $matches = $PlayerTimeLimitMatch->find(['time_limit_match_list_id='.$listId.' and score > 0', 'order'=>'score desc,update_time asc', 'limit'=>$maxScore])->toArray();
            //var_dump($matches);

            //获取前三信息
            $j = 0;
            $top3 = [];
            $Player = new Player;
            $Guild = new Guild;
            while($j < 3){
                if(!isset($matches[$j])) break;
                $_player = $Player->getByPlayerId($matches[$j]['player_id']);
                if($_player['guild_id']){
                    $_guild = $Guild->getGuildInfo($_player['guild_id']);
                    $guildShort = $_guild['short_name'];
                }else{
                    $guildShort = '';
                }
                $top3[] = ['player_id'=>$_player['id'], 'nick'=>$_player['nick'], 'guild_short'=>$guildShort];
                $j++;
            }

            //循环玩家
            $pdi = 0;
            $PlayerMail = new PlayerMail;
            $Drop = new Drop;
            foreach($matches as $_i => $_v){
                //发奖档次
                $rank = $_i+1;
                while($rank > $pointDrop[$pdi]['max_point']*1){
                    $pdi++;
                    if(!isset($pointDrop[$pdi])){
                        break 2;
                    }
                }

                $drop = $Drop->rand($_v['player_id'], [$pointDrop[$pdi]['drop']]);
                if(!$drop){
                    echo qd()."生成掉落失败，playerId=".$_v['player_id'].";dropid=".$pointDrop[$pdi]['drop']."\r\n";
                    exit;
                }
                $item = [];
                foreach($drop as $_d){
                    $item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
                }

                //发送发奖邮件
                if(!$PlayerMail->sendSystem($_v['player_id'], PlayerMail::TYPE_LIMITRANKGIFT, '', '', 0, ['rank'=>$rank, 'top3'=>$top3], $item)){
                    echo qd()."发送邮件失败,playerId".$_v['player_id']."\r\n";
                    exit;
                }
            }

            //更新list表 status
            if(!$TimeLimitMatchList->updateAll(['award_status'=>1], ['id'=>$listId])){
                echo qd()."更新状态失败\r\n";
                exit;
            }


            echo qd()."限时阶段排名发奖成功!\r\n";
        }

        //查找总排名id
        $configId = (new TimeLimitMatchConfig)->getCurrentRoundId(true); #获取本轮比赛的configId，没有或者已经过期则返回false
        //var_dump($configId);
        if($configId){
            echo qd()."限时总排名开始发奖[configId=$configId]》》\r\n";
            //获取发奖的最大排名数
            $TimeLimitMatchPointDrop = new TimeLimitMatchPointDrop;
            $pointDrop = $TimeLimitMatchPointDrop->find(['type=3', 'order'=>'min_point'])->toArray();
            $maxScore = 0;
            foreach($pointDrop as $_pd){
                $maxScore = max($maxScore, $_pd['max_point']);
            }

            //获取前n排名玩家
            $PlayerTimeLimitMatchTotal = new PlayerTimeLimitMatchTotal;
            $matches = $PlayerTimeLimitMatchTotal->find(['time_limit_match_config_id='.$configId.' and score > 0', 'order'=>'score desc,update_time asc', 'limit'=>$maxScore])->toArray();

            //获取前三信息
            $j = 0;
            $top3 = [];
            $Player = new Player;
            $Guild = new Guild;
            while($j < 3){
                if(!isset($matches[$j])) break;
                $_player = $Player->getByPlayerId($matches[$j]['player_id']);
                if($_player['guild_id']){
                    $_guild = $Guild->getGuildInfo($_player['guild_id']);
                    $guildShort = $_guild['short_name'];
                }else{
                    $guildShort = '';
                }
                $top3[] = [/*'player_id'=>$_player['id'], */'nick'=>$_player['nick'], 'guild_short'=>$guildShort];
                $j++;
            }

            $pdi = 0;
            $PlayerMail = new PlayerMail;
            $Drop = new Drop;
            foreach($matches as $_i => $_v){
                //发奖档次
                $rank = $_i+1;
                while($rank > $pointDrop[$pdi]['max_point']*1){
                    $pdi++;
                    if(!isset($pointDrop[$pdi])){
                        break 2;
                    }
                }

                $drop = $Drop->rand($_v['player_id'], [$pointDrop[$pdi]['drop']]);
                if(!$drop){
                    echo qd()."生成掉落失败，playerId=".$_v['player_id'].";dropid=".$pointDrop[$pdi]['drop']."\r\n";
                    exit;
                }
                $item = [];
                foreach($drop as $_d){
                    $item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
                }

                //发送发奖邮件
                if(!$PlayerMail->sendSystem($_v['player_id'], PlayerMail::TYPE_LIMITTOTALRANKGIFT, '', '', 0, ['rank'=>$rank, 'top3'=>$top3], $item)){
                    echo qd()."发送邮件失败,playerId".$_v['player_id']."\r\n";
                    exit;
                }

                //更新排名
                $PlayerTimeLimitMatchTotal->updateAll(['rank'=>$rank], ['id'=>$_v['id']]);
            }

            //更新list表 status
            if(!(new TimeLimitMatchConfig)->updateAll(['status'=>1], ['id'=>$configId])){
                echo qd()."更新状态失败\r\n";
                exit;
            }
            echo qd()."限时总排名发奖成功!\r\n";
        }

        //commit
        dbCommit($db);
		Cache::db()->del('historyTopInfo');
        echo qd().'ok\r\n';
    }
}