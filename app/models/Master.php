<?php

class Master extends ModelBase{
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			return $this->findList('level');
		});
		return $ret;
	}
    /**
     * 获取玩家经验值区间对应的值
     * @param  int $exp 
     * @return array      
     */
    public function dicGetOneByExp($exp){
        $all = $this->dicGetAll();
        $all = Set::sort($all, '{n}.level', 'asc');
        $re = ['current'=>[],'next'=>[]];
        foreach($all as $k=>$v) {
            if($v['exp'] > $exp) {
                $re['next'] = $v;
                break;
            }
            $re['current'] = $v;
        }
        return $re;
    }
    /**
     * 获得最大武将数
     * 
     * @param <type> $playerLevel 
     * 
     * @return <type>
     */
    public function getMaxGeneral($playerLevel){
		$ret = $this->dicGetOne($playerLevel);
		if(!$ret)
			return false;
		return $ret['max_general'];
	}

    /**
     * 获取最大存储数量
     * @param  [type] $playerLevel [description]
     * @return [type]              [description]
     */
    public function getMaxStoreNum($playerId, $playerLevel){
        $ret = $this->dicGetOne($playerLevel);
        if(!$ret)
            return false;
        $PlayerBuff = new PlayerBuff;
        $dailyStoreBuff = $PlayerBuff->getPlayerBuff($playerId, "alliance_daily_storage_plus", 0);
        return ['day'=>$ret['day_storage']*(1+$dailyStoreBuff), 'all'=>$ret['max_warehouse']];
    }
}
