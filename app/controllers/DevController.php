<?php
/**
 * 开发后台工具
 */
class DevController extends ControllerBase{
    /**
     * 此控制器不进行auth判断
     * @var boolean
     */
    public $initFlag = false;

    public function initialize() {
        //parent::initialize();
        //$this->view->setLayout('layout');
        if(!QA && !(new AdminController)->checkAuthId(101, 0)){
            echo 'please login admin <a target="blank" href="/admin/">goto</a>';
            exit;
        }
    }
    /**
     * 后台合法列表
     * @var array
     */
    // private $servAddrs = ['10.103.252.89', '10.103.252.38'];
    // /**
    //  * 服务器地址验证
    //  * @param  object $dispatcher 
    //  */
    // public function beforeExecuteRoute($dispatcher){
    //     $servAddr = $this->request->getServerAddress();
    //     if(!(strpos($servAddr, '127.')==0 || in_array($servAddr, $this->servAddrs))) {
    //         exit('illegal login');
    //     }
    // }
    /**
     * dev-qa-显示uuid hashCode
     */
    public function genJsonAction($playerId=0){
        $player = Player::findFirst($playerId);
        if(!$player) {
            $player = Player::findFirst("uuid={$playerId}");
        }
        if($player) {
            $player = $player->toArray();
            $uuid = $player['uuid'];
            $hashCode = hashMethod($uuid);
            echo '?json={"uuid":"'.$uuid.'","hashCode":"'.$hashCode.'", "login_channel ":"dev_login", "download_channel ":"dev_download","platform":"dev_platform","device_mode":"dev","system_version ":"dev"}';
            exit;
        } else {
            exit('not found');
        }
    }
    /**
     * dev-qa-显示玩家基本信息
     */
    public function showPlayerAction($playerId=0){
        if($playerId) {
            dump($playerId);
            $Player = new Player;
            $player = $Player->getByPlayerId($playerId);
            if($player) {
                dump($player);
                dump((new PlayerInfo)->getByPlayerId($playerId));
            } else {
                dump('not exists playerId:'.$playerId);
            }
        } else {
            dump('no playerId');
        }
    }
    public function searchPlayerAction($level){
        $re = Player::find(["level>={$level}", 'order'=>'level asc'])->toArray();
        foreach($re as $k=>$v) {
            echo "<tr><td>{$v['id']}</td><td>{$v['nick']}</td><td>{$v['level']}</td><td>{$v['guild_id']}</td><td><a href='javascript:;' onclick='G.showPlayer({$v['id']});'>查看</a></td></tr>";
        }
        exit;
    }
    /**
     * dev-qa开发工具页
     */
    public function indexAction(){
        $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
    }
    /**
     * dev-qa-生成新号
     */
    public function fishPlayerAction(){
        $Player = new Player;
        $uuid = 'fish-'.'test1';//uniqid();//生成的uuid
        // $uuid = 'fish-'.uniqid();//生成的uuid
        $nick = 'nick-'.uniqid();//生成的nick
        $player = $Player->newPlayer(['uuid'=>$uuid, 'avatar_id'=>1, 'nick'=>$nick, 'login_channel'=>'test', 'download_channel'=>'test','pay_channel'=>'test','platform'=>'test','device_mode'=>'test','system_version'=>'test','lang'=>'test']);

        $this->response->redirect("dev/showPlayer/".$player['id']);
    }
    /**
     * dev-qa-删除玩家
     */
    public function delPlayerAction($uuid){
        $Player = new Player;
        pr($Player->deletePlayer($uuid));
        exit;
    }
    /**
     * dev-qa-清除cache
     */
    public function clearAllCacheAction(){
        Cache::clearAllCache();
        echo 'ok',PHP_EOL;
        exit;
    }
    /**
     * dev-qa-清除玩家cache
     */
    public function clearPlayerCacheAction(){
        $playerId = $_POST['id'];
        Cache::delPlayerAll($playerId);
        exit('done');
    }

