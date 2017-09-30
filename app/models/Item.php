<?php
class Item extends ModelBase{
	//获取所有天赋
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				$_r = $this->parseColumn($_r);
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
	public function parseColumn($_r){
		$_r['drop'] = parseGroup($_r['drop'], false);
		//$_r['use'] = parseArray($_r['use']);
		return $_r;
	}
	
    /**
     * 获取道具加速时间
     * 
     * @param <type> $itemId 
     * @param <type> $type 加速类型：1.建筑；2.造兵；3.医疗；4.研究;5.陷阱
     * 
     * @return <type>
     */
	public static function getAcceSecond($itemId, $type){
		$Item = new Item;
		$item = $Item->dicGetOne($itemId);
		if(!$item)
			return false;
		$iaid = $item['item_acceleration'];
		$ItemAcceleration = new ItemAcceleration;
		$ia = $ItemAcceleration->dicGetOne($iaid);
		if(!$ia)
			return false;
		if($ia['type'] && $ia['type'] != $type){
			return false;
		}
		return $ia['item_num']*1;
		/*$itemIds = array(20701 => 3600);
		switch($type){
			case 1://建筑
				$itemIds = $itemIds+array(
					20801 => 5*60,
					20802 => 10*60,
					20803 => 30*60,
					20804 => 3600,
					20805 => 2*3600,
					20806 => 8*3600,
				);
			break;
			case 2://造兵
				$itemIds = $itemIds + array(
					20901 => 5*60,
					20902 => 10*60,
					20903 => 30*60,
					20904 => 3600,
					20905 => 2*3600,
					20906 => 8*3600,
				);
			break;
			case 3://医疗
				$itemIds = $itemIds + array(
					21001 => 5*60,
					21002 => 10*60,
					21003 => 30*60,
					21004 => 3600,
					21005 => 2*3600,
					21006 => 8*3600,
				);
			break;
			case 4://研究
				$itemIds = $itemIds + array(
					21101 => 5*60,
					21102 => 10*60,
					21103 => 30*60,
					21104 => 3600,
					21105 => 2*3600,
					21106 => 8*3600,
				);
			break;
			default:
				return false;
		}
		if(!isset($itemIds[$itemId])){
			return false;
		}
		return $itemIds[$itemId];*/
	}

	public static function getAllPieceIds(){
		$ids = Cache::db()->get(__CLASS__ . ':getAllPieceIds');
		if(!$ids){
			$data = self::find(['item_type=4'])->toArray();
			$ids = Set::extract('/id', $data);
			Cache::db()->set(__CLASS__ . ':getAllPieceIds', $ids);
		}
		return $ids;
	}

	public static function isGodFragment($itemId){
		if($itemId*1 >= 41000 && $itemId*1 < 42000){
			return true;
		}
		return false;
	}
}