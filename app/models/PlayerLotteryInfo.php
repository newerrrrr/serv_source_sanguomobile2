<?php
/**
 * 玩家抽奖
 *
 */
class PlayerLotteryInfo extends ModelBase{

	/**
	 * 创建新纪录
	 * @param  [type] $playerId [description]
	 * @return [type]           [description]
	 */
	public function newRecord($playerId){
		$self = new self;
		$ret = $self->create(array(
			'player_id' => $playerId,
			'free_times' => 1,
			'current_position' => 1,
			'last_date' => date("Y-m-d 00:00:00"),
			'coin_num' => '0',
			'jade_num' => '0',
			'create_time' => date('Y-m-d H:i:s'),
		));
		$this->clearDataCache($playerId);
		return self::findFirst(["player_id={$playerId}"])->toArray();
	}

	/**
	 * 更新硬币
	 * @param [type] $playerId [description]
	 */
	public function updateCoin($playerId, $num){
		$re = $this->getByPlayerId($playerId);
		$result = $this->updateAll(['coin_num'=>'`coin_num`+'.$num],['id'=>$re['id'], 'coin_num >='=>$num*(-1)]);
		$this->clearDataCache($playerId);
		return ($result>0);
	}

	/**
	 * 增加免费次数
	 * @param [type] $playerId [description]
	 */
	public function addFreeTimes($playerId){
		$re = $this->getByPlayerId($playerId);
		$this->updateAll(['free_times'=>1],['id'=>$re['id']]);
		$this->clearDataCache($playerId);
	}

	public function useFreeTimes($playerId){
		$re = $this->getByPlayerId($playerId);
		$result = $this->updateAll(['free_times'=>0],['id'=>$re['id'],'free_times'=>1]);
		$this->clearDataCache($playerId);
		return ($result>0);
	}

	/**
	 * 更新勾玉
	 * @param [type] $playerId [description]
	 */
	public function updateJade($playerId, $num){
		$re = $this->getByPlayerId($playerId);
		$result = $this->updateAll(['jade_num'=>'`jade_num`+'.$num],['id'=>$re['id'], 'jade_num >='=>$num*(-1)]);
		$this->clearDataCache($playerId);
		return ($result>0);
	}

	/**
	 * 修改九宫格游戏状态
	 * @return [type] [description]
	 */
	public function changeDrawCardId($playerId, $id){
		$re = $this->getByPlayerId($playerId);
		$this->updateAll(['draw_card_id'=>$id],['id'=>$re['id']]);
		$this->clearDataCache($playerId);
	}

	 /**
     * 通过id获取玩家信息
     *
     * @return $player array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $r = Cache::getPlayer($playerId, __CLASS__);
        if(!$r) {
            $re = self::findFirst(["player_id={$playerId}"]);
            if(!$re){
            	$re = $this->newRecord($playerId);
            }else{
            	$re = $re->toArray();
            	if($re['last_date']!=date("Y-m-d 00:00:00")){
            		$this->updateAll(['free_times'=>1, 'last_date'=>"'".date("Y-m-d 00:00:00")."'"],['id'=>$re['id']]);
            		$re = self::findFirst(["player_id={$playerId}"])->toArray();
            	}
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

    public function go($playerId, $position){
    	$re = $this->getByPlayerId($playerId);
    	$this->updateAll(['current_position'=>$position],['id'=>$re['id']]);
    	$this->clearDataCache($playerId);
    }
}