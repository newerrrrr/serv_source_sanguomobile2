<?php
/**
 * 王战npc出发
 * 
 *
 *
 *
 */
class KingTask extends \Phalcon\CLI\Task{
    /**
     * 国王战开始初始化
     * 
     * @param <type> $param 
     * 
     * @return <type>
     */
	public function initAction($param=array()){
		$King = new King;
		if($King->findFirst(['status <'.King::STATUS_VOTED])){
			echo '前一届王战未结束'.PHP_EOL;
			return;
		}
		$lastOne = $King->findFirst(['order'=>'id desc']);
		if($lastOne){
			$lastOne = $lastOne->toArray();
			$t1 = strtotime(substr($lastOne['end_time'], 0, 10));
			$t2 = strtotime(date('Y-m-d'));
			if($t2 - $t1 < 14*24*3600){
				echo '未到开始时间'.PHP_EOL;
				return;
			}
		}
		$King->addNew();
		$KingTown = new KingTown;
		for($i=1; $i<5; $i++){
			$KingTown->resetTown($i);
		}

		
		$Map = new Map;
		$map = $Map->find(['map_element_origin_id in (16, 18, 19)'])->toArray();
		$Map->updateAll(['guild_id'=>0], ['map_element_origin_id'=>[16, 18, 19]]);
		foreach($map as $_m){
			$Map->clearMapCache($_m['x'], $_m['y']);
		}
		
		$GuildKingPoint = new GuildKingPoint;
		$GuildKingPoint->delAll();
		
		//重置官职
		$KingAppoint = new KingAppoint;
		$Drop = new Drop;
		$Player = new Player;
		$PlayerBuffTemp = new PlayerBuffTemp;
		$players = Player::find(['job <> 0'])->toArray();
		foreach($players as $_p){
			if(!(new KingAppoint)->cancelAppoint($_p)){
				echo '[player='.$_p['id'].']找不到职位';
			}
		}
		
		//清礼包
		(new KingPlayerReward)->delAll();
	}
	
    /**
     * 19点正式开始王战
     * 
     * @param <type> $param 
     * 
     * @return <type>
     */
	public function startAction($param=array()){
		//获取当前王战
		$King = new King;
		$king = $King->findFirst(['status='.King::STATUS_READY]);
		if(!$king){
			echo '['.date('Y-m-d H:i:s').']'.'当前没有王战'."\r\n";
			exit;
		}
		if(substr($king->start_time, 0, 10) != date('Y-m-d')){
			echo '['.date('Y-m-d H:i:s').']'.'时间不正确'."\r\n";
			exit;
		}
		//修改王战状态
		if(!$King->upStatus($king->id, King::STATUS_BATTLE)){
			echo '['.date('Y-m-d H:i:s').']'.'开始比赛失败[1]'."\r\n";
			exit;
		}
		
		(new PlayerBuffTemp)->up(0, 105, 7200);
		Cache::db('bufftemp')->flushDB();
	}
	
    /**
     * npc攻打城寨
     * 
     * @param <type> $param 
     * 
     * @return <type>
     */	
    public function npcStartAction($param=array()){
		//$round = $param[0];
		
		//检查当前王战状态
		$kingX = 619;
		$kingY = 619;
		$Map = new Map;
		$map = $Map->getByXy($kingX, $kingY);
		$King = new King;
		$king = $King->getCurrentBattle();
		if(!$king){
			echo '['.date('Y-m-d H:i:s').']'.'当前没有王战'."\r\n";
			exit;
		}
		
		$round = $king['round']+1;//计算当次第几批npc
		
		//检查npc是否存在，防止超出最大npc批次
		$Npc = new Npc;
		if(!$Npc->dicGetOne(20000+$round) || !$Npc->dicGetOne(30000+$round)){
			exit;
		}
		
		//BEGIN
		$db = $this->di['db'];
		dbBegin($db);
		
		//获取所有城寨
		$KingTown = new KingTown;
		$PlayerProjectQueue = new PlayerProjectQueue;
		$towns = $KingTown->find();
		$needTime = 60;//固定60秒行走时间
		//循环城寨，建立npc队列
		foreach($towns as $_town){
			$_map = $Map->getByXy($_town->x, $_town->y);
			$extraData = [
				'from_map_id' => $map['id'],
				'from_x' => $kingX,
				'from_y' => $kingY,
				'to_map_id' => $_map['id'],
				'to_x' => $_town->x,
				'to_y' => $_town->y,
			];
			//根据批次拼接npcid
			if($_town->type == KingTown::TYPE_BIG){
				$npcId = 30000+$round;
			}else{
				$npcId = 20000+$round;
			}
			
			//建立npc攻打队列
			$PlayerProjectQueue->addQueue($npcId, 0, 0, PlayerProjectQueue::TYPE_KINGNPCATTACK_GOTO, $needTime, 0, [], $extraData);
		}
		//更新批次
		$King->updateAll(['round'=>$round], ['id'=>$king['id']]);
		
		//COMMIT
		dbCommit($db);
		
		echo 'ok';
    }