	/**
     * dev-qa-前端测试
     */
	public function testPostAction(){
        $postData = getPost();
        echo json_encode($postData);
        exit;
	}
    /**
     * 清空所有玩家武将经验 for qingqing
     */
    public function clearGeneralExpAction(){
        $PlayerGeneral = new PlayerGeneral;
        $affectedRows =  $PlayerGeneral->updateAll(['exp'=>0], ['1'=>1]);
        echo $affectedRows . '行记录被更新';
        Cache::clearAllCache();
        exit;
    }
    /**
     * dev-qa-for wangyongchao
     */
    public function testDelGuildAction(){
        $id = $_POST['id'];
        $PlayerGuild        = new PlayerGuild;
        $Player             = new Player;

        $player = $Player->getByPlayerId($id);
        $guildId = $player['guild_id'];
        if($guildId) {
            $PlayerGuild->find("player_id={$id}")->delete();
            $PlayerGuild->clearGuildCache($guildId);
            $Player->updateAll(['guild_id'=>0], ['id'=>$id]);
            $Player->clearDataCache($id);
            echo 'ok';
        } else {
            echo '无联盟';
        }
    }
    /**
     * 根据buff表生成player_buff的sql
     */
    public function generatePlayerBuffAction(){
        $re = (new Buff)->dicGetAll();
        $re = Set::sort($re, "{n}.id", "asc");

        $re1= (new PlayerBuff)->sqlGet('DESC `player_buff`');
        $re1 = Set::combine($re1, '{n}.Field', '{n}.Type');
        $sql2 = '';
        foreach($re as $k=>$v) {
            if($v['name'] && !array_key_exists($v['name'], $re1)) {
                $sql2 .= "ALTER TABLE  `player_buff` ADD  `{$v['name']}` INT( 11 ) DEFAULT  '{$v['starting_num']}' COMMENT '{$v['desc1']}';\n";
            }
        }
        echo "<pre>";
        print_r($sql2);
        echo "</pre>";

    }

    public function generateEncodeUrlAction($url){
        $data = urldecode($url);
        echo $re = encodePostData($data);
        pr(decodePostData($re));
    }
    public function testDecodeAction(){
        $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
    }

    /**
     * 战斗模拟器
     * @return [type] [description]
     */
    public function battleTestAction(){
        $aStr = $_POST['aStr'];
        $dStr = empty($_POST['dStr'])?[]:$_POST['dStr'];
        $battleType = intval($_POST['battleType']);

        if(!empty($aStr) && !empty($battleType)){
            $attackUnitList = sanguoDecodeStr($aStr);
            /*$attackUnitList = [];
            $PlayerArmy = new PlayerArmy;
            foreach($attackUnitList as $k=>$v){
                $army = $PlayerArmy->getByPositionId($k, $v);
                $attackUnitList[$k] = $army['id'];
            }*/
            $defendUnitList = empty($dStr)?0:sanguoDecodeStr($dStr);
            $Battle = new Battle;
            $re = $Battle->battleCore($attackUnitList, $defendUnitList, $battleType);
            pr($re);exit;
        }


    }

    public function clearServerCacheAction(){
        Cache::db('server')->flushDB();
        echo 'ok';
        exit;
    }
    public function clearChatCacheAction(){
        Cache::db('chat')->flushDB();
        echo 'ok';
        exit;
    }
    public function clearDispatcherCacheAction(){
        Cache::db('dispatcher')->flushDB();
        echo 'ok';
        exit;
    }

    public function testKBAction(){
        var_dump((new Battle)->battleCore([400001=>1], [500001=>2], 1));
        exit;
    }



