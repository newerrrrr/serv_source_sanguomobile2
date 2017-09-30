<?php
//排行榜
class Rank extends ModelBase{
	public $blacklist = ['type', 'create_time'];
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($gpd, $type, $rank, $name, $value){
		$o = new self;
		$ret = $o->create(array(
			'gpd' => $gpd,
			'type' => $type,
			'rank' => $rank,
			'name' => $name,
			'value' => $value,
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret)
			return false;
			
		return $o->affectedRows();
	}
	
	public function clearTable(){
		$this->sqlExec('TRUNCATE '.$this->getSource());
	}
}