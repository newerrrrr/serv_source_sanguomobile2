<?php
/**
 * 登录服
 */
class LoginServerController extends ControllerBase{
    public function initialize(){}

    /**
     * login_server/login
     */
    public function loginanysdkAction(){
        require APP_PATH . '/app/lib/anysdk/AnySDK.config.php';
        require APP_PATH . '/app/lib/anysdk/AnySDK.Sdk.class.php';
        $login_params = $_REQUEST;
        $anysdk = new Sdk_AnySDK();
        $response = $anysdk->loginForward($login_params);
        // 登录验证成功
        if ($anysdk->getLoginStatus()) {
            // 获取登录结果的一些字段
            $channelId = $anysdk->getLoginChannel();
            $uid = $anysdk->getLoginUid();
            $user_sdk = $anysdk->getLoginUserSdk();
            $plugin_id = $anysdk->getLoginPluginId();
            $server_id = $anysdk->getLoginServerId();
            $data = $anysdk->getLoginData();   // 获取登录验证渠道返回的原始内容
            // 获取登录结果字段值示例结束

            $channelName   = @(new AndroidChannel)->dicGetOneByChannelId($channelId)['channel_name'];
            $uuid          = $uid . '_' . $channelName;
            $udata['list'] = (new PlayerServerList)->getByUuid($uuid);
            $udata['last'] = (new PlayerLastServer)->getByUuid($uuid);
            $anysdk->setLoginExt($udata);
            $response = json_encode($anysdk->getLoginResponse());

        }
        $response = is_scalar($response) ? $response : json_encode($response);
        echo $response;
    }
    /**
     *  登录验证
     *
     * 使用方法如下
     *
     * ```php
     * login_server/login
     * postData:{...}
     * ```
     */
    public function loginAction(){
        global $config;
        $loginConfig = [//登录相关
            'host'    => $config->login_server->uc->host,
            'signkey' => $config->login_server->uc->signkey,
        ];

        if(isset($_POST['json'])) {
            $response  = json_decode($_POST['json'], true);
            $uid       = $response['uid'];
            $sessionId = urlencode($response['sessionId']);
            $game      = 'sanguomobiletwo';
            $channel   = $response['channel'];
            $signkey   = $loginConfig['signkey'];
            $sign      = md5($uid.$sessionId.$signkey.$game.$channel);//md5(uid+sessionId+signkey+game+chanel) ,此处signkey下面单独提供
            //拼参数
            $paramArr = [
                'uid'       => $uid,
                'sessionId' => $sessionId,
                'game'      => $game,
                'channel'   => $channel,
                'sign'      => $sign
                ];
            if(isset($response['channel_extern_data'])) {
                $paramArr['channel_extern_data'] = $response['channel_extern_data'];
            }
            $host  = $loginConfig['host'];
            $param = http_build_query($paramArr);

            $url = $host . '?' . $param;

            $content = json_decode(file_get_contents($url), true);
            if($content['status']!='failed') {
                $content['channel'] = $channel;
            }
            $content['message'] = urldecode($content['message']);
            if($content['status']=='success') {
                $data         = [];
                $uuid         = $content['uid'] . '_' . $channel;
                $data['list'] = (new PlayerServerList)->getByUuid($uuid);
                $data['last'] = (new PlayerLastServer)->getByUuid($uuid);
                $r            = ['returnMsg' =>$content, 'PlayerServerList'=>$data];
            } else {
                $r = ['returnMsg'=>$content, 'PlayerServerList'=>[]];
            }
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        } else {
            $r = ['returnMsg'=>'missing post data!', 'PlayerServerList'=>[]];
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    /**
     * 获取服务器列表
     *
     * 使用方法如下
     *
     * ```php
     * login_server/getServerList
     * ```
     */
    public function getServerListAction(){
        $serverList = (new ServerList)->dicGetAll();
        $data['server_list'] = keepFields($serverList, ['id', 'areaName', 'name', 'status', 'isNew', 'gameServerHost', 'netServerHost', 'default_enter', 'maintain_notice']);
        $data['whitelist_flag'] = 0;

        $clientIp          = $_SERVER['REMOTE_ADDR'];
        $LoginServerConfig = new LoginServerConfig;
        $ipLimitConfig     = $LoginServerConfig->getValueByKey('ip_limit_config_global');//全局服务器白名单
        if($ipLimitConfig) {
            $ipLimit  = json_decode($ipLimitConfig, true);
            $limitIps = $ipLimit['ips'];
            foreach($limitIps as $v) {
                $v = trim($v);
                if(strpos($v, '.*')!==false) {//ip段
                    $ipSegment = substr($v, 0, strlen($v)-strlen('.*'));
                    if(strpos($clientIp, $ipSegment)===0) {
                        $data['whitelist_flag'] = 1;
                    }
                }
                if($v==$clientIp) {//直接ip命中
                    $data['whitelist_flag'] = 1;
                }
            }
        }
        echo (json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
        exit;
    }
    /**
     * 获取玩家服务器列表 & 最后一次登录服务器
     *
     * 使用方法如下
     *
     * ```php
     * login_server/getPlayerServerList
     * postData:{"uuid":"uuid-test"}
     * return: {...}
     * ```
     */
    public function getPlayerServerListAction(){
        $uuid             = $_POST['uuid'];
        $PlayerServerList = new PlayerServerList;
        $PlayerLastServer = new PlayerLastServer;
        $data             = [];
        $data['list']     = $PlayerServerList->getByUuid($uuid);
        $data['last']     = $PlayerLastServer->getByUuid($uuid);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    /**
     * login_server/clearLoginServerCache/sanguo_login_server
     */
    public function clearLoginServerCacheAction($pwd=''){
        if($pwd=='sanguo_login_server') {
            Cache::db('login_server')->flushDB();
            exit('OK');
        } else {
            exit('密码错误');
        }
    }
}

