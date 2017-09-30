<?php

/**
 * pk排行榜
 *
 */
class PkRank extends PkModelBase{
    /**
     * @param $data
     * BasicInfo ----B
     *  server_id
     *  player_id
     *  nick
     *  level
     *  avatar_id
     *  guild_name
     *  guild_short_name
     * BasicInfo ----E
     *
     * duel_rank
     * score
     * general_data ------->json   武将、装备、生命、战力
     */
    public function addNew($data){
        $self = new self;
        foreach($data as $k=>$v) {
            $self->{$k} = $v;
        }
        $self->create_time = date('Y-m-d H:i:s');
        $self->save();
    }

    /**
     * 获取所有duel_rank的排行榜
     *
     * @return array
     */
    public function getAllRank($serverId){
        $group = (new PkGroup)->getPkGroupByServerId($serverId);
        if(empty($group)) return [];
        $pkGroupId = $group['id'];

        $r = array_fill(1, 8, []);
        $re       = self::find(["pk_group_id=:pkGroupId:", 'bind'=>['pkGroupId'=>$pkGroupId]])->toArray();
        $re       = $this->adapter($re);
        $re       = Set::combine($re, '{n}.id', '{n}', '{n}.duel_rank');
        $DuelRank = new DuelRank;
        $allRank  = $DuelRank->getAllRank();
        foreach($allRank as $v) {
            if(!isset($re[$v])) {
                continue;
            }
            $rank = [];
            foreach($re[$v] as $vv) {
                if(!empty($vv['general_data'])) {
                    $vv['general_data'] = json_decode($vv['general_data'], true);
                }
                $rank[] = $vv;
            }
            $r[$v] = $rank;
        }
        return $r;
    }
}