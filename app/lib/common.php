<?php
function lcg_value1(){
	return mt_rand(1, mt_getrandmax()) / mt_getrandmax();
}
if(!function_exists('microtime_float')) {
    function microtime_float(){
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}
/**
 * php log internal
 * @param  object $o 
 */
function phplog($o){
    file_put_contents(APP_PATH."/app/logs/debug.log", $o, FILE_APPEND);
}
/**
 * 发送之前 延迟发送的socket数据
 */
function flushSocketSend(){
    if(StaticData::$delaySocketSendFlag) {
        StaticData::$delaySocketSendFlag = false;
        foreach(StaticData::$delaySocketSendData as $v) {
			if(!$v['server']){
				socketSend($v['data']);
			}else{
				crossSocketSend($v['server'], $v['data']);
			}
        }
		StaticData::$delaySocketSendData = [];
    }
}
/**
 * 通过socket发送数据给服务器
 *
 * 使用方法如下：
 * ```php
 * $data = ['Msg'=>'ChatSendRequest', 'Type'=>1, 'Data'=>['to_player_id'=>100029, 'msg_content'=>'100029,bbbbb'.mt_rand(888,999)]];
 * socketSend($data);
 * ```
 * 
 * @param  array  $data 数据格式如下 # <code>['Type':1, 'Data'=>[]] </code> # Type和Data的具体值，前后端商定
 *
 */
function socketSend(array $data){
    if(StaticData::$delaySocketSendFlag) {//延迟发送
        StaticData::$delaySocketSendData[] = ['server'=>0, 'data'=>$data];
        return false;
    }
    global $config;
    $maxConnectTimes = 3;//最大重连次数

    $client = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>5, 'usec' =>0));//设置5秒接受超时

    ConnectServer:
    if(@socket_connect($client, $config->swoole->host, $config->swoole->port)) {
        // $msgId  = StaticData::$msgIds['ChatSendRequest'];
        if(isset($data['Msg'])) {
            $msg       = $data['Msg'];
            $msgId     = StaticData::$msgIds[$msg];
            $msgIdMap  = StaticData::$msgIdMap[$msg];
            $msgIdResp = StaticData::$msgIds[$msgIdMap];
        } else {
            $msgId     = StaticData::$msgIds['WebServerRequest'];
            $msgIdResp = StaticData::$msgIds['WebServerResponse'];
        }
        $data   = json_encode($data);
        $head   = pack("A4I1I1A*", "SGMB", $msgId, strlen($data), $data);
        socket_write($client, $head, strlen($head));
        while($out=@socket_read($client, 1024)) {//此处不报错
            $data = unpackData($out);
            if($data['msgId']==$msgIdResp) {//通信完，关闭socket
                socket_close($client);
                return $data;
                // break;
            }
        }
    } else {//断开重连,暂设置10次
        if($maxConnectTimes--) {
            goto ConnectServer;
        } else {
            socket_close($client);
        }
    }
}

function crossSocketSend($serverId, $data){
	if(StaticData::$delaySocketSendFlag) {//延迟发送
        StaticData::$delaySocketSendData[] = ['server'=>$serverId, 'data'=>$data];
        return false;
    }
	global $config;
	if($config->server_id==$serverId) {//本服
		socketSend($data);
	} else {//他服
		$targetGameServerHost = (new ServerList)->getGameServerHostByServerId($serverId);
		//$targetGameServerHost = (new ServerList)->getGameServerHostByServerId($serverId);
		if ($targetGameServerHost) {
			$url          = $targetGameServerHost . '/api/sendSocket';
			$field        = ['data'=>iEncrypt($data)];//['type' => iEncrypt($type), 'data'=>iEncrypt($data)];
			curlPost($url, $field);
		}
	}
}

function logSend($playerId, $data){
    if(LOG_TASK_SWITCH_ON==0) {
        return;
    }
    $sconfig = StaticData::$logConfig;
    $client  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if(@socket_connect($client, SWOOLE_HOST, $sconfig['port'])) {
        $data   = json_encode($data, JSON_UNESCAPED_UNICODE);
        $head   = pack("I1I1A*", $playerId, strlen($data), $data);
        socket_write($client, $head, strlen($head));
        socket_close($client);
    }
}
/**
 * 原生php mysqli的执行sql语句
 *
 * ```php
 * $sql = 'select id, player_id, type from player_project_queue limit 1;';
 * iquery($sql);
 * ```
 * 
 * @param  string $sql 
 * @param  bool $updateFlag true for update
 * @param  bool $multiFlag  multi or single
 * @param  string $dbconfig  'pk'
 * @return array
 */
