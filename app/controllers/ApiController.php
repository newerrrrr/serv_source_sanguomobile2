<?php
/**
 * rpc api
 */
class ApiController extends ControllerBase{
    public $initFlag = false;
    /**
     * 对外开放接口,获取玩家信息
     *
     * 使用方法如下
     *
     * ```php
     * api/getPlayerInfo
     * post: {"user_code":"9ARNT5"}
     * return: {"id":"id","nick":"nick","server_id":"server_id"} (←解密后)
     * ```
     */
    public function getPlayerInfoAction(){
        $userCode = $_POST['user_code'];
        $info = [];
        if(strlen($userCode)>0) {
            $player = Player::findFirst(["user_code=:userCode:", 'bind'=>['userCode'=>$userCode]]);
            if($player) {
                $player            = $player->toArray();
                $info['id']        = $player['id'];
                $info['nick']      = $player['nick'];
                $info['server_id'] = $player['server_id'];
            }   
        }
        // $info = [];
        $info = json_encode($info);
        //加密
        $key = 'sanguo_mobile2';
        $info = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $info, MCRYPT_MODE_CBC, md5(md5($key))));
        echo $info;
        // $info = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key),base64_decode($info), MCRYPT_MODE_CBC, md5(md5($key)));
        // dump($info);
        exit;
    }

    /**
     * 此接口只GM后台admin可以使用，不对外开放
     *
     * ```php
     *  api/clearCacheAction
     *  postData: {"index":3}
     *  return null;
     * ```
     */
    public function clearCacheAction(){
        $index    = $_POST['index'];
        $indexArr = json_decode($index, true);
        foreach($indexArr as $v) {
            Cache::db($v)->flushDB();
        }
        exit;
    }

    /**
     * 获取玩家player表信息
     *
     * ```php
     *  api/getPlayerBasicInfo
     *  postData: {"player_id":333}
     *  return: basicInfo
     * ```
     *
     */
    public function getPlayerBasicInfoAction(){
        $playerId = iDecrypt($_POST['player_id'], 'Player');
        $player = [];
        if($playerId>0) {
            $player = (new Player)->getByPlayerId($playerId);
            if($player) {
                $player = keepFields($player, Player::$basicInfo, true);
            }
            if($player['guild_id']>0) {
                $guild                      = (new Guild)->getGuildInfo($player['guild_id']);
                $player['guild_name']       = $guild['name'];
                $player['guild_short_name'] = $guild['short_name'];
            } else {
                $player['guild_name'] = $player['guild_short_name'] = '';
            }
        }
        $info = iEncrypt($player);
        echo $info;
        exit;
    }

    /**
     * 获取玩家的武将基础信息
     *
     * ```php
     * api/getPlayerGeneralBasicInfo
     * postData: {"player_id":333,"general_id":555}
     * return: totalAttr
     * ```
     */
    public function getPlayerGeneralBasicInfoAction() {
        $playerId  = iDecrypt($_POST['player_id'], 'PlayerGeneral');
        $generalId = iDecrypt($_POST['general_id'], 'PlayerGeneral');
        if($playerId>0 && $generalId>0) {
            $generalData = (new PlayerGeneral)->getTotalAttr($playerId, $generalId);
            $info        = iEncrypt($generalData);
            echo $info;
        }
        exit;
    }

    /**
     * 获取武斗相关buff
     *
     * ```php
     * api/getPlayerPkBuff
     * postData: {"player_id":333}
     * return: info
     * ```
     */
    public function getPlayerPkBuffAction(){
        $playerId  = iDecrypt($_POST['player_id'], 'PlayerPkBuff');
        if($playerId>0) {
            $playerBuff = (new PlayerGeneralBuff)->getByPlayerId($playerId);
            if($playerBuff) {
                $playerBuff = keepFields($playerBuff, ['general_force_inc', 'general_intelligence_inc', 'general_governing_inc', 'general_charm_inc', 'general_political_inc'], true);
            }
            $info = iEncrypt($playerBuff);
            echo $info;
        }
        exit;
    }
	
	public function getModelDataAction(){
		//$whiteList = ['Player', 'PlayerGeneral', 'PlayerArmy', 'PlayerArmyUnit', 'Guild'];
		$para = iDecrypt($_POST['para']);
		$model = $para['model'];
		$func = $para['func'];
		$args = $para['args'];
		$ret = [];
		if(/*!in_array($model, $whiteList) || */!method_exists($model, $func)){
			exit;
		}
		$oname = new $model;
		$ret = call_user_func_array([$oname, $func], $args);
		echo iEncrypt($ret);
		exit;
	}

	/**
     * 接受跨服长连接请求
     *
     */
	public function sendSocketAction(){
		$data = iDecrypt($_POST['data']);
		$type = $data['Type'];
		$data = $data['Data'];
		socketSend(['Type'=>$type, 'Data'=>$data]);
		exit;
	}

    /**
     * anysdk充值回调分发
     * 
     * 
     * @return <type>
     */
	public function anysdkNotifyAction(){
		require_once(APP_PATH . '/app/lib/anysdk/AnySDK.config.php');
		require_once(APP_PATH . '/app/lib/anysdk/AnySDK.Sdk.class.php');
		
		$payment_params = $_REQUEST;
		unset($payment_params['_url']);
		$anysdk = new Sdk_AnySDK(ANYSDK_ENHANCED_KEY, ANYSDK_PRIVATE_KEY);

		/**
		 * 设置调试模式
		 * 
		 */
		//$anysdk->setDebugMode(Sdk_AnySDK::DEBUG_MODE_ON);

		/**
		 * ip白名单检查
		 *
		$anysdk->pushIpToWhiteList('127.0.0.1');
		$anysdk->checkIpWhiteList() or die(Sdk_AnySDK::PAYMENT_RESPONSE_FAIL . 'ip');
		 */

		/**
		 * SDK默认只检查增强签名，如果要检查普通签名和增强签名，则需要此设置
		 * 
		 */
		$anysdk->setPaymentSignCheckMode(Sdk_AnySDK::PAYMENT_SIGN_CHECK_MODE_BOTH);
		$check_sign = $anysdk->checkPaymentSign($payment_params);
		if (!$check_sign) {
			echo $anysdk->getDebugInfo(), "\n=====我是分割线=====\n";
			die(Sdk_AnySDK::PAYMENT_RESPONSE_FAIL . 'sign_error');
		}

		/**
		 * 检查订单状态，1为成功
		 */
		if (intval($anysdk->getPaymentStatus()) !== Sdk_AnySDK::PAYMENT_STATUS_SUCCESS) {
			die(Sdk_AnySDK::PAYMENT_RESPONSE_OK);
		}

		/**
		 * 获取支付通知详细参数
		 * 
		 */
		$amount = $anysdk->getPaymentAmount();
		$product_id = $anysdk->getPaymentProductId();
		$product_name = $anysdk->getPaymentProductName();
		$product_count = $anysdk->getPaymentProductCount();
		$channel_product_id = $anysdk->getPaymentChannelProductId();
		$user_id = $anysdk->getPaymentUserId();
		$game_user_id = $anysdk->getPaymentGameUserId();
		$order_id = $anysdk->getPaymentOrderId();
		$channel_order_id = $anysdk->getPaymentChannelOrderId();
		$private_data = $anysdk->getPaymentPrivateData();
		$server_id = $anysdk->getPaymentServerId();
		$channelNumber = $anysdk->getPaymentChannelNumber();
	/*}
	
	public function order1Action(){
		require_once(APP_PATH . '/app/lib/anysdk/AnySDK.config.php');
		require_once(APP_PATH . '/app/lib/anysdk/AnySDK.Sdk.class.php');
		$order_id = '111111';
		$private_data = $_POST['ext'];
		
	*/	
		
		parse_str($private_data, $data);
		
		$host = (new ServerList)->getGameServerIpByServerId($server_id);
		
		$data['commodity_id'] = $product_id;
		$data['player_id'] = $game_user_id;
		$data['server_id'] = $server_id;
		$data['outTradeNo'] = $order_id;
		$data['keyword'] = ['amount'=>$amount, 'channelNumber'=>$channelNumber];
		$data['sign'] =  (new OrderController)->buildSign([
			'Order_id'=>$data['order_id'], 
			'commodity_id'=>$product_id, 
			'Player_id'=>$game_user_id
		]);
		//分发
		$ch = curl_init();

		// 设置URL和相应的选项
		curl_setopt($ch, CURLOPT_URL, $host."/order/notify/anysdk");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

		// 抓取URL并把它传递给浏览器
		$ret = curl_exec($ch);

		// 关闭cURL资源，并且释放系统资源
		curl_close($ch);
		
		if($ret == 'ok'){
			echo Sdk_AnySDK::PAYMENT_RESPONSE_OK;
		}else{
			if(json_decode($ret, true)['message'] == '订单已回调'){
				echo Sdk_AnySDK::PAYMENT_RESPONSE_OK;
			}
			(new ModelBase)->execByServer($server_id, 'PlayerCommonLog', 'add', [$data['player_id'], ['type'=>'anysdk_pay_notify', 'memo'=>['data'=>$data, 'ret'=>$ret]]]);
		}

		//echo $anysdk->getDebugInfo(), "\n=====我是分割线=====\n";
		//echo Sdk_AnySDK::PAYMENT_RESPONSE_OK;
	}
}

