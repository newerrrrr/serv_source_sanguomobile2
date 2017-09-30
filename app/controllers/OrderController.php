<?php
use Phalcon\Mvc\View;
class OrderController extends ControllerBase
{
    public $ipLimitSwitch = false;//无视白名单

	const notifySignkey = 'dssgmt@noizjkwes';
	const signKey = 'ztgiwe@paypower';
	const GAMEID = 'sanguomobiletwo';
	
	public function initialize() {
		if(!in_array($this->getActionName(), ['notify', 'notifyByWeb'])){
			parent::initialize();
		}
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 获取可买礼包列表
     * 
     * 
     * @return <type>
     */
	public function getGiftListAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		$post = getPost();
		$channel = @$post['channel'];
		
		
		$now = time();
		
		$playerInfo = (new PlayerInfo)->getByPlayerId($playerId);
		$PayWay = new PayWay;
		if(!$PayWay->getChannelByPayway($channel, $playerInfo['pay_channel'])){
			exit;
		}

		//获取缓存
		$cache = Cache::getPlayer($playerId, 'buyGiftLists-'.$channel);
		if($cache){
			if($cache['validtime'] < $now || (date('H') == 0 && date('i') <= 5) || (date('H') == 12 && date('i') <= 5)){
				Cache::delPlayer($playerId, 'buyGiftLists-'.$channel);
				$cache = false;
			}else{
				$ret = $cache['data'];
			}
		}
		
		if(!isset($ret)){
			$PlayerOrder = new PlayerOrder;
			//查找府衙礼包和死伤礼包
			$series2 = [];
			$validTime2 = $now+$PlayerOrder->giftspTime;
			$playerInfo = (new PlayerInfo)->getByPlayerId($playerId);
			if($playerInfo['gift_lv12_begin_time'] && $now <= $playerInfo['gift_lv12_begin_time']+$PlayerOrder->giftspTime){
				$series2[] = $PlayerOrder->giftspname2id('gift_lv12_begin_time');
				$validTime2 = min($validTime2, $playerInfo['gift_lv12_begin_time']+$PlayerOrder->giftspTime);
			}
			if($playerInfo['gift_lv22_begin_time'] && $now <= $playerInfo['gift_lv22_begin_time']+$PlayerOrder->giftspTime){
				$series2[] = $PlayerOrder->giftspname2id('gift_lv22_begin_time');
				$validTime2 = min($validTime2, $playerInfo['gift_lv22_begin_time']+$PlayerOrder->giftspTime);
			}
			if($playerInfo['gift_lv37_begin_time'] && $now <= $playerInfo['gift_lv37_begin_time']+$PlayerOrder->giftspTime){
				$series2[] = $PlayerOrder->giftspname2id('gift_lv37_begin_time');
				$validTime2 = min($validTime2, $playerInfo['gift_lv37_begin_time']+$PlayerOrder->giftspTime);
			}
			if($playerInfo['gift_lose_power_begin_time'] && $now <= $playerInfo['gift_lose_power_begin_time']+$PlayerOrder->giftspTime){
				$series2[] = $PlayerOrder->giftspname2id('gift_lose_power_begin_time');
				$validTime2 = min($validTime2, $playerInfo['gift_lose_power_begin_time']+$PlayerOrder->giftspTime);
			}
			
			//获取开服天数
			$startTime = (new Configure)->getValueByKey('server_start_time');
			$days = ceil(max(0, time() - $startTime) / (24*3600));
			//获取当前有效的充值礼包配置
			$ActivityCommodity = new ActivityCommodity;
			$ActivityCommodityExtra = new ActivityCommodityExtra;
			//$acs = $ActivityCommodity->find(['open_time <= '.$now.' and close_time >='.$now, 'order'=>'activity_id, series_order'])->toArray();
			$acs = $ActivityCommodity->sqlGet('select * from '.$ActivityCommodity->getSource().' a, '.$ActivityCommodityExtra->getSource().' b where b.open_time <= '.$now.' and b.close_time >='.$now.' and a.id=b.id order by a.activity_id, a.series_order');
			//1006,1020节日礼包
			$acs1006 = $ActivityCommodity->sqlGet('select * from '.$ActivityCommodity->getSource().' where open_time <= '.$now.' and close_time >='.$now.' order by activity_id, series_order');
			$acs = array_merge($acs, $acs1006);
			//1024周末礼包
			if(in_array(date('w', $now), [0, 6])){
				$acs1024 = $ActivityCommodity->sqlGet('select * from '.$ActivityCommodity->getSource().' where activity_id=1024 order by activity_id, series_order');
				//var_dump($acs1024);
				$acs = array_merge($acs, $acs1024);
			}
			
			if($series2){
				$acs2 = $ActivityCommodity->sqlGet('select * from '.$ActivityCommodity->getSource().' where series in ('.join(',', $series2).') order by activity_id, series_order');
				$acs = array_merge($acs, $acs2);
			}
			//var_dump($acs);
			
			//归类配置
			$acsFilter = [];
			foreach($acs as $_a){
				if(!$_a['act_same_index']) continue;
				if(in_array($_a['series'], $series2)){
					$_time = $playerInfo[$PlayerOrder->giftsp[$_a['series']]];
					$ace = [
						'open_time'=>$_time,
						'close_time'=>$_time+$PlayerOrder->giftspTime,
					];
				}elseif(in_array($_a['activity_id'], $PlayerOrder->spdayType)){
					$ace = [
						'open_time'=>$_a['open_time'],
						'close_time'=>$_a['close_time'],
					];
				}elseif($_a['activity_id'] == 1024){
					$sub = 0;
					if(date('w', $now) == 0){
						$sub = 1;
					}
					$ace = [];
					$ace['open_time'] = mktime(0, 0, 0, date('m', $now), date('d', $now)-$sub, date('Y', $now));
					@$ace['close_time'] += $ace['open_time'] + 3600*24*2;
				}else{
					$ace = $ActivityCommodityExtra->dicGetOne($_a['id']);
				}
				$acsFilter[$_a['series']][$_a['series_order']] = array_merge($_a, $ace);
			}
			//var_dump($acsFilter);
			
			//获取充值记录
			$acValid = [];
			foreach($acsFilter as $_f){
				//echo 'player_id='.$playerId.' and status=1 and series='.$_f[1]['series'].' and create_time >= "'.date('Y-m-d H:i:s', $_f[1]['open_time']).'" and create_time <= "'.date('Y-m-d H:i:s', $_f[1]['close_time']).'"';
				//$validFByDayLimit = false;
				$_f_new = [];
				foreach($_f as $__k => $__f){
					if(!$__f['day_limit'] || $days <= $__f['day_limit']){
						$_f_new[$__k] = $__f;
						//$validFByDayLimit = $__f['series_order'];
						//break;
					}
				}
				$_f = $_f_new;
				if(!$_f) continue;
				$first = array_values($_f)[0];
				//if(!$validFByDayLimit) continue;
				
				$order = $PlayerOrder->find(['player_id='.$playerId.' and status=1 and series='.$first['series'].' and create_time >= "'.date('Y-m-d H:i:s', $first['open_time']).'" and create_time <= "'.date('Y-m-d H:i:s', $first['close_time']).'"', 'order'=>'series_order desc'])->toArray();
				if($order){
					$order = $order[0];
					$findFlag = false;
					$_acValid = false;
					$next = false;
					foreach($_f as $__k => $__f){
						if($findFlag){
							$_acValid = $__f;
							$next = true;
							break;
						}
						if($__f['series_order'] == $order['series_order']){
							$findFlag = true;
							continue;
						}
					}
					$_acValid = $_f[$__k];
					//var_dump($_acValid);
					if(!$findFlag){//正常应该不允许这种情况
						$acValid[] = array_values($_f)[0];
					}elseif($next){//下一条
						$acValid[] = $_acValid;
					}else{//重复最后一条或没有
						if(!in_array($_acValid['activity_id'], $PlayerOrder->onceOnly)){
							$acValid[] = $__f;
						}
					}
				}else{
					$acValid[] = $first;
				}
			}
			//var_dump($acValid);
			
			$ret = [];
			$Pricing = new Pricing;
			if(date('H') < 12){
				$validTime = mktime(12, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
			}else{
				$validTime = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now))+3600*24;
			}
			foreach($acValid as $_v){
				//$_pricing = $Pricing->getByPaymentCode($_v['commodity_id']);
				$_pricing = $Pricing->findFirst(['gift_type='.$_v['gift_type'].' and channel="'.$channel.'"']);
				//var_dump($_pricing);
				if(!$_pricing) continue;
				$_pricing = $_pricing->toArray();
				$ret[] = ['id'=>$_pricing['id'], 'aci'=>$_v['id'], 'endTime'=>$_v['close_time']*1];
				$validTime = min($validTime, $_v['close_time']);
			}
			$validTime = min($validTime, $validTime2);
			
			//放入缓存
			Cache::setPlayer($playerId, 'buyGiftLists-'.$channel, ['data'=>$ret, 'validtime'=>$validTime]);
			
		}
		
