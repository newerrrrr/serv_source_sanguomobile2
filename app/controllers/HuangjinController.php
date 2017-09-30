<?php
/**
 * 黄巾起义 控制器
 */
class HuangjinController extends ControllerBase{
    /**
     * 开启(废弃)
     * 
     * 
     * @return <type>
     */
    public function startAction(){
		exit;
        $playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$guildId = $player['guild_id'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':guildId=' .$guildId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$GuildHuangjin = new GuildHuangjin;
			//是否加入联盟
			if(!$guildId){
				throw new Exception(10429);//请先加入联盟
			}
			
			//是否r4
			$pg = (new PlayerGuild)->getByPlayerId($playerId);
			if($pg['rank'] < 4){
				throw new Exception(10430);//需要会长或R4才可激活黄巾起义活动。
			}
			
			//是否在活动时间内
			$aml = (new AllianceMatchList)->getLastMatch(4);
			if(!$aml){
				throw new Exception(10431);//不在黄巾起义活动时间内
			}
			$round = $aml['round'];
			
			//是否超过9点
			if(time() > strtotime(date('Y-m-d 21:00:00'))){
				throw new Exception(10432);//不在黄巾起义活动时间内
			}
			
			//状态是否正确
			if(!($hj = $GuildHuangjin->findFirst(['guild_id='.$guildId]))){
				if(!$GuildHuangjin->add($guildId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$hj = $GuildHuangjin->findFirst(['guild_id='.$guildId]);
			}
			$hj = $hj->toArray();
			if($hj['round'] == $round && $hj['status']){
				throw new Exception(10433);//活动已经开始
			}
			
			//获取首座完成的联盟堡垒
			$map = (new Map)->findFirst(['map_element_id=101 and guild_id='.$guildId.' and status=1', 'order'=>'id']);
			if(!$map){
				throw new Exception(10434);//需要联盟堡垒才能激活黄巾起义活动。
			}
			$map = $map->toArray();
			
			//修改状态，初始化数据
			if(!$GuildHuangjin->reset($guildId, $round, $hj['rowversion'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取最近的山水坐标
			$mountainPos = $this->getNearestMountain($map['block_id']);
			if(!$mountainPos){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//创建第一波队列
			if($hj['history_top_wave'] >= 1){
				$needTime = 20;//(new Starting)->getValueByKey("huangjin_time_faster");
			}else{
				$needTime = 120;//(new Starting)->getValueByKey("huangjin_time");
			}
			if(!(new PlayerProjectQueue)->addQueue(1, 0, 0, PlayerProjectQueue::TYPE_HJNPCATTACK_GOTO, $needTime, 0, ['target_guild_id'=>$guildId], [])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
				
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
				
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
    }
	/*
	public function getNearestMountain($blockId){
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
					return ['x'=>$_ret[0]['x'], 'y'=>$_ret[0]['y']];
				}
				$d++;
			}
			$blockC++;
		}
		return false;
	}
	*/
}