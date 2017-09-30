<?php
class ClientTestTask extends \Phalcon\CLI\Task{
    /**
     * bootstrap
     * @return [type] [description]
     */
    public function mainAction($param=array()){
		$playerId = $param[0];
		$player = (new Player)->getByPlayerId($playerId);
		if(!$player){
			echo 'no find player';
			exit;
		}
        $data = array(
			'uuid'=>$player['uuid'],
		);
		global $config;
		$maxConnectTimes = 10;//最大重连次数

		$client = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>5, 'usec' =>0));//设置5秒接受超时

		ConnectServer:
		if(@socket_connect($client, $config->swoole->host, $config->swoole->port)) {
			$msgId  = StaticData::$msgIds['LoginRequest'];
			$data   = json_encode($data);
			$head   = pack("A4I1I1A*", "SGMB", $msgId, strlen($data), $data);
			socket_write($client, $head, strlen($head));
			while(true) {//此处不报错
				//$out=@socket_read($client, 1024);
				$h=@socket_read($client, 12);
				if(!strlen($h)){
					goto nn;
				}
				$h1 = unpack("A4head/I1msgId/I1length", $h);
				$h2=@socket_read($client, $h1['length']*1);
				$out = $h.$h2;
				if($out){
					$data = unpackData($out);
					switch($data['msgId']){
						case StaticData::$msgIds['LoginResponse']:
							echo "LoginResponse\r\n";
						break;
						case StaticData::$msgIds['HeartBeatResponse']:
						break;
						case StaticData::$msgIds['DataResponse']:
							echo "DataResponse\r\n";
							if(@$data['content']){
								var_dump(json_decode($data['content'], true));
							}
						break;
						case StaticData::$msgIds['WebServerResponse']:
							echo "WebServerResponse\r\n";
						break;
						default:
							echo "UNDEFINED Response!!\r\n";
							var_dump( $data);
							
					}
				}else{
					nn:
					$msgId  = StaticData::$msgIds['HeartBeatRequest'];
					$data   = json_encode($data);
					$head   = pack("A4I1I1A*", "SGMB", $msgId, strlen($data), $data);
					socket_write($client, $head, strlen($head));
				}
			}
		} else {//断开重连,暂设置10次
			if($maxConnectTimes--) {
				goto ConnectServer;
			} else {
				echo 'close';
				socket_close($client);
			}
		}
    }

}
