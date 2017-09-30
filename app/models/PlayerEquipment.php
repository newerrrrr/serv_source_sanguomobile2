<?php
//武将装备背包
class PlayerEquipment extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->_clearDataCache();
	}
	
	public function getCount($playerId){
		$modelClassName = __CLASS__ . '1';
        $re = Cache::getPlayer($playerId, $modelClassName);
        if(!$re) {
			$re = $this->sqlGet('select item_id,count(*) as num from '.$this->getSource().' where player_id='.$playerId.' group by item_id');;
			foreach($re as &$_r){
				$_r['item_id'] = $_r['item_id']*1;
				$_r['num'] = $_r['num']*1;
			}
			unset($_r);
            Cache::setPlayer($playerId, $modelClassName, $re);
        }
        return $re;
	}
	
	public function _clearDataCache($playerId=0){
		if(!$playerId){
			$playerId = $this->player_id;
		}
		$this->clearDataCache($playerId);
		$modelClassName = __CLASS__ . '1';
		Cache::delPlayer($playerId, $modelClassName);
	}
	
    /**
     * 新增道具
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	 public function add($playerId, $itemId, $num = 1){
		$i = 0;
		while($i < $num){
			$o = new self;
			$ret = $o->create(array(
				'player_id' => $playerId,
				'item_id' => $itemId,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret || !$o->affectedRows())
				return false;
			$i++;
		}
		
		//刷新新手任务
		(new PlayerTarget)->refreshBlueEquipNum($playerId, $itemId);
		(new PlayerTarget)->refreshMaxStarEquipNum($playerId, $itemId);
		return true;
	}
	/*public function add($playerId, $itemId, $num=1){
		$i = 0;
		while($i < $num){
			//$this->reset();
			$ret = $this->create(array(
				'player_id' => $playerId,
				'item_id' => $itemId,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret || !$this->affectedRows()){
				return false;
			}
			$i++;
		}
		return true;
	}*/
		
    /**
     * 更新id
     * 
     * @param <type> $status 
     * 
     * @return <type>
     */
	public function updateId($newItemId){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'item_id'=>$newItemId, 
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->_clearDataCache();
		if(!$ret)
			return false;
		//刷新新手任务
		(new PlayerTarget)->refreshBlueEquipNum($this->player_id);
		(new PlayerTarget)->refreshMaxStarEquipNum($this->player_id);
		return true;
	}
	
	public function hasItemCount($playerId, $itemId){
		$data = $this->getByPlayerId($playerId);
		$i = 0;
		foreach($data as $_data){
			if($_data['item_id'] == $itemId){
				$i++;
			}
		}
		return $i;
	}
	
	public function del($playerId, $itemId, $num=1){
		$count = $this->hasItemCount($playerId, $itemId);
		if($count < $num)
			return false;
		$data = $this->getByPlayerId($playerId);
		$i = 0;
		foreach($data as $_data){
			if($_data['item_id'] == $itemId && $i < $num){
				$ret = $this->assign($_data)->delete();
				if(!$ret || !$this->affectedRows())
					return false;
				$i++;
			}
			if($i >= $num)
				break;
		}
		$this->_clearDataCache($playerId);
		
		//刷新新手任务
		(new PlayerTarget)->refreshBlueEquipNum($playerId, $itemId);
		(new PlayerTarget)->refreshMaxStarEquipNum($playerId, $itemId);
		return true;
	}
}