function iconnection($dbconfig=null){
    global $config;
    if(is_null($dbconfig)) {
        $mysqli = @new mysqli($config->database->host, $config->database->username, $config->database->password, $config->database->dbname);
    } elseif($dbconfig=='pk') {
        $mysqli = @new mysqli($config->pk_server->database->host, $config->pk_server->database->username, $config->pk_server->database->password, $config->pk_server->database->dbname);
	} elseif($dbconfig=='cross') {
        $mysqli = @new mysqli($config->cross_server->database->host, $config->cross_server->database->username, $config->cross_server->database->password, $config->cross_server->database->dbname);
	} elseif($dbconfig=='citybattle') {
        $mysqli = @new mysqli($config->citybattle_server->database->host, $config->citybattle_server->database->username, $config->citybattle_server->database->password, $config->citybattle_server->database->dbname);
    }
    if(mysqli_connect_errno()){
        echo "ERROR:".mysqli_connect_error();
        return false;
    }
	return $mysqli;
}
function iquery($sql, $updateFlag=false, $multiFlag=false, $dbconfig=null){
	$mysqli = iconnection($dbconfig);
	if(!$mysqli)
		return false;
    if($multiFlag) {
        $re = $mysqli->multi_query($sql);
    } else {
        $re = $mysqli->query($sql);
    }
    if(!$re){
        echo "ERROR:".$mysqli->error;
		$mysqli->close();
        return false;
    }
    if(!$updateFlag) {
        $result = $re->fetch_all(MYSQLI_ASSOC);
        $re->free();
    } else {
        $result = $mysqli->affected_rows;
    }
    $mysqli->close();
    return $result;
}
function iescape($str, $dbconfig=null){
	$mysqli = iconnection($dbconfig);
	if(!$mysqli)
		return $str;
	$str = $mysqli->real_escape_string($str);
	$mysqli->close();
	return $str;
}
/**
 * view stack trace
 */
function trace() {
	debug_print_backtrace();
}

//获取缓存data键名
function getDataCacheKey($playerId, $cacheName){
	return 'data'.$cacheName.':I'.$playerId;
}

/**
 * 按字段过滤结果
 * 
 * @param <array/object> $data 待过滤数据
 * @param <type> $fields 指定字段
 * @param <type> $reverse true：fields为过滤掉的字段；false：fields为保留的字段
 * 
 * @return <array>
 */
function objFilter($data, $fields=array(), $reverse=false){
	if(!is_array($data)){
		$data = $data->toArray();
	}
	foreach($data as $_k => $_v){
		if(!$reverse && !in_array($_k, $fields)){
			unset($data[$_k]);
		}elseif($reverse && in_array($_k, $fields)){
			unset($data[$_k]);
		}
	}
	return $data;
}

/**
 * 按字段过滤结果集
 * 
 * @param <array/object> $data 待过滤数据集
 * @param <type> $fields 指定字段
 * @param <type> $reverse true：fields为过滤掉的字段；false：fields为保留的字段
 * 
 * @return <array>
 */
function objFilters($data, $fields=array(), $reverse=false){
	if(!is_array($data)){
		$data = $data->toArray();
	}
	$ret = array();
	foreach($data as $_k => $_a){
		$ret[$_k] = objFilter($_a, $fields, $reverse);
	}
	return $ret;
}

/**
 * 打印输出函数
 * @param  anytype  $o 
 */
function pr($o){
    if(!QA) return;
    if(DEBUG_LOG_ON) {
        if($o) {
            echo '<xmp>';
            print_r($o);
            echo "</xmp>\n";
        } else {
            var_dump($o);
        }
    } else {
        print_r($o);
    }
}
/**
 * log for cli
 *
 * @param      $o
 * @param bool $formaterFlag
 */
function log4cli($o, $formaterFlag=true){
    if(QA) {
        log4task($o, $formaterFlag);
    }
}

/**
 * server log
 * log for server
 * @param $o
 */
function log4server($o, $formaterFlag=true){
    if(QA || CLI_LOG_ON) {
        log4task($o, $formaterFlag);
    }
}
/**
 * log for task
 *
 * @param      $o
 * @param bool $formaterFlag
 */
function log4task($o, $formaterFlag=true){
    if($formaterFlag) {
        echo '[cli][' . date('Y-m-d H:i:s') . ']';
    }
    if(empty($o)) {
        echo '<空>';
    } else {
        print_r($o);
    }
    if($formaterFlag) {
        echo PHP_EOL;
    }
}
/**
 * 调用phalcon的debug方法
 *
 * @param      $o
 * @param bool $cliFlag
 * @param bool $returnFlag
 * @return null|string
 */
function dump($o, $cliFlag=false, $returnFlag=false){
    $out = (new \Phalcon\Debug\Dump())->variable($o);
    if($cliFlag) {
        $out =  strip_tags($out);
    }
    if($returnFlag) {
        return $out;
    } else {
        echo $out;
    }
}
/**
 * 合并请求
 *
 * <code>
 *       $nodes = [
 *       ['url'=>'King/getInfo', 'postData'=>['a'=>'A']],
 *       ['url'=>'Lottery/checkPlayerLotteryInfo'],
 *       ];
 * </code>
 *
 * @param array $nodes
 * @param array $uuid 验证
 * @return string
 */
function comboRequest(array $nodes, $postData, $uuid){
    global $config;
    $timestamp = time();
    $validStr  = md5($uuid . INNER_REQUEST_VALIDATION_STRING);
    $validStr  = $validStr . '@' . $timestamp;
    $validStr  = base64_encode($validStr);
    $innerUrl  = (new ServerList)->getGameServerIpByServerId($config->server_id);//'http://32.cn';
    $response  = ['code'=>0, 'data'=>[], 'basic'=>[], 'steps'=>[], 'exec_time'=>[]];
    foreach($nodes as $node) {
        $url             = strtolower($node['url']);
        $fields          = $postData;
        $fields['inner'] = 1;
        if(isset($node['field'])) {
            $fields = array_merge($fields, $node['field']);
        }
        $_url = $innerUrl . '/' . $url . "?uuid={$uuid}&inner=". $validStr;
        //debug($_url, 1);
        //debug($fields, 1);
        $_resp = curlPost($_url, $fields);
        if($_resp) {
            $_resp = json_decode($_resp, true);
            if(!is_array($_resp)) return [];//格式不符合
            if($_resp['code']>0) {//有异常，优先返回
                return encodeResponseData(json_encode($_resp));
            } else {//处理合并逻辑
                if(isset($response['data'][$url])) {
                    $response['data'][$url] = array_merge($response['data'][$url], $_resp['data']);
                } else {
                    $response['data'][$url] = $_resp['data'];
                }
                $response['basic']           = array_merge($response['basic'], $_resp['basic']);
                $response['steps']           = array_merge($response['steps'], $_resp['steps']);
                $response['exec_time'][$url.'_'.uniqid()] = $_resp['exec_time'];
            }
        } else {
            return [];
        }
    }
    return $response;
}
/**
 * 获取post数据
 *
 * @return int|mixed|string
 */
