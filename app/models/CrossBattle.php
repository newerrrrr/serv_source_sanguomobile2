<?php
class CrossBattle extends CrossModelBase{
    CONST STATUS_READY 	= 0;
    CONST STATUS_ATTACK_READY = 1;
    CONST STATUS_ATTACK = 2;
    CONST STATUS_ATTACK_CLAC = 3;
    CONST STATUS_DEFEND_READY = 4;
    CONST STATUS_DEFEND = 5;
    CONST STATUS_DEFEND_CLAC = 6;
    CONST STATUS_FINISH = 7;

    //获得战斗信息
    public function getBattle($id){
        $ret  = self::find(["id={$id}"]);
        if(!empty($ret)){
            $ret = $ret->toArray();
            return $ret[0];
        }else{
            return [];
        }

    }

    //根据公会id获取battleId
    public function getBattleIdByGuildId($guildId, &$re=null){
        $ret = self::find(["(guild_1_id={$guildId} or guild_2_id={$guildId}) and status<".self::STATUS_FINISH])->toArray();
        if($ret){
			$re = $ret[0];
        }else{
            return false;
        }
        return $re['id'];
    }
	
	public function getLastBattleIdByGuildId($guildId, &$re=null){
        $ret = self::findFirst(["(guild_1_id={$guildId} or guild_2_id={$guildId})", 'order'=>'id desc']);
        if($ret){
			$re = $ret->toArray();
			return $ret->id;
        }else{
            return false;
        }
    }

    public function add($data){
        $self                      = new self;
        $self->round_id            = $data['round_id'];//轮次round_id
        $self->guild_1_id          = $data['guild_1_id'];
        $self->guild_2_id          = $data['guild_2_id'];
        $self->map_type            = $data['type'];
        $self->start_time          = $data['start_time'];
        $self->attack_area         = '1,2';
        $self->status              = 0;
        $self->create_time         = date("Y-m-d H:i:s");
        $self->update_time         = date("Y-m-d H:i:s");
        $self->save();
        return $self->id;
    }

    //本轮战斗列表
    public function getCurrentBattleIdList(){
        $result = [];
        $ret = self::find(["status<".self::STATUS_FINISH]);
        if(!empty($ret)){
            $re = $ret->toArray();
            foreach($re as $v){
                $result[] = $v['id'];
            }
        }
        return $result;
    }

    //返回攻防双方公会id
    public function getADGuildId($battleId){
		if(is_numeric($battleId)){
			$battleInfo = $this->getBattle($battleId);
		}else{
			$battleInfo = $battleId;
		}
        if($battleInfo['status']==self::STATUS_ATTACK || $battleInfo['status']==self::STATUS_ATTACK_READY || $battleInfo['status']==self::STATUS_ATTACK_CLAC){
            return ['attack'=>$battleInfo['guild_1_id'], 'defend'=>$battleInfo['guild_2_id']];
        }elseif($battleInfo['status']==self::STATUS_DEFEND || $battleInfo['status']==self::STATUS_DEFEND_READY){
            return ['attack'=>$battleInfo['guild_2_id'], 'defend'=>$battleInfo['guild_1_id']];
        }
    }

    //获取比赛当前状态
    public function getBattleStatus($battleId, &$battleInfo=''){
        $battleInfo = $this->getBattle($battleId);
        return $battleInfo['status'];
    }

    //是否正在战斗中
    public function isActivity($battleId, &$battleInfo=''){
		if(is_numeric($battleId)){
			$status = $this->getBattleStatus($battleId, $battleInfo);
		}else{
			$status = $battleId['status'];
		}
        return ($status==self::STATUS_ATTACK || $status==self::STATUS_DEFEND);
    }

    //返回上届比赛日期
    public function getLastBattleDate(){
        $result = '';
        $ret = self::find(["status=".self::STATUS_FINISH." order by id desc"]);
        if(!empty($ret)){
            $re = $ret->toArray();
            $result = date("Y-m-d", strtotime($re[0]['start_time']));
        }
        return $result;
    }

