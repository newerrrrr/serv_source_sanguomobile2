<?php
//坐标收藏
class PlayerCoordinate extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId, $x, $y, $type, $name){
		if($this->find(array('player_id='.$playerId. ' and x='.$x.' and y='.$y))->toArray()){
			return false;
		}
		$ret = $this->create(array(
			'player_id' => $playerId,
			'type' => $type,
			'x' => $x,
			'y' => $y,
			'name' => $name,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
			
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
		
	/**
     * 编辑
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function up($playerId, $x, $y, $name){
		if(!$this->find(array('player_id='.$playerId. ' and x='.$x.' and y='.$y))->toArray()){
			return false;
		}
		
		$now = date('Y-m-d H:i:s');
		$ret = $this->updateAll(array(
			'name' => "'".$name."'",
			'update_time'=>"'".$now."'",
			'rowversion'=>"'".uniqid()."'"
		), array("player_id"=>$playerId, "x"=>$x, "y"=>$y));
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
		
    /**
     * 删除
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function drop($playerId, $x, $y){
		$o = $this->findFirst(array('player_id='.$playerId. ' and x='.$x.' and y='.$y));
		if(!$o){
			return false;
		}else{
			$o->delete();
		}
		$this->clearDataCache($playerId);
		return $this->affectedRows();
	}
	
}