function getPost(){
    if(isset($_REQUEST['_url'])) {
        StaticData::$_url = $_REQUEST['_url'];
    }
    if(isset($_GET['inner'])) {//是否内部访问
        $uuid      = $_REQUEST['uuid'];
        $validStr  = base64_decode($_GET['inner']);
        $pos       = strrpos($validStr, '@');
        $len       = strlen($validStr);
        $timestamp = substr($validStr, $pos + 1, $len - $pos);
        $validStr  = substr($validStr, 0, $pos);
        if(md5($uuid.INNER_REQUEST_VALIDATION_STRING)==$validStr && (time()-$timestamp)<15) {
            StaticData::$_postData = $_POST;
            return $_POST;
        }
    }
    if(ENCODE_FLAG) {
        if((empty($_POST) && QA) || StaticData::$adminQAFlag) {//qa test 服务器浏览器
            unset($_REQUEST['_url']);
            reset($_REQUEST);//确保指针指向数组头位置
            $data = key($_REQUEST);
            $data = decodePostData($data);
            StaticData::$_postData = $data;
            return $data;
        } else {//客户端
            reset($_POST);//确保指针指向数组头位置
            $postData = key($_POST);
            $postData = decodePostData($postData);
            StaticData::$_postData = $postData;
            return $postData;
        }
    } else {
         $jsonData = (empty($_POST) && QA) ? $_REQUEST['json'] : $_POST['json'];
         return json_decode($jsonData, true);
    }
}
/**
 * 二进制字符显示
 * @param  binary string $binarydata 
 * @return string             
 */
function displayBinary($binarydata){
    $field=bin2hex($binarydata);
    $field=chunk_split($field,2,"\\x");
    $field= "\\x" . substr($field,0,-2);
    return $field;
}
/**
 * 加密&解密二进制
 * @param  binary string $binaryStr 
 * @return binary string            
 */
function en_decryptData($binaryStr){
    $binaryStrLen = strlen($binaryStr);
    $cryptArr = [0x4c, 0x48, 0x53, 0x6c, 0x68, 0x73, 0x78, 0x79, 0x39, 0x5a, 0x4e, 0x2b, 0x3a, 0x3b, 0x0a, 0x7d];
    $cryptLen = count($cryptArr);

    $i = 0;
    for($j=0; $j<$binaryStrLen; $j++) {
        $val = $binaryStr[$j];
        $val = unpack('c', $val);
        $val = array_pop($val);
        $xor = $cryptArr[$i++];
        $val = $val ^ $xor;
        $binaryStr[$j] = pack('c', $val);
        if($i==$cryptLen) {
            $i = 0;
        }
    }
    return $binaryStr;
}

/**
 * decode post 数据
 * @param  string $postData 
 * @return string
 */
function decodePostData($postData){
    $key = 'json';
    $postData = strtr($postData, ['%2B'=>'+','%2F'=>'/','%3D'=>'=']);
    $postData = base64_decode($postData);
    $postData = aesDecode($postData);
    $postData = zlib_decode($postData);

    $originStr = $postData;
    $pos       = strrpos($originStr, '@');
    $len       = strlen($originStr);
    $hashStr   = substr($originStr, $pos + 1, $len - $pos);
    $postData  = $originStr = substr($originStr, 0, $pos);

    if(!validateStr($originStr, $hashStr)) {
        exit("\n[ERROR]illegal validate\n");
    }

    $postData = substr($postData, strlen($key.'='), strlen($postData)-strlen($key.'='));
    $postData = json_decode($postData, true);
    return $postData;
}
/**
 * 加密数据
 * @param  string $postData 
 * @return string           
 */
function encodeResponseData($postData){
    if(ENCODE_FLAG) {
        $data = zlib_encode($postData, ZLIB_ENCODING_DEFLATE);
        $data = aesEncode($data);
        $data = base64_encode($data);
        // $data = strtr($data, ['+'=>'%2B','/'=>'%2F','='=>'%3D']);
        return $data;
    } else {
        return $postData;
    }
}
/**
 * 解密数据
 * @param  string $postData 
 * @return string           
 */
function decodeResponseData($postData){
    if(strpos($postData, 'Notice') !== false || strpos($postData, 'Warning') !== false || strpos($postData, 'error') !== false) {
        echo $postData;
        exit;
    } else {
        $data = base64_decode($postData);
        $data = aesDecode($data);
        return zlib_decode($data);
    }
}
/**
 * 模拟客户端加密数据
 * @param  string $postData 
 * @return string           
 */
