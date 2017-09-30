<?php
/**
 * 联盟相关逻辑
 */
use Phalcon\Mvc\View;
class GuildMissionController extends ControllerBase{
    
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
	
	
	/*
	 * 展示联盟任务
	 */
	public function showGuildMissionAction(){
	    global $config;
	    $serverId = $config->server_id;
	    
	    $playerId = $this->getCurrentPlayerId();	    
	    $player = (new Player)->getByPlayerId($playerId);
	    
	    
	    $CityBattleGuildMission =  new CityBattleGuildMission();
	    $CityBattlePlayer = new CityBattlePlayer();
	   
	    $guildId = $CityBattlePlayer->joinGuildId($serverId, $player['guild_id']);	    
	    $guilMission = $CityBattleGuildMission->getGuildMission($guildId);
        
	    $result = [];
	    if($guilMission){	        
	        $result['missionId'] = $guilMission['mission_id']*1;
	        $result['count'] = $guilMission['count']*1;
	        $result['missionStatus'] = $guilMission['status']*1;//1未完成可参与；2已完成不可参与；3改阵营不可参与；4还未报名不可参与

	    }
	    else
	    {	       	
	        $result['missionId'] = 1;
	        $result['count'] = 0;
	        $result['missionStatus'] = 4;//1未完成可参与；2已完成不可参与；3改阵营不可参与；4还未报名不可参与
	    }    

	    echo $this->data->send(['guildMission'=>$result]);	    
	}
	
	/*
	 * 获取倒计时时间
	 */
	private function getTime(){
	    //可购买剩余时间
	    $CityBattleRound = new CityBattleRound();
	    $battleStatus = $CityBattleRound->getCurrentRoundStatus();
	     
	    $leftTime = 0;
	    if(!$battleStatus){
	        $leftTime = -1;//从未开过城战
	    }
	    else{
	        $CountryBasicSetting = new CountryBasicSetting();
	        $openDate = $CountryBasicSetting->getValueByKey('open_date');//开始时间是周几
	        $startHour = $CountryBasicSetting->getValueByKey('match_ready');//城战开始时间
	        $endHour = $CountryBasicSetting->getValueByKey('close_time');//城战结束时间
	         
	        $openDateArray = explode(',', $openDate);
	        $todayWeek = date('w');//0是周日
	         
	        $now = time();
	        $diff = 0;//今日距离下次城战的天数
	        if($todayWeek < $openDateArray[0]){
	            if($todayWeek == 0){
	                $diff = $openDateArray[0]-intval($todayWeek);
	            }
	            else{
	                $diff = $openDateArray[0]-intval($todayWeek);
	            }
	            $nextDateStartTime = strtotime(date('Y-m-d', strtotime($diff.' days'))." ".$startHour);
	            $leftTime = $nextDateStartTime- $now;
	        }
	        else if($todayWeek > $openDateArray[0] && $todayWeek < $openDateArray[1]){
	            $diff = $openDateArray[1]-intval($todayWeek);
	            $nextDateStartTime = strtotime(date('Y-m-d', strtotime($diff.' days'))." ".$startHour);
	            $leftTime = $nextDateStartTime- $now;
	        }
	        else if($todayWeek == $openDateArray[0]){
	            $nextDateStartTime = strtotime(date('Y-m-d')." ".$startHour);
	            $nextDateEndTime = strtotime(date('Y-m-d')." ".$endHour);
	             
	            if($now < $nextDateStartTime){
	                $leftTime = $nextDateStartTime-$now;
	            }
	            else if($now >= $nextDateStartTime && $now <= $nextDateEndTime){
	                $leftTime = -1;
	            }
	            else if($now > $nextDateEndTime){
	                $diff = $openDateArray[1]-intval($todayWeek);
	                $nextDateStartTime = strtotime(date('Y-m-d', strtotime($diff.' days'))." ".$startHour);
	                $leftTime = $nextDateStartTime-$now;
	            }
	             
	        }
	        else if($todayWeek==$openDateArray[1]){
	            $nextDateStartTime = strtotime(date('Y-m-d')." ".$startHour);
	            $nextDateEndTime = strtotime(date('Y-m-d')." ".$endHour);
	             
	            if($now < $nextDateStartTime){
	                $leftTime = $nextDateStartTime-$now;
	            }
	            else if($now >= $nextDateStartTime && $now <= $nextDateEndTime){
	                $leftTime = -1;
	            }
	            else if($now > $nextDateEndTime){
	                $diff = intval(abs($openDateArray[0]-intval($todayWeek)))+1; //加1天
	                $nextDateStartTime = strtotime(date('Y-m-d', strtotime($diff.' days'))." ".$startHour);
	                $leftTime = $nextDateStartTime-$now;
	            }
	             
	        }
	         
	    }
	    return $leftTime;
	}
	
