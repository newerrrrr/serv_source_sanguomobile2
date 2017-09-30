<?php
/**
 * 走马灯
 *
 */
class RoundMessage extends ModelBase{
    /**
     * @param int $playerId
     * @param array $data
     *
     * @return null
     *
     * ```php
     * type:
     *  0: 系统消息
     *  1: 战斗相关
     *  2: 招募武将
     *  3: 击杀boss
     *  4: 紫色品质装备品质
     *  5: 任免国王[已废弃]
     *  6: 官职任免
     *  8: 聚宝盆获得神武将信物
     *  9: 化神
     *
     * battle_type: 1:野外 2:城池 3:堡垒
     * battle_win: 1:进攻方胜 2:进攻方败
     *
     * 
     * 添加走马灯数据
     * 
     * $data['type'] = 1;
     * $data['...'] = ...;
     * $RoundMessage = new RoundMessage;
     * $RoundMessage->addNew($playerId, $data);
     * ```
     */
    public function addNew($playerId, array $data){
        if(isset($data['battle_attacker_power_loss']) && $data['battle_attacker_power_loss']<=1000 && isset($data['battle_defender_power_loss']) && $data['battle_defender_power_loss']<=1000) return null;
        $self              = new self;
        $Player            = new Player;
        $Guild             = new Guild;
        //进攻方 
        $self->player_id   = $playerId;
        if($data['type']!=0) {
            $player            = $Player->getByPlayerId($playerId);
            $self->player_nick = $player['nick'];
        }
        //防守方
        if(isset($data['battle_defender_id'])) {
            $defenderPlayer = $Player->getByPlayerId($data['battle_defender_id']);
            $self->battle_defender_player_nick = $defenderPlayer['nick'];
        }
        if($data['type']==1 && $data['battle_type']==3) {
            //进攻方
            $guildId                                = $player['guild_id'];
            $guild                                  = $Guild->getGuildInfo($guildId);
            $self->battle_attacker_guild_id         = $guildId;
            $self->battle_attacker_guild_short_name = $guild['short_name'];
            //防守方-堡垒
            $defenderGuildId                        = $data['battle_defender_guild_id'];
            $defenderGuild                          = $Guild->getGuildInfo($defenderGuildId);
            $self->battle_defender_guild_id         = $guildId;
            $self->battle_defender_guild_short_name = $defenderGuild['short_name'];

        }
        foreach($data as $k=>$v) {
            if($k=='data') {
                $self->$k = json_encode($v, JSON_UNESCAPED_UNICODE);
            } else {
                $self->$k = $v;
            }
        }
        $self->create_time = date('Y-m-d H:i:s');
        $self->save();
        //聊天推送
        $pushData = ['type'=>0];
        switch($self->type){
            case 2://招募武将
                $generals = (new General)->getAllByOriginId();
                $general  = $generals[$self->general_id];
                if($general['general_quality']>4) {
                    $pushData['type']       = 2;
                    $pushData['general_id'] = intval($self->general_id);
                }
                break;
            case 4://武器进阶
                $pushData['type']         = 3;
                $pushData['equipment_id'] = intval($self->equipment_id);
                break;
            case 8://聚宝盆-神武将信物
                $pushData['type']        = 11;
                $pushData['item_id']     = intval($data['data']['item_id']);
                break;
            case 9://化神
                $pushData['type']        = 12;
                $pushData['general_id']  = intval($data['data']['general_id']);
                break;
        }
        if($pushData['type'] != 0) {
            $data = ['Type'=>'world_chat', 'Data'=>['player_id'=>$playerId, 'content'=>'', 'pushData'=>$pushData]];
            socketSend($data);
        }
    }
    /**
     * 获取消息
     * @return array
     */
    public function getRoundMessage(){
        $alistNum = 100;
        $blistNum = 10;
        //先读取GM消息
        $re = self::find(['type=0', 'order'=>'create_time asc', 'limit'=>$alistNum])->toArray();
        if(!$re) {
            $redis = Cache::db();
            $key = 'roundMessage_Current_Type';
            Data:

            $currentType = $redis->get($key);
            $atypeFlag = false;
            if(!$currentType || $currentType=='B') {
                $redis->set($key, 'A');
                $re = self::find(['type=1', 'order'=>'create_time desc', 'limit'=>$alistNum])->toArray();
                if(count($re)==1) {
                    $doNotUpdateFlag = true;
                }
                $atypeFlag = true;
            } else {
                $re = self::find(['type<>1 and type<>0', 'order'=>'create_time desc', 'limit'=>$blistNum])->toArray();
                $redis->set($key, 'B');
                if(empty($re)) {
                    goto Data;
                }
            }
        }
        $re = array_reverse($re);
        //消息处理
        foreach($re as $k=>$v) {
            $v['data'] = json_decode($v['data'], true);//解开data的json格式
            if($v['status']==0) {
                continue;
            } else {
                if(isset($doNotUpdateFlag)) {//最后一条A记录
                    return $this->adapter($v, true);
                }
                $createTime = $v['create_time'];
                
                if(!isset($atypeFlag)) {//删无效公告
                    self::find("create_time<='{$createTime}' and type=0")->delete();
                } else {
                    if($atypeFlag) {//删无效A
                        self::find("create_time<='{$createTime}' and type=1")->delete();
                    } else {//删无效B
                        self::find("create_time<='{$createTime}' and type<>0 and type<>1")->delete();
                    }
                }
                return $this->adapter($v, true);
            }
        }
    }
}
