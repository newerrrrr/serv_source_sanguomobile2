<?php
//邮件组
class PlayerMailGroup extends ModelBase{
	public $blacklist = array('create_time');
	const CACHEKEY = 'data_MAILIGROUP';
	public function afterSave(){
		$this->clearDataCache();
	}
	
    /**
     * 获取组群玩家id数组
     * 
     * @param <type> $groupId 组群id
     * 
     * @return <array>玩家id数组
     */
	public function getGroup($groupId){
        $data = Cache::db()->hGet(self::CACHEKEY, $groupId);
        if(!$data) {
            $data = self::find(["group_id='".$groupId."'"])->toArray();
			$data = Set::extract('/player_id', $data);
            Cache::db()->hSet(self::CACHEKEY, $groupId, $data);
        }
		return $data;
    }
	
	public function getGroupCreater($groupId){
		 $data = self::findFirst(["group_id='".$groupId."' and is_creater=1"]);
		 if(!$data)
			 return false;
		 return $data->player_id;
	}
	
    /**
     * 新建主群
     * 
     * @param <array> $playerIds 玩家id数组
     * 
     * @return <type>
     */
	public function newGroup($playerIds, $createrId){
		$groupId = uniqid();
		foreach($playerIds as $_playerId){
			if(!(new self)->add($_playerId, $groupId, ($createrId==$_playerId ? 1 : 0))){
				return false;
			}
		}
		return $groupId;
	}
	
    /**
     * 组群新增玩家
     * 
     * @param <type> $groupId 组群id
     * @param <type> $playerId 玩家id
     * 
     * @return <type>
     */
	public function addMemeber($groupId, $playerId){
		if($this->findFirst(array('group_id="'.$groupId.'" and player_id='.$playerId))){
			return false;
		}
		if(!(new self)->add($playerId, $groupId, 0)){
			return false;
		}
		$this->clearDataCache($groupId);
		return true;
	}
	
    /**
     * 组群删除玩家
     * 
     * @param <type> $groupId 组群id
     * @param <type> $playerId 玩家id
     * 
     * @return <type>
     */
	public function deleteMemeber($groupId, $playerId){
		if(!$this->find(array('group_id="'.$groupId.'" and player_id='.$playerId))->delete()){
			return false;
		}
		$this->clearDataCache($groupId);
		return $this->affectedRows();
	}
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $groupId 
     * 
     * @return <type>
     */
	public function add($playerId, $groupId, $isCreater){
		$ret = $this->create(array(
			'player_id' => $playerId,
			'group_id' => $groupId,
			'is_creater' => $isCreater,
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
	
	public function changeCreater($playerId, $groupId){
		$this->sqlExec('update '.$this->getSource().' set is_creater=1 where group_id="'.$groupId.'" '.($playerId ? 'and player_id='.$playerId : '').' limit 1');
		$this->clearDataCache($groupId);
		Cache::db()->hDel('chatConnectId', $groupId);
	}
	
	public function clearDataCache($groupId=0, $noBasicFlag=true){
		 Cache::db()->hDel(self::CACHEKEY, $groupId);
	}
}