    /*
     * 获取城市商店
     */	
	public function getCityShopAction(){
	    $post = getPost();
	    $cityId = intval(@$post['cityId']);
	    if(!$cityId){
	        throw new Exception(10783); //兑换物品不存在 //参数异常
	    }
	    
	    $CityBattleShop = new CityBattleShop();    
	    $Shop = new Shop;
	    $shopAll = $Shop->dicGetAll();
	    $shopInfo = [];
	    foreach($shopAll as $shopOne){
	        if($shopOne['shop_type']!=3){
	            continue;
	        }
	        if(!$shopOne['city_id']){
	            continue;
	        }
	        if(!$shopOne['if_onsale']){
	            continue;
	        }
	        if($shopOne['city_id'] == $cityId){
	            $numInfo = $CityBattleShop->getShopNum($shopOne['id']);
	            $shopInfo[$shopOne['id']] = $numInfo['total'];
	        }
	        
	    }
	    $data = [];
	    $data['shop_info'] = $shopInfo;	    
	    $data['left_time'] = $this->getTime();
	    echo $this->data->send($data);
	}
	
	
	/**
	 * 城战商店购买
	 * 
	 *
	 * @return <type>
	 */
	public function cityShopBuyAction(){
	    $playerId = $this->getCurrentPlayerId();	
	    $player = $this->getCurrentPlayer();
	    $post = getPost();
	    $shopId = isset($post['shopId'])? intval($post['shopId']): 0;
	    $itemNum = isset($post['itemNum'])? intval($post['itemNum']): 0;
	    $use = isset($post['use'])? $post['use'] : false;	    

	    
	    if(!checkRegularNumber($shopId) || !checkRegularNumber($itemNum))
	        exit;
	
	        $CityBattleShop = new CityBattleShop();
	        
	        //锁定
	        $lockKey = __CLASS__ . ':' . __METHOD__ . ':' .$shopId;//避免数量爆棚
	        Cache::lock($lockKey);
	        $db = $this->di['db'];
	        dbBegin($db);
	
	        try {
	            $currentTime = $this->getTime();
	            if($currentTime == -1){
	                throw new Exception(10784);//城战期间不可以兑换碎片 //商店暂未开放购买
	            }
	            
	            //检查商品数量是否够	            
	            
	            $numInfo = $CityBattleShop->getShopNum($shopId);
	            if($numInfo['total'] < $itemNum){ 
	                throw new Exception(10785);//商品库存不足 //商品库存不足
	            }
	            //获取城池等级	            
	            $PlayerBuild = new PlayerBuild;
	            $playerBuild = $PlayerBuild->getByOrgId($playerId, 1);
	            $cityLv = $playerBuild[0]['build_level'];
	            	
	            	
	            //检查道具是否属于商店列表
	            
	            $Shop = new Shop;
	            $shop = $Shop->dicGetOne($shopId);
	            if(!$shop){
	                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
	            }
	            	
	            if($shop['shop_type'] != 3){
	                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
	            }
	            
	            //判断是否符合城池
	            $City = new City();
	            $cityCamp = $City->dicGetOne($shop['city_id']);
	            if($cityCamp['camp_id'] != $player['camp_id']){
	                throw new Exception(10786);//阵营不符合 //您的阵营无法购买该城市商店的物品
	            }
	            
	            	
	            //判断允许等级
	            if($cityLv < $shop['min_level'] || $cityLv > $shop['max_level']){
	                throw new Exception(10371);//无法购买该道具
	            }
	            
	            //新增更新全服道具剩余次数-start
	            if($shop['buy_daily_limit'] != -1){

	                $ret = $CityBattleShop->updateShopNum($shopId, $itemNum);
	                if(!$ret){
	                   throw new Exception(10787);//商品购买失败 //商品购买失败
	                }
	            }
	            //新增更新全服道具剩余次数-end
	            
	            
	            //获取当日购买记录	            
	            $PlayerShop = new PlayerShop;
	            $playerShop = $PlayerShop->getByShopId($playerId, $shopId);
	            if($playerShop){
	                //检查购买上限
	                if($shop['buy_daily_limit'] != -1 && $playerShop['num'] + $itemNum > $shop['buy_daily_limit']){
	                    throw new Exception(10342);//购买数量超过上限
	                }
	            }
	            	                        
	            	
	            //获取所有costId
	            $Cost = new Cost;
	            $costs = $Cost->getByCostId($shop['cost_id']);
	            $CityBattleCommonLog = new CityBattleCommonLog();
	            	
	            //var_dump($costs);
	            $beginCt = $playerShop['num'] + 1;
	            $endCt = $playerShop['num'] + $itemNum;
	            $costList = [];
	            $gem = 0;
	            foreach($costs as $_cost){
	                if($beginCt <= $_cost['min_count'] && $endCt >= $_cost['max_count']){
	                    $_num = $_cost['max_count'] - $_cost['min_count'] + 1;
	                }elseif($beginCt >= $_cost['min_count'] && $endCt <= $_cost['max_count']){
	                    $_num = $endCt - $beginCt + 1;
	                }elseif($beginCt <= $_cost['min_count'] && $endCt <= $_cost['max_count'] && $endCt >= $_cost['min_count']){
	                    $_num = $endCt - $_cost['min_count'] + 1;
	                }elseif($beginCt >= $_cost['min_count'] && $beginCt <= $_cost['max_count'] && $endCt >= $_cost['max_count']){
	                    $_num = $_cost['max_count'] - $beginCt + 1;
	                }else{
	                    continue;
	                }
	                $costList[$_cost['min_count']] = $_num;
	
	                //消耗货币
	                if(!$Cost->updatePlayer($playerId, $shop['cost_id'], $_cost['min_count'], $_num)){
	                    if($_cost['cost_type'] == 7){
	                        throw new Exception(10152);
	                    }
	                    else{
	                        throw new Exception(10118);
	                    }
	                    
	                }
	                //记入城战商店日至
	                $logData = [];
	                $logData['shop_id'] = $shopId;
	                $logData['cost_type'] = $_cost['cost_type'];
	                $logData['num'] = $itemNum;
	                $logData['consume'] = $_cost['cost_num']*$itemNum;
	                
	                $CityBattleCommonLog->add(0, $playerId, $player['camp_id'], $logData);
	                if($_cost['cost_type'] == 7){
	                    $gem += $_cost['cost_num'] * $_num;
	                }
	            }
	            
	            
	            
	            //增加个人道具
	            $Drop = new Drop;
	            $dropItems = $Drop->gain($playerId, [$shop['commodity_data']], $itemNum, '商店购买'.$shopId);
	            if(!$dropItems){
	                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
	            }
	            	
	            //更新购买记录
	            if(!$PlayerShop->up($playerId, $shopId, $itemNum)){
	                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
	            }
	            	
	            if($gem){
	                (new PlayerMission)->updateMissionNumber($playerId, 17, $gem);
	            }
	            	
	            if($use){
	                $Item = new Item;
	                foreach($dropItems as $_di){
	                    if($_di['type'] == 2){
	                        $_item = $Item->dicGetOne($_di['id']);
	                        if($_item['button_type']){
	                            (new ItemController)->useItem($player, $_di['id'], $_di['num']);
	                        }
	                    }
	                }
	            }
	
	            dbCommit($db);
	            $err = 0;
	        } catch (Exception $e) {
	            list($err, $msg) = parseException($e);
	            dbRollback($db);
	
	            //清除缓存
	        }
	        
	        $shopInfo = $CityBattleShop->getShopNum($shopId);
	        
	        $this->afterCommit();
	        //解锁
	        Cache::unlock($lockKey);
	        $data = [];
	        $data['shop_info'] = $shopInfo;
	        $data['left_time'] = $this->getTime();
	        if(!$err){
	            echo $this->data->send($data);
	        }else{
	            echo $this->data->sendErr($err);
	        }
	}

	
}