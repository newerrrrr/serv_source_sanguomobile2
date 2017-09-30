<?php
class CityBattleGuildMission extends CityBattleModelBase{
   
   CONST STATUS_START 	= 1; //任务开始
   CONST STATUS_FINISH = 2; //任务完成
   CONST STATUS_INVALID = 3; //任务无效
   CONST STATUS_DEL= 4; //任务完成已经置为删除状态
   
   //* $guildId使用合成的guildid
   //* $guild 每个服务器内的联盟id
  
    /*
     * 建立新任务
     * $guildId使用合成的guildid
     * $guild 每个服务器内的联盟id
     */
    public function addGuildMission($guildId, $missionId){
        //新建任务时候是否已经存在联盟任务
        $missionInfo = $this->getGuildMission($guildId);
        if($missionInfo && $missionInfo['status']!=4){
            return $missionInfo['id'];
        }
        
        $CityBattlePlayer = new CityBattlePlayer;
        $serverGuild = $CityBattlePlayer->parseGuildId($guildId);
        $guild = $serverGuild['guild_id'];
        
        $AllianceQuest = new AllianceQuest;
        $currentMissionInfo = $AllianceQuest->getMissionBase($missionId);
        
        $ModelBase = new ModelBase();
        $guildInfo = $ModelBase->execByServer($serverGuild['server_id'], 'Guild', 'getGuildInfo', [$guild]);

        
        $self                      = new self;
        $self->guild_id            = $guildId;
        $self->camp_id             = ($guildInfo['camp_id']) ? $guildInfo['camp_id'] : 0;
        $self->mission_id          = $missionId;
        $self->type                = $currentMissionInfo['alliance_quest_type'];
        $self->num_value           = $currentMissionInfo['num_value'];
        $self->count               = 0;
        $self->status              = self::STATUS_START; //新任务开始
        $self->start_time          = date("Y-m-d H:i:s");
        $self->save();
        return $self->id;

    }
    
    /*
     * 根据联盟id获取最近一条联盟任务
     */
    public function getGuildMission($guildId, $type=false){
        if($type){
            $ret = self::find(["guild_id={$guildId} and type={$type} and status =".self::STATUS_START." order by id desc"])->toArray();
        }
        else{
            $ret = self::find(["guild_id={$guildId} and status != ".self::STATUS_DEL." order by id desc"])->toArray();
        }
              
        if(!empty($ret)){
            $re = $ret[0];
        }else{
            return false;
        }
        return $re;
    }
    
    /*
     * 更新联盟任务状态
     */
    public function updateGuildMission($condition, $updateData){
        $re = $this->updateAll($updateData, $condition);
        return $re;
    }
    
    /*
     * 内部根据联盟任务id读取任务相关完成数据
     */
    private function getGuildMissionInfo($cityBattleGuildMissionId){
        $ret = $this->find(['id ='.$cityBattleGuildMissionId.' and status=1'])->toArray();
        if(!$ret){
           return  false;
        }
        return $ret[0];
    }
    
    /*
     * 内部更新任务完成数量
     */
    private function addGuildMissionCount($cityBattleGuildMissionId, $num=0){
        
        $guildMissionInfo = $this->getGuildMissionInfo($cityBattleGuildMissionId);
        if(!$guildMissionInfo || $num<0){
            return false;
        }
        //非计数任务
        if($guildMissionInfo['type']==3){
            $updateData = [];
            $updateData['status'] = self::STATUS_FINISH;
            $updateData['finish_time'] = "'".date("Y-m-d H:i:s")."'";
            $rt = $this->updateGuildMission(array('id'=>$cityBattleGuildMissionId), $updateData);
            
        }//计数任务
        else
        {
            $updateData = [];
            if($guildMissionInfo['count']+$num >= $guildMissionInfo['num_value']){
                //完成任务
                $updateData['count'] = $guildMissionInfo['num_value'];
                $updateData['status'] = self::STATUS_FINISH;
                $updateData['finish_time'] = "'".date("Y-m-d H:i:s")."'";
                $rt = $this->updateGuildMission(array('id'=>$cityBattleGuildMissionId), $updateData);
            }
            else{
                //未完成
                
                $updateData['count'] = $guildMissionInfo['count']+$num;
                
                $rt = $this->updateGuildMission(array('id'=>$cityBattleGuildMissionId, 'num_value >='=>$updateData['count']), $updateData);
            }
        }
        return $rt;
        
    }
    /*
     * 根据阵营获取联盟任务id
     */
    private function getGuildsByCampType($campId, $type, $num_value){
        if($type==3){
            //num_value此时是城池编号
            $ret = self::find(["camp_id ={$campId} and type ={$type} and num_value={$num_value} and status =".self::STATUS_START])->toArray();
        }
        else{
            $ret = self::find(["camp_id ={$campId} and type ={$type} and  status =".self::STATUS_START])->toArray();
        }
        
        $guildMissionIds = [];
        if(!empty($ret)){
            foreach($ret as $e){
                $guildMissionIds[] = $e['id'];
            }            
        }
        return $guildMissionIds;
    }
    
    
    /*
     * 根据阵营更新任务完成数量
     * $num_value 此处表示城池id
     * 
     */
    public function addCountByCamp($campId, $type, $num_value=0, $num=0){
        $guildMissionIds = $this->getGuildsByCampType($campId, $type, $num_value);
        if(!empty($guildMissionIds)){
            foreach($guildMissionIds as $cityBattleGuildMissionId){
                $this->addGuildMissionCount($cityBattleGuildMissionId, $num);
            }
        }       
    
    }    
    /*
     * 根据guild和mission更新任务完成数量
     * @param:
     * guild int
     * mission int
     */
    public function addCountByGuildType($guildId, $type, $num=0){
        $guildMissionInfo = $this->getGuildMission($guildId, $type);
        if(!$guildMissionInfo){
            return false;
        }
        $this->addGuildMissionCount($guildMissionInfo['id'], $num);    
    }
    
    /*获取不同状态下的任务*/
    public function getGuildMissionByStatus($status){
        $ret = self::find(['status = '.$status])->toArray();
        return $ret;
    }
    
    /*
     * 更新阵营
     * 如果任务为2完成；则标记
     * $guildId合成的id
     */
    public function setMarkByChangeCamp($guildId){
        $guildMissionInfo = $this->getGuildMission($guildId);
        $updateData = [];
        if(!empty($guildMissionInfo)){
            if($guildMissionInfo['status'] == self::STATUS_START){
                //未完成的直接改为3失效并做标记
                $updateData['status'] = self::STATUS_INVALID;
                $updateData['camp_mark'] = 1;
            }
            else if($guildMissionInfo['status'] == self::STATUS_FINISH){
                //已完成的状态保持2并做标记
                $updateData['camp_mark'] = 1;
            }
            else{
                $updateData['camp_mark'] = 1;
            }
            return $this->updateGuildMission(array('id'=>$guildMissionInfo['id']), $updateData);
            
        }
        else{
            return false;
        }
        
        
        
        
        
    }
    
    
    
}
?>