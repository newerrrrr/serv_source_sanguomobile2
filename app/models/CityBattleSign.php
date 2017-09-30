<?php
class CityBattleSign extends CityBattleModelBase{
    CONST IN_SIGN = 0;//报名中
    CONST SIGN_SUCCESS = 1;//入选城战
    CONST SIGN_FAIL = 2;//落选城战

    function getPlayerSign($playerId, $roundId=0){
        if($roundId==0){
            $CityBattleRound = new CityBattleRound;
            $roundId = $CityBattleRound->getCurrentRound();
        }
        $re = self::find(["player_id={$playerId} and round_id={$roundId}"])->toArray();
        if($re){
            return $re[0];
        }else{
            return false;
        }
    }

    function getSignInfo($cityBattleId, $campId){
        $num1 = self::count(["battle_id={$cityBattleId} and camp_id={$campId} and sign_type=1"]);
        $num2 = self::count(["battle_id={$cityBattleId} and camp_id={$campId} and sign_type=2"]);
        $num3 = self::count(["battle_id={$cityBattleId} and camp_id={$campId} and sign_type=3"]);
        return [$num1, $num2, $num3];
    }

    function getSignNum($cityBattleId, $campId){
        $re = self::count(["id={$cityBattleId} and camp_id={$campId} and (sign_type=1 or sign_type=2)"]);
        return $re;
    }

    function changeCity($playerId, $campId, $fromCityBattleId, $toCityBattleId){
        $playerSign = $this->getPlayerSign($playerId);
        if($playerSign['battle_id']==$fromCityBattleId){
            $CityBattle = new CityBattle;
            if($playerSign['sign_type']==1 || $playerSign['sign_type']==2){
                $signFlag = $CityBattle->updateSignNum($toCityBattleId, $campId, "add");
            }else{
                $signFlag = true;
            }

            if($signFlag){
                $CityBattle->updateSignNum($fromCityBattleId, $campId, "dec");
                $this->updateAll(['battle_id'=>$toCityBattleId],['id'=>$playerSign['id'], 'battle_id'=>$fromCityBattleId]);
                $CityBattleCommonLog = new CityBattleCommonLog;
                $CityBattleCommonLog->add($toCityBattleId, $playerId, $campId, '玩家转报名：'.$fromCityBattleId."-".$toCityBattleId);//日志记录
                return true;
            }
        }
        return false;
    }

    function sign($playerId, $cityBattleId, $roundId, $campId, $signType, $generalPower){
        $len = strlen($playerId);
        $serverId = substr($playerId, 0, $len-6)*1;
        $self                       = new self;
        $self->server_id           = $serverId;
        $self->player_id           = $playerId;
        $self->round_id            = $roundId;
        $self->camp_id             = $campId;
        $self->battle_id           = $cityBattleId;
        $self->sign_type           = $signType;
        $self->general_power       = $generalPower;
        $self->status               = self::IN_SIGN;
        $self->sign_time           = date("Y-m-d H:i:s");

        $CityBattle = new CityBattle;
        if($signType==1 || $signType==2){
            $signFlag = $CityBattle->updateSignNum($cityBattleId, $campId, "add");
            /*if($signFlag){//收费
                $point = 1;
                $Player = new Player;
                $re = $Player->updateGem($playerId, $point, true, []);
            }*/
        }else{
            $signFlag = true;
        }

        if($signFlag){
            $self->save();
            $CityBattleCommonLog = new CityBattleCommonLog;
            $CityBattleCommonLog->add($cityBattleId, $playerId, $campId, '玩家报名');//日志记录
            return $self->id;
        }else{
            return false;
        }
    }
}?>