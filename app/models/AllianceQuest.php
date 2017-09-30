<?php
/**
 * 联盟任务基础配置 字典表
 */
class AllianceQuest extends ModelBase{
	/*
	 * 根据id获取任务详细信息
	 */
	public function getMissionBase($id){
	    $ret = self::find(['id ='.$id])->toArray();
	    if(!empty($ret)){
	        $re = $ret[0];
	    }else{
	        return false;
	    }
	    return $re;
	}
	
	
	/*
	 * 根据step获取下一个任务id
	 * 
	 */
	public function getMissionNextId($step, $campId){
	    $nextStep = $step+1;
	    $ret = self::find(['step_id ='.$nextStep])->toArray();
	    if(!empty($ret)){
	        if(count($ret) == 3){//有阵营之分的任务
	            foreach($ret as $ev){
	                if($ev['country_id'] == $campId){
	                    return $ev['id'];
	                }
	            }
	        }
	        else{
	            return $ret[0]['id'];	            
	        }
	        
	    }
	    else {
	        return false;
	    }
	        	      
	}
	

	
	
}