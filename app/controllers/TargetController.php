<?php
/**
 * 新手目标 相关业务逻辑
 */
use Phalcon\Mvc\View;
class TargetController extends ControllerBase{
    public function initialize() {
        parent::initialize();
        $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
    }
    /**
     * 领取新手目标奖励
     *
     * 使用方法如下：
     * ```php
     * target/getTargetAward
     * postData: {"current_id":1}
     * return: {}
     * ```
     */
    public function getTargetAwardAction(){
    	$playerId  = $this->getCurrentPlayerId();
        //锁定
        $lockKey   = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $postData  = getPost();
        $currentId = $postData['current_id'];

        $PlayerTarget = new PlayerTarget;
        if(!$PlayerTarget->beforeFilter($playerId)) {//超时
            $errCode = 10372;//[新手目标]已过7天时间周期
            goto sendErr;
        }
        $nextTarget = $PlayerTarget->getTargetAward($playerId, $currentId);
        if(!$nextTarget) {
            $errCode = 10373;//[新手目标]任务未完成
            goto sendErr;
        }
        if($nextTarget == -1) {
            $nextTarget = [];
        }
        if($nextTarget==0 || $nextTarget==1) {//异常
            $nextTarget = [];
        }

        Cache::unlock($lockKey);
        echo $this->data->send($nextTarget);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
}