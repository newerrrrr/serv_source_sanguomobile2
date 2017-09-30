<?php
//建筑
class GuildWarehouse extends ModelBase{
    public $blacklist = ['create_time', 'update_time'];    

    /**
     * 获取所有公会仓库信息
     * @param  int $guildId 帮会id
     * @return array          
     */
    public function getAllGuildWarehouse($guildId){
        $re = Cache::getGuild($guildId, __CLASS__);
        if(!$re) {
            $re = self::find(["guild_id={$guildId}"])->toArray();
            $re = $this->adapter($re);
            $sortedAllData = Set::combine($allData, "{n}.player_id", "{n}");
            Cache::setGuild($guildId, __CLASS__, $re);
        }
        return $re;
    }
    /**
     * 获取玩家仓库信息
     * @param  int  $playerId   
     * @param  boolean $forDataFlag 是否用来返回Data接口数据
     * @return array
     */
    public function getByPlayerId($playerId, $forDataFlag=false){
        $player = (new Player)->getByPlayerId($playerId);
        $guildId = $player['guild_id'];
        if($guildId) {
            $getAll= $this->getAllGuildWarehouse($guildId);
            $re = filterFields([$getAllGuildMember[$playerId]], $forDataFlag, $this->blacklist)[0];
        } else {
            $re = [];
        }
        return $re;
    }

    /**
     * 玩家储存资源
     * 
     * @param  [type] $playerId         [description]
     * @param  [type] $storeResourceArr [0,0,0,0,0] 五项资源，排序为黄金 粮草 木材 石头 铁矿
     * @return [type]                   [description]
     */
    public function storeResource($playerId, $storeResourceArr){
        $player = (new Player)->getByPlayerId($playerId);
        if(empty($player['guild_id'])){
            return false;
        }
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $re = $this->getByPlayerId($playerId);
        if(empty($re)){
            $self = new self;
            $self->player_id = $playerId;
            $self->guild_id = $player['guild_id'];
            $self->gold_amount = $storeResourceArr[0];
            $self->food_amount = $storeResourceArr[1];
            $self->wood_amount = $storeResourceArr[2];
            $self->stone_amount = $storeResourceArr[3];
            $self->iron_amount = $storeResourceArr[4];
            $self->last_store_time = date("Y-m-d");
            $self->last_day_store_amount = $this->calcResourceToStad($storeResourceArr);
            $self->save();
        }else{
            $this->updateAll(['gold_amount'=>$re['gold_amount']+$storeResourceArr[0],['food_amount'=>$re['food_amount']+$storeResourceArr[1],['wood_amount'=>$re['wood_amount']+$storeResourceArr[2],['stone_amount'=>$re['stone_amount']+$storeResourceArr[3],['iron_amount'=>$re['iron_amount']+$storeResourceArr[4]], ['id'=>$re['id']]);
        }
        $this->clearGuildCache($player['guild_id']);
        Cache::unlock($lockKey);
    }

    /**
     * 玩家取出资源
     * 
     * @param  [type] $playerId         [description]
     * @param  [type] $storeResourceArr [0,0,0,0,0] 五项资源，排序为黄金 粮草 木材 石头 铁矿
     * @return [type]                   [description]
     */
    public function retrievingResource($playerId, $storeResourceArr){
        $player = (new Player)->getByPlayerId($playerId);
        if(empty($player['guild_id'])){
            return false;
        }
        //锁定
        $lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $re = $this->getByPlayerId($playerId);
        if(!empty($re) && $re['gold_amount']>=$storeResourceArr[0] && $re['food_amount']>=$storeResourceArr[1] 
            && $re['wood_amount']>=$storeResourceArr[2] && $re['stone_amount']>=$storeResourceArr[3] && $re['iron_amount']>=$storeResourceArr[4]){
            $this->updateAll(['gold_amount'=>$re['gold_amount']-$storeResourceArr[0],['food_amount'=>$re['food_amount']-$storeResourceArr[1],['wood_amount'=>$re['wood_amount']-$storeResourceArr[2],['stone_amount'=>$re['stone_amount']-$storeResourceArr[3],['iron_amount'=>$re['iron_amount']-$storeResourceArr[4]], ['id'=>$re['id']]);
            $this->clearGuildCache($player['guild_id']);
        }
        Cache::unlock($lockKey);
    }


}