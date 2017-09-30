<?php
/**
 * 修bug
 */
class FixbugTask extends \Phalcon\CLI\Task
{
    public function fixArmyNumAction() {
    	$PlayerArmy = new PlayerArmy;
		$PlayerArmyUnit = new PlayerArmyUnit;
		$PlayerGeneral = new PlayerGeneral;
		$PlayerSoldier = new PlayerSoldier;
		$Player = new Player;
		$playerId = 0;
		$busy = [];

		$db = $this->di['db'];
		/*
		select player_id, position, total from player_army d left join (
	select c.id, a.corps_in_control+if(b.buff_num is null, 0, b.buff_num)+if(c.vip_level >=6, 1, 0)+1 as total from (player c left join (select player_id, sum(buff_num)*1 as buff_num from player_buff_temp where  buff_id=361 group by player_id) b on c.id=b.player_id) left join player_buff a on c.id=a.player_id
) e on d.player_id=e.id
 where position > total
		*/
		
		while($pa = $PlayerArmy->sqlGet('select * from player_army where player_id>'.$playerId.' order by player_id asc, position desc limit 1')){
			$pa = $pa[0];
			//获取玩家最大军团数
			$maxArmyNum = $Player->getMaxArmyNum($pa['player_id']);
			
			//如果检查是否超过上限
			if($pa['position'] <= $maxArmyNum)
				goto ccc;
			
			//检查军团状态
			if($pa['status']){
				echo $pa['player_id']." busy\r\n";
				$busy[] = $pa['player_id'];
				goto ccc;
			}
			
			
			dbBegin($db);
			//$PlayerArmy->sqlGet('select * from player_army where id='.$pa['id'].' for update');
			//解散军团
			$pau = $PlayerArmyUnit->sqlGet('select * from player_army_unit where player_id='.$pa['player_id'].' and army_id='.$pa['id']);
			if($pau){
				foreach($pau as $_pau){
					if($_pau['general_id']){
						$_playerGeneral = $PlayerGeneral->getByGeneralId($pa['player_id'], $_pau['general_id']);
						if($_playerGeneral){
							if(!$PlayerGeneral->assign($_playerGeneral)->updateArmy(0)){
								dbRollback($db);
								continue;
							}
						}
					}
					if($_pau['soldier_id'] && $_pau['soldier_num']){//归还空闲士兵
						if(!$PlayerSoldier->updateSoldierNum($pa['player_id'], $_pau['soldier_id'], $_pau['soldier_num'])){
							dbRollback($db);
							continue;
						}
					}
					$PlayerArmyUnit->assign($_pau)->delete();
				}
			}
			$PlayerArmyUnit->_clearDataCache($pa['player_id']);
			
			//删除army
			$PlayerArmy->sqlExec('delete from player_army where id='.$pa['id']);
			$PlayerArmy->clearDataCache($pa['player_id']);
			
			dbCommit($db);
			
			echo $pa['player_id']." ".$pa['id']." ok\r\n";

			ccc:
			$playerId = $pa['player_id'];
		}
		echo "---------------------------\r\n";
		echo "busy playerId:\r\n";
		foreach($busy as $_b){
			echo $_b."\r\n";
		}
    	echo "ok\n";
    }

