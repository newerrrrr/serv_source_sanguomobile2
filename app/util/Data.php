<?php
/**
 * 数据回传类
 */
class Data {
    /**
     * 成功code码
     */
    const SUCCESS = 0;
    /**
     * php执行时间预警关闭
     * @var boolean
     */
    public $noExecTimeFlag = true;
    /**
     * 某人在ControlerBase中初始化，如果没有则直接返回错
     * @var integer
     */
    public $playerId = 0;
    /**
     * 通知前端的basic码
     * @var array
     */
    public $basic = [];
	
	public $extra = [];
	
	
    /**
     * commit后需要清除的缓存
     */
	public $datas = [];
	
    /**
     * 黑名单
     */
	public $blacklist = ['PlayerDrawCard', 'Map', 'PlayerCommonLog', 'PlayerCosumeLog', 'PlayerGemLog', 'PlayerOrder'];

    /**
     * 手动更改player_id
     */
    public function setPlayerId($playerId){
        $this->playerId = $playerId;
    }
    /**
     * 设置需要更改的基础数据
     * @param string $basic e.g. Player, PlayerGeneral
     */
    public function setBasic($basic){
        if(is_array($basic)) {
            $this->basic = array_unique(array_merge($this->basic, $basic));
        } else {
            $this->basic[] = $basic;
            $this->basic = array_unique($this->basic);
        }
    }
    
    /**
     * 过滤器
     * 
     * @param <type> $whiteList 
     * @param <type> $reverse  true:whiteList为黑名单；false：whiteList为白名单
     * 
     * @return <type>
     */
    public function filterBasic($whiteList=array(), $reverse = false){
        $ar = array();
        foreach($this->basic as $_basic){
			if($reverse){
				if(!in_array($_basic, $whiteList)){
					$ar[] = $_basic;
				}	
			}else{
				if(in_array($_basic, $whiteList)){
					$ar[] = $_basic;
				}			
			}
        }
        $this->basic = $ar;
    }
    /**
     * 发送错误码
     *     
     * @param  int $err error code
     * @return string      json string
     */
    public function sendErr ($err) {
        if(QA) {
            $errMsg  = '';
            $errcode = (new ErrorCode)->dicGetOne($err);
            if ($errcode) {
                $errMsg = $errcode['zhcn'];
            }
            $data = json_encode(['code' => $err, 'errMsg' => $errMsg, 'data' => [], 'basic' => []], JSON_UNESCAPED_UNICODE);
        } else {
            $data = json_encode(['code' => $err, 'data' => [], 'basic' => []], JSON_UNESCAPED_UNICODE);
        }
//        logSend($this->playerId, '错误码:'.$err);
        $this->basic = [];//清空
		$this->extra = [];
        if(isset($_POST['inner'])) {
            return $data;
        }
        $data = encodeResponseData($data);
        return $data;
    }
    /**
     * 发送数据
     *     
     * @param  array $data 
     * @param  array|bool $filter
     * @return string      json string
     */
    public function send(array $data=[], $filter = false){
        global $startTime;
        if($this->playerId) {
            $code = self::SUCCESS;
            if(is_array($filter)){
                $this->filterBasic($filter);
            }
			$this->filterBasic($this->blacklist, true);
            $this->basic = array_unique($this->basic);
            $basic       = DataController::get($this->playerId, $this->basic);
            $Player      = new Player;
            $player      = $Player->getByPlayerId($this->playerId);
            $step        = $player['step'];//加在send data里的step，用于新手引导
            $stepSet     = $player['step_set'];
            //php执行时间
            $endTime     = microtime_float();
            $subTime     = $endTime-$startTime;
            $subTime     = sprintf('执行耗时：%.3f秒', $subTime);
            $timeout     = 15;
            if(in_array($_SERVER['SERVER_ADDR'], ['10.103.252.87'])) {//指定服务器
                $timeout = 29;
            }
            if(!$this->noExecTimeFlag && $subTime>$timeout) {//超过2秒
                $this->noExecTimeFlag = false;
                $data                 = ['name' => 'postData'];
                $url                  = $_GET['_url'];
                $code                 = "[PHP-TIME：{$subTime},url={$url}]";
                $data['postData']     = getPost();
                debug($code);
                debug(json_encode($data, JSON_UNESCAPED_UNICODE));
            }
            global $peak_s;
            $peak_e = memory_get_peak_usage();
            $memUsage = $peak_e - $peak_s;
            $memUsage = convertHummanReadability($memUsage);
            $sendData    = json_encode(['code'=>$code, 'data'=>$data, 'basic'=>$basic, 'extra'=>$this->extra, 'steps'=>['step'=>$step, 'step_set'=>$stepSet],'exec_time'=>$subTime, 'mem_usage'=>$memUsage], JSON_UNESCAPED_UNICODE);
//            logSend($this->playerId, json_decode($sendData, true));
            $this->basic = [];//清空
			$this->extra = [];
            if(isset($_POST['inner'])) {
                return $sendData;
            }
            $sendData    = encodeResponseData($sendData);
            return $sendData;
        } else {
            trace();
            exit('[ERROR]not exists playerId When Send Data');
        }
    }
	/**
     * 发送原始数据       
     * @param  array  $data 
     * @return string
     */
    public function sendRaw(array $data) {
        $data        = json_encode(['code' => self::SUCCESS, 'data' => $data, 'basic' => [], 'extra'=>$this->extra]);
        $this->basic = [];//清空
		$this->extra = [];
        if(isset($_POST['inner'])) {
            return $data;
        }
        $data        = encodeResponseData($data);
        return $data;
    }
}