	/**
	 * 结束战斗
	 */
	public function finishBattleAction($param=array()){
		$King = new King;
		$king = $King->getCurrentBattle();
		if(!$king){
			echo '['.date('Y-m-d H:i:s').']'.'当前没有王战'."\r\n";
			exit;
		}
		
		//修改王战状态
		if(!$King->upStatus($king['id'], King::STATUS_REWARD)){
			echo '['.date('Y-m-d H:i:s').']'.'更新状态失败[1]'."\r\n";
			exit;
		}

		return true;
	}

    /**
     * 战斗结算
     * 
     * 
     * @return <type>
     */
	public function battleRewardAction($param=array()){
		$Map = new Map;
		//获取当前王战
		$King = new King;
		$king = $King->getNeedRewardBattle();
		if(!$king){
			echo '['.date('Y-m-d H:i:s').']'.'当前没有王战'."\r\n";
			exit;
		}
		
		//修改王战状态
		// if(!$King->upStatus($king['id'], King::STATUS_REWARD)){
		// 	echo '['.date('Y-m-d H:i:s').']'.'更新状态失败[1]'."\r\n";
		// 	exit;
		// }
		
		//BEGIN
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$now = time();
			
			//遣返所有城寨内防守部队
			$otherPpqs = PlayerProjectQueue::find(['type in ('.PlayerProjectQueue::TYPE_KINGTOWN_DEFENCE.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCEASIST.','.PlayerProjectQueue::TYPE_KINGGATHERBATTLE_DEFENCE.') and status=1']);
			foreach($otherPpqs as $_ppq){
				//撤销原有队列
				if(!$_ppq->updateEndtime(date('Y-m-d H:i:s'))){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//把动态积分增长计算到GuildKingPoint
			(new KingTown)->calculate($king['id']);
			
			//计算排名
			$gkp = GuildKingPoint::find(['order'=>'point desc'])->toArray();
			if($gkp){
				//发奖
				$lastPoint = false;
				$guilds = [];
				$sort1 = [];//积分
				$sort2 = [];//时间
				$firstGuildId = [];
				foreach($gkp as $_k => $_gkp){
					$guilds[$_k] = $_gkp['guild_id'];
					$sort1[$_k] = $_gkp['point'];
					$sort2[$_k] = strtotime($_gkp['update_time']);//然而并没什么卵用。。。
					//获取联盟排行 todo
					
					/*if($lastPoint != $_gkp->point){
						$j++;
					}
					//todo
					
					if($j == 1){
						$firstGuildId[] = $_gkp->guild_id;
					}
					$lastPoint = $_gkp->point;*/
				}
				array_multisort($sort1, SORT_DESC, $sort2, SORT_ASC, $gkp);
				$gkp = array_values($gkp);
				$kingGuild = $gkp[0]['guild_id'];
				
				//发奖
				$j = 1;
				$KingRankReward = new KingRankReward;
				$Drop = new Drop;
				$PlayerGuild = new PlayerGuild;
				$mailType = PlayerMail::TYPE_KINGRANKGIFT;
				$mailTitle = '国王战排名奖励';
				
				//获取前三信息，给邮件显示用的
				$top3 = [];
				foreach($gkp as $_k => $_gkp){
					$_guild = (new Guild)->getGuildInfo($_gkp['guild_id']);
					$top3[$j] = ['nick'=>$_guild['name'], 'guild_short'=>''];
					$j++;
					if($j > 3)
						break;
				}
				
				//循环发奖
				$j = 1;
				foreach($gkp as $_k => $_gkp){
					//取得奖项
					$r = $KingRankReward->findFirst(['min_rank <='.$j.' and max_rank >='.$j]);
					if(!$r) continue;
					$r = $r->toArray();
					$drop = $Drop->dicGetOne($r['bonus']);
					$PlayerMail = new PlayerMail;
					//组合邮件道具
					$item = [];
					foreach($drop['drop_data'] as $_d){
						$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
					}
					$msg = '';
					$data = ['rank'=>$j, 'top3'=>$top3];
					//获取所有公会成员
					$members = $PlayerGuild->getAllGuildMember($_gkp['guild_id']);
					$playerIds = array_keys($members);
					//发送邮件
					$PlayerMail->sendSystem($playerIds, $mailType, $mailTitle, $msg, 0, $data, $item);
					$j++;
				}
				
				//king表更新胜利公会
				if(!$King->upGuild($king['id'], $kingGuild)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//map表更新王战占领公会
				$map = $Map->getByXy(619, 619);
				$map['guild_id'] = $kingGuild;
				if(!$Map->alter($map['id'], $map)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				//无人参加战斗
			}
			
			
			//修改王战状态
			if(!$King->upStatus($king['id'], King::STATUS_FINISH)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//重置map表town所属
			$towns = (new KingTown)->find()->toArray();
			foreach($towns as $_t){
				$map = $Map->getByXy($_t['x'], $_t['y']);
				$map['guild_id'] = 0;
				if(!$Map->alter($map['id'], $map)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//COMMIT
			dbCommit($db);
			$err = 0;
			//$return = true;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
			
			//$return = false;
		}
		//$this->afterCommit();
		
		echo $err."\r\n";
		return true;
	}
	/**
	 * 如果国王没有任命，默认选帮主为国王
	 * /usr/local/php/bin/php /opt/htdocs/sanguomobile2/app/cli.php king checkKing
	 */
	public function checkKingAction(){
        //获取当前王战
        $King   = new King;
        $Player = new Player;
        $king   = $King->findFirst(['order'=>'id desc']);

        if(date('Y-m-d')!=date('Y-m-d', strtotime($king->start_time)) || date('H')<23) {//必须是当天才执行
            exit("时间未到，请帮主前去任命");
        }

        if($king->guild_id==0 && $king->player_id==0) {
            $king->status = King::STATUS_VOTED;
            $king->save();
            exit("本界国王战无人参加,脚本强制结束\n");
        }
		if($king && $king->status==King::STATUS_FINISH && $king->player_id==0) {
			$guildId     = $king->guild_id;
			$rank        = PlayerGuild::RANK_R5;
			$playerGuild = PlayerGuild::findFirst(["guild_id=:guildId: and rank=:rank:", 'bind'=>['guildId'=>$guildId, 'rank'=>$rank]]);
			if($playerGuild) {
				$targetPlayerId = $playerGuild->player_id;
				//任命逻辑
		        if($King->upCurrentKing($king->id, $targetPlayerId)) {
		            $targetPlayer = $Player->getByPlayerId($targetPlayerId);
		            $Player->alter($targetPlayerId, ['job' => 1]);
		            //增加bufftemp
		            $KingAppoint = new KingAppoint;
		            $kingAppoint = $KingAppoint->dicGetOne(1);
		            $dropId      = $kingAppoint['add_buff'];
		            if($dropId){
		                (new Drop)->gain($targetPlayerId, $dropId, 1, '任命国王');
		            }
		            $data = ['Type'=>'appoint_king', 'Data'=>['king_player_id'=>$targetPlayerId,'king_nick'=>$targetPlayer['nick']]];
		            socketSend($data);
		            exit("king未任命，系统任命帮主[id={$targetPlayerId}]为king。");
		        }
			}
		}
		if($king->status==King::STATUS_VOTED) {
			exit("king已任命为 {$king->player_id}\n");
		} else {
			exit("不在king任命期间\n");
		}
	}
}
