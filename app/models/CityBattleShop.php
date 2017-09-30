<?php
/**
 * 城战商店
 *
 */
class CityBattleShop extends CityBattleModelBase{
    
    /*
     * 记录商品数量
     */
    public function initShopNum($shopId){
        $Shop = new Shop();
        $shopInfo = $Shop->dicGetOne($shopId);
        
        $self                      = new self;
        $self->shop_id             = $shopId;
        $self->total               = $shopInfo['buy_daily_limit'];
        $self->save();
        return $self->id;
    }

    /*
     * 获取商品数量
     */
    public function getShopNum($shopId){        
        $re = $this->find(['shop_id ='.$shopId])->toArray();
        if(!$re){
            $this->initShopNum($shopId);
        }
        $re = $this->find(['shop_id ='.$shopId])->toArray();
        return $re[0];
    }
    
    
	/*
	 * 城战里的城市商店里商品数量针对全服限制
	 */
	public function updateShopNum($shopId, $itemNum){
	    $updateData = [];
	    $updateData['total'] = "total -".$itemNum;

	    $re = $this->updateAll($updateData, ['shop_id'=>$shopId, 'total >='=>$itemNum]);
        return $re;
	}
	
	
	
	
}