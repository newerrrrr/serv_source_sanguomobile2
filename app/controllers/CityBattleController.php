<?php
class CityBattleController extends ControllerBase{
	public $mapXBegin = 9;
	public $mapXEnd = 75;
	public $mapXBorderEnd = 84;
	public $mapYBegin = 9;
	public $mapYEnd = 75;
	public $mapYBorderEnd = 84;
	public $soldierTypeIds = [
		1 => [10019, 10020],
		2 => [20019, 20020],
		3 => [30019, 30020],
		4 => [40019, 40020],
	];
	public $catapultDistance = 40;//投石车攻击半径
	private $bnqcache = [];//blocknqueue的缓存
	public $safeSection = [6, 7];//内城战安全区

    //查看某轮城战列表
    function getCityBattleListAction(){
        $post = getPost();
        $roundId = floor(@$post['round']);
        if(empty($roundId)){
            $CityBattleRound = new CityBattleRound;
            $roundId = $CityBattleRound->getCurrentRound();
        }
        $CityBattle = new CityBattle;
        $list = $CityBattle->getRoundBattleList($roundId);
        echo $this->data->send(['battleList'=>$list]);
    }

	/**
	 *  攻城战玩家迁城
	 *
	 * url: cross/siegeChangeLocation
	 * postData: {"area":1}
	 * return: {}
	 */
	public function siegeChangeLocationAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$campId = $player['camp_id'];
		$post = getPost();
		$area = floor(@$post['area']);

		$CityBattlePlayer = new CityBattlePlayer;
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId);
        $cbp = $CityBattlePlayer->getByPlayerId($playerId);

        if($cbp['area']==$area){
            $errCode = 10790;//当前已在该区域
            goto sendErr;
        }

		$CityBattle = new CityBattle;
        $isActivity = $CityBattle->canChangeLocation($battleId);
        $isSiege = $CityBattle->inSeige($battleId);
        if(!$isActivity){
            $errCode = 10791;//不在战斗阶段
            goto sendErr;
        }
        if(!$isSiege){
            $errCode = 10792;//不是攻城阶段
            goto sendErr;
        }
        if($cbp['status']==0){
            $errCode = 10793;//玩家未进入战场
            goto sendErr;
        }

        $CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
        $CityBattlePlayerProjectQueue->battleId = $battleId;
        $hasActivityQueue = $CityBattlePlayerProjectQueue->hasActivityQueue($playerId);
        if($hasActivityQueue){
            $errCode = 10794;//部队行军中
            goto sendErr;
        }

		$cb = $CityBattle->getBattle($battleId);
		$defendCamp = $cb['camp_id'];
		if($defendCamp==$campId || $campId==$area){//守方随意迁城 攻击方只能迁自己的区域
            $CountryBasicSetting = new CountryBasicSetting;
            $changeCD = $CountryBasicSetting->getValueByKey("wf_castle_teleport_colddown");

            if($cbp['change_location_time']>time()-$changeCD){
                $errCode = 10795;//迁城CD中
                goto sendErr;
            }
			$CityBattleMap = new CityBattleMap;
			$mapType = $cb['map_type'];
			$position = $CityBattleMap->getNewCastlePosition($playerId, $campId, $battleId, $mapType, $area);

			if($position){
				$CityBattleMap->changeCastleLocation($battleId, $playerId, $position[0], $position[1], $area);
			}else{
				$errCode = 10796;//迁城失败
				goto sendErr;
			}
		}else{
            $errCode = 10797;//不能迁到该区域
            goto sendErr;
        }
sendErr:
		if(!empty($errCode)){
			echo $this->data->sendErr($errCode);
			exit;
		}else{
			echo $this->data->send();
			exit;
		}
	}

    /**
     *  攻城战玩家复活
     *
     * url: cross/siegeRevive
     * postData: {}
     * return: {}
     */
    public function siegeReviveAction(){
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $campId = $player['camp_id'];

		$CityBattlePlayer = new CityBattlePlayer;
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId);
		$CityBattlePlayer->battleId = $battleId;
        $cbp = $CityBattlePlayer->getByPlayerId($playerId);
        $CityBattle = new CityBattle;
        $isActivity = $CityBattle->canChangeLocation($battleId);
        $isSiege = $CityBattle->inSeige($battleId);
        if(!$isActivity){
            $errCode = 10798;//不在战斗阶段
            goto sendErr;
        }
        if(!$isSiege){
            $errCode = 10799;//不是攻城阶段
            goto sendErr;
        }
        if($cbp['status']==0){
            $errCode = 10800;//玩家未进入战场
            goto sendErr;
        }
		$cb = $CityBattle->getBattle($battleId);
		$defendCamp = $cb['camp_id'];

        $CountryBasicSetting = new CountryBasicSetting;
        if($defendCamp==$campId){
            $basicCD = $CountryBasicSetting->getValueByKey("wf_defender_respawn_time");
            $exCD = $CountryBasicSetting->getValueByKey("wf_defender_respawn_add_time");
            $reviveCD = $basicCD+$exCD*$cbp['dead_times'];
        }else{
            $basicCD = $CountryBasicSetting->getValueByKey("wf_attacker_respawn_time");
            $exCD = $CountryBasicSetting->getValueByKey("wf_attacker_respawn_add_time");
            $reviveCD = $basicCD+$exCD*$cbp['dead_times'];
        }

        if($cbp['dead_time']>time()-$reviveCD){
            $errCode = 10801;//复活CD中
            goto sendErr;
        }

		if($defendCamp==$campId){
			$area = 4;
		}else{
			$area = $campId;
		}
		$mapType = $cb['map_type'];
		$CityBattleMap = new CityBattleMap;
		$position = $CityBattleMap->getNewCastlePosition($playerId, $campId, $battleId, $mapType, $area);

		if($cbp['is_dead']==1 && $position){
			$CityBattleMap->changeCastleLocation($battleId, $playerId, $position[0], $position[1], $area);
            $CityBattlePlayer->updateAll(['is_dead'=>0, 'dead_times'=>$cbp['dead_times']+1], ['id'=>$cbp['id']]);
		}else{
			$errCode = 10802;//无法复活
			goto sendErr;
		}
sendErr:
		if(!empty($errCode)){
			echo $this->data->sendErr($errCode);
			exit;
		}else{
			echo $this->data->send();
			exit;
		}
    }

    /**
     *  内城战玩家迁城
     *
     * url: cross/meleeChangeLocation
     * postData: {"section":1}
     * return: {}
     */
    public function meleeChangeLocationAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$campId = $player['camp_id'];
		$post = getPost();
		$section = floor(@$post['section']);

		$CityBattlePlayer = new CityBattlePlayer;
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId);
		$CityBattlePlayer->battleId = $battleId;
        $cbp = $CityBattlePlayer->getByPlayerId($playerId);

        if($cbp['section']==$section){
            $errCode = 10803;//当前已在该区域
            goto sendErr;
        }

        $CityBattle = new CityBattle;
		$cb = $CityBattle->getBattle($battleId);
        $isActivity = $CityBattle->canChangeLocation($cb);
        $isSiege = $CityBattle->inSeige($battleId);
        if(!$isActivity){
            $errCode = 10804;//不在战斗阶段
            goto sendErr;
        }
        if($isSiege){
            $errCode = 10805;//不是内城战阶段
            goto sendErr;
        }
        if($cbp['status']==0){
            $errCode = 10806;//玩家未进入战场
            goto sendErr;
        }
		//如果已经进入内城战，判断进入的阵营是否淘汰
		if(!in_array($cbp['camp_id'], [$cb['attack_camp'], $cb['defend_camp']])){
			 $errCode = 10807;//所属阵营已被淘汰
            goto sendErr;
		}
		
        $CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
        $CityBattlePlayerProjectQueue->battleId = $battleId;
        $hasActivityQueue = $CityBattlePlayerProjectQueue->hasActivityQueue($playerId);
        if($hasActivityQueue){
            $errCode = 10808;//部队行军中
            goto sendErr;
        }

        $CountryBasicSetting = new CountryBasicSetting;
        $changeCD = $CountryBasicSetting->getValueByKey("wf_castle_teleport_colddown");
        if($cbp['change_location_time']>time()-$changeCD){
            $errCode = 10809;//迁城CD中
            goto sendErr;
        }
        $cb = $CityBattle->getBattle($battleId);
		$mapType = $cb['map_type'];
		$CityBattleMap = new CityBattleMap;
		$position = $CityBattleMap->getNewCastlePosition($playerId, $campId, $battleId, $mapType, $section, false);

		if($position){
			$CityBattleMap->changeCastleLocation($battleId, $playerId, $position[0], $position[1], 1, false, $section);
			(new CityBattleTask)->_refreshScore($battleId);
		}else{
			$errCode = 10810;//无法迁城
			goto sendErr;
		}
		sendErr:
		if(!empty($errCode)){
			echo $this->data->sendErr($errCode);
			exit;
		}else{
			echo $this->data->send();
			exit;
		}
    }

    /**
     *  内城战玩家复活
     *
     * url: cross/meleeRevive
     * postData: {}
     * return: {}
     */
    public function meleeReviveAction(){
        $player = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $campId = $player['camp_id'];

		$CityBattlePlayer = new CityBattlePlayer;
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId);
		$CityBattlePlayer->battleId = $battleId;
        $cbp = $CityBattlePlayer->getByPlayerId($playerId);

        $CityBattle = new CityBattle;
        $cb = $CityBattle->getBattle($battleId);
        $isActivity = $CityBattle->canChangeLocation($cb);
        $isSiege = $CityBattle->inSeige($battleId);
        if(!$isActivity){
            $errCode = 10811;//不在战斗阶段
            goto sendErr;
        }
        if($isSiege){
            $errCode = 10812;//不是内城战阶段
            goto sendErr;
        }
        if($cbp['status']==0){
            $errCode = 10813;//玩家未进入战场
            goto sendErr;
        }
		//如果已经进入内城战，判断进入的阵营是否淘汰
		if(!in_array($cbp['camp_id'], [$cb['attack_camp'], $cb['defend_camp']])){
			 $errCode = 10814;//所属阵营已被淘汰
            goto sendErr;
		}

        $cb = $CityBattle->getBattle($battleId);
        //$defendCamp = $cb['camp_id'];
		$isAttack = $CityBattle->isAttack($campId, $battleId);
        $CountryBasicSetting = new CountryBasicSetting;
        if(!$isAttack){
            $basicCD = $CountryBasicSetting->getValueByKey("wf_defender_respawn_time");
            $exCD = $CountryBasicSetting->getValueByKey("wf_defender_respawn_add_time");
            $reviveCD = $basicCD+$exCD*$cbp['dead_times'];
        }else{
            $basicCD = $CountryBasicSetting->getValueByKey("wf_attacker_respawn_time");
            $exCD = $CountryBasicSetting->getValueByKey("wf_attacker_respawn_add_time");
            $reviveCD = $basicCD+$exCD*$cbp['dead_times'];
        }

        if($cbp['dead_time']>time()-$reviveCD){
            $errCode = 10815;//复活CD中
            goto sendErr;
        }


		if($isAttack){
			$section = 6;
		}else{
			$section = 7;
		}
		$mapType = $cb['map_type'];
		$CityBattleMap = new CityBattleMap;
		$position = $CityBattleMap->getNewCastlePosition($playerId, $campId, $battleId, $mapType, $section, false);

		if($cbp['is_dead']==1 && $position){
			$CityBattleMap->changeCastleLocation($battleId, $playerId, $position[0], $position[1], 1, false, $section);
            $CityBattlePlayer->updateAll(['is_dead'=>0, 'dead_times'=>$cbp['dead_times']+1], ['id'=>$cbp['id']]);
		}else{
			$errCode = 10816;//无法复活
			goto sendErr;
		}
sendErr:
		if(!empty($errCode)){
			echo $this->data->sendErr($errCode);
			exit;
		}else{
			echo $this->data->send();
			exit;
		}
    }

	/**
	 *  城战报名
	 *
	 * url: city_battle/signCityBattle
	 * postData: {"cityId":1,"signType":1}
	 * return: {}
	 */
    function signCityBattleAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $post = getPost();
        $cityId = floor(@$post['cityId']);
        $signType = floor(@$post['signType']);

        $campId = $player['camp_id'];
        $guildId = $player['guild_id'];

        if($campId==0){
            $errCode = 10817;//无阵营不能报名
            goto sendErr;
        }

        $City = new City;
        if(!$City->canSign($cityId, $campId)){
            $errCode = 10818;//所在阵营不能抵达该城市
            goto sendErr;
        }

        $CityBattleSign = new CityBattleSign;
        $CityBattleRound = new CityBattleRound;
        $CityBattle = new CityBattle;
        $roundInfo = $CityBattleRound->getCurrentRoundInfo();
        $roundId = $roundInfo['id'];
        $roundStatus = $roundInfo['status'];
        $cityBattle = $CityBattle->getBattleByCityId($cityId);
        $cityBattleId = $cityBattle['id'];

        //报名条件检测
        $PlayerBuild = new PlayerBuild;
        $playerCastleLv = $PlayerBuild->getPlayerCastleLevel($playerId);
        $CountryBasicSetting = new CountryBasicSetting;
        $levelLimit = $CountryBasicSetting->getValueByKey("entry_conditions_level");
        if($playerCastleLv<$levelLimit){
            $errCode = 10819;//主城等级不足
            goto sendErr;
        }

		$CityBattlePlayer = new CityBattlePlayer;
        $CityBattlePlayer->battleId = $cityBattleId;
		$cbp = $CityBattlePlayer->getByPlayerId($playerId, false, true);
        $ScoreLimit = $CountryBasicSetting->getValueByKey("entry_conditions_score");
		if(!empty($cbp) && $cbp['round_id']==$roundId-1 && $cbp['score']<=$ScoreLimit){
            $errCode = 10820;//上场城战积分不够
            goto sendErr;
		}
		$cbs = $CityBattleSign->getPlayerSign($playerId);
		if(!empty($cbs)){
            $errCode = 10821;//报名重复
            goto sendErr;
        }

        if($roundStatus==CityBattleRound::SIGN_FIRST && $signType==1){//优先报名
            //检测玩家是否符合优先报名条件
            if($player['total_rmb']>=5000){
                $PlayerItem = new PlayerItem;
                $itemId = $CountryBasicSetting->getValueByKey("vip_sign_up_condition_item");
                $itemNum = $PlayerItem->hasItemCount($playerId, $itemId);
                if($itemNum>0){
                    $success = $CityBattleSign->sign($playerId, $cityBattleId, $roundId, $campId, $signType, $player['general_power']);
                    if($success){
                        $PlayerItem->drop($playerId, $itemId, 1);
                    }
                }else{
                    $errCode = 10822;//缺少报名道具
                    goto sendErr;
                }
            }else{
                $errCode = 10823;//不能参加诸侯报名
                goto sendErr;
            }
        }elseif($roundStatus==CityBattleRound::SIGN_NORMAL && $signType==2){//收费报名
            $PlayerItem = new PlayerItem;
            $itemId = $CountryBasicSetting->getValueByKey("arrow_sign_up_condition");
            $itemNum = $PlayerItem->hasItemCount($playerId, $itemId);
            if($itemNum>0){
                $success = $CityBattleSign->sign($playerId, $cityBattleId, $roundId, $campId, $signType, $player['general_power']);
                if($success){
                    $PlayerItem->drop($playerId, $itemId, 1);
                }
            }
        }elseif($roundStatus==CityBattleRound::SIGN_NORMAL && $signType==3){//普通报名
            $success = $CityBattleSign->sign($playerId, $cityBattleId, $roundId, $campId, $signType, $player['general_power']);
        }else{
            $success = false;
        }
        if(!$success){
            $errCode = 10824;//报名人数已满
        }
