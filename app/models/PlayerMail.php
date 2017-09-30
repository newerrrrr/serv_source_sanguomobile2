<?php
//邮件
class PlayerMail extends ModelBase{
	public $blacklist                = array('player_id', 'create_time', 'update_time', 'rowversion');
	const TYPE_SYSTEM                = 1;//系统
	const TYPE_CHATSINGLE            = 2;//聊天（单人）
	const TYPE_CHATGROUP             = 3;//聊天（多人）
	const TYPE_DETECT                = 10;//侦查
	const TYPE_DETECTED              = 11;//被侦查
	const TYPE_ATTACKCITYWIN         = 20;//攻城战斗胜利
	const TYPE_ATTACKCITYLOSE        = 21;//攻城战斗失败
	const TYPE_DEFENCECITYWIN        = 22;//守城战斗胜利
	const TYPE_DEFENCECITYLOSE       = 23;//守城战斗失败
	const TYPE_ATTACKARMYWIN         = 24;//攻击部队胜利
	const TYPE_ATTACKARMYLOSE        = 25;//攻击部队失败
	const TYPE_DEFENCEARMYWIN        = 26;//防守部队胜利
	const TYPE_DEFENCEARMYLOSE       = 27;//防守部队失败
	const TYPE_ATTACKNPCWIN          = 28;//攻击怪物胜利
	const TYPE_ATTACKNPCLOSE         = 29;//攻击怪物失败
	const TYPE_COLLECTIONREPORT      = 30;//采集报告
	const TYPE_OCCUPY                = 31;//占领报告
	const TYPE_ATTACKBASEWARN        = 32;//攻打堡垒预警
	const TYPE_SPYBASEWARN           = 35;//侦查堡垒预警
	const TYPE_ATTACKBOSSWIN         = 33;//攻击BOSS胜利
	const TYPE_ATTACKBOSSLOSE        = 34;//攻击BOSS失败
	const TYPE_GUILDINVITE           = 40;//联盟邀请
	const TYPE_GUILDAPPLY            = 41;//联盟申请
	const TYPE_GUILDAPPROVAL         = 42;//联盟审批
	const TYPE_GUILDQUIT             = 43;//联盟成员退盟（包括被赶出）
	const TYPE_GUILDGATHER           = 44;//联盟集结信息
	const TYPE_GUILDAUTHCHG          = 45;//联盟权限修改
	const TYPE_GUILDINVITEMOVE       = 46;//联盟邀请迁城
	const TYPE_GUILD_CHANGE_CAMP     = 1047;//联盟转移阵营
	const TYPE_KINGGIFT              = 47;//国王礼包
	const TYPE_LIMITRANKGIFT         = 48;//限时比赛排名礼包
	const TYPE_LIMITTOTALRANKGIFT    = 49;//限时比赛总排名礼包
	const TYPE_FAIL_SAVE             = 50;//战争损失补偿
	const TYPE_GUILDMISSIONRANKGIFT  = 51;//联盟限时比赛总排名礼包
	const TYPE_GUILDMISSIONSCOREGIFT = 52;//联盟限时比赛积分礼包
	const TYPE_LIMITSCOREGIFT        = 53;//限时比赛阶段礼包
	const TYPE_GUILDPAYGIFT          = 54;	//联盟充值礼包
	const TYPE_FIRST_JOIN_GUILD       = 55;	//第一次加入联盟
	const TYPE_BIG_DEAL              = 56;	//大额充值,重置金额达到要求则发送邮件,一次性
	const TYPE_HUANGJINWAVEGIFT 	 = 57;//黄巾起义波次奖励
	const TYPE_KINGRANKGIFT 		 = 58;//黄巾起义波次奖励
	const TYPE_KINGAPPOINT	 		 = 59;//官职任命
	const TYPE_KINGAPPOINTKING 		 = 60;//国王任命
	const TYPE_EMAIL_TIPS 		     = 61;//新手邮件提醒email_tips
	const TYPE_BATTLE_EXPBOOK	     = 62;//神武将经验道具
	const TYPE_GUILDLEADER_IMPEACH   = 63;//盟主弹劾
	const TYPE_NOTICE_ACTIVITY   	 = 64;//提醒明天活动
	const TYPE_GUILDMISSION_GIFT   	 = 65;//联盟活动礼包
	const TYPE_PKROUND_GIFT   		 = 66;//武斗赛季奖励
	const TYPE_CROSS_FAILJOIN  		 = 67;//跨服落选
	const TYPE_PAY_RETURN	  		 = 68;//充值返利
	
	//跨服
	const TYPE_CROSSATTACKCITYWIN      = 70;//跨服战攻城战斗胜利
    const TYPE_CROSSATTACKCITYLOSE   = 71;//跨服战攻城战斗失败
    const TYPE_CROSSDEFENCECITYWIN   = 72;//跨服战守城战斗胜利
    const TYPE_CROSSDEFENCECITYLOSE  = 73;//跨服战守城战斗失败
    const TYPE_CROSSATTACKARMYWIN    = 74;//跨服战攻击投石车胜利
    const TYPE_CROSSATTACKARMYLOSE   = 75;//跨服战攻击投石车失败
    const TYPE_CROSSDEFENCEARMYWIN   = 76;//跨服战防守投石车胜利
    const TYPE_CROSSDEFENCEARMYLOSE  = 77;//跨服战防守投石车失败
    const TYPE_CROSSATTACKDOOR       = 78;//跨服战攻击城门
    const TYPE_CROSSATTACKBASE       = 79;//跨服战攻击大本营
    const TYPE_CROSS_AWARD_JOINED    = 80;//跨服战参与奖励
    const TYPE_CROSS_AWARD_NOTJOINED = 81;//跨服战大锅饭奖励
    const TYPE_CROSS_DETECT          = 82;//侦查
    const TYPE_CROSS_DETECTED        = 83;//被侦查
    const TYPE_CROSS_SIGN            = 84;//参加报名
	
	//城战
	const TYPE_CB_ATTACKCITYWIN      = 90;//城战攻城战斗胜利
    const TYPE_CB_ATTACKCITYLOSE     = 91;//城战攻城战斗失败
    const TYPE_CB_DEFENCECITYWIN     = 92;//城战守城战斗胜利
    const TYPE_CB_DEFENCECITYLOSE    = 93;//城战守城战斗失败
    const TYPE_CB_ATTACKARMYWIN      = 94;//城战攻击投石车胜利
    const TYPE_CB_ATTACKARMYLOSE    = 95;//城战攻击投石车失败
    const TYPE_CB_DEFENCEARMYWIN    = 96;//城战防守投石车胜利
    const TYPE_CB_DEFENCEARMYLOSE   = 97;//城战防守投石车失败
    const TYPE_CB_ATTACKDOOR        = 98;//城战攻击城门
    const TYPE_CB_REAWARD_JOINED    = 100;//城战战参与奖励
    const TYPE_CB_REAWARD_NOTJOINED = 101;//城战战大锅饭奖励
    const TYPE_CB_DETECT            = 102;//侦查
    const TYPE_CB_DETECTED          = 103;//被侦查
    const TYPE_CB_SIGN              = 104;//参加报名
    const TYPE_CB_AWARD_YU_LIN_JUN  = 105;//城战羽林军
    const TYPE_CB_AWARD_NORMAL      = 106;//城战奖励
    const TYPE_CB_AWARD_TASK        = 107;//城战任务完成奖励
	const TYPE_CB_TOKEN        		= 108;//城战令牌
   
	const READFLAG_UNREAD            = 0;//未读
	const READFLAG_READ              = 1;//已读
	const READFLAG_GET               = 2;//已领取
	
	const STATUS_NORMAL              = 0;
	const STATUS_LOCK                = 1;//锁定
	const STATUS_DELETE              = -1;//删除
	
