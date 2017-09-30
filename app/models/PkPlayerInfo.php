<?php

/**
 * pk玩家信息
 *
 */
class PkPlayerInfo extends PkModelBase{
    //匹配相关
    const MATCH_SCORE_STEP = 100;//搜索step
    const MATCH_SEARCH_NUM = 5;//历史搜索玩家记录10个
    const MAX_MATCH_TIMES  = 10;//总匹配10次后
    const MAX_LOCK_TIMES   = 5;//锁定次数
    //匹配锁玩家
    const LOCK_STATUS_ON   = 1;//锁住
    const LOCK_STATUS_OFF  = 0;//无锁
    //每日奖励领取状态
    const DAILY_AWARD_STATUS_CAN_GET       = 0;//可以领取
    const DAILY_AWARD_STATUS_GAINED        = 1;//已经领取
    const DAILY_AWARD_STATUS_SHELL_RUNNING = 2;//脚本运行期间

    public $blacklist = ['create_time', 'update_time', 'memo', 'searched_player_ids'];

    /**
     * 更改武将id 化神后
     * (new PkPlayerInfo)->updateGeneralId($playerId, $oldGeneralId, $newGeneralId);
     *
     * 涉及跨服千万不要用此方法！！！
     *
     * @param $playerId
     * @param $oldGeneralId
     * @param $newGeneralId
     *
     */
    public function updateGeneralId($playerId, $oldGeneralId, $newGeneralId){
        global $config;
        $serverId = $config->server_id;
        $ppi      = $this->getBasicInfo($serverId, $playerId);
        $field    = array_search($oldGeneralId, ['general_1'=>$ppi['general_1'], 'general_2'=>$ppi['general_2'], 'general_3'=>$ppi['general_3']]);
        if($field!==false) {
            $this->updateAll([$field=>$newGeneralId], ['server_id'=>$serverId, 'player_id'=>$playerId, $field=>$oldGeneralId]);
            $this->clearPkDataCache($serverId, $playerId, 'pk_player_info');
        }
    }
    /**
     * 获取pk_player_info表信息
     *
     * @param int  $serverId
     * @param int  $playerId
     * @param bool $forDataFlag
     *
     * @return array
     */
    public function getBasicInfo($serverId, $playerId, $forDataFlag = false){
        $className = get_class($this);
        $key1      = $serverId . '_' . $playerId;
        $r         = Cache::getPlayer($key1, $className, 'pk_player_info');
        if(!$r) {
            $re = self::findFirst(['server_id=:serverId: and player_id=:playerId:', 'bind' => ['serverId' => $serverId, 'playerId' => $playerId]]);
            if(!$re) {
                $id = $this->addNew($serverId, $playerId);
                $re = self::findFirst($id);
            }
            $r = $this->adapter($re->toArray(), true);
            Cache::setPlayer($key1, $className, $r, 'pk_player_info');
        }
        $r = filterFields([$r], $forDataFlag, $this->blacklist)[0];

        //开启 状态 开始时间 结束时间
        //未开启 没有下一季 开始时间=0 结束时间=0、 有，开始时间 结束时间

        $PkGroup = new PkGroup;
        $group = $PkGroup->getPkGroupByServerId($serverId);
        $roundInfo = ['status'=>0, 'start_time'=>0, 'end_time'=>0];
        if($group) {
            $currentRoundStartTime = strtotime($group['current_round_start_time']);
            $nextRoundStartTime = strtotime($group['next_round_start_time']);

            $roundInfo['start_time'] = $currentRoundStartTime;
            $roundInfo['end_time'] = $nextRoundStartTime;

            if(time()>$currentRoundStartTime && time()<$nextRoundStartTime) {
                $roundInfo['status'] = 1;
            }
        }
        $r['round_info'] = $roundInfo;
        return $r;
    }
    /**
     * 添加玩家pk基础信息数据
     *
     * @param       $serverId
     * @param       $playerId
     * @param array $data
     *
     * @return int
     */
    public function addNew($serverId, $playerId, $data=[]){
        $exists = self::findFirst(['server_id=:serverId: and player_id=:playerId:', 'bind'=>['serverId'=>$serverId, 'playerId'=>$playerId]]);
        if(!$exists) {
            $DuelInitdata    = new DuelInitdata;
            $DuelRank        = new DuelRank;
            $initData        = $DuelInitdata->get();
            $gids            = (new PlayerGeneral)->getGeneralIds($playerId);
            $self            = new self;
            $self->server_id = $serverId;
            $self->player_id = $playerId;
            $self->score     = 0;
            $duelrank        = $DuelRank->getOneByScore($self->score);
            if ($duelrank) {
                $self->prev_duel_rank_id = $self->duel_rank_id = $duelrank['id'];
                $self->duel_rank    = $duelrank['rank'];
            }
            $self->free_search_times_per_day = $initData['default_num'];
            foreach(['general_1', 'general_2', 'general_3'] as $v) {
                if(isset($data[$v])) {
                    if($data[$v]!=0 && !in_array($data[$v], $gids)) {
                        return -1;
                    } else {
                        $self->{$v} = $data[$v];
                    }
                }
            }
            $self->update_time = date('Y-m-d H:i:s');
            $self->create_time = date('Y-m-d H:i:s');
            $self->save();
            $this->clearPkDataCache($serverId, $playerId, 'pk_player_info');
            return $self->id;
        }
        return 0;
    }

