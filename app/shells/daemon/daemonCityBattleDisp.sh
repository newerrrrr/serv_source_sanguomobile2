#!/bin/sh
t=`date '+%Y_%m_%d_%H_%M_%S'`
dirname="/tmp/citybattle_dispatcher_"$t
mkdir $dirname


if [ "$1" != "" ]; then
	queue=($1)
else
	queue=("back" "home" "gotoCityBattle" "attackDoor" "gotoHammer" "gotoLadder" "doneLadder" "gotoCrossbow" "gotoCatapult")
fi

for var in ${queue[@]};
do
	process=`ps -ef|grep 'php_swoole_citybattledispatcher_task_'$var'_father'|grep -v grep|awk '{print $2}'`;
	if [ "$process" == "" ]; then
		echo "start "$var;
		cp -rf /tmp/run_citybattle_$var.log $dirname
		ps -ef|grep php_swoole_citybattledispatcher > $dirname"/process.log"
		nohup /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php city_battle_dispatcher run $var  > /tmp/run_citybattle_$var.log &
	fi
done
exit;
