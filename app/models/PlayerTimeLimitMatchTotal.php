<?php
/**
 * 限时比赛 字典表
 */
class PlayerTimeLimitMatchTotal extends ModelBase{
    public function addNew($data){
        $self                             = new self;
        $self->player_id                  = $data['player_id'];
        $self->time_limit_match_config_id = $data['time_limit_match_config_id'];
        $self->score                      = 0;
		$self->rank						  =	0;
        $self->update_time                = $self->create_time = date('Y-m-d H:i:s');
        $self->save();

        // $this->clearPlayerTimeLimitMatchCache();
    }
    public function getByPlayerId($a, $b=false){
        return ;
    }
    /**
     * 获取玩家限时比赛总积分表
     * @param  int $playerId 
     * @return array           
     */
    public function getPlayerTimeLimitMatchTotal($playerId){
        DataTotal:
        $configId = (new TimeLimitMatchConfig)->getCurrentRoundId();
        $re = self::findFirst("player_id={$playerId} and time_limit_match_config_id={$configId}");
        if($re) {
            $r = $this->adapter($re->toArray(), true);
            return $r;
        } else {
            $data['player_id'] = $playerId;
            $data['time_limit_match_config_id'] = $configId;
            $this->addNew($data);
            goto DataTotal;
        }
        return null;
    }
    /**
     * 更新每日积分
     * @param  int  $playerId  
     * @param  int  $matchType 
     * @param  int  $score     
     * @param  boolean $minusFlag 
     */
    public function updateScore($playerId, $matchType, $score, $minusFlag=false){
        if($minusFlag) {
            $this->updateAll();
        } else {
            $this->updateAll(['score'=>"score+{$score}"], [
                'player_id' => $playerId,
                ]);
        }
    }
}