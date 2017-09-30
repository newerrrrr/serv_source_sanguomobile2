<?php
/**
 * 礼包刷新
 * 
 *
 *
 *
 */
class GiftTask extends \Phalcon\CLI\Task{
	public $normalId = [1004=>1 , 1005=>0];//常规礼包
	
	public function initAction($param=array()){
	}
	
	public function refreshAction($param=array()){
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		try {
		
			$Activity = new Activity;
			$ActivityExtra = new ActivityExtra;
			//循环每种，是否存在礼包配置
			$now = time();
			foreach($this->normalId as $_activityId => $_num){
				$act = $Activity->findFirst(['id='.$_activityId])->toArray();
				if(!$act){
					throw new Exception('缺少配置'.$_activityId);
				}
				$act = $Activity->parseColumn($act);
				$actex = $ActivityExtra->dicGetOne($act['id']);
				$ActivityCommodity = new ActivityCommodity;
				$ActivityCommodityExtra = new ActivityCommodityExtra;
				//if(!$ActivityCommodityExtra->find(['activity_id='.$_activityId.' and open_time<='.$now.' and close_time>='.$now])->toArray()){
					//echo 'select * from '.$ActivityCommodity->getSource().' a, '.$ActivityCommodityExtra->getSource().' b where a.activity_id='.$_activityId.' and b.open_time<='.$now.' and b.close_time>='.$now.' and a.id=b.id';
				if(!$ActivityCommodityExtra->sqlGet('select * from '.$ActivityCommodity->getSource().' a, '.$ActivityCommodityExtra->getSource().' b where a.activity_id='.$_activityId.' and b.open_time<='.$now.' and b.close_time>='.$now.' and a.id=b.id')){
					//如果没有，随机一种
					$drop = $act['drop'];

					if($act['id'] == 1005){
						$activityOrder = (new ActivityOrder)->getNext(@$actex['memo']*1);
						//更新drop
						$ActivityExtra->upMemo($act['id'], $activityOrder['id']);
						//$Activity->updateAll(['drop'=>$activityOrder['id']], ['id'=>$act['id']]);
						$drop = $activityOrder['series'];
					}else{
						shuffle($drop);
					}
					
					$i = 0;
					if(!$_num){
						$_num = count($drop);
					}
					while($i < $_num && $drop[$i]){
						//var_dump($drop[$i]);
						//$openTime = mktime(0, 0, 0, substr($act['open_date'], 4, 2), substr($act['open_date'], 6, 2), substr($act['open_date'], 0, 4));
						//$closeTime = mktime(23, 59, 59, substr($act['close_date'], 4, 2), substr($act['close_date'], 6, 2), substr($act['close_date'], 0, 4));
						$openTime = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
						$closeTs = $now+3600*24*$act['interval'];
						$closeTime = mktime(0, 0, 0, date('m', $closeTs), date('d', $closeTs), date('Y', $closeTs));
						//$ActivityCommodity->updateAll(['open_time'=>$openTime, 'close_time'=>$closeTime], ['series'=>$drop[$i]]);
						$acs = $ActivityCommodity->find(['series='.$drop[$i]])->toArray();
						//var_dump($acs);
						$acids = Set::extract('/id', $acs);
						foreach($acids as $_id){
							$ActivityCommodityExtra->updateDate($_id, $openTime, $closeTime);
						}
						$i++;
					}
				}
			}
			dbCommit($db);
		
		//commit
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			echo $msg."\r\n";
			//清除缓存
		}
		
		//删除缓存Activity,ActivityCommodity
		Cache::db(CACHEDB_STATIC)->delete('ActivityExtra');
		Cache::db(CACHEDB_STATIC)->delete('ActivityCommodityExtra');
		
		echo 'ok';
	}
}