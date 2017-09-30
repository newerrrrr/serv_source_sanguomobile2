<?php
/**
 * 祭天掉落
 */
class GambleGeneralSoul extends ModelBase{
    /**
     * 获取祭天掉落drop ids
     * @return array
     */
    public function getDropIds(){
        $re = $this->dicGetAll();
        $r = [];
        foreach($re as $k=>$v) {
            $r[$v['id']] = intval($v['drop_id']);
        }
        return $r;
    }
}