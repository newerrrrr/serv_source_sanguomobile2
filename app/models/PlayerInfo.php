<?php

class PlayerInfo extends ModelBase{
    /**
     * 暂定的冗余字段
     *  level_animation
     *  email
     * @var array
     */
    public $blacklist = ['create_time', 'update_time', 'login_hashcode', 'login_channel', 'download_channel', 'pay_channel', 'platform', 'device_mode', 'system_version', 'level_animation','email'];
    public $bowlType1 = [//占星drop_group
                         1=>'bowl_counter_drop_group_1',
                         2=>'bowl_counter_drop_group_2'
    ];
    public $bowlType2 = [//天陨drop_group
                         10=>'bowl_counter_drop_group_10',
                         11=>'bowl_counter_drop_group_11',
                         12=>'bowl_counter_drop_group_12',
                         14=>'bowl_counter_drop_group_14',
    ];
    /**
     * 玩家静态数据表，存非频繁改动的字段
     * @param  int $playerId player id
     * @return bool           sucess or not
     */
    public function newPlayerInfo($playerId, $data){
        $self                   = new self;
        $self->player_id        = $playerId;
        $self->login_hashcode   = loginHashMethod($playerId);
        $self->login_channel    = $data['login_channel'];
        $self->download_channel = $data['download_channel'];
        $self->pay_channel      = $data['pay_channel'];
        $self->platform         = $data['platform'];
        $self->device_mode      = $data['device_mode'];
        $self->system_version   = $data['system_version'];
        $self->create_time      = $this->update_time = date('Y-m-d H:i:s', time());
        $self->save();
        return $self->id;
    }

    /**
     * 通过id获取玩家信息
     *
     * @return $player array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $r = Cache::getPlayer($playerId, __CLASS__);
        if(!$r) {
            $re = self::findFirst(["player_id=:playerId:", 'bind'=>['playerId'=>$playerId]]);
            if($re) {
                $re = $re->toArray();
                $r = $this->adapter($re, true);
                Cache::setPlayer($playerId, __CLASS__, $r);
            } else {
                return [];
            }
        }
		$r['first_pay'] = parseArray($r['first_pay']);
		$r['newbie_login'] = parseArray($r['newbie_login']);
		$r['general_star_reward'] = parseArray($r['general_star_reward'], true);

        if($r['sacrifice_time']>strtotime(date('Y-m-d 00:00:00'))) {//祭天是否免费
            $r['sacrifice_free_flag'] = 0;
        } else {
            $r['sacrifice_free_flag'] = 1;
        }

        $startTimestamp     = (new Configure)->getValueByKey('server_start_time');
        $startTimestampZero = strtotime(date('Y-m-d 00:00:00', $startTimestamp));
        $subDay             = ceil((time() - $startTimestampZero) / (24 * 60 * 60));
        $r['sub_day']       = $subDay;

        if($forDataFlag) {
            return filterFields([$r], $forDataFlag, $this->blacklist)[0];
        } else {
            return $r;
        }
    }

    /**
     * 更改player_info表的值
     * @param  int $playerId 
     * @param  array  $fields  
     */
    public function alter($playerId, array $fields){
        $re = self::findFirst("player_id=$playerId");
        if(!$re) return null;
        if(!array_key_exists('update_time', $fields)){
            $fields['update_time'] = date('Y-m-d H:i:s');
        }
		if(array_key_exists('newbie_login', $fields)){
            $fields['newbie_login'] = join(',', $fields['newbie_login']);
        }
		if(array_key_exists('general_star_reward', $fields)){
            $fields['general_star_reward'] = join(',', $fields['general_star_reward']);
        }
        $re->save($fields);
        $this->clearDataCache($playerId);
		return true;
    }
	
	public function updateGiftBeginTime($playerId, $type){
		$this->alter($playerId, [$type=>date('Y-m-d H:i:s')]);
		$re = self::findFirst("player_id=$playerId");
		Cache::delPlayer($playerId, 'buyGiftLists-'.$re->pay_channel);
	}

