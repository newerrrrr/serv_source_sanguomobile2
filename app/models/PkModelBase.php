<?php

/**
 * pk的model base类，继承自ModelBase
 *
 */
class PkModelBase extends ModelBase{
    /**
     * 武斗pk库的初始化指向
     */
    public function initialize(){
        $this->setConnectionService('db_pk_server');
		self::setup(['notNullValidations'=>false]);
    }
}