function encodePostData($postData){
    if(ENCODE_FLAG) {
        $data = $postData . '@' . hashMethod($postData);
        $data = zlib_encode($data, ZLIB_ENCODING_DEFLATE);
        $data = aesEncode($data);
        $data = base64_encode($data);
        $data = strtr($data, ['+'=>'%2B','/'=>'%2F','='=>'%3D']);
        return $data;
    } else {
        return $postData;
    }
}
/**
 * 输出log到debug.log文件中,需要constant中开启DEBUG_LOG为true
 * 
 * 使用方法如下
 *
 * ```php
 *       debug('333');
 *       $player = new Player;
 *       $re = $player->findFirst();
 *       $re = $re->toArray();
 *       debug($re);
 * ```
 * @param mixed $o 需要log的信息
 * @param bool $forceFlag true: 强制打log， false是全局log开关 DEBUG_LOG_ON 打开方可开启
 */
function debug($o, $forceFlag=false){
    if($forceFlag || DEBUG_LOG_ON) {
        global $di;
        if($o) {
            $info = print_r($o, true);
        } else {
            $info = var_export($o, true);
        }
        $di->get('debug')->info($info);
    }
}

/**
 * 解析异常抛出信息
 * 
 * @param <type> $e Exception
 * 
 * @return array array($err, $msg)
 */
function parseException($e){
	$err = '';
	$msg = '';
	if(!$e->getCode()){
		$msg = $e->getMessage();
		$msg = explode('::', $msg);
		if(count($msg) == 2){
			$err = $msg[0];
			$msg = $msg[1];
		}elseif(count($msg) == 3){
			$err = '';
			$class = str_replace('Controller', '', $msg[0]);
			$func = str_replace('Action', '', $msg[1]);
			$msg = $class.'::'.$func.'::'.$msg[2];
		}else{
			$msg = $msg[0];
		}
	}else{
		$err = '1';
		$msg = $e->getMessage();
	}
	
	if(is_numeric($err.$msg)){
		$code = ($err.$msg)*1;
	}else{
		$code = $err.$msg;
	}
	if(substr($code, 0, 8) == 'SQLSTATE'){
		if(QA){
			$code = 'system error: '.$code;
		}else{
			$code = 'system error';
		}
	}
	//return array($err, $msg);
	return array($code, $err);
}

/**
 * 验证uuid值
 * 
 * 使用方法如下
 * ```php
 * validateUuid($uuid, $hashCode)
 * ```
 * @param   string    $uuid    uuid
 * @param   string    $hashCode    hash code
 * @return  bool    ture:yes, false:no
 */
function validateUuid($uuid, $hashCode){
    return validateStr($uuid, $hashCode);
}

/**
 * @param $str
 * @param $hashCode
 *
 * @return bool
 *
 * 验证
 */
function validateStr($str, $hashCode){
    return $hashCode==hashMethod($str);
}

/**
 * 前端hash方法的后端实现
 * @param  string $in input string
 * @return string     output string
 */
function hashMethod($in){
    $out = md5($in.'Salt.SanGuoMobile2');
    return $out;
}
/**
 * 登录hash
 * @param  int $playerId 
 * @return string           
 */
function loginHashMethod($playerId){
    $out = md5($playerId.uniqid());
    return $out;
}

/**
 * 过滤字段函数
 * 
 * @param   array    $re    需要过滤的数组
 * @param   bool    $forDataFlag    是否提供给data用
 * @param   array    $blacklist    黑名单字段
 * @return  array    过滤后的数组
 */
function filterFields($re, $forDataFlag, $blacklist){
    if($forDataFlag && $blacklist) {//如果是给data包使用，需要检查blacklist
        foreach($re as $k1=>$v1){
            foreach($blacklist as $v2) {
                unset($re[$k1][$v2]);
            }
        }
        return $re;
    }
    return $re;
}

/**
 * 保留前端需要的字段 
 *
 * ```php
 * $arr = [['a'=>'A', 'b'=>'b', 'c'=>'C', 'd'=>'D'], ['a'=>'A1', 'b'=>'b1', 'c'=>'C', 'd'=>'D'],['a'=>'A2', 'b'=>'b2', 'c'=>'C', 'd'=>'D'],];
 * $re = keepFields($arr, ['b','d','a']);
 * ```
 * @param   array    $arr    需要过滤的数组
 * @param   array    $whitelist    黑名单字段
 * @return  array    保留的数组
 */
function keepFields(array $arr, $whitelist, $firstFlag=false){
    if(empty($arr)) {
        return [];
    }
    if($firstFlag) {
        $arr = [$arr];
    }
    $re = [];
    foreach($arr as $k1=>$v1){
        foreach($whitelist as $v2) {
            if(isset($arr[$k1][$v2])) {
                $re[$k1][$v2] = $arr[$k1][$v2];
            }
        }
    }
    if($firstFlag) {
        $re = $re[0];
    }
    return $re;
}

/**
 * 概率计算
 * @param array('a'=>0.5, 'b'=>0.2)
 * @return string (key of array, eg. 'a' or 'b')
 */
