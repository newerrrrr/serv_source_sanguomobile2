<?php
class ActivityTask extends \Phalcon\CLI\Task{
    public function mainAction(){

    }

    /**
     * @return [type]       [description]
     */
    public function startAction(){
        $Configure = new Configure;
        $serverStartTime = $Configure->getValueByKey("server_start_time");
        if($serverStartTime+3600*24*4>time()){
            return;
        }

    	$AllianceMatchList = new AllianceMatchList;
    	$ActivityConfigure = new ActivityConfigure;

    	$lastMatch = $ActivityConfigure->getLastActivityByActivityId(1003);
    	if(!empty($lastMatch) && $lastMatch['end_time']>time()){
    		return;
    	}
    	$data1 = array('type'=>1, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24*9), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*10));
    	$AllianceMatchList->addNew($data1);

    	$data3 = array('type'=>3, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24*11), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*11));
        $AllianceMatchList->addNew($data3);

        $data4 = array('type'=>4, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24*12), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*12));
        $AllianceMatchList->addNew($data4);

        $data2 = array('type'=>2, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24*13), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*13));
    	$AllianceMatchList->addNew($data2);

    	$ActivityConfigure->openActivity(1003, date("Y-m-d 00:00:00", time()+3600*24*9), date("Y-m-d 08:00:00", time()+3600*24*9), date("Y-m-d 21:59:59", time()+3600*24*13), []);
    }

    public function mailAllPlayerAction(){
        //判断是否有联盟活动
        $AllianceMatchList = new AllianceMatchList;
        $minT = date("Y-m-d 00:00:00", time()+3600*24);
        $maxT = date("Y-m-d 23:59:59", time()+3600*24);
        $re = $AllianceMatchList->findFirst("start_time>'{$minT}' and start_time<'{$maxT}'");
        if($re){
            $re = $re->toArray();
            $data = ["activity_id"=>1003, "activity_sub_id"=>$re['type']*1];
            $PlayerMail = new PlayerMail;
            $PlayerMail->sendSystem(0, PlayerMail::TYPE_NOTICE_ACTIVITY, "明日活动提示邮件", "", 0, $data);
            log4cli("发送邮件成功");
        }else{
            log4cli("当前无限时比赛");
        }

        //限时比赛 提前通知邮件
        $today = date('Y-m-d 00:00:00');
        $sql = <<<SQL_STAT
SELECT * FROM time_limit_match_list b LEFT JOIN time_limit_match_config a
ON a.id=b.`time_limit_match_config_id`
WHERE a.`status`=0 AND b.match_date_start > '{$today}'
order by b.match_date_start asc
LIMIT 1;
SQL_STAT;
        $TimeLimitMatchList = new TimeLimitMatchList;
        $re                 = $TimeLimitMatchList->sqlGet($sql);
        if($re) {
            $r          = $re[0];
            if(strtotime($r['match_date_start']) - time() < 12*60*60) {//第一场排除判断
                $matchType  = intval($r['match_type']);
                $data       = ["activity_id" => 1002, "activity_sub_id" => $matchType];
                $PlayerMail = new PlayerMail;
                $PlayerMail->sendSystem(0, PlayerMail::TYPE_NOTICE_ACTIVITY, "明日限时比赛活动提示邮件", "", 0, $data);
                log4cli("显示比赛提前邮件发送,match_type={$matchType}");
            }
        } else {
            //查看时间是否在8小时内
            // 第一场 hard code
            $c = Configure::findFirst("key='last_time_limit_match_date'");
            if((time() - $c->value)>=(14*24*60*60-8*60*60)) {//超过14天
                $data       = ["activity_id"=>1002, "activity_sub_id"=>1];
                $PlayerMail = new PlayerMail;
                $PlayerMail->sendSystem(0, PlayerMail::TYPE_NOTICE_ACTIVITY, "明日限时比赛活动提示邮件", "", 0, $data);
                log4cli("第一场，尚未开始的限时比赛，这里写死match_type。显示比赛提前邮件发送,match_type=1");
            } else {
                log4cli("当前无限时比赛");
            }
        }
    }
    public function resetAction(){
        $db = $this->di['db'];
        
        $sql1 = "update player set hsb=0";
        $db->execute($sql1);

        Cache::clearPlayerCache();

        $sql2 = "TRUNCATE TABLE guild_mission_rank";
        $db->execute($sql2);

        $sql3 = "TRUNCATE TABLE alliance_match_list";
        $db->execute($sql3);

        $sql4 = "TRUNCATE TABLE activity_configure";
        $db->execute($sql4);

        $AllianceMatchList = new AllianceMatchList;
        $ActivityConfigure = new ActivityConfigure;

        $data1 = array('type'=>1, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*2));
        $AllianceMatchList->addNew($data1);

        $data3 = array('type'=>3, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24*3), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*3));
        $AllianceMatchList->addNew($data3);

        $data4 = array('type'=>4, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24*4), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*4));
        $AllianceMatchList->addNew($data4);

        $data2 = array('type'=>2, 'start_time'=>date("Y-m-d 08:00:00", time()+3600*24*5), 'end_time'=>date("Y-m-d 21:59:59", time()+3600*24*5));
        $AllianceMatchList->addNew($data2);

        $ActivityConfigure->openActivity(1003, date("Y-m-d 00:00:00", time()+3600*24), date("Y-m-d 08:00:00", time()+3600*24), date("Y-m-d 21:59:59", time()+3600*24*5), []);
    }
}