<?php
use Phalcon\Mvc\View;
class InterfaceController extends ControllerBase
{
	public function initialize() {
		//parent::initialize();
		//$this->view->setLayout('layout');
		$this->view->setRenderLevel(View::LEVEL_LAYOUT);
		$AdminController = new AdminController;
		$AdminController->initialize();
		if(!$AdminController->checkAuthId(101, 0)){
			//echo 'please login admin <a href="/admin/?r='.urlencode('/interface/into').'">goto</a>';
			$this->response->redirect( 'admin/?r='.urlencode('/interface/into') )->sendHeaders();
			exit;
		}
		unset($_REQUEST['adminQA']);
	}
	
	public function indexAction(){
		
	}
	
	public function intoAction(){
		
	}
	
	public function mapAction(){
		
	}
	
	public function map2Action(){
		
	}
	
	public function ajaxMapAction($from_x, $from_y, $to_x, $to_y){
		$b = new Build;
		$ret = $b->sqlGet('select * from player_project_queue where status = 1 and (from_x>='.$from_x.' or to_x>='.$from_x.') and (from_x<='.$to_x.' or to_x<='.$to_x.') and (from_y>='.$from_y.' or to_y>='.$from_y.') and (from_y<='.$to_y.' or to_y<='.$to_y.')');
		
		//echo json_encode(array('err'=>'ok', 'data'=>$ret));
		//exit;
		//过滤2
		$p3 = array('x'=>floor(($from_x + $to_x)/2), 'y'=>floor(($from_y + $to_y)/2));
		$r = sqrt(pow(floor(abs($from_x - $to_x)/2), 2) + pow(floor(abs($from_y - $to_y)/2), 2));
		$ret2 = array();
		foreach($ret as $_r){
			$dis = $this->GetNearestDistance(array('x'=>$_r['from_x'], 'y'=>$_r['from_y']), array('x'=>$_r['to_x'], 'y'=>$_r['to_y']), $p3);
			if($dis <= $r){
				$ret2[] = $_r;
			}
		}
		//var_dump($ret);
		echo json_encode(array('err'=>'ok', 'data'=>$ret2));
		
		exit;
	}
	
	public function ajaxMap2Action(){
		$blockList = json_decode($_REQUEST['block'], true);
		$PlayerProjectQueue = new PlayerProjectQueue;
		//转化xy
		//$xys = array();
		$ret2 = array();
		$Map = new Map;
		foreach($blockList as $_b){
			$_xy = Map::calcXyByBlock($_b);
			$_xy = array(
				'from_x'=>max(0, $_xy['from_x']-12),
				'to_x'=>min(1236, $_xy['to_x']+12),
				'from_y'=>max(0, $_xy['from_y']-12),
				'to_y'=>min(1236, $_xy['to_y']+12),
			);
			$ret = $Map->sqlGet('select * from player_project_queue where status = 1 and (from_x>='.$_xy['from_x'].' or to_x>='.$_xy['from_x'].') and (from_x<='.$_xy['to_x'].' or to_x<='.$_xy['to_x'].') and (from_y>='.$_xy['from_y'].' or to_y>='.$_xy['from_y'].') and (from_y<='.$_xy['to_y'].' or to_y<='.$_xy['to_y'].')');
			
			//过滤2
			$p3 = array('x'=>floor(($_xy['from_x'] + $_xy['to_x'])/2), 'y'=>floor(($_xy['from_y'] + $_xy['to_y'])/2));
			$r = sqrt(pow(floor(abs($_xy['from_x'] - $_xy['to_x'])/2), 2) + pow(floor(abs($_xy['from_y'] - $_xy['to_y'])/2), 2));
			foreach($ret as $_r){
				$dis = $this->GetNearestDistance(array('x'=>$_r['from_x'], 'y'=>$_r['from_y']), array('x'=>$_r['to_x'], 'y'=>$_r['to_y']), $p3);
				if($dis <= $r){
					$ret2[] = $_r;
				}
			}
		}
		echo json_encode(array('err'=>'ok', 'data'=>$ret2));
		
		exit;
	}
	
