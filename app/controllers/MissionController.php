<?php
/**
 * 主线任务 or 每日任务
 */
class MissionController extends ControllerBase{
    /**
     * 刷新单个每日任务
     *
     * 使用方法如下：
     * ```php
     * mission/refreshDailyMission
     * postData: {"current_id":125}
     * return: {}
     * ```
     * 
     */
    public function refreshDailyMissionAction(){
        $playerId  = $this->getCurrentPlayerId();
        //锁定
        $lockKey   = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $postData  = getPost();
        $currentId = $postData['current_id'];


        $PlayerMission = new PlayerMission;
        $playerMission = $PlayerMission->getById($playerId, $currentId);
        if($playerMission) {
            if($playerMission['mission_type']==1) {//主线任务
                $errCode = 10141;
                goto sendErr;
            }
            if(!(in_array($playerMission['status'], [PlayerMission::START, PlayerMission::COMPLETE]) && $playerMission['date_limit']==date('Y-m-d'))) {
                $errCode = 10334;//每日任务-只能刷新未领奖的当天的每日任务
                goto sendErr;
            }
            if(!(new Cost)->updatePlayer($playerId, 110)) {//宝石刷新每日任务 gem不足
                $errCode = 10244;
                goto sendErr;
            }
            $PlayerMission->refreshDailyMissionWithGem($playerId, $currentId);
        } else {
            $errCode = 10142;
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
     * 领取任务奖励
     *
     * 使用方法如下：
     * ```php
     * mission/getMissionReward
     * postData: {"current_id":125}
     * return: {}
     * ```
     */
    public function getMissionRewardAction(){
        $playerId  = $this->getCurrentPlayerId();
        //锁定
        $lockKey   = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $postData  = getPost();
        $currentId = $postData['current_id'];

        $PlayerMission = new PlayerMission;
        if(!$PlayerMission->getMissionReward($playerId, $currentId)) {
            $errCode = 10245;
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
     * @deprecated  仅供测试
     *
     */
    public function updateMissionTestAction(){
         $playerId  = $this->getCurrentPlayerId();
         $postData = getPost();
         $missionType = $postData['mission_type'];
         $missionNumber = $postData['mission_number'];

         $PlayerMission = new PlayerMission;
         $PlayerMission->updateMissionNumber($playerId, $missionType, $missionNumber);

         $data = $postData;
         echo $this->data->send();
         exit;
    }
}