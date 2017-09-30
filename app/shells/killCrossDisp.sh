while [ 1 ]
do
	process=`ps -ef|grep 'php_swoole_crossdispatcher_task_'|grep -v grep|grep father|awk '{print $2}'`;
	if [ "$process" == "" ]; then
		break;
	fi
	ps -ef|grep 'php_swoole_crossdispatcher_task_'|grep -v grep|awk '{print $2}'|xargs kill -15
done; 
