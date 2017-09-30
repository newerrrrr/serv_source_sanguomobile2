<?php
/**
 * 联盟
 *
 */
class GuildMissionAward extends CityBattleModelBase{	

    /**
     * 创建联盟发奖日志
     * @param  array $data 
     */
    public function createAwardLog(array $data){

        $self                         = new self;
        $self->server_id              = $data['server_id'];
        $self->guild_id               = $data['guild_id'];
        $self->player_id              = $data['player_id'];
        $self->award_id               = $data['award_id'];
        $self->create_time            = date('Y-m-d H:i:s');
        $self->save();
        
    }
    
}
