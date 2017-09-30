<?php
/**
 * 通知类型表
 */
class PlayerSoldierInjured extends ModelBase{
    /**
     * 收治伤兵
     *
     * @param  array $playerSoldier 
     */
    public function receive($playerSoldier){
        if(empty($playerSoldier)) return [];
        $PlayerBuild     = new PlayerBuild;
        $Soldier         = new Soldier;
        $PlayerCommonLog = new PlayerCommonLog;
        $arr         = [];
        //以playerId分组
        foreach($playerSoldier as $k=>$v) {
            $v['key'] = $k;
            $arr[$v['playerId']][] = $v;
        }
        //每个玩家进行收治
        foreach($arr as $k=>&$v) {
            $maxCureNum  = $PlayerBuild->getMaxCureNum($k);//醫院容量
            $curingNum   = $this->getCuringNum($k);//當前醫院收治量
            $maxCureNum  = $maxCureNum-$curingNum;//醫院剩餘容量
            if($maxCureNum>=0) {//医院未满，兵进医院
                //case: 拼装数组
                foreach($v as &$vv) {
                    $tmpSoldier          = $Soldier->dicGetOne($vv['soldierId']);
                    $vv['soldier_level'] = $tmpSoldier['soldier_level'];
                    $vv['soldier_type']  = $tmpSoldier['soldier_type'];
                }
                unset($vv);
                 //case: 规则 步骑兵车
                $v = Set::sort($v, '{n}.soldier_type', 'asc');
                $v = Set::sort($v, '{n}.soldier_level', 'desc');
                //case: 排序后开始入医院
                $cureNum = 0;
                $vlog = [];
                foreach($v as $kk=>&$vvv) {
                    if($vvv['num']==0) {
                        unset($v[$kk]);
                        continue;
                    }
                    if($cureNum>=$maxCureNum) {//收完处理死兵
                        $vvv['dieNum']     = $vvv['num'];
                        $vvv['receiveNum'] = 0;
                    } else {
                        if($cureNum+$vvv['num']>=$maxCureNum) {//部分医疗，部分死
                            $this->addNew($vvv['playerId'], $vvv['soldierId'], $maxCureNum-$cureNum);
                            $vvv['receiveNum'] = $maxCureNum-$cureNum;
                            $vvv['dieNum']     = $vvv['num']-($maxCureNum-$cureNum);
                            $cureNum           = $maxCureNum;
                        } else {//全伤
                            $this->addNew($vvv['playerId'], $vvv['soldierId'], $vvv['num']);
                            $vvv['receiveNum'] = $vvv['num'];
                            $vvv['dieNum']     = 0;
                            $cureNum           += $vvv['num'];
                        }
                    }
                    $vlog[] = $vvv;
                }
                unset($vvv);
                $PlayerCommonLog->add($k, ['type'=>'[伤兵]伤兵进医院', 'memo'=>['醫院容量'=>$maxCureNum,'當前醫院收治量'=>$curingNum,'injured'=>$vlog]]);//日志
            } else {//医院已满，兵全死
                foreach($v as &$vvvv) {
                    $vvvv['dieNum']     = $vvvv['num'];
                    $vvvv['receiveNum'] = 0;
                    $PlayerCommonLog->add($k, ['type'=>'[伤兵]伤兵进医院-医院已满，兵全死', 'memo'=>['injured_soldier'=>$vvvv, '醫院容量'=>$maxCureNum,'當前醫院收治量'=>$curingNum,]]);//日志
                }
                unset($vvvv);
            }

        }
        unset($v);
        //格式化数组
        $re = [];
        foreach($arr as $v) {
            foreach($v as $vv) {
                $re[$vv['key']] = $vv;
            }
        }
        return $re;
    }
    /**
     * 添加新纪录
     * @param int $playerId  
     * @param int $soldierId 
     * @param int $num       
     */
    public function addNew($playerId, $soldierId, $num){
        if($num<=0) return false;
        $exists = self::findFirst("player_id={$playerId} and soldier_id={$soldierId}");
        if($exists) {//已经存在则累加
            $this->updateAll(['num'=>"num+{$num}", 'update_time'=>qd()], ['id'=>$exists->id]);
        } else {
            $self              = new self;
            $self->player_id   = $playerId;
            $self->soldier_id  = $soldierId;
            $self->num         = $num;
            $self->create_time = $self->update_time = date('Y-m-d H:i:s');
            $self->save();
        }
        $this->clearDataCache($playerId);
        return true;
    }
    /**
     * 获取已经入院的伤兵
     * @param  int $playerId 
     * @return int           
     */
    public function getCuringNum($playerId){
        $sum = 0;
        $all = $this->getByPlayerId($playerId);
        if($all) {
            $sum = array_sum(Set::extract('/num', $all));
        }
        return $sum;
    }
    /**
     * 治疗伤兵
     * @param  int $playerId
     * @param  bool $inTimeFlag
     * @param  array $soldierArr  [["id"=>1,"soldier_id"=>1001, "num"=>2],["id"=>2,"soldier_id"=>1002,"num"=>1]]
     * @return [type]             [description]
     */
    public function cure($playerId, $injuredSoldierArr, $inTimeFlag=false){
        $db = $this->di['db'];
        $PlayerSoldier = new PlayerSoldier;
        dbBegin($db);
        $cureNumberForDailyMission = 0;
        foreach($injuredSoldierArr as $k=>$v){
            //扣伤兵
            $affectedRows = $this->updateAll(['num'=>'num-'.$v['num'], 'update_time'=>qd()], ['id'=>$v['id'], 'num >'=>$v['num']]);
            if($affectedRows==0) {
                if($this->updateAll(['num'=>0, 'update_time'=>qd()], ['id'=>$v['id'], 'num'=>$v['num']])){//更新到0的时候删除该字段
                    self::findFirst($v['id'])->delete();
                } else {
                    goto errCatch;
                }
            }
            $cureNumberForDailyMission += $v['num'];
            //回到player_soldier里
            if($inTimeFlag) {
                $PlayerSoldier->updateSoldierNum($playerId, $v['soldier_id'], $v['num']);
            }
        }
        dbCommit($db);
        (new PlayerMission)->updateMissionNumber($playerId, 11, $cureNumberForDailyMission);//每日任务

        (new PlayerTarget)->updateTargetCurrentValue($playerId, 26, $cureNumberForDailyMission);//更新新手目标任务

        $this->clearDataCache($playerId);
        //日志
        $PlayerCommonLog = new PlayerCommonLog;
        $PlayerCommonLog->add($playerId, ['type'=>'[伤兵]医疗兵', 'memo'=>['injured_soldier'=>$injuredSoldierArr, 'inTimeFlag'=>$inTimeFlag]]);
        return false;
        errCatch: {
            dbRollback($db);
            $this->clearDataCache($playerId);
            $PlayerSoldier->clearDataCache($playerId);
        }
    }