	public $cataType = array(
		//聊天
		1 => array(self::TYPE_CHATSINGLE, self::TYPE_CHATGROUP),
		//联盟
		2 => array(
			self::TYPE_GUILDINVITE, 
			self::TYPE_GUILDAPPLY, 
			self::TYPE_GUILDAPPROVAL, 
			self::TYPE_GUILDQUIT, 
			self::TYPE_GUILDGATHER, 
			self::TYPE_GUILDAUTHCHG,  
			self::TYPE_GUILDINVITEMOVE,
			self::TYPE_ATTACKBASEWARN,
			self::TYPE_SPYBASEWARN,
			self::TYPE_GUILDLEADER_IMPEACH,
            self::TYPE_GUILD_CHANGE_CAMP,
			),
		//侦查
		3 => array(self::TYPE_DETECT, self::TYPE_DETECTED, self::TYPE_CROSS_DETECT, self::TYPE_CROSS_DETECTED),
		//战斗
		4 => array(
			self::TYPE_ATTACKCITYWIN, self::TYPE_ATTACKCITYLOSE, 
			self::TYPE_DEFENCECITYWIN, self::TYPE_DEFENCECITYLOSE, 
			self::TYPE_ATTACKARMYWIN, self::TYPE_ATTACKARMYLOSE, 
			self::TYPE_DEFENCEARMYWIN, self::TYPE_DEFENCEARMYLOSE, 
		),
		//系统
		5 => array(
            self::TYPE_SYSTEM, self::TYPE_OCCUPY, self::TYPE_KINGGIFT,
            self::TYPE_LIMITRANKGIFT, self::TYPE_LIMITTOTALRANKGIFT, self::TYPE_LIMITSCOREGIFT,
            self::TYPE_FAIL_SAVE,
            self::TYPE_GUILDMISSIONRANKGIFT, self::TYPE_GUILDMISSIONSCOREGIFT,
            self::TYPE_GUILDPAYGIFT,
            self::TYPE_FIRST_JOIN_GUILD,
            self::TYPE_BIG_DEAL,
            self::TYPE_HUANGJINWAVEGIFT,
            self::TYPE_KINGRANKGIFT,
            self::TYPE_KINGAPPOINT,
            self::TYPE_KINGAPPOINTKING,
            self::TYPE_EMAIL_TIPS,
            self::TYPE_BATTLE_EXPBOOK,
            self::TYPE_NOTICE_ACTIVITY,
            self::TYPE_GUILDMISSION_GIFT,
            self::TYPE_PKROUND_GIFT,
            self::TYPE_CROSS_FAILJOIN,
            self::TYPE_CROSS_AWARD_JOINED,
            self::TYPE_CROSS_AWARD_NOTJOINED,
            self::TYPE_CROSS_SIGN,
            self::TYPE_PAY_RETURN,
			self::TYPE_CB_REAWARD_JOINED,
			self::TYPE_CB_REAWARD_NOTJOINED,
			self::TYPE_CB_SIGN,
            self::TYPE_CB_AWARD_YU_LIN_JUN,
            self::TYPE_CB_AWARD_NORMAL,
			self::TYPE_CB_AWARD_TASK,
			self::TYPE_CB_TOKEN,
		),
		//采集
		6 => array(
			self::TYPE_COLLECTIONREPORT
		),
		//怪物
		7 => array(
			self::TYPE_ATTACKNPCWIN, self::TYPE_ATTACKNPCLOSE, 
			self::TYPE_ATTACKBOSSWIN, self::TYPE_ATTACKBOSSLOSE, 

		),
		//跨服
		8 => array(
			self::TYPE_CROSSATTACKCITYWIN,
			self::TYPE_CROSSATTACKCITYLOSE,
			self::TYPE_CROSSDEFENCECITYWIN,
			self::TYPE_CROSSDEFENCECITYLOSE,
			self::TYPE_CROSSATTACKARMYWIN,
			self::TYPE_CROSSATTACKARMYLOSE,
			self::TYPE_CROSSDEFENCEARMYWIN,
			self::TYPE_CROSSDEFENCEARMYLOSE,
			self::TYPE_CROSSATTACKDOOR,
			self::TYPE_CROSSATTACKBASE,
		),
		//城战
		9 => array(
			self::TYPE_CB_ATTACKCITYWIN,
			self::TYPE_CB_ATTACKCITYLOSE,
			self::TYPE_CB_DEFENCECITYWIN,
			self::TYPE_CB_DEFENCECITYLOSE,
			self::TYPE_CB_ATTACKARMYWIN,
			self::TYPE_CB_ATTACKARMYLOSE,
			self::TYPE_CB_DEFENCEARMYWIN,
			self::TYPE_CB_DEFENCEARMYLOSE,
			self::TYPE_CB_ATTACKDOOR,
		),
	);
	
	public $typeDesc = [
			self::TYPE_SYSTEM           	=> '系统',
			self::TYPE_CHATSINGLE       	=> '聊天（单人）',
			self::TYPE_CHATGROUP        	=> '聊天（多人）',
			self::TYPE_DETECT           	=> '侦查',
			self::TYPE_DETECTED         	=> '被侦查',
			self::TYPE_ATTACKCITYWIN    	=> '攻城战斗胜利',
			self::TYPE_ATTACKCITYLOSE   	=> '攻城战斗失败',
			self::TYPE_DEFENCECITYWIN   	=> '守城战斗胜利',
			self::TYPE_DEFENCECITYLOSE  	=> '守城战斗失败',
			self::TYPE_ATTACKARMYWIN    	=> '攻击部队胜利',
			self::TYPE_ATTACKARMYLOSE   	=> '攻击部队失败',
			self::TYPE_DEFENCEARMYWIN   	=> '防守部队胜利',
			self::TYPE_DEFENCEARMYLOSE  	=> '防守部队失败',
			self::TYPE_ATTACKNPCWIN     	=> '攻击怪物胜利',
			self::TYPE_ATTACKNPCLOSE    	=> '攻击怪物失败',
			self::TYPE_COLLECTIONREPORT 	=> '采集报告',
			self::TYPE_OCCUPY           	=> '占领报告',
			self::TYPE_ATTACKBASEWARN   	=> '攻打堡垒预警',
			self::TYPE_SPYBASEWARN   		=> '侦查堡垒预警',
			self::TYPE_ATTACKBOSSWIN    	=> '攻击BOSS胜利',
			self::TYPE_ATTACKBOSSLOSE   	=> '攻击BOSS失败',
			self::TYPE_GUILDINVITE      	=> '联盟邀请',
			self::TYPE_GUILDAPPLY       	=> '联盟申请',
			self::TYPE_GUILDAPPROVAL    	=> '联盟审批',
			self::TYPE_GUILDQUIT        	=> '联盟成员退盟（包括被赶出）',
			self::TYPE_GUILDGATHER      	=> '联盟集结信息',
			self::TYPE_GUILDAUTHCHG     	=> '联盟权限修改',
			self::TYPE_GUILDINVITEMOVE  	=> '联盟邀请迁城',
			self::TYPE_KINGGIFT			=> '国王礼包',
			self::TYPE_LIMITRANKGIFT		=> '限时比赛排名礼包',
			self::TYPE_LIMITTOTALRANKGIFT	=> '限时比赛总排名礼包',
			self::TYPE_FAIL_SAVE			=> '战争损失补偿',
			self::TYPE_GUILDMISSIONRANKGIFT	=> '联盟限时比赛总排名礼包',
			self::TYPE_GUILDMISSIONSCOREGIFT	=> '联盟限时比赛积分礼包',
			self::TYPE_LIMITSCOREGIFT			=> '限时比赛阶段礼包',
			self::TYPE_GUILDPAYGIFT          => '联盟充值礼包',
			self::TYPE_FIRST_JOIN_GUILD       => '第一次加入联盟',
			self::TYPE_BIG_DEAL              => '大额充值',
			self::TYPE_HUANGJINWAVEGIFT 	 => '黄巾起义波次奖励',
			self::TYPE_KINGRANKGIFT 		 => '黄巾起义波次奖励',
			self::TYPE_KINGAPPOINT	 		 => '官职任命',
			self::TYPE_KINGAPPOINTKING 		 => '国王任命',
			self::TYPE_EMAIL_TIPS 		     => '新手邮件提醒email_tips',
			self::TYPE_BATTLE_EXPBOOK	     => '神武将经验道具',
			self::TYPE_GUILDLEADER_IMPEACH		=> '盟主弹劾',
			self::TYPE_NOTICE_ACTIVITY			=>'提醒明天活动',
			self::TYPE_GUILDMISSION_GIFT		=>'联盟活动礼包',
			self::TYPE_PKROUND_GIFT				=>'武斗赛季奖励',
			
			self::TYPE_CROSS_FAILJOIN			=> '跨服落选',
			self::TYPE_CROSSATTACKCITYWIN    	=> '跨服攻城战斗胜利',
			self::TYPE_CROSSATTACKCITYLOSE   	=> '跨服攻城战斗失败',
			self::TYPE_CROSSDEFENCECITYWIN   	=> '跨服守城战斗胜利',
			self::TYPE_CROSSDEFENCECITYLOSE  	=> '跨服守城战斗失败',
			self::TYPE_CROSSATTACKARMYWIN    	=> '跨服攻击部队胜利',
			self::TYPE_CROSSATTACKARMYLOSE   	=> '跨服攻击部队失败',
			self::TYPE_CROSSDEFENCEARMYWIN   	=> '跨服防守部队胜利',
			self::TYPE_CROSSDEFENCEARMYLOSE  	=> '跨服防守部队失败',
			
			self::TYPE_CB_ATTACKCITYWIN    	=> '城战攻城战斗胜利',
			self::TYPE_CB_ATTACKCITYLOSE   	=> '城战攻城战斗失败',
			self::TYPE_CB_DEFENCECITYWIN   	=> '城战守城战斗胜利',
			self::TYPE_CB_DEFENCECITYLOSE  	=> '城战守城战斗失败',
			self::TYPE_CB_ATTACKARMYWIN    	=> '城战攻击部队胜利',
			self::TYPE_CB_ATTACKARMYLOSE   	=> '城战攻击部队失败',
			self::TYPE_CB_DEFENCEARMYWIN   	=> '城战防守部队胜利',
			self::TYPE_CB_DEFENCEARMYLOSE  	=> '城战防守部队失败',
            self::TYPE_CB_AWARD_NORMAL      => '城战奖励',
            self::TYPE_CB_AWARD_YU_LIN_JUN  => '城战羽林军',
		];
	
