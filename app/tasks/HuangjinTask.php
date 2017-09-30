<?php
/**
 * 黄巾起义
 * 
 *
 *
 *
 */
class HuangjinTask extends \Phalcon\CLI\Task{
	
	public function startAction($param=array()){
		if(AllianceMatchList::DOING != (new AllianceMatchList)->getAllianceMatchStatus(3, $aml)){
			echo '不在黄巾起义活动时间内';
			exit;
		}
		var_dump($aml);
		$round = $aml['round'];
			
		$guilds = (new Guild)->find()->toArray();
		$db = $this->di['db'];
		
		try {
			$GuildHuangjin = new GuildHuangjin;
			//$GuildHuangjin->updateAll(['status'=>0], []);
			//获取所有联盟
			foreach($guilds as $_guild){
				dbBegin($db);
				$_guildId = $_guild['id'];
				//状态是否正确
				if(!($hj = $GuildHuangjin->findFirst(['guild_id='.$_guildId]))){
					if(!$GuildHuangjin->add($_guildId)){
						echo __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__ . PHP_EOL;
						dbRollback($db);
						continue;
					}
					$hj = $GuildHuangjin->findFirst(['guild_id='.$_guildId]);
				}
				$hj = $hj->toArray();
				if($hj['round'] == $round && $hj['status']){
					echo '该联盟活动已经开始[guildId='.$_guildId.']' . PHP_EOL;
					dbRollback($db);
					continue;
				}
				
				//获取首座完成的联盟堡垒
				$map = (new Map)->findFirst(['map_element_id=101 and guild_id='.$_guildId.' and status=1', 'order'=>'id']);
				if(!$map){
					//echo '[notice]没有联盟堡垒' . PHP_EOL;
					dbRollback($db);
					continue;
				}
				$map = $map->toArray();
				
				//修改状态，初始化数据
				if(!$GuildHuangjin->reset($_guildId, $round, $hj['rowversion'])){
					echo __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__ . PHP_EOL;
					dbRollback($db);
					continue;
				}
				
				//获取最近的山水坐标
				$mountainMap = $this->getNearestMountain($map['block_id']);
				if(!$mountainMap){
					echo __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__ . PHP_EOL;
					dbRollback($db);
					continue;
				}
				
				//创建第一波队列
				if($hj['history_top_wave'] >= 1){
					$needTime = (new Starting)->getValueByKey("huangjin_time_faster");
				}else{
					$needTime = (new Starting)->getValueByKey("huangjin_time");
				}
				$extraData = [
					'from_map_id' => $mountainMap['id'],
					'from_x' => $mountainMap['x'],
					'from_y' => $mountainMap['y'],
					'to_map_id' => $map['id'],
					'to_x' => $map['x'],
					'to_y' => $map['y'],
				];
				if(!(new PlayerProjectQueue)->addQueue(1, 0, 0, PlayerProjectQueue::TYPE_HJNPCATTACK_GOTO, $needTime, 0, ['target_guild_id'=>$_guildId], $extraData)){
					echo __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__ . PHP_EOL;
					dbRollback($db);
					continue;
				}
				dbCommit($db);
			}
		
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			echo $err.PHP_EOL;
			dbRollback($db);

			//清除缓存
		}
		
		echo 'finish';
	}
	
	protected function getNearestMountain($blockId){
		$blockC = $blockId;
		$per = 103;
		$max = 0;
		while($blockC < $per*$per){
			$d = 0;
			$yi = 0;
			$ys = [];
			
			while($d < $per){
				$arBlock = [];
				while($yi <= $d){
					if($blockC - $yi*$per >= 0){
						$ys[$yi][] = $blockC - $yi*$per;
					}
					if($blockC + $yi*$per <= $per*$per){
						$ys[$yi][] = $blockC + $yi*$per;
					}
					$ys[$yi] = array_unique($ys[$yi]);
					$yi++;
				}
				
				foreach($ys as $_yi => $_blocks){
					foreach($_blocks as $_block){
						$_line = floor($_block / $per);
						if($_yi == $d){
							$xi = 0;
							while($xi <= $d){
								$__line = floor(($_block - $xi) / $per);
								//echo '_line:'.$_line.'<br>';
								if($_line == $__line && $_block - $xi >= 0){
									//echo $__f - $xi.'<br>';
									$arBlock[] = $_block - $xi;
								}
								$__line = floor(($_block + $xi) / $per);
								if($_line == $__line){
									//echo $__f + $xi.'<br>';
									$arBlock[] = $_block + $xi;
								}
								$xi++;
							}
						}else{
							$__line = floor(($_block - $d) / $per);
							if($_line == $__line && $_block - $d >= 0){
								$arBlock[] = $_block - $d;
							}
							$__line = floor(($_block + $d) / $per);
							if($_line == $__line){
								$arBlock[] = $_block + $d;
							}
						}
					}
				}
				$arBlock = array_unique($arBlock);
				//var_dump($arBlock);
				
				//$_ret = (new Map)->findFirst(["block_id in (".join(',', $arBlock).") and map_element_id=1801", 'order'=>'rand()']);
				$_ret = (new Map)->sqlGet("select * from map where block_id in (".join(',', $arBlock).") and map_element_id=1801 order by rand() limit 1");
				if($_ret){
					return $_ret[0];
				}
				$d++;
			}
			$blockC++;
		}
		return false;
	}
}
