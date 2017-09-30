<?php
/**
 * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php round_message
 */
class RoundMessageTask extends \Phalcon\CLI\Task{
    public function mainAction(){
        cli_set_process_title('php_swoole_round_message');//set process name
        $interval = 20000;
        $RoundMessage = new RoundMessage;
        swoole_timer_tick($interval, function() use ($RoundMessage){
            $msg = $RoundMessage->getRoundMessage();
            if($msg) {
                $data = [
                    'Type'=> 'round_message',
                    'Data'=> ['content'=>['data'=>$msg]]
                ];
                socketSend($data);
                log4cli($msg);
            } else {
                log4cli("no msg");
            }
        });
	}
}
