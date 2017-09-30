<?php
/**
 * 推送
 *
 *
 *
 */
class PushTask extends \Phalcon\CLI\Task{
	public $defaultLanguage = 'Zhtw';
	public $titleCode = 400000;
	public $title=[];
	public $groupNum = 50;//批量发送每批个数
	public $currentWork = 0;
	public $maxRunWork = 1000;
	
    /**
     * bootstrap
     * @return [type] [description]
     */
    public function mainAction($param=array()){
		global $config;
		$processName = "php_task_push";
        $processExists = exec("ps -ef|grep {$processName}|grep -v grep");
        if(!empty($processExists)){
			echo "[INFO]shell exists.",PHP_EOL;
			return;
		}
			
		cli_set_process_title($processName);//set process name
		
		require_once(APP_PATH . '/app/lib/igt/IGt.Push.php');
		require_once(APP_PATH . '/app/lib/igt/igetui/IGt.AppMessage.php');
		require_once(APP_PATH . '/app/lib/igt/igetui/IGt.APNPayload.php');
		require_once(APP_PATH . '/app/lib/igt/igetui/template/IGt.BaseTemplate.php');
		require_once(APP_PATH . '/app/lib/igt/IGt.Batch.php');
		require_once(APP_PATH . '/app/lib/igt/igetui/utils/AppConditions.php');
		
		$startTime = time();
		
		$pp = new PlayerPush;
		while(true){
			try {
				$ret = $pp->find(['send_time <= now()', 'limit'=>100])->toArray();
				echo 'begin:'.count($ret).PHP_EOL;
				echo 'currentWork:'.$this->currentWork.PHP_EOL;
				foreach($ret as $_r){
					//push
					$this->push($_r['player_id'], $_r['type'], $_r['code'], $_r['param'], $_r['txt']);
					//delete
					$pp->del($_r['id']);
					//exit;
				}
				echo 'end'.PHP_EOL;
				if($ret){
					$this->currentWork++;
					if($this->currentWork >= $this->maxRunWork){
						$this->restartPush();
					}
				}
				sleep(1);
			}catch(Exception $e){
				$this->restartPush();
			}
		}
	}
	
	public function restartPush(){
		global $config;
		cli_set_process_title("php_task_end_push");
		exec($config['daemonPushPath'].' > /tmp/daemonPush.log');
		exit;
	}
	
	public function push($playerId, $type, $code, $param, $txt=''){
		global $config;
		$PlayerLastServer = (new PlayerLastServer);
		if($param){
			$param = json_decode($param, true);
		}else{
			$param = [];
		}
		if($playerId){//个别推送
			echo 'single:';
			$this->pushSingle($playerId, $type, $code, $param, $txt);
		}else{//全体推送
			echo 'multi:';
			$Player = new Player;
			//安卓用户
			echo '[android]';
			$id = 0;
			while($players = $Player->sqlGet("select id,uuid,client_id,lang from player where device_type=2 and client_id>'' and id>".$id." ".($type ? ("and FIND_IN_SET('".$type."' ,push_tag)") : "")." order by id limit ".$this->groupNum)){
				//根据语言分组玩家
				$groupPlayer = [];
				foreach($players as $_p){
					if($config->server_id != $PlayerLastServer->getByUuid($_p['uuid'])['last_server_id']){
						//echo $_p['uuid'];
						continue;
					}
					$groupPlayer[$_p['lang']][] = $_p['client_id'];
				}
				foreach($groupPlayer as $_lang => $_p){
					$this->pushAndoidList($_p, $_lang, $code, $param, $txt);
				}
				$id = $players[count($players)-1]['id'];
			}
			
			//苹果用户
			echo '[ios]';
			$id = 0;
			while($players = $Player->sqlGet("select id,uuid,device_token,lang from player where device_type=1 and device_token>'' and id>".$id." ".($type ? ("and FIND_IN_SET('".$type."' ,push_tag)") : "")." order by id limit ".$this->groupNum)){
				//根据语言分组玩家
				$groupPlayer = [];
				foreach($players as $_p){
					if($config->server_id != $PlayerLastServer->getByUuid($_p['uuid'])['last_server_id']){
						//echo $_p['uuid'];
						continue;
					}
					$groupPlayer[$_p['lang']][] = $_p['device_token'];
				}
				foreach($groupPlayer as $_lang => $_p){
					$this->pushIosList($_p, $_lang, $code, $param, $txt);
				}
				$id = $players[count($players)-1]['id'];
			}
		}
		echo PHP_EOL;
	}
	
