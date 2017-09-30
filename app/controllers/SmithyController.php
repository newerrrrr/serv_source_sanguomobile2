<?php
//铁匠铺
use Phalcon\Mvc\View;
class SmithyController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 升阶
     * 
     * $_POST['generalId'] 武将id，0表示背包
	 * $_POST['itemId'] 道具id
     * @return <type>
     */
	public function levelUpAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$itemId = floor(@$post['itemId']);
		$materialItemId = floor(@$post['materialItemId']);
		if(!checkRegularNumber($generalId, true) || !checkRegularNumber($itemId) || !checkRegularNumber($materialItemId, true))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$PlayerEquipment = new PlayerEquipment;
			
			//检查铁匠铺是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 9)){
				throw new Exception(10180);
			}
			
			//获得武将道具or背包道具
			if($generalId){
				$PlayerGeneral = new PlayerGeneral;
				$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
				if(!$playerGeneral){
					throw new Exception(10181);
				}
				if($playerGeneral['weapon_id'] == $itemId){
					$equipType = 'weapon';
				}elseif($playerGeneral['armor_id'] == $itemId){
					$equipType = 'armor';
				}elseif($playerGeneral['horse_id'] == $itemId){
					$equipType = 'horse';
				}elseif($playerGeneral['zuoji_id'] == $itemId){
					$equipType = 'zuoji';
				}else{
					throw new Exception(10182);
				}
			}else{
				$data = $PlayerEquipment->getByPlayerId($playerId);
				foreach($data as $_data){
					if($_data['item_id'] == $itemId){
						$playerEquipment = $_data;
						break;
					}
				}
				if(!@$playerEquipment){
					throw new Exception(10183);
				}
			}
			
			//获取目标装备id
			$Equipment = new Equipment;
			$equipment = $Equipment->dicGetOne($itemId);
			if(!$equipment){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			if(!$equipment['target_equip']){
				throw new Exception(10184);
			}
			$nextEquipment = $equipment['target_equip'];
			$nextEquip = $Equipment->dicGetOne($nextEquipment);
			
			//红武器仅神武将可升级
			if($nextEquip['quality_id'] == 7 && $nextEquip['equip_type'] == 1){
				if(!$generalId)
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				if(!(new General)->isGod($generalId))
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获得item配置
			$needItems = array();
			$needEquips = array();
			$needTheEquips = array();
			$needSilver = 0;
			$levels = array('901'=>"1", '902'=>"2", '903'=>"3", '904'=>"4", '905'=>"5");
			foreach($equipment['consume'] as $_c){
				if($_c[0] == '1'){//需要道具
					$needItems[] = array('id'=>$_c[1], 'num'=>$_c[2]);
				}elseif($_c[0] == '0'){//需要指定品质装备
					$needEquips[] = array('id'=>$levels[$_c[1]], 'num'=>$_c[2]);
				}elseif($_c[0] == '3'){//需要白银
					$needSilver += $_c[1];
				}elseif($_c[0] == '2'){//需要指定装备
					$needTheEquips[] = array('id'=>$_c[1], 'num'=>$_c[2]);
				}
			}
			if($needSilver){
				$needSilver *= max(0, (1 - (new PlayerBuff)->getEquipLvSilverBuff($playerId)));
				$needSilver = floor($needSilver);
			}
			//var_dump((new PlayerBuff)->getEquipLvSilverBuff($playerId));
			
			if($needTheEquips){
				foreach($needTheEquips as $_e){
					//消耗材料装备
					if(!$PlayerEquipment->del($playerId, $_e['id'], $_e['num'])){
						throw new Exception(10185);
					}
				}
			}
			
			if($needEquips){
				if(!$materialItemId){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//判断材料装备是否和升级装备同品质
				$materialEquipment = $Equipment->dicGetOne($materialItemId);
				if(!$materialEquipment){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($materialEquipment['quality_id'] != $needEquips[0]['id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//消耗材料装备
				if(!$PlayerEquipment->del($playerId, $materialItemId)){
					throw new Exception(10186);
				}
			}
			
			//消耗材料
			$PlayerItem = new PlayerItem;
			foreach($needItems as $_i){
				if(!$PlayerItem->drop($playerId, $_i['id'], $_i['num'])){
					throw new Exception(10187);
				}
			}
			
			//消耗白银
			$Player = new Player;
			if(!$Player->hasEnoughResource($playerId, array('silver'=>$needSilver))){
				throw new Exception(10188);
			}
			if(!$Player->updateResource($playerId, array('silver'=>-$needSilver))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新武将道具or背包道具
			if($generalId){
				if(!$PlayerGeneral->assign($playerGeneral)->updateEquip($equipType.'_id', $nextEquipment)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				//再检测一次装备是否存在，防止材料和原物品id相同
				$data = $PlayerEquipment->getByPlayerId($playerId);
				foreach($data as $_data){
					if($_data['item_id'] == $itemId){
						$playerEquipment = $_data;
						break;
					}
				}
				if(!@$playerEquipment){
					throw new Exception(10183);
				}
				if(!$PlayerEquipment->assign($playerEquipment)->updateId($nextEquipment)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			(new PlayerTarget)->updateTargetCurrentValue($playerId, 19, 1);
			(new PlayerTarget)->refreshMaxStarEquipNum($playerId, $nextEquipment);
			
			
			if(($equipment['quality_id'] >= 4 && $equipment['star_level'] >= 1) || $nextEquip['quality_id'] == 7){
				(new RoundMessage)->addNew($playerId, ['type'=>4, 'equipment_id'=>$nextEquipment, 'equipment_star'=>$nextEquip['star_level']]);//走马灯公告
			}
			
			if($nextEquip['quality_id'] == 7){
				(new PlayerCommonLog)->add($playerId, ['type'=>'升级红武器', 'memo'=>['equip'=>$nextEquipment, 'name'=>$nextEquip['desc1'].'lv'.$nextEquip['star_level']]]);//日志
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
     * 重铸
     * 
	 * $_POST['itemId'] 道具id
     * @return <type>
     */
	public function rebuildAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		if(!checkRegularNumber($itemId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);
		
		try {
			$itemOut = array();
			
			//检查铁匠铺是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 9)){
				throw new Exception(10189);
			}
			
			//取出道具
			$PlayerEquipment = new PlayerEquipment;
			$data = $PlayerEquipment->getByPlayerId($playerId);
			foreach($data as $_data){
				if($_data['item_id'] == $itemId){
					$playerEquipment = $_data;
					break;
				}
			}
			if(!@$playerEquipment){
				throw new Exception(10190);
			}
			
			//检查装备是否为0阶
			$Equipment = new Equipment;
			$equipment = $Equipment->dicGetOne($itemId);
			if(!$equipment){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$equipment['star_level']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($equipment['quality_id'] == 6){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//消耗锦囊
			$Player = new Player;
			if(!$Player->updateGem($playerId, -$equipment['recast_cost'], true, ['cost'=>10014, 'memo'=>'装备重铸'])){
				throw new Exception(10191);
			}
			
			//删除装备
			if(!$PlayerEquipment->assign($playerEquipment)->del($playerId, $itemId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获得
			$Drop = new Drop;
			$dropItem = $Drop->gain($playerId, $equipment['recast'], 1, '装备重铸：'.$itemId);
			if(!$dropItem){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//增加材料
			/*$PlayerItem = new PlayerItem;
			if(!$PlayerItem->add($playerId, $_itemId, $_num)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$itemOut[] = array('id'=>$_itemId, 'num'=>$_num);
			
			//修改装备
			if(!$PlayerEquipment->assign($playerEquipment)->updateId($newItemId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$itemOut[] = array('id'=>$newItemId, 'num'=>1);*/
			
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
			echo $this->data->send($dropItem);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 分解
     * 
	 * $_POST['itemId'] 道具id
     * @return <type>
     */
	public function splitAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$itemId = @$post['itemId'];
		if(!is_array($itemId))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$itemOut = array();
			//检查铁匠铺是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 9)){
				throw new Exception(10192);
			}
			
			//取出道具
			$PlayerItem = new PlayerItem;
			$Player = new Player;
			$Drop = new Drop;
			$Equipment = new Equipment;
			
			//整理装备数量
			$sortItem = [];
			foreach($itemId as $_itemId){
				@$sortItem[$_itemId]++;
			}
			
			foreach($sortItem as $_itemId => $_num){
				$_itemId = floor($_itemId);
				if(!checkRegularNumber($_itemId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				$PlayerEquipment = new PlayerEquipment;
				/*$num = $PlayerEquipment->hasItemCount($playerId, $_itemId);
				if($num < $_num){
					throw new Exception(10193);
				}*/
				/*$data = $PlayerEquipment->getByPlayerId($playerId);
				foreach($data as $_data){
					if($_data['item_id'] == $_itemId){
						$playerEquipment = $_data;
						break;
					}
				}
				if(!@$playerEquipment){
					throw new Exception(10193);
				}*/
				
				//增加材料
				/*if(!$PlayerItem->add($playerId, $_itemId, $_num)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$itemOut[] = array('id'=>$_itemId, 'num'=>$_num);
				
				//增加白银
				if(!$Player->updateResource($playerId, array('silver'=>$silver))){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$itemOut[] = array('id'=>'silver', 'num'=>$silver);*/
				
				//删除装备
				if(!$PlayerEquipment->del($playerId, $_itemId, $_num)){
					throw new Exception(10193);
				}
				/*if(!$PlayerEquipment->assign($playerEquipment)->del($playerId, $_itemId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}*/
				
				$equipment = $Equipment->dicGetOne($_itemId);
				if(!$equipment){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if($equipment['quality_id'] == 6){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			
				//获得
				$dropItem = $Drop->gain($playerId, $equipment['decomposition'], $_num, 'equipDecomposition:装备分解：'.$_itemId);
				if(!$dropItem){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$itemOut = array_merge($itemOut, $dropItem);
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
			echo $this->data->send($itemOut);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 材料合成
     * 
	 * $_POST['itemId'] 目标合成道具id
     * @return <type>
     */
	public function materialCombineAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		$num = floor(@$post['num']);
		if(!checkRegularNumber($itemId) || !checkRegularNumber($num))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$itemOut = array();
			//检查铁匠铺是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 9)){
				throw new Exception(10194);
			}
			
			//获取配置
			$Item = new Item;
			$item = $Item->dicGetOne($itemId);
			if(!$item){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($item['item_type'] != 3){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取合成配方
			$ItemCombine = new ItemCombine;
			$itemCombine = $ItemCombine->dicGetOne($itemId);
			if(!$itemCombine){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//获取需要道具
			$PlayerItem = new PlayerItem;
			foreach($itemCombine['consume'] as $_c){
				if($_c[0] == '1'){
					//消耗道具
					if(!$PlayerItem->drop($playerId, $_c[1], $_c[2] * $num)){
						throw new Exception(10195);
					}
				}
			}
			
			//增加材料
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->add($playerId, $itemId, $itemCombine['count'] * $num)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerMission)->updateMissionNumber($playerId, 13, $num);
			
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
}