<?php
/**
 * 限时比赛 字典表
 */
class PlayerTimeLimitMatch extends ModelBase{
    public function addNew($data){
//        $exists = self::findFirst(['player_id=:playerId: and time_limit_match_list_id=:tlmListId:', 'bind'=>['playerId'=>$data['player_id'], 'tlmListId'=>$data['time_limit_match_list_id']]]);
//        if(!$exists) {
            $self                           = new self;
            $self->player_id                = $data['player_id'];
            $self->time_limit_match_list_id = $data['time_limit_match_list_id'];
            $self->match_type               = $data['match_type'];
            $self->score                    = 0;
            $self->update_time              = $self->create_time = date('Y-m-d H:i:s');
            $self->save();
//        }

        // $this->clearPlayerTimeLimitMatchCache();
    }
    public function getByPlayerId($a, $b=false){
        return ;
    }
    /**
     * 获取今天用户参加的限时比赛  
     * @param  int $playerId             
     * @param  int $timeLimitMatchListId 
     * @return array                       
     */
    public function getPlayerTodayMatch($playerId){
        $todayMatchList = (new TimeLimitMatchList)->getTodayMatch();
        if(!$todayMatchList || (is_int($todayMatchList) && $todayMatchList==-1)) return [];
        $timeLimitMatchListId = $todayMatchList['id'];
        TodayMatch://获取今日比赛
        $re = self::findFirst("player_id={$playerId} and time_limit_match_list_id={$timeLimitMatchListId}");
        if($re) {
            $r = $this->adapter($re->toArray(),true);
            return $r;
        } else {
            $data['player_id'] = $playerId;
            $data['time_limit_match_list_id'] = $timeLimitMatchListId;
            $data['match_type'] = TimeLimitMatchList::findFirst($timeLimitMatchListId)->toArray()['match_type'];
            $this->addNew($data);
            goto TodayMatch;
        }
        return [];
    }
    /**
     * 更新每日积分
     *
     * @param       $playerId
     * @param       $matchTypeId
     * @param       $score
     * @param array $info
     *
     * @return bool|null
     */
    public function updateScore($playerId, $matchTypeId, $score, $info=[]){
        $oneMatchType = (new TimeLimitMatchType)->dicGetOne($matchTypeId);
        $matchType    = $oneMatchType['type'];
        $score        = floor($score * $oneMatchType['point']);
        $re           = $this->getPlayerTodayMatch($playerId);
        if(!$re) return null;
        if($re['match_type']!=$matchType) return null;

        $timeLimitMatchListId = $re['time_limit_match_list_id'];
        $timeLimitMatchList = TimeLimitMatchList::findFirst($timeLimitMatchListId);
        if($timeLimitMatchList && $timeLimitMatchList->award_status==1) return null;

        $PlayerTimeLimitMatchTotal = new PlayerTimeLimitMatchTotal;
        $PlayerTimeLimitMatchTotal->getPlayerTimeLimitMatchTotal($playerId);
        $timeLimitMatchListId      = $re['time_limit_match_list_id'];
        if($this->updateAll(['score'=>"score+{$score}", 'update_time'=>qd()], [
            'player_id'                => $playerId,
            'time_limit_match_list_id' => $timeLimitMatchListId,
            'match_type'               => $matchType,
            ])) {//更新成功后，更新总表
            $configId = (new TimeLimitMatchConfig)->getCurrentRoundId();
            $PlayerTimeLimitMatchTotal->updateAll(['score'=>"score+{$score}", 'update_time'=>qd()],[
                    'player_id'                  => $playerId,
                    'time_limit_match_config_id' => $configId,
                ]);
        }
        //判断是否跨越奖励积分
        $todayMatch     = (new TimeLimitMatchList)->getTodayMatch();
        $TimeLimitMatch = new TimeLimitMatch;
		$tlm = $TimeLimitMatch->dicGetOne($todayMatch['time_limit_match_id']);
		if(!$tlm)
			return false;

        //$tlm                     = $tlm->toArray();
        $tlm                     = $TimeLimitMatch->parseColumn($tlm);
        $TimeLimitMatchPointDrop = new TimeLimitMatchPointDrop;
        $PlayerMail              = new PlayerMail;
        $Drop                    = new Drop;
        $testQA = false;
        if($testQA) {
            debug('======================B', 1);
            debug('原始值: '.$re['score'], 1);
            debug('增量值: '.$score, 1);
        }
		foreach($tlm['drop_id'] as $_dropid){
			$pd = $TimeLimitMatchPointDrop->dicGetOne($_dropid);
			if($pd){
                if($testQA) {
                    debug('比较值min_point: '.$pd['min_point'], 1);
                }
				if($re['score'] < $pd['min_point'] && ($re['score']+$score) >= $pd['min_point']){
					$drop = $Drop->rand($playerId, [$pd['drop']]);
					if(!$drop){
						return false;
					}
					$item = [];
					foreach($drop as $_d){
						$item = $PlayerMail->newItem($_d[0], $_d[1], $_d[2], $item);
					}
					
					//发送发奖邮件
					if(!$PlayerMail->sendSystem($playerId, PlayerMail::TYPE_LIMITSCOREGIFT, '', '', 0, ['step_point'=>$pd['min_point']], $item)){
						return false;
					}
					//break;
				}
			}
		}
		if($testQA) {
            debug('======================E', 1);
        }
		(new PlayerCommonLog)->add($playerId, ['type'=>'限时比赛type'.$oneMatchType['type'], 'desc'=>$oneMatchType['desc'], 'addScore'=>$score, 'info'=>$info]);
    }
}