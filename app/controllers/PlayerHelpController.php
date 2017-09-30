<?php
/**
 * 屯所帮助，含：帮助所有，帮助一个，发送帮助
 */
class PlayerHelpController extends ControllerBase{
    /**
     * 查看来帮助我的所有援军
     *
     * 使用方法如下：
     * ```php
     * player_help/viewHelpArmy
     * postData: {}
     * return {data}
     * ```
     */
    public function viewHelpArmyAction(){
        $playerId = $this->getCurrentPlayerId();
        $data     = (new PlayerProjectQueue)->getHelpArmy($playerId);
        echo $this->data->send($data);
        exit;
    }
    /**
     * 踢回自己家的援军部队
     *
     * 使用方法如下
     * ```php
     * player_help/letHelpArmyBackHome
     * postData: {'ppq_id':1111}
     * return: {}
     * ```
     */
    public function letHelpArmyBackHomeAction(){
        $playerId = $this->getCurrentPlayerId();
         //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $postData = getPost();
        $ppqId    = $postData['ppq_id'];
        (new PlayerProjectQueue)->letHelpArmyBackHome($playerId, $ppqId);
        Cache::unlock($lockKey);
        $data     = (new PlayerProjectQueue)->getHelpArmy($playerId);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
           echo $this->data->sendErr($errCode);
           exit;
        }
    }
    /**
     * 选择盟友后增援 
     *
     * ```php
     * player_help/sendArmy
     * postData: {"to_player_id":100029,"army_id":123, "useMove":1}
     * return: {PlayerProjectQueue}
     * ```
     */
    public function sendArmyAction(){
        $playerId           = $this->getCurrentPlayerId();
        //锁定
        $lockKey            = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        
        $player             = $this->getCurrentPlayer();
        $guildId            = $player['guild_id'];
        $postData           = getPost();
        $toPlayerId         = $postData['to_player_id'];
        $armyId             = $postData['army_id'];
        
        $db                 = $this->di['db'];
        dbBegin($db);

        $Player             = new Player;
        $Map                = new Map;
        $PlayerBuild        = new PlayerBuild;
        $PlayerProjectQueue = new PlayerProjectQueue;
        

        $playerBuild    = $PlayerBuild->getByOrgId($playerId, 11);//获取玩家屯所
        if(!$playerBuild) {
            $errCode = 10343;//自己没有屯所，无法进行援助
            goto sendErr;
        }
        $toPlayerBuild    = $PlayerBuild->getByOrgId($toPlayerId, 11);//获取玩家屯所
        if(!$toPlayerBuild) {
            $errCode = 10344;//对方没有屯所，无法进行援助
            goto sendErr;
        }
        //如果自己有罩子,直接off掉
        if($Player->isAvoidBattle($player)){
            $Player->offAvoidBattle($playerId);
        }
        //PPQ for PlayerProjectQueue
        $toPlayer           = $Player->getByPlayerId($toPlayerId);
        $currentPPQ         = $PlayerProjectQueue->getByPlayerId($playerId);

        try{
            $Map->doBeforeGoOut($playerId, $armyId, (new Starting)->dicGetOne('energy_cost_aid'), ['ppq'=>$currentPPQ]);
        } catch (Exception $e) {
            list($errCode, $msg) = parseException($e);
            goto sendErr;
        }

        $toPPQ              = $PlayerProjectQueue->getHelpArmy($toPlayerId);
        //队列是否足够
        if(count($currentPPQ)>=$Player->getQueueNum($playerId)) {
            $errCode = 10252;
            goto sendErr;
        }
        //对方队列是否足够
        if($toPPQ['current_help_num']>=$toPPQ['max_help_num']) {
            $errCode = 10253;
            goto sendErr;
        }
        //非同盟
        if(!($guildId || $toPlayer['guild_id']!=$guildId)) {
            $errCode = 10254;
            goto sendErr;
        }
        //已经给目标玩家派出队列
        foreach($toPPQ as $k=>$v) {
            if($v['type']==PlayerProjectQueue::TYPE_CITYASSIST_GOTO && $v['target_player_id']==$toPlayerId && $v['status']==1) {
                $errCode = 10255;
                goto sendErr;
            }
        }
        //建立队列 
        $type                     = PlayerProjectQueue::TYPE_CITYASSIST_GOTO;//增援
        if(isset($postData['useMove'])) {
            $useMove = $postData['useMove'];
        } else {
            $useMove = false;
        }
        if($useMove){
            try{
                $MapController = new MapController;
                $distance = sqrt(pow($player['x'] - $toPlayer['x'], 2) + pow($player['y'] - $toPlayer['y'], 2));
                $MapController->useHpMove($player, $distance);
                $needTime = MapController::EXTRASEC;
            } catch(Exception $e) {
                list($errCode, $msg) = parseException($e);
                goto sendErr;
            }
        }else{
            $needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $toPlayer['x'], $toPlayer['y'], 3, $armyId);
        }
        
        // $targetInfo               = ['army_id'=>$armyId];
        
        $extraData                = [];
        $extraData['from_map_id'] = $player['map_id'];
        $extraData['from_x']      = $player['x'];
        $extraData['from_y']      = $player['y'];
        $extraData['to_map_id']   = $toPlayer['map_id'];
        $extraData['to_x']        = $toPlayer['x'];
        $extraData['to_y']        = $toPlayer['y'];

        $PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $toPlayerId, $type, $needTime, $armyId, [], $extraData);
        dbCommit($db);
        //向被援助方推送消息
        socketSend(['Type'=>'send_army','Data'=>['playerId'=>$toPlayerId]]);

        Cache::unlock($lockKey);
        $data                     = DataController::get($playerId, ['PlayerProjectQueue']);
        echo $this->data->send($data);
        exit;
        sendErr: {
            dbRollback($db);
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 发送帮助请求
     *
     * 使用方法如下
     * ```php
     * player_help/sendHelp
     * postData: {'position':1015}
     * return {}
     *
     * ```
     */
    public function sendHelpAction(){
        $playerId = $this->getCurrentPlayerId();
        
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);//锁定
        $player = $this->getCurrentPlayer();

        if(!$player['guild_id']) {
            $errCode = 10294;//发送帮助请求-该玩家没有联盟,this player do not have a guild
            goto sendErr;
        }

        $postData = getPost();
        $position = $postData['position'];

        $PlayerBuild = new PlayerBuild;
        $PlayerHelp = new PlayerHelp;

        $PlayerHelp->addPlayerHelp($playerId, $position);
        Cache::unlock($lockKey);
        $data = [];
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 帮助所有玩家
     *
     * 使用方法如下
     * ```php
     * url: player_help/helpAll
     * postData: {}
     * return: 
     * ```
     */
    public function helpAllAction(){
        $playerId = $this->getCurrentPlayerId();
        $player   = $this->getCurrentPlayer();
        //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);

        $PlayerHelp = new PlayerHelp;
        $PlayerHelp->updateAllHelpNum($playerId, $player['guild_id']);
        
        Cache::unlock($lockKey);
        echo $this->data->send();
        exit;
    }
}