sendErr:
        if(!empty($errCode)){
            echo $this->data->sendErr($errCode);
            exit;
        }else{
			$CityBattleGuildMission = new CityBattleGuildMission;
            global $config;
            $serverId = $config->server_id;
            $guildId = $CityBattlePlayer->joinGuildId($serverId, $guildId);
			$CityBattleGuildMission->addGuildMission($guildId, 1);

            $newSignInfo = [];
            $newSignInfo['player'] = $CityBattleSign->getPlayerSign($playerId);
            if(!empty($newSignInfo['player'])){
                $battleId = $newSignInfo['player']['battle_id'];
                $cb = $CityBattle->getBattle($battleId);
                $newSignInfo['player']['city_id'] = $cb['city_id'];
            }
            $newSignInfo['signNum'][$campId] = $CityBattleSign->getSignInfo($cityBattleId, $campId);

            echo $this->data->send($newSignInfo);
            exit;
        }
    }

    /**
     * 更改报名城市
     *
     * url: city_battle/changeSignCity
     * postData: {"cityId":1}
     * return: {}
     */
    public function changeSignCityAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $post = getPost();
        $cityId = floor(@$post['cityId']);

        $campId = $player['camp_id'];
        $guildId = $player['guild_id'];

        $City = new City;
        if(!$City->canSign($cityId, $campId)){
            $errCode = 10825;//所在阵营不能抵达该城市
            goto sendErr;
        }


        $CityBattleSign = new CityBattleSign;
        $CityBattleRound = new CityBattleRound;
        $CityBattle = new CityBattle;
        $cb = $CityBattle->getBattleByCityId($cityId);
        $roundInfo = $CityBattleRound->getCurrentRoundInfo();
        $roundId = $roundInfo['id'];
        $roundStatus = $roundInfo['status'];
        $cityBattle = $CityBattle->getBattleByCityId($cityId);
        $cityBattleId = $cityBattle['id'];

        $cbs = $CityBattleSign->getPlayerSign($playerId);
        if(empty($cbs)){
            $errCode = 10826;//未报名
            goto sendErr;
        }
        if(!in_array($roundStatus,[CityBattleRound::SIGN_FIRST, CityBattleRound::SIGN_NORMAL])){
            $errCode = 10827;//不在报名时间段
            goto sendErr;
        }

        $successFlag = $CityBattleSign->changeCity($playerId, $campId, $cbs['battle_id'], $cb['id']);
        if(!$successFlag){
            $errCode = 10828;//目标城市已满
        }