    /**
     * 是否拥有至尊卡
     * @param  int $playerId 
     * @return bool           
     */
    public function haveLongCard($playerId){
        $re = $this->getByPlayerId($playerId);
        return ($re['long_card']==1) ? true : false;
    }
    /**
     * 是否拥有月卡
     * @param  int $playerId 
     * @return bool           
     */
    public function haveMonthCard($playerId){
        $re = $this->getByPlayerId($playerId);
        $monthCardDeadline = $re['month_card_deadline'];
        return ($monthCardDeadline>time()) ? true : false;
    }
    /**
     * 获取至尊卡奖励
     * @param  int $playerId 
     * @return int           
     */
    public function getLongCardAward($playerId){
        if($this->haveLongCard($playerId)) {
            $re = $this->getByPlayerId($playerId);
            $longCardDate = $re['long_card_date'];
            if(date('Y-m-d 00:00:00', $longCardDate)==date("Y-m-d 00:00:00")) {//当天已经领过
                return -2;
            } else {//开始领取
                $awardNum = (new Starting)->dicGetOne('vip_card_daily_reward');
                (new Player)->updateGem($playerId, $awardNum, true, '至尊卡奖励');
                $this->alter($playerId, ['long_card_date'=>date('Y-m-d H:i:s')]);
            }
        } else {
            return -1;//没有至尊卡
        }
        return 1;//成功领取
    }
    /**
     * 获取月卡奖励
     * @param  int $playerId 
     * @return int           
     */
    public function getMonthCardAward($playerId){
        if($this->haveMonthCard($playerId)) {
            $re = $this->getByPlayerId($playerId);
            $monthCardDate = $re['month_card_date'];
            if(date('Y-m-d 00:00:00', $monthCardDate)==date("Y-m-d 00:00:00")) {//当天已经领过
                return -2;
            } else {//开始领取
                $awardNum = (new Starting)->dicGetOne('month_card_daily_reward');
                (new Player)->updateGem($playerId, $awardNum, true, '月卡奖励');
                $this->alter($playerId, ['month_card_date'=>date('Y-m-d H:i:s')]);
            }
        } else {
            return -1;//没有月卡
        }
        return 1;//成功领取
    }
    /**
     * 是否在禁言期间,如果是,则返回禁言截止日期
     * @param  int $playerId 
     * @return int           
     */
    public function getBanMsgTime($playerId){
        $info = $this->getByPlayerId($playerId);
        $banMsgTime = $info['ban_msg_time'];
        if($banMsgTime && $banMsgTime>=time()) {//禁言ing
            return $banMsgTime;
        }
        return false;
    }
    /**
     * 是否在封号期间,如果是,则返回封号截止日期
     * @param  int $playerId 
     * @return int           
     */
    public function getBanTime($playerId){
        $info = $this->getByPlayerId($playerId);
        $banTime = $info['ban_time'];
        if($banTime && $banTime>=time()) {//禁言ing
            return $banTime;
        }
        return false;
    }
    /**
     * 更新facebook分享次数,首次获得分享奖励
     * @param  int $playerId 
     */
    public function facebookShare($playerId){
        $info               = $this->getByPlayerId($playerId);
        $facebookShareCount = $info['facebook_share_count'];
        if($facebookShareCount<1) {
            $Drop     = new Drop;
            $Activity = new Activity;
            $activity = $Activity->dicGetOne(1016);
            $Drop->gain($playerId, $activity['drop'], 1, '更新facebook分享次数,首次获得分享奖励');
        }
        $this->alter($playerId, ['facebook_share_count'=>$info['facebook_share_count']+1]);
    }
    /**
     * 大额充值发送邮件
     * @param  int $playerId 
     */
    public function sendBigDealMailOnce($playerId){
        $info = $this->getByPlayerId($playerId);
        $bigDealMail = $info['big_deal_mail'];
        if($bigDealMail==0) {
            $sql = "SELECT SUM(COUNT) `sum` FROM player_order a,pricing b WHERE a.player_id={$playerId} AND a.status=1 AND a.payment_code=b.payment_code";
            $re = $this->sqlGet($sql);
            $alreadyPay = 0;
            if(!empty($re)) {
                $alreadyPay = $re[0]['sum'];
            }
            $hugeSumPay = (new Starting)->dicGetOne('huge_sum_pay');

            $sendFlag = ($alreadyPay>=$hugeSumPay);//金额是否已经达到要求
            if($sendFlag) {//send mail 
                (new PlayerMail)->sendSystem($playerId, PlayerMail::TYPE_BIG_DEAL, 'system mail', '');
                $this->alter($playerId, ['big_deal_mail'=>1]);
            }
        }
    }
    /**
     * @param $playerId
     * @param $type
     * @param $dropGroup
     *
     * @return mixed
     *
     * 获取玩家聚宝盆对应type和dropGroup的计数器
     */
    public function getbowlByCounter($playerId, $type, $dropGroup){
        $info             = $this->getByPlayerId($playerId);
        $Astrology        = new Astrology;
        $dropGroupGain100 = $Astrology->dropGroupGain100;
        $fields = ($type==1) ? $this->bowlType1 : $this->bowlType2;
        if(in_array($dropGroup, $dropGroupGain100)) {//概率100%，必掉
            $astrology   = $Astrology->getByCounter($dropGroup);
            $otherFields = [];
            foreach($fields as $v) {
                $otherFields[$v] = $info[$v];
            }
            $astrology['current_field'] = [];
            $astrology['other_field'] = $otherFields;
        } else {
            $currentField = $fields[$dropGroup];
            unset($fields[$dropGroup]);
            if($dropGroup==$Astrology::$onceDropType) {//特殊掉落逻辑
                unset($fields['bowl_counter_drop_group_12']);
            }
            if($info['bowl_counter_drop_group_14_status']==1) {
                unset($fields['bowl_counter_drop_group_14']);
            } else {
                unset($fields['bowl_counter_drop_group_12']);
            }
            $otherFields = [];
            foreach($fields as $v) {
                $otherFields[$v] = $info[$v];
            }
            $currentCounter             = $info[$currentField];
            $astrology                  = $Astrology->getByCounter($dropGroup, $currentCounter);
            $astrology['current_field'] = ['name'=>$currentField, 'value'=>$currentCounter];//当前计数器
            $astrology['other_field']   = $otherFields;//其他计数器
        }
        return $astrology;
    }
    /**
     * @param $playerId
     * @param $type
     *
     * @return bool
     *
     * 更新免费时间
     */
    public function updateBowlFreeLastTime($playerId, $type){
        $info     = $this->getByPlayerId($playerId);
        $Starting = new Starting;
        if($type==1) {//占星
            $field            = 'bowl_type1_last_time';
            $bowlTypeLastTime = $info[$field];
            $duration         = $Starting->getValueByKey('astrology_free_time');//占星免费时间
            $times            = $Starting->getValueByKey('astrology_free_num');//占星最大免费次数
        } elseif($type==2) {//天陨
            $field            = 'bowl_type2_last_time';
            $bowlTypeLastTime = $info[$field];
            $duration         = $Starting->getValueByKey('high_astrology_free_time');//天陨免费时间
            $times            = $Starting->getValueByKey('high_astrology_free_num');//天陨最大免费次数
        } else {
            return false;
        }
        $subTime    = time() - $bowlTypeLastTime;
        $subTimes1  = floor($subTime / $duration);
        $isLastFlag = ($subTimes1 == 1) ? true : false;//最后一次
        $subTimes   = min($subTimes1, $times);
        if($subTime>=$duration) {//没到免费时间
            if($isLastFlag) {
                $updateTime = $info[$field] + $duration;
            }
            else {
                $updateTime = time() - ($subTimes - 1) * $duration;
            }
            $this->alter($playerId, [$field => date('Y-m-d H:i:s', $updateTime)]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 祭天免费时间
     *
     * @param $playerId
     *
     * @return bool
     */
    public function updateSacrificeTime($playerId){
        $info = $this->getByPlayerId($playerId);
        if($info['sacrifice_free_flag']==1) {//可以免费
            $this->alter($playerId, ['sacrifice_time'=>date('Y-m-d H:i:s'), 'sacrifice_flag'=>1]);//半价标识
            return true;
        }
        return false;
    }

	public function updateNewbiePay($playerId, $flag){
		//检查是否新手期内
		if($flag == 1){
			$player = (new Player)->getByPlayerId($playerId);
			$days = (time() - $player['create_time']) / (3600*24);
			$days++;
			if($days > (new Starting)->dicGetOne('act_server_time')*1){
				return true;
			}
		}
		$ret = $this->updateAll(['newbie_pay'=>$flag], ['player_id'=>$playerId, 'newbie_pay'=>$flag-1]);
		$this->clearDataCache($playerId);
		return $ret;
	}
	
	public function updateWash($playerId, $date){
		$ret = $this->updateAll(['skill_wash_date'=>"'".$date."'"], ['player_id'=>$playerId, 'skill_wash_date <>'=>"'".$date."'"]);
		$this->clearDataCache($playerId);
		return $ret;
	}

    /**
     * 获取玩家城战军团列表
     * 主要给跨服获取，省数据
     * @param $playerId
     *
     * @return array|mixed
     */
	public function getGeneralIdList($playerId, $generateFlag=false){
	    $info = $this->getByPlayerId($playerId);
	    if(empty($info['general_id_list'])) {
	        if($generateFlag) {
                $generalIdList = (new PlayerGuild)->getDefaultCrossArmyInfo($playerId);
                $this->alter($playerId, ['general_id_list'=>json_encode($generalIdList)]);
                (new PlayerCommonLog)->add($playerId, ['type'=>'[城战军团]选人脚本生成', 'general_id_list'=>$generalIdList]);//日志记录
                return $generalIdList;
            } else {
                return [];
            }
        } else {
	        return json_decode($info['general_id_list'], true);
        }
    }
}
