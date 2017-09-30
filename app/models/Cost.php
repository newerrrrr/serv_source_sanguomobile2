<?php
/**
 * 花费
 *
 */
class Cost extends ModelBase{
	var $byCostIds = [];
	
	public function getByCostId($costId){
		$ar = @$this->byCostIds[$costId];
		if($ar)
			return $ar;
		$ret = $this->dicGetAll();
		$ar = array();
		foreach($ret as $_k => $_r){
			if($_r['cost_id'] == $costId){
				$ar[$_k] = $_r;
			}
		}
		$this->byCostIds[$costId] = $ar;
		return $ar;
	}

	public function getCostByCount($costId, $count=0){
		$ret = $this->getByCostId($costId);
		foreach($ret as $_k => $_r){
			if($_r['cost_id'] == $costId && $_r['min_count'] <= $count && $_r['max_count'] >= $count){
				return $_r;
			}
		}
		return false;
	}
	
    /**
     * 根据cost_id和count消耗对应物资
     * 
     * @param <type> $playerId 
     * @param <type> $costId 
     * @param <type> $count 特殊计数
	 * @param <type> $num 消耗数量 
     * 
     * @return <type>
     */
	public function updatePlayer($playerId, $costId, $count=0, $num = 1){
		$cost = $this->getCostByCount($costId, $count);
		if(!$cost){
			return false;
		}
		$Player = new Player;
		$costNum = $cost['cost_num'] * $num;
		
		if($cost['cost_type'] == 7){//元宝
			if(!$Player->updateGem($playerId, -$costNum, true, array('cost'=>$cost['id']))){
				return false;
			}
		}elseif($cost['cost_type'] >= 1 && $cost['cost_type'] <= 8 || in_array($cost['cost_type'], [13, 20, 21, 22, 23])){//资源
			$ar = array(1=>'gold', 2=>'food', 3=>'wood', 4=>'stone', 5=>'iron', 6=>'silver', 8=>'guild_coin', 13=>'point', 20=>'feats', 21=>'xuantie', 22=>'jiangyin', 23=>'junzi');
			if(!$Player->hasEnoughResource($playerId, array($ar[$cost['cost_type']]=>$costNum))){
				return false;
			}
			if(!$Player->updateResource($playerId, array($ar[$cost['cost_type']]=>-$costNum))){
				return false;
			}
			if($cost['cost_type']==13) {//锦囊
                //日志
                $PlayerCommonLog = new PlayerCommonLog;
                $PlayerCommonLog->add($playerId, ['type'=>'锦囊消耗', 'memo'=>['cost_id'=>$cost['cost_id'],'desc1'=>$cost['desc1'], 'num'=>$num, 'cost_num'=>$cost['cost_num'],'total_cost_num'=>$costNum]]);
            }
			if($cost['cost_type']==20) {//功勋
                //日志
                $PlayerCommonLog = new PlayerCommonLog;
                $PlayerCommonLog->add($playerId, ['type'=>'功勋消耗', 'memo'=>['cost_id'=>$cost['cost_id'],'desc1'=>$cost['desc1'], 'num'=>$num, 'cost_num'=>$cost['cost_num'],'total_cost_num'=>$costNum]]);
            }
			if($cost['cost_type'] == 8){
				(new PlayerTarget)->updateTargetCurrentValue($playerId, 22, $costNum);
			}
		}elseif($cost['cost_type'] == 9){//行动力
			if(!$Player->updateMove($playerId, -$costNum)){
				return false;
			}
		}elseif($cost['cost_type'] == 12){//联盟荣誉
			$player = $Player->getByPlayerId($playerId);
			if(!$player['guild_id'])
				return false;
			$Guild = new Guild;
			if(!$Guild->updateCoin($player['guild_id'], -$costNum)){
				return false;
			}
		}else{
			return false;
		}
		return true;
	}
}
