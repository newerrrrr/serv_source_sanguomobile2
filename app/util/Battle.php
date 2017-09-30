<?php
/**
 * 战斗
 */
class Battle{

	const RANGE_PARA = 0;//射程修正系数

 	const SKILL_SHIELD            = 20001;//特技盾牌 
    const SKILL_GRIT              = 20002;//特技体魄
    const SKILL_IRON_ARMOR        = 20003;//特技铁甲
    const SKILL_PIKE              = 20004;//特技长矛////
    const SKILL_CRITICAL_HIT      = 20005;//特技致命一击////
    const SKILL_BRUTCAL_ATTACK    = 20006;//特技猛攻
    const SKILL_CHARGE            = 20007;//特技冲锋////
    const SKILL_DODGE             = 20008;//特技闪避////
    const SKILL_DEADLY_MARCH      = 20009;//特技奔袭
    const SKILL_RESOURCE_BATTLE   = 20010;//特技资源战////
    const SKILL_DOUBLE_ATTACK     = 20011;//特技连击////
    const SKILL_PUNCTURE          = 20012;//特技穿刺////
    const SKILL_HEAVY_ATTACK      = 20013;//特技强击
    const SKILL_DEFENSE           = 20014;//特技守城////
    const SKILL_AIMING            = 20015;//特技瞄准
    const SKILL_POWER_ATTACK      = 20016;//特技重击
    const SKILL_SIEGE_ATTACK      = 20017;//特技攻城////
    const SKILL_ACCUMULATED_POWER = 20018;//特技蓄力
    const SKILL_ARMORNING         = 20019;//特技装甲
    const SKILL_SIEGE_DEFENSE     = 20020;//特技攻城防御////
    const SKILL_LOAD              = 20021;//特技负载

    public $crossBattleId= 0;//跨服战战场id
    public $cityBattleId = 0;//城战战场id

	/**
	 * 数组格式
	 * $unitList = [
	*  [
	*  	'from'                => 0,//1=army 2=playerSoldier  
	*		'playerId'        => 0,
	*		'generalId'       => 0,
    *       'generalStar'     => 0,//武将星级
	*		'soldierId'       => 0,
	*		'power'           => 0, 
	*		'attack'          => 0, 
	*		'defend'          => 0, 
	*		'life'            => 0 
	*	 	'num'             => 0, 
	*		'maxNum'          => 0,
	*		'generalMaxNum'	  => 0,//武将可带兵最大数量
	*		'type'            => 0,
	*		'range'           => 0, 
	*		'skill'           => [], 
	*		'takeDamage'      => 0, 
	*		'injuredNum'      => 0,
	*		'killedNum'       => 0,
    *       'reviveNum'       => 0,
	*		'killList'        => [],
	*		'killWeight'      => 0,
	*		'totalTakeDamage' => 0,
	*		'totalDamage'	  => 0,
	*		'godSkill'        => [],
	*	],
	 *	.....
	 * ];
	 *  $buildList = [
	 *  [
	 *		'playerId'   => 0,
	 *		'trapId'  	 => 0, 
	 *		'attack'     => 0, 
	 *	 	'num'        => 0, 
	 *		'maxNum'     => 0,
	 *		'type'       => 0,
	 *		'takeDamage' => 0, 
	 *		'killedNum'  => 0, 
	 *		'killList'   => [],
	 *	],
	 *	.....
	 * ];
	 */
	
	public $lvDamageProtected = 0;//根据玩家城堡等级做伤害保护

	public $godGeneralSkillArr = [
		'attack'=>[
			//'0'=>['pid'=>0, 'gid'=>0, 'para'=>0],
			//'1'=>['pid'=>0, 'gid'=>0, 'para'=>0],
		], 
		'defend'=>[
			
		]
	];//发动技能的神武将列表

	public $reduceDefendBuff = ['attack'=>0, 'defend'=>0];//士兵减少防御debuff
	public $reduceLifeBuff = ['attack'=>0, 'defend'=>0];//士兵减少生命debuff
	public $reduceAttackBuff = ['attack'=>0, 'defend'=>0];//士兵减少攻击debuff
	public $reduceGeneralForce = ['attack'=>0, 'defend'=>0];//武将降低武力
	public $removeGeneralArr = ['attack'=>[],'defend'=>[]];//移除武将列表
    public $rangeNumList = ['attack'=>[], 'defend'=>[]];//攻击射程统计数据