	//武将天赋计算
	public function generalBuffAction(){
		$ModelBase = new ModelBase;
		$PlayerGeneralBuff = new PlayerGeneralBuff;
		//找到最后一条处理playerId
		$ret = $ModelBase->sqlGet('select max(id) from player_general_buff');
		if($ret && $ret[0]['max(id)']){
			$maxPlayerId = $ModelBase->sqlGet('select player_id from player_general_buff where id='.$ret[0]['max(id)'])[0]['player_id'];
		}else{
			$maxPlayerId = 0;
		}
		
		echo 'begin playerId:'.$maxPlayerId.PHP_EOL;
		
		//解析所有武将天赋
		$generals = (new General)->getAllByOriginId();
		$Buff = new Buff;
		foreach($generals as &$_general){
			$_buff = [];
			foreach($_general['general_talent_buff_id'] as $_buffId){
				$_buff[] = $Buff->dicGetOne($_buffId)['name'];
			}
			$_general['general_talent_buff_id'] = $_buff;
		}
		unset($_general);
		
		$limit = 10;
		//循环查找待处理的playerIds
		$db = $this->di['db'];
		while($ret = $ModelBase->sqlGet('select id from player where id>'.$maxPlayerId.' order by id limit '.$limit)){
			
			//循环playerIds
			foreach($ret as $_ret){
				$_playerId = $_ret['id'];
				//begin
				dbBegin($db);
				
				//查找所有武将
				$_generals = $ModelBase->sqlGet('select general_id,star_lv from player_general where player_id='.$_playerId);
				
				//循环武将
				$_buff = [];
				foreach($_generals as $_general){
					$_generalId = $_general['general_id'];
					if($generals[$_generalId]["general_talent_value"] === ''){
						echo '[error]general_id:'.$_generalId.PHP_EOL;
						continue;
					}
					$_buffIds = $generals[$_generalId]['general_talent_buff_id'];
					$star = $_general['star_lv'];
					eval('$_buffValue = '.$generals[$_generalId]["general_talent_value"].';');
					foreach($_buffIds as $_buffId){
						@$_buff[$_buffId] += $_buffValue;
					}
				}
				
				//增加buff
				$PlayerGeneralBuff->newPlayerBuff($_playerId, $_buff);
				
				//commit
				dbCommit($db);
				echo 'playerId:'.$_playerId.PHP_EOL;
			}
			$maxPlayerId = $_playerId;
		}
		//刷新缓存
		Cache::db('cache')->flushDB();
		
		echo 'finish!'.PHP_EOL;
	}

    /**
     * 将魂转换
     * 
     * 
     * @return <type>
     */
	public function transferSoulAction(){
		$ModelBase = new ModelBase;
		$PlayerItem = new PlayerItem;
		$General = new General;
		$Player = new Player;
		$PlayerCommonLog = new PlayerCommonLog;
		$generals = $General->getAllByOriginId();
	
		//查找满级武将
		$id = 0;
		$count = 0;
		$db = $this->di['db'];
		while($ret = $ModelBase->sqlGet('select * from player_general where id>'.$id.' and star_lv=15')){
			//循环
			foreach($ret as $_r){
				$generalId = $_r['general_id'];
				$playerId = $_r['player_id'];
				
				//获取对应将魂
				$itemId = $generals[$generalId]['general_item_soul'];
				
				//获取将魂数量
				$pi = $ModelBase->sqlGet('select num from player_item where player_id='.$playerId.' and item_id='.$itemId);
				
				if($pi and $pi[0]['num']>0){
					$pi = $pi[0];
					//begin
					dbBegin($db);
					
					//删除将魂
					if(!$PlayerItem->drop($playerId, $itemId, $pi['num'])){
						echo 'error1 playerId:'.$playerId.',general='.$generalId.PHP_EOL; 
						dbRollback($db);
						continue;
					}
					
					//增加将印
					if(!$Player->addJiangyinNum($playerId, $pi['num'])){
						echo 'error2 playerId:'.$playerId.',general='.$generalId.PHP_EOL; 
						dbRollback($db);
						continue;
					}
					
					$PlayerCommonLog->add($playerId, ['type'=>'脚本将印转换', 'memo'=>['soulItemId'=>$itemId,'num'=>$pi['num']]]);//日志
					
					//end
					$count++;
					dbCommit($db);
					//echo 'success playerId:'.$playerId.',general='.$generalId.PHP_EOL; 
				}
			}
			$id = $_r['id'];
		}
		
		echo 'finish:'.$count;
	}
}