<?php
/**
 * 王战
 *
 */
class King extends ModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	
	const STATUS_READY 	= 0;
	const STATUS_BATTLE = 1;
	const STATUS_REWARD = 2;
	const STATUS_FINISH = 3;
	const STATUS_VOTED = 4;//已任命
	/**
	 * 获取历任国王
	 * @return array 
	 */
	public function getHistoryKing(){
		$statusVoted = self::STATUS_VOTED;
        $re          = self::find(["status={$statusVoted}", 'order' => 'id desc'])->toArray();
        $r           = [];
		if($re) {
		    $last = self::findFirst(['order'=>'id desc']);
		    if($last && $last->status==$statusVoted) {
                array_pop($re);
            }
            $re     = array_reverse($re);
            $Player = new Player;
            $k      = 1;
			foreach($re as $v) {
			    if($v['player_id']==0) continue;
				$player = $Player->getByPlayerId($v['player_id']);
                $r[]    = [
					'rank'       => $k++,
					'nick'       => $player['nick'],
					'avatar_id'  => intval($player['avatar_id']),
					'start_time' => strtotime($v['start_time']),
				];
			}
		}
		return $r;		
	}
	public function getCurrentBattle(){
		$ret = self::findFirst(['status='.self::STATUS_BATTLE]);
		if(!$ret)
			return false;
		return $ret->toArray();
	}
	public function getLastBattle(){
		$ret = self::findFirst(['order'=>'id desc']);
		if(!$ret)
			return false;
		return $ret->toArray();
	}

	public function hasFirstKing(){
        $ret = self::findFirst(['status>='.self::STATUS_FINISH]);
        if(!$ret){
            return false;
        }else{
            return true;
        }

    }
	
	public function getNeedRewardBattle(){
		$ret = self::findFirst(['status='.self::STATUS_REWARD]);
		if(!$ret)
			return false;
		return $ret->toArray();
	}

	public function addNew(){
		$self              = new self;
		$self->guild_id    = 0;
		$self->player_id   = 0;
		$self->start_time = date("Y-m-d 19:00:00");
		$self->end_time = date("Y-m-d 20:00:00");
		$self->create_time = date("Y-m-d H:i:s");
		$self->update_time = date("Y-m-d H:i:s");
		$self->rowversion = uniqid();
		$self->save();
	}
	
	public function upStatus($id, $status, $fromStatus=false){
		$now = date('Y-m-d H:i:s');
        $cond = ["id"=>$id];
        if($fromStatus) {
            $cond['status'] = $fromStatus;
        } else {
            $cond['status <>'] = $status;
        }
		$ret = $this->updateAll(array(
			'status'      => $status,
            'update_time' => "'" . $now . "'",
            'rowversion'  => "'".uniqid()."'"
		), $cond);
		if(!$ret){
			return false;
		}
		return true;
	}

    /**
     * 更新国王信息
     * @param $id
     * @param $targetPlayerId
     *
     * @return bool
     */
	public function upCurrentKing($id, $targetPlayerId){
        $ret = $this->updateAll([
                'player_id'   => $targetPlayerId,
                'status'      => self::STATUS_VOTED,
                'update_time' => qd(),
                'rowversion'  => q(uniqid()),
            ], ['id'=>$id, 'status'=>self::STATUS_FINISH]);
        if(!$ret){
            return false;
        }
        return true;
    }
	
	public function upGuild($id, $guildId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'guild_id'=>$guildId,
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("id"=>$id));
		if(!$ret){
			return false;
		}
		return true;
	}

	public function getFirstKingDate(){
        $ret = self::findFirst(['order'=>'id asc']);
        if($ret){
            $ret = $ret->toArray();
            return $ret['start_time'];
        }
        return false;
    }
}
