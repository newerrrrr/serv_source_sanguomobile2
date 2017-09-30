<?php
//邮件主体
class PlayerMailInfo extends ModelBase{
	public $blacklist = array('create_time');
	const CACHEKEY = 'data_MAILINFO';
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getById($id){
		$data = Cache::db()->hGet(self::CACHEKEY, $id);
        if(!$data) {
            $data = self::findFirst(["id={$id}"]);
			if(!$data)
				return false;
			$data = $data->toArray();
            Cache::db()->hSet(self::CACHEKEY, $id, $data);
        }
		return $data;
	}
	
    /**
     * 新增邮件主体
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($fromPlayerId, $fromPlayerName, $fromPlayerAvatar, $fromGuildShort, $title, $msg, $data, $item, $expireTime){
		if($data){
			$data = json_encode($data);
		}else{
			$data = '';
		}
		if($item){
			//$item = json_encode($item);
			foreach($item as &$_item){
				$_item = join(',', $_item);
			}
			$item = join(';', $item);
			unset($_item);
		}else{
			$item = '';
		}
		$ret = $this->create(array(
			'from_player_id' => $fromPlayerId,
			'from_player_name' => $fromPlayerName,
			'from_player_avatar' => $fromPlayerAvatar,
			'from_guild_short' => $fromGuildShort,
			'title' => $title,
			'msg' => $msg,
			'data' => $data,
			'item' => $item,
			'expire_time' => $expireTime,
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
	
	public function clearDataCache($id=0, $noBasicFlag=true){
		Cache::db()->hDel(self::CACHEKEY, $id);
	}
	
}