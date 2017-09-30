<?php
use Phalcon\Mvc\View;
class MailController extends ControllerBase
{
	/*public $cataType = array(
				//聊天
				1 => array(PlayerMail::TYPE_CHATSINGLE, PlayerMail::TYPE_CHATGROUP),
				//联盟
				2 => array(PlayerMail::TYPE_GUILDINVITE, PlayerMail::TYPE_GUILDAPPLY, PlayerMail::TYPE_GUILDAPPROVAL, PlayerMail::TYPE_GUILDQUIT, PlayerMail::TYPE_GUILDGATHER, PlayerMail::TYPE_GUILDAUTHCHG),
				//侦查
				3 => array(PlayerMail::TYPE_DETECT, PlayerMail::TYPE_DETECTED),
				//战斗
				4 => array(
					PlayerMail::TYPE_ATTACKCITYWIN, PlayerMail::TYPE_ATTACKCITYLOSE, 
					PlayerMail::TYPE_DEFENCECITYWIN, PlayerMail::TYPE_DEFENCECITYLOSE, 
					PlayerMail::TYPE_ATTACKARMYWIN, PlayerMail::TYPE_ATTACKARMYLOSE, 
					PlayerMail::TYPE_DEFENCEARMYWIN, PlayerMail::TYPE_DEFENCEARMYLOSE, 
					PlayerMail::TYPE_ATTACKNPCWIN, PlayerMail::TYPE_ATTACKNPCLOSE, 
				),
				//系统
				5 => array(
					PlayerMail::TYPE_SYSTEM, PlayerMail::TYPE_COLLECTIONREPORT, PlayerMail::TYPE_OCCUPY, 
				),
			);*/
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 邮件列表
     * 
     * type: 1:聊天；2:联盟；3:侦查；4:战斗；5:系统
     * @return <type>
     */
	public function getListAction(){
		$pageNum = 5;
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$type = floor(@$post['type']);
		$id = floor(@$post['id']);
		$direction = floor(@$post['direction']);
		$direction = ($direction==1 ? 1 : 0);
		if(!checkRegularNumber($type) || !checkRegularNumber($id, true))
			exit;
		
		try {
			$PlayerMail = new PlayerMail;
			$cata = $PlayerMail->cataType;

			if(!isset($cata[$type]))
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			$types = $cata[$type];
			if($direction){
				$pageNum = 9999;
			}
			if($type == 1){
				$sql = 'select * from (select * from (select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.from_guild_short, b.title, b.msg, b.data, b.item, a.memo from player_mail a,player_mail_info b where player_id='.$playerId.' and (type in ('.join(',', array_diff($types, [PlayerMail::TYPE_CHATSINGLE])).') || (type = '.PlayerMail::TYPE_CHATSINGLE.' and player_id <> connect_id)) and status >= 0 and a.mail_info_id=b.id order by a.id desc) c group by type, connect_id) d';
				if($id){
					if(!$direction){//older
						$sql .= ' where id < '.$id;
					}else{
						$sql .= ' where id > '.$id;
					}
				}
				$sql .= ' order by id desc limit '.$pageNum;
				//$data = $PlayerMail->sqlGet($sql);
				//$data = $PlayerMail->sqlGet('select * from (select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.title, b.msg, b.data, b.item from player_mail a,player_mail_info b where player_id='.$playerId.' and type in ('.join(',', $types).') and status >= 0 and a.mail_info_id=b.id order by a.id desc) c group by type, connect_id order by id desc limit '.($page-1)*$pageNum.', '.$pageNum);
				//$data = $PlayerMail->sqlGet('select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.title, b.msg, b.data, b.item from (select * from player_mail where player_id='.$playerId.' and type in ('.join(',', $types).') and status >= 0 order by id desc) as a ,player_mail_info b where a.mail_info_id=b.id group by type, connect_id order by id desc limit '.($page-1)*$pageNum.', '.$pageNum);
			}else{
				$sql = 'select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.from_guild_short, b.title, b.msg, b.data, b.item, a.memo from player_mail a,player_mail_info b where player_id='.$playerId.' and type in ('.join(',', $types).') and status >= 0 and a.mail_info_id=b.id';
				if($id){
					if(!$direction){//older
						$sql .= ' and a.id < '.$id;
					}else{
						$sql .= ' and a.id > '.$id;
					}
				}
				$sql .= ' order by a.id desc limit '.$pageNum;
				//$data = $PlayerMail->sqlGet('select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.title, b.msg, b.data, b.item from player_mail a,player_mail_info b where player_id='.$playerId.' and type in ('.join(',', $types).') and status >= 0 and a.mail_info_id=b.id order by a.id desc limit '.($page-1)*$pageNum.', '.$pageNum);
				//$data = $PlayerMail->sqlGet('select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.title, b.msg, b.data, b.item from (select * from player_mail where player_id='.$playerId.' and type in ('.join(',', $types).') and status >= 0 order by id desc) as a ,player_mail_info b where a.mail_info_id=b.id order by id desc limit '.($page-1)*$pageNum.', '.$pageNum);
			}
			$data = $PlayerMail->sqlGet($sql);
			$data = $PlayerMail->adapter($data);
			foreach($data as &$_d){
				$_d['connect_id'] = $_d['connect_id'].'';
				if(!$_d['data']){
					$_d['data'] = array();
				}else{
					$_d['data'] = json_decode($_d['data'], true);
				}
				if(!$_d['item']){
					$_d['item'] = array();
				}else{
					$_item = explode(';', $_d['item']);
					foreach($_item as &$__item){
						$__item = explode(',', $__item);
						$__item = array_map('intval', $__item);
					}
					unset($__item);
					$_d['item'] = $_item;
				}
				if($_d['memo']){
					$_d['memo'] = json_decode($_d['memo'], true);
				}
				if($type == 1){//单人聊天的对方名字 or 多人聊天的发起人名字
					$connectNick = Cache::db()->hGet('chatConnectId', $_d['connect_id']);
					if(!$connectNick){
						$connectNick = '';
						if($_d['type'] == PlayerMail::TYPE_CHATSINGLE){
							$connectPlayerId = $_d['connect_id'];
						}else{
							$connectPlayerId = (new PlayerMailGroup)->getGroupCreater($_d['connect_id']);
							if(!$connectPlayerId){
								(new PlayerMailGroup)->changeCreater(0, $_d['connect_id']);
								$connectPlayerId = (new PlayerMailGroup)->getGroupCreater($_d['connect_id']);
							}
						}
						if($connectPlayerId){
							$_player = (new Player)->getByPlayerId($connectPlayerId);
							if($_player){
								$connectNick = $_player['nick'];
								Cache::db()->hSet('chatConnectId', $_d['connect_id'], $connectNick);
							}
						}
					}
					$_d['connect_nick'] = $connectNick;
				}
			}
			unset($_d);
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('mail'=>$data));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 获取战报邮件
     * 
     * 
     * @return <type>
     */
	public function getSharedMailAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$id = floor(@$post['id']);
		if(!checkRegularNumber($id, true))
			exit;
		
		try {
			$PlayerMail = new PlayerMail;
			//$data = $PlayerMail->getMailInfo($id);
			$sql = 'select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.from_guild_short, b.title, b.msg, b.data, b.item, a.memo from player_mail a,player_mail_info b where a.id="'.$id.'" and a.mail_info_id=b.id and a.type in ('.join(',', array_merge($PlayerMail->cataType[3], $PlayerMail->cataType[4])).')';
			$data = $PlayerMail->sqlGet($sql);
			$data = $PlayerMail->adapter($data);
			if($data){
				foreach($data as &$_d){
					$_d['connect_id'] = $_d['connect_id'].'';
					if(!$_d['data']){
						$_d['data'] = array();
					}else{
						$_d['data'] = json_decode($_d['data'], true);
					}
					if(!$_d['item']){
						$_d['item'] = array();
					}else{
						$_item = explode(';', $_d['item']);
						foreach($_item as &$__item){
							$__item = explode(',', $__item);
							$__item = array_map('intval', $__item);
						}
						unset($__item);
						$_d['item'] = $_item;
					}
					if($_d['memo']){
						$_d['memo'] = json_decode($_d['memo'], true);
					}
				}
				unset($_d);
				$data = $data[0];
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		
		if(!$err){
			if($data){
				echo $this->data->send(array('mail'=>$data));
			}else{
				echo $this->data->send();
			}
			
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 聊天记录
     * 
     * type: 1:单人聊天；2:多人聊天
	 * connectId: 对象（playerId或组群id）
	 * page: 
     * @return <type>
     */
	public function getChatAction(){
		$pageNum = 100;
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$type = floor(@$post['type']);
		$connectId = @$post['connectId'];
		$id = floor(@$post['id']);
		$direction = floor(@$post['direction']);
		$direction = ($direction==1 ? 1 : 0);
		//$page = floor(@$post['page']);
		if(!checkRegularNumber($type) || !checkRegularNumber($id, true))
			exit;
		if(!in_array($type, array(2, 3)))
			exit;
		if(!$connectId)
			exit;
		if($type==3 && strlen($connectId) != 13){
			exit;
		}elseif($type==2 && !checkRegularNumber($connectId)){
			exit;
		}
		
		try {
			//如果是组群信息，检查是否还在组群内
			if($type == 3){
				$PlayerMailGroup = new PlayerMailGroup;
				$playerIds = $PlayerMailGroup->getGroup($connectId);
				if(!$playerIds){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//检查自己是否在内
				if(!in_array($playerId, $playerIds)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				$playerIds = [$playerId, $connectId];
			}
			
			$PlayerMail = new PlayerMail;
			$sql = 'select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.from_guild_short, b.msg from player_mail a,player_mail_info b where player_id='.$playerId.' and type='.$type.' and connect_id="'.$connectId.'" and status >= 0 and a.mail_info_id=b.id';
			if($id){
				if(!$direction){//older
					$sql .= ' and a.id < '.$id;
				}else{
					$sql .= ' and a.id > '.$id;
				}
			}
			$sql .= ' order by a.id desc limit '.$pageNum;
			$data = $PlayerMail->sqlGet($sql);
			//$data = $PlayerMail->sqlGet('select a.id, a.type, a.connect_id, a.read_flag, a.create_time, a.status, b.from_player_id, b.from_player_name, b.from_player_avatar, b.msg from player_mail a,player_mail_info b where player_id='.$playerId.' and type='.($type+1).' and connect_id="'.$connectId.'" and status >= 0 and a.mail_info_id=b.id order by a.id desc limit '.($page-1)*$pageNum.', '.$pageNum);
			$data = $PlayerMail->adapter($data);
			foreach($data as &$_d){
				$_d['connect_id'] = $_d['connect_id'].'';
			}
			unset($_d);
			
			//循环获取成员信息
			/*$Player = new Player;
			$playerInfos = array();
			foreach($playerIds as $_playerId){
				$_player = $Player->getByPlayerId($_playerId);
				if(!$_player)
					continue;
				$playerInfos[] = array(
					'id'=>$_player['id'],
					'nick'=>$_player['nick'],
					'avatar_id'=>$_player['avatar_id'],
					'power'=>$_player['power'],
				);
			}*/
			
			//dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('mail'=>$data/*, 'memeber'=>$playerInfos*/));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 获取未读邮件数量
     * 
     * 
     * @return <type>
     */
	public function getUnreadAction(){
		$playerId = $this->getCurrentPlayerId();
		
		try {
			$PlayerMail = new PlayerMail;
			$data = $PlayerMail->sqlGet('select type, count(*), min(id), max(id) from player_mail where player_id='.$playerId.' and (type <> '.PlayerMail::TYPE_CHATSINGLE.' || (type = '.PlayerMail::TYPE_CHATSINGLE.' and player_id <> connect_id)) and read_flag=0 and status>=0 GROUP BY type');
			$ret = [];
			foreach($data as $_d){
				foreach($PlayerMail->cataType as $_key => $_type){
					if(in_array($_d['type'], $_type)){
						@$ret[$_key]['count'] += $_d['count(*)'];
						if(!isset($ret[$_key]['min_id'])){
							$ret[$_key]['min_id'] = $_d['min(id)']*1;
						}else{
							$ret[$_key]['min_id'] = min($ret[$_key]['min_id'], $_d['min(id)'])*1;
						}
						if(!isset($ret[$_key]['max_id'])){
							$ret[$_key]['max_id'] = $_d['max(id)']*1;
						}else{
							$ret[$_key]['max_id'] = max($ret[$_key]['max_id'], $_d['max(id)'])*1;
						}
						break;
					}
				}
			}
			foreach($PlayerMail->cataType as $_key => $_type){
				if(!isset($ret[$_key])){
					$ret[$_key] = [
						'count'=>0,
						'min_id'=>0,
						'max_id'=>0,
					];
				}
			}
			ksort($ret);
			//dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('mailCount'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 设置已读
     * 
	 * type: 邮件类型mail.type
	 * connectId: (type=2 or 3 时填写)
	 * mailId: (type != 2 and 3 时填写)
     */
	public function setReadAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$type = @$post['type'];
		$type = $type ? $type : 0;
		if(!in_array($type, [2, 3, 4, 5, 6, 7, 8, 9])){
			$type = 0;
			$mailIds = @$post['mailIds'];
			if(!is_array($mailIds)){
				exit;
			}
			foreach($mailIds as &$_mailId){
				$_mailId = floor($_mailId);
				if(!checkRegularNumber($_mailId))
					exit;
			}
			unset($_mailId);
		}
				
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerMail = new PlayerMail;
			if(!$type){
				$data = $PlayerMail->find(['id in ('.join(',', $mailIds).') and type in ('.PlayerMail::TYPE_CHATSINGLE.', '.PlayerMail::TYPE_CHATGROUP.')'])->toArray();
				if($data){
					$ids = Set::extract('/connect_id', $data);
					$ids = array_unique($ids);
					$PlayerMail->updateReadByConnectId($playerId, $ids, PlayerMail::READFLAG_READ);
				}
				$PlayerMail->updateReadByMailId($playerId, $mailIds, PlayerMail::READFLAG_READ);
			}else{
				$PlayerMail->updateReadByType($playerId, $PlayerMail->cataType[$type], PlayerMail::READFLAG_READ);
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 设置锁定
     * 
	 * type: 邮件类型mail.type
	 * lock: 0.解锁，1.锁定
	 * connectId: (type=2 or 3 时填写)
	 * mailId: (type != 2 and 3 时填写)
     */
	public function setLockAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		//$type = floor(@$post['type']);
		$lock = floor(@$post['lock']);
		if(!checkRegularNumber($lock, true))
			exit;
		if(!in_array($lock, array(0, 1)))
			exit;
		$lock = ($lock ? PlayerMail::STATUS_LOCK : PlayerMail::STATUS_NORMAL);
		$mailIds = @$post['mailIds'];
		if(!is_array($mailIds)){
			exit;
		}
		foreach($mailIds as &$_mailId){
			$_mailId = floor($_mailId);
			if(!checkRegularNumber($_mailId))
				exit;
		}
		unset($_mailId);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerMail = new PlayerMail;

			//获取聊天类的mailid
			$data = $PlayerMail->find(['id in ('.join(',', $mailIds).') and type in ('.PlayerMail::TYPE_CHATSINGLE.', '.PlayerMail::TYPE_CHATGROUP.')'])->toArray();
			if($data){
				$ids = Set::extract('/connect_id', $data);
				$ids = array_unique($ids);
				$PlayerMail->updateStatusByConnectId($playerId, $ids, $lock);
			}
			$PlayerMail->updateStatusByMailId($playerId, $mailIds, $lock);
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 删除邮件
     * 
	 * type: 邮件类型mail.type
	 * connectIds: 数组(type=2 or 3 时填写)
	 * mailIds: 数组(type != 2 and 3 时填写)
     */
	public function deleteAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$type = @$post['type'];
		$type = $type ? $type : 0;
		if(!in_array($type, [2, 3, 4, 5, 6, 7, 8, 9])){
			$type = 0;
			$mailIds = @$post['mailIds'];
			if(!is_array($mailIds)){
				exit;
			}
			foreach($mailIds as &$_mailId){
				$_mailId = floor($_mailId);
				if(!checkRegularNumber($_mailId))
					exit;
			}
			unset($_mailId);
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerMail = new PlayerMail;
			if(!$type){
				//获取聊天类的mailid
				$data = $PlayerMail->find(['id in ('.join(',', $mailIds).') and type in ('.PlayerMail::TYPE_CHATSINGLE.', '.PlayerMail::TYPE_CHATGROUP.')'])->toArray();
				if($data){
					$ids = Set::extract('/connect_id', $data);
					$ids = array_unique($ids);
					$PlayerMail->updateStatusByConnectId($playerId, $ids, -1);
					$mailIds = array_diff($mailIds, Set::extract('/id', $data));
				}
				if($mailIds){
					//过滤有附件且未领取的有奖
					$data = $PlayerMail->find(['id in ('.join(',', $mailIds).') and read_flag in (0, 1) and type in ('.join(',', $PlayerMail->cataType[5]).')'])->toArray();
					if($data){
						$ids = Set::extract('/mail_info_id', $data);
						$data2 = (new PlayerMailInfo)->find(['id in ('.join(',', $ids).') and item > ""'])->toArray();
						$infoIds = Set::extract('/id', $data2);
						$ids2 = [];
						foreach($data as $_d){
							if(in_array($_d['mail_info_id'], $infoIds)){
								$ids2[] = $_d['id'];
							}
						}
						$mailIds = array_diff($mailIds, $ids2);
					}
					$PlayerMail->updateStatusByMailId($playerId, $mailIds, -1);
				}
			}else{
				$PlayerMail->updateStatusByType($playerId, $PlayerMail->cataType[$type], -1);
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 写邮件
     * 
	 * type: 1.单人；2.多人；3.联盟全体；4.单人（名字）
	 * toPlayer: （type=1时）填写对象playerIds数组
						（type=2时）如果填写字符串，表示groupId
						（type=2时）如果填写playerId数组，表示发送对象nicks，将创建新多人会话
						（type=4时）填写对象对象玩家名字数组
	 * msg：邮件内容
     */
	public function chatAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$type = floor(@$post['type']);
		$msg = trim(@$post['msg']);
		$toPlayer = @$post['toPlayer'];
		if(!checkRegularNumber($type) || !in_array($type, array(1, 2, 3, 4)))
			exit;
		if('' == $msg)
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取发信人联盟缩写
			$fromPlayerName = $player['nick'];
			if($player['guild_id']){
				$guild = (new Guild)->getGuildInfo($player['guild_id']);
				//$fromPlayerName = '('.$guild['short_name'].')'.$fromPlayerName;
				$guildShortName = $guild['short_name'];
			}else{
				$guildShortName = '';
			}
			$PlayerMail = new PlayerMail;
			
			//过滤邮件msg
			$msg = mb_substr($msg, 0, 100);
			
			$SensitiveWord = new SensitiveWord;
			if($SensitiveWord->checkSensitiveContent($msg, 1)){
//				throw new Exception(10291);//内包含敏感词汇  //Jira 2302
                $msg = $SensitiveWord->filterWord($msg);//敏感字
			}
			
			//发送邮件
			$Player = new Player;
			switch($type){
				case 4://单人(名字)
					//查找名字
					if(!is_array($toPlayer)){
						$toPlayer = array($toPlayer);
					}
					$toplayerIds = array();
					foreach($toPlayer as $_tp){
						$_tp = $Player->getByPlayerNick($_tp);
						if(!$_tp)
							continue;
						$toplayerIds[] = $_tp['id'];
					}
					$toplayerIds = array_diff($toplayerIds, array($playerId));
					if(!$toplayerIds){
						throw new Exception(10126);
					}
					$toPlayer = array_unique($toplayerIds);
					$searchFlag = true;
				case 1://单人
					if(!@$searchFlag){
						if(!is_array($toPlayer)){
							$toPlayer = array($toPlayer);
						}
					}
					$toPlayer = array_diff($toPlayer, array($playerId));
					$findFlag = false;
					foreach($toPlayer as $_i=>$_playerId){
						if(!@$searchFlag){
							//检查对象存在
							if(!checkRegularNumber($_playerId))
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							if(!$Player->getByPlayerId($_playerId)){
								unset($toPlayer[$_i]);
								//throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
							}
						}
						$findFlag = true;
					}
					if(!$findFlag)
						throw new Exception(10127);
					if(!$PlayerMail->sendSingle($toPlayer, $playerId, $fromPlayerName, $player['avatar_id'], $guildShortName, $msg)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				break;
				case 2://组群
					if(is_string($toPlayer)){
						if(!$PlayerMail->sendGroup($toPlayer, $playerId, $fromPlayerName, $player['avatar_id'], $guildShortName, $msg)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}elseif(is_array($toPlayer)){
						$toPlayer = array_unique($toPlayer);
						//检查对象存在
						$playerNames = array();
						$playerIds = array();
						foreach($toPlayer as $_i => $_playerName){
							if(!$_player = $Player->getByPlayerNick($_playerName)){
								continue;
							}
							//检查是否为盟友
							/*if($_player['guild_id'] != $player['guild_id']){
								unset($toPlayer[$_i]);
								continue;
							}*/
							$playerIds[] = $_player['id'];
							$playerNames[] = $_player['nick'];
						}
						if(!$playerIds || (count($playerIds) == 1 && $playerIds[0] == $playerId)){
							throw new Exception(10128);
						}
						if(!in_array($playerId, $playerIds)){
							$playerIds[] = $playerId;
						}
						//增加组群
						$PlayerMailGroup = new PlayerMailGroup;
						$groupId = $PlayerMailGroup->newGroup($playerIds, $playerId);
						if(!$groupId)
							return false;
						
						//发送增加人员邮件
						$addNoticeMsg = $fromPlayerName.' 将 '.join('、', $playerNames).' 加入聊天';
						if(!$PlayerMail->sendGroupPlayer($groupId, array_diff($playerIds, array($playerId)), $addNoticeMsg, PlayerMail::READFLAG_UNREAD)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						$addNoticeMsg = '你将 '.join('、', $playerNames).' 加入聊天';
						if(!$PlayerMail->sendGroupPlayer($groupId, array($playerId), $addNoticeMsg, PlayerMail::READFLAG_READ)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						//发送聊天
						if(!$groupId = $PlayerMail->sendGroup($groupId, $playerId, $fromPlayerName, $player['avatar_id'], $guild['short_name'], $msg)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
				break;
				case 3://联盟全体
					$msg = '（对全体发送）'.$msg;
					//获取所有成员
					$PlayerGuild = new PlayerGuild;
					$players = $PlayerGuild->getAllGuildMember($player['guild_id']);
					if(!$players){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					$playerIds = array_keys($players);
					//$playerIds = array_diff($playerIds, array($playerId));
					//发送邮件
					if(!$PlayerMail->sendSingle($playerIds, $playerId, $fromPlayerName, $player['avatar_id'], $guildShortName, $msg)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				break;
				default:
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 发送集结邀请邮件
     * 
     * 
     * @return <type>
     */
	public function sendGatherMailAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$toPlayer = @$post['toPlayer'];
		$queueId = floor(@$post['queueId']);
		if(!is_array($toPlayer)){
			exit;
		}
		if(!checkRegularNumber($queueId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取公会成员
			$PlayerGuild = new PlayerGuild;
			$members = $PlayerGuild->getAllGuildMember($player['guild_id']);
			if(!$members)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$memberIds = array_keys($members);
			//循环检查玩家是否为盟友
			$playerIds = [];
			$toPlayer = array_unique($toPlayer);
			foreach($toPlayer as $_playerId){
				if(!checkRegularNumber($_playerId)) continue;
				if(!in_array($_playerId, $memberIds)) continue;
				$playerIds[] = $_playerId;
			}
			
			//获取集结queue
			$ppq = PlayerProjectQueue::findFirst(['id='.$queueId.' and type='.PlayerProjectQueue::TYPE_GATHER_WAIT.' and status=1']);
			if(!$ppq){
				throw new Exception('没有找到队伍');
			}
			
			//获取army
			$PlayerArmy = new PlayerArmy;
			$pa = $PlayerArmy->getByArmyId($playerId, $ppq->army_id);
			if(!$pa){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$PlayerArmyUnit = new PlayerArmyUnit;
			$pau = $PlayerArmyUnit->getByArmyId($playerId, $ppq->army_id);
			if(!$pau){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取武将数量
			//获取士兵种类数量
			$Soldier = new Soldier;
			$generalNum = 0;
			$soldier = [];
			foreach($pau as $_pau){
				if($_pau['general_id']){
					$generalNum++;
				}
				if($_pau['soldier_id'] && $_pau['soldier_num']){
					$_soldier = $Soldier->dicGetOne($_pau['soldier_id']);
					if(!$_soldier){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					@$soldier[$_soldier['soldier_type']] += $_pau['soldier_num'];
				}
			}
			
			//计算战斗力
			$power = $PlayerArmy->getPower($playerId, $ppq->army_id);
			
			//计算负重
			$weight = $PlayerArmyUnit->calculateWeight($playerId, $ppq->army_id);

			//目标信息
			$targetInfo = json_decode($ppq->target_info, true);
			$targetInfo['nick'] = '';
			if($targetInfo['type'] == 'attackPlayer'){
				$_player = (new Player)->getByPlayerId($ppq->target_player_id);
				$targetInfo['nick'] = $_player['nick'];
			}
			$targetInfo['guild_name'] = '';
			if(in_array($targetInfo['type'], ['attackPlayer', 'attackBase']) && $targetInfo['to_guild_id']){
				$_guild = (new Guild)->getGuildInfo($targetInfo['to_guild_id']);
				$targetInfo['guild_name'] = $_guild['short_name'];
			}
			
			//发送邮件
			$PlayerMail = new PlayerMail;
			if(!$PlayerMail->sendSystem($playerIds, PlayerMail::TYPE_GUILDGATHER, '', '', 0, [
				'from_player_id'=>$playerId, 
				'from_player_name'=>$player['nick'], 
				'from_player_avatar'=>$player['avatar_id'], 
				'x'=>$player['x'], 
				'y'=>$player['y'],
				'end_time'=>strtotime($ppq->end_time),
				'leader_general_id'=>$pa['leader_general_id'],
				'general_num'=>$generalNum,
				'power'=>$power,
				'soldier'=>$soldier,
				'weight'=>$weight,
				'queue_id'=>$queueId,
				'army_id'=>$ppq->army_id,
				'target_info'=>$targetInfo,
			])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 组群修改成员(创建者使用)（废除）
     * 
	 * groupId: 群组号
	 * playerIds: 成员数组
     */
	public function groupChangePlayerAction(){
		return;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$groupId = @$post['groupId'];
		$playerIds = @$post['playerIds'];
		if(strlen($groupId) != 13){
			exit;
		}
		if(!is_array($playerIds)){
			exit;
		}
		$playerIds = array_unique($playerIds);
		foreach($playerIds as $_playerId){
			if(!checkRegularNumber($_playerId)){
				exit;
			}
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查是否为创建者
			$PlayerMailGroup = new PlayerMailGroup;
			$createId = $PlayerMailGroup->getGroupCreater($groupId);
			if($createId != $playerId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查自己是否在
			if(!in_array($playerId, $playerIds)){
				$playerIds[] = $playerId;
			}
			
			//获取组群成员
			$oldPlayerIds = $PlayerMailGroup->getGroup($groupId);
			if(!$oldPlayerIds){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//增加成员
			$Player = new Player;
			$addPlayerNames = array();
			$addPlayerIds = array();
			foreach($playerIds as $_i => $_playerId){
				if(in_array($_playerId, $oldPlayerIds)) continue;
				//检查玩家
				if(!$_player = $Player->getByPlayerId($_playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//检查是否为盟友
				if($_player['guild_id'] != $player['guild_id']){
					unset($playerIds[$_i]);
					continue;
				}
				if(!$PlayerMailGroup->addMemeber($groupId, $_playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$addPlayerNames[] = $_player['nick'];
				$addPlayerIds[] = $_playerId;
			}
			
			//删除成员
			$delPlayerNames = array();
			$delPlayerIds = array();
			foreach($oldPlayerIds as $_playerId){
				if(in_array($_playerId, $playerIds)) continue;
				if(!$PlayerMailGroup->deleteMemeber($groupId, $_playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$delPlayerNames[] = $_player['nick'];
				$delPlayerIds[] = $_playerId;
			}
			
			//发送增加成员邮件
			if($addPlayerIds){
				$PlayerMail = new PlayerMail;
				$addNoticeMsg = $player['nick'].' 将 '.join('、', $addPlayerNames).' 加入聊天';
				if(!$PlayerMail->sendGroupPlayer($groupId, array_diff($playerIds, array($playerId)), $addNoticeMsg, PlayerMail::READFLAG_UNREAD)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$addNoticeMsg = '你将 '.join('、', $addPlayerNames).' 加入聊天';
				if(!$PlayerMail->sendGroupPlayer($groupId, array($playerId), $addNoticeMsg, PlayerMail::READFLAG_READ)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//发送删除成员邮件
			if($delPlayerIds){
				$delNoticeMsg = $player['nick'].' 将 '.join('、', $delPlayerNames).' 移出聊天';
				if(!$PlayerMail->sendGroupPlayer($groupId, array_diff($playerIds, array($playerId)), $delNoticeMsg, PlayerMail::READFLAG_UNREAD)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$delNoticeMsg = '你将 '.join('、', $delPlayerNames).' 移出聊天';
				if(!$PlayerMail->sendGroupPlayer($groupId, array($playerId), $delNoticeMsg, PlayerMail::READFLAG_READ)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//给删除成员发送提醒邮件
				$delNoticeMsg = '你已被 '.$player['nick'].' 移出聊天';
				if(!$PlayerMail->sendGroupPlayer($groupId, $delPlayerIds, $delNoticeMsg, PlayerMail::READFLAG_UNREAD)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 增加组群成员(组员使用)
     * 
	 * groupId: 群组号
	 * playerIds: 成员数组
     */
	public function groupAddPlayerAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$groupId = @$post['groupId'];
		$playerIds = @$post['playerIds'];
		if(strlen($groupId) != 13){
			exit;
		}
		if(!is_array($playerIds)){
			exit;
		}
		$playerIds = array_unique($playerIds);
		foreach($playerIds as $_playerId){
			if(!checkRegularNumber($_playerId)){
				exit;
			}
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerMailGroup = new PlayerMailGroup;
			
			//获取组群成员
			$oldPlayerIds = $PlayerMailGroup->getGroup($groupId);
			if(!$oldPlayerIds){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查玩家是否在列表中
			if(!in_array($playerId, $oldPlayerIds)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//增加成员
			$Player = new Player;
			$addPlayerNames = array();
			$addPlayerIds = array();
			foreach($playerIds as $_i => $_playerId){
				if(in_array($_playerId, $oldPlayerIds)) continue;
				//检查玩家
				if(!$_player = $Player->getByPlayerId($_playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//检查是否为盟友
				if($_player['guild_id'] != $player['guild_id']){
					unset($playerIds[$_i]);
					continue;
				}
				if(!$PlayerMailGroup->addMemeber($groupId, $_playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$addPlayerNames[] = $_player['nick'];
				$addPlayerIds[] = $_playerId;
			}
			
			if(!$addPlayerIds){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			//发送增加成员邮件
			if($addPlayerIds){
				$PlayerMail = new PlayerMail;
				$addNoticeMsg = $player['nick'].' 将 '.join('、', $addPlayerNames).' 加入聊天';
				if(!$PlayerMail->sendGroupPlayer($groupId, array_diff($playerIds, array($playerId)), $addNoticeMsg, PlayerMail::READFLAG_UNREAD)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$addNoticeMsg = '你将 '.join('、', $addPlayerNames).' 加入聊天';
				if(!$PlayerMail->sendGroupPlayer($groupId, array($playerId), $addNoticeMsg, PlayerMail::READFLAG_READ)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
						
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		$playerIds = $PlayerMailGroup->getGroup($groupId);
		if(!$playerIds){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//循环获取成员信息
		$playerInfos = array();
		foreach($playerIds as $_playerId){
			$_player = $Player->getByPlayerId($_playerId);
			if(!$_player)
				continue;
			$playerInfos[] = array(
				'id'=>$_player['id'],
				'nick'=>$_player['nick'],
				'avatar_id'=>$_player['avatar_id'],
				'power'=>$_player['power'],
			);
		}
		
		if(!$err){
			echo $this->data->send(array('groupId'=>$groupId, 'groupMember'=>$playerInfos));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 退出组群
     * 
	 * groupId: 群组号
     */
	public function groupQuitAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$groupId = @$post['groupId'];
		if(strlen($groupId) != 13){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerMailGroup = new PlayerMailGroup;
			
			//获取组群成员
			$oldPlayerIds = $PlayerMailGroup->getGroup($groupId);
			if(!$oldPlayerIds){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			
			//检查玩家是否在列表中
			if(!in_array($playerId, $oldPlayerIds)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerMail = new PlayerMail;
			$delNoticeMsg = $player['nick'].' 已经退出组群';
			if(!$PlayerMail->sendGroupPlayer($groupId, array_diff($oldPlayerIds, array($playerId)), $delNoticeMsg, PlayerMail::READFLAG_UNREAD)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//给删除成员发送提醒邮件
			$delNoticeMsg = '你已经退出组群';
			if(!$PlayerMail->sendGroupPlayer($groupId, array($playerId), $delNoticeMsg, PlayerMail::READFLAG_UNREAD)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if(!$PlayerMailGroup->deleteMemeber($groupId, $playerId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//如果创建者退出，随机指定一个最早的成员
			$groupCreaterId = $PlayerMailGroup->getGroupCreater($groupId);
			if(!$groupCreaterId){
				$newCreaterId = 0;
				foreach($oldPlayerIds as $_playerId){
					if($_playerId != $playerId){
						$newCreaterId = $_playerId;
						break;
					}
				}
				if($newCreaterId){
					$PlayerMailGroup->changeCreater($newCreaterId, $groupId);
					//$PlayerMailGroup->updateAll(['is_creater'=>1], ['group_id'=>"'".$groupId."'", 'player_id'=>$newCreaterId]);
					//Cache::db()->hDel('chatConnectId', $groupId);
				}
			}
			
			//删除邮件
			$PlayerMail->updateStatusByConnectId($playerId, [$groupId], -1, true);
						
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
				
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 收取道具
     * 
	 * mailIds: 邮件id数组
     * @return <type>
     */
	public function fetchItemAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$mailIds = @$post['mailIds'];
		if(!is_array($mailIds) || !$mailIds){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerMail = new PlayerMail;
			$Drop = new Drop;
			//循环获取邮件
			$data = $PlayerMail->sqlGet('select a.id, a.type, a.connect_id, a.read_flag, b.item from player_mail a,player_mail_info b where player_id='.$playerId.' and a.id in ('.join(',', $mailIds).') and read_flag < '.PlayerMail::READFLAG_GET.' and status >='.PlayerMail::STATUS_NORMAL.' and a.mail_info_id=b.id');
			foreach($data as $_d){
				//检查是否有附件
				if(!$_d['item'] || !in_array($_d['type'], $PlayerMail->cataType[5]))
					continue;
				
				//获取附件道具
				$_item = explode(';', $_d['item']);
				$gainItems = array();
				foreach($_item as $__item){
					list($_type, $_itemId, $_num) = explode(',', $__item);
					@$gainItems[$_type][$_itemId] += $_num;
				}
				if(!$Drop->_gain($playerId, $gainItems, 'mail:'.$_d['type'].'|'.$_d['id'])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//更新邮件flag
				if(!$PlayerMail->updateReadByMailId($playerId, $_d['id'], PlayerMail::READFLAG_GET)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 写邮件给联盟全体
     * 
	 * msg：邮件内容
     */
	/*public function sendGuildAllAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$msg = trim(@$post['msg']);
		if(!$msg)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取联盟
			$guildId = $player['guild_id'];
			
			//获取发信人联盟缩写
			$guild = (new Guild)->getGuildInfo($player['guild_id']);
			if(!$guild){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$fromPlayerName = $player['nick'];
			$guildShortName = $guild['short_name'];
			
			//获取所有成员
			$PlayerGuild = new PlayerGuild;
			$playerGuild = $PlayerGuild->getAllGuildMember($guildId);
			if(!$playerGuild){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$playerIds = Set::extract('/player_id', $playerGuild);
			
			//过滤邮件msg
			$msg = '（对全体发送）'.mb_substr($msg, 0, 100);
			if($SensitiveWord->checkSensitiveContent($msg, 1)){
				throw new Exception(10292);//内包含敏感词汇
			}
			
			//发送邮件
			$PlayerMail = new PlayerMail;
			if(!$PlayerMail->sendSingle($playerIds, $playerId, $fromPlayerName, $player['avatar_id'], $guildShortName, $msg)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}*/

	/**
     * 获取组成员
     * 
	 * groupId: 群组号
     */
	 public function getGroupMemberAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$groupId = @$post['groupId'];
		if(strlen($groupId) != 13){
			exit;
		}
		

		try {
			//获取组成员
			$PlayerMailGroup = new PlayerMailGroup;
			$playerIds = $PlayerMailGroup->getGroup($groupId);
			if(!$playerIds){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查自己是否在内
			if(!in_array($playerId, $playerIds)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//循环获取成员信息
			$Player = new Player;
			$playerInfos = array();
			foreach($playerIds as $_playerId){
				$_player = $Player->getByPlayerId($_playerId);
				if(!$_player)
					continue;
				$playerInfos[] = array(
					'id'=>$_player['id'],
					'nick'=>$_player['nick'],
					'avatar_id'=>$_player['avatar_id'],
					'power'=>$_player['power'],
				);
			}
			
			
			//dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('groupId'=>$groupId, 'groupMember'=>$playerInfos));
		}else{
			echo $this->data->sendErr($err);
		}
	}
}