	public function pushSingle($playerId, $type, $code, $param, $txt=''){
		global $config;
		//获取player
		echo 1;
		$player = (new Player)->getByPlayerId($playerId);
		if($config->server_id != (new PlayerLastServer)->getByUuid($player['uuid'])['last_server_id']){
			//echo $player['uuid'];
			return true;
		}
		echo 2;
		//检查推送开关
		$pushTag = $player['push_tag'];
		if($type && !in_array($type, $pushTag)){
			return true;
		}
		echo 3;
		//文字合成
		$language = ucfirst($player['lang']);
		if(!class_exists($language)){
			$language = $this->defaultLanguage;
		}
		$Lan = new $language;
		if($code){
			$lan = $Lan->dicGetOne($code);
			$param = $this->transParam($Lan, $code, $param);
			$txt = joinStr($lan['desc'], $param);
		}else{
			
		}
		echo 4;
		//分机型推送
		$badge = $player['badge']+1;
		$i = 0;
		$retry = 3;
		$pushRet = false;
		while(!$pushRet && $i < $retry){
			if($player['device_type'] == 1){
				if(!$player['device_token'])
					break;
				echo '{ios}';
				$pushRet = $this->_pushIos($player['device_token'], $txt, $badge);
			}else{
				$title = $this->getTitle($language);
				if(!$player['client_id'] || (time() - $player['last_online_time']) < 60)
					break;
				echo '{android}';
				$pushRet = $this->_pushAndoid($player['client_id'], $title, $txt, $badge);
			}
			$i++;
		}
		echo 5;
		//badge++
		if($pushRet){
			(new Player)->alter($playerId, ['badge'=>'badge+1']);
		}
		echo 6;
		return true;
	}
	
	public function pushAndoidList($ids, $lang, $code, $param, $txt=''){
		global $config;
		
		echo 1;
		$language = ucfirst($lang);
		if(!class_exists($language)){
			$language = $this->defaultLanguage;
		}
		$Lan = new $language;
		if($code){
			$lan = $Lan->dicGetOne($code);
			$param = $this->transParam($Lan, $code, $param);
			$txt = joinStr($lan['desc'], $param);
		}else{
			
		}
		echo 2;
		$title = $this->getTitle($language);
		echo 3;
		$this->_pushAndoid($ids, $title, $txt);
		echo 4;
	}
	
	public function pushIosList($ids, $lang, $code, $param, $txt=''){
		global $config;
		
		echo 1;
		$language = ucfirst($lang);
		if(!class_exists($language)){
			$language = $this->defaultLanguage;
		}
		$Lan = new $language;
		if($code){
			$lan = $Lan->dicGetOne($code);
			$param = $this->transParam($Lan, $code, $param);
			$txt = joinStr($lan['desc'], $param);
		}else{
			
		}
		echo 2;
		$this->_pushIos($ids, $txt);
		echo 3;
	}
	
	public function _pushAndoid($clientId, $title, $txt, $badge=0){
		global $config;
		echo 'a';
		$igt = new IGeTui($config['push']['host'],$config['push']['appkey'],$config['push']['mastersecret']);
		echo 'b';
		$template =  new IGtNotificationTemplate();
		$template->set_appId($config['push']['appid']);//应用appid
		$template->set_appkey($config['push']['appkey']);//应用appkey
		$template->set_transmissionType(1);//透传消息类型
		$template->set_transmissionContent("");//透传内容
		$template->set_title($title);//通知栏标题
		$template->set_text($txt);//通知栏内容
		$template->set_logo("push.png");//通知栏logo
		$template->set_isRing(true);//是否响铃
		$template->set_isVibrate(true);//是否震动
		$template->set_isClearable(true);//通知栏是否可清除
		echo 'c';
		if(is_array($clientId)){
			//putenv("gexin_pushList_needDetails=true");
			//putenv("gexin_pushList_needAsync=true");

			$message = new IGtListMessage();
		}else{
			$message = new IGtSingleMessage();
		}
		echo 'd';
		$message->set_isOffline(true);//是否离线
		$message->set_offlineExpireTime(3600*12*1000);//离线时间
		$message->set_data($template);//设置推送消息类型
	//	$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
	
		if(is_array($clientId)){
			echo 'e';
			$contentId = $igt->getContentId($message,"".date('Ymd')."job");	
			$targetList = [];
			echo 'f';
			foreach($clientId as $_c){
				$target = new IGtTarget();
				$target->set_appId($config['push']['appid']);
				$target->set_clientId($_c);
				$targetList[] = $target;
			}
			echo 'g';
			//var_dump($targetList);
			try {
				$rep = $igt->pushMessageToList($contentId, $targetList);
				if($rep['result'] == 'ok'){
					echo 'h';
					return true;
				}else{
					echo 'i';
					var_dump($rep);
					return false;
				}

			}catch(RequestException $e){
				echo 'j';
				$requstId =e.getRequestId();
				//$rep = $igt->pushMessageToSingle($message, $target,$requstId);
				//var_dump($rep);
				return false;
			}
		}else{
			echo 'k';
			//接收方
			$target = new IGtTarget();
			$target->set_appId($config['push']['appid']);
			$target->set_clientId($clientId);
			
			try {
				echo 'l';
				$rep = $igt->pushMessageToSingle($message, $target);
				//var_dump($rep);
				if($rep['result'] == 'ok'){
					echo 'm';
					return true;
				}else{
					echo 'n';
					var_dump($rep);
					return false;
				}

			}catch(RequestException $e){
				echo 'o';
				$requstId =e.getRequestId();
				//$rep = $igt->pushMessageToSingle($message, $target,$requstId);
				//var_dump($rep);
				return false;
			}
		}
	}
	
