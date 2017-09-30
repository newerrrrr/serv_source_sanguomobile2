<?php
/**
 * 玩家抽奖
 *
 */
class PlayerDrawCard extends ModelBase{
	public $blacklist = ['card_order'];

	public function beginDrawCard($playerId){
		$re = $this->getByPlayerId($playerId);
		if($re){
			return false;
		}else{
			$PlayerBuild = new PlayerBuild;
			$Chest = new Chest;
			$level = $PlayerBuild->getPlayerCastleLevel($playerId);
			$cardOrder = $Chest->createCardOrder($level, $cId);
			$self = new self;
			$self->create(array(
				'player_id' => $playerId,
				'chest_type_id' => $cId,
				'card_order' => json_encode($cardOrder),
				'open_order' => 0,
				'status' => 1,
				'create_time' => date('Y-m-d H:i:s'),
			));
			$this->clearDataCache($playerId);
			$re = $this->getByPlayerId($playerId);
			$id = $re['id'];
			$PlayerLotteryInfo = new PlayerLotteryInfo;
			$PlayerLotteryInfo->changeDrawCardId($playerId, $id);
			return $cId;
		}
	}

	public function openPosition($playerId, $position){
		$re = $this->getByPlayerId($playerId);
		if(!$re || empty($re['is_start'])){
			return false;
		}else{
			$id = $re['id'];
			$p = $re['open_order'].$position;
			$this->updateAll(['open_order'=>$p],['id'=>$id]);
			$this->clearDataCache($playerId);
			return true;
		}
	}

	public function getTimes($playerId){
		$re = $this->getByPlayerId($playerId);
		if($re){
			$oOrder = str_split($re['open_order']);
			$times = 0;
			$count = count($oOrder);
			$cOrder = json_decode($re['card_order'],true);
			$Chest = new Chest;
			while($count>0){
				$cId = $cOrder[$count-1];
				$chest = $Chest->dicGetOne($cId);
				if($chest['type']==2){
					$times += $chest['value'];
					$count--;
				}else{
					break;
				}
			}
			return ($times>0)?$times:1;
		}else{
			return false;
		}
	}

	public function startDrawCard($playerId){
		$re = $this->getByPlayerId($playerId);
		if(!$re){
			return false;
		}else{
			$id = $re['id'];
			$this->updateAll(['is_start'=>1],['id'=>$id]);
			$this->clearDataCache($playerId);
			return true;
		}
	}

	public function endDrawCard($playerId){
		$re = $this->getByPlayerId($playerId);
		if(!$re || empty($re['is_start'])){
			return false;
		}else{
			$id = $re['id'];
			$this->updateAll(['status'=>2],['id'=>$id]);
			$this->clearDataCache($playerId);
			$PlayerLotteryInfo = new PlayerLotteryInfo;
			$PlayerLotteryInfo->changeDrawCardId($playerId, 0);
			return true;
		}
	}

	/**
     * 通过id获取玩家信息
     *
     * @return $player array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $r = Cache::getPlayer($playerId, __CLASS__);
        if(!$r) {
            $re = self::findFirst(["player_id={$playerId} and status=1"]);
            if(!$re){
            	return false;
            }else{
            	$re = $re->toArray();
            }
            $r = $this->adapter($re, true);
            Cache::setPlayer($playerId, __CLASS__, $r);
        }
        if($forDataFlag) {
            return filterFields([$r], $forDataFlag, $this->blacklist)[0];
        } else {
            return $r;
        }
    }
}