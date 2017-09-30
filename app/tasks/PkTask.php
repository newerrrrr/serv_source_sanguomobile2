<?php
/**
 * pk信息初始化生成脚本
 * 
 */
class PkTask extends \Phalcon\CLI\Task
{
    const Max    = 200;
    const Level  = 10;
    const Offset = 1000;

    /**
     * 组里的某一台服务器立即运行一次即可，无需添加到crontab里
     *
     * online
     * 开服初始化生成数据脚本
     *
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk systemInit
     */
    public function systemInitAction(){
        echo "[".date('Y-m-d H:i:s')."]开始.";
        //等级 武将数>3 按id顺序前三个
        $level = self::Level;
        $max   = self::Max;
        $sql   = <<<SQLSTAT
SELECT GROUP_CONCAT(pg.general_id) gids, pg.player_id, p.server_id, p.level FROM player_general pg 
LEFT JOIN player p ON pg.player_id=p.id
WHERE pg.player_id IS NOT NULL AND p.level>{$level} AND p.uuid not like 'Robot%'
GROUP BY pg.player_id HAVING COUNT(*)>3 ORDER BY pg.id asc
LIMIT {$max};
SQLSTAT;
        $re = (new PlayerGeneral)->sqlGet($sql);
        $PkPlayerInfo = new PkPlayerInfo;
        foreach($re as $v) {
            $serverId           = $v['server_id'];
            $playerId           = $v['player_id'];
            $data               = [];
            $generalArr         = explode(',', $v['gids']);
            $data['general_1']  = $generalArr[0];
            $data['general_2']  = $generalArr[1];
            $data['general_3']  = $generalArr[2];
            $PkPlayerInfo->addNew($serverId, $playerId, $data);
            echo ".";
        }
        echo PHP_EOL,"[".date('Y-m-d H:i:s')."]结束",PHP_EOL;
    }

    /**
     * @deprecated
     * qa
     * 武将胜败数据
     * 排行榜模拟数据生成脚本
     *
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk test
     */
    public function testAction(){
        $PkPlayerGeneral    = new PkPlayerGeneral;
        $PkGeneralStatistic = new PkGeneralStatistic;
        $PkPlayerInfo       = new PkPlayerInfo;
        $re                 = PkPlayerInfo::find(["general_1<>0 and general_2<>0 and general_3<>0"])->toArray();
        foreach($re as $v) {
            $a1 = ['win_times'=>mt_rand(100, 999), 'lose_times'=>mt_rand(100, 999)];
            $a2 = ['win_times'=>mt_rand(100, 999), 'lose_times'=>mt_rand(100, 999)];
            $a3 = ['win_times'=>mt_rand(100, 999), 'lose_times'=>mt_rand(100, 999)];
            $PkPlayerGeneral->saveData($v['server_id'], $v['player_id'], $v['general_1'], $a1);
            $PkGeneralStatistic->saveData($v['general_1'], $a1);
            $PkPlayerGeneral->saveData($v['server_id'], $v['player_id'], $v['general_2'], $a2);
            $PkGeneralStatistic->saveData($v['general_1'], $a2);
            $PkPlayerGeneral->saveData($v['server_id'], $v['player_id'], $v['general_3'], $a3);
            $PkGeneralStatistic->saveData($v['general_1'], $a3);

            $DuelRank = new DuelRank;
            $score    = mt_rand(0, 60000);
            $rank     = $DuelRank->getOneByScore($score);
            $PkPlayerInfo->alter($v['server_id'], $v['player_id'], ['score'=>$score, 'duel_rank_id'=>$rank['id'], 'duel_rank'=>$rank['rank']]);
            echo '.';
        }
        echo "\nOK\n";

    }