	/**
	 * 战斗核心逻辑
	 * @param  [type] $attackPlayerList 
	 * @param  [type] $defendPlayerList 
	 * 以上 两个参数：
	 * 如果是玩家的作战队列，格式为数组['pid'=>'armyId','p2id'=>'army2Id','p3id'=>'army3Id'...]，防守城堡时，城堡所有者armyId设为0；
	 * 如果防守联盟建筑时，堡垒中没有作战单位，则defendPlayerList为int型联盟id；
	 * 如果是NPC作战队列，格式为 1——['npcId'=>N, 'npcNum'=>N], npcNum不填或填0视为满编部队， 2——['townId'=>N]指王战中城堡id，3——['waveId'=>N]黄巾起义波次数
	 * @param  [type] $battleType 1攻城战 2资源战 3堡垒战斗 4王战攻击NPC 5王战防御NPC 6王战争夺堡垒 7普通攻击NPC 8攻击Boss 9 据点战 10 黄巾起义 11 跨服战 13 城战
	 * @return [type] 
	 */
	public function battleCore($attackPlayerList, $defendPlayerList, $battleType, $extra = ['basePosition'=>['x'=>0, 'y'=>0]]){
		switch ($battleType) {
			case 1:
				$attacker = "Player";
				$defender = "Player";
				$attackDieRate = 1;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 2:
				$attacker = "Player";
				$defender = "Player";
				$attackDieRate = 1;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 3:
				$attacker = "Player";
				$defender = "Guild";
				$attackDieRate = 1;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 4:
				$attacker = "Player";
				$defender = "Npc";
				$attackDieRate = 0;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 5:
				$attacker = "Npc";
				$defender = "Guild";
				$attackDieRate = 0;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 6:
				$attacker = "Player";
				$defender = "Guild";
				$attackDieRate = 0;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 7:
				$attacker = "Player";
				$defender = "Npc";
				$attackDieRate = 0;
				$defendDieRate = 1;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 8:
				$attacker = "Player";
				$defender = "Npc";
				$attackDieRate = 0;
				$defendDieRate = 1;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = false;
				break;
			case 9:
				$attacker = "Player";
				$defender = "Player";
				$attackDieRate = 0;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = true;
				break;
			case 10:
				$attacker = "Npc";
				$defender = "Guild";
				$attackDieRate = 0;
				$defendDieRate = 0;
                $this->crossBattleId = 0;
                $this->cityBattleId = 0;
				$noDamage = true;
				break;
            case 11:
                $attacker = "Player";
                $defender = "Player";
                $attackDieRate = 1;
                $defendDieRate = 1;
                $this->crossBattleId = $extra['battleId'];
                $this->cityBattleId = 0;
                $noDamage = false;
                break;
            case 12:
                $attacker = "Player";
                $defender = "Player";
                $attackDieRate = 1;
                $defendDieRate = 1;
                $this->crossBattleId = 0;
                $this->cityBattleId = $extra['battleId'];
                $noDamage = false;
                break;
			default:
				return false;
		}
		if($this->crossBattleId>0){
            $Player = new CrossPlayer;
            $Player->battleId = $this->crossBattleId;
        }elseif($this->cityBattleId>0){
            $Player = new CityBattlePlayer;
            $Player->battleId = $this->cityBattleId;
        }else{
            $Player = new Player;
        }
        $KingTown = new KingTown;

		switch ($attacker) {
			case 'Guild':
				if(!is_array($attackPlayerList)){
					$aPlayerId = 0;
					$aGuildId = $attackPlayerList;
					$attackUnitList = [];
					break;
				}else{
					$tmpArr = array_slice ($attackPlayerList, 0, 1, true);
					reset($tmpArr);
					$aPlayerId = key($tmpArr);
					$attackUnitList = $this->getAttackUnitList($attackPlayerList);
					break;
				}
			case 'Player':
				$tmpArr = array_slice ($attackPlayerList, 0, 1, true);
				reset($tmpArr);
				$aPlayerId = key($tmpArr);
				$attackUnitList = $this->getAttackUnitList($attackPlayerList);
				break;
			case 'Npc':
				$aPlayerId = 0;
				$aGuildId = 0;
				if(!empty($attackPlayerList['town_id'])){
					$townId = $attackPlayerList['town_id'];
					$kt = $KingTown->getById($townId);
					$attackUnitList = $this->getNpcUnitList(array('npc_id'=>$kt['npc_id'],'npc_num'=>$kt['npc_num']));
					$npcId = $kt['npc_id'];
				}elseif(!empty($attackPlayerList['wave_id'])){
					$waveId = $attackPlayerList['wave_id'];
					$attackUnitList = $this->getNpcUnitListByWaveId($waveId);
					$npcId = 0;
				}elseif(is_array($attackPlayerList)){
					$attackUnitList = $this->getNpcUnitList($attackPlayerList);
					$npcId = $attackPlayerList['npc_id'];
				}
				break;
		}

		switch ($defender) {
			case 'Guild':
				if(!is_array($defendPlayerList)){
					$dPlayerId = 0;
					$dGuildId = $defendPlayerList;
					$defendUnitList = [];
					$defendBuildList = [];
					break;
				}else{
					$tmpArr = array_slice ($defendPlayerList, 0, 1, true);
					reset($tmpArr);
					$dPlayerId = key($tmpArr);
					$defendUnitList = $this->getDefendUnitList($defendPlayerList);
					$defendBuildList = [];
					break;
				}
			case 'Player':
				$tmpArr = array_slice ($defendPlayerList, 0, 1, true);
				reset($tmpArr);
				$dPlayerId = key($tmpArr);
				$defendUnitList = $this->getDefendUnitList($defendPlayerList);
                if($this->crossBattleId==0 && $this->cityBattleId==0){
                    $defendBuildList = $this->getDefendBuildList($dPlayerId, $battleType);
                }else{
                    $defendBuildList= [];
                }
				break;
			case 'Npc':
				$dPlayerId = 0;
				$dGuildId = 0;
				if(!empty($defendPlayerList['town_id'])){
					$townId = $defendPlayerList['town_id'];
					$kt = $KingTown->getById($townId);
					$defendUnitList = $this->getNpcUnitList(array('npc_id'=>$kt['npc_id'],'npc_num'=>$kt['npc_num']));
					$npcId = $kt['npc_id'];
				}elseif(!empty($defendPlayerList['wave_id'])){
					$waveId = $defendPlayerList['wave_id'];
					$defendUnitList = $this->getNpcUnitListByWaveId($waveId);
					$npcId = 0;
				}elseif(is_array($defendPlayerList)){
					$defendUnitList = $this->getNpcUnitList($defendPlayerList);
					$npcId = $defendPlayerList['npc_id'];
				}
				$defendBuildList = [];				
				break;
		}

		/*if($this->crossBattleId==0 && $attacker!="Npc" && $defender!="Npc" && is_array($attackPlayerList) && is_array($defendPlayerList)){//保护性减伤
			$pcLevel = 1;
			$PlayerBuild = new PlayerBuild;
			foreach ($attackPlayerList as $key => $value) {
				$tmpLv = $PlayerBuild->getPlayerCastleLevel($key);
				if($tmpLv>$pcLevel){
					$pcLevel = $tmpLv;
				}
			}
			foreach ($defendPlayerList as $key => $value) {
				$tmpLv = $PlayerBuild->getPlayerCastleLevel($key);
				if($tmpLv>$pcLevel){
					$pcLevel = $tmpLv;
				}
			}
			$this->lvDamageProtected = 1-pow(2.114, 1-$pcLevel/10);
			if($this->lvDamageProtected<0) $this->lvDamageProtected = 0;
			if($this->lvDamageProtected>0.9) $this->lvDamageProtected = 0.9;
		}*/

		$this->calcGeneralSkill($attackUnitList, true);
		$this->calcGeneralSkill($defendUnitList, false);

		$attackUnitList = $this->calcSoldierAttr($attackUnitList, $battleType, true);
		$defendUnitList = $this->calcSoldierAttr($defendUnitList, $battleType, false);

        $PlayerBuff = new PlayerBuff;
        if($this->crossBattleId==0 && $this->cityBattleId==0){
            $aBuff = $PlayerBuff->getBattleBuff($aPlayerId);
            $dBuff = $PlayerBuff->getBattleBuff($dPlayerId);
        }else{
            $aBuff = $PlayerBuff->getBattleBuff(0);
            $dBuff = $PlayerBuff->getBattleBuff(0);
        }
        $aPlayer = ($aPlayerId==0)?['guild_id'=>$aGuildId]:$Player->getByPlayerId($aPlayerId);
        $dPlayer = ($dPlayerId==0)?['guild_id'=>$dGuildId]:$Player->getByPlayerId($dPlayerId);

		$winner = $this->checkWin($attackUnitList, $defendUnitList, $battleType);
		if($winner=="Attacker"){
			$win = true;
            $drawFlag = false;
			$allDead = false;
            $noobProtect = false;
		}elseif($winner=="Defender"){
			$win = false;
            $drawFlag = false;
			$allDead = true;
            $noobProtect = false;
		}else{
			$win = false;
			$drawFlag = false;
			$allDead = false;
			for($turn=1; $turn<=5; $turn++){
				$re = $this->oneTurnBattle($attackUnitList, $defendUnitList, $defendBuildList, $aBuff, $dBuff, $battleType, $turn);
				$attackUnitList = $re['attackUnitList'];
				$defendUnitList = $re['defendUnitList'];
				$defendBuildList = $re['defendBuildList'];
				if($re['win']=="Attacker"){
					$win = true;
					break;
				}elseif($re['win']=="Defender"){
					if($turn==1){
						$allDead = true;
					}
					break;
				}elseif($turn==5){
					$drawFlag = true;
				}
			}


			// log4cli("================Battle-Result-Begin=======================\n");
			// log4cli("atkList\n");
			// log4cli($attackUnitList);
			// log4cli("\ndefList:\n");
			// log4cli($defendUnitList);
			// log4cli("\n=================Battle-Result-End=======================\n");	

			//扣除防禦損失陷阱
            if($this->crossBattleId==0 && $this->cityBattleId==0){
                $PlayerTrap = new PlayerTrap;
                foreach ($defendBuildList as $key => $value) {
                    $PlayerTrap->updateTrapNum($dPlayerId, $value['trapId'], $value['num']-$value['maxNum']);
                }
            }

			if(in_array($battleType, [1,2])){
                $basiceProtectBuffRate = 1-(80-$extra['defendCastleLv'])/100;
                $attackProtectBuffRate = $basiceProtectBuffRate;
                $np = $PlayerBuff->getPlayerBuff($dPlayerId, "noob_protection");
                if($np>0){
                    $noobProtect = true;
                }else{
                    $noobProtect = false;
                }
                $defendProtectBuffRate = $basiceProtectBuffRate*(1-$np);
            }else{
                $attackProtectBuffRate = 1;
                $defendProtectBuffRate = 1;
                $noobProtect = false;
            }

			//扣除玩家士兵
			//然后转换伤兵
			if(!$noDamage){
                if($this->crossBattleId>0){
                    $PlayerArmyUnit = new CrossPlayerArmyUnit;
                    $PlayerSoldier = new CrossPlayerSoldier;
                    $PlayerArmyUnit->battleId = $this->crossBattleId;
                    $PlayerSoldier->battleId = $this->crossBattleId;
                }elseif($this->cityBattleId>0){
                    $PlayerArmyUnit = new CityBattlePlayerArmyUnit;
                    $PlayerSoldier = new CityBattlePlayerSoldier;
                    $PlayerArmyUnit->battleId = $this->cityBattleId;
                    $PlayerSoldier->battleId = $this->cityBattleId;
                }else{
                     $PlayerArmyUnit = new PlayerArmyUnit;
                     $PlayerSoldier = new PlayerSoldier;
                }

				if($attacker!='Npc'){
					foreach ($attackUnitList as $key => $value) {
                        if(22==$value['godSkill']['type'] && $value['num']>0){
                            $attackUnitList[$key]['num'] = $value['maxNum'];
                            $attackUnitList[$key]['killedNum'] = 0;
                            $attackUnitList[$key]['reviveNum'] = 0;
                        }else{
                            $attackUnitList[$key]['killedNum'] = ceil($value['killedNum']*$attackProtectBuffRate*(1-$value['soldierRestore']));
                            $attackUnitList[$key]['reviveNum'] = $value['killedNum']-$attackUnitList[$key]['killedNum'];
                        }
						if($value['from']==1){
							$PlayerArmyUnit->subSoldier($value['playerId'],  $value['generalId'], $attackUnitList[$key]['killedNum']);
						}elseif($value['from']==2){
							$PlayerSoldier->updateSoldierNum($value['playerId'], $value['soldierId'], (-1)*$attackUnitList[$key]['killedNum']);
						}
					}
					if($this->crossBattleId==0 && $this->cityBattleId==0 && $attackDieRate==1){
						$attackDieRate -= $aBuff['positive_battle_dead_trans_wounded'];
						$attackDieRate -= $PlayerBuff->useDeadtoWound($aPlayerId);
					}
					$attackUnitList = $this->changeInjureSoldier($attackUnitList, $attackDieRate);
				}

				if($defender!='Npc'){
					foreach ($defendUnitList as $key => $value) {
                        if(22==$value['godSkill']['type'] && $value['num']>0){
                            $defendUnitList[$key]['num'] = $value['maxNum'];
                            $defendUnitList[$key]['killedNum'] = 0;
                            $defendUnitList[$key]['reviveNum'] = 0;
                        }else{
                            $defendUnitList[$key]['killedNum'] = ceil($value['killedNum']*$defendProtectBuffRate*(1-$value['soldierRestore']));
                            $defendUnitList[$key]['reviveNum'] = $value['killedNum']-$defendUnitList[$key]['killedNum'];
                        }
                        if($value['from']==1){
                            $PlayerArmyUnit->subSoldier($value['playerId'],  $value['generalId'], $defendUnitList[$key]['killedNum']);
                        }elseif($value['from']==2){
                            $PlayerSoldier->updateSoldierNum($value['playerId'], $value['soldierId'], (-1)*$defendUnitList[$key]['killedNum']);
                        }
					}
					if($this->crossBattleId==0 && $this->cityBattleId==0 && $defendDieRate==1){
						$defendDieRate -= $dBuff['positive_battle_dead_trans_wounded'];
						$defendDieRate -= $PlayerBuff->useDeadtoWound($dPlayerId);
					}
					$defendUnitList = $this->changeInjureSoldier($defendUnitList, $defendDieRate);
				}elseif($battleType==4){
					foreach ($defendUnitList as $key => $value) {
						$KingTown->updateSoldierNum($townId, $value['killedNum']);
					}
				}
				

				//守城战时防御方主将接受伤兵
				if($battleType==1){
					$injureSoldier = [];
					foreach($defendUnitList as $k=>$v){
						if($dPlayerId==$v['playerId']){
							$injureSoldier[$k] = [
									"playerId"   => $v['playerId'],
									"generalId"  => $v['generalId'],
									"soldierId"  => $v['soldierId'], 
									"num"        => $v['injuredNum'],
									"dieNum"     => 0,
									"receiveNum" => 0,
									];
						}
					}
				
					$injureRet = (new PlayerSoldierInjured)->receive($injureSoldier);
					foreach($injureRet as $k=>$v){
						$defendUnitList[$k]['injuredNum'] = $v["receiveNum"];
						$defendUnitList[$k]['killedNum'] = $v["dieNum"];
					}
				}
			}
		}
		
		$aLosePower = 0;
		$aLosePowerRate = 0;
        $aSoldierLosePower = 0;
        $aSoldierLosePowerTrue = 0;
        $aSoldierLoseNum = 0;
		$aUnit = $this->formatUnitList($attackUnitList, [], $aLosePower, $aLosePowerRate, $aSoldierLosePower, $aSoldierLosePowerTrue, $aSoldierLoseNum);
		$killWeightArr = [];
		$weightArr = [];
		$PlayerMission = new PlayerMission;
		$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
		foreach ($aUnit as $k => $v) {
			$killWeightArr[$k] = $v['killWeight'];
			$weightArr[$k] = $v['weight'];
			if($this->crossBattleId==0 && $this->cityBattleId==0 && $attacker!="Npc" && $defender!="Npc" && !$noDamage){
				$kn = array_sum(Set::extract('/kill_num', $v['unit']));
				$PlayerMission->updateMissionNumber($k, 9, $kn);
				$Player->addKillSoldierNum($k, $kn);
				$PlayerTimeLimitMatch->updateScore($k, 13, $v['killWeight']/10000);
				$PlayerTimeLimitMatch->updateScore($k, 14, $v['killWeight']/10000);
				$PlayerTimeLimitMatch->updateScore($k, 15, $v['killWeight']/10000);
			}
		}

		$dLosePower = 0;
		$dLosePowerRate = 0;
        $dSoldierLosePower = 0;
        $dSoldierLosePowerTrue = 0;
        $dSoldierLoseNum = 0;
		$dUnit = $this->formatUnitList($defendUnitList, $defendBuildList, $dLosePower, $dLosePowerRate, $dSoldierLosePower, $dSoldierLosePowerTrue, $dSoldierLoseNum);
		$PlayerFailSaveReward = new PlayerFailSaveReward;
		foreach ($dUnit as $k => $v) {
			if($this->crossBattleId==0 && $this->cityBattleId==0 && $attacker!="Npc" && $defender!="Npc" && !$noDamage){
				$PlayerTimeLimitMatch->updateScore($k, 13, $v['killWeight']/10000);
				$PlayerTimeLimitMatch->updateScore($k, 14, $v['killWeight']/10000);
				$PlayerTimeLimitMatch->updateScore($k, 15, $v['killWeight']/10000);
				$PlayerFailSaveReward->checkNeedSave($k, $v['losePower']/10000);
			}
		}

		/*if($drawFlag && in_Array($battleType, [1,2,3,6,9])){
			$win = ($aSoldierLosePowerTrue<$dSoldierLosePowerTrue);
		}*/

		$robResourceList = [];
		if($win && $battleType==1){
			$noLoss = $PlayerBuff->getPlayerBuff($dPlayerId, "no_loss");
			if($noLoss==0){
				$rNameArr = ['gold', 'food', 'wood', 'stone', 'iron'];
				$Player = new Player;
				$PlayerBuild = new PlayerBuild;
				$produceResource = $robedResource = $PlayerBuild->getResourceNoCollection($dPlayerId);
				$dPlayer = $Player->getByPlayerId($dPlayerId);
				$protectedResource = $PlayerBuild->getResourceProtected($dPlayerId);

				//log4cli("\n=================loot-Begin=======================\n");
				//log4cli("\n玩家资源:\n");
				foreach ($rNameArr as $value) {
					//log4cli("\n".$value.":\n");
					//log4cli($dPlayer[$value]);
				}

				//log4cli("\n保护资源:\n");
				//log4cli($protectedResource);

				foreach ($rNameArr as $value) {
					if($dPlayer[$value]>$protectedResource[$value]){
						$robedResource[$value] += $dPlayer[$value]-$protectedResource[$value];
					}
				}

//				log4cli("\n可被抢资源:\n");
//				log4cli($robedResource);

				$robResourceList = splitResource($killWeightArr, $weightArr, $robedResource);
				foreach ($rNameArr as $value) {
					if($robResourceList[0][$value]>0){
						$robedResource[$value] -= $robResourceList[0][$value];
					}
				}

				$leftRobedResource = [];
				
				foreach ($rNameArr as $value) {
					if($robedResource[$value]>0){
						if($robedResource[$value]<=$produceResource[$value]){
							$PlayerBuild->reduceResource($dPlayerId, $value, 1-$robedResource[$value]/$produceResource[$value]);
						}else{
							$PlayerBuild->reduceResource($dPlayerId, $value, 0);
							$leftRobedResource[$value] = $produceResource[$value]-$robedResource[$value];
						}
					}
				}

//				log4cli("\n扣除资源:\n");
//				log4cli($leftRobedResource);
//				log4cli("\n=================loot-End=======================\n");

				if(!empty($leftRobedResource)){
					$Player->updateResource($dPlayerId, $leftRobedResource);
				}

				// echo "=================share-the-loot-Begin=======================\n";
				// echo "total:\n";
				// pr($robedResource);
				// echo "list:\n";
				// pr($robResourceList);
				// echo "=================share-the-loot-End=======================\n";
			}
			$Player->setFireStatus($dPlayerId);
		}

        $bossTotalTakeDamage = 0;
        $battleLogId = 0;
        if($this->crossBattleId==0 && $this->cityBattleId==0){
            $Player = new Player;
            if($attacker!="Npc" && $defender!="Npc"){
                foreach ($aUnit as $key => $value) {
                    $Player->alter($key, ['attack_time'=>qd()]);
                }
            }

            $PlayerMission = new PlayerMission;
            if($win && $attacker!="Npc" && $defender!="Npc"){
                foreach ($aUnit as $key => $value) {
                    $PlayerMission->updateMissionNumber($key, 6, 1);
                }
            }
            if($win && $attacker!="Npc" && $defender=="Npc"){
                foreach ($aUnit as $key => $value) {
                    $PlayerMission->updateMissionNumber($key, 5, 1);
                    $PlayerMission->updateMissionNumber($key, 25, $npcId);
                    $PlayerMission->updateMissionNumber($key, 27, $npcId);
                    $PlayerMission->updateMissionNumber($key, 28, $npcId);
                }
            }
            if(!$win && $attacker!="Npc" && $defender!="Npc"){
                foreach ($dUnit as $key => $value) {
                    $PlayerMission->updateMissionNumber($key, 10, 1);
                }
            }
            if($attacker!="Npc" && $defender!="Npc"){
                $xyArr = [];
                if(!empty($aPlayer['x']) && !empty($aPlayer['y'])){
                    $xyArr['ax'] = $aPlayer['x'];
                    $xyArr['ay'] = $aPlayer['y'];
                }else{
                    $xyArr['ax'] = $extra['basePosition']['ax'];
                    $xyArr['ay'] = $extra['basePosition']['ay'];
                }
                if(!empty($dPlayer['x']) && !empty($dPlayer['y'])){
                    $xyArr['dx'] = $dPlayer['x'];
                    $xyArr['dy'] = $dPlayer['y'];
                }else{
                    $xyArr['dx'] = $extra['basePosition']['dx'];
                    $xyArr['dy'] = $extra['basePosition']['dy'];
                }
                $battleInfo = [
                    'aList'			=> is_array($attackPlayerList)?array_keys($attackPlayerList):[$attackPlayerList],
                    'aLostPower'	=> $aLosePower,
                    'dList'			=> is_array($defendPlayerList)?array_keys($defendPlayerList):[$defendPlayerList],
                    'dLostPower'	=> $dLosePower,
                    'robResource'	=> $robResourceList,
                ];
                $battleLogId = (new GuildBattleLog)->add($aPlayerId, $aPlayer['guild_id'], $dPlayerId, $dPlayer['guild_id'], $win, $battleType, $xyArr, $battleInfo);
            }elseif($battleType==10){
                $xyArr['ax'] = $extra['basePosition']['ax'];
                $xyArr['ay'] = $extra['basePosition']['ay'];
                $xyArr['dx'] = $extra['basePosition']['dx'];
                $xyArr['dy'] = $extra['basePosition']['dy'];
                $battleInfo = [
                    'aList'			=> $attackPlayerList,
                    'aLostPower'	=> $aLosePower,
                    'dList'			=> is_array($defendPlayerList)?array_keys($defendPlayerList):[$defendPlayerList],
                    'dLostPower'	=> $dLosePower,
                    'robResource'	=> [],
                ];
                $battleLogId = (new GuildBattleLog)->add(0, 0, $dPlayerId, $dPlayer['guild_id'], ($aLosePowerRate<=0.5), $battleType, $xyArr, $battleInfo);
            }

            if($battleType==8){
                if($win){
                    $bossTotalTakeDamage = $defendPlayerList['npc_hp'];
                }else{
                    $bossTotalTakeDamage = floor($defendUnitList[0]['totalTakeDamage']/$defendUnitList[0]['ratio']);
                    if($defendPlayerList['npc_hp']<=$bossTotalTakeDamage){
                        $win = true;
                    }
                }
            }
        }

		//关羽技能特殊处理
		foreach ($this->removeGeneralArr['attack'] as $key => $value) {
			foreach ($this->godGeneralSkillArr['defend'] as $k => $v) {
				if($v['pid']==$value['ap'] && $v['gid']==$value['ag']){
					$this->godGeneralSkillArr['defend'][$k]['oppGeneralInfo'] = ['pid'=>$value['dp'], 'gid'=>$value['dg'], 'flag'=>$value['flag']];
				}
			}
		}
		foreach ($this->removeGeneralArr['defend'] as $key => $value) {
			foreach ($this->godGeneralSkillArr['attack'] as $k => $v) {
				if($v['pid']==$value['ap'] && $v['gid']==$value['ag']){
					$this->godGeneralSkillArr['attack'][$k]['oppGeneralInfo'] = ['pid'=>$value['dp'], 'gid'=>$value['dg'], 'flag'=>$value['flag']];
				}
			}
		}		

		$result = ['win'=>$win, 'allDead'=>$allDead, 'aList'=>$attackUnitList, 'dList'=>$defendUnitList, 'aFormatList'=>$aUnit, 'dFormatList'=>$dUnit, 'dBuildList'=>$defendBuildList, 'resource'=>$robResourceList, 'bossTotalTakeDamage'=>$bossTotalTakeDamage, 'aLosePower'=>floor($aLosePower/10000), 'dLosePower'=>floor($dLosePower/10000), 'aSoldierLosePower'=>$aSoldierLosePower, 'dSoldierLosePower'=>$dSoldierLosePower, 'aSoldierLoseNum'=>$aSoldierLoseNum, 'dSoldierLoseNum'=>$dSoldierLoseNum, 'aLosePowerRate'=>$aLosePowerRate, 'dLosePowerRate'=>$dLosePowerRate, 'battleLogId'=>$battleLogId, 'godGeneralSkillArr'=>$this->godGeneralSkillArr, 'noobProtect'=>$noobProtect];
		if(in_array($battleType, [1,2,3,6])){
			$this->dropGodGeneralBook($result);
		}
		return $result;
	}