	public function _pushIos($token, $txt, $badge=0){
		global $config;
		//APN简单推送
		echo 'a';
		$igt = new IGeTui($config['push']['host'],$config['push']['appkey'],$config['push']['mastersecret']);
		echo 'b';
		$template = new IGtAPNTemplate();
		$apn = new IGtAPNPayload();
		$alertmsg=new SimpleAlertMsg();
		$alertmsg->alertMsg=$txt;
		$apn->alertMsg=$alertmsg;
		$apn->badge=$badge;
		$apn->sound="";
	   // $apn->add_customMsg("payload","payload");
		$apn->contentAvailable=1;
		$apn->category="ACTIONABLE";
		$template->set_apnInfo($apn);
		echo 'c';
		if(is_array($token)){
			echo 'd';
			$listmessage = new IGtListMessage();
			$listmessage->set_data($template);
			echo 'e';
			$contentId = $igt->getAPNContentId($config['push']['appid'], $listmessage);
			echo 'f';
			//var_dump($token);
			$ret = $igt->pushAPNMessageToList($config['push']['appid'], $contentId, $token);
			echo 'g';
		}else{
			echo 'h';
			$message = new IGtSingleMessage();
			$message->set_data($template);
			echo 'i';
			$ret = $igt->pushAPNMessageToSingle($config['push']['appid'], $token, $message);
			if($ret['result'] == 'ok'){
				echo 'j';
				return true;
			}else{
				echo 'k';
				var_dump($ret);
				return false;
			}
			
		}
		
	}
	
	public function getTitle($language){
		if(isset($this->title[$language])){
			return $this->title[$language];
		}else{
			if(!class_exists($language)){
				$language = $this->defaultLanguage;
			}
			$Lan = new $language;
			$title = $Lan->dicGetOne($this->titleCode);
			$this->title[$language] = $title['desc'];
			return $title['desc'];
		}
	}

	public function transParam($Lang, $code, $param){
		$trans = [
			'400001'=>['buildname'],
			'400002'=>['soldiername'],
			'400008'=>['activityname'],
			'400009'=>['alliancename'],
			'400013'=>['bossname'],
		];
		if(isset($trans[$code])){
			$newParam = [];
			foreach($param as $_k => $_v){
				if(in_array($_k, $trans[$code])){
					$lan = $Lang->dicGetOne($_v);
					if($lan){
						$newParam[$_k] = $lan['desc'];
					}else{
						$newParam[$_k] = $_v;
					}
				}else{
					$newParam[$_k] = $_v;
				}
			}
		}else{
			$newParam = $param;
		}
		return $newParam;
	}
	
	
    /**
     * 每天18：30推送当天没登陆的前一天注册的玩家
     * 
     * 
     * @return <type>
     */
	public function createUnloginPushAction(){
		$today = date('Y-m-d 00:00:00');
		$yesterday = date('Y-m-d 00:00:00', time()-3*24*3600);
		$sql = 'insert into player_push (select null, id, 3, 0, "", "三國鼎立，群雄割據，全新陣營系統蓄勢待發！ ", now(), now() from player where login_time >= "'.$yesterday.'" and login_time < "'.$today.'")';
		(new ModelBase)->sqlExec($sql);
		echo '['.date('Y-m-d H:i:s')."]ok\r\n";
	}
}