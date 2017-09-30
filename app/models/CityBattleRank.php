<?php
/**
 * table city_battle_rank
 *
 */
class CityBattleRank extends CityBattleModelBase{
    public $mapKey = 'CityBattleRank_pidVrank';
    /**
     * add new data
     * @param $data
     */
    public function addNew($data){
        $self              = new self;
        $self->round_id    = $data['round_id'];
        $self->camp_id     = $data['camp_id'];
        $self->player_id   = $data['player_id'];
        $self->server_id   = $data['server_id'];
        $self->rank        = $data['rank'];
        $self->score       = $data['score'];
        $self->guild_name  = $data['guild_name'];
        $self->nick        = $data['nick'];
        $self->create_time = date('Y-m-d H:i:s');
        $self->save();
    }

    /**
     * 获取所有
     *
     * @return mixed
     */
    public function getAllTitle(){
        $ret = $this->cache($this->mapKey, function() {
            return $this->findList('player_id');
        });
        $ret = $this->adapter($ret);
        return $ret;
    }

    /**
     * 获取玩家称号
     *
     * @param $playerId
     *
     * @return int
     */
    public function getRankPlayerId($playerId){
        $ret = $this->getAllTitle();
        if(isset($ret[$playerId])) {
            return intval($ret[$playerId]['rank']);
        } else {
            return 0;
        }
    }

    public function getTitleByPlayerId($playerId){
        $rank = $this->getRankPlayerId($playerId);
        if($rank>=1 && $rank<=2){
            $re = 2;
        }elseif($rank>=3 && $rank<=10){
            $re = 1;
        }else{
            $re = 0;
        }
        return $re;
    }

    /**
     * 删除玩家称号
     *
     * @param $playerId
     */
    public function delPlayerTitle($playerId){
        self::find([
            "player_id=:playerId:",
            'bind'=>compact('playerId')
                   ])->delete();
        $this->clearRankCache();
    }

    /**
     * 删除缓存
     */
    public function clearRankCache(){
        Cache::db(CACHEDB_STATIC, 'CityBattleRank')->del($this->mapKey);
    }
}
