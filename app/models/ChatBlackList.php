<?php
/**
 * 王战-选举
 *
 */
class ChatBlackList extends ModelBase {
	public $blacklist = ['create_time'];
	/**
	 * 添加新纪录
	 */
	public function addNew($playerId, $blackPlayerId){
		$exists = self::findFirst("player_id={$playerId} and black_player_id={$blackPlayerId}");
		if(!$exists) {
			$self = new self;
			$self->player_id = $playerId;
			$self->black_player_id = $blackPlayerId;
			$self->create_time = date('Y-m-d H:i:s');
			$self->save();
			$this->clearDataCache($playerId);
            return true;
		}
        return false;
	}
	/**
	 * 删除一条黑名单
	 * @param  int $playerId      
	 * @param  int $blackPlayerId 
	 */
	public function removeBlack($playerId, $blackPlayerIds){
		if(is_array($blackPlayerIds) && $blackPlayerIds) {
			$blackPlayerIds = join(',', $blackPlayerIds);
			$re = self::find("player_id={$playerId} and black_player_id in ({$blackPlayerIds})");
			if($re->toArray()){
				$re->delete();
				$this->clearDataCache($playerId);
                return true;
			}
            return false;
		}
        return false;
	}
	/**
     * 获取黑名单信息
     * 
     * @param   int    $playerId    player id
     * @return  array    description
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
    	$re = Cache::getPlayer($playerId, __CLASS__);
        if(!$re) {
            $re = self::find(["player_id={$playerId}"])->toArray();
            $Player = new Player;
            foreach($re as $k=>&$v) {
				$player               = $Player->getByPlayerId($v['black_player_id']);
				$v['black_nick']      = $player['nick'];
				$v['black_level']     = $player['level'];
				$v['black_avatar_id'] = $player['avatar_id'];
            }
            unset($v);
            $re = $this->adapter($re);
            Cache::setPlayer($playerId, __CLASS__, $re);
        }
        if($forDataFlag) {
            return filterFields($re, $forDataFlag, $this->blacklist);
        } else {
            return $re;
        }

    }

}
