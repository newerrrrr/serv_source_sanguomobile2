<?php
//道具背包
class PlayerItem extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
    /**
     * 新增道具
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId, $itemId, $num=1){
		if($itemId >= 40000 && $itemId <= 50000){
			$isNew = 0;
		}else{
			$isNew = 1;
		}
		$o = new self;
		if(!$o->find(array('player_id='.$playerId. ' and item_id='.$itemId))->toArray()){
			$ret = $o->create(array(
				'player_id' => $playerId,
				'item_id' => $itemId,
				'num' => $num,
				'is_new' => $isNew,
				'create_time' => date('Y-m-d H:i:s'),
				//'rowversion' => '',
			));
			if(!$ret)
				return false;
			
		}else{
			$now = date('Y-m-d H:i:s');
			$ret = $o->updateAll(array(
				'num' => 'num+'.$num,
				'is_new' => $isNew,
				'update_time'=>"'".$now."'",
				'rowversion'=>"'".uniqid()."'"
			), array("player_id"=>$playerId, "item_id"=>"'".$itemId."'"));
		}
		$o->clearDataCache($playerId);
		if(!$this->transferSoul($playerId, $itemId))
			return false;
		socketSend(['Type'=>'item', 'Data'=>['playerId'=>[$playerId]]]);
		return $o->affectedRows();
	}
		
    /**
     * 丢弃道具
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function drop($playerId, $itemId, $num=1){
		$o = $this->findFirst(array('player_id='.$playerId. ' and item_id='.$itemId.' and num>='.$num));
		if(!$o){
			return false;
		}else{
			$data = $o->toArray();
			if($data['num'] == $num){
				$o->delete();
				if(!$o->affectedRows()){
					return false;
				}
			}else{
				$now = date('Y-m-d H:i:s');
				$ret = $this->updateAll(array(
					'item_id'=>$itemId,
					'num' => 'num-'.$num,
					'update_time'=>"'".$now."'",
					'rowversion'=>"'".uniqid()."'"
				), array("player_id"=>$playerId, "item_id"=>"'".$itemId."'", "num >="=>$num));
				if(!$ret){
					return false;
				}
			}
			(new PlayerCommonLog)->add($playerId, ['type'=>'使用道具', 'item_id'=>$itemId, 'num'=>$num, 'name'=>(new Item)->dicGetOne($itemId)['desc1']]);
		}
		$this->clearDataCache($playerId);
		return true;
	}
	
	public function hasItemCount($playerId, $itemId){
		$data = $this->getByPlayerId($playerId);
		foreach($data as $_data){
			if($_data['item_id'] == $itemId){
				return $_data['num'];
			}
		}
		return 0;
	}
	
	public function setNew($playerId, $isNew){
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'is_new' => $isNew,
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId));
		$this->clearDataCache($playerId);
		return true;
	}
	
	public function transferSoul($playerId, $soulItemId){
		if($soulItemId >= 61000 && $soulItemId <= 62000){
			$general = (new General)->findFirst('general_item_soul='.$soulItemId);
			if(!$general)
				return false;
			$generalId = $general->general_original_id;
			//获取对应武将
			$pg = (new PlayerGeneral)->getByGeneralId($playerId, $generalId);
			if(!$pg)
				return true;
			//最大星级
			$maxStar = (new GeneralStar)->maximum(array('column'=>'star', 'general_original_id='.$generalId));
			//检查对应武将是否满级
			if($pg['star_lv'] < $maxStar)
				return true;
			
			$num = $this->hasItemCount($playerId, $soulItemId);
			if($num > 0){
				if(!$this->drop($playerId, $soulItemId, $num))
					return false;
				$rate = 1;
				$jiangyinNum = $rate * $num;
				if(!(new Player)->addJiangyinNum($playerId, $jiangyinNum))
					return false;
				(new PlayerCommonLog)->add($playerId, ['type'=>'自动将印转换', 'memo'=>['soulItemId'=>$soulItemId,'num'=>$num]]);//日志
				@$this->getDI()->get('data')->extra['jiangyin'][$soulItemId]['fromNum'] += $num;
				@$this->getDI()->get('data')->extra['jiangyin'][$soulItemId]['toNum'] += $jiangyinNum;
			}
			return true;
		}
		return true;
	}
}