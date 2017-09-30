<?php

/**
 * duel_initdata字典表
 *
 */
class DuelInitdata extends ModelBase{
    /**
     * @return mixed
     */
    public function get(){
        $id = 1;
        return self::dicGetOne($id);
    }
}