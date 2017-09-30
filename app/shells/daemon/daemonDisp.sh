#!/bin/sh
t=`date '+%Y_%m_%d_%H_%M_%S'`
dirname="/tmp/dispatcher_"$t
mkdir $dirname


if [ "$1" != "" ]; then
	queue=($1)
else
	queue=("back" "home" "gotoCollection" "gotoCityBattle" "gotoNpcBattle" "doCollection" "gotoGather" "readyGather" "battleGather" "backMidGather" "gotoSpy" "gotoReinforce" "gotoTown" "npcGotoTown" "gotoGuildBuild" "battleBase" "gotoFetchItem" "hjnpcGotoBase")
fi

for var in ${queue[@]};
do
	process=`ps -ef|grep 'php_swoole_dispatcher_task_'$var'_father'|grep -v grep|awk '{print $2}'`;
	if [ "$process" == "" ]; then
		echo "start "$var;
		cp -rf /tmp/run_$var.log $dirname
		ps -ef|grep php_swoole_dispatcher > $dirname"/process.log"
		nohup /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php dispatcher run $var  > /tmp/run_$var.log &
	fi
done
exit;
