<?php
/**
 * 地图配置表
 */
class CrossMapConfig extends CrossModelBase{
    function getByMapArea($type, $area=0){
        if(empty($area)){
            $re = $this->find("map_type={$type}");
        }else{
            $re = $this->find("map_type={$type} and area={$area}");
        }
        if($re){
            return $re->toArray();
        }else{
            return [];
        }
    }

    function getByXy($type, $x, $y){
        $re = $this->find("map_type={$type} and x={$x} and y={$y}");
        if($re){
            $re = $re->toArray();
            return $re[0];
        }else{
            return [];
        }
    }

    function getAreaByXy($type, $x, $y){
        $re = $this->getByXy($type, $x, $y);
        if($re){
            return $re['area'];
        }else{
            return false;
        }
    }

    /**
     * 判断该位置是否能安放城堡[包括其他2x2的建筑]
     * @param  [type] $position [description]
     * @return [type]           [description]
     */
    public function checkLargeElementPosition($type, $position){

        for($x=$position[0]-1; $x<=$position[0]+1; $x++){
            for($y=$position[1]-1; $y<=$position[1]+1; $y++){
                $tmpRe = $this->getByXy($type, $x, $y);

                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在城堡
                    if(!empty($tmpRe) && in_array($tmpRe['cross_map_element_id'], Map::$largeElementIdList)){
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
     * 检测是否可容下一般地图随机元素
     * @param $type 战场地图类型
     * @param $position 位置［x,y］
     * @param int $isLargeElement 是否为大型单位
     * @return bool
     */
    public function checkRandElementPosition($type, $position, $isLargeElement=0){
        if($isLargeElement){
            $offset = -1;
        }else{
            $offset = 0;
        }
        for($x=$position[0]+$offset; $x<=$position[0]+1; $x++){
            for($y=$position[1]+$offset; $y<=$position[1]+1; $y++){
                $tmpRe = $this->getByXy($type, $x, $y);
                if($x==$position[0]+1 || $y==$position[1]+1){//边缘，不能存在大型单位
                    if(!empty($tmpRe) && in_array($tmpRe['cross_map_element_id'], Map::$largeElementIdList)){
                        return false;
                    }
                }else{//中心，不能存在任何
                    if(!empty($tmpRe)){
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 地图类型接口 //extension
     * @return int
     */
    public static function getMapType(){
        return 1;
    }
}