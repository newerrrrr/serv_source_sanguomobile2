<?php
/**
 * 联盟相关逻辑
 */
use Phalcon\Mvc\View;
class GuildController extends ControllerBase{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}

    /**
     * 合并消息如下
     *
     * ```
     * guild/viewAllMember
     * guild/viewAllRequestMember
     * guild/viewGuildInfo
     * guild/showBoard
     * ```
     *  guild/comboGuildMemberInfo
     * postData: {}
     * return {[PlayerGuild],[PlayerGuildRequest],[Guild]}
     */
	public function comboGuildMemberInfoAction(){
        $playerId            = $this->getCurrentPlayerId();
        $player              = $this->getCurrentPlayer();
        $guildId             = $player['guild_id'];
        $PlayerGuild         = new PlayerGuild;
        $playerGuildRequests = [];
        $data                = [];
        if($guildId) {
            $guild       = (new Guild)->getGuildInfo($guildId);
			$guild['donate_date'] = strtotime($guild['donate_date']);
            $playerGuild = $PlayerGuild->getByPlayerId($playerId);
            if($playerGuild['rank']>=PlayerGuild::RANK_R4) {
                $playerGuildRequests = (new PlayerGuildRequest)->getAllGuildRequest($guildId);
            }
            $playerGuilds = $PlayerGuild->getAllGuildMember($guildId, true);
            $guildBoard   = (new GuildBoard)->getByPlayerId($playerId);

            $data['PlayerGuild']        = keepFields($playerGuilds, ['player_id', 'rank', 'Player']);
            $data['PlayerGuildRequest'] = keepFields($playerGuildRequests, ['player_id', 'Player']);
            $data['Guild']              = $guild;

            $data['Guild']['current_season_start_date'] = (new CityBattleRound)->getCurrentSeasonStartDate();//for cocos ouch
            
            $data['GuildBoard']         = $guildBoard;
        }

        echo $this->data->send($data);
        exit;
    }
	/**
	 * 查看联盟成员列表
	 *
	 * 使用方法如下
	 * ```php
	 * guild/viewAllMember
	 * postData: {"guild_id":1}
	 * return: {PlayerGuild}
	 * ```
	 */
	public function viewAllMemberAction(){
		$postData = getPost();
		$guildId = $postData['guild_id'];
		$playerGuilds = (new PlayerGuild)->getAllGuildMember($guildId, true);
		echo $this->data->send(['PlayerGuild'=>keepFields($playerGuilds, ['store_food','player_id', 'rank', 'Player'])]);
		exit;
	}
    /**
     * 查看联盟成员列表-国王战
     *
     * 使用方法如下
     * ```php
     * guild/viewAllMemberKing
     * postData: {"guild_id":1}
     * return: {PlayerGuild}
     * ```
     */
    public function viewAllMemberKingAction(){
        $postData = getPost();
        $guildId  = $postData['guild_id'];
        $guild = (new Guild)->getGuildInfo($guildId);
        $leaderPlayerId = $guild['leader_player_id'];
        $playerGuilds = (new PlayerGuild)->getAllGuildMember($guildId, true);
        $data = [];
        foreach($playerGuilds as $k=>$v) {
            $data[$k]['player_id'] = $k;
            $data[$k]['avatar_id'] = $v['Player']['avatar_id'];
            $data[$k]['nick'] = $v['Player']['nick'];
            $data[$k]['power'] = $v['Player']['power'];
            $data[$k]['job'] = $v['Player']['job'];
            $data[$k]['rank'] = $v['rank'];
        }
        $leader = $data[$leaderPlayerId];
        unset($data[$leaderPlayerId]);
        $data = array_values($data);
        $data = Set::sort($data, '{n}.power', 'asc');//desc
        $data = Set::sort($data, '{n}.rank', 'desc');
        array_unshift($data, $leader);
        echo $this->data->send(['PlayerGuild'=>$data]);
        exit;
    }
	/**
	 * 查看所有申请联盟列表
	 *
	 * 使用方法如下
	 * ```php
	 * guild/viewAllRequestMember
	 * postData: {"guild_id":1}
	 * return: {PlayerGuildRequest}
	 * ```
	 */
	public function viewAllRequestMemberAction(){
		$postData = getPost();
		$guildId = $postData['guild_id'];
		if(!$guildId) {
			$errCode = 10061;
			echo $this->data->sendErr($errCode);
			exit;
		}
		$playerGuildRequests = (new PlayerGuildRequest)->getAllGuildRequest($guildId);
        $playerGuildRequests = keepFields($playerGuildRequests, ['player_id', 'Player']);
		echo $this->data->send(['PlayerGuildRequest'=>$playerGuildRequests]);
		exit;
	}

    /**
     * 合并消息
     *
     * ```
     * guild/viewGuildBuild
     * guild/canCreateGuildBuild
     * ```
     * guild/comboGuildBuild
     * postData: {}
     * return: {[GuildBuild], [CanCreate]}
     */
    public function comboGuildBuildAction(){
        $player   = $this->getCurrentPlayer();
        $guildId  = $player['guild_id'];
        //表示已经有联盟了
        if(!$guildId) {
            $errCode = 10060;
            goto sendErr;
        }

        $Map             = new Map;
        $GuildScience = new GuildScience;

        $mapElementIdArr = [1=>101, 2=>201, 3=>'resource', 4=>801];
        $data['GuildBuild'] = [];
        foreach($mapElementIdArr as $k=>$v) {
            if($k==3) {
                $map = $Map->getGuildResourceElement($guildId);
                $map = $map ? [$map] : [];
            } else {
                $map = $Map->getGuildMapElement($guildId, $v);
            }
            $data['GuildBuild'][] = $map;
        }

        //canCreateGuildBuild
        $data['CanCreate'] = [];
        $GuildScience->checkMapElement($guildId, 101);
        $guildBaseNumber = $Map->getGuildMapElementNum($guildId, 101);
        if($guildBaseNumber>0) {//判断堡垒至少有一个已经造好的
            $GuildScience->checkMapElement($guildId, 201);
            $canCreateData      = $GuildScience->guildMapElementArr;
            $resourceMapElement = $Map->getGuildResourceElement($guildId);//超级矿
            if ($resourceMapElement) {
                $canCreateData[] = ['map_element_id' => $resourceMapElement['map_element_id'], 'current' => 1, 'max' => 1];
            } else {
                $canCreateData[] = ['map_element_id' => 0, 'current' => 0, 'max' => 1];
            }
            //仓库
            $storeMapElementId = 801;

            $all      = $Map->getAllByGuildId($guildId);
            $storeNum = 0;
            foreach ($all as $k => $v) {
                if ($v['map_element_id'] == $storeMapElementId) {
                    $storeNum++;
                }
            }
            $canCreateData[] = ['map_element_id' => $storeMapElementId, 'current' => $storeNum, 'max' => 1];
        } else {
            $canCreateData   = $GuildScience->guildMapElementArr;
            $canCreateData[] = ['map_element_id' => 201, 'current' => 0, 'max' => 0];
            $canCreateData[] = ['map_element_id' => 0, 'current' => 0, 'max' => 0];
            $canCreateData[] = ['map_element_id' => 801, 'current' => 0, 'max' => 0];
        }

        $data['CanCreate'] = $canCreateData;
        echo $this->data->send($data);
        exit;
        sendErr: {
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
	/**
	 * 查看联盟领地建筑
	 *
	 * 使用方法如下：
	 * ```php
	 * guild/viewGuildBuild
	 * postData: {"type":1}
	 * return: {Map}
	 * ```
	 * 
	 * <pre>
	 * - "type":1 #堡垒
	 * - "type":2 #箭塔
	 * - "type":3 #矿场 (金矿，粮矿，木矿，石矿，铁矿)
	 * - "type":4 #仓库
	 * </pre>
	 */
	public function viewGuildBuildAction(){
		$playerId = $this->getCurrentPlayerId();
		$player   = $this->getCurrentPlayer();
		$guildId  = $player['guild_id'];
		//表示已经有联盟了
		if(!$guildId) {
			$errCode = 10060;
			goto sendErr;
		}

		$postData        = getPost();
		$type            = $postData['type'];
		
		$Map             = new Map;
		$MapElement      = new MapElement;
		
		$mapElementIdArr = ['1'=>101, '2'=>201, '3'=>'resource', '4'=>801];
		if(array_key_exists($type, $mapElementIdArr)) {
			$mapElementId = $mapElementIdArr[$type];
			if($type==3) {//矿场
				$map = $Map->getGuildResourceElement($guildId);
				if($map) {
					$map = [$map];
				} else {
					$map = [];
				}
			} else {
				$map = $Map->getGuildMapElement($guildId, $mapElementId);
			}
		}
		if($map) {
			$data = $map;
		} else {//不存在
			$data = [];
		}
		echo $this->data->send($data);
		exit;
		sendErr: {
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 查看联盟建筑detail
	 *
	 * 使用方法如下：
	 * ```php
	 * guild/viewGuildBuildDetail
	 * postData: {"x":100,"y":200}
	 * return: {detail}
	 * ```
	 */
	public function viewGuildBuildDetailAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();

		$guildId = $player['guild_id'];
		$postData = getPost();
		$x = $postData['x'];
		$y = $postData['y'];
		
		$Map = new Map;
		$PlayerProjectQueue = new PlayerProjectQueue;

		$toMap = $Map->getByXy($x, $y);
		if(in_array($toMap['map_element_origin_id'], [1,2,3,4,5,6,7,8])) {
			if($toMap['guild_id']==$guildId) {
				$data = $PlayerProjectQueue->getGuildBuildArmy($toMap);
			} else {
				$errCode = 10310;//查看联盟详情-不是自己联盟的建筑
				goto sendErr;
			}
		} else {
			$errCode = 10311;//查看联盟详情-不是联盟建筑
			goto sendErr;
		}
		echo $this->data->send($data);
		exit;
		sendErr: {
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 查看玩家可以造的联盟建筑
	 *
	 * 使用方法如下
	 * ```php
	 * guild/canCreateGuildBuild
	 * postData: {}
	 * retgurn: {101=>['current'=>1, 'max'=>2]...}
	 * ```
	 */
    public function canCreateGuildBuildAction(){
        $player  = $this->getCurrentPlayer();
        $guildId = $player['guild_id'];
        if(!$guildId) {
            $errCode = 10062;
            goto sendErr;
        }
        $Map          = new Map;
        $GuildScience = new GuildScience;

        $GuildScience->checkMapElement($guildId, 101);
        $guildBaseNumber = $Map->getGuildMapElementNum($guildId, 101);
        if($guildBaseNumber>0) {//判断堡垒至少有一个已经造好的
            $GuildScience->checkMapElement($guildId, 201);
            $data               = $GuildScience->guildMapElementArr;
            $resourceMapElement = $Map->getGuildResourceElement($guildId);//超级矿
            if ($resourceMapElement) {
                $data[] = ['map_element_id' => $resourceMapElement['map_element_id'], 'current' => 1, 'max' => 1];
            } else {//
                $data[] = ['map_element_id' => 0, 'current' => 0, 'max' => 1];
            }
            //仓库
            $storeMapElementId = 801;
            $all               = $Map->getAllByGuildId($guildId);
            $storeNum          = 0;
            foreach ($all as $k => $v) {
                if ($v['map_element_id'] == $storeMapElementId) {
                    $storeNum++;
                }
            }
            $data[] = ['map_element_id' => $storeMapElementId, 'current' => $storeNum, 'max' => 1];
        } else {
            $data   = $GuildScience->guildMapElementArr;
            $data[] = ['map_element_id' => 201, 'current' => 0, 'max' => 0];
            $data[] = ['map_element_id' => 0, 'current' => 0, 'max' => 0];
            $data[] = ['map_element_id' => 801, 'current' => 0, 'max' => 0];
        }

        echo $this->data->send($data);
        exit;
        sendErr: {
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
	/**
	 * 联盟领地建造
	 *
	 * 使用方法如下
	 * ```php
	 * guild/createGuildBuild
	 * postData: {"type":1?,"x":100,"y":100,"?":"?"}
	 * return: {Map}
	 * ```
	 * <pre>
	 * - "type":1 #堡垒
	 * - "type":2 #箭塔
	 * - "type":3 #矿场,"resource":1金矿，2：粮矿，3：木矿，4：石矿，5：铁矿
	 * - "type":4 #仓库
	 * - 
	 * </pre>
	 */
	public function createGuildBuildAction(){
		$playerId = $this->getCurrentPlayerId();
		$lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		$player   = $this->getCurrentPlayer();
		$guildId  = $player['guild_id'];
		//表示已经有联盟了
		if(!$guildId) {
			$errCode = 10062;
			goto sendErr;
		}

		$postData     = getPost();
		$type         = $postData['type'];
		$x            = $postData['x'];
		$y            = $postData['y'];
		
		$Map          = new Map;
		$MapElement   = new MapElement;
		$Guild        = new Guild;
		$PlayerGuild  = new PlayerGuild;
		$GuildScience = new GuildScience;
		$playerGuild  = $PlayerGuild->getByPlayerId($playerId);
		//联盟建筑，权限不足
		 if($playerGuild['rank']<PlayerGuild::RANK_R4) {
		 	$errCode = 10063;
		 	goto sendErr;
		 }
		$guildId = $playerGuild['guild_id'];
		$mapElementId = 0;
		$mapElementOriginId = 0;
		switch($type){
			case 1://联盟堡垒 build_element_id 101
				if(!$Map->checkCastlePosition([$x, $y], 0)) {
					$errCode = 10286;//放置联盟建筑-堡垒-当前位置不可用
					goto sendErr;
				}
				$mapElementId = 101;//堡垒
				$mapElement = $MapElement->dicGetOne($mapElementId);
				if($GuildScience->checkMapElement($guildId, $mapElementId)) {
					$Map->addNew([
							'x'                     => $x,
							'y'                     => $y,
							'map_element_id'        => $mapElementId,
							'map_element_origin_id' => $mapElement['origin_id'],
							'map_element_level'     => $mapElement['level'],
							'durability'            => $mapElement['starting_num'],
							'max_durability'        => $mapElement['max_num'],
							'status'                => 0,
							'guild_id'              => $guildId,
						]);
				} else {
					$errCode = 10064;
					goto sendErr;
				}
				break;
			case 2://联盟箭塔 build_element_id 201
				$mapElementId = 201;//箭塔
				if(!$Map->checkRandElementPosition([$x, $y])) {
					$errCode = 10287;//放置联盟建筑-箭塔-当前位置不可用
					goto sendErr;
				}
				if($Map->getGuildMapElementNum($guildId, 101)==0) {
					$errCode = 10066;
					goto sendErr;
				}
				if(!$Map->isGuildBuildInGuildArea($x, $y, $mapElementId, $guildId)) {//建造联盟建筑-不在联盟堡垒范围内
					$errCode = 10349;//建造联盟建筑-不在联盟堡垒范围内
					goto sendErr;
				}
				$mapElement = $MapElement->dicGetOne($mapElementId);
				if($GuildScience->checkMapElement($guildId, $mapElementId)) {
					$Map->addNew([
							'x'                     => $x,
							'y'                     => $y,
							'map_element_id'        => $mapElementId,
							'map_element_origin_id' => $mapElement['origin_id'],
							'map_element_level'     => $mapElement['level'],
							'durability'            => $mapElement['starting_num'],
							'max_durability'        => $mapElement['max_num'],
							'status'                => 0,
							'guild_id'              => $guildId,
						]);
				} else {
					$errCode = 10065;
					goto sendErr;
				}
				break;
			case 3://联盟矿场 build_element_id 金301 粮401 木501 石601 铁701
				if(!$Map->checkCastlePosition([$x, $y], 0)) {
					$errCode = 10288;//放置联盟建筑-超级矿-当前位置不可用
					goto sendErr;
				}
				if(isset($postData['resource'])) {
					$resource = $postData['resource'];
					$resourceMapElementArr = [1=>301, 2=>401, 3=>501, 4=>601, 5=>701];
					$mapElementId = $resourceMapElementArr[$resource];
					if($Map->getGuildMapElementNum($guildId, 101)==0) {
						$errCode = 10066;
						goto sendErr;
					}
					if(!$Map->isGuildBuildInGuildArea($x, $y, $mapElementId, $guildId)) {//建造联盟建筑-不在联盟堡垒范围内
						$errCode = 10350;//建造联盟建筑-不在联盟堡垒范围内
						goto sendErr;
					}
					$mapElement = $MapElement->dicGetOne($mapElementId);
					if(!$Map->getGuildResourceElement($guildId)) {//堡垒造好，并且没有超级矿
						$Map->addNew([
								'x'                     => $x,
								'y'                     => $y,
								'map_element_id'        => $mapElementId,
								'map_element_origin_id' => $mapElement['origin_id'],
								'map_element_level'     => $mapElement['level'],
								'durability'            => $mapElement['starting_num'],
								'max_durability'        => $mapElement['max_num'],
								'status'                => 0,
								'guild_id'              => $guildId,
								'resource'				=> $mapElement['max_res'],
							]);
					} else {
						$errCode = 10067;
						goto sendErr;
					}
				}
				break;
			case 4://联盟仓库 build_element_id 801
				$mapElementId = 801;
				if(!$Map->checkCastlePosition([$x, $y], 0)) {
					$errCode = 10289;//放置联盟建筑-仓库-当前位置不可用
					goto sendErr;
				}
				if(!$Map->isGuildBuildInGuildArea($x, $y, $mapElementId, $guildId)) {//建造联盟建筑-不在联盟堡垒范围内
					$errCode = 10351;//建造联盟建筑-不在联盟堡垒范围内
					goto sendErr;
				}
				if($Map->getGuildMapElementNum($guildId, 101)==0) {
					$errCode = 10068;
					goto sendErr;
				}
				$mapElement = $MapElement->dicGetOne($mapElementId);
				if(!$Map->getGuildMapElement($guildId, $mapElementId)) {//堡垒造好，并且没有造仓库
					$Map->addNew([
							'x'                     => $x,
							'y'                     => $y,
							'map_element_id'        => $mapElementId,
							'map_element_origin_id' => $mapElement['origin_id'],
							'map_element_level'     => $mapElement['level'],
							'durability'            => $mapElement['starting_num'],
							'max_durability'        => $mapElement['max_num'],
							'status'                => 0,
							'guild_id'              => $guildId,
						]);
				} else {
					$errCode = 10069;
					goto sendErr;
				}
				break;
			default:
				break;
		}
		Cache::unlock($lockKey);
		if($mapElementId) {
			$data = $Map->getGuildMapElement($guildId, $mapElementId);
		} else {
			$errCode = 10070;
			goto sendErr;
		}
        //联盟聊天推送
        $pushData = [
            'type'           => 14,
            'map_element_id' => $mapElementId,
            'x'              => $x,
            'y'              => $y,
            'nick'           => $player['nick'],
        ];
        $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
        socketSend($data);

		echo $this->data->send($data);
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
    /**
     * 创建联盟
     *
     * 使用方法如下
     * ```php
     * guild/createGuild
     * postData: {"create_guild_data":{"name":"god guild","short_name":"我的萌","icon_id":1,"need_check":1,"desc":"Fire in the hole","condition_fuya_level":10,"condition_player_power":5555}}
     * return: {Guild}
     * ```
     */
	public function createGuildAction(){
		$playerId        = $this->getCurrentPlayerId();
		$lockKey         = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		
		$player          = $this->getCurrentPlayer();
		$postData        = getPost();
		$createGuildData = $postData['create_guild_data'];
		
		$SensitiveWord = new SensitiveWord;
		$Guild         = new Guild;
		$PlayerGuild   = new PlayerGuild;
		//判断是否满足条件
		//a 等级不足
		if($player['level'] < 3) {//等级不足
			$errCode = 10238;
			goto sendErr;
		}
		//b 已经有联盟
		if($player['guild_id']) {
			$errCode = 10239;
			goto sendErr;
		}
		//名称非法
		if(strlen($createGuildData['name'])<1 || $SensitiveWord->checkSensitiveContent($createGuildData['name'], 2)) {
			$errCode = 10240;
			goto sendErr;
		}
		//短名称非法
		if(strlen($createGuildData['short_name'])<1 || $SensitiveWord->checkSensitiveContent($createGuildData['short_name'], 2)) {
			$errCode = 10241;
			goto sendErr;
		}

		//联盟名称是否重复
		if($Guild->checkNameExists($createGuildData['name'])) {
			$errCode = 10071;
			goto sendErr;
		}
		//联盟短名称是否重复
		if($Guild->checkShortNameExists($createGuildData['short_name'])) {
			$errCode = 10106;
			goto sendErr;
		}
		//宣言非法
		if($SensitiveWord->checkSensitiveContent($createGuildData['desc'])) {
			$errCode = 10242;
			goto sendErr;
		}
		//1000元宝是否满足
        $firstCreateGuild = $this->currentPlayerInfo['first_create_guild'];
        if($firstCreateGuild==0) {//首次建盟免费
            (new PlayerInfo)->alter($playerId, ['first_create_guild'=>1]);
        } else {
            if (!(new Cost)->updatePlayer($playerId, 105)) {//gem不足
                $errCode = 10243;
                goto sendErr;
            }
        }
		$createGuildData['leader_player_id'] = $playerId;
		

		//case 1: 创建guild主表
        $createGuildData['camp_id'] = $player['camp_id'];
		$data = $Guild->createGuild($createGuildData);
		//case 2: 添加一条player_guild数据，帮主数据
		$PlayerGuild->addNew($playerId, $data['id'], PlayerGuild::RANK_R5);
		// //case 3: 更新玩家联盟
		// $Player->setGuildId($playerId, $data['id']);
		// case 4: 创建留言板信息	
		$title = "";
		$text = "";
		(new GuildBoard)->saveRecord(0, $data['id'], 1, $title, $text);
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}

    /**
     * 换阵营 
     * guild/changeCamp
     */
	public function changeCampAction(){
        $playerId        = $this->getCurrentPlayerId();
        $lockKey         = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);//锁定

        $player       = $this->getCurrentPlayer();
        $postData     = getPost();
        $guildId      = $player['guild_id'];

        $Guild       = new Guild;
        $PlayerGuild = new PlayerGuild;
        $playerGuild = $PlayerGuild->getByPlayerId($playerId);
        //判断是否满足条件
        $guildInfo   = $Guild->getGuildInfo($guildId);
        if(empty($guildInfo)) {
            $errCode = 10770;//[换阵营]不存在当前联盟
            goto sendErr;
        }
        //a 你没权限操作
        if($playerGuild['rank'] < PlayerGuild::RANK_R5){
            $errCode = 10081;
            goto sendErr;
        }

        $guildCampId = $guildInfo['camp_id'];
        $allCamp     = (new CountryCampList)->dicGetAllId();
        if(!$postData['camp_id'] || !in_array($postData['camp_id'], $allCamp)) {
            $errCode = 10771;//[换阵营]必须选择一个阵营
            goto sendErr;
        } else {
            if($postData['camp_id']==$guildCampId) {
                $errCode = 10772;//[换阵营]阵营与当前相同
                goto sendErr;
            }
            $campId = intval($postData['camp_id']);
        }

        $CityBattleRound = new CityBattleRound;
        $roundStatus     = $CityBattleRound->getCurrentRoundStatus();
        $battleStatusArr = [CityBattleRound::SIGN_FIRST,CityBattleRound::SIGN_NORMAL,CityBattleRound::SELECT_PLAYER,CityBattleRound::SELECT_PLAYER_FINISH,CityBattleRound::DOING,CityBattleRound::CLAC_REWARD];
        if($guildCampId!=0 && $roundStatus !== false && in_array($roundStatus, $battleStatusArr)) {//已经有阵营的在此期间不能转阵营
            $errCode = 10773;//[换阵营]城战期间不能转阵营
            goto sendErr;
        }
        //人数最少的国家玩家数/当前你要加入的国家玩家数 要>70%
        //70%走Country_basic_setting表里的choose_camp_per
        //choose_camp_protect_num 目标阵营基数
        $CityBattleCampNumber = new CityBattleCampNumber;
        $allCampNumber        = $CityBattleCampNumber->getAll();
        $CountryBasicSetting  = new CountryBasicSetting;
        $chooseCampPer        = $CountryBasicSetting->getValueByKey('choose_camp_per');
        $chooseCampProtectNum = $CountryBasicSetting->getValueByKey('choose_camp_protect_num');
        $minNumberCamp        = array_slice($allCampNumber, 0, 1, true);
        if($allCampNumber[$campId]['number']>$chooseCampProtectNum && ($minNumberCamp/$allCampNumber[$campId]['number'])<($chooseCampPer/DIC_DATA_DIVISOR)) {//超过基数，开始判定
            $errCode = 10789;//当前国家已满，请选择其他国家
            goto sendErr;
        }

        $currentCityBattleStartDate = (new CityBattleRound)->getCurrentSeasonStartDate();
        do {
            if($guildCampId==0 || $guildInfo['change_camp_time']<=$currentCityBattleStartDate) break; //免费
            //花元宝            
            $costId = 29;
            if (!(new Cost)->updatePlayer($playerId, $costId)) {//gem不足
                $errCode = 10243;
                goto sendErr;
            }
            //10030 联盟荣誉
            $costId2 = 30;
            if (!(new Cost)->updatePlayer($playerId, $costId2)) {//联盟荣誉不足
                $errCode = 10774;//[转阵营]联盟荣誉不足
                goto sendErr;
            }

        } while(false);

        //开始转阵营
        $Guild->alter($guildId, ['camp_id'=>$campId, 'change_camp_time'=>qd()]);
        (new Player)->updateAllCampId($guildId, $campId, $guildCampId);

        //联盟更改阵营将任务置为失效
        $joinedGuildId = CityBattlePlayer::joinGuildId($player['server_id'], $guildId);
        (new CityBattleGuildMission)->setMarkByChangeCamp($joinedGuildId);

        //发邮件
        $allMembers = $PlayerGuild->getAllGuildMember($guildId, false);
        $playerIdsAll = Set::extract('/player_id', $allMembers);
        //推送swoole消息到联盟聊天里
        $pushData = [
            'type'          => 17,
            'camp_id'       => $campId,
        ];
        $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
        socketSend($data);
        //换阵营邮件
        (new PlayerMail)->sendSystem($playerIdsAll, PlayerMail::TYPE_GUILD_CHANGE_CAMP, '', '', 0, ['new_camp_id'=>$campId], [], '换阵营');
        //log
        (new PlayerCommonLog)->add($playerId,
                                   [
                                       'type' => '换阵营',
                                       'memo' => ['playerId' => $playerId, 'guildId' => $guildId, 'old_camp_id' => $guildCampId, 'new_camp_id' => $campId]
                                   ]);
        $data = [];
        Cache::unlock($lockKey);
        echo $this->data->send($data);
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
	/**
	 * 申请加入联盟
	 *
	 * 使用方法如下
	 * ```php
	 * guild/applyForGuild
	 * postData: {"guild_id":7}
	 * return: {}
	 * ```
	 */
	public function applyForGuildAction(){
		$playerId           = $this->getCurrentPlayerId();
		//锁定
		$lockKey            = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$player             = $this->getCurrentPlayer();
		$campId             = $player['camp_id'];
		$postData           = getPost();
		$guildId            = $postData['guild_id'];
		
		$Guild              = new Guild;
		$PlayerGuild        = new PlayerGuild;
		$PlayerGuildRequest = new PlayerGuildRequest;
		$PlayerBuild        = new PlayerBuild;
		$Player             = new Player;
		$fuyaBuild          = $PlayerBuild->getByOrgId($playerId, 1)[0];//获取官府id
		$guild              = $Guild->getGuildInfo($guildId);

		//申请条件判断
        //有阵营且阵营跟联盟不一致的
        if($campId!=0 && $campId!=$guild['camp_id']) {
            $errCode = 10775;//[申请入盟]你的阵营与联盟阵营不相同
            goto sendErr;
        }
		//a0 非法情况，有了帮会还来申请
		$playerGuild = $PlayerGuild->getByPlayerId($playerId);
		if($playerGuild) {
			$errCode = 10072;
			goto sendErr;
		}
		//a1 已经申请过
		$playerGuildRequest = $PlayerGuildRequest->getByPlayerId($playerId);
		if($playerGuildRequest && array_key_exists($guildId, $playerGuildRequest)) {
			$errCode = 10073;
			goto sendErr;
		}
		//a 人数已满
		if($guild['num']>=$guild['max_num']){
			$errCode = 10074;
			goto sendErr;
		}
		//b 官府等级不够
		if($fuyaBuild['build_level']<$guild['condition_fuya_level']) {
			$errCode = 10075;
			goto sendErr;

		}
		//c 战力不足
		if($Player->getPower($playerId)<intval($guild['condition_player_power'])) {
			$errCode = 10076;
			goto sendErr;
		}
		//申请逻辑处理
		if($guild['need_check']==0) {//直接进联盟
			$PlayerGuild->setCampId($guild['camp_id'])->addNew($playerId, $guildId, PlayerGuild::RANK_R1);
		} else {//先进邀请表
			$PlayerGuildRequest->apply($playerId, $guildId);
            $requestNumber = count($PlayerGuildRequest->getAllGuildRequest($guildId));
            socketSend(['Type'=>'apply_guild', 'Data'=>['guild_id'=>$guildId, 'request_number'=>$requestNumber]]);
		}
		Cache::unlock($lockKey);
		echo $this->data->send($Guild->getGuildInfo($guildId));
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 同意玩家申请
	 *
	 * 使用方法如下
	 * ```php
	 * guild/agree
	 * postData: {"apply_player_id":100017}
	 * return: {PlayerGuildRequest}
	 * ```
	 */
	public function agreeAction(){
		$playerId    = $this->getCurrentPlayerId();
        $player      = $this->getCurrentPlayer();
        $campId      = $player['camp_id'];
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$postData           = getPost();
		$applyPlayerId      = $postData['apply_player_id'];
		
		$Guild              = new Guild;
		$PlayerGuild        = new PlayerGuild;
		$PlayerGuildRequest = new PlayerGuildRequest;

		$playerGuild        = $PlayerGuild->getByPlayerId($playerId);
		$guildId            = $playerGuild['guild_id'];
		$guild              = $Guild->getGuildInfo($guildId);

		//解决缓存PlayerGuildRequest问题B
		$PlayerGuildRequest->clearCache($applyPlayerId, $guildId);
		//解决缓存PlayerGuildRequest问题E

		if(empty($guild)) {
			$errCode = 10438;//不存在该联盟;
			goto sendErr;
		}
		$applyPlayerGuild   = $PlayerGuild->getByPlayerId($applyPlayerId);

		//同意条件判断
        //有阵营且阵营跟联盟不一致的
        $applyPlayer = (new Player)->getByPlayerId($applyPlayerId);
        if($applyPlayer['camp_id']!=0 && $applyPlayer['camp_id']!=$guild['camp_id']) {
            $errCode = 10776;//[同意入盟-盟主点同意]你的阵营与联盟阵营不相同
            goto sendErr;
        }
		//a 你没权限操作
		if($playerGuild['rank'] < PlayerGuild::RANK_R4){
			$errCode = 10077;
			goto sendErr;
		}
		//b 人数已满
		if($guild['num']>=$guild['max_num']){
			$errCode = 10078;
			goto sendErr;
		}
		//c 该玩家已经加入联盟
		if($applyPlayerGuild) {
			$errCode = 10079;
			goto sendErr;
		}
		
		//同意逻辑处理 
		if(!$PlayerGuildRequest->accept($applyPlayerId, $guildId, $guild['camp_id'])){//已经同意或者拒绝过
			$errCode = 10080;
			goto sendErr;
		} else {//同意处理成功后,发长连接通知该玩家
            //step b 发送swoole消息
            $data = [
                'Type'  => 'guild_accept',
                'Data'  => [
                    'nick'             => $player['nick'],
                    'guild_rank_name'  => $guild['GuildRankName'][$playerGuild['rank'] - 1],
                    'guild_name'       => $guild['name'],
                    'guild_short_name' => $guild['short_name'],
                    'to_player_id'     => $applyPlayerId,
                ]
            ];
            socketSend($data);//发送联盟帮助
            //长连接通知管理
            $requestNumber = count($PlayerGuildRequest->getAllGuildRequest($guildId));
            socketSend(['Type'=>'apply_guild', 'Data'=>['guild_id'=>$guildId, 'request_number'=>$requestNumber]]);
        }
		Cache::unlock($lockKey);

		$data = $PlayerGuildRequest->getAllGuildRequest($guildId);
		$guild = $Guild->getGuildInfo($guildId);
		echo $this->data->send(['Guild'=>$guild, 'PlayerGuildRequest'=>$data]);
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 拒绝申请加入联盟
	 * 
	 * 使用方法如下
	 * ```php
	 * guild/refuse
	 * postData: {"apply_player_id":100017}
	 * return: {PlayerGuildRequest}
	 * ```
	 */
	public function refuseAction(){
		$playerId    = $this->getCurrentPlayerId();
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		
		$player             = $this->getCurrentPlayer();
		$postData           = getPost();
		$applyPlayerId      = $postData['apply_player_id'];
		
		$Guild              = new Guild;
		$PlayerGuild        = new PlayerGuild;
		$PlayerGuildRequest = new PlayerGuildRequest;
		
		$playerGuild        = $PlayerGuild->getByPlayerId($playerId);
		$guildId            = $playerGuild['guild_id'];
		$guild              = $Guild->getGuildInfo($guildId);

		//拒绝条件判断
		//a 你没权限操作
		if($playerGuild['rank'] < PlayerGuild::RANK_R4){
			$errCode = 10081;
			goto sendErr;
		}
		
		//解决缓存PlayerGuildRequest问题B
		$PlayerGuildRequest->clearCache($applyPlayerId, $guildId);
		//解决缓存PlayerGuildRequest问题E
		
		//拒绝逻辑处理 
		if(!$PlayerGuildRequest->refuse($applyPlayerId, $guildId)){
			$errCode = 10083;
			goto sendErr;
		}
        //长连接通知管理
        $requestNumber = count($PlayerGuildRequest->getAllGuildRequest($guildId));
        socketSend(['Type'=>'apply_guild', 'Data'=>['guild_id'=>$guildId, 'request_number'=>$requestNumber]]);

		Cache::unlock($lockKey);

		$data['from_player'] = $player;
		$data['from_guild']  = $guild;

        $toPlayerIds = [$applyPlayerId];
        $type        = PlayerMail::TYPE_GUILDAPPROVAL;
        $title       = 'system email';
        $msg         = '';
        $time        = 0;
        (new PlayerMail)->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data);

		// $data = $PlayerGuildRequest->getAllGuildRequest($guildId);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 同意邀请入盟
	 *
	 * 使用方法如下
	 * ```php
	 * guild/agreeInvite
	 * postData:{"mail_id":100}
	 * return: {}
	 * ```
	 */
	public function agreeInviteAction(){
		$playerId    = $this->getCurrentPlayerId();
		//锁定
		$lockKey     = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$player      = $this->getCurrentPlayer();
		$postData    = getPost();
		$mailId      = $postData['mail_id'];
		$campId      = $player['camp_id'];
		
		$Guild       = new Guild;
		$PlayerGuild = new PlayerGuild;
		$PlayerMail  = new PlayerMail;
		
		$mailInfo    = $PlayerMail->getMailInfo($mailId);

		$execFlag    = json_decode($mailInfo['memo'], true)['exec_flag'];

		if($execFlag==1) {//已经操作过该邮件
			$errCode = 10084;
			goto sendErr;
		}
		//c 该玩家已经加入联盟
		if($player['guild_id']>0) {
			$errCode = 10079;
			goto sendErr;
		}

		$mailInfoData = json_decode($mailInfo['mail_info']['data'], true);
		$guildId = $mailInfoData['from_guild']['id'];
		$guild = $Guild->getGuildInfo($guildId);
		if(empty($guild)) {
			$errCode = 10438;//不存在该联盟;
			goto sendErr;
		}
        //有阵营且阵营跟联盟不一致的
        if($campId!=0 && $campId!=$guild['camp_id']) {
            $errCode = 10777;//[邀请入盟-邮件-点同意]你的阵营与联盟阵营不相同
            goto sendErr;
        }
		if($guild['num']>=$guild['max_num']) {
			$errCode = 10085;
			goto sendErr;
		}
		$PlayerGuild->setCampId($guild['camp_id'])->addNew($playerId, $guildId, PlayerGuild::RANK_R1);
		$PlayerMail->updateMemosByMailId($playerId, $mailId, ['exec_flag'=>1]);
		Cache::unlock($lockKey);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 随机邀请的同意操作
	 * 
	 * 使用方法如下
	 * ```php
	 * guild/agreeRandInvite
	 * postData:{"guild_id":100}
	 * return: {}
	 */
	public function agreeRandInviteAction() {
		$playerId    = $this->getCurrentPlayerId();
		$lockKey     = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;//锁定
		Cache::lock($lockKey);
		$player      = $this->getCurrentPlayer();
		$postData    = getPost();
		$guildId     = $postData['guild_id'];
		$campId      = $player['camp_id'];
		// 该玩家已经加入联盟
		if($player['guild_id']>0) {
			$errCode = 10079;
			goto sendErr;
		}

		$Guild       = new Guild;
		$PlayerGuild = new PlayerGuild;
		$PlayerBuild = new PlayerBuild;

		$fuyaBuild   = $PlayerBuild->getByOrgId($playerId, 1)[0];//获取官府id

		$guild = $Guild->getGuildInfo($guildId);
		if(empty($guild)) {
			$errCode = 10438;//不存在该联盟;
			goto sendErr;
		}
        //有阵营且阵营跟联盟不一致的
        if($campId!=0 && $campId!=$guild['camp_id']) {
            $errCode = 10778;//[随机邀请入盟-点同意后]你的阵营与联盟阵营不相同
            goto sendErr;
        }
		//人数已满
		if($guild && $guild['num']>=$guild['max_num']) {
			$errCode = 10085;
			goto sendErr;
		}
        if($fuyaBuild['build_level']<$guild['condition_fuya_level'] || $player['power']<$guild['condition_player_power']) {
        	$errCode = 10081;
        	goto sendErr;
        }
		$PlayerGuild->setCampId($guild['camp_id'])->addNew($playerId, $guildId, PlayerGuild::RANK_R1);
		Cache::unlock($lockKey);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 拒绝邀请入盟
	 *
	 * 使用方法如下
	 * ```php
	 * guild/refuseInvite
	 * postData:{"mail_id":100}
	 * return: {}
	 * ```
	 */
	public function refuseInviteAction(){
		$playerId   = $this->getCurrentPlayerId();
		//锁定
		$lockKey    = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$player     = $this->getCurrentPlayer();
		$postData   = getPost();
		$mailId     = $postData['mail_id'];
		
		$PlayerMail = new PlayerMail;
		
		$mailInfo   = $PlayerMail->getMailInfo($mailId);
		$execFlag   = json_decode($mailInfo['memo'], true)['exec_flag'];

		if($execFlag==1) {//已经操作过该邮件
			$errCode = 10086;
			goto sendErr;
		}
		$PlayerMail->updateMemosByMailId($playerId, $mailId, ['exec_flag'=>1]);
		Cache::unlock($lockKey);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 查看当前联盟
	 *
	 * 使用方法如下
	 * ```php
	 * guild/viewGuildInfo
	 * postData: {"guild_id":1}
	 * return: {[Guild]|[]}
	 * ```
	 */
	public function viewGuildInfoAction(){
		$playerId = $this->getCurrentPlayerId();
		$player   = $this->getCurrentPlayer();
		$guildId  = $player['guild_id'];
		$postData = getPost();
		if(!empty($postData['guild_id'])) {
			$guildId = $postData['guild_id'];
		}
		$data     = [];
		//表示已经有联盟了
		if($guildId) {
			$data = $guild = (new Guild)->getGuildInfo($guildId);
            $data['current_season_start_date'] = (new CityBattleRound)->getCurrentSeasonStartDate();
		}
		echo $this->data->send($data);
		exit;
	}
	/**
	 * 搜索联盟
	 *
	 * 使用方法如下
	 * ```php
	 * postData中的need_check有三个值 -1:任意 1：需要 0：不需要
	 * 
	 * guild/searchGuild
	 * postData: {"name":"aa","num":30,"condition_fuya_level":3,"condition_player_power":100,"need_check":-1,"from_page":0,"num_per_page":10}
	 * return {PlayerGuild}
	 * ```
	 */
	public function searchGuildAction(){
		$playerId                                = $this->getCurrentPlayerId();
		$player                                  = $this->getCurrentPlayer();
		$postData                                = getPost();
		
		$searchData['name']                      = $postData['name'];
		$searchData['num']                       = $postData['num'];
		$searchData['condition_fuya_level']      = $postData['condition_fuya_level'];
		$searchData['guild_power']               = $postData['condition_player_power'];
		// $searchData['condition_player_power'] = $postData['condition_player_power'];
		$searchData['need_check']                = $postData['need_check'];
		$searchData['from_page']                 = $postData['from_page'];
		$searchData['num_per_page']              = $postData['num_per_page'];
		
		$SensitiveWord                           = new SensitiveWord;
		$Guild                                   = new Guild;

		//a 搜索关键字有敏感字
		if($SensitiveWord->checkSensitiveContent($postData['name'])) {
			$errCode = 10087;
			goto sendErr;
		}
		$data = $Guild->search($searchData);
		echo $this->data->send($data);
		exit;
		sendErr: {
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 邀请入盟
	 *
	 * 使用方法如下
	 * ```php
	 * guild/inviteGuild
	 * postData:{"invite_player_id":100017, "guild_id":7}
	 * return: {}
	 * ```
	 * @return [type] [description]
	 */
	public function inviteGuildAction(){
		$playerId = $this->getCurrentPlayerId();
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$player         = $this->getCurrentPlayer();
		$postData       = getPost();
		$invitePlayerId = $postData['invite_player_id'];
		$guildId        = $player['guild_id'];
		//自己没有盟
		if(!$guildId) {
			$errCode = 10088;
			goto sendErr;
		}

		$Guild        = new Guild;
		$PlayerGuild  = new PlayerGuild;
		$Player       = new Player;
		
		$guild        = $Guild->getGuildInfo($guildId);
		$invitePlayer = $Player->getByPlayerId($invitePlayerId);
		$playerGuild  = $PlayerGuild->getByPlayerId($playerId);
		//a 权限不足
		if($playerGuild['rank']<PlayerGuild::RANK_R4) {
			$errCode = 10089;
			goto sendErr;
		}
		//b 人数已满
		if($guild['num']>=$guild['max_num']) {
			$errCode = 10090;
			goto sendErr;
		}
		//c 对方玩家已经加联盟了
		if($invitePlayer['guild_id']) {
			$errCode = 10091;
			goto sendErr;
		}
		if($invitePlayer['camp_id']!=0 && $invitePlayer['camp_id']!=$guild['camp_id']) {
		    $errCode = 10779;//[邀请入盟]所属不同阵营无法发送
		    goto sendErr;
        }
		//处理邀请逻辑
		//邮件内容
        Cache::unlock($lockKey);
		$data['from_player']  = $player;
		$data['from_guild']   = $guild;
		$leader               = $Player->getByPlayerId($guild['leader_player_id']);
		$data['guild_leader'] = $leader;

        $toPlayerIds = [$invitePlayerId];
        $type        = PlayerMail::TYPE_GUILDINVITE;
        $title       = 'system email';
        $msg         = '';
        $time        = 0;
        $memo 		 = ['exec_flag'=>0];
        (new PlayerMail)->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data, [], $memo);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 修改联盟
	 *
	 * 使用方法如下
	 * 
	 * ```php
	 * guild/alterGuild
	 * postData:{"type":1}
	 * return: {}
	 * ```
	 * <pre>
	 * - "type":1 #宣言
	 * - "desc":"修改宣言"
	 *
	 * - "type":2 #招募条件
	 * - "need_check":1,"condition_fuya_level":10,"condition_player_power":1000 
	 * 
	 * - "type":3  #联盟名称
	 * - "name":"修改名称"
	 *
	 * - "type":4 #联盟图标
	 * - "icon_id":4 #联盟图标
	 *
	 * - "type":5 #联盟公告
	 * - "notice": "修改联盟公告"
	 *
	 * - "type":6 #短名称
	 * - "short_name":"AbC"
	 * </pre>
	 */
	public function alterGuildAction(){
		$playerId = $this->getCurrentPlayerId();
		//锁定
		$lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$player   = $this->getCurrentPlayer();
		$guildId  = $player['guild_id'];
		//自己没有盟
		if(!$guildId) {
			$errCode = 10092;
			goto sendErr;
		}
		$postData      = getPost();
		$type          = $postData['type'];
		$Guild         = new Guild;
		$PlayerGuild   = new PlayerGuild;
		$SensitiveWord = new SensitiveWord;
		$Cost          = new Cost;

		$playerGuild = $PlayerGuild->getByPlayerId($playerId);
		switch($type) {
			case 1://联盟宣言
				$desc = $postData['desc'];
				//宣言非法
				if($SensitiveWord->checkSensitiveContent($desc)) {
					$errCode = 10093;
					goto sendErr;
				}
				// 你没权限操作
				if($playerGuild['rank'] < PlayerGuild::RANK_R4){
					$errCode = 10094;
					goto sendErr;
				}
//				$Guild->alter($guildId, ['desc'=>q($desc)]);//修改截取长度
                $g = Guild::findFirst($guildId);
                if($g) {
                    $g->desc = addslashes($desc);
                    $g->save();
                    $Guild->clearGuildCache($guildId);
                }
				break;
			case 2://招募条件
				$needCheck            = $postData['need_check'];
				$conditionFuyaLevel   = $postData['condition_fuya_level'];
				$conditionPlayerPower = $postData['condition_player_power'];
				// 你没权限操作
				if($playerGuild['rank'] < PlayerGuild::RANK_R4){
					$errCode = 10095;
					goto sendErr;
				}
				$Guild->alter($guildId, [
					'need_check'             => $needCheck,
					'condition_fuya_level'   => $conditionFuyaLevel,
					'condition_player_power' => $conditionPlayerPower,
					]);
				break;
			case 3://联盟名称
				$name = $postData['name'];
				//宣言非法
				if(strlen($name)<1 && $SensitiveWord->checkSensitiveContent($name)) {
					$errCode = 10096;
					goto sendErr;
				}
				// 你没权限操作
				if($playerGuild['rank'] < PlayerGuild::RANK_R5){
					$errCode = 10097;
					goto sendErr;
				}
				//联盟名称是否重复
				if($Guild->checkNameExists($name)) {
					$errCode = 10098;
					goto sendErr;
				}
				//宝石不足
				//500元宝是否满足-修改联盟名称
				if(!$Cost->updatePlayer($playerId, 106)){//gem不足
					$errCode = 10099;
					goto sendErr;
				}
//				$Guild->alter($guildId, ['name'=>q($name)]);// 修改截取长度
                //save guild name
                $g = Guild::findFirst($guildId);
                if($g) {
                    $g->name = addslashes($name);
                    $g->save();
                    $Guild->clearGuildCache($guildId);
                }
				break;
			case 4://修改联盟图标
				$iconId = $postData['icon_id'];
				// 你没权限操作
				if($playerGuild['rank'] < PlayerGuild::RANK_R5){
					$errCode = 10100;
					goto sendErr;
				}
				//元宝是否满足-修改联盟图标
				if(!$Cost->updatePlayer($playerId, 107)){//gem不足
					$errCode = 10101;
					goto sendErr;
				}
				$Guild->alter($guildId, ['icon_id'=>$iconId]);
				break;
			case 5://修改联盟公告
				$notice = $postData['notice'];
				//联盟公告非法
				if($SensitiveWord->checkSensitiveContent($notice)) {
					$errCode = 10102;
					goto sendErr;
				}
				// 你没权限操作
				if($playerGuild['rank'] < PlayerGuild::RANK_R4){
					$errCode = 10103;
					goto sendErr;
				}
//				$Guild->alter($guildId, ['notice'=>q($notice)]);// 修改截取长度
                //save guild notice
                $g = Guild::findFirst($guildId);
                if($g) {
                    $g->notice = addslashes($notice);
                    $g->save();
                    $Guild->clearGuildCache($guildId);
                }
				break;
			case 6://修改短名字
				$shortName = $postData['short_name'];
				//短名字含敏感字
				if(strlen($shortName)<1 && $SensitiveWord->checkSensitiveContent($shortName, 2)) {
					$errCode = 10104;
					goto sendErr;
				}
				// 你没权限操作
				if($playerGuild['rank'] < PlayerGuild::RANK_R5){
					$errCode = 10105;
					goto sendErr;
				}
				//联盟名称是否重复
				if($Guild->checkShortNameExists($shortName)) {
					$errCode = 10106;
					goto sendErr;
				}
				//宝石不足
				//500元宝是否满足-修改联盟名称
				if(!$Cost->updatePlayer($playerId, 106)){//gem不足
					$errCode = 10107;
					goto sendErr;
				}
//				$Guild->alter($guildId, ['short_name'=>q($shortName)]);// 修改截取长度
                $g = Guild::findFirst($guildId);
                if($g) {
                    $g->short_name = addslashes($shortName);
                    $g->save();
                    $Guild->clearGuildCache($guildId);
                }
				break;
			default:
				$errCode = 1;
				goto sendErr;
		}
		Cache::unlock($lockKey);
		$data = $Guild->getGuildInfo($guildId);
		echo $this->data->send($data);
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}

    /**
     * 盟主，副盟主，踢驻守部队
     *
     * ```
     *  guild/kickDefendArmyFromGuildBase
     *  postData: {'ppq_id'}
     * ```
     */
	public function kickDefendArmyFromGuildBaseAction(){
        $playerId = $this->getCurrentPlayerId();
        //锁定
        $lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
        Cache::lock($lockKey);
        $player   = $this->getCurrentPlayer();

        $PlayerGuild = new PlayerGuild;
        $guildId = $player['guild_id'];
        if($guildId) {
            $playerGuild = $PlayerGuild->getByPlayerId($playerId);
            if($playerGuild['rank']>=PlayerGuild::RANK_R4) {
                $postData           = getPost();
                $ppqId              = $postData['ppq_id'];
                $PlayerProjectQueue = new PlayerProjectQueue;
                $ppq                = $PlayerProjectQueue->getDefendArmyFromGuildBase($ppqId);
                if($ppq) {
                    $targetPlayerId    = $ppq['player_id'];
                    $targetPlayerGuild = $PlayerGuild->getByPlayerId($targetPlayerId);
                    $targetGuildId     = $ppq['guild_id'];
                    if ($targetGuildId == $guildId) {
                        if ($playerGuild['rank'] < $targetPlayerGuild['rank']) {
                            $errCode = 10493;//[踢回驻守部队]对方权限高于你
                            goto sendErr;
                        }
                        $PlayerProjectQueue->updateAll(['end_time' => qd(), 'rowversion' => 'rowversion*1+1'], ['id' => $ppqId]);
                    } else {
                        $errCode = 10494;//[踢回驻守部队]权限不足
                        goto sendErr;
                    }
                }

            } else {
                $errCode = 10495;//[踢回驻守部队]权限不足
                goto sendErr;
            }
        } else {
            $errCode = 10061;
            goto sendErr;
        }

        Cache::unlock($lockKey);
        echo $this->data->send();
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }
	/**
	 * 建造联盟建筑
	 *
	 * 使用方法如下
	 * ```php
	 * guild/gotoGuildBuild
	 * postData: {"x":100,"y":100,"army_id":74,"useMove":1}
	 * return: {}
	 * ```
	 */
	public function gotoGuildBuildAction(){
		$playerId = $this->getCurrentPlayerId();
		//锁定
		$lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$player   = $this->getCurrentPlayer();
		$postData = getPost();
		$x        = $postData['x'];
		$y        = $postData['y'];
		$armyId   = $postData['army_id'];
		$db                 = $this->di['db'];
        dbBegin($db);

		$Map                = new Map;
		$MapElement         = new MapElement;
		$PlayerProjectQueue = new PlayerProjectQueue;

		$map = $Map->getByXy($x, $y);
		//目标地图元素不存在
		if(!$map) {
			$errCode = 10263;//目标地图元素不存在
			goto sendErr;
		}
		//不是本盟的联盟建筑
		if($map['guild_id']!=$player['guild_id']) {
			$errCode = 10264;//不是本盟的联盟建筑
			goto sendErr;
		}
		$mapElement = $MapElement->dicGetOne($map['map_element_id']);
		$ppq = $PlayerProjectQueue->getConstructGuildBuild($map);
		if(count($ppq)>=$mapElement['max_construction']) {//超过最大建造数
			$errCode = 10265;//超过最大建造数
			goto sendErr;
		}
		$currentPPQ = $PlayerProjectQueue->getByPlayerId($playerId);
		try{
            $Map->doBeforeGoOut($playerId, $armyId, false, ['ppq'=>$currentPPQ]);
        } catch (Exception $e) {
            list($errCode, $msg) = parseException($e);
            goto sendErr;
        }

		$typeArr = [101=>PlayerProjectQueue::TYPE_GUILDBASE_GOTO,201=>PlayerProjectQueue::TYPE_GUILDTOWER_GOTO,801=>PlayerProjectQueue::TYPE_GUILDWAREHOUSE_GOTO];
        if(in_array($map['map_element_id'],[301,401,501,601,701])) {
            $type = PlayerProjectQueue::TYPE_GUILDCOLLECT_GOTO;
        } else {
            $type = $typeArr[$map['map_element_id']];
        }
        if(isset($postData['useMove'])) {
	        $useMove = $postData['useMove'];
        } else {
        	$useMove = false;
        }
        if($useMove){
            try{
                $MapController = new MapController;
                $distance = sqrt(pow($player['x'] - $map['x'], 2) + pow($player['y'] - $map['y'], 2));
                $MapController->useHpMove($player, $distance);
                $needTime = MapController::EXTRASEC;
            } catch(Exception $e) {
                list($errCode, $msg) = parseException($e);
                goto sendErr;
            }
        }else{
        	$needTime = PlayerProjectQueue::calculateMoveTime($playerId, $player['x'], $player['y'], $map['x'], $map['y'], 3, $armyId);
        }

        
        $extraData                = [];
        $extraData['from_map_id'] = $player['map_id'];
        $extraData['from_x']      = $player['x'];
        $extraData['from_y']      = $player['y'];
        $extraData['to_map_id']   = $map['id'];
        $extraData['to_x']        = $map['x'];
        $extraData['to_y']        = $map['y'];
        $PlayerProjectQueue->addQueue($playerId, $player['guild_id'], 0, $type, $needTime, $armyId, [], $extraData);


		$data = [];//$map;
		dbCommit($db);
		Cache::unlock($lockKey);
		echo $this->data->send($data);
		exit;
		sendErr: {
			dbRollback($db);
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 解散联盟
	 *
	 * 使用方法如下
	 * ```php
	 * guild/dismissGuild
	 * postData: {}
	 * return: {}
	 * ```
	 */
	public function dismissGuildAction(){
		$playerId = $this->getCurrentPlayerId();
		$lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		$player   = $this->getCurrentPlayer();
		$guildId  = $player['guild_id'];
		//自己没有盟
		if(!$guildId) {
			$errCode = 10108;
			goto sendErr;
		}

        $CityBattleRound = new CityBattleRound;
        $roundStatus     = $CityBattleRound->getCurrentRoundStatus();
        $battleStatusArr = [
            CityBattleRound::SIGN_FIRST,
            CityBattleRound::SIGN_NORMAL,
            CityBattleRound::SELECT_PLAYER,
            CityBattleRound::SELECT_PLAYER_FINISH,
            CityBattleRound::DOING,
            CityBattleRound::CLAC_REWARD
        ];
        if($roundStatus !== false && in_array($roundStatus, $battleStatusArr)) {
            $errCode = 10780;//[解散联盟]城战过程中不能解散联盟
            goto sendErr;
        }

		$ActivityConfigure = new ActivityConfigure;
		$re = $ActivityConfigure->getCurrentActivity(1003);

		if(!empty($re)){
			$errCode = 10455;//联盟活动进行时不能解散联盟
			goto sendErr;
		}

		$King = new King;
		$kingBattle = $King->getCurrentBattle();
		$kingBattle2 = $King->getNeedRewardBattle();

		if(!empty($kingBattle) || !empty($kingBattle2)){
			$errCode = 10490;//国王战过程中不能解散联盟
			goto sendErr;
		}

        if((new CrossGuildInfo)->isJoined($guildId)){
            $errCode = 10630;//跨服战中不能解散公会
            goto sendErr;
        }



		$king = $King->findFirst(['order'=>'id desc']);
		if($king->guild_id==$guildId) {
            $errCode = 10491;//国王战胜利公会不能解散
            goto sendErr;
        }

		$Map                = new Map;
		$Guild              = new Guild;
		$PlayerGuild        = new PlayerGuild;
		$PlayerProjectQueue = new PlayerProjectQueue;
		$playerGuild        = $PlayerGuild->getByPlayerId($playerId);
		//权限不足
		if($playerGuild['rank']<PlayerGuild::RANK_R5) {
			$errCode = 10109;
			goto sendErr;
		}
		//解散联盟逻辑
		//case : 删联盟表记录 guild
		$Guild->dismissGuild($guildId);
		//case : 归还资源 
		$PlayerProjectQueue->callbackGuildQueue($guildId);
		$PlayerGuild->takeOutAllResource($guildId);
		//case : 删联盟成员表记录 player_guild
		//case : 更改player表的记录 guild_id
		$PlayerGuild->dismissPlayerGuild($guildId);
		//case : 拆联盟建筑  
		$mapElementList = $Map->getAllByGuildId($guildId);
		foreach($mapElementList as $key=>$value){
		    if(in_array($value['map_element_origin_id'], [1,2,3,4,5,6,7,8])) {
		    	$Map->delMap($value['id']);
		    }
		}
		
		//删除科技
		(new GuildScience)->find(['guild_id='.$guildId])->delete();
		(new GuildBuff)->find(['guild_id='.$guildId])->delete();
		
		Cache::unlock($lockKey);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}
	/**
	 * 拆除单个联盟建筑
	 *
	 * 使用方法如下
	 * ```php
	 * guild/dismissSingleGuildBuild
	 * postData: {"x":123,"y":456}
	 * return: {}
	 * ```
	 */
	public function dismissSingleGuildBuildAction(){
        $playerId           = $this->getCurrentPlayerId();
        $postData           = getPost();
        $x                  = $postData['x'];
        $y                  = $postData['y'];
        $lockKey            = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId . ':x='.$x.':y='.$y;//同个建筑锁x，y
        Cache::lock($lockKey);//锁定
        $player             = $this->getCurrentPlayer();
        $guildId            = $player['guild_id'];


        $Map                = new Map;
		$PlayerGuild        = new PlayerGuild;
		$PlayerProjectQueue = new PlayerProjectQueue;

		$playerGuild = $PlayerGuild->getByPlayerId($playerId);
        //联盟建筑，权限不足
         if($playerGuild['rank']<PlayerGuild::RANK_R4) {
            $errCode = 10063;
            goto sendErr;
         }

        $map = $Map->getByXy($x, $y);
        if(!$map) {
	    	$errCode = 10312;//不存在目标建筑
	    	goto sendErr;
	    }

        switch($map['map_element_id']) {
        	case 101://堡垒
        		$PlayerProjectQueue->callbackGuildQueue($guildId, 1, $map['id']);
        		if($map['status']==1 && $Map->getGuildMapElementNum($guildId, 101)==1) {//最后一个堡垒
        			$PlayerProjectQueue->callbackGuildQueue($guildId, 2);
					$PlayerProjectQueue->callbackGuildQueue($guildId, 3);
					$PlayerProjectQueue->callbackGuildQueue($guildId, 4);
					$mapElementList = $Map->getAllByGuildId($guildId);
					$PlayerGuild->takeOutAllResource($guildId);
					foreach($mapElementList as $key=>$value){
                        if(in_array($value['map_element_origin_id'], [2,3,4,5,6,7,8])){//不是玩家城堡的联盟建筑
							$Map->delMap($value['id']);
						}
					}
	        	}
	        	$Map->delMap($map['id']);
        		break;
        	case 201://箭塔
        		$PlayerProjectQueue->callbackGuildQueue($guildId, 4, $map['id']);
        		$Map->delMap($map['id']);
        		break;
        	case 801://仓库
        		$PlayerProjectQueue->callbackGuildQueue($guildId, 3, $map['id']);
        		$PlayerGuild->takeOutAllResource($guildId);//归还资源
        		$Map->delMap($map['id']);
        		break;
        	case 301:
        	case 401:
        	case 501:
        	case 601:
        	case 701:
        		$PlayerProjectQueue->callbackGuildQueue($guildId, 2, $map['id']);
				//等待采集部队全部返回
				$i = 0;
				$flag = false;
				while($i < 10){
					if(!$PlayerProjectQueue->find(['type='.PlayerProjectQueue::TYPE_GUILDCOLLECT_ING.' and to_map_id='.$map['id'].' and status=1'])->toArray()){
						$flag = true;
						break;
					}
					sleep(1);
					$i++;
				}
				if(!$flag){
					$errCode = 10352;//等待部队返回超时
					goto sendErr;
				}
        		$Map->delMap($map['id']);
        		break;
        	default:
        		$errCode = 10313;//拆除单个联盟建筑-不是联盟建筑.
        		goto sendErr;
        }

        Cache::unlock($lockKey);
        //联盟聊天推送
        $pushData = [
            'type'           => 15,
            'map_element_id' => $map['map_element_id'],
            'x'              => $x,
            'y'              => $y,
            'nick'           => $player['nick'],
        ];
        $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
        socketSend($data);
        echo $this->data->send();
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

	/**
     * 联盟商店进货
     * 
     * 
     * @return <type>
     */
	public function shopStockAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		$itemNum = floor(@$post['itemNum']);
		if(!checkRegularNumber($itemId) || !checkRegularNumber($itemNum))
			exit;
		
		$PlayerGuild = new PlayerGuild;
		$playerGuild = $PlayerGuild->getByPlayerId($playerId);
		if(!$playerGuild)
			return false;
		$guildId = $playerGuild['guild_id'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':guildId=' .$guildId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查道具是否属于商店列表
			$AllianceShop = new AllianceShop;
			$allianceShop = $AllianceShop->dicGetOne($itemId);
			if(!$allianceShop){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取公会个人记录
			$PlayerGuild = new PlayerGuild;
			$playerGuild = $PlayerGuild->getByPlayerId($playerId);
			if(!$playerGuild)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
		
			//检查公会权限
			if($playerGuild['rank'] < 4){
				throw new Exception(10110);
			}
			
			//扣除公会积分
			/*$Guild = new Guild;
			if(!$Guild->updateCoin($guildId, -$allianceShop['alliance_price']*$itemNum)){
				throw new Exception(10111);
			}*/
			$Cost = new Cost;
			if(!$Cost->updatePlayer($playerId, $allianceShop['alliance_cost'], 0, $itemNum)){
				throw new Exception(10428);//联盟荣誉不足
			}
			
			//增加道具
			$GuildShop = new GuildShop;
			if(!$GuildShop->add($guildId, $itemId, $itemNum)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new GuildShopLog)->add($guildId, 1, $playerId, $player['nick'], $itemId, $itemNum);
			
			$userData = [
			    'type'    => 10,//联盟商店-买东西
                'nick'    => $player['nick'],
                'itemId'  => $itemId,
                'itemNum' => $itemNum,
			];
			$data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$userData]];
			socketSend($data);
				
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 联盟商店日志
     * 
     * 
     * @return <type>
     */
	public function shopLogAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$type = floor(@$post['type']);
		if(!in_array($type, [1, 2]))
			exit;
		
		$data = (new GuildShopLog)->find(['columns'=>['nick', 'item_id', 'num'], 'guild_id='.$player['guild_id'].' and type='.$type, 'order'=>'id desc', 'limit'=>10])->toArray();
		
		echo $this->data->send(['log'=>$data]);
	}
	
	/**
     * 联盟商店购买
     * 
     * 
     * @return <type>
     */
	public function shopBuyAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$itemId = floor(@$post['itemId']);
		$itemNum = floor(@$post['itemNum']);
		if(!checkRegularNumber($itemId) || !checkRegularNumber($itemNum))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查道具是否属于商店列表
			$AllianceShop = new AllianceShop;
			$allianceShop = $AllianceShop->dicGetOne($itemId);
			if(!$allianceShop){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取公会个人记录
			$PlayerGuild = new PlayerGuild;
			$playerGuild = $PlayerGuild->getByPlayerId($playerId);
			if(!$playerGuild)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			$guildId = $playerGuild['guild_id'];
			
			//扣除个人积分
			/*$needCoin = $allianceShop['player_price'] * $itemNum;
			$Player = new Player;
			if(!$Player->hasEnoughResource($playerId, array('guild_coin'=>$needCoin))){
				throw new Exception(10112);
			}
			if(!$Player->updateResource($playerId, array('guild_coin'=>-$needCoin))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}*/
			$Cost = new Cost;
			if(!$Cost->updatePlayer($playerId, $allianceShop['player_cost'], 0, $itemNum)){
				throw new Exception(10113);
			}
			
			//扣除商店道具
			$GuildShop = new GuildShop;
			if(!$GuildShop->drop($guildId, $itemId, $itemNum)){
				throw new Exception(10114);
			}
			
			//增加个人道具
			$PlayerItem = new PlayerItem;
			if(!$PlayerItem->add($playerId, $itemId, $itemNum)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerMission)->updateMissionNumber($playerId, 19, $itemNum);
				
			(new GuildShopLog)->add($guildId, 2, $playerId, $player['nick'], $itemId, $itemNum);	
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 捐献推荐
     * 
     * 
     * @return <type>
     */
	public function donateRecommendAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$scienceType = floor(@$post['scienceType']);
		if(!checkRegularNumber($scienceType))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			//检查是否为盟主
			if($playerId != (new Guild)->getGuildInfo($player['guild_id'])['leader_player_id']){
				throw new Exception(10496);//只有盟主可以指定推荐科技捐献
			}
			
			//检查scienceType是否存在
			if(!(new AllianceScience)->getByScienceType($scienceType)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新
			$Guild = new Guild;
			$Guild->updateAll(['science_type'=>$scienceType], ['id'=>$player['guild_id']]);
			$Guild->clearGuildCache($player['guild_id']);
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 获得捐赠信息
     * 
     * 
     * @return <type>
     */
	public function getDonateAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$scienceType = floor(@$post['scienceType']);
		if(!checkRegularNumber($scienceType))
			exit;
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		$PlayerGuildDonate = new PlayerGuildDonate;
		$PlayerGuildDonateButton = new PlayerGuildDonateButton;
		
		try {
			$retData = array();
			$retData2 = array();

			$guildId = $player['guild_id'];
			if(!$guildId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//刷新科技进阶
			$GuildScience = new GuildScience;
			$GuildScience->levelupFinish($guildId);
			
			//获取公会科技
			$guildScience = $GuildScience->getByscienceType($playerId, $scienceType, true);
			if(!$guildScience){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			/*$retData['science_level'] = $guildScience['science_level'];
			$retData['science_exp'] = $guildScience['science_exp'];
			$retData['finish_time'] = $guildScience['finish_time'];
			$retData['status'] = $guildScience['status'];
			$retData['button'] = array();
			*/
			
			//获取科技列表
			$AllianceScience = new AllianceScience;
			$allianceScience = $AllianceScience->getByScienceType($scienceType, $guildScience['science_level']+1);
			if(!$allianceScience){
				$allianceScience = $AllianceScience->getByScienceType($scienceType, $guildScience['science_level']);
				if(!$allianceScience){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			
			//检查层级
			if(!$GuildScience->checkLevelType($guildId, $allianceScience['level_type'], $allianceScience['open_task'])){
				throw new Exception(10115);
			}
			
			//检查科技是否master
			if($guildScience['science_level'] >= $allianceScience['max_level']){
				
			}else{
				
				//判断是否可被捐献
				if($guildScience['status']){
					
				}else{
					//获取玩家捐献数据
					$pgd = $PlayerGuildDonate->getByPlayerId($playerId);
					if(!$pgd){
						if(!$PlayerGuildDonate->add($playerId, array(), 0, '0000-00-00 00:00:00')){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						$pgd = $PlayerGuildDonate->getByPlayerId($playerId);
					}
					//判断等级是否相同
					$level=$guildScience['science_level']+1;
					
					//获取玩家捐献按钮数据
					$pgdb = $PlayerGuildDonateButton->getByScienceType($playerId, $scienceType);
					if(!$pgdb){
						if(!$PlayerGuildDonateButton->add($playerId, $scienceType)){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
						$pgdb = $PlayerGuildDonateButton->getByScienceType($playerId, $scienceType);
					}
					
					//如果等级不同，刷新按钮
					if($pgdb['level'] != $level){
						$button = $pgdb;
						$button['level'] = $level;
						$button = $PlayerGuildDonateButton->randBtn($button, $scienceType);
						if(!$PlayerGuildDonateButton->assign($pgdb)->updateData($level, $button['btn1_cost'], $button['btn1_unit'], $button['btn1_num'], $button['btn2_cost'], $button['btn2_unit'], $button['btn2_num'], $button['btn2_counter'], $button['btn3_cost'], $button['btn3_unit'], $button['btn3_num'], $button['btn3_counter'])){
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
						}
					}
					
				}
			}
			$retData = $PlayerGuildDonate->getByPlayerId($playerId, true);
			$retData2 = $PlayerGuildDonateButton->getByScienceType($playerId, $scienceType, true);
			//$retData['button'] = $retData['button'][$scienceType];
				
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			$this->data->filterBasic(array('PlayerGuildDonate', 'GuildScience', 'PlayerGuildDonateButton'), true);
			echo $this->data->send(array('GuildScience'=>$guildScience, 'PlayerGuildDonate'=>$retData, 'PlayerGuildDonateButton'=>$retData2));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 科技捐献
     * 
     * 
     * @return <type>
     */
	public function scienceDonateAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$scienceType = floor(@$post['scienceType']);
		$btn = floor(@$post['btn']);
		if(!checkRegularNumber($scienceType) || !checkRegularNumber($btn))
			exit;
		if(!in_array($btn, array(1, 2, 3)))
			exit;
		
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':guildId=' .$player['guild_id'];
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$guildId = $player['guild_id'];
			if(!$guildId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//刷新科技进阶
			$GuildScience = new GuildScience;
			$GuildScience->levelupFinish($guildId);
			
			//获取入盟时间todo
			
			//获取公会科技
			$guildScience = $GuildScience->getByscienceType($playerId, $scienceType);
			if(!$guildScience){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取科技
			$AllianceScience = new AllianceScience;
			$allianceScience = $AllianceScience->getByScienceType($scienceType, $guildScience['science_level']+1);
			if(!$allianceScience){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查科技是否master
			if($guildScience['science_level'] >= $allianceScience['max_level']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断是否可被捐献
			if($guildScience['status']){
				throw new Exception(10475);//当前状态不能捐献
			}
			
			//检查层级
			if(!$GuildScience->checkLevelType($guildId, $allianceScience['level_type'], $allianceScience['open_task'])){
				throw new Exception(10116);
			}
			
			//获取玩家捐献数据
			$PlayerGuildDonate = new PlayerGuildDonate;
			$pgd = $PlayerGuildDonate->getByPlayerId($playerId);
			if(!$pgd){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			//$button = $pgd['button'];
			
			//检查cd
			if($pgd['status']){
				throw new Exception(10117);
			}
			
			$level=$guildScience['science_level']+1;
			
			//获取玩家捐献按钮数据
			$PlayerGuildDonateButton = new PlayerGuildDonateButton;
			$pgdb = $PlayerGuildDonateButton->getByScienceType($playerId, $scienceType);
			if(!$pgdb){
				if(!$PlayerGuildDonateButton->add($playerId, $scienceType)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$pgdb = $PlayerGuildDonateButton->getByScienceType($playerId, $scienceType);
			}
			
			//如果等级不同，刷新按钮
			$button = $pgdb;
			if($pgdb['level'] != $level){
				$button['level'] = $level;
				$button = $PlayerGuildDonateButton->randBtn($button, $scienceType);
				if(!$PlayerGuildDonateButton->assign($pgdb)->updateData($level, $button['btn1_cost'], $button['btn1_unit'], $button['btn1_num'], $button['btn2_cost'], $button['btn2_unit'], $button['btn2_num'], $button['btn2_counter'], $button['btn3_cost'], $button['btn3_unit'], $button['btn3_num'], $button['btn3_counter'])){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$pgdb = $PlayerGuildDonateButton->getByScienceType($playerId, $scienceType);
			}

			//判断按钮开启
			if(!$button['btn'.$btn.'_unit']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			$Cost = new Cost;
			if(!$Cost->updatePlayer($playerId, $button['btn'.$btn.'_cost'])){
				throw new Exception(10118);
			}
			
			//增加捐献度
			$Drop = new Drop;
			$dropItems = $Drop->gain($playerId, array($allianceScience['button'.$btn.'_drop']), 1);
			if(!$dropItems){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//增加研究值
			if(!$GuildScience->assign($guildScience)->addExp($allianceScience['button'.$btn.'_exp'])){
				throw new Exception(10374);//CLICK TOO QUICK
			}
			
			//增加联盟荣誉
			$Guild = new Guild;
			if(!$Guild->updateCoin($guildId, $allianceScience['button'.$btn.'_honor'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			(new PlayerMission)->updateMissionNumber($playerId, 12, $allianceScience['button'.$btn.'_honor']);
			
			//计算按钮刷新
			if($btn > 1){
				$button['btn'.$btn.'_cost'] = 0;
				$button['btn'.$btn.'_unit'] = 0;
				$button['btn'.$btn.'_num'] = 0;
				$button['btn'.$btn.'_counter'] = 0;
			}
			$i = 2;
			while($i <= 3){
				if($button['btn'.$i.'_unit'] == 7){//游戏币
					$button['btn'.$i.'_counter']--;
					if(!$button['btn'.$i.'_counter']){
						$button['btn'.$i.'_cost'] = 0;
						$button['btn'.$i.'_unit'] = 0;
						$button['btn'.$i.'_num'] = 0;
					}
				}
				$i++;
			}
			$button = $PlayerGuildDonateButton->randBtn($button, $scienceType);
			
			//更新个人捐献数据
			$ts = $pgd['finish_time'];
			$now = time();
			if($ts < $now){
				$ts = $now;
			}
			
			//计算捐赠增加cd
			$adt = (new Starting)->dicGetOne('alliance_donate_time');
			$adc = (new Starting)->dicGetOne('alliance_donate_cd');
			$pgdFinishTime = $ts + $adt;
			if($pgdFinishTime - $now >= $adc){
				$pgdStatus = 1;
			}else{
				$pgdStatus = 0;
			}
			if(!$PlayerGuildDonate->assign($pgd)->updateData($pgdStatus, date('Y-m-d H:i:s', $pgdFinishTime))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if(!$PlayerGuildDonateButton->assign($pgdb)->updateData($level, $button['btn1_cost'], $button['btn1_unit'], $button['btn1_num'], $button['btn2_cost'], $button['btn2_unit'], $button['btn2_num'], $button['btn2_counter'], $button['btn3_cost'], $button['btn3_unit'], $button['btn3_num'], $button['btn3_counter'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新统计
			$statExp = $allianceScience['button'.$btn.'_exp'];
			$statCoin = $allianceScience['button'.$btn.'_honor'];
			$PlayerGuildDonateStat = new PlayerGuildDonateStat;
			$PlayerGuildDonateStat->updateData($playerId, $statCoin, $statExp);
				
			//活动积分
			if(!(new Activity)->addGuildMissionScore($playerId, $allianceScience['button'.$btn.'_honor'])){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			(new PlayerTarget)->updateTargetCurrentValue($playerId, 21, 1);
			
			//更新公会捐献人数
			$today = date('Y-m-d');
			if($pgd['last_donate_time'] != $today){
				$PlayerGuildDonate->updateDonateTime($playerId, $today);
				$Guild->addDonateCount($guildId, $today, 1);
			}
			
				
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			$pgd = $PlayerGuildDonate->getByPlayerId($playerId, true);
			$pgdb = $PlayerGuildDonateButton->getByScienceType($playerId, $scienceType, true);
			$guildScience = $GuildScience->getByscienceType($playerId, $scienceType, true);
			$this->data->filterBasic(array('PlayerGuildDonate', 'GuildScience', 'PlayerGuildDonateButton', 'PlayerGuildDonateStat'), true);
			echo $this->data->send(array('GuildScience'=>$guildScience, 'PlayerGuildDonate'=>$pgd, 'PlayerGuildDonateButton'=>$pgdb));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 联盟科技进阶开始
     * 
     * 
     * @return <type>
     */
	public function scienceUpAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$scienceType = floor(@$post['scienceType']);
		if(!checkRegularNumber($scienceType))
			exit;
		
		$PlayerGuild = new PlayerGuild;
		$playerGuild = $PlayerGuild->getByPlayerId($playerId);
		if(!$playerGuild)
			return false;
		$guildId = $playerGuild['guild_id'];
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':guildId=' .$guildId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			
			//检查权限
			if($playerGuild['rank'] < 4){
				throw new Exception(10119);
			}
			
			//刷新科技进阶
			$GuildScience = new GuildScience;
			$GuildScience->levelupFinish($guildId);
			
			//获取公会科技
			$guildScience = $GuildScience->getByscienceType($playerId, $scienceType);
			if(!$guildScience){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取科技
			$AllianceScience = new AllianceScience;
			$allianceScience = $AllianceScience->getByScienceType($scienceType, $guildScience['science_level']);
			if(!$allianceScience){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$needTime = $allianceScience['up_time'];
			
			//检查是否有其他进阶队列
			$guildSciences = $GuildScience->getByPlayerId($playerId);
			foreach($guildSciences as $_r){
				if($_r['status'] == 2){
					throw new Exception(10120);
				}
			}
			
			//检查科技是否可进阶
			if($guildScience['status'] != 1){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新公会科技
			if(!$GuildScience->assign($guildScience)->levelup($needTime)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取联盟成员，增加buff todo
				
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		//$data = DataController::get($playerId, array('PlayerStudy'));
		if(!$err){
			$pgd = (new PlayerGuildDonate)->getByPlayerId($playerId, true);
			$pgdb = (new PlayerGuildDonateButton)->getByScienceType($playerId, $scienceType, true);
			$guildScience = $GuildScience->getByscienceType($playerId, $scienceType, true);
			echo $this->data->send(array('GuildScience'=>$guildScience, 'PlayerGuildDonate'=>$pgd, 'PlayerGuildDonateButton'=>$pgdb));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
    /**
     * 清除cd
     * 
     * 
     * @return <type>
     */
	public function scienceClearTimeAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$guildId = $player['guild_id'];
			if(!$guildId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//刷新科技进阶
			$GuildScience = new GuildScience;
			$GuildScience->levelupFinish($guildId);
			
			//玩家捐赠数据
			$PlayerGuildDonate = new PlayerGuildDonate;
			$pgd = $PlayerGuildDonate->getByPlayerId($playerId);
			if(!$pgd){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			//是否处于锁定状态
			if(!$pgd['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//计算花费
			//$payUnit = (new Starting)->dicGetOne('time_cost');
			$sec = $pgd['finish_time'] - time();
			//$fee = ceil($sec / $payUnit);//20秒一元宝
			$fee = clacAccNeedGem($sec);
			
			//花费
			$Player = new Player;
			if(!$Player->updateGem($playerId, -$fee, true, array('cost'=>10010, 'memo'=>'捐献冷却时间清除'))){
				throw new Exception(10121);
			}
			
			//更新捐赠数据
			if(!$PlayerGuildDonate->assign($pgd)->updateData(0, date('Y-m-d H:i:s'))){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
				
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		//$data = DataController::get($playerId, array('PlayerStudy'));
		if(!$err){
			$pgd = $PlayerGuildDonate->getByPlayerId($playerId, true);
			$this->data->filterBasic(array('PlayerGuildDonate'), true);
			echo $this->data->send(array('PlayerGuildDonate'=>$pgd));
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
     * 捐献礼包
     * 
     * 
     * @return <type>
     */
	public function donateRewardAction(){
		$reward = [
			1 => ['num'=>'10', 'drop'=>1310001],
			2 => ['num'=>'20', 'drop'=>1310002],
			3 => ['num'=>'30', 'drop'=>1310003],
		];
		
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$id = floor(@$post['id']);
		if(!checkRegularNumber($id) || !isset($reward[$id]))
			exit;
		
		
		//锁定
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$db = $this->di['db'];
		dbBegin($db);

		try {
			$guildId = $player['guild_id'];
			if(!$guildId){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//获取playerguild
			$PlayerGuildDonate = new PlayerGuildDonate;
			$pgd = $PlayerGuildDonate->getByPlayerId($playerId);
			if(!$pgd){
				if(!$PlayerGuildDonate->add($playerId, array(), 0, '0000-00-00 00:00:00')){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$pgd = $PlayerGuildDonate->getByPlayerId($playerId);
			}
		
			//检查是否已经领取
			if(in_array($id, $pgd['donate_reward'])){
				throw new Exception(10520);//该档捐献奖励已被领取
			}
			
			//获取公会
			$guild = (new Guild)->getGuildInfo($guildId);
			
			//修正
			$today = date('Y-m-d');
			if($guild['donate_date'] != $today){
				(new Guild)->addDonateCount($guildId, $today, 0);
				$guild = (new Guild)->getGuildInfo($guildId);
			}
			
			//查看是否当天已捐献
			if($pgd['last_donate_time'] != $today){
				throw new Exception(10521);//您需要捐献之后才可以领取宝箱
			}
			
			//检查捐献数是否以满足
			if($guild['donate_counter'] < $reward[$id]['num']){
				throw new Exception(10522);//未满足捐献奖励领取条件
			}
			
			//领取
			$gain = (new Drop)->gain($playerId, [$reward[$id]['drop']]);
			if(!$gain){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//更新
			$pgd['donate_reward'][] = $id;
			if(!$PlayerGuildDonate->updateDonateReward($playerId, $pgd['donate_reward'], $today)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
				
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		//$data = DataController::get($playerId, array('PlayerStudy'));
		if(!$err){
			echo $this->data->send(['donate_reward'=>$gain]);
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	
    /**
     * 获取捐献列表
     * 
     * type: 0.历史；1.周；2.日
     * @return <type>
     */
	public function donateRankAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$type = floor(@$post['type']);
		if(!in_array($type, array(0, 1, 2)))
			exit;
		
		try {
			//获取所在联盟
			$guildId = $player['guild_id'];
			if(!$guildId){
				throw new Exception(10497);//您已经退出联盟
			}
			
			//获取联盟成员
			$PlayerGuild = new PlayerGuild;
			$members = $PlayerGuild->getAllGuildMember($guildId);
			if(!$members){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			$memberIds = Set::extract('/player_id', $members);
			//$memberIds = array(100016, 100017);
			
			$condition = "player_id in (".join(',', $memberIds).") and type = ".$type;
			
			$time = time();
			if($type == 1){
				$w = date('w', $time);
				$date1 = date('Y-m-d', strtotime(date('Y-m-d', $time)) + 3600*24*(7-$w));
				$condition .= ' and date="'.$date1.'"';
			}elseif($type == 2){
				$date2 = date('Y-m-d', $time);
				$condition .= ' and date="'.$date2.'"';
			}
			//获取捐献列表
			 $pgds = PlayerGuildDonateStat::find([$condition])->toArray();			
			
			//排序
			$pgds = Set::sort($pgds, '{n}.exp', 'desc');
			$rank = array();
			$donatedPlayerIds = [];
			$rankId = 0;
			foreach($pgds as $_i =>$_d){
				$donatedPlayerIds[] = $_d['player_id'];
				$rank[$_i+1] = array(
					'player_id'=>$_d['player_id'],
					'nick'=>$members[$_d['player_id']]['Player']['nick'],
					'rank'=>$members[$_d['player_id']]['rank'],
					'exp'=>$_d['exp'],
					'coin'=>$_d['coin'],
				);
				$rankId = $_i+1;
			}
			foreach($members as $_playerId=>$_m){
				if(in_array($_playerId, $donatedPlayerIds)) continue;
				$rankId++;
				$rank[$rankId] = array(
					'player_id'=>$_playerId,
					'nick'=>$members[$_playerId]['Player']['nick'],
					'rank'=>$members[$_playerId]['rank'],
					'exp'=>0,
					'coin'=>0,
				);
			}
				
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

		}
		
		if(!$err){
			echo $this->data->send(array('rank'=>$rank));
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 调整玩家联盟阶级
     *
     * 使用方法如下
     * 
     * ```php
     * /guild/changePlayerRank/
     * postData: {"targetPlayerId":"","targetRank":""}
     * return: {}
     * ```
     */
	public function changePlayerRankAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$targetPlayerId = intval(@$post['targetPlayerId']);
		$targetRank = intval(@$post['targetRank']);

		$PlayerGuild = new PlayerGuild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		$targetPlayerGuildInfo = $PlayerGuild->getByPlayerId($targetPlayerId);
		$currentRank = $targetPlayerGuildInfo['rank'];

		if(!empty($targetPlayerGuildInfo) && $playerGuildInfo['guild_id']==$targetPlayerGuildInfo['guild_id'] && $targetPlayerGuildInfo['rank']!=$targetRank
			&& $playerGuildInfo['rank']>$targetPlayerGuildInfo['rank'] && $playerGuildInfo['rank']>$targetRank){
			$PlayerGuild->updatePlayerRank($targetPlayerId, $targetRank);
			//send mail
			//邮件内容
			$data['from_player'] = $player;
			$data['from_guild']  = (new Guild)->getGuildInfo($targetPlayerGuildInfo['guild_id']);
			$data['from_rank']   = $currentRank;
			$data['to_rank']     = $targetRank;

			if($currentRank>$targetRank) {//降级
				$data['changeFlag'] = 0;
				$data['changeFlagDesc'] = 'down';
            } elseif($currentRank<$targetRank) {//升级
                $data['changeFlag'] = 1;
                $data['changeFlagDesc'] = 'up';


            }
            //联盟聊天推送
            $Player       = new Player;
            $player       = $Player->getByPlayerId($playerId);
            $targetPlayer = $Player->getByPlayerId($targetPlayerId);
            if($targetRank!=0) {//调整权限
		        $type        = PlayerMail::TYPE_GUILDAUTHCHG;
		    }
	        (new PlayerMail)->sendSystem([$targetPlayerId], $type, 'system email', '', 0, $data);
            $pushData     = [
                'type'         => 7,
                'member_nick'  => $targetPlayer['nick'],
                'admin_nick'   => $player['nick'],
                'step'         => $data['changeFlagDesc'],//升/降
                'to_rank'      => $targetRank,
                'to_rank_name' => $data['from_guild']['GuildRankName'][$targetRank-1],
            ];
            $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
            socketSend($data);
			echo $this->data->send();
		}else{
			echo $this->data->sendErr(1);//错误的对象
		}
	}

	/**
	 * 批量踢出玩家功能
	 * ```php
     * /guild/expelPlayerBat/
     * postData: {"targetPlayerId":[]}
     * return: {} 
	 */
	public function expelPlayerBatAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$targetPlayerIdArr = @$post['targetPlayerId'];

		$PlayerGuild = new PlayerGuild;
		$Player = new Player;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定

        $CityBattleRound = new CityBattleRound;
        $roundStatus     = $CityBattleRound->getCurrentRoundStatus();
        $battleStatusArr = [
            CityBattleRound::SIGN_FIRST,
            CityBattleRound::SIGN_NORMAL,
            CityBattleRound::SELECT_PLAYER,
            CityBattleRound::SELECT_PLAYER_FINISH,
            CityBattleRound::DOING,
            CityBattleRound::CLAC_REWARD
        ];
        if($roundStatus !== false && in_array($roundStatus, $battleStatusArr)) {
            $err = 10781;//城战过程中不能退盟
            goto SendErr;
        }

		$King = new King;
		$kingBattle = $King->getCurrentBattle();

		if(!empty($kingBattle)){
			$err = 10389;//国王战过程中不能退盟
			goto SendErr;
		}

        $guildId = $player['guild_id'];
        if((new CrossGuildInfo)->isJoined($guildId)){
            $err = 10631;//跨服战中不能退盟
            goto SendErr;
        }
		
		$PlayerProjectQueue = new PlayerProjectQueue;
		$PlayerHelp = new PlayerHelp;
		$PlayerGuildDonateStat = new PlayerGuildDonateStat;
		$PlayerMail = new PlayerMail;
		$Guild = new Guild;
		$failList = [];
		foreach ($targetPlayerIdArr as $targetPlayerId) {			
			if($PlayerProjectQueue->isGather($targetPlayerId)){
				$failList[] = $targetPlayerId;
				continue;
			}

			
			$targetPlayerGuildInfo = $PlayerGuild->getByPlayerId($targetPlayerId);
			$targetPlayer = $Player->getByPlayerId($targetPlayerId);
			if(!empty($targetPlayerGuildInfo) && !empty($playerGuildInfo) && $targetPlayerGuildInfo['guild_id']==$playerGuildInfo['guild_id'] && $playerGuildInfo['rank']==PlayerGuild::RANK_R5){
				//删除公会帮助
				$PlayerHelp->delAllPlayerHelp($targetPlayerGuildInfo['guild_id'], $targetPlayerId);
				//删除捐献排名
				$PlayerGuildDonateStat->clearAll($targetPlayerId);
				//返回公会相关的行军队列
				$PlayerProjectQueue->callbackGuildQueue($targetPlayerGuildInfo['guild_id'], 0, 0, $targetPlayerId);
				$PlayerProjectQueue->callbackGuildQueue($targetPlayerGuildInfo['guild_id'], 5, $targetPlayer['map_id']);
				//返回所有储存资源
				$PlayerGuild->takeOutAllResource($targetPlayerGuildInfo['guild_id'], $targetPlayerId);
				$PlayerGuild->resetMissionScore($targetPlayerId);
				$PlayerGuild->updatePlayerRank($targetPlayerId, 0);
				//send mail
				$data['from_player'] = $player;
				$data['from_guild'] = $Guild->getGuildInfo($targetPlayerGuildInfo['guild_id']);
				$PlayerMail->sendSystem([$targetPlayerId], PlayerMail::TYPE_GUILDQUIT, 'system email', '', 0, $data);

                //联盟聊天推送
                $pushData = [
                    'type'        => 8,
                    'member_nick' => $targetPlayer['nick'],
                    'admin_nick'  => $player['nick'],
                ];
                $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
                socketSend($data);
			}else{
				$failList[] = $targetPlayerId;
			}
				
		}

		SendErr:
		Cache::unlock($lockKey);//锁定
		if(empty($err)){
			echo $this->data->send(["failList"=>$failList]);
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 副会长代替会长
	 * 使用方法如下
	 * 
     * ```php
     * /guild/replaceGuildLeader/
     * postData: {}
     * return: {} 
     * ```
	 */
	public function replaceGuildLeaderAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':guildId=' .$player['guild_id'];
		Cache::lock($lockKey);//锁定

		$PlayerGuild = new PlayerGuild;
		$Player = new Player;
		$Guild = new Guild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		if(empty($playerGuildInfo) || $playerGuildInfo['rank']!=4){
			$err = 10525;//玩家没有公会或不是副会长
			goto SendErr;
		}

		$guild = $Guild->getByPlayerId($playerId);
		$leaderId = $guild['leader_player_id'];
		$leaderPlayerInfo = $Player->getByPlayerId($leaderId);
		if($leaderPlayerInfo['last_online_time']>=time()-3*24*3600){
			$err = 10526;//会长离线没有超过3天
			goto SendErr;
		}

		$PlayerGuild->updatePlayerRank($leaderId, PlayerGuild::RANK_R4);
		$PlayerGuild->updatePlayerRank($playerId, PlayerGuild::RANK_R5);
		$Guild->alter($guild['id'], ['leader_player_id'=>$playerId]);//修改公会信息

        //发送邮件
        $mailData['from_nick'] = $player['nick'];
        $mailData['to_nick']   = $leaderPlayerInfo['nick'];
        (new PlayerMail)->sendSystem([$leaderId], PlayerMail::TYPE_GUILDLEADER_IMPEACH, 'system email', '', 0, $mailData);
        $userData = [
            'type'      => 13,//帮主弹劾
            'from_nick' => $player['nick'],
            'to_nick'   => $leaderPlayerInfo['nick'],
        ];
        $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$userData]];
        socketSend($data);

SendErr:
		Cache::unlock($lockKey);//锁定
		if(empty($err)){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 将玩家踢出公会或退出公会
	 * 使用方法如下
	 * 
     * ```php
     * /guild/expelPlayer/
     * postData: {"targetPlayerId":""}
     * return: {} 
     * ```
	 */
	public function expelPlayerAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$targetPlayerId = intval(@$post['targetPlayerId']);

		$PlayerGuild = new PlayerGuild;
		$Player = new Player;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定

        $CityBattleRound = new CityBattleRound;
        $roundStatus     = $CityBattleRound->getCurrentRoundStatus();
        $battleStatusArr = [
            CityBattleRound::SIGN_FIRST,
            CityBattleRound::SIGN_NORMAL,
            CityBattleRound::SELECT_PLAYER,
            CityBattleRound::SELECT_PLAYER_FINISH,
            CityBattleRound::DOING,
            CityBattleRound::CLAC_REWARD
        ];
        if($roundStatus !== false && in_array($roundStatus, $battleStatusArr)) {
            $err = 10782;//城战过程中不能退盟
            goto SendErr;
        }

		$King = new King;
		$kingBattle = $King->getCurrentBattle();

		if(!empty($kingBattle)){
			$err = 10389;//国王战过程中不能退盟
			goto SendErr;
		}

        $guildId = $player['guild_id'];
        if((new CrossGuildInfo)->isJoined($guildId)){
            $err = 10632;//跨服战中不能退盟
            goto SendErr;
        }

		$PlayerProjectQueue = new PlayerProjectQueue;
		if($PlayerProjectQueue->isGather($targetPlayerId)){
			$err = 10390;//集结出征中不能退盟
			goto SendErr;
		}

		if($targetPlayerId==$playerId && !empty($playerGuildInfo) && $playerGuildInfo['rank']!=PlayerGuild::RANK_R5){
			//删除公会帮助
			$PlayerHelp = new PlayerHelp;
			$PlayerHelp->delAllPlayerHelp($playerGuildInfo['guild_id'], $targetPlayerId);
			//删除捐献排名
			$PlayerGuildDonateStat = new PlayerGuildDonateStat;
			$PlayerGuildDonateStat->clearAll($targetPlayerId);
			//返回公会相关的行军队列
			$PlayerProjectQueue = new PlayerProjectQueue;
			$PlayerProjectQueue->callbackGuildQueue($playerGuildInfo['guild_id'], 0, 0, $targetPlayerId);
			$PlayerProjectQueue->callbackGuildQueue($playerGuildInfo['guild_id'], 5, $player['map_id']);
			//返回所有储存资源
			$PlayerGuild->takeOutAllResource($playerGuildInfo['guild_id'], $targetPlayerId);
			$PlayerGuild->resetMissionScore($targetPlayerId);
			$PlayerGuild->updatePlayerRank($targetPlayerId, 0);

            (new PlayerCommonLog)->add($playerId, ['type'=>'退出联盟', 'memo'=>['playerId'=>$playerId, 'guildId'=>$playerGuildInfo['guild_id']]]);

            //联盟聊天推送
            $guildInfo = (new Guild)->getGuildInfo($playerGuildInfo['guild_id']);
            $pushData = [
                'type'        => 9,
                'member_nick' => $player['nick'],
            ];
            $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$guildInfo['leader_player_id'], 'content'=>'', 'pushData'=>$pushData]];
            socketSend($data);

		}else{
			$targetPlayerGuildInfo = $PlayerGuild->getByPlayerId($targetPlayerId);
			$targetPlayer = $Player->getByPlayerId($targetPlayerId);
			if(!empty($targetPlayerGuildInfo) && !empty($playerGuildInfo) && $targetPlayerGuildInfo['guild_id']==$playerGuildInfo['guild_id'] && $playerGuildInfo['rank']>=PlayerGuild::RANK_R4 && $playerGuildInfo['rank']>$targetPlayerGuildInfo['rank']){
				//删除公会帮助
				$PlayerHelp = new PlayerHelp;
				$PlayerHelp->delAllPlayerHelp($targetPlayerGuildInfo['guild_id'], $targetPlayerId);
				//删除捐献排名
				$PlayerGuildDonateStat = new PlayerGuildDonateStat;
				$PlayerGuildDonateStat->clearAll($targetPlayerId);
				//返回公会相关的行军队列
				$PlayerProjectQueue = new PlayerProjectQueue;
				$PlayerProjectQueue->callbackGuildQueue($targetPlayerGuildInfo['guild_id'], 0, 0, $targetPlayerId);
				$PlayerProjectQueue->callbackGuildQueue($targetPlayerGuildInfo['guild_id'], 5, $targetPlayer['map_id']);
				//返回所有储存资源
				$PlayerGuild->takeOutAllResource($targetPlayerGuildInfo['guild_id'], $targetPlayerId);
				$PlayerGuild->resetMissionScore($targetPlayerId);
				$PlayerGuild->updatePlayerRank($targetPlayerId, 0);

                (new PlayerCommonLog)->add($playerId, ['type'=>'踢出联盟', 'memo'=>['playerId'=>$playerId, 'guildId'=>$playerGuildInfo['guild_id']]]);
				//send mail
				$data['from_player'] = $player;
				$data['from_guild'] = (new Guild)->getGuildInfo($targetPlayerGuildInfo['guild_id']);
				(new PlayerMail)->sendSystem([$targetPlayerId], PlayerMail::TYPE_GUILDQUIT, 'system email', '', 0, $data);

                //联盟聊天推送
                $pushData = [
                    'type'        => 8,
                    'member_nick' => $targetPlayer['nick'],
                    'admin_nick'  => $player['nick'],
                ];
                $data = ['Type'=>'guild_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
                socketSend($data);
			}else{
				$err = 10290;//踢出联盟-错误的对象
			}
		}

SendErr:
		Cache::unlock($lockKey);//锁定
		if(empty($err)){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 换帮主
	 * 使用方法如下
	 * 
     * ```php
     * /guild/transferLeader/
     * postData: {"targetPlayerId":""}
     * return: {} 
     * ```
	 */
	public function transferLeaderAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$targetPlayerId = intval(@$post['targetPlayerId']);

		$PlayerGuild = new PlayerGuild;
		$Guild = new Guild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		$targetPlayerGuildInfo = $PlayerGuild->getByPlayerId($targetPlayerId);

		if($targetPlayerGuildInfo['guild_id']==$playerGuildInfo['guild_id'] && $playerGuildInfo['rank']==PlayerGuild::RANK_R5){
			$PlayerGuild->updatePlayerRank($playerId, PlayerGuild::RANK_R4);
			$PlayerGuild->updatePlayerRank($targetPlayerId, PlayerGuild::RANK_R5);
			$Guild->alter($playerGuildInfo['guild_id'], ['leader_player_id'=>$targetPlayerId]);//修改截公会信息
			echo $this->data->send();
		}else{
			echo $this->data->sendErr(1);//错误的对象
		}
	}

	/**
	 * 修改公会Rank名称 
	 * 使用方法如下
	 * 
     * ```php
     * /guild/changeRankName/
     * postData: {"rank":"","name":""}
     * return: {} 
     * ```
	 */
	public function changeRankNameAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$rank = intval(@$post['rank']);
		$name = @$post['name'];
		if((new SensitiveWord)->checkSensitiveContent($name, 2)){
			$err = 10470;//含有敏感字
			goto SendErr;
		}

		$PlayerGuild = new PlayerGuild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		if(empty($playerGuildInfo) || $playerGuildInfo['rank']!=5){
			$err = 10471;//操作者不是公会会长
			goto SendErr;
		}

		$GuildRankName = new GuildRankName;
		$rankNameList = $GuildRankName->getRankName($playerGuildInfo['guild_id'], 0);
		foreach ($rankNameList as $value) {
			if($name==$value){
				$err = 10472;//rank名不能重复
				goto SendErr;
			}
		}
		
SendErr:
		if(empty($err)){
			$GuildRankName->changeRankName($playerGuildInfo['guild_id'], $rank, $name);
			$GuildRankName->clearGuildCache($playerGuildInfo['guild_id']);
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 储存资源
	 * 使用方法如下
	 * 
     * ```php
     * /guild/storeResource/
     * postData: {"resourceArr":""}
     * return: {} 
     * ```
	 */
	public function storeResourceAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$resourceArr = $post['resourceArr'];

		$db = $this->di['db'];
		dbBegin($db);
		if(empty($resourceArr)){
			$err = 10511;//缺少参数
			goto SendErr;
		}else{
			if(empty($resourceArr[1])){
				$resourceArr2['gold'] = 0;
			}else{
				$resourceArr2['gold'] = $resourceArr[1];
			}
			if(empty($resourceArr[2])){
				$resourceArr2['food'] = 0;
			}else{
				$resourceArr2['food'] = $resourceArr[2];
			}
			if(empty($resourceArr[3])){
				$resourceArr2['wood'] = 0;
			}else{
				$resourceArr2['wood'] = $resourceArr[3];
			}
			if(empty($resourceArr[4])){
				$resourceArr2['stone'] = 0;
			}else{
				$resourceArr2['stone'] = $resourceArr[4];
			}
			if(empty($resourceArr[5])){
				$resourceArr2['iron'] = 0;
			}else{
				$resourceArr2['iron'] = $resourceArr[5];
			}
		}

		$Player = new Player;
		$PlayerGuild = new PlayerGuild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);
		$guildId = $playerGuildInfo['guild_id'];

		$Map = new Map;
		$PlayerProjectQueue = new PlayerProjectQueue;
		$from = $Map->getByXy($player['x'], $player['y']);

		if(!empty($playerGuildInfo)){
			$target = $Map->getGuildMapElement($guildId, 801);//获取联盟仓库
		}
		if(!empty($target)){
			$target = $target[0];
		}else{
			$err = 10456;//公会仓库不存在
			goto SendErr;
		}

		$Master = new Master;
		$maxStoreArr = $Master->getMaxStoreNum($playerId, $player['level']);
		$unitResource0 = $PlayerGuild->getTodayStoreResouce($playerId);
		$unitResource1 = $resourceArr2['food']+$resourceArr2['gold']*1+$resourceArr2['wood']*4+$resourceArr2['stone']*12+$resourceArr2['iron']*32;
		$unitResource2 = $playerGuildInfo['store_food']+$playerGuildInfo['store_gold']*1+$playerGuildInfo['store_wood']*4+$playerGuildInfo['store_stone']*12+$playerGuildInfo['store_iron']*32;
		if($unitResource0+$unitResource1>$maxStoreArr['day']){
			$err = 10512;//超过每日储存上限
			goto SendErr;
		}

		if($unitResource1+$unitResource2>$maxStoreArr['all']){
			$err = 10513;//超过总储存上限
			goto SendErr;
		}

		$needResource = [];
		$useResource = [];
		$extraData = [	'from_map_id'	=> $from['id'],
						'from_x'		=> $from['x'],
						'from_y'		=> $from['y'],
						'to_map_id'		=> $target['id'],
						'to_x'			=> $target['x'],
						'to_y'			=> $target['y'],
					];
		foreach ($resourceArr2 as $key=>$value) {
			$needResource[$key] = $value;
			$useResource[$key] = -1*$value;
			$extraData['carry_'.$key] = $value;
		}
		if($Player->hasEnoughResource($playerId, $needResource)){
			$Player->updateResource($playerId, $useResource);
		}else{
			$err = 10514;//资源不足
			goto SendErr;
		}
		
		$currentPPQ = $PlayerProjectQueue->getByPlayerId($playerId);
		try{
            $Map->doBeforeGoOut($playerId, 0, false, ['ppq'=>$currentPPQ]);
        } catch (Exception $e) {
            list($errCode, $msg) = parseException($e);
            goto SendErr;
        }

SendErr:	

		if(empty($err)){
			dbCommit($db);
			$needTime = $PlayerProjectQueue->calculateMoveTime($playerId, $player['x'], $player['y'], $target['x'], $target['y'], 5, 0);
			$PlayerProjectQueue->addQueue($playerId, $guildId, 0, PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHGOTO, $needTime, 0, [], $extraData);
			echo $this->data->send();
		}else{
			dbRollback($db);
			echo $this->data->sendErr($err);//操作者资源不够或者没有公会仓库
		}
	}

	/**
	 * 取出资源
	 * 使用方法如下
	 * 
     * ```php
     * /guild/takeOutResource/
     * postData: {"resourceArr":""}
     * return: {} 
     * ```
	 */
	public function takeOutResourceAction(){
		$playerId = $this->getCurrentPlayerId();
		$player = $this->getCurrentPlayer();
		$post = getPost();
		$resourceArr = $post['resourceArr'];

		$db = $this->di['db'];
		dbBegin($db);
		if(empty($resourceArr)){
			$err = 10515;//缺少参数
			goto SendErr;
		}else{
			$takeOutResource = [];
			if(empty($resourceArr[1])){
				$takeOutResource['gold'] = 0;
			}else{
				$takeOutResource['gold'] = $resourceArr[1];
			}
			if(empty($resourceArr[2])){
				$takeOutResource['food'] = 0;
			}else{
				$takeOutResource['food'] = $resourceArr[2];
			}
			if(empty($resourceArr[3])){
				$takeOutResource['wood'] = 0;
			}else{
				$takeOutResource['wood'] = $resourceArr[3];
			}
			if(empty($resourceArr[4])){
				$takeOutResource['stone'] = 0;
			}else{
				$takeOutResource['stone'] = $resourceArr[4];
			}
			if(empty($resourceArr[5])){
				$takeOutResource['iron'] = 0;
			}else{
				$takeOutResource['iron'] = $resourceArr[5];
			}
		}

		$Player = new Player;
		$PlayerGuild = new PlayerGuild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);
		$guildId = $playerGuildInfo['guild_id'];

		$Map = new Map;
		$PlayerProjectQueue = new PlayerProjectQueue;
		$from = $Map->getByXy($player['x'], $player['y']);

		if(!empty($playerGuildInfo)){
			$target = $Map->getGuildMapElement($guildId, 801);//获取联盟仓库
		}

		$extraData = [];


		if(!empty($target) && $target[0]['status']==1){
			$target = $target[0];
		}else{
			$err = 10457;//公会仓库不存在
			goto SendErr;
		}

		$extraData = [	'from_map_id'	=> $from['id'],
						'from_x'		=> $from['x'],
						'from_y'		=> $from['y'],
						'to_map_id'		=> $target['id'],
						'to_x'			=> $target['x'],
						'to_y'			=> $target['y'],
					];

		foreach ($takeOutResource as $key => $value) {
			if($value>$playerGuildInfo['store_'.$key]){
				$err = 10516;//仓库储存资源不足
				goto SendErr;
			}
		}

		$currentPPQ = $PlayerProjectQueue->getByPlayerId($playerId);
		try{
            $Map->doBeforeGoOut($playerId, 0, false, ['ppq'=>$currentPPQ]);
        } catch (Exception $e) {
            list($errCode, $msg) = parseException($e);
            goto SendErr;
        }

		SendErr:	

		if(empty($err)){
			dbCommit($db);
			$needTime = $PlayerProjectQueue->calculateMoveTime($playerId, $player['x'], $player['y'], $target['x'], $target['y'], 5, 0);
			$PlayerProjectQueue->addQueue($playerId, $guildId, 0, PlayerProjectQueue::TYPE_GUILDWAREHOUSE_FETCHGOTO, $needTime, 0, $takeOutResource, $extraData);
			echo $this->data->send();
		}else{
			dbRollback($db);
			echo $this->data->sendErr($err);//操作者资源不够或者没有公会仓库
		}
	}

	/**
	 * 查询全公会总资源量
	 * 使用方法如下
	 * 
     * ```php
     * /guild/getAllGuildStoreResource/
     * postData: {"guildId":""}
     * return: {} 
     * ```
	 */
	function getAllGuildStoreResourceAction($guildId){;
		$post = getPost();
		$guildId = $post['guildId'];

		if(!empty($guildId)){
			echo $this->data->sendErr(1);//参数错误
		}
        $PlayerGuild = new PlayerGuild;

        $playerGuildList = $PlayerGuild->getAllGuildMember($guildId);
        $result = [];
        foreach ($playerGuildList as $value) {
            $result[$value['player_id']] = [$value['store_gold'], $value['store_food'], $value['store_wood'], $value['store_stone'], $value['store_iron']];
        }

        echo $this->data->send($result);
    }
    /**
     * 邀请迁城
     *
     * 使用方法如下：
     * ```php
     * guild/inviteChangeCastleLocation
     * postData:{"target_player_id":11,"x":11,"y":22}
     * returnData:{}
     * ```
     */
    public function inviteChangeCastleLocationAction(){
    	$playerId = $this->getCurrentPlayerId();
    	$player = $this->getCurrentPlayer();

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);//锁定
		$guildId = $player['guild_id'];
		$postData = getPost();
		$targetPlayerId = $postData['target_player_id'];
		$targetX = $postData['x'];
		$targetY = $postData['y'];
		//自己没有盟
		if(!$guildId) {
			$errCode = 10088;
			goto sendErr;
		}
		$PlayerGuild = new PlayerGuild;
		$playerGuild  = $PlayerGuild->getByPlayerId($playerId);
		$targetPlayerGuild = $PlayerGuild->getByPlayerId($playerId);
		if($targetPlayerGuild['guild_id']!=$guildId) {
			$errCode = 10314;//邀请迁城-不能邀请其他盟成员
			goto sendErr;
		}
		$guild        = (new Guild)->getGuildInfo($guildId);

		//a 权限不足
		if($playerGuild['rank']<PlayerGuild::RANK_R4) {
			$errCode = 10315;//邀请迁城-权限不足，需要R4权限
			goto sendErr;
		}


		Cache::unlock($lockKey);
		//处理邀请迁城发送邮件
		$data['from_player']  = keepFields($player, ['id', 'nick', 'avatar_id'], true);
		$data['from_player']['guild_short_name']   = $guild['short_name'];
		$data['x'] = $targetX;
		$data['y'] = $targetY;

		$toPlayerIds = [$targetPlayerId];
        $type        = PlayerMail::TYPE_GUILDINVITEMOVE;
        $title       = 'system email';
        $msg         = '';
        $time        = 0;
        $memo 		 = ['exec_flag'=>0];
        (new PlayerMail)->sendSystem($toPlayerIds, $type, $title, $msg, $time, $data, [], $memo);

		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
    }
    /**
	 * 同意或拒绝邀请迁城邀请迁城
	 *
	 * 使用方法如下
	 * ```php
	 * guild/handleChangeCastleLocation
	 * postData:{"mail_id":100}
	 * return: {}
	 * ```
	 */
	public function handleChangeCastleLocationAction(){
		$playerId    = $this->getCurrentPlayerId();
		//锁定
		$lockKey     = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		$player      = $this->getCurrentPlayer();
		$postData    = getPost();
		$mailId      = $postData['mail_id'];
		
		$PlayerMail  = new PlayerMail;
		
		$mailInfo    = $PlayerMail->getMailInfo($mailId);

		$execFlag    = json_decode($mailInfo['memo'], true)['exec_flag'];

		if($execFlag==1) {//已经操作过该邮件
			$errCode = 10084;
			goto sendErr;
		}
		$mailInfoData = json_decode($mailInfo['mail_info']['data'], true);
		$PlayerMail->updateMemosByMailId($playerId, $mailId, ['exec_flag'=>1]);
		Cache::unlock($lockKey);
		echo $this->data->send();
		exit;
		sendErr: {
			Cache::unlock($lockKey);
			echo $this->data->sendErr($errCode);
			exit;
		}
	}

    /**
     * 获取联盟任务排名
     * 
     * 
     * @return <type>
     */
	public function getMissionRankAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		/*$post = getPost();
		$type = floor(@$post['type']);
		if(!in_array($type, [1]))
			exit;*/
		
		try {
			$Guild = new Guild;
			$pg = $Guild->getByPlayerId($playerId);
			if(!$pg){
				//throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}else{
				$guildId = $pg['id'];
			}
			
			$data = [];
			//-----------捐献--------
			$type = 1;
			$limit = 3;
			//获取当前活动状态
			$hasActivity = true;
			$AllianceMatchList = new AllianceMatchList;
			if($AllianceMatchList->getAllianceMatchStatus($type, $activity) != AllianceMatchList::DOING){
				$hasActivity = false;
			}
			
			$isActivityOpen = ($hasActivity ? true : false);
			$activityStartTime = empty($activity)?0:$activity['start_time'];
			$activityEndTime = empty($activity)?0:$activity['end_time'];

			if($hasActivity){//当前正在进行活动
				//如果有活动，获取实时排名
				if($type == 1){
					$ret = $Guild->find(['order'=>'mission_score desc, guild_power desc', 'limit'=>$limit])->toArray();
				}
				$rank = [];
				foreach($ret as $_i => $_r){
					$rank[] = [
						'id'=>$_i+1,
						'rank'=>$_i+1,
						'guild_id'=>$_r['id']*1,
						'name'=>$_r['name'],
						'avatar'=>$_r['icon_id']*1,
						'score'=>$_r['mission_score']*1,
					];
				}
				
				//获得我的排名
				if($pg){
					$myRank = $Guild->count(['mission_score > '.$pg['mission_score'].' or (mission_score='.$pg['mission_score'].' and guild_power > '.$pg['guild_power'].')'])+1;
					$myScore = $pg['mission_score'];
					if(!$myScore){
						$myRank = 0;
					}	
				}else{
					$myScore = 0;
					$myRank = 0;
				}
			}elseif(!$hasActivity && $activity){//当前无活动。但是有历史
				$GuildMissionRank = new GuildMissionRank;
				if($activityStartTime > time() && $activity['round'] > 1){
					$rank = $GuildMissionRank->getRankList($activity['round']-1, $type, $limit);
				}else{
					$rank = $GuildMissionRank->getRankList($activity['round'], $type, $limit);
				}
				//$rank = $GuildMissionRank->adapter($rank);
				$rank = filterFields($rank, true, $GuildMissionRank->blacklist);
				
				//获得我的排名
				if($pg){
					$gmr = $GuildMissionRank->getGuildRank($activity['round'], $type, $pg['id']);
					if($gmr){
						$myRank = $gmr['rank'];
						$myScore = $gmr['score'];
					}else{
						$myRank = 0;
						$myScore = 0;
					}
				}else{
					$myRank = 0;
					$myScore = 0;
				}
			}else{//无数据
				$rank = [];
				$myRank = 0;
				$myScore = 0;
			}
			$data[$type] = array('rank'=>$rank, 'myRank'=>$myRank*1, 'myScore'=>$myScore*1, 'isActivityOpen'=>$isActivityOpen, 'activityStartTime'=>$activityStartTime, 'activityEndTime'=>$activityEndTime);
			
			//-----和氏璧-----
			$AllianceMatchList = new AllianceMatchList;
			$activity = [];
			if($AllianceMatchList->getAllianceMatchStatus(2, $activity) != AllianceMatchList::DOING){
				$isActivityOpen = false;
			}else{
				$isActivityOpen = true;
			}

			$GuildMissionRank  = new GuildMissionRank ;
			if(!empty($activity)){//当前正在进行活动 || 历史
				$activityStartTime = $activity['start_time'];
				$activityEndTime = $activity['end_time'];

				if($pg){
					$guildRank = $GuildMissionRank->getGuildRank($activity['round'], 2, $guildId);
					if($guildRank){
						$myRank = $guildRank['rank'];
						$myScore = $guildRank['score'];
					}else{
						$myRank = 0;
						$myScore = 0;
					}
				}else{
					$myRank = 0;
					$myScore = 0;
				}

				$rankList = $GuildMissionRank->getRankList($activity['round'], 2);
				if(empty($rankList)){
					$rankList = [];
				}
			}else{
				$activityStartTime = 0;
				$activityEndTime = 0;
				$rankList = [];
				$myRank = 0;
				$myScore = 0;
			}

			$data[2] = array('rank'=>$rankList, 'myRank'=>$myRank*1, 'myScore'=>$myScore*1, 'isActivityOpen'=>$isActivityOpen, 'activityStartTime'=>$activityStartTime, 'activityEndTime'=>$activityEndTime);

			//--------据点战---------
			$AllianceMatchList = new AllianceMatchList;
			$activity = [];
			if($AllianceMatchList->getAllianceMatchStatus(4, $activity) != AllianceMatchList::DOING){
				$isActivityOpen = false;
			}else{
				$isActivityOpen = true;
			}

			$GuildMissionRank  = new GuildMissionRank ;
			if(!empty($activity)){//当前正在进行活动 || 历史
				$activityStartTime = $activity['start_time'];
				$activityEndTime = $activity['end_time'];
				if($pg){
					$guildRank = $GuildMissionRank->getGuildRank($activity['round'], 4, $guildId);
					if($guildRank){
						$myRank = $guildRank['rank'];
						$myScore = $guildRank['score'];
					}else{
						$myRank = 0;
						$myScore = 0;
					}
				}else{
					$myRank = 0;
					$myScore = 0;
				}

				$rankList = $GuildMissionRank->getRankList($activity['round'], 4);
				if(empty($rankList)){
					$rankList = [];
				}
			}else{
				$activityStartTime = 0;
				$activityEndTime = 0;
				$rankList = [];
				$myRank = 0;
				$myScore = 0;
			}

			$data[4] = array('rank'=>$rankList, 'myRank'=>$myRank*1, 'myScore'=>$myScore*1, 'isActivityOpen'=>$isActivityOpen, 'activityStartTime'=>$activityStartTime, 'activityEndTime'=>$activityEndTime);

			
			//-----------黄巾起义--------
			$type = 3;
			$limit = 3;
			//获取当前活动状态
			$hasActivity = true;
			$AllianceMatchList = new AllianceMatchList;
			if($AllianceMatchList->getAllianceMatchStatus($type, $activity) != AllianceMatchList::DOING){
				$hasActivity = false;
			}
			$isActivityOpen = ($hasActivity ? true : false);
			$activityStartTime = empty($activity)?0:$activity['start_time'];
			$activityEndTime = empty($activity)?0:$activity['end_time'];

			if($hasActivity){//当前正在进行活动
				//如果有活动，获取实时排名
				$ret = (new GuildHuangjin)->find(['order'=>'score desc, top_wave desc, last_win_wave desc, update_time asc', 'score > 0 and status >= 1'/*, 'limit'=>$limit*/])->toArray();
				$rank = [];
				$myScore = 0;
				$myRank = 0;
				foreach($ret as $_i => $_r){
					if($_i+1 <= 3){
						$_guild = $Guild->getGuildInfo($_r['guild_id']);
						$rank[] = [
							'id'=>$_i+1,
							'rank'=>$_i+1,
							'guild_id'=>$_r['guild_id']*1,
							'name'=>$_guild['name'],
							'avatar'=>$_guild['icon_id']*1,
							'score'=>$_r['score']*1,
						];
					}
					if($pg && $guildId == $_r['guild_id']){//获得我的排名
						$myScore = $_r['score']*1;
						$myRank = $_i+1;
					}
				}
				
			}elseif(!$hasActivity && $activity){//当前无活动。但是有历史
				$GuildMissionRank = new GuildMissionRank;
				if($activityStartTime > time() && $activity['round'] > 1){
					$rank = $GuildMissionRank->getRankList($activity['round']-1, $type, $limit);
				}else{
					$rank = $GuildMissionRank->getRankList($activity['round'], $type, $limit);
				}
				//$rank = $GuildMissionRank->adapter($rank);
				$rank = filterFields($rank, true, $GuildMissionRank->blacklist);
				
				//获得我的排名
				if($pg){
					$gmr = $GuildMissionRank->getGuildRank($activity['round'], $type, $pg['id']);
					if($gmr){
						$myRank = $gmr['rank'];
						$myScore = $gmr['score'];
					}else{
						$myRank = 0;
						$myScore = 0;
					}
				}else{
					$myRank = 0;
					$myScore = 0;
				}
			}else{//无数据
				$rank = [];
				$myRank = 0;
				$myScore = 0;
			}
			$data[$type] = array('rank'=>$rank, 'myRank'=>$myRank*1, 'myScore'=>$myScore*1, 'isActivityOpen'=>$isActivityOpen, 'activityStartTime'=>$activityStartTime, 'activityEndTime'=>$activityEndTime);
			
				
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);

		}
		
		if(!$err){
			echo $this->data->send($data);
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 查看公告板
	 *
	 * 使用方法如下
	 * ```php
	 * guild/showBoard
	 * postData:{}
	 * return: {}
	 * ```
	 */
	public function showBoardAction(){
		$playerId = $this->getCurrentPlayerId();
    	$player = $this->getCurrentPlayer();
    	$guildId = $player['guild_id'];
    	if(empty($guildId)){
			$err = 10458;//玩家没有公会
			goto SendErr;
		}
    	$GuildBoard = new GuildBoard;
		$re = $GuildBoard->getByPlayerId($playerId);
		
		SendErr:
		if(empty($err)){
			echo $this->data->send($re);
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 修改公告板
	 *
	 * 使用方法如下
	 * ```php
	 * guild/changeBoard
	 * postData:{"orderId":"1","title":"","text":"","updateTime":""}
	 * return: {}
	 * ```
	 */
	public function changeBoardAction(){
		$playerId = $this->getCurrentPlayerId();
    	$player = $this->getCurrentPlayer();
    	$guildId = $player['guild_id'];

		$lockKey = 'changeGuildBoard:guildId=' .$guildId;
		Cache::lock($lockKey);//锁定
		
		$postData = getPost();
		$orderId = $postData['orderId'];
		$title = $postData['title'];
		$text = $postData['text'];
		$updateTime = $postData['updateTime'];

		if(empty($guildId)){
			$err = 10459;//玩家没有公会
			goto SendErr;
		}
		$PlayerGuild = new PlayerGuild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		if($playerGuildInfo['rank']<5){
			$err = 10460;//没有修改权限
			goto SendErr;
		}

		//检测是否有编辑冲突 
		$GuildBoard = new GuildBoard;
		$re = $GuildBoard->getByPlayerId($playerId);
		foreach ($re as $key => $value) {
			if($value['order_id']==$orderId && $updateTime!=$value['update_time']){
				$err = 10517;//留言已经被修改
				goto SendErr;
			}
		}

		$SensitiveWord = new SensitiveWord;
		if($SensitiveWord->checkSensitiveContent($text, 1)){
            $text = $SensitiveWord->filterWord($text);//敏感字
		}
		if($SensitiveWord->checkSensitiveContent($title, 1)){
            $title = $SensitiveWord->filterWord($title);//敏感字
		}

		$GuildBoard->saveRecord($playerId, $guildId, $orderId, $title, $text);

		SendErr:
		Cache::unlock($lockKey);//锁定
		if(empty($err)){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}

	/**
	 * 交换公告位置
	 *
	 * 使用方法如下
	 * ```php
	 * guild/swapBoard
	 * postData:{"orderId1":"1","orderId2":"2","updateTime1":"","updateTime2":""}
	 * return: {}
	 * ```
	 */
	public function swapBoardAction(){
		$playerId = $this->getCurrentPlayerId();
    	$player = $this->getCurrentPlayer();
    	$guildId = $player['guild_id'];
    	$lockKey = 'changeGuildBoard:guildId=' .$guildId;
		Cache::lock($lockKey);//锁定
		
		$postData = getPost();
		$orderId1 = $postData['orderId1'];
		$orderId2 = $postData['orderId2'];
		$updateTime1 = $postData['updateTime1'];
		$updateTime2 = $postData['updateTime2'];

		if(empty($guildId)){
			$err = 10461;//玩家没有公会
			goto SendErr;
		}
		$PlayerGuild = new PlayerGuild;
		$playerGuildInfo = $PlayerGuild->getByPlayerId($playerId);

		if($playerGuildInfo['rank']<5){
			$err = 10462;//没有修改权限
			goto SendErr;
		}

		//检测是否有编辑冲突
		$GuildBoard = new GuildBoard;
		$re = $GuildBoard->getByPlayerId($playerId);
		foreach ($re as $key => $value) {
			if(($value['order_id']==$orderId1 && $updateTime1!=$value['update_time']) || ($value['order_id']==$orderId2 && $updateTime2!=$value['update_time'])){
				$err = 10518;//留言已经被修改
				goto SendErr;
			}
		}

		$GuildBoard->swapRecord($guildId, $orderId1, $orderId2);
		$this->data->setBasic(['GuildBoard']);
		SendErr:
		Cache::unlock($lockKey);//锁定
		if(empty($err)){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
    /**
     * 邀请随机玩家入盟
     *
     * 使用方法如下
     * ```php
     * guild/inviteRandPlayers
     * postData: {}
     * return: {Guild}
     * ```
     */
    public function inviteRandPlayersAction(){
		$playerId = $this->getCurrentPlayerId();
		$lockKey  = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;//锁定
		Cache::lock($lockKey);
		$player   = $this->getCurrentPlayer();
		
		$guildId  = $player['guild_id'];
        //自己没有盟
        if(!$guildId) {
            $errCode = 10088;
            goto sendErr;
        }
		$Guild       = new Guild;
		$PlayerGuild = new PlayerGuild;
		
		$guild       = $Guild->getGuildInfo($guildId);
		$playerGuild = $PlayerGuild->getByPlayerId($playerId);

        //a 权限不足
        if($playerGuild['rank']<PlayerGuild::RANK_R5) {
            $errCode = 10089;
            goto sendErr;
        }

        //b 人数已满
        if($guild['num']>=$guild['max_num']) {
            $errCode = 10090;
            goto sendErr;
        }

        if($guild['invite_end_time']>0 && $guild['invite_end_time']>time()) {
        	$errCode = 10427;//[随机邀请入盟]冷却时间未到,尚不能邀请
        	goto sendErr;
        }

        //处理邀请逻辑
        $Guild->searchRandPlayers($guild, $playerGuild);
        Cache::unlock($lockKey);
        echo $this->data->send();
        exit;
        sendErr: {
            Cache::unlock($lockKey);
            echo $this->data->sendErr($errCode);
            exit;
        }
    }

    /**
     * 查看公会礼包
     *
     * 使用方法如下
     * ```php
     * guild/getGuildGiftInfo
     * postData: {}
     * return: {}
     * ```
     */
    function getGuildGiftInfoAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $guildId = $player['guild_id'];

        $GuildGiftPool = new GuildGiftPool;
        $GuildGiftDistributionLog = new GuildGiftDistributionLog;
        $giftList = $GuildGiftPool->getGuildGiftList($guildId);
        $round = $giftList['round'];
        $type = $giftList['type'];
        $disList = $GuildGiftDistributionLog->getDistributionList($guildId, $round, $type);
        foreach($giftList['gift'] as $k=>$v){
            if(!empty($disList[$k])){
                $giftList['gift'][$k] -= $disList[$k];
            }
        }
        echo $this->data->send(['giftList'=>$giftList['gift']]);
    }

    /**
     * 分发公会礼包
     *
     * 使用方法如下
     * ```php
     * guild/distributeGift
     * postData: {'targetPlayerId':0,'giftId':'0'}
     * return: {}
     * ```
     */
    function distributeGiftAction(){
        $playerId = $this->getCurrentPlayerId();
        $player = $this->getCurrentPlayer();
        $guildId = $player['guild_id'];
        $lockKey = 'distribute:guildId=' .$guildId;
        Cache::lock($lockKey);//锁定
        $postData = getPost();
        $targetPlayerId = $postData['targetPlayerId'];
        $giftId = $postData['giftId'];

        $PlayerGuild = new PlayerGuild;
        $pgInfo = $PlayerGuild->getByPlayerId($playerId);
        $targetPgInfo = $PlayerGuild->getByPlayerId($targetPlayerId);

        if(empty($pgInfo) || empty($targetPgInfo) || $pgInfo['guild_id']!=$targetPgInfo['guild_id'] || $pgInfo['rank']<5){
            $errCode = 10530;//玩家没有分配权限
            goto sendErr;
        }

        $GuildGiftPool = new GuildGiftPool;
        $GuildGiftDistributionLog = new GuildGiftDistributionLog;
        $giftList = $GuildGiftPool->getGuildGiftList($guildId);
        $round = $giftList['round'];
        $type = $giftList['type'];
        $disList = $GuildGiftDistributionLog->getDistributionList($guildId, $round, $type);
        foreach($giftList['gift'] as $k=>$v){
            if(!empty($disList[$k])){
                $giftList['gift'][$k] -= $disList[$k];
            }
        }
        if(empty($giftList['gift']) || empty($giftList['gift'][$giftId])){
            $errCode = 10531;//没有礼包可发放
            goto sendErr;
        }

        if($GuildGiftDistributionLog->hasGetGift($targetPlayerId, $round, $type)){
            $errCode = 10532;//已发放过一次礼包
            goto sendErr;
        }

        $PlayerMail = new PlayerMail;
        //$item = $PlayerMail->newItemByDrop($targetPlayerId, [$giftId]);
        $item = $PlayerMail->newItem(2, $giftId, 1);
        $Guild = new Guild;
        $guild = $Guild->getByPlayerId($playerId);
        $gName = $guild['name'];
        $pName = $player['nick'];
        $PlayerMail->sendSystem([$targetPlayerId], PlayerMail::TYPE_GUILDMISSION_GIFT, 'guild gift email', '', 0, ['guildName'=>$gName, 'LeaderName'=>$pName], $item, []);
        $GuildGiftDistributionLog->addNew($targetPlayerId, $guildId, $giftId, $round, $type);
        $giftList['gift'][$giftId] -= 1;

        sendErr: Cache::unlock($lockKey);

        if(empty($errCode)){
            echo $this->data->send(['giftList'=>$giftList['gift']]);
        }else{
            echo $this->data->sendErr($errCode);
        }
    }
}