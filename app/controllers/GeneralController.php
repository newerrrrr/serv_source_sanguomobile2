<?php
//use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
//use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
//use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
use Phalcon\Mvc\View;
class GeneralController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 武将升级
     * 
     * $_POST['generalId'] 武将id
     * @return <type>
	 * @todo 驻守武将更新resource_in_extra
     */
	public function levelUpAction(){
		return;
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取武将
			$PlayerGeneral = new PlayerGeneral;
			$general = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$general){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取玩家等级
			$player = $this->getCurrentPlayer();
			$playerLevel = $player['level'];
			
			//判断可升级最大等级
			$GeneralExp = new GeneralExp;
			$lv = $GeneralExp->exp2lv($general['exp']);
			$lv = max(1, min($playerLevel, $lv));
			if($lv == $general['lv']){
				throw new Exception(10056);
			}
			if(!$PlayerGeneral->assign($general)->lvup($lv)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新驻守建筑数据
			$PlayerGeneral->refreshBuild($playerId, $generalId);
			
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
			echo $this->data->send(array('lv'=>$lv));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 装备
     * 
     * $_POST['generalId'] 武将id
	 * $_POST['equipId'] 装备id，0 - 卸下
     * @return <type>
     */
	public function equipAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		$type = floor(@$post['type']);
		$itemId = floor(@$post['itemId']);
		if(!checkRegularNumber($generalId) || !checkRegularNumber($itemId, true))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//判断type
			//$type = $equipment['equip_type'];
			if(!in_array($type, array(2, 3, 4))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($type == 4 && !(new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//获取道具配置
			if($itemId){
				$Equipment = new Equipment;
				$equipment = $Equipment->dicGetOne($itemId);
				if(!$equipment){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				
				//判断type
				//$type = $equipment['equip_type'];
				if($type != $equipment['equip_type']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
				
			//获取玩家武将
			$PlayerGeneral = new PlayerGeneral;
			$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$playerGeneral){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//获取武将
			$General = new General;
			$general = $General->getByGeneralId($playerGeneral['general_id'], $playerGeneral['lv']);
			
			//检查槽位开启
			if(!$general['prop'.($type-1)]){
				throw new Exception(10057);
			}
			
			//检查是否已经装备or卸下
			$typeName = array(2=>'armor_id', 3=>'horse_id', 4=>'zuoji_id');
			if($playerGeneral[$typeName[$type]] == $itemId){
				//throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}else{
				$PlayerEquipment = new PlayerEquipment;
				if($playerGeneral[$typeName[$type]]){//脱下原来装备
					if(!$PlayerEquipment->add($playerId, $playerGeneral[$typeName[$type]])){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			
				if($itemId){
					//检查是否存在空闲的该装备
					//更新背包
					if(!$PlayerEquipment->del($playerId, $itemId, 1)){
						throw new Exception(10058);
					}
					
					//检查装备要求的武将等级
					if($playerGeneral['lv'] < $equipment['min_general_level']){
						throw new Exception(10059);
					}
				}
				
				//更新武将
				if(!$PlayerGeneral->assign($playerGeneral)->updateEquip($typeName[$type], $itemId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//更新驻守建筑数据
			$PlayerGeneral->refreshBuild($playerId, $generalId);
			
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

    /**
     * 解雇武将
     * 
     * 
     * @return <type>
     */
	public function fireAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$generalId = floor(@$post['generalId']);
		if(!checkRegularNumber($generalId)){
			exit;
		}
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//获取武将
			$PlayerGeneral = new PlayerGeneral;
			$playerGeneral = $PlayerGeneral->getByGeneralId($playerId, $generalId);
			if(!$playerGeneral){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//如果是神武将，无法被解雇
			if((new General)->isGod($generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			
			$General = new General;
			$general = $General->getByGeneralId($generalId);
			if(!$general)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//获取建筑
			$PlayerBuild = new PlayerBuild;
			$playerBuild = $PlayerBuild->getByPlayerId($playerId);
			
			//检查武将是否处于驻守
			foreach($playerBuild as $_pb){
				if($_pb['general_id_1'] == $generalId){
					throw new Exception(10308);//武将正在驻守中，不可解雇
					break;
				}
			}
			
			//获取军团
			$PlayerArmyUnit = new PlayerArmyUnit;
			$pau = $PlayerArmyUnit->getByPlayerId($playerId);
			
			//检查武将是否在军团中
			foreach($pau as $_pau){
				if($_pau['general_id'] == $generalId){
					throw new Exception(10309);//武将正在军团中，不可解雇
					break;
				}
			}
			
			//退还武将信物
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->add($playerId, $general['piece_item_id'], $general['piece_required'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//卸下防具饰品
			$PlayerEquipment = new PlayerEquipment;
			foreach([$playerGeneral['armor_id'], $playerGeneral['horse_id']] as $_equipId){
				if($_equipId){
					if(!$PlayerEquipment->add($playerId, $_equipId)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
				
			//退还武器材料
			$Equipment = new Equipment;
			$equipment = $Equipment->dicGetOne($playerGeneral['weapon_id']);
			$Drop = new Drop;
			if(!$Drop->gain($playerId, $equipment['recast'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//删除武将
			if(!$PlayerGeneral->del($playerId, $generalId)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//日志
			$PlayerCommonLog = new PlayerCommonLog;
			$PlayerCommonLog->add($playerId, ['type'=>'解雇武将', 'generalId'=>$generalId*1]);
			
			
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