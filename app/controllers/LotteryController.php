<?php
/**
 * 玩家控制器
 */
class LotteryController extends ControllerBase{

	/**
	 * 新玩家7日抽奖
	 * 
	 * ```php
	 * /Lottery/quickMoney/
     * postData: json={}
     * return: json{"gem_num":""}
     * 
	 */
	public function quickMoneyAction(){
        exit;
		$playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();

        //锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

        if($player['create_time']<=time()-3600*24*7){
        	$err = 10391;//超过7天无法抽奖
        	goto SendErr;
        }
        
        
        $PlayerQuickMoney = new PlayerQuickMoney;

        $count = $PlayerQuickMoney->countByPlayer($playerId);

        if($count>=10){
        	$err = 10392;//超过最高抽奖次数
        	goto SendErr;
        }

        if(!$err){
        	$QuickMoney = new QuickMoney;
        	$Cost = new Cost;
        	$qm = $QuickMoney->dicGetOne($count+1);
        	
        	if($Cost->updatePlayer($playerId, $qm['cost_id'], $count+1)){
        		$Drop = new Drop;
        		$drop = $Drop->gain($playerId, [$qm['drop']], 1, '新玩家7日抽奖');
        		$gemNum = $drop[0]['num'];
        		$PlayerQuickMoney->createRecord($playerId, $gemNum);
                $err = 0;
        	}else{
        		$err = 10393;//元宝不足
        		goto SendErr;
        	}
        }
        SendErr: Cache::unlock($lockKey);
        if(!$err){
			echo $this->data->send(['gem_num'=>$gemNum]);
		}else{
			echo $this->data->sendErr($err);
		}

	}

	/**
	 * 新玩家7日抽奖已抽取次数
	 * 
	 * ```php
	 * /Lottery/checkQuickMoneyTimes/
     * postData: json={}
     * return: json{"times":""，"end_time":""}
     * 
	 */
	public function checkQuickMoneyTimesAction(){
		$playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $PlayerQuickMoney = new PlayerQuickMoney;
        $count = $PlayerQuickMoney->countByPlayer($playerId);
        echo $this->data->send(['times'=>$count, 'end_time'=>$player['create_time']]);
	}

	/**
	 * 玩家皇陵探宝的信息
	 * 
	 * ```php
	 * /Lottery/checkPlayerLotteryInfo/
     * postData: json={}
     * return: json{"basicInfo":""，"drawInfo":""}
     * 
	 */
	public function checkPlayerLotteryInfoAction(){
		$playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $PlayerLotteryInfo = new PlayerLotteryInfo;
		$pl = $PlayerLotteryInfo->getByPlayerId($playerId);
		
		if($pl['draw_card_id']>0){
			$PlayerDrawCard = new PlayerDrawCard;
			$re = $PlayerDrawCard->getByPlayerId($playerId);
			$cOrder = json_decode($re['card_order'], true);
			$oOrder = str_split($re['open_order']);
			$dc = ['is_start'=>$re['is_start'], 'chest_type_id'=>$re['chest_type_id'], 'data'=>[]];
			for($i=0;$i<9;$i++){
				if(!empty($oOrder[$i])){
					$dc['data'][$oOrder[$i]] = $cOrder[$i];
				}
			}
		}else{
			$dc = [];
		}
		
		$result = ['PlayerLotteryInfo'=>$pl, 'PlayerDrawCard'=>$dc];	
        echo $this->data->send($result);
	}

