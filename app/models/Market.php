<?php
/**
 * 集市
 */
class Market extends ModelBase{
    /**
     * 随机超值商品
     * 
     * @param <type> $ 
     * 
     * @return <type>
     */
	function getRandSp($cityLv){
		$all = $this->dicGetAll();
		$ids = [];
		foreach($all as $_all){
			if($_all['type'] == 2 && $cityLv >= $_all['min_level'] && $cityLv <= $_all['max_level']){
				$ids[] = $_all['id'];
			}
		}
		shuffle($ids);
		return $ids[0];
	}
	
	function rand($n, $spId, $cityLv, $except=[]){
		$all = $this->dicGetAll();
		$notInThisType = [];
		$notInThisType[] = $all[$spId]['refresh_control_id'];
		$notInclude = [];
		foreach($except as $_e){
			$notInThisType[] = $all[$_e]['refresh_control_id'];
			$notInclude[] = $all[$_e]['refresh_control_id'];
		}
		$rate = [];
		foreach($all as $_all){
			if(($_all['type'] == 1 && $cityLv >= $_all['min_level'] && $cityLv <= $_all['max_level'] && !in_array($_all['refresh_control_id'], $notInThisType)) || $_all['id'] == $spId){
				$rate[$_all['id']] = $_all['type_chance'];
			}
		}
		//ksort($rate);
		//var_dump($notInThisType);
		$i = 0;
		$ids = [];
		while($i < $n){
			$_id = random($rate);
			//if(in_array($_id, $ids) || in_array($_id, $except)) continue;
			if(in_array($all[$_id]['refresh_control_id'], $notInclude)) continue;
			$ids[] = $_id;
			$notInclude[] = $all[$_id]['refresh_control_id'];
			$i++;
		}
		return $ids;
	}
}