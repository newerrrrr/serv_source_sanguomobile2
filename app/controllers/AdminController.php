<?php
use Phalcon\Mvc\View;
/**
 *	管理端 
 */
class AdminController extends ControllerBase
{
	public $user;
	public $authType = [
		101=>'玩家信息',
		102=>'玩家建筑',
		103=>'玩家武将',
		104=>'玩家道具',
		105=>'玩家军团',
		106=>'玩家增益',
		107=>'玩家科技',
		108=>'玩家天赋',
		109=>'充值订单',
		110=>'玩家邮件',
		201=>'增加元宝',
		202=>'封号/解封',
		203=>'发送道具',
		204=>'修改密码',
		205=>'修改玩家属性',
		206=>'发送至尊卡/月卡',
		207=>'发送礼包',
		301=>'玩家元宝消耗日志',
		302=>'玩家元宝获取日志',
		303=>'常规日志',
		304=>'管理端日志',
		305=>'联盟商店日志',
	    306=>'监控假量',
		401=>'发送 全体/指定 邮件',
		501=>'联盟信息',
		601=>'写公告',
		602=>'看公告',
		603=>'发送走马灯公告',
		604=>'聊天相关',
		605=>'队列脚本控制',
		606=>'激活码生成',
		607=>'激活码管理',
		608=>'创建机器人',
		609=>'缓存工具',
		610=>'增加推送',
		700=>'活动配置',
        800=>'维护文字修改',
        801=>'武斗跨服配置',
		900=>'跨服战信息',
		901=>'城战信息',
	];
	public $cookie;
	public $timeout = 3600*24*365;
		
	public function initialize() {
		//parent::initialize();
		//$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
		//$this->view->setRenderLevel(View::LEVEL_LAYOUT);
		if('login' != $this->getActionName() && 'loginRemote' != $this->getActionName()){
			if(substr($this->getActionName(), 0, 4) == 'ajax'){
				$ajax = true;
			}else{
				$ajax = false;
			}
			if(isset($_REQUEST['cookie'])){
				$cookie = json_decode($_REQUEST['cookie'], true);
				$timeout = time()+$this->timeout;
				setcookie('sg2admin_name', $cookie['name'], $timeout, '/');
				$this->cookie['sg2admin_name'] = $cookie['name'];
				setcookie('sg2admin_valid_time', $cookie['timeout'], $timeout, '/');
				$this->cookie['sg2admin_valid_time'] = $cookie['timeout'];
				setcookie('sg2admin_crypt', $cookie['crypt'], $timeout, '/');
				$this->cookie['sg2admin_crypt'] = $cookie['crypt'];
				
				$name = @$cookie['name'];
				$validTime = @$cookie['timeout'];
				$crypt = @$cookie['crypt'];
			}else{
				$name = @$_COOKIE['sg2admin_name'];
				$validTime = @$_COOKIE['sg2admin_valid_time'];
				$crypt = @$_COOKIE['sg2admin_crypt'];
			}

			//检查是否登录
			//检查时限
			if(!$name || !$validTime || !$crypt
			|| $validTime < time()
			|| $crypt != $this->encodeCrypt($name, $validTime)
			){
				setcookie('sg2admin_name', '', time(), '/');
				setcookie('sg2admin_valid_time', '', time(), '/');
				setcookie('sg2admin_crypt', '', time(), '/'); 
				//跳转登录
				if(isset($_REQUEST['r'])){
					$redirect = $_REQUEST['r'];
				}else{
					$redirect = $_SERVER['REQUEST_URI'];
				}
				$r = explode('?', $redirect);
				$url = $r[0];
				if(@$r[1]){
					$param = explode('&', $r[1]);
					$_param = [];
					foreach($param as $_p){
						if(explode('=', $_p)[0] == 'cookie') continue;
						$_param[] = $_p;
					}
					$url .= '?'.join('&', $_param);
				}
				
				if(!$ajax)
					$this->response->redirect( '/admin/login?r='.urlencode($url) )->sendHeaders();
				exit;
			}elseif($ajax && isset($_REQUEST['cookie'])){

			}elseif(isset($_REQUEST['cookie'])){
				if($_SERVER['REQUEST_METHOD'] == 'POST'){
					$redirect = $_SERVER['REQUEST_URI'];
					$r = explode('?', $redirect);
					$url = $r[0];
					if(@$r[1]){
						$param = explode('&', $r[1]);
						$_param = [];
						foreach($param as $_p){
							if(explode('=', $_p)[0] == 'cookie') continue;
							$_param[] = $_p;
						}
						$url .= '?'.join('&', $_param);
					}
					echo '<form method="POST" action="'.$url.'" name="frm">';
					foreach($_POST as $_k => $_p){
						echo "<input type='hidden' name='".htmlentities($_k)."' value='".htmlentities($_p, ENT_COMPAT | ENT_HTML401 , 'UTF-8')."'>";
					}
					echo '</form>';
					echo '<script>';
					echo 'document.frm.submit();';
					echo '</script>';
				}else{
					$this->response->redirect( '/admin/'.$this->getActionName() )->sendHeaders();
				}
				exit;
			}else{
				$this->cookie = $_COOKIE;
			}
			
			//读取服务器列表
			if(!$ajax){
				$ServerList = new ServerList;
				$serverList = $ServerList->dicGetAll();
				$currentServer = '';
				$currentServerId = 0;
				foreach($serverList as $_l){
					if($_l['gameServerHost'] == 'http://'.$_SERVER['HTTP_HOST']){
						$currentServer = $_l['name'];
						$currentServerId = $_l['id'];
						break;
					}
				}
				$this->view->setVars([
					"serverList"=>	$serverList,
					"currentServer"=>$currentServer,
					"currentServerId"=>$currentServerId,
				]);
			}
		}
		$this->view->setVar("actionName",$this->getActionName());
	}
	
	public function indexAction(){
		$this->response->redirect( 'admin/dashboard' )->sendHeaders();
		exit;
	}
	
	public function loginAction(){
		$this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
		$name = trim(@$_REQUEST['admin_name']);
		$password = trim(@$_REQUEST['admin_password']);
		$server = trim(@$_REQUEST['admin_server']);

		//读取服务器
		$ServerList = new ServerList;
		$serverList = $ServerList->dicGetAll();
		$this->view->setVar(
			"serverList",
			$serverList
		);
		
		if($name && $password){
			$AdminUser = new AdminUser;
			if(!$AdminUser->checkUser($name, $password)){
				$this->view->setVar(
					"errmsg",
					'用户不存在或者密码错误'
				);
			}else{
				//setcookie
				$timeout = time()+$this->timeout;
				//setcookie('sg2admin_name', $name, $timeout, '/');
				//setcookie('sg2admin_valid_time', $timeout, $timeout, '/');
				//setcookie('sg2admin_crypt', $this->encodeCrypt($name, $timeout), $timeout, '/');
				//跳转主页
				$cookie = ['name'=>$name, 'timeout'=>$timeout, 'crypt'=>$this->encodeCrypt($name, $timeout)];
				//echo $server.'/admin/loginRemote?cookie='.json_encode($cookie);exit;
				//$this->response->redirect( $server.'/admin/loginRemote?cookie='.json_encode($cookie) .'&redirect='.@$_REQUEST['admin_redirect'])->sendHeaders();
				if(@$_REQUEST['admin_redirect']){
					$redirect = $_REQUEST['admin_redirect'];
				}else{
					$redirect = '/admin/index';
				}
				$r = explode('/', $redirect);
				if($r[1] != 'admin'){
					setcookie('sg2admin_name', $name, $timeout, '/');
					setcookie('sg2admin_valid_time', $timeout, $timeout, '/');
					setcookie('sg2admin_crypt', $this->encodeCrypt($name, $timeout), $timeout, '/');
					$this->response->redirect( $server.$redirect)->sendHeaders();
				}else{
					if(false === strpos($redirect, '?')){
						$redirect .= '?';
					}
				//var_dump($server.$redirect.'&cookie='.json_encode($cookie));exit;

					$this->response->redirect( $server.$redirect.'&cookie='.json_encode($cookie))->sendHeaders();
				}
				exit;
			}
		}
	}
	
	public function generalUrlWithCookie($url){
		if(false === strpos($url, '?')){
			$url .= '?';
		}
		$cookie = ['name'=>$_COOKIE['sg2admin_name'], 'timeout'=>$_COOKIE['sg2admin_valid_time'], 'crypt'=>$_COOKIE['sg2admin_crypt']];
		return $url . '&cookie='.urlencode(json_encode($cookie));
	}
	/*
	public function loginRemoteAction(){
		$cookie = trim(@$_REQUEST['cookie']);
		$cookie = json_decode($cookie, true);
		$redirect = trim(@$_REQUEST['redirect']);
		
		$timeout = time()+10*3600;
		setcookie('sg2admin_name', $cookie['name'], $timeout, '/');
		setcookie('sg2admin_valid_time', $cookie['timeout'], $timeout, '/');
		setcookie('sg2admin_crypt', $cookie['crypt'], $timeout, '/');
		
		//var_dump($_REQUEST);
		//exit;
		if($redirect){
			$this->response->redirect( $redirect )->sendHeaders();
		}else{
			$this->response->redirect( 'admin/index' )->sendHeaders();
		}
	}
	*/
	public function logoutAction(){
		setcookie('sg2admin_name', '', time(), '/');
		setcookie('sg2admin_valid_time', '', time(), '/');
		setcookie('sg2admin_crypt', '', time(), '/'); 
		setcookie('sg2admin_pleaseChangePwd', 1, time(), '/');
		//跳转登录
		$this->response->redirect( 'admin/login' )->sendHeaders();
		exit;
	}
	
	public function directToLogin(){
		//跳转主页
		$this->response->redirect( 'admin/login' )->sendHeaders();
		exit;
	}
	
	public function redirectServerAction(){
		$url = $_REQUEST['url'].'/admin/';
		$url = $this->generalUrlWithCookie($url);
		$this->response->redirect( $url )->sendHeaders();
		exit;
	}
	
	public function modifyPwdAction(){
		$oldpassword = @$_REQUEST['modify_oldpassword'];
		$password = @$_REQUEST['modify_password'];
		if(!$password || strlen($password) < 6 || strlen($password) > 20){
			echo json_encode(['errmsg'=>'密码长度不符合规则（6-20位）']);
			exit;
		}
		if($oldpassword == $password){
			echo json_encode(['errmsg'=>'新密码必须和旧密码不同']);
			exit;
		}
		$AdminUser = new AdminUser;
		if(!$AdminUser->modifyPwd($this->cookie['sg2admin_name'], $oldpassword, $password)){
			echo json_encode(['errmsg'=>'修改密码失败']);
			exit;
		}
		echo json_encode(['errmsg'=>'ok']);
		exit;
	}
	
	public function encodeCrypt($name, $timeout){
		return md5($name . $timeout . AdminUser::AUTH_SECRET);
	}
	
	public function getPlayerId(){
		$type = @$_REQUEST['_playerType'];
		$id = @$_REQUEST['_playerId'];
		if(!$id)
			return 0;
		$Player = new Player;
		if($type == 1){
			$player = $Player->findFirst($id);
			if(!$player)
				$playerId = 0;
			else
				$playerId = $id;
		}elseif($type == 2){
			$ret = $Player->getPlayerByUuid($id);
			if($ret){
				$playerId = $ret['id'];
			}
		}elseif($type == 3){
			$Player = new Player;
			$ret = $Player->getByPlayerNick($id);
			if($ret){
				$playerId = $ret['id'];
			}
		}elseif($type == 4){
			$Player = new Player;
			$ret = $Player->find(['user_code="'.$id.'"'])->toArray();
			if($ret){
				$playerId = $ret[0]['id'];
			}
		}
		if(!$playerId || !is_numeric($playerId))
			return false;
		return $playerId;
	}
		
    /**
     * 动态数据表格(for mysql)
	 * 参见logGemConsumeAction例子
     * 
     * @param <type> $model 
     * @param <type> $where 
     * 
     * @return <type>
     */
	public function dataTableGet($model, $where='', $returnAllColumn=false){
		if(!$where)
			$where = '1=1';
		$start = $_REQUEST['start'];
		$draw = $_REQUEST['draw'];
		$length = $_REQUEST['length'];
		$order = $_REQUEST['order'];
		$search = $_REQUEST['search'];
		$columns = $_REQUEST['columns'];
		$column = [];
		$searchStr = [];
		$searchStr1 = [];
		$search['value'] = trim($search['value']);
		
		$re = $model->getDescType();
		$timeColumn = [];
		foreach($re as $k=>$v) {
            $v = strtolower($v);
            if('timestamp'==$v) {
                $timeColumn[] = $k;
			}
		}
		
		foreach($columns as $_c){
			$column[] = $_c['data'];
		}
		$column = array_intersect($column, array_keys($re));
		foreach($columns as $_c){
			if(!in_array($_c['data'], $column)) continue;
			if($search['value']){
				if(!preg_match('/^[0-9a-zA-Z\-\/\.\:\s]*$/', $search['value']) && in_array($_c['data'], $timeColumn)){//中文字符时候不搜索时间列
					
				}else{
					$searchStr[] = '`'.$_c['data'] . '` like "%'.$search['value'].'%"';
					$searchStr1[] = $_c['data'] . ' like "%'.$search['value'].'%"';
				}
			}
		}
		if($searchStr){
			$searchStr = join(' or ', $searchStr);
			$searchStr1 = join(' or ', $searchStr1);
		}
		
		if($searchStr){
			$whereSearch = $where . ' and ('.$searchStr.')';
			$whereSearch1 = $where . ' and ('.$searchStr1.')';
		}else{
			$whereSearch1 = $whereSearch = $where;
		}
		//echo $whereSearch;
		//$data = $model->find([$whereSearch, 'columns'=>join(',', $column), 'limit'=>$length, 'offset'=>$start, 'order'=>$column[$order[0]['column']].' '.$order[0]['dir']])->toArray();
		if($returnAllColumn){
			$selectColumn = '*';
		}else{
			$selectColumn = '`'.join('`,`', $column).'`';
		}
		$sql = 'select '.$selectColumn.' from '.$model->getSource().' where '.$whereSearch.' order by `'.$column[$order[0]['column']].'` '.$order[0]['dir'].' limit '.$start.','.$length;
		//exit;
		$data = $model->sqlGet($sql);
		$recordsTotal = $model->count([$where]);
		$recordsFiltered = $model->count([$whereSearch1]);
		return [
			'draw'=>$draw,
			'recordsTotal'=>$recordsTotal,
			'recordsFiltered'=>$recordsFiltered,
			'data'=>$data,
		];
	}
	
	/**
     * 动态数据表格(for mysql)
	 * 参见logGemConsumeAction例子
     * 
     * @param <type> $model 
     * @param <type> $where 
     * 
     * @return <type>
     */
	public function dataCollectionGet($model, $where=[]){
		if(!$where)
			$where = [];
		$start = $_REQUEST['start']*1;
		$draw = $_REQUEST['draw'];
		$length = $_REQUEST['length']*1;
		$order = $_REQUEST['order'];
		$search = $_REQUEST['search'];
		$columns = $_REQUEST['columns'];
		$column = [];
		$searchStr = [];
		$search['value'] = trim($search['value']);
		
		if($search['value']){
			foreach($columns as $_c){
				$searchStr[] = [$_c['data']=>new MongoRegex('/'.$search['value'].'/')];
			}
		}
		foreach($columns as $_c){
			$column[] = $_c['data'];
		}
		
		if($searchStr){
			$whereSearch = [$where, '$or'=>$searchStr];
		}else{
			$whereSearch = $where;
		}
		//$data = $model->sqlGet('select `'.join('`,`', $column).'` from '.$model->getSource().' where '.$whereSearch.' order by `'.$column[$order[0]['column']].'` '.$order[0]['dir'].' limit '.$start.','.$length);
		//var_dump(['conditions'=>$whereSearch, 'sort'=>[$column[$order[0]['column']]=>($order[0]['dir']=='desc'?-1:1)], 'skip'=>$start, 'limit'=>$length]);
		$data = $model->findArray(['conditions'=>$whereSearch, 'sort'=>[$column[$order[0]['column']]=>($order[0]['dir']=='desc'?-1:1)], 'skip'=>$start, 'limit'=>$length]);
		$recordsTotal = $model->count([$where]);
		$recordsFiltered = $model->count([$whereSearch]);
		return [
			'draw'=>$draw,
			'recordsTotal'=>$recordsTotal,
			'recordsFiltered'=>$recordsFiltered,
			'data'=>$data,
		];
	}
	
	public function dashboardAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 101;
		if(!$this->checkAuthId($authCode))
			return;
		if(!$this->user['pwd_status'] && !@$_COOKIE['sg2admin_pleaseChangePwd']){
			$this->view->setVar(
				"pleaseChangePwd",
				1
			);
			$timeout = time()+10*3600;
			setcookie('sg2admin_pleaseChangePwd', 1, $timeout, '/');
		}
		$this->view->setVar(
			"playerAllCounter",
			Player::count()
        );
		
		$this->view->setVar(
			"playerNewCounter",
			Player::count(['create_time >="'.date('Y-m-d 00:00:00').'"'])
        );
		
		$this->view->setVar(
			"playerLoginCounter",
			Player::count(['login_time >="'.date('Y-m-d 00:00:00').'"'])
        );
		
		//计算在线人数-大概	
		$data        = ['Msg'=>'DataRequest', 'Type'=>'all_conn_info', 'Data'=>[]];
        $re          = socketSend($data);
        $r           = json_decode($re['content'], true);
		$this->view->setVar(
			"playerOnlineCounter",
			count($r)
		);
		
		//渠道
		if(!@$_REQUEST['loginChannel'])
			$_REQUEST['loginChannel'] = '';
		$loginChannel = $_REQUEST['loginChannel'];
		$loginChannels = [
			''=>'所有',
			'huawei'=>'华为',
			'lenovo'=>'联想',
			'oppo'=>'OPPO',
			'gionee'=>'金立',
			'meizu'=>'魅族',
			'vivo'=>'VIVO',
			'mi'=>'小米',
			'coolpad'=>'酷派',
			'tencent'=>'应用宝',
			'aligames'=>'阿里游戏',
			'baidu'=>'百度',
			'qihu'=>'360',
			'pengyouwan'=>'朋友玩',
			'downjoy'=>'当乐',
			'muzhiwan'=>'拇指玩',
			'x7sy'=>'小7手游',
			'6y'=>'乐游',
			'taptap'=>'taptap',
		];
		$this->view->setVars([
			"loginChannels"=>$loginChannels,
			"loginChannel"=>$_REQUEST['loginChannel'],
			"loginChannelName"=>@$loginChannels[$_REQUEST['loginChannel']],
		]);
		
		//留存
		if(isset($_REQUEST['askForLiucun'])){
			//实时数据
			$Player = new Player;
			$today = time();
			$todayDate = date('Y-m-d', $today);
			$days = [2, 3, 4, 5, 6, 7, 14, 30];
			$dates = [];
			$liucun = [];
			$liucunData = [];
			foreach($days as $_d){
				$dates[$_d] = date('Y-m-d', $today-($_d-1)*3600*24);
				if('' == $loginChannel){
					$c1 = $Player->count(["create_time >='".$dates[$_d]." 00:00:00' and create_time <='".$dates[$_d]." 23:59:59' and login_time >='".$todayDate." 00:00:00' and login_time <='".$todayDate." 23:59:59' and uuid not like 'Robot%'"]);
					$c2 = $Player->count(["create_time >='".$dates[$_d]." 00:00:00' and create_time <='".$dates[$_d]." 23:59:59' and uuid not like 'Robot%'"]);
				}elseif('taptap' == $loginChannel){
					$c1 = $Player->sqlGet("select count(a.id) from player a, player_info b where a.id=b.player_id and b.download_channel='".$loginChannel."' and a.create_time >='".$dates[$_d]." 00:00:00' and a.create_time <='".$dates[$_d]." 23:59:59' and a.login_time >='".$todayDate." 00:00:00' and a.login_time <='".$todayDate." 23:59:59' and a.uuid not like 'Robot%'")[0]['count(a.id)'];
					$c2 = $Player->sqlGet("select count(a.id) from player a, player_info b where a.id=b.player_id and b.download_channel='".$loginChannel."' and a.create_time >='".$dates[$_d]." 00:00:00' and a.create_time <='".$dates[$_d]." 23:59:59' and a.uuid not like 'Robot%'")[0]['count(a.id)'];
				}else{
					$c1 = $Player->sqlGet("select count(a.id) from player a, player_info b where a.id=b.player_id and b.login_channel='".$loginChannel."' and a.create_time >='".$dates[$_d]." 00:00:00' and a.create_time <='".$dates[$_d]." 23:59:59' and a.login_time >='".$todayDate." 00:00:00' and a.login_time <='".$todayDate." 23:59:59' and a.uuid not like 'Robot%'")[0]['count(a.id)'];
					$c2 = $Player->sqlGet("select count(a.id) from player a, player_info b where a.id=b.player_id and b.login_channel='".$loginChannel."' and a.create_time >='".$dates[$_d]." 00:00:00' and a.create_time <='".$dates[$_d]." 23:59:59' and a.uuid not like 'Robot%'")[0]['count(a.id)'];
				}
				
				if($c2){
					$liucun[$dates[$_d]] = $c1 / $c2;
				}else{
					$liucun[$dates[$_d]] = 0;
				}
				$liucunData['liucun'.$_d] = $liucun[$dates[$_d]];
			}
						
			$this->view->setVars([
				"liucun"=>$liucun,
				"liucunDay"=>$days,
				"liucunDt"=>date('Y-m-d H:i:s', $today),
				"liucunData"=>json_encode($liucunData),
				"showType"=>1,
			]);
		}
		
