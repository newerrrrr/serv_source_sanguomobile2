<?php
//道具背包
class GuildKingPoint extends ModelBase{
	public $blacklist = array('create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		//$this->clearDataCache();
	}

	public function delAll(){
		$Courses = self::find();
		$Courses->delete();
	}
	
    /**
     * 新增道具
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($guildId, $point){
		if(!$this->find(array('guild_id='.$guildId))->toArray()){
			$o = new self;
			$ret = $o->create(array(
				'guild_id' => $guildId,
				'point' => $point,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$now = date('Y-m-d H:i:s');
			$ret = $this->updateAll(array(
				'point' => 'point+'.$point,
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("guild_id"=>$guildId));
		}
		//$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
		
}