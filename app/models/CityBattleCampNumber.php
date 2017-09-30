<?php
/**
 * 阵营人数
 *
 */
class CityBattleCampNumber extends CityBattleModelBase{
    /**
     * 增加
     */
    public function inc($campId, $number=1){
        $this->updateAll(['number'=>"greatest(0, number+{$number})"], ['camp_id'=>$campId]);
    }

    /**
     * 减少
     */
    public function dec($campId, $number=1){
        $this->updateAll(['number'=>"greatest(0, number-{$number})"], ['camp_id'=>$campId]);
    }

    /**
     * 获取所有阵营人数
     *
     * @return mixed
     */
    public function getAll(){
        $re = self::find(["order"=>'number asc'])->toArray();
        $r = Set::combine($re, '{n}.camp_id', '{n}');
        return $r;
    }
}
