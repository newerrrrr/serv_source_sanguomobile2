<?php

/**
 * pk玩家-武将-信息
 *
 */
class PkPlayerGeneral extends PkModelBase {
    /**
     * 更新玩家武将信息
     *
     * @param $serverId
     * @param $playerId
     * @param $generalId
     * @param $data
     */
    public function saveData($serverId, $playerId, $generalId, $data){
        $exists = self::findFirst(["server_id=:serverId: and player_id=:playerId: and general_id=:generalId:",
                               'bind' => [
                                   'serverId'  => $serverId,
                                   'playerId'  => $playerId,
                                   'generalId' => $generalId,
                               ],
                              ]);
        $fields = ['win_times', 'lose_times'];
        if($exists) {//更新记录
            $updateData = [];
            $updateData['update_time'] = qd();
            foreach($fields as $v) {
                if(isset($data[$v])) {
                    $inc            = $data[$v];
                    $updateData[$v] = "{$v}+{$inc}";
                }
            }
            $this->updateAll($updateData, ['server_id'=>$serverId, 'player_id'=>$playerId, 'general_id'=>$generalId]);
        } else {//添加记录
            $self = new self;
            $self->server_id = $serverId;
            $self->player_id = $playerId;
            $self->general_id = $generalId;
            $self->update_time = $self->create_time = date('Y-m-d H:i:s');
            foreach($fields as $v) {
                if(isset($data[$v])) {
                    $self->{$v} = $data[$v];
                }
            }
            $self->save();
        }
    }
}