sendErr:
        if(!empty($errCode)){
            echo $this->data->sendErr($errCode);
            exit;
        }else{
            $newSignInfo = [];
            $newSignInfo['player'] = $CityBattleSign->getPlayerSign($playerId);
            if(!empty($newSignInfo['player'])){
                $battleId = $newSignInfo['player']['battle_id'];
                $cb = $CityBattle->getBattle($battleId);
                $newSignInfo['player']['city_id'] = $cb['city_id'];
            }
            $newSignInfo['signNum'][$campId] = $CityBattleSign->getSignInfo($cityBattleId, $campId);

            echo $this->data->send($newSignInfo);
            exit;
        }
    }

    /**
     * 选定参赛武将
     * url: city_battle/setGeneral/
     * postData: {}
     * ["army":["index":1,"general_ids":[20017,20025,20018,20022,20021]]]
     * ["skill":[["generalId":11,"skillId":22],["generalId":111,"skillId":222]]]
     * return: {}
     */
    public function setGeneralAction(){
        $player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $campId = $player['camp_id'];
        if($campId==0) {
            $errCode = 10716;//[城战武将设置]你当前没有加入阵营，不能提交出战申请
            goto sendErr;
        }
        //case 比赛状态
        $CityBattleRound = new CityBattleRound;
        $PlayerGuild     = new PlayerGuild;
        $CityBattleSign  = new CityBattleSign;
        $PlayerInfo      = new PlayerInfo;

        $currentRound = $CityBattleRound->getCurrentRoundInfo();
        $currentPlayerInfo = $this->currentPlayerInfo;
       if(!$currentRound || !in_array($currentRound['status'], [CityBattleRound::SIGN_FIRST, CityBattleRound::SIGN_NORMAL])) {
           $errCode = 10717;//[城战武将设置]不在报名时间内
           goto sendErr;
       }
        $currentRoundId = $currentRound['id'];
        $cityBattlePlayerSign = $CityBattleSign->getPlayerSign($playerId, $currentRoundId);
        if(!$cityBattlePlayerSign) {
            $errCode = 10718;//[城战武将设置]尚未报名
            goto sendErr;
        }
        $generalIdList = json_decode($currentPlayerInfo['general_id_list'], true);

        if(!empty($generalIdList['skill'])) {
            $generalIdList['skill'] = array_values($generalIdList['skill']);
        }
        if(!empty($generalIdList['total_skill'])) {
            $generalIdList['total_skill'] = array_values($generalIdList['total_skill']);
        }

        if(empty($generalIdList)) {//如果无，则生成一次默认的
            $generalIdList = $PlayerGuild->getDefaultCrossArmyInfo($playerId);
            $PlayerInfo->alter($playerId, ['general_id_list'=>json_encode($generalIdList)]);
            (new PlayerCommonLog)->add($playerId, ['type'=>'[城战军团]首次', 'current_round_id'=>$currentRoundId, 'general_id_list'=>$generalIdList]);//日志记录
        }
//        dump($generalIdList);
        $postData = getPost();
       // $postData['army'] = ['index'=>1, 'general_ids'=>[20017,20025,20018,20022,20021/*,20026*/]];
       // $postData['skill'] = [['generalId'=>20058, 'skillId'=>10105], ['generalId'=>20065, 'skillId'=>10098]];//10105
        if(isset($postData['army'])) {//更换军团武将信息
            $PlayerGeneral = new PlayerGeneral;
            $armyData      = $postData['army'];
            $armyIndex     = $armyData['index'];//第几军团
            if(!in_array($armyIndex, [0,1])) {
                $errCode = 10719;//[城战武将设置]传入军团信息有误
                goto sendErr;
            }
            $generalIds = (Array)$armyData['general_ids'];
            if(count($generalIds)==0 || count($generalIds)>6) {
                $errCode = 10720;//[城战武将设置]武将数据有误-传入数量不对
                goto sendErr;
            }
            //case 验证武将准确性
            foreach($generalIds as $gid) {
                if(!$PlayerGeneral->getByGeneralId($playerId, $gid)) {
                    $errCode = 10721;//[城战武将设置]武将数据有误-传入玩家不存在的武将
                    goto sendErr;
                }
            }
            //case 当前技能更改：删除已有武将的技能（判断当前武将中是否有已选技能），添加新增武将的技能
            $needRemoveGeneralIds = array_diff($generalIdList['army'][$armyIndex], $generalIds);
            //检查已选
            foreach($generalIdList['skill'] as $k=>$v) {
                if(in_array($v['generalId'], $needRemoveGeneralIds)) {
                    unset($generalIdList['skill'][$k]);
                }
            }
            //检查总技能
            foreach($generalIdList['total_skill'] as $k=>$v) {
                if(in_array($v['generalId'], $needRemoveGeneralIds)) {
                    unset($generalIdList['total_skill'][$k]);
                }
            }
            $needJoinGeneralIds = array_diff($generalIds, $generalIdList['army'][$armyIndex]);
            if(count($needJoinGeneralIds)>0) {
                $generalCrossSkills = $PlayerGuild->getGeneralCrossSkill($playerId, $needJoinGeneralIds);//技能映射
                $generalIdList['total_skill'] = array_merge($generalIdList['total_skill'], $generalCrossSkills);
            }
            //入库
            $generalIdList['army'][$armyIndex] = $generalIds;//替换掉新的武将
            $PlayerInfo->alter($playerId, ['general_id_list'=>json_encode($generalIdList)]);
            (new PlayerCommonLog)->add($playerId, ['type'=>'[城战武将设置]修改军团武将', 'current_round_id'=>$currentRoundId, 'general_id_list'=>$generalIdList]);//日志记录
        } elseif (isset($postData['skill'])) {//更改技能
            //case 检查技能是否存在
            $totalSkill = $generalIdList['total_skill'];
            $newSkill = $postData['skill'];
            if(count($newSkill)>2) {
                $errCode = 10722;
//[城战武将设置]技能数量超过2个
                goto sendErr;
            }
            // dump($newSkill);
            // dump($totalSkill);
            $totalLength = count($totalSkill);
            foreach($newSkill as $v1) {
                $i = 0;
                foreach($totalSkill as $v2) {
                    if($v2['generalId']==$v1['generalId'] && $v2['skillId']==$v1['skillId']) break;
                    $i++;
                }
                if($i==$totalLength) {
                    $errCode = 10723;//[城战武将设置]技能不存在
                    goto sendErr;
                }
            }
            $generalIdList['skill'] = $newSkill;
            //入库
            $PlayerInfo->alter($playerId, ['general_id_list'=>json_encode($generalIdList)]);
            (new PlayerCommonLog)->add($playerId, ['type'=>'[城战武将设置]修改主动技能', 'current_round_id'=>$currentRoundId, 'general_id_list'=>$generalIdList]);//日志记录
        }
        $data = [];
        $data['general_id_list'] = $generalIdList;
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    //查看城战排名
    function getCityBattleRank(){

    }

    /**
     * 阵营捐献
     * 
     * 
     * @return <type>
     */
	public function scienceDonateAction(){
		global $config;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$scienceType = floor(@$post['scienceType']);
		$btn = floor(@$post['btn']);
		if(!checkRegularNumber($scienceType) || !checkRegularNumber($btn))
			exit;
		if(!in_array($btn, array(1, 2, 3)))
			exit;
		
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':campId=' .$player['camp_id'].':scienceType='.$scienceType;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		$db2 = $this->di['db_citybattle_server'];
		dbBegin($db);
		dbBegin($db2);

		try {
			$campId = $player['camp_id'];
			if(!$campId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取周数
			$week = (new CityBattleRound)->getCurrentWeek();
			if(!$week){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$CountryScienceExp = new CountryScienceExp;
			$countryScienceExp = $CountryScienceExp->dicGetOne($week);
			
			
			//获取公会科技
			if(!$player['camp_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$CityBattleScience = new CityBattleScience;
			$cityBattleScience = $CityBattleScience->getForUpdate($player['camp_id'], $scienceType);
			if(!$cityBattleScience){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$nextLevel = $cityBattleScience['science_level']+1;
			//获取科技
			$CountryScience = new CountryScience;
			$countryScience = $CountryScience->getByScienceType($scienceType, $nextLevel);
			if(!$countryScience){
				throw new Exception(10736); //已经达到捐献等级上限
			}
			
			//检查科技是否master
			if($cityBattleScience['science_level'] >= $countryScience['max_level']){
				throw new Exception(10737); //已经达到捐献等级上限
			}
						
			//获取玩家捐献数据
			$PlayerCitybattleDonate = new PlayerCitybattleDonate;
			$pgd = $PlayerCitybattleDonate->getByPlayerId($playerId);
			if(!$pgd){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$buttonLimit = $PlayerCitybattleDonate->buttonLimit;
			
			//检查cd
			if($pgd['button'.$btn.'_counter'] >= $buttonLimit[$btn]){
				throw new Exception(10738); //超过捐献上限
			}
						
			$Consume = new Consume;
			if(!$Consume->del($playerId, $countryScience['button'.$btn.'_consume'])){
				throw new Exception(10118);
			}
			
			//增加捐献度
			$Drop = new Drop;
			$dropItems = $Drop->gain($playerId, array($countryScience['button'.$btn.'_drop']), 1);
			if(!$dropItems){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//增加研究值
			if(!$CityBattleScience->assign($cityBattleScience)->addExp($countryScience['button'.$btn.'_exp']*$countryScienceExp['player_exp_rate']/DIC_DATA_DIVISOR)){
				throw new Exception(10374);//CLICK TOO QUICK
			}
			
			//增加数量
			if(!$PlayerCitybattleDonate->assign($pgd)->updateData($btn, 1)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CityBattleGuildMission)->addCountByGuildType(CityBattlePlayer::joinGuildId($config->server_id, $player['guild_id']), 2, 1);//任务
							
			dbCommit($db);
			dbCommit($db2);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			dbRollback($db2);
			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			$CityBattleScience = $CityBattleScience->getByscienceType($playerId, $scienceType, true);
			echo $this->data->send(['CityBattleScience'=>$CityBattleScience]);
		}else{
			echo $this->data->sendErr($err);
		}
	}

	public function battleInfoAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		$CityBattle = new CityBattle;
		$CityBattlePlayer = new CityBattlePlayer;
		//判断是否跨服战中
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
		if(!$battleId){
			$errCode = 10724;
//当前没有战斗
            goto sendErr;
		}
		$cb = $CityBattle->getBattle($battleId);
		if(!$cb){
			$errCode = 10725;//当前没有战斗
            goto sendErr;
		}
		
		$topPlayer = [];
		if(in_array($cb['status'], [CityBattle::STATUS_CLAC_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_CLAC_MELEE, CityBattle::STATUS_FINISH])){
			$camps = (new CountryCampList)->dicGetAllId();
			foreach($camps as $_d){
				$topPlayer[$_d] = [];
				$ret = CityBattlePlayer::find(['battle_id='.$battleId.' and camp_id='.$_d.' and status>0 and kill_soldier>0', 'order'=>'kill_soldier desc', 'limit'=>5])->toArray();
				foreach($ret as $_r){
					$topPlayer[$_d][] = [
						'player_id'=>$_r['player_id'],
						'nick'=>$_r['nick'],
						'avatar_id'=>$_r['avatar_id'],
						'kill_soldier'=>$_r['kill_soldier'],
					];
				}
			}
		}
		$cb = $CityBattle->adapter([$cb])[0];
		echo $this->data->send(['battleInfo'=>$cb, 'topPlayer'=>$topPlayer]);
		exit;
		sendErr:
		echo $this->data->sendErr($errCode);
		exit;
	}
	
	public function setSoldierAction(){
		global $config;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		$unitPosition = floor(@$post['unitPosition']);
		$soldierId = @$post['soldierId'];
		//$soldierNum = @$post['soldierNum'];
		if(!checkRegularNumber($soldierId) || !checkRegularNumber($armyPosition) || !checkRegularNumber($unitPosition))
			exit;
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		dbBegin($db);

		try {
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!in_array($cb['status'], [CityBattle::STATUS_READY_SEIGE, CityBattle::STATUS_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_MELEE])){
				throw new Exception(10739); //比赛已经结束
			}
			
			//检查玩家状态
			$CityBattlePlayer->battleId = $battleId;
			if(!$cbp || !$cbp['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$PlayerGeneral = new CityBattlePlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
			$PlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			//$PlayerSoldier = new CityBattlePlayerSoldier;
			//$PlayerSoldier->battleId = $battleId;
			$PlayerArmy = new CityBattlePlayerArmy;
			$PlayerArmy->battleId = $battleId;

			
			//检查军团是否空闲
			$playerArmy = $PlayerArmy->getByPlayerId($playerId);
			$isNewArmy = true;
			$armyId = 0;
			foreach($playerArmy as $_pa){
				if($_pa['position'] == $armyPosition){
					if($_pa['status']){
						throw new Exception(10013);
					}
					$playerArmy = $_pa;
					$armyId = $_pa['id'];
					$isNewArmy = false;
				}
			}
			if($isNewArmy){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查position槽是否开启
			if(!$PlayerArmy->getByPositionId($playerId, $armyPosition)){
				$armyNum = $CityBattlePlayer->getMaxArmyNum($playerId);
				if($armyPosition > $armyNum){
					throw new Exception(10014);
				}
			}
			
			//检查武将数量是否超过上限
			if($unitPosition > $CityBattlePlayer->getArmyGeneralNum($playerId)){
				throw new Exception(10015);
			}
			
			//unset该武将士兵
			$playerArmyUnit = $PlayerArmyUnit->getByPlayerId($playerId);
			foreach($playerArmyUnit as $_pau){
				if($_pau['army_id'] != $armyId) continue;
				if($_pau['unit'] != $unitPosition) continue;
				if(!$_pau['general_id']){
					throw new Exception(10016);
				}
				$generalId = $_pau['general_id'];
				$soldierNum = $_pau['soldier_num'];
				$pau = $_pau;
				/*if($_pau['soldier_id'] && $_pau['soldier_num']){//归还空闲士兵
					if(!$PlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, $_pau['soldier_num'])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}*/
				break;
			}
			//$PlayerArmyUnit->_clearDataCache($playerId);
			if(!@$generalId){
				throw new Exception(10017);
			}
			if(!$soldierNum){
				throw new Exception(10740); //士兵数量空
			}
			
			$general = (new General)->getByGeneralId($generalId);
			
			//检查soldierId
			if(!in_array($soldierId, $this->soldierTypeIds[$general['soldier_type']])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
							
			//更新军团单位
			if(!$PlayerArmyUnit->assign($pau)->updateSoldier($playerId, $generalId, $soldierId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
							
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	public function fullfillSoldierAction(){
		global $config;
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$armyPosition = floor(@$post['armyPosition']);
		if(!checkRegularNumber($armyPosition))
			exit;
	
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		dbBegin($db);

		try {
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!in_array($cb['status'], [CityBattle::STATUS_READY_SEIGE, CityBattle::STATUS_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_MELEE])){
				throw new Exception(10741); //比赛已经结束
			}
			
			//检查玩家状态
			$CityBattlePlayer->battleId = $battleId;
			if(!$cbp || !$cbp['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$cbp['is_in_map']){
				throw new Exception(10742); //复活中无法补兵
			}
			
			$now = time();
			//$soldierTimeBuff = (new CityBattleBuff)->getCampBuff($player['camp_id'], 500);
			$soldierTimeBuff = $cbp['buff']['country_battle_soldier_revive_time_reduce']*1;
			$PlayerArmy = new CityBattlePlayerArmy;
			$PlayerArmy->battleId = $battleId;
			$playerArmy = $PlayerArmy->getByPositionId($playerId, $armyPosition);
			if(!$playerArmy){
				throw new Exception(10743); //未找到军团
			}
			if($playerArmy['status']){
				throw new Exception(10744); //军团还在执行任务
			}
			if($playerArmy['fill_soldier_time'] + ((new CountryBasicSetting)->dicGetOne('wf_soldier_buy_cd') - $soldierTimeBuff) > $now){
				throw new Exception(10745); //补兵冷却时间未到
			}
			
			$PlayerGeneral = new CityBattlePlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
			$PlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			//$PlayerSoldier = new CityBattlePlayerSoldier;
			//$PlayerSoldier->battleId = $battleId;

			if(!$PlayerArmyUnit->fullfill($playerId, $playerArmy['id'])){
				throw new Exception(10746); //兵已满
			}
			if(!$PlayerArmy->assign($playerArmy)->updateFillTime()){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			/*if(!$CityBattlePlayer->alter($playerId, ['fill_soldier_time'=>"'".date('Y-m-d H:i:s', $now)."'"])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
			//检查军团是否空闲
			/*$playerArmy = $PlayerArmy->getByPlayerId($playerId);
			$armyId = 0;
			foreach($playerArmy as $_pa){
				if($_pa['status']){
					continue;
				}
				$playerArmy = $_pa;
				$armyId = $_pa['id'];
				
				$playerArmyUnit = $PlayerArmyUnit->getByPlayerId($playerId);
				$playerArmyUnit = Set::sort($playerArmyUnit, '{n}.unit', 'asc');
				foreach($playerArmyUnit as $_pau){
					if($_pau['army_id'] != $armyId) continue;
					if(!$_pau['general_id']) continue;
					if(!$_pau['soldier_id']){
						if(!$_pau['last_soldier_id']){
							$soldierType = (new General)->getByGeneralId($_pau['general_id'])['soldier_type'];
							$_pau['last_soldier_id'] = $this->soldierTypeIds[$soldierType][0];
						}
						$_pau['soldier_id'] = $_pau['last_soldier_id'];
					}
					//检查带兵上限
					$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $_pau['general_id']);
					$_bringSoldierMax = $PlayerGeneral->assign($playerGeneral)->getMaxBringSoldier();
					if(!$_bringSoldierMax){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					//更新军团单位
					$PlayerArmyUnit->assign($_pau)->updatePosition($_pau['general_id'], $_pau['soldier_id'], $_bringSoldierMax);

				}
				$PlayerArmyUnit->_clearDataCache($playerId);
			}*/
			
							
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	public function showBlockNQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		//$areaList = @$post['areaList'];
		$queueList = @$post['queueList'];
		
		//获取battleId
		$CityBattle = new CityBattle;
		$CityBattlePlayer = new CityBattlePlayer;
		//判断是否跨服战中
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
		if(!$battleId){
			$errCode = 10727;
//当前没有战斗
            echo $this->data->sendErr($errCode);
			exit;
		}
		$cb = $CityBattle->getBattle($battleId);
		if(!$cb){
			$errCode = 10728;//当前没有战斗
            echo $this->data->sendErr($errCode);
			exit;
		}
		//判断是否跨服战中
		if(!in_array($cb['status'], [CityBattle::STATUS_READY_SEIGE, CityBattle::STATUS_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_MELEE])){
			$this->battleInfoAction();
			exit;
		}
		
		//$ad = (new CrossBattle)->getADGuildId($crossBattle);
		//$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		//$areas = $this->getViewArea($guildId, $ad, $crossBattle);
		
		$result1 = $this->_showArea($cb, $player['camp_id'], $err1);
		$result2 = $this->_showQueue($cb, $player, $cbp, $queueList, $err2);
		$catapult = $this->_showCatapultTarget($player, $cb);
		
		$cb = $CityBattle->adapter([$cb])[0];
		
		//$crossPlayer = $this->getBnqCache('player', $battleId, $playerId);

		if(!$err1 && !$err2){
			echo $this->data->send(['block'=>$result1, 'queue'=>$result2, 'battleInfo'=>$cb, 'catapult'=>$catapult, 'citybattlePlayer'=>$cbp]);
		}else{
			if($err1)
				echo $this->data->sendErr($err1);
			else
				echo $this->data->sendErr($err2);
		}
	}
	
	/**
     * 取块数据
     *
     * ```php
     * /Cross/showArea/
     * postData: json={"AreaList":[]}
     * return: json{"Map":"", "Player":"", "Guild":""}
     * ```
     *
     */
    public function showAreaAction(){
		global $config;
        // debug('------------------B');
        //debug("ST-".time());

		$player = $this->getCurrentPlayer();
        $playerId = $this->getCurrentPlayerId();
        // debug('------player_id='.$playerId);
        //$post = getPost();
        //$areaList = $post['areaList'];
		
		//获取battleId
		$CityBattle = new CityBattle;
		$CityBattlePlayer = new CityBattlePlayer;
		//判断是否跨服战中
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
		if(!$battleId){
			exit;
		}
		$cb = $CityBattle->getBattle($battleId);
		if(!$cb){
			exit;
		}
		//判断是否跨服战中
		if(!in_array($cb['status'], [CityBattle::STATUS_READY_SEIGE, CityBattle::STATUS_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_MELEE])){
			exit;
		}
		
		//$ad = (new CrossBattle)->getADGuildId($crossBattle);
		//$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		//$areas = $this->getViewArea($guildId, $ad, $crossBattle);
        /*$CrossPlayer = new CrossPlayer;
        $player = $CrossPlayer->getByPlayerId($playerId);
        $guildId = $player['guild_id'];
        $CrossBattle = new CrossBattle;
        $battleId = $CrossBattle->getBattleIdByGuildId($guildId);*/
        $result = $this->_showArea($cb, $player['camp_id'], $err);

        if(!$err){
            echo $this->data->send($result);
            // debug('------------------E');
        }else{
            echo $this->data->sendErr($err);
        }
    }
	
	public function showQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$blockList = @$post['blockList'];
		
		//获取battleId
		$CityBattle = new CityBattle;
		$CityBattlePlayer = new CityBattlePlayer;
		//判断是否跨服战中
		$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
		if(!$battleId){
			$errCode = 10729;
//当前没有战斗
            echo $this->data->sendErr($errCode);
			exit;
		}
		$cb = $CityBattle->getBattle($battleId);
		if(!$cb){
			$errCode = 10730;//当前没有战斗
            echo $this->data->sendErr($errCode);
			exit;
		}
		//判断是否跨服战中
		if(!in_array($cb['status'], [CityBattle::STATUS_READY_SEIGE, CityBattle::STATUS_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_MELEE])){
			$errCode = 10731;//比赛已经结束
			echo $this->data->sendErr($errCode);
			exit;
		}
		
		$result = $this->_showQueue($cb, $player, $cbp, $blockList, $err);
		
		if(!$err){
			echo $this->data->send($result);
		}else{
			echo $this->data->sendErr($err);
		}

	}
	
	public function _showArea($cb, $campId, &$err=0){
		$battleId = $cb['id'];
        $Map = new CityBattleMap;
        $Player = new CityBattlePlayer;
		$Player->battleId = $battleId;
        $Camp = new CityBattleCamp;
		$Camp->battleId = $battleId;

        $result = ['Map'=>[], 'Player'=>[]];
        $err = 0;
		$tmpList = $Map->find("battle_id={$battleId} and status=1")->toArray();
		$tmpList = $Map->adapter($tmpList);
		foreach ($tmpList as $key => $value) {
			//过滤非视野内的敌方玩家
			//if($value['map_element_origin_id'] == 406 && $value['camp_id'] != $campId && !in_array($value['area'], $areaList)) continue;
			$result['Map'][$value['id']] = $value;
			if(!empty($value['player_id']) && empty($result['Player'][$value['player_id']])){
				$whiteList = ["id", "user_code","server_id","camp_id","nick","avatar_id","level","wall_durability","wall_durability_max","prev_x","prev_y","map_id","x","y","is_in_map","is_in_map","rowversion","rank_title"];
				//$tmpPlayerInfo = $Player->getByPlayerId($value['player_id']);
				$tmpPlayerInfo = $this->getBnqCache('player', $battleId, $value['player_id']);
				$result['Player'][$value['player_id']] = keepFields($tmpPlayerInfo, $whiteList, true);
			}
			/*if(!empty($value['camp_id']) && empty($result['Camp'][$value['camp_id']])){
				$result['Camp'][$value['camp_id']] = $this->getBnqCache('camp', $battleId, $value['camp_id']);
			}*/
		}
        return $result;
    }
	
	public function _showQueue($cb, $player, $cbp, $blockList, &$err=0){
		global $config;
		$playerId = $player['id'];
		if(!is_array($blockList))
			exit;
		foreach($blockList as $_b){
			if(!checkRegularNumber($_b, true))
				exit;
		}
		
		try {
			$battleId = $cb['id'];
			
			//计算area
			//$ad = (new CrossBattle)->getADGuildId($crossBattle);
			//$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
			//$areas = $this->getViewArea($guildId, $ad, $crossBattle);
			/*if($crossPlayer['is_in_map']){
				$areaId = (new CrossMap)->getByXy($battleId, $crossPlayer['x'], $crossPlayer['y'])['area'];
			}elseif($crossPlayer['prev_x'] && $crossPlayer['prev_y']){
				$areaId = (new CrossMapConfig)->getAreaByXy($crossBattle['map_type'], $crossPlayer['prev_x'], $crossPlayer['prev_y']);
			}else{
				$areaId = 1;
			}*/
			
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$sortGatherReturn = [];
			//转化xy
			//$xys = array();
			$ret2 = array();
			foreach($blockList as $_b){
				$_xy = CityBattleMap::calcXyByBlock($_b);
				$_xy = array(
					'from_x'=>max(0, $_xy['from_x']-12),
					'to_x'=>min($this->mapXBorderEnd, $_xy['to_x']+12),
					'from_y'=>max(0, $_xy['from_y']-12),
					'to_y'=>min($this->mapYBorderEnd, $_xy['to_y']+12),
				);
				$ret = $PlayerProjectQueue->find(['battle_id='.$battleId.' and status=1'])->toArray();//todo
				//过滤2
				$p3 = array('x'=>floor(($_xy['from_x'] + $_xy['to_x'])/2), 'y'=>floor(($_xy['from_y'] + $_xy['to_y'])/2));
				$r = sqrt(pow(floor(abs($_xy['from_x'] - $_xy['to_x'])/2), 2) + pow(floor(abs($_xy['from_y'] - $_xy['to_y'])/2), 2));
				foreach($ret as $_r){
					if($_r['player_id'] == $playerId){
						$ret2[$_r['id']] = $_r;
					}else{
						$dis = $this->GetNearestDistance(array('x'=>$_r['from_x'], 'y'=>$_r['from_y']), array('x'=>$_r['to_x'], 'y'=>$_r['to_y']), $p3);
						if($dis <= $r){
							$ret2[$_r['id']] = $_r;
						}
					}
				}
			}
			
			$queue = array();
			$playerIds = array();
			$mapXys = array();
			$PlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			foreach($ret2 as $_r){
				//获取部队展现形式
				$_at = Cache::db(CACHEDB_PLAYER, 'CityBattle')->get('queueSoldierType:'.$_r['id']);
				if($_at){
					$_r['army_type'] = $_at;
				}else{
					$_r['army_type'] = [];
					if($_r['army_id']){
						if($_pau = $PlayerArmyUnit->getByArmyId($_r['player_id'], $_r['army_id'])){
							foreach($_pau as $__pau){
								if(!$__pau['soldier_id']) continue;
								$_r['army_type'][$__pau['general_id']] = substr($__pau['soldier_id'], 0, 1)*1;
							}
						}
						if(!$_r['army_type']){//无兵队列不显示
							continue;
						}
					}
					Cache::db(CACHEDB_PLAYER, 'CityBattle')->set('queueSoldierType:'.$_r['id'], $_r['army_type']);
				}
				$playerIds[] = $_r['player_id'];
				
				
				$queue[$_r['id']] = $_r;
				$mapXys[$_r['to_map_id']] = ['x'=>$_r['to_x'], 'y'=>$_r['to_y']];
				$mapXys[$_r['from_map_id']] = ['x'=>$_r['from_x'], 'y'=>$_r['from_y']];
			}
			$queue = filterFields($queue, true, ['carry_gold', 'carry_food', 'carry_wood', 'carry_stone', 'carry_iron', 'carry_soldier']);
			$queue = $PlayerProjectQueue->afterFindQueue($queue);
			
			//整理顺序，与我有关，有我盟友有关必发，截取100条
			/*
			$myQ = [];
			$otherQ = [];
			foreach($queue as $_k => $_q){
				if($_q['player_id'] == $playerId ||
				$_q['guild_id'] == $player['guild_id'] ||
				$_q['target_player_id'] == $playerId
				){
					$myQ[$_k] = $_q;
				}else{
					$otherQ[$_k] = $_q;
				}
			}
			$otherQ = array_slice($otherQ, 0, 70-count($myQ), true);
			$queue = $myQ + $otherQ;
			*/
			
			//$campIds = array();
			//获取相关玩家信息
			$players = array();
			$playerIds = array_unique($playerIds);
			foreach($playerIds as $_playerId){
				//$_player = $CrossPlayer->getByPlayerId($_playerId);
				$_player = $this->getBnqCache('player', $battleId, $_playerId);
				if(!$_player)
					continue;
				$players[$_playerId] = $_player;
				/*if($_player['camp_id']){
					$campIds[] = $_player['camp_id'];
				}*/
			}
			//$players = filterFields($players, true, ['uuid','levelup_time','talent_num_total','talent_num_remain','general_num_total','general_num_remain','army_num','army_general_num','queue_num','move','move_max','gold','food','wood','stone','iron','silver','point','rmb_gem','gift_gem','valid_code']);
			
			//获取相关联盟信息
			/*$Guild = new CrossGuild;
			$Guild->battleId = $battleId;
			$guildIds = array_unique($guildIds);
			$guilds = array();
			foreach($guildIds as $_guildId){
				//$_guild = $Guild->getGuildInfo($_guildId);
				$_guild = $this->getBnqCache('guild', $battleId, $_guildId);
				if(!$_guild)
					continue;
				$guilds[$_guildId] = $_guild;
			}*/
			
			//获取map相关信息
			//$Map = new CityBattleMap;
			$mapElement = array();
			foreach($mapXys as $_mapId => $_mapXy){
				//$_map = $Map->getByXy($battleId, $_mapXy['x'], $_mapXy['y']);
				$_map = $this->getBnqCache('map', $battleId, ['x'=>$_mapXy['x'], 'y'=>$_mapXy['y']], $cb);
				if(!$_map)
					continue;
				$mapElement[$_mapId] = [
					'map_element_id'=>$_map['map_element_id']*1,
					'player_id'=>$_map['player_id']*1,
					'camp_id'=>$_map['camp_id']*1,
				];
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		if(!$err){
			return array('Queue'=>$queue, 'Player'=>$players, 'MapElement'=>$mapElement);
		}else{
			return false;
		}
	}
	
	public function _showCatapultTarget($player, $cb){
		global $config;
		$playerId = $player['id'];
		$battleId = $cb['id'];
		//$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
		
		//查找我占领的投石车
		$condition = ['player_id='.$playerId.' and type='.CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and end_time="0000-00-00 00:00:00" and status=1'];
		$ppq = CityBattlePlayerProjectQueue::findFirst($condition);
		if(!$ppq){
			return false;
		}
		
		$Map = new CityBattleMap;
		//$catapultMap = $Map->getByXy($battleId, $ppq->to_x, $ppq->to_y);
		$catapultMap = $this->getBnqCache('map', $battleId, ['x'=>$ppq->to_x, 'y'=>$ppq->to_y], $cb);
		if(!$catapultMap || $catapultMap['map_element_origin_id'] != 404)
			return false;
		
		/*if($crossBattle['guild_1_id'] == $guildId){
			$guildId2 = $crossBattle['guild_2_id'];
		}else{
			$guildId2 = $crossBattle['guild_1_id'];
		}*/
		//查找所有可见区域内的敌方城堡
		//判断区域是否可见
		//$guilds = (new CrossBattle)->getADGuildId($crossBattle);
		//$areas = $this->getViewArea($guildId, $guilds, $crossBattle);
		/*$crossBattle['attack_area'] = parseArray($crossBattle['attack_area']);
		if($guilds['attack'] == $guildId){//攻击方，检查区域是否开启
			$areas = $crossBattle['attack_area'];
		}else{//防守方，检查是否有工会成员在此区域
			$playerMap = $Map->find("battle_id={$battleId} and guild_id={$guildId} and map_element_origin_id=15")->toArray();
			$areas = Set::extract("/area", $playerMap);
			$areas = array_unique(array_merge([3, 4, 5], $areas));
		}*/
		$inSeige = (new CityBattle)->inSeige($cb);
		
		$maps = $Map->find(['battle_id='.$battleId.' and status=1 and map_element_origin_id=406 and camp_id<>'.$player['camp_id']])->toArray();
		
		//遍历是否在半径内
		$target = [];
		$CityBattlePlayer = new CityBattlePlayer;
		$CityBattlePlayer->battleId = $battleId;
		foreach($maps as $_m){
			if(!$inSeige && in_array($_m['section'], $this->safeSection)) continue;
			$distance = sqrt(pow($_m['x'] - $catapultMap['x'], 2) + pow($_m['y'] - $catapultMap['y'], 2));
			if($distance <= $this->catapultDistance){
				//$_player = $CrossPlayer->getByPlayerId($_m['player_id']);
				$_player = $this->getBnqCache('player', $battleId, $_m['player_id']);
				$target[] = [
					'player_id' => $_m['player_id'],
					'nick' => $_player['nick'],
					'x'=> $_m['x'],
					'y'=> $_m['y'],
					'camp_id'=> $_m['camp_id'],
					'wall_durability' => $_player['wall_durability'],
					'wall_durability_max' => $_player['wall_durability_max'],
				];
			}
		}
		
		return ['attack_time'=>strtotime($catapultMap['attack_time']), 'attack_cd'=>$catapultMap['attack_cd'], 'target'=>$target];
	}
	
	public function getBnqCache($type, $battleId, $para, $cb=''){
		if(is_array($para)){
			$_para = join('_', $para);
		}else{
			$_para = $para;
		}
		if(isset($this->bnqcache[$type][$_para])){
			return $this->bnqcache[$type][$_para];
		}else{
			switch($type){
				case 'player':
					$CityBattlePlayer = new CityBattlePlayer;
					$CityBattlePlayer->battleId = $battleId;
					$ret = $CityBattlePlayer->getByPlayerId($para);
				break;
				case 'camp':
					$CityBattleCamp = new CityBattleCamp;
					$CityBattleCamp->battleId = $battleId;
					$ret = $CityBattleCamp->getByCampId($para);
				break;
				case 'map':
					$Map = new CityBattleMap;
					$ret = $Map->getByXy($battleId, $para['x'], $para['y']);
				break;
			}
			if($ret)
				$this->bnqcache[$type][$_para] = $ret;
			return $ret;
		}
	}
	
	/**
     * 获取队伍信息
     * 
     * 
     * @return <type>
     */
	public function getQueueInfoAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}

		try {
			//获取battleId
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//判断是否跨服战中
			if(!in_array($cb['status'], [CityBattle::STATUS_READY_SEIGE, CityBattle::STATUS_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_MELEE])){
				throw new Exception(10747); //比赛已经结束
			}
		
			//获取队列
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getById($queueId);
			if(!$ppq){
				throw new Exception(10331);//找不到队列
			}
			//验证状态
			if($ppq['status'] != 1){
				throw new Exception(10332);//队列已经完成
			}
			
			//验证是否是我的队列
			if($ppq['camp_id'] != $player['camp_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$ppqs = [$ppq];
			
			//获取军团信息
			$Player = new CityBattlePlayer;
			$Player->battleId = $battleId;
			$PlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
			$ret = [];
			foreach($ppqs as $_ppq){
				$_ret = [];
				$_player = $Player->getByPlayerId($_ppq['player_id']);
				$pau = $PlayerArmyUnit->getByArmyId($_ppq['player_id'], $_ppq['army_id']);
				if(!$pau){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$_ret['player_id'] = $_player['player_id'];
				$_ret['player_nick'] = $_player['nick'];
				$_ret['army'] = [];
				foreach($pau as $_pau){
					$_tmp = [];
					$_tmp['general_id'] = $_pau['general_id'];
					$_tmp['soldier_id'] = $_pau['soldier_id'];
					$_tmp['soldier_num'] = $_pau['soldier_num'];
					$_ret['army'][] = $_tmp;
				}
				$ret[] = $_ret;
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('armyInfo'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 获取阵营成员位置
     * 
     * 
     * @return <type>
     */
	public function getCampPositionAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];

		try {
			//获取battleId
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//判断是否跨服战中
			if(!in_array($cb['status'], [CityBattle::STATUS_READY_SEIGE, CityBattle::STATUS_SEIGE, CityBattle::STATUS_READY_MELEE, CityBattle::STATUS_MELEE])){
				throw new Exception(10748); //比赛已经结束
			}
			
			
			$CityBattleMap = new CityBattleMap;
			$result = $CityBattleMap->find(['battle_id='.$battleId.' and status=1 and camp_id='.$player['camp_id'].' and map_element_origin_id=406'])->toArray();
			$ret = [];
			foreach($result as $_r){
				$ret[] = ['x'=>$_r['x'], 'y'=>$_r['y'], 'area'=>$_r['area']];
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('guildPosition'=>$ret));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 队列战斗结果
     * 
     * 
     * @return <type> 0：无数据，1：队列还未处理，2：无战斗，3：战斗胜利，4：战斗失败
     */
	public function queueBattleRetAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId))
			exit;
		
		try {
			//判断是否跨服战中
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$CityBattle->isActivity($cb)){
				throw new Exception(10749); //比赛已经结束
			}
			
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$queue = $PlayerProjectQueue->getById($queueId);
			
			$battleFlag = 0;
			if(!$queue){
				//无数据
			}else{
				if($queue['status'] == 1){
					$battleFlag = 1;
				}else{
					$battleFlag = $queue['battle']+2;
				}
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			//dbRollback($db);

			//清除缓存
		}
		//解锁
		
		if(!$err){
			echo $this->data->send(array('battle'=>$battleFlag));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 获取去往坐标时间
     * 
     * @param <type> $type 行军种类：1.采集，2.打怪，3.出征，4.侦查，5.搬运资源,6.集结
     * @return <type>
     */
	public function getGotoTimeAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		//$armyId = floor(@$post['armyId']);
		$type = floor(@$post['type']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			exit;
		}
		if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
			exit;
		if(!in_array($type, array(1, 2, 3, 4, 5, 6)))
			exit;

		try {
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$CityBattle->isActivity($cb)){
				throw new Exception(10750); //比赛已经结束
			}
			
			//获取地图点信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $x, $y);
			if(!$map)
				throw new Exception(10357);//目标不存在
						
			//获取军团
			$PlayerArmy = new CityBattlePlayerArmy;
			$PlayerArmy->battleId = $battleId;
			$armies = $PlayerArmy->getByPlayerId($playerId);
			
			//计算行军时间
			$needTime = [];
			foreach($armies as $_army){
				$_needTime = CityBattlePlayerProjectQueue::calculateMoveTime($battleId, $playerId, $cbp['x'], $cbp['y'], $x, $y, $type, $_army['id']);
				$needTime[$_army['id']] = $_needTime;
			}
						
			//如果直接使用体力
			$distance = sqrt(pow($cbp['x'] - $x, 2) + pow($cbp['y'] - $y, 2));

			$needMove = distance2move($distance);
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('time'=>$needTime, 'needMove'=>$needMove));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 召回静止队列
     * queueId： 队列id
     * 
     * @return <type>
     */
	public function callbackStayQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$queueId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		dbBegin($db);

		try {
			//判断是否跨服战中
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$CityBattle->isActivity($cb)){
				throw new Exception(10751); //比赛已经结束
			}
			
			//获取队列
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$types = $PlayerProjectQueue->stayTypes;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(10274);//未找到队列
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			if(!$PlayerProjectQueue->callbackQueue($ppq->id, $ppq->to_x, $ppq->to_y, ['playerCallBack'=>true])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			$i = 0;
			while($i < 10){
				if(!$PlayerProjectQueue->findFirst(['id='.$queueId.' and status=1'])){
					break;
				}
				usleep(500000);
				$i++;
			}
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 召回移动队列
     * queueId： 队列id
     * 
     * @return <type>
     */
	public function callbackMoveQueueAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$queueId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		dbBegin($db);

		try {
			//判断是否跨服战中
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$CityBattle->isActivity($cb)){
				throw new Exception(10752); //比赛已经结束
			}
			
			//获取队列
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$types = $PlayerProjectQueue->moveTypes;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断队列类型
			if(!isset($types[$ppq->type])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取返回type
			$returnType = $types[$ppq->type];
			
			//消耗召回道具
			$itemId = 21500;
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->drop($playerId, $itemId)){
				throw new Exception(10221);
			}
			
			if(!$PlayerProjectQueue->callbackQueue($ppq->id, $ppq->to_x, $ppq->to_y)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 加速队列
     * queueId： 队列id
     * 
     * @return <type>
     */
	public function acceQueueAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$queueId = floor(@$post['queueId']);
		if(!checkRegularNumber($queueId)){
			exit;
		}
		$itemId = 52119;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':queueId=' .$queueId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		$db2 = $this->di['db'];
		dbBegin($db);
		dbBegin($db2);

		try {
			//获取队列
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$ppq = $PlayerProjectQueue->findFirst($queueId);
			if(!$ppq || $ppq->status != 1)
				throw new Exception(10636);//无法加速，队列已经达到目的地
			if($ppq->player_id != $playerId)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断是否为移动队列
			if($ppq->from_map_id == $ppq->to_map_id){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//如果是行动力加速
			$now = time();
			//消耗道具
			$accTimeRate = 0.5;
			$v = 1 / (1-$accTimeRate);
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->drop($playerId, $itemId)){
				throw new Exception(10222);
			}
			
			//重新计算end_time
			if(strtotime($ppq->end_time) < $now){
				throw new Exception(10636);//无法加速，队列已经达到目的地
			}
			$accelerateInfo = json_decode($ppq->accelerate_info, true);
			$restSecond = max(0, strtotime($ppq->end_time) - $now);
			$cutSecond = floor($restSecond*$accTimeRate);
			$newEndTime = date('Y-m-d H:i:s', $now + ($restSecond - $cutSecond));
			
			//更新end_time
			if(!isset($accelerateInfo['log'])){
				$accelerateInfo['log'] = [];
			}
			$accelerateInfo['log'][] = array('time'=>$now, 'itemId'=>$itemId, 'cutsecond'=>$cutSecond, 'v'=>$v);
			$accelerateInfo['log'] = array_slice($accelerateInfo['log'], -10);
			
			if(!$ppq->updateAcce($newEndTime, $accelerateInfo)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			dbCommit($db);
			dbCommit($db2);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			dbRollback($db2);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 进入战场
     * 
     * 
     * @return <type>
     */
	public function enterBattlefieldAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		/*$post = getPost();
		$armyIds = @$post['armyIds'];
		if(!is_array($armyIds) || count($armyIds)>(new CrossPlayer)->getMaxArmyNum()){
			exit;
		}*/

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		$db2 = $this->di['db'];
		dbBegin($db);
		dbBegin($db2);

		try {
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$cb || $cb['status'] >= CityBattle::STATUS_CLAC_MELEE){
				throw new Exception(10753); //比赛已经结束
			}
			if($cb['status'] == CityBattle::STATUS_DEFAULT){
				throw new Exception(10754); //比赛还未开始
			}
			
			if($cbp['status'] > 0){
				throw new Exception(10755); //玩家已经进入场地
			}
			
			//如果已经进入内城战，判断进入的阵营是否淘汰
			if(!$CityBattle->inSeige($battleId)){
				if(!in_array($cbp['camp_id'], [$cb['attack_camp'], $cb['defend_camp']])){
					throw new Exception(10788); //所属阵营已被淘汰
				}
			}
			
			//查看是否有在野外的部队
			if((new PlayerProjectQueue)->findFirst(['player_id='.$playerId.' and status=1'])){
				throw new Exception(10607);//请召回所有野外部队
			}
						
			//更新玩家状态
			$CityBattlePlayer->alter($playerId, ['status'=>1]);
			
			//增加罩子
			if(!(new Player)->alter($playerId, ['is_in_cross'=>1])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new CityBattleGuildMission)->addCountByGuildType($cbp['guild_id'], 1, 1);
			
			(new CityBattleCommonLog)->add($battleId, $playerId, $player['camp_id'], '进入战场');

			dbCommit($db);
			dbCommit($db2);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			dbRollback($db2);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 去攻城
     * 
     * 
     * @return <type>
     */
	public function gogogoAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		$armyId = floor(@$post['armyId']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true) || !checkRegularNumber($armyId)){
			exit;
		}
		if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		dbBegin($db);

		try {
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			$CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$CityBattle->isActivity($cb)){
				throw new Exception(10756); //比赛已经结束
			}
			
			//获取地图点信息
			$Map = new CityBattleMap;
			$map = $Map->getByXy($battleId, $x, $y);
			if(!$map)
				throw new Exception(10626);//目标未找到
			
			if(!$cbp || !$cbp['is_in_map'] || !$cbp['status']){
				throw new Exception(10609);//正在复活中
			}
			
			//判断目标是否是同area
			$playerMap = $Map->getByXy($battleId, $cbp['x'], $cbp['y']);
			if(!$playerMap)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			if($playerMap['area'] != $map['area']){
				throw new Exception(10610);//目标不在同个区域
			}
			
			//如果城门战，一方城门/云梯已攻破
			$inSeige = $CityBattle->inSeige($cb);
			if($inSeige && $cb['door'.$cbp['camp_id']]){
				throw new Exception(10757); //攻城已完成，无法发起攻击
			}
			
			//内城战判断是否在安全区域
			if(!$inSeige && in_array($playerMap['section'], $this->safeSection)){
				throw new Exception('ERRMSG:请迁城前往1-5区域，再进行操作');
			}
			
			//判断是否非同盟城堡
			//$ad = $CrossBattle->getADGuildId($crossBattle);
			if($map['map_element_origin_id'] == 406){//城堡
				if($map['player_id'] == $playerId){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($cbp['camp_id']){
					if($cbp['camp_id'] == $map['camp_id']){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				
				//内城战判断是否在安全区域
				if(!$inSeige && in_array($map['section'], $this->safeSection)){
					throw new Exception('ERRMSG:目标在安全区域无法发起攻击');
				}
			
				$type = CityBattlePlayerProjectQueue::TYPE_CITYBATTLE_GOTO;
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 401){//城门
				//判断是否为攻击方
				if(!$CityBattle->isAttack($player['camp_id'], $battleId)){//攻击方
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			
				//判断城门血
				if(!$map['durability']){
					throw new Exception(10611);//城门已经攻破
				}
			
				$type = CityBattlePlayerProjectQueue::TYPE_ATTACKDOOR_GOTO;
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 402){//攻城锤
				
				$Map->rebuildBuilding($map);
				
				//判断血
				if(!$map['durability']){
					throw new Exception(10612);//攻城锤正在修复中，无法入驻
				}
				if($CityBattle->isAttack($player['camp_id'], $battleId)){//攻击方
					
					$type = CityBattlePlayerProjectQueue::TYPE_HAMMER_GOTO;
				}else{//防守方
					//$type = CrossPlayerProjectQueue::TYPE_ATTACKHAMMER_GOTO;
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 403){//云梯
				$Map->rebuildBuilding($map);
				
				//判断血
				if(!$map['durability']){
					throw new Exception(10613);//云梯正在修复中，无法入驻
				}
				//判断进度
				//$MapElement = new MapElement;
				//$me = $MapElement->dicGetOne($map['map_element_id']);
				if($map['resource'] >= (new CountryBasicSetting)->dicGetOne('wf_ladder_max_progress')){
					throw new Exception(10614);//天梯建造已经完成
				}
				
				if($CityBattle->isAttack($player['camp_id'], $battleId)){//攻击方
					$type = CityBattlePlayerProjectQueue::TYPE_LADDER_GOTO;
				}else{//防守方
					//$type = CrossPlayerProjectQueue::TYPE_ATTACKLADDER_GOTO;
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 405){//床弩
				if($CityBattle->isAttack($player['camp_id'], $battleId)){//防守方
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//检查是否有驻守其他床弩
				$condition = ['player_id='.$playerId.' and type='.CityBattlePlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and status=1'];
				if($CityBattlePlayerProjectQueue->findFirst($condition)){
					throw new Exception(10615);//每人只能同时占领一个床弩
				}
				
				if($map['player_id']){
					throw new Exception(10616);//该床弩已经被盟友占领
				}
				
				$type = CityBattlePlayerProjectQueue::TYPE_CROSSBOW_GOTO;
				$targetInfo = [];
			}elseif($map['map_element_origin_id'] == 404){//投石车
				
				//获取攻击方的占领区
				/*$crossBattle = $CrossBattle->getBattle($battleId);
				$attackArea = parseArray($crossBattle['attack_area']);
				
				if((in_array($map['area'], $attackArea) && $ad['attack'] == $crossPlayer['guild_id'])
					|| 
				(!in_array($map['area'], $attackArea) && $ad['defend'] == $crossPlayer['guild_id'])){
					$type = CrossPlayerProjectQueue::TYPE_CATAPULT_GOTO;
				}else{
					throw new Exception(10617);//已经失去该区域的投石车控制权
				}*/
				
				//检查是否有驻守其他
				$condition = ['player_id='.$playerId.' and type='.CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and status=1'];
				if($CityBattlePlayerProjectQueue->findFirst($condition)){
					throw new Exception(10618);//每人只能同时占领一个投石车
				}
				
				if($map['camp_id'] == $player['camp_id'] && $map['player_id']){
					throw new Exception(10619);//该投石车已经被盟友占领
				}
				
				$type = CityBattlePlayerProjectQueue::TYPE_CATAPULT_GOTO;
				$targetInfo = [];
			}else{
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$Map->doBeforeGoOut($battleId, $playerId, $armyId, false);
			
			//计算行军时间
			$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
			$CityBattlePlayerGeneral->battleId = $battleId;
			//御驾亲征:主动技：所在军团下次攻击城池或城墙时出征时伤害增加|<#0,255,0#>%{num}|%，但行军速度降低|<#0,255,0#>%{num1}|%。
			$moveDebuff = 0;
			if(in_array($type, [CityBattlePlayerProjectQueue::TYPE_CITYBATTLE_GOTO, CityBattlePlayerProjectQueue::TYPE_ATTACKDOOR_GOTO])){
				$skillId = 10054;
				if($CityBattlePlayerGeneral->getSkillsByArmies([$armyId], [$skillId])[$skillId][0]){//有该技能的武将在当前军团内
					$CityBattlePlayerMasterskill = new CityBattlePlayerMasterskill;
					$CityBattlePlayerMasterskill->battleId = $battleId;
					if($CityBattlePlayerMasterskill->useActive($playerId, $battleId, $skillId, $cpmsId)){
						$cpms = $CityBattlePlayerMasterskill->findFirst($cpmsId)->toArray();
						$moveDebuff = $cpms['v2'];
						@$targetInfo['skill'][$skillId] += $cpms['v1'];
					}
					
				}
			}
			
			//快马加鞭:军团出发时减少%
			$moveBuff = $CityBattlePlayerGeneral->getSkillsByArmies([$armyId], [3])[3][0];
			
			$needTime = CityBattlePlayerProjectQueue::calculateMoveTime($battleId, $playerId, $cbp['x'], $cbp['y'], $x, $y, 3, $armyId, $moveDebuff, $moveBuff);
			if(!$needTime){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($cbp['debuff_queuetime']){//缓兵之计
				$needTime += $cbp['debuff_queuetime'];
				$CityBattlePlayer->alter($playerId, ['debuff_queuetime'=>0]);
			}
			
			//急行军:军团出发时减少
			$needTime -= $CityBattlePlayerGeneral->getSkillsByArmies([$armyId], [2])[2][0];
			
			
			//建立队列
			$pm = $Map->getByXy($battleId, $cbp['x'], $cbp['y']);
			$dm = $map;
			if(!$pm || !$dm)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$extraData = [
				'from_map_id' => $pm['id'],
				'from_x' => $cbp['x'],
				'from_y' => $cbp['y'],
				'to_map_id' => $dm['id'],
				'to_x' => $x,
				'to_y' => $y,
				'area' => $pm['area'],
			];
			$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
			$needTime = floor(max($needTime, 0));
			if(!$PlayerProjectQueue->addQueue($playerId, $cbp['camp_id'], $map['player_id'], $type, $needTime, $armyId, $targetInfo, $extraData)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
						
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 控制投石车
     * 
     * 
     * @return <type>
     */
	public function useCatapultAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$x = floor(@$post['x']);
		$y = floor(@$post['y']);
		if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
			exit;
		}
		if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
			exit;

		//锁定
		$DispatcherTask = new CityBattleDispatcherTask;
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		dbBegin($db);

		try {
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			$CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$CityBattle->isActivity($cb)){
				throw new Exception(10758); //比赛已经结束
			}
			
			//检查玩家状态
			if(!$cbp || !$cbp['is_in_map'] || !$cbp['status']){
				throw new Exception(10609);//正在复活中
			}
			
			if($CityBattle->inSeige($cb) && $cb['door'.$cbp['camp_id']]){
				throw new Exception(10759); //攻城已完成，无法发起攻击
			}
			
			$perTry = 1;
			$tryLimit = 5;
			$i = 0;
			global $inDispWorker;
			$inDispWorker = true;
			while(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'CityBattle'), $battleId, $x, $y)){
				sleep($perTry);
				$i++;
				if($i >= $tryLimit){
					throw new Exception(10623);//请稍后重试
				}
			}
			
			$this->catapultAttack($cbp, $player['camp_id'], $battleId, $x, $y, $cb);
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		if(@$battleId)
			$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $battleId, $x, $y);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	public function catapultAttack($player, $campId, $battleId, $x, $y, $cb, $counterAttack=false){
		$playerId = $player['player_id'];
		$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
		$CityBattlePlayerGeneral->battleId = $battleId;
		
		//查找我占领的投石车
		$condition = ['player_id='.$playerId.' and type='.CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and end_time="0000-00-00 00:00:00" and status=1'];
		$ppq = CityBattlePlayerProjectQueue::findFirst($condition);
		if(!$ppq){
			throw new Exception(10624);//尚未占领投石车
		}
		//反戈一击:驻守投石车时，若自己的城池遭到攻击，投石车会立即额外反击一次，造成|<#0,255,0#>%{num}|%的投石伤害。
		if($counterAttack){
			$rate = $CityBattlePlayerGeneral->getSkillsByArmies([$ppq->army_id], [10102])[10102][0];
			if(!$rate){
				return false;
			}
		}else{
			$rate = 1;
		}
		
		$Map = new CityBattleMap;
		$catapultMap = $Map->getByXy($battleId, $ppq->to_x, $ppq->to_y);
		if(!$catapultMap || $catapultMap['map_element_origin_id'] != 404)
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		
		//检查冷却时间
		if(!$counterAttack){
			if(time() < strtotime($catapultMap['attack_time']) + $catapultMap['attack_cd']){
				throw new Exception(10625);//投石车正在冷却中
			}
		}
		
		//查找目标
		$map = $Map->getByXy($battleId, $x, $y);
		if(!$map || $map['map_element_origin_id'] != 406)
			throw new Exception(10626);//目标未找到
		
		//判断目标是否为敌方城堡
		if($map['camp_id'] == $campId){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		
		//判断对象是否城门战且已破门
		$inSeige = (new CityBattle)->inSeige($cb);
		if($inSeige && $cb['door'.$map['camp_id']]){
			throw new Exception(10760); //攻城已完成，无法发起攻击
		}
		
		//判断对象是否为内城战安全区
		if(!$inSeige && in_array($map['section'], $this->safeSection)){
			throw new Exception('ERRMSG:目标在安全区域无法发起攻击');
		}
		
		//判断区域是否可见
		/*$CityBattle = new CityBattle;
		$guilds = $CrossBattle->getADGuildId($crossBattle);
		if(!$counterAttack){
			$areas = $this->getViewArea($guildId, $guilds, $crossBattle);
			if(!in_array($map['area'], $areas)){
				throw new Exception(10627);//敌方城堡不在视野范围内
			}
		}
		*/
		//计算投石车和目标的距离
		if(!$counterAttack){
			$distance = sqrt(pow($ppq->to_x - $x, 2) + pow($ppq->to_y - $y, 2));
			if($distance > $this->catapultDistance){
				throw new Exception(10629);//敌方城堡不在投石车攻击范围内
			}
		}
		
		//计算投石车攻击力
		$formula = (new CountryBasicSetting)->getValueByKey('wf_catapult_atkpower');
		$power = (new QueueCityBattle)->getArmyPower($battleId, $playerId, $ppq->army_id);
		eval('$reduceDurability = '.$formula.';');
		if(!$reduceDurability){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		//攻击加成
		$buff = 0;
		$addBuff = 0;
		
		//君临天下：若该武将的统御高于所有敌军武将，则所有本方器械的伤害增加%
		$CityBattleCamp = new CityBattleCamp;
		$CityBattleCamp->battleId = $battleId;
		$buff += $CityBattleCamp->getByPlayerId($playerId)['buff_buildattack'];
		
		//投石精通：驻守时增加投石车攻击伤害%
		$buff += $CityBattlePlayerGeneral->getSkillsByArmies([$ppq->army_id], [18])[18][0];
		
		//城战科技：弹道学：提升投石车和床弩伤害|<#72,255,164#>%{num}%%|
		$buff += (new CityBattleBuff)->getCampBuff($player['camp_id'], 504);
		
		//床弩大师:每次攻击后，床弩的攻击力增加
		$addBuff += $CityBattlePlayerGeneral->getSkillsByArmies([$ppq->army_id], [19])[19][0] * $catapultMap['attack_times'];
		
		$reduceDurability *= 1+$buff;
		$reduceDurability += $addBuff;
		//反击百分比
		$reduceDurability = max(1, floor($reduceDurability*$rate));
		
		//玩家扣血
		$Player = new CityBattlePlayer;
		$Player->battleId = $battleId;
		$cityBattlePlayer = $Player->getByPlayerId($map['player_id']);
		if(!$cityBattlePlayer || !$cityBattlePlayer['is_in_map'] || !$cityBattlePlayer['status']){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		if(!$Player->alter($map['player_id'], ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-'.$reduceDurability.')'])){
			throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}
		$cityBattlePlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
		
		//日志
		(new CityBattleCommonLog)->add($battleId, $playerId, $player['camp_id'], '投石车'.($counterAttack ? '反':'攻').'击玩家[defend='.$cityBattlePlayer['player_id'].'('.$cityBattlePlayer['camp_id'].')]|扣血-'.$reduceDurability.',剩余'.max(0, $cityBattlePlayer['wall_durability']-$reduceDurability).'|byPlayerId='.$playerId.'('.$player['camp_id'].')');
		
		//如果玩家血0，删除城堡
		if($cityBattlePlayer['wall_durability'] <= $reduceDurability){
			//不屈之力：城防首次被击破时可恢复|<#72,255,98#>%{num}||<#3,169,235#>%{numnext}|城防值
			if(!$cityBattlePlayer['skill_first_recover']){
				$recoverhp = $CityBattlePlayerGeneral->getSkillsByPlayer($cityBattlePlayer['player_id'], null, [10089])[10089][0];
				if($recoverhp){
					$Player->alter($cityBattlePlayer['player_id'], ['wall_durability'=>'wall_durability+'.$recoverhp, 'skill_first_recover'=>1]);
					(new CityBattleCommonLog)->add($battleId, $cityBattlePlayer['player_id'], $cityBattlePlayer['camp_id'], '玩家发动不屈之力|加血+'.$recoverhp);
					(new QueueCityBattle)->crossNotice($battleId, 'skill_10089', ['nick'=>$cityBattlePlayer['nick']]);
					goto a;
				}
			}
			//城池剩余兵数算给敌方人头数
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			$killSoldierNum = $CityBattlePlayerArmyUnit->getHomeSoldier($cityBattlePlayer['player_id']);
			if($killSoldierNum){
				//更新英勇值
				$addScore = floor($killSoldierNum * (new CountryBasicSetting)->getValueByKey('kill_soldier_score') / 100);
				$Player->alter($player['player_id'], ['kill_soldier'=>'kill_soldier+'.$killSoldierNum, 'score'=>'score+'.$addScore]);
				(new CityBattleCommonLog)->add($battleId, $player['player_id'], $player['camp_id'], '更新英勇值+'.$addScore.'|by投石车杀敌');
				
				(new CityBattle)->addKill($battleId, $campId, $killSoldierNum);
			}
						
			$Map->delPlayerCastle($battleId, $cityBattlePlayer['player_id']);
			
			//一血通知
			(new CityBattle)->updateFirstBlood($cb, $player, $cityBattlePlayer);
			
			//连杀
			$Player->addContinueKill($playerId, $player, $cb);
			
			(new CityBattleGuildMission)->addCountByGuildType($player['guild_id'], 7, 1);//任务：联盟成员在跨服战中击破敌方城池%{num}次
			
			//日志
			(new CityBattleCommonLog)->add($battleId, $cityBattlePlayer['player_id'], $cityBattlePlayer['camp_id'], '玩家扑街|byPlayerId='.$playerId.'('.$cityBattlePlayer['camp_id'].')');
			
			(new QueueCityBattle)->crossNotice($battleId, 'playerDead', ['from_nick'=>$player['nick'], 'to_nick'=>$cityBattlePlayer['nick']]);
		}
		a:
		
		//更新投石车攻击时间
		if(!$counterAttack){
			$attackTime = date('Y-m-d H:i:s');
			$Map->alter($catapultMap['id'], ['attack_time'=>"'".$attackTime."'", 'attack_times'=>'attack_times+1']);
		}
		
		//长连接通知
		if(!$counterAttack){
			$msgType = 'catapultAttack';
		}else{
			$msgType = 'catapultCounterAttack';
		}
		$Player->battleId = $battleId;
		$members = $Player->find(['battle_id='.$Player->battleId.' and status>0'])->toArray();
		$playerIds = [];
		foreach($members as $_d){
			$_serverId = CityBattlePlayer::parsePlayerId($_d['player_id'])['server_id'];
			$playerIds[$_serverId][] = $_d['player_id'];
		}
		foreach($playerIds as $_serverId => $_playerIds){
			crossSocketSend($_serverId, ['Type'=>'citybattle', 'Data'=>['playerId'=>$_playerIds, 'type'=>$msgType, 'fromNick'=>$player['nick'], 'toNick'=>$cityBattlePlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $cityBattlePlayer['wall_durability']-$reduceDurability), 'from_x'=>$catapultMap['x'], 'from_y'=>$catapultMap['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']]]);
		}
		return true;
	}
	
	/**
     * 主动技使用
     * 
     * 
     * @return <type>
     */
	public function useSkillAction(){
		global $config;
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$skillId = floor(@$post['skillId']);
		if(!checkRegularNumber($generalId) || !checkRegularNumber($skillId))
			exit;

		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db_citybattle_server'];
		dbBegin($db);

		try {
			$CityBattle = new CityBattle;
			$CityBattlePlayer = new CityBattlePlayer;
			$CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
			//判断是否跨服战中
			$battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
			if(!$battleId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$cb = $CityBattle->getBattle($battleId);
			if(!$cb){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$CityBattle->isActivity($cb)){
				throw new Exception(10761); //尚不在比赛中
			}
			
			if(!$cbp || !$cbp['is_in_map'] || !$cbp['status']){
				throw new Exception(10609);//正在复活中
			}
			$campId = $cbp['camp_id'];
			
			if($CityBattle->inSeige($cb) && $cb['door'.$cbp['camp_id']]){
				throw new Exception(10762); //攻城已完成，无法发起攻击
			}
			
			//检查技能存在
			$CityBattlePlayerMasterskill = new CityBattlePlayerMasterskill;
			$CityBattlePlayerMasterskill->battleId = $battleId;
			$cpms = $CityBattlePlayerMasterskill->getBySkillId($playerId, $generalId, $skillId);
			if(!$cpms){
				throw new Exception(10647);//技能不存在
			}
			//检查技能次数
			if(!$cpms['rest_times']){
				throw new Exception(10648);//技能次数已经用完
			}
			
			//具体效果
			$ret = [];
			switch($skillId){
				case 10054://御驾亲征:所在军团下次攻击城池或城墙时出征时伤害增加|<#0,255,0#>%{num}|%，但行军速度降低|<#0,255,0#>%{num1}|%。
					
					$needActive = 1;
					$skillNotice = [];
					
				break;
				case 10098://业火冲天:对城门，攻城锤或云梯造成|<#0,255,0#>%{num}|伤害
					/*$x = floor(@$post['x']);
					$y = floor(@$post['y']);
					if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					*/
					$attackBuildRet = $this->skillAttackBuild($cb, $campId, $cbp, $cpms);
					
					//判断对象器械是否属于
					$needActive = 0;
					
					$skillNotice = [];
					$ret = ['target'=>$attackBuildRet['notices']];
				break;
				case 10105://破胆怒吼
					$skillRet = $this->skillRoar($cb, $campId, $cbp, $cpms);
					if(!$skillRet){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
					
					$needActive = 0;
					
					$ret = $skillNotice = ['fromNick'=>$cbp['nick'], 'originIds'=>$skillRet['originIds'], 'toArea'=>$skillRet['area'], 'toPlayerIds'=>$skillRet['toPlayerIds']];
				break;
				case 10110://五雷轰顶:敌军所有下次出征行军时间增加|<#0,255,0#>%{num}|秒
					$values = $cpms['v1'];
					$enemyCampIds = array_diff((new CountryCampList)->dicGetAllId(), [$player['camp_id']]);

					$members = $CityBattlePlayer->getByCampId($enemyCampIds);
					$playerIds = [];
					foreach($members as $_m){
						$CityBattlePlayer->alter($_m['player_id'], ['debuff_queuetime'=>'GREATEST(debuff_queuetime, '.$values.')']);
						$playerIds[] = $_m['player_id'];
					}
					
					$needActive = 0;
					
					$ret = $skillNotice = ['fromNick'=>$cbp['nick'], 'second'=>$values, 'toPlayerIds'=>$playerIds];
				break;
			}
			$ret['type'] = 'skill_'.$skillId;

			//使用技能
			if(!$CityBattlePlayerMasterskill->useTimes($playerId, $battleId, $generalId, $skillId, $needActive)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($skillNotice)
				(new QueueCityBattle)->crossNotice($battleId, 'skill_'.$skillId, $skillNotice);
			
			(new CityBattleGuildMission)->addCountByGuildType($cbp['guild_id'], 6, 1);//任务：联盟成员在跨服战中使用主动技能%{num}次
			
			if(!(new CityBattle)->isActivity($battleId)){
				throw new Exception(10608);//尚不在比赛中
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send(['notice'=>$ret]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 主动技：业火冲天
     * 
     * @param <type> $crossBattle 
     * @param <type> $guildId 
     * @param <type> $crossPlayer 
     * @param <type> $cpms 
     * 
     * @return <type>
     */
	public function skillAttackBuild($cb, $campId, $cbp, $cpms){
		$CityBattle = new CityBattle;
		$playerId = $cbp['player_id'];
		$battleId = $cb['id'];
		$area = $cbp['section'];
		if($CityBattle->inSeige($cb)){
			$mapType = ActiveSkillTarget::SCENE_CITYBATTLEDOOR;
		}else{
			$mapType = ActiveSkillTarget::SCENE_CITYBATTLEMELEE;
		}
		if($CityBattle->isAttack($campId, $battleId)){
			$side = ActiveSkillTarget::SIDE_ATTACK;
		}else{
			$side = ActiveSkillTarget::SIDE_DEFEND;
		}
		
		$ActiveSkillTarget = new ActiveSkillTarget;
		$ast = $ActiveSkillTarget->getTarget($mapType, 10098, $side, $area);
		if(!$ast || !$ast['target']){
			throw new Exception(10649);//未找到可攻击目标
		}
		$target = $ast['target'];
		
		$Map = new CityBattleMap;
		
		//城门战对象区域是否已经破门
		if($side == ActiveSkillTarget::SIDE_DEFEND && $mapType == ActiveSkillTarget::SCENE_CITYBATTLEDOOR && $cb['door'.$target[0][0]]){
			throw new Exception(10763); //对方阵营攻城已完成，无法发起攻击
		}
		
		$notices = [];
		$DispatcherTask = new CityBattleDispatcherTask;
		$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		
		foreach($target as $_t){
			$_targetArea = $_t[0];
			$_targetOriginIds = $_t[1];
			
			$maps = $Map->find(['battle_id='.$battleId.' and status=1 and section='.$_targetArea.' and map_element_origin_id in ('.join(',', $_targetOriginIds).') and camp_id <> '.$campId])->toArray();
			
			if($_targetOriginIds[0] == 406){
				shuffle($maps);
			}
			
			foreach($maps as $map){
				$x = $map['x'] = $map['x']*1;
				$y = $map['y'] = $map['y']*1;
				
				//lock
				$perTry = 1;
				$tryLimit = 5;
				$i = 0;
				global $inDispWorker;
				$inDispWorker = true;
				while(!$DispatcherTask->cacheAddXY(Cache::db('dispatcher', 'CityBattle'), $battleId, $x, $y)){
					sleep($perTry);
					$i++;
					if($i >= $tryLimit){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				if($i){
					$map = $Map->getByXy($battleId, $x, $y);
				}
				
				//不同器械
				switch($map['map_element_origin_id']){
					case 401://城门
						//判断城门血
						if(!$map['durability']){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $battleId, $x, $y);
							throw new Exception(10651);//城门已被攻破
							//continue;
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//城门扣血
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						(new CityBattleCommonLog)->add($map['battle_id'], $playerId, $campId, '攻击城门['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$cbp['player_id'].'('.$cbp['camp_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$cbp['nick'], 'toNick'=>'', 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$cbp['x'], 'from_y'=>$cbp['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCityBattle)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						//更新英勇值
						$addScore = floor(min($map['durability'], $reduceDurability) * (new CountryBasicSetting)->getValueByKey('damage_gate_score') / 100);
						$Player = new CityBattlePlayer;
						$Player->battleId = $battleId;
						$Player->alter($playerId, ['score'=>'score+'.$addScore]);
						(new CityBattleCommonLog)->add($battleId, $cbp['player_id'], $cbp['camp_id'], '更新英勇值+'.$addScore.'|by业火冲天');
						
						//如果破门
						if($map['durability'] <= $reduceDurability){
							
							//更新城门状况
							$CityBattle->updateDoor($map['battle_id'], $cbp['camp_id']);
							
							//撤离所有下一个区域的敌方占领投石车和床弩
							$PlayerProjectQueue->callbackCatapult($map['battle_id'], $map['next_area']);
							$PlayerProjectQueue->callbackCrossbow($map['battle_id'], $map['next_area']);
							
							//遣返本区攻城锤/云梯内部队
							if(!$cb['defend_camp']){
								$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area']);
								$PlayerProjectQueue->callbackLadder($map['battle_id'], $map['area']);
							}
							
							//任务：联盟成员在跨服战中参与击破城门%{num}次
							$guildMemberNum = $Player->getGuildMemberNumByCampId($cbp['camp_id']);
							foreach($guildMemberNum as $_guildId=>$_num){
								(new CityBattleGuildMission)->addCountByGuildType($_guildId, 5, $_num);
							}
							
							//日志
							(new CityBattleCommonLog)->add($map['battle_id'], $playerId, $cbp['camp_id'], '破门['.$map['area'].']|byPlayerId='.$cbp['player_id'].'('.$cbp['camp_id'].')');
							
							(new QueueCityBattle)->crossNotice($map['battle_id'], 'doorBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
							
							$CityBattle->endBattle($battleId);
						}
					break;
					case 402://攻城锤
						//判断是否占领
						if(!$map['camp_id']){
							goto unlock;
							break;
						}
						
						//修复
						$Map->rebuildBuilding($map);
						
						//检查血
						if(!$map['durability']){
							goto unlock;
							continue;
							//throw new Exception(10653);//攻城锤处于修理状态
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//扣血
						$recoverTime = (new CountryBasicSetting)->dicGetOne('wf_warhammer_respawn_time');
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						
						(new CityBattleCommonLog)->add($map['battle_id'], $playerId, $cbp['camp_id'], '攻击攻城锤['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$cbp['player_id'].'('.$cbp['camp_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$cbp['nick'], 'toNick'=>'', 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$cbp['x'], 'from_y'=>$cbp['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCityBattle)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果攻城锤血0，遣返所有攻城锤部队
						if($map['durability'] <= $reduceDurability){
							
							$PlayerProjectQueue->callbackHammer($map['battle_id'], $map['area'], $map['id']);
							
							//日志
							(new CityBattleCommonLog)->add($map['battle_id'], $playerId, $cbp['camp_id'], '攻城锤0血['.$map['area'].']|byPlayerId='.$cbp['player_id'].'('.$cbp['camp_id'].')');
							(new QueueCityBattle)->crossNotice($map['battle_id'], 'hammerBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
						}
						
					break;
					case 403://云梯
						//判断是否占领
						if(!$map['camp_id']){
							goto unlock;
							//throw new Exception(10654);//目标云梯未处于可攻击状态
							continue;
						}
						
						//刷新云梯进度
						$condition = ['type='.CityBattlePlayerProjectQueue::TYPE_LADDER_ING.' and battle_id='.$map['battle_id'].' and to_map_id='.$map['id'].' and status=1'];
						$ppqs = $PlayerProjectQueue->find($condition)->toArray();
						if($ppqs){
							(new QueueCityBattle)->refreshLadder($ppqs[0], $ppqs, $map, time(), $finishLadder, $finishBattle);
							if($finishLadder){
								$db = $this->di['db_citybattle_server'];
								dbCommit($db);
								throw new Exception(10655);//目标云梯已经建造完成
							}
						}
						
						//检查进度
						$ladderMaxProgress = (new CountryBasicSetting)->dicGetOne('wf_ladder_max_progress');
						if($map['resource'] >= $ladderMaxProgress){
							$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $battleId, $x, $y);
							throw new Exception(10655);//目标云梯已经建造完成
						}
						
						//修复
						$Map->rebuildBuilding($map);
						
						//检查血
						if(!$map['durability']){
							goto unlock;
							//throw new Exception(10656);//云梯处于修理状态
						}
						
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//扣血
						$recoverTime = (new CountryBasicSetting)->dicGetOne('wf_ladder_respawn_time');
						$Player = new CityBattlePlayer;
						$Player->battleId = $battleId;
						$playerIds = Set::extract('/player_id', $Player->getByCampId($map['camp_id']));
						$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
						$CityBattlePlayerGeneral->battleId = $battleId;
						$recoverTimeBuff = $CityBattlePlayerGeneral->getSkillsByPlayers($playerIds, [24])[24][0];
						$recoverTime -= $recoverTimeBuff;
						$recoverTime = floor($recoverTime);
						
						$Map->alter($map['id'], ['durability'=>'GREATEST(0, (@wall:=durability)-'.$reduceDurability.')', 'recover_time'=>'if(durability=0, "'.date('Y-m-d H:i:s', time()+$recoverTime).'", "0000-00-00 00:00:00")']);
						$map['durability'] = $Map->sqlGet('select @wall')[0]['@wall'];
						
						(new CityBattleCommonLog)->add($map['battle_id'], $playerId, $cbp['camp_id'], '攻击云梯['.$map['area'].']|扣血-'.$reduceDurability.',剩余'.max(0, $map['durability']-$reduceDurability).'|byPlayerId='.$cbp['player_id'].'('.$cbp['camp_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$cbp['nick'], 'toNick'=>'', 'reduce'=>$reduceDurability, 'rest'=>max(0, $map['durability']-$reduceDurability), 'from_x'=>$cbp['x'], 'from_y'=>$cbp['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCityBattle)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果云梯血0，遣返所有云梯部队
						if($map['durability'] <= $reduceDurability){
							$PlayerProjectQueue->callbackLadder($map['battle_id'], $map['area'], $map['id']);
							
							//日志
							(new CityBattleCommonLog)->add($map['battle_id'], $playerId, $cbp['camp_id'], '天梯0血['.$map['area'].']|byPlayerId='.$cbp['player_id'].'('.$cbp['camp_id'].')');
							(new QueueCityBattle)->crossNotice($map['battle_id'], 'ladderBroken', ['x'=>$map['x'], 'y'=>$map['y']]);
							
						}
					break;
					case 406://城堡
						//计算攻击力
						$reduceDurability = $cpms['v1'];
						
						//玩家扣血
						$Player = new CityBattlePlayer;
						$Player->battleId = $battleId;
						$cityBattlePlayer = $Player->getByPlayerId($map['player_id']);
						if(!$cityBattlePlayer || !$cityBattlePlayer['is_in_map'] || !$cityBattlePlayer['status']){
							goto unlock;
						}
						if(!$Player->alter($map['player_id'], ['wall_durability'=>'GREATEST(0, (@wall:=wall_durability)-'.$reduceDurability.')'])){
							goto unlock;
						}
						$cityBattlePlayer['wall_durability'] = $Player->sqlGet('select @wall')[0]['@wall'];
						
						//日志
						(new CityBattleCommonLog)->add($map['battle_id'], $playerId, $cbp['camp_id'], '攻击玩家[defend='.$cityBattlePlayer['player_id'].'('.$cityBattlePlayer['camp_id'].')]|扣血-'.$reduceDurability.',剩余'.max(0, $cityBattlePlayer['wall_durability']-$reduceDurability).'|byPlayerId='.$cbp['player_id'].'('.$cbp['camp_id'].')|bySkill='.$cpms['skill_id']);
						$notices[] = $notice = ['fromNick'=>$cbp['nick'], 'toNick'=>$cityBattlePlayer['nick'], 'reduce'=>$reduceDurability, 'rest'=>max(0, $cityBattlePlayer['wall_durability']-$reduceDurability), 'from_x'=>$cbp['x'], 'from_y'=>$cbp['y'], 'to_x'=>$map['x'], 'to_y'=>$map['y']];
						(new QueueCityBattle)->crossNotice($map['battle_id'], 'skill_'.$cpms['skill_id'], $notice);
						
						//如果玩家血0，删除城堡
						if($cityBattlePlayer['wall_durability'] <= $reduceDurability){
							
							//不屈之力：城防首次被击破时可恢复|<#72,255,98#>%{num}||<#3,169,235#>%{numnext}|城防值
							if(!$cityBattlePlayer['skill_first_recover']){
								$CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
								$CityBattlePlayerGeneral->battleId = $battleId;
								$recoverhp = $CityBattlePlayerGeneral->getSkillsByPlayer($cityBattlePlayer['player_id'], null, [10089])[10089][0];
								if($recoverhp){
									$Player->alter($cityBattlePlayer['player_id'], ['wall_durability'=>'wall_durability+'.$recoverhp, 'skill_first_recover'=>1]);
									(new CityBattleCommonLog)->add($battleId, $cityBattlePlayer['player_id'], $cityBattlePlayer['camp_id'], '玩家发动不屈之力|加血+'.$recoverhp);
									(new QueueCityBattle)->crossNotice($battleId, 'skill_10089', ['nick'=>$cityBattlePlayer['nick']]);
									goto attackPlayerEnd;
								}
							}
										
							$Map->delPlayerCastle($battleId, $cityBattlePlayer['player_id']);
							
							//一血通知
							$CityBattle->updateFirstBlood($cb, $cbp, $cityBattlePlayer);
							
							//连杀
							$Player->addContinueKill($playerId, $cbp, $cb);
							
							(new CityBattleGuildMission)->addCountByGuildType($cbp['guild_id'], 7, 1);//任务：联盟成员在跨服战中击破敌方城池%{num}次
							
							//日志
							(new CityBattleCommonLog)->add($battleId, $cityBattlePlayer['player_id'], $cityBattlePlayer['camp_id'], '玩家扑街|byPlayerId='.$playerId.'('.$cityBattlePlayer['camp_id'].')');
							
							(new QueueCityBattle)->crossNotice($battleId, 'playerDead', ['from_nick'=>$cbp['nick'], 'to_nick'=>$cityBattlePlayer['nick']]);
						}
						attackPlayerEnd:
					break;
				}
				
				//unlock
				unlock:
				$DispatcherTask->cacheRemoveXY(Cache::db('dispatcher', 'CityBattle'), $battleId, $x, $y);
				
				//攻击城池仅随机一个
				if($notices && $map['map_element_origin_id'] == 406){
					break;
				}
			}
			
			if($notices){
				break;
			}
		}
		if(!$notices){
			throw new Exception(10650);//未找到目标
		}
		
		return ['notices'=>$notices];
	}
	
	/**
     * 主动技：破胆怒吼
     * 
     * 
     * @return <type>
     */
	public function skillRoar($cb, $campId, $cbp, $cpms){
		$CityBattle = new CityBattle;
		$playerId = $cbp['player_id'];
		$battleId = $cb['id'];
		$area = $cbp['section'];
		if($CityBattle->inSeige($cb)){
			$mapType = ActiveSkillTarget::SCENE_CITYBATTLEDOOR;
		}else{
			$mapType = ActiveSkillTarget::SCENE_CITYBATTLEMELEE;
		}
		if($CityBattle->isAttack($campId, $battleId)){
			$side = ActiveSkillTarget::SIDE_ATTACK;
		}else{
			$side = ActiveSkillTarget::SIDE_DEFEND;
		}
		
		$ActiveSkillTarget = new ActiveSkillTarget;
		$ast = $ActiveSkillTarget->getTarget($mapType, 10105, $side, $area);
		if(!$ast || !$ast['target']){
			throw new Exception(10649);//未找到可攻击目标
		}
		$target = $ast['target'];
		
		$Map = new CityBattleMap;
		
		//城门战对象区域是否已经破门
		if($side == ActiveSkillTarget::SIDE_DEFEND && $mapType == ActiveSkillTarget::SCENE_CITYBATTLEDOOR && $cb['door'.$target[0][0]]){
			throw new Exception(10765); //对方阵营攻城已完成，无法发起攻击
		}
		
		$notices = [];
		$DispatcherTask = new CityBattleDispatcherTask;
		$PlayerProjectQueue = new CityBattlePlayerProjectQueue;
		$PlayerProjectQueue->battleId = $battleId;
		$playerIds = [];
		
		$camps = (new CountryCampList)->dicGetAllId();
		$targetCampId = array_diff($camps, [$campId]);
		
		foreach($target as $_t){
			$_targetArea = $_t[0];
			$_targetOriginIds = $_t[1];
			
			foreach($_targetOriginIds as $_originId){
				//不同器械
				switch($_originId){
					case 405://床弩
						if(!$PlayerProjectQueue->callbackCrossbow($battleId, ['section'=>$_targetArea], true, $_playerIds)){
							throw new Exception(10659);//操作超时
						}
					break;
					case 404://投石车
						if(!$PlayerProjectQueue->callbackCatapult($battleId, ['section'=>$_targetArea], $targetCampId, true, $_playerIds)){
							throw new Exception(10660);//操作超时
						}
					break;
					default:
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					break;
				}
				$playerIds = array_merge($playerIds, $_playerIds);
			}
			if($playerIds){
				break;
			}
		}
		if(!$playerIds){
			throw new Exception(10650);//未找到目标
		}
		
		return ['originIds'=>$_targetOriginIds, 'area'=>$_targetArea, 'toPlayerIds'=>$playerIds];
	}
	
	function GetPointDistance($p1, $p2){
		return sqrt(($p1['x']-$p2['x'])*($p1['x']-$p2['x'])+($p1['y']-$p2['y'])*($p1['y']-$p2['y']));
	}
	function GetNearestDistance($PA, $PB, $P3){

		$a=$this->GetPointDistance($PB,$P3);
		if($a<=0)
			return 0;
		$b=$this->GetPointDistance($PA,$P3);
		if($b<=0)
			return 0;
		$c=$this->GetPointDistance($PA,$PB);
		if($c<=0)
			return $a;//如果PA和PB坐标相同，则退出函数，并返回距离

		if($a*$a>=$b*$b+$c*$c)
			return $b;      //如果是钝角返回b
		if($b*$b>=$a*$a+$c*$c)
			return $a;      //如果是钝角返回a

		$l=($a+$b+$c)/2;
		$s=sqrt($l*($l-$a)*($l-$b)*($l-$c));
		return 2*$s/$c;
	}

    /**
     * 城池内功能产出
	 * ```php
	 *  city_battle/output
	 * ```
     */
	public function outputAction(){
        $playerId        = $this->getCurrentPlayerId();
        $lockKey         = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);//锁定

        $player       = $this->getCurrentPlayer();
        $playerCampId = $player['camp_id'];
        $postData     = getPost();
        $cityId       = $postData['city_id'];
        $isGetTime    = $postData['is_get_time'];
        $countryCityOutputDate = $this->currentPlayerInfo['country_city_output_date'];


        if($isGetTime) {
            $data = [];
            $countryCityOutputDate = empty($countryCityOutputDate) ? [] : json_decode($countryCityOutputDate, true);
            $citys                 = City::find(["camp_id={$playerCampId}", 'columns' => ['id']]);
            foreach($citys as $city) {
                if(isset($countryCityOutputDate[$city->id])) {
                    $data[$city->id] = strtotime($countryCityOutputDate[$city->id]);
                } else {
                    $data[$city->id] = 0;
                }
            }
            goto Response;
        }


        if($countryCityOutputDate) {
            $countryCityOutputDate = json_decode($countryCityOutputDate, true);
            if (isset($countryCityOutputDate[$cityId]) && strtotime($countryCityOutputDate[$cityId]) >= mktime(0, 0, 0)) {//Y-m-d 00:00:00
                $errCode = 10732;//[城池内功能产出]今日已领取，无法再领
                goto sendErr;
            }
        } else {
            $countryCityOutputDate = [];//初始化
        }
        if($playerCampId==0) {
        	$errCode = 10733;//[城池内功能产出]玩家无阵营
        	goto sendErr;
        }
        $city = City::findFirst(["id=:cityId:", 'bind'=>['cityId'=>$cityId]]);

        if (!$city || $city->camp_id != $playerCampId) {
            $errCode = 10734;//[城池内功能产出]玩家阵营与城池阵营不一致
            goto sendErr;
        }

		$cityMap = (new CountryCityMap)->dicGetOne($cityId);
        if($cityMap['city_type']!=2) {
            $errCode = 10735;//[城池内功能产出]该城池无产出
            goto sendErr;
		}
		$drop = $cityMap['drop'];
		$junziBuff = (new CityBattleBuff)->getJunziBuff($playerCampId);
        (new Drop)->gain($playerId,  parseArray($drop), 1, '[城池内功能产出奖励]', ['junziBuff'=>$junziBuff]);//获得
        $countryCityOutputDate[$cityId] = date('Y-m-d 00:00:00');
		(new PlayerInfo)->alter($playerId, ['country_city_output_date'=>json_encode($countryCityOutputDate)]);

        $data = [];
        Response:
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
	}

	/**
	 * 城战报名开始时间
	 * ```php
	 *  city_battle/getRoundInfo
	 *
	 * ```
	 */
	public function getRoundInfoAction(){
		$CityBattleRound = new CityBattleRound;
		$round = $CityBattleRound->getCurrentRoundInfo();
		$CountryBasicSetting = new CountryBasicSetting;
		$signStartTime = $CountryBasicSetting->getValueByKey("enroll_start");
		$matchReady = $CountryBasicSetting->getValueByKey("match_ready");

        $cTime = strtotime($round['create_time'])+3600*24;
        $data = ['status'=>$round['status']*1, 'signStart'=>strtotime(date("Y-m-d ".$signStartTime, $cTime)), 'signEnd'=>strtotime(date("Y-m-d ".$matchReady, $cTime))];
        echo $this->data->send($data);

	}

	/**
	 * 城池报名情况
	 * ```php
	 *  city_battle/getSignInfo
	 *	postData: {"cityId":1001, "campId":1}
	 * ```
	 */
	public function getSignInfoAction(){
        $playerId  = $this->getCurrentPlayerId();
		$postData     = getPost();
		$cityId       = $postData['cityId'];
        $campId       = $postData['campId'];
		$CityBattle = new CityBattle;
		$cityBattle = $CityBattle->getBattleByCityId($cityId);

		$CityBattleSign = new CityBattleSign;
		$data = [];
        $data['player'] = $CityBattleSign->getPlayerSign($playerId);
        if(!empty($data['player'])){
            $battleId = $data['player']['battle_id'];
            $cb = $CityBattle->getBattle($battleId);
            $data['player']['city_id'] = $cb['city_id'];
        }

        if(empty($cityBattle)){
            $data['signNum'] = false;
        }else{
            $cityBattleId = $cityBattle['id'];
            $data['signNum'][$campId] = $CityBattleSign->getSignInfo($cityBattleId, $campId);
        }
		echo $this->data->send($data);
		exit;
	}

    /**
     * 服务器第一次城战开启时间点
     * ```php
     *  city_battle/getFirstCityBattleDate
     *	postData: {}
     * ```
     */
	public function getFirstCityBattleDateAction(){
        $King = new King;
        $d = $King->getFirstKingDate();
        $dInfo = getdate(strtotime($d));
        switch($dInfo['wday']){
            case 0:
                $day = 1;
                break;
            case 1:
                $day = 7;
                break;
            case 2:
                $day = 6;
                break;
            case 3:
                $day = 5;
                break;
            case 4:
                $day = 4;
                break;
            case 5:
                $day = 3;
                break;
            case 6:
                $day = 2;
                break;
        }
        $date = date("Y-m-d", strtotime($d)+24*60*60*$day);
        echo $this->data->send(["date"=>strtotime($date)]);
        exit;
    }

    /**
     * 占领信息
	 * ```php
	 * city_battle/occupyInfo
	 * ```
     */
	public function occupyInfoAction(){
        $currentRoundId = 0;
        $cityBattleData = $campData = [];

        $lastFinishRound = CityBattleRound::findFirst(['status=:status:','bind'=>['status'=>CityBattleRound::FINISH], 'order'=>'id desc']);
        if($lastFinishRound) {
            $currentRoundId = $lastFinishRound->id;
        }
		if($currentRoundId) {
			$CityBattle = new CityBattle;
            $Camp       = new Camp;
            $City       = new City;

            $cityBattleData = $CityBattle->adapter($CityBattle->sqlGet("select city_id, win_camp, start_time from city_battle where round_id={$currentRoundId} and win_camp<>0;"));
            $_campData      = $Camp->sqlGet("select id, camp_score from camp;");
            $_campData      = Set::combine($_campData, '{n}.id', '{n}.camp_score');
            $allCamp        = (new CountryCampList)->dicGetAllId();//所有阵营
            foreach($allCamp as $v) {
				$citys             = Set::extract('/id', $City->adapter(City::find(["camp_id={$v}", 'columns' => ['id']])->toArray()));
                $_d['camp_id']     = $v;
                $_d['city_number'] = count($citys);
                $_d['camp_score']  = intval($_campData[$v]);
                $_d['city_ids']    = $citys;
                $campData[]        = $_d;
			}
		}
        $currentCityBattleStartDate = (new CityBattleRound)->getCurrentSeasonStartDate();
        $W                          = (new CountryBasicSetting)->getValueByKey('cseason_duration');
        $seasonEndTime              = $currentCityBattleStartDate + $W*7*24*60*60;//$W:周

        $seasonDuration = (new CountryBasicSetting)->getValueByKey("cseason_duration");
        $re             = (new CityBattleRound)->getCurrentRoundInfo();
        $currentDu      = $re['count_in_season'];

        if($currentDu>=$seasonDuration){
            $remainRound = 0;
        }else{
            $remainRound = intval($seasonDuration-$currentDu);
        }


        $data['cityBattle_data'] = $cityBattleData;
        $data['camp_data']       = $campData;
        $data['season_end_time'] = $seasonEndTime;
        $data['remain_round']    = $remainRound;
        echo $this->data->send($data);
        exit;
	}
    /**
     * 去侦查
     *
     *
     * @return <type>
     */
    public function spyAction(){
        $player   = $this->getCurrentPlayer();
        $playerId = $player['id'];
        $post     = getPost();
        $x        = floor(@$post['x']);
        $y        = floor(@$post['y']);
        if(!checkRegularNumber($x, true) || !checkRegularNumber($y, true)){
            exit;
        }
        if($x < $this->mapXBegin || $x > $this->mapXEnd || $y < $this->mapYBegin || $y > $this->mapYEnd)
            exit;

        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $db = $this->di['db_citybattle_server'];
        dbBegin($db);

        try {
            $CityBattle = new CityBattle;
            $CityBattlePlayer = new CityBattlePlayer;
            //判断是否城战中
            $battleId = $CityBattlePlayer->getCurrentBattleId($playerId, $cbp);
            if(!$battleId){
                throw new Exception(10767); //当前没有比赛
            }
            $cb = $CityBattle->getBattle($battleId);
            if(!$CityBattle->isActivity($cb)){
                throw new Exception(10768); //比赛已经结束
            }

            //获取地图点信息
            $Map = new CityBattleMap;
            $map = $Map->getByXy($battleId, $x, $y);
            if(!$map)
                throw new Exception(10357);//目标不存在
            //检查玩家状态
            if(!$cbp || !$cbp['is_in_map'] || !$cbp['status']){
                throw new Exception(10609);//正在复活中
            }

            $CityBattlePlayerSoldier = new CityBattlePlayerSoldier;
            $targetPlayerId = $map['player_id'];
            if($targetPlayerId) {
                $targetCityBattlePlayer = $CityBattlePlayer->getByPlayerId($targetPlayerId);
                $data['nick'] = $targetCityBattlePlayer['nick'];
            }
            //init
            $armyIdArr           = [];
            $data['battle_army'] = [];

            if($map['map_element_origin_id'] == 406){//城堡
                $_army = (new CityBattlePlayerArmy)->adapter(CityBattlePlayerArmy::find(["player_id={$targetPlayerId} and status=0 and battle_id={$battleId}"])->toArray());
                if($_army) {
                    foreach($_army as $a) {
                        $armyIdArr[] = $a['id'];
                    }
                }
                $targetCityBattlePlayer = $CityBattlePlayer->getByPlayerId($map['player_id']);
                //预备役部队类型、准确数量
                $data['durability']   = (int)$targetCityBattlePlayer['wall_durability'];//城防值
                $data['reserve_army'] = [];
                $reserveArmy = $CityBattlePlayerSoldier->adapter($CityBattlePlayerSoldier->sqlGet("select soldier_id, num from city_battle_player_soldier where battle_id={$battleId} and player_id={$targetPlayerId}"));
                if($reserveArmy) {
                    $data['reserve_army'] = $reserveArmy;
                }
            }
            elseif($map['map_element_origin_id'] == 404){//投石车catapult
                $_q = CityBattlePlayerProjectQueue::findFirst("player_id={$targetPlayerId} and type=".CityBattlePlayerProjectQueue::TYPE_CATAPULT_ING.' and battle_id='.$battleId.' and status=1');
                if($_q) {
                    $armyIdArr = [$_q->army_id];
                }
                $data['durability'] = (int)$map['durability'];//城防值
            }
            elseif($map['map_element_origin_id'] == 405){//床弩
                $_q = CityBattlePlayerProjectQueue::findFirst("player_id={$targetPlayerId} and type=".CityBattlePlayerProjectQueue::TYPE_CROSSBOW_ING.' and battle_id='.$battleId.' and status=1');
                if($_q) {
                    $armyIdArr = [$_q->army_id];
                }
            } else {
                throw new Exception(10357);//目标不存在
            }
            $CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
            $CityBattlePlayerGeneral = new CityBattlePlayerGeneral;
            //防守部队类型、准确数量
            //防守部队武将信息
            foreach($armyIdArr as $amId) {
                $_info = $CityBattlePlayerArmyUnit->adapter($CityBattlePlayerArmyUnit->sqlGet("select army_id, general_id, soldier_id, soldier_num from city_battle_player_army_unit where battle_id={$battleId} and army_id={$amId}"));
                $_generalInfo = $CityBattlePlayerGeneral->sqlGet("SELECT general_id, star_lv FROM city_battle_player_general WHERE battle_id={$battleId} AND army_id={$amId};");
                $_generalInfo = Set::combine($_generalInfo, '{n}.general_id', '{n}.star_lv');
                if($_info) {
                    foreach($_info as &$v) {
                        $v['general_star'] = intval(@$_generalInfo[$v['general_id']]);
                    }
                    $data['battle_army'][] = $_info;
                    unset($v);
                }
            }

            dbCommit($db);
            $err = 0;
        } catch (Exception $e) {
            list($err, $msg) = parseException($e);
            dbRollback($db);
            //清除缓存
        }
        $this->afterCommit();
        //解锁
        Cache::unlock($lockKey);

        if(!$err){
            echo $this->data->send($data);
        }else{
            echo $this->data->sendErr($err);
        }
    }

    /**
     * 勇士羽林军排名
     */
    public function getCityBattleRankAction(){
        $allTitle = (new CityBattleRank)->getAllTitle();
        $data = keepFields($allTitle, ['server_id', 'guild_name', 'nick', 'score', 'rank','camp_id']);
        echo $this->data->send($data);
        exit;
    }
}
?>
