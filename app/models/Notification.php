<?php
/**
 * 通知类型表
 */
class Notification extends ModelBase{
    /**
     * 字典表获取所有 通知类型, 以name为key
     * 
     * @return <type>
     */
    public function dicGetAll(){
        $ret = $this->cache(get_class($this), function() {
            return $this->findList('name');
        });
        return $ret;
    }
}