	/**
	 * 单轮战斗
	 * 
	 * @param  [type] $attackUnit 攻击方单位列表
	 * @param  [type] $defendUnit 防御方单位列表
	 * @param  [type] $defendBuildList 防御方建筑和陷阱
	 * @param  [type] $battleType 战斗方式
	 * @param  int $turn 当前回合数
	 * @return [type]             [description]
	 */
	function oneTurnBattle($attackUnitList, $defendUnitList, $defendBuildList, $aBuff, $dBuff, $battleType, $turn){
		//备份数据
		$_attackUnitList = $attackUnitList;
		$_defendUnitList = $defendUnitList;
		
		//防御建筑生效
		if($this->crossBattleId==0 && !empty($defendBuildList)){
			foreach ($defendBuildList as $k=>$defendBuild) {
				$re = $this->buildEffect($defendBuild, $_attackUnitList, $aBuff, $dBuff);
				if(!empty($re)){
					$defendBuildList[$k] = $re['defendBuild'];
					$_attackUnitList = $re['_attackUnitList'];
				}
			}
		}

		$reflectArr = ['attack'=>[], 'defend'=>[]];

		//攻击方进攻
		foreach ($attackUnitList as $k=>$attackUnit) {
			$re = $this->attack($attackUnit, $_defendUnitList, $aBuff, $dBuff, false, $turn, $battleType);
			if(!empty($re)){
				$attackUnitList[$k] = $re['0'];
				$_defendUnitList = $re['1'];
                if(!empty($re[2])){
                    foreach($re[2] as $reflectKey=>$reflectValue){
                        $reflectArr['defend'][] = ['aKey'=>$reflectValue['reflectKey'], 'dKey'=>$k, 'damage'=>$reflectValue['damage']];
                    }
                }
			}
		}
		
		//防御方反击
		foreach ($defendUnitList as $k=>$defendUnit) {
			$re = $this->attack($defendUnit, $_attackUnitList, $dBuff, $aBuff, true, $turn, $battleType);
			if(!empty($re)){
				$defendUnitList[$k] = $re['0'];
				$_attackUnitList = $re['1'];
                if(!empty($re[2])){
                    foreach($re[2] as $reflectKey=>$reflectValue){
                        $reflectArr['attack'][] = ['aKey'=>$reflectValue['reflectKey'], 'dKey'=>$k, 'damage'=>$reflectValue['damage']];
                    }
                }
			}
		}

        //单轮结算
        $attackUnitList = $this->calcSoldierNum($attackUnitList, $_attackUnitList);
        $defendUnitList = $this->calcSoldierNum($defendUnitList, $_defendUnitList);

		//结算反弹伤害
        foreach($reflectArr['attack'] as $k=>$v){
            $re = $this->reflectDamage($attackUnitList[$v['aKey']], $defendUnitList[$v['dKey']], $v['damage'], $aBuff, $dBuff, false, $turn, $battleType);
            $attackUnitList[$v['aKey']] = $re['0'];
            $defendUnitList[$v['dKey']] = $re['1'];
        }
        foreach($reflectArr['defend'] as $k=>$v){
            $re = $this->reflectDamage($defendUnitList[$v['aKey']], $attackUnitList[$v['dKey']], $v['damage'], $dBuff, $aBuff, true, $turn, $battleType);
            $defendUnitList[$v['aKey']] = $re['0'];
            $attackUnitList[$v['dKey']] = $re['1'];
        }

		$win = $this->checkWin($attackUnitList, $defendUnitList, $battleType);

		$result = ['attackUnitList'=>$attackUnitList, 'defendUnitList'=>$defendUnitList, 'defendBuildList'=>$defendBuildList, 'win'=>$win];

		return $result;
	}

	function checkWin($attackUnitList, $defendUnitList, $battleType){
		$attackDie = true;
		foreach ($attackUnitList as $value) {
			if($value['num']>0){
				$attackDie = false;
				break;
			}
		}

		$defendDie = true;
		foreach ($defendUnitList as $value) {
			if($value['num']>0){
				$defendDie = false;
				break;
			}
		}

		if($battleType==8 && $attackDie && $defendDie){
			$result = "Attacker";
		}elseif($attackDie){
			$result = "Defender";
		}elseif($defendDie){
			$result = "Attacker";
		}else{
			$result = false;
		}
		return $result;
	}

	/**
	 * 防御建筑生效
	 * @param  [type] &$defendBuild    [description]
	 * @param  [type] &$defendUnitList [description]
	 * @return [type]                  [description]
	 */
	function buildEffect($defendBuild, $defendUnitList, $aBuff, $dBuff){
		$denominator = 0;
		foreach ($defendUnitList as $k=>$defendUnit) {
			if($defendBuild['type']==0 || $defendUnit['type']==$defendBuild['type']){
				$defendUnitList[$k]['rangeWeight'] = $defendUnit['num'];
				$denominator += $defendUnit['num'];
			}else{
				$defendUnitList[$k]['rangeWeight'] = 0;
			}
		}

		if($denominator==0){
			return;
		}

		$PlayerBuff = new PlayerBuff;

		if($defendBuild['type']==0){
			$towerBuff = $PlayerBuff->getPlayerBuff($defendBuild['playerId'], "tower_atk_plus");
			$totalDamage = $defendBuild['attack']*$defendBuild['num']*(1+$towerBuff);
		}else{
			$nameArr = [1=>'rock', 2=>'arrow', 3=>'wood'];
			$actPBuff = $PlayerBuff->getPlayerBuff($defendBuild['playerId'], $nameArr[$defendBuild['type']]."_activated_probability");
			$totalDamage = $defendBuild['attack']*(1+$aBuff[$nameArr[$defendBuild['type']].'_atk_plus']-$dBuff[$nameArr[$defendBuild['type']].'_atk_reduce'])*$defendBuild['num']*0.02*(1+$actPBuff);
		}

		if($totalDamage>0){
			$overFlowDamage = 0;

			foreach ($defendUnitList as $k=>$defendUnit) {
				if($defendUnit['killedNum']==$defendUnit['maxNum']){
					continue;
				}
				$defendUnitList[$k]['takeDamage'] += $totalDamage*$defendUnit['rangeWeight']/$denominator;

				$currentKilledNum = floor($defendUnitList[$k]['takeDamage']/($defendUnit['life']+$defendUnit['defend']));
				if($currentKilledNum>$defendUnit['maxNum']-$defendUnit['killedNum']){
					$overFlowDamage += $currentKilledNum-($defendUnit['maxNum']-$defendUnit['killedNum'])*($defendUnit['life']+$defendUnit['defend']);
					$currentKilledNum = $defendUnit['maxNum']-$defendUnit['killedNum'];
				}

				$defendBuild['killList'] = $this->calcKillsoldierList($defendBuild['killList'], $defendUnit['soldierId'], $currentKilledNum);

				$defendUnitList[$k]['killedNum'] += $currentKilledNum;
				$defendUnitList[$k]['takeDamage'] -= ($defendUnit['life']+$defendUnit['defend'])*$currentKilledNum;
			}

			$defendBuild['num'] = floor($defendBuild['num']*(1-0.02*(1+$actPBuff)*(1-$overFlowDamage/$totalDamage)));
			$defendBuild['killedNum'] = $defendBuild['maxNum']-$defendBuild['num'];
		}	

		return ['defendBuild'=>$defendBuild, '_attackUnitList'=>$defendUnitList];
	}

