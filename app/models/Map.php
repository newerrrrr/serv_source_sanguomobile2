<?php
/**
 * 地图信息表
 */
class Map extends ModelBase{
    private $createGuildBuildMapElementId = false;//将要创建的建筑的map_element_id
    public static $largeElementIdList = [1,3,4,5,6,7,8,15,16,18,19];

    /**
     * 添加一条地图元素
     * @param array $data key:必须有："x,y,map_element_id,map_element_origin_id",可有可无："guild_id, player_id"
     */
    public function addNew(array $data){
        $self                        = new self;
        $self->x                     = $data['x'];
        $self->y                     = $data['y'];
        $self->block_id              = $this->calcBlockByXy($data['x'], $data['y']);
        $self->map_element_id        = $data['map_element_id'];
        $self->map_element_origin_id = $data['map_element_origin_id'];
        $self->map_element_level     = $data['map_element_level'];
		$self->rowversion = 1;
		$self->resource     = @$data['resource']*1;
        if(isset($data['durability'])) {
            $self->durability = $data['durability'];
        }
        if(isset($data['max_durability'])) {
            $self->max_durability = $data['max_durability'];
        }
        if(isset($data['status'])) {//联盟建筑
            $self->status = $data['status'];
        }

        if(isset($data['guild_id'])) {
            $self->guild_id = $data['guild_id'];
        }
        if(isset($data['player_id'])) {
            $self->player_id = $data['player_id'];
        }
        $self->update_time = $self->create_time = $self->build_time = date('Y-m-d H:i:s');
        $self->save();
        $this->clearMapCache($data['x'], $data['y']);
    }

    public function delMap($id){
        $re = self::findFirst($id);
        if($re){
            $re->delete();
            $this->clearMapCache($re->x, $re->y);
            return true;
        }else{
            return false;
        }
    }

    public function delPlayerCastle($playerId){
        $map = self::findFirst("player_id={$playerId} and map_element_origin_id=15");
        if(!empty($map)){
            $map = $map->toArray();
            $PlayerProjectQueue = new PlayerProjectQueue;
            $PlayerProjectQueue->callbackGuildQueue($map['guild_id'], 5, $map['id']);
            $this->delMap($map['id']);
            $Player = new Player;
            $Player->clearDataCache($playerId);
            return true;
        }
        return false;
    }

    /**
     * 更改map表的值
     * @param  int $playerId 
     * @param  array  $fields  
     */
    public function alter($id, array $fields){
        $re = self::findFirst($id);
        if(!$re){
            return false;
        }

		if(in_array($re->map_element_origin_id, array(9, 10, 11, 12, 13, 22)) && !$fields['resource']){
			$re->delete();
            $flag = $re->affectedRows();
		}elseif(in_array($re->map_element_origin_id, array(1)) && !$fields['durability']){
			$PlayerProjectQueue = new PlayerProjectQueue;
			$PlayerProjectQueue->callbackGuildQueue($re->guild_id, 1, $re->id);
			if($this->getGuildMapElementNum($re->guild_id, 101)==1) {//最后一个堡垒 
				$PlayerProjectQueue->callbackGuildQueue($re->guild_id, 2);
				$PlayerProjectQueue->callbackGuildQueue($re->guild_id, 3);
				$PlayerProjectQueue->callbackGuildQueue($re->guild_id, 4);
                $PlayerGuild = new PlayerGuild;
                $PlayerGuild->takeOutAllResource($re->guild_id);
				$mapElementList = $this->getAllByGuildId($re->guild_id);
				foreach($mapElementList as $key=>$value){
                    if(in_array($value['map_element_origin_id'], [2,3,4,5,6,7,8])){//不是玩家城堡的联盟建筑
						$flag = $this->delMap($value['id']);
					}
				}
			}
			$re->delete();
            $flag = $re->affectedRows();
		}else{
			if(isset($fields['build_time'])){
				if(is_numeric($fields['build_time'])){
					$fields['build_time'] = "'".date('Y-m-d H:i:s', $fields['build_time'])."'";
				}else{
					$fields['build_time'] = "'".$fields['build_time']."'";
				}
			}
			if(isset($fields['create_time'])){
				if(is_numeric($fields['build_time'])){
					$fields['create_time'] = "'".date('Y-m-d H:i:s', $fields['create_time'])."'";
				}else{
					$fields['create_time'] = "'".$fields['create_time']."'";
				}
			}
			$fields['update_time'] = "'".date('Y-m-d H:i:s')."'";
			$fields['rowversion'] = $fields['rowversion']+1;
			//$flag = $re->save($fields);
			$flag = $this->updateAll($fields, ['id'=>$id]);
		}
        $this->clearMapCache($re->x, $re->y);
        return $flag;
    }

