<?php
class CityBattle extends CityBattleModelBase{
	CONST STATUS_DEFAULT = 0;
    CONST STATUS_READY_SEIGE = 1;
    CONST STATUS_SEIGE = 2;
    CONST STATUS_CLAC_SEIGE = 3;
	CONST STATUS_READY_MELEE = 4;
    CONST STATUS_MELEE = 5;
    CONST STATUS_CLAC_MELEE = 6;
    CONST STATUS_FINISH = 7;
	
	static $endBattle = false;

	public $winScore = 2000;
	
    //获得战斗信息
    public function getBattle($id){
        $ret  = self::findFirst(["id={$id}"]);
        if($ret){
            $ret = $ret->toArray();
            return $ret;
        }else{
            return false;
        }
    }

    //根据cityId获取battle
    public function getBattleByCityId($cityId){
        $ret = self::find(["city_id={$cityId} and status<".self::STATUS_FINISH])->toArray();
        if($ret){
            $re = $ret[0];
        }else{
            return false;
        }
        return $re;
    }

    //根据cityId获取battleId
    public function getRoundBattleList($roundId){
        $ret = self::find(["round_id={$roundId}"])->toArray();
        if($ret){
            return $ret;
        }else{
            return false;
        }
    }

    public function updateStatus($cityBattleId, $oldStatus, $newStatus){
        $re = $this->updateAll(['status'=>$newStatus],['id'=>$cityBattleId, 'status'=>$oldStatus]);
        return $re;
    }

    public function addNew($data){
        $self                       = new self;
        $self->round_id            = $data['round_id'];
        $self->city_id             = $data['city_id'];
        $self->map_type            = 1;
        $self->camp_id             = $data['camp_id'];
        $self->max_num             = $data['max_num'];
        $self->start_time          = $data['start_time'];
        $self->defend_camp         = $data['defend_camp'];
        $self->create_time         = date("Y-m-d H:i:s");
        $self->update_time         = date("Y-m-d H:i:s");
        $self->save();
        return $self->id;
    }

    public function createCityBattle($roundId){
        $City = new City;
        $cityList = $City->dicGetAll();
        $CountryBasicSetting = new CountryBasicSetting();
        $sTime = $CountryBasicSetting->getValueByKey('match_start');//开始时间
        $startTime = date("Y-m-d ".$sTime, strtotime("+1 day"));
        foreach($cityList as $k=>$v){
            if($v['city_type']==1){
                continue;
            }
            $data = [
                'round_id'      =>$roundId,
                'city_id'       =>$k,
                'camp_id'       =>$v['camp_id'],
                'max_num'       =>$v['join_max_num'],
                'start_time'    =>$startTime,
                'defend_camp'   =>$v['camp_id'],
            ];
            $this->addNew($data);
        }
    }

    public function updateSignNum($cityBattleId, $campId, $op="add"){
        $ret = self::find(["id={$cityBattleId}"])->toArray();
        if(empty($ret)){
            false;
        }
        switch($campId){
            case 1:
                $campStr = "sign_num_wei";
                break;
            case 2:
                $campStr = "sign_num_shu";
                break;
            case 3:
                $campStr = "sign_num_wu";
                break;
            default:
                return false;
        }
        if($op=="add"){
            $affectedRow = $this->updateAll([$campStr=>"{$campStr}+1"], ['id'=>$cityBattleId, $campStr." <"=>'max_num']);
        }elseif($op=="dec"){
            $affectedRow = $this->updateAll([$campStr=>"{$campStr}-1"], ['id'=>$cityBattleId, $campStr." >"=>"0"]);
        }else{
            return false;
        }

        if($affectedRow>0){
            return true;
        }else{
            return false;
        }
    }

	//本轮战斗列表
    public function getCurrentBattleIdList(){
        $result = [];
        $ret = self::find(["status<".self::STATUS_FINISH]);
        if(!empty($ret)){
            $re = $ret->toArray();
            foreach($re as $v){
                $result[] = $v['id'];
            }
        }
        return $result;
    }
	
	public function inSeige($battleId){
		if(is_numeric($battleId)){
			$battleInfo = $this->getBattle($battleId);
		}else{
			$battleInfo = $battleId;
		}
        if(in_array($battleInfo['status'], [self::STATUS_READY_SEIGE , self::STATUS_SEIGE, self::STATUS_CLAC_SEIGE])){
            return true;
        }elseif(in_array($battleInfo['status'], [self::STATUS_READY_MELEE, self::STATUS_MELEE, self::STATUS_CLAC_MELEE])){
            return false;
        }
	}

	//是否可以迁城
    public function canChangeLocation($battleId){
        if(is_numeric($battleId)){
			$status = $this->getBattle($battleId)['status'];
		}else{
			$status = $battleId['status'];
		}
        return in_array($status, [self::STATUS_READY_SEIGE, self::STATUS_SEIGE, self::STATUS_READY_MELEE, self::STATUS_MELEE]);
    }
	
