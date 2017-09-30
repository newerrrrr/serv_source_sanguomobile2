<?php
class LogTask extends \Phalcon\CLI\Task{
    public function mainAction($argv=[]){
        $this->init();
    }
    /**
     * 连接初始化操作
     */
    public function init() {
        $sconfig = StaticData::$logConfig;
        $this->serv = new swoole_server(SWOOLE_HOST, $sconfig['port']);
        $this->serv->set($sconfig);
        $this->serv->on('Start', [$this, 'onStart']);
        $this->serv->on('Connect', [$this, 'onConnect']);
        $this->serv->on('Receive', [$this, 'onReceive']);
        $this->serv->on('Close', [$this, 'onClose']);
        $this->serv->on('Shutdown', [$this, 'onShutdown']);
        cli_set_process_title('system_log_task');//set process name
        $this->serv->start();
    }
    /**
     * 服务器启动
     * @param  swoole_server $serv 
     */
    public function onStart($serv){
    }
    /**
     * 客户端连接过来调用的函数
     * @param  swoole_server $serv    
     * @param  int $fd      
     * @param  int $from_id 
     */
    public function onConnect($serv, $fd, $from_id) {
    }
    /**
     * 从客户端接受数据包
     * @param  swoole_server $serv    
     * @param  int        $fd     
     * @param  int        $from_id 
     * @param  string        $data    
     */
    public function onReceive(swoole_server $serv, $fd, $from_id, $data) {
        if(is_string($data)) $data = trim($data);
        $tdata = unpack("I1playerId/I1len/A*data", $data);
        $playerId = $tdata['playerId'];
        if(LOG_TASK_PLAYER_ID==$playerId) {
            echo '[access_log]['.date('Y-m-d H:i:s').']';
            print_r(json_decode($tdata['data'], true));
            echo PHP_EOL;
        }
    }
    /**
     * 客户端连接关闭
     * @param  swoole_server $serv    
     * @param  int $fd      
     * @param  int $from_id 
     */
    public function onClose($serv, $fd, $from_id) {
    }
    /**
     * server关闭 kill -15 swoole主线程 # ps -ejHF|grep php
     * @param  swoole_server $serv 
     */
    public function onShutdown($serv){
    }
}