    /**
     * 玩家升级后，需要升级世界地图的map_element
     * @param  int $playerId
     */
    public function levelUp($playerId){
        $player         = (new Player)->getByPlayerId($playerId);
        $map            = $this->getByXy($player['x'], $player['y']);
        $fuyaBuildLevel = (new PlayerBuild)->getByOrgId($playerId, 1)[0]['build_level'];//获取官府id
        $mapElement     = (new MapElement)->dicGetOneByOriginIdAndLevel($map['map_element_origin_id'], $fuyaBuildLevel);
        $this->updateAll(['map_element_id'=>$mapElement['id'], 'map_element_level'=>$mapElement['level'], 'update_time'=>qd(), 'rowversion'=>'rowversion+1'], ['id'=>$map['id'], 'map_element_id'=>$map['map_element_id']]);
        $this->clearMapCache($player['x'], $player['y']);
    }
    /**
     * 获取帮会的超级矿信息
     * @param  int $guildId      
     * @return 
     */
    public function getGuildResourceElement($guildId){
        $all = $this->getAllByGuildId($guildId);
        foreach($all as $k=>$v) {
            if(in_array($v['map_element_id'], [301,401,501,601,701])) {
                return $v;
            }
        }
        return false;
    }
    
    /**
     * 某个地图元素数量
     * @param int $guildId     
     * @param int $mapElementId 
     * @return bool true：存在，false：不存在
     */
    public function getGuildMapElementNum($guildId, $mapElementId){
        $all = $this->getAllByGuildId($guildId);
        $count = 0;
        foreach($all as $k=>$v) {
            if($v['map_element_id']==$mapElementId && $v['status']==1) {
                $count++;
            }
        }
        return $count;
    }
    /**
     * 获取map信息
     * @param  int $guildId      
     * @param  int $mapElementId 
     * @return array               
     */
    public function getGuildMapElement($guildId, $mapElementId){
        $all = $this->getAllByGuildId($guildId);
        $re = [];
        foreach($all as $k=>$v) {
            if($v['map_element_id']==$mapElementId) {
                $re[] = $v;
            }
        }
        return $re;
    }

    /**
     * 根据blockId取信息
     * 
     * @param  [type] $blockId [description]
     * @return [type]          [description]
     */
    public function getAllByBlockId($blockId){
        $key = "MapBlock_{$blockId}";
        $re = Cache::db("map")->hGetAll($key);
        if(!$re){
            $result = [];
            $re = self::find("block_id={$blockId}")->toArray();
            $re = $this->adapter($re);
            foreach ($re as $k => $v) {
                $result[$v['x']."_".$v['y']] = $v;
            }
            Cache::db("map")->hMSet($key, $result);
        }else{
            $result = $re;
        }
        return $result;
    }

    /**
     * 把坐标转换成blockid
     * @param  [type] $x [description]
     * @param  [type] $y [description]
     * @return [type]    [description]
     */
    public function calcBlockByXy($x, $y){
        return floor($x/12)+floor($y/12)*103;
    }
	
	public static function calcXyByBlock($block){
       $y = floor($block / 103);
	   $x = ($block - $y * 103) * 12;
	   $y *= 12;
	   return ['from_x'=>$x, 'to_x'=>$x+12, 'from_y'=>$y, 'to_y'=>$y+12];
    }

    /**
     * 根据坐标取记录
     * @param  [type] $x [description]
     * @param  [type] $y [description]
     * @return [type]    [description]
     */
    public function getByXy($x, $y){
        $blockId = $this->calcBlockByXy($x, $y);
        $key = "MapBlock_{$blockId}";
        $re = Cache::db("map")->hGet($key, $x."_".$y);
        if(!$re){
            $blockRe = $this->getAllByBlockId($blockId);
            if(!empty($blockRe[$x."_".$y])){
                $re = $blockRe[$x."_".$y];
            }else{
                $re = [];
            }
        }
        return $re;
    }

