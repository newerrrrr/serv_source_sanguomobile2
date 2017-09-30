while [ 1 ] 
do
	process=`ps -ef|grep 'php_swoole_server_task'|grep -v grep|awk '{print $2}'`;
	if [ "$process" == "" ]; then
		break;
	fi  
ps -ef|grep 'php_swoole_server_task'|grep -v grep|awk '{print $2}'|xargs kill -15 
done; 