	//是否正在战斗中
    public function isActivity($battleId, &$battleInfo=''){
		if(is_numeric($battleId)){
			$status = $this->getBattle($battleId)['status'];
		}else{
			$status = $battleId['status'];
		}
        return ($status==self::STATUS_SEIGE || $status==self::STATUS_MELEE || self::$endBattle);
    }

	public function isAttack($campId, $battleId){
		$cb = $this->getBattle($battleId);
		if($this->inSeige($cb)){
			if($cb['camp_id']==$campId){
				return false;
			}else{
				return true;
			}
		}else{
			if($cb['attack_camp'] == $campId){
				return true;
			}else{
				return false;
			}
		}
    }	

	public function alter($id, array $fields, $condition=[]){
		if(isset($fields['start_time'])){
			$fields['start_time'] = "'".$fields['start_time']."'";
		}
		if(isset($fields['score_time'])){
			$fields['score_time'] = "'".$fields['score_time']."'";
		}
		$fields['update_time'] = "'".date('Y-m-d H:i:s')."'";
		$condition['id'] = $id;
        return $this->updateAll($fields, $condition);
    }
	
	public function endBattle($battleId){
		$cb = $this->findFirst($battleId);
		$winScore = $this->winScore;//内城战获胜阈值
		
		$sql = 'update '.$this->getSource().' set '.
			'door_battle_time = if(status='.self::STATUS_SEIGE.', '.(time()-strtotime($cb->real_start_time)).', door_battle_time),'.//城门战时间
			'defend_score = if(status='.self::STATUS_SEIGE.' and camp_id>0, door_battle_time*0.2, defend_score),'.//防守规定时间，守方加积分
			'melee_end_time = if(status='.self::STATUS_MELEE.', "'.date('Y-m-d H:i:s').'", "0000-00-00 00:00:00"),'.
			'status = status+1,'.
			'update_time="'.date('Y-m-d H:i:s').'"'.
		'where id='.$battleId.' and ((status='.self::STATUS_SEIGE.' and attack_camp>0 and defend_camp>0) or (status='.self::STATUS_MELEE.' and (attack_score>='.$winScore.' or defend_score>='.$winScore.')) )';
		$ret = $this->sqlExec($sql);
		//$ret = $this->updateAll($updateData, ['id'=>$battleId, 'status'=>[self::STATUS_SEIGE, self::STATUS_MELEE], 'attack_camp >'=>0, 'defend_camp >'=>0]);
		if($ret){
			if($cb->status == self::STATUS_SEIGE){
				self::$endBattle = true;
				(new CityBattleCommonLog)->add($battleId, 0, 0, '城门战结束[破门]');
			}else{
				self::$endBattle = true;
				(new CityBattleCommonLog)->add($battleId, 0, 0, '内城战结束');
			}
		}
		return $ret;
	}
	
	public function updateFirstBlood($cb, $fromPlayer, $toPlayer){
		if($cb['status'] == self::STATUS_SEIGE){
			$field = 'first_blood_1';
		}else{
			$field = 'first_blood_2';
		}
		$ret = $this->updateAll([$field=>$toPlayer['player_id']], ['id'=>$cb['id'], 'status'=>[self::STATUS_SEIGE, self::STATUS_MELEE], $field=>0]);
		if($ret){
			$Player = new CityBattlePlayer;
			$Player->battleId = $cb['id'];
			$members = $Player->find(['battle_id='.$Player->battleId.' and status>0'])->toArray();
			$playerIds = [];
			foreach($members as $_d){
				$_serverId = CityBattlePlayer::parsePlayerId($_d['player_id'])['server_id'];
				$playerIds[$_serverId][] = $_d['player_id'];
			}
			foreach($playerIds as $_serverId => $_playerIds){
				crossSocketSend($_serverId, ['Type'=>'citybattle', 'Data'=>['playerId'=>$_playerIds, 'type'=>'firstblood', 'fromNick'=>$fromPlayer['nick'], 'toNick'=>$toPlayer['nick'], 'fromAvatar'=>$fromPlayer['avatar_id']]]);
			}
		}
	}
	
	public function addKill($battleId, $campId, $kill){
		$field = ['camp_'.$campId.'_kill'=>'camp_'.$campId.'_kill+'.$kill];
		$field['update_time'] = "'".date('Y-m-d H:i:s')."'";
		return $this->updateAll($field, ['id'=>$battleId, 'status'=>[self::STATUS_SEIGE, self::STATUS_MELEE]]);
	}
	
	public function updateDoor($battleId, $campId){
		$this->sqlExec('set @dc=0');
		$ret = $this->sqlExec('update city_battle set defend_camp=if(defend_camp=0, @dc:='.$campId.', defend_camp), attack_camp=if(@dc<>0 or defend_camp='.$campId.', 0, '.$campId.'), door'.$campId.'=1 where id='.$battleId);
		return $ret;
	}
}
?>