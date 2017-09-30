<?php
class Drop extends ModelBase{
	public $except = array();
	public $resource = [
		['id'=>10100, 'name'=>'黄金', 'consume'=>true],
		['id'=>10200, 'name'=>'粮食', 'consume'=>true],
		['id'=>10300, 'name'=>'木头', 'consume'=>true],
		['id'=>10400, 'name'=>'石材', 'consume'=>true],
		['id'=>10500, 'name'=>'铁材', 'consume'=>true],
		['id'=>10600, 'name'=>'白银', 'consume'=>true],
		['id'=>10700, 'name'=>'元宝', 'consume'=>true],
		['id'=>10800, 'name'=>'个人荣誉', 'consume'=>true],
		['id'=>10900, 'name'=>'体力', 'consume'=>true],
		['id'=>11000, 'name'=>'主公经验', 'consume'=>false],
		['id'=>11600, 'name'=>'vip点数', 'consume'=>false],
		['id'=>11700, 'name'=>'和氏璧', 'consume'=>true],
		['id'=>11800, 'name'=>'城墙血', 'consume'=>false],
		['id'=>11900, 'name'=>'武将经验', 'consume'=>false],
		['id'=>11300, 'name'=>'锦囊', 'consume'=>true],
		['id'=>12000, 'name'=>'功勋', 'consume'=>true],
		['id'=>12100, 'name'=>'玄铁', 'consume'=>true],
		['id'=>12200, 'name'=>'将印', 'consume'=>true],
		['id'=>12300, 'name'=>'军资', 'consume'=>true],
	];
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				$_r['drop_data'] = explode(';', $_r['drop_data']);
				foreach($_r['drop_data'] as &$__r){
					$__r = explode(',', $__r);
				}
				unset($__r);
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
	public function rand($playerId, $dropIds, &$dropType=0){
		$dropType = 2;
		//获得主公等级
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		if(!$player)
			return false;
		$castleLevel = (new PlayerBuild)->getPlayerCastleLevel($playerId);
		
		//循环
		$dropCfg = [];
		$godGeneralItemIds = false;
		foreach($dropIds as $_id){
			$drop = $this->dicGetOne($_id);
			if(!$drop){
				continue;
			}
			if($drop['drop_type'] == 3){
				$_level = $player['vip_level'];
			}else{
				$_level = $castleLevel;
			}
			if($drop['min_level'] <= $_level && $drop['max_level'] >= $_level){
				$dropCfg[] = $drop;
				$dropType = min($dropType, $drop['drop_type']*1);
				//break;
			}
			if($drop['drop_type'] == 4 && false === $godGeneralItemIds){
				$godGeneralItemIds = [];
				$General = new General;
				$pg = (new PlayerGeneral)->getGeneralIds($playerId);
				$myGodGeneralItemIds = [];
				$myGodGeneralIds = [];
				foreach($pg as $_pg){
					if($General->isGod($_pg)){
						$myGodGeneralIds[] = $_pg;
					}
				}
				if($myGodGeneralIds){
					$myGodGeneralItemIds = array_keys((new General)->findList('piece_item_id', null, ['general_original_id in ('.join(',', $myGodGeneralIds).')']));
				}
				$myGodGeneralItemIds2 = array_keys((new PlayerItem)->findList('item_id', null, ['player_id='.$playerId.' and item_id > 41000 and item_id < 42000']));
				$myGodGeneralItemIds = array_merge($myGodGeneralItemIds, $myGodGeneralItemIds2);
			}
		}
		if(false === $godGeneralItemIds){
			$godGeneralItemIds = [];
		}
		if(!@$dropCfg){
			return [];
		}
		//$dropType = $dropCfg['drop_type'];
		
		
		$ret = array();
		foreach($dropCfg as $_drop){
			//计算掉落概率1
			if(lcg_value1() > $_drop['rate'] / 10000){
				continue;
			}
			//计算掉落概率2
			$keys = array();
			if($_drop['drop_type'] == 1 || $_drop['drop_type'] == 4){
				$rates = array();
				if(!$_drop['drop_data'])
					continue;//防止粗心的策划没配数据！fuck
				foreach($_drop['drop_data'] as $_k => $_d){
					//检查except
					//橙武将信物
					if($_d[0] == 2 && $_d[1]*1 >= 40000 && $_d[1]*1 < 41000 && !isset($this->except[$playerId][$_d[0]])){
						//var_dump($this->except);
						$this->getGeneralPieceExcept($playerId);
					}
					if(isset($this->except[$playerId][$_d[0]])){
						if(in_array($_d[1], $this->except[$playerId][$_d[0]])){
							continue;
						}
					}
					//神武将信物
					if($_d[0] == 2 && Item::isGodFragment($_d[1]) && in_array($_d[1], $myGodGeneralItemIds)){
						continue;
					}
					//设置概率
					$rates[$_k] = $_d[3];//如果报错。打陈涛。drop多了一个分号在结尾
				}
				if(!$rates)
					continue;
				$i = 0;
				//$keyNum = [];
				while($i < $_drop['drop_count']*1){
					if(!$rates)
						return false;
					$_key = random($rates);
					//unset($rates[$_key]);
					@$keys[$_key]++;
					//$keys[] = $_key;
					//@$keyNum[$_key]++;
					if($_drop['drop_data'][$_key][0] == 2 && Item::isGodFragment($_drop['drop_data'][$_key][1])){
						unset($rates[$_key]);
						$myGodGeneralItemIds[] = $_drop['drop_data'][$_key][1]*1;
					}
					$i++;
				}
			}else{
				//$keys = array_keys($_drop['drop_data']);
				//$keyNum = array_fill(0, count($keys), 1);
				$keys = array_combine(array_keys($_drop['drop_data']), array_fill(0, count($_drop['drop_data']), 1));
			}
			foreach($keys as $_k => $_i){
				$_tmp = $_drop['drop_data'][$_k];
				$_tmp[2] *= $_i;
				$ret[] = $_tmp;
			}
			
		}
		if(!$ret)
			return [];
		return $ret;
	}
	
