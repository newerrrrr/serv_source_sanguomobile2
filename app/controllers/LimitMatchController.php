<?php
/**
 * 限时比赛 相关业务逻辑
 */
use Phalcon\Mvc\View;
class LimitMatchController extends ControllerBase{
    public function initialize() {
        parent::initialize();
        $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
    }
	
    /**
     * 当前活动列表
     * 
     * 
     * @return <type>
     */
	/*public function activityListAction(){
		$player                    = $this->getCurrentPlayer();
		$playerId                  = $player['id'];
		
		$activityId = 1006;
		
		$Activity = new Activity;
		$data1 = $Activity->find(['date_type <> 2 or (date_type = 2 and open_date<=UNIX_TIMESTAMP() and close_date>=UNIX_TIMESTAMP())'])->toArray();
		$ActivityCommodity = new ActivityCommodity;
		$data2 = $ActivityCommodity->find(['activity_id = '.$activityId.' and open_time <= UNIX_TIMESTAMP() and close_time >= UNIX_TIMESTAMP()'])->toArray();
		
		$data = [];
		if(!$data2){//去掉1006
			foreach($data1 as $_d){
				if($_d['id'] != $activityId){
					$data[] = $_d;
				}
			}
		}else{//更新时间
		}
		$data = Set::extract('/id', $data);
		echo $this->data->send(array('list'=>$data));
	}*/
	
    /**
     * 显示当前限时比赛相关信息
     *
     * 使用方法如下
     *
     * ```php
     * url: limit_match/showLimitMatch
     * postData:{}
     * return: {...}
     * ```
     */
    public function showLimitMatchAction() {
		$player                    = $this->getCurrentPlayer();
		$playerId                  = $player['id'];
		
		$TimeLimitMatchConfig      = new TimeLimitMatchConfig;
		$TimeLimitMatchList        = new TimeLimitMatchList;
		$PlayerTimeLimitMatch      = new PlayerTimeLimitMatch;
		$PlayerTimeLimitMatchTotal = new PlayerTimeLimitMatchTotal;
		$config                    = $TimeLimitMatchConfig->getCurrentRound();
		$list                      = $TimeLimitMatchList->getCurrentRound();
		$todayMatch                = $TimeLimitMatchList->getTodayMatch();
		
		$lastMatchConfig           = $TimeLimitMatchConfig->getLastRound();
		$interval                  = (new Starting)->dicGetOne('time_limit_match_interval');
		$nextTime                  = $lastMatchConfig['end_time'] + 24*60*60*$interval;//下一次开启时间
		$data['next_match_time']   = intval($nextTime);
        if(is_int($todayMatch) && $todayMatch==-1) {//因杀人比赛需求上时间更改到08：00-22：00而作的修改
            $playerTodayMatchTotal      = $PlayerTimeLimitMatchTotal->getPlayerTimeLimitMatchTotal($playerId);
            $data['config_match']       = $config;
            $data['list_match']         = $list;
            $data['today_match']        = $TimeLimitMatchList->passArgs['today_match'];
            $data['player_today_match'] = [
                'id'                       => 0,
                'player_id'                => $playerId,
                'time_limit_match_list_id' => $TimeLimitMatchList->passArgs['today_match']['time_limit_match_id'],
                'match_type'               => $TimeLimitMatchList->passArgs['today_match']['match_type'],
                'score'                    => 0,
                'update_time'              => time(),
                'create_time'              => time(),

            ];
            $data['player_total_match'] = $playerTodayMatchTotal;
            $data['rank'] = $this->getMyRank($playerId, 1, $TimeLimitMatchList->passArgs['today_match']['id'], $config['id'], true);
            $data['rankall'] = $this->getMyRank($playerId, 2, $TimeLimitMatchList->passArgs['today_match']['id'], $config['id'], true);
            echo $this->data->send($data);
            exit;
        }
    	if($todayMatch) {
			$playerTodayMatch           = $PlayerTimeLimitMatch->getPlayerTodayMatch($playerId);
			$playerTodayMatchTotal      = $PlayerTimeLimitMatchTotal->getPlayerTimeLimitMatchTotal($playerId);

			$data['config_match']       = $config;
			$data['list_match']         = $list;
			$data['today_match']        = $todayMatch;
			$data['player_today_match'] = $playerTodayMatch;
			$data['player_total_match'] = $playerTodayMatchTotal;
			
			$data['rank'] = $this->getMyRank($playerId, 1, $todayMatch['id'], $todayMatch['time_limit_match_config_id'], true);
			$data['rankall'] = $this->getMyRank($playerId, 2, $todayMatch['id'], $todayMatch['time_limit_match_config_id'], true);
	    	echo $this->data->send($data);
	    	exit;
		}elseif($lastMatchConfig['status'] == 0){//第一次
			echo $this->data->send(['next_match_time'=>$lastMatchConfig['start_time']]);
    		exit;
    	} else {
    		echo $this->data->send($data);
    		exit;
    	}
    }
	
