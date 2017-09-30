<?php
/**
 * 地图信息表
 */
class CrossMap extends CrossModelBase{
    public $createGuildBuildMapElementId = 0;

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
        $self->battle_id             = $data['battle_id'];
        $self->area                  = $data['area'];
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
        if(isset($data['target_area'])) {
            $self->target_area = $data['target_area'];
        } else $self->target_area = 0;

        if(isset($data['next_area'])) {
            $self->next_area = $data['next_area'];
        } else $self->next_area = 0;

        $self->update_time = $self->create_time = date('Y-m-d H:i:s');
		$self->build_time = '0000-00-00 00:00:00';
        $self->save();
    }

    public function delMap($id){
        $re = self::findFirst($id);
        if($re){
            $re->delete();
            return true;
        }else{
            return false;
        }
    }

    public function delPlayerCastle($battleId, $playerId){
        $map = self::findFirst("player_id={$playerId} and battle_id={$battleId} and map_element_origin_id=15");
        if(!empty($map)){
            $map = $map->toArray();
            $CrossPlayerProjectQueue = new CrossPlayerProjectQueue;
			$CrossPlayerProjectQueue->battleId = $battleId;
            $CrossPlayerProjectQueue->callbackPlayerQueue($battleId, $playerId);
			
            $this->delMap($map['id']);
			
            $CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
			$CrossPlayer->alter($playerId, ['is_dead'=>1, 'dead_time'=>'"'.date('Y-m-d H:i:s').'"', 'prev_x'=>$map['x'], 'prev_y'=>$map['y'], 'continue_kill'=>0]);
            $CrossPlayer->clearDataCache($playerId);
			
			$soldierNum = (new WarfareServiceConfig)->dicGetOne('wf_playercastle_respawn_soldier_count');
			$soldierIdForall = (new WarfareServiceConfig)->dicGetOne('all_soldier');
			$CrossPlayerSoldier = new CrossPlayerSoldier;
			$CrossPlayerSoldier->battleId = $battleId;
			$CrossPlayerSoldier->find(['battle_id='.$battleId.' and player_id='.$playerId])->delete();
			$CrossPlayerSoldier->clearDataCache($playerId);
			$CrossPlayerSoldier->updateSoldierNum($playerId, $soldierIdForall, $soldierNum);
			
			$CrossPlayerArmyUnit = new CrossPlayerArmyUnit;
			$CrossPlayerArmyUnit->battleId = $battleId;
			$CrossPlayerArmyUnit->updateAll(['soldier_id'=>0, 'soldier_num'=>0], ['battle_id'=>$battleId, 'player_id'=>$playerId]);
			$CrossPlayerArmyUnit->clearDataCache($playerId);
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
            //$re->delete();
            $flag = $re->affectedRows();
        }else{
            if(isset($fields['build_time'])){
                if(is_numeric($fields['build_time'])){
                    $fields['build_time'] = "'".date('Y-m-d H:i:s', $fields['build_time'])."'";
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
            $fields['rowversion'] = $re->rowversion+1;
            //$flag = $re->save($fields);
            $flag = $this->updateAll($fields, ['id'=>$id]);
        }
        return $flag;
    }

    /**
     * 玩家升级后，需要升级世界地图的map_element
     * @param  int $playerId
     */
//    public function levelUp($playerId){
//        $player         = (new Player)->getByPlayerId($playerId);
//        $map            = $this->getByXy($player['x'], $player['y']);
//        $fuyaBuildLevel = (new PlayerBuild)->getByOrgId($playerId, 1)[0]['build_level'];//获取官府id
//        $mapElement     = (new MapElement)->dicGetOneByOriginIdAndLevel($map['map_element_origin_id'], $fuyaBuildLevel);
//        $this->updateAll(['map_element_id'=>$mapElement['id'], 'map_element_level'=>$mapElement['level'], 'update_time'=>qd(), 'rowversion'=>'rowversion+1'], ['id'=>$map['id'], 'map_element_id'=>$map['map_element_id']]);
//        $this->clearMapCache($player['x'], $player['y']);
//    }
    /**
     * 获取帮会的超级矿信息
     * @param  int $guildId
     * @return
     */
//    public function getGuildResourceElement($guildId){
//        $all = $this->getAllByGuildId($guildId);
//        foreach($all as $k=>$v) {
//            if(in_array($v['map_element_id'], [301,401,501,601,701])) {
//                return $v;
//            }
//        }
//        return false;
//    }

    /**
     * 某个地图元素数量
     * @param int $guildId
     * @param int $mapElementId
     * @return bool true：存在，false：不存在
     */
    public function getGuildMapElementNum($battleId, $guildId, $mapElementId){
        $all = $this->getAllByGuildId($battleId, $guildId);
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
    public function getGuildMapElement($battleId, $guildId, $mapElementId){
        $all = $this->getAllByGuildId($battleId, $guildId);
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
    public function getAllByBlockId($battleId, $blockId){
        $result = [];
        $re = self::find("battle_id={$battleId} and block_id={$blockId}")->toArray();
        $re = $this->adapter($re);
        foreach ($re as $k => $v) {
            $result[$v['x']."_".$v['y']] = $v;
        }
        return $result;
    }

    /**
     * 获取区域内信息
     * @param $area
     */
    function getAllByArea($battleId, $area){
        $result = [];
        $re = self::find("battle_id={$battleId} and area={$area}")->toArray();
        $re = $this->adapter($re);
        foreach ($re as $k => $v) {
            $result[$v['x']."_".$v['y']] = $v;
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
        return floor($x/12)+floor($y/12)*7;
    }

    public static function calcXyByBlock($block){
        $y = floor($block / 7);
        $x = ($block - $y * 7) * 12;
        $y *= 12;
        return ['from_x'=>$x, 'to_x'=>$x+12, 'from_y'=>$y, 'to_y'=>$y+12];
    }

    /**
     * 根据坐标取记录
     * @param  [type] $x [description]
     * @param  [type] $y [description]
     * @return [type]    [description]
     */
    public function getByXy($battleId, $x, $y){
        $re = self::findFirst(["battle_id={$battleId} and x={$x} and y={$y}"]);
        if($re){
            $re = $re->toArray();
        }else{
            $re = [];
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
    public function isInArea($battleId, $x, $y, $position){
        $MapElement = new MapElement;
        $p1 = $this->getByXy($battleId, $x, $y);
        $mapElement1 = $MapElement->dicGetOne($p1['map_element_id']);
        if($this->createGuildBuildMapElementId) {
            $mapElement2 = $MapElement->dicGetOne($this->createGuildBuildMapElementId);
            $this->createGuildBuildMapElementId = false;
        } else {
            $p2 = $this->getByXy($battleId, $position[0],$position[1]);
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
//    public function isInGuildArea($playerId){
//        $Player = new Player;
//        $player = $Player->getByPlayerId($playerId);
//        $position = [$player['x'], $player['y']];
//        $guildId = $player['guild_id'];
//        if($guildId==0){
//            return false;
//        }else{
//            $re = $this->getGuildMapElement($guildId, 101);
//            foreach ($re as $value) {
//                $inArea = $this->isInArea($value['x'], $value['y'], $position);
//                if($inArea){
//                    return true;
//                }
//            }
//            return false;
//        }
//    }
    /**
     * 联盟建筑是否在联盟堡垒范围内
     * @param  int  $x
     * @param  int  $y
     * @param  int  $guildId
     * @return boolean
     */
//    public function isGuildBuildInGuildArea($x, $y, $mapElementId, $guildId){
//        $position = [$x, $y];
//        $guildId = $guildId;
//        if($guildId==0){
//            return false;
//        }else{
//            $re = $this->getGuildMapElement($guildId, 101);
//            foreach ($re as $value) {
//                if($value['status']==0){
//                    continue;
//                }
//                if($mapElementId) {
//                    $this->createGuildBuildMapElementId = $mapElementId;
//                }
//                $inArea = $this->isInArea($value['x'], $value['y'], $position);
//                if($inArea){
//                    return true;
//                }
//            }
//            return false;
//        }
//    }

    /**
     * 通过帮会id读取
     * @param  [type] $guildId [description]
     * @return [type]          [description]
     */
    public function getAllByGuildId($battleId, $guildId){
        if($guildId==0) {
            return false;
        }

        $indexRe = $this->find("battle_id={$battleId} and guild_id={$guildId}")->toArray();
        $re = [];
        foreach ($indexRe as $k => $v) {
            $re[] = $this->getByXy($battleId, $v['x'], $v['y']);
        }
        return $re;
    }

    /**
     * 删除地图缓存
     * @param  int $x
     * @param  int $y
     */
//    public function clearMapCache($x, $y){
//        //清理旧公会Cache
//        $re = $this->getByXy($x, $y);
//        if($re){
//            $guildId = $re['guild_id'];
//            $key1    = "MapGuild_{$guildId}";
//            Cache::db("map")->del($key1);
//        }
//
//        //清理新BlockCache
//        $blockId = $this->calcBlockByXy($x, $y);
//        $key = "MapBlock_{$blockId}";
//        Cache::db("map")->del($key);
//
//        //清理新公会Cache
//        $re = self::findFirst("x={$x} and y={$y}");
//        if($re){
//            $re      = $re->toArray();
//            $guildId = $re['guild_id'];
//            $key2    = "MapGuild_{$guildId}";
//            Cache::db("map")->del($key2);
//        }
//    }

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
//    function checkCenterPosition($x, $y){
//        if($x>=600  && $x<=635 && $y>=600  && $y<=635){
//            return true;
//        }else{
//            return false;
//        }
//    }

    /**
     * 判断该位置是否能安放城堡[包括其他2x2的建筑]
     * @param  [type] $position [description]
     * @param  [type] $playerId [description]
     * @return [type]           [description]
     */
    public function checkCastlePosition($battleId, $position, $playerId){
        $CrossMapConfig = new CrossMapConfig;
        $CrossBattle = new CrossBattle;
        $cbInfo = $CrossBattle->getBattle($battleId);
        $mapType = $cbInfo['map_type'];

        for($x=$position[0]-1; $x<=$position[0]+1; $x++){
            for($y=$position[1]-1; $y<=$position[1]+1; $y++){
                $tmpRe = $this->getByXy($battleId, $x, $y);
                $tmpRe2 = $CrossMapConfig->getByXy($mapType, $x, $y) ;

                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在城堡
                    if(!empty($tmpRe) && in_array($tmpRe['map_element_origin_id'], Map::$largeElementIdList) && !($tmpRe['map_element_origin_id']==15 && $tmpRe['player_id']==$playerId)){
                        return false;
                    }
                }else{//中心，不能存在任何物体
                    if(!empty($tmpRe) && !($tmpRe['map_element_origin_id']==15 && $tmpRe['player_id']==$playerId)){
                        return false;
                    }
                    if(!empty($tmpRe2) && $tmpRe2['cross_map_element_id']>0){
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
    public function checkRandElementPosition($battleId, $position){
//        if($this->checkCenterPosition($position[0], $position[1])){
//            return false;
//        }
        $Map = new Map;
        for($x=$position[0]; $x<=$position[0]+1; $x++){
            for($y=$position[1]; $y<=$position[1]+1; $y++){
                $tmpRe = $this->getByXy($battleId, $x, $y);

                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在城堡
                    if(!empty($tmpRe) && in_array($tmpRe['map_element_origin_id'], Map::$largeElementIdList)){
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
    public function getNewCastlePosition($playerId, $guildId, $battleId, $mapType, $area){
        $CrossBattle = new CrossBattle;
        $isAttack = $CrossBattle->isAttack($guildId, $battleId);
        if($isAttack){
            $sidesType = 1;
        }else{
            $sidesType = 2;
        }
        $CrossMapConfig = new CrossMapConfig;
        $locationList = $CrossMapConfig->getByMapArea($mapType, $area);
        shuffle($locationList);
        foreach($locationList as $v){
            if($v['cross_map_element_id']==0 && $v['sides_type']==$sidesType){
                $x = $v['x'];
                $y = $v['y'];
                $position = [$x,$y];
                if($this->checkCastlePosition($battleId, $position, $playerId)){
                    return $position;
                }
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
    public function changeCastleLocation($battleId, $playerId, $x, $y, $areaId){
        $canSet = $this->checkCastlePosition($battleId, [$x, $y], $playerId);
        if($canSet){
            $CrossPlayer = new CrossPlayer;
			$CrossPlayer->battleId = $battleId;
            $PlayerCommonLog = new PlayerCommonLog;
            $player = $CrossPlayer->getByPlayerId($playerId);
            if($player['is_in_map']){
                $mapInfo = $this->getByXy($battleId, $player['x'], $player['y']);
                if($mapInfo['player_id']==$playerId){
                    //$PlayerProjectQueue = new CrossPlayerProjectQueue;
                    //$PlayerProjectQueue->callbackGuildQueue($mapInfo['guild_id'], 5, $mapInfo['id']);
                    $this->updateAll(['x'=>$x, 'y'=>$y, 'block_id'=>$this->calcBlockByXy($x, $y), 'area'=>$areaId, 'update_time'=>qd(), 'rowversion'=>'rowversion+1'],['id'=>$mapInfo['id'], 'player_id'=>$playerId]);
                    $PlayerCommonLog->add($playerId, ['type'=>'跨服战玩家迁城', 'from'=>$player["x"].'-'.$player["y"], 'to'=>$x."-".$y]);//日志记录
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
                    'battle_id'=>$battleId,
                    'area'=>$areaId,
                    'map_element_id'=>$me['id'],
                    'map_element_origin_id'=>15,
                    'map_element_level'=>$level,
                    'durability'=>$player['wall_durability_max'],
                    'max_durability'=>$player['wall_durability_max'],
                    'guild_id'=>$player['guild_id'],
                    'player_id'=>$playerId,
                ];
                $this->addNew($data);
                $PlayerCommonLog->add($playerId, ['type'=>'跨服战玩家复活', 'to'=>$x."-".$y]);//日志记录
                $CrossPlayer->updateAll(['wall_durability'=>$player['wall_durability_max'], 'prev_x'=>$player['x'], 'prev_y'=>$player['y']], ['battle_id'=>$battleId, 'player_id'=>$playerId]);
                $CrossPlayer->clearDataCache($playerId);
            }


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
    public function doBeforeGoOut($battleId, $playerId, $armyId=0, $needMove = false, $data=array()){
        //扣除体力
        $Player = new CrossPlayer;
		$Player->battleId = $battleId;
        /*if($needMove){
            if(!$Player->updateMove($playerId, -$needMove)){
                throw new Exception(10228);
            }
        }*/

        //判断队列数
        $maxQueueNum = $Player->getQueueNum($playerId);
        if(!@$data['ppq']){
            $PlayerProjectQueue = new CrossPlayerProjectQueue;
			$PlayerProjectQueue->battleId = $battleId;
            $ppq = $PlayerProjectQueue->getByPlayerId($playerId);
        }else{
            $ppq = $data['ppq'];
        }

        if(count($ppq) >= $maxQueueNum){
            throw new Exception(10229);
        }


        if($armyId){
            //获取军团
            $PlayerArmy = new CrossPlayerArmy;
			$PlayerArmy->battleId = $battleId;
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
            $PlayerArmyUnit = new CrossPlayerArmyUnit;
			$PlayerArmyUnit->battleId = $battleId;
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
            $PlayerGeneral = new CrossPlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
            if(!$PlayerGeneral->updateGooutByGeneralIds($playerId, $generalIds)){
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
            }
        }
        return true;
    }

    public function getResourcePosition($battleId, $playerId, $type){
        $CrossPlayer = new CrossPlayer;
		$CrossPlayer->battleId = $battleId;
        $player = $CrossPlayer->getByPlayerId($playerId);

        $re = self::find(["player_id=0 and battle_id={$battleId} and map_element_origin_id={$type}", 'order'=>"(x-{$player['x']})*(x-{$player['x']})+(y-{$player['y']})*(y-{$player['y']})", 'limit'=>1])->toArray();
        return ['x'=>$re[0]['x'], 'y'=>$re[0]['y']];
    }

    public function changePlayerGuildId($playerId, $guildId){
        $re = self::find(["player_id={$playerId}"])->toArray();
        foreach ($re as $key => $value) {
            $value['guild_id'] = $guildId;
            $this->alter($value['id'], $value);
        }
    }

    public function isVisibleArea($battleId, $guildId, $area){
        $re = self::find("battle_id={$battleId} and guild_id={$guildId} and area={$area}")->toArray();
        if($re){
            return true;
        }else{
            return false;
        }
    }


	public function rebuildBuilding(&$data){
		if(!in_array($data['map_element_origin_id'], [301, 304])){
			return false;
		}
		if(is_numeric($data['recover_time'])){
			$recoverTime = $data['recover_time'];
		}else{
			$recoverTime = strtotime($data['recover_time']);
		}
		if(!$data['durability'] && $recoverTime <= time()){
			$data['durability'] = $data['max_durability'];
			$this->alter($data['id'], ['durability'=>'max_durability']);
		}
		return true;
	}

    /**
     * 跨服战 匹配后生成battle地图
     *
     * @param $battleId
     */
	public function initBattleMap($battleId) {
        $CrossMapConfig = new CrossMapConfig;
        $MapElement     = new MapElement;

        self::find("battle_id={$battleId}")->delete();//删除已有
        $cacheKey = __METHOD__."_battle_map_init";
        $battleMap = Cache::db('cache', 'Cross')->get($cacheKey);
        if(empty($battleMap)) {
            $battleMap = $CrossMapConfig->sqlGet("select * from cross_map_config where cross_map_element_id not in (1801, 0);");
            Cache::db('cache', 'Cross')->setex($cacheKey, 3600, $battleMap);
        }
        foreach($battleMap as $v) {
            $mapElementOne                 = $MapElement->dicGetOne($v['cross_map_element_id']);
            $data['x']                     = $v['x'];
            $data['y']                     = $v['y'];
            $data['map_element_id']        = $v['cross_map_element_id'];
            $data['map_element_origin_id'] = $mapElementOne['origin_id'];
            $data['map_element_level']     = $mapElementOne['level'];
            $data['battle_id']             = $battleId;
            $data['area']                  = $v['area'];
            $data['resource']              = 0;
            $data['durability']            = $v['max_durability'];
            $data['max_durability']        = $v['max_durability'];
            $data['next_area']             = $v['next_area'];
            $data['target_area']           = $v['target_area'];
            $data['status']                = 1;
            $this->addNew($data);
        }
    }

    public function getSpBuild($battleId){
        $re = self::find("battle_id={$battleId} and map_element_origin_id!=15");
        if(!empty($re)){
            $re = $this->adapter($re->toArray());
            return $re;
        }else{
            return false;
        }
    }
}