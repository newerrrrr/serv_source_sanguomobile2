#!/bin/sh
queue=("back" "home" "gotoCollection" "gotoCityBattle" "gotoNpcBattle" "doCollection" "gotoGather" "readyGather" "battleGather" "backMidGather" "gotoSpy" "gotoReinforce" "gotoTown" "npcGotoTown" "gotoGuildBuild" "battleBase" "gotoFetchItem" "hjnpcGotoBase")

for var in ${queue[@]};
do
	nohup /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php dispatcher run $var  > /tmp/run_$var.log &
done
exit;