		echo $this->data->send(array('list'=>$ret));
	}
	
    /**
     * 创建订单
     * 
     * 
     * @return <type>
     */
	public function createOrderAction(){
		global $config;
		$serverId = $config['server_id'];
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$id = @$post['id'];
		$aci = @$post['aci'];
		if(!checkRegularNumber($id))
			exit;
				
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerInfo = new PlayerInfo;
			$playerInfo = $PlayerInfo->getByPlayerId($playerId);
			//获取充值配置
			$Pricing = new Pricing;
			$pricing = $Pricing->dicGetOne($id);
			if(!$pricing){
				throw new Exception(10363);//购买项不存在
			}
			
			//检查是否开启
			if(!$pricing['isopen']){
				throw new Exception(10364);//购买项未开放
			}
			
			//检查平台
			$paywayId = (new PayWay)->getChannelByPayway($pricing['channel'], $playerInfo['pay_channel']);
			if(!$paywayId){
			//if($playerInfo['pay_channel'] != $pricing['channel']){
				Cache::delPlayer($playerId, 'buyGiftLists-'.$pricing['channel']);
				throw new Exception(10408);//渠道不符合
			}
			
			//检查等级
			$payway = (new PayWay)->dicGetOne($paywayId);
			$key = array_search($pricing['channel'], $payway['pay_way']);
			if($player['level'] < $payway['pay_way_lv'][$key]){
				throw new Exception(10417);//玩家等级不足
			}
			
			//检查时间
			
			//检查类型
			$activityCommodityId = 0;
			$series = 0;
			$seriesOrder = 0;
			$PlayerOrder = new PlayerOrder;
			switch($pricing['goods_type']){
				case 1://元宝
				
				break;
				case 2://永久月卡
					if($PlayerInfo->haveLongCard($playerId)){
						throw new Exception(10409);//无法重复购买
					}
				break;
				case 3://月卡
					/*if($PlayerInfo->haveMonthCard($playerId)){
						throw new Exception(10410);//无法重复购买月卡
					}*/
				break;
				case 4://礼包
					if(!$aci){
						throw new Exception(10365);//礼包未指定
					}
					$ActivityCommodity = new ActivityCommodity;
					$ac = $ActivityCommodity->dicGetOne($aci);
					if(!$ac){
						throw new Exception(10366);//未找到购买项
					}
					if($ac['gift_type'] != $pricing['gift_type']){
						Cache::delPlayer($playerId, 'buyGiftLists-'.$pricing['channel']);
						throw new Exception(10367);//购买项错误[1]
					}
					$activityCommodityId = $ac['id'];
					$series = $ac['series'];
					$seriesOrder = $ac['series_order'];
					
					//检查购买重复性
					if(!$PlayerOrder->chkAvailable($playerId, $activityCommodityId, true)){
						Cache::delPlayer($playerId, 'buyGiftLists-'.$pricing['channel']);
						throw new Exception(10368);//购买项无法被购买
					}
				break;
				default:
					throw new Exception(10369);//购买项种类不存在
			}
			
			//通知平台创建订单
			if($pricing['channel'] == 'paypal'){
				$mode = 'web';
			}else{
				$mode = 'sdk';
			}
			
			if($playerInfo['pay_channel'] == 'anysdk'){
				$orderId = date('YmdHis').rand(100000, 999999);
				//创建订单
				if(!$PlayerOrder->add($playerId, $orderId, $pricing['payment_code'], $activityCommodityId, $pricing['channel'], $mode, $pricing['price'], 0, '', $series, $seriesOrder, 0, 'anysdk')){
					throw new Exception(10418);//创建订单失败
				}
				$retData = ['ext'=>http_build_query([
					'order_id'=>$orderId,
					//'commodity_id'=>$pricing['id'],
					//'player_id'=>$playerId,
					'channel'=>$pricing['channel'],
					'mode'=>$mode,
					//'server_id'=>$serverId,
				])];
			}else{
				$extra = [];
				if($playerInfo['pay_channel'] == 'aligames'){
					$extra['accountId'] = explode('_', $player['uuid'])[0];
				}
				$ret = $this->createOrder($playerId, $pricing['channel'], $pricing['payment_code'], $mode, $serverId, $extra);
				if($ret && $ret['status'] == 'success'){
					$retData = $ret;
					unset($retData['status']);
					
					if(in_array($pricing['channel'], ['alipay', 'alipay_cn'])){
						parse_str($ret['orderInfo'], $orderInfo);
						//var_dump($orderInfo);
						$orderId = substr($orderInfo['out_trade_no'], 1, -1);
					}elseif(isset($ret['orderId'])){
						$orderId = $ret['orderId'];
					}elseif(isset($ret['order_id'])){
						$orderId = $ret['order_id'];
					}elseif(isset($ret['out_trade_no'])){
						$orderId = $ret['out_trade_no'];
					}elseif(isset($ret['requestId'])){
						$orderId = $ret['requestId'];
					}
					//创建订单
					if(!$PlayerOrder->add($playerId, $orderId, $pricing['payment_code'], $activityCommodityId, $pricing['channel'], $mode, $pricing['price'], 0, '', $series, $seriesOrder, 0, 'game')){
						throw new Exception(10418);//创建订单失败
						//echo $this->ret(1, '创建订单失败[1]');
						//exit;
					}
					
				}else{
					throw new Exception(10370);//创建订单失败
				}
				
			}
			$retData['price'] = $pricing['price'];
			
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
			echo $this->data->send(['order'=>$retData]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	public function createOrder($playerId, $channel, $paymentCode, $mode, $server, $extra=[]){
		global $config;
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player)
			return false;
		$param = [
			'from'			=>	'mobile',
			'game'			=>	self::GAMEID,
			'playerId'		=>	$playerId,
			'channel'		=>	$channel,
			'paymentCode'	=>	$paymentCode,
			'mode'			=>	$mode,
			'notifyUrl'		=>	$config['paynotifyurl'],
			'serverId'		=>	$server,
			'sign'			=>	'',
			'encode'		=>	1,
			'serverName'	=>	'手机三国2 - ['.$server.']服',
			'roleName'		=>	$player['nick'],
			'userLevel'		=>	$player['level'],
			'balance'		=>	$player['rmb_gem']+$player['gift_gem'],
		];
		
		if($channel == 'aligames'){
			$param['accountId'] = $extra['accountId'];
		}
		
		if($channel == 'paypal'){//paypal需要自己生成订单号
			$param['keyword'] = date('YmdHis').rand(100000, 999999);
		}
		$param['sign'] = md5($param['game'] . $param['playerId'] . $param['channel'] . $param['paymentCode'] . self::signKey);
		
		if($channel == 'paypal'){//paypal需要客户端跳转
			return [
				'status'=>'success',
				'url'=>$config['dsucpayurl']."?".http_build_query($param),
				'orderId'=>$param['keyword'],
			];
		}
		/*
			开发环境：27.115.98.171
			测试环境：27.115.98.172:9998
			正式环境：pay.m543.com.cn
		*/
		$ch = curl_init();
//file_put_contents('tmp.log',  "http://27.115.98.171/payment/createOrder?".http_build_query($param));
		// 设置URL和相应的选项
		curl_setopt($ch, CURLOPT_URL, $config['dsucpayurl']."?".http_build_query($param));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// 抓取URL并把它传递给浏览器
		$ret = curl_exec($ch);
		if($ret === false){
			return false;
		}
		// 关闭cURL资源，并且释放系统资源
		curl_close($ch);
//file_put_contents('tmp2.log',  $ret);
		$ret = json_decode($ret, true);
		//$ret = json_decode($ret, true);
		//var_dump($ret);
		return $ret;
	}
	
    /**
     * 官网充值回调
     * 
     * 
     * @return <type>
     */
	public function notifyByWebAction(){
		$this->notifyAction('web');
	}
		
    /**
     * 充值回调
     * 
     * @return <type>
     */
	public function notifyAction($from=''){
		$outTradeNo = '';
		$data= file_get_contents('php://input', 'r');
		$data = json_decode($data, true);
		//var_dump($data);
		if(!@$data['order_id'] || 
			!@$data['commodity_id'] || 
			!@$data['player_id'] || 
			!@$data['channel'] || 
			!@$data['mode'] || 
			!@$data['server_id'] || 
			!@$data['sign']
		){
			echo $this->ret(1, '参数不正确');
			exit;
		}
		
		$orderId = $data['order_id'];
		$commodityId = $data['commodity_id'];
		$playerId = $data['player_id'];
		$channel = $data['channel'];
		$mode = $data['mode'];
		$serverId = $data['server_id'];
		$keyword = @$data['keyword'];
		$sign = $data['sign'];
		
		if($channel == 'paypal'){
			$outTradeNo = $orderId;
			if($from != 'web'){
				$orderId = $keyword;
			}
		}
		if($from == 'web'){
			$tunnel = 'web';
		}else{
			$tunnel = 'game';
		}
		
		if($from == 'anysdk'){
			if(in_array($keyword['channelNumber'], ['000016'/*联想*/, '160280'/*酷派*/, '160086'/*pengyouwan*/])){
				$androidChannel = (new AndroidChannel)->dicGetOneByChannelId($keyword['channelNumber']);
				if(!$androidChannel){
					echo $this->ret(1, '渠道未找到');
					exit;
				}
				if(!$commodityId){
					echo $this->ret(1, 'commodityId错误');
					exit;
				}
				$channelName = $androidChannel['channel_name'];
				$pricing = (new Pricing)->findFirst(['product_id='.$commodityId]);
				if(!$pricing){
					echo $this->ret(1, 'pricing未找到');
					exit;
				}
				if($pricing->channel != $channelName){
					echo $this->ret(1, '渠道不对应');
					exit;
				}
				$commodityId = $pricing->id;
			}
			$outTradeNo = $data['outTradeNo'];
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerInfo = new PlayerInfo;
			$playerInfo = $PlayerInfo->getByPlayerId($playerId);
			
			$Drop = new Drop;
			$drop = [];
			//校验加密串
			$_sign = $this->buildSign([
				'Order_id'=>$data['order_id'], 
				'commodity_id'=>$data['commodity_id'], 
				'Player_id'=>$data['player_id']
			]);
			if($sign != $_sign){
				throw new Exception('校验失败');
			}
			
			//获取支付项id配置
			$Pricing = new Pricing;
			$pricing = $Pricing->dicGetOne($commodityId);
			if(!$pricing){
				throw new Exception('充值配置不存在');
			}
			
			$PlayerOrder = new PlayerOrder;
			if($from == 'web'){//官网补充一个创建过程
				$playerOrder = $PlayerOrder->findFirst(['order_id="'.$orderId.'"']);
				if($playerOrder){
					throw new Exception('订单已回调');
				}
				if($pricing['goods_type'] != 1){
					throw new Exception('官网充值只支持充元宝');
				}
				//创建订单
				if(!$PlayerOrder->add($playerId, $orderId, $pricing['payment_code'], 0, $pricing['channel'], $mode, $pricing['price'], 0, '', 0, 0, 0, $tunnel)){
					throw new Exception('创建订单失败');
				}
			}
			
			//检查订单是否存在
			$playerOrder = $PlayerOrder->findFirst(['order_id="'.$orderId.'"']);
			if(!$playerOrder){
				throw new Exception('订单不存在');
				//echo $this->ret(1, '订单不存在');
				//exit;
			}
			$playerOrder = $playerOrder->toArray();
			if($playerOrder['status']){
				throw new Exception('订单已回调');
				//echo $this->ret(1, '订单已回调');
				//exit;
			}
			
			if(!$pricing['isopen']){
				throw new Exception('该配置未开放');
			}
			
			//核对渠道
			if($channel != $pricing['channel']){
				throw new Exception('渠道不合法');
			}
			
			if($pricing['payment_code'] != $playerOrder['payment_code']){
				throw new Exception('充值配置id不匹配');
			}
			
			if($from == 'anysdk'){
				if($pricing['price'] != $keyword['amount']){
					throw new Exception('价格不匹配');
				}
				if($playerOrder['player_id'] != $playerId){
					throw new Exception('玩家不匹配');
				}
			}
			
			//获取player
			$Player = new Player;
			$player = $Player->getByPlayerId($playerId);
			if(!$player){
				throw new Exception('玩家未找到');
				//echo $this->ret(1, '玩家未找到');
				//exit;
			}
			
			//是否首冲
			//if(!$PlayerOrder->count(['player_id='.$playerId])){
			if($pricing['goods_type'] == 1){
				$firstPay = $playerInfo['first_pay'];
				if(!in_array($pricing['gift_type'], $firstPay)){
					$isFirstPay = true;
					$firstPay[] = $pricing['gift_type'];
					$PlayerInfo->alter($playerId, ['first_pay'=>join(',', $firstPay)]);
				}else{
					$isFirstPay = false;
				}
			}else{
				$isFirstPay = false;
			}
			
			//更新gem
			$gem = $pricing['count'];
			if($isFirstPay){
				$gem += $pricing['first_add_count'];
			}else{
				$gem += $pricing['add_count'];
			}
			if($gem){
				if(!$Player->updateGem($playerId, $gem, false, ['memo'=>'充值'])){
					throw new Exception('增加元宝失败');
					//echo $this->ret(1, '增加元宝失败');
					//exit;
				}
			}
			if($pricing['rmb_value']){
				if(!$Player->alter($playerId, ['total_rmb'=>'total_rmb+'.$pricing['rmb_value']])){
					throw new Exception('增加元宝失败1');
				}
			}
			
			//是否为充值礼包
			switch($pricing['goods_type']){
				case 2://永久月卡
					$PlayerInfo->alter($playerId, ['long_card'=>1]);
					break;
				case 3://月卡
					$mcd = $playerInfo['month_card_deadline'];
					if($mcd>time()) {//时间未到续买
						$deadline = date("Y-m-d 00:00:00", $mcd+30*24*60*60);
					} else {
						$deadline = date("Y-m-d 00:00:00", strtotime("+30 day"));
					}
					$PlayerInfo->alter($playerId, ['month_card_deadline'=>$deadline]);
					break;
				case 4://！！如果该类型修改，需要修改后台方法ajaxPlayerdoSendGiftAction！！
					$ActivityCommodity = new ActivityCommodity;
					$ac = $ActivityCommodity->dicGetOne($playerOrder['activity_commodity_id']);
					
					if($ac['drop_id']){
						$drop[] = $ac['drop_id'];
					}
						
					//检查可购买性
					/*if(!$PlayerOrder->chkAvailable($playerId, $ac['id'], false)){
						throw new Exception('该礼包不可购买');
						//echo $this->ret(1, '该礼包不可购买');
						//exit;
					}*/
					
					//如果是联盟礼包
					if($ac['guild_drop_id'] && $player['guild_id']){
						//获取联盟所有玩家，除了自己
						$PlayerGuild = new PlayerGuild;
						$members = array_keys($PlayerGuild->getAllGuildMember($player['guild_id']));
						$members = array_diff($members, [$playerId]);
						$PlayerMail = new PlayerMail;
						foreach($members as $_m){
							$item = $PlayerMail->newItemByDrop($_m, [$ac['guild_drop_id']]);
							if(!$PlayerMail->sendSystem($_m, PlayerMail::TYPE_GUILDPAYGIFT, '', '', 0, ['nick'=>$player['nick']], $item, '联盟礼包')){
								throw new Exception('增加联盟礼包失败');
							}
							/*if(!$Drop->gain($_m, [$ac['guild_drop_id']], 1, '联盟礼包')){
								throw new Exception('增加联盟礼包失败');
							}*/
						}
						
					}
				break;
			}
			
			//drop
			if($pricing['bonus_drop']){
				$drop[] = $pricing['bonus_drop'];
			}

			if($drop){
				if(!$Drop->gain($playerId, $drop, 1, '充值购买')){
					throw new Exception('增加道具失败');
					//echo $this->ret(1, '增加道具失败');
					//exit;
				}
			}
			
			//增加订单
			if(!$PlayerOrder->setFinish($orderId, $gem, join(',', $drop), $outTradeNo)){
				throw new Exception('更新订单失败');
				//echo $this->ret(1, '更新订单失败');
				//exit;
			}
			
			//累计充值活动
			if(!(new PlayerActivityCharge)->addGem($playerId, $pricing['count'])){
				throw new Exception('累计充值活动失败');
			}
			//新人充值活动
			if(!(new PlayerNewbieActivityCharge)->addGem($playerId, $pricing['count'])){
				throw new Exception('新人累计充值活动失败');
			}
			//抢购活动
			if(!(new PlayerActivityPanicBuy)->addGem($playerId, $pricing['count'])){
				throw new Exception('抢购活动失败');
			}
			//新人充值礼包活动
			$PlayerInfo->updateNewbiePay($playerId, 1);
			
			Cache::delPlayer($playerId, 'buyGiftLists-'.$pricing['channel']);
			$PlayerInfo->sendBigDealMailOnce($playerId);//大额充值邮件,首次达到目标发送
			dbCommit($db);
			socketSend(['Type'=>'pay_callback', 'Data'=>['playerId'=>$playerId, 'goods_type'=>$pricing['goods_type'], 'pricing'=>$pricing], 'order_id'=>$orderId]);
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
			if($from == 'anysdk'){
				echo 'ok';
			}else{
				echo $this->ret(0);
			}
		}else{
			echo $this->ret(1, $err);
		}
		
	}
	
	public function buildSign($arr){
		//var_dump($arr);
		//echo number_format($arr['Order_id'], 0, '', '') ;exit;
		return md5($arr['Order_id'] . $arr['commodity_id'] . $arr['Player_id'] . self::notifySignkey);
	}
	
	public function ret($result, $reason=''){
		if(!$result){
			return json_encode(['status'=>'success', 'message'=>'payment success']);
		}else{
			return json_encode(['status'=>'failed', 'message'=>$reason]);
		}
		//return json_encode(['result'=>$result, 'reason'=>$reason]);
	}
}