	/**
	 * 单次攻击
	 * @param  array &$attackUnit     [description]
	 * @param  array &$defendUnitList [description]
	 * @param  boolean $isCounter 是否为防御方反击
	 * 
	 * @return [type]                  [description]
	 */
	function attack(&$attackUnit, &$defendUnitList, $aBuff, $dBuff, $isCounter, $turn, $battleType){
        $reflectList = [];
        $sunceFlag = false;
        $zhoutaiFlag = false;
		if(11==$attackUnit['godSkill']['type']){//神武将技 type=11 [孙策] 部队攻击力不减
			$totalDamage = $attackUnit['attack']*$attackUnit['maxNum'];
			$sunceFlag = true;
		}elseif(22==$attackUnit['godSkill']['type']){//神武将技 type=22 [周泰] 整队士兵同时死亡
            $totalDamage = $attackUnit['attack']*$attackUnit['maxNum'];
            $zhoutaiFlag = true;
        }else{
			$totalDamage = $attackUnit['attack']*$attackUnit['num'];
		}

		if($attackUnit['num']==0){//已经被消灭部队
			return;
		}
        if($isCounter==true){
            $selfIndex = "defend";
            $oppIndex = "attack";
        }else{
            $selfIndex = "attack";
            $oppIndex = "defend";
        }

		if(in_array(self::SKILL_CHARGE, $attackUnit['skill'])){
			$chargeFactor = 1;
		}else{
			$chargeFactor = -1;
		}

		$denominator = 0;
		foreach ($defendUnitList as $k=>$defendUnit) {
			$rangePara = 1;
			// if(in_array(self::SKILL_AIMING, $defendUnit['skill'])){
			// 	$rangePara += 0.5;
			// }
			// if(in_array(self::SKILL_ACCUMULATED_POWER, $defendUnit['skill'])){
			// 	$rangePara += 1;
			// }
			$defendUnitList[$k]['rangeWeight'] = $defendUnit['num']*pow($defendUnit['range']*$rangePara+self::RANGE_PARA, $chargeFactor);
			$denominator += $defendUnitList[$k]['rangeWeight'];
		}

		$directDamage = [0,0,0,0,0];
		if($turn==1 && 6==$attackUnit['godSkill']['type']){//神武将技 type=6 面对特定兵种直接造成伤害
			if(in_array(1,$attackUnit['godSkill']['target'])){
				$directDamage[1] = $attackUnit['godSkill']['value']*$attackUnit['num']/$attackUnit['generalMaxNum'];
			}
			if(in_array(2,$attackUnit['godSkill']['target'])){
				$directDamage[2] = $attackUnit['godSkill']['value']*$attackUnit['num']/$attackUnit['generalMaxNum'];
			}
			if(in_array(3,$attackUnit['godSkill']['target'])){
				$directDamage[3] = $attackUnit['godSkill']['value']*$attackUnit['num']/$attackUnit['generalMaxNum'];
			}
			if(in_array(4,$attackUnit['godSkill']['target'])){
				$directDamage[4] = $attackUnit['godSkill']['value']*$attackUnit['num']/$attackUnit['generalMaxNum'];
			}
		}

        if(16==$attackUnit['godSkill']['type']){//神武将技 type=16 [周瑜] 火攻
            $zhouyuStartKey = mt_rand(0, max(count($defendUnitList)-$attackUnit['godSkill']['exDamageTimes'], 0));
        }else{
            $zhouyuStartKey = 0;
        }



		foreach ($defendUnitList as $k=>$defendUnit) {
			if($defendUnit['killedNum']==$defendUnit['maxNum']){
				continue;
			}
			
			$skillArr = $this->calcUnitSkill($attackUnit, $defendUnit, $isCounter, $battleType);

			$attackPara = $skillArr[0];
			$defendPara = $skillArr[1];
			$lifePara = $skillArr[2];

			switch ($attackUnit['type']) {
				case 1:
                case 5:
					$aPre = 'infantry';
					break;
				case 2:
					$aPre = 'cavalry';
					break;
				case 3:
					$aPre = 'archer';
					break;
				case 4:
					$aPre = 'siege';
					break;
				default:
					return;
					break;
			}

			switch ($defendUnit['type']) {
				case 1:
                case 5:
					$dPre = 'infantry';
					break;
				case 2:
					$dPre = 'cavalry';
					break;
				case 3:
					$dPre = 'archer';
					break;
				case 4:
					$dPre = 'siege';
					break;
				default:
					return;
					break;
			}
			
			$attackBuff = 1+$aBuff[$aPre."_atk_plus"]-$dBuff[$aPre."_atk_reduce"];
			if($battleType==1 || $battleType==11){
				$attackBuff += $aBuff["citybattle_".$aPre."_atk_plus"];
			}elseif($battleType==2 || $battleType==7){
				$attackBuff += $aBuff["fieldbattle_".$aPre."_atk_plus"];
			}
			
			$defendBuff = 1+$dBuff[$dPre."_def_plus"]-$aBuff[$dPre."_def_reduce"];
			if($battleType==1 || $battleType==11){
				$defendBuff += $dBuff["citybattle_".$dPre."_def_plus"];
			}elseif($battleType==2 || $battleType==7){
				$defendBuff += $dBuff["fieldbattle_".$dPre."_def_plus"];
			}

			$lifeBuff = 1+$dBuff[$dPre."_life_plus"]-$aBuff[$dPre."_life_reduce"];
			if($battleType==1 || $battleType==11){
				$lifeBuff += $dBuff["citybattle_".$dPre."_life_plus"];
			}elseif($battleType==2 || $battleType==7){
				$lifeBuff += $dBuff["fieldbattle_".$dPre."_life_plus"];
			}

			$partialDamage = $totalDamage*$defendUnit['rangeWeight']/$denominator*$attackPara*$attackBuff;

			if($turn==1 && 1==$attackUnit['godSkill']['type']){//神武将技 type=1 士兵首轮伤害增加%
				$partialDamage *= 1+$attackUnit['godSkill']['value'];
			}
			if(5==$attackUnit['godSkill']['type'] && in_array($defendUnit['type'], $attackUnit['godSkill']['target']) ){//神武将技 type=5 面对特定兵种伤害增加%
				$partialDamage *= 1+$attackUnit['godSkill']['value'];
			}
            if(24==$attackUnit['godSkill']['type'] && $defendUnit['maxNum']<$defendUnit['generalMaxNum'] ){//神武将技 type=24 面对预备役部队和不满员部队伤害增加%
                $partialDamage *= 1+$attackUnit['godSkill']['value'];
            }

			if($turn==1 && 2==$defendUnit['godSkill']['type']){//神武将技 type=2 士兵首轮受伤减免%
				$partialDamage *= 1-$defendUnit['godSkill']['value'];
			}
			if(4==$defendUnit['godSkill']['type'] && in_array($attackUnit['type'], $defendUnit['godSkill']['target'])){//神武将技 type=4 面对特定兵种受伤减免%
				$partialDamage *= 1-$defendUnit['godSkill']['value'];
			}
			if(22==$defendUnit['godSkill']['type']){//神武将技 type=22 [周泰] 减免伤害
                $partialDamage *= 1-$defendUnit['godSkill']['value'];
            }
            if(20==$attackUnit['godSkill']['type']){//神武将技 type=20 [吕布] 减低敌方防御
                $defendBuff *= (1-$attackUnit['godSkill']['value']);
            }
            if(26==$attackUnit['godSkill']['type']){//神武将技 type=26 [马超] 根据对方射程增加伤害
                if($this->rangeNumList[$oppIndex][$defendUnit['range']]==0){
                    $this->rangeNumList[$oppIndex][$defendUnit['range']] = 1;
                }
                $partialDamage *= (1+$attackUnit['godSkill']['value'])*(1+pow($this->rangeNumList[$oppIndex][$defendUnit['range']]-1, 0.5));
                $machaoFlag = true;
            }else{
                $machaoFlag = false;
            }
			if($isCounter==false){
				$partialDamage *= (1-$this->reduceAttackBuff['attack'])*(1-$this->lvDamageProtected);
				$defendTotalPoint = $defendUnit['life']*$lifePara*$lifeBuff*(1-$this->reduceLifeBuff['defend'])+$defendUnit['defend']*$defendPara*$defendBuff*(1-$this->reduceDefendBuff['defend']);
			}else{
				$partialDamage *= (1-$this->reduceAttackBuff['defend'])*(1-$this->lvDamageProtected);
				$defendTotalPoint = $defendUnit['life']*$lifePara*$lifeBuff*(1-$this->reduceLifeBuff['attack'])+$defendUnit['defend']*$defendPara*$defendBuff*(1-$this->reduceDefendBuff['attack']);
			}

            if($turn==1 && $k>=$zhouyuStartKey && 16==$attackUnit['godSkill']['type'] && $attackUnit['godSkill']['exDamageTimes']>0){//神武将技 type=16 [周瑜] 火攻造成伤害
                $partialDamage += $attackUnit['godSkill']['value']*$attackUnit['num'];
                $attackUnit['godSkill']['exDamageTimes']--;
                $zhouyuFlag = true;
            }else{
                $zhouyuFlag = false;
            }

			$partialDamage += $directDamage[$defendUnit['type']];
			$defendUnitList[$k]['takeDamage'] += $partialDamage;

			$currentKilledNum = floor($defendUnitList[$k]['takeDamage']/$defendTotalPoint);
			if($currentKilledNum>$defendUnit['maxNum']-$defendUnit['killedNum']){
				$currentKilledNum = $defendUnit['maxNum']-$defendUnit['killedNum'];
                $tDamage = $currentKilledNum*$defendTotalPoint;
				$defendUnitList[$k]['totalTakeDamage'] += $currentKilledNum*$defendTotalPoint;
				$attackUnit['totalDamage'] += $currentKilledNum*$defendTotalPoint;
				if($sunceFlag || $zhouyuFlag || $zhoutaiFlag || $machaoFlag){//孙策 周瑜 周泰 马超技能特殊处理
					if(!$isCounter){
						$tmpKey = "attack";
					}else{
						$tmpKey = "defend";
					}
					foreach ($this->godGeneralSkillArr[$tmpKey] as $key => $value) {
						if($attackUnit['playerId']==$value['pid'] && $attackUnit['generalId']==$value['gid']){
                            if($sunceFlag || $zhoutaiFlag){
                                if(empty($this->godGeneralSkillArr[$tmpKey][$key]['damage'])){
                                    $this->godGeneralSkillArr[$tmpKey][$key]['damage'] = $currentKilledNum*$defendTotalPoint*($attackUnit['maxNum']-$attackUnit['num'])/$attackUnit['maxNum'];
                                }else{
                                    $this->godGeneralSkillArr[$tmpKey][$key]['damage'] += $currentKilledNum*$defendTotalPoint*($attackUnit['maxNum']-$attackUnit['num'])/$attackUnit['maxNum'];
                                }
                            }elseif($zhouyuFlag){
                                if(empty($this->godGeneralSkillArr[$tmpKey][$key]['damage'])){
                                    $this->godGeneralSkillArr[$tmpKey][$key]['damage'] = min($currentKilledNum*$defendTotalPoint, $attackUnit['godSkill']['value']*$attackUnit['num']);
                                }else{
                                    $this->godGeneralSkillArr[$tmpKey][$key]['damage'] += min($currentKilledNum*$defendTotalPoint, $attackUnit['godSkill']['value']*$attackUnit['num']);
                                }
                            }elseif($machaoFlag){
                                $i = (1+$attackUnit['godSkill']['value'])*(1+pow($this->rangeNumList[$oppIndex][$defendUnit['range']]-1, 0.5));
                                $this->godGeneralSkillArr[$tmpKey][$key]['damage'] = $currentKilledNum*$defendTotalPoint*($i-1)/$i;
                            }
						}
					}	
				}
			}else{
                $tDamage = $partialDamage;
				$defendUnitList[$k]['totalTakeDamage'] += $partialDamage;
				$attackUnit['totalDamage'] += $partialDamage;
				if($sunceFlag || $zhouyuFlag || $zhoutaiFlag){//孙策 周瑜 技能特殊处理
					if(!$isCounter){
						$tmpKey = "attack";
					}else{
						$tmpKey = "defend";
					}
					foreach ($this->godGeneralSkillArr[$tmpKey] as $key => $value) {
						if($attackUnit['playerId']==$value['pid'] && $attackUnit['generalId']==$value['gid']){
							if(empty($this->godGeneralSkillArr[$tmpKey][$key]['damage'])){
                                if($sunceFlag || $zhoutaiFlag){
                                    if(empty($this->godGeneralSkillArr[$tmpKey][$key]['damage'])){
                                        $this->godGeneralSkillArr[$tmpKey][$key]['damage'] = $partialDamage*($attackUnit['maxNum']-$attackUnit['num'])/$attackUnit['maxNum'];
                                    }else{
                                        $this->godGeneralSkillArr[$tmpKey][$key]['damage'] += $partialDamage*($attackUnit['maxNum']-$attackUnit['num'])/$attackUnit['maxNum'];
                                    }
                                }elseif($zhouyuFlag){
                                    if(empty($this->godGeneralSkillArr[$tmpKey][$key]['damage'])){
                                        $this->godGeneralSkillArr[$tmpKey][$key]['damage'] = $attackUnit['godSkill']['value']*$attackUnit['num'];
                                    }else{
                                        $this->godGeneralSkillArr[$tmpKey][$key]['damage'] += $attackUnit['godSkill']['value']*$attackUnit['num'];
                                    }
                                }
							}
						}
					}	
				}
			}

            if(13==$defendUnit['godSkill']['type']){//神武将技 type=13 [曹操]伤害反弹
                $reflectList[] = ['reflectKey'=>$k, 'damage'=>$partialDamage*$defendUnit['godSkill']['value']];
                if($isCounter){
                    $tmpKey = "attack";
                }else{
                    $tmpKey = "defend";
                }
                foreach ($this->godGeneralSkillArr[$tmpKey] as $key => $value) {
                    if($defendUnit['playerId']==$value['pid'] && $defendUnit['generalId']==$value['gid']){
                        if(empty($this->godGeneralSkillArr[$tmpKey][$key]['damage'])){
                            $this->godGeneralSkillArr[$tmpKey][$key]['damage'] = $tDamage;
                        }else{
                            $this->godGeneralSkillArr[$tmpKey][$key]['damage'] += $tDamage;
                        }
                    }
                }
            }

            $defendUnitList[$k]['killedNum'] += $currentKilledNum;
            $defendUnitList[$k]['takeDamage'] -= $currentKilledNum*($defendTotalPoint);

            if(22==$defendUnit['godSkill']['type']){//防御方是周泰
                if($defendUnitList[$k]['killedNum']==$defendUnitList[$k]['maxNum']){
                    $attackUnit['killList'] = $this->calcKillsoldierList($attackUnit['killList'], $defendUnit['soldierId'], $defendUnit['maxNum']);
                    $attackUnit['killWeight'] += $defendUnit['power'];
                    if($isCounter){
                        $tmpKey = "attack";
                    }else{
                        $tmpKey = "defend";
                    }
                    foreach ($this->godGeneralSkillArr[$tmpKey] as $key => $value) {
                        if($defendUnit['playerId']==$value['pid'] && $defendUnit['generalId']==$value['gid']){
                                $this->godGeneralSkillArr[$tmpKey][$key]['allDead'] = true;
                        }
                    }
                }
            }else{
                $attackUnit['killList'] = $this->calcKillsoldierList($attackUnit['killList'], $defendUnit['soldierId'], $currentKilledNum);
                if($currentKilledNum>0){
                    $attackUnit['killWeight'] += $currentKilledNum*$defendUnit['power']/$defendUnit['maxNum'];
                }
            }

			

		}
		return ['0'=>$attackUnit, '1'=>$defendUnitList, '2'=>$reflectList];
	}

