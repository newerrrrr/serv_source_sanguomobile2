<?php
/**
 * 玩家装备-宝物
 */
class PlayerEquipMaster extends ModelBase{
	const STATUS_ON = 1;
	const STATUS_OFF = 0;
	public $blacklist = array('create_time', 'update_time');
	
	public function beforeSave() {
		$this->update_time = date('Y-m-d H:i:s', time());
	}

    /**
     * 获取当前model的数据
     * 
     * @param   int    $playerId    player id
     * @param   bool    $forDataFlag    给data包用，传回格式一定是find出来的格式，如果是findFirst，请在子类里覆盖实现
     * @return  array    description
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $modelClassName = get_class($this);
        $re = Cache::getPlayer($playerId, $modelClassName);
        if(!$re) {
            $re = self::find(["player_id={$playerId}"])->toArray();
            $PlayerEquipMasterSkill = new PlayerEquipMasterSkill;
            foreach($re as $k=>&$v) {
                $pems = $PlayerEquipMasterSkill->getPlayerEquipMasterSkill($v['id']);
            	$v['equip_skill'] = $pems;
            }
            $re = $this->adapter($re);
            Cache::setPlayer($playerId, $modelClassName, $re);
        }
        return filterFields($re, $forDataFlag, $this->blacklist);
    }
	/**
	 * 玩家获得一件主公装备
	 * 
	 * @param  int $playerId      
	 * @param  int $equipMasterId 
	 */
	public function newPlayerEquipMaster($playerId, $equipMasterId){
		$self = new self;
        $PlayerEquipMasterSkill = new PlayerEquipMasterSkill;
        // $self->equip_skill = json_encode($equipSkill);

        $self->player_id = $playerId;
        $self->equip_master_id = $equipMasterId;
        $self->status = 0;
        $self->create_time = date('Y-m-d H:i:s', time());
        $self->save();
        $pemId = $self->id;

        //主公装备附加值
        $equipMaster = (new EquipMaster)->dicGetOne($equipMasterId);
        $equipSkills = explode(',', $equipMaster['equip_skill_id']);
        foreach($equipSkills as $equipSkillId) {
            $es = (new EquipSkill)->dicGetOne($equipSkillId);
            if($es) {
                $buffIdArr = $es['skill_buff_id'];
                $extraVal = mt_rand($es['min'], $es['max']);
                foreach($buffIdArr as $buffId) {
                    $PlayerEquipMasterSkill->newPlayerEquipMasterSkill($pemId, $equipSkillId, $buffId, $extraVal);
                }
            }
        }

        $this->clearDataCache($playerId);
		return true;
	}

	/**
	 * 更改装备状态
	 * @param    int  $playerId      
	 * @param  int $equipMasterId 
	 * @param  int $status        
	 */
	public function changeEquipMasterStatusAndPosition($playerId, $id, $status, $position){
        if($status==self::STATUS_ON) {//检测该位置是否有其他装备
            $re = self::find("player_id={$playerId} and position={$position}");
            if($re->toArray()) {
                return false;
            }
        }
        //buff更改
        $PlayerEquipMasterSkill = new PlayerEquipMasterSkill;
        $PlayerBuff             = new PlayerBuff;
        $EquipSkill             = new EquipSkill;
        if($status==self::STATUS_ON) {//穿上宝物
            $affectedRows = $this->updateAll(['status'=>$status, 'position'=>$position, 'update_time'=>qd()],['id'=>$id, 'player_id'=>$playerId, 'status'=>self::STATUS_OFF]);
    		$this->clearDataCache($playerId);
            
            //新手目标
            $re = $this->getByPlayerId($playerId);
            $targetValue = 0;
            foreach($re as $k=>$v) {
                if($v['status']==1) {
                    $targetValue++;
                }
            }
            (new PlayerTarget)->updateTargetCurrentValue($playerId, 17, $targetValue, false);//更新新手目标任务

            $minusFlag = false;
        } elseif($status==self::STATUS_OFF) {//卸下宝物
            $affectedRows = $this->updateAll(['status'=>$status, 'position'=>$position, 'update_time'=>qd()],['id'=>$id, 'player_id'=>$playerId, 'status'=>self::STATUS_ON]);
            $minusFlag = true;
            $this->clearDataCache($playerId);
        }
        if($affectedRows) {
            (new Player)->refreshPower($playerId, 'master_power');
            $singleEquipMaster = $this->getSingleEquipMaster($playerId, $id);
            foreach($singleEquipMaster['equip_skill'] as $k=>$v) {
                $equipSkill = $EquipSkill->dicGetOne($k);
                $buffIdArr = $equipSkill['skill_buff_id'];
                foreach($buffIdArr as $buffId) {
                    $PlayerBuff->setPlayerBuff($playerId, $buffId, $v, $minusFlag);
                }
            }
        }

	}
	/**
	 * 返回某一条宝物装备
	 * @param  int $playerId      
	 * @param  int $equipMasterId 宝物装备id
	 * @return array                
	 */
	public function getSingleEquipMaster($playerId, $id){
		$info = $this->getByPlayerId($playerId);
		foreach($info as $k=>$v) {
			if($v['id']==$id) {
				return $v;
			}
		}
		return false;
	}
	/**
	 * 更换宝物装备
	 * @param  [type] $playerId         
	 * @param  [type] $oldId  0 或者旧装备的id
     * @param  [type] $newId  新装上去的id
	 * @param  [type] $position  位置
	 * @return [type]                   
	 */
	public function changeToNewEquipMaster($playerId, $oldId, $newId, $position){
		if($oldId) {
			$this->changeEquipMasterStatusAndPosition($playerId, $oldId, self::STATUS_OFF, -1);
		}
		$this->changeEquipMasterStatusAndPosition($playerId, $newId, self::STATUS_ON, $position);
		$this->clearDataCache($playerId);
	}

    /**
     * 出售主公宝物
     *
     * @param $playerId
     * @param $id
     *
     * @return int
     */
	public function sellEquipMaster($playerId, $id){
        $info = $this->getSingleEquipMaster($playerId, $id);
        $Player = new Player;
        if($info) {
            $equipMasterId = $info['equip_master_id'];
            $equipMaster   = (new EquipMaster)->dicGetOne($equipMasterId);
            $selldrop      = $equipMaster['selldrop'];
            $pointArr      = ['point' => $selldrop];
            $Player->updateResource($playerId, $pointArr);//加锦囊
            self::find(["id=:id:", 'bind' => ['id' => $id]])->delete();
            PlayerEquipMasterSkill::find(['player_equip_master_id=:id:', 'bind' => ['id' => $id]])->delete();
            $this->clearDataCache($playerId);
            //日志
            $PlayerCommonLog = new PlayerCommonLog;
            $PlayerCommonLog->add($playerId, ['type'=>'[出售宝物]获得锦囊', 'memo'=>['total_num'=>$selldrop, 'equip_master_id'=>$equipMaster['id'],'desc1'=>$equipMaster['desc1']]]);
        }
        return 0;

    }

}