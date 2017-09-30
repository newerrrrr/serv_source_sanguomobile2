<?php
/**
 * 联盟商店进货日志
 */
class GuildShopLog extends ModelBase{
    public function add($guildId, $type, $playerId, $nick, $itemId, $num){
        $this->guild_id = $guildId;
		$this->type = $type;
		$this->player_id = $playerId;
		$this->nick = $nick;
		$this->item_id = $itemId;
		$this->num = $num;
        $this->create_time = date('Y-m-d H:i:s', time());
        return $this->save();
    }
}
