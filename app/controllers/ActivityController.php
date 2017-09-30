<?php
/**
 * 活动相关
 */
use Phalcon\Mvc\View;
class ActivityController extends ControllerBase{

	/**
	 * 读取所有当前已开始的任务
	 *
	 * 使用方法如下
	 * ```php
	 * /Activity/getActivity/
	 * return: {}
	 * ```
	 *  
	 * @return [type] [description]
	 */
	function getActivityAction(){
		$ActivityConfigure = new ActivityConfigure;
		$activityList = $ActivityConfigure->getCurrentActivity();
		echo $this->data->send(['activityList'=>$activityList]);
	}
	
	/**
     * 购买成长基金
     * 
     * @return <type>
     */
	public function growthBuyAction(){
		$costId = 601;
		$playerId = $this->getCurrentPlayerId();
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$data = [];
			//获取玩家数据
			$PlayerGrowth = new PlayerGrowth;
			$pg = $PlayerGrowth->getByPlayerId($playerId);
			
			if($pg['buy']){
				throw new Exception(10382); //已经购买成长基金
			}
			
			//检查是否是至尊卡用户
			if(!(new PlayerInfo)->haveLongCard($playerId)){
				throw new Exception(10383); //请先购买至尊卡
			}
			
			$data['buy'] = 1;
			
			//消费元宝
			if(!(new Cost)->updatePlayer($playerId, $costId, 1)){
				throw new Exception('10034');//元宝不足
			}
			
			//更新栏位
			if(!$PlayerGrowth->alter($playerId, $data, $pg['rowversion'])){
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
     * 成长基金领取
     * 
	 * type:1-府衙等级，2-购买人数
	 * id：相关配置id
     * @return <type>
     */
	public function growthGainAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$type = floor(@$post['type']);
		$id = floor(@$post['id']);
		if(!checkRegularNumber($type) || !checkRegularNumber($id)){
			exit;
		}
		if(!in_array($type, [1, 2]))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$data = [];
			//获取玩家数据
			$PlayerGrowth = new PlayerGrowth;
			$pg = $PlayerGrowth->getByPlayerId($playerId);
			
			if(!$pg['buy']){
				throw new Exception(10384); //还未购买成长基金
			}
			
			//检查条件
			switch($type){
				case 1:
					if(in_array($id, $pg['level_reward'])){
						throw new Exception(10385); //已经领取
					}
					$currentNum = (new PlayerBuild)->getPlayerCastleLevel($playerId);
					$GrowthLevelReward = new GrowthLevelReward;
					$glr = $GrowthLevelReward->dicGetOne($id);
					if(!$glr){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					$needNum = $glr['level'];
					if($currentNum < $needNum){
						throw new Exception(10386); //府衙等级不足
					}
					$dropId = $glr['drop'];
					$data['level_reward'] = $pg['level_reward'];
					$data['level_reward'][] = $id;
				break;
				case 2:
					if(in_array($id, $pg['num_reward'])){
						throw new Exception(10387); //已经领取
					}
					$currentNum = (new PlayerGrowth)->getTotalNum();
					$GrowthNumberReward = new GrowthNumberReward;
					$glr = $GrowthNumberReward->dicGetOne($id);
					if(!$glr){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					$needNum = $glr['number'];
					if($currentNum < $needNum){
						throw new Exception(10388); //购买人数不足
					}
					$dropId = $glr['drop'];
					$data['num_reward'] = $pg['num_reward'];
					$data['num_reward'][] = $id;
				break;
				default:
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//增加道具
			if(!(new Drop)->gain($playerId, [$dropId], 1, '成长基金领取')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			
			//更新栏位
			if(!$PlayerGrowth->alter($playerId, $data, $pg['rowversion'])){
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
     * 累计充值活动数据
     * 
     * 
     * @return <type>
     */
	public function chargeAction(){
		$playerId = $this->getCurrentPlayerId();
		
		try {
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityCharge::ACTID);
			if(!$activityConfigure){
				throw new Exception(10439); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$activityConfigure['activity_para'] = json_decode($activityConfigure['activity_para'], true);
			foreach($activityConfigure['activity_para']['reward'] as $_k => &$_reward){
				$_reward = [
					'gem'=>$_k,
					'drop'=>parseGroup($_reward, false),
				];
			}
			unset($_reward);
			unset($activityConfigure['create_time'], $activityConfigure['status'], $activityConfigure['show_time'], $activityConfigure['activity_name']);
			
			$PlayerActivityCharge = new PlayerActivityCharge;
			$ret = $PlayerActivityCharge->getByActId($playerId, $activityConfigureId);
			
			//$ret = $PlayerActivityCharge->adapter($ret, true);
			if($ret){
				$ret = filterFields([$ret], true, $PlayerActivityCharge->blacklist)[0];
			}else{
				$ret = [
					'id'=>0,
					'activity_configure_id'=>$activityConfigureId,
					'gem'=>0,
					'flag'=>[],
				];
			}
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(['activity'=>$activityConfigure, 'charge'=>$ret]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 累计充值活动领取
     * 
     * 
     * @return <type>
     */
	public function chargeRewardAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$gem = floor(@$post['gem']);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取活动
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityCharge::ACTID);
			if(!$activityConfigure){
				throw new Exception(10440); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$config = json_decode($activityConfigure['activity_para'], true);
			$reward = $config['reward'];
			
			//检查gem
			if(!isset($reward[$gem])){
				throw new Exception(10441); //奖项不存在
			}
			
			//获取充值活动数据
			$PlayerActivityCharge = new PlayerActivityCharge;
			$ret = $PlayerActivityCharge->getByActId($playerId, $activityConfigureId);
			
			//检查是否领取
			if(in_array($gem, $ret['flag'])){
				throw new Exception(10442); //该奖项已领取
			}
			
			//检查累计是否达到
			if($ret['gem'] < $gem){
				throw new Exception(10443); //没有达到领取条件
			}
			
			//发奖
			if(!$drop = (new Drop)->gainFromDropStr($playerId, $reward[$gem], '累计充值活动['.$activityConfigureId.']')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			if(!$PlayerActivityCharge->setFlag($playerId, $activityConfigureId, $gem)){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	/**
     * 累计登录活动数据
     * 
     * 
     * @return <type>
     */
	public function loginAction(){
		$playerId = $this->getCurrentPlayerId();
		
		try {
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityLogin::ACTID);
			if(!$activityConfigure){
				throw new Exception(10444); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$activityConfigure['activity_para'] = json_decode($activityConfigure['activity_para'], true);
			foreach($activityConfigure['activity_para']['reward'] as $_k => &$_reward){
				$_reward = [
					'days'=>$_k,
					'drop'=>parseGroup($_reward, false),
				];
			}
			unset($_reward);
			unset($activityConfigure['create_time'], $activityConfigure['status'], $activityConfigure['show_time'], $activityConfigure['activity_name']);
			
			$PlayerActivityLogin = new PlayerActivityLogin;
			$ret = $PlayerActivityLogin->getByActId($playerId, $activityConfigureId);
			
			//$ret = $PlayerActivityLogin->adapter($ret, true);
			if($ret){
				$ret = filterFields([$ret], true, $PlayerActivityLogin->blacklist)[0];
			}else{
				$ret = [
					'id'=>0,
					'activity_configure_id'=>$activityConfigureId,
					'days'=>0,
					'flag'=>[],
				];
			}
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(['activity'=>$activityConfigure, 'login'=>$ret]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 累计登录活动领取
     * 
     * 
     * @return <type>
     */
	public function loginRewardAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$days = floor(@$post['days']);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取活动
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityLogin::ACTID);
			if(!$activityConfigure){
				throw new Exception(10445); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$config = json_decode($activityConfigure['activity_para'], true);
			$reward = $config['reward'];
			
			//检查gem
			if(!isset($reward[$days])){
				throw new Exception(10446); //奖项不存在
			}
			
			//获取累计登录活动数据
			$PlayerActivityLogin = new PlayerActivityLogin;
			$ret = $PlayerActivityLogin->getByActId($playerId, $activityConfigureId);
			
			//检查是否领取
			if(in_array($days, $ret['flag'])){
				throw new Exception(10447); //该奖项已领取
			}
			
			//检查累计是否达到
			if($ret['days'] < $days){
				throw new Exception(10448); //没有达到领取条件
			}
			
			//发奖
			if(!$drop = (new Drop)->gainFromDropStr($playerId, $reward[$days], '累计登录活动['.$activityConfigureId.']')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			if(!$PlayerActivityLogin->setFlag($playerId, $activityConfigureId, $days)){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 累计充值消耗数据
     * 
     * 
     * @return <type>
     */
	public function consumeAction(){
		$playerId = $this->getCurrentPlayerId();
		
		try {
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityConsume::ACTID);
			if(!$activityConfigure){
				throw new Exception(10439); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$activityConfigure['activity_para'] = json_decode($activityConfigure['activity_para'], true);
			foreach($activityConfigure['activity_para']['reward'] as $_k => &$_reward){
				$_reward = [
					'gem'=>$_k,
					'drop'=>parseGroup($_reward, false),
				];
			}
			unset($_reward);
			unset($activityConfigure['create_time'], $activityConfigure['status'], $activityConfigure['show_time'], $activityConfigure['activity_name']);
			
			$PlayerActivityConsume = new PlayerActivityConsume;
			$ret = $PlayerActivityConsume->getByActId($playerId, $activityConfigureId);
			
			//$ret = $PlayerActivityConsume->adapter($ret, true);
			if($ret){
				$ret = filterFields([$ret], true, $PlayerActivityConsume->blacklist)[0];
			}else{
				$ret = [
					'id'=>0,
					'activity_configure_id'=>$activityConfigureId,
					'gem'=>0,
					'flag'=>[],
				];
			}
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(['activity'=>$activityConfigure, 'charge'=>$ret]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 累计消耗活动领取
     * 
     * 
     * @return <type>
     */
	public function consumeRewardAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$gem = floor(@$post['gem']);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取活动
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityConsume::ACTID);
			if(!$activityConfigure){
				throw new Exception(10440); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$config = json_decode($activityConfigure['activity_para'], true);
			$reward = $config['reward'];
			
			//检查gem
			if(!isset($reward[$gem])){
				throw new Exception(10441); //奖项不存在
			}
			
			//获取充值活动数据
			$PlayerActivityConsume = new PlayerActivityConsume;
			$ret = $PlayerActivityConsume->getByActId($playerId, $activityConfigureId);
			
			//检查是否领取
			if(in_array($gem, $ret['flag'])){
				throw new Exception(10442); //该奖项已领取
			}
			
			//检查累计是否达到
			if($ret['gem'] < $gem){
				throw new Exception(10443); //没有达到领取条件
			}
			
			//发奖
			if(!$drop = (new Drop)->gainFromDropStr($playerId, $reward[$gem], '累计消耗活动['.$activityConfigureId.']')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			if(!$PlayerActivityConsume->setFlag($playerId, $activityConfigureId, $gem)){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 大转盘数据
     * 
     * 
     * @return <type>
     */
	public function wheelAction(){
		$playerId = $this->getCurrentPlayerId();
		
		try {
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityWheel::ACTID);
			if(!$activityConfigure){
				throw new Exception(10439); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$activityConfigure['activity_para'] = json_decode($activityConfigure['activity_para'], true);
			foreach($activityConfigure['activity_para']['reward'] as $_k => &$_reward){
				$_reward = [
					'counter'=>$_k,
					'drop'=>parseGroup($_reward, false),
				];
			}
			unset($_reward);
			foreach($activityConfigure['activity_para']['wheel'] as $_k => &$_reward){
				$_reward = parseGroup($_reward['drop'], false);
			}
			unset($_reward);
			unset($activityConfigure['create_time'], $activityConfigure['status'], $activityConfigure['show_time'], $activityConfigure['activity_name']);
			
			$PlayerActivityWheel = new PlayerActivityWheel;
			$ret = $PlayerActivityWheel->getByActId($playerId, $activityConfigureId);
			
			//$ret = $PlayerActivityConsume->adapter($ret, true);
			if($ret){
				$ret = filterFields([$ret], true, $PlayerActivityWheel->blacklist)[0];
			}else{
				$ret = [
					'id'=>0,
					'activity_configure_id'=>$activityConfigureId,
					'counter'=>0,
					'flag'=>[],
				];
			}
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(['activity'=>$activityConfigure, 'charge'=>$ret]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 大转盘累计领取
     * 
     * 
     * @return <type>
     */
	public function wheelRewardAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$counter = floor(@$post['counter']);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取活动
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityWheel::ACTID);
			if(!$activityConfigure){
				throw new Exception(10440); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$config = json_decode($activityConfigure['activity_para'], true);
			$reward = $config['reward'];
			
			//检查gem
			if(!isset($reward[$counter])){
				throw new Exception(10441); //奖项不存在
			}
			
			//获取充值活动数据
			$PlayerActivityWheel = new PlayerActivityWheel;
			$ret = $PlayerActivityWheel->getByActId($playerId, $activityConfigureId);
			if(!$ret){
				throw new Exception(10443); //没有达到领取条件
			}
			
			//检查是否领取
			if(in_array($counter, $ret['flag'])){
				throw new Exception(10442); //该奖项已领取
			}
			
			//检查累计是否达到
			if($ret['counter'] < $counter){
				throw new Exception(10443); //没有达到领取条件
			}
			
			//发奖
			if(!$drop = (new Drop)->gainFromDropStr($playerId, $reward[$counter], '大转盘累计奖励['.$activityConfigureId.']')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			if(!$PlayerActivityWheel->setFlag($playerId, $activityConfigureId, $counter)){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 转动大转盘
     * 
     * 
     * @return <type>
     */
	public function wheelPlayAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$num = floor(@$post['num']);
		if(!checkRegularNumber($num, true)){
			exit;
		}
		if($num != 10){
			$num = 1;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取活动
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityWheel::ACTID);
			if(!$activityConfigure){
				throw new Exception(10440); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$config = json_decode($activityConfigure['activity_para'], true);
			$wheel = $config['wheel'];
			
			//消耗道具
			$PlayerItem = new PlayerItem;
			$hasCounter = $PlayerItem->hasItemCount($playerId, $config['itemId']);
			$consumeCounter = min($num, $hasCounter);
			$gemCounter = $num - $consumeCounter;
			if($consumeCounter){
				if(!$PlayerItem->drop($playerId, $config['itemId'], $consumeCounter)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			if($gemCounter){
				if(!(new Player)->updateGem($playerId, -$config['gem']*$gemCounter, true, ['cost'=>10018])){
					throw new Exception(10034);
				}
			}
			/*if(!(new PlayerItem)->drop($playerId, $config['itemId'])){
				//如果没有道具消耗gem
				if(!(new Player)->updateGem($playerId, -$config['gem'], true, ['cost'=>10018])){
					throw new Exception(10034);
				}
			}*/
			
			//随机
			$paw = (new PlayerActivityWheel)->getByActId($playerId, $activityConfigureId);
			if(!$paw){
				$counter = 0;
			}else{
				$counter = $paw['counter']*1;
			}
			$rates1 = [];
			$rates2 = [];
			foreach($wheel as $_k => $_w){
				$rates1[$_k] = $_w['rate'];
				$rates2[$_k] = $_w['rate2'];
			}
			$memodrop = [];
			$drops = [];
			$keys = [];
			$i = 0;
			while($i < $num){
				$counter++;
				if($counter < $config['xcounter']){
					$rates = $rates1;
				}else{
					$rates = $rates2;
				}
				$_key = random($rates);
				$drop = $wheel[$_key]['drop'];
				
				//获取道具
				if(!$drop = (new Drop)->gainFromDropStr($playerId, $drop, '大转盘活动x'.$num.'['.$activityConfigureId.']')){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				/*$memodrop[] = $drop;
				foreach($drop as &$_d){
					$_d = array_values($_d);
				}
				unset($_d);
				*/
				$drops[] = $drop;
				$keys[] = $_key;
				
				$i++;
			}
			
			//增加次数
			if(!(new PlayerActivityWheel)->addCounter($playerId, $num)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//日志
			(new PlayerCommonLog)->add($playerId, ['type'=>'大转盘活动x'.$num, 'memo'=>['key'=>$keys, 'drop'=>$drops]]);
			
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
			
			echo $this->data->send(['key'=>$keys, 'drop'=>$drops]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 打怪掉落活动数据
     * 
     * 
     * @return <type>
     */
	public function npcDropAction(){
		$playerId = $this->getCurrentPlayerId();
		
		try {
			$activityConfigure = (new ActivityConfigure)->getCurrentActivity(1019);
			if(!$activityConfigure){
				throw new Exception(10449); //活动尚未开始
			}
			$activityConfigure = $activityConfigure[0];
			$activityConfigureId = $activityConfigure['id'];
			$activityConfigure['activity_para'] = json_decode($activityConfigure['activity_para'], true);
			unset($activityConfigure['create_time'], $activityConfigure['status'], $activityConfigure['show_time'], $activityConfigure['activity_name']);
			
			$activityConfigure['activity_para']['npc']['drop'] = parseGroup($activityConfigure['activity_para']['npc']['drop'], false);
			$activityConfigure['activity_para']['boss']['drop'] = parseGroup($activityConfigure['activity_para']['boss']['drop'], false);
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(['activity'=>$activityConfigure]);
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 新人累计登录活动领取
     * 
     * 
     * @return <type>
     */
	public function newbieLoginRewardAction(){		
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$days = floor(@$post['days']);
		
		if(!checkNewbieActivityServer()){
			return false;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查有效期
			$createDate = strtotime(date('Y-m-d', $player['create_time']));
			$today = strtotime(date('Y-m-d'));
			$maxDay = (new ActNewbieSign)->getMaxDay();
			$diffDay = floor(($today - $createDate) / (3600*24)) + 1;
			if($diffDay > $maxDay){
				throw new Exception(10554); //该奖励无法被领取
			}
			
			//获取活动
			$reward = (new ActNewbieSign)->dicGetAll();
			
			//检查gem
			if(!isset($reward[$days])){
				throw new Exception(10446); //奖项不存在
			}
			
			//获取累计登录活动数据
			$playerInfo = (new PlayerInfo)->getByPlayerId($playerId);
			
			$PlayerNewbieActivityLogin = new PlayerNewbieActivityLogin;
			$ret = $PlayerNewbieActivityLogin->getByPlayerId($playerId);
			if(!$ret){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查是否领取
			if(in_array($days, $ret['flag'])){
				throw new Exception(10447); //该奖项已领取
			}
			$maxDays = count($playerInfo['newbie_login']);
			if($days > $maxDays){
				throw new Exception(10448); //没有达到领取条件
			}
			/*
			//检查累计是否达到
			if(!in_array($days, $playerInfo['newbie_login'])){
				throw new Exception(10448); //没有达到领取条件
			}
			*/
			
			//发奖
			if(!$drop = (new Drop)->gain($playerId, $reward[$days]['drop'], 1, '新人累计登录活动['.$days.']')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			$ret['flag'][] = $days;
			$ret['flag'] = join(',', $ret['flag']);
			if(!$PlayerNewbieActivityLogin->setFlag($playerId, $ret['flag'], $ret['rowversion'])){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 新人累计充值活动领取
     * 
     * 
     * @return <type>
     */
	public function newbieChargeRewardAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$id = floor(@$post['id']);
		
		if(!checkNewbieActivityServer()){
			return false;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$reward = (new ActNewbieRecharge)->dicGetOne($id);
			if(!$reward){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取充值活动数据
			$PlayerNewbieActivityCharge = new PlayerNewbieActivityCharge;
			$period = $PlayerNewbieActivityCharge->getCurrentPeriod($playerId);
			$ret = $PlayerNewbieActivityCharge->getByPeriodId($playerId, $reward['period']);
			
			if($period != $reward['period']){
				throw new Exception(10555); //该奖项不可领
			}
			
			$gem = $reward['recharge_price'];
			//检查是否领取
			if(in_array($gem, $ret['flag'])){
				throw new Exception(10442); //该奖项已领取
			}
			
			//检查累计是否达到
			if($ret['gem'] < $gem){
				throw new Exception(10443); //没有达到领取条件
			}
			
			//发奖
			if(!$drop = (new Drop)->gain($playerId, $reward['drop'], 1, '新人累计充值活动['.$id.']')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			$ret['flag'][] = $gem;
			$ret['flag'] = join(',', $ret['flag']);
			if(!$PlayerNewbieActivityCharge->setFlag($playerId, $reward['period'], $ret['flag'], $ret['rowversion'])){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 新人累计消耗活动领取
     * 
     * 
     * @return <type>
     */
	public function newbieConsumeRewardAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$id = floor(@$post['id']);
		
		if(!checkNewbieActivityServer()){
			return false;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$ActNewbieCost = new ActNewbieCost;
			$reward = $ActNewbieCost->dicGetOne($id);
			if(!$reward){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取充值活动数据
			$PlayerNewbieActivityConsume = new PlayerNewbieActivityConsume;
			$period = $PlayerNewbieActivityConsume->getCurrentPeriod($playerId);
			$ret = $PlayerNewbieActivityConsume->getByPeriodId($playerId, $reward['period']);
			$gem = $reward['cost_price'];
			
			if($period != $reward['period']){
				throw new Exception(10556); //该奖项不可领
			}
			
			//判断是否为最大档
			$maxId = $ActNewbieCost->getMaxGem($reward['period']);
			if($id == $maxId){
				//检查累计是否达到
				$totalgem = $ActNewbieCost->sum(['period='.$reward['period'].' and id<'.$id, "column" => "cost_price"]);
				$totalgem += (@$ret['flag'][$id][0]+1)*$gem;
				if($ret['gem'] < $totalgem){
					throw new Exception(10443); //没有达到领取条件
				}
			}else{
				//检查是否领取
				if(isset($ret['flag'][$id])){
					throw new Exception(10442); //该奖项已领取
				}
				
				//检查累计是否达到
				$totalgem = $ActNewbieCost->sum(['period='.$reward['period'].' and id<='.$id, "column" => "cost_price"]);
				if($ret['gem'] < $totalgem){
					throw new Exception(10443); //没有达到领取条件
				}
			}

			//发奖
			if(!$drop = (new Drop)->gain($playerId, $reward['drop'], 1, '新人累计消耗活动['.$id.']')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			@$ret['flag'][$id][0]++;
			$ret['flag'] = joinGroup($ret['flag']);
			if(!$PlayerNewbieActivityConsume->setFlag($playerId, $reward['period'], $ret['flag'], $ret['rowversion'])){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	
	/**
	 * 兑换活动
	 *
	 *
	 * @return <type>
	 */
	public function exchangeShowAction(){
	    $playerId = $this->getCurrentPlayerId();
	
	    try {
	        $activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityExchange::ACTID);
	        if(!$activityConfigure){	            
	            throw new Exception(10693); //活动尚未开始
	        }
	        $activityConfigure = $activityConfigure[0];
	        $activityConfigureId = $activityConfigure['id'];
	        $activityConfigure['activity_para'] = json_decode($activityConfigure['activity_para'], true);
	        
	        $PlayerActivityExchange = new PlayerActivityExchange;
	        $Consume = new Consume;
	        
	        if(!is_array($activityConfigure['activity_para']['reward'])){
	            throw new Exception(10694); //活动尚未开始
	        }
	        $newReward = [];
	        foreach($activityConfigure['activity_para']['reward'] as $exchangeId => &$_reward){
               $resource = $Consume->check($playerId, $_reward['consume']); //获取每档实际拥有个数  
               
               $consume = parseGroup($_reward['consume'], false);
               if(!is_array($consume)){
                   continue;
               }
               foreach($consume as $key=>&$everyConsume){
                   foreach($everyConsume as &$ec){
                       $ec = intval($ec);
                   }
                   $everyConsume[3] = intval($resource[$key][$everyConsume[1]]);
               }
               $drop = parseGroup($_reward['drop'], false);
               if(!is_array($drop)){
                   continue;
               }
               foreach($drop as &$everyDrop){
                   foreach($everyDrop as &$ed){
                       $ed = intval($ed);
                   }
               }
               //兑换次数
               $ret = $PlayerActivityExchange->getByActId($playerId, $activityConfigureId, $exchangeId);
               $_reward = [
                   'exchangId'=>$exchangeId,
                   'limit'=>$_reward['limit'],
                   'consume'=> $consume,
                   'drop'=> $drop,
                   'has'=> $ret ? $ret['num'] : 0,//已经兑换次数
               ];
               $newReward[] = $_reward;

	        }
	        $activityConfigure['activity_para']['reward'] = $newReward;
	        unset($_reward);
	        unset($activityConfigure['create_time'], $activityConfigure['status'], $activityConfigure['show_time'], $activityConfigure['activity_name']);
	        $err = 0;
	    } catch (Exception $e) {
	        list($err, $msg) = parseException($e);
	        //清除缓存
	    }
	    if(!$err){
	        echo $this->data->send(['activity'=>$activityConfigure]);
	    }else{
	        echo $this->data->sendErr($err);
	    }
	}
	
	/**
	 * 点击兑换
	 *
	 *
	 * @return <type>
	 */
	public function doExchangeAction(){
	    $playerId = $this->getCurrentPlayerId();
	    $post = getPost();
	    $exchangeId = isset($post['exchangeId']) ? $post['exchangeId'] : 0;
	    if(!$exchangeId){
	        throw new Exception(10695); //兑换物品不存在
	    }
	    //锁定
	    $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
	    Cache::lock($lockKey);
	    $db = $this->di['db'];
	    dbBegin($db);
	
	    try {
	        //获取活动
	        $activityConfigure = (new ActivityConfigure)->getCurrentActivity(PlayerActivityExchange::ACTID);
	        if(!$activityConfigure){
	            throw new Exception(10696); //活动尚未开始
	        }
	        
	        $activityConfigure = $activityConfigure[0];
	        $activityConfigureId = $activityConfigure['id'];
	        $config = json_decode($activityConfigure['activity_para'], true);
	        $reward = $config['reward'];
	        //检查该兑换档位是否存在
	        if(!isset($reward[$exchangeId])){
	            throw new Exception(10697); //兑换物品不存在
	        }

	        //检查兑换次数
	        $PlayerActivityExchange = new PlayerActivityExchange;
	        $ret = $PlayerActivityExchange->getByActId($playerId, $activityConfigureId, $exchangeId);
	        $hasEx = $ret ? $ret['num'] : 0;	
	        //检查是否领取
	        if($reward[$exchangeId]['limit'] !=0 && $reward[$exchangeId]['limit'] <= $hasEx){
	            throw new Exception(10698); //达到兑换次数限制
	        }

            //消耗兑换所需道具
	        if(!(new Consume)->del($playerId, $reward[$exchangeId]['consume'], '兑换活动')){
	            throw new Exception(10699); //兑换所需物品不足
	        }
	        //日志
	        $PlayerCommonLog = new PlayerCommonLog;
	        $PlayerCommonLog->add($playerId, ['type'=>'兑换活动消耗['.$activityConfigureId.'_'.$exchangeId.']', 'memo'=>['consume'=>$reward[$exchangeId]['consume']]]);
	        //发放兑换物品
	        if(!$drop = (new Drop)->gainFromDropStr($playerId, $reward[$exchangeId]['drop'], '兑换活动['.$activityConfigureId.'_'.$exchangeId.']')){
	            throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
	        }
	        $PlayerCommonLog->add($playerId, ['type'=>'兑换活动获得['.$activityConfigureId.'_'.$exchangeId.']', 'memo'=>['drop'=>$reward[$exchangeId]['drop']]]);
	        //更新兑换次数
	        if(!$PlayerActivityExchange->addCount($playerId, $activityConfigureId, $exchangeId)){
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
	 * 秒杀展示
	 *
	 *
	 * @return <type>
	 */
	public function panicShowAction(){
	    $playerId = $this->getCurrentPlayerId();
	
	    try {
	        $activityConfigure = (new ActivityConfigure)->getCurrentActivity(ActivityPanicBuy::ACTID);
	        if(!$activityConfigure){
	            throw new Exception(10700); //活动尚未开始
	        }
	        $activityConfigure = $activityConfigure[0];
	        $activityConfigureId = $activityConfigure['id'];//秒杀活动的当届id	        
	        $ActivityPanicBuy = new ActivityPanicBuy;
	        $panicInfo = json_decode($activityConfigure['activity_para'], true);
	        
	        $PlayerActivityPanicBuy = new PlayerActivityPanicBuy;
	        if($panicInfo['reward']){
    	        foreach($panicInfo['reward'] as $key=>&$info){
    	            //嵌入当前玩家在当届每轮秒杀中的次数
    	            $payDay = $info['time'];
    	            if(!is_array($info)){
    	                continue;
    	            }
    	            foreach($info['ar'] as &$ev){
    	                if(!is_array($ev)){
    	                    continue;
    	                }
    	                
						$ev['beginTime'] = strtotime($ev['beginTime']);
						$ev['endTime'] = strtotime($ev['endTime']);
    	                foreach ($ev['items'] as &$e){	
    	                    if(!is_array($e)){
    	                        continue;
    	                    }
    	                    $e['drop'] = parseGroup($e['drop'], false);
							foreach($e['drop'] as &$_d){
								$_d = array_map('intval', $_d);
							}
							unset($_d);
    	                    $detail = $ActivityPanicBuy->getByActId($activityConfigureId, $e['id']);
    	                    $e['num'] = $detail ? $detail['num']*1 : 0;
							$e['price'] = $e['price']*1;
							$e['limit'] = $e['limit']*1;
    	                }
    	            }
					unset($ev);
    	            $buyInfo = $PlayerActivityPanicBuy->getByActId($playerId, $activityConfigureId, $payDay);
    	            $info['gem'] = $buyInfo ? $buyInfo['gem']*1 : 0;
    
    	        }
				unset($info);
	        }
	        $activityConfigure['activity_para'] = $panicInfo;//当届的所有轮秒杀详情
	        unset($_reward);
	        unset($activityConfigure['create_time'], $activityConfigure['status'], $activityConfigure['show_time'], $activityConfigure['activity_name']);
	        $err = 0;
	    } catch (Exception $e) {
	        list($err, $msg) = parseException($e);
	        //清除缓存
	    }
	    if(!$err){
	        echo $this->data->send(['activity'=>$activityConfigure]);
	    }else{
	        echo $this->data->sendErr($err);
	    }
	}
	
	
	/**
	 * 秒杀
	 */
	public function doPanicAction(){
	    $playerId = $this->getCurrentPlayerId();
	    $post = getPost();
	    $buyId = isset($post['buyId']) ? $post['buyId'] : 0;
	    if(!$buyId){
	        throw new Exception(10701); //秒杀物品不存在
	    }
	    //锁定
	    $lockKey = __CLASS__ . ':' . __METHOD__ . ':' .$buyId;
	    Cache::lock($lockKey);
	    $db = $this->di['db'];
	    dbBegin($db);
	    
	    try {
	        //获取活动
	        $activityConfigure = (new ActivityConfigure)->getCurrentActivity(ActivityPanicBuy::ACTID);
	        if(!$activityConfigure){
	            throw new Exception(10702); //活动尚未开始
	        }
	        $activityConfigure = $activityConfigure[0];
	        $activityConfigureId = $activityConfigure['id'];
	    	$ActivityPanicBuy = new ActivityPanicBuy;
	        $panicInfo = $ActivityPanicBuy->getByActIdNow($activityConfigureId, $buyId);
	        if(empty($panicInfo)){
	            throw new Exception(10703); //该轮抢购未开始
	        }
	        
	        $hasNum = $panicInfo['num'];//当前已经被秒杀的数量
	        if($panicInfo['num'] >= $panicInfo['limit']){
	            throw new Exception(10704); //物品已经抢完
	        }
	        //检查当前玩家是否满足充值要求
	        $PlayerActivityPanicBuy = new PlayerActivityPanicBuy;
	        $buyInfo = $PlayerActivityPanicBuy->getByActId($playerId, $activityConfigureId, $panicInfo['pay_day']);
	        if(empty($buyInfo) || $buyInfo['gem'] <= 0){
	            throw new Exception(10705); //您不符合活动要求
	        }
	        //扣钱
	        if(!(new Player)->updateGem($playerId, -$panicInfo['price'], true, ['cost'=>10025])){
	            throw new Exception(10034);
	        }
	        //检查通过进行秒杀
	        $ret = $ActivityPanicBuy->addCount($activityConfigureId, $buyId, 1, $panicInfo['rowversion']);
	        if($ret){
	            //PlayerActivityPanicBuy新增记录
	            $rett = $PlayerActivityPanicBuy->setFlag($playerId, $activityConfigureId, $panicInfo['pay_day'], $buyId);
	            if($rett){
	                //发放秒杀到的物品
	                if(!$drop = (new Drop)->gainFromDropStr($playerId, $panicInfo['drop'], '秒杀活动['.$activityConfigureId.'_'.$buyId.']')){
	                    throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
	                }
	                //日志
	                (new PlayerCommonLog)->add($playerId, ['type'=>'秒杀活动['.$activityConfigureId.'_'.$buyId.']', 'memo'=>['drop'=>$drop]]);
	                
	                $panicInfo = $ActivityPanicBuy->getByActId($activityConfigureId, $buyId);//获取最新num值
	                $dorpNew = [];
	                $dorpNew['drop'] = $drop;
	                $dorpNew['panicNum'] = $panicInfo['num']*1;
	            }
	            else{
	                throw new Exception(10706); //活动异常
	            }
	        }
	        else {
	            throw new Exception(10707);	             //很遗憾没有抢到
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
	        echo $this->data->send($dorpNew);
	    }else{
	        echo $this->data->sendErr($err);
	    }
	    
	}
	
    /**
     * 新人充值有礼
     * 
     * 
     * @return <type>
     */
	public function newbiePayRewardAction(){
		$playerId = $this->getCurrentPlayerId();
		
		if(!checkNewbieActivityServer(2)){
			return false;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$act = (new Activity)->dicGetOne(2004);
			if(!$act){
				throw new Exception(10439); //活动尚未开始
			}
			
			//获取充值活动数据
			$PlayerInfo = new PlayerInfo;
			$playerInfo = $PlayerInfo->getByPlayerId($playerId);
			
			//检查是否充值
			if(!$playerInfo['newbie_pay']){
				throw new Exception(10708); //还未充值
			}
			
			//检查是否领取
			if($playerInfo['newbie_pay'] == 2){
				throw new Exception(10709); //已经领取奖励
			}
			
			//发奖
			if(!$drop = (new Drop)->gain($playerId, $act['drop'], 1, '新人充值有礼活动')){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新标志
			if(!$PlayerInfo->updateNewbiePay($playerId, 2)){
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
			echo $this->data->send(['drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
}