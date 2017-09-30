<?php
/**
 * 玩家每日在线时长统计表-model
 * @author lijiaojiao
 */
class PlayerOnline extends ModelBase{
    
    /**
     * 首次连接成功写入表
     * @param  int $playerId 
     */
    
    public function setRecord($playerId){       
        $self    = new self;
        $self->player_id = $playerId;
        $self->date = date("Y-m-d");        
        $self->online = 0;
        $self->first_exp = 0;
        $self->day_exp = 0;        
        $self->save();
    }
    
    /**
     * 查询某日是否已经建立记录
     * @param  int $playerId 
     *         string $date
     * @return array record data       
     */
    
    public function getRecord($playerId, $date){
        try {
            $res = self::findFirst(["player_id={$playerId} and date='{$date}'"]);
            return $res;
        } catch(PDOException $e) {
            echo "################ PDOException:" . __METHOD__ . ":" . __LINE__,PHP_EOL;

            global $di, $config;
            $di['db']->connect($config->database->toArray());

            try {
                echo "+++++++++++++++++++++++重连中。。。。。。。\n";
                $res = self::findFirst(["player_id={$playerId} and date='{$date}'"]);
                echo "+++++++++++++++++++++++重连成功。。。。。。。\n";
                return $res;
            } catch(PDOException $e) {
                echo "--------------- PDOException:" . __METHOD__ . ":" . __LINE__,PHP_EOL;
                trace();
            }

            return null;
        }
    }
    
    /**
     * 更新时长
     * @param  int $playerId 
     *         string $date
     * @return true/false       
     */
    
    public function updateRecord($playerId, $date, $fields){
        $res = $this->updateAll($fields, ['player_id'=>$playerId,'date'=>q($date)]);
        return $res;
    }
    
    /**
     * 记录经验
     * @param  int $playerId 
     *         string $date
     */
    public function recordExp($playerId){
        $Player = new Player();
        $playerBase = $Player->getByPlayerId($playerId);        
        $date = date("Y-m-d");
        //记录当日经验
        $fields = array();
        $fields['first_exp'] = $playerBase['current_exp'];
        $todayRecord = $this->getRecord($playerId, $date);
        if(!$todayRecord){
            $this->setRecord($playerId);
        }
        $res = $this->updateRecord($playerId, $date, $fields);
        //获得最近一条记录
        $latelyRecord = (new self)->findFirst(["player_id={$playerId} and date !='{$date}'","order"=>'id desc']);
        if($latelyRecord){
            $latelyRecord = $latelyRecord->toArray();
        }
        else
        {
            return $res;
        }
        if(!empty($latelyRecord)){
            //更新上一次的day_exp
            $latelyDate = $latelyRecord['date'];
            $latelyExp = $playerBase['current_exp'] - $latelyRecord['first_exp'];
            $fieldsLately = array();
            $fieldsLately['day_exp'] = $latelyExp;
            $res = $this->updateRecord($playerId, $latelyDate, $fieldsLately);
        }        
        return $res;
    }
    
   
}