    /**
     * 解雇伤兵
     */
    public function fire($playerId, $injuredSoldierArr){
        $db = $this->di['db'];
        dbBegin($db);
        foreach($injuredSoldierArr as $k=>$v){
            //扣伤兵
            $affectedRows = $this->updateAll(['num'=>'num-'.$v['num'], 'update_time'=>qd()], ['id'=>$v['id'], 'num >'=>$v['num']]);
            if($affectedRows==0) {
                if($this->updateAll(['num'=>0, 'update_time'=>qd()], ['id'=>$v['id'], 'num'=>$v['num']])){//更新到0的时候删除该字段
                    self::findFirst($v['id'])->delete();
                } else {
                    goto errCatch;
                }
            }
        }
        dbCommit($db);
        $this->clearDataCache($playerId);
        //日志
        $PlayerCommonLog = new PlayerCommonLog;
        $PlayerCommonLog->add($playerId, ['type'=>'[伤兵]解雇兵', 'memo'=>['injured_soldier'=>$injuredSoldierArr]]);
        return false;
        errCatch: {
            dbRollback($db);
            $this->clearDataCache($playerId);
        }
    }

    /**
     * 升级在医院中尚未治疗的伤兵
     *
     * @param $playerId
     * @param $oldSoldierId
     * @param $newSoldierId
     */
    public function lvUpInjuredSoldier($playerId, $oldSoldierId, $newSoldierId){
        $affectedRows = $this->updateAll(['soldier_id'=>$newSoldierId], ['player_id'=>$playerId, 'soldier_id'=>$oldSoldierId]);
        if($affectedRows>0) {
            //日志
            (new PlayerCommonLog)->add($playerId, ['type'=>'[在医院中尚未医疗的兵的升级]', 'memo'=>['oldSoldierId'=>$oldSoldierId, 'newSoldierId'=>$newSoldierId]]);
            $this->clearDataCache($playerId);
        }
    }

}