    /**
     * 是否在作用范围内
     * 
     * @param  [type]  $x        设施坐标
     * @param  [type]  $y        设施坐标
     * @param  [type]  $position 事件发生地点位置 格式为[x,y]
     * @return boolean           [description]
     */
    public function isInArea($x, $y, $position){
        $MapElement = new MapElement;
        $p1 = $this->getByXy($x,$y);
        $mapElement1 = $MapElement->dicGetOne($p1['map_element_id']);
        if($this->createGuildBuildMapElementId) {
            $mapElement2 = $MapElement->dicGetOne($this->createGuildBuildMapElementId);
            $this->createGuildBuildMapElementId = false;
        } else {
            $p2 = $this->getByXy($position[0],$position[1]);
            $mapElement2 = $MapElement->dicGetOne($p2['map_element_id']);
        }
        if($mapElement1['lattice']==$mapElement2['lattice']){
            if(abs($x-$position[0])<=$mapElement1['range'] && abs($y-$position[1])<=$mapElement1['range']){
                return true;
            }else{
                return false;
            }
        }elseif($mapElement1['lattice']==1 && $mapElement2['lattice']==4){
            if($x-$position[0]<=$mapElement1['range']-1 && $x-$position[0]>=$mapElement1['range']*(-1) && $y-$position[1]<=$mapElement1['range']-1 && $y-$position[1]>=$mapElement1['range']*(-1)){
                return true;
            }else{
                return false;
            }
        }elseif($mapElement1['lattice']==4 && $mapElement2['lattice']==1){
            if($x-$position[0]<=$mapElement1['range']+1 && $x-$position[0]>=$mapElement1['range']*(-1) && $y-$position[1]<=$mapElement1['range']+1 && $y-$position[1]>=$mapElement1['range']*(-1)){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * 玩家是否在联盟范围内
     * 
     * @param  [type]  $playerId 玩家id
     * @return boolean           [description]
     */
    public function isInGuildArea($playerId){
        $Player = new Player;
        $player = $Player->getByPlayerId($playerId);
        $position = [$player['x'], $player['y']];
        $guildId = $player['guild_id'];
        if($guildId==0){
            return false;
        }else{
            $re = $this->getGuildMapElement($guildId, 101);
            foreach ($re as $value) {
                $inArea = $this->isInArea($value['x'], $value['y'], $position);
                if($inArea){
                    return true;
                }
            }
            return false;
        }
    }
    /**
     * 联盟建筑是否在联盟堡垒范围内
     * @param  int  $x       
     * @param  int  $y       
     * @param  int  $guildId 
     * @return boolean          
     */
    public function isGuildBuildInGuildArea($x, $y, $mapElementId, $guildId){
        $position = [$x, $y];
        $guildId = $guildId;
        if($guildId==0){
            return false;
        }else{
            $re = $this->getGuildMapElement($guildId, 101);
            foreach ($re as $value) {
                if($value['status']==0){
                    continue;
                }
                if($mapElementId) {
                    $this->createGuildBuildMapElementId = $mapElementId;
                }
                $inArea = $this->isInArea($value['x'], $value['y'], $position);
                if($inArea){
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * 通过帮会id读取
     * @param  [type] $guildId [description]
     * @return [type]          [description]
     */
    public function getAllByGuildId($guildId){
        if($guildId==0){
            return false;
        }
        $key = "MapGuild_{$guildId}";
        $indexList = Cache::db("map")->hGetAll($key);
        if(!$indexList){
            $indexRe = $this->find("guild_id={$guildId}")->toArray();
            foreach ($indexRe as $k => $v) {
                Cache::db("map")->hSet($key, $v['x']."_".$v['y'], "MapBlock_".$v['block_id']);
            }
            $indexList = Cache::db("map")->hGetAll($key);
        }
        $re = [];
        foreach ($indexList as $k => $v) {
            $pArr = explode("_", $k);
            $re[] = $this->getByXy($pArr[0], $pArr[1]);
        }
        return $re;
    }

    /**
     * 删除地图缓存
     * @param  int $x 
     * @param  int $y 
     */
    public function clearMapCache($x, $y){
        //清理旧公会Cache
        $re = $this->getByXy($x, $y);
        if($re){
            $guildId = $re['guild_id'];
            $key1    = "MapGuild_{$guildId}";
            Cache::db("map")->del($key1);
        }

        //清理新BlockCache
        $blockId = $this->calcBlockByXy($x, $y);
        $key = "MapBlock_{$blockId}";
        Cache::db("map")->del($key);

        //清理新公会Cache
        $re = self::findFirst("x={$x} and y={$y}");
        if($re){
            $re      = $re->toArray();
            $guildId = $re['guild_id'];
            $key2    = "MapGuild_{$guildId}";
            Cache::db("map")->del($key2);
        }
    }

    /**
     * 覆写避免出错
     * @return [type] [description]
     */
    public function clearDataCache($playerId=0, $noBasicFlag=true){
        return;
    }

    /**
     * 检查是否在中心
     * @param  [type] $x [description]
     * @param  [type] $y [description]
     * @return [type]    [description]
     */
    function checkCenterPosition($x, $y){
        if($x>=600  && $x<=635 && $y>=600  && $y<=635){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断该位置是否能安放城堡[包括其他2x2的建筑]
     * @param  [type] $position [description]
     * @param  [type] $playerId [description]
     * @return [type]           [description]
     */
    public function checkCastlePosition($position, $playerId){
        if($this->checkCenterPosition($position[0], $position[1])){
            return false;
        }
        for($x=$position[0]-1; $x<=$position[0]+1; $x++){
            for($y=$position[1]-1; $y<=$position[1]+1; $y++){
                $tmpRe = $this->getByXy($x, $y);
                
                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在城堡
                    if(!empty($tmpRe) && in_array($tmpRe['map_element_origin_id'], self::$largeElementIdList) && !($tmpRe['map_element_origin_id']==15 && $tmpRe['player_id']==$playerId)){
                        return false;
                    }
                }else{//中心，不能存在任何物体
                    if(!empty($tmpRe) && !($tmpRe['map_element_origin_id']==15 && $tmpRe['player_id']==$playerId)){
                        return false;
                    }
                }     
            }
        }
        return true;
    }

    /**
     * 检测是否可容下一般地图随机元素
     * @param  [type] $position [description]
     * @return [type]          [description]
     */
    public function checkRandElementPosition($position){
        if($this->checkCenterPosition($position[0], $position[1])){
            return false;
        }
        for($x=$position[0]; $x<=$position[0]+1; $x++){
            for($y=$position[1]; $y<=$position[1]+1; $y++){
                $tmpRe = $this->getByXy($x, $y);
                
                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在城堡
                    if(!empty($tmpRe) && in_array($tmpRe['map_element_origin_id'], self::$largeElementIdList)){
                        return false;
                    }
                }else{//中心，不能存在任何物体
                    if(!empty($tmpRe)){
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 创建一个新的城堡坐标
     * @return [type] [description]
     */
    public function getNewCastlePosition($playerId=0){
        $i = 0;
        while($i<500){
            $_x = mt_rand(12,1011);
            $_y = mt_rand(12,211);
            $rand = mt_rand(1,4);
            switch ($rand) {
                case 1:
                    $x = $_x;
                    $y = $_y;
                    break;
                case 2:
                    $x = 1000+$_y;
                    $y = $_x;
                    break;
                case 3:
                    $x = 200+$_x;
                    $y = 1000+$_y;
                    break;
                case 4:
                    $x = $_y;
                    $y = 200+$_x;
                    break;
            }
            //$x = mt_rand(516,719);
            //$y = mt_rand(516,719);

            $position = [$x,$y];
            if($this->checkCastlePosition($position, $playerId)){
                return $position;
            }
        }
        return false;
    }

    /**
     * 玩家迁城
     * @param  [type] $playerId [description]
     * @param  [type] $x        [description]
     * @param  [type] $y        [description]
     * @return [type]           [description]
     */
    public function changeCastleLocation($playerId, $x, $y){
        $canSet = $this->checkCastlePosition([$x, $y], $playerId);
        if($canSet){
            $Player = new Player;
            $PlayerCommonLog = new PlayerCommonLog;
            $player = $Player->getByPlayerId($playerId);
            if($player['is_in_map']){
                $mapInfo = $this->getByXy($player['x'], $player['y']);
                if($mapInfo['player_id']==$playerId){
                    $PlayerProjectQueue = new PlayerProjectQueue;
                    $PlayerProjectQueue->callbackGuildQueue($mapInfo['guild_id'], 5, $mapInfo['id']);
                    $this->updateAll(['x'=>$x, 'y'=>$y, 'block_id'=>$this->calcBlockByXy($x, $y), 'update_time'=>qd(), 'rowversion'=>'rowversion+1'],['id'=>$mapInfo['id'], 'player_id'=>$playerId]);
                    $PlayerCommonLog->add($playerId, ['type'=>'玩家迁城', 'from'=>$player["x"].'-'.$player["y"], 'to'=>$x."-".$y]);//日志记录
                }
            }else{
                $PlayerBuild = new PlayerBuild;
                $pc = $PlayerBuild->getByOrgId($playerId, 1);
                $level = $pc[0]['build_level'];
                $MapElement = new MapElement;
                $me = $MapElement->dicGetOneByOriginIdAndLevel(15, $level);
                $data = [   
                        'x'=>$x, 
                        'y'=>$y, 
                        'map_element_id'=>$me['id'],
                        'map_element_origin_id'=>15,
                        'map_element_level'=>$level,
                        'durability'=>$player['wall_durability_max'],
                        'max_durability'=>$player['wall_durability_max'],
                        'guild_id'=>$player['guild_id'],
                        'player_id'=>$playerId,
                        ];
                $this->addNew($data);
                $PlayerCommonLog->add($playerId, ['type'=>'城池被毁玩家迁城', 'to'=>$x."-".$y]);//日志记录
            }
            
            $Player->updateAll(['durability_last_update_time'=>qd(), 'wall_durability'=>$player['wall_durability_max'], 'fire_end_time'=>qd(), 'prev_x'=>$player['x'], 'prev_y'=>$player['y']], ['id'=>$playerId]);
            $this->clearMapCache($x, $y);
            $this->clearMapCache($player['x'], $player['y']);
            $Player->clearDataCache($playerId);
        }
        return $canSet;
    }
	
    /**
     * 军团出门前必用函数
     * 
     * @param <type> $playerId 
     * @param <type> $armyId 
     * @param <type> $needMove  
     * @param <type> $data 
     * 
     * @return <type>
     */
	public function doBeforeGoOut($playerId, $armyId=0, $needMove = false, $data=array()){
		//扣除体力
		$Player = new Player;
		if($needMove){
			if(!$Player->updateMove($playerId, -$needMove)){
				throw new Exception(10228);
			}
		}
		
		//判断是否在跨服战中
		if($Player->getByPlayerId($playerId)['is_in_cross']){
			throw new Exception(10633);//正在进行跨服战，部队无法外出
		}
		
		//判断队列数
		$maxQueueNum = $Player->getQueueNum($playerId);
		if(!@$data['ppq']){
			$PlayerProjectQueue = new PlayerProjectQueue;
			$ppq = $PlayerProjectQueue->getByPlayerId($playerId);
		}else{
			$ppq = $data['ppq'];
		}

		if(count($ppq) >= $maxQueueNum){
			throw new Exception(10229);
		}
		
		
		if($armyId){
			//获取军团
			$PlayerArmy = new PlayerArmy;
			$playerArmy = $PlayerArmy->getByArmyId($playerId, $armyId);
			if(!$playerArmy)
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			
			//判断军团是否空闲
			if($playerArmy['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//判断军团是否已经设置武将
			if(!$playerArmy['leader_general_id']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//检查army士兵数
			$Soldier = new Soldier;
			$PlayerArmyUnit = new PlayerArmyUnit;
			$pau = $PlayerArmyUnit->getByArmyId($playerId, $armyId);
			$findFlag = false;
			$generalIds = [];
			foreach($pau as $_pau){
				/*if(!$_pau['soldier_id']){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}*/
				if($_pau['soldier_id'] && $_pau['soldier_num'] > 0){
					$findFlag = true;
				}
				$generalIds[] = $_pau['general_id'];
			}
			if(!$findFlag){
				throw new Exception(10280);//部队没有士兵
			}
			
			//修改army状态
			if(!$PlayerArmy->assign($playerArmy)->updateStatus(1)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//修改武将状态
			$PlayerGeneral = new PlayerGeneral;
			if(!$PlayerGeneral->updateGooutByGeneralIds($playerId, $generalIds)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
		}
		return true;
	}
	
    public function getResourcePosition($playerId, $type){
        $Player = new Player;
        $player = $Player->getByPlayerId($playerId);

        $re = self::find(["player_id=0 and map_element_origin_id={$type}", 'order'=>"(x-{$player['x']})*(x-{$player['x']})+(y-{$player['y']})*(y-{$player['y']})", 'limit'=>1])->toArray();
        return ['x'=>$re[0]['x'], 'y'=>$re[0]['y']];
    }

    public function changePlayerGuildId($playerId, $guildId){
        $re = self::find(["player_id={$playerId}"])->toArray();
        foreach ($re as $key => $value) {
            $value['guild_id'] = $guildId;
            $this->alter($value['id'], $value);
            $this->clearMapCache($value['x'], $value['y']);
        }
    }
}