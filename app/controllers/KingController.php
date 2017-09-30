<?php
//王战
use Phalcon\Mvc\View;
class KingController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 得到王战状态和信息（king表信息：时间，王座联盟，国王）
     * 
     * 
     * @return <type>
     */
	public function getInfoAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];

		$ret = [];
		$now = time();
		try {
			//获取王战信息
			$King = new King;
			$king = $King->findFirst(['order'=>'id desc']);
			if(!$king){
				$king['start_time'] = 0;
				$king['end_time'] = 0;
			}else{
				$king = $king->toArray();
			}
			$king = filterFields($King->adapter([$king]), true, $King->blacklist)[0];
			
			$king['nick'] = '';
			$king['avatar_id'] = 0;
			if($king['player_id']){
				$kingPlayer = (new Player)->getByPlayerId($king['player_id']);
				$king['nick'] = $kingPlayer['nick'];
				$king['avatar_id'] = $kingPlayer['avatar_id'];
			}
			$king['guild_name'] = '';
			if($king['guild_id']){
				$kingGuild             = (new Guild)->getGuildInfo($king['guild_id']);
                $king['guild_name']    = $kingGuild['name'];
                $king['guild_icon_id'] = $kingGuild['icon_id'];
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('King'=>$king));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 城寨信息（kingTown表：占领联盟信息）
     * 
     * 
     * @return <type>
     */
	public function getTownInfoAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];

		$ret = [];
		$now = time();
		try {
			//获取王战信息
			$KingTown = new KingTown;
			$kingTown = $KingTown->find()->toArray();
			$kingTown = filterFields($kingTown, true, ['create_time', 'update_time', 'rowversion']);
			
			$guildInfo = [];
			$Guild = new Guild;
			foreach($kingTown as &$_t){
                $_t['guild_short_name'] = '';
				if($_t['guild_id']){
					if(!@$guildInfo[$_t['guild_id']]){
						$guild = $Guild->getGuildInfo($_t['guild_id']);
						$guildInfo[$_t['guild_id']] = $guild['short_name'];
					}
					$_t['guild_short_name'] = $guildInfo[$_t['guild_id']];
				}
			}
			unset($_t);
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('kingTown'=>$kingTown));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 获取城寨军团信息
     * 
     * 
     * @return <type>
     */
	public function getTownArmyAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$postData       = getPost();
        $x = $postData['x'];
		$y = $postData['y'];
		if(!checkRegularNumber($x) || !checkRegularNumber($y)){
			exit;
		}

		$ret = [];
		$now = time();
		try {
			//获取王战信息
			$KingTown = new KingTown;
			$kingTown = $KingTown->getByXy($x, $y);
			if($kingTown['guild_id'] != $player['guild_id']){
				throw new Exception(10484);//该城寨不属于您所在联盟
			}
			
			//获取部队
			$toMap = (new Map)->getByXy($x, $y);
			if(!$toMap){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$army = (new PlayerProjectQueue)->getGuildBuildArmy($toMap);
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('kingTownArmy'=>$army));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 获取各联盟王战积分情况
     * 
     * 
     * @return <type>
     */
	public function getScoreAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];

		$ret = [];
		$now = time();
		try {
			//获取王战信息
			$King = new King;
			$king = $King->getCurrentBattle();
			if(!$king){
				$king = $King->getLastBattle();
				if(!$king){
					throw new Exception(10321);//当前没有王战
				}
			}
			$king = filterFields($King->adapter([$king]), true, $King->blacklist)[0];
			
			$GuildKingPoint = new GuildKingPoint;
			//获取分值表
			$gkp = filterFields($GuildKingPoint->adapter($GuildKingPoint->find()->toArray()), true, $GuildKingPoint->blacklist);
			
			foreach($gkp as $_r){
				$ret[$_r['guild_id']] = [
					'guild_id' 	=> $_r['guild_id'],
					'point' 	=> $_r['point'],
					'add_begin_time' => $now,//动态积分增长开始时间
					'add_per_second' =>	0,//每秒增长积分
				];
			}
			
			//获取动态
			$KingTown = new KingTown;
			$kingtown = $KingTown->find();
			$townNum = [];
			foreach($kingtown as $_kingtown){
				if($_kingtown->guild_id)
					@$townNum[$_kingtown->guild_id]++;
			}
			foreach($kingtown as $_kt){
				if(!$_kt->guild_id) continue;
				if($_kt->type == KingTown::TYPE_BIG){
					$perPoint = KingTown::POINT_BY_BIGTOWN * min(KingTown::MAX_RATE, $townNum[$_kt->guild_id]);
				}else{
					$perPoint = KingTown::POINT_BY_SMALLTOWN * min(KingTown::MAX_RATE, $townNum[$_kt->guild_id]);
				}
				if(!isset($ret[$_kt->guild_id])){
					$ret[$_kt->guild_id] = [
						'guild_id' 	=> $_kt->guild_id,
						'point' 	=> 0,
						'add_begin_time' => $now,
						'add_per_second' =>	0,
					];
				}
				$ret[$_kt->guild_id]['point'] += max(0, ($now - strtotime($_kt->point_start_time))) * $perPoint;
				$ret[$_kt->guild_id]['add_per_second'] += $perPoint;
			}
			
			//获取联盟名字
			$Guild = new Guild;
			foreach($ret as &$_r){
				$guild = $Guild->getGuildInfo($_r['guild_id']);
				$_r['guild_name'] = $guild['name'];
				$_r['guild_icon'] = $guild['icon_id'];
				$_r['guild_power'] = $guild['guild_power'];
			}
			unset($_r);
			
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('GuildKingPoint'=>$ret, 'King'=>$king));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 获取所有官职玩家
     * 
     * 
     * @return <type>
     */
	public function getJobAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];

		try {
			$ret = [];
			//获取王战信息
			$Player = new Player;
			$player = $Player->find(['job>0'])->toArray();
			foreach($player as $_p){
				$ret[$_p['job']] = [
					'id'=>$_p['id'],
					'nick'=>$_p['nick'],
					'avatar_id'=>$_p['avatar_id'],
					'job'=>$_p['job'],
					'time'=>strtotime($_p['appointment_time']),
				];
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('Job'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 刷新
     * 
     * $_POST['type'] 1：免费，2 : 道具/付费
     * @return <type>
     */
	/*public function gotoTownAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId)){
			exit;
		}
		if($x < 12 || $x > 1236 || $y < 12 || $y > 1236)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取地图点信息
			$Map = new Map;
			$map = $Map->getByXy($x, $y);
			if(!$map)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断是否城寨
			if($map['map_element_origin_id'] != 15 || $map['player_id'] == $playerId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//无盟玩家不能占领
			if(!$player['guild_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断对方是否带套子
			if((new PlayerBuff)->isAvoidBattle($map['player_id'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$this->doBeforeGoOut($playerId, $armyId, true);
			
			//计算行军时间
			$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $x, $y, 3, $armyId);
			if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//建立队列
			$pm = $Map->getByXy($player['x'], $player['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $player['x'],
				'from_y' => $player['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
			];
			$PlayerProjectQueue = new PlayerProjectQueue;
			$PlayerProjectQueue->addQueue($playerId, $player['guild_id'], $map['player_id'], PlayerProjectQueue::TYPE_CITYBATTLE_GOTO, $needTime, $armyId, [], $extraData);
			
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
     * 任命国王
     *
     * 使用方法如下
     *
     * king/appointKing
     * postData: {"target_player_id":333}
     * return:{}
     */
	public function appointKingAction(){
        $playerId = $this->getCurrentPlayerId();
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' . $playerId;//锁定
        Cache::lock($lockKey);

        $player         = $this->getCurrentPlayer();
        $postData       = getPost();
        $targetPlayerId = $postData['target_player_id'];
        $guildId        = $player['guild_id'];
        if(!$guildId) {
            $errCode = 100081;//你没权限操作
            goto sendErr;
        }

        $PlayerGuild = new PlayerGuild;
        $King        = new King;
        $Player      = new Player;

        $playerGuild       = $PlayerGuild->getByPlayerId($playerId);
        $targetPlayerGuild = $PlayerGuild->getByPlayerId($targetPlayerId);
        $targetPlayer      = $Player->getByPlayerId($targetPlayerId);

        $king = $King->findFirst(['order'=>'id desc']);
        if(empty($king)) {
            $errCode = 10476;//[任命国王]没有发生过国王战
            goto sendErr;
        }
        if($king->guild_id!=$guildId) {
            $errCode = 10477;//[任命国王]当前联盟并非最终胜利方，不能任命国王
            goto sendErr;
        }
        if($playerGuild['rank']<PlayerGuild::RANK_R5){
            $errCode = 10478;//[任命国王]只有盟主才有权限任命国王
            goto sendErr;
        }
        if($targetPlayerGuild['guild_id']!=$guildId) {
            $errCode = 10479;//[任命国王]被任命的不是本盟的
            goto sendErr;
        }
        if($king->status!=King::STATUS_FINISH) {
            $errCode = 10480;//[任命国王]当前不在任命期间
            goto sendErr;
        }

        //任命逻辑
        if($King->upCurrentKing($king->id, $targetPlayerId)) {
            $Player->alter($targetPlayerId, ['job' => 1]);
            //增加bufftemp
            $KingAppoint = new KingAppoint;
            $kingAppoint = $KingAppoint->dicGetOne(1);
            $dropId      = $kingAppoint['add_buff'];
            if($dropId){
                (new Drop)->gain($targetPlayerId, $dropId, 1, '任命国王');
            }
            $data = ['Type'=>'appoint_king', 'Data'=>['king_player_id'=>$targetPlayerId,'king_nick'=>$targetPlayer['nick']]];
            socketSend($data);
			
			$data = ['king_nick'=>$targetPlayer['nick'], 'target_player_job'=>1];
			(new PlayerMail)->sendSystem([$targetPlayer['id']], PlayerMail::TYPE_KINGAPPOINTKING, 'system email', '', 0, $data);
        }

        echo $this->data->send();
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
	/**
	 * 获取历任国王
	 *
	 * 使用方法如下：
	 * king/getHistoryKing
	 * postData:{}
	 * return:{}
	 */
	public function getHistoryKingAction(){
		$player = $this->getCurrentPlayer();
		$King = new King;
		$historyKing = $King->getHistoryKing();
		echo $this->data->send($historyKing);
		exit;
	}
    /**
     * 任命官职
     * 
     * 
     * @return <type>
     */
	public function appointmentAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$jobId = floor(@$post['jobId']);//0-撤命
		$nick = @$post['nick'];
		if(!checkRegularNumber($jobId))
			exit;
		
		$Player = new Player;
		$Drop = new Drop;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查是否是国王
			if($player['job']*1 !== 1){
				throw new Exception(10322);//只有国王可以任命官职
			}
			
			
			//卸任原该官职人
			if($jobId){
				$oPlayer = $Player->getByJob($jobId);
				if($oPlayer){
					//检查任命cd
					$cd = (new Starting)->getValueByKey("change_title_time_cd")*3600;
					if(time() - $oPlayer['appointment_time'] < $cd){
						throw new Exception(10323);//每8小时只能修改一次官职
					}
					if(!(new KingAppoint)->cancelAppoint($oPlayer)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			
			//检查任命对象是否存在
			$targetPlayer = $Player->getByPlayerNick($nick);
			if(!$targetPlayer){
				throw new Exception(10324);//未能找到玩家
			}
			if($targetPlayer['job']*1 === 1){
				throw new Exception(10325);//国王无法担任其他官职
			}
			
			//检查官职是否存在且不为国王
			$KingAppoint = new KingAppoint;
			$kingAppoint = $KingAppoint->dicGetOne($jobId);
			if(!$kingAppoint){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($jobId === 1){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查任命对象是否已有官职，如果已有，撤销官职
			if($targetPlayer['job']){
				//检查任命cd
				$cd = (new Starting)->getValueByKey("change_title_time_cd")*3600;
				if(time() - $targetPlayer['appointment_time'] < $cd){
					throw new Exception(10323);//每4小时只能修改一次官职
				}
				(new KingAppoint)->cancelAppoint($targetPlayer);//撤销官职
				//解析原job的drop的buffTempId
				/*$_kingAppoint = $KingAppoint->dicGetOne($targetPlayer['job']);
				if(!$_kingAppoint){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$_dropId = $_kingAppoint['add_buff'];
				
				//删除bufftemp
				foreach($_dropId as $__dropId){
					$_drop = $Drop->dicGetOne($__dropId);
					$buffTempIds = [];
					foreach($_drop['drop_data'] as $_d){
						$buffTempIds[] = $_d[1];
					}
				}
				if($buffTempIds){
					$PlayerBuffTemp = new PlayerBuffTemp;
					$PlayerBuffTemp->delByTempId($targetPlayer['id'], $buffTempIds);
				}
					//删除job
				$Player->alter($targetPlayer['id'], ['job'=>0]);
				*/
				
			}
			if($jobId){
				//增加bufftemp
				$dropId = $kingAppoint['add_buff'];
				if($dropId){
					if(!$ret = $Drop->gain($targetPlayer['id'], $dropId, 1, '官职任命')){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				//更新job
				$Player->alter($targetPlayer['id'], ['job'=>$jobId, 'appointment_time'=>"'".date('Y-m-d H:i:s')."'"]);
                $data['king_nick']          = $player['nick'];
                $data['target_player_nick'] = $targetPlayer['nick'];
                $data['target_player_job']  = $jobId;
                (new PlayerMail)->sendSystem([$targetPlayer['id']], PlayerMail::TYPE_KINGAPPOINT, 'system email', '', 0, $data);
                $data['target_player_id']   = $targetPlayer['id'];
                (new RoundMessage)->addNew($targetPlayer['id'], ['type'=>6, 'data'=>$data]);//走马灯公告
			}
			
			//更新任命cd
			//$Player->alter($playerId, ['appointment_time'=>"'".date('Y-m-d H:i:s')."'"]);
			
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
	 * 发礼包
	 *
	 * 使用方法如下
	 * ```php
	 * king/kingGift
	 * postData:{"targetPlayerId":1,"giftType":1} type:1守卫 2援助 3征战
	 * returnData:{}
	 * ```
	 */
	public function kingGiftAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$targetPlayerId = floor(@$post['targetPlayerId']);
		$giftType = @$post['giftType'];
		if(!checkRegularNumber($targetPlayerId) || !checkRegularNumber($giftType) )
			exit;

		$Player = new Player;
		$KingPlayerReward = new KingPlayerReward;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$King = new King;
		$king = $King->getCurrentBattle();
		if($king){
			$err = 10481;//国王战进行中
        	goto SendErr;
		}

		if($player['job']!=1){
			$err = 10482;//玩家不是国王
        	goto SendErr;
		}

		$hasGet = $KingPlayerReward->getByPlayerId($targetPlayerId);
		if($hasGet){
			$err = 10483;//玩家已领取过礼包
        	goto SendErr;
		}

		$leftGiftList = $KingPlayerReward->clacLeftGiftNum();

        $toPlayerIds = [$targetPlayerId];
        $type        = PlayerMail::TYPE_KINGGIFT;
        $title       = 'king gift email';
        $msg         = '';
        $time        = 0;

        $dropId = 0;
        switch ($giftType) {
        	case 1:
        		if($leftGiftList[1]>0){
        			$dropId = 470001;
        		}else{
        			$err = 10463;//礼包不足
        			goto SendErr;
        		}
        		break;
        	case 2:
        		if($leftGiftList[2]>0){
        			$dropId = 470002;
        		}else{
        			$err = 10464;//礼包不足
        			goto SendErr;
        		}
        		break;
        	case 3:
        		if($leftGiftList[3]>0){
        			$dropId = 470003;
        		}else{
        			$err = 10465;//礼包不足
        			goto SendErr;
        		}
        		break;
        }

        $PlayerMail = new PlayerMail;
        $item = $PlayerMail->newItemByDrop($targetPlayerId, [$dropId]);
        $PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, ['kingName'=>$player['nick']], $item, []);
        $KingPlayerReward->addNew(['playerId'=>$targetPlayerId, 'type'=>$giftType]);

		SendErr: Cache::unlock($lockKey);
		
		if(empty($err)){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 剩余礼包数量
	 *
	 * 使用方法如下
	 * ```php
	 * king/getLeftGiftList/
	 * postData:{}
	 * returnData:{}
	 * ```
	 */
	function getLeftGiftListAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];

		if($player['job']!=1){
			$errCode = 1;//玩家不是国王
			echo $this->data->sendErr($errCode);
		}else{
			$KingPlayerReward = new KingPlayerReward;
			$leftGiftList = $KingPlayerReward->clacLeftGiftNum();
			echo $this->data->send(['leftGiftList'=>$leftGiftList]);
		}
	}
}