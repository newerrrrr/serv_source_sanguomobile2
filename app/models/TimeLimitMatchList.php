<?php
/**
 * 限时比赛 字典表
 */
class TimeLimitMatchList extends ModelBase{
    public $passArgs = [];//传输数据
    public function addNew($data){
        $self                             = new self;
        $self->time_limit_match_config_id = $data['time_limit_match_config_id'];
        $self->match_type                 = $data['match_type'];
        $self->match_date_start           = $data['match_date_start'];
        $self->match_date_end             = $data['match_date_end'];
        $self->time_limit_match_id        = $data['time_limit_match_id'];
        $self->save();
        // $re = self::findFirst($id)->toArray();

        $this->clearTimeLimitMatchCache();
    }
    /**
     * 获得当前轮的限时比赛
     * @return array 
     */
    public function getCurrentRound(){
        $config = (new TimeLimitMatchConfig)->getCurrentRound();
        if($config) {
            $configId = $config['id'];
            $re = self::find(["time_limit_match_config_id={$configId}", 'order'=>'id asc'])->toArray();
            if($re) {
                $this->setTimeLimitMatchCache($re);
                $r = $this->adapter($re);
                return $r;
            }
        }
        return null;
    }
    /**
     * 获取今天比赛listId
     * @return int 
     */
    public function getTodayListId(){
        $re = $this->getTodayMatch();
        if($re) {
            return $re['id'];
        }
        return null;
    }
    /**
     * 获取前一天比赛的listId
     * @return int 
     */
    public function getPrevDayListId($forAwardFlag=true){
        $matchDateEnd = date('Y-m-d 00:00:00');
        $re = self::findFirst(["match_date_end<=:matchDateEnd:", 'bind'=>['matchDateEnd'=>$matchDateEnd], 'order'=>'id desc']);
        if($re) {
            if($forAwardFlag && $re->award_status==0) {
                return $re->id;
            } 
            if(!$forAwardFlag) {
                return $re->id;
            }
        }
        return false;
    }
    /**
     * 获得当前轮的,满足条件的 今天的，限时比赛
     * @return array 
     */
    public function getTodayMatch(){
        //date_default_timezone_set('Asia/ShangHai');
        $configId = (new TimeLimitMatchConfig)->getCurrentRoundId();
        if(!$configId) return false;
        $today = date('Y-m-d H:i:s');
        $re = self::findFirst("time_limit_match_config_id={$configId} and match_date_start<='{$today}' and match_date_end>= '{$today}'");
        if($re) {
            $r = $this->adapter($re->toArray(), true);
            return $r;
        } else {#杀人比赛特殊逻辑
            $tlmc      = TimeLimitMatchConfig::findFirst($configId);
            $endTime   = strtotime($tlmc->end_time);
            $startTime = strtotime($tlmc->start_time);
            if($tlmc && $startTime<time() && time()<$endTime) {//说明当前轮还没结束
                $newest = self::findFirst(["time_limit_match_config_id={$configId} and match_date_end<'{$today}'", 'order'=>'id desc']);
                if(!$newest || date('Y-m-d')!=substr($newest->match_date_start, 0, strlen('0000-00-00'))) {
                    $newest = self::findFirst(["time_limit_match_config_id={$configId} and match_date_start>'{$today}'", 'order'=>'id asc']);
                }
                if($newest && in_array($newest->match_type, [9,10,11])) {
                    $this->passArgs['next_match_time'] = strtotime($newest->match_date_start);
                    $this->passArgs['today_match'] = $this->adapter($newest->toArray(), true);
                    return -1;
                }
            }
        }
        return false;
    }
    /**
     * 写cache
     * @param [type] $data [description]
     */
    public function setTimeLimitMatchCache($data){
        Cache::db(CACHEDB_STATIC)->set(__CLASS__, $data);
    }
    /**
     * 清除cache
     */
    public function clearTimeLimitMatchCache(){
        Cache::db(CACHEDB_STATIC)->del(__CLASS__);
    }
}