		//付费
		if(isset($_REQUEST['askForPay'])){
			$Player = new Player;
			$today = time();
			$todayDate = date('Y-m-d', $today);
			$payData = [];
			
			if('' == $loginChannel){
				//登陆人数
				$loginNum = $Player->count(["login_time >='".$todayDate." 00:00:00' and login_time <='".$todayDate." 23:59:59' and uuid not like 'Robot%'"]);
				
				$PlayerOrder = new PlayerOrder;
				
				//付费人数
				$payPlayerNum = $PlayerOrder->sqlGet('select count(distinct player_id) as num from '.$PlayerOrder->getSource().' where status=1 and create_time >="'.$todayDate.' 00:00:00" and create_time <= "'.$todayDate.' 23:59:59"')[0]['num']*1;
				
				//付费总额
				$payRmb = $PlayerOrder->sqlGet('select sum(rmb_value) as num from '.$PlayerOrder->getSource().' a, pricing b where a.payment_code = b.payment_code and status=1 and create_time >="'.$todayDate.' 00:00:00" and create_time <= "'.$todayDate.' 23:59:59"')[0]['num']*1;
			}elseif('taptap' == $loginChannel){
				//登陆人数
				$loginNum = $Player->sqlGet("select count(a.id) from player a, player_info b where a.id=b.player_id and b.download_channel='".$loginChannel."' and a.login_time >='".$todayDate." 00:00:00' and a.login_time <='".$todayDate." 23:59:59' and a.uuid not like 'Robot%'")[0]['count(a.id)'];
				
				$PlayerOrder = new PlayerOrder;
				
				//付费人数
				$payPlayerNum = $PlayerOrder->sqlGet('select count(distinct a.player_id) as num from '.$PlayerOrder->getSource().' a, player_info b where a.player_id=b.player_id and b.download_channel="'.$loginChannel.'" and a.status=1 and a.create_time >="'.$todayDate.' 00:00:00" and a.create_time <= "'.$todayDate.' 23:59:59"')[0]['num']*1;
				
				//付费总额
				$payRmb = $PlayerOrder->sqlGet('select sum(rmb_value) as num from '.$PlayerOrder->getSource().' a, pricing b, player_info c where a.player_id=c.player_id and c.download_channel="'.$loginChannel.'" and a.payment_code = b.payment_code and a.status=1 and a.create_time >="'.$todayDate.' 00:00:00" and a.create_time <= "'.$todayDate.' 23:59:59"')[0]['num']*1;
			}else{
				//登陆人数
				$loginNum = $Player->sqlGet("select count(a.id) from player a, player_info b where a.id=b.player_id and b.login_channel='".$loginChannel."' and a.login_time >='".$todayDate." 00:00:00' and a.login_time <='".$todayDate." 23:59:59' and a.uuid not like 'Robot%'")[0]['count(a.id)'];
				
				$PlayerOrder = new PlayerOrder;
				
				//付费人数
				$payPlayerNum = $PlayerOrder->sqlGet('select count(distinct a.player_id) as num from '.$PlayerOrder->getSource().' a, player_info b where a.player_id=b.player_id and b.login_channel="'.$loginChannel.'" and a.status=1 and a.create_time >="'.$todayDate.' 00:00:00" and a.create_time <= "'.$todayDate.' 23:59:59"')[0]['num']*1;
				
				//付费总额
				$payRmb = $PlayerOrder->sqlGet('select sum(rmb_value) as num from '.$PlayerOrder->getSource().' a, pricing b, player_info c where a.player_id=c.player_id and c.login_channel="'.$loginChannel.'" and a.payment_code = b.payment_code and a.status=1 and a.create_time >="'.$todayDate.' 00:00:00" and a.create_time <= "'.$todayDate.' 23:59:59"')[0]['num']*1;
			}
			
			$payData['pay_rate'] = $loginNum ? $payPlayerNum / $loginNum : 0;
			$payData['pay_rmb'] = $payRmb;
			$payData['arpu'] = $loginNum ? $payRmb / $loginNum : 0;
			$payData['arppu'] = $payPlayerNum ? $payRmb / $payPlayerNum : 0;

			$this->view->setVars([
				"paydata"=>$payData,
				"payDt"=>date('Y-m-d H:i:s', $today),
				"payData"=>json_encode($payData),
				"showType"=>2,
			]);
		}
		