	private $playerData = [];
	
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		//$this->clearDataCache();
	}
	
	public function getMailInfo($mailId){
		$ret = self::findFirst($mailId);
		if(!$ret)
			return false;
		$ret = $ret->toArray();
		$ret = $this->adapter($ret, true);
		$ret['mail_info'] = array();
		$PlayerMailInfo = new PlayerMailInfo;
		$info = $PlayerMailInfo->findFirst($ret['mail_info_id']);
		if($info){
			$ret['mail_info'] = $info->toArray();
			$ret['mail_info'] = $PlayerMailInfo->adapter($ret['mail_info'], true);
		}
		return $ret;
	}
	
	/**
     * 发送消息
     * 
     * @param <array|int> $toPlayerIds 发送对象，可单人，可群发，0=发送全体玩家
     * @param <type> $type 邮件类型
     * @param <type> $title 邮件标题
     * @param <type> $msg 邮件文本
     * @param <type> $time 邮件有效时长（秒），设置0表示使用系统默认有效时间
     * @param <type> $data 额外参数
     * @param <type> $item 附件 同Drop格式
     * 
     * @return <type>
	 * @example: 
	 *  $PlayerMail = new PlayerMail;
		$toPlayerIds = [100016];
		$type = PlayerMail::TYPE_SYSTEM;
		$title = '这是一封系统邮件';
		$msg = 'f4a6f4ea6f4ea4fe6a4f';
		$time = 0;
		$data = '';
		$item = $PlayerMail->newItem(1, 10100, 500);
		$item = $PlayerMail->newItem(1, 10700, 500, $item);
		$PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data, $item);
      */
	 public function sendSystem($toPlayerIds, $type, $title, $msg, $time=0, $data=array(), $item=array(), $memo=[]){
		//检查禁止类型
		if(in_array($type, array(self::TYPE_CHATSINGLE, self::TYPE_CHATGROUP)))
			return false;
		
		//计算过期时间
		if(!$time){
			$time = PLAYER_MAIL_EXPIRETIME;
		}
		$expireTime = date('Y-m-d H:i:s', time()+$time);
		
		//新建info
		$PlayerMailInfo = new PlayerMailInfo;
		if(!$PlayerMailInfo->add(0, '', 0, '', $title, $msg, $data, $item, $expireTime)){
			return false;
		}
		$mailInfoId = $PlayerMailInfo->id;
		
		$noticeData = [];
		if(0 == $toPlayerIds){
			$_playerId = 0;
			$row = 10;
			$toPlayerIds = [];
			while($_data = $PlayerMailInfo->sqlGet('select id from player where id>'.$_playerId.' order by id limit '.$row)){
				$_playerIds = Set::extract('/id', $_data);
				$toPlayerIds = array_merge($toPlayerIds, $_playerIds);
				$_playerId = $_playerIds[count($_playerIds)-1];
			}
		}
		if(!is_array($toPlayerIds))
			$toPlayerIds = array($toPlayerIds);

		foreach($toPlayerIds as $_playerId){
			$o = new self;
			if(!$o->add($_playerId, $type, 0, $mailInfoId, $expireTime, PlayerMail::READFLAG_UNREAD, false, $memo)){
				return false;
			}
			$noticeData[$_playerId] = ['mail_id'=>$o->id*1, 'cata_type'=>$this->getCataByType($type), 'type'=>$type, 'connect_id'=>"0"];
		}
		
		$this->notice($noticeData);
		return true;
	}
	
    /**
     * 发送玩家单人消息
     * 
     * @param <type> $toPlayerIds 发送对象数组
     * @param <type> $fromPlayerId 发信者
     * @param <type> $title 邮件标题
     * @param <type> $msg 邮件文本
     * @param <type> $time 邮件有效时长（秒），设置0表示使用系统默认有效时间
     * @param <type> $data 额外参数
     * @param <type> $item 附件
     * 
     * @return <type>
     */
	public function sendSingle($toPlayerIds, $fromPlayerId, $fromPlayerName, $fromPlayerAvatar, $fromGuildShort, $msg, $time=0, $data=array(), $item=array()){
		//计算过期时间
		if(!$time){
			$time = PLAYER_MAIL_EXPIRETIME;
		}
		$expireTime = date('Y-m-d H:i:s', time()+$time);
		
		if(!is_array($toPlayerIds)){
			$toPlayerIds = array($toPlayerIds);
		}
		
		//新建info
		$PlayerMailInfo = new PlayerMailInfo;
		if(!$PlayerMailInfo->add($fromPlayerId, $fromPlayerName, $fromPlayerAvatar, $fromGuildShort, $title='', $msg, $data, $item, $expireTime)){
			return false;
		}
		$mailInfoId = $PlayerMailInfo->id;
		
		$noticeData = [];
		//给发送者和对象各产生一封邮件
		foreach($toPlayerIds as $_toPlayerId){
			//查找是否有锁定邮件
			if(self::findFirst(['player_id='.$_toPlayerId.' and connect_id="'.$fromPlayerId.'" and type='.self::TYPE_CHATSINGLE.' and status='.self::STATUS_LOCK])){
				$_status = self::STATUS_LOCK;
			}else{
				$_status = self::STATUS_NORMAL;
			}
			$o = new self;
			if(!$o->add($_toPlayerId, self::TYPE_CHATSINGLE, $fromPlayerId, $mailInfoId, $expireTime, self::READFLAG_UNREAD, $_status)){
				return false;
			}
			$noticeData[$_toPlayerId] = ['mail_id'=>$o->id*1, 'cata_type'=>$this->getCataByType(self::TYPE_CHATSINGLE), 'type'=>self::TYPE_CHATSINGLE, 'connect_id'=>$fromPlayerId.""];
			if(!in_array($fromPlayerId, $toPlayerIds)){//对联盟全体不发双份
				//查找是否有锁定邮件
				if(self::findFirst(['player_id='.$fromPlayerId.' and connect_id='.$_toPlayerId.' and type='.self::TYPE_CHATSINGLE.' and status='.self::STATUS_LOCK])){
					$_status = self::STATUS_LOCK;
				}else{
					$_status = self::STATUS_NORMAL;
				}

				if(!(new self)->add($fromPlayerId, self::TYPE_CHATSINGLE, $_toPlayerId, $mailInfoId, $expireTime, self::READFLAG_READ, $_status)){
					return false;
				}
			}
		}
		
		$this->notice($noticeData);
		return true;
	}
	
	/**
     * 发送玩家组群消息
     * 
     * @param <type> $groupId 组群id
     * @param <type> $fromPlayerId 发信者
     * @param <type> $title 邮件标题
     * @param <type> $msg 邮件文本
     * @param <type> $time 邮件有效时长（秒），设置0表示使用系统默认有效时间
     * @param <type> $data 额外参数
     * @param <type> $item 附件
     * 
     * @return <type>
     */
	public function sendGroup($groupId, $fromPlayerId, $fromPlayerName, $fromPlayerAvatar, $fromGuildShort, $msg, $time=0, $data=array(), $item=array()){
		//计算过期时间
		if(!$time){
			$time = PLAYER_MAIL_EXPIRETIME;
		}
		$expireTime = date('Y-m-d H:i:s', time()+$time);
		
		//新建info
		$PlayerMailInfo = new PlayerMailInfo;
		if(!$PlayerMailInfo->add($fromPlayerId, $fromPlayerName, $fromPlayerAvatar, $fromGuildShort, $title='', $msg, $data, $item, $expireTime)){
			return false;
		}
		$mailInfoId = $PlayerMailInfo->id;
		
		//获取组成员
		$PlayerMailGroup = new PlayerMailGroup;
		$playerIds = $PlayerMailGroup->getGroup($groupId);
		if(!$playerIds)
			return false;
		//检查是否在组群内
		if(!in_array($fromPlayerId, $playerIds))
			return false;
		
		//给组成员各产生一封邮件
		$noticeData = [];
		foreach($playerIds as $_playerId){
			if($_playerId == $fromPlayerId){
				$readFlag = self::READFLAG_READ;
			}else{
				$readFlag = self::READFLAG_UNREAD;
			}
			//查找是否有锁定邮件
			if(self::findFirst(['player_id='.$_playerId.' and connect_id="'.$groupId.'" and type='.self::TYPE_CHATGROUP.' and status='.self::STATUS_LOCK])){
				$_status = self::STATUS_LOCK;
			}else{
				$_status = self::STATUS_NORMAL;
			}
			$o = new self;
			if(!$o->add($_playerId, self::TYPE_CHATGROUP, $groupId, $mailInfoId, $expireTime, $readFlag, $_status)){
				return false;
			}
			$noticeData[$_playerId] = ['mail_id'=>$o->id*1, 'cata_type'=>$this->getCataByType(self::TYPE_CHATGROUP), 'type'=>self::TYPE_CHATGROUP, 'connect_id'=>$groupId];
		}
		
		unset($noticeData[$fromPlayerId]);
		$this->notice($noticeData);
		//$this->notice(array_diff($playerIds, array($fromPlayerId)));
		return true;
	}
	
    /**
     * 给组群的某些成员发消息
     * 
     * @param <type> $groupId 
     * @param <type> $playerId 
     * @param <type> $msg 
     * @param <type> $time 
     * @param <type> $data 
     * @param <type> $item 
     * 
     * @return <type>
     */
	public function sendGroupPlayer($groupId, $playerIds, $msg, $readFlag, $time=0, $data=array(), $item=array()){
		//计算过期时间
		if(!$time){
			$time = PLAYER_MAIL_EXPIRETIME;
		}
		$expireTime = date('Y-m-d H:i:s', time()+$time);
		
		//新建info
		$PlayerMailInfo = new PlayerMailInfo;
		if(!$PlayerMailInfo->add(0, '', 0, '', $title='', $msg, $data, $item, $expireTime)){
			return false;
		}
		$mailInfoId = $PlayerMailInfo->id;
		
		//$readFlag = self::READFLAG_UNREAD;
		$noticeData = [];
		foreach($playerIds as $_playerId){
			//查找是否有锁定邮件
			if(self::findFirst(['player_id='.$_playerId.' and connect_id="'.$groupId.'" and type='.self::TYPE_CHATGROUP.' and status='.self::STATUS_LOCK])){
				$_status = self::STATUS_LOCK;
			}else{
				$_status = self::STATUS_NORMAL;
			}
			$o = new self;
			if(!$o->add($_playerId, self::TYPE_CHATGROUP, $groupId, $mailInfoId, $expireTime, $readFlag, $_status)){
				return false;
			}
			$noticeData[$_playerId] = ['mail_id'=>$o->id*1, 'cata_type'=>$this->getCataByType(self::TYPE_CHATGROUP), 'type'=>self::TYPE_CHATGROUP, 'connect_id'=>$groupId];
		}
		
		if(self::READFLAG_UNREAD == $readFlag){
			$this->notice($noticeData);
		}
		return true;
	}
	
	/**
     * 发送新组群消息
     * 
     * @param <array> $toPlayerIds 玩家id数组
     * @param <type> $fromPlayerId 发信者
     * @param <type> $title 邮件标题
     * @param <type> $msg 邮件文本
     * @param <type> $time 邮件有效时长（秒），设置0表示使用系统默认有效时间
     * @param <type> $data 额外参数
     * @param <type> $item 附件
     * 
     * @return <type> 返回组群号
     */
	 /*
	public function sendNewGroup($toPlayerIds, $fromPlayerId, $fromPlayerName, $fromPlayerAvatar, $msg, $time=0, $data=array(), $item=array()){
		$PlayerMailGroup = new PlayerMailGroup;
		$groupId = $PlayerMailGroup->newGroup($toPlayerIds, $fromPlayerId);
		if(!$groupId)
			return false;
		
		if(!$this->sendGroup($groupId, $fromPlayerId, $fromPlayerName, $fromPlayerAvatar, $msg, $time, $data, $item)){
			return false;
		}
		return $groupId;
	}*/
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId, $type, $connectId, $mailInfoId, $expireTime, $readFlag=0, $status=0, $memo=[]){
		$ret = $this->create(array(
			'player_id' => $playerId,
			'type' => $type,
			'connect_id' => $connectId,
			'mail_info_id' => $mailInfoId,
			'read_flag' => $readFlag,
			'memo' => json_encode($memo, JSON_UNESCAPED_UNICODE),
			'status' => $status,
			'expire_time' => $expireTime,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}

	public function notice($data){
		socketSend(['Type'=>'mail', 'Data'=>$data]);
	}

	public function updateReadByConnectId($playerId, $connectId, $readFlag){
		if(!is_array($connectId)){
			$connectId = "'".$connectId."'";
		}
		$condition = array('player_id'=>$playerId, /*'type'=>$type, */'connect_id'=>$connectId, 'read_flag <>'=>$readFlag);
		$ret = $this->updateAll(array('read_flag'=>'if('.$readFlag.'>read_flag, '.$readFlag.', read_flag)', 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), $condition);
		return true;
	}
	
	public function updateReadByMailId($playerId, $mailId, $readFlag){
		$ret = $this->updateAll(array('read_flag'=>'if('.$readFlag.'>read_flag, '.$readFlag.', read_flag)', 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), array('id'=>$mailId, 'player_id'=>$playerId, 'read_flag <>'=>$readFlag));
		return true;
	}
	
	public function updateReadByType($playerId, $type, $readFlag){
		$ret = $this->updateAll(array('read_flag'=>'if('.$readFlag.'>read_flag, '.$readFlag.', read_flag)', 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), array('player_id'=>$playerId, 'type'=>$type, 'read_flag <>'=>$readFlag));
		return true;
	}
	
	public function updateStatusByConnectId($playerId, $connectId, $status, $force=false){
		if(!is_array($connectId)){
			$connectId = "'".$connectId."'";
		}
		$condition = array('player_id'=>$playerId, /*'type'=>$type, */'connect_id'=>$connectId);
		if(!$force){
			if($status == -1){
				$condition['status'] = 0;
			}else{
				$condition['status >='] = 0;
			}
		}
		$ret = $this->updateAll(array('status'=>$status, 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), $condition);
		return true;
	}
	
	public function updateStatusByMailId($playerId, $mailId, $status){
		$condition = array('id'=>$mailId, 'player_id'=>$playerId);
		if($status == -1){
			$condition['status'] = 0;
		}else{
			$condition['status >='] = 0;
		}
		$ret = $this->updateAll(array('status'=>$status, 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), $condition);
		return true;
	}
	
	public function updateStatusByType($playerId, $type, $status){
		$condition = array('player_id'=>$playerId, 'type'=>$type);
		if($status == -1){
			$condition['status'] = 0;
		}else{
			$condition['status >='] = 0;
		}
		$ret = $this->updateAll(array('status'=>$status, 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), $condition);
		return true;
	}
	
    /**
     * 更新memo字段
     * 
     * @param <type> $playerId 
     * @param <type> $mailId 
     * @param <type> $memo 
     * 
     * @return <type>
     */
	public function updateMemosByMailId($playerId, $mailId, $memo=[]){
		$condition = array('id'=>$mailId, 'player_id'=>$playerId);
		$ret = $this->updateAll(array('memo'=>"'".json_encode($memo)."'", 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), $condition);
		return $ret;
	}
	
	public function newItem($type, $itemId, $num, $itemArr=array()){
		$itemArr[] = [$type, $itemId, $num];
		return $itemArr;
	}
	
	public function newItemByDrop($playerId, $dropIds, $itemArr=array()){
		$Drop = new Drop;
		if($playerId){
			$dropData = $Drop->rand($playerId, $dropIds);
		}else{
			$dropData = [];
			foreach($dropIds as $_dropId){
				$_drop = $Drop->dicGetOne($_dropId);
				$dropData = array_merge($dropData, $_drop['drop_data']);
			}
			
		}
		if($dropData){
			foreach($dropData as $_dropData){
				//$carryItem[] = [$_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1];
				$itemArr = $this->newItem($_dropData[0]*1, $_dropData[1]*1, $_dropData[2]*1, $itemArr);
			}
		}
		return $itemArr;
	}

    /**
     * pvp战斗邮件
     * 
     * @param <array> $attackerIds 
     * @param <array> $defenderIds 
     * @param <int> $result 1：胜利，0：失败
     * @param <int> $type 1：攻城，2：撞田,3:堡垒,4:王战pvp，5：王战pve，6：王战npc攻打,7: 打怪,8:boss,9:据点战,10:黄巾起义,11.跨服攻城，12.跨服攻击部队
     * @param <int> $x 发生战斗x
     * @param <int> $y 发生战斗y
     * @param <array> $getResource 获得资源['wood'=>xxx....]
     * @param <array> $attackerData 
     * @param <array> $defenderData 
	 * @example:
	 *	$attackerIds = [100016];
	 *	$defenderIds = [100080];
	 *	$result = 1;
	 *	$type = 1;
	 *	$x = 50;
	 *	$y = 50;
	 *	$getResource = ['wood'=>100];
	 *	$attackerData = [
	 *		100016 => [ //玩家id
	 *			'power'=>200,
	 *			'unit'=>[
	 *				0 => [//武将id
	 *					'general_id'=>xxx,
	 *					'soldier_id'=>10001,
	 *					'soldier_num'=>10,
	 *					'kill_num'=>90,//消灭
	 *					'killed_num'=>5,//损失
	 *					'injure_num'=>3,//受伤
	 *					'live_num'=>2,//存活
	 *				],
	 *				1 => [
	 *					'general_id'=>xxx,
	 *					'soldier_id'=>10001,
	 *					'soldier_num'=>100,
	 *					'kill_num'=>10,
	 *					'killed_num'=>5,
	 *					'injure_num'=>3,
	 *					'live_num'=>92,
	 *				],
	 *			],
	 *		]
	 *	];
	 *	$defenderData = [
	 *		100080 => [
	 *			'power'=>200,
	 *			'unit'=>[
	 *				0 => [
	 *					'general_id'=>xxx,
	 *					'soldier_id'=>10002,
	 *					'soldier_num'=>10,
	 *					'kill_num'=>90,
	 *					'killed_num'=>5,
	 *					'injure_num'=>3,
	 *					'live_num'=>2,
	 *				],
	 *				1 => [
	 *					'general_id'=>xxx,
	 *					'soldier_id'=>10001,
	 *					'soldier_num'=>10,
	 *					'kill_num'=>90,
	 *					'killed_num'=>5,
	 *					'injure_num'=>3,
	 *					'live_num'=>2,
	 *				],
	 *				'trap' => [//陷阱
	 *					[
	 *						'soldier_id'=>10001,//陷阱种类
	 *						'soldier_num'=>10,//陷阱数量
	 *						'kill_num'=>90,//陷阱击杀
	 *						'killed_num'=>5,//损失数量
	 *						'injure_num'=>0,
	 *						'live_num'=>5,//剩余数量
	 *					],
	 *				],
	 *				'tower' => [//箭塔
	 *					'soldier_id'=>0,
	 *					'soldier_num'=>0,//箭塔数量
	 *					'kill_num'=>90,//箭塔击杀
	 *					'killed_num'=>0,
	 *					'injure_num'=>0,
	 *					'live_num'=>0,
	 *				],
	 *			],
	 *		]
	 *	];
     * 
     * @return <type>
     */
	public function sendPVPBattleMail($attackerIds, $defenderIds, $result, $type, $x, $y, $getResource, $attackerData, $defenderData, $allDead = false, $extraData = []){
		$this->playerData = [];
		//获取玩家名字
		if($type == 5 || $type == 7 || $type == 8){//王战pve或打怪
			$playerIds = $attackerIds;
			if($type == 7 || $type == 8){
				$elementId = $defenderIds;
			}
			$defenderIds = [];
		}elseif($type == 6 || $type == 10){
			$playerIds = $defenderIds;
			$npcId = $attackerIds;
			$attackerIds = [];
			if($type == 10){
				$toGuildId = $defenderIds;
			}
		}elseif($type == 3){
			$playerIds = $attackerIds;
			$toGuildId = $defenderIds;
			$defenderIds = [];
		}else{
			$playerIds = array_merge($attackerIds, $defenderIds);
		}
		$players = array();
		$Player = new Player;
		//$guilds = [];
		$Guild = new Guild;
		/*$playerIds = [];
		foreach(array_merge(array_keys($attackerData), array_keys($defenderData)) as $_playerId){
			if(!$_playerId) continue;
			$_player = $Player->getByPlayerId($_playerId);
			$players[$_playerId] = $_player;
			//获取联盟
		}
		*/
		
		//资源
		$resource1 = $resource2 = ['food'=>0, 'wood'=>0, 'gold'=>0, 'stone'=>0, 'iron'=>0];
		if(!in_array($type, [7, 8])){
			foreach($getResource as $_playerId => $_resource){
				if(!$_playerId) continue;
				foreach($_resource as $_k => $_r){
					$resource2[$_k] += -$_r;
					$resource1[$_k] += $_r;
				}
			}
		}
		//获取战斗buff
		$PlayerBuff = new PlayerBuff;
		/*$playerBuffs = [];
		foreach([$attackerIds[0], $defenderIds[0]] as $_playerId){
			$playerBuffs[$_playerId] = $PlayerBuff->getBattleBuff($_playerId);
		}*/
		
		if($type == 6 || $type == 10){
			//计算攻击方归类
			$attackerSum = [
				'nick'=>'',
				'avatar'=>$npcId,
				'x'=>$x,
				'y'=>$y,
				'power'=>0,
				'guild_name'=>'',
				'guild_short_name'=>'',
				'soldier_num'=>0,
				'kill_num'=>0,
				'killed_num'=>0,
				'injure_num'=>0,
				'live_num'=>0,
				'trap_lost'=>0,
				'buff'=>[],
			];
			foreach($attackerData as $_playerId =>&$_playerData){
				$attackerSum['power'] += $_playerData['power'];
				$_playerData['player_id'] = $_playerId*1;
				$_playerData['nick'] = '';
				$_playerData['avatar'] = $npcId;
				foreach($_playerData['unit'] as $_unitData){
					$attackerSum['soldier_num'] += $_unitData['soldier_num'];
					$attackerSum['kill_num'] += $_unitData['kill_num'];
					$attackerSum['killed_num'] += $_unitData['killed_num'];
					$attackerSum['injure_num'] += $_unitData['injure_num'];
					$attackerSum['live_num'] += $_unitData['live_num'];
				}
			}
			unset($_playerData);
			$attackerSum['players'] = array_values($attackerData);
		}else{
			$pg = $Guild->getByPlayerId($attackerIds[0]);
			if($pg){
				$_guild = ['name'=>$pg['name'], 'short'=>$pg['short_name']];
			}else{
				$_guild = ['name'=>'', 'short'=>''];
			}
			//计算攻击方归类
			$_player = $this->getPlayer($attackerIds[0]);
			$attackerSum = [
				'nick'=>$_player['nick'],
				'avatar'=>$_player['avatar_id'],
				'x'=>$_player['x'],
				'y'=>$_player['y'],
				'power'=>0,
				'guild_name'=>$_guild['name'],
				'guild_short_name'=>$_guild['short'],
				'soldier_num'=>0,
				'kill_num'=>0,
				'killed_num'=>0,
				'injure_num'=>0,
				'live_num'=>0,
				'trap_lost'=>0,
				'buff'=>$PlayerBuff->getBattleBuff($attackerIds[0]),
			];
			foreach($attackerData as $_playerId =>&$_playerData){
				$_player = $this->getPlayer($_playerId);
				$attackerSum['power'] += $_playerData['power'];
				$_playerData['player_id'] = $_playerId*1;
				$_playerData['nick'] = $_player['nick'];
				/*if(!@$players[$_playerId]){
					$players[$_playerId] = $Player->getByPlayerId($_playerId);
				}*/
				$_playerData['avatar'] = $_player['avatar_id'];
				foreach($_playerData['unit'] as $_unitData){
					$attackerSum['soldier_num'] += $_unitData['soldier_num'];
					$attackerSum['kill_num'] += $_unitData['kill_num'];
					$attackerSum['killed_num'] += $_unitData['killed_num'];
					$attackerSum['injure_num'] += $_unitData['injure_num'];
					$attackerSum['live_num'] += $_unitData['live_num'];
				}
			}
			unset($_playerData);
			$attackerSum['players'] = array_values($attackerData);
		}
		$attackerSum['power_lost'] = @$extraData['aLosePower']*1;
		
		
		//计算防守方归类
		if($type == 5){//king pve
			//获取城寨图标
			$map = (new Map)->getByXy($x, $y);
			if(!$map)
				return false;
			$defenderSum = [
				'nick'=>'',
				'avatar'=>$map['map_element_id'],
				'x'=>$x,
				'y'=>$y,
				'power'=>0,
				'guild_name'=>'',
				'guild_short_name'=>'',
				'soldier_num'=>0,
				'kill_num'=>0,
				'killed_num'=>0,
				'injure_num'=>0,
				'live_num'=>0,
				'trap_lost'=>0,//$defenderData[$defenderIds[0]]['unit']['trap']['killed_num']*1,
				'buff'=>[],
				'oldDurability'=>0,
				'newDurability'=>0,
			];
			foreach($defenderData as $_playerId =>&$_playerData){
				if(isset($defenderData[$_playerId])){
					$defenderSum['power'] += $defenderData[$_playerId]['power'];
					$defenderData[$_playerId]['player_id'] = $_playerId*1;
					$defenderData[$_playerId]['nick'] = '';
					$defenderData[$_playerId]['avatar'] = 0;
					foreach($defenderData[$_playerId]['unit'] as $_k => $_unitData){
						//if(in_array($_k, ['trap', 'tower'])) continue;
						if($_k === 'trap'){
							foreach($_unitData as $_trap){
								$defenderSum['trap_lost'] += $_trap['killed_num'];
								$defenderSum['kill_num'] += $_trap['kill_num'];
							}
						}else{
							$defenderSum['soldier_num'] += $_unitData['soldier_num'];
							$defenderSum['kill_num'] += $_unitData['kill_num'];
							$defenderSum['killed_num'] += $_unitData['killed_num'];
							$defenderSum['injure_num'] += $_unitData['injure_num'];
							$defenderSum['live_num'] += $_unitData['live_num'];
						}
					}
				}
			}
			$defenderSum['players'] = array_values($defenderData);
		}elseif($type == 7 || $type == 8){//打怪
			//获取城寨图标
			$map = (new Map)->getByXy($x, $y);
			if(!$map)
				return false;
			$defenderSum = [
				'nick'=>'',
				'avatar'=>$elementId*1,
				'x'=>$x,
				'y'=>$y,
				'power'=>0,
				'guild_name'=>'',
				'guild_short_name'=>'',
				'soldier_num'=>0,
				'kill_num'=>0,
				'killed_num'=>0,
				'injure_num'=>0,
				'live_num'=>0,
				'trap_lost'=>0,//$defenderData[$defenderIds[0]]['unit']['trap']['killed_num']*1,
				'buff'=>[],
				'oldDurability'=>0,
				'newDurability'=>0,
			];
			foreach($defenderData as $_playerId =>&$_playerData){
				if(isset($defenderData[$_playerId])){
					$defenderSum['power'] += $defenderData[$_playerId]['power'];
					$defenderData[$_playerId]['player_id'] = $_playerId*1;
					$defenderData[$_playerId]['nick'] = '';
					$defenderData[$_playerId]['avatar'] = 0;
					foreach($defenderData[$_playerId]['unit'] as $_k => $_unitData){
						//if(in_array($_k, ['trap', 'tower'])) continue;
						if($_k === 'trap'){
							foreach($_unitData as $_trap){
								$defenderSum['trap_lost'] += $_trap['killed_num'];
								$defenderSum['kill_num'] += $_trap['kill_num'];
							}
						}else{
							$defenderSum['soldier_num'] += $_unitData['soldier_num'];
							$defenderSum['kill_num'] += $_unitData['kill_num'];
							$defenderSum['killed_num'] += $_unitData['killed_num'];
							$defenderSum['injure_num'] += $_unitData['injure_num'];
							$defenderSum['live_num'] += $_unitData['live_num'];
						}
					}
				}
			}
			$defenderSum['players'] = array_values($defenderData);
		}elseif($type != 3 && $type != 10){
			$pg = $Guild->getByPlayerId($defenderIds[0]);
			if($pg){
				$_guild = ['name'=>$pg['name'], 'short'=>$pg['short_name']];
			}else{
				$_guild = ['name'=>'', 'short'=>''];
			}
			$_player = $this->getPlayer($defenderIds[0]);
			$defenderSum = [
				'nick'=>$_player['nick'].'',
				'avatar'=>$_player['avatar_id']*1,
				'x'=>$_player['x'],
				'y'=>$_player['y'],
				'power'=>0,
				'guild_name'=>$_guild['name'],
				'guild_short_name'=>$_guild['short'],
				'soldier_num'=>0,
				'kill_num'=>0,
				'killed_num'=>0,
				'injure_num'=>0,
				'live_num'=>0,
				'trap_lost'=>0,//$defenderData[$defenderIds[0]]['unit']['trap']['killed_num']*1,
				'buff'=>$PlayerBuff->getBattleBuff($defenderIds[0]),
				'oldDurability'=>0,
				'newDurability'=>0,
				'protectOpen'=>@$extraData['protectOpen']*1,
			];
			//foreach($defenderData as $_playerId =>&$_playerData){
			foreach($defenderIds as $_playerId){
				if(isset($defenderData[$_playerId])){
					$_player = $this->getPlayer($_playerId);
					$defenderSum['power'] += $defenderData[$_playerId]['power'];
					$defenderData[$_playerId]['player_id'] = $_playerId*1;
					$defenderData[$_playerId]['nick'] = $_player['nick'];
					/*if(!@$players[$_playerId]){
						$players[$_playerId] = $Player->getByPlayerId($_playerId);
					}*/
					$defenderData[$_playerId]['avatar'] = $_player['avatar_id'];
					foreach($defenderData[$_playerId]['unit'] as $_k => $_unitData){
						//if(in_array($_k, ['trap', 'tower'])) continue;
						if($_k === 'trap'){
							foreach($_unitData as $_trap){
								$defenderSum['trap_lost'] += $_trap['killed_num'];
								$defenderSum['kill_num'] += $_trap['kill_num'];
							}
						}else{
							$defenderSum['soldier_num'] += $_unitData['soldier_num'];
							$defenderSum['kill_num'] += $_unitData['kill_num'];
							$defenderSum['killed_num'] += $_unitData['killed_num'];
							$defenderSum['injure_num'] += $_unitData['injure_num'];
							$defenderSum['live_num'] += $_unitData['live_num'];
						}
					}
				}/*else{
					$defenderData[$_playerId] = [];
				}*/
			}
			$defenderSum['players'] = array_values($defenderData);
		}else{
			//获取联盟名
			$Guild = new Guild;
			$guild = $Guild->getGuildInfo($toGuildId);
			$defenderSum = [
				'nick'=>@$guild['name'].'',
				'avatar'=>101,
				'x'=>$x,
				'y'=>$y,
				'power'=>0,
				'guild_name'=>@$guild['name'].'',
				'guild_short_name'=>@$guild['short_name'].'',
				'soldier_num'=>0,
				'kill_num'=>0,
				'killed_num'=>0,
				'injure_num'=>0,
				'live_num'=>0,
				'trap_lost'=>0,//$defenderData[$defenderIds[0]]['unit']['trap']['killed_num']*1,
				'buff'=>[],
				'oldDurability'=>@$extraData['oldDurability']*1,
				'newDurability'=>@$extraData['newDurability']*1,
			];
			$mainPlayerId = 0;
			foreach($defenderData as $_playerId =>&$_playerData){
				if(!$mainPlayerId){
					$mainPlayerId = $_playerId;
				}
				if(isset($defenderData[$_playerId])){
					$_player = $this->getPlayer($_playerId);
					$defenderSum['power'] += $defenderData[$_playerId]['power'];
					$defenderData[$_playerId]['player_id'] = $_playerId*1;
					$defenderData[$_playerId]['nick'] = $_player['nick'];
					/*if(!@$players[$_playerId]){
						$players[$_playerId] = $Player->getByPlayerId($_playerId);
					}*/
					$defenderData[$_playerId]['avatar'] = $_player['avatar_id'];
					foreach($defenderData[$_playerId]['unit'] as $_k => $_unitData){
						//if(in_array($_k, ['trap', 'tower'])) continue;
						if($_k === 'trap'){
							foreach($_unitData as $_trap){
								$defenderSum['trap_lost'] += $_trap['killed_num'];
								$defenderSum['kill_num'] += $_trap['kill_num'];
							}
						}else{
							$defenderSum['soldier_num'] += $_unitData['soldier_num'];
							$defenderSum['kill_num'] += $_unitData['kill_num'];
							$defenderSum['killed_num'] += $_unitData['killed_num'];
							$defenderSum['injure_num'] += $_unitData['injure_num'];
							$defenderSum['live_num'] += $_unitData['live_num'];
						}
					}
				}
			}
			$defenderSum['players'] = array_values($defenderData);
			
			//获取联盟成员
			$PlayerGuild = new PlayerGuild;
			$members = $PlayerGuild->getAllGuildMember($toGuildId);
			$defenderIds = array_keys($members);
			if($mainPlayerId){
				$defenderSum['buff'] = $PlayerBuff->getBattleBuff($mainPlayerId);
			}
		}
		$defenderSum['power_lost'] = @$extraData['dLosePower']*1;
		
		
		//循环发邮件
		foreach(['a'=>$attackerIds, 'd'=>$defenderIds] as $_k => $_playerIds){
			$_result = '';
			$_data = [
				'x'=>$x,
				'y'=>$y,
				//'win'=>$_result,
				'type'=>$type,
				//'resource'=>$getResource,
			];
			$_item = [];

			if($type == 1){//攻城
				if($_k == 'a' && $result){//攻击方胜利
					$mailType = self::TYPE_ATTACKCITYWIN;
					$_result = true;
				}elseif($_k == 'a' && !$result){//攻击方失败
					$mailType = self::TYPE_ATTACKCITYLOSE;
					$_result = false;
				}elseif($_k == 'd' && !$result){//防守方胜利
					$mailType = self::TYPE_DEFENCECITYWIN;
					$_result = true;
				}elseif($_k == 'd' && $result){//防守方失败
					$mailType = self::TYPE_DEFENCECITYLOSE;
					$_result = false;
				}
			}elseif($type == 7){
				if($result){
					$_result = true;
					$mailType = self::TYPE_ATTACKNPCWIN;
				}else{
					$_result = false;
					$mailType = self::TYPE_ATTACKNPCLOSE;
				}
			}elseif($type == 8){
				$_data['boss_lost_hp'] = $extraData['boss_lost_hp'];
				$_data['boss_left_hp'] = $extraData['boss_left_hp'];
				if($result){
					$_result = true;
					$mailType = self::TYPE_ATTACKBOSSWIN;
				}else{
					$_result = false;
					$mailType = self::TYPE_ATTACKBOSSLOSE;
				}
			}else{//撞田
				if($_k == 'a' && $result){//攻击方胜利
					$mailType = self::TYPE_ATTACKARMYWIN;
					$_result = true;
				}elseif($_k == 'a' && !$result){//攻击方失败
					$mailType = self::TYPE_ATTACKARMYLOSE;
					$_result = false;
				}elseif($_k == 'd' && !$result){//防守方胜利
					$mailType = self::TYPE_DEFENCEARMYWIN;
					$_result = true;
				}elseif($_k == 'd' && $result){//防守方失败
					$mailType = self::TYPE_DEFENCEARMYLOSE;
					$_result = false;
				}
			}
			$_data['win'] = $_result;
			
			if(!$result && $allDead && $_k == 'a' && in_array($type, range(1, 6))){
				$_data['all_dead'] = true;
			}else{
				$_data['all_dead'] = false;
			}
			if($_k == 'a'){
				$_data1 = $attackerSum;
				$_data2 = $defenderSum;
				$_data['resource'] = $resource1 ? $resource1 : [];
				if(@$extraData['godGeneralSkillArr']['attack'])
					$_data1['godGeneralSkillArr'] = $extraData['godGeneralSkillArr']['attack'];
				if(@$extraData['godGeneralSkillArr']['defend'])
					$_data2['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['defend'];
				if(isset($extraData['noobProtect']))
					$_data2['noobProtect'] = @$extraData['noobProtect'];
				/*if(in_array($type, [1]) && $_playerIds && isset($getResource[$_playerIds])){
					$_data['resource'] = $getResource[$_playerIds];
				}elseif(!in_array($type, [1])){
					$_data['resource'] = $resource1;
				}*/
			}else{
				$_data1 = $defenderSum;
				$_data2 = $attackerSum;
				$_data['resource'] = $resource2 ? $resource2 : [];
				if(@$extraData['godGeneralSkillArr']['defend'])
					$_data1['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['defend'];
				if(@$extraData['godGeneralSkillArr']['attack'])
					$_data2['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['attack'];
				if(isset($extraData['noobProtect']))
					$_data1['noobProtect'] = @$extraData['noobProtect'];
			}
			$_data['player1'] = $_data1;//我方
			$_data['player2'] = $_data2;//敌方
			if(isset($extraData['item']) && $_playerIds && isset($extraData['item'][$_playerIds[0]])){
				$_item = $extraData['item'][$_playerIds[0]];
			}
			if($type == 10){//黄金起义-杀死npc百分比
				$_data['killPercent'] = $extraData['killPercent'];
			}
			$_data['item'] = $_item;
			//更新战报详情
			if(@$extraData['battleLogId'] && $_k == 'a'){
				(new GuildBattleLog)->updateDetail($extraData['battleLogId'], $_data);
			}
			$_data['battleLogId'] = @$extraData['battleLogId']*1;
			
			//发送邮件
			if($type == 9 || $type == 10){//据点战/黄巾起义 不发送邮件
				return true;
			}
			if($_data['all_dead']){
				unset($_data['resource']);
				unset($_data['player1']);
				unset($_data['player2']);
			}
			if($_k == 'a' && /*in_array($type, [1]) &&*/ count($_playerIds) > 1){//集结攻城抢资源
				foreach($_playerIds as $_playerId){
					$_data['resource'] = $getResource[$_playerId] ? $getResource[$_playerId] : [];
					$_data['item'] = (@$extraData['item'][$_playerId] ? $extraData['item'][$_playerId] : []);
					if(!$this->sendSystem($_playerId, $mailType, '', '', 0, $_data, [])){
						return false;
					}
				}
			}else{
				if(!$this->sendSystem($_playerIds, $mailType, '', '', 0, $_data, [])){
					return false;
				}
			}
		}
		return true;
	}
	
    /**
     * 跨服战邮件
	 * type：1,.城池战，2.部队战
     * 
     * @param <type> $attackerIds 
     * @param <type> $defenderIds 
     * @param <type> $result 
     * @param <type> $type 
     * @param <type> $x 
     * @param <type> $y 
     * @param <type> $attackerData 
     * @param <type> $defenderData 
     * @param <type> $allDead  
     * @param <type> $extraData  
     * 
     * @return <type>
     */
	public function sendCrossBattleMail($side, $attackerIds, $defenderIds, $result, $type, $x, $y, $attackerData, $defenderData, $allDead = false, $extraData = []){
		//计算攻击方归类
		$attackerSum = [
			'nick'=>'',
			'avatar'=>0,
			'x'=>0,
			'y'=>0,
			'power'=>0,
			'guild_name'=>$extraData['guild_1_name'],
			'guild_short_name'=>$extraData['guild_1_short'],
			'soldier_num'=>0,
			'kill_num'=>0,
			'killed_num'=>0,
			'injure_num'=>0,
			'live_num'=>0,
			'trap_lost'=>0,
			'buff'=>[],
		];
		foreach($attackerData as $_playerId =>&$_playerData){
			if(!$attackerSum['nick']){
				$attackerSum['nick'] = $_playerData['nick'];
				$attackerSum['avatar'] = $_playerData['avatar'];
				$attackerSum['x'] = $_playerData['x'];
				$attackerSum['y'] = $_playerData['y'];
			}
			$attackerSum['power'] += $_playerData['power'];
			$_playerData['player_id'] = $_playerId*1;
			//$_playerData['nick'] = $_player['nick'];
			//$_playerData['avatar'] = $_player['avatar_id'];
			foreach($_playerData['unit'] as $_unitData){
				$attackerSum['soldier_num'] += $_unitData['soldier_num'];
				$attackerSum['kill_num'] += $_unitData['kill_num'];
				$attackerSum['killed_num'] += $_unitData['killed_num'];
				$attackerSum['injure_num'] += $_unitData['injure_num'];
				$attackerSum['live_num'] += $_unitData['live_num'];
			}
		}
		unset($_playerData);
		$attackerSum['players'] = array_values($attackerData);
		$attackerSum['power_lost'] = @$extraData['aLosePower']*1;
		
		
		$defenderSum = [
			'nick'=>'',
			'avatar'=>0,
			'x'=>0,
			'y'=>0,
			'power'=>0,
			'guild_name'=>$extraData['guild_2_name'],
			'guild_short_name'=>$extraData['guild_2_short'],
			'soldier_num'=>0,
			'kill_num'=>0,
			'killed_num'=>0,
			'injure_num'=>0,
			'live_num'=>0,
			'trap_lost'=>0,
			'buff'=>[],
			'oldDurability'=>0,
			'newDurability'=>0,
		];
		if(in_array($type, [11, 12])){
			foreach($defenderIds as $_playerId){
				if(isset($defenderData[$_playerId])){
					$_playerData = $defenderData[$_playerId];
					if(!$defenderSum['nick']){
						$defenderSum['nick'] = $_playerData['nick'];
						$defenderSum['avatar'] = $_playerData['avatar'];
						$defenderSum['x'] = $_playerData['x'];
						$defenderSum['y'] = $_playerData['y'];
					}
					$defenderSum['power'] += @$_playerData['power'];
					$defenderData[$_playerId]['player_id'] = $_playerId*1;
					//$defenderData[$_playerId]['nick'] = $_player['nick'];
					//$defenderData[$_playerId]['avatar'] = $_player['avatar_id'];
					if(@$defenderData[$_playerId]['unit']){
						foreach($defenderData[$_playerId]['unit'] as $_k => $_unitData){
							//if(in_array($_k, ['trap', 'tower'])) continue;
							if($_k === 'trap'){
								foreach($_unitData as $_trap){
									$defenderSum['trap_lost'] += $_trap['killed_num'];
									$defenderSum['kill_num'] += $_trap['kill_num'];
								}
							}else{
								$defenderSum['soldier_num'] += $_unitData['soldier_num'];
								$defenderSum['kill_num'] += $_unitData['kill_num'];
								$defenderSum['killed_num'] += $_unitData['killed_num'];
								$defenderSum['injure_num'] += $_unitData['injure_num'];
								$defenderSum['live_num'] += $_unitData['live_num'];
							}
						}
					}
				}/*else{
					$defenderData[$_playerId] = [];
				}*/
			}
		}else{
			$defenderSum['avatar'] = $extraData['element_id']*1;
			$defenderSum['x'] = $x;
			$defenderSum['y'] = $y;
		}
		if(in_array($type, [11, 13, 14])){
			$defenderSum['oldDurability'] = @$extraData['oldDurability']*1;
			$defenderSum['newDurability'] = @$extraData['newDurability']*1;
			$defenderSum['durabilityMax'] = @$extraData['durabilityMax']*1;
		}
		$defenderSum['players'] = array_values($defenderData);
		$defenderSum['power_lost'] = @$extraData['dLosePower']*1;
		
		
		//发邮件
		$_result = '';
		$_data = [
			'x'=>$x*1,
			'y'=>$y*1,
			//'win'=>$_result,
			'type'=>$type,
			//'resource'=>$getResource,
			'battle_id'=>$extraData['battleId'],
		];
		$_item = [];

		if($type == 11){//攻城
			if($side == 'attack' && $result){//攻击方胜利
				$mailType = self::TYPE_CROSSATTACKCITYWIN;
				$_result = true;
			}elseif($side == 'attack' && !$result){//攻击方失败
				$mailType = self::TYPE_CROSSATTACKCITYLOSE;
				$_result = false;
			}elseif($side == 'defend' && !$result){//防守方胜利
				$mailType = self::TYPE_CROSSDEFENCECITYWIN;
				$_result = true;
			}elseif($side == 'defend' && $result){//防守方失败
				$mailType = self::TYPE_CROSSDEFENCECITYLOSE;
				$_result = false;
			}
		}elseif($type == 12){//投石车
			if($side == 'attack' && $result){//攻击方胜利
				$mailType = self::TYPE_CROSSATTACKARMYWIN;
				$_result = true;
			}elseif($side == 'attack' && !$result){//攻击方失败
				$mailType = self::TYPE_CROSSATTACKARMYLOSE;
				$_result = false;
			}elseif($side == 'defend' && !$result){//防守方胜利
				$mailType = self::TYPE_CROSSDEFENCEARMYWIN;
				$_result = true;
			}elseif($side == 'defend' && $result){//防守方失败
				$mailType = self::TYPE_CROSSDEFENCEARMYLOSE;
				$_result = false;
			}
		}elseif($type == 13){//城门
			$mailType = self::TYPE_CROSSATTACKDOOR;
			$_result = $result;
		}elseif($type == 14){//大本营
			$mailType = self::TYPE_CROSSATTACKBASE;
			$_result = $result;
		}else{
			return false;
		}
		$_data['win'] = $_result;
		
		if(!$result && $allDead && $_k == 'a'){
			$_data['all_dead'] = true;
		}else{
			$_data['all_dead'] = false;
		}
		if($side == 'attack'){
			$_data1 = $attackerSum;
			$_data2 = $defenderSum;
			$_data['resource'] = [];
			if(@$extraData['godGeneralSkillArr']['attack'])
				$_data1['godGeneralSkillArr'] = $extraData['godGeneralSkillArr']['attack'];
			if(@$extraData['godGeneralSkillArr']['defend'])
				$_data2['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['defend'];
		}else{
			$_data1 = $defenderSum;
			$_data2 = $attackerSum;
			$_data['resource'] = [];
			if(@$extraData['godGeneralSkillArr']['defend'])
				$_data1['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['defend'];
			if(@$extraData['godGeneralSkillArr']['attack'])
				$_data2['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['attack'];
		}
		$_data['player1'] = $_data1;//我方
		$_data['player2'] = $_data2;//敌方
		$_data['item'] = [];
		
		if($_data['all_dead']){
			unset($_data['resource']);
			unset($_data['player1']);
			unset($_data['player2']);
		}
		if($side == 'attack'){
			$playerIds = $attackerIds;
		}else{
			$playerIds = $defenderIds;
		}
		if(!$this->sendSystem($playerIds, $mailType, '', '', 0, $_data, [])){
			return false;
		}
		return true;
	}
	
	/**
     * 城战邮件
	 * type：1,.城池战，2.部队战
     * 
     * @param <type> $attackerIds 
     * @param <type> $defenderIds 
     * @param <type> $result 
     * @param <type> $type 
     * @param <type> $x 
     * @param <type> $y 
     * @param <type> $attackerData 
     * @param <type> $defenderData 
     * @param <type> $allDead  
     * @param <type> $extraData  
     * 
     * @return <type>
     */
	public function sendCityBattleMail($side, $attackerIds, $defenderIds, $result, $type, $x, $y, $attackerData, $defenderData, $allDead = false, $extraData = []){
		//计算攻击方归类
		$attackerSum = [
			'nick'=>'',
			'avatar'=>0,
			'x'=>0,
			'y'=>0,
			'power'=>0,
			'camp_id'=>$extraData['camp_attack']*1,
			'soldier_num'=>0,
			'kill_num'=>0,
			'killed_num'=>0,
			'injure_num'=>0,
			'live_num'=>0,
			'trap_lost'=>0,
			'buff'=>[],
		];
		foreach($attackerData as $_playerId =>&$_playerData){
			if(!$attackerSum['nick']){
				$attackerSum['nick'] = $_playerData['nick'];
				$attackerSum['avatar'] = $_playerData['avatar'];
				$attackerSum['x'] = $_playerData['x'];
				$attackerSum['y'] = $_playerData['y'];
			}
			$attackerSum['power'] += $_playerData['power'];
			$_playerData['player_id'] = $_playerId*1;
			//$_playerData['nick'] = $_player['nick'];
			//$_playerData['avatar'] = $_player['avatar_id'];
			foreach($_playerData['unit'] as $_unitData){
				$attackerSum['soldier_num'] += $_unitData['soldier_num'];
				$attackerSum['kill_num'] += $_unitData['kill_num'];
				$attackerSum['killed_num'] += $_unitData['killed_num'];
				$attackerSum['injure_num'] += $_unitData['injure_num'];
				$attackerSum['live_num'] += $_unitData['live_num'];
			}
		}
		unset($_playerData);
		$attackerSum['players'] = array_values($attackerData);
		$attackerSum['power_lost'] = @$extraData['aLosePower']*1;
		
		
		$defenderSum = [
			'nick'=>'',
			'avatar'=>0,
			'x'=>0,
			'y'=>0,
			'power'=>0,
			'camp_id'=>$extraData['camp_defend']*1,
			'soldier_num'=>0,
			'kill_num'=>0,
			'killed_num'=>0,
			'injure_num'=>0,
			'live_num'=>0,
			'trap_lost'=>0,
			'buff'=>[],
			'oldDurability'=>0,
			'newDurability'=>0,
		];
		if(in_array($type, [11, 12])){
			foreach($defenderIds as $_playerId){
				if(isset($defenderData[$_playerId])){
					$_playerData = $defenderData[$_playerId];
					if(!$defenderSum['nick']){
						$defenderSum['nick'] = $_playerData['nick'];
						$defenderSum['avatar'] = $_playerData['avatar'];
						$defenderSum['x'] = $_playerData['x'];
						$defenderSum['y'] = $_playerData['y'];
					}
					$defenderSum['power'] += @$_playerData['power'];
					$defenderData[$_playerId]['player_id'] = $_playerId*1;
					//$defenderData[$_playerId]['nick'] = $_player['nick'];
					//$defenderData[$_playerId]['avatar'] = $_player['avatar_id'];
					if(@$defenderData[$_playerId]['unit']){
						foreach($defenderData[$_playerId]['unit'] as $_k => $_unitData){
							//if(in_array($_k, ['trap', 'tower'])) continue;
							if($_k === 'trap'){
								foreach($_unitData as $_trap){
									$defenderSum['trap_lost'] += $_trap['killed_num'];
									$defenderSum['kill_num'] += $_trap['kill_num'];
								}
							}else{
								$defenderSum['soldier_num'] += $_unitData['soldier_num'];
								$defenderSum['kill_num'] += $_unitData['kill_num'];
								$defenderSum['killed_num'] += $_unitData['killed_num'];
								$defenderSum['injure_num'] += $_unitData['injure_num'];
								$defenderSum['live_num'] += $_unitData['live_num'];
							}
						}
					}
				}/*else{
					$defenderData[$_playerId] = [];
				}*/
			}
		}else{
			$defenderSum['avatar'] = $extraData['element_id']*1;
			$defenderSum['x'] = $x;
			$defenderSum['y'] = $y;
		}
		if(in_array($type, [11, 13, 14])){
			$defenderSum['oldDurability'] = @$extraData['oldDurability']*1;
			$defenderSum['newDurability'] = @$extraData['newDurability']*1;
			$defenderSum['durabilityMax'] = @$extraData['durabilityMax']*1;
		}
		$defenderSum['players'] = array_values($defenderData);
		$defenderSum['power_lost'] = @$extraData['dLosePower']*1;
		
		
		//发邮件
		$_result = '';
		$_data = [
			'x'=>$x*1,
			'y'=>$y*1,
			//'win'=>$_result,
			'type'=>$type,
			//'resource'=>$getResource,
			'battle_id'=>$extraData['battleId'],
		];
		$_item = [];

		if($type == 11){//攻城
			if($side == 'attack' && $result){//攻击方胜利
				$mailType = self::TYPE_CB_ATTACKCITYWIN;
				$_result = true;
			}elseif($side == 'attack' && !$result){//攻击方失败
				$mailType = self::TYPE_CB_ATTACKCITYLOSE;
				$_result = false;
			}elseif($side == 'defend' && !$result){//防守方胜利
				$mailType = self::TYPE_CB_DEFENCECITYWIN;
				$_result = true;
			}elseif($side == 'defend' && $result){//防守方失败
				$mailType = self::TYPE_CB_DEFENCECITYLOSE;
				$_result = false;
			}
		}elseif($type == 12){//投石车
			if($side == 'attack' && $result){//攻击方胜利
				$mailType = self::TYPE_CB_ATTACKARMYWIN;
				$_result = true;
			}elseif($side == 'attack' && !$result){//攻击方失败
				$mailType = self::TYPE_CB_ATTACKARMYLOSE;
				$_result = false;
			}elseif($side == 'defend' && !$result){//防守方胜利
				$mailType = self::TYPE_CB_DEFENCEARMYWIN;
				$_result = true;
			}elseif($side == 'defend' && $result){//防守方失败
				$mailType = self::TYPE_CB_DEFENCEARMYLOSE;
				$_result = false;
			}
		}elseif($type == 13){//城门
			$mailType = self::TYPE_CB_ATTACKDOOR;
			$_result = $result;
		}else{
			return false;
		}
		$_data['win'] = $_result;
		
		if(!$result && $allDead && $_k == 'a'){
			$_data['all_dead'] = true;
		}else{
			$_data['all_dead'] = false;
		}
		if($side == 'attack'){
			$_data1 = $attackerSum;
			$_data2 = $defenderSum;
			$_data['resource'] = [];
			if(@$extraData['godGeneralSkillArr']['attack'])
				$_data1['godGeneralSkillArr'] = $extraData['godGeneralSkillArr']['attack'];
			if(@$extraData['godGeneralSkillArr']['defend'])
				$_data2['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['defend'];
		}else{
			$_data1 = $defenderSum;
			$_data2 = $attackerSum;
			$_data['resource'] = [];
			if(@$extraData['godGeneralSkillArr']['defend'])
				$_data1['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['defend'];
			if(@$extraData['godGeneralSkillArr']['attack'])
				$_data2['godGeneralSkillArr'] = @$extraData['godGeneralSkillArr']['attack'];
		}
		$_data['player1'] = $_data1;//我方
		$_data['player2'] = $_data2;//敌方
		$_data['item'] = [];
		
		if($_data['all_dead']){
			unset($_data['resource']);
			unset($_data['player1']);
			unset($_data['player2']);
		}
		if($side == 'attack'){
			$playerIds = $attackerIds;
		}else{
			$playerIds = $defenderIds;
		}
		if(!$this->sendSystem($playerIds, $mailType, '', '', 0, $_data, [])){
			return false;
		}
		return true;
	}
	
	public function getPlayer($playerId){
		if(isset($this->playerData[$playerId])){
			return $this->playerData[$playerId];
		}
		return $this->playerData[$playerId] = (new Player)->getByPlayerId($playerId);
	}

	public function getCataByType($type){
		foreach($this->cataType as $_cata => $_data){
			if(in_array($type, $_data))
				return $_cata;
		}
		return false;
	}
}