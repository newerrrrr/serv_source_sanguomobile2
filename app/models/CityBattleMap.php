<?php
/**
 * 地图信息表
 */
class CityBattleMap extends CityBattleModelBase{
    public $createGuildBuildMapElementId = 0;
	public $cb;

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
        $self->target_map_element_id = $data['target_map_element_id'];
        $self->battle_id             = $data['battle_id'];
        $self->area                  = $data['area'];
		$self->status				 = $data['status'];
        $self->rowversion = 1;
        $self->resource     = @$data['resource']*1;
        if(isset($data['durability'])) {
            $self->durability = $data['durability'];
        }
        if(isset($data['max_durability'])) {
            $self->max_durability = $data['max_durability'];
        }
        /*if(isset($data['status'])) {//联盟建筑
            $self->status = $data['status'];
        }*/

        if(isset($data['camp_id'])) {
            $self->camp_id = $data['camp_id'];
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
		
		if(isset($data['part'])) {
			$self->part = $data['part'];
		} else $self->part = $this->getCurrentPart($self->battle_id);
		
		if(isset($data['section'])) {
			$self->section = $data['section'];
		} else $self->section = 0;

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
        $map = self::findFirst("player_id={$playerId} and battle_id={$battleId} and status=1 and map_element_origin_id=406");
        if(!empty($map)){
            $map = $map->toArray();
            $CityBattlePlayerProjectQueue = new CityBattlePlayerProjectQueue;
			$CityBattlePlayerProjectQueue->battleId = $battleId;
            $CityBattlePlayerProjectQueue->callbackPlayerQueue($battleId, $playerId);
			
			//刷新分数
			if($map['part'] == 2)
				(new CityBattleTask)->_refreshScore($battleId);
			
            $this->delMap($map['id']);
			
            $CityBattlePlayer = new CityBattlePlayer;
			$CityBattlePlayer->battleId = $battleId;
			$CityBattlePlayer->alter($playerId, ['is_dead'=>1, 'dead_time'=>'"'.date('Y-m-d H:i:s').'"', 'prev_x'=>$map['x'], 'prev_y'=>$map['y'], 'continue_kill'=>0]);
            $CityBattlePlayer->clearDataCache($playerId);
						
			$CityBattlePlayerArmyUnit = new CityBattlePlayerArmyUnit;
			$CityBattlePlayerArmyUnit->battleId = $battleId;
			$CityBattlePlayerArmyUnit->fullfill($playerId);
			//$CityBattlePlayerArmyUnit->updateAll(['soldier_num'=>0], ['battle_id'=>$battleId, 'player_id'=>$playerId]);
			//$CityBattlePlayerArmyUnit->clearDataCache($playerId);
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
    public function getCampMapElementNum($battleId, $campId, $mapElementId){
        $all = $this->getAllByCampId($battleId, $campId);
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
    public function getCampMapElement($battleId, $campId, $mapElementId){
        $all = $this->getAllByCampId($battleId, $campId);
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
        $re = self::find("battle_id={$battleId} and status=1 and block_id={$blockId}")->toArray();
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
        $re = self::find("battle_id={$battleId} and status=1 and area={$area}")->toArray();
        $re = $this->adapter($re);
        foreach ($re as $k => $v) {
            $result[$v['x']."_".$v['y']] = $v;
        }
        return $result;
    }
	
	function getAllBySection($battleId, $section){
        $result = [];
        $re = self::find("battle_id={$battleId} and status=1 and section={$section}")->toArray();
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
        $re = self::findFirst(["battle_id={$battleId} and status=1 and x={$x} and y={$y}"]);
        if($re){
            $re = $re->toArray();
        }else{
            $re = [];
        }
        return $re;
    }
	
	public function getCurrentPart($battleId=0){
		if(!$this->cb){
			$CityBattle = new CityBattle;
			$cb = $CityBattle->getBattle($battleId);
		}else{
			$cb = $this->cb;
		}
		if(!$cb)
			return false;
		if($CityBattle->inSeige($cb))
			return 1;
		return 2;
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
    public function getAllByCampId($battleId, $campId){
        if($campId==0) {
            return false;
        }
        $indexRe = $this->find("battle_id={$battleId} and status=1 and camp_id={$campId}")->toArray();
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
        $CityBattleMapConfig = new CityBattleMapConfig;
        $CityBattle = new CityBattle;
        $cbInfo = $CityBattle->getBattle($battleId);
        $mapType = $cbInfo['map_type'];
        $part = $this->getCurrentPart($battleId);

        /*for($x=$position[0]-1; $x<=$position[0]+1; $x++){
            for($y=$position[1]-1; $y<=$position[1]+1; $y++){
                $tmpRe = $this->getByXy($battleId, $x, $y);
                $tmpRe2 = $CityBattleMapConfig->getByXy($mapType, $x, $y, $part) ;

                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在城堡
                    if(!empty($tmpRe) && in_array($tmpRe['map_element_origin_id'], Map::$largeElementIdList) && !($tmpRe['map_element_origin_id']==406 && $tmpRe['player_id']==$playerId)){
                        return false;
                    }
                }else{//中心，不能存在任何物体
                    if(!empty($tmpRe) && !($tmpRe['map_element_origin_id']==406 && $tmpRe['player_id']==$playerId)){
                        return false;
                    }
                    if(!empty($tmpRe2) && $tmpRe2['city_battle_map_element_id']>0){
                        return false;
                    }
                }
            }
        }*/
		$x = $position[0];
		$y = $position[1];
		$tmpRe = $this->getByXy($battleId, $x, $y);
		if(!empty($tmpRe) && !($tmpRe['map_element_origin_id']==406 && $tmpRe['player_id']==$playerId)){
			return false;
		}
		$tmpRe2 = $CityBattleMapConfig->getByXy($mapType, $x, $y, $part) ;
		if(!empty($tmpRe2) && $tmpRe2['city_battle_map_element_id']>0){
			return false;
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
        $Map = new CityBattleMap;
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
    public function getNewCastlePosition($playerId, $campId, $battleId, $mapType, $targetId, $isSiege=true){
        $CityBattle = new CityBattle;
        $isAttack = $CityBattle->isAttack($campId, $battleId);
        if($isAttack){
            $sidesType = 1;
        }else{
            $sidesType = 2;
        }
        $CityBattleMapConfig = new CityBattleMapConfig;
        $part = $this->getCurrentPart($battleId);
        if($isSiege){
            $locationList = $CityBattleMapConfig->getByMapArea($mapType, $part, $targetId);
        }else{
            $locationList = $CityBattleMapConfig->getByMapSection($mapType, $part, $targetId);
        }

        shuffle($locationList);
        foreach($locationList as $v){
            if($v['city_battle_map_element_id']==0 && $v['sides_type']==$sidesType){
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
    public function changeCastleLocation($battleId, $playerId, $x, $y, $areaId, $isSiege=true, $sectionId=0){
        $canSet = $this->checkCastlePosition($battleId, [$x, $y], $playerId);
        if($canSet){
            if($sectionId==0){
                $CityBattleMapConfig = new CityBattleMapConfig;
                $type = 1;
                $part= $isSiege?1:2;
                $sectionId = $CityBattleMapConfig->getSectionByXy($type, $x, $y, $part);
            }
            $CityBattlePlayer = new CityBattlePlayer;
			$CityBattlePlayer->battleId = $battleId;
            $CityBattleCommonLog = new CityBattleCommonLog;
            $player = $CityBattlePlayer->getByPlayerId($playerId);
            if($player['is_in_map']){

                $mapInfo = $this->getByXy($battleId, $player['x'], $player['y']);
                if($mapInfo['player_id']==$playerId){
                    //$PlayerProjectQueue = new CrossPlayerProjectQueue;
                    //$PlayerProjectQueue->callbackGuildQueue($mapInfo['guild_id'], 5, $mapInfo['id']);
                    if($isSiege){
                        $this->updateAll(['x'=>$x, 'y'=>$y, 'block_id'=>$this->calcBlockByXy($x, $y), 'area'=>$areaId, 'section'=>$sectionId, 'update_time'=>qd(), 'rowversion'=>'rowversion+1'],['id'=>$mapInfo['id'], 'player_id'=>$playerId]);
                        $CityBattlePlayer->updateAll(['change_location_time'=>"'".date("Y-m-d H:i:s")."'"], ['id'=>$player['id']]);
                        $CityBattleCommonLog->add($battleId, $playerId, $player['camp_id'], '攻城战玩家迁城|from:'.$player["x"].'-'.$player["y"].'|to:'.$x."-".$y);//日志记录
                    }else{
                        $this->updateAll(['x'=>$x, 'y'=>$y, 'block_id'=>$this->calcBlockByXy($x, $y), 'area'=>$areaId, 'section'=>$sectionId, 'update_time'=>qd(), 'rowversion'=>'rowversion+1'],['id'=>$mapInfo['id'], 'player_id'=>$playerId]);
                        $CityBattlePlayer->updateAll(['change_location_time'=>"'".date("Y-m-d H:i:s")."'"], ['id'=>$player['id']]);
                        $CityBattleCommonLog->add($battleId, $playerId, $player['camp_id'], '内城战玩家迁城|from:'.$player["x"].'-'.$player["y"].'|to:'.$x."-".$y);//日志记录
                    }
                }
            }else{
                $PlayerBuild = new PlayerBuild;
                $pc = $PlayerBuild->getByOrgId($playerId, 1);
                $level = $pc[0]['build_level'];
                $MapElement = new MapElement;
                $me = $MapElement->dicGetOneByOriginIdAndLevel(406, 1);
                $data = [
                    'x'=>$x,
                    'y'=>$y,
                    'battle_id'=>$battleId,
                    'part'=>$isSiege?1:2,
                    'area'=>$areaId,
                    'section'=>$sectionId,
                    'status'=>1,
                    'map_element_id'=>$me['id'],
                    'map_element_origin_id'=>406,
                    'map_element_level'=>$level,
                    'target_map_element_id'=>0,
                    'durability'=>$player['wall_durability_max'],
                    'max_durability'=>$player['wall_durability_max'],
                    'camp_id'=>$player['camp_id'],
                    'player_id'=>$playerId,
                ];
                $this->addNew($data);
                if($isSiege){
                    $CityBattleCommonLog->add($battleId, $playerId, $player['camp_id'], '攻城战玩家复活|to:'.$x."-".$y);//日志记录
                }else{
                    $CityBattleCommonLog->add($battleId, $playerId, $player['camp_id'], '内城战玩家复活|to:'.$x."-".$y);//日志记录
                }

                $CityBattlePlayer->updateAll(['change_location_time'=>"'".date("Y-m-d 00:00:00")."'", 'wall_durability'=>$player['wall_durability_max'], 'prev_x'=>$player['x'], 'prev_y'=>$player['y']], ['battle_id'=>$battleId, 'player_id'=>$playerId]);
                $CityBattlePlayer->clearDataCache($playerId);
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
        $Player = new CityBattlePlayer;
		$Player->battleId = $battleId;
        /*if($needMove){
            if(!$Player->updateMove($playerId, -$needMove)){
                throw new Exception(10228);
            }
        }*/

        //判断队列数
        $maxQueueNum = $Player->getQueueNum($playerId);
        if(!@$data['ppq']){
            $PlayerProjectQueue = new CityBattlePlayerProjectQueue;
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
            $PlayerArmy = new CityBattlePlayerArmy;
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
            $PlayerArmyUnit = new CityBattlePlayerArmyUnit;
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
            $PlayerGeneral = new CityBattlePlayerGeneral;
			$PlayerGeneral->battleId = $battleId;
            if(!$PlayerGeneral->updateGooutByGeneralIds($playerId, $generalIds)){
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
            }
        }
        return true;
    }

    public function getResourcePosition($battleId, $playerId, $type){
        $CityBattlePlayer = new CityBattlePlayer;
		$CityBattlePlayer->battleId = $battleId;
        $player = $CityBattlePlayer->getByPlayerId($playerId);
        $re = self::find(["player_id=0 and battle_id={$battleId} and status=1 and map_element_origin_id={$type}", 'order'=>"(x-{$player['x']})*(x-{$player['x']})+(y-{$player['y']})*(y-{$player['y']})", 'limit'=>1])->toArray();
        return ['x'=>$re[0]['x'], 'y'=>$re[0]['y']];
    }

    public function changePlayerCampId($playerId, $campId){
        $re = self::find(["player_id={$playerId}"])->toArray();
        foreach ($re as $key => $value) {
            $value['camp_id'] = $campId;
            $this->alter($value['id'], $value);
        }
    }

    public function isVisibleArea($battleId, $campId, $area){
        $re = self::find("battle_id={$battleId} and status=1 and camp_id={$campId} and area={$area}")->toArray();
        if($re){
            return true;
        }else{
            return false;
        }
    }


	public function rebuildBuilding(&$data){
		if(!in_array($data['map_element_origin_id'], [402, 403])){
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
        $CityBattleMapConfig = new CityBattleMapConfig;
        $MapElement          = new MapElement;

        self::find("battle_id={$battleId}")->delete();//删除已有
        $cacheKey  = __METHOD__ . "_battle_map_init";
        $battleMap = Cache::db('cache', 'CityBattle')->get($cacheKey);
        if(empty($battleMap)) {
            $battleMap = $CityBattleMapConfig->sqlGet("select * from city_battle_map_config where city_battle_map_element_id >0;");
            Cache::db('cache', 'CityBattle')->setex($cacheKey, 3600, $battleMap);
        }
        foreach($battleMap as $v) {
            $mapElementOne                 = $MapElement->dicGetOne($v['city_battle_map_element_id']);
            $data['x']                     = $v['x'];
            $data['y']                     = $v['y'];
            $data['map_element_id']        = $v['city_battle_map_element_id'];
            $data['map_element_origin_id'] = $mapElementOne['origin_id'];
            $data['map_element_level']     = $mapElementOne['level'];
            $data['target_map_element_id'] = $v['target_map_element_id'];
            $data['battle_id']             = $battleId;
            $data['part']                  = $v['part'];
            $data['area']                  = $v['area'];
            $data['section']               = $v['section'];
            $data['resource']              = 0;
            $data['durability']            = $v['max_durability'];
            $data['max_durability']        = $v['max_durability'];
            $data['next_area']             = $v['next_area'];
            $data['target_area']           = $v['target_area'];
            $data['status']                = 0;
            $this->addNew($data);
        }
    }

    public function getSpBuild($battleId){
        $re = self::find("battle_id={$battleId} and status=1 and map_element_origin_id!=406");
        if(!empty($re)){
            $re = $this->adapter($re->toArray());
            return $re;
        }else{
            return false;
        }
    }
}