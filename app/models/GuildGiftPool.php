<?php
//建筑
    class GuildGiftPool extends ModelBase{
        function getGuildGiftList($guildId){
            $result = Cache::getGuild($guildId, __CLASS__);
            if(empty($result)){
                $re = $this->find("guild_id={$guildId}");
                $result = ['round'=>0, 'type'=>0, 'gift'=>[]];
                if($re){
                    $re = $re->toArray();
                    foreach($re AS $v){
                        $result['round'] = $v['round'];
                        $result['type'] = $v['type'];
                        $result['gift'][$v['gift_id']] = $v['num'];
                    }
                }
                Cache::setGuild($guildId, __CLASS__, $result);
            }
            return $result;
        }

        function addNew($guildId, $giftId, $round, $type, $num){
            $self = new Self;
            $self->guild_id = $guildId;
            $self->gift_id = $giftId;
            $self->round = $round;
            $self->type = $type;
            $self->num = $num;
            $self->create_time = date("Y-m-d H:i:s");
            $self->save();
            $this->clearGuildCache($guildId);
			return true;
        }
    }