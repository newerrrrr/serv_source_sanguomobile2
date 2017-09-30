<?php
//联盟阶级称谓
class GuildRankName extends ModelBase{
	public function getByPlayerId($playerId, $forDataFlag=false){
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$guildId = $player['guild_id'];
		return $this->getByGuildId($guildId, $forDataFlag);
    }
	
	public function getByGuildId($guildId, $forDataFlag=false){
        $data = Cache::getGuild($guildId, __CLASS__);
        if(!$data) {
            $data = self::find(["guild_id={$guildId}"])->toArray();

            Cache::setGuild($guildId, __CLASS__, $data);
        }
		$data = $this->adapter($data);
        if($forDataFlag) {
            return filterFields($data, $forDataFlag, $this->blacklist);
        } else {
            return $data;
        }
    }

    /**
     * 新建公会时创建阶级名称
     * 
     * @param [type] $guildId [description]
     */
    public function addRankName($guildId){
    	for($i=1;$i<=5;$i++){
    		$self = new Self;
    		$self->guild_id = $guildId;
    		$self->rank = $i;
    		$self->name = "";
    		$self->save();
    	}
    	$this->clearGuildCache($guildId);
    }

    /**
     * 获得公会阶级名称
     * 
     * @param  [type]  $guildId [description]
     * @param  integer $rank    [description]
     * @return [type]           [description]
     */
    public function getRankName($guildId, $rank=0){
    	$re = $this->getByGuildId($guildId);
    	foreach($re as $v){
    		if($v['rank']==$rank){
                $result = $v['name'];
                break;
    		}
            if($rank==0){
                $result[$v['rank']] = $v['name'];
            }
    	}
        return $result;
    }

    /**
     * 修改公会阶级名称
     * 
     * @param  [type] $guildId [description]
     * @param  [type] $rank    [description]
     * @param  [type] $name    [description]
     * @return [type]          [description]
     */
    public function changeRankName($guildId, $rank, $name){
    	if((new SensitiveWord)->checkSensitiveContent($name, 2)){
    		return false;
    	}
    	$re = $this->getByGuildId($guildId);
    	foreach($re as $v){
    		if($v['rank']==$rank){
    			$this->updateAll(['name'=>q($name)], ['id'=>$v['id']]);
    			$this->clearGuildCache($guildId);
    			return true;
    		}
    	}
    	return false;
    }
}?>