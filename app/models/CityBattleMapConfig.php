<?php
/**
 * 地图配置表
 */
class CityBattleMapConfig extends CityBattleModelBase{
    function getByMapArea($type, $part, $area=0){
        if(empty($area)){
            $re = $this->find("map_type={$type} and part={$part}");
        }else{
            $re = $this->find("map_type={$type} and part={$part} and area={$area}");
        }
        if($re){
            return $re->toArray();
        }else{
            return [];
        }
    }

    function getByMapSection($type, $part, $section){
        if(empty($section)){
            $re = $this->find("map_type={$type} and part={$part}");
        }else{
            $re = $this->find("map_type={$type} and part={$part} and section={$section}");
        }
        if($re){
            return $re->toArray();
        }else{
            return [];
        }
    }

    function getByXy($type, $x, $y, $part){
        $re = $this->find("map_type={$type} and part={$part} and x={$x} and y={$y}")->toArray();
        if($re){
            return $re[0];
        }else{
            return [];
        }
    }

    function getAreaByXy($type, $x, $y, $part=1){
        $re = $this->getByXy($type, $x, $y, $part);
        if($re){
            return $re['area'];
        }else{
            return false;
        }
    }

    function getSectionByXy($type, $x, $y, $part){
        $re = $this->getByXy($type, $x, $y, $part);
        if($re){
            return $re['section'];
        }else{
            return false;
        }
    }

    /**
     * 地图类型接口 //extension
     * @return int
     */
    public static function getMapType(){
        return 1;
    }
}