    /**
     * qa
     * 生成pk记录
     *
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk test1
     *
     * @param array $argv
     */
    public function test1Action($argv=[]) {
        if(empty($argv)) {
            echo '请输入playerId',PHP_EOL;
            exit;
        }
        $playerId = $argv[0];
        echo qd()."player_id={$playerId}的数据生成中-";

        $PkPlayerInfo = new PkPlayerInfo;
        $Pk           = new Pk;
        $Player       = new Player;
        $player       = $Player->getByPlayerId($playerId);
        $serverId     = $player['server_id'];
        foreach(range(1,10) as $v) {
            $matchResult = $PkPlayerInfo->match($playerId);
            if($matchResult) {
                $id = $Pk->addNew($serverId, $playerId, $matchResult['server_id'], $matchResult['player_id']);
                if($id!=-1) {
                    $Pk->updateAll(['status' => 1,'is_win'=>mt_rand(0,1),'score'=>mt_rand(0, 1000)], ['id' => $id]);
                    echo '-';
                }
            } else {
                echo "没有匹配到玩家\n";
            }
        }

        echo "->ok!\n";
    }
    public function tAction(){
        $DuelRank = new DuelRank;
        $DuelRank->getAllRank();
    }

    /**
     * 某一台服务器运行即可
     *
     * 生成排行榜脚本
     * online
     * #生成所有数据
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk rank
     *
     * qa
     * #生成指定duel_rank的排行榜
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk rank 8
     */
    public function rankAction($argv=[]){
        $DuelRank        = new DuelRank;
        $PkPlayerInfo    = new PkPlayerInfo;
        $PkPlayerGeneral = new PkPlayerGeneral;
        $PkRank          = new PkRank;
        $Player          = new Player;
        $PkGroup         = new PkGroup;
        $PlayerGeneral   = new PlayerGeneral;
        $allRank         = $DuelRank->getAllRank();
        if(!empty($argv)) {
            $allRank = [$argv[0]];
        }
        $allGroup = $PkGroup->getAllGroup();
        foreach($allGroup as $g) {
            $gid = intval($g['id']);
            if(empty($g['server_ids'])) {
                log4task("组{$gid}：当前组服务器为空");
                continue;
            }
            $serverIds = implode(',', $g['server_ids']);
            foreach($allRank as $k=>$v) {
                //清空上次排行榜
                log4task("组{$gid}：清空duel_rank={$v}排行榜");
                $PkRank->sqlExec("delete from pk_rank where duel_rank={$v} and pk_group_id={$gid};");

                log4task("组{$gid}：生成duel_rank={$v}的排行榜");
                $sql1 = "SELECT * FROM pk_player_info WHERE duel_rank={$v} AND server_id in ({$serverIds}) ORDER BY score DESC LIMIT 10;";
                $re   = $PkPlayerInfo->sqlGet($sql1);
                if(empty($re)) {
                    log4task("组{$gid}：当前人数为空");
                    continue;
                }
                $pos  = 1;
                foreach ($re as $vv) {
                    log4task("-", false);
                    $tserverId            = $vv['server_id'];
                    $tplayerId            = $vv['player_id'];
                    $rdata                = $Player->getPlayerBasicInfoByServer($tserverId, $tplayerId);
                    $rdata['pos']         = $pos++;
                    $rdata['server_id']   = $tserverId;
                    $rdata['player_id']   = $tplayerId;
                    $rdata['score']       = $vv['score'];
                    $rdata['duel_rank']   = $vv['duel_rank'];
                    $rdata['pk_group_id'] = $gid;

                    $generalData = [];
                    $sql2        = "SELECT * FROM pk_player_general WHERE server_id={$tserverId} AND player_id={$tplayerId} ORDER BY win_times/(win_times+lose_times) DESC limit 3;";
                    $gdata       = $PkPlayerGeneral->sqlGet($sql2);
                    foreach ($gdata as $v) {
                        log4task("-", false);
                        $pgInfo = $PlayerGeneral->getPkGeneralBasicInfo($tserverId, $tplayerId, $v['general_id']);
                        if ($pgInfo) {
                            $info1         = keepFields($pgInfo['PlayerGeneral'], ['general_id', 'lv', 'weapon_id', 'armor_id', 'horse_id', 'zuoji_id'], true);
                            $info1         = array_map('intval', $info1);
                            $generalData[] = $info1;
                        }
                    }
                    $rdata['general_data'] = json_encode($generalData, JSON_UNESCAPED_UNICODE);
                    $PkRank->addNew($rdata);
                }
                log4task("\n", false);
                log4task("组{$gid}：排行榜脚本ok!");
            }
        }
    }
    /**
     * 某一台服务器运行即可
     * 每天8点重置脚本
     *  每日免费匹配次数、每日购买次数、每日匹配次数
     * online
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk pkDailyReset
     */
    public function pkDailyResetAction(){
        $DuelInitdata = new DuelInitdata;
        $initData     = $DuelInitdata->get();
        $defaultTimes = intval($initData['default_num']);
        $sql          = "LOCK TABLES pk_player_info WRITE;
        UPDATE pk_player_info SET daily_reset_exec_date=now(),free_search_times_per_day={$defaultTimes},current_day_buy_times=0,current_day_match_times=0,current_day_gain_id=0;
        UNLOCK TABLES;";
        iquery($sql, true, true, 'pk');
        Cache::db('pk_player_info', 'PkPlayerInfo')->flushDB();
        log4task("每日重置脚本ok!");
    }
    /**
     * 某一台服务器运行即可
     *
     * 暂定每天22点执行
     * online
     * 每日积分奖励结算脚本
     *  积分
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk pkDailyAward
     */
    public function pkDailyAwardAction(){
        $now = date('Y-m-d H:i:s');
        $sql = "LOCK TABLES pk_player_info WRITE;
        UPDATE pk_player_info SET daily_award_status=2, award_exec_date='{$now}';
        UPDATE pk_player_info SET daily_score=score;
        UPDATE pk_player_info SET daily_award_status=0;
        UNLOCK TABLES;";
        iquery($sql, true, true, 'pk');
        Cache::db('pk_player_info', 'PkPlayerInfo')->flushDB();
        log4task('奖励结算ok!');
        log4task("\n", false);
        $this->console->handle(['task' => 'pk','action' => 'rank']);//排行榜脚本
    }
    /**
     * 所有服服务器运行
     * pk赛季末结算奖励脚本
     * online
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk pkRoundAward
     */
    public function pkRoundAwardAction(){
        cli_set_process_title('_father_pk_task_round_award');//set process name
        set_time_limit(0);
        global $config;
        $serverId     = intval($config->server_id);
        log4task("server_id={$serverId}开始结算赛季奖励");
        $PkPlayerInfo = new PkPlayerInfo;
        $PkGroup      = new PkGroup;

        $group = $PkGroup->getPkGroupByServerId($serverId);
        if(is_null($group)) {
            log4task("本服server_id={$serverId}没有编组！");
            exit;
        }
        $nextRoundStartTime = strtotime($group['next_round_start_time']);
        if(time()<($nextRoundStartTime-2*60*60) || time()>$nextRoundStartTime) {
            log4task("本赛季尚未结束，请在[".date('Y-m-d H:i:s', $nextRoundStartTime-2*60*60).' - '.$group['next_round_start_time']."]之间内运行此脚本");
            exit;
        }
        if(in_array($serverId, $group['exec_server_ids'])) {
            log4task('本服赛季结算脚本已经执行过！');
            exit;
        }
        $total_number = $PkPlayerInfo::count(["server_id=:serverId: and (score!=0 or general_1!=0 or general_2!=0 or general_3!=0)", 'bind' => ['serverId' => $serverId]]);
        if($total_number > 0) {
            system('/usr/local/php/bin/php ' . APP_PATH . '/app/cli.php pk doPkRoundAward ' . $total_number);
        }
    }
    /**
     * 所有服服务器运行
     * pk赛季末结算奖励脚本
     * interior by pkRoundAward
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk doPkRoundAward
     * @param $params
     */
    public function doPkRoundAwardAction($params){
        cli_set_process_title('_father_pk_task_round_award');//set process name
        set_time_limit(0);
        $total_number = $params[0];
        $offset       = self::Offset;
        $n            = ceil($total_number/$offset);
        log4task("共{$total_number}条数据，每{$offset}一组丢到子进程执行，共有{$n}个子进程将会生成执行。");
        //生成子进程
        for($i=0; $i<$n; $i++) {
            $callback = [new self, 'pkRoundAward0'];
            $process  = new swoole_process($callback, false, true);
            $process->write($i);
            $pid = $process->start();
            log4task("pid={$pid}的进程被创建-邮件发奖");
        }
        //回收子进程
        for($i=0; $i<$n; $i++) {
            $p = swoole_process::wait();
            log4task("pid={$p['pid']}进程被回收");
        }
        unset($process);

        //起另一子进程处理剩余结算
        $process_remain = new swoole_process([new self, 'pkRoundAward1'], false, false);
        $pid_remain     = $process_remain->start();
        log4task("pid={$pid_remain}的进程被创建-结算剩余");

        $p_remain = swoole_process::wait();
        log4task("pid={$p_remain['pid']}进程被回收");
        unset($process_remain);
        log4task("赛季结算ok![{$total_number}条数据]");
    }
    /**
     * 子进程 赛季奖励发邮件
     * @param $worker
     */
    public function pkRoundAward0($worker){
        set_time_limit(0);
        $name = "_sub_pk_task_round_award";
        $worker->name($name);
        $i = $worker->read();

        global $config;
        $serverId     = intval($config->server_id);
        log4task("子进程pid={$worker->pid},第{$i}个子进程，计算开始。。。");
        log4task("子进程pid={$worker->pid},第{$i}个子进程，范围 limit=>[".self::Offset.", ".self::Offset*$i."]");
        $PkPlayerInfo = new PkPlayerInfo;
        $DuelRank     = new DuelRank;
        $PlayerMail   = new PlayerMail;

        $all = $PkPlayerInfo::find(["server_id=:serverId: and (score!=0 or general_1!=0 or general_2!=0 or general_3!=0)", 'bind'=>['serverId'=>$serverId], "order"=>"id asc", "limit"=>[self::Offset, self::Offset*$i]]);
        $j = 1;
        foreach($all as $v) {
            if($v->score==0 && $v->general_1==0 && $v->general_2==0 && $v->general_3==0) continue;//积分为0并且武将没上阵的，不发奖励
            log4task("<{$worker->pid} ". $j++."> ", false);
            $duelRank = $DuelRank->getOneByScore($v->score);
            if($duelRank) {
                $dropId               = $duelRank['drop'];
                $item                 = $PlayerMail->newItemByDrop($v->player_id, [$dropId]);
                $data['duel_rank_id'] = intval($v->duel_rank_id);
                $data['score']        = intval($v->score);
                $PlayerMail->sendSystem($v->player_id, PlayerMail::TYPE_PKROUND_GIFT, '', '', 0, $data, $item, '赛季结算奖励');
                if(QA) {
                    (new PlayerCommonLog)->add($v->player_id, [
                        'type' => '赛季结算奖励',
                        'memo' => "dropId:" . $dropId . ":time=" . date('Y-m-d H:i:s') . ":server_id=" . $v->server_id . ":sysServer_id=" . $serverId . ":duel_rank_id=" . $v->duel_rank_id . ":duel_rank=" . $v->duel_rank . ":score=" . $v->score . ":id=" . $v->id . ""]);
                }

            }
            //初始化
            $PkPlayerInfo->updateAll([
                'duel_rank_id'       => 1,
                'duel_rank'          => 1,
                'score'              => 0,
                'win_times'          => 0,
                'continue_win_times' => 0,
                'update_time'        => qd(),
             ], ['id'=>$v->id]);
        }
        log4task("\n", false);
        $n = $j - 1;
        log4task("子进程pid={$worker->pid},第{$i}个子进程，结算ok![{$n}条数据]");
        $worker->exit(0);
    }
    /**
     * 处理赛季奖励结余，pk_group初始 pk_rank清空等
     *
     * @param $worker
     */
    public function pkRoundAward1($worker){
        log4task("子进程pid={$worker->pid} 开始处理结算剩余操作。。。");
        global $config;
        $serverId = intval($config->server_id);
        $PkGroup  = new PkGroup;
        $now      = date('Y-m-d H:i:s');

        $group = $PkGroup->getPkGroupByServerId($serverId);
        do {
            $affectedRows = $PkGroup->updateAll(['lock_status'=>1], ['id'=>$group['id'], 'lock_status'=>0]);//锁住该条记录
            if($affectedRows>0) {
                $group = $PkGroup->getPkGroupByServerId($serverId);
                break;
            }
            sleep(1);
        } while(true);

        $execServerIds   = $group['exec_server_ids'];
        $execServerIds[] = $serverId;
        $execServerIds   = implode(";", $execServerIds);

        $PkGroup->alter($group['id'], ['exec_server_ids'=>$execServerIds, 'update_log'=>"[赛季结算脚本-{$now}]"]);

        $PkGroup->updateAll(['lock_status'=>0], ['id'=>$group['id']]);//解锁
        Cache::db('pk_player_info', 'PkPlayerInfo')->flushDB();

        log4task("子进程pid={$worker->pid} 清空组pk_group.id=".$group['id']." 排行榜");
        (new PkRank)->sqlExec("delete from pk_rank where pk_group_id=".$group['id']);
        log4task("\n", false);
        $worker->exit(0);
    }
    /**
     * 某一台服服务器运行
     * pk开启新赛季脚本
     * online
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk pkRoundStartNew
     */
    public function pkRoundStartNewAction(){
        set_time_limit(0);
        log4task("下一赛季开启脚本,本脚本需在pkRoundAward跑完之后运行，切记！");
        $PkGroup      = new PkGroup;
        $DuelInitdata = new DuelInitdata;
        $initData     = $DuelInitdata->get();
        $seasonTime   = intval($initData['season_time']);

        $all = $PkGroup->getAllGroup();
        foreach($all as $v) {
            $runFlag = true;
            //检测结算脚本是否运行结束
            foreach($v['server_ids'] as $sid) {
                if(!in_array($sid, $v['exec_server_ids'])) {
                    log4task("id={$sid}服的pkRoundAward赛季结算脚本未执行！");
                    $runFlag = false;
                }
            }
            if($runFlag) {
                $now                               = date('Y-m-d H:i:s');
                $currentNextRoundStartTime         = strtotime($v['next_round_start_time']);
                $newRoundNextStartTime             = date('Y-m-d H:i:s', $currentNextRoundStartTime + $seasonTime * 24 * 60 * 60);
                $udata['current_round_start_time'] = $v['next_round_start_time'];
                $udata['next_round_start_time']    = $newRoundNextStartTime;
                $udata['exec_server_ids']          = '';
                $udata['update_log']               = "新赛季脚本-{$now}";
                $PkGroup->alter($v['id'], $udata);
            }

        }
        log4task("新赛季脚本end!");
    }
    /**
     * 删除旧老数据
     * 某一台服务器运行即可
     * online
     * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php pk deleteOldPk
     */
    public function deleteOldPkAction(){
        set_time_limit(0);
        $now = date('Y-m-d H:i:s', time()-7*24*60*60);
        $sql = "DELETE FROM `pk` WHERE `end_time`<'{$now}' AND `status`=1;";
        iquery($sql, true, false, 'pk');
        log4task("删除旧老数据ok!");
    }


}