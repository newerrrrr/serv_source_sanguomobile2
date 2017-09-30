#!/bin/sh
t=`date '+%Y_%m_%d_%H_%M_%S'`
dirname="/tmp/cross_dispatcher_"$t
if [ -e $dirname ];then
	for i in {1..20}
	do
		if [ -e $dirname"_"$i ];then
			continue;
		else
			mkdir $dirname"_"$i
			break;
		fi
	done
else
	mkdir $dirname
fi


if [ "$1" != "" ]; then
	queue=($1)
else
	queue=("back" "home" "gotoCityBattle" "attackDoor" "gotoHammer" "gotoLadder" "doneLadder" "gotoCrossbow" "gotoCatapult" "attackBase")
fi

for var in ${queue[@]};
do
	process=`ps -ef|grep 'php_swoole_crossdispatcher_task_'$var'_father'|grep -v grep|awk '{print $2}'`;
	if [ "$process" == "" ]; then
		echo "start "$var;
		cp -rf /tmp/run_cross_$var.log $dirname
		ps -ef|grep php_swoole_crossdispatcher > $dirname"/process.log"
		nohup /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php cross_dispatcher run $var  > /tmp/run_cross_$var.log &
	fi
done
exit;
