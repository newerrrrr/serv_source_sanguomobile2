<?php
/**
 * 玩家成长基金
 *
 */
class PlayerGrowth extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
    /**
     * 玩家酒馆数据
     * @param  int $playerId player id
     * @return bool           sucess or not
     */
    public function add($playerId){
        $this->player_id = $playerId;
		$this->buy = 0;
		$this->num_reward = '';
		$this->level_reward = '';
		$this->begin_time = '0000-00-00 00:00:00';
        $this->create_time = date('Y-m-d H:i:s');
        return $this->save();
    }
	
	public function getByPlayerId($playerId, $forDataFlag=false){
        $data = Cache::getPlayer($playerId, __CLASS__);
        if(!$data) {
            $data = self::findFirst(["player_id={$playerId}"]);
			if(!$data){
				$this->add($playerId);
				$data = self::findFirst(["player_id={$playerId}"]);
			}
			$data = $data->toArray();
            Cache::setPlayer($playerId, __CLASS__, $data);
        }
		$data = $this->adapter($data, true);
		$data['num_reward'] = parseArray($data['num_reward']);
		$data['level_reward'] = parseArray($data['level_reward']);
        if($forDataFlag) {
            return filterFields(array($data), $forDataFlag, $this->blacklist)[0];
        } else {
            return $data;
        }
    }
	
	public function alter($playerId, $fields, $rowversion){
		if(isset($fields['num_reward'])){
			/*foreach($fields['num_reward'] as &$_it){
				$_it = join(',', $_it);
			}
			unset($_it);*/
			$fields['num_reward'] = join(',', $fields['num_reward']);
			$fields['num_reward'] = "'".$fields['num_reward']."'";
		}
		if(isset($fields['level_reward'])){
			/*foreach($fields['level_reward'] as &$_it){
				$_it = join(',', $_it);
			}
			unset($_it);*/
			$fields['level_reward'] = join(',', $fields['level_reward']);
			$fields['level_reward'] = "'".$fields['level_reward']."'";
		}
		$fields['update_time'] = "'".date('Y-m-d H:i:s')."'";
		$fields['rowversion'] = "'".uniqid()."'";
        $ret = $this->updateAll($fields, ["player_id"=>$playerId, 'rowversion'=>"'".$rowversion."'"]);
        $this->clearDataCache($playerId);
		return $ret;
    }
	
    /**
     * 获取全服购买人数
     * 
     * 
     * @return <type>
     */
	public function getTotalNum(){
		$num = $this->count(['buy=1']);
		//查找开服日期
		$startTime = (new Configure)->getValueByKey('server_start_time');
		$num += min(3000, floor(max(0, time() - $startTime) / 3600)*5);
		return $num;
	}
}
