<?php
/**
 * 损失补偿
 *
 */
class PlayerFailSaveReward extends ModelBase{
    /**
     * 检查玩家是否需要补偿
     * @return [type] [description]
     */
    public function checkNeedSave($playerId, $losePower){
        $FailSaveReward = new FailSaveReward;
        $rewardList = $FailSaveReward->dicGetAll();

        $PlayerBuild = new PlayerBuild;
        $PlayerMail = new PlayerMail;
        $pcLevel = $PlayerBuild->getPlayerCastleLevel($playerId);
        foreach ($rewardList as $value) {
            if( $pcLevel<=$value['level_max'] && (1==$value['reward_type'] || (2==$value['reward_type'] && !$this->checkHasGetReward($playerId, $value['id']))) && ($losePower>=$value['power_min'] && $losePower<=$value['power_max'])){
                $item = $PlayerMail->newItemByDrop($playerId, [$value['drop']]);

                $type        = PlayerMail::TYPE_FAIL_SAVE;
                $title       = 'fail save email';
                $msg         = '';
                $time        = 0;
    
                $PlayerMail->sendSystem($playerId, $type, $title, $msg, $time, ['text'=>$value['language_id']*1], $item, []);
                $this->addNew(['player_id'=>$playerId, 'reward_id'=>$value['id']]);
                break;
            }
        }
    }

    public function addNew($data){
        $self = new self;
        $self->player_id = $data['player_id'];
        $self->reward_id = $data['reward_id'];
        $self->create_time = date("Y-m-d H:i:s");
        $self->save();
        $this->clearDataCache($data['player_id']);
        return true;
    }

    public function checkHasGetReward($playerId, $rewardId){
        $playerRecord = $this->getByPlayerId($playerId);
        foreach ($playerRecord as $key => $value) {
            if($value['reward_id']==$rewardId){
                return true;
            }
        }
        return false;
    }
}