	public function alter($id, array $fields){
		if(isset($fields['attack_area'])){
			$fields['attack_area'] = "'".$fields['attack_area']."'";
		}
		if(isset($fields['start_time'])){
			$fields['start_time'] = "'".$fields['start_time']."'";
		}
		if(isset($fields['change_time'])){
			$fields['change_time'] = "'".$fields['change_time']."'";
		}
		$fields['update_time'] = "'".date('Y-m-d H:i:s')."'";
        return $this->updateAll($fields, ['id'=>$id]);
    }

    public function getPlayerControlArea($guildId, $battleId){
        $adGuild = $this->getADGuildId($battleId);
        if($adGuild['attack']==$guildId){
            $battleInfo = $this->getBattle($battleId);
            return parseArray($battleInfo['attack_area']);
        }elseif($adGuild['defend']==$guildId){
            return [1,2,3,4,5,6];
        }
    }

    public function isAttack($guildId, $battleId){
        $adGuild = $this->getADGuildId($battleId);
        if($adGuild['attack']==$guildId){
            return true;
        }elseif($adGuild['defend']==$guildId){
            return false;
        }
    }
	
	public function endBattle($battleId){
		$ret = $this->findFirst($battleId);
		if($ret->status == self::STATUS_ATTACK){
			$field = 'guild_1_beat';
			$field2 = 'guild_1_time';
			$field3 = 'real_start_time';
		}else{
			$field = 'guild_2_beat';
			$field2 = 'guild_2_time';
			$field3 = 'change_time';
		}
		return $this->updateAll(['status'=>'status+1', $field=>1, $field2=>time()-strtotime($ret->$field3), 'update_time'=>"'".date('Y-m-d H:i:s')."'"], ['id'=>$battleId, 'status'=>[self::STATUS_ATTACK, self::STATUS_DEFEND]]);
	}
	
	public function updateFirstBlood($cb, $fromPlayer, $toPlayer){
		if($cb['status'] == self::STATUS_ATTACK){
			$field = 'first_blood_1';
		}else{
			$field = 'first_blood_2';
		}
		$ret = $this->updateAll([$field=>$toPlayer['player_id']], ['id'=>$cb['id'], 'status'=>[self::STATUS_ATTACK, self::STATUS_DEFEND], $field=>0]);
		if($ret){
			$guilds = $this->getADGuildId($cb);
			$Player = new CrossPlayer;
			$Player->battleId = $cb['id'];
			foreach(['attack', 'defend'] as $_t){
				$playerIds = [];
				$serverId = CrossPlayer::parseGuildId($guilds[$_t])['server_id'];
				if($serverId){
					$members = $Player->getByGuildId($guilds[$_t]);
					foreach($members as $_d){
						if(!$_d['status']) continue;
						$playerIds[] = $_d['player_id'];
					}
					crossSocketSend($serverId, ['Type'=>'cross', 'Data'=>['playerId'=>$playerIds, 'type'=>'firstblood', 'fromNick'=>$fromPlayer['nick'], 'toNick'=>$toPlayer['nick'], 'fromAvatar'=>$fromPlayer['avatar_id']]]);
				}
			}
		}
	}
	
    /**
     * 更新公会占领区域
     * 
     * @param <type> $battleId 
     * @param <type> $newArea 
     * 
     * @return <type>
     */
	public function updateAttackArea($battleId, $newArea){
		$crossBattle = $this->getBattle($battleId);
		$attackArea = parseArray($crossBattle['attack_area']);
		$attackArea[] = $newArea;
		$attackArea = join(',', array_unique($attackArea));
		return $this->alter($battleId, ['attack_area'=>$attackArea]);
	}
	
	public function addKill($battleId, $guildId1, $kill1, $kill2){
		$ret = $this->findFirst($battleId);
		if($ret->guild_1_id == $guildId1){
			$field = ['guild_1_kill'=>'guild_1_kill+'.$kill1, 'guild_2_kill'=>'guild_2_kill+'.$kill2];
		}else{
			$field = ['guild_2_kill'=>'guild_2_kill+'.$kill1, 'guild_1_kill'=>'guild_1_kill+'.$kill2];
		}
		$field['update_time'] = "'".date('Y-m-d H:i:s')."'";
		return $this->updateAll($field, ['id'=>$battleId, 'status'=>[self::STATUS_ATTACK, self::STATUS_DEFEND]]);
	}
}?>