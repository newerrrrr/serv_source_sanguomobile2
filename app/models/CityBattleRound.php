<?php
class CityBattleRound extends CityBattleModelBase{
    CONST NOT_START = -1;//比赛未开始
    CONST SIGN_FIRST = 0;//诸侯报名
    CONST SIGN_NORMAL = 1;//正常报名
    CONST SELECT_PLAYER = 2;//筛选玩家中
    CONST SELECT_PLAYER_FINISH = 3;//筛选玩家结束
    CONST DOING = 4;//比赛中
    CONST CLAC_REWARD = 5;//比赛发奖结算
    CONST FINISH = 6;//比赛完成

    public function getCurrentRoundInfo(){
        $result = Cache::db('cache', 'CityBattle')->get("cbRoundInfo");
        if(!$result){
            $ret = self::find([
                'order'=>'id desc'
            ])->toArray();
            if($ret){
                $result = $ret[0];
                Cache::db('cache', 'CityBattle')->set("cbRoundInfo", $result);
            }else{
                $result = false;
            }
        }
        return $result;
    }

    public function getCurrentRound(){
        $ret = $this->getCurrentRoundInfo();
        if($ret){
            return $ret['id'];
        }else{
            return false;
        }
    }

    public function getCurrentRoundStatus(){
        $ret = $this->getCurrentRoundInfo();
        if($ret){
            return $ret['status'];
        }else{
            return false;
        }
    }

    public function getCurrentSeason(){
        $ret = $this->getCurrentRoundInfo();
        if($ret){
            return $ret['season_id'];
        }else{
            return false;
        }
    }

    public function getCurrentSeasonStartDate(){
        $seasonId = $this->getCurrentSeason();
        if($seasonId){
            $ret = self::findFirst(["season_id={$seasonId} and count_in_season=1"]);
            if($ret){
                return strtotime($ret->create_time);
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

	public function getCurrentWeek(){
		$ret = $this->getCurrentRoundInfo();
		if($ret){
            return ceil($ret['count_in_season']/2);
        }else{
            return false;
        }
	}
	
    public function addNew(){
        $re = $this->getCurrentRoundInfo();
        $CountryBasicSetting = new CountryBasicSetting;
        $seasonDuration = $CountryBasicSetting->getValueByKey("cseason_duration");
        if($re){
            if($re['count_in_season']==$seasonDuration){
                $seasonId = $re['season_id']+1;
                $countInSeason = 1;
            }else{
                $seasonId = $re['season_id'];
                $countInSeason = $re['count_in_season']+1;
            }
        }else{
            $seasonId = 1;
            $countInSeason = 1;
        }

        $self                       = new self;
        $self->season_id           = $seasonId;
        $self->count_in_season    = $countInSeason;
        $self->status              = self::NOT_START;
        $self->create_time         = date("Y-m-d H:i:s");
        $self->save();
        Cache::db('cache', 'CityBattle')->delete("cbRoundInfo");
        return $self->id;
    }

    /**
     * CityBattleRound::resetData();
     * 赛季重置
     * ↘	当赛季结束，将重置部分数据，重新开启城战
     * ↘	重置数据：
     * 1	所有城池归属权清空
     * 2	阵营科技数据
     * 3	城战联盟任务
     * ↘	联盟获得一次免费的转阵营机会
     * 赛季积分
     * ↘	每个城战地图内的城池含有一个积分
     * ↘	城池不同积分不同
     * ↘	每轮城战结束后会增加积分
     */
    public static function resetData(){
        (new City)->updateAll(['camp_id'=>0]);//所有城池归属权清空
        (new Camp)->updateAll(['camp_score'=>0]);//阵营积分
        CityBattleMission::find()->delete();//城战联盟任务
        CityBattleScience::find()->delete();//阵营科技数据
    }

	/**
     * 更新当前进行中的行记录
     * e.g. alterCurrent(['status'=>3], ['status'=>2]);
     * @param array $fields
     * @param array $conditions
     *
     * @return int
     */
    public function alterCurrent(array $fields, array $conditions=[]){
        $current = self::findFirst(['status<>:status:', 'bind'=>['status'=>self::FINISH]]);
        if($current) {
            if($conditions) {
                $conditions['id'] = $current->id;
            } else {
                $conditions = ['id'=>$current->id];
            }
            Cache::db('cache', 'CityBattle')->delete("cbRoundInfo");
            return $this->updateAll($fields, $conditions);
        }
        return 0;
    }

    public function setRoundStatus($from, $to){
        $ret = $this->getCurrentRoundInfo();
        if($ret){
            $roundId = $ret['id'];
            $re = $this->updateAll(['status'=>$to], ['id'=>$roundId, 'status'=>$from]);
            if($re){
                Cache::db('cache', 'CityBattle')->delete("cbRoundInfo");
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}?>