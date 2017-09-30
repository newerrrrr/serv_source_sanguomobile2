#ps -ef|grep 'php_swoole_dispatcher_task_'|grep -v grep
ps -ejHF|grep 'php_swoole_dispatcher'|grep -v grep
ps -ejHF|grep 'php_swoole_server_task'|grep -v grep
ps -ejHF|grep 'php_swoole_crossdispatcher'|grep -v grep