	function GetPointDistance($p1, $p2){
		return sqrt(($p1['x']-$p2['x'])*($p1['x']-$p2['x'])+($p1['y']-$p2['y'])*($p1['y']-$p2['y']));
	}
	function GetNearestDistance($PA, $PB, $P3){

		//----------图2--------------------
		//float a,b,c;
		$a=$this->GetPointDistance($PB,$P3);
		if($a<=0)
			return 0;
		$b=$this->GetPointDistance($PA,$P3);
		if($b<=0)
			return 0;
		$c=$this->GetPointDistance($PA,$PB);
		if($c<=0)
			return $a;//如果PA和PB坐标相同，则退出函数，并返回距离
		//------------------------------

		if($a*$a>=$b*$b+$c*$c)//--------图3--------
			return $b;      //如果是钝角返回b
		if($b*$b>=$a*$a+$c*$c)//--------图4-------
			return $a;      //如果是钝角返回a

		//图1
		$l=($a+$b+$c)/2;     //周长的一半
		$s=sqrt($l*($l-$a)*($l-$b)*($l-$c));  //海伦公式求面积，也可以用矢量求
		return 2*$s/$c;
	}

	
	public function debugurlAction(){
		/*$url = $_REQUEST['url'];
		$ret = file_get_contents($url);
		echo $ret;
		echo "<hr>";
		var_dump(json_decode($ret, true));*/
	}
	
	public function clearCacheAction(){
		Cache::clearAllCache();
		Cache::db('server')->del('Dispatcher');
		echo '刷新完毕';
	}
	
	public function getUuidByIdAction($playerId){
		$Player = new Player;
		$ret = $Player->getByPlayerId($playerId);
		if($ret){
			echo $ret['uuid'];
		}
		exit;
	}
	
	public function getHashCodeAction($type=1, $id=0){
		if(!$id){
			echo json_encode(array('err'=>'请输入玩家id'));
			exit;
		}
		if($type == 1){
			$Player = new Player;
			$ret = $Player->find(['id="'.$id.'"'])->toArray();
			if($ret){
				$uuid = $ret[0]['uuid'];
			}
		}elseif($type == 2){
			$uuid = $id;
		}elseif($type == 3){
			$Player = new Player;
			$ret = $Player->find(['nick="'.$id.'"'])->toArray();
			if($ret){
				$uuid = $ret[0]['uuid'];
			}
		}elseif($type == 4){
			$Player = new Player;
			$ret = $Player->find(['user_code="'.$id.'"'])->toArray();
			if($ret){
				$uuid = $ret[0]['uuid'];
			}
		}else{
			echo json_encode(array('err'=>'type类型错误'));
			exit;
		}
		if(!@$uuid){
			echo json_encode(array('err'=>'未找到玩家'));
			exit;
		}
		$hashCode = md5($uuid.'Salt.SanGuoMobile2');
		echo json_encode(array('uuid'=>$uuid, 'hashCode'=>$hashCode, 'err'=>''));
		exit;
	}
	
	public function getPlayerIdFromType($type, $id){
		$Player = new Player;
		if($type == 1){
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
		if(!$playerId)
			exit;
		return $playerId;
	}
	
	public function toolClearPayStudyNumAction($type, $id){
		if(!QA)
			return;
		$playerId = $this->getPlayerIdFromType($type, $id);
		$Player->updateAll(['study_pay_num'=>0], ['id'=>$playerId]);
		echo 'ok';
		exit;
	}
	public function toolShowAppIniAction(){
		if(!QA)
			return;
		dump(file_get_contents(APP_PATH.'/app/app.ini'));
		exit;
	}

	public function clientSendAction($type, $id, $msgType=0){
		$playerId = $this->getPlayerIdFromType($type, $id);
		socketSend(['Type'=>'mail', 'Data'=>[$playerId=>['mail_id'=>1, 'type'=>1, 'connect_id'=>0]]]);
		//socketSend(['Type'=>'fight', 'Data'=>['playerId'=>[100128,100129,100174,100189,100234,100235,100239,100240,100016,100325,100348,100353,100391,100444,100445,100452,100461,100448,100463,100462,100479,100484,100503,100515,100511,100520,100499,100538,100559,100562,100553,100567,100555,100571,100574,100565,100586,100497,100596,100622,100623,100632,100615,100602,100648,100652,100663,100681,100682,100686,100702,100694,100707,100714,100581,100754,100785,100828,100832,100826,100827,100813,100878,100900,100882,100912,100929,100930]]]);
		//socketSend(['Type'=>'fight', 'Data'=>['playerId'=>[100615]]]);
		
		echo '已发送';
		exit;
	}
	
	public function transTimeAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$time = $post['time'];
		if(is_numeric($time)){
			$time1 = date('Y-m-d H:i:s', $time);
		}else{
			$time1 = strtotime($time);
		}
		echo $this->data->send(array('time'=>$time1));
	}
	/**
	 * 添加宝物
	 */
	public function addEquipMasterItemAction(){
		if(!QA)
			return;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$itemId = $post['itemId'];
		(new PlayerEquipMaster)->newPlayerEquipMaster($playerId, $itemId);
		echo $this->data->send();
		exit;
	}
	