    /**
     * 获得掉落的道具
     * 
     * @param <type> $playerId 
     * @param <array|int> dropId数组 
	 * @param <int> num 数量
     * @param <type> 说明
     * 
     * @return <type>
     */
	public function gain($playerId, $dropIds, $num=1, $memo='', $extra=[]){
		if(!is_array($dropIds))
			$dropIds = array($dropIds);
		$i = 0;
		$dropData = array();
		while($i < $num){
			$_dropData = $this->rand($playerId, $dropIds, $dropType);
			if(!$_dropData)
				return false;
			if(in_array($dropType, [2, 3])){
				$dropData = $_dropData;
				foreach($dropData as &$_d){
					$_d[2] *= $num;
				}
				unset($_d);
				break;
			}else{
				$dropData = array_merge($dropData, $_dropData);
			}
			$i++;
		}

		//整理道具，增加发送速度
		$gainItems = array();
		foreach($dropData as $_dropData){
			list($_type, $_itemId, $_num, $_rate) = $_dropData;
			@$gainItems[$_type][$_itemId] += $_num;
			
			//白银特殊处理
			if($_type == 1 && $_itemId == 10600){
				$extra['silverSplit'] = $num;
			}
		}
		if(!$memo){
			$memo = 'fromDrop:['.join(',', $dropIds).']|num:'.$num;
			$extra['dropId'] = @$dropIds[0];
		}
		return $this->_gain($playerId, $gainItems, $memo, $extra);
	}
	
	public function gainFromDropStr($playerId, $dropStr, $memo=''){
		$dropData = parseGroup($dropStr, false);
		//整理道具，增加发送速度
		$gainItems = array();
		foreach($dropData as $_dropData){
			list($_type, $_itemId, $_num) = $_dropData;
			@$gainItems[$_type][$_itemId] += $_num;
		}
		return $this->_gain($playerId, $gainItems, $memo);
	}
	