    /**
     * 匹配算法
     *
     * @param $serverId
     * @param $playerId
     * @return array
     */
    public function match($serverId, $playerId){//TODO
        $ppi               = $this->getBasicInfo($serverId, $playerId);
        $score             = $ppi['score'];
        $searchedPlayerIds = json_decode($ppi['searched_player_ids'], true);
        if(is_null($searchedPlayerIds)) $searchedPlayerIds = [];

        $i         = 0;
        $lockTimes = 0;
        $r         = [];
        $groups    = (new PkGroup)->getGroupsByServerId($serverId);
        $serverIds = implode(",", $groups);
        /**
         * 程序算法流程
         * 0. 初始化：取出之前重复的玩家id，自己的积分score，区间积分初始化：minScore=score-100；maxScore=score+100，自己的锁次数：T，以备搜索
         * 1. 根据条件搜索 go 2
         * 2. 搜不到满足条件的玩家，区间加减100：maxScore=maxScore+100,minScore=minScore-100，[minScore, maxScore]，go 1
         *    如果10次以内都搜不到，则将在重复玩家里搜索 go 1；
         *    搜到玩家 go 3
         * 3. 目标玩家未加锁 go 4；
         *    目标玩家已加锁：如果T<10,T+1, go 1；如果T=10,go 4
         * 4. 匹配玩家，将匹配的目标玩家锁住，将目标玩家加入重复列表，超过10个则挤掉第一个
         */
        do {

            if(!empty($searchedPlayerIds)) {
                $notInStat = $this->getSearchedSql($searchedPlayerIds);
            } else {
                $notInStat = '';
            }

            $i          = $i + 1;
            $minScore   = max($score - $i*self::MATCH_SCORE_STEP, 0);
            $maxScore   = $score + $i*self::MATCH_SCORE_STEP;
            #三个武将才能匹配
            $sql        = <<<SQLSTAT
SELECT * FROM pk_player_info
WHERE player_id<>{$playerId} AND server_id in ({$serverIds}) AND score >= {$minScore} AND score <= {$maxScore}{$notInStat} AND general_1<>0 AND general_2<>0 AND general_3<>0
ORDER BY RAND() LIMIT 1;
SQLSTAT;
            $re = $this->sqlGet($sql);
            if($re) {
                $r = $this->adapter($re[0], true);
                if($r['lock_status']==self::LOCK_STATUS_ON && $lockTimes<self::MAX_LOCK_TIMES) {//搜到一个锁定的玩家，自己的锁未达到10次
                    $lockTimes++;
                    usleep(200000);//0.2 second
                } else {//匹配到目标玩家， 搜到一个无锁定的玩家，则设为最大锁定次数
                    $this->alter($r['server_id'], $r['player_id'], ['lock_status' => self::LOCK_STATUS_ON]);
                    if (count($searchedPlayerIds) >= self::MATCH_SEARCH_NUM) {
                        array_shift($searchedPlayerIds);
                    }
                    array_push($searchedPlayerIds, ['server_id' => $r['server_id'], 'player_id' => $r['player_id']]);
                    $updateData['searched_player_ids']     = q(json_encode($searchedPlayerIds));
                    $updateData['current_day_match_times'] = 'current_day_match_times+1';
                    $this->alter($serverId, $playerId, $updateData);
                    break;
                }
            }
            if($i>=self::MAX_MATCH_TIMES && count($searchedPlayerIds)>0) {
                array_shift($searchedPlayerIds);
                $this->alter($serverId, $playerId, ['searched_player_ids' => q(json_encode($searchedPlayerIds))]);
            }
        } while(true);
        return $r;
    }

    /**
     * 拼接sql
     *
     * @param $searchArr
     *
     * @return bool|string
     */
    public function getSearchedSql($searchArr){
        $notIn       = ' AND player_id NOT IN (';
        $playerIdArr = Set::extract('/player_id', $searchArr);
        $playerIdSql = implode(',', $playerIdArr);
        $notIn       .= $playerIdSql . ')';
        return $notIn;
    }
    /**
     * 更改玩家pk数据
     *
     * @param       $serverId
     * @param       $playerId
     * @param       $conditions
     * @param array $fields
     *
     * @return int
     */
    public function alter($serverId, $playerId, array $fields, array $conditions=[]){
        $gids = (new PlayerGeneral)->getGeneralIds($playerId);
        foreach(['general_1','general_2','general_3'] as $v) {//检查武将信息
            if (isset($fields[$v]) && $fields[$v]!=0 && !in_array($fields[$v], $gids)) {
                return -1;
            }
        }
        if(!isset($fields['update_time'])){//更新update_time
            $fields['update_time'] = qd();
        }
        $cond = ['server_id'=>$serverId, 'player_id'=>$playerId];
        if($conditions) {
            $cond = array_merge($cond, $conditions);
        }
        $re = $this->updateAll($fields, $cond);
        $this->clearPkDataCache($serverId, $playerId, 'pk_player_info');
        return $re;
    }
}