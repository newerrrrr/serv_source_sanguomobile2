t=`date '+%Y_%m_%d_%H_%M_%S'`
dirname="/tmp/dispatcher_"$t
mkdir $dirname
cp -rf /tmp/run_* $dirname
ps -ef|grep php_swoole_dispatcher > $dirname"/process.log"

while [ 1 ]
do
	process=`ps -ef|grep 'php_swoole_dispatcher_task_'|grep -v grep|grep father|awk '{print $2}'`;
	if [ "$process" == "" ]; then
		break;
	fi
	ps -ef|grep 'php_swoole_dispatcher_task_'|grep -v grep|awk '{print $2}'|xargs kill -15
done; 