	public function _gain($playerId, $gainItems, $memo='', $extra=[]){
		//获取
		$Player = new Player;
		$PlayerItem = new PlayerItem;
		$PlayerGeneral = new PlayerGeneral;
		$PlayerEquipment = new PlayerEquipment;
		$PlayerEquipMaster = new PlayerEquipMaster;
		$PlayerSoldier = new PlayerSoldier;
		$PlayerCommonLog = new PlayerCommonLog;
		$Item = new Item;
		$Equipment =  new Equipment();
		$dropItems = array();

		foreach($gainItems as $_type => $_gainItems){
			foreach($_gainItems as $_itemId => $_num){
				//$_dropData = $dropCfg['drop_data'][$_k];
				//list($_type, $_itemId, $_num, $_rate) = $_dropData;
				switch($_type){
					case 1://资源：黄金、粮草、木材、石材、铁材、白银
						$resource = array();
						$gem = 0;
						if($_itemId == '10200'){//粮食
							$resource['food'] = $_num;
						}elseif($_itemId == '10100'){//黄金
							$resource['gold'] = $_num;
						}elseif($_itemId == '10300'){//木头
							$resource['wood'] = $_num;
						}elseif($_itemId == '10500'){//铁材
							$resource['iron'] = $_num;
						}elseif($_itemId == '10400'){//石材
							$resource['stone'] = $_num;
						}elseif($_itemId == '10600'){//白银
							if(substr($memo, 0, strlen('equipDecomposition:')) == 'equipDecomposition:'){
								$_buff = (new PlayerBuff)->getEquipDecompositionBuff($playerId);
								if(@$extra['silverSplit']){
									$_num = floor(($_num / $extra['silverSplit']) * (1+$_buff)) * $extra['silverSplit'];
								}else{
									$_num *= (1 + $_buff);
								}
								//$resource['silver'] *= 1 + (new PlayerBuff)->getEquipDecompositionBuff($playerId);
								//$resource['silver'] = floor($resource['silver']);
								(new PlayerTarget)->updateTargetCurrentValue($playerId, 18, $_num);
							}
							$resource['silver'] = $_num;
						}elseif($_itemId == '10700'){//元宝
							$gem = $_num;
						}elseif($_itemId == '10800'){//联盟商店货币
							$resource['guild_coin'] = $_num;
						}elseif($_itemId == '10900'){//体力
							if(!$Player->updateMove($playerId, $_num)){
								//return false;
							}
						}elseif($_itemId == '11000'){//主公经验
							if(!$Player->addExp($playerId, $_num)){
								//return false;
							}
						}elseif($_itemId == '11300'){//锦囊
							$resource['point'] = $_num;
                            $pointLogFlag = true;
						}elseif($_itemId == '11400'){//铜币
							if(!(new PlayerLotteryInfo)->updateCoin($playerId, $_num)){
								return false;
							}
						}elseif($_itemId == '11500'){//勾玉
							if(!(new PlayerLotteryInfo)->updateJade($playerId, $_num)){
								return false;
							}					
						}elseif($_itemId == '11600'){//vip点数
							if(!$Player->addVipExp($playerId, $_num)){
								return false;
							}
						/*}elseif($_itemId == '11100'){//行动力上限
							if(!$Player->alter($playerId, ['move'=>'move+'.$_num, 'move_max'=>'move_max+'.$_num])){
								return false;
							}
						}elseif($_itemId == '11200'){//城墙上限
							if(!$Player->alter($playerId, ['wall_durability'=>'wall_durability+'.$_num, 'wall_durability_max'=>'wall_durability_max+'.$_num])){
								return false;
							}*/
						}elseif($_itemId == '11700'){//和氏璧
							$Player->updateHsb($playerId, $_num);
						}elseif($_itemId == '11800'){//城墙血
							$Player->restoreWallDurability($playerId, $_num);
						}elseif($_itemId == '12000'){//功勋
							$resource['feats'] = $_num;
						}elseif($_itemId == '12100'){//玄铁
							$resource['xuantie'] = $_num;
						}elseif($_itemId == '12200'){//将印
							$resource['jiangyin'] = $_num;
						}elseif($_itemId == '12300'){//军资
							if(@$extra['junziBuff']){
								$_num *= (1 + $extra['junziBuff']);
							}
							$resource['junzi'] = $_num;
						}
						
						if($resource){
							if(!$Player->updateResource($playerId, $resource)){
								return false;
							} else {
                                if(isset($pointLogFlag)) {//锦囊
                                    //日志
                                    $PlayerCommonLog->add($playerId, ['type'=>$memo.'[锦囊]', 'memo'=>['total_num'=>$resource['point']]]);
                                }
                            }
							if(isset($resource['feats'])){
								$PlayerCommonLog->add($playerId, ['type'=>$memo.'[功勋]', 'memo'=>['total_num'=>$resource['feats']]]);
							}
							if(isset($resource['xuantie'])){
							    $PlayerCommonLog->add($playerId, ['type'=>$memo.'[玄铁]', 'memo'=>['total_num'=>$resource['xuantie']]]);
							}
							if(isset($resource['jiangyin'])){
							    $PlayerCommonLog->add($playerId, ['type'=>$memo.'[将印]', 'memo'=>['total_num'=>$resource['jiangyin']]]);
							}
							if(isset($resource['junzi'])){
							    $PlayerCommonLog->add($playerId, ['type'=>$memo.'[军资]', 'memo'=>['total_num'=>$resource['junzi']]]);
							}

						}
						if($gem){
							if(!$Player->updateGem($playerId, $gem, true, $memo, @$extra['dropId'])){
								return false;
							}
						}
					break;
					case 2://道具
						if(!$PlayerItem->add($playerId, $_itemId, $_num)){
							return false;
						}
						$_item = $Item->dicGetOne($_itemId);
						$PlayerCommonLog->add($playerId, ['type'=>$memo.'['.$_item['desc1'].']', 'memo'=>['num'=>$_num]]);
					break;
					case 3://武将
						if(!$PlayerGeneral->add($playerId, $_itemId)){
							return false;
						}
					break;
					case 4://装备
						if(!$PlayerEquipment->add($playerId, $_itemId, $_num)){
							return false;
						}
						$_equip = $Equipment->dicGetOne($_itemId);
						$PlayerCommonLog->add($playerId, ['type'=>$memo.'['.$_equip['desc1'].']', 'memo'=>['num'=>$_num]]);
						
					break;
					case 5://静态buff
						/*$buff = (new Buff)->dicGetOne($_itemId);
						if(!$buff)
							return false;
						$buffName = $buff['name'];*/
						$PlayerBuff = new PlayerBuff;
						if(!$PlayerBuff->setPlayerBuff($playerId, $_itemId, $_num)){
							return false;
						}
					break;
					case 6://时效性buff
						/*$buff = (new BuffTemp)->dicGetOne($_itemId);
						if(!$buff)
							return false;*/
						if(isset($extra['second'])){
							$second = $_num + $extra['second'];
						}else{
							$second = $_num;
						}
						$PlayerBuffTemp = new PlayerBuffTemp;
						if(!$PlayerBuffTemp->up($playerId, $_itemId, $second)){
							return false;
						}
					break;
					case 7://主公宝物
						$i = 0;
						while($i < $_num){
							if(!$PlayerEquipMaster->newPlayerEquipMaster($playerId, $_itemId)){
								return false;
							}
							$i++;
						}
					break;
					case 8://士兵
						if(!$PlayerSoldier->updateSoldierNum($playerId, $_itemId, $_num)){
							return false;
						}
					break;
					/*case 5://联盟商店货币
						//查找所在联盟
						$player = $Player->getByPlayerId($playerId);
						if(!$player['guild_id'])
							return false;
						
						//增加联盟商店货币
						$Guild = new Guild;
						if(!$Guild->addCoin($player['guild_id'], $_num)){
							return false;
						}
					break;*/
					default:
						return false;
				}
				$dropItems[] = array('type'=>$_type, 'id'=>$_itemId, 'num'=>$_num);

			}
		}
		return $dropItems;
	}
	
