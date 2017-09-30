<?php
/**
 * table city
 *
 */
class City extends CityBattleModelBase{
	public function alter($id, array $fields){
		$flag = $this->updateAll($fields, ['id'=>$id]);
        return $flag;
    }
	
	public function getCityName($id){
		if(!$id) return '';
		return $this->findFirst($id)->desc;
	}

	public function canSign($cityId, $campId){
        $re = $this->findFirst($cityId)->toArray();
        if($re['camp_id']==$campId){
            return true;
        }
        $cityList = explode(",", $re['link']);
        foreach($cityList as $v){
            $re = $this->findFirst($v)->toArray();
            if($re['camp_id']==$campId){
                return true;
            }
        }
        return false;
    }
}
