<?php
/**
 * 每日/签到 奖励控制器
 */
class AwardController extends ControllerBase{
    /**
     * 领取当日的每日签到奖励
     *
     * 使用方法如下
     *
     * ```php
     * award/doGetSignAward
     * postData:{}
     * return: {...}
     * ```
     */
    public function doGetSignAwardAction(){
        $playerId = $this->getCurrentPlayerId();
        $lockKey = __CLASS__.":".__METHOD__.":playerId=".$playerId;
        Cache::lock($lockKey);//锁定

        $PlayerSignAward = new PlayerSignAward;
        if(!$PlayerSignAward->doGetSignAward($playerId)) {
            $errCode = 10303;//领取每日签到奖励-已领过今日签到奖励
            goto sendErr;
        }
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
     * 领取当日的每日在线奖励
     *
     * 使用方法如下
     *
     * ```php
     * award/doGetOnlineAward
     * postData:{}
     * return: {...}
     * ```
     */
    public function doGetOnlineAwardAction(){
        $playerId = $this->getCurrentPlayerId();
        $lockKey = __CLASS__.":".__METHOD__.":playerId=".$playerId;
        Cache::lock($lockKey);//锁定

        $PlayerOnlineAward = new PlayerOnlineAward;
        if(!$PlayerOnlineAward->doGetOnlineAward($playerId)) {
            $errCode = 10304;//领取每日在线奖励-时间未到
            goto sendErr;
        }
        Cache::unlock($lockKey);
        echo $this->data->send();
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
}