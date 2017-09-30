<?php
/**
 * 玩家签到奖励表
 */
class PlayerSignAward extends ModelBase{
    public $blacklist = ['update_time'];
    const AWARD_N = 0;//未领
    const AWARD_Y = 1;//已领
    const ROUND_N = 0;//非当前周期
    const ROUND_Y = 1;//当前周期
    /**
     * 生成每日签到一个周期的记录
     * @param  int $playerId 
     */
    public function generate($playerId){
        $SignAward = new SignAward;
        $Drop = new Drop;
        $allSignAward = $SignAward->dicGetAll();
        $allSignAward = Set::sort($allSignAward, '{n}.id', 'asc');
        $this->updateAll(['round_flag'=>self::ROUND_N], ['player_id'=>$playerId, 'round_flag'=>self::ROUND_Y]);
        foreach($allSignAward as $v) {
            $self = new self;
            $self->player_id = $playerId;
            $self->sign_award_id = $v['id'];

            $dropIds = parseArray($v['drop']);
            $self->award_item = json_encode($Drop->rand($playerId, $dropIds));

            $self->round_flag = self::ROUND_Y;
            $self->update_time = $self->create_time = date('Y-m-d H:i:s');
            $self->save();
        }
        $this->clearDataCache($playerId);
    }
    /**
     * 通过player_id获取签到信息
     * @param  int $playerId
     * @return array          
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $re = Cache::getPlayer($playerId, __CLASS__);
        if(!$re) {
            signAward:
            $currentRoundFlag = self::ROUND_Y;
            $re = self::find(["player_id={$playerId} and round_flag={$currentRoundFlag}", 'order'=>'sign_award_id asc'])->toArray();
            if($re) {
                $re = $this->adapter($re);
                foreach($re as $k=>&$v) {
                    $v['award_item'] = json_decode($v['award_item'], true);
                }
                Cache::setPlayer($playerId, __CLASS__, $re);
            } else {
            generateSignAward:
                $this->generate($playerId);
                goto signAward;
            }
        }
        //判断当前周期是否结束,已结束则重新开启新的周期
        $last = $re[count($re)-1];
        if($last['status']==self::AWARD_Y && $last['get_award_time'] && date('Y-m-d', $last['get_award_time'])!=date('Y-m-d')) {
            $this->updateAll(['round_flag'=>self::ROUND_N, 'update_time'=>qd()], ['player_id'=>$playerId]);//清当前
            $this->clearDataCache($playerId);//生成新一轮
            goto generateSignAward;
        }
        if($forDataFlag && $re) {
            return filterFields($re, $forDataFlag, $this->blacklist);
        }
        return $re;
    }
    /**
     * 获取当日的签到奖励
     * @param  int $playerId 
     * @return bool           
     */
    public function doGetSignAward($playerId){
        $all = $this->getByPlayerId($playerId);
        $return = false;
        foreach($all as $v) {
            if($v['get_award_time'] && date('Y-m-d', $v['get_award_time'])==date('Y-m-d')) {//已经领过每日签到奖励
                break;
            }
            if($v['status']==self::AWARD_N) {
                //case a:
                //设为已领奖
                $this->updateAll(['status'=>self::AWARD_Y, 'memo'=>q("已领奖"), 'get_award_time'=>qd(), 'update_time'=>qd()], ['id'=>$v['id'], 'status'=>self::AWARD_N]);
                $this->clearDataCache($playerId);
                //获得奖励
                $Drop = new Drop;
                $dropData = $v['award_item'];
                //整理道具，增加发送速度
                $gainItems = [];
                foreach($dropData as $_dropData){
                    list($_type, $_itemId, $_num, $_rate) = $_dropData;
                    @$gainItems[$_type][$_itemId] += $_num;
                }
                $Drop->_gain($playerId, $gainItems, '每日签到奖励');
                //case b:
                //vip经验获得
                $Player = new Player;
                $player = $Player->getByPlayerId($playerId);
                $signDate = $player['sign_date'];
                $signTimes = $player['sign_times'];

                $vipLevel = $player['vip_level'];
                $vipActive = (new PlayerBuff)->getPlayerBuff($playerId, 'vip_active');

                $today = strtotime(date('Y-m-d 00:00:00'));
                $yestoday = $today-24*60*60;
                if($signDate!=$today) {//今天尚未领取过
                    if($signDate<$yestoday) {//签到中断
                        $vipSignTimes = 1;
                        $vipExp = (new VipExpDaily)->dicGetBySpecialCondition($vipLevel, $vipActive, $vipSignTimes);
                        if($Player->updateAll(['sign_date'=>q(date('Y-m-d 00:00:00')), 'sign_times'=>1], ['id'=>$playerId])) {
                            $Player->clearDataCache($playerId);
                            $Player->addVipExp($playerId, $vipExp['vipexp']);
                        }
                    } else {//未中断
                        $vipSignTimes = $player['sign_times'] + 1;
                        $vipExp = (new VipExpDaily)->dicGetBySpecialCondition($vipLevel, $vipActive, $vipSignTimes);
                        if($Player->updateAll(['sign_date'=>q(date('Y-m-d 00:00:00')), 'sign_times'=>'sign_times+1'], ['id'=>$playerId, 'sign_date'=>q(date('Y-m-d 00:00:00', $yestoday))])) {
                            $Player->clearDataCache($playerId);
                            $Player->addVipExp($playerId, $vipExp['vipexp']);
                        }
                    }
                }
                $return = true;
                break;
            }
        }
        return $return;
    }
}