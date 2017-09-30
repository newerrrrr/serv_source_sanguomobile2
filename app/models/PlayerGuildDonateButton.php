<?php
//玩家联盟捐献按钮
class PlayerGuildDonateButton extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time', 'rowversion');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
		$this->rowversion = uniqid();
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}
	
	public function getByPlayerId($playerId, $forDataFlag=false){
		 $playerGuildDonate = Cache::getPlayer($playerId, __CLASS__);
        if(!$playerGuildDonate) {
            $playerGuildDonate = self::find(["player_id={$playerId}"])->toArray();
            Cache::setPlayer($playerId, __CLASS__, $playerGuildDonate);
        }
		$playerGuildDonate = $this->adapter($playerGuildDonate);
        if($forDataFlag) {
            return filterFields($playerGuildDonate, $forDataFlag, $this->blacklist);
        } else {
            return $playerGuildDonate;
        }
    }
	
	public function getByScienceType($playerId, $scienceType, $forDataFlag=false){
		$data = $this->getByPlayerId($playerId, $forDataFlag);
		if(!$data)
			return false;
		foreach($data as $_d){
			if($_d['science_type'] == $scienceType){
				return $_d;
			}
		}
		return false;
	}
	
    /**
     * 新增
     * 
     * @param <type> $playerId 
     * @param <type> $itemId 
     * 
     * @return <type>
     */
	public function add($playerId, $scienceType, $level=0, $btn1Cost=0, $btn1Unit=0, $btn1Num=0, $btn2Cost=0, $btn2Unit=0, $btn2Num=0, $btn2Counter=0, $btn3Cost=0, $btn3Unit=0, $btn3Num=0, $btn3Counter=0){
		if($this->find(array('player_id='.$playerId.' and science_type='.$scienceType))->toArray()){
			return false;
		}
		$ret = $this->create(array(
			'player_id' => $playerId,
			'science_type' => $scienceType,
			'level' => $level,
			'btn1_cost' => $btn1Cost,
			'btn1_unit' => $btn1Unit,
			'btn1_num' => $btn1Num,
			'btn2_cost' => $btn2Cost,
			'btn2_unit' => $btn2Unit,
			'btn2_num' => $btn2Num,
			'btn2_counter' => $btn2Counter,
			'btn3_cost' => $btn3Cost,
			'btn3_unit' => $btn3Unit,
			'btn3_num' => $btn3Num,
			'btn3_counter' => $btn3Counter,
			'create_time' => date('Y-m-d H:i:s'),
			//'rowversion' => '',
		));
		if(!$ret)
			return false;
		return $this->affectedRows();
	}
		
	public function updateData($level, $btn1Cost, $btn1Unit, $btn1Num, $btn2Cost, $btn2Unit, $btn2Num, $btn2Counter, $btn3Cost, $btn3Unit, $btn3Num, $btn3Counter){
		$now = date('Y-m-d H:i:s');
		$ret = $this->saveAll(array(
			'level' => $level,
			'btn1_cost' => $btn1Cost,
			'btn1_unit' => $btn1Unit,
			'btn1_num' => $btn1Num,
			'btn2_cost' => $btn2Cost,
			'btn2_unit' => $btn2Unit,
			'btn2_num' => $btn2Num,
			'btn2_counter' => $btn2Counter,
			'btn3_cost' => $btn3Cost,
			'btn3_unit' => $btn3Unit,
			'btn3_num' => $btn3Num,
			'btn3_counter' => $btn3Counter,
			'update_time'=>$now,
			'rowversion'=>uniqid()
		), "id={$this->id} and rowversion='{$this->rowversion}'");
		$this->clearDataCache();
		if(!$ret || !$this->affectedRows())
			return false;
		return true;
	}
	
	public function randBtn($btn, $scienceType){
		$adc = (new Starting)->dicGetOne('alliance_donate_clean')*1;
		$Cost = new Cost;
		//读取科技配置
		$AllianceScience = new AllianceScience;
		$allianceScience = $AllianceScience->getByScienceType($scienceType, $btn['level']);
		if(!$btn['btn1_unit']){
			shuffle($allianceScience['button1_cost_id']);
			$cost = $Cost->dicGetOne($allianceScience['button1_cost_id'][0]);
			$btn['btn1_cost'] = $cost['cost_id']*1;
			$btn['btn1_unit'] = $cost['cost_type']*1;
			$btn['btn1_num'] = $cost['cost_num']*1;
		}
		if(!$btn['btn2_unit'] && lcg_value1() <= 0.18){
			shuffle($allianceScience['button2_cost_id']);
			$cost = $Cost->dicGetOne($allianceScience['button2_cost_id'][0]);
			$btn['btn2_cost'] = $cost['cost_id']*1;
			$btn['btn2_unit'] = $cost['cost_type']*1;
			$btn['btn2_num'] = $cost['cost_num']*1;
			if($btn['btn2_unit'] == 7)
				$btn['btn2_counter'] = $adc;
			else
				$btn['btn2_counter'] = 0;
		}
		if(!$btn['btn3_unit'] && lcg_value1() <= 0.06){
			shuffle($allianceScience['button3_cost_id']);
			$cost = $Cost->dicGetOne($allianceScience['button3_cost_id'][0]);
			$btn['btn3_cost'] = $cost['cost_id']*1;
			$btn['btn3_unit'] = $cost['cost_type']*1;
			$btn['btn3_num'] = $cost['cost_num']*1;
			if($btn['btn3_unit'] == 7)
				$btn['btn3_counter'] = $adc;
			else
				$btn['btn3_counter'] = 0;
		}
		return $btn;
	}
}