    /**
     * 反弹伤害
     */
    function reflectDamage($attackUnit, $defendUnit, $damage, $aBuff, $dBuff, $isCounter, $turn, $battleType){
        $skillArr = $this->calcUnitSkill($attackUnit, $defendUnit, $isCounter, $battleType);

        $attackPara = $skillArr[0];
        $defendPara = $skillArr[1];
        $lifePara = $skillArr[2];

        switch ($attackUnit['type']) {
            case 1:
            case 5:
                $aPre = 'infantry';
                break;
            case 2:
                $aPre = 'cavalry';
                break;
            case 3:
                $aPre = 'archer';
                break;
            case 4:
                $aPre = 'siege';
                break;
            default:
                return;
                break;
        }

        switch ($defendUnit['type']) {
            case 1:
            case 5:
                $dPre = 'infantry';
                break;
            case 2:
                $dPre = 'cavalry';
                break;
            case 3:
                $dPre = 'archer';
                break;
            case 4:
                $dPre = 'siege';
                break;
            default:
                return;
                break;
        }

        $attackBuff = 1+$aBuff[$aPre."_atk_plus"]-$dBuff[$aPre."_atk_reduce"];
        if($battleType==1 || $battleType==11){
            $attackBuff += $aBuff["citybattle_".$aPre."_atk_plus"];
        }elseif($battleType==2 || $battleType==7){
            $attackBuff += $aBuff["fieldbattle_".$aPre."_atk_plus"];
        }

        $defendBuff = 1+$dBuff[$dPre."_def_plus"]-$aBuff[$dPre."_def_reduce"];
        if($battleType==1 || $battleType==11){
            $defendBuff += $dBuff["citybattle_".$dPre."_def_plus"];
        }elseif($battleType==2 || $battleType==7){
            $defendBuff += $dBuff["fieldbattle_".$dPre."_def_plus"];
        }

        $lifeBuff = 1+$dBuff[$dPre."_life_plus"]-$aBuff[$dPre."_life_reduce"];
        if($battleType==1 || $battleType==11){
            $lifeBuff += $dBuff["citybattle_".$dPre."_life_plus"];
        }elseif($battleType==2 || $battleType==7){
            $lifeBuff += $dBuff["fieldbattle_".$dPre."_life_plus"];
        }

        if($isCounter==false){
            $defendTotalPoint = $defendUnit['life']*$lifePara*$lifeBuff*(1-$this->reduceLifeBuff['defend'])+$defendUnit['defend']*$defendPara*$defendBuff*(1-$this->reduceDefendBuff['defend']);
        }else{
            $defendTotalPoint = $defendUnit['life']*$lifePara*$lifeBuff*(1-$this->reduceLifeBuff['attack'])+$defendUnit['defend']*$defendPara*$defendBuff*(1-$this->reduceDefendBuff['attack']);
        }

        $defendUnit['takeDamage'] += $damage;

        $currentKilledNum = floor($defendUnit['takeDamage']/$defendTotalPoint);
        if($currentKilledNum>$defendUnit['maxNum']-$defendUnit['killedNum']){
            $currentKilledNum = $defendUnit['maxNum']-$defendUnit['killedNum'];
            $defendUnit['totalTakeDamage'] += $currentKilledNum*$defendTotalPoint;
            $attackUnit['totalDamage'] += $currentKilledNum*$defendTotalPoint;
        }else{
            $defendUnit['totalTakeDamage'] += $damage;
            $attackUnit['totalDamage'] += $damage;
        }

        $attackUnit['killList'] = $this->calcKillsoldierList($attackUnit['killList'], $defendUnit['soldierId'], $currentKilledNum);
        if($currentKilledNum>0){
            $attackUnit['killWeight'] += $currentKilledNum*$defendUnit['power']/$defendUnit['maxNum'];
        }


        $defendUnit['killedNum'] += $currentKilledNum;
        $defendUnit['takeDamage'] -= $currentKilledNum*($defendTotalPoint);
        $defendUnit['num'] = $defendUnit['maxNum']-$defendUnit['killedNum'];
        return ['0'=>$attackUnit, '1'=>$defendUnit];
    }

	/**
	 * 计算剩余士兵数量
	 * @param  [type] &$targetList [description]
	 * @param  [type] $sourceList  [description]
	 * @return [type]              [description]
	 */
	function calcSoldierNum($targetList, $sourceList){
		foreach ($sourceList as $k=>$v) {
			$targetList[$k]['num'] = $v['maxNum']-$v['killedNum'];
			$targetList[$k]['killedNum'] = $v['killedNum'];
			$targetList[$k]['takeDamage'] = $v['takeDamage'];
			$targetList[$k]['totalTakeDamage'] = $v['totalTakeDamage'];
		}
		return $targetList;
	}

	/**
	 * 统计单组士兵杀敌数量
	 * @param  [type] $killList  [description]
	 * @param  [type] $soldierId [description]
	 * @param  [type] $killedNum [description]
	 * @return [type]            [description]
	 */
	function calcKillsoldierList($killList, $soldierId, $killedNum){
		if(empty($killList[$soldierId])){
			$killList[$soldierId] = $killedNum;
		}else{
			$killList[$soldierId] += $killedNum;
		}
		return $killList;
	}

	/**
	 * 计算单位特技
	 * @param  [type] $attackUnit [description]
	 * @param  [type] $defendUnit [description]
	 * @param  [type] $isCounter  是否是防御者在反击
	 * @param  [type] $battleType 战斗类型
	 * @return [type]             [description]
	 */
	function calcUnitSkill($attackUnit, $defendUnit, $isCounter, $battleType){
		$attackPara = 1;
		$defendPara = 1;
		$lifePara = 1;

		if($attackUnit['type']==3 && in_array(self::SKILL_SHIELD, $defendUnit['skill'])){
			$attackPara *= 0.5;
		}

		// if(in_array(self::SKILL_GRIT, $defendUnit['skill'])){
		// 	$lifePara *= 2;
		// }

		// if(in_array(self::SKILL_IRON_ARMOR, $defendUnit['skill'])){
		// 	$defendPara *= 2;
		// }

		if($defendUnit['type']==2 && in_array(self::SKILL_PIKE, $attackUnit['skill'])){
			$attackPara *= 1.5;
		}

		if(lcg_value1()<0.25 && in_array(self::SKILL_CRITICAL_HIT, $attackUnit['skill'])){
			$attackPara *= 2;
		}

		// if(in_array(self::SKILL_BRUTCAL_ATTACK, $attackUnit['skill'])){
		// 	$attackPara *= 1.5;
		// }

		if(lcg_value1()<0.2 && in_array(self::SKILL_DODGE, $defendUnit['skill'])){
			$attackPara *= 0;
		}

		if($battleType==2 && in_array(self::SKILL_RESOURCE_BATTLE, $attackUnit['skill'])){
			$attackPara *= 2;
		}

		if(in_array(self::SKILL_DOUBLE_ATTACK, $attackUnit['skill'])){
			$attackPara *= 2;
		}

		if(($defendUnit['type']!=1 && $defendUnit['type']!=5) && in_array(self::SKILL_PUNCTURE, $attackUnit['skill'])){
			$attackPara *= 1.5;
		}

		// if(in_array(self::SKILL_POWER_ATTACK, $attackUnit['skill'])){
		// 	$attackPara *= 2;
		// }

		if(($battleType==1 || $battleType==11) && $isCounter==true && in_array(self::SKILL_DEFENSE, $attackUnit['skill'])){
			$attackPara *= 1.5;
		}

		if(($battleType==1 || $battleType==11) && $isCounter==false && in_array(self::SKILL_SIEGE_ATTACK, $attackUnit['skill']) ){
			$attackPara *= 2;
		}

		// if(in_array(self::SKILL_ARMORNING, $defendUnit['skill'])){
		// 	$lifePara *= 2;
		// 	$defendPara *= 2;
		// }

		if(($battleType==1 || $battleType==11) && $isCounter==true && in_array(self::SKILL_SIEGE_DEFENSE, $defendUnit['skill']) ){
			$lifePara *= 1.5;
			$defendPara *= 1.5;
		}

		if($attackPara<0){
			$attackPara = 0;
		}
		if($defendPara<0){
			$defendPara = 0;
		}
		if($lifePara<0){
			$lifePara = 0;
		}

		return [$attackPara, $defendPara, $lifePara];
	}