function random($proArr) {
	$result = '';
    $proSum = array_sum($proArr);
    foreach ($proArr as $key=>$proCur) {
        $randNum = $proSum*lcg_value1();
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    return $result;
}
/**
 * 返回概率计算的结果
 *
 * 使用方法如下：
 * ```php
 * call:    randomByField(['11'=>['a'=>2, 'b'=>4], '22'=>['a'=>3, 'b'=>5]], 'a');
 * return: ['11'=>['a'=>2, 'b'=>4]]
 * ```
 * @param  array $proArr 
 * @param  string $field  
 * @return array         
 */
function randomByField($proArr, $field=''){
    $result = [];
    $proSum = 0;
    foreach($proArr as $v) {
        $proSum += $v[$field];
    }
    foreach ($proArr as $key=>$v) {
        $randNum = $proSum*lcg_value1();
        if($randNum <= $v[$field]) {
            $result[$key] = $v;
            break;
        } else {
            $proSum -= $v[$field];
        }
    }
    return $result;
}

/**
 * 解析接受到的数据包
 * @param  array $content 发送的数据内容
 * @param  int $msgId 发送的数据内容 //default: 10005 “数据”包响应
 *
 * @return string  binary string
 */
function packData($content, $msgId=10005) {
    $content = is_scalar($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE);
    $packet  = pack("A4I1I1A*", SWOOLE_MSG_HEAD, $msgId, strlen($content), $content);
    return $packet;
}

/**
 * 解包
 * @param  string $content 
 * @return array Unpack data from binary string
 */
function unpackData($content){
    $unpacket = unpack("A4head/I1msgId/I1length/A*content", $content);
    return $unpacket;
}

/**
 * 检查自然数
 * 
 * @param <type> $v 
 * 
 * @return <type>
 */
function checkRegularNumber($v, $zeroFlag = false){
	if((int)abs($v).'' == $v && ($v > 0 || ($v == 0 && $zeroFlag))){
		return true;
	}
	return false;
}
/**
 * quote for ZDQ    
 * @param  string $str
 * @return string
 */
function q($str=''){
    return "'".$str."'";
}

/**
 * quote for ZDQ    
 * @param  string $time
 * @return string
 */
function qd($time=0) {
    $time = $time ? $time : time();
    return "'".date("Y-m-d H:i:s", $time)."'";
}

/**
 * 解析字典数据的group格式
 * 
 * @param <type> $data 
 * 
 * @return <type>
 */
function parseGroup($data, $hasKey=true, $deleteKey=false){
	if($data){
		$data = explode(';', $data);
		$_data = array();
		foreach($data as $_d){
			$_d = explode(',', $_d);
			if($hasKey){
				$_data[$_d[0]] = $_d;
				if($deleteKey){
					unset($_data[$_d[0]][0]);
					$_data[$_d[0]] = array_values($_data[$_d[0]]);
				}
			}else{
				$_data[] = $_d;
			}
		}
	}else{
		$_data = array();
	}
	return $_data;
}

function joinGroup($data, $hasKey=true){
	foreach($data as $_k =>&$_d){
		if($hasKey){
			if(!is_array($_d))
				$_d = [$_d];
			$_d = array_merge([$_k], $_d);
		}
		$_d = join(',', $_d);
	}
	unset($_d);
	return join(';', $data);
}

/**
 * 解析字典数据的array格式
 * 
 * @param <type> $data 
 * @param <type> $intFlag
 *
 * @return <type>
 */
function parseArray($data, $intFlag=false){
	if($data){
		$re = explode(',', $data);
        if($intFlag) {
            $re = array_map('intval', $re);
        }
        return $re;
	}else{
		return array();
	}
}

/**
 * 解码两层k=>v组合成的字符串数据
 * @param  [type] $str [description]
 * @return [type]      [description]
 */
function sanguoDecodeStr($str){
    $result = [];
    $tArr = explode(";", $str);
    foreach ($tArr as $value) {
		if($value){
			$t2Arr = explode(",", $value);
		}else{
			$t2Arr = [];
		}
        $result[$t2Arr[0]] = $t2Arr[1];
    }
    return $result;
}

/**
 * 计算资源负重
 * 
 * 
 * @return <type>
 */
function resourceWeight($resource){
	$weight = 0;
	foreach($resource as $_k => $_r){
		switch($_k){
			case 'wood':
			case 11:
			case 5:
				$weight += $_r * WEIGHT_WOOD;
			break;
			case 'food':
			case 10:
			case 4:
				$weight += $_r * WEIGHT_FOOD;
			break;
			case 'gold':
			case 9://矿类型
			case 3:
				$weight += $_r * WEIGHT_GOLD;
			break;
			case 'iron':
			case 13:
			case 7:
				$weight += $_r * WEIGHT_IRON;
			break;
			case 'stone':
			case 12:
			case 6:
				$weight += $_r * WEIGHT_STONE;
			break;
		}
	}
	return $weight;
}

/**
 * 计算实际负重所能携带资源
 * 
 * 
 * @return <type>
 */
function weightCarry($inResource, $weight){
	$types = [
		'wood'=>'wood','5'=>'wood','11'=>'wood',
		'food'=>'food','10'=>'food','4'=>'food',
		'gold'=>'gold','9'=>'gold','3'=>'gold',
		'iron'=>'iron','13'=>'iron','7'=>'iron',
		'stone'=>'stone','12'=>'stone','6'=>'stone',
	];
	if(resourceWeight($inResource) <= $weight){
		$outResource = [];
		foreach($inResource as $_k => $_num){
			$outResource[$types[$_k]] = $_num;
		}
		return $outResource;
	}
	$outResource = array();
	$_weight = 0;
	
	//处理大块资源
	$ar = ['wood'=>WEIGHT_WOOD, 11=>WEIGHT_WOOD, 5=>WEIGHT_WOOD,
		'food'=>WEIGHT_FOOD, 10=>WEIGHT_FOOD, 4=>WEIGHT_FOOD,
		'gold'=>WEIGHT_GOLD, 9=>WEIGHT_GOLD, 3=>WEIGHT_GOLD,
		'iron'=>WEIGHT_IRON, 13=>WEIGHT_IRON, 7=>WEIGHT_IRON,
		'stone'=>WEIGHT_STONE, 12=>WEIGHT_STONE, 6=>WEIGHT_STONE,
	];
	while(array_sum($inResource) > 0){
		$minNum = false;
		$basicWeight = 0;
		foreach($inResource as $_k => $_r){
			if(!$_r) continue;
			if($minNum === false){
				$minNum = $_r;
			}else{
				$minNum = min($minNum, $_r);
			}
			$basicWeight += $ar[$_k];
		}
		if($basicWeight > ($weight - $_weight))
			break;
		//echo 'basicWeight:'.$basicWeight.'<br>';
		//echo 'minNum:'.$minNum.'<br>';
		$basicNum = min($minNum, floor(($weight - $_weight) / $basicWeight));
		//echo 'basicNum:'.$basicNum.'<br>';
		foreach($inResource as $_k => &$_r){
			if(!$_r) continue;
			$_r -= $basicNum;
			@$outResource[$types[$_k]] += $basicNum;
		}
		unset($_r);
		//var_dump($outResource);
		//var_dump($inResource);
		$_weight += $basicNum * $basicWeight;
		//echo 'rest:'.($weight - $_weight).'<br>';
	}
	
	//处理剩余分摊
	/*while(array_sum($inResource) > 0){
		$flag = false;
		foreach($inResource as $_k => &$_r){
			if(!$_r) continue;
			switch($_k){
				case 'wood':
				case 11:
				case 5:
					$_type = 'wood';
					$_weight += WEIGHT_WOOD;
				break;
				case 'food':
				case 10:
				case 4:
					$_type = 'food';
					$_weight += WEIGHT_FOOD;
				break;
				case 'gold':
				case 9://矿类型
				case 3:
					$_type = 'gold';
					$_weight += WEIGHT_GOLD;
				break;
				case 'iron':
				case 13:
				case 7:
					$_type = 'iron';
					$_weight += WEIGHT_IRON;
				break;
				case 'stone':
				case 12:
				case 6:
					$_type = 'stone';
					$_weight += WEIGHT_STONE;
				break;
				default:
					return false;
			}
			if($_weight <= $weight){
				$_r--;
				@$outResource[$_type]++;
				$flag = true;
			}
		}
		unset($_r);
		if(!$flag)
			break;
	}*/
	return $outResource;
}

/**
 * 资源分赃
 * 
 * @param <array> $contributions 贡献权重
 * @param <array> $weights 负重
 * @param <array> $resource 资源
 * @example splitResource(array(100016=>10, 100017=>90), array(100016=>1000, 100017=>1500), array('food'=>500, 'wood'=>700))
 * 
 * @return <type>
 */
function splitResource($contributions, $weights, $resource){
	$personNum = count($contributions);//分赃人数
	$contriSum = array_sum($contributions);//总贡献
	if(!$contriSum){
		foreach($contributions as &$_c){
			$_c = 1;
		}
		unset($_c);
		$contriSum = array_sum($contributions);//总贡献
	}
	$gets = [];//分赃结果
	$getWeights = [];//分赃重量
	$materialType = ['iron'=>WEIGHT_IRON, 'stone'=>WEIGHT_STONE, 'wood'=>WEIGHT_WOOD, 'gold'=>WEIGHT_GOLD, 'food'=>WEIGHT_FOOD];
	//按照权重排序
	arsort($contributions);
	$emptyResourceCount = 0;
	$fullPlayer = [];
	$emptyResource = [];
	$fullPlayerResource = [];
	//$j=0;
	//while(true){
		foreach($materialType as $_mt => $_mtweight){
			$_fullPlayerResource = [];
			while(true){
				if(!@$resource[$_mt]){
					if(!@$emptyResource[$_mt]){
						$emptyResource[$_mt] = true;
						$emptyResourceCount++;
					}
				}else{
					foreach($contributions as $_playerId => $_cont){
						if(@$fullPlayer[$_playerId]) continue;
						if(!$resource[$_mt]){//资源分光
						}elseif(($weights[$_playerId] - @$getWeights[$_playerId]*1) < $_mtweight){//重量不够
							$fullPlayerResource[$_playerId][$_mt] = true;
							$_fullPlayerResource[$_playerId] = true;
						}else{
							$_shouldGetResource = $resource[$_mt] * ($_cont / $contriSum);
							$_playerLeftWeight = $weights[$_playerId] - @$getWeights[$_playerId]*1;//该玩家剩余重量
							$_realGetResource = floor(max(1, min($_playerLeftWeight, $_shouldGetResource * $_mtweight) / $_mtweight));
							$resource[$_mt] -= $_realGetResource;
							@$gets[$_playerId][$_mt] += $_realGetResource;
							@$getWeights[$_playerId] += $_realGetResource * $_mtweight;
						}
						if(!@$fullPlayer[$_playerId] && (count(@$fullPlayerResource[$_playerId]) + count($emptyResource)) >= 5){
							$fullPlayer[$_playerId] = true;
							$contriSum -= $contributions[$_playerId];
						}
					}
				}
				if(!@$resource[$_mt] || count($_fullPlayerResource) >= $personNum){
					break;
				}
				//if(count($emptyResource) >= 5 /*资源发完*/ || count($fullPlayer) >= $personNum /*全部满负重*/){
				//	break 2;
				//}
			}
		}
	//}
	$gets[0] = ['iron'=>@$resource['iron']*1, 'stone'=>@$resource['stone']*1, 'gold'=>@$resource['gold']*1, 'wood'=>@$resource['wood']*1, 'food'=>@$resource['food']*1];//$resource;
	return $gets;
}

function clacAccNeedGem($second){
	return ceil(pow($second, 0.911)*0.085);
}

function clacDistance($p1, $p2){
    return sqrt(pow(($p2[0]-$p1[0]),2)+pow(($p2[1]-$p1[1]),2)); 
}

function getRandByArr($arr){
	$total = array_sum($arr);
	$seed = mt_rand(1, $total);
	foreach ($arr as $key => $value) {
		if($value>=$seed){
			return $key;
		}else{
			$seed -= $value;
		}
	}
	return false;
}

function getRandString($length){
    $str = "";
    for($i=0;$i<$length;$i++){
        $m = mt_rand(48, 83);
        $str .= chr(($m>57)?$m+7:$m);
    }
    return $str;
}

function distance2move($distance){
	return floor(max(pow($distance, 0.911)*0.45, 5));
}

function joinStr($str, $args){
	//$args = ['level'=>2, 'name'=>'abc'];
	//$str = '將在%{level}級時解鎖%{name}';
	if(preg_match_all("/\%{([\d\w\_]+)}/", $str, $match)){
		$r = [];
		$_args = [];
		foreach($match[1] as $_m){
			//$r[] = '".@$args["'.$_m.'"]."';
			$r[] = "%s";
			$_args[] = $args[$_m];
		}
		$s = str_replace($match[0], $r, $str);
		$str = vsprintf($s, $_args);
		//var_dump(eval('$str = '.$s.';'));
		//var_dump($str);
		return $str;
	}else{
		return $str;
	}
}
/**
 * cli下红色字
 */
function red($str){
    return "\033[31;1m{$str}\033[0m";
}
/**
 * cli下蓝色字
 */
function blue($str){
    return "\033[34;1m{$str}\033[0m";
}
/**
 * cli下显示各种颜色字
 * @param  string  $str    
 * @param  string  $color  颜色 
 * @param  boolean $isBold 是否加粗
 * @return string          
 */
function color($str, $color='red', $isBold=false) {
    $coloText = '';
    $codes = [
        'red'    => 31,//红色
        'green'  => 32,//绿色
        'brown'  => 33,//棕色
        'blue'   => 34,//蓝色
        'purple' => 35,//紫色
        'cyan'   => 36,//青色
        'white'  => 37,//白色
    ];
    if(isset($codes[$color])) {
        $colorCode = $codes[$color];
        if($isBold) {
            $colorCode .= ';1';
        }
        $colorText = "\033[{$colorCode}m{$str}\033[0m";
    } else {
        $colorText = $str;
    }
    return $colorText;
}

/**
 * server log的fd前缀颜色
 *
 * @param $fd
 *
 * @return string
 */
function fdPrefix($fd) {
    $colors = [
        'red'   ,//红色
        'green' ,//绿色
        'brown' ,//棕色
        'blue'  ,//蓝色
        'purple',//紫色
        'cyan'  ,//青色
        'white' ,//白色
    ];
    $key = $fd%count($colors);
    $color = $colors[$key];
    return color("fd={$fd} ", $color, true);

}

function dbBegin($db){
	$db->begin();
	CollectionBase::begin();
	StaticData::$delaySocketSendFlag = true;
	ModelBase::$_delaySocketSendFlag = true;
}

function dbCommit($db){
	$db->commit();
	CollectionBase::commit();
	flushSocketSend();
	(new ModelBase)->flushCrossExec();
}

function dbRollback($db){
	$db->rollback();
	CollectionBase::rollback();
	StaticData::$delaySocketSendFlag = false;
	ModelBase::$_delaySocketSendFlag = false;
	StaticData::$delaySocketSendData = [];
	ModelBase::$_delaySocketSendData = [];
}
/**
 * 使用方法如下
 *
 * ```php
 * $url='http://127.0.0.1/dev/t';
 * $fields = [
 *   'lname'=>'justcoding',
 *   'fname'=>'phplover',
 *  ];
 *  curlPost($url, $fields);
 *  ```
 *
 * @param $url
 * @param $fields
 *
 * @return string
 *
 *
 */
function curlPost($url, $fields){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

    ob_start();
    curl_exec($ch);
    $result = ob_get_contents();
    ob_end_clean();
    curl_close($ch);
    return $result;
}

/**
 * 使用方法如下
 *
 * ```php
 * $nodes = ['url'=>'http://127.0.0.1/dev/t', 'fields'=>['a'=>'A','b'=>'B'],[...]]
 * //不传fields则默认fields为[]
 *  curlMultiPost($nodes);
 *  ```
 *
 * @param $nodes
 */
function curlMultiPost($nodes) {
    $mh      = curl_multi_init();
    $curlArr = [];
    foreach ($nodes as $k=>$v) {
        $curlArr[$k] = curl_init($v['url']);
        curl_setopt($curlArr[$k], CURLOPT_RETURNTRANSFER, false);
        if(!isset($v['fields'])) {
            $v['fields'] = [];
        }
        curl_setopt($curlArr[$k], CURLOPT_POST, count($v['fields']));
        curl_setopt($curlArr[$k], CURLOPT_POSTFIELDS, $v['fields']);
        curl_multi_add_handle($mh, $curlArr[$k]);
    }
    $running = null;
    ob_start();
    do {
        usleep(10000);
        curl_multi_exec($mh, $running);
    } while ($running > 0);
    ob_end_clean();
    curl_multi_close($mh);
}

/**
 * has emoji description
 * @param  string $text 
 * @return string
 */
function hasEmoji($text){
  return preg_match('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', $text);
}
/**
 * [enEmoji description]
 */
function enEmoji($text){
    $text=  preg_replace_callback('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', function($m){
            $bin = pack('a*', $m[0]);
            $hex = bin2hex($bin);
            return '#-'.$hex.'-#';
        }, $text);
    return $text;
}
/**
 * [deEmoji description]
 */
function deEmoji($text){
    $text = preg_replace_callback('/#\-(.+?)\-#/u', function($m){
            $hex = $m[1];
            $text = unpack('a*e', hex2bin($hex));
            return $text['e'];
        }, $text);
    return $text;
}
/**
 * key也保留的shuffle
 */
function ashuffle(&$arr) {
    uasort($arr, function($a, $b) {
        return rand(-1, 1);
    });
}

/**
 * 开放接口时 接口的加密方法
 *
 * @param $o
 * @param $key
 *
 * @return string
 */
function iEncrypt($o, $key='sanguo_mobile2'){
    $str = json_encode($o, JSON_UNESCAPED_UNICODE);
    $info = urlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $str, MCRYPT_MODE_CBC, md5(md5($key)))));
    return $info;
}

