<?php
/**
 * 玩家信息 player info 控制器
 */
class PlayerInfoController extends ControllerBase{
    /**
     * 领取至尊卡奖励
     *
     * 使用方法如下
     *
     * ```php
     * url: player_info/getLongCardAward
     * postData: {}
     * return: {}
     * ```
     * 
     */
    public function getLongCardAwardAction(){
        $playerId = $this->getCurrentPlayerId();
        //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $player   = $this->getCurrentPlayer();
        
        $flag     = (new PlayerInfo)->getLongCardAward($playerId);
        switch($flag) {
            case -1:
                $errCode = 10412;//至尊卡奖励-没有购买至尊卡
                goto sendErr;
            case -2:
                $errCode = 10413;//至尊卡奖励-当天已经领过至尊卡奖励
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
     * 领取月卡奖励
     *
     * 使用方法如下
     *
     * ```php
     * url: player_info/getMonthCardAward
     * postData: {}
     * return: {}
     * ```
     * 
     */
    public function getMonthCardAwardAction(){
        $playerId = $this->getCurrentPlayerId();
        //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $player   = $this->getCurrentPlayer();
        
        $flag     = (new PlayerInfo)->getMonthCardAward($playerId);
        switch($flag) {
            case -1:
                $errCode = 10414;//月卡奖励-没有购买月卡
                goto sendErr;
            case -2:
                $errCode = 10415;//月卡奖励-当天已经领过月卡奖励
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
     * facebook分享
     *
     * 使用方法如下
     * 
     * ```
     * player_info/facebookShare
     * postData: {}
     * return: {}
     * ```
     *
     */
    public function facebookShareAction(){
        $playerId = $this->getCurrentPlayerId();
        //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $player   = $this->getCurrentPlayer();
        
        $PlayerInfo = new PlayerInfo;
        $PlayerInfo->facebookShare($playerId);
  
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
     * 更改随身秘书状态
     *
     * ```php
     *  player_info/changeSecretaryStatus
     *  postData:{"status":xx}
     *  return: {}
     * ```
     */
    public function changeSecretaryStatusAction(){
        $playerId = $this->getCurrentPlayerId();
        //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $postData = getPost();
        $status = intval($postData['status']);
        if(in_array($status, [1,2,3,4])) {
            (new PlayerInfo)->alter($playerId, ['secretary_status'=>$status]);
        } else {
            $errCode = 10061;//程序异常
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