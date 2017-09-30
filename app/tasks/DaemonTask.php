<?php
/**
 * 守护进程 
 * 
 * ```php
 * php cli.php daemon run resource
 * ```
 */
class DaemonTask extends \Phalcon\CLI\Task{
    public $processTitlePrefix = "php_swoole_daemon_task_";
    public function mainAction(){
    	echo __METHOD__.PHP_EOL;
    }
    /**
     * 运行入口方法
     * @param  array  $params 
     */
    public function runAction(array $params){
    	swoole_process::daemon(true);//设置为守护进程
        $pid    = getmypid();
        $pidTxt = file_get_contents(APP_PATH.'/app/tasks/swoole/swoole_daemon_pid.txt');
        if($pidTxt) {
            $pidArr = json_decode($pidTxt, true);
        } else {
            $pidArr = [];
        }
        $method          = $params[0];
        $pidArr[$method] = $pid;
        file_put_contents(APP_PATH.'/app/tasks/swoole/swoole_daemon_pid.txt', json_encode($pidArr));

        cli_set_process_title($this->processTitlePrefix.$method);//set process name
    	$processName = "php_swoole_dispatcher_task_{$method}_father";
        //定时器
        swoole_timer_add(1000, function($interval) use ($method, $processName) {
        	$processExists = exec("ps -ef|grep {$processName}|grep -v grep");
            // echo "timer[$interval] :".date("H:i:s")." call\n"; 
        	if(empty($processExists)){
                // echo "[INFO]shell not exists.",PHP_EOL;
                //重启脚本
                exec("/usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php dispatcher run {$method}");
                echo "[INFO]success running.",PHP_EOL;
    		} else {
    			// echo "[INFO]shell exists.",PHP_EOL;
    		}
        });
    }
    /**
     * kill all daemon process
     */
    public function killAllAction(){
        $pidTxt = file_get_contents(APP_PATH.'/app/tasks/swoole/swoole_daemon_pid.txt');
        $pidArr = json_decode($pidTxt, true);
        foreach($pidArr as $method=>$pid) {
            echo "[INFO]" . $this->processTitlePrefix . $method . " killed.\n";
            swoole_process::kill($pid);
        }
        file_put_contents(APP_PATH.'/app/tasks/swoole/swoole_daemon_pid.txt', 0);

    }
}