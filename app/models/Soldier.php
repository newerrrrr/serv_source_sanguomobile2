<?php
/**
 * 士兵
 *
 */
class Soldier extends ModelBase{
	/**
     * 获取所有士兵
     * 
     * @return <type>
     */
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				if($_r['cost']){
					$_r['cost'] = explode(';', $_r['cost']);
					$_cost = array();
					foreach($_r['cost'] as $_c){
						list($_k, $_c) = explode(',', $_c);
						$_cost[$_k] = $_c;
					}
					$_r['cost'] = $_cost;
				}else{
					$_r['cost'] = array();
				}

				if($_r['upgrade_cost']){
					$_r['upgrade_cost'] = explode(';', $_r['upgrade_cost']);
					$_cost = array();
					foreach($_r['upgrade_cost'] as $_c){
						list($_k, $_c) = explode(',', $_c);
						$_cost[$_k] = $_c;
					}
					$_r['upgrade_cost'] = $_cost;
				}else{
					$_r['upgrade_cost'] = array();
				}
				

				if($_r['rescue_cost']){
					$_r['rescue_cost'] = explode(';', $_r['rescue_cost']);
					$_cost = array();
					foreach($_r['rescue_cost'] as $_c){
						list($_k, $_c) = explode(',', $_c);
						$_cost[$_k] = $_c;
					}
					$_r['rescue_cost'] = $_cost;
				}else{
					$_r['rescue_cost'] = array();
				}

				$_r['skillList'] = [];
				if($_r['skill_1']){
					$_r['skillList'][] = $_r['skill_1'];
				}
				if($_r['skill_2']){
					$_r['skillList'][] = $_r['skill_2'];
				}
				if($_r['skill_3']){
					$_r['skillList'][] = $_r['skill_3'];
				}
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}

	/**
	 * 计算治疗伤兵的宝石
	 * @param  array  $arr [["soldier_id"=>1001, "num"=>2],["soldier_id"=>1002,"num"=>1]]
	 * @return array
	 */
	public function getSumOfRescueCostGem($playerId, array $arr){
		$cost = 0;
		array_walk($arr, function($v, $k) use (&$cost){
			$soldier = $this->dicGetOne($v['soldier_id']);
			$cost += ceil(($soldier['rescue_gem_cost']*$v['num'])/DIC_DATA_DIVISOR);
		});
		return $cost;
	}
	/**
	 * 计算治疗伤兵的资源
	 * @param  array  $arr [["soldier_id"=>1001, "num"=>2],["soldier_id"=>1002,"num"=>1]]
	 * @return array
	 */
	public function getSumOfRescueCostAndTime($playerId, array $arr){
		$cost = ['gold'=>0, 'food'=>0, 'wood'=>0, 'stone'=>0, 'iron'=>0];
		$totalTime = 0;
		array_walk($arr, function($v, $k) use (&$cost, &$totalTime){
			$soldier = $this->dicGetOne($v['soldier_id']);
			$cost['gold']  -= $soldier['rescue_cost'][1]*$v['num'];
			$cost['food']  -= $soldier['rescue_cost'][2]*$v['num'];
			$cost['wood']  -= $soldier['rescue_cost'][3]*$v['num'];
			$cost['stone'] -= $soldier['rescue_cost'][4]*$v['num'];
			$cost['iron']  -= $soldier['rescue_cost'][5]*$v['num'];
			$totalTime     += $soldier['rescue_time']*$v['num'];
		});
		$PlayerBuff = new PlayerBuff;
		$buff1 = $PlayerBuff->getPlayerBuff($playerId, 'cure_speed');
		$totalTime = ceil($totalTime/(1+$buff1));

		$buff2 = $PlayerBuff->getPlayerBuff($playerId, 'cure_cost_minus');
		foreach($cost as &$v) {
			$v = ceil($v*(1-$buff2));
		}

		return ['cost'=>$cost, 'duration'=>$totalTime];
	}
}