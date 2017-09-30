<?php
//联盟商店
class GuildShop extends ModelBase{
	public $blacklist = array('guild_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearGuildCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player)
			return false;
		$guildId = $player['guild_id'];
		return $this->getByGuildId($guildId, $forDataFlag);
    }
	
	public function getByGuildId($guildId, $forDataFlag=false){
        $guildShop = Cache::getGuild($guildId, __CLASS__);
        if(!$guildShop) {
            $guildShop = self::find(["guild_id={$guildId}"])->toArray();

            Cache::setGuild($guildId, __CLASS__, $guildShop);
        }
		$guildShop = $this->adapter($guildShop);
        if($forDataFlag) {
            return filterFields($guildShop, $forDataFlag, $this->blacklist);
        } else {
            return $guildShop;
        }
    }
	
    /**
     * 新增道具
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($guildId, $itemId, $num=1){
		if(!$this->find(array('guild_id='.$guildId. ' and item_id='.$itemId))->toArray()){
			$ret = $this->create(array(
				'guild_id' => $guildId,
				'item_id' => $itemId,
				'num' => $num,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$now = date('Y-m-d H:i:s');
			$ret = $this->updateAll(array(
				'num' => 'num+'.$num,
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("guild_id"=>$guildId, "item_id"=>"'".$itemId."'"));
		}
		$this->clearGuildCache($guildId);
		return $this->affectedRows();
	}
		
    /**
     * 丢弃道具
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function drop($guildId, $itemId, $num=1){
		$o = $this->findFirst(array('guild_id='.$guildId. ' and item_id='.$itemId.' and num>='.$num));
		if(!$o){
			return false;
		}else{
			$data = $o->toArray();
			if($data['num'] == $num){
				$o->delete();
			}else{
				$now = date('Y-m-d H:i:s');
				$ret = $this->updateAll(array(
					'item_id'=>$itemId,
					'num' => 'num-'.$num,
					'update_time'=>"'".$now."'",
					'rowversion'=>"'".uniqid()."'"
				), array("guild_id"=>$guildId, "item_id"=>"'".$itemId."'", "num >="=>$num));
				
			}
		}
		$this->clearGuildCache($guildId);
		return $this->affectedRows();
	}
	
	public function hasItemCount($guildId, $itemId){
		$data = $this->getByGuildId($guildId);
		foreach($data as $_data){
			if($_data['item_id'] == $itemId){
				return $_data['num'];
			}
		}
		return 0;
	}
	
}