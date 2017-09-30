<?php
/**
 * 城战联盟任务
 * php cli.php city_battle_guild_mission main
 */
class CityBattleGuildMissionTask extends \Phalcon\CLI\Task {
   CONST STATUS_START 	= 1; //任务开始
   CONST STATUS_FINISH = 2; //任务完成
   CONST STATUS_INVALID = 3; //任务无效
   CONST STATUS_DEL= 4; //任务完成已经置为删除状态
   //!!!!!先发放奖励再进行任务刷新
   
    /*
     * 每周指定时间刷新任务
     */
    public function mainAction(){       
        set_time_limit(0);
        log4task('TaskRefresh...');
        $startTimeExec = microtime_float();
        
        $AllianceQuest = new AllianceQuest;        
        $CityBattleGuildMission = new CityBattleGuildMission;
        //获取任务表中已经达成的任务
        log4task('one...');
        $targetGuildMission = $CityBattleGuildMission->getGuildMissionByStatus(self::STATUS_FINISH);
        if(!empty($targetGuildMission)){
            foreach($targetGuildMission as $mission){
                $currentMissionInfo = $AllianceQuest->getMissionBase($mission['mission_id']);
                //更新上个状态为已完成                
                $CityBattleGuildMission->updateGuildMission(array('id'=>$mission['id'],'status'=>self::STATUS_FINISH), array('status'=>self::STATUS_DEL));//已经完成的任务置为4过期
                //新建新的任务     
                if($mission['camp_mark']==1){
                    //表示已经转阵营从第一个任务刷
                    $CityBattleGuildMission->addGuildMission($mission['guild_id'], 1);
                }
                else
                {
                    $nextMissionId = $AllianceQuest->getMissionNextId($currentMissionInfo['step_id'], $mission['camp_id']);
                    if(!$nextMissionId){
                        continue;
                    }
                    $CityBattleGuildMission->addGuildMission($mission['guild_id'], $nextMissionId);
                }
            }
            
        }
        //将更改阵营失效后的任务分配新的第一条任务
        log4task('two...');
        $invalidGuildMission = $CityBattleGuildMission->getGuildMissionByStatus(self::STATUS_INVALID);
        if($invalidGuildMission){
            foreach($invalidGuildMission as $oneMission){
                //更新上个状态为已完成
                $CityBattleGuildMission->updateGuildMission(array('id'=>$oneMission['id'],'status'=>self::STATUS_INVALID), array('status'=>self::STATUS_DEL));//已经完成的任务置为4过期
                //分配新任务
                if($oneMission['camp_mark']==1){
                    $CityBattleGuildMission->addGuildMission($oneMission['guild_id'], 1);
                }
                else{
                    $CityBattleGuildMission->addGuildMission($oneMission['guild_id'], 1);
                }
                
            }
        }
       
        $subTimeExec = microtime_float() - $startTimeExec;
        log4task('TaskRefresh...End! exec_time:'.$subTimeExec);
    }
    
    /*
     * 发放联盟任务奖励
     */
    public function  awardGuildMissionAction(){
        set_time_limit(0);
        log4task('TaskAward...Start');
        $startTimeExec = microtime_float();
        
        $PlayerMail = new PlayerMail;
        $AllianceQuest = new AllianceQuest;
        $CityBattleGuildMission = new CityBattleGuildMission;
        $ModelBase =  new ModelBase();
        $cityBattlePlayer = new CityBattlePlayer();
        $GuildMissionAward = new GuildMissionAward();
        $finishGuildMission = $CityBattleGuildMission->getGuildMissionByStatus(self::STATUS_FINISH);
        if(!$finishGuildMission){
            log4task('TaskAward...Empty');
            return false;
        }
        log4task('TaskAward...total:'.count($finishGuildMission)."guild");
        foreach($finishGuildMission as $key=>$every){
            $order = $key+1;
            log4task('Order...'.$order);
            $serverGuild = $cityBattlePlayer->parseGuildId($every['guild_id']); 
           
            //根据任务id获取奖励
            $missionInfo = $AllianceQuest->getMissionBase($every['mission_id']);
            $awardId = $missionInfo['alliance_quest_reward']; 
            
            $guildMembers = $ModelBase->execByServer($serverGuild['server_id'], 'PlayerGuild', 'getAllGuildMember', [$serverGuild['guild_id']]);
            
            if(empty($guildMembers) || count($guildMembers)==0){
                log4task('['.$serverGuild['server_id'].'_'.$serverGuild['guild_id'].'_empty]');
                continue;
            }

            $item = [];
            $item = $PlayerMail->newItemByDrop(0, [$awardId]);
            
            foreach($guildMembers as $memberId=>$info){
                $paramData = [];
                log4task('['.$serverGuild['server_id'].'_'.$serverGuild['guild_id']."_".$memberId.'_'.$awardId.']');
                $GuildMissionAward->createAwardLog(array('server_id'=>$serverGuild['server_id'], 'guild_id'=>$serverGuild['guild_id'], 'player_id'=>$memberId, 'award_id'=>$awardId));
                $this->sendAward($ModelBase, $serverGuild['server_id'], $memberId, $item, $paramData, $every['id']);
            }             
        }
        
        $subTimeExec = microtime_float() - $startTimeExec;
        log4task('TaskAward...End! exec_time:'.$subTimeExec);
    }
    
    private function sendAward($ModelBase, $serverId, $playerId, $normalItem, $data, $log=''){
        $ModelBase->execByServer($serverId, 'PlayerMail', 'sendSystem', [[$playerId], PlayerMail::TYPE_CB_AWARD_TASK, 'system email', '', 0, $data, $normalItem, '城战任务完成奖励-' . $log]);
    }
    
    
    
    
    
    
    
        
    /*//触发条件
    //有人报名后创建第一条联盟任务
    CityBattleGuildMission::addGuildMission($guild, 1);
    //联盟更改阵营将任务置为失效
    CityBattleGuildMission::setMarkByChangeCamp($guild);   
    
    //每项任务完成时候调用接口更新
          不计数任务仅攻城
    CityBattleGuildMission::addCountByCamp($campId, $type);
          计数任务     $num表示此项完成数
    CityBattleGuildMission::addCountByGuildType($guild, $type, $num);*/
         

}