	public function setExcept($playerId, $except = array()){
		@$this->except[$playerId] = $except;
	}
	
	public function getGeneralPieceExcept($playerId){
		$General = new General;
		$pieceExceptIds = [];
		//获取所有武将
		$PlayerGeneral = new PlayerGeneral;
		$generalIds = $PlayerGeneral->getGeneralIds($playerId);
		$rootIds = [];
		foreach($generalIds as $_generalId){
			$rootIds[] = $General->getRootId($_generalId);
		}
		
		//获取武将配置
		$generals = $General->dicGetAll();
		$gs = [];
		foreach($generals as $_g){
			if(!$_g['piece_item_id']) continue;
			$gs[$_g['piece_item_id']] = $_g;
			if(in_array($_g['root_id'], $rootIds)){
				$pieceExceptIds[] = $_g['piece_item_id']*1;
			}
		}
		
		//获取背包信物
		$Item = new Item;
		$pieceIds = $Item->getAllPieceIds();
		
		$PlayerItem = new PlayerItem;
		$playerItem = $PlayerItem->getByPlayerId($playerId);
		foreach($playerItem as $_pi){
			if(!in_array($_pi['item_id'], $pieceIds) || in_array($_pi['item_id'], $pieceExceptIds)) continue;
			if($_pi['num'] >= $gs[$_pi['item_id']]['piece_required']){
				$pieceExceptIds[] = $gs[$_pi['item_id']]['piece_item_id']*1;
			}
		}
		
		//$pieceExceptIds = array_values(array_unique($pieceExceptIds));
		@$this->except[$playerId][2] = $pieceExceptIds;
		return $pieceExceptIds;
	}

