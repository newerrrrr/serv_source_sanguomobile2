<?php
//建筑
class GuildGiftDistributionLog extends ModelBase{
    function getDistributionList($guildId, $round, $type){
        $result = Cache::getGuild($guildId, __CLASS__);
        if(empty($result)){
            $result = [];
            $re = $this->find("guild_id={$guildId} and round={$round} and type={$type}");
            if($re){
                $re = $re->toArray();
                foreach($re AS $v){
                    if(empty($result[$v['gift_id']])){
                        $result[$v['gift_id']] = 1;
                    }else{
                        $result[$v['gift_id']]++;
                    }
                }
            }
            Cache::setGuild($guildId, __CLASS__, $result);
        }
        return $result;
    }

    function hasGetGift($playerId, $round, $type){
        $re = $this->findFirst("player_id={$playerId} and round={$round} and type={$type}");
        if($re){
            return true;
        }else{
            return false;
        }
    }

    function addNew($playerId, $guildId, $giftId, $round, $type){
        $self = new Self;
        $self->player_id = $playerId;
        $self->guild_id = $guildId;
        $self->gift_id = $giftId;
        $self->round = $round;
        $self->type = $type;
        $self->create_time = date("Y-m-d H:i:s");
        $self->save();
        $this->clearGuildCache($guildId);
    }
	
	function clearAllCache(){
		//$round = $this->maximum(array('column'=>'round'));
		$ret = $this->sqlGet('select distinct guild_id from '.$this->getSource());
		foreach($ret as $_r){
			$this->clearGuildCache($_r['guild_id']);
		}
	}
}