<?php
/**
 * 消耗
 *
 */
class Consume{
	public function del($playerId, $str, $memo=''){
		$Player = new Player;
		if(!is_array($str)){
			$group = parseGroup($str, false);
		}else{
			$group = $str;
		}
		foreach($group as $_g){
			$_type = $_g[0];
			$_itemId = $_g[1];
			$_num = $_g[2];
			$_costId = @$_g[3];
			switch($_type){
				case 1://资源
					$resource = [];
					$gem = 0;
					if($_itemId == '10200'){//粮食
							$resource['food'] = -$_num;
						}elseif($_itemId == '10100'){//黄金
							$resource['gold'] = -$_num;
						}elseif($_itemId == '10300'){//木头
							$resource['wood'] = -$_num;
						}elseif($_itemId == '10500'){//铁材
							$resource['iron'] = -$_num;
						}elseif($_itemId == '10400'){//石材
							$resource['stone'] = -$_num;
						}elseif($_itemId == '10600'){//白银
							$resource['silver'] = -$_num;
						}elseif($_itemId == '10700'){//元宝
							$gem = -$_num;
						}elseif($_itemId == '10800'){//联盟商店货币
							$resource['guild_coin'] = -$_num;
						}elseif($_itemId == '10900'){//体力
							if(!$Player->updateMove($playerId, -$_num)){
								return false;
							}
						//}elseif($_itemId == '11000'){//主公经验
						//	if(!$Player->addExp($playerId, $_num)){
								//return false;
						//	}
						}elseif($_itemId == '11300'){//锦囊
							$resource['point'] = -$_num;
                            $pointLogFlag = true;
						}elseif($_itemId == '11400'){//铜币
							if(!(new PlayerLotteryInfo)->updateCoin($playerId, -$_num)){
								return false;
							}
						}elseif($_itemId == '11500'){//勾玉
							if(!(new PlayerLotteryInfo)->updateJade($playerId, -$_num)){
								return false;
							}					
						//}elseif($_itemId == '11600'){//vip点数
						//	if(!$Player->addVipExp($playerId, $_num)){
						//		return false;
						//	}
						}elseif($_itemId == '11700'){//和氏璧
							if(!$Player->updateHsb($playerId, -$_num)){
								return false;
							}
						}elseif($_itemId == '12000'){//功勋
							$resource['feats'] = -$_num;
						}elseif($_itemId == '12100'){//玄铁
							$resource['xuantie'] = -$_num;
						}elseif($_itemId == '12200'){//将印
							$resource['jiangyin'] = -$_num;
						}elseif($_itemId == '12300'){//军资
							$resource['junzi'] = -$_num;
						}
						
						if($resource){
							if(!$Player->updateResource($playerId, $resource)){
								return false;
							} else {
                            }
						}
						if($gem){
							if(!$memo){
								$memo = 'from consume:'.$str;
							}
							if($memo =='兑换活动'){
    							if(!$Player->updateGem($playerId, $gem, true, ['cost'=>10031,'memo'=>$memo])){
    								return false;
    							}
							}elseif($_costId){
								if(!$Player->updateGem($playerId, $gem, true, ['cost'=>$_costId,'memo'=>$memo])){
    								return false;
    							}
							}else{
							    if(!$Player->updateGem($playerId, $gem, true, $memo)){
							        return false;
							    }
							}
							
						}
				break;
				case 2://道具
					if(!(new PlayerItem)->drop($playerId, $_itemId, $_num)){
						return false;
					}
				break;
				case 4://装备
					if(!(new PlayerEquipment)->del($playerId, $_itemId, $_num)){
						return false;
					}
				break;
				case 8://士兵
					if(!(new PlayerSoldier)->updateSoldierNum($playerId, $_itemId, -$_num)){
						return false;
					}
				break;
				default:
					return false;
			}
		}
		return true;
	}
	/*
	 * 返回物品的个数
	 * 用于活动展示
	 */
	public function check($playerId, $str){
	    $Player = new Player;
	    if(!is_array($str)){
	        $group = parseGroup($str, false);
	    }else{
	        $group = $str;
	    }
	    $resource = [];
	    foreach($group as $k=>$_g){
	        $_type = $_g[0];
	        $_itemId = $_g[1];
	        $_num = $_g[2];
	        $everySource = [];
	        switch($_type){
	            case 1://资源
	                $playerInfo = $Player->getByPlayerId($playerId);
	                
	                //$gem = 0;
	                if($_itemId == '10200'){//粮食
	                    $everySource[$_itemId] = $playerInfo['food'];	                    
	                }elseif($_itemId == '10100'){//黄金
	                    $everySource[$_itemId] = $playerInfo['gold'];
	                }elseif($_itemId == '10300'){//木头
	                    $everySource[$_itemId] = $playerInfo['wood'];
	                }elseif($_itemId == '10500'){//铁材
	                    $everySource[$_itemId] = $playerInfo['iron'];
	                }elseif($_itemId == '10400'){//石材
	                    $everySource[$_itemId] = $playerInfo['stone'];
	                }elseif($_itemId == '10600'){//白银
	                    $everySource[$_itemId] = $playerInfo['silver'];
	                }elseif($_itemId == '10700'){//元宝
	                    $everySource[$_itemId] = $playerInfo['rmb_gem']+$playerInfo['gift_gem'];
	                }elseif($_itemId == '10800'){//联盟商店货币
	                    $everySource[$_itemId] = $playerInfo['guild_coin'];
	                }elseif($_itemId == '10900'){//体力
	                    $everySource[$_itemId] = $playerInfo['move'];

	                    //}elseif($_itemId == '11000'){//主公经验
	                    //	if(!$Player->addExp($playerId, $_num)){
	                    //return false;
	                    //	}
	                }elseif($_itemId == '11300'){//锦囊
	                    $everySource[$_itemId] = $playerInfo['point'];
	                }elseif($_itemId == '11400'){//铜币
	                    $lotteryInfo = (new PlayerLotteryInfo)->getByPlayerId($playerId);
	                    $everySource[$_itemId] = $lotteryInfo['coin_num'];
	                }elseif($_itemId == '11500'){//勾玉
	                    $lotteryInfo = (new PlayerLotteryInfo)->getByPlayerId($playerId);
	                    $everySource[$_itemId] = $lotteryInfo['jade_num'];
	                    
	                    //}elseif($_itemId == '11600'){//vip点数
	                    //	if(!$Player->addVipExp($playerId, $_num)){
	                    //		return false;
	                    //	}
	                }elseif($_itemId == '11700'){//和氏璧
	                    $everySource[$_itemId] = $playerInfo['hsb'];
	
	                }elseif($_itemId == '12000'){//功勋
	                    $everySource[$_itemId] = $playerInfo['feats'];
	                }

	                break;
	            case 2://道具
	                $num = (new PlayerItem)->hasItemCount($playerId, $_itemId);
	                $everySource[$_itemId] = $num;
	                break;
	            case 4://装备
	                $num = (new PlayerEquipment)->hasItemCount($playerId, $_itemId);
	                $everySource[$_itemId] = $num;
	                break;
	            case 8://士兵
	                $num = (new PlayerSoldier)->getBySoldierId($playerId, $_itemId);
	                $everySource[$_itemId] = $num;
	                break;
	            default:
	                return false;
	        }
	        $resource[$k] = $everySource;
	    }
	    return $resource;
	}
	
	
}