	public function getTranslateInfo($dropStr, $toStr=false, $join="\r\n"){
		if(!is_array($dropStr)){
			$drop = parseGroup($dropStr, false);
		}else{
			$drop = $dropStr;
		}
		
		$ret = [];
		$resource = array_combine(Set::extract('/id', $this->resource), Set::extract('/name', $this->resource));
		foreach($drop as $_d){
			switch($_d[0]){
				case 1:
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'基础资源',
						'name'=>$resource[$_d[1]],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
				case 2:
					$item = (new Item)->dicGetOne($_d[1]);
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'道具',
						'name'=>$item['desc1'],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
				case 3:
					$general = (new General)->getByGeneralId($_d[1]);
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'武将',
						'name'=>$general['desc1'],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
				case 4:
					$Equipment = (new Equipment)->dicGetOne($_d[1]);
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'武将装备',
						'name'=>$Equipment['desc1'],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
				case 5:
					$Buff = (new Buff)->dicGetOne($_d[1]);
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'静态buff',
						'name'=>$Buff['desc1'],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
				case 6:
					$BuffTemp = (new BuffTemp)->dicGetOne($_d[1]);
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'静态buff',
						'name'=>$BuffTemp['desc1'],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
				case 7:
					$EquipMaster = (new EquipMaster)->dicGetOne($_d[1]);
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'主公宝物',
						'name'=>$EquipMaster['desc1'],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
				case 8:
					$Soldier = (new Soldier)->dicGetOne($_d[1]);
					$ret[] = [
						'id'=>$_d[1],
						'type'=>'士兵',
						'name'=>$Soldier['desc1'],
						'num'=>$_d[2],
						'type_id'=>$_d[0],
					];
				break;
			}
		}
		
		if($toStr){
			$str = [];
			foreach($ret as $_r){
				$str[] = $_r['name'] . 'x' . $_r['num'];
			}
			$ret = join($join, $str);
		}
		return $ret;
	}
}