    public function addMapElementAction(){
        $MapElement = new MapElement;
        $Map = new Map;

        $element = $MapElement->dicGetOneByOriginIdAndLevel(9, 5);
        $x = 5;
        $y = 14;
        $success = $Map->checkRandElementPosition([$x, $y]);

        if($success){
            print_r("success-".$x."-".$y."\n");
            $data = ['x'=>$x,'y'=>$y,'map_element_id'=>$element['id'],'map_element_origin_id'=>9,'resource'=>$element['max_res'],'map_element_level'=>5];
            $Map->addNew($data);
        }else{
            print_r("fail-".$x."-".$y."\n");
        }

    }
    /**
     * 更改第一个主线任务id(清空后重置)
     */
    public function changeFirstMainMissionAction(){
        $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
        if(isset($_POST['playerId']) && isset($_POST['mainId'])) {
            $playerId = $_POST['playerId'];
            $mainId = $_POST['mainId'];

            $PlayerMission = new PlayerMission;

            PlayerMission::find("player_id={$playerId}")->delete();
            $PlayerMission->clearDataCache($playerId);

            $data['mission_id']         = $mainId;
            $mission                    = (new Mission)->dicGetOneMainMission($data['mission_id']);
            $data['mission_type']       = $mission['mission_type'];
            $data['max_mission_number'] = 0;
            $data['memo']               = $mission['mission_number'];
            $data['position']           = 0;
            $re                         = $PlayerMission->addNew($playerId, $data);
            $PlayerMission->updateMissionNumber($playerId, $mission['mission_type']);//立即检测下主线任务是否完成
            $re                         = PlayerMission::findFirst($re['id'])->toArray();
            echo "ok";
            exit;
        }


    }
    /**
     * 获取在线玩家
     */
    public function getAllConnAction(){
        $data        = ['Msg'=>'DataRequest', 'Type' => 'all_conn_info', 'Data' => []];
        $re          = socketSend($data);
        $r           = json_decode($re['content'], true);
        dump('在线人数'.count($r));
        dump($r);
        exit;
    }

    public function testAppointKingAction(){
        echo <<<EOT
<a href="?g=g">点击测试</a><span style="color:red;font-size:12px;">全服发任命国王的消息[国王昵称为"测试+随机两位数]"</span><br>
EOT;
        if(isset($_GET['g'])) {
            echo "<script>alert('发送成功!');</script>";
            $data = ['Type'=>'appoint_king', 'Data'=>['king_nick'=>'测试+'.mt_rand(10, 99)]];
            socketSend($data);
        }
        exit;

    }

    public function tsAction(){
        var_dump((new Battle)->battleCore([4000091=>100], [4000092=>0], 1));
        exit;
    }

    public function dddAction(){
        $Drop = new Drop;
        $Chest = new Chest;
        
        $BModel = new ModelBase;
        $re = $BModel->sqlGet("select * from player_draw_card_bak order by id desc limit 10");
        $arr = [0,1*20,3*20,7*20,15*20,31*20,63*20,127*20,255*20];
        foreach ($re as $value) {
            echo "========{$value['create_time']} Begin=========<br>";
            $time = strlen(floor($value['open_order']));
            echo "Times:".$time."<br>";
            $co = json_decode($value['card_order'], true);
            $eff = 0;
            $t = 1;
            foreach($co as $k=>$v){
                $chest = $Chest->dicGetOne($v);
                if($chest['type']==1){
                    $drop = $Drop->dicGetOne($chest['value']);
                    $con = $Drop->getTranslateInfo($drop['drop_data']);
                    var_dump($con);
                    if($time>$k){
                        $eff += $con[0]['num']*$t;
                        $t = 0;
                    }
                }else{
                    echo "倍率x".$chest['value'];
                    if($t==1){
                        $t = $chest['value'];
                    }else{
                        $t += $chest['value'];
                    }
                }
                echo "<br>";
            }
            echo "Cost:".$arr[$time]."&nbsp;Effect:".$eff."<br>";
            echo "========{$value['create_time']} End=========<br>";
        }
    }
}