	/**
	 * 获取玩家防御建筑
	 * @param  [type] $playerId [description]
	 * @return [type]           [description]
	 */
	function getDefendBuildList($playerId, $battleType){
		$result = [];
		$Player = new Player;
		$PlayerTrap = new PlayerTrap;
		$Map = new Map;
		$Trap = new Trap;

		$trapList = $PlayerTrap->getByPlayerId($playerId);

		$player = $Player->getByPlayerId($playerId);
		$playerPosition = [$player['x'], $player['y']];
		$guildId = $player['guild_id'];

		if($guildId>0){
			$guildBuildList = $Map->getAllByGuildId($guildId);
			foreach ($guildBuildList as $value) {
				if($value['map_element_origin_id']==2 && $Map->isInArea($value['x'], $value['y'], $playerPosition)){
					$result[] = ['playerId'=>$playerId, 'trapId'=>0, 'attack'=>10000, 'num'=>1, 'maxNum'=>1, 'type'=>0, 'takeDamage'=>0, 'killedNum'=>0, 'killList'=>[]];
					break;
				}
			}
		}

		if($battleType==1){
			foreach ($trapList as $value) {
				$tmpTrapInfo = $Trap->dicGetOne($value['trap_id']);
				$result[] = ['playerId'=>$playerId, 'trapId'=>$value['trap_id'], 'attack'=>$tmpTrapInfo['atk'], 'num'=>$value['num'], 'maxNum'=>$value['num'], 'type'=>$tmpTrapInfo['trap_type'], 'takeDamage'=>0, 'killedNum'=>0, 'killList'=>[]];
			}
		}
		

		return $result;
	}

	/**
	 * 获取攻击方进攻士兵
	 * @param  [type] $queue [description]
	 * @return [type]        [description]
	 */
	function getAttackUnitList($attackPlayerList){
		$result = [];
        if($this->crossBattleId>0){
            $PlayerArmyUnit = new CrossPlayerArmyUnit;
            $PlayerArmyUnit->battleId = $this->crossBattleId;
        }elseif($this->cityBattleId>0){
            $PlayerArmyUnit = new CityBattlePlayerArmyUnit;
            $PlayerArmyUnit->battleId = $this->cityBattleId;
        }else{
            $PlayerArmyUnit = new PlayerArmyUnit;
        }

		$Soldier = new Soldier;
		foreach($attackPlayerList as $key=>$value){
			$tmpArmyInfo = $PlayerArmyUnit->getByArmyId($key, $value);
			foreach ($tmpArmyInfo as $k => $v) {
				if($v['soldier_num']>0){
					$tmpSoldierInfo = $Soldier->dicGetOne($v['soldier_id']);
					$result[] = [
						'from'            => 1,
						'playerId'        => $key,
						'generalId'       => $v['general_id'],
                        'generalStar'     => 0,
						'soldierId'       => $v['soldier_id'], 
						'power'           => $tmpSoldierInfo['power']*$v['soldier_num'],
						'attack'          => $tmpSoldierInfo['attack'], 
						'defend'          => $tmpSoldierInfo['defense'], 
						'life'            => $tmpSoldierInfo['life'],
						'num'             => $v['soldier_num'], 
						'maxNum'          => $v['soldier_num'],
                        'generalMaxNum'  => $v['soldier_num'],
						'type'            => $tmpSoldierInfo['soldier_type'],
						'range'           => $tmpSoldierInfo['distance'], 
						'skill'           => $tmpSoldierInfo['skillList'], 
						'takeDamage'      => 0, 
						'killedNum'       => 0,
						'injuredNum'      => 0,
                        'reviveNum'      => 0,
						'killList'        => [],
						'killWeight'      => 0,
						'totalTakeDamage' => 0,
						'totalDamage'	  => 0,
					];
				}
			}
		}
		return $result;
	}

	/**
	 * 获取防御方防御士兵
	 * @param  [type] $queue [description]
	 * @return [type]        [description]
	 */
	function getDefendUnitList($defendPlayerList){
		$result = [];

        if($this->crossBattleId>0){
            $PlayerArmyUnit = new CrossPlayerArmyUnit;
            $PlayerArmy = new CrossPlayerArmy;
            $PlayerSoldier = new CrossPlayerSoldier;
            $PlayerArmyUnit->battleId = $this->crossBattleId;
            $PlayerArmy->battleId = $this->crossBattleId;
            $PlayerSoldier->battleId = $this->crossBattleId;
        }elseif($this->cityBattleId>0){
            $PlayerArmyUnit = new CityBattlePlayerArmyUnit;
            $PlayerArmy = new CityBattlePlayerArmy;
            $PlayerSoldier = new CityBattlePlayerSoldier;
            $PlayerArmyUnit->battleId = $this->cityBattleId;
            $PlayerArmy->battleId = $this->cityBattleId;
            $PlayerSoldier->battleId = $this->cityBattleId;
        }else{
            $PlayerArmyUnit = new PlayerArmyUnit;
            $PlayerArmy = new PlayerArmy;
            $PlayerSoldier = new PlayerSoldier;
        }

		$Soldier = new Soldier;
		$PlayerBuild = new PlayerBuild;
		foreach($defendPlayerList as $key=>$value){
			if($value==0){
				$playerArmy = $PlayerArmy->getByPlayerId($key);
				foreach ($playerArmy as $army) {
					if($army['status']==0){
						$tmpArmyInfo = $PlayerArmyUnit->getByArmyId($key, $army['id']);
						foreach ($tmpArmyInfo as $k => $v) {
							if($v['soldier_num']>0){
								$tmpSoldierInfo = $Soldier->dicGetOne($v['soldier_id']);
								$result[] = [
									'from'		 => 1,
									'playerId'   => $key,
									'generalId'  => $v['general_id'],
                                    'generalStar'=> 0,
									'soldierId'  => $v['soldier_id'], 
									'power'		  => $tmpSoldierInfo['power']*$v['soldier_num'],
									'attack'     => $tmpSoldierInfo['attack'], 
									'defend'     => $tmpSoldierInfo['defense'], 
									'life'       => $tmpSoldierInfo['life'],
								 	'num'        => $v['soldier_num'], 
									'maxNum'     => $v['soldier_num'],
                                    'generalMaxNum'=> $v['soldier_num'],
									'type'       => $tmpSoldierInfo['soldier_type'],
									'range'      => $tmpSoldierInfo['distance'], 
									'skill'      => $tmpSoldierInfo['skillList'], 
									'takeDamage' => 0, 
									'killedNum'  => 0,
									'injuredNum' => 0,
                                    'reviveNum'  => 0,
									'killList'   => [],
									'killWeight' => 0,
									'totalTakeDamage' => 0,
									'totalDamage'	  => 0,
								];
							}
						}
					}
				}
				$ps = $PlayerSoldier->getByPlayerId($key);
				if($this->crossBattleId==0 && $this->cityBattleId==0){
                    $pb = $PlayerBuild->getByOrgId($key, 2);
                    $dGeneralId = $pb[0]['general_id_1'];
                }else{
                    $dGeneralId = 0;
                }
				foreach ($ps as $v) {
					if($v['num']>0){
						$tmpSoldierInfo = $Soldier->dicGetOne($v['soldier_id']);
						$result[] = [
							'from'            => 2,
							'playerId'        => $key,
							'generalId'       => $dGeneralId,
                            'generalStar'     => 0,
							'soldierId'       => $v['soldier_id'], 
							'power'           => $tmpSoldierInfo['power']*$v['num'],
							'attack'          => $tmpSoldierInfo['attack'], 
							'defend'          => $tmpSoldierInfo['defense'], 
							'life'            => $tmpSoldierInfo['life'],
							'num'             => $v['num'], 
							'maxNum'          => $v['num'],
                            'generalMaxNum'  => $v['num'],
							'type'            => $tmpSoldierInfo['soldier_type'],
							'range'           => $tmpSoldierInfo['distance'], 
							'skill'           => $tmpSoldierInfo['skillList'], 
							'takeDamage'      => 0, 
							'killedNum'       => 0,
							'injuredNum'      => 0,
                            'reviveNum'       => 0,
							'killList'        => [],
							'killWeight'      => 0,
							'totalTakeDamage' => 0,
							'totalDamage'	  => 0,
						];
					}
				}
			}else{
				$tmpArmyInfo = $PlayerArmyUnit->getByArmyId($key, $value);
				foreach ($tmpArmyInfo as $k => $v) {
					if($v['soldier_num']>0){
						$tmpSoldierInfo = $Soldier->dicGetOne($v['soldier_id']);
						$result[] = [
							'from'            => 1,
							'playerId'        => $key,
							'generalId'       => $v['general_id'],
							'soldierId'       => $v['soldier_id'],
                            'generalStar'     => 0,
							'power'           => $tmpSoldierInfo['power']*$v['soldier_num'],
							'attack'          => $tmpSoldierInfo['attack'], 
							'defend'          => $tmpSoldierInfo['defense'], 
							'life'            => $tmpSoldierInfo['life'],
							'num'             => $v['soldier_num'], 
							'maxNum'          => $v['soldier_num'],
                            'generalMaxNum'  => $v['soldier_num'],
							'type'            => $tmpSoldierInfo['soldier_type'],
							'range'           => $tmpSoldierInfo['distance'], 
							'skill'           => $tmpSoldierInfo['skillList'], 
							'takeDamage'      => 0, 
							'killedNum'       => 0,
							'injuredNum'      => 0,
                            'reviveNum'       => 0,
							'killList'        => [],
							'killWeight'      => 0,
							'totalTakeDamage' => 0,
							'totalDamage'	  => 0,
						];
					}
				}
			}
		}
		return $result;
	}