/**
 * 开放接口时 接口的解密方法
 * @param $str
 * @param $key
 *
 * @return string
 */
function iDecrypt($str, $key='sanguo_mobile2'){
    $info = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key),base64_decode(urldecode($str)), MCRYPT_MODE_CBC, md5(md5($key))));
    $info = json_decode($info, true);
    return $info;
}

/**
 * 加密给前端
 *
 * @param $content
 *
 * @return string
 *
 */
function aesEncode($content){
    $length    = strlen($content);
    $key       = pack('A*', 'hdkjfhxcbvuiuri3289492hdskjfhadr');//key定义为：char aes_key_buf[32] = "ijshanckdwiiuedhsiqo0955;[jsmc4";
    $iv        = pack("c16", 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00);//iv定义为 16字节的\0字符
    $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $content, MCRYPT_MODE_CBC, $iv);
    $re        = pack('N1', $length).$encrypted;
    return $re;
}
/**
 * 解密前端数据
 * ```php
 * 数据最前面的4个字节为一个int的二进制（注意是大端），这个int表示了加密之前的数据总长度
 * 现在只需要读出加密的数据，然后偏移4个字节传入cbc获取解密之后的数据，这个解密之后的数据比原始数据要大，其中有效的数据长度就是那个int表示的长度。
 * ```
 * @param $binaryData
 *
 * @return string
 */
