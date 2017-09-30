<?php

use Phalcon\Mvc\Controller;
/**
 * 控制器父类
 */
class ControllerBase extends Controller
{
    /**
     * 当前玩家信息
     */
    public $currentPlayer = null;
    /**
     * @var null 当前玩家的player_info信息
     */
    public $currentPlayerInfo = null;
    /**
     * 当前玩家id
     */
    public $currentPlayerId = 0;
    /**
     * @var string 当前请求的controller
     */
    public $controllerName = '';
    /**
     * 当前请求的action
     */
    public $actionName = '';
    /**
     * post数据
     *
     * @var null
     */
    public $postData = null;
    /**
     * 是否执行初始化
     */
    public $initFlag = true;
    /**
     * 白名单过滤
     *
     * true: 白名单起作用
     * false: 无视白名单
     *
     * @var bool
     */
    public $ipLimitSwitch = true;
    /**
     * 是否联盟号登录
     * @var boolean
     */
    public $userCodeLoginFlag = false;
    public $lockKey  = 'ControllerBase_doBeforeActionEachRequest_playerId=';

    /**
     * 控制器初始化.
     * @param   type    $varname    description
     * @return  type    description
     */
    public function initialize(){
        if($this->initFlag) {
            $this->auth();
        }
    }
    /**
     * 客户端授权认证
     */
    public function auth(){
        if(isset($_REQUEST['adminQA']) && $_REQUEST['adminQA']==1) {
            StaticData::$adminQAFlag = true;
            unset($_REQUEST['adminQA']);
        }
        $isPost   = $this->request->isPost() || QA || StaticData::$adminQAFlag;
        $postData = $this->postData = getPost();
        if($isPost) {
            //联盟号登录模式 -------------B
            if(isset($postData['userCode']) && isset($postData['userCodeHash'])) {//系统登录模式 //说有安全问题，改为QA模式下生效
                if(!QA) {
                    echo $this->data->sendErr('非QA模式不可以使用!');
                    exit;
                }
                $userCode     = $postData['userCode'];
                $userCodeHash = $postData['userCodeHash'];
                if(validateUuid($userCode, $userCodeHash)) {
                    $Player = new Player;
                    $player = Player::findFirst(["user_code='{$userCode}'"]);
                    if($player) {
                        $playerId                = $player->id;
                        $player                  = $Player->getByPlayerId($playerId);

                        //case d binding
                        $this->currentPlayer     = $player;
                        $this->currentPlayerId   = $playerId;
                        $this->data->setPlayerId($playerId);
                        $this->userCodeLoginFlag = true;

                        //新手引导更改步骤数
                        if(isset($postData['steps']) && isset($postData['steps']['step']) && is_numeric($postData['steps']['step'])) {//更改步骤数
                            $Player->alter($playerId, ['step'=>$postData['steps']['step']]);
                        }
                        //新手引导 数据集合
                        if(isset($postData['steps']) && isset($postData['steps']['step_set']) && is_numeric($postData['steps']['step_set'])) {//更改步骤数
                            $stepSet   = $player['step_set'];
                            $stepSet[] = $postData['steps']['step_set'];
                            $stepSet   = array_unique($stepSet);
                            $Player->alter($playerId, ['step_set'=>q(json_encode($stepSet))]);
                        }
                        return;
                    } else {
                        echo $this->data->sendErr('user_code不存在!');
                        exit;
                    }
                }
                return false;
            }//联盟号登录模式 -------------E
            //正常游戏登录---------------B
            $uuid           = $postData['uuid'];//case a 获取uuid
            $hashCode       = $postData['hashCode'];
            $controllerName = $this->controllerName = strtolower($this->dispatcher->getControllerName());
            $actionName     = $this->actionName     = strtolower($this->dispatcher->getActionName());
            if(validateUuid($uuid, $hashCode)) {//验证uuid
                //case b 根据uuid生成新玩家或者获取已存在玩家
                if($controllerName!='common' && $actionName!='ntpdate'
                   && isset($postData['timeCollated'])
                   && $postData['timeCollated']==1
                   && isset($postData['timestamp'])) {
                    $this->filterInvalidRequest($postData['timestamp']);
                }
                $uuidFromGetReqValidateFlag = false;
                if(isset($_GET['inner'])) {//如果是内部请求，不检测uuid
                    $uuidFromGetReqValidateFlag = true;
                }
                elseif(isset($_GET['uuid'])) {//判断get中的uuid和post中的uuid是否一致
                    $uuidFromGetReq = trim($_GET['uuid']);
                    if($uuidFromGetReq==$uuid) {
                        $uuidFromGetReqValidateFlag = true;
                    }
                }
                elseif(QA || StaticData::$adminQAFlag){
                    $uuidFromGetReqValidateFlag = true;
                }
                if($uuid && $uuidFromGetReqValidateFlag) {
                    $Player = new Player;
                    $player = $Player->getPlayerByUuid($uuid);//case c 判断cache中是否存在uuid<=>id
                    if(!$player) {//如果用户不存在
                        if($controllerName=='common' && $actionName=='checkplayer') {//检测是否有玩家存在
                            $player = $Player->newPlayer($postData);
                            if(!$player) {
                                echo $this->data->sendErr($Player->errCode);
                                exit;
                            }
                            $playerInfo = (new PlayerInfo)->getByPlayerId($player['id']);
                            echo $this->data->sendRaw(['checkPlayer'=>0,'login_hashcode'=>$playerInfo['login_hashcode']]);
                            exit;
                        } elseif($controllerName=='common' && $actionName=='getvalidcode') {
                            $data = ['valid_code'=>'new_new_new'];
                            echo $this->data->sendRaw($data);
                            exit;
                        } else {
                            exit("\n[ERROR]illegal url to new player\n");
                        }
                    }
                    //case d binding
                    $this->currentPlayer   = $player;
                    $this->currentPlayerId = $player['id'];
                    $this->data->setPlayerId($player['id']);
                    $this->doBeforeActionEachRequest();//每次请求到对应action前的操作
                } else {
                    exit("\n[ERROR]Request without uuid or Validate uuid from Get Request!\n");
                }
            } else {
                exit("\n[ERROR]illegal login\n");
            }
        } else {
            exit("\n[ERROR]not a post request\n");
        }
    }
    /**
     * 过滤非法请求
     *
     * @param $timestamp
     */
    public function filterInvalidRequest($timestamp){
        $subTime = time()-$timestamp;
        if($subTime>15) {//时间在15秒之外为无用请求
            $errCode = 9995;//'时间超过15秒';
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
    /**
     * 每次请求到对应action前的操作
     */
    public function doBeforeActionEachRequest(){
        $playerId       = $this->currentPlayerId;
        $player         = $this->currentPlayer;
        $controllerName = $this->controllerName;
        $actionName     = $this->actionName;
        $postData       = $this->postData;

        $Player     = new Player;
        $PlayerInfo = new PlayerInfo;

        $lockKey  = $this->lockKey.$playerId;
        Cache::lock($lockKey);//锁定

        $playerInfo = $this->currentPlayerInfo = $PlayerInfo->getByPlayerId($player['id']);
        //a 检测login time
        if(date('Y-m-d', $player['login_time'])!=date('Y-m-d')) {
            $Player->alter($playerId, ['login_time'=>qd()]);
            //统计进入player_online
            (new PlayerOnline)->recordExp($playerId);
            (new PlayerActivityLogin)->addDays($playerId, 1);//累计登录
        } else {
            //累计登录"第一天早上登录,下午开启活动"判断
            $activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityLogin::ACTID);
            if($activityConfigure) {
                $PlayerActivityLogin = new PlayerActivityLogin;
                $activityConfigure = $activityConfigure[0];
                $actCfgId = $activityConfigure['id'];
                if(date('Y-m-d')==date('Y-m-d', $activityConfigure['start_time'])) {//当天开启,但是已经登录
                    $playerAct = $PlayerActivityLogin->getByActId($playerId, $actCfgId);
                    if(!$playerAct) {
                        $PlayerActivityLogin->addDays($playerId, 1);//累计登录
                    }
                }
            }
        }
        //b 检测服务器端版本号
        if(isset($postData['game_version'])) {
            $currentGameVersion = (new LoginServerConfig)->getValueByKey('game_version');
            if($postData['game_version'] != $currentGameVersion) {
                Cache::unlock($lockKey);
                // $errCode = 10348;//与服务器版本号不一致!
                $errCode = 9998;//与服务器版本号不一致!
                echo $this->data->sendErr($errCode);
                exit;
            }
        }
        //c 检测是否在其他设备登录
        if(!($controllerName=='common' && $actionName=='checkplayer') && !($controllerName=='common' && strtolower($actionName)=='getvalidcode') && isset($postData['login_hashcode'])) {
            if($postData['login_hashcode'] != $playerInfo['login_hashcode']) {
                Cache::unlock($lockKey);
                $errCode = 9999;//'该帐号在其他设备上登录';
                echo $this->data->sendErr($errCode);
                exit;
            }
            if($PlayerInfo->getBanTime($player['id'])) {
                Cache::unlock($lockKey);
                $errCode = 9997;//'该帐号已被封号';
                echo $this->data->sendErr($errCode);
                exit;
            }
        }
        //d 新手引导更改步骤数
        if(isset($postData['steps']) && isset($postData['steps']['step']) && is_numeric($postData['steps']['step'])) {//更改步骤数
            $Player->alter($playerId, ['step'=>$postData['steps']['step']]);
        }
        //e 新手引导 数据集合
        if(isset($postData['steps']) && isset($postData['steps']['step_set']) && is_numeric($postData['steps']['step_set'])) {//更改步骤数
            $stepSet   = $player['step_set'];
            $stepSet[] = $postData['steps']['step_set'];
            $stepSet   = array_unique($stepSet);
            $Player->alter($playerId, ['step_set'=>q(json_encode($stepSet))]);
        }
        //f 发送Email_tips
        $this->sendEmailTips();
		//g 新手登陆登陆情况记录
		$this->newbieLogin($player, $playerInfo);
        //case 修正士兵
        if($player['has_corrected']==0) {
            $Player->correctPlayerBuildAndSoldier($playerId);
        }
        //case 限制ip
        $this->ipLimit();
        //case 存access log
        $this->saveAccessLog();
        Cache::unlock($lockKey);//锁定
    }
    /**
     * 发送Email_tips系统邮件
     */
    public function sendEmailTips(){
        $EmailTips          = new EmailTips;
        $PlayerMail         = new PlayerMail;
        $PlayerInfo         = new PlayerInfo;
        $allEmailTips       = $EmailTips->dicGetAll();
        $allEmailTips       = Set::sort($allEmailTips, '{n}.id', 'asc');
        $playerCreateTime   = $this->currentPlayer['create_time'];
        //7天以上就不发tip邮件了
        if(time()-$playerCreateTime>604800){//7*24*60*60
            return false;
        }
        $currentEmailTipsId = $this->currentPlayerInfo['email_tips_id'];
        $subTime            = time() - $playerCreateTime;//玩家注册游戏到now的时间差
        foreach($allEmailTips as $v) {
            $id = intval($v['id']);
            if($id>$currentEmailTipsId && $subTime>=$v['time']*60) {//发送邮件
                if($PlayerInfo->updateAll(['email_tips_id'=>$id], ['id'=>$this->currentPlayerInfo['id'],'player_id'=>$this->currentPlayerId, 'email_tips_id'=>$currentEmailTipsId])) {
                    $PlayerMail->sendSystem([$this->currentPlayerId], PlayerMail::TYPE_EMAIL_TIPS, 'system email', '', 0, ['mail_tips_id' => $id]);
                    $currentEmailTipsId = $id;
                    $PlayerInfo->clearDataCache($this->currentPlayerId);
                }
            }
        }
        return true;
    }
    
	/**
     * 新手登陆登陆情况记录
     */
	public function newbieLogin($player, $playerInfo){
		$createDate = strtotime(date('Y-m-d', $player['create_time']));
		$today = strtotime(date('Y-m-d'));
		$maxDay = (new ActNewbieSign)->getMaxDay();
		$diffDay = floor(($today - $createDate) / (3600*24)) + 1;
		if($diffDay <= $maxDay){
			if(!in_array($diffDay, $playerInfo['newbie_login'])){
				$playerInfo['newbie_login'][] = $diffDay;
				(new PlayerInfo)->alter($player['id'], ['newbie_login'=>$playerInfo['newbie_login']]);
			}
		}
	}
    /**
     * 添加accesslog到player_common_log表中
     */
    public function saveAccessLog(){
        if(QA&&ACCESS_LOG_FLAG) {
            $playerId = $this->currentPlayer['id'];
            (new PlayerCommonLog)->add($playerId, ['type' => 'accesslog', 'url' => StaticData::$_url, 'postData' => StaticData::$_postData]);
        }
    }
	
	/**
     * 限ip
     */
    public function ipLimit(){
        global $config;
        $serverId   = $config->server_id;
        $serverList = (new ServerList)->dicGetAll();
        $serverList = Set::combine($serverList, '{n}.id', '{n}');
        $serverList = $serverList[$serverId];
        if(in_array($serverList['status'], [1,2]) && $this->ipLimitSwitch) {
            $clientIp = $_SERVER['REMOTE_ADDR'];
            $LoginServerConfig = new LoginServerConfig;
            //全局服务器白名单
            $ipLimitConfig = $LoginServerConfig->getValueByKey('ip_limit_config_global');
            if($ipLimitConfig) {
                $ipLimit  = json_decode($ipLimitConfig, true);
                $limitIps = $ipLimit['ips'];
                foreach($limitIps as $v) {
                    $v = trim($v);
                    if(strpos($v, '.*')!==false) {//ip段
                        $ipSegment = substr($v, 0, strlen($v)-strlen('.*'));
                        if(strpos($clientIp, $ipSegment)===0) goto NotExit;
                    }
                    if($v==$clientIp) {//直接ip命中
                        goto NotExit;
                    }
                }
            }
            //single服务器白名单
            $ipLimitConfig2 = $LoginServerConfig->getValueByKey('ip_limit_config_single');
            if($ipLimitConfig2) {
                $ipLimit2 = json_decode($ipLimitConfig2, true);
                $ipLimit2 = Set::combine($ipLimit2, '{n}.serverId', '{n}.ips');
                if(isset($ipLimit2[$serverId])) {
                    foreach($ipLimit2[$serverId] as $v) {
                        $v = trim($v);
                        if(strpos($v, '.*')!==false) {//ip段
                            $ipSegment2 = substr($v, 0, strlen($v)-strlen('.*'));
                            if(strpos($clientIp, $ipSegment2)===0) goto NotExit;
                        }
                        if($v==$clientIp) {//直接ip命中
                            goto NotExit;
                        }
                    }
                }
            }
            $errCode = 9996;//维护期间-白名单之外的ip
            echo $this->data->sendErr($errCode);
            $lockKey  = $this->lockKey.$this->currentPlayerId;
            Cache::unlock($lockKey);
            exit;
        }
        NotExit:{
            return;
        }
    }
    /**
     * 获取当前玩家数据 array
     */
    public function getCurrentPlayer(){
        if(!$this->currentPlayer){
            $this->auth();
        }
        return $this->currentPlayer;
    }
    /**
     * 获取当前玩家id 
     */
    public function getCurrentPlayerId(){
        if(!$this->currentPlayerId){
            $this->auth();
        }
        return $this->currentPlayerId;
    }
    
	public function afterCommit(){
		//$data = array_unique($this->getDI()->get('data')->datas);
		foreach($this->di['data']->datas as $_playerId => $_d){
			$_d = array_unique($_d);
			foreach($_d as $__d){
				Cache::delPlayer($_playerId, $__d);
			}
		}
	}

	public function getControllerName(){
		return $this->getDI()['dispatcher']->getControllerName();
	}
	
	public function getActionName(){
		return $this->getDI()['dispatcher']->getActionName();
	}
	
	public function getParams(){
		return $this->getDI()['dispatcher']->getParams();
	}
}