	/**
	 * 计算神武将技能发动
	 * @return [type] [description]
	 */
	function calcGeneralSkill($unitList, $isAttack=true){
        if($this->crossBattleId>0){
            $PlayerGeneral = new CrossPlayerGeneral;
            $PlayerGeneral->battleId = $this->crossBattleId;
        }elseif($this->cityBattleId>0){
            $PlayerGeneral = new CityBattlePlayerGeneral;
            $PlayerGeneral->battleId = $this->cityBattleId;
        }else{
            $PlayerGeneral = new PlayerGeneral;
        }

		foreach($unitList as $key=>$value){
			if(empty($value['generalId']) || $value['from']==2){
				continue;
			}
			$general = $PlayerGeneral->getTotalAttr($value['playerId'], $value['generalId']);
            $generalStar = $general['PlayerGeneral']['star_lv']*1;
			if(!empty($general['skill']['combat'])){
                if($isAttack==true){
                    $selfIndex = 'attack';
                    $oppIndex = 'defend';
                }else{
                    $selfIndex = 'defend';
                    $oppIndex = 'attack';
                }

                if(7==$general['skill']['combat']['type']){//神武将技能 type=7 减少对方防御%
                    $this->reduceDefendBuff[$oppIndex] = 1-(1-$this->reduceDefendBuff[$oppIndex])*(1-$general['skill']['combat']['value']);
                }

                if(8==$general['skill']['combat']['type']){//神武将技能 type=8 减少对方生命%
                    $this->reduceLifeBuff[$oppIndex] = 1-(1-$this->reduceLifeBuff[$oppIndex])*(1-$general['skill']['combat']['value']);
                }

                if(12==$general['skill']['combat']['type']){//神武将技能 type=12 减少对方攻击%
                    $this->reduceAttackBuff[$oppIndex] = 1-(1-$this->reduceAttackBuff[$oppIndex])*(1-$general['skill']['combat']['value']);
                }

                if(10==$general['skill']['combat']['type']){//神武将技能 type=10 [关羽] 去除对方一个武将
                    $this->removeGeneralArr[$oppIndex][] = ['ap'=>$value['playerId'], 'ag'=>$value['generalId'], 'dp'=>0, 'dg'=>0];
                }

                if(9==$general['skill']['combat']['type']){//神武将技能 type=9 [张飞] 降低对方武将武力
                    $this->reduceGeneralForce[$oppIndex] = 1-(1-$this->reduceGeneralForce[$oppIndex])*(1-$general['skill']['combat']['value']);
                }

                if(18==$general['skill']['combat']['type']){//神武将技能 type=18 [司马懿] 去除对方一个武将
                    $this->removeGeneralArr[$oppIndex][] = ['ap'=>$value['playerId'], 'ag'=>$value['generalId'], 'dp'=>0, 'dg'=>0];
                }

                if(23==$general['skill']['combat']['type']){//神武将技能 type=23 [黄忠] 去除对方一个武将
                    $this->removeGeneralArr[$oppIndex][] = ['ap'=>$value['playerId'], 'ag'=>$value['generalId'], 'dp'=>0, 'dg'=>0];
                }

                if(11==$general['skill']['combat']['type']){//神武将技能 type=11 [孙策] 麾下士兵必定造成全额伤害
                    $this->godGeneralSkillArr[$selfIndex][] = ['star'=>$generalStar, 'type'=>$general['skill']['combat']['type'], 'pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'para'=>$general['skill']['combat']['value'], 'damage'=>0];
                }elseif(13==$general['skill']['combat']['type']){//神武将技能 type=13 [曹操] 反弹对方伤害
                    $this->godGeneralSkillArr[$selfIndex][] = ['star'=>$generalStar, 'type'=>$general['skill']['combat']['type'], 'pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'para'=>$general['skill']['combat']['value'], 'damage'=>0];
                }elseif(16==$general['skill']['combat']['type']){//神武将技能 type=16 [周瑜] 火攻对方部队
                    $this->godGeneralSkillArr[$selfIndex][] = ['star'=>$generalStar, 'type'=>$general['skill']['combat']['type'], 'pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'para'=>$general['skill']['combat']['value'], 'damage'=>0, 'num'=>$this->getGeneralSkillHitNum()];
                }elseif(19==$general['skill']['combat']['type']){//神武将技能 type=19 [诸葛亮] 减低对方部队防御
                    $this->godGeneralSkillArr[$selfIndex][] = ['star'=>$generalStar, 'type'=>$general['skill']['combat']['type'], 'pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'para'=>$general['skill']['combat']['value'], 'num'=>$this->getGeneralSkillHitNum()];
                }elseif(22==$general['skill']['combat']['type']){//神武将技能 type=22 [周泰] 麾下士兵视作整体同时死亡
                    $this->godGeneralSkillArr[$selfIndex][] = ['star'=>$generalStar, 'type'=>$general['skill']['combat']['type'], 'pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'para'=>$general['skill']['combat']['value'], 'damage'=>0, 'allDead'=>false];
                }elseif(26==$general['skill']['combat']['type']){//神武将技能 type=26 [马超] 对对方造成基于同射程士兵总队数的伤害
                    $this->godGeneralSkillArr[$selfIndex][] = ['star'=>$generalStar, 'type'=>$general['skill']['combat']['type'], 'pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'para'=>$general['skill']['combat']['value'], 'damage'=>0];
                }elseif(!empty($general['skill']['combat']['type'])){
                    $this->godGeneralSkillArr[$selfIndex][] = ['star'=>$generalStar, 'type'=>$general['skill']['combat']['type'], 'pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'para'=>$general['skill']['combat']['value']];
                }
			}
		}
	}

	/**
	 * 计算士兵受到武将加成之后的攻击防御
	 * @param  [type] $unitList [description]
	 * @param  [type] $battleType [description]
	 * @return [type]            [description]
	 */
	function calcSoldierAttr($unitList, $battleType, $isAttack=true){
        if($this->crossBattleId>0){
            $PlayerGeneral = new CrossPlayerGeneral;
            $PlayerGeneral->battleId = $this->crossBattleId;
        }elseif($this->cityBattleId>0){
        $PlayerGeneral = new CityBattlePlayerGeneral;
            $PlayerGeneral->battleId = $this->cityBattleId;
        }else{
            $PlayerGeneral = new PlayerGeneral;
        }
		$Soldier = new Soldier;

        if($isAttack){
            $selfIndex = 'attack';
            $oppIndex = 'defend';
        }else {
            $selfIndex = 'defend';
            $oppIndex = 'attack';
        }

		$maxNum = 0;
		$canRemoveList = [];
		foreach($unitList as $key=>$value){
			if(!empty($value['generalId']) && $value['from']==1){
				$maxNum++;
				$canRemoveList[] = ['pid'=>$value['playerId'], 'gid'=>$value['generalId'], 'unitKey'=>$key];
			}
		}
		if($maxNum>0){
            $removeNum = count($this->removeGeneralArr[$selfIndex]);
            if($removeNum>0){//关羽 司马懿 黄忠 技能
                if($removeNum<$maxNum){
                    $removeKey = array_rand($canRemoveList, $removeNum);
                }else{
                    $removeKey = array_rand($canRemoveList, $maxNum);
                }
                if(!is_array($removeKey)){
                    $removeKey = [$removeKey];
                }
                foreach ($removeKey as $key=>$value) {
                    $oppGeneral = $PlayerGeneral->getTotalAttr($this->removeGeneralArr[$selfIndex][$key]['ap'], $this->removeGeneralArr[$selfIndex][$key]['ag']);
                    $selfGeneral = $PlayerGeneral->getTotalAttr($unitList[$canRemoveList[$value]['unitKey']]['playerId'], $unitList[$canRemoveList[$value]['unitKey']]['generalId']);
                    if($oppGeneral['General']['general_original_id']==10106){//关羽 比武力
                        $oppGeneralAttr = $oppGeneral['attr']['force'];
                        if(in_array($oppGeneral['skill']['combat']['type'], [10,11])){//战斗中额外增加武力的神武将技能
                            $oppGeneralAttr += $oppGeneral['skill']['combat']['value'];
                        }
                        $selfGeneralAttr = $selfGeneral['attr']['force'];
                        if(in_array($selfGeneral['skill']['combat']['type'], [10,11])){//战斗中额外增加武力的神武将技能
                            $selfGeneralAttr += $selfGeneral['skill']['combat']['value'];
                        }
                    }elseif($oppGeneral['General']['general_original_id']==10103){//司马懿比智力
                        $oppGeneralAttr = $oppGeneral['attr']['intelligence'];
                        if(in_array($oppGeneral['skill']['combat']['type'], [18])){//战斗中额外增加智力的神武将技能
                            $oppGeneralAttr += $oppGeneral['skill']['combat']['value'];
                        }
                        $selfGeneralAttr = $selfGeneral['attr']['intelligence'];
                        if(in_array($selfGeneral['skill']['combat']['type'], [18])){//战斗中额外增加智力的神武将技能
                            $selfGeneralAttr += $selfGeneral['skill']['combat']['value'];
                        }
                    }elseif($oppGeneral['General']['general_original_id']==10109){//黄忠拼脸
                        $oppGeneralAttr = $oppGeneral['skill']['combat']['value'];
                        $selfGeneralAttr = lcg_value()*10000;
                    }
                    $this->removeGeneralArr[$selfIndex][$key]['dp'] = $unitList[$canRemoveList[$value]['unitKey']]['playerId'];
                    $this->removeGeneralArr[$selfIndex][$key]['dg'] = $unitList[$canRemoveList[$value]['unitKey']]['generalId'];
                    if($oppGeneralAttr>$selfGeneralAttr){
                        $unitList[$canRemoveList[$value]['unitKey']]['removeGeneral'] = true;
                        $this->removeGeneralArr[$selfIndex][$key]['flag'] = true;
                    }else{
                        $this->removeGeneralArr[$selfIndex][$key]['flag'] = false;
                    }
                }
            }
		}

        $General = new General;
		foreach($unitList as $key=>$value){
			if(empty($value['generalId'])) {
                $unitList[$key]['godSkill'] = ['type'=>0];
                continue;
            }

            $general = $PlayerGeneral->getTotalAttr($value['playerId'], $value['generalId']);
			if(in_array($general['skill']['combat']['type'], [10,11])){//战斗中额外增加武力的神武将技能
				$general['attr']['force'] += $general['skill']['combat']['value'];
			}
            if(in_array($general['skill']['combat']['type'], [18])){//战斗中额外增加智力的神武将技能
                $general['attr']['intelligence'] += $general['skill']['combat']['value'];
            }
			if($isAttack){
				$general['attr']['force'] *= (1-$this->reduceGeneralForce['attack']);
			}else{
				$general['attr']['force'] *= (1-$this->reduceGeneralForce['defend']);
			}

            $soldier = $Soldier->dicGetOne($value['soldierId']);
            if($value['from']==1){
                $range = $soldier['distance'];
                if(empty($this->rangeNumList[$selfIndex][$range])){
                    $this->rangeNumList[$selfIndex][$range] = 1;
                }else{
                    $this->rangeNumList[$selfIndex][$range]++;
                }
            }

			if(empty($value['removeGeneral'])){
				switch ($soldier['soldier_type']) {
					case 1:
                    case 5:
						$sStr = 'infantry';
						break;
					case 2:
						$sStr = 'cavalry';
						break;
					case 3:
						$sStr = 'archer';
						break;
					case 4:
						$sStr = 'siege';
						break;
					default:
						$sStr = false;
						break;
				}
				switch($battleType){
					case 1:
						$btStr = 'citybattle';
						break;
					case 2:
					case 7:
						$btStr = 'fieldbattle';
						break;
					default:
						$btStr = false;
						break;
				}
                if($General->isGod($value['generalId'])){
                    $a = 0.001;
                }else{
                    $a = 0.0002;
                }

                $attackBuff = 1+($general['attr']['force']>$general['attr']['intelligence']?$general['attr']['force']:$general['attr']['intelligence'])*$a;
                $defendBuff = 1+$general['attr']['governing']*$a;
                $lifeBuff = 1+$general['attr']['governing']*$a;
                if($sStr){
                    $attackBuff += empty($general['buff']["{$sStr}_atk_plus"])?0:$general['buff']["{$sStr}_atk_plus"];
                    $defendBuff += empty($general['buff']["{$sStr}_def_plus"])?0:$general['buff']["{$sStr}_def_plus"];
                    $lifeBuff += empty($general['buff']["{$sStr}_life_plus"])?0:$general['buff']["{$sStr}_life_plus"];
                }
                if($btStr && $sStr){
                    $attackBuff += empty($general['buff']["{$btStr}_{$sStr}_atk_plus"])?0:$general['buff']["{$btStr}_{$sStr}_atk_plus"];
                    $defendBuff += empty($general['buff']["{$btStr}_{$sStr}_def_plus"])?0:$general['buff']["{$btStr}_{$sStr}_def_plus"];
                    $lifeBuff += empty($general['buff']["{$btStr}_{$sStr}_life_plus"])?0:$general['buff']["{$btStr}_{$sStr}_life_plus"];
                }

                $unitList[$key]['attack'] = $soldier['attack']*$attackBuff;
                $unitList[$key]['defend'] = $soldier['defense']*$defendBuff;
                $unitList[$key]['life'] = $soldier['life']*$lifeBuff;
			}
            if($value['from']==1){
                $unitList[$key]['godSkill'] = $general['skill']['combat'];
                if(in_array($general['skill']['combat']['type'], [14])){//战斗中额外增加部队生命值的神武将技能
                    $unitList[$key]['life'] *= (1+$general['skill']['combat']['value']/10000);
                }
                if(in_array($general['skill']['combat']['type'], [21])){//战斗中减少部队生命值提高攻击力的神武将技能 [董卓]
                    $unitList[$key]['attack'] *= (1+$general['skill']['combat']['value']/10000);
                    $unitList[$key]['life'] *= 0.75;
                }
            }else{
                $unitList[$key]['godSkill'] = [];
            }

            $unitList[$key]['soldierRestore'] = 0;
            if($this->crossBattleId>0 || $this->cityBattleId>0){
                $pgInfo = $PlayerGeneral->getByGeneralId($value['playerId'], $value['generalId']);
                for($i=1;$i<=3;$i++){
                    if($pgInfo['cross_skill_id_'.$i]==10108){
                        $unitList[$key]['soldierRestore'] += $pgInfo['cross_skill_v1_'.$i];
                    }
                }
            }


            $unitList[$key]['power'] *= $general['soldierPower'][$soldier['soldier_type']]['powerK'];

			$playerGeneral = $PlayerGeneral->getByGeneralId($value['playerId'], $value['generalId']);
			$unitList[$key]['generalMaxNum'] = $PlayerGeneral->assign($playerGeneral)->getMaxBringSoldier();
            $unitList[$key]['generalStar'] = $playerGeneral['star_lv'];

            //周瑜技能特殊处理
            foreach ($this->godGeneralSkillArr[$selfIndex] as $k => $v) {
                if($v['pid']==$value['playerId'] && $v['gid']==$value['generalId']){
                    if($value['type']==16){
                        $unitList[$key]['godSkill']['exDamageTimes'] = $v['num'];
                    }
                    break;
                }
            }

		}
		//诸葛亮技能特殊处理
		$reduceDefendNum = 0;
        foreach ($this->godGeneralSkillArr[$oppIndex] as $value) {
            if($value['type']==19){
                $reduceDefendNum += $value['num'];
            }
        }


        if($reduceDefendNum>0){
            $allUnitNum = count($unitList);
            $zhugeStartKey = mt_rand(0, max($allUnitNum-$reduceDefendNum,0));
            $n = 0;
            foreach ($this->godGeneralSkillArr[$oppIndex] as $value) {
                if($value['type']==19){
                    for($i=0;$i<$value['num'];$i++){
                        if(isset($unitList[$zhugeStartKey+$n])){
                            $unitList[$zhugeStartKey+$n]['defend'] *= 1-$value['para'];
                            $n++;
                        }
                    }
                }
            }
        }
		return $unitList;
	}

	/**
	 * 士兵转换为伤兵
	 * @param  [type] &$unitList [description]
	 * @param  [type] $dieRate  [description]
	 * @return [type]            [description]
	 */
	public function changeInjureSoldier($unitList, $dieRate){
		foreach ($unitList as $key => $value) {
			$deathNum = ceil($value['killedNum']*$dieRate);
			$unitList[$key]['injuredNum'] = $value['killedNum']-$deathNum;
			$unitList[$key]['killedNum'] = $deathNum;
		}
		return $unitList;
	}

	/**
	 * 格式化战斗单位数组 使其符合战报要求
	 * @param  [type] $unitList [description]
	 * @return [type]           [description]
	 */
	function formatUnitList($unitList, $buildList=[], &$losePower=0, &$losePowerRate=0, &$soldierLosePower=0, &$losePowerTrue=0, &$loseSoldierNum=0){
		$Soldier = new Soldier;
		$PlayerBuff = new PlayerBuff;
		$PlayerGeneral = new PlayerGeneral;
		$result = [];
		$totalPower = 0;
		foreach ($unitList as $key => $value) {
            $tmpSoldier = $Soldier->dicGetOne($value['soldierId']);
            if($this->crossBattleId==0 && $this->cityBattleId==0){
                $tmpPlayerGeneral = $PlayerGeneral->getTotalAttr($value['playerId'], $value['generalId']);
                switch ($tmpSoldier['soldier_type']) {
                    case 1:
                    case 5:
                        $pre = 'infantry';
                        break;
                    case 2:
                        $pre = 'cavalry';
                        break;
                    case 3:
                        $pre = 'archer';
                        break;
                    case 4:
                        $pre = 'siege';
                        break;
                    default:
                        $pre = false;
                        break;
                }
                if(!empty($pre)){
                    $weightBuff = $PlayerBuff->getPlayerBuff($value['playerId'], $pre."_carry_plus");
                    if(!empty($tmpPlayerGeneral['buff'][$pre."_carry_plus"])){
                        $weightBuff += $tmpPlayerGeneral['buff'][$pre."_carry_plus"];
                    }
                }else{
                    $weightBuff = 0;
                }
            }else{
                $weightBuff = 0;
            }

			$losePower += ($value['maxNum']-$value['num']-$value['reviveNum'])/$value['maxNum']*$value['power'];
            $soldierLosePower += ($value['maxNum']-$value['num']-$value['reviveNum'])*$tmpSoldier['power'];
            $losePowerTrue += ($value['maxNum']-$value['num'])*$tmpSoldier['power'];
            $loseSoldierNum += $value['maxNum']-$value['num'];
			$totalPower += $value['power'];
			if(empty($result[$value['playerId']])){
				$result[$value['playerId']] = 
				[
					'power'      => $value['power'],
					'losePower'	 => ($value['maxNum']-$value['num']-$value['reviveNum'])/$value['maxNum']*$value['power'],
					'killWeight' => $value['killWeight'],
					'weight'     => $tmpSoldier['weight']*$value['num']*(1+$weightBuff),
					'unit'       =>
					[
						'0' =>
							[
								'general_id'  => $value['generalId'],
								'soldier_id'  => $value['soldierId'],
                                'general_star'=> $value['generalStar']*1,
								'attack'	  => $value['attack']*1,
								'defend'	  => $value['defend']*1,
								'life'	  	  => $value['life']*1,
								'soldier_num' => $value['maxNum'],
								'kill_num'    => array_sum($value['killList']),//消灭
								'killed_num'  => $value['killedNum'],//损失
								'injure_num'  => $value['injuredNum'],//受伤
                                'revive_num'  => $value['reviveNum'],//复活
								'live_num'    => $value['num'],//存活
								'takeDamage'  => floor($value['totalTakeDamage']),//受到伤害
								'doDamage'	  => floor($value['totalDamage']),//造成伤害
							]
					]
				];
			}else{
				$result[$value['playerId']]['power']      += $value['power'];
				$result[$value['playerId']]['losePower']  += ($value['maxNum']-$value['num']-$value['reviveNum'])/$value['maxNum']*$value['power'];
				$result[$value['playerId']]['killWeight'] += $value['killWeight'];
				$result[$value['playerId']]['weight']     += $tmpSoldier['weight']*$value['num']*(1+$weightBuff);
				$result[$value['playerId']]['unit'][] = 
					[
						'general_id'  => $value['generalId'],
						'soldier_id'  => $value['soldierId'],
                        'general_star'=> $value['generalStar']*1,
						'attack'	  => $value['attack']*1,
						'defend'	  => $value['defend']*1,
						'life'	  	  => $value['life']*1,
						'soldier_num' => $value['maxNum'],
						'kill_num'    => array_sum($value['killList']),//消灭
						'killed_num'  => $value['killedNum'],//损失
						'injure_num'  => $value['injuredNum'],//受伤
                        'revive_num'  => $value['reviveNum'],//复活
						'live_num'    => $value['num'],//存活
						'takeDamage'  => floor($value['totalTakeDamage']),//受到伤害
						'doDamage'	  => floor($value['totalDamage']),//造成伤害
					];
			}
		}
		if(!empty($buildList)){
			foreach($buildList as $key=>$value){
				if($value['type']==0){
					$result[$value['playerId']]['unit']['tower'] = 
						[
							'soldier_id'  =>0,
							'soldier_num' =>1,//箭塔数量
							'kill_num'    =>array_sum($value['killList']),//箭塔击杀
							'killed_num'  =>0,
							'injure_num'  =>0,
							'live_num'    =>0,
						];
				}else{
					$result[$value['playerId']]['unit']['trap'][] = 
						[
							'soldier_id'  =>$value['trapId'],//陷阱种类
							'soldier_num' =>$value['maxNum'],//陷阱数量
							'kill_num'    =>array_sum($value['killList']),//陷阱击杀
							'killed_num'  =>$value['killedNum'],//损失数量
							'injure_num'  =>0,
							'live_num'    =>$value['num'],//剩余数量
						];
				}
			}
		}
		if(!empty($totalPower)){
			$losePowerRate = $losePower/$totalPower;
		}else{
			$losePowerRate = 0;
		}
		
		return $result;
	}

	/**
	 * 计算Npc单维列表
	 * @param  [type] $npcArr [description]
	 * @return [type]         [description]
	 */
	function getNpcUnitList($npcArr){
		$npcId = $npcArr['npc_id'];
		$Npc = new Npc;
		$npc = $Npc->dicGetOne($npcId);

		$result[] = [
                        'from'            => 1,
						'playerId'        => 0,
						'generalId'       => 0,
                        'generalStar'     => 0,
						'soldierId'       => 0, 
						'power'           => 0,
						'attack'          => $npc['attack'], 
						'defend'          => $npc['defense'], 
						'life'            => (empty($npcArr['npc_hp']))?$npc['life']*$npc['hp_ratio']:$npcArr['npc_hp']*$npc['hp_ratio'],
						'num'             => (empty($npcArr['npc_num']))?$npc['number']:$npcArr['npc_num'], 
						'maxNum'          => (empty($npcArr['npc_num']))?$npc['number']:$npcArr['npc_num'],
                        'generalMaxNum'  => (empty($npcArr['npc_num']))?$npc['number']:$npcArr['npc_num'],
						'type'            => 1,
						'range'           => 1, 
						'skill'           => [], 
						'takeDamage'      => 0, 
						'killedNum'       => 0,
						'injuredNum'      => 0,
                        'reviveNum'       => 0,
						'killList'        => [],
						'killWeight'      => 0,
						'npc_id'          => $npcId,
						'totalTakeDamage' => 0,
						'totalDamage'	  => 0,
						'ratio'           => $npc['hp_ratio'],
					];
		return $result;
	}

	/**
	 * 计算黄巾起义Npc列表
	 * @param  [type] $npcArr [description]
	 * @return [type]         [description]
	 */
	function getNpcUnitListByWaveId($waveId){
		$HuangjinAttackMob = new HuangjinAttackMob;
		$Soldier = new Soldier;
		$mobInfo = $HuangjinAttackMob->dicGetOne($waveId);
		$soldierArr = $mobInfo['type_and_count'];

		foreach ($soldierArr as $key=>$value) {
			$tmpSoldierInfo = $Soldier->dicGetOne($value[0]);
			$result[] = [
                        'from'            => 1,
						'playerId'        => 0,
						'generalId'       => 0,
                        'generalStar'     => 0,
						'soldierId'       => $value[0], 
						'power'           => $tmpSoldierInfo['power']*$value[1],
						'attack'          => $tmpSoldierInfo['attack'], 
						'defend'          => $tmpSoldierInfo['defense'], 
						'life'            => $tmpSoldierInfo['life'],
						'num'             => $value[1], 
						'maxNum'          => $value[1],
                        'generalMaxNum'  => $value[1],
						'type'            => $tmpSoldierInfo['soldier_type'],
						'range'           => $tmpSoldierInfo['distance'], 
						'skill'           => $tmpSoldierInfo['skillList'], 
						'takeDamage'      => 0, 
						'killedNum'       => 0, 
						'injuredNum'      => 0,
                        'reviveNum'       => 0,
						'killList'        => [],
						'killWeight'      => 0,
						'totalTakeDamage' => 0,
						'totalDamage'	  => 0,
						'ratio'           => 1,
					];
		}
		return $result;
	}

	public function getGeneralSkillHitNum(){
        $s = lcg_value1();
        if ($s>0.5){
           return 1;
        }elseif($s>0.25){
            return 2;
        }elseif($s>0.125){
            return 3;
        }elseif($s>0.0625){
            return 4;
        }elseif($s>0.0325){
            return 5;
        }else{
            return 6;
        }
    }
	
	public function dropGodGeneralBook($ret){
		//算总权重
		$aKillWeight = 0;
		$dKillWeight = 0;
		foreach($ret['aFormatList'] as $_playerId => $_r){
			$aKillWeight += $_r['killWeight'];
		}
		foreach($ret['dFormatList'] as $_playerId => $_r){
			$dKillWeight += $_r['killWeight'];
		}
		
		//发邮件
		$PlayerMail = new PlayerMail;
		$Drop = new Drop;
		foreach(['a'=>$ret['aFormatList'], 'd'=>$ret['dFormatList']] as $_k => $_formatList){
			if($_k == 'a'){
				$_killPower = $ret['dLosePower'];
				$_killWeight = $aKillWeight;
			}else{
				$_killPower = $ret['aLosePower'];
				$_killWeight = $dKillWeight;
			}
			if(!$_killWeight)
				continue;
			foreach($_formatList as $_playerId => $_r){
				$__killPowerCP = $__killPower = $_killPower * $_r['killWeight'] / $_killWeight;
				
				$_j = 0;
				$_book = [];
				foreach([7=>250010, 6=>250009, 5=>250008, 4=>250007, 3=>250006] as $_n=>$_dropid){
					$x = floor($__killPowerCP / pow(10, $_n));
					if($x){
						@$_book[$_dropid] += $x;
						$_j++;
						$__killPowerCP -= $x * pow(10, $_n);
					}
					if($_j >= 2)
						break;
				}
				if($_book){
					$toPlayerIds = [$_playerId];
					$type = PlayerMail::TYPE_BATTLE_EXPBOOK;
					$title = '获取杀敌经验书';
					$msg = '';
					$time = 0;
					$data = '';
					$item = [];
					foreach($_book as $_dropid => $_num){
						$_drop = $Drop->dicGetOne($_dropid);
						foreach($_drop['drop_data'] as $_d){
							$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2]*$_num, $item);
						}
					}
					$PlayerMail->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data, $item);
				}
			}
		}
		return true;
	}
}