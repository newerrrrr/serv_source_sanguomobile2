<?php
/**
 * 玩家装备-宝物
 */
class PlayerEquipMasterSkill extends ModelBase{
    /**
     * 新建
     * @param  int $pemId          
     * @param  int $equipSkillId    
     * @param  int $equipSkillValue 
     */
    public function newPlayerEquipMasterSkill($pemId, $equipSkillId, $buffId, $equipSkillValue){
        $self                         = new self;
        $self->player_equip_master_id = $pemId;
        $self->equip_skill_id         = $equipSkillId;
        $self->equip_skill_value      = $equipSkillValue;
        $self->buff_id                = $buffId;
        $self->create_time            = $self->update_time = date('Y-m-d H:i:s');
        $self->save();
    }
    /**
     * 获取
     * @param  int $pemId 
     * @return array
     */
    public function getPlayerEquipMasterSkill($pemId){
        $re = self::find("player_equip_master_id={$pemId}");
        $r = Set::combine($re->toArray(), "{n}.equip_skill_id", "{n}.equip_skill_value");
        return $r;
    }
}