		//历史图表
		if(isset($_REQUEST['askForLiucun']) || isset($_REQUEST['askForPay'])){
			if(isset($_REQUEST['askForLiucun'])){
				$column = [
					'liucun2'=>'次日留存',
					'liucun3'=>'3日留存',
					'liucun4'=>'4日留存',
					'liucun5'=>'5日留存',
					'liucun6'=>'6日留存',
					'liucun7'=>'7日留存',
					'liucun14'=>'14日留存',
					'liucun30'=>'30日留存',
				];
				$columnType = 1;
			}else{
				$column = [
					'pay_rate'=>'付费率',
					'pay_rmb'=>'付费额',
					'arpu'=>'ARPU',
					'arppu'=>'ARPPU',
				];
				$columnType = 2;
			}
			
			$StatSnapshot = new StatSnapshot;
			$timeTypes = ['all'=>0, 'year'=>365, 'month'=>30, 'week'=>7, 'day'=>1];
			$_REQUEST['timeTypes'] = @$_REQUEST['timeTypes'] ? $_REQUEST['timeTypes'] : 'day';
			$tableColumn = [];
			foreach(array_keys($column) as $_k){
				if(!in_array($_k, ['id', 'dt', 'type', 'pay_rmb', 'arpu', 'arppu'])){
					$tableColumn[] = 'concat('.$_k.'*100, "%") as '.$_k;
				}else{
					$tableColumn[] = $_k;
				}
			}
			if($timeTypes[$_REQUEST['timeTypes']]){
				$chartData = $StatSnapshot->sqlGet('select dt,'.join(',', $tableColumn).' from '.$StatSnapshot->getSource().' where type='.$columnType.' and channel="'.$loginChannel.'" and dt >="'.date('Y-m-d H:i:s', time()-$timeTypes[$_REQUEST['timeTypes']]*24*3600).'"');
			}else{
				$chartData = $StatSnapshot->sqlGet('select dt,'.join(',', $tableColumn).' from '.$StatSnapshot->getSource().' where type='.$columnType.' and channel="'.$loginChannel.'"');
			}
			
			if(isset($_REQUEST['askForLiucun'])){
				$_liucunData = [];
				foreach($liucunData as $_k=>$_d){
					if(!in_array($_k, ['id', 'dt', 'type', 'pay_rmb', 'arpu', 'arppu'])){
						$_liucunData[$_k] = $_d*100 .'%';
					}else{
						$_liucunData[$_k] = $_d;
					}
				}
				$chartData[] = array_merge(['dt'=>date('Y-m-d H:i:s', $today)], $_liucunData);
			}
			if(isset($_REQUEST['askForPay'])){
				$_payData = [];
				foreach($payData as $_k=>$_d){
					if(!in_array($_k, ['id', 'dt', 'type', 'pay_rmb', 'arpu', 'arppu'])){
						$_payData[$_k] = $_d*100 .'%';
					}else{
						$_payData[$_k] = $_d;
					}
				}
				$chartData[] = array_merge(['dt'=>date('Y-m-d H:i:s', $today)], $payData);
			}
			
			$this->view->setVars([
				"chartColumn"=>$column,
				"chartData"=>$chartData,
			]);
			//var_dump($chartData);exit;
		}
	}
	
    /**
     * 保存留存/付费快照
     * 
     * 
     * @return <type>
     */
	public function ajaxStatSnapshotAction(){
		$authCode = 101;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$dt = $_REQUEST['dt'];
		$type = $_REQUEST['type'];
		$loginChannel = $_REQUEST['loginChannel'];
		$data = $_REQUEST['data'];
		
		$StatSnapshot = new StatSnapshot;
		if($StatSnapshot->findFirst(['dt="'.$dt.'" and type='.$type.' and channel="'.$loginChannel.'"'])){
			echo json_encode(['err'=>'该快照已存在']);
			exit;
		}
		if($StatSnapshot->add($dt, $type, $loginChannel, $data)){
			echo json_encode(['err'=>'保存快照失败']);
			exit;
		}
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function ajaxDelStatSnapshotAction(){
		$authCode = 101;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$id = $_REQUEST['id'];
		$StatSnapshot = new StatSnapshot;
		$r = $StatSnapshot->findFirst($id);
		if(!$r){
			echo json_encode(['err'=>'记录不存在']);
			exit;
		}
		$r->delete();
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
    /**
     * 查看留存/付费快照
     * 
     * 
     * @return <type>
     */
	public function ajaxShowStatSnapshotAction(){
		$authCode = 101;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$type = $_REQUEST['type'];
		$loginChannel = $_REQUEST['loginChannel'];
		$where = [];
		if($type){
			$where[] = 'type='.$type;
		}
		$where[] = 'channel="'.$loginChannel.'"';
		$where = join(' and ', $where);
		$StatSnapshot = new StatSnapshot;
		$data = $this->dataTableGet($StatSnapshot, $where, true);
		foreach($data['data'] as &$_d){
			foreach($_d as $__k => &$__d){
				if(!in_array($__k, ['id', 'type', 'pay_rmb', 'dt', 'arpu', 'arppu'])){
					$__d = round($__d * 100, 2) . '%';
				}
			}
			$_d['op'] = "<button onclick='delSnapshot(".$_d['id'].")' class=\"btn btn-danger btn-xs\" type=\"button\">删除快照</button>";
			unset($__d);
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
    /**
     * 检查管理员权限
     * 
     * @param <type> $authId 
     * @param <type> $op 如果失败操作，0.none 1.direct，2.none
     * 
     * @return <type>
     */
	public function checkAuthId($authId, $failop=1){
		$AdminUser = new AdminUser;
		$user = $AdminUser->getUser(@$this->cookie['sg2admin_name']);
		if(!$user){
			switch($failop){
				case 1:
					$this->directToLogin();
				break;
				case 2:
					exit;
				break;
				default:
					return false;
				break;
			}
		}
		$this->user = $user;
		if($user['auth']['auth'] !== true){
			if(!in_array($authId, $user['auth']['auth'])){
				if($failop == 1){
					$this->view->setVar(
						"errmsg",
						'没有权限'
					);
				}else{
					echo json_encode(['err'=>'没有权限']);
					exit;
				}
				return false;
			}else{
				$this->view->setVar(
					"isRoot",
					false
				);
			}
		}else{
			$this->view->setVar(
				"isRoot",
				true
			);
		}
		return true;
	}
	
	public function addAdminLog($type, $memo){
		(new AdminLog)->add($this->cookie['sg2admin_name'], $type, $memo);
	}
	
    /**
     * 调用道具代码生成器
     * ps：需要在phtml中嵌入
	 * $this->partial("shared/dropCreate", array('dropInputId' => 'drop的textarea标签id', 'memoInputId'=>'drop描述的textarea标签id'));
	 * dropInputId和memoInputId可省略
     * 
     * @return <type>
     */
	public function getDropCreateVar(){
		$resource = (new Drop)->resource;
	
		$Item = new Item;
		$item = $Item->dicGetAll();
		
		$EquipMaster = new EquipMaster;
		$equipMaster = $EquipMaster->dicGetAll();
		
		$Equipment = new Equipment;
		$equipment = $Equipment->dicGetAll();
		
		$Soldier = new Soldier;
		$soldier = $Soldier->dicGetAll();
		
		$this->view->setVars([
			'resource'=>$resource,
			'item' => $item,
			'equipMaster'=>$equipMaster,
			'equipment'=>$equipment,
			'soldier'=>$soldier,
		]);
	}
	
	public function ajaxTransDropstrAction(){
		$dropstr = $_REQUEST['dropstr'];
		$ret = (new Drop)->getTranslateInfo($dropstr);
		echo json_encode(['err'=>'ok', 'data'=>$ret]);
		exit;
	}
	
	public function ajaxMultiServerHandleAction(){
		$server = $_REQUEST['server'];
		$action = $_REQUEST['action'];
		$data = $_REQUEST['data'];
		
		$cookie = ['name'=>$this->cookie['sg2admin_name'], 'timeout'=>$this->cookie['sg2admin_valid_time'], 'crypt'=>$this->cookie['sg2admin_crypt']];
		$ServerList = new ServerList;
		$serverList = $ServerList->dicGetAll();
		$ret = [];
		foreach($serverList as $_server){
			if(!in_array($_server['id'], $server)) continue;
			$fields = $data;
			$fields['cookie'] = json_encode($cookie);
			$_ret = curlPost($_server['gameServerHost'].'/admin/'.$action, $fields);
			$ret[$_server['id']] = json_decode($_ret, true);
		}
		echo json_encode($ret);
		exit;
	}
	
    /**
     * 管理员编辑
     * 
     * 
     * @return <type>
     */
	public function adminManagerAction(){
		//检查权限
		$this->view->setVar("treeact",'player');
		$authCode = 0;
		if(!$this->checkAuthId($authCode))
			return;
		//获取所有管理员
		$admins = (new AdminUser)->find(['status=0'])->toArray();
		$auths = (new AdminAuth)->find()->toArray();
		$_auths = [];
		foreach($auths as $_auth){
			$_auths[$_auth['id']] = $_auth;
		}
		$this->view->setVars([
			"admins"=>$admins,
			"auths"=>$_auths,
			//"authType"=>$authType,
		]);
	}
	
	public function adminAddAction(){
		$authCode = 0;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$adminName = $_REQUEST['name'];
		$adminAuth = $_REQUEST['auth'];
		
		if(!(new AdminAuth)->find($adminAuth)){
			echo json_encode(['err'=>'权限不存在']);
			exit;
		}
		if(!(new AdminUser)->add($adminName, $adminAuth)){
			echo json_encode(['err'=>'该名字已经存在']);
			exit;
		}
		$memo = [
			'desc'=>'增加管理员',
			'name'=>$adminName,
			'auth'=>$adminAuth,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function adminDeleteAction(){
		$authCode = 0;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$adminId = $_REQUEST['adminId'];
		
		if(!(new AdminUser)->find($adminId)->delete()){
			echo json_encode(['err'=>'删除失败']);
			exit;
		}
		$memo = [
			'desc'=>'删除管理员',
			'adminId'=>$adminId,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function adminModifyAuthAction(){
		$authCode = 0;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$adminId = $_REQUEST['adminId'];
		$auth = $_REQUEST['auth'];
		
		if(!(new AdminAuth)->find($auth)){
			echo json_encode(['err'=>'权限不存在']);
			exit;
		}
		if(!(new AdminUser)->updateAll(['auth'=>$auth], ['id'=>$adminId])){
			echo json_encode(['err'=>'修改权限失败']);
			exit;
		}
		$memo = [
			'desc'=>'修改管理员权限',
			'adminId'=>$adminId,
			'auth'=>$auth,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function adminauthManagerAction(){
		//检查权限
		$this->view->setVar("treeact",'player');
		$authCode = 0;
		if(!$this->checkAuthId($authCode))
			return;
		//获取所有管理员
		$auths = (new AdminAuth)->find()->toArray();
		$_auths = [];
		foreach($auths as $_auth){
			if($_auth['auth'] != 0){
				$_auth['auth'] = explode(',', $_auth['auth']);
				$__auth = [];
				foreach($_auth['auth'] as &$_a){
					$__auth[$_a] = $this->authType[$_a];
				}
				$_auth['auth'] = $__auth;
				unset($_a);
				
			}
			$_auths[] = $_auth;
		}
		//var_dump($_auths);
		//exit;
		$this->view->setVars([
			"auths"=>$_auths,
			"authType"=>$this->authType,
		]);
	}
	
	public function adminauthAddAction(){
		$authCode = 0;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$authName = $_REQUEST['name'];
		$authInfo = $_REQUEST['auth'];
		
		if((new AdminAuth)->find(['name="'.$authName.'"'])->toArray()){
			echo json_encode(['err'=>'该名字已经存在']);
			exit;
		}
		if(!(new AdminAuth)->add($authName, $authInfo)){
			echo json_encode(['err'=>'增加失败']);
			exit;
		}
		$memo = [
			'desc'=>'增加权限',
			'authName'=>$authName,
			'authInfo'=>$authInfo,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function adminauthDeleteAction(){
		$authCode = 0;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$authId = $_REQUEST['authId'];
		
		if($authId == 1){
			echo json_encode(['err'=>'不可删除']);
			exit;
		}
		if(!(new AdminAuth)->find($authId)->delete()){
			echo json_encode(['err'=>'删除失败']);
			exit;
		}
		$memo = [
			'desc'=>'删除权限',
			'authId'=>$authId,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function modifyAuthAction(){
		$authCode = 0;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$authId = $_REQUEST['authId'];
		$auth = $_REQUEST['auth'];
		$name = $_REQUEST['name'];
		
		if(!$auth || $authId == 1)
			exit;
		$adminAuth = (new AdminAuth)->findFirst($authId);
		if(!$adminAuth)
			exit;
		$adminAuth = $adminAuth->toArray();
		if($adminAuth['name'] == $name && count($auth)==count(explode(',', $adminAuth['auth'])) && count(array_intersect($auth, explode(',', $adminAuth['auth']))) == count($auth)){
			echo json_encode(['err'=>'没有变化']);
			exit;
		}
		if(!(new AdminAuth)->updateAll(['name'=>"'".$name."'", 'auth'=>"'".join(',', $auth)."'"], ['id'=>$authId])){
			echo json_encode(['err'=>'修改权限失败']);
			exit;
		}
		$memo = [
			'desc'=>'修改权限',
			'authId'=>$authId,
			'name'=>$name,
			'auth'=>$auth,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
    /**
     * 查看玩家信息
     * auth：101
     * 
     * @return <type>
     */
	public function playerInfoAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 101;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if($player['camp_id']){
			$player['camp_name'] = (new CountryCampList)->dicGetOne($player['camp_id'])['desc'];
		}else{
			$player['camp_name'] = '';
		}
		$this->view->setVar(
			"player",
			$player
		);
		
		$PlayerInfo = new PlayerInfo;
		$playerInfo = $PlayerInfo->getByPlayerId($playerId);
		$this->view->setVar(
			"playerInfo",
			$playerInfo
		);
		
		/*$PlayerGuild = new PlayerGuild;
		$playerGuild = $PlayerGuild->getByPlayerId($playerId);
		$this->view->setVar(
			"playerGuild",
			$playerGuild
		);*/
		
		$Guild = new Guild;
		$guild = $Guild->getByPlayerId($playerId);
		$this->view->setVar(
			"guild",
			$guild
		);
		
		//vip状态
		$PlayerBuff = new PlayerBuff;
		$this->view->setVar(
			"vipStatus",
			$PlayerBuff->getPlayerBuff($playerId, 'vip_active')
		);
		
		//检查vip等级是否一致
		$pbt = (new PlayerBuffTemp)->findFirst(['player_id='.$playerId.' and buff_id=464']);
		if($pbt){
			$pbt = $pbt->toArray();
			$vipLevel = substr($pbt['buff_temp_id'], 0, 3)*1 - 110;
			if($vipLevel != $player['vip_level']){
				$this->view->setVar(
					"vipReset",
					true
				);
			}
		}
		
		//职位
		if($player['job']){
			$KingAppoint = new KingAppoint;;
			$jobName = $KingAppoint->dicGetOne($player['job'])['desc1'];
		}else{
			$jobName = '';
		}
		
		//城堡等级
		$castleLv = (new PlayerBuild)->getPlayerCastleLevel($playerId);
		
		$this->view->setVars([
			'jobName'=>$jobName,
			'castleLv'=>$castleLv,
		]);

		$this->view->banTime = (new PlayerInfo)->getBanTime($playerId);
		
		
		//获取平台用户名
		try {
			$config           = include APP_PATH . "/app/config/config.php";
			$appSecret        = 'EF86RC80';
			$parameter        = array();
			$uuidArr          = explode('_', $player['uuid']);
			$parameter['uid'] = $uuidArr[0];
			$channel          = $uuidArr[1];
			if($channel=='dsuc') {
				$data   = DsucCrypt::encrypt(json_encode($parameter),$appSecret);
				$params = array('data'=>$data);
				$url    = 'http://'.$config['dsucHost'].'/auth/getUser';
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				$output = curl_exec($ch);

				curl_close($ch);
				if($output){
					$data=json_decode($output,true);
					$this->view->setVar(
						"dsucUsername",
						$data['userName']
					);
				}
			} else {
				$this->view->setVar(
						"dsucUsername",
						'[渠道:'.$channel.']'
					);
			}
		} catch (Exception $e) {
		}
	}
	
    /**
     * 更新元宝
     * 
     * 
     * @return <type>
     */
	public function ajaxAddGemAction(){
		$authCode = 201;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$playerId = $_REQUEST['playerId'];
		$addType = $_REQUEST['addType'];//1:免费，2.收费
		$addGem = $_REQUEST['addGem'];
		$memo = $_REQUEST['memo'];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		if($addType == 2){
			$zh = '收费元宝';
			$giftFlag = false;
		}else{
			$zh = '免费元宝';
			$giftFlag = true;
		}
		if(!$Player->updateGem($playerId, $addGem, $giftFlag, '管理端增加')){
			echo json_encode(['err'=>'操作失败']);
			exit;
		}
		$memo = [
			'desc'=>'增加'.$zh,
			'playerId'=>$playerId,
			'gem'=>$addGem,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	/**
     * 修改玩家属性
     * 
     * 
     * @return <type>
     */
	public function ajaxModPlayerAttrAction(){
		$authCode = 205;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$playerId = $_REQUEST['playerId'];
		$addType = $_REQUEST['addType'];
		$addValue = floor($_REQUEST['addValue']);
		$memo = $_REQUEST['memo'];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		if(!in_array($addType, ['point', 'feats', 'jiangyin', 'xuantie', 'junzi'])){
			echo json_encode(['err'=>'非法的属性值']);
			exit;
		}
		if($addValue < 0){
			if($player[$addType] < abs($addValue)){
				echo json_encode(['err'=>'扣除数值过大']);
				exit;
			}
		}
		if(!$Player->alter($playerId, [$addType=>$addType.'+('.$addValue.')'])){
			echo json_encode(['err'=>'操作失败']);
			exit;
		}
		if($addType == 'point'){
			(new PlayerCommonLog)->add($playerId, ['type'=>'后台修改[锦囊]', 'memo'=>['total_num'=>$addValue]]);
		}elseif($addType == 'feats'){
			(new PlayerCommonLog)->add($playerId, ['type'=>'后台修改[功勋]', 'memo'=>['total_num'=>$addValue]]);
		}
		$memo = [
			'desc'=>'修改'.$addType.($addValue >= 0 ? '+' : '').$addValue,
			'playerId'=>$playerId,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
    /**
     * 发送至尊卡
     */
	public function ajaxAddLongCardAction(){
		$authCode = 206;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$playerId = $_REQUEST['playerId'];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		$PlayerInfo = new PlayerInfo;
		if($PlayerInfo->haveLongCard($playerId)){
			echo json_encode(['err'=>'无法重复购买至尊卡']);
			exit;
		}
		$PlayerInfo->alter($playerId, ['long_card'=>1]);
		$memo = [
			'desc'=>'发送至尊卡',
			'playerId'=>$playerId,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	/**
     * 发送月卡
     */
	public function ajaxAddMonthCardAction(){
		$authCode = 206;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$playerId = $_REQUEST['playerId'];
		$num = floor($_REQUEST['num']);
		if($num <= 0){
			echo json_encode(['err'=>'输入错误']);
			exit;
		}
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		
		$PlayerInfo = new PlayerInfo;
		$playerInfo = $PlayerInfo->getByPlayerId($playerId);
		$mcd = $playerInfo['month_card_deadline'];
		if($mcd>time()) {//时间未到续买
			$deadline = date("Y-m-d 00:00:00", $mcd+30*24*60*60);
		} else {
			$deadline = date("Y-m-d 00:00:00", strtotime("+".(30*$num)." day"));
		}
		$PlayerInfo->alter($playerId, ['month_card_deadline'=>$deadline]);
		$memo = [
			'desc'=>'发送月卡',
			'playerId'=>$playerId,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
    /**
     * 重建城池
     */
	public function ajaxRebuildCastleAction(){
		$authCode = 205;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$playerId = $_REQUEST['playerId'];
		if(!(new Map)->delPlayerCastle($playerId)){
			echo json_encode(['err'=>'操作失败']);
			exit;
		}
		$memo = [
			'desc'=>'重建城池',
			'playerId'=>$playerId,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function playerResetPwdAction(){
		$this->view->setVar("treeact",'playerdo');
		$authCode = 204;
		if(!$this->checkAuthId($authCode))
			return;
	}
	
	public function ajaxPlayerResetPwdAction(){
		$authCode = 204;
		if(!$this->checkAuthId($authCode, 2))
			return;
		$userAccount = @$_REQUEST['userAccount'];
		$countryCode = @$_REQUEST['countryCode'];
		$newPwd = @$_REQUEST['newPwd'];
		if(!$userAccount){
			echo json_encode(['err'=>'请输入账号']);
			exit;
		}
		if(!$newPwd){
			echo json_encode(['err'=>'请输入新密码']);
			exit;
		}
		
		$config = include APP_PATH . "/app/config/config.php";
		$password = UcCryption::e($newPwd, '4h3e4hz6');
		$source = 'SANGUOMOBILETWO';
		$timestamp = time();
		$key = 'SS96WX66MO86FABI7RK';
		$params = [
			'user_account'=>$userAccount,
			'country_code'=>$countryCode,
			'password'=>$password,
			'source'=>$source,
			'timestamp'=>$timestamp,
			'skey'=>Sha1(md5($userAccount .$countryCode. $password .$source.$timestamp.$key))
		];
		$url='http://'.$config['dsucHost'].'/auth/resetPwd';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$output = curl_exec($ch);

		curl_close($ch);
		if($output){
			$data=json_decode($output,true);
			if($data['status'] == 'success'){
				$ret = true;
			}
		}
		
		if(@$ret){
			$memo = [
				'desc'=>'修改玩家密码',
				'userAccount'=>$userAccount,
			];
			$this->addAdminLog($authCode, json_encode($memo));
			echo json_encode(['err'=>'ok']);
			
		}else{
			$errTypes = [
				'1009'=>'用户名不符合规则',
				'1001'=>'该账号不存在',
				'1001'=>'用户名不符合规则',
				'1010'=>'密码不符合规则',
				'1022'=>'密码相同',
			];
			if(@$errTypes[$data['message']]){
				$err = $errTypes[$data['message']];
			}else{
				$err = $data['message'];
			}
			echo json_encode(['err'=>'修改失败['.$err.']']);
		}
		exit;
	}
	
	public function ajaxPlayerSkipNewbieAction(){
		$authCode = 101;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		$PlayerInfo = new PlayerInfo;
		$PlayerInfo->alter($playerId, ['skip_newbie'=>1]);
		$memo = [
			'desc'=>'关闭新手引导',
			'playerId'=>$playerId,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
    /**
     * 检查并修复状态不正确部队
     * 
     * 
     * @return <type>
     */
	public function ajaxPlayerFixArmyAction(){
		$authCode = 101;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		
		$armyIds = [];

		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
		
		try {
			//获取出征部队
			$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByPlayerId($playerId);
			$PlayerProjectQueue = new PlayerProjectQueue;
			
			//循环检查是否有对应出征部队
			$armies = [];
			foreach($playerArmy as $_pa){
				if($_pa['status']){
					$condition = ['player_id='.$playerId.' and army_id='.$_pa['id'].' and status=1'];
					if(!$PlayerProjectQueue->findFirst($condition)){
						$armies[] = $_pa;
					}
				}
			}

			//修复部队
			$PlayerGeneral = new PlayerGeneral;
			foreach($armies as $_army){
				if(!$PlayerArmy->assign($_army)->updateStatus(0)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//设置武将状态
				$generalIds = $PlayerGeneral->getGeneralIdsByArmyId($playerId, $_army['id']);
				$PlayerGeneral->updateReturnByGeneralIds($playerId, $generalIds);
				$armyIds[] = $_army['id'];
			}
			
			$memo = [
				'desc'=>'修复部队army_ids='.join(',', $armyIds),
				'playerId'=>$playerId,
			];
			$this->addAdminLog($authCode, json_encode($memo));
		
			dbCommit($db);
			//echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败'.$err]);
			exit;
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		echo json_encode(['err'=>'ok', 'num'=>count($armyIds)]);
		exit;
	}
	
    /**
     * 获取指定时间内防守方（包括去援助）的死兵数量
     * 
     * 
     * @return <type>
     */
	public function ajaxPlayerFixSoldierAction(){
		$authCode = 101;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$beginTime = $_REQUEST['beginTime'];
		$endTime = $_REQUEST['endTime'];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		
		$PlayerMail = new PlayerMail;
		$ret = $PlayerMail->sqlGet('select data from player_mail_info where id in (select mail_info_id from '.$PlayerMail->getSource().' where player_id='.$playerId.' and type in ('.PlayerMail::TYPE_DEFENCECITYWIN.', '.PlayerMail::TYPE_DEFENCECITYLOSE.', '.PlayerMail::TYPE_DEFENCEARMYWIN.', '.PlayerMail::TYPE_DEFENCEARMYLOSE.') and create_time >="'.$beginTime.'" and create_time <= "'.$endTime.'")');

		$num = 0;
		foreach($ret as $_r){
			$_d = json_decode($_r['data'], true);
			if(!in_array($_d['type'], [1, 3])) continue;
			foreach($_d['player1']['players'] as $_player){
				if($_player['player_id'] == $playerId){
					foreach($_player['unit'] as $_k => $_unit){
						if($_k === 'trap'){
						}else{
							$num += $_unit['killed_num'] + $_unit['injure_num'];
						}
					}
					break;
				}
			}
		}
		
		echo json_encode(['err'=>'ok', 'num'=>$num]);
		exit;
	}
	
    /**
     * 修正vip效果
     * 
     * 
     * @return <type>
     */
	public function ajaxVipResetAction(){
		$authCode = 101;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			$Drop = new Drop;
			$PlayerBuffTemp = new PlayerBuffTemp;
			$pbt = $PlayerBuffTemp->findFirst(['player_id='.$playerId.' and buff_id=464']);
			if($pbt){
				$pbt = $pbt->toArray();
				$vipLevel = substr($pbt['buff_temp_id'], 0, 3)*1 - 110;
				if($vipLevel != $player['vip_level']){
					$flag = true;
				}
			}
			
			if(@$flag){
				//删除旧效果
				$PlayerBuffTemp->find(['player_id='.$playerId.' and buff_temp_id >= 11001 and buff_temp_id <= 13000'])->delete();
				$PlayerBuffTemp->_clearDataCache($playerId);
				
				//增加新效果
				$Item = new Item;
				$item = $Item->dicGetOne(23703);//道具id为vip一天
				$addTime = -86400 /*抵消一天*/ +(strtotime($pbt['expire_time'])-time());
				if($addTime >= -86400){
					foreach($item['drop'] as $_drop){
						$ret = $Drop->gain($playerId, $_drop, 1, '', ['second'=>$addTime]);
						if(!$ret)
							throw new Exception('修正失败');
					}
				}
				
				$memo = [
					'desc'=>'修正vip效果',
					'playerId'=>$playerId,
				];
				$this->addAdminLog($authCode, json_encode($memo));
				
			}else{
				throw new Exception('无需修正');
			}
			
			dbCommit($db);
			echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败'.$err]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
    /**
     * 玩家建筑
     * 
     * 
     * @return <type>
     */
	public function playerBuildAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 102;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		$PlayerBuild = new PlayerBuild;
		$pb = $PlayerBuild->getByPlayerId($playerId);
		$pb = Set::sort($pb, '{n}.origin_build_id', 'asc');
		
		$Build = new Build;
		$build = $Build->dicGetAll();
		
		$general = (new General)->getAllByOriginId();
		
		$this->view->setVars(
            array(
                'pb'   => $pb,
				'build' => $build,
				'general'=>$general,
				'playerId'=>$playerId,
            )
        );
	}
	
	/**
     * 一键建筑升级
     * 
     * 
     * @return <type>
     */
	public function ajaxQuickBuildLvupAction(){
		$authCode = 102;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$lv = floor($_REQUEST['lv']);
		if(!is_numeric($lv) || $lv<1 || $lv > 50){
			echo json_encode(['err'=>'请输入1-50']);
			exit;
		}
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player){
			echo json_encode(['err'=>'玩家不存在']);
			exit;
		}
		if(!(new PlayerBuild)->castleLevelCheat($playerId, $lv)){
			echo json_encode(['err'=>'操作失败']);
			exit;
		}
		$memo = [
			'desc'=>'一键建筑20级',
			'playerId'=>$playerId,
			'lv'=>20,
		];
		$this->addAdminLog($authCode, json_encode($memo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
    /**
     * 武将信息
     * 
     * 
     * @return <type>
     */
	public function playerGeneralAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 103;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		$equipment = (new Equipment)->dicGetAll();
		
		$PlayerGeneral = new PlayerGeneral;
		$pg = $PlayerGeneral->getByPlayerId($playerId);
		
		//$general = (new General)->dicGetAll();
		$General = new General;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerArmy = new PlayerArmy;
		foreach($pg as &$_pg){
			$_pg['general'] = $General->getByGeneralId($_pg['general_id']);
			$_pau = $PlayerArmyUnit->getByGeneralId($playerId, $_pg['general_id']);
			if($_pau){
				$_pg['army'] = $PlayerArmy->getByArmyId($playerId, $_pau['army_id']);
			}else{
				$_pg['army'] = false;
			}
		}
		unset($_pg);
		
		//信物
		$item = (new Item)->dicGetAll();
		$PlayerItem = new PlayerItem;
		$piece = $PlayerItem->find(['player_id='.$playerId.' and item_id >= 40000 and item_id < 50000'])->toArray();
		
		//城战技能
		$battleSkill = (new BattleSkill)->dicGetAll();
		$battleSkill[0]['skill_name1'] = '无';
		
		$this->view->setVars(
            array(
                'pg'   => $pg,
				'equipment' => $equipment,
				'item'=>$item,
				'piece'=>$piece,
				'battleSkill'=>$battleSkill,
            )
        );
	}
	
    /**
     * 玩家道具
     * 
     * 
     * @return <type>
     */
	public function playerItemAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 104;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		//获取背包
		$PlayerItem = new PlayerItem;
		$pi = $PlayerItem->getByPlayerId($playerId);
		
		$item = (new Item)->dicGetAll();
		
		//获取主公装备
		$PlayerEquipMaster = new PlayerEquipMaster;
		$pem = $PlayerEquipMaster->getByPlayerId($playerId);
		
		$equipMaster = (new EquipMaster)->dicGetAll();
		
		//获取武将装备背包
		$PlayerEquipment = new PlayerEquipment;
		$pe = $PlayerEquipment->getByPlayerId($playerId);
		$pe_ = [];
		foreach($pe as $_pe){
			$pe_[$_pe['item_id']]++;
		}
		$pe = $pe_;
		
		$equipment = (new Equipment)->dicGetAll();
		
		$this->view->setVars([
			'pi'=>$pi,
			'item'=>$item,
			'pem'=>$pem,
			'equipMaster'=>$equipMaster,
			'pe'=>$pe,
			'equipment'=>$equipment,
		]);
	}
	
    /**
     * 武将军团
     * 
     * 
     * @return <type>
     */
	public function playerArmyAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 105;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		//获取最大军团数
		$maxArmyNum = (new Player)->getMaxArmyNum($playerId);
		
		//获取军团
		$pa = (new PlayerArmy)->getByPlayerId($playerId);
		$pa = Set::sort($pa, '{n}.position', 'asc');
		
		//获取军团单位
		foreach($pa as &$_pa){
			$_unit = (new PlayerArmyUnit)->getByArmyId($playerId, $_pa['id']);
			$_unit = Set::sort($_unit, '{n}.unit', 'asc');
			$_pa['unit'] = $_unit;
		}
		unset($_pa);
		
		//获取散兵
		$ps = (new PlayerSoldier)->getByPlayerId($playerId);
		$ps = Set::sort($ps, '{n}.soldier_id', 'asc');
		
		//伤兵
		$psi = (new PlayerSoldierInjured)->getByPlayerId($playerId);
		$psi = Set::sort($psi, '{n}.soldier_id', 'asc');
		
		$general = (new General)->getAllByOriginId();
		$soldier = (new Soldier)->dicGetAll();
		
		$this->view->setVars([
			'playerId'=> $playerId,
			'maxArmyNum'=>$maxArmyNum,
			'pa'=>$pa,
			'ps'=>$ps,
			'general'=>$general,
			'soldier'=>$soldier,
			'psi'=>$psi,
		]);
	}
	
    /**
     * 玩家增益
     * 
     * 
     * @return <type>
     */
	public function playerBuffAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 106;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		$buff = (new Buff)->dicGetAll();
		$buffn = [];
		foreach($buff as $_b){
			$buffn[$_b['name']] = $_b;
		}
		
		$pb = (new PlayerController)->getBuff($playerId);
		$pbt = [];
		foreach($pb as $_k => $_pb){
			if(!isset($buffn[$_k])) continue;
			if($buffn[$_k]['buff_type'] == 1){
				$pbt[$_k] = floatval($_pb['v']/DIC_DATA_DIVISOR)*100;
			}else{
				$pbt[$_k] = $_pb['v'];
			}
		}
		
		//获取所有武将驻守的建筑
		$PlayerBuild = new PlayerBuild;
		$pbd = $PlayerBuild->find(['player_id='.$playerId.' and general_id_1>0'])->toArray();
		$positions = Set::extract('/position', $pbd);
		$exception = ['infantry_carry_plus', 'cavalry_carry_plus', 'archer_carry_plus', 'siege_carry_plus'];
		foreach($positions as $_pos){
			$generalBuff = $PlayerBuild->calcGeneralBuff($playerId, $_pos);
			
			foreach($generalBuff['general'] as $_k => $_v){
				if(in_array($_k, $exception)) continue;
				$pbt[$_k] += $_v;
			}
			foreach($generalBuff['equip'] as $_k => $_v){
				if(in_array($_k, $exception)) continue;
				$pbt[$_k] += $_v;
			}
		}
		//ksort($pbt);
		
		$this->view->setVars([
			'pbt' => $pbt,
			'buffn'=>$buffn,
		]);
	}
	
    /**
     * 研究所
     * 
     * 
     * @return <type>
     */
	public function playerScienceAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 107;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		$ps = (new PlayerScience)->getByPlayerId($playerId);
		$science = (new Science)->dicGetAll();
		
		$this->view->setVars([
			'ps' => $ps,
			'science'=>$science,
		]);
	}
	
	/**
     * 天赋
     * 
     * 
     * @return <type>
     */
	public function playerTalentAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 108;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		$ps = (new PlayerTalent)->getByPlayerId($playerId);
		$talent = (new Talent)->dicGetAll();
		
		$this->view->setVars([
			'ps' => $ps,
			'talent'=>$talent,
		]);
	}
	
    /**
     * 充值订单
     * 
     * 
     * @return <type>
     */
	public function playerOrderAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 109;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(false === $playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		if(!$playerId){
			$playerId = 0;
		}
		
		$this->view->setVars([
			'playerId' => $playerId,
		]);
	}
	
	public function ajaxPlayerOrderAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 109;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$beginTime = $_REQUEST['beginTime'];
		$endTime = $_REQUEST['endTime'];
		$status = $_REQUEST['status'];
		$where = [];
		if($playerId){
			$where[] = 'player_id='.$playerId;
		}
		if($beginTime){
			$where[] = 'create_time >="'.$beginTime.'"';
		}
		if($endTime){
			$where[] = 'create_time <="'.$endTime.'"';
		}
		if($status!=-1){
			$where[] = 'status='.$status;
		}
		$where = join(' and ', $where);
		/*if($playerId){
			$where = 'player_id='.$playerId;
		}else{
			$where = '';
		}*/
		
		$PlayerOrder = new PlayerOrder;
		$data = $this->dataTableGet($PlayerOrder, $where);
		
		foreach($data['data'] as &$_d){
			$_p = (new Pricing)->getByPaymentCode($_d['payment_code']);
			$_d['price'] = $_p['type'] .' '. $_d['price'];
		}
		unset($_d);
		
		echo json_encode($data);
		exit;
	}
	
	/**
     * 玩家邮件
     * 
     * 
     * @return <type>
     */
	public function playerMailAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 110;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(false === $playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		if(!$playerId){
			$playerId = 0;
		}
		
		$this->view->setVars([
			'playerId' => $playerId,
			'types' => (new PlayerMail)->typeDesc,
		]);
	}
	
	public function ajaxPlayerMailAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 110;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$beginTime = $_REQUEST['beginTime'];
		$endTime = $_REQUEST['endTime'];
		$type = $_REQUEST['type'];
		$status = $_REQUEST['status'];
		$where = [];
		if($playerId){
			$where[] = 'player_id='.$playerId;
		}
		if($beginTime){
			$where[] = 'create_time >="'.$beginTime.'"';
		}
		if($endTime){
			$where[] = 'create_time <="'.$endTime.'"';
		}
		if($type){
			$where[] = 'type='.$type;
		}
		if($status!=''){
			$where[] = 'status='.$status;
		}
		$where = join(' and ', $where);
		$PlayerMail = new PlayerMail;
		$data = $this->dataTableGet($PlayerMail, $where);
		
		$PlayerMailInfo = new PlayerMailInfo;
		$PlayerMailGroup = new PlayerMailGroup;
		$Player = new Player;
		$types = $PlayerMail->typeDesc;
		foreach($data['data'] as &$_d){
			$_d['from_player'] = '';
			$_d['title'] = '';
			$_d['msg'] = '';
			$_d['data'] = '';
			$_d['item'] = '';
			$_d['operation'] = '';
			$pmi = $PlayerMailInfo->findFirst($_d['mail_info_id']);
			if($pmi){
				$_d['title'] = $pmi->title;
				$_d['msg'] = $pmi->msg;
				$_d['data'] = $pmi->data;
				$_d['item'] = $pmi->item;
				if($_d['type'] == PlayerMail::TYPE_CHATSINGLE){
					$_d['from_player'] = '<a href="javascript:linkPlayer(1, '.$pmi->from_player_id.', 1)">'.$pmi->from_player_name.'</a>';
				}elseif($_d['type'] == PlayerMail::TYPE_CHATGROUP){
					$_d['from_player'] = '<a href="javascript:showGroup('.$_d['id'].')">[组]('.$pmi->from_player_name.')</a>';
					$groupMembers = $PlayerMailGroup->getGroup($_d['connect_id']);
					$str = [];
					foreach($groupMembers as $_m){
						$_player = $Player->getByPlayerId($_m);
						$str[] = '<a href="javascript:linkPlayer(1, '.$_m.', 1)">'.$_player['nick'].'</a>';
					}
					$_d['from_player'] .= '<div class="chatGroup_'.$_d['id'].'" style="display:none">'.join('<br>', $str).'</div>';
				}
				$c = $this->getMailContent($_d['type'], json_decode($_d['data'], true));
				if($c['title'])
					$_d['title'] = $c['title'];
				if($c['msg'])
					$_d['msg'] = $c['msg'];
			}
			$_d['type'] .= ' - '.$types[$_d['type']];
			if($_d['status'] == -1){
				$_d['operation'] = '<button class="btn btn-danger btn-xs" type="button" onclick="changeStatus('.$_d['player_id'].','.$_d['id'].', 0)">恢复</button>';
			}else{
				$_d['operation'] = '<button class="btn btn-danger btn-xs" type="button" onclick="changeStatus('.$_d['player_id'].','.$_d['id'].', -1)">删除</button>';
			}
			$_d['operation'] .= ' <button class="btn btn-primary btn-xs" type="button" onclick="showDetail(this)">详情</button>';
		}
		unset($_d);
		
		echo json_encode($data);
		exit;
	}
	
	public function ajaxPlayerMailChangeStatusAction(){
		$this->view->setVar("treeact",'player');
		$authCode = 110;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = @$_REQUEST['playerId'];
		$id = @$_REQUEST['id'];
		$status = @$_REQUEST['status'];
		if(!$playerId || !$id || !in_array($status, [-1, 0])){
			echo json_encode(['err'=>'参数错误']);
			exit;
		}
		
		if($status == -1){
			(new PlayerMail)->updateStatusByMailId($playerId, $id, $status);
		}else{
			$condition = array('id'=>$id, 'player_id'=>$playerId);
			$condition['status'] = -1;
			(new PlayerMail)->updateAll(array('status'=>$status, 'update_time'=>'"'.date('Y-m-d H:i:s').'"', 'rowversion'=>'"'.uniqid().'"'), $condition);
		}
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function getMailContent($type, $param){
		//var_dump($param);
		$ret = ['title'=>'', 'msg'=>''];
		switch($type){
			case PlayerMail::TYPE_DETECT:
				if($param['type'] == 1){
					$ret['title'] = '侦察主城';
				}elseif($param['type'] == 2){
					$ret['title'] = '侦察联盟堡垒';
				}elseif($param['type'] == 3){
					$ret['title'] = '侦察资源';
				}
			break;
			case PlayerMail::TYPE_DETECTED:
				$ret['title'] = '侦察主城';
				$ret['msg'] = $param['target_player']['nick']."侦察了你的城堡";
			break;
			case PlayerMail::TYPE_GUILDINVITE:
				$ret['title'] = '邀请玩家';
				$ret['msg'] = "联盟“".$param['from_guild']['name']."”邀请你加入";
			break;
			case PlayerMail::TYPE_GUILDAPPROVAL:
				$ret['title'] = '来自“'.$param['from_guild']['name'].'”联盟的拒绝通知';
				$ret['msg'] = '“'.$param['from_guild']['name']."”的“".$param['from_player']['nick']."”拒绝了你的申请";
			break;
			case PlayerMail::TYPE_GUILDQUIT:
				$ret['title'] = '来自联盟“'.$param['from_guild']['name'].'”的移出通知';
				$ret['msg'] = '你被联盟“'.$param['from_guild']['name'].'”的“'.$param['from_player']['nick'].'”移出了联盟';
			break;
			case PlayerMail::TYPE_GUILDGATHER:
				$ret['title'] = $param['from_player_name'].'发起集结';
				if($param['target_info']['type'] == 'attackBoss'){
					$MapElement = new MapElement;
					$me = $MapElement->dicGetOne($param['target_info']['element_id']);
					$info = $me['level'].'级'.$me['desc1'].'(x:'.$param['target_info']['to_x'].',y:'.$param['target_info']['to_y'].')';
				}elseif($param['target_info']['type'] == 'attackBase'){
					$info = $param['target_info']['guild_name'].'的堡垒(x:'.$param['target_info']['to_x'].',y:'.$param['target_info']['to_y'].')';
				}elseif($param['target_info']['type'] == 'attackTown'){
					$MapElement = new MapElement;
					$me = $MapElement->dicGetOne($param['target_info']['element_id']);
					$info = $me['desc1'].'(x:'.$param['target_info']['to_x'].',y:'.$param['target_info']['to_y'].')';
				}elseif($param['target_info']['type'] == 'attackPlayer'){
					$info = $param['target_info']['guild_name'].'的“'.$param['target_info']['nick'].'”(x:'.$param['target_info']['to_x'].',y:'.$param['target_info']['to_y'].')';
				}
				$ret['msg'] = '“'.$param['from_player_name'].'”向'.$info.'发起集结';
			break;
			case PlayerMail::TYPE_GUILDAUTHCHG:
				if($param['changeFlag']){
					$ret['title'] = '来自联盟“'.$param['from_guild']['name'].'”的晋升通知';
					$ret['msg'] = '你被“'.$param['from_player']['nick'].'”晋升为R'.$param['to_rank'];
				}else{
					$ret['title'] = '来自联盟“'.$param['from_guild']['name'].'”的降级通知';
					$ret['msg'] = '你被“'.$param['from_player']['nick'].'”降级为R'.$param['to_rank'];
				}
			break;
			case PlayerMail::TYPE_GUILDINVITEMOVE:
				$ret['title'] = '发件人:'.$param['from_player']['nick'];
				$ret['msg'] = '“'.$param['from_player']['nick'].'”邀请您迁城至(x:'.$param['x'].', y:'.$param['y'].')';
			break;
			case PlayerMail::TYPE_ATTACKBASEWARN:
				$ret['msg'] = '“'.$param['playerNick'].'”正在攻击我方联盟堡垒';
			break;
			case PlayerMail::TYPE_SPYBASEWARN:
				$ret['msg'] = '“'.$param['playerNick'].'”正在侦察我方联盟堡垒';
			break;
			case PlayerMail::TYPE_KINGGIFT:
				$ret['title'] = '国王礼包';
				$ret['msg'] = '鉴于你在国王战中的英勇表现，“'.$param['kingName'].'”赠送一个国王礼包以表敬意。';
			break;
			case PlayerMail::TYPE_LIMITRANKGIFT:
				$ret['title'] = '限时比赛排名礼包';
				$ret['msg'] = '恭喜主公在当前限时比赛中排名第'.$param['rank'].'位，获得如下奖励';
			break;
			case PlayerMail::TYPE_LIMITTOTALRANKGIFT:
				$ret['title'] = '限时比赛总排名礼包';
				$ret['msg'] = '恭喜主公在限时比赛中总排名第'.$param['rank'].'位，获得如下奖励';
			break;
			case PlayerMail::TYPE_FAIL_SAVE:
				$ret['title'] = '战斗力损耗补偿';
				$ret['msg'] = '主公，胜败乃兵家常事，不要气馁，继续加油!';
			break;
			case PlayerMail::TYPE_GUILDMISSIONRANKGIFT:
				$ret['title'] = '联盟任务排名礼包';
				$ret['msg'] = '恭喜主公所在联盟排名第'.$param['rank'].'位。获得如下奖励';
			break;
			case PlayerMail::TYPE_GUILDMISSIONSCOREGIFT:
				$ret['title'] = '联盟任务积分礼包';
				$ret['msg'] = '由于主公与联盟成员在联盟任务中的英勇表现，积分达到了'.$param['score'].'分，获得如下奖励';
			break;
			case PlayerMail::TYPE_LIMITSCOREGIFT:
				$ret['title'] = '限时比赛阶段礼包';
				$ret['msg'] = '由于主公在限时比赛中的英勇表现，积分达到了'.$param['step_point'].'分，获得如下奖励';
			break;
		}
		return $ret;
	}
	
    /**
     * 发送道具
     * 
     * 
     * @return <type>
     */
	public function playerdoItemAction(){
		$this->view->setVar("treeact",'playerdo');
		if(!$this->checkAuthId(203))
			return;
		
		$Item = new Item;
		$item = $Item->dicGetAll();
		
		$EquipMaster = new EquipMaster;
		$equipMaster = $EquipMaster->dicGetAll();
		
		$Equipment = new Equipment;
		$equipment = $Equipment->dicGetAll();
		
		$this->view->setVars([
			'item' => $item,
			'equipMaster'=>$equipMaster,
			'equipment'=>$equipment,
		]);
	}
	
	public function ajaxPlayerdoItemAction(){
		$authCode = 203;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$type = $_REQUEST['playerToType'];
		$failPlayer = [];
		if($type == 2){
			$userCode = trim($_REQUEST['playerToUserCode']);
			//usercode to playerId
			if($userCode == ''){
				echo json_encode(['err'=>'userCode为空']);
				exit;
			}
			$userCode = explode(',', $userCode);
			if(count($userCode) > 5000){
				echo json_encode(['err'=>'userCode必须小于5000条']);
				exit;
			}
			$userCode = array_map("strtoupper", $userCode);
			$players = (new Player)->find(['user_code in ("'.join('","', $userCode).'")'])->toArray();
			$code = Set::extract('/user_code', $players);
			$toPlayerIds = Set::extract('/id', $players);
			$failPlayer = array_values(array_diff($userCode, $code));
		}else{
			$_playerId = 0;
			$row = 10;
			$toPlayerIds = [];
			$Player = new Player;
			while($_data = $Player->sqlGet('select id from player where id>'.$_playerId.' order by id limit '.$row)){
				$_playerIds = Set::extract('/id', $_data);
				$toPlayerIds = array_merge($toPlayerIds, $_playerIds);
				$_playerId = $_playerIds[count($_playerIds)-1];
			}
		}
		$item = trim($_REQUEST['item']);
		$item = parseGroup($item, false);
		foreach($item as $_d){
			if(count($_d) != 3
			|| !checkRegularNumber($_d[0])
			|| !checkRegularNumber($_d[1])
			|| !checkRegularNumber($_d[2])
			){
				echo json_encode(['err'=>'道具格式错误']);
				exit;
			}
		}
		
		set_time_limit(0);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			//整理道具
			$gainItem = [];
			foreach($item as $_item){
				if(!in_array($_item[0], [2, 4, 7])) continue;
				@$gainItem[$_item[0]][$_item[1]] = $_item[2]*1;
			}
			$Drop = new Drop;
			foreach($toPlayerIds as $_playerId){
				$Drop->except = [];
				$Drop->_gain($_playerId, $gainItem, '管理端发送道具');
			}
			
			$logMemo = [
				'desc'=>'发送道具',
				'playerIds'=>$toPlayerIds,
				'gainItem'=>$gainItem,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'failPlayer'=>$failPlayer]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败'.$err]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
    /**
     * 发送礼包
     */
	public function playerdoSendGiftAction(){
		$this->view->setVar("treeact",'playerdo');
		if(!$this->checkAuthId(207))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		
		$channel = $_REQUEST['channel'];
		if(!$channel){
			$channel = 'googleplay';
		}
		
		$PayWay = new PayWay;
		$payway = $PayWay->dicGetAll();
		$channels = [];
		foreach($payway as $_p){
			$channels = array_merge($channels, $_p['pay_way']);
		}
		$channels = array_unique($channels);
		$channels = Set::sort($channels, '{n}', 'asc');
		
		$ActivityCommodity = new ActivityCommodity;
		$gift = $ActivityCommodity->dicGetAll();
		$gift = Set::sort($gift, '{n}.id', 'asc');
		
		$Pricing = new Pricing;
		foreach($gift as &$_g){
			$p = $Pricing->findFirst(['gift_type='.$_g['gift_type'].' and channel="'.$channel.'"']);
			if($p){
				$_g['price'] = $p->type . $p->price;
			}
		}
		unset($_g);
		
		$this->view->setVars([
			'gift' => $gift,
			'channel' => $channel,
			'channels' => $channels,
			'playerId' => $playerId,
		]);
	}
	
	public function ajaxPlayerdoSendGiftAction(){
		$authCode = 207;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$id = $_REQUEST['id'];
		$channel = $_REQUEST['channel'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			$ActivityCommodity = new ActivityCommodity;
			$ac = $ActivityCommodity->dicGetOne($id);
			if(!$ac){
				throw new Exception('礼包未找到');
			}
			
			$Pricing = new Pricing;
			$pricing = $Pricing->findFirst(['gift_type='.$ac['gift_type'].' and channel="'.$channel.'"']);
			if(!$pricing){
				throw new Exception('pricing未找到');
			}
			$pricing = $pricing->toArray();
			
			$Player = new Player;
			$player = $Player->getByPlayerId($playerId);
			if(!$player){
				throw new Exception('玩家未找到');
			}
			
			$drop = [];
			if($ac['drop_id']){
				$drop[] = $ac['drop_id'];
			}
			
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
				}
			}
			
			//drop
			if($pricing['bonus_drop']){
				$drop[] = $pricing['bonus_drop'];
			}

			if($drop){
				$Drop = new Drop;
				if(!$Drop->gain($playerId, $drop, 1, '后台发送礼包')){
					throw new Exception('增加道具失败');
				}
			}
			
			Cache::delPlayer($playerId, 'buyGiftLists-'.$pricing['channel']);
			
			$logMemo = [
				'desc'=>'发送礼包',
				'playerId'=>$playerId,
				'id'=>$id,
				'channel'=>$channel,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败'.$err]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	
    /**
     * 元宝消耗日志
     * 
     * 
     * @return <type>
     */
	public function logGemConsumeAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 301;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		$this->view->setVar("playerId",$playerId);
		
		$Cost = new Cost;
		$cost = $Cost->dicGetAll();
		$cost = Set::sort($cost, '{n}.id', 'asc');
		$this->view->setVar("cost",$cost);
	}
	
	/**
     * 元宝消耗日志数据
     * 
     * 
     * @return <type>
     */
	public function ajaxLogGemConsumeAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 301;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$beginTime = $_REQUEST['beginTime'];
		$endTime = $_REQUEST['endTime'];
		$type = $_REQUEST['type'];
		//$where = 'player_id='.$playerId;
		$where = [];
		if($playerId){
			$where[] = 'player_id='.$playerId;
		}
		if($beginTime){
			$where[] = 'create_time >="'.$beginTime.'"';
		}
		if($endTime){
			$where[] = 'create_time <="'.$endTime.'"';
		}
		if($type){
			$where[] = 'cost_id='.$type;
		}
		$where = join(' and ', $where);
		$PlayerConsumeLog = new PlayerConsumeLog;
		$data = $this->dataTableGet($PlayerConsumeLog, $where);
		
		$Cost = new Cost;
		$cost = $Cost->dicGetAll();
		foreach($data['data'] as &$_d){
			if($_d['cost_id']){
				$_d['cost_id'] .= '（'.$cost[$_d['cost_id']]['desc1'].'）';
			}
		}
		unset($_d);
		
		echo json_encode($data);
		exit;
	}
	
	/**
     * 元宝获取日志
     * 
     * 
     * @return <type>
     */
	public function logGemGainAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 302;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		$this->view->setVar("playerId",$playerId);
	}
	
	/**
     * 元宝获取日志数据
     * 
     * 
     * @return <type>
     */
	public function ajaxLogGemGainAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 302;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$beginTime = $_REQUEST['beginTime'];
		$endTime = $_REQUEST['endTime'];
		$type = $_REQUEST['type'];
		//$where = 'player_id='.$playerId;
		$where = [];
		if($playerId){
			$where[] = 'player_id='.$playerId;
		}
		if($beginTime){
			$where[] = 'create_time >="'.$beginTime.'"';
		}
		if($endTime){
			$where[] = 'create_time <="'.$endTime.'"';
		}
		if($type){
			$where[] = 'memo like "%'.$type.'%"';
		}
		$where = join(' and ', $where);
		$PlayerGemLog = new PlayerGemLog;
		$data = $this->dataTableGet($PlayerGemLog, $where);
		
		$Drop = new Drop;
		foreach($data['data'] as &$_d){
			if(substr($_d['memo'], 0, strlen('fromDrop:')) == 'fromDrop:'){
				if(preg_match('/^fromDrop:\[(\d+)\]/',$_d['memo'],$v)){
					$_drop = $v[1];
					$drop = $Drop->dicGetOne($_drop);
					$_d['memo'] .= '<div class="alert alert-info"><i class="fa fa-bullhorn fa-fw"></i>'.$drop['desc1'].'</div>';
				}
			}
		}
		unset($_d);
				
		echo json_encode($data);
		exit;
	}
	
	/**
     * 联盟商店日志
     * 
     * 
     * @return <type>
     */
	public function logGuildShopAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 305;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
        $logType = isset($_REQUEST['logType']) ? $_REQUEST['logType'] : '';
		if(false === $playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		$this->view->setVar("playerId",$playerId);
		$this->view->setVar("logType", $logType);
	}
	
	/**
     * 联盟商店日志数据
     * 
     * 
     * @return <type>
     */
	public function ajaxLogGuildShopAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 305;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$beginTime = $_REQUEST['beginTime'];
		$endTime = $_REQUEST['endTime'];
		$type = $_REQUEST['type'];
		$where = [];
		if($playerId){
			$where[] = 'player_id='.$playerId;
		}
		if($beginTime){
			$where[] = 'create_time>="'.$beginTime.'"';
		}
		if($endTime){
			$where[] = 'create_time<="'.$endTime.'"';
		}
		if($type){
			$where[] = 'type="'.$type.'"';
		}
		$where = join(' and ', $where);
		$GuildShopLog = new GuildShopLog;
		$data = $this->dataTableGet($GuildShopLog, $where);
		$Item = new Item;
		foreach($data['data'] as &$_d){
			$_d['item_id'] .= '('.$Item->dicGetOne($_d['item_id'])['desc1'].')';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	/**
     * 玩家常规日志
     * 
     * 
     * @return <type>
     */
	public function logCommonAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 303;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
        $logType = isset($_REQUEST['logType']) ? $_REQUEST['logType'] : '';
		if(false === $playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		$this->view->setVar("playerId",$playerId);
		$this->view->setVar("logType", $logType);
	}
	
	/**
     * 玩家常规日志数据
     * 
     * 
     * @return <type>
     */
	public function ajaxLogCommonAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 303;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = $_REQUEST['playerId'];
		$beginTime = $_REQUEST['beginTime'];
		$endTime = $_REQUEST['endTime'];
		$type = $_REQUEST['type'];
		$where = [];
		if($playerId){
			//$where['player_id'] = $playerId*1;
			$where[] = 'player_id='.$playerId;
		}
		if($beginTime){
			//$where['create_time'] = ['$gte'=>$beginTime];
			$where[] = 'create_time>="'.$beginTime.'"';
		}
		if($endTime){
			//$where['create_time'] = ['$lte'=>$endTime];
			$where[] = 'create_time<="'.$endTime.'"';
		}
		if($type){
			//$where['type'] = new MongoRegex('/'.$type.'/');
			$where[] = 'type like "%'.$type.'%"';
		}
		$where = join(' and ', $where);
		$PlayerCommonLog = new PlayerCommonLog;
		//$data = $this->dataCollectionGet($PlayerCommonLog, $where);
		$data = $this->dataTableGet($PlayerCommonLog, $where);
		foreach($data['data'] as &$_d){
			/*$__d = $_d;
			unset($__d['id']);
			unset($__d['player_id']);
			unset($__d['create_time']);
			$_d = [
				'id'=>$_d['id'],
				'player_id'=>$_d['player_id'],
				'create_time'=>$_d['create_time'],
				'memo'=>json_encode($__d),
			];*/
			$_memo = json_decode($_d['memo'], true);
			$_d['memo'] = '<div class="alert alert-info extrainfoBlk" title="点击有惊喜"><i class="fa fa-bullhorn fa-fw"></i><span class="extrainfo">'.dump($_memo, true, true).'</span></div>';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	/**
	 * 监控假量
	 *
	 *
	 * @return <type>
	 */
	public function logPlayerOnlineAction(){
	    $this->view->setVar("treeact",'log');
	    $authCode = 306;
	    if(!$this->checkAuthId($authCode))
	        return;
	        $playerId = $this->getPlayerId();
	        $logType = isset($_REQUEST['logType']) ? $_REQUEST['logType'] : '';
	        if(false === $playerId){
	            $this->view->setVar("errmsg",'玩家不存在');
	            return;
	        }
	        $this->view->setVar("playerId",$playerId);
	        $this->view->setVar("logType", $logType);
	}
	
	/**
	 * 监控假量
	 *
	 * @return <type>
	 */
	public function ajaxlogPlayerOnlineAction(){
	    $this->view->setVar("treeact",'log');
	    $authCode = 306;
	    if(!$this->checkAuthId($authCode, 2))
	        exit;
	        //$beginTime = isset($_REQUEST['beginTime'])? $_REQUEST['beginTime']:'';
	        //$endTime = isset($_REQUEST['endTime'])?$_REQUEST['endTime']:'';
	        $onlineTime = isset($_REQUEST['onlineTime'])? intval($_REQUEST['onlineTime'])*60 : 0;
	        $condition_online = ($_REQUEST['condition_online'])==1? ">":'<=';
	        $exp = isset($_REQUEST['exp'])? intval($_REQUEST['exp']) :0;
	        $condition_exp = ($_REQUEST['condition_exp'])==1? ">":'<=';
	        $level = isset($_REQUEST['level'])? intval($_REQUEST['level']) : 0;
	        $condition_level =($_REQUEST['condition_level'])==1? ">":'<=';
	        $regDays = isset($_REQUEST['regDays'])? intval($_REQUEST['regDays']) : 0;
	        $condition_reg = ($_REQUEST['condition_reg'])==1? ">":'<=';

	        
	        $sql = 'select a.player_id, AVG(a.online) as avgline,AVG(a.day_exp) as avgexp ,b.level,DATEDIFF(b.login_time, b.create_time) as reglogin  from `player_online` as a left join `player` as b on a.player_id=b.id where ';	        
	        $where = [];
	        /*if($beginTime){
	            $where[] = 'date >="'.$beginTime.'"';
	        }
	        if($endTime){
	            $where[] = 'date <="'.$endTime.'"';
	        }*/

	        if($level){
	            $where[] = ' b.level '.$condition_level.$level;
	        }
	        
	        if(!empty($where)){
	            $where = join(' and ', $where);
	            $sql .= $where." and";
	        }


	        $start = isset($_REQUEST['start'])? $_REQUEST['start'] : 0;
	        $length = isset($_REQUEST['length'])? $_REQUEST['length'] : 25;
	        $date = date("Y-m-d");
	        $sql .= ' DATEDIFF(b.login_time, b.create_time) '.$condition_reg.$regDays.' and a.date != "'.$date.'" group by a.player_id having AVG(a.online)'.$condition_online.$onlineTime.' and AVG(a.day_exp)'.$condition_exp.$exp.' order by a.player_id desc';

	        $playerOnline = new PlayerOnline();
	        $recordsAllData = $playerOnline->sqlGet($sql);
	        $recordsFiltered = count($recordsAllData);
	        
	        $sql .=' limit '.$start.','.$length;	        
    		$data = $playerOnline->sqlGet($sql);
    		$recordsTotal = count($data);
    		$PlayerInfo = new PlayerInfo();
    		foreach($data as &$edata){
    		    $playerDetail = $PlayerInfo->getByPlayerId($edata['player_id']);
    		    $edata['channel'] = $playerDetail['login_channel'];
    		    $edata['avgline'] = ceil($edata['avgline']/60);
    		    $edata['avgexp'] = $edata['avgexp'];
    		    $edata['level'] = $edata['level'];
    		    $edata['reglogin'] = $edata['reglogin'];
    		}
    		
    		$return = array();
    		$return['draw'] = $_REQUEST['draw'];
    		$return['recordsTotal'] = $recordsTotal;
    		$return['recordsFiltered'] = $recordsFiltered;
    		$return['data'] = $data;
	
	        echo json_encode($return);
	        exit;
	}
	
	/**
     * 管理端日志
     * 
     * 
     * @return <type>
     */
	public function logAdminAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 304;
		if(!$this->checkAuthId($authCode))
			return;
		$AdminUser = new AdminUser;
		$adminUser = $AdminUser->find()->toArray();
		$this->view->setVar("adminUser",$adminUser);
		$this->view->setVar("authType",$this->authType);
	}
	
	/**
     * 管理端日志数据
     * 
     * 
     * @return <type>
     */
	public function ajaxLogAdminAction(){
		$this->view->setVar("treeact",'log');
		$authCode = 304;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$where = [];
		if(@$_REQUEST['adminName']){
			$adminName = $_REQUEST['adminName'];
			$where[] = 'name="'.$adminName.'"';
		}
		if(@$_REQUEST['adminType']){
			$type = $_REQUEST['adminType'];
			$where[] = 'type="'.$type.'"';
		}
		$where = join(' and ', $where);
		$AdminLog = new AdminLog;
		$data = $this->dataTableGet($AdminLog, $where);
		
		foreach($data['data'] as &$_d){
			$_memo = json_decode($_d['memo'], true);
			$_d['memo'] .= '<div class="alert alert-info extrainfoBlk" title="点击有惊喜"><i class="fa fa-bullhorn fa-fw"></i><span class="extrainfo">'.var_export($_memo, true).'</span></div>';
		}
		unset($_d);
						
		echo json_encode($data);
		exit;
	}
	
    /**
     * 发送 全体/指定 邮件
     * 
     * 
     * @return <type>
     */
	public function sendAllMailAction(){
		$this->view->setVar("treeact",'mail');
		if(!$this->checkAuthId(401))
			return;
		
		$this->getDropCreateVar();
	}

	public function ajaxSendAllMailAction(){
		$authCode = 401;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$type = $_REQUEST['sendAllMailToType'];
		$failPlayer = [];
		if($type == 2){
			$userCode = trim($_REQUEST['sendAllMailUserCode']);
			//usercode to playerId
			if($userCode == ''){
				echo json_encode(['err'=>'userCode为空']);
				exit;
			}
			$userCode = explode(',', $userCode);
			if(count($userCode) > 5000){
				echo json_encode(['err'=>'userCode必须小于5000条']);
				exit;
			}
			$userCode = array_map("strtoupper", $userCode);
			$players = (new Player)->find(['user_code in ("'.join('","', $userCode).'")'])->toArray();
			$code = Set::extract('/user_code', $players);
			$toPlayerIds = Set::extract('/id', $players);
			$failPlayer = array_values(array_diff($userCode, $code));
		}else{
			$toPlayerIds = 0;
		}
		$msg = trim($_REQUEST['sendAllMailMsg']);
		$drop = trim($_REQUEST['sendAllMailDrop']);
		$drop = parseGroup($drop, false);
		foreach($drop as $_d){
			if(count($_d) != 3
			|| !checkRegularNumber($_d[0])
			|| !checkRegularNumber($_d[1])
			|| !checkRegularNumber($_d[2])
			){
				echo json_encode(['err'=>'道具格式错误']);
				exit;
			}
		}
		
		set_time_limit(0);
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			(new PlayerMail)->sendSystem($toPlayerIds, PlayerMail::TYPE_SYSTEM, '系統郵件', $msg, 0, [], $drop);
			
			$logMemo = [
				'desc'=>'发送邮件',
				'msg'=>$msg,
				'playerIds'=>$toPlayerIds,
				'drop'=>$drop,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'failPlayer'=>$failPlayer]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败']);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}

	/**
     * 联盟信息
     * 
     * 
     * @return <type>
     */
	public function guildInfoAction(){
		$this->view->setVar("treeact",'guild');
		$authCode = 501;
		if(!$this->checkAuthId($authCode))
			return;
		
		if(@$_REQUEST['guildId']){
			$guildId = $_REQUEST['guildId'];
			$Guild = new Guild;
			$PlayerGuild = new PlayerGuild;
			$guild = $Guild->getGuildInfo($guildId);
			$_player = (new Player)->getByPlayerId($guild['leader_player_id']);
			$guild['leader_player_name'] = $_player['nick'];
			$players = $PlayerGuild->getAllGuildMember($guildId);
			$players = Set::sort($players, '{n}.rank', 'desc');
			
			if($guild['camp_id']){
				$campName = (new CountryCampList)->dicGetOne($guild['camp_id'])['desc'];
			}else{
				$campName = '';
			}
			
			//获取所有联盟建筑坐标
			$Map = new Map;
			$map = $Map->find(['guild_id='.$guildId.' and map_element_origin_id in (1, 2, 3, 4, 5, 6, 7, 8)', 'order'=>'map_element_origin_id asc'])->toArray();
			$builds = [];
			foreach($map as $_m){
				$builds[] = [
					'name'=>(new MapElement)->dicGetOne($_m['map_element_id'])['desc1'],
					'x'=>$_m['x'],
					'y'=>$_m['y'],
					'status'=>[0=>'（建造中）', 1=>''][$_m['status']],
				];
			}
			
			/*$members = [];
			foreach($players as $_k => $_p){
				$members[] = $_k . '('.$_p['Player']['nick'].')';
			}*/
			$this->view->setVars([
				'guild'=>$guild,
				'members'=>$players,
				'builds'=>$builds,
				'campName'=>$campName,
			]);
		}
	}
	
	/**
     * 管理端日志数据
     * 
     * 
     * @return <type>
     */
	public function ajaxGuildInfoAction(){
		$this->view->setVar("treeact",'guild');
		$authCode = 501;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$Guild = new Guild;
		$data = $this->dataTableGet($Guild);
		
		$Player = new Player;
		$PlayerGuild = new PlayerGuild;
		foreach($data['data'] as &$_d){
			//盟主
			$_player = $Player->getByPlayerId($_d['leader_player_id']);
			//$_d['leader_player_id'] .= '('.$_player['nick'].')';
			$_d['leader_player_id'] = '<a href="javascript:linkPlayer(1, '.$_d["leader_player_id"].', 1)">'.$_d['leader_player_id'].'('.$_player['nick'].')</a>';
			$_d['button'] = '<button class="btn btn-primary btn-xs" type="button" onclick="showGuildDetail('.$_d['id'].')">查看详情</button>';
			/*$_d['members'] = [];
			//成员
			$_players = $PlayerGuild->getAllGuildMember($_d['id']);
			//var_dump($_players);
			foreach($_players as $_k => $_p){
				$_d['members'][] = $_k.'('.$_p['Player']['nick'].')';
			}
			$_d['members'] = join('<Br>', $_d['members']);*/
		}
		unset($_d);
						
		echo json_encode($data);
		exit;
	}
	/**
	 * 写公告
	 */
	public function addNoticeAction(){
		$this->view->setVar("treeact",'notice');
		$authCode = 601;
		if(!$this->checkAuthId($authCode))
			return;
	}
	/**
	 * 修改公告
	 */
	public function modifyNoticeAction($noticeId=0){
		$this->view->setVar("treeact",'notice');
		$authCode = 601;
		if(!$this->checkAuthId($authCode))
			return;
		if($noticeId) {
			$notice = Notice::findFirst($noticeId);
			if($notice)
				$this->view->notice = $notice->toArray();
		}
	}
	
	public function ajaxDoAddNoticeAction(){
		$this->view->setVar("treeact",'guild');
		$authCode = 601;
		if(!$this->checkAuthId($authCode, 2))
			exit('权限不足');

		$Notice             = new Notice;
        $data['title']      = $_POST['title'];
        $data['content']    = $_POST['content'];
        $data['begin_time'] = $_POST['begin_time'];
        $data['end_time']   = $_POST['end_time'];
        $data['channel']    = trim($_POST['channel']);
		$Notice->addNew($data);
		echo 'ok';
		exit;
	}
	public function ajaxDoEditNoticeAction(){
		$this->view->setVar("treeact",'guild');
		$authCode = 601;
		if(!$this->checkAuthId($authCode, 2))
			exit('权限不足');
		$notice = Notice::findFirst($_POST['id']);
		if($notice) {
			$notice->title      = $_POST['title'];
			$notice->content    = $_POST['content'];
			$notice->begin_time = $_POST['begin_time'];
			$notice->end_time   = $_POST['end_time'];
            $notice->channel    = $_POST['channel'];
			$notice->save();
		}
		echo 'ok';
		exit;
	}
	public function ajaxDeleteNoticeAction(){
		$this->view->setVar("treeact",'guild');
		$authCode = 602;
		if(!$this->checkAuthId($authCode, 2))
			exit('权限不足');

		$id = $_POST['id'];
		$Notice = new Notice;
		$Notice->del($id);
		echo "删除成功";
		exit;
		
	}
	public function ajaxSetNoticeNewAction(){
		$this->view->setVar("treeact",'guild');
		$authCode = 602;
		if(!$this->checkAuthId($authCode, 2))
			exit('权限不足');

		$id        = $_POST['id'];
		$Notice    = new Notice;
		$n         = $Notice->findFirst($id);
		$n->is_new = 1;
		$n->save();
		exit;
	}
	public function ajaxSetNoticeNotNewAction(){
		$this->view->setVar("treeact",'guild');
		$authCode = 602;
		if(!$this->checkAuthId($authCode, 2))
			exit('权限不足');

		$id        = $_POST['id'];
		$Notice    = new Notice;
		$n         = $Notice->findFirst($id);
		$n->is_new = 0;
		$n->save();
		exit;
	}
	/**
	 * 看公告
	 */
	public function viewNoticeAction(){
		$this->view->setVar("treeact",'notice');
		$authCode = 602;
		if(!$this->checkAuthId($authCode))
			return;
		$data = (new Notice)->getAll(true);
		$this->view->setVar("data",$data);
	}

	/**
     * 发送 全体/指定 邮件
     * 
     * 
     * @return <type>
     */
	public function sendRoundMsgAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(603))
			return;
		
	}

	public function ajaxSendRoundMsgAction(){
		$authCode = 603;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		
		$msg = trim($_REQUEST['msg']);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			(new RoundMessage)->addNew(0, ['type'=>0, 'gm_notice'=>$msg]);
			
			$logMemo = [
				'desc'=>'发送走马灯公告',
				'msg'=>$msg,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败']);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
    /**
     * 队列脚本控制
     * 
     * 
     * @return <type>
     */
	public function dispOperationAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(605))
			return;
		$DispatcherTask = new DispatcherTask;
		$this->view->setVar("method",$DispatcherTask->methodArr);
	}
	
	public function ajaxDispOperationAction(){
		$authCode = 605;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		
		$method = trim($_REQUEST['method']);
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			$DispatcherTask = new DispatcherTask;
			$methodArr = array_keys($DispatcherTask->methodArr);
			$methods = [];
			if(!$method){
				$methods = $methodArr;
			}else{
				if(!in_array($method, $methodArr)){
					echo json_encode(['err'=>'脚本名不存在']);
					exit;
				}
				$methods[] = $method;
			}
			
			foreach($methods as $_m){
				Cache::db()->set('restart_'.$_m, 1);
			}
			
			$logMemo = [
				'desc'=>'重启disp脚本（'.$method.'）',
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败']);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	public function ajaxDispLogAction(){
		$authCode = 605;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$where = [];
		/*$where['player_id'] = 0;
		$where['type'] = '重启Disp';
		$PlayerCommonLog = new PlayerCommonLog;
		$data = $this->dataCollectionGet($PlayerCommonLog, $where);
		foreach($data['data'] as &$_d){
			$_d = [
				'id'=>$_d['id'],
				'memo'=>$_d['type'] .'('. $_d['method'] . ')',
				'create_time'=>$_d['create_time'],
			];
		}*/
		$where[] = 'player_id=0';
		$where[] = 'type = "重启Disp"';
		$where = join(' and ', $where);
		$PlayerCommonLog = new PlayerCommonLog;
		$data = $this->dataTableGet($PlayerCommonLog, $where, true);
		foreach($data['data'] as &$_d){
			$_memo = json_decode($_d['memo'], true);
			$_d = [
				'id'=>$_d['id'],
				'memo'=>$_d['type'] .'('. $_memo['method'] . ')',
				'create_time'=>$_d['create_time'],
			];
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
    /**
     * 激活码生成
     * 
     * 
     * @return <type>
     */
	public function cdkGenerateAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(606))
			return;
		
		//获取渠道
		$PayWay = new PayWay;
		$payWay = $PayWay->dicGetAll();
		$channel = [];
		foreach($payWay as $_p){
			if($_p['channel'] == 'anysdk'){
				foreach($_p['pay_way'] as $__p){
					$channel[$__p] = $__p;
				}
			}else{
				$channel[$_p['channel']] = $_p['channel'];
			}
		}

		//礼包
		$CdkDrop = new CdkDrop;
		$cdkDrop = $CdkDrop->find()->toArray();
		
		//输出
		$this->view->setVars([
			'lang'=>[''=>'不限', 'zhtw'=>'繁体', 'zhcn'=>'简体'],
			'channel'=>array_merge([''=>'不限'], $channel),
			'type'=>['0'=>'通用', '1'=>'非通用'],
			'drop'=>$cdkDrop,
		]);
	}
	
	public function ajaxCdkGenerateAction(){
		$authCode = 606;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$cdkLang = trim($_REQUEST['cdkLang']);
		$cdkChannel = trim($_REQUEST['cdkChannel']);
		$cdkType = trim($_REQUEST['cdkType']);
		$cdkDrop = trim($_REQUEST['cdkDrop']);
		$beginTime = trim($_REQUEST['beginTime']);
		$endTime = trim($_REQUEST['endTime']);
		$cdkPre = trim($_REQUEST['cdkPre']);
		$cdkNum = trim($_REQUEST['cdkNum']);
		$cdkMemo = trim($_REQUEST['cdkMemo']);
		
		//获取渠道
		$PayWay = new PayWay;
		$payWay = $PayWay->dicGetAll();
		$channel = [];
		foreach($payWay as $_p){
			if($_p['channel'] == 'anysdk'){
				foreach($_p['pay_way'] as $__p){
					$channel[$__p] = $__p;
				}
			}else{
				$channel[$_p['channel']] = $_p['channel'];
			}
		}
		
		
		if(!in_array($cdkLang, ['', 'zhtw', 'zhcn'])){
			echo json_encode(['err'=>'语言错误']);
			exit;
		}
		if(!in_array($cdkChannel, array_merge([''], $channel))){
			echo json_encode(['err'=>'渠道错误']);
			exit;
		}
		if(!in_array($cdkType, [0, 1])){
			echo json_encode(['err'=>'类型错误']);
			exit;
		}
		//礼包
		$CdkDrop = new CdkDrop;
		if(!$cdkDrop){
			echo json_encode(['err'=>'礼包错误']);
			exit;
		}
		if(!($cd = $CdkDrop->findFirst($cdkDrop))){
			echo json_encode(['err'=>'礼包不存在']);
			exit;
		}
		if(!$beginTime || !$endTime || !strtotime($beginTime) || !strtotime($endTime)){
			echo json_encode(['err'=>'时间错误']);
			exit;
		}
		if($beginTime >= $endTime){
			echo json_encode(['err'=>'结束时间必须大于开始时间']);
			exit;
		}
		if(!preg_match('/^[a-zA-Z0-9]{2}$/', $cdkPre)){
			echo json_encode(['err'=>'前缀输入错误']);
			exit;
		}
		if(preg_match('/(0|o|O|l|L|i|I)/', $cdkPre)){
			echo json_encode(['err'=>'禁止使用数字0，大小写Ll，大小写Ii，大小写Oo']);
			exit;
		}
		if(!is_numeric($cdkNum) || $cdkNum < 1 || $cdkNum > 10000){
			echo json_encode(['err'=>'一次生成数量为1-10000']);
			exit;
		}
			
		$Cdk = new Cdk;
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $Cdk->getWriteConnection();
		dbBegin($db);
	
		try {
			$i = 0;
			while($i < $cdkNum){
				$cdk = $Cdk->generateCdk($cdkPre);
				try {
					$Cdk->add($cdk, $cdkType, $cdkLang, $cdkChannel, $cd->drop, $beginTime, $endTime, $cdkMemo);
				} catch (Exception $e) {//cdk重复
					continue;
				}
				$i++;
			}
			
			$logMemo = [
				'desc'=>'新增激活码',
				'cdkLang'=>$cdkLang,
				'cdkChannel'=>$cdkChannel,
				'cdkType'=>$cdkType,
				'cdkDrop'=>$cdkDrop,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'cdkPre'=>$cdkPre,
				'cdkNum'=>$cdkNum,
				'cdkMemo'=>$cdkMemo,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败']);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	/**
     * 激活码礼包显示
     * 
     * 
     * @return <type>
     */
	public function cdkDropShowAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(606))
			return;
	}
	
	public function ajaxCdkDropShowAction(){
		$authCode = 606;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$where = [];
		$CdkDrop = new CdkDrop;
		$data = $this->dataTableGet($CdkDrop, $where, true);
		foreach($data['data'] as &$_d){
			$_d['memo'] = str_replace("\n", "<Br>", $_d['memo']);
			$_d['operation'] = '<button class="btn btn-danger btn-xs" type="button" onclick="changeStatus('.$_d['id'].')">删除</button>';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function ajaxCdkDropDeleteAction(){
		$authCode = 606;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$id = $_REQUEST['id'];
		if(!$id){
			echo json_encode(['err'=>'未找到记录']);
			exit;
		}
			
		$CdkDrop = new CdkDrop;
		$cdkDrop = $CdkDrop->findFirst($id);
		if($cdkDrop){
			$cdkDrop->delete();
			echo json_encode(['err'=>'ok']);
		}else{
			echo json_encode(['err'=>'未找到记录']);
		}
		exit;
	}
	
	/**
     * 激活码礼包生成
     * 
     * 
     * @return <type>
     */
	public function cdkDropGenerateAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(606))
			return;
		
		$this->getDropCreateVar();
	}
	
	public function ajaxCdkDropGenerateAction(){
		$authCode = 606;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$cdkDropName = trim($_REQUEST['cdkDropName']);
		$cdkDropMemo = trim($_REQUEST['cdkDropMemo']);
		$cdkDrop = trim($_REQUEST['cdkDrop']);
		$drop = parseGroup($cdkDrop, false);
		foreach($drop as $_d){
			if(count($_d) != 3
			|| !checkRegularNumber($_d[0])
			|| !checkRegularNumber($_d[1])
			|| !checkRegularNumber($_d[2])
			){
				echo json_encode(['err'=>'道具格式错误']);
				exit;
			}
		}
		if(!$cdkDropName){
			echo json_encode(['err'=>'请输入礼包名称']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			$CdkDrop = new CdkDrop;
			$CdkDrop->add($cdkDropName, $cdkDrop, $cdkDropMemo);
			
			$logMemo = [
				'desc'=>'新增激活码礼包',
				'cdkDropName'=>$cdkDropName,
				'cdkDropMemo'=>$cdkDropMemo,
				'cdkDrop'=>$cdkDrop,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败']);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
    /**
     * cdk管理
     * 
     * 
     * @return <type>
     */
	public function cdkSearchAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(607))
			return;
		//获取渠道
		$PayWay = new PayWay;
		$payWay = $PayWay->dicGetAll();
		$channel = [];
		foreach($payWay as $_p){
			if($_p['channel'] == 'anysdk'){
				foreach($_p['pay_way'] as $__p){
					$channel[$__p] = $__p;
				}
			}else{
				$channel[$_p['channel']] = $_p['channel'];
			}
		}
				
		//输出
		$this->view->setVars([
			'lang'=>[''=>'不限', 'zhtw'=>'繁体', 'zhcn'=>'简体'],
			'channel'=>array_merge([''=>'不限'], $channel),
			'type'=>['0'=>'通用', '1'=>'非通用'],
		]);
	}
	
	public function ajaxCdkSearchAction(){
		$authCode = 607;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$cdk = trim($_REQUEST['cdk']);
		$cdkLang = trim($_REQUEST['cdkLang']);
		$cdkChannel = trim($_REQUEST['cdkChannel']);
		$cdkType = trim($_REQUEST['cdkType']);
		$beginTime = trim($_REQUEST['beginTime']);
		$endTime = trim($_REQUEST['endTime']);
		$createBeginTime = trim($_REQUEST['createBeginTime']);
		$createEndTime = trim($_REQUEST['createEndTime']);
		$cdkPre = trim($_REQUEST['cdkPre']);
		$cdkMemo = trim($_REQUEST['cdkMemo']);
		$export = @$_REQUEST['export'];
		
		$where = [];
		if($cdk != ''){
			$where[] = 'cdk="'.$cdk.'"';
		}
		if($cdkLang != -1){
			$where[] = 'lang="'.$cdkLang.'"';
		}
		if($cdkChannel != -1){
			$where[] = 'channel="'.$cdkChannel.'"';
		}
		if($cdkType != -1){
			$where[] = 'type="'.$cdkType.'"';
		}
		if($beginTime){
			$where[] = 'begin_time>="'.$beginTime.'"';
		}
		if($endTime){
			$where[] = 'end_time<="'.$endTime.'"';
		}
		if($createBeginTime){
			$where[] = 'create_time>="'.$createBeginTime.'"';
		}
		if($createEndTime){
			$where[] = 'create_time<="'.$createEndTime.'"';
		}
		if($cdkPre){
			$where[] = 'cdk like "'.$cdkPre.'%"';
		}
		if($cdkMemo){
			$where[] = 'memo like "%'.$cdkMemo.'%"';
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		$Cdk = new Cdk;
		if(!$export){
			$data = $this->dataTableGet($Cdk, $where, true);
			foreach($data['data'] as &$_d){
				$_d['type'] = ['0'=>'通用', '1'=>'非通用'][$_d['type']];
				$_d['status'] = ['0'=>'初始', '1'=>'已使用'][$_d['status']];
				$_d['drop'] = (new Drop)->getTranslateInfo($_d['drop'], true, '<br>').'<span style="display:none">'.$_d['drop'].'</span>';
				
			}
			unset($_d);
			echo json_encode($data);
		}else{
			header("Content-type:text/csv;");
			header("Content-Disposition:attachment;filename=cdk.csv");
			header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
			header('Expires:0');
			header('Pragma:public');
			$sql = 'select * from '.$Cdk->getSource().' where '.$where;
			$id = 0;
			$title = ['编号', '激活码', '类型', '语言', '渠道', '礼包', 'drop', '使用人数', '备注', '状态', '有效开始时间', '有效结束时间', '创建时间', '更新时间'];
			$title = '"'.implode('","', $title).'"' . PHP_EOL;
			echo mb_convert_encoding($title, "cp936", "UTF-8");
			$drops = [];
			
			while($data = $Cdk->sqlGet($sql.' and id>'.$id.' order by id limit 100')){
				$str = '';
				foreach ($data as $row) {
					$str_arr = array();
					if(!isset($drops[$row['drop']])){
						$drops[$row['drop']] = (new Drop)->getTranslateInfo($row['drop'], true, "；");
					}
					$row = [
						$row['id'],
						$row['cdk'],
						['0'=>'通用', '1'=>'非通用'][$row['type']],
						$row['lang'],
						$row['channel'],
						$drops[$row['drop']],
						$row['drop'],
						$row['count'],
						$row['memo'],
						['0'=>'初始', '1'=>'已使用'][$row['status']],
						$row['begin_time'],
						$row['end_time'],
						$row['create_time'],
						$row['update_time'],
					];
					foreach ($row as $column) {
						$str_arr[] = '"' . str_replace('\"', '\"\"', $column) . '"';
					}
					$str.=implode(',', $str_arr) . PHP_EOL;
				}
				$id = $row[0];
				$str = mb_convert_encoding($str, "cp936", "UTF-8");
				echo $str;
			}
			//var_dump($data);
		}
		exit;
	}
	
	/**
	 * 禁言页面
	 */
	public function banPlayerChatAction(){
		$this->view->setVar("treeact",'chat');
		$authCode = 604;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		$PlayerInfo = new PlayerInfo;
		$playerInfo = $PlayerInfo->getByPlayerId($playerId);

		$data = (new Notice)->getAll();
		$this->view->setVar("playerId",$playerId);
		$this->view->setVar("data",$data);
		$banMsgTime = $playerInfo['ban_msg_time'];
		$banMsgTime = '';
		if($playerInfo['ban_msg_time']) {
			$banMsgTime = date('Y-m-d H:i:s', $playerInfo['ban_msg_time']);
		}
		$this->view->banMsgTime = $banMsgTime;
	}
	/**
	 * 禁言
	 */
	public function doBanPlayerChatAction(){
		$authCode = 604;
		if(!$this->checkAuthId($authCode))
			return;

		$playerId = $_POST['playerId'];
		$banDate = $_POST['banDate'];

		$PlayerInfo = new PlayerInfo;
		$PlayerInfo->alter($playerId, ['ban_msg_time'=>$banDate]);
		exit;
	}
	/**
	 * 解禁
	 */
	public function doNotBanPlayerChatAction(){
		$authCode = 604;
		if(!$this->checkAuthId($authCode))
			return;

		$playerId = $_POST['playerId'];

		$PlayerInfo = new PlayerInfo;
		$PlayerInfo->alter($playerId, ['ban_msg_time'=>'0000-00-00 00:00:00']);
		exit;
	}
	/**
	 * 世界聊天list
	 */
	public function worldChatListAction(){
		$this->view->setVar("treeact",'chat');
		$authCode = 604;
		if(!$this->checkAuthId($authCode))
			return;
		$worldMsg = (new ChatUtil)->getAllWorldMsg(true);
		$this->view->worldMsg = $worldMsg;
	}
    /**
     * 世界聊天list
     */
    public function campChatListAction(){
        $this->view->setVar("treeact",'chat');
        $authCode = 604;
        if(!$this->checkAuthId($authCode))
            return;
        $campId = $_GET['camp_id'];
        $campMsg = (new ChatUtil)->getCampMsg($campId);
        $this->view->campMsg = $campMsg;
        $this->view->campId = $campId;
    }
	/**
	 * flush聊天
	 */
	public function ajaxFlushAllWorldChatAction(){
		$this->view->setVar("treeact",'chat');
		$authCode = 604;
		if(!$this->checkAuthId($authCode))
			return;
		Cache::db(CACHEDB_CHAT)->del('WorldChat');
		exit;
	}
	/**
	 * flush聊天
	 */
	public function ajaxFlushAllCampChatAction(){
		$this->view->setVar("treeact",'chat');
		$authCode = 604;
		if(!$this->checkAuthId($authCode))
			return;
        $campId = $_POST['camp_id'];
        Cache::db('chat', 'CityBattle')->del('CampChat-'.$campId);
		exit;
	}

	/**
	 * 删除单条
	 */
	public function delSingleWorldChatAction(){
		$this->view->setVar("treeact",'chat');
		$authCode = 604;
		if(!$this->checkAuthId($authCode))
			return;
		$id  = $_POST['id'];
		$r   = Cache::db(CACHEDB_CHAT);
		$str = '';
		$r->lSet('WorldChat', $id, $str);
		$r->lRem('WorldChat', $str);
		exit;
	}
    /**
     * 删除单条
     */
    public function delSingleCampChatAction(){
        $this->view->setVar("treeact",'chat');
        $authCode = 604;
        if(!$this->checkAuthId($authCode))
            return;
        $id     = $_POST['id'];
        $campId = $_POST['camp_id'];
        $r      = Cache::db('chat', 'CityBattle');
        $c      = $r->lGet('CampChat-'.$campId, $id);
        $r->lRem('CampChat-'.$campId, $c);
        exit;
    }
	/**
	 * 封号页面
	 */
	public function banAccountAction(){
		$this->view->setVar("treeact",'playerdo');
		$authCode = 202;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $this->getPlayerId();
		if(!$playerId){
			$this->view->setVar("errmsg",'玩家不存在');
			return;
		}
		$PlayerInfo = new PlayerInfo;
		$playerInfo = $PlayerInfo->getByPlayerId($playerId);

		$data = (new Notice)->getAll();
		$this->view->setVar("playerId",$playerId);
		$this->view->setVar("data",$data);
		$banTime = $playerInfo['ban_time'];
		$banTime = '';
		if($playerInfo['ban_time']) {
			$banTime = date('Y-m-d H:i:s', $playerInfo['ban_time']);
		}
		$this->view->banTime = $banTime;
	}
	/**
	 * 封号
	 */
	public function doBanAccountAction(){
		$authCode = 202;
		if(!$this->checkAuthId($authCode))
			return;
		$playerId = $_POST['playerId'];
		$banDate = $_POST['banDate'];

		$PlayerInfo = new PlayerInfo;
		$PlayerInfo->alter($playerId, ['ban_time'=>$banDate]);
		exit;
	}
	/**
	 * 批量封号页面
	 */
	public function banMultiAccountAction(){
		$this->view->setVar("treeact",'playerdo');
		$authCode = 202;
		if(!$this->checkAuthId($authCode))
			return;
	}
	/**
	 * 批量封号
	 */
	public function doBanMultiAccountAction(){
		$authCode = 202;
		if(!$this->checkAuthId($authCode))
			return;
		$userCode = array_unique(explode(',', $_POST['userCode']));
		$banDate = $_POST['banDate'];

		$PlayerInfo = new PlayerInfo;

		foreach($userCode as $k=>$v) {
			$re = Player::findFirst("user_code='{$v}'");
			if($re) {
				$PlayerInfo->alter($re->id, ['ban_time'=>$banDate]);
			}
		}
		exit;
	}
	/**
	 * 解封
	 */
	public function doNotBanAccountAction(){
		$authCode = 202;
		if(!$this->checkAuthId($authCode))
			return;

		$playerId = $_POST['playerId'];

		$PlayerInfo = new PlayerInfo;
		$PlayerInfo->alter($playerId, ['ban_time'=>'0000-00-00 00:00:00']);
		exit;
	}
	
	/**
	 * 创建机器人
	 */
	public function createRobotAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(608))
			return;		
	}
	
	public function ajaxCreateRobotAction(){
		$authCode = 608;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$robotNum = floor(@$_REQUEST['robotNum']);
		if(!$robotNum){
			echo json_encode(['err'=>'请输入数字']);
			exit;
		}
		if($robotNum < 1 || $robotNum > 50){
			echo json_encode(['err'=>'请输入1-50以内的数字']);
			exit;
		}
		
		set_time_limit(0);
		$Configure = new Configure;
		$sTime = $Configure->getValueByKey("server_start_time");
		$day = ceil((time()-$sTime)/3600/24);
		$Player = new Player;
		$Player->createRobot($day, $robotNum);
		
		$logMemo = [
			'desc'=>'创建机器人*'.$robotNum,
		];
		$this->addAdminLog($authCode, json_encode($logMemo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	/**
	 * 缓存工具
	 */
	public function cacheToolAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(609))
			return;
		global $config;
		$arr = [];
		$arr[''] = $config->redis->index->toArray();
		$arr['login_server'] = $config->login_server->redis->index->toArray();
		$arr['pk_server'] = $config->pk_server->redis->index->toArray();
		$arr['cross_server'] = $config->cross_server->redis->index->toArray();
		$arr['citybattle_server'] = $config->cross_server->redis->index->toArray();
		$this->view->setVar("cacheArr",$arr);
	}
	
	public function ajaxClearCacheAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$cacheKeys = @$_REQUEST['cacheKeys'];
		if(!$cacheKeys){
			echo json_encode(['err'=>'请选择库']);
			exit;
		}
		global $config;
		$otherList = ['login_server'=>'ServerList', 'pk_server'=>'Pk', 'cross_server'=>'Cross', 'citybattle_server'=>'CityBattle'];
		foreach($cacheKeys as $_k){
			$_k = explode(' ', trim($_k));
			if(count($_k) > 1){
				$cacheArr = $config->$_k[0]->redis->index->toArray();
				if(isset($cacheArr[$_k[1]])){
					Cache::db($_k[1], $otherList[$_k[0]])->flushDB();
				}
			}else{
				$cacheArr = $config->redis->index->toArray();
				if(isset($cacheArr[$_k[0]])){
					Cache::db($_k[0])->flushDB();
				}
			}
		}
		
		$logMemo = [
			'desc'=>'删除缓存：'.join(',', $cacheKeys),
		];
		$this->addAdminLog($authCode, json_encode($logMemo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function ajaxClearCachePlayerAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$playerId = floor(@$_REQUEST['playerId']);
		if(!$playerId || !checkRegularNumber($playerId)){
			echo json_encode(['err'=>'请输入玩家id']);
			exit;
		}
		Cache::delPlayerAll($playerId);
		
		$logMemo = [
			'desc'=>'删除玩家缓存：'.$playerId,
		];
		$this->addAdminLog($authCode, json_encode($logMemo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function ajaxClearCacheGuildAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$guildId = floor(@$_REQUEST['guildId']);
		if(!$guildId || !checkRegularNumber($guildId)){
			echo json_encode(['err'=>'请输入公会id']);
			exit;
		}
		Cache::delGuildAll($guildId);
		
		$logMemo = [
			'desc'=>'删除公会缓存：'.$guildId,
		];
		$this->addAdminLog($authCode, json_encode($logMemo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function ajaxClearCacheDispLockAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$x = @$_REQUEST['x'];
		$y = @$_REQUEST['y'];
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			echo json_encode(['err'=>'请输入坐标']);
			exit;
		}
		Cache::db('dispatcher')->del($x.'_'.$y);
		
		$logMemo = [
			'desc'=>'删除disp坐标锁：'.$x.','.$y,
		];
		$this->addAdminLog($authCode, json_encode($logMemo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function ajaxClearCacheCrossDispLockAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$x = @$_REQUEST['x'];
		$y = @$_REQUEST['y'];
		$battleId = @$_REQUEST['battleId'];
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			echo json_encode(['err'=>'请输入坐标']);
			exit;
		}
		Cache::db('dispatcher', 'Cross')->del($battleId.'_'.$x.'_'.$y);
		
		$logMemo = [
			'desc'=>'删除cross disp坐标锁：'.$battleId.','.$x.','.$y,
		];
		$this->addAdminLog($authCode, json_encode($logMemo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function ajaxClearCacheCitybattleDispLockAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$x = @$_REQUEST['x'];
		$y = @$_REQUEST['y'];
		$battleId = @$_REQUEST['battleId'];
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			echo json_encode(['err'=>'请输入坐标']);
			exit;
		}
		Cache::db('dispatcher', 'CityBattle')->del($battleId.'_'.$x.'_'.$y);
		
		$logMemo = [
			'desc'=>'删除citybattle disp坐标锁：'.$battleId.','.$x.','.$y,
		];
		$this->addAdminLog($authCode, json_encode($logMemo));
		echo json_encode(['err'=>'ok']);
		exit;
	}
	
	public function ajaxGetDispLockAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$data = Cache::db('dispatcher')->keys('*');
		echo json_encode(['err'=>'ok', 'data'=>$data]);
		exit;
	}
	
	public function ajaxGetCrossDispLockAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$data = Cache::db('dispatcher', 'Cross')->keys('*');
		echo json_encode(['err'=>'ok', 'data'=>$data]);
		exit;
	}
	
	public function ajaxGetCitybattleDispLockAction(){
		$authCode = 609;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$data = Cache::db('dispatcher', 'CityBattle')->keys('*');
		echo json_encode(['err'=>'ok', 'data'=>$data]);
		exit;
	}
	
	/**
     * 发送 推送
     * 
     * 
     * @return <type>
     */
	public function addPushAction(){
		$this->view->setVar("treeact",'other');
		if(!$this->checkAuthId(610))
			return;
		
	}

	public function ajaxAddPushAction(){
		$authCode = 610;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$type = $_REQUEST['sendAllMailToType'];
		$failPlayer = [];
		if($type == 2){
			$userCode = trim($_REQUEST['sendAllMailUserCode']);
			//usercode to playerId
			if($userCode == ''){
				echo json_encode(['err'=>'userCode为空']);
				exit;
			}
			$userCode = explode(',', $userCode);
			if(count($userCode) > 5000){
				echo json_encode(['err'=>'userCode必须小于5000条']);
				exit;
			}
			$userCode = array_map("strtoupper", $userCode);
			$players = (new Player)->find(['user_code in ("'.join('","', $userCode).'")'])->toArray();
			$code = Set::extract('/user_code', $players);
			$toPlayerIds = Set::extract('/id', $players);
			$failPlayer = array_values(array_diff($userCode, $code));
		}else{
			$toPlayerIds = 0;
		}
		$msg = trim($_REQUEST['sendAllMailMsg']);
		$sendTime = trim($_REQUEST['sendTime']);
		set_time_limit(0);
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			$PlayerPush = new PlayerPush;
			if(is_array($toPlayerIds)){
				foreach($toPlayerIds as $_playerId){
					$PlayerPush->add($_playerId, 0, 0, [], $msg, $sendTime);
				}
			}else{
				$PlayerPush->add(0, 0, 0, [], $msg, $sendTime);
			}
			
			$logMemo = [
				'desc'=>'新增推送',
				'msg'=>$msg,
				'playerIds'=>$toPlayerIds,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'failPlayer'=>$failPlayer]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败']);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}

	public function activityShowAction(){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		$activity = (new Activity)->dicGetAll();
		$this->view->setVars([
			'activity'=>$activity,
		]);
	}
	
	public function ajaxActivityShowAction(){
		$authCode = 607;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$activityId = trim($_REQUEST['activity_id']);
		
		$where = [];
		$ActivityConfigure = new ActivityConfigure;
		if($activityId != ''){
			$where[] = 'activity_id='.$activityId;
		}else{
			$where[] = 'activity_id in ('.join(',', $ActivityConfigure->acts).')';
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$activity = (new Activity)->dicGetAll();
		//$ActivityConfigure->find(['activity_id in (1017, 1018, 1019)'])->toArray()
		$data = $this->dataTableGet($ActivityConfigure, $where, true);
		foreach($data['data'] as &$_d){
			$_config = json_decode($_d['activity_para'], true);
			$_d['operation'] = '<button class="btn btn-success btn-xs" type="button" onclick="editAct('.$_d['activity_id'].','.$_d['id'].')">编辑</button>';
			if(!$_d['status']){
				$_d['operation'] .= ' <button class="btn btn-danger btn-xs" type="button" onclick="changeActStatus('.$_d['id'].', 1)">开启</button>';
			}else{
				$_d['operation'] .= ' <button class="btn btn-danger btn-xs" type="button" onclick="changeActStatus('.$_d['id'].', 0)">关闭</button>';
			}
			$_d['activity_name'] = @$_config['name'];
			$_d['activity_id'] = $activity[$_d['activity_id']]['name_dec'];
			$_d['status'] = ['0'=>'未开启', '1'=>'开启'][$_d['status']];
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function ajaxActivityChangeStatusAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		$status = trim(@$_REQUEST['status']) ? 1 : 0;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			(new ActivityConfigure)->updateAll(['status'=>$status], ['id'=>$actConfigId]);
			
			$logMemo = [
				'desc'=>($status ? '开启' : '关闭').'活动',
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok']);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>'系统失败']);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	public function addActivityLoginAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			$act['activity_para'] = json_decode($act['activity_para'], true);
			//var_dump($act);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivityLoginAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$subDays = ceil((strtotime($endTime) - strtotime($beginTime))/(24*60*60));
		$reward = @$_REQUEST['reward'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		if(!is_array($reward)){
			echo json_encode(['err'=>'奖励格式错误']);
			exit;
		}
		foreach($reward as $_r){
			if(!@$_r['day'] || !@$_r['drop']){
				echo json_encode(['err'=>'奖励格式错误']);
				exit;
			}
			if(!is_numeric($_r['day'])){
				echo json_encode(['err'=>'档位错误']);
				exit;
			}
			if($_r['day']>$subDays) {
				echo json_encode(['err'=>"天数超出起止时间:[违例天数:{$_r['day']}],最大天数不能>={$subDays}"]);
				exit;
			}
			$drop = parseGroup($_r['drop'], false);
			foreach($drop as $_d){
				if(count($_d) != 3
				|| !checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'道具格式错误']);
					exit;
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$para['reward'] = [];
			foreach($reward as $_r){
				$para['reward'][$_r['day']] = $_r['drop'];
			}
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(PlayerActivityLogin::ACTID, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'累计充值活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'reward'=>$reward,
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}

    /**
     * 累计充值活动
     * 
     * @param <type> $actId 
     * 
     * @return <type>
     */
	public function addActivityChargeAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			$act['activity_para'] = json_decode($act['activity_para'], true);
			//var_dump($act);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivityChargeAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$reward = @$_REQUEST['reward'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		if(!is_array($reward)){
			echo json_encode(['err'=>'奖励格式错误']);
			exit;
		}
		foreach($reward as $_r){
			if(!@$_r['day'] || !@$_r['drop']){
				echo json_encode(['err'=>'奖励格式错误']);
				exit;
			}
			if(!is_numeric($_r['day'])){
				echo json_encode(['err'=>'档位错误']);
				exit;
			}
			$drop = parseGroup($_r['drop'], false);
			foreach($drop as $_d){
				if(count($_d) != 3
				|| !checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'道具格式错误']);
					exit;
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		if($beginTime > $endTime){
			echo json_encode(['err'=>'活动时间错误']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$para['reward'] = [];
			foreach($reward as $_r){
				$para['reward'][$_r['day']] = $_r['drop'];
			}
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(PlayerActivityCharge::ACTID, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'累计充值活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'reward'=>$reward,
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	/**
     * 累计消耗活动
     * 
     * @param <type> $actId 
     * 
     * @return <type>
     */
	public function addActivityConsumeAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			$act['activity_para'] = json_decode($act['activity_para'], true);
			//var_dump($act);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivityConsumeAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$reward = @$_REQUEST['reward'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		if(!is_array($reward)){
			echo json_encode(['err'=>'奖励格式错误']);
			exit;
		}
		foreach($reward as $_r){
			if(!@$_r['day'] || !@$_r['drop']){
				echo json_encode(['err'=>'奖励格式错误']);
				exit;
			}
			if(!is_numeric($_r['day'])){
				echo json_encode(['err'=>'档位错误']);
				exit;
			}
			$drop = parseGroup($_r['drop'], false);
			foreach($drop as $_d){
				if(count($_d) != 3
				|| !checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'道具格式错误']);
					exit;
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		if($beginTime > $endTime){
			echo json_encode(['err'=>'活动时间错误']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$para['reward'] = [];
			foreach($reward as $_r){
				$para['reward'][$_r['day']] = $_r['drop'];
			}
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(PlayerActivityConsume::ACTID, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'累计消耗活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'reward'=>$reward,
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	public function addActivityNpcDropAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			$act['activity_para'] = json_decode($act['activity_para'], true);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivityNpcDropAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$reward = @$_REQUEST['reward'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		$actMemo = @$_REQUEST['actMemo'];
		if(!is_array($reward)){
			echo json_encode(['err'=>'奖励格式错误']);
			exit;
		}
		foreach($reward as $_r){
			if(!@$_r['drop']){
				echo json_encode(['err'=>'奖励格式错误']);
				exit;
			}
			if(!is_numeric($_r['rate']) || $_r['rate'] < 0 || $_r['rate'] > 100){
				echo json_encode(['err'=>'概率错误']);
				exit;
			}
			$drop = parseGroup($_r['drop'], false);
			foreach($drop as $_d){
				if(count($_d) != 3
				|| !checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'道具格式错误']);
					exit;
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		if($beginTime > $endTime){
			echo json_encode(['err'=>'活动时间错误']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$para['npc'] = ['rate'=>$reward['npc']['rate']/100, 'drop'=>$reward['npc']['drop']];
			$para['boss'] = ['rate'=>$reward['boss']['rate']/100, 'drop'=>$reward['boss']['drop']];
			$para['memo'] = $actMemo;
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(1019, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'累计充值活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'reward'=>$reward,
				'actConfigId'=>$actConfigId,
				'actMemo'=>$actMemo,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}

	/**
     * 大转盘活动
     * 
     * @param <type> $actId 
     * 
     * @return <type>
     */
	public function addActivityWheelAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			$act['activity_para'] = json_decode($act['activity_para'], true);
			//var_dump($act);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivityWheelAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$gem = floor(@$_REQUEST['gem']);
		$itemId = floor(@$_REQUEST['itemId']);
		$xcounter = floor(@$_REQUEST['xcounter']);
		$memo = trim(@$_REQUEST['memo']);
		$wheel = @$_REQUEST['wheel'];
		$reward = @$_REQUEST['reward'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		if(!is_array($reward)){
			echo json_encode(['err'=>'奖励格式错误']);
			exit;
		}
		//转盘奖励
		foreach($wheel as $_r){
			if(count($_r) != 3 || !checkRegularNumber($_r['rate'], true) || !checkRegularNumber($_r['rate2'], true)){
				echo json_encode(['err'=>'道具格式错误']);
				exit;
			}
			$drop = parseGroup($_r['drop'], false);
			if(!$drop){
				echo json_encode(['err'=>'道具格式错误']);
				exit;
			}
			foreach($drop as $_d){
				if(!checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'道具格式错误']);
					exit;
				}
			}
		}
		//累计数量奖励
		foreach($reward as $_r){
			if(!@$_r['day'] || !@$_r['drop']){
				echo json_encode(['err'=>'奖励格式错误']);
				exit;
			}
			if(!is_numeric($_r['day'])){
				echo json_encode(['err'=>'档位错误']);
				exit;
			}
			$drop = parseGroup($_r['drop'], false);
			foreach($drop as $_d){
				if(count($_d) != 3
				|| !checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'道具格式错误']);
					exit;
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		if($beginTime > $endTime){
			echo json_encode(['err'=>'活动时间错误']);
			exit;
		}
		if(!checkRegularNumber($gem)){
			echo json_encode(['err'=>'请输入元宝数']);
			exit;
		}
		if(!checkRegularNumber($itemId)){
			echo json_encode(['err'=>'请输入道具id']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$para['gem'] = $gem;
			$para['itemId'] = $itemId;
			$para['memo'] = $memo;
			$para['xcounter'] = $xcounter;
			
			if(!(new Item)->dicGetOne($itemId)){
				throw new Exception('游戏消耗道具不存在');
			}
			
			$para['reward'] = [];
			foreach($reward as $_r){
				$para['reward'][$_r['day']] = $_r['drop'];
			}
			$para['wheel'] = $wheel;
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(PlayerActivityWheel::ACTID, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'累计消耗活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'reward'=>$reward,
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	/**
     * 兑换活动
     * 
     * @param <type> $actId 
     * 
     * @return <type>
     */
	public function addActivityExchangeAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			$act['activity_para'] = json_decode($act['activity_para'], true);
			//var_dump($act);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivityExchangeAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$reward = @$_REQUEST['reward'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		if(!is_array($reward)){
			echo json_encode(['err'=>'奖励格式错误']);
			exit;
		}
		foreach($reward as $_r){
			if(!@$_r['consume'] || !@$_r['drop']){
				echo json_encode(['err'=>'格式错误']);
				exit;
			}
			if(!checkRegularNumber($_r['limit'], true)){
				echo json_encode(['err'=>'兑换次数错误']);
				exit;
			}
			$consume = parseGroup($_r['consume'], false);
			foreach($consume as $_d){
				if(count($_d) != 3
				|| !checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'消耗道具格式错误']);
					exit;
				}
			}
			$drop = parseGroup($_r['drop'], false);
			foreach($drop as $_d){
				if(count($_d) != 3
				|| !checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'获得道具格式错误']);
					exit;
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		if($beginTime > $endTime){
			echo json_encode(['err'=>'活动时间错误']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$para['reward'] = [];
			$i = 1;
			foreach($reward as $_r){
				$para['reward'][$i] = $_r;
				$i++;
			}
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(PlayerActivityExchange::ACTID, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'兑换活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'reward'=>$reward,
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
	/**
     * 秒杀活动
     * 
     * @param <type> $actId 
     * 
     * @return <type>
     */
	public function addActivityPanicBuyAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			//var_dump($act);exit;
			$act['activity_para'] = json_decode($act['activity_para'], true);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivityPanicBuyAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$reward = @$_REQUEST['reward'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		if(!is_array($reward)){
			echo json_encode(['err'=>'奖励格式错误']);
			exit;
		}
		$_endTime = false;
		foreach($reward as $_r){
			if(!@$_r['time'] || !@$_r['ar'] || !is_array($_r['ar'])){
				echo json_encode(['err'=>'格式错误']);
				exit;
			}
			foreach($_r['ar'] as $_ar){
				if(!@$_ar['beginTime'] || !@$_ar['endTime'] || !@$_ar['items'] || !is_array($_ar['items'])){
					echo json_encode(['err'=>'格式错误2']);
					exit;
				}
				if($_ar['beginTime'] > $_ar['endTime']){
					echo json_encode(['err'=>'轮次时间错误：'.$_ar['beginTime'] .'~'. $_ar['endTime']]);
					exit;
				}
				if($_endTime && $_ar['beginTime'] < $_endTime){
					echo json_encode(['err'=>'轮次交叉时间存在:'.$_ar['beginTime']]);
					exit;
				}
				$_endTime = $_ar['endTime'];
				foreach($_ar['items'] as $_item){
					if(!@$_item['drop'] || !@$_item['price'] || !@$_item['limit']){
						echo json_encode(['err'=>'格式错误3']);
						exit;
					}
					if(!checkRegularNumber($_item['limit'], true)){
						echo json_encode(['err'=>'限购次数错误']);
						exit;
					}
					$drop = parseGroup($_item['drop'], false);
					foreach($drop as $_d){
						if(count($_d) != 3
						|| !checkRegularNumber($_d[0])
						|| !checkRegularNumber($_d[1])
						|| !checkRegularNumber($_d[2])
						){
							echo json_encode(['err'=>'获得道具格式错误']);
							exit;
						}
					}
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		if($beginTime > $endTime){
			echo json_encode(['err'=>'活动时间错误']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$i = 1;
			foreach($reward as &$_r){
				foreach($_r['ar'] as &$_ar){
					foreach($_ar['items'] as &$_item){
						$_item['id'] = $i;
						$i++;
					}
					unset($_item);
				}
				unset($_ar);
			}
			unset($_r);
			$para['reward'] = $reward;
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(PlayerActivityPanicBuy::ACTID, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$old = $ActivityConfigure->findFirst($actConfigId)->toArray();
				$now = date('Y-m-d H:i:s');
				if($old['start_time'] <= $now && $old['end_time'] >= $now){
					throw new Exception('活动已经开始，不允许修改');
				}
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			$ActivityPanicBuy = new ActivityPanicBuy;
			$ActivityPanicBuy->find(["activity_configure_id=".$actConfigId])->delete();
			foreach($reward as $_r){
				foreach($_r['ar'] as $_ar){
					foreach($_ar['items'] as &$_item){
						if(!$ActivityPanicBuy->add($actConfigId, $_item['id'], $_item['price'], $_item['limit'], $_item['drop'], $_r['time'], $_ar['beginTime'], $_ar['endTime'])){
							throw new Exception('配置失败');
						}
					}
					unset($_item);
				}
			}

			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'兑换活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'reward'=>$reward,
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
    /**
     * 祭天
     * 
     * @param <type> $actId 
     * 
     * @return <type>
     */
	public function addActivitySacrificeAction($actId=0){
		$this->view->setVar("treeact",'activity');
		if(!$this->checkAuthId(700))
			return;
		
		if($actId){
			$act = (new ActivityConfigure)->findFirst($actId);
			if(!$act)
				goto a;
			$act = $act->toArray();
			$act['activity_para'] = json_decode($act['activity_para'], true);
			//var_dump($act);
			$this->view->setVars([
				'add'=>0,
				'act'=>$act,
			]);
		}else{
			a:
			$this->view->setVars([
				'add'=>1,
			]);
		}
		
		$this->getDropCreateVar();
	}
	
	public function ajaxAddActivitySacrificeAction(){
		$authCode = 700;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$actName = trim(@$_REQUEST['actName']);
		$beginTime = trim(@$_REQUEST['beginTime']);
		$endTime = trim(@$_REQUEST['endTime']);
		$gem = floor(@$_REQUEST['gem']);
		$gemMulti = floor(@$_REQUEST['gemMulti']);
		$itemId = floor(@$_REQUEST['itemId']);
		$xcounter = floor(@$_REQUEST['xcounter']);
		$memo = trim(@$_REQUEST['memo']);
		$wheel = @$_REQUEST['wheel'];
		$addMode = trim(@$_REQUEST['addMode']) ? 1 : 0;
		$actConfigId = trim(@$_REQUEST['actConfigId']);
		//转盘奖励
		foreach($wheel as $_r){
			if(count($_r) != 3 || !checkRegularNumber($_r['rate'], true) || !checkRegularNumber($_r['rate2'], true)){
				echo json_encode(['err'=>'道具格式错误']);
				exit;
			}
			$drop = parseGroup($_r['drop'], false);
			if(!$drop){
				echo json_encode(['err'=>'道具格式错误']);
				exit;
			}
			foreach($drop as $_d){
				if(!checkRegularNumber($_d[0])
				|| !checkRegularNumber($_d[1])
				|| !checkRegularNumber($_d[2])
				){
					echo json_encode(['err'=>'道具格式错误']);
					exit;
				}
			}
		}
		if(!$actName){
			echo json_encode(['err'=>'请输入活动名称']);
			exit;
		}
		if(!$beginTime || !$endTime){
			echo json_encode(['err'=>'请输入活动时间']);
			exit;
		}
		if($beginTime > $endTime){
			echo json_encode(['err'=>'活动时间错误']);
			exit;
		}
		if(!checkRegularNumber($gem) || !checkRegularNumber($gemMulti)){
			echo json_encode(['err'=>'请输入元宝数']);
			exit;
		}
		if(!checkRegularNumber($itemId, true)){
			echo json_encode(['err'=>'请输入道具id']);
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':user=' .$this->user['name'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
	
		try {
			
			//整理para
			$para = [];
			$para['name'] = $actName;
			$para['gem'] = $gem;
			$para['gemMulti'] = $gemMulti;
			$para['itemId'] = $itemId;
			$para['memo'] = $memo;
			$para['xcounter'] = $xcounter;
			
			if(!(new Item)->dicGetOne($itemId)){
				throw new Exception('游戏消耗道具不存在');
			}
			
			$para['wheel'] = $wheel;
			
			$ActivityConfigure = new ActivityConfigure;
			if($addMode){
				if(!$ActivityConfigure->openActivity(PlayerActivitySacrifice::ACTID, $beginTime, $beginTime, $endTime, $para, $actConfigId)){
					throw new Exception('该时间内已有同类活动');
				}
			}else{
				$para = json_encode($para, JSON_UNESCAPED_UNICODE);
				if(!$ActivityConfigure->saveAll([
					'show_time'=>$beginTime,
					'start_time'=>$beginTime,
					'end_time'=>$endTime,
					'activity_para'=>$para,
					'create_time'=>date('Y-m-d H:i:s'),
				], 'id='.$actConfigId)){
					throw new Exception('编辑失败');
				}
			}
			
			
			$logMemo = [
				'desc'=>($addMode ? '新增' : '编辑').'累计消耗活动',
				'actName'=>$actName,
				'beginTime'=>$beginTime,
				'endTime'=>$endTime,
				'actConfigId'=>$actConfigId,
			];
			$this->addAdminLog($authCode, json_encode($logMemo));
			
			dbCommit($db);
			echo json_encode(['err'=>'ok', 'actConfigId'=>$actConfigId]);
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			echo json_encode(['err'=>$e->getMessage()]);
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		exit;
	}
	
    /**
     * ip限制
     */
	public function ipLimitAction(){
        $this->view->setVar("treeact",'maintain');
        $authCode = 0;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $serverList = (new ServerList)->dicGetAll();
        $this->view->setVar("serverList",$serverList);
    }
    public function ajaxIpLimitAction($flag=1){
        $authCode = 0;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $LoginServerConfig = new LoginServerConfig;
        if($flag==1) {
            $ips = $_POST['ips'];
                $sArr['ips'] = array_unique(explode(';', $ips));
                $LoginServerConfig->saveData('ip_limit_config_global', json_encode($sArr));

                $memo = [
                    'desc'      => 'ip限制白名单-全局',
                    'ips'       => $ips,
                ];
                $this->addAdminLog($authCode, json_encode($memo, JSON_UNESCAPED_UNICODE));

        } elseif($flag==2) {
            $serverId = $_POST['serverId'];
            $ips      = $_POST['ips'];
            if(!empty($serverId)) {
                $ipsArr            = array_unique(explode(";", $ips));
                $sArr0['serverId'] = $serverId;
                $sArr0['ips']      = $ipsArr;
                $ipLimitSingle     = LoginServerConfig::findFirst(["key=:key:", 'bind'=>['key'=>'ip_limit_config_single']]);
                if($ipLimitSingle) {
                    $isFlag = false;
                    $ipLimitSingle = json_decode($ipLimitSingle->value, true);
                    foreach($ipLimitSingle as &$v) {
                        if($v['serverId']==$serverId) {
                            $v['ips'] = $ipsArr;
                            $isFlag = true;
                            break;
                        }
                    }
                    $sArr = $ipLimitSingle;
                    if(!$isFlag) {
                        $sArr[] = $sArr0;
                    }
                } else {
                    $sArr[]            = $sArr0;

                }
                $LoginServerConfig->saveData('ip_limit_config_single', json_encode($sArr));
                $memo = [
                    'desc'      => 'ip限制白名单-单服',
                    'serverId'  => $serverId,
                    'ips'       => $ips,
                ];
                $this->addAdminLog($authCode, json_encode($memo, JSON_UNESCAPED_UNICODE));
            }
        }

        exit;
    }
    /**
     * 更改客户端版本号
     * 根据输入框中输入的版本号来更改登录服的login_server_config的game_version值
     */
    public function alterGameVersionAction(){
        $this->view->setVar("treeact",'maintain');
        $authCode = 0;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $currentGameVersion = (new LoginServerConfig)->getValueByKey('game_version');
        $this->view->setVar('currentGameVersion', $currentGameVersion);
    }
    public function ajaxAlterGameVersionAction(){
        $this->view->setVar("treeact",'maintain');
        $authCode = 0;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $gameVersion = $_POST['gameVersion'];
        (new LoginServerConfig)->saveData('game_version', $gameVersion);
        $memo = [
            'desc'         => '修改game_version',
            'game_version' => $gameVersion,
        ];
        $this->addAdminLog($authCode, json_encode($memo, JSON_UNESCAPED_UNICODE));
        exit;

    }
    /**
     * 更改服务器状态
     * status
     * is_new
     * default_enter
     */
    public function alterServerListFieldAction(){
        $this->view->setVar("treeact", 'maintain');
        $authCode = 0;
        if (!$this->checkAuthId($authCode, 2))
            exit;
        $serverList = (new ServerList)->dicGetAll();
        $this->view->setVar('serverList', $serverList);
    }
    public function ajaxAlterServerListFieldAction(){
        $this->view->setVar("treeact", 'maintain');
        $authCode = 0;
        if (!$this->checkAuthId($authCode, 2))
            exit;
        $id    = $_POST['id'];
        $field = $_POST['field'];
        $value = $_POST['value'];
        if(in_array($field, ['status', 'isNew', 'default_enter', 'maintain_notice', 'all_status'])) {
            if($field=='default_enter') {
                (new ServerList)->alterDefaultEnter($id);
            } elseif($field=='all_status') {
                (new ServerList)->alterAllStatus($value);
            } else {
                (new ServerList)->alterServerList($id, $field, $value);
            }
        }
        $memo = [
            'desc'  => '修改' . $field,
            'value' => $value,
        ];
        $this->addAdminLog($authCode, json_encode($memo, JSON_UNESCAPED_UNICODE));
        exit;
    }
    /**
     * 更改维护文字
     */
    public function alterMaintainNoticeAction(){
        $this->view->setVar("treeact",'maintain');
        $authCode = 800;
        if(!$this->checkAuthId($authCode, 2))
            exit;
    }
    public function ajaxAlterMaintainNoticeAction(){
        $this->view->setVar("treeact",'maintain');
        $authCode = 800;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $id    = $_POST['id'];
        $field = $_POST['field'];
        $value = $_POST['value'];
        if($field=='maintain_notice') {
            (new ServerList)->alterServerList($id, $field, $value);
        }
        $memo = [
            'desc'  => '修改maintain_notice',
            'value' => $value,
        ];
        $this->addAdminLog($authCode, json_encode($memo, JSON_UNESCAPED_UNICODE));
        exit;
    }
    /**
     * 清缓存
     */
    public function clearServerCacheAction(){
        $this->view->setVar("treeact",'maintain');
        $authCode = 0;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        global $config;
        $serverList                        = (new ServerList)->dicGetAll();
        $redisIndex                        = $config->redis->index->toArray();
        $this->view->redisIndex            = $redisIndex;
        $this->view->serverList            = $serverList;
    }
    public function ajaxClearServerCacheAction(){
        $this->view->setVar("treeact",'maintain');
        $authCode = 0;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $server     = $_POST['server'];
        $index      = $_POST['index'];
        $serverList = (new ServerList)->dicGetAll();
        $serverList = Set::combine($serverList, '{n}.id', '{n}');
        $nodes = [];
        foreach($server as $k=>$v) {
            $nodes[$k]['url']    = $serverList[$v]['gameServerHost'] . '/api/clearCache';
            $nodes[$k]['fields'] = ['index'=>json_encode($index)];
        }
        curlMultiPost($nodes);
        $memo = [
            'desc'  => '清redis缓存',
            'nodes' => $nodes,
        ];
        $this->addAdminLog($authCode, json_encode($memo, JSON_UNESCAPED_UNICODE));
        exit;
    }
    public function pkConfigAction(){
        $this->view->setVar("treeact",'other');
        $authCode = 801;
        if(!$this->checkAuthId($authCode, 2))
            exit;
    }
    public function ajaxPkConfigAction(){
        $this->view->setVar("treeact",'other');
        $authCode = 801;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $serverId              = $_POST['serverId'];
        $name                  = $_POST['name'];
        $currentRoundStartTime = trim($_POST['currentRoundStartTime']) . ' ' . PkGroup::START_TIME;
        $nextRoundStartTime    = trim($_POST['nextRoundStartTime']) . ' ' . PkGroup::START_TIME;

        $serverIds                        = implode(';', $serverId);
        $PkGroup                          = new PkGroup;
        $date                             = date('Y-m-d H:i:s');
        $data['name']                     = $name;
        $data['server_ids']               = $serverIds;
        $data['current_round_start_time'] = $currentRoundStartTime;
        $data['next_round_start_time']    = $nextRoundStartTime;
        $data['update_log']               = "{添加[区：{$serverIds},赛季时间：<{$currentRoundStartTime},{$nextRoundStartTime}>]@{$date}}";
        $PkGroup->addNew($data);
    }
    public function pkConfigModifyAction(){
        $this->view->setVar("treeact",'other');
        $authCode = 801;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $id              = $_GET['id'];
        $PkGroup         = new PkGroup;
        $one             = $PkGroup->getGroupById($id);
        $this->view->one = $one;
    }
    public function ajaxPkConfigModifyAction(){
        $this->view->setVar("treeact",'other');
        $authCode = 801;
        if(!$this->checkAuthId($authCode, 2))
            exit;
        $PkGroup      = new PkGroup;
        $DuelInitdata = new DuelInitdata;

        $id        = $_POST['id'];
        $one       = $PkGroup->getGroupById($id);
        $initData  = $DuelInitdata->get();
        $closeTime = intval($initData['duel_close_time']);
        if(!$one) exit('fail');
        if(isset($_POST['close'])) {//关闭
            if(time()>(strtotime($one['next_round_start_time'])-$closeTime*60*60)) {
                $PkGroup->close($id);
                (new PkRank)->sqlExec("delete from pk_rank where pk_group_id=".$id);
                exit('ok');
            } else {
                exit('time');
            }
        } else {//修改
            if(isset($_POST['serverId'])) {
                if(time()>(strtotime($one['next_round_start_time'])-$closeTime*60*60)) {
                    $serverId           = $_POST['serverId'];
                    $serverIds          = implode(';', $serverId);
                    $data['server_ids'] = $serverIds;
                } else {
                    exit('time');
                }
            }
            $name                             = $_POST['name'];
            $nextRoundStartTime               = trim($_POST['nextRoundStartTime']) . ' ' . PkGroup::START_TIME;
            $date                             = date('Y-m-d H:i:s');
            $data['name']                     = $name;
            $data['next_round_start_time']    = $nextRoundStartTime;
            $data['update_log']               = "{修改[区：".@$serverIds."
            ,赛季结束时间：<{$nextRoundStartTime}>]@{$date}}";
            $PkGroup->alter($id, $data);
            exit('ok');
        }
    }

	public function crossRoundAction(){
		$this->view->setVar("treeact",'cross');
		if(!$this->checkAuthId(900))
			return;
	}
	
	public function ajaxCrossRoundAction(){
		$authCode = 900;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$status = trim(@$_REQUEST['status']);
		
		$where = [];
		if($status == 1){
			$where[] = 'status<5';
		}elseif($status == 2){
			$where[] = 'status=5';
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CrossRound = new CrossRound;
		$data = $this->dataTableGet($CrossRound, $where, true);
		foreach($data['data'] as &$_d){
			$_d['status'] = [
				'0'=>'报名', 
				'1'=>'匹配脚本运行开始',
				'2'=>'匹配脚本运行结束',
				'3'=>'处于比赛中状态',
				'4'=>'待发奖状态',
				'5'=>'整轮比赛结束'
			][$_d['status']];
			$_d['operation'] = '<button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/crossBattle/\', {roundId:'.$_d['id'].'})">详情</button>';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function crossBattleAction(){
		$this->view->setVar("treeact",'cross');
		if(!$this->checkAuthId(900))
			return;
	}
	
	public function ajaxCrossBattleAction(){
		$authCode = 900;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$roundId = trim(@$_REQUEST['roundId']);
		$serverId = trim(@$_REQUEST['serverId']);
		$guildId = trim(@$_REQUEST['guildId']);
		
		$CrossPlayer = new CrossPlayer;
		
		$where = [];
		if($roundId){
			$where[] = 'round_id='.$roundId;
		}
		if($serverId && $guildId){
			$guildId = $CrossPlayer->joinGuildId($serverId, $guildId);
			$where[] = "(guild_1_id={$guildId} or guild_2_id={$guildId})";
		}
		if($serverId && !$guildId){
			$guildIdBegin = $CrossPlayer->joinGuildId($serverId, 0);
			$guildIdEnd = $CrossPlayer->joinGuildId($serverId+1, 0);
			$where[] = "((guild_1_id>{$guildIdBegin} and guild_1_id<{$guildIdEnd}) or (guild_2_id>{$guildIdBegin} and guild_2_id<{$guildIdEnd}))";
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CrossBattle = new CrossBattle;
		$CrossGuild = new CrossGuild;
		$data = $this->dataTableGet($CrossBattle, $where, true);
		foreach($data['data'] as &$_d){
			$CrossGuild->battleId = $_d['id'];
			list($_d['server1'], $_d['guild1_id']) = array_values($CrossPlayer->parseGuildId($_d['guild_1_id']));
			$guildInfo = $CrossGuild->getGuildInfo($_d['guild_1_id']);
			$_d['guild1'] = $guildInfo['name'] . '<br>(' . $_d['server1'] . '服-' . $_d['guild1_id'] . ')<br><button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/crossGuild/\', {battleId:'.$_d['id'].', serverId:'.$_d['server1'].', guildId:'.$_d['guild1_id'].'})">详情</button>';
			
			list($_d['server2'], $_d['guild2_id']) = array_values($CrossPlayer->parseGuildId($_d['guild_2_id']));
			$guildInfo = $CrossGuild->getGuildInfo($_d['guild_2_id']);
			$_d['guild2'] = $guildInfo['name'] . '<br>('. $_d['server2'] . '服-' . $_d['guild2_id'] . ')<br><button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/crossGuild/\', {battleId:'.$_d['id'].', serverId:'.$_d['server2'].', guildId:'.$_d['guild2_id'].'})">详情</button>';
			
			$_d['status'] = [
				'0'=>'未开始', 
				'1'=>'攻击准备',
				'2'=>'攻击',
				'3'=>'中场结算',
				'4'=>'防御准备',
				'5'=>'防御',
				'6'=>'整场结算',
				'7'=>'比赛结束',
			][$_d['status']];
			$_d['operation'] = '<button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/crossLog/\', {battleId:'.$_d['id'].'})">日志</button>';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function crossGuildAction(){
		$this->view->setVar("treeact",'cross');
		if(!$this->checkAuthId(900))
			return;
	}
	
	public function ajaxCrossGuildAction(){
		$authCode = 900;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$roundId = trim(@$_REQUEST['roundId']);
		$battleId = trim(@$_REQUEST['battleId']);
		$serverId = trim(@$_REQUEST['serverId']);
		$guildId = trim(@$_REQUEST['guildId']);
		
		$CrossPlayer = new CrossPlayer;
		
		$where = [];
		if($roundId){
			$where[] = 'round_id='.$roundId;
		}
		if($battleId){
			$where[] = 'battle_id='.$battleId;
		}
		if($serverId && $guildId){
			$guildId = $CrossPlayer->joinGuildId($serverId, $guildId);
			$where[] = "guild_id={$guildId}";
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CrossBattle = new CrossBattle;
		$CrossGuild = new CrossGuild;
		$data = $this->dataTableGet($CrossGuild, $where, true);
		$guildScore = [];
		foreach($data['data'] as &$_d){
			$CrossGuild->battleId = $_d['id'];
			$_guildId = $_d['guild_id'];
			list($_d['server'], $_d['guild_id']) = array_values($CrossPlayer->parseGuildId($_d['guild_id']));
			//获取成员
			$CrossPlayer->battleId = $_d['battle_id'];
			$_player = $CrossPlayer->find(['battle_id='.$_d['battle_id'].' and guild_id='.$_guildId]);
			$player = [];
			foreach($_player as $_p){
				$player[] = $_p->nick.'('.$_p->player_id.')';
			}
			$_d['player'] = join('<Br>', $player);
			if(isset($guildScore[$_guildId])){
				$_d['score'] = $guildScore[$_guildId];
			}else{
				$cgi = CrossGuildInfo::findFirst(['guild_id='.$_guildId]);
				if($cgi){
					$_d['score'] = $guildScore[$_guildId] = '';
				}else{
					$_d['score'] = $guildScore[$_guildId] = $cgi['match_score'];
				}
			}
			
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function crossLogAction(){
		$this->view->setVar("treeact",'cross');
		if(!$this->checkAuthId(900))
			return;
	}
	
	public function ajaxCrossLogAction(){
		$authCode = 900;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$battleId = trim(@$_REQUEST['battleId']);
		$memo = trim(@$_REQUEST['memo']);
		
		$CrossPlayer = new CrossPlayer;
		
		$where = [];
		if($battleId != ''){
			$where[] = 'battle_id='.$battleId;
		}
		if($memo != ''){
			$where[] = 'memo like "%'.$memo.'%"';
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CrossCommonLog = new CrossCommonLog;
		$data = $this->dataTableGet($CrossCommonLog, $where, true);
		echo json_encode($data);
		exit;
	}
	
	
	
	
	
	public function citybattleRoundAction(){
		$this->view->setVar("treeact",'citybattle');
		if(!$this->checkAuthId(901))
			return;
	}
	
	public function ajaxCitybattleRoundAction(){
		$authCode = 901;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$status = trim(@$_REQUEST['status']);
		
		$where = [];
		if($status == 1){
			$where[] = 'status<'.CityBattleRound::FINISH;
		}elseif($status == 2){
			$where[] = 'status='.CityBattleRound::FINISH;
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CityBattleRound = new CityBattleRound;
		$data = $this->dataTableGet($CityBattleRound, $where, true);
		foreach($data['data'] as &$_d){
			$_d['status'] = [
				CityBattleRound::NOT_START=>'比赛未开始', 
				CityBattleRound::SIGN_FIRST=>'诸侯报名',
				CityBattleRound::SIGN_NORMAL=>'正常报名',
				CityBattleRound::SELECT_PLAYER=>'筛选玩家中',
				CityBattleRound::SELECT_PLAYER_FINISH=>'筛选玩家结束',
				CityBattleRound::DOING=>'比赛中',
				CityBattleRound::CLAC_REWARD=>'比赛发奖结算',
				CityBattleRound::FINISH=>'比赛完成',
			][$_d['status']];
			$_d['operation'] = '<button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/cityBattle/\', {roundId:'.$_d['id'].'})">详情</button>';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function cityBattleAction(){
		$this->view->setVar("treeact",'citybattle');
		if(!$this->checkAuthId(901))
			return;
		
		$City = new City;
		$cities = $City->findList('id', null, ['city_type=2']);
		
		//输出
		$this->view->setVars([
			'cities'=>$cities,
		]);
	}
	
	public function ajaxCityBattleAction(){
		$authCode = 901;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$roundId = trim(@$_REQUEST['roundId']);
		$cityId = trim(@$_REQUEST['cityId']);
		
		$CityBattlePlayer = new CityBattlePlayer;
		
		$where = [];
		if($roundId){
			$where[] = 'round_id='.$roundId;
		}
		if($cityId){
			$where[] = "city_id=".$cityId;
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CityBattle = new CityBattle;
		$CountryCampList = new CountryCampList;
		$camps = $CountryCampList->dicGetAll();
		$camps[0] = ['desc'=>''];
		$City = new City;
		$data = $this->dataTableGet($CityBattle, $where, true);
		$doorStatus = [0=>'未攻破', 1=>'已攻破'];
		foreach($data['data'] as &$_d){
			$_d['city_id'] = $City->getCityName($_d['city_id']).'('.$_d['city_id'].')';
			$_d['camp_id'] = $camps[$_d['camp_id']]['desc'].'('.$_d['camp_id'].')';
			$_d['attack_camp'] = $camps[$_d['attack_camp']]['desc'].'('.$_d['attack_camp'].')';
			$_d['defend_camp'] = $camps[$_d['defend_camp']]['desc'].'('.$_d['defend_camp'].')';
			$_d['win_camp'] = $camps[$_d['win_camp']]['desc'].'('.$_d['win_camp'].')';
			$_d['door1'] = $doorStatus[$_d['door1']];
			$_d['door2'] = $doorStatus[$_d['door2']];
			$_d['door3'] = $doorStatus[$_d['door3']];
			
			$_d['status'] = [
				CityBattle::STATUS_DEFAULT=>'未开始', 
				CityBattle::STATUS_READY_SEIGE=>'城门战准备',
				CityBattle::STATUS_SEIGE=>'城门战',
				CityBattle::STATUS_CLAC_SEIGE=>'中场结算',
				CityBattle::STATUS_READY_MELEE=>'内城战准备',
				CityBattle::STATUS_MELEE=>'内城战',
				CityBattle::STATUS_CLAC_MELEE=>'整场结算',
				CityBattle::STATUS_FINISH=>'比赛结束',
			][$_d['status']];
			$_d['operation'] = '<button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/citybattleLog/\', {battleId:'.$_d['id'].'})">日志</button>';
			$_d['operation'] .= '<button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/citybattleCamp/\', {battleId:'.$_d['id'].', campId:1})">'.$camps[1]['desc'].'详情</button>';
			$_d['operation'] .= '<button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/citybattleCamp/\', {battleId:'.$_d['id'].', campId:2})">'.$camps[2]['desc'].'详情</button>';
			$_d['operation'] .= '<button class="btn btn-danger btn-xs" type="button" onclick="linkPage(\'admin/citybattleCamp/\', {battleId:'.$_d['id'].', campId:3})">'.$camps[3]['desc'].'详情</button>';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function citybattleCampAction(){
		$this->view->setVar("treeact",'citybattle');
		if(!$this->checkAuthId(901))
			return;
		
		$camps = (new CountryCampList)->dicGetAll();
		//输出
		$this->view->setVars([
			'camps'=>$camps,
		]);
	}
	
	public function ajaxCitybattleCampAction(){
		$authCode = 901;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$roundId = trim(@$_REQUEST['roundId']);
		$battleId = trim(@$_REQUEST['battleId']);
		$campId = trim(@$_REQUEST['campId']);
		
		$CityBattlePlayer = new CityBattlePlayer;
		
		$where = [];
		if($roundId){
			$where[] = 'round_id='.$roundId;
		}
		if($battleId){
			$where[] = 'battle_id='.$battleId;
		}
		if($campId){
			$where[] = 'camp_id='.$campId;
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CountryCampList = new CountryCampList;
		$camps = $CountryCampList->dicGetAll();
		
		$CityBattle = new CityBattle;
		$CityBattleCamp = new CityBattleCamp;
		$data = $this->dataTableGet($CityBattleCamp, $where, true);
		$guildScore = [];
		foreach($data['data'] as &$_d){
			$_player = $CityBattlePlayer->find(['battle_id='.$_d['battle_id'].' and camp_id='.$_d['camp_id']]);
			$player = [];
			foreach($_player as $_p){
				$player[] = $_p->nick.'('.$_p->player_id.')';
			}
			$_d['player'] = join('<Br>', $player);			
			$_d['camp_id'] = $camps[$_d['camp_id']]['desc'].'('.$_d['camp_id'].')';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
	
	public function citybattleLogAction(){
		$this->view->setVar("treeact",'citybattle');
		if(!$this->checkAuthId(901))
			return;
	}
	
	public function ajaxCitybattleLogAction(){
		$authCode = 901;
		if(!$this->checkAuthId($authCode, 2))
			exit;
		$battleId = trim(@$_REQUEST['battleId']);
		$memo = trim(@$_REQUEST['memo']);
		
		$where = [];
		if($battleId != ''){
			$where[] = 'battle_id='.$battleId;
		}
		if($memo != ''){
			$where[] = 'memo like "%'.$memo.'%"';
		}
		$where = join(' and ', $where);
		if(!$where){
			$where = '1=1';
		}
		
		$CountryCampList = new CountryCampList;
		$camps = $CountryCampList->dicGetAll();
		$camps[0] = ['desc'=>''];
		
		$CityBattleCommonLog = new CityBattleCommonLog;
		$data = $this->dataTableGet($CityBattleCommonLog, $where, true);
		foreach($data['data'] as &$_d){
			$_d['camp_id'] = $camps[$_d['camp_id']]['desc'].'('.$_d['camp_id'].')';
		}
		unset($_d);
		echo json_encode($data);
		exit;
	}
}