	public function getRank($type, $listId, $configId){
		//获取排名列表
		$limit = 100;
		
		$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
		$PlayerTimeLimitMatchTotal = new PlayerTimeLimitMatchTotal;
		
		$TimeLimitMatchList = new TimeLimitMatchList;
		
		//$playerInfo = Cache::db()->get('LimitMatchRank'.$type);
		//if(!$playerInfo){
			if($type == 1){//阶段

				$matches = $PlayerTimeLimitMatch->find(['time_limit_match_list_id='.$listId, 'order'=>'score desc,update_time asc', 'limit'=>$limit])->toArray();
				
			}else{//总
				
				$matches = $PlayerTimeLimitMatchTotal->find(['time_limit_match_config_id='.$configId, 'order'=>'score desc,update_time asc', 'limit'=>$limit])->toArray();
				
				//获取我的排名
			}
			
			//获取玩家信息
			$playerInfo = [];
			$Player = new Player;
			$PlayerGuild = new PlayerGuild;
			foreach($matches as $_i => $_m){
				$_player = $Player->getByPlayerId($_m['player_id']);
				//$_playerGuild = $PlayerGuild->getByPlayerId($_m['player_id']);
				$playerInfo[] = [
					'player_id' => $_player['id']*1,
					'nick' => $_player['nick'].'',
					'avatar_id' => $_player['avatar_id']*1,
					'guild_id' => $_player['guild_id']*1,
					'score' => $_m['score']*1,
				];
			}
			
			//Cache::db()->setex('LimitMatchRank'.$type, 3600, $playerInfo);
		//}
		return $playerInfo;
	}
	
	public function getMyRank($playerId, $type, $listId, $configId, $fromRank=false){
		if($fromRank){
			$playerInfo = $this->getRank($type, $listId, $configId);
		
			$myRank = 0;
			foreach($playerInfo as $_i=>$_p){
				if($_p['player_id'] == $playerId){
					$myRank = $_i+1;
					break;
				}
			}
			if($myRank)
				return $myRank;
		}
	
		$PlayerTimeLimitMatch = new PlayerTimeLimitMatch;
		$PlayerTimeLimitMatchTotal = new PlayerTimeLimitMatchTotal;
		$myRank = Cache::db()->get('LimitMatchMyRank'.$type.':'.$playerId);
		if(!$myRank){
			if($type == 1){
				//获取我的排名
				if($my = $PlayerTimeLimitMatch->findFirst(['player_id='.$playerId.' and time_limit_match_list_id='.$listId])){
					$myRank = $PlayerTimeLimitMatch->count(['time_limit_match_list_id='.$listId.' and (score > '.$my->score.' or (score='.$my->score.' and update_time < "'.$my->update_time.'"))'])+1;
				}else{
					$myRank = 0;
				}
			}else{
				//获取我的排名
				if($my = $PlayerTimeLimitMatchTotal->findFirst(['player_id='.$playerId.' and time_limit_match_config_id='.$configId])){
					$myRank = $PlayerTimeLimitMatchTotal->count(['time_limit_match_config_id='.$configId.' and (score > '.$my->score.' or (score='.$my->score.' and update_time < "'.$my->update_time.'"))'])+1;
				}else{
					$myRank = 0;
				}
			}
			Cache::db()->setex('LimitMatchMyRank'.$type.':'.$playerId, 3600, $myRank);
		}
		return $myRank;
	}
	
    /**
     * 排名
     *
     * type：1.阶段排名；2.总排名
     * @return <type>
     */
	public function rankAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$type = floor(@$post['type']);
		if(!checkRegularNumber($type))
			exit;
		
		if(!in_array($type, [1, 2])){
			exit;
		}
		
		try {
			$TimeLimitMatchList = new TimeLimitMatchList;
			$listId = 0;
			$configId = 0;
			//查找阶段排名id
			if($type == 1){
				$listId = $TimeLimitMatchList->getTodayListId(); 
				if(!$listId){
                    if(!isset($TimeLimitMatchList->passArgs['today_match'])) {
                        throw new Exception(10355);//当前无比赛
                    } else {
                        $listId = $TimeLimitMatchList->passArgs['today_match']['id'];
                    }
				}
			}elseif($type == 2){
				$configId = (new TimeLimitMatchConfig)->getCurrentRoundId(); 
				if(!$configId){
					throw new Exception(10356);//当前无比赛
				}
			}
			$playerInfo = $this->getRank($type, $listId, $configId);
			
		
			$myRank = 0;
			foreach($playerInfo as $_i=>$_p){
				if($_p['player_id'] == $playerId){
					$myRank = $_i+1;
					break;
				}
			}
			
			//获取我的排名
			if(!$myRank){
				$myRank = $this->getMyRank($playerId, $type, $listId, $configId);
			}
			
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

			//清除缓存
		}
		
		if(!$err){
			echo $this->data->send(array('rank'=>$playerInfo, 'myRank'=>$myRank));
		}else{
			echo $this->data->sendErr($err);
		}
	}

    /**
     * 获取历史最高分
     * 
     * 
     * @return <type>
     */
	public function historyTopAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		
		//$TimeLimitMatchConfig = new TimeLimitMatchConfig;
		//$configId = (new TimeLimitMatchConfig)->getCurrentRoundId(); 
		
		$historyTopInfo = Cache::db()->get('historyTopInfo');
		if(!$historyTopInfo){
			$PlayerTimeLimitMatchTotal = new PlayerTimeLimitMatchTotal;
			$where = 'rank=1';
			/*if($configId){
				$where .= ' and time_limit_match_config_id < '.$configId;
			}*/
			$ptlt = $PlayerTimeLimitMatchTotal->find([$where, 'order'=>'time_limit_match_config_id'])->toArray();
			
			$historyTopInfo = [];
			
			//获取玩家信息
			$Player = new Player;
			foreach($ptlt as $_t){
				$_player = $Player->getByPlayerId($_t['player_id']);
				$historyTopInfo[] = [
					'nick'=>$_player['nick'].'',
					'score'=>$_t['score']*1,
					'player_id'=>$_t['player_id']*1,
					'avatar'=>$_player['avatar_id']*1,
					'config_id'=>$_t['time_limit_match_config_id']*1,
				];
			}
			$time = time();
			$sec = mktime(0, 0, 0, date('m', $time), date('d', $time)+1, date('Y', $time)) - time();
			Cache::db()->setex('historyTopInfo', $sec, $historyTopInfo);
		}
		
		echo $this->data->send(array('historyTopInfo'=>$historyTopInfo));
	}
}