<?php
//公会公告板
class GuildBoard extends ModelBase{
	public function getByPlayerId($playerId, $forDataFlag=false){
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$guildId = $player['guild_id'];
		return $this->getByGuildId($guildId, $forDataFlag);
    }
	
	public function getByGuildId($guildId, $forDataFlag=false){
        $data = Cache::getGuild($guildId, __CLASS__);
        if(!$data) {
            $data = self::find(["guild_id={$guildId}","order"=>"order_id asc"])->toArray();

            Cache::setGuild($guildId, __CLASS__, $data);
        }
        $Player = new Player;
		foreach ($data as $key => $value) {
            if(empty($value['player_id'])){
                $data[$key]['nick'] = "系统";
                $data[$key]['avatar_id'] = 0;
            }else{
                $tmpP = $Player->getByPlayerId($value['player_id']);
                $data[$key]['nick'] = $tmpP['nick'];
                $data[$key]['avatar_id'] = $tmpP['avatar_id'];
            }
		}
		$data = $this->adapter($data);
        if($forDataFlag) {
            return filterFields($data, $forDataFlag, $this->blacklist);
        } else {
            return $data;
        }
    }

    public function saveRecord($playerId, $guildId, $orderId, $title, $text){
    	Cache::lock("UpdateGuildBoard:gId=".$guildId);
    	$re = $this->getByGuildId($guildId);
    	$flag = false;
    	foreach($re as $k=>$v){
    		if($v['order_id']==$orderId){
    			$id = $v['id'];
    			$this->updateAll(['player_id'=>$playerId, 'title'=>"'".$title."'", 'content'=>"'".$text."'", 'update_time'=>qd()],['id'=>$id]);
    			$flag = true;
    			break;
    		}
    	}
    	if(!$flag){
    		$self = new Self;
    		$self->guild_id = $guildId;
    		$self->player_id = $playerId;
    		$self->order_id = $orderId;
    		$self->title = $title;
    		$self->content = $text;
    		$self->update_time = date("Y-m-d H:i:s");
    		$self->save();
    	}
    	$this->clearGuildCache($guildId);
    	Cache::unlock("UpdateGuildBoard:gId=".$guildId);
    }

    public function swapRecord($guildId, $orderId1, $orderId2){
    	Cache::lock("UpdateGuildBoard:gId=".$guildId);
    	$re = $this->getByGuildId($guildId);
    	foreach($re as $k=>$v){
    		if($v['order_id']==$orderId1){
    			$id = $v['id'];
    			$this->updateAll(['order_id'=>$orderId2, 'update_time'=>qd()],['id'=>$id]);
    		}elseif($v['order_id']==$orderId2){
    			$id = $v['id'];
    			$this->updateAll(['order_id'=>$orderId1, 'update_time'=>qd()],['id'=>$id]);
    		}
    	}
    	$this->clearGuildCache($guildId);
    	Cache::unlock("UpdateGuildBoard:gId=".$guildId);
    }
}