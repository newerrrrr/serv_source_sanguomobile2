<?php
//玩家支付订单
class PlayerOrder extends ModelBase{
	public $onceOnly = [1004, 1020];
	public $giftsp = [
		100091 => 'gift_lv12_begin_time',
		100096 => 'gift_lv22_begin_time',
		100101 => 'gift_lv37_begin_time',
		100106 => 'gift_lose_power_begin_time',
	];
	public $spdayType = [1006, 1020];
	public $giftspTime = 86400;
	
	public function add($playerId, $orderId, $commodityId, $aci, $channel, $mode, $price, $gem, $drop, $series, $seriesOrder, $status, $tunnel='game'){
		$o = new self;
		$ret = $o->create(array(
			'player_id' => $playerId,
			'order_id' => $orderId,
			'payment_code' => $commodityId,
			'activity_commodity_id' => $aci,
			'channel' => $channel,
			'mode'	=> $mode,
			'price' => $price,
			'gem' => $gem,
			'drop' => $drop,
			'series' => $series,
			'series_order' => $seriesOrder,
			'status' => $status,
			'tunnel' => $tunnel,
			'create_time' => date('Y-m-d H:i:s'),
		));
		if(!$ret)
			return false;
		return $o->affectedRows();
	}
	
	public function setFinish($orderId, $gem=0, $drop='', $outTradeNo=''){
		return $this->updateAll(['status'=>1, 'gem'=>"'".$gem."'", 'drop'=>"'".$drop."'", 'out_trade_no'=>"'".$outTradeNo."'"], ['order_id'=>"'".$orderId."'"]);
	}
	
	public function chkAvailable($playerId, $aci, $chkTime = true){
		$now = time();
		//获取配置
		$ActivityCommodity = new ActivityCommodity;
		$ActivityCommodityExtra = new ActivityCommodityExtra;
		$ac = $ActivityCommodity->dicGetOne($aci);

		if(!$ac)
			return false;
		if(!in_array($ac['series'], array_keys($this->giftsp))){
			if(in_array($ac['activity_id'], $this->spdayType)){
				$ace = ['open_time'=>$ac['open_time'], 'close_time'=>$ac['close_time']];
			}elseif($ac['activity_id'] == 1024){
				$sub = 0;
				if(date('w', $now) == 0){
					$sub = 1;
				}
				$ace = [];
				$ace['open_time'] = mktime(0, 0, 0, date('m', $now), date('d', $now)-$sub, date('Y', $now));
				@$ace['close_time'] += $ace['open_time'] + 3600*24*2;
			}else{
				$ace = $ActivityCommodityExtra->dicGetOne($aci);
			}
			if(!$ace)
				return false;
		}else{//伤亡礼包和等级礼包另外取时间
			$playerInfo = (new PlayerInfo)->getByPlayerId($playerId);
			$_time = $playerInfo[$this->giftsp[$ac['series']]];
			$ace = ['open_time'=>$_time, 'close_time'=>$_time+$this->giftspTime];
			
		}

		//检查时间
		if($chkTime){
			if($now < $ace['open_time'] || $now > $ace['close_time']){
				return false;
			}
			
		}
		
		//获取订单历史
		$order = $this->find(['player_id='.$playerId.' and status=1 and series='.$ac['series'].' and create_time >= "'.date('Y-m-d H:i:s', $ace['open_time']).'" and create_time <= "'.date('Y-m-d H:i:s', $ace['close_time']).'"'])->toArray();
		
		//查找最后series_order
		$maxSeriesOrder = 0;
		$lastSeriesOrder = 0;
		foreach($order as $_o){
			$lastSeriesOrder = max($lastSeriesOrder, $_o['series_order']);
		}
		$maxSeriesOrder = $lastSeriesOrder + 1;
		
		
		//查找配置中最大order
		$where = 'series='.$ac['series'];
		if($chkTime){
			//获取开服天数
			$startTime = (new Configure)->getValueByKey('server_start_time');
			$days = ceil(max(0, time() - $startTime) / (24*3600));
			$where .= ' and (day_limit=0 or day_limit >='.$days.')';
		}
		$acs = $ActivityCommodity->find([$where])->toArray();
		//var_dump($acs);
		$dicMaxSeriesOrder = 0;
		foreach($acs as $_a){
			$dicMaxSeriesOrder = max($dicMaxSeriesOrder, $_a['series_order']);
		}
		
		$seriesOrder = min($maxSeriesOrder, $dicMaxSeriesOrder);
		if(in_array($ac['activity_id'], $this->onceOnly)){//购买一次
			if($lastSeriesOrder == $dicMaxSeriesOrder){
				return false;
			}
		}else{//无限
			
		}

		//检查可购买礼包是否与传入一致
		$find = false;
		foreach($acs as $_a){
			if($_a['series_order'] == $seriesOrder){
				$find = true;
				if($aci != $_a['id'])
					return false;
				break;
			}
		}
		if(!$find)
			return false;
		return true;
	}
		
	public function giftspname2id($name){
		$k = array_search($name, $this->giftsp);
		if(false === $k){
			return false;
		}
		return $k;
	}
}