	public function addItemAction(){
		if(!QA)
			return;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$itemId = $post['itemId'];
		$num = $post['num'];
		if(!checkRegularNumber($itemId) || !checkRegularNumber($num)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查道具
			$Item = new Item;
			$item = $Item->dicGetOne($itemId);
			if(!$item){
				throw new Exception(10122);
			}
			
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->add($playerId, $itemId, $num)){
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

	public function armyReturnAction(){
		if(!QA)
			return;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerArmy = new PlayerArmy;
			$PlayerArmy->sqlExec('delete from player_project_queue where player_id='.$playerId.' and status=1');
			$PlayerArmy->sqlExec('update player_army set status=0 where player_id='.$playerId);
			$PlayerArmy->sqlExec('update player_general set status=0 where player_id='.$playerId);
			Cache::delPlayerAll($playerId);
			
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
	 * 手动生成每日任务，或者是查看每日任务
	 * @return array 
	 */
	public function playerDailyMissionAction(){
		if(!QA)
			return;
		$player = $this->getCurrentPlayer();
		$re = (new PlayerMission)->getDailyMission($player['id'], $player['level']);
		$re = (new PlayerMission)->adapter($re);
		echo $this->data->send($re);
		exit;
	}

	public function orderAction(){
		if(!QA)
			return;
		$post = getPost();
		unset($post['uuid']);
		unset($post['hashCode']);
		$OrderController = new OrderController;
		//$post['Order_id'] = NumToStr($post['Order_id']);
		$post['sign'] = $OrderController->buildSign([
			'Order_id'=>$post['order_id'], 
			'commodity_id'=>$post['commodity_id'], 
			'Player_id'=>$post['player_id']
		]);
		$query = http_build_query($post);
		$ch = curl_init();

		// 设置URL和相应的选项
		curl_setopt($ch, CURLOPT_URL, 'http://'.$_SERVER['HTTP_HOST']."/order/notify");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

		// 抓取URL并把它传递给浏览器
		$ret = curl_exec($ch);

		// 关闭cURL资源，并且释放系统资源
		curl_close($ch);
		echo $ret;
		var_dump(json_decode( $ret, true));
		
		//echo '/order/notify?'.$query;
		//header('location:/order/notify?'.$query);
		//echo '<script>window.open("'.'/order/notify?'.$query.'")</script>';
	}
	
	public function orderWebAction(){
		if(!QA)
			return;
		$post = getPost();
		unset($post['uuid']);
		unset($post['hashCode']);
		$OrderController = new OrderController;
		//$post['Order_id'] = NumToStr($post['Order_id']);
		$post['sign'] = $OrderController->buildSign([
			'Order_id'=>$post['order_id'], 
			'commodity_id'=>$post['commodity_id'], 
			'Player_id'=>$post['player_id']
		]);
		$query = http_build_query($post);
		$ch = curl_init();

		// 设置URL和相应的选项
		curl_setopt($ch, CURLOPT_URL, 'http://'.$_SERVER['HTTP_HOST']."/order/notifyByWeb");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

		// 抓取URL并把它传递给浏览器
		$ret = curl_exec($ch);

		// 关闭cURL资源，并且释放系统资源
		curl_close($ch);
		echo $ret;
		var_dump(json_decode( $ret, true));
		
		//echo '/order/notify?'.$query;
		//header('location:/order/notify?'.$query);
		//echo '<script>window.open("'.'/order/notify?'.$query.'")</script>';
	}
	
	public function getPlayerTableAction(){
		if(!QA)
			return;
		$playerTables = [];
		$configDir = 'D:/www/_sg2/app/db/create_static';
		//检查字典文件夹是否存在
		if(!is_dir($configDir)){
			echo '找不到字典文件夹：'.$configDir;
			exit;
		}
		//获取字典表名
		$configFiles = scandir($configDir);
		$configTable = [];
		foreach($configFiles as $_f){
			if($_f == '.' || $_f == '..' || substr($_f, -4) != '.sql') continue;
			$configTable[] = strtolower(substr($_f, 0, -4));
		}

		//获取当前数据库所有表名
		$Player = new Player;
		$tables = $Player->sqlGet('show tables');
		//过滤字典表
		foreach($tables as $_t){
			$_t['Tables_in_sanguo2'] = strtolower($_t['Tables_in_sanguo2']);
			if(substr($_t['Tables_in_sanguo2'], 0, 1) != '_' && !in_array($_t['Tables_in_sanguo2'], $configTable)){
				$playerTables[] = $_t['Tables_in_sanguo2'];
			}
		}
//var_dump($playerTables);
		//创建sql
		//$str = '';
		foreach($playerTables as $_t){
			$r = $Player->sqlGet('show create table '.$_t);
			echo $this->sqlFormat($r[0]['Create Table']).";<Br><Br>";
			//echo 'ALTER TABLE `'.$_t.'` CHANGE `id` `id` BIGINT(11) NOT NULL AUTO_INCREMENT;<Br><Br>';
			//echo "alter table `".$_t."` AUTO_INCREMENT=1;<br>";
		}
		
		//echo "INSERT INTO `admin_user` (`id`, `name`, `password`, `pwd_status`, `auth`, `status`, `create_time`) VALUES ('1', 'admin', '2a915e220f683b798394babb4ecef1fb', '0', '1', '0', '0000-00-00 00:00:00');<br>";
		//echo "INSERT INTO `admin_auth` (`id`, `name`, `auth`) VALUES ('1', 'root', '0');<br>";
		
		foreach($playerTables as $_t){
			echo 'truncate `' . $_t."`;<Br>";
		}
	}
	
	public function sqlFormat($sql){
		return preg_replace('/(AUTO_INCREMENT=\d+\s)/', 'AUTO_INCREMENT=1 ',$sql);
	}
	
	public function fixPlayerBuffAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		try {
			$Drop = new Drop;
			//清空buff
			$PlayerBuff = new PlayerBuff;
			$PlayerBuff->resetData($playerId);
			
			//总结所有已学talent
			$Talent = new Talent;
			$PlayerTalent = new PlayerTalent;
			$pt = $PlayerTalent->find(['player_id='.$playerId])->toArray();
			foreach($pt as $_pt){
				$talent = $Talent->dicGetOne($_pt['talent_id']);
				//var_dump($talent);
				$talents = $Talent->find(['talent_type_id='.$talent['talent_type_id'], 'order'=>'level_id'])->toArray();
				foreach($talents as $_t){
					$_t = $Talent->parseColumn($_t);
					if($_t['level_id'] <= $talent['level_id']){
						if(!$Drop->gain($playerId, $_t['talent_drop'], 1, 'addTalent:'.$_t['id'])){
							//throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
				}
			}
			
			//总结所有已研究科技
			$Science = new Science;
			$PlayerScience = new PlayerScience;
			$ps = $PlayerScience->find(['player_id='.$playerId])->toArray();
			foreach($ps as $_ps){
				if(!$_ps['science_id']) continue;
				$science = $Science->dicGetOne($_ps['science_id']);
				//var_dump($science);
				$sciences = $Science->find(['science_type_id='.$science['science_type_id'], 'order'=>'level_id'])->toArray();
				foreach($sciences as $_t){
					if($_t['level_id'] <= $science['level_id']){
						if(!$Drop->gain($playerId, $_t['science_drop'], 1, 'addScience:'.$_t['id'])){
							//throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
				}
			}
			
			//主公装备
			$EquipSkill = new EquipSkill;
			$PlayerEquipMaster = new PlayerEquipMaster;
			$pe = $PlayerEquipMaster->getByPlayerId($playerId);
			//var_dump($pe);
			foreach($pe as $_pe){
				if($_pe['position'] < 0) continue;
				foreach($_pe['equip_skill'] as $k=>$v) {
					$equipSkill = $EquipSkill->dicGetOne($k);
					//var_dump($equipSkill);
					$buffIdArr = parseArray($equipSkill['skill_buff_id']);
					foreach($buffIdArr as $buffId) {
						$PlayerBuff->setPlayerBuff($playerId, $buffId, $v);
					}
				}
			}
		
			//commit
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}

	}

	public function sendSysMailToAllAction(){
		set_time_limit(0);
		$post = getPost();
		//$title = @$post['title'];
		$msg = @$post['msg'];
		
		$db = $this->di['db'];
		dbBegin($db);
		
		$PlayerMail = new PlayerMail;
		$PlayerMail->sendSystem(0, PlayerMail::TYPE_SYSTEM, '系統郵件', $msg);
		
		dbCommit($db);
		
		echo 'ok';
	}
}
