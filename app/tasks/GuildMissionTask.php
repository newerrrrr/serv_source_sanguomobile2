<?php
/**
 * 联盟任务发奖
 * php cli.php guild_mission donateReward
 */
class GuildMissionTask extends \Phalcon\CLI\Task {
    public function mainAction(){
    	$this->inventoryGuildScore(1);
    }
	
	public function rankRewardAction($args=null){
		//查找是否有结束且未发奖的活动
		$type = 1;
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus($type, $re) == AllianceMatchList::WAIT_REWARD){
			$this->donateReward($re);
		}
		
		$type = 2;
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus($type, $re) == AllianceMatchList::WAIT_REWARD){
			$this->hsbReward($re);
		}
		
		$type = 3;
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus($type, $re) == AllianceMatchList::WAIT_REWARD){
			$this->huangjinReward($re);
		}
		
		$type = 4;
		$AllianceMatchList = new AllianceMatchList;
		if($AllianceMatchList->getAllianceMatchStatus($type, $re) == AllianceMatchList::WAIT_REWARD){
			$this->judianReward($re);
		}
	}
	
	public function donateReward($re){
		
		echo "开始处理捐献奖励[AllianceMatchListId=".$re['id']."]》》\r\n";
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$matchType = 1;
		
		//清空排名表
		$Guild = new Guild;
		$GuildMissionRank = new GuildMissionRank;
		//$GuildMissionRank->find(['type='.$matchType])->delete();
		
		//排序所有联盟至排名表
		$GuildMissionRank->sqlExec('set @rank = 0');
		$GuildMissionRank->sqlExec('insert into '.$GuildMissionRank->getSource().' (id, round, type, rank, guild_id, name, avatar, score, create_time)  (select null, '.$re['round'].','.$matchType.', @rank:=@rank+1, c.* from (select id, name, icon_id, mission_score, now() from '.$Guild->getSource().' where mission_score > 0 order by mission_score desc, guild_power desc) c)');
		
		//获取发奖配置
		$AllianceMatch = new AllianceMatch;
		$tlm = $AllianceMatch->findFirst(['match_type='.$matchType]);
		if(!$tlm){
			echo "找不到记录AllianceMatch:type=".$matchType."\r\n";
			exit;
		}
		
		$tlm = $tlm->toArray();
		$tlm = $AllianceMatch->parseColumn($tlm);
		$dropIds = $tlm['drop_id'];
		$pointDropIds = $tlm['rank_drop_id'];
			
		$AllianceMatchPointDrop = new AllianceMatchPointDrop;
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $pointDropIds).')', 'order'=>'min_point'])->toArray();
		
		//获取排名
		$rank = $GuildMissionRank->getRankList($re['round'], $matchType);
		$rankTop3 = Set::extract('/.[rank<=3]', $rank);
		
		//获取前三信息
		$top3 = [];
		$Guild = new Guild;
		foreach($rankTop3 as $_top){
			$top3[$_top['rank']] = ['nick'=>$_top['name'], 'guild_short'=>''];
		}
		ksort($top3);
		$top3 = array_values($top3);
		
		//循环
		$pdi = 0;
		$PlayerGuild = new PlayerGuild;
		$Drop = new Drop;
		$PlayerMail = new PlayerMail;
		//发排名邮件
		foreach($rank as $_r){
			while($_r['rank'] > $pointDrop[$pdi]['max_point']*1){
				$pdi++;
				if(!isset($pointDrop[$pdi])){
					break 2;
				}
			}
			
			$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_r['guild_id']));
			
			foreach($playerIds as $_playerId){
				$drop = $Drop->rand($_playerId, [$pointDrop[$pdi]['drop']]);
				if(!$drop){
					echo "生成掉落失败，playerId=".$_playerId.";dropid=".$pointDrop[$pdi]['drop']."\r\n";
					exit;
				}
				$item = [];
				foreach($drop as $_d){
					$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
				}
				
				//发送发奖邮件
				if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONRANKGIFT, '', '', 0, ['type'=>$matchType, 'rank'=>$_r['rank'], 'top3'=>$top3], $item)){
					echo '发送邮件失败,playerId'.$_playerId;
					exit;
				}
			}
		}
		
		//获取所有联盟
		$Guild = new Guild;
		$guild = $Guild->find(['mission_score>0'])->toArray();
		
		//发档次奖励
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $dropIds).')', 'order'=>'min_point'])->toArray();
		foreach($guild as $_g){
			$flag = false;
			$_drop = [];
			foreach($pointDrop as $_p){
				//if($_p['min_point'] <= $_g['mission_score'] && $_p['max_point'] >= $_g['mission_score']){
				if($_p['min_point'] <= $_g['mission_score']){
					$flag = true;
					$_drop[] = $_p['drop'];
					//break;
				}
			}
			if($flag){
				$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_g['id']));
				
				foreach($playerIds as $_playerId){
					$drop = $Drop->rand($_playerId, $_drop);
					if(!$drop){
						echo "生成掉落失败，playerId=".$_playerId.";dropid=".join(',', $_drop)."\r\n";
						exit;
					}
					$item = [];
					foreach($drop as $_d){
						$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
					}
					
					//发送发奖邮件
					if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONSCOREGIFT, '', '', 0, ['type'=>$matchType, 'score'=>$_g['mission_score']], $item)){
						echo "发送邮件失败,playerId".$_playerId."\r\n";
						exit;
					}
				}
			}
		}
		
		//发公会礼包
		$GuildGiftPool = new GuildGiftPool;
		(new ModelBase)->sqlExec('truncate '.$GuildGiftPool->getSource());
		foreach($rank as $_r){
			if(!$this->sendGiftPool($_r['guild_id'], $re['round'], $re['type'], $_r['rank'])){
				echo '发送奖池失败,guild_id='.$_r['guild_id'];
				exit;
			}
		}
		
		//重置所有分数
		$PlayerGuild->updateAll(['guild_mission_score'=>0]);
		$Guild->updateAll(['mission_score'=>0]);
		
		//设置发奖状态成功
		(new AllianceMatchList)->finishReward($re['id']);
		
		//commit
		dbCommit($db);
		
		(new GuildGiftDistributionLog)->clearAllCache();
		echo "ok\r\n";
	}
	
	
	public function hsbReward($re){
		echo "开始处理和氏璧奖励[AllianceMatchListId=".$re['id']."]》》\r\n";
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$matchType = 2;
		
		//清空排名表
		$Guild = new Guild;
		$GuildMissionRank = new GuildMissionRank;
		//$GuildMissionRank->find(['type='.$matchType])->delete();
		
		//获取发奖配置
		$AllianceMatch = new AllianceMatch;
		$tlm = $AllianceMatch->findFirst(['match_type='.$matchType]);
		if(!$tlm){
			echo "找不到记录AllianceMatch:type=".$matchType."\r\n";
			exit;
		}
		
		$tlm = $tlm->toArray();
		$tlm = $AllianceMatch->parseColumn($tlm);
		$dropIds = $tlm['drop_id'];
		$pointDropIds = $tlm['rank_drop_id'];
		
		//更新
		$this->inventoryGuildScore($matchType);
		
			
		$AllianceMatchPointDrop = new AllianceMatchPointDrop;
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $pointDropIds).')', 'order'=>'min_point'])->toArray();
		
		//获取排名
		$rank = $GuildMissionRank->getRankList($re['round'], $matchType);
		$rankTop3 = Set::extract('/.[rank<=3]', $rank);
		
		//获取前三信息
		$top3 = [];
		$Guild = new Guild;
		foreach($rankTop3 as $_top){
			$top3[$_top['rank']] = ['nick'=>$_top['name'], 'guild_short'=>''];
		}
		ksort($top3);
		$top3 = array_values($top3);
		
		//循环
		$pdi = 0;
		$PlayerGuild = new PlayerGuild;
		$Drop = new Drop;
		$PlayerMail = new PlayerMail;
		//发排名邮件
		foreach($rank as $_r){
			while($_r['rank'] > $pointDrop[$pdi]['max_point']*1){
				$pdi++;
				if(!isset($pointDrop[$pdi])){
					break 2;
				}
			}
			
			$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_r['guild_id']));
			
			foreach($playerIds as $_playerId){
				$drop = $Drop->rand($_playerId, [$pointDrop[$pdi]['drop']]);
				if(!$drop){
					echo "生成掉落失败，playerId=".$_playerId.";dropid=".$pointDrop[$pdi]['drop']."\r\n";
					exit;
				}
				$item = [];
				foreach($drop as $_d){
					$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
				}
				
				//发送发奖邮件
				if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONRANKGIFT, '', '', 0, ['type'=>$matchType, 'rank'=>$_r['rank'], 'top3'=>$top3], $item)){
					echo "发送邮件失败,playerId".$_playerId."\r\n";
					exit;
				}
			}
		}
		
		//获取所有联盟
		$gmr = $GuildMissionRank->find(['round='.$re['round'].' and type='.$matchType])->toArray();
		
		//发档次奖励
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $dropIds).')', 'order'=>'min_point'])->toArray();
		foreach($gmr as $_g){
			$flag = false;
			$_drop = [];
			foreach($pointDrop as $_p){
				//if($_p['min_point'] <= $_g['mission_score'] && $_p['max_point'] >= $_g['mission_score']){
				if($_p['min_point'] <= $_g['score']){
					$flag = true;
					$_drop[] = $_p['drop'];
					//break;
				}
			}
			if($flag){
				$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_g['guild_id']));
				
				foreach($playerIds as $_playerId){
					$drop = $Drop->rand($_playerId, $_drop);
					if(!$drop){
						echo "生成掉落失败，playerId=".$_playerId.";dropid=".join(',', $_drop)."\r\n";
						exit;
					}
					$item = [];
					foreach($drop as $_d){
						$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
					}
					
					//发送发奖邮件
					if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONSCOREGIFT, '', '', 0, ['type'=>$matchType, 'score'=>$_g['score']], $item)){
						echo "发送邮件失败,playerId".$_playerId."\r\n";
						exit;
					}
				}
			}
		}
		
		//发公会礼包
		$GuildGiftPool = new GuildGiftPool;
		(new ModelBase)->sqlExec('truncate '.$GuildGiftPool->getSource());
		foreach($rank as $_r){
			if(!$this->sendGiftPool($_r['guild_id'], $re['round'], $re['type'], $_r['rank'])){
				echo '发送奖池失败,guild_id='.$_r['guild_id'];
				exit;
			}
		}
		
		//设置发奖状态成功
		(new AllianceMatchList)->finishReward($re['id']);
		//(new AllianceMatchList)->updateAll(['calc_status='.AllianceMatchList::NO_ACTIVITY], ['id'=>$re['id']]);
		
		(new ActivityTask)->startAction();
		
		//commit
		dbCommit($db);
		
		(new GuildGiftDistributionLog)->clearAllCache();
		echo "ok\r\n";
	}
	
	public function huangjinReward($re){
		
		echo "开始处理黄巾起义奖励[AllianceMatchListId=".$re['id']."]》》\r\n";
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$matchType = 3;
		
		//清空排名表
		$Guild = new Guild;
		$GuildMissionRank = new GuildMissionRank;
		//$GuildMissionRank->find(['type='.$matchType])->delete();
		$GuildHuangjin = new GuildHuangjin;
		$HuangjinAttackMob = new HuangjinAttackMob;
		$hjam = $HuangjinAttackMob->dicGetAll();
		$PlayerGuild = new PlayerGuild;
		$Drop = new Drop;
		$PlayerMail = new PlayerMail;
		
		//波次奖励
		$ret = $GuildHuangjin->sqlGet('select * from '.$GuildHuangjin->getSource().' where status=2 and score>0 and round='.$re['round']);
		foreach($ret as $_r){
			//获取奖励波次
			$_wave = max($_r['last_wave']-1, $_r['last_win_wave']);
			$_drop = $hjam[$_wave]['drop'];
			$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_r['guild_id']));
				
			foreach($playerIds as $_playerId){
				$drop = $Drop->rand($_playerId, [$_drop]);
				if(!$drop){
					echo "生成掉落失败，playerId=".$_playerId.";dropid=".join(',', $_drop)."\r\n";
					exit;
				}
				$item = [];
				foreach($drop as $_d){
					$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
				}
				
				//发送发奖邮件
				if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_HUANGJINWAVEGIFT, '', '', 0, ['wave'=>$_wave], $item)){
					echo "发送邮件失败,playerId".$_playerId."\r\n";
					exit;
				}
			}
		}
		
		//排序所有联盟至排名表
		$GuildMissionRank->sqlExec('set @rank = 0');
		$GuildMissionRank->sqlExec('insert into '.$GuildMissionRank->getSource().' (id, round, type, rank, guild_id, name, avatar, score, create_time)  (select null, '.$re['round'].','.$matchType.', @rank:=@rank+1, c.* from (select guild_id, name, icon_id, score, now() from '.$GuildHuangjin->getSource().' a, '.$Guild->getSource().' b where a.round = '.$re['round'].' and a.score > 0 and a.status = 2 and a.guild_id=b.id order by score desc, top_wave desc, last_win_wave desc, a.update_time asc) c)');
		
		//获取发奖配置
		$AllianceMatch = new AllianceMatch;
		$tlm = $AllianceMatch->findFirst(['match_type='.$matchType]);
		if(!$tlm){
			echo "找不到记录AllianceMatch:type=".$matchType."\r\n";
			exit;
		}
		
		$tlm = $tlm->toArray();
		$tlm = $AllianceMatch->parseColumn($tlm);
		$dropIds = $tlm['drop_id'];
		$pointDropIds = $tlm['rank_drop_id'];
			
		$AllianceMatchPointDrop = new AllianceMatchPointDrop;
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $pointDropIds).')', 'order'=>'min_point'])->toArray();
		
		//获取排名
		$rank = $GuildMissionRank->getRankList($re['round'], $matchType);
		$rankTop3 = Set::extract('/.[rank<=3]', $rank);
		
		//获取前三信息
		$top3 = [];
		$Guild = new Guild;
		foreach($rankTop3 as $_top){
			$top3[$_top['rank']] = ['nick'=>$_top['name'], 'guild_short'=>''];
		}
		ksort($top3);
		$top3 = array_values($top3);
		
		//循环
		$pdi = 0;
		$PlayerMail = new PlayerMail;
		$Drop = new Drop;
		//发排名邮件
		foreach($rank as $_r){
			while($_r['rank'] > $pointDrop[$pdi]['max_point']*1){
				$pdi++;
				if(!isset($pointDrop[$pdi])){
					break 2;
				}
			}
			
			$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_r['guild_id']));
			
			foreach($playerIds as $_playerId){
				$drop = $Drop->rand($_playerId, [$pointDrop[$pdi]['drop']]);
				if(!$drop){
					echo "生成掉落失败，playerId=".$_playerId.";dropid=".$pointDrop[$pdi]['drop']."\r\n";
					exit;
				}
				$item = [];
				foreach($drop as $_d){
					$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
				}
				
				//发送发奖邮件
				if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONRANKGIFT, '', '', 0, ['type'=>$matchType, 'rank'=>$_r['rank'], 'top3'=>$top3], $item)){
					echo '发送邮件失败,playerId'.$_playerId;
					exit;
				}
			}
		}
		
		//获取所有联盟
		$Guild = new Guild;
		$Drop = new Drop;
		$ret = $GuildHuangjin->find(['round = '.$re['round'].' and score > 0 and status = 2'])->toArray();
		
		//发档次奖励
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $dropIds).')', 'order'=>'min_point'])->toArray();
		foreach($ret as $_g){
			$flag = false;
			$_drop = [];
			foreach($pointDrop as $_p){
				//if($_p['min_point'] <= $_g['mission_score'] && $_p['max_point'] >= $_g['mission_score']){
				if($_p['min_point'] <= $_g['score']){
					$flag = true;
					$_drop[] = $_p['drop'];
					//break;
				}
			}
			if($flag){
				$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_g['guild_id']));
				
				foreach($playerIds as $_playerId){
					$drop = $Drop->rand($_playerId, $_drop);
					if(!$drop){
						echo "生成掉落失败，playerId=".$_playerId.";dropid=".join(',', $_drop)."\r\n";
						exit;
					}
					$item = [];
					foreach($drop as $_d){
						$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
					}
					
					//发送发奖邮件
					if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONSCOREGIFT, '', '', 0, ['type'=>$matchType, 'score'=>$_g['score']], $item)){
						echo "发送邮件失败,playerId".$_playerId."\r\n";
						exit;
					}
				}
			}
		}
		
		//发公会礼包
		$GuildGiftPool = new GuildGiftPool;
		(new ModelBase)->sqlExec('truncate '.$GuildGiftPool->getSource());
		foreach($rank as $_r){
			if(!$this->sendGiftPool($_r['guild_id'], $re['round'], $re['type'], $_r['rank'])){
				echo '发送奖池失败,guild_id='.$_r['guild_id'];
				exit;
			}
		}
		
		//设置发奖状态成功
		(new AllianceMatchList)->finishReward($re['id']);
		
		//commit
		dbCommit($db);
		
		(new GuildGiftDistributionLog)->clearAllCache();
		echo "ok\r\n";
	}
	
	public function judianReward($re){
		echo "开始处理据点战奖励[AllianceMatchListId=".$re['id']."]》》\r\n";
		//begin
		$db = $this->di['db'];
		dbBegin($db);
		
		$matchType = 4;
		
		//清空排名表
		$Guild = new Guild;
		$GuildMissionRank = new GuildMissionRank;
		//$GuildMissionRank->find(['type='.$matchType])->delete();
		
		//获取发奖配置
		$AllianceMatch = new AllianceMatch;
		$tlm = $AllianceMatch->findFirst(['match_type='.$matchType]);
		if(!$tlm){
			echo "找不到记录AllianceMatch:type=".$matchType."\r\n";
			exit;
		}
		
		$tlm = $tlm->toArray();
		$tlm = $AllianceMatch->parseColumn($tlm);
		$dropIds = $tlm['drop_id'];
		$pointDropIds = $tlm['rank_drop_id'];
		
		//更新
		$this->inventoryGuildScore(3);
		
			
		$AllianceMatchPointDrop = new AllianceMatchPointDrop;
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $pointDropIds).')', 'order'=>'min_point'])->toArray();
		
		//获取排名
		$rank = $GuildMissionRank->getRankList($re['round'], $matchType);
		$rankTop3 = Set::extract('/.[rank<=3]', $rank);
		
		//获取前三信息
		$top3 = [];
		$Guild = new Guild;
		foreach($rankTop3 as $_top){
			$top3[$_top['rank']] = ['nick'=>$_top['name'], 'guild_short'=>''];
		}
		ksort($top3);
		$top3 = array_values($top3);
		
		//循环
		$pdi = 0;
		$PlayerGuild = new PlayerGuild;
		$Drop = new Drop;
		$PlayerMail = new PlayerMail;
		//发排名邮件
		foreach($rank as $_r){
			while($_r['rank'] > $pointDrop[$pdi]['max_point']*1){
				$pdi++;
				if(!isset($pointDrop[$pdi])){
					break 2;
				}
			}
			
			$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_r['guild_id']));
			
			foreach($playerIds as $_playerId){
				$drop = $Drop->rand($_playerId, [$pointDrop[$pdi]['drop']]);
				if(!$drop){
					echo "生成掉落失败，playerId=".$_playerId.";dropid=".$pointDrop[$pdi]['drop']."\r\n";
					exit;
				}
				$item = [];
				foreach($drop as $_d){
					$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
				}
				
				//发送发奖邮件
				if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONRANKGIFT, '', '', 0, ['type'=>$matchType, 'rank'=>$_r['rank'], 'top3'=>$top3], $item)){
					echo "发送邮件失败,playerId".$_playerId."\r\n";
					exit;
				}
			}
		}
		
		//获取所有联盟
		$gmr = $GuildMissionRank->find(['round='.$re['round'].' and type='.$matchType])->toArray();
		
		//发档次奖励
		$pointDrop = $AllianceMatchPointDrop->find(['id in ('.join(',', $dropIds).')', 'order'=>'min_point'])->toArray();
		foreach($gmr as $_g){
			$flag = false;
			$_drop = [];
			foreach($pointDrop as $_p){
				//if($_p['min_point'] <= $_g['mission_score'] && $_p['max_point'] >= $_g['mission_score']){
				if($_p['min_point'] <= $_g['score']){
					$flag = true;
					$_drop[] = $_p['drop'];
					//break;
				}
			}
			if($flag){
				$playerIds = Set::extract('/player_id', $PlayerGuild->getAllGuildMember($_g['guild_id']));
				
				foreach($playerIds as $_playerId){
					$drop = $Drop->rand($_playerId, $_drop);
					if(!$drop){
						echo "生成掉落失败，playerId=".$_playerId.";dropid=".join(',', $_drop)."\r\n";
						exit;
					}
					$item = [];
					foreach($drop as $_d){
						$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
					}
					
					//发送发奖邮件
					if(!$PlayerMail->sendSystem($_playerId, PlayerMail::TYPE_GUILDMISSIONSCOREGIFT, '', '', 0, ['type'=>$matchType, 'score'=>$_g['score']], $item)){
						echo "发送邮件失败,playerId".$_playerId."\r\n";
						exit;
					}
				}
			}
		}
		
		//发公会礼包
		$GuildGiftPool = new GuildGiftPool;
		(new ModelBase)->sqlExec('truncate '.$GuildGiftPool->getSource());
		foreach($rank as $_r){
			if(!$this->sendGiftPool($_r['guild_id'], $re['round'], $re['type'], $_r['rank'])){
				echo '发送奖池失败,guild_id='.$_r['guild_id'];
				exit;
			}
		}
		
		//设置发奖状态成功
		(new AllianceMatchList)->finishReward($re['id']);
		//(new AllianceMatchList)->updateAll(['calc_status='.AllianceMatchList::NO_ACTIVITY], ['id'=>$re['id']]);
		
		//commit
		dbCommit($db);
		
		(new GuildGiftDistributionLog)->clearAllCache();
		echo "ok\r\n";
	}
	
	public function sendGiftPool($guildId, $round, $type, $rank){
		$GuildGiftPool = new GuildGiftPool;
		$amcd = (new AllianceMatchChestDrop)->getByRank($rank);
		foreach($amcd as $_a){
			if(!$GuildGiftPool->addNew($guildId, $_a['gift'], $round, $type, $_a['num'])){
				return false;
			}
		}
		return true;
	}

	/**
	 * 清算公会比赛分数
	 * @param  $type 1 正常结算 2 和氏璧活动结束后计算 3 据点活动结束后计算
	 * @return [type] [description]
	 */
	public function inventoryGuildScore($type=1){
		$AllianceMatchList = new AllianceMatchList;
		$re = $AllianceMatchList->getAllianceMatchStatus(2, $aml);
		$re2 = $AllianceMatchList->getAllianceMatchStatus(4, $aml2);
		if($type==2 || $re==AllianceMatchList::DOING){
			$sqlStr = "select guild_id, sum(`hsb`) from player where guild_id>0 and hsb>0 group by guild_id";

			global $config;
			$mysqli = @new mysqli($config->database->host, $config->database->username, $config->database->password, $config->database->dbname);
			if(mysqli_connect_errno()){
			    echo "ERROR:".mysqli_connect_error();
			    exit;
			}
			$re = $mysqli->query($sqlStr);
			if($type==1){
				$s = 10;
			}else{
				$s = 120;
			}
			$GuildMissionRank = new GuildMissionRank;
			while($row = $re->fetch_row()){
				$GuildMissionRank->addScore($aml['round'], 2, $row[0], $row[1]*$s);
			}

			$sqlStr2 = "update guild_mission_rank, (select guild_mission_rank.id, @rank:=@rank+1 rank from guild_mission_rank ,(select @rank:=0) r where guild_mission_rank.round={$aml['round']} and guild_mission_rank.type=2 order by score desc) m set guild_mission_rank.rank=m.rank where guild_mission_rank.id=m.id and guild_mission_rank.type=2 and guild_mission_rank.round={$aml['round']};";
			$re = $mysqli->query($sqlStr2);

			if($type==2){
				$sqlStr3 = "update player set hsb=0;";
				$re = $mysqli->query($sqlStr3);
				Cache::clearPlayerCache();
			}
		}elseif($type==3 || $re2==AllianceMatchList::DOING){
			global $config;
			$mysqli = @new mysqli($config->database->host, $config->database->username, $config->database->password, $config->database->dbname);
			if($type!=3){
				$sqlStr = "select guild_id, count(*) from map where map_element_origin_id=22 and guild_id>0 group by guild_id";

				if(mysqli_connect_errno()){
				    echo "ERROR:".mysqli_connect_error();
				    exit;
				}
				$re = $mysqli->query($sqlStr);
				$s = 50;
				$GuildMissionRank = new GuildMissionRank;
				while($row = $re->fetch_row()){
					$GuildMissionRank->addScore($aml2['round'], 4, $row[0], $row[1]*$s);
				}
			}

			$sqlStr2 = "update guild_mission_rank, (select guild_mission_rank.id, @rank:=@rank+1 rank from guild_mission_rank ,(select @rank:=0) r where guild_mission_rank.round={$aml2['round']} and guild_mission_rank.type=4 order by score desc) m set guild_mission_rank.rank=m.rank where guild_mission_rank.id=m.id and guild_mission_rank.type=4 and guild_mission_rank.round={$aml2['round']};";
			$re = $mysqli->query($sqlStr2);
		}
	}
}