function aesDecode($binaryData){
    $data      = unpack("N1length", $binaryData);
    $content   = substr($binaryData, 4, strlen($binaryData) - 4);
    $length    = $data['length'];
    $key       = pack('A*', 'hdkjfhxcbvuiuri3289492hdskjfhadr');//key定义为：char aes_key_buf[32] = "ijshanckdwiiuedhsiqo0955;[jsmc4";
    $iv        = pack("c16", 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00);//iv定义为 16字节的\0字符
    $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $content, MCRYPT_MODE_CBC, $iv);
    $re        = substr($decrypted, 0, $length);
    return $re;
}

/**
 * 模拟客户端访问 -暂不允许跨服
 *
 * ```php
 *  $re = simulateClientPostRequest('1459665_dsuc', 'data/index', ['name'=>['PlayerInfo']]);
 *  dump($re);
 * ```
 * @param $uuid
 * @param $url
 * @param $fields
 *
 * @return mixed|string
 */
function simulateClientPostRequest($uuid, $url, $fields){
    global $config;
    $postUrl = (new ServerList)->getGameServerIpByServerId($config->server_id) . '/' . $url . '?uuid=' . $uuid;
//    dump($postUrl);

    $player = (new Player)->getPlayerByUuid($uuid);
    $playerInfo = (new PlayerInfo)->getByPlayerId($player['id']);

    $fields['timestamp']      = time();
    $fields['timeCollated']   = 1;
    $fields['uuid']           = $uuid;
    $fields['game_version']   = (new LoginServerConfig)->getValueByKey('game_version');
    $fields['login_hashcode'] = $playerInfo['login_hashcode'];
    $fields['hashCode']       = hashMethod($uuid);

    $postData = json_encode($fields);
    $postData = 'json=' . $postData;
    $postData = [encodePostData($postData) => ''];
    $re       = trim(curlPost($postUrl, $postData));
    $re       = decodeResponseData($re);
    $re       = json_decode($re, true);
    return $re;
}

function checkNewbieActivityServer($flag=1){
	global $config;
	$serverId   = $config->server_id;
	if($flag == 1){
		if($serverId < (new Starting)->dicGetOne('act_server_id')){
			return false;
		}
	}elseif($flag == 2){
		if($serverId < (new Starting)->dicGetOne('act_server_id2')){
			return false;
		}
	}
	return true;
}

/**
 * 子进程重连db
 */
function reConnectDb(){
    global $di, $config;
    $di['db']->connect($config->database->toArray());
    $di['db_login_server']->connect($config->login_server->database->toArray());
    $di['db_pk_server']->connect($config->pk_server->database->toArray());
    $di['db_cross_server']->connect($config->cross_server->database->toArray());
	$di['db_citybattle_server']->connect($config->citybattle_server->database->toArray());
}

/**
 * array to string
 * @param $o
 *
 * @return string
 */
function arr2str($o){
    if(!is_scalar($o)) {
        return json_encode($o, JSON_UNESCAPED_UNICODE);
    }
    return $o;
}