	/**
	 * 玩家皇陵探宝投骰子
	 * 
	 * ```php
	 * /Lottery/playerGo/
     * postData: json={}
     * return: json{"randP":""，"drop":""}
     * 
	 */
	public function playerGoAction(){
		$playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();

        //锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

        $PlayerLotteryInfo = new PlayerLotteryInfo;
		$pl = $PlayerLotteryInfo->getByPlayerId($playerId);

		if(!$PlayerLotteryInfo->useFreeTimes($playerId) && !$PlayerLotteryInfo->updateCoin($playerId, -1000)){
			$err = 10394;//硬币不够
        	goto SendErr;
		}
        $fTFlag = false;
        $p = mt_rand(1,6);
        switch ($pl['current_position']) {
        	case 3:
        		if($p>2){
        			$endPosition = $pl['current_position']+$p+4;
        		}else{
        			$endPosition = 14+$p;
        		}
        		break;
        	case 15:
        	case 16:
        		$endPosition = $pl['current_position']+$p;
        		if($endPosition>16){
        			$endPosition -= 7;
        			if($endPosition>=15){
        				$endPosition -= 14;
                        $fTFlag = true;
        			}
        		}
        		break;
        	default:
        		$endPosition = $pl['current_position']+$p;
        		if($endPosition>=15){
        			$endPosition -= 14;
                    $fTFlag = true;
        		}
        		break;
        }
        $Wheel = new Wheel;
        $PlayerBuild = new PlayerBuild;
        $levelId = $PlayerBuild->getPlayerCastleLevel($playerId);
        $reward = $Wheel->getByGridAndLv($levelId, $endPosition);

        $PlayerLotteryInfo->go($playerId, $endPosition);
        if($fTFlag){
            $PlayerLotteryInfo->addFreeTimes($playerId);
        }

        if(empty($reward)){
        	$err = 10395;//奖励不存在
        	goto SendErr;
        }elseif($reward['type']==1){
        	$PlayerDrawCard = new PlayerDrawCard;
        	$cId = $PlayerDrawCard->beginDrawCard($playerId);
        	$drop = ['drawCardTypeId'=>$cId];
            $err = 0;
        }else{
        	$Drop = new Drop;
        	$drop = $Drop->gain($playerId, [$reward['drop']], 1, '玩家皇陵探宝投骰子');
            $err = 0;
        }



        SendErr: Cache::unlock($lockKey);
        if(!$err){
			echo $this->data->send(['randP'=>$p, 'endPosition'=>$endPosition, 'drop'=>$drop]);
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 开始皇陵探宝翻牌
     * 
     * ```php
     * /Lottery/startDrawCard/
     * postData: json={}
     * return: json{}
     * 
     */
    public function startDrawCardAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
         //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);

        $err = 0;

        $PlayerDrawCard = new PlayerDrawCard;
        $pd = $PlayerDrawCard->getByPlayerId($playerId);
        if(!$pd){
            $err = 10396;//该局不存在
            goto SendErr;
        }

        $PlayerDrawCard->startDrawCard($playerId);

        SendErr: Cache::unlock($lockKey);
        if(!$err){
            echo $this->data->send();
        }else{
            echo $this->data->sendErr($err);
        }
    }

	/**
	 * 玩家皇陵探宝翻牌
	 * 
	 * ```php
	 * /Lottery/playerDraw/
     * postData: json={}
     * return: json{}
     * 
	 */
	public function playerDrawAction(){
		$playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();

	    $postData = getPost();
		$position = $postData['position'];

        //锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

        $err = 0;

		$PlayerDrawCard = new PlayerDrawCard;
        $pd = $PlayerDrawCard->getByPlayerId($playerId);
        if(!$pd){
        	$err = 10397;//该局不存在
        	goto SendErr;
        }
        if($pd['open_order']==0){
            $oOrder = str_split($pd['open_order']);
        	$count = 0;
        }else{
        	$oOrder = str_split($pd['open_order']);
			$count = count($oOrder);
        }

        if($count>=9 || in_array($position, $oOrder)){
        	$err = 10398;//打开的位置错误
        	goto SendErr;
        }
		
        $costJadeNum = $count>0?pow(2, $count-1):0;
        if($costJadeNum>0){
            $PlayerLotteryInfo = new PlayerLotteryInfo;
            if(!$PlayerLotteryInfo->updateJade($playerId, $costJadeNum*(-1))){
                $err = 10399;//勾玉不够
                goto SendErr;
            }
        }
       
        $times = $PlayerDrawCard->getTimes($playerId);


        $PlayerDrawCard->openPosition($playerId, $position);

        $Chest = new Chest;
        $cOrder = json_decode($pd['card_order'], true);
        $rewardId = $cOrder[$count];

        $chest = $Chest->dicGetOne($rewardId);
        

        if($chest['type']==1){
            if($times>1){
                $dropIdArr = array_fill(0, $times, $chest['value']);
            }else{
                $dropIdArr = [$chest['value']]; 
            }
            $Drop = new Drop;
        	$Drop->gain($playerId, $dropIdArr, 1, '玩家皇陵探宝翻牌');
            //世界聊天 聊天推送
            if($times>=5 && $chest['type']==1) {
                $userData = [
                    'type'        => 4,
                    'times'       => $times,
                    'drop'        => intval($chest['value'])
                ];
                $data = ['Type'=>'world_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$userData]];
                socketSend($data);
            }
        }


		SendErr: Cache::unlock($lockKey);
        if(!$err){
			echo $this->data->send(['chest'=>$chest, 'times'=>$times]);
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 玩家放弃皇陵探宝翻牌
     * 
     * ```php
     * /Lottery/quitDrawCard/
     * postData: json={}
     * return: json{}
     * 
     */
    public function quitDrawCardAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
         //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);

        $err = 0;

        $PlayerDrawCard = new PlayerDrawCard;
        $pd = $PlayerDrawCard->getByPlayerId($playerId);
        if(!$pd){
            $err = 10400;//该局不存在
            goto SendErr;
        }

        $PlayerDrawCard->endDrawCard($playerId);

        SendErr: Cache::unlock($lockKey);
        if(!$err){
            echo $this->data->send();
        }else{
            echo $this->data->sendErr($err);
        }
    }
}