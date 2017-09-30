<?php
//use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
//use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
//use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
use Phalcon\Mvc\View;
class DataController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
    /**
     * 打印数据包
     * 
     * 
     * @return void
     */
	public function indexAction(){
		$playerId = $this->getCurrentPlayerId();
		$d = getPost();
		$ret = $this->get($playerId, @$d['name']);
		//echo retMsg(0, $ret);
		echo $this->data->send($ret);
	}
	
    /**
     * 获取数据包
     * 
     * 
     * @return void
     */
	public static function get($playerId, $name=array()){
		global $di, $config;
		$data = $di->get('data');
		if(!$data->playerId) {
			$di->get('data')->setPlayerId($playerId);
		}
		/*if(!$playerId)
			exit;*/
		$ret = array();
		if(is_array($name)){
			foreach($name as $_name){
				//$ret[$_name] = $this->getCache($_name, $playerId, @$d['param'][$_name]);
				if(class_exists($_name)){
					$_oname = new $_name;
					if(method_exists(__CLASS__,'instead'.$_name)){
						$_ret = self::{'instead'.$_name}($playerId);
					}else{
						if(substr($_name, 0, 5) == 'Cross'){
							$player = (new self)->getCurrentPlayer();
							$guildId = CrossPlayer::joinGuildId($config->server_id, $player['guild_id']);
							$battleId = (new CrossBattle)->getBattleIdByGuildId($guildId);
							if(!$battleId){
								$battleId = (new CrossBattle)->getLastBattleIdByGuildId($guildId);
							}
							$_oname->battleId = $battleId*1;
						}elseif(substr($_name, 0, 10) == 'CityBattle'){
							$battleId = (new CityBattlePlayer)->getCurrentBattleId($playerId);
							if(!$battleId)
								$battleId = 0;
							$_oname->battleId = $battleId*1;
						}
						$_ret = $_oname->getByPlayerId($playerId, true);
					}
					if($_ret && method_exists(__CLASS__,'deal'.$_name)){
						$ret[$_name] = self::{'deal'.$_name}($_ret, $playerId);
					}else{
						$ret[$_name] = $_ret;
					}
				}
			}
		}
		return $ret;
	}
	
    /**
     * 读写缓存
     * 
     * @param <string> $cacheName 缓存包名
     * @param <int> $playerId 玩家id
     * @param <array> $param 额外参数
     * 
     * @return <array>
     */
	public function getCache($cacheName, $playerId, $param=array()){
		$dataKey = 'data'.$cacheName;
		$cacheKey = getDataCacheKey($playerId, $cacheName);
		//$ret = Cache::db()->get($cacheKey);
		$ret = Cache::getPlayer($playerId, $dataKey);
		if($ret){
			if(@$ret['param'] != md5(serialize($param)) || @$ret['expire'] > time()){
				$data = false;
			}else{
				$data = $ret['data'];
			}
		}
		if(!@$data){
			$data = $this->{$dataKey}($playerId, $param);
			if(false !== $data){
				//获取自定义数据生命周期
				$constName = 'self::'.strtoupper('TIMEOUT'.$cacheName);
				if(defined($constName)){
					$timeout = constant($constName);
				}else{
					$timeout = CACHE_PLAYERDATA_TIMEOUT;
				}
				$ret = array(
					'param'=>md5(serialize(@$ret['param'])),
					'expire'=>time()+$timeout,
					'data' => $data,
				);
				//写入缓存
				Cache::setPlayer($playerId, $dataKey, $ret);
				//Cache::db()->set($cacheKey, $ret, $timeout);
			}
		}
		return $data;
	}
	
    /**
     * 清除玩家缓存包
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public function clearCacheByPlayerAction($playerId){
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player)
			exit;
		$uuid = $player['uuid'];
		$cacheKeyPlayer = "player:uuid={$uuid}";
		Cache::db()->delete($cacheKeyPlayer);
		$cacheKeyPlayerId = "playerId:uuid={$uuid}";
		Cache::db()->delete($cacheKeyPlayerId);
		Cache::delPlayerAll($playerId);
		/*$func = get_class_methods($this);
		foreach($func as $_f){
			if(substr($_f, 0, 4) == 'data'){
				$cacheKey = getDataCacheKey($playerId, substr($_f, 4));
				Cache::db()->delete($cacheKey);
			}
		}*/
	}
	
	//-----------------------------------以下是处理数据包----------------------------------------
	public static function dealPlayerPub($data, $playerId){
		if($data['last_pay_reload_date'] != date('Y-m-d')){
			$PlayerPub = new PlayerPub;
			$PlayerPub->resetPayReload($data['player_id']);
			$data = $PlayerPub->getByPlayerId($data['player_id'], true);
		}
		$data['generals'] = array_map('floor', explode(',', $data['generals']));
		return $data;
	}
	
	public static function dealPlayerArmy($data, $playerId){
		$ret = array();
		$PlayerArmyUnit = new PlayerArmyUnit;
		foreach($data as $_k => $_data){
			$_data['weight'] = $PlayerArmyUnit->calculateWeight($playerId, $_data['id']);
			$ret[$_data['id']] = $_data;
		}
		return $ret;
	}
	
	public static function dealPlayerArmyUnit($data, $playerId){
		$PlayerGeneral = new PlayerGeneral;
		$Soldier = new Soldier;
		$General = new General;
		foreach($data as $_k => &$_data){
			//获取power
			$_power = 0;
			if($_data['soldier_id'] && $_data['soldier_num']){
				$general = $PlayerGeneral->getTotalAttr($playerId, $_data['general_id']);
				/*if($general){
					$_power += $_ret['attr']['power'];
				}*/
				$_soldier = $Soldier->dicGetOne($_data['soldier_id']);
				if($_soldier){
					$_power += ($_soldier['power'] * $_data['soldier_num']) / DIC_DATA_DIVISOR;
				}
				
				$_power *= $general['soldierPower'][$_soldier['soldier_type']]['powerK'];
			}
			
			

			$_data['power'] = floor($_power);
		}
		unset($_data);
		return $data;
	}
	
	public static function dealPlayerItem($data, $playerId){
		$ret = array();
		foreach($data as $_k => $_data){
			$ret[$_data['item_id']] = $_data;
		}
		return $ret;
	}

	public static function dealPlayerMill($data, $playerId){
		$_beginTime = $beginTime = $data['begin_time'];
		$now = time();
		foreach($data['item_ids'] as &$_it){
			if($beginTime + $_it[1] <= $now){//生产完成
				$_status = 1;
				$_beginTime += $_it[1];
			}elseif($beginTime <= $now && ($beginTime + $_it[1]) >= $now){//正在生产
				$_status = 2;
			}else{//将要生产
				$_status = 0;
			}
			$beginTime += $_it[1];
			$_it = [
				'item_id'=>$_it[0]*1,
				'second'=>$_it[1]*1,
				'status'=>$_status,
			];
		}
		unset($_it);
		$data['begin_time'] = $_beginTime;
		return $data;
	}
	
	public static function dealPlayerGrowth($data, $playerId){
		//查找总购买人数
		$data['total_num'] = (new PlayerGrowth)->getTotalNum();
		return $data;
	}
	
	public static function dealGuild($data, $playerId){
		//查找总购买人数
		$data['donate_date'] = strtotime($data['donate_date']);
		return $data;
	}
	
	public static function dealPlayerGuildDonate($data, $playerId){
		//查找总购买人数
		$data['reward_time'] = strtotime($data['reward_time']);
		return $data;
	}
	
	public static function dealPlayerNewbieActivityCharge($data, $playerId){
		//查找总购买人数
		foreach($data as &$_d){
			$_d['flag'] = parseArray($_d['flag']);
		}
		unset($_d);
		return $data;
	}
	
	public static function dealPlayerNewbieActivityConsume($data, $playerId){
		//查找总购买人数
		foreach($data as &$_d){
			$_d['flag'] = parseGroup($_d['flag'], true, true);
			foreach($_d['flag'] as &$_f){
				$_f = $_f[0];
			}
			unset($_f);
		}
		unset($_d);
		return $data;
	}
	
	public static function insteadPlayerBuff($playerId){
		$PlayerController = new PlayerController;
		return $PlayerController->getBuff($playerId);
	}
	
	public static function insteadPlayerBuffTemp($playerId){
		return self::insteadPlayerBuff($playerId);
	}
	
	public static function insteadPlayerGeneralBuff($playerId){
		return self::insteadPlayerBuff($playerId);
	}
	
	public static function insteadGuildBuff($playerId){
		return self::insteadPlayerBuff($playerId);
	}
	public static function insteadPkPlayerInfo($playerId){
        global $config;
        $serverId = $config->server_id;
        return (new PkPlayerInfo)->getBasicInfo($serverId, $playerId, true);
    }
	
	public static function insteadPlayerEquipment($playerId){
		return (new PlayerEquipment)->getCount($playerId);
	}
	/*public static function insteadPlayerDrawCard($playerId){
		return false;
	}*/

	public static function dealCrossPlayerArmy($data, $playerId){
		$ret = array();
		foreach($data as $_k => $_data){
			$ret[$_data['id']] = $_data;
		}
		return $ret;
	}
	
	public static function dealCrossPlayerArmyUnit($data, $playerId){
		$PlayerGeneral = new CrossPlayerGeneral;
		$Soldier = new Soldier;
		$General = new General;
		foreach($data as $_k => &$_data){
			//获取power
			$_power = 0;
			if($_data['soldier_id'] && $_data['soldier_num']){
				$PlayerGeneral->battleId = $_data['battle_id'];
				$general = $PlayerGeneral->getTotalAttr($playerId, $_data['general_id']);
				/*if($general){
					$_power += $_ret['attr']['power'];
				}*/
				$_soldier = $Soldier->dicGetOne($_data['soldier_id']);
				if($_soldier){
					$_power += ($_soldier['power'] * $_data['soldier_num']) / DIC_DATA_DIVISOR;
				}
				
				$_power *= $general['soldierPower'][$_soldier['soldier_type']]['powerK'];
			}
			
			

			$_data['power'] = floor($_power);
		}
		unset($_data);
		return $data;
	}
	
	public static function dealCityBattlePlayerArmy($data, $playerId){
		$ret = array();
		foreach($data as $_k => $_data){
			$ret[$_data['id']] = $_data;
		}
		return $ret;
	}
	
	public static function dealCityBattlePlayerArmyUnit($data, $playerId){
		$PlayerGeneral = new CityBattlePlayerGeneral;
		$Soldier = new Soldier;
		$General = new General;
		foreach($data as $_k => &$_data){
			//获取power
			$_power = 0;
			if($_data['soldier_id'] && $_data['soldier_num']){
				$PlayerGeneral->battleId = $_data['battle_id'];
				$general = $PlayerGeneral->getTotalAttr($playerId, $_data['general_id']);
				/*if($general){
					$_power += $_ret['attr']['power'];
				}*/
				$_soldier = $Soldier->dicGetOne($_data['soldier_id']);
				if($_soldier){
					$_power += ($_soldier['power'] * $_data['soldier_num']) / DIC_DATA_DIVISOR;
				}
				
				$_power *= $general['soldierPower'][$_soldier['soldier_type']]['powerK'];
			}
			
			

			$_data['power'] = floor($_power);
		}
		unset($_data);
		return $data;
	}
	
}

