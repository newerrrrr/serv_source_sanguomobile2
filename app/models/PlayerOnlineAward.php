<?php
/**
 * 玩家在线奖励表
 */
class PlayerOnlineAward extends ModelBase{
    public $blacklist = ['update_time'];
    const AWARD_N = 0;//未领
    const AWARD_Y = 1;//已领
    const AWARD_X = 2;//失效

    private $nextFlag = false;
    /**
     * 生成每日在线的一个周期的记录
     * @param  int $playerId 
     */
    public function generate($playerId){
        $OnlineAward = new OnlineAward;
        $allOnlineAward = $OnlineAward->dicGetAll();
        $allOnlineAward = Set::sort($allOnlineAward, '{n}.id', 'asc');
        $Drop = new Drop;
        $today = date('Y-m-d 00:00:00');
        $k = 1;
        foreach($allOnlineAward as $v) {
            $self = new self;
            $self->player_id = $playerId;
            $self->online_award_id = $v['id'];

            $dropIds = parseArray($v['drop']);
            $self->award_item = json_encode($Drop->rand($playerId, $dropIds));

            $self->online_award_duration = $v['get_time'];
            $self->date_limit = $today; 
            if($k==1) {
                $self->time_start = date('Y-m-d H:i:s');
            }
            $self->update_time = $self->create_time = date('Y-m-d H:i:s');
            $self->save();
            $k++;
        }
        $this->clearDataCache($playerId);
    }
    /**
     * 通过player_id获取在线奖励信息
     * @param  int $playerId
     * @return array          
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $re = Cache::getPlayer($playerId, __CLASS__);
        if(!$re) {
            onlineAward:
            $today = date('Y-m-d 00:00:00');
            $re = self::find(["player_id={$playerId} and date_limit='{$today}'", 'order'=>'online_award_id asc'])->toArray();
            if($re) {
                $re = $this->adapter($re);
                foreach($re as $k=>&$v) {
                    $v['award_item'] = json_decode($v['award_item'], true);
                }
                Cache::setPlayer($playerId, __CLASS__, $re);
            } else {
            generateOnlineAward:
                $this->generate($playerId);
                goto onlineAward;
            }
        }
        $last = $re[count($re)-1];
        if(date('Y-m-d 00:00:00')!=date('Y-m-d 00:00:00', $last['date_limit'])) {//不是今天的每日在线奖励
            $this->updateAll(['status'=>self::AWARD_X], ['player_id'=>$playerId, 'date_limit'=>q(date('Y-m-d 00:00:00', $last['date_limit']))]);
            $this->clearDataCache($playerId);
            goto generateOnlineAward;
        }
        if($forDataFlag && $re) {
            return filterFields($re, $forDataFlag, $this->blacklist);
        }
        return $re;
    }
    /**
     * 获取每日在线奖励
     * @param  int $playerId 
     * @return bool           
     */
    public function doGetOnlineAward($playerId){
        //获得奖励
        $Drop = new Drop;
        $all = $this->getByPlayerId($playerId);
        $return = false;
        $time = time();
        foreach($all as $v) {
            if($this->nextFlag) {//$this->nextFlag为true时break
                $this->nextFlag = false;
                $this->updateAll(['time_start'=>qd()], ['id'=>$v['id']]);
                $this->clearDataCache($playerId);
                break;
            }
            if($v['status']==self::AWARD_N && $time-$v['time_start']>=$v['online_award_duration']) {
                if($this->updateAll(['status'=>self::AWARD_Y, 'update_time'=>qd(), 'time_award'=>qd(), 'memo'=>q('已领奖')], ['id'=>$v['id'], 'status'=>self::AWARD_N])) {
                    $this->clearDataCache($playerId);
                    $this->nextFlag = true;
                    $dropData = $v['award_item'];
                    //整理道具，增加发送速度
                    $gainItems = [];
                    foreach($dropData as $_dropData){
                        list($_type, $_itemId, $_num, $_rate) = $_dropData;
                        @$gainItems[$_type][$_itemId] += $_num;
                    }
                    $Drop->_gain($playerId, $gainItems, '每日在线奖励');
                    $return = true;
                }
            } elseif($v['status']==self::AWARD_N) {
                break;
            }
        }
        return $return;
    }
}