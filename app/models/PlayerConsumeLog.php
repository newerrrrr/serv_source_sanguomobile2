<?php

class PlayerConsumeLog extends ModelBase{
    /**
     * 玩家消费记录
     * @param  int $playerId player id
     * @return bool           sucess or not
     */
    public function add($playerId, $rmbGem, $giftGem, $costId=0, $memo=''){
        $this->player_id = $playerId;
		$this->rmb_gem = $rmbGem;
		$this->gift_gem = $giftGem;
		$this->cost_id = $costId;
		$this->memo = $memo;
        $this->create_time = date('Y-m-d H:i:s', time());
        return $this->save();
    }
}
