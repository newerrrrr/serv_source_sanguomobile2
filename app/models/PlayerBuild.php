<?php
//建筑
class PlayerBuild extends ModelBase{
	public $blacklist = array('player_id', 'create_time', 'update_time');
	public function beforeSave(){
		$this->update_time = date('Y-m-d H:i:s');
	}
	
	public function afterSave(){
		$this->clearDataCache();
	}	
    /**
     * 获取指定玩家的指定id建筑
     * 
     * @param <type> $playerId  
     * @param <type> $buildId 
     * 
     * @return <type>
     */
	public function getByBuildId($playerId, $buildId){
		$ret = $this->getByPlayerId($playerId);
		$result = array();
		foreach ($ret as $key => $value) {
			if($value['build_id']==$buildId){
				$result[] = $value;
			}
		}
		return $result;
	}

	/**
     * 获取指定玩家的指定位置建筑
     * 
     * @param <type> $playerId 
     * @param <type> $position 
     * 
     * @return <type>
     */
	public function getByPosition($playerId, $position){
		$ret = $this->getByPlayerId($playerId);
		$result = array();
		foreach ($ret as $key => $value) {
			if($value['position']==$position){
				$result = $value;
				break;
			}
		}
		return $result;
	}

	/**
     * 获取指定玩家的指定orgID的建筑
     * 
     * @param <type> $playerId 
     * @param <type> $orgId 
     * 
     * @return <type>
     */
	public function getByOrgId($playerId, $orgId){
		$ret = $this->getByPlayerId($playerId);
		$result = array();
		foreach ($ret as $key => $value) {
			if($value['origin_build_id']==$orgId){
				$result[] = $value;
			}
		}
		return $result;
	}

	public function getPlayerCastleLevel($playerId){
		$re = $this->getByOrgId($playerId, 1);
		return $re[0]['build_level'];
	}

    /**
     * 重写父类方法，此处以id为key
     * 
     * @param  int  $playerId    
     * @param  boolean $forDataFlag 
     * @return array    
     */
    public function getByPlayerId($playerId, $forDataFlag=false) {
        $re = Cache::getPlayer($playerId, __CLASS__);
        if(!$re) {
            $re = self::find(["player_id={$playerId}"])->toArray();
            foreach($re as $k=>&$v) {
                if(!empty($v['work_content'])){
                	$v['work_content'] = json_decode($v['work_content'], true);
                }else{
                	$v['work_content'] = array();
                }
            }
            $re = $this->adapter($re);
            $re = Set::combine($re, '{n}.id', '{n}');
            Cache::setPlayer($playerId, __CLASS__, $re);
        }
        return filterFields($re, $forDataFlag, $this->blacklist);
    }

    /**
     * 获取资源建筑实际时产
     * @return [type] [description]
     */
    public function getBuildOutput($playerId, $position, $calcExAddtion=true){
    	$PlayerBuff = new PlayerBuff;
    	$playerBuild = $this->getByPosition($playerId, $position);
    	if(in_array($playerBuild['origin_build_id'],[16,21,26,31,36])){
			$buffArr = ['16'=>'gold_income', '21'=>'wood_income', '26'=>'food_income', '31'=>'stone_income', '36'=>'iron_income'];
			$buff = $PlayerBuff->getPlayerBuff($playerId, $buffArr[$playerBuild['origin_build_id']], $playerBuild['position']);
			if($calcExAddtion==true && $playerBuild['ex_addition_end_time']>time()){
				$hour = (1+$buff)*$playerBuild['resource_in']*(1+$playerBuild['ex_addition']/10000);
			}else{
				$hour = (1+$buff)*$playerBuild['resource_in'];
			}
			return $hour;
		}else{
			return 0;
		}
    }

	/**
	 * 判断建筑是否存在（存在同orgID建筑并且level更高时则存在，返回buildId；否则不存在，返回0）
	 * 
	 * @param  [type]  $playerId [description]
	 * @param  [type]  $buildId  [description]
	 * 
	 * @return int 存在则返回buildId，不存在时返回0
	 */
	public function isBuildExist($playerId, $buildId){
		$Build = new Build;
		$buildInfo = $Build->dicGetOne($buildId);

		$orgId = $buildInfo['origin_build_id'];
		$level = $buildInfo['build_level'];

		$re = $this->getByOrgId($playerId, $orgId);

		$result = 0;
		foreach ($re as $key => $value) {
			if($value['build_level']>=$level){
				$result = $value['build_id'];
				break;
			}
		}
		return $result;
	}

	/**
	 * 获取所有正在升级的建筑
	 */
	public function getAllLvUpBuild($playerId){
		$ret = $this->getByPlayerId($playerId);
		$result = array();
		foreach ($ret as $key => $value) {
			if($value['status']==2){
				$result[] = $value;
			}
		}
		return $result;
	}

	/**
	 * 计算玩家基础时产
	 * @param  [type] $playerId [description]
	 * @return array ["gold"=>0,"food"=>0,"wood"=>0,"stone"=>0,"iron"=>0];
	 */
	public function getBasicResourceIn($playerId){
		$ret = $this->getByPlayerId($playerId);
		$result = ["gold"=>0,"food"=>0,"wood"=>0,"stone"=>0,"iron"=>0];
		$Build = new Build;
		foreach ($ret as $value) {
			$build = $Build->dicGetOne($value['build_id']);
			if(!empty($build['output'])){
				foreach ($build['output'] as $k=>$v) {
					if(in_array($k, [1,2,3,4,5])){
						$result[Build::$outputTypeArr[$k]] += $v;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * 计算玩家总时产 
	 * @param  [type] $playerId [description]
	 * @param [type] $exAddtion 是否包含临时资源提速
	 * @return array ["gold"=>0,"food"=>0,"wood"=>0,"stone"=>0,"iron"=>0]
	 */
	public function getTotalResourceIn($playerId, $exAddtion=true){
		$ret = $this->getByPlayerId($playerId);
		$result = ["gold"=>0,"food"=>0,"wood"=>0,"stone"=>0,"iron"=>0];
		$Build = new Build;
		$PlayerBuff = new PlayerBuff;
		foreach ($ret as $value) {
			$build = $Build->dicGetOne($value['build_id']);
			if(!empty($build['output'])){
				foreach ($build['output'] as $k=>$v) {
					if(in_array($k, [1,2,3,4,5])){
						$output = $this->getBuildOutput($playerId, $value['position'], $exAddtion);
						$result[Build::$outputTypeArr[$k]] += $output;
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * 建造新建筑
	 * 
	 * @param  [type]  $playerId
	 * @param  [type]  $buildId
	 * @param  integer $position 建筑位置
	 * 
	 * @return boolean 是否建造成功[失败原因为建筑位置错误或建筑信息错误]
	 */
	public function newBuild($playerId, $buildId, $position){
		$Build = new Build;
		$playerBuildInfo = $this->getByPosition($playerId, $position);
		$newBuildInfo = $Build->dicGetOne($buildId);
        if(in_array($newBuildInfo['origin_build_id'], [16,21,26,31,36])){
            $newLevel = $this->getPlayerCastleLevel($playerId);
            $newBuildInfo = $Build->getOneByOrgIdAndLevel($newBuildInfo['origin_build_id'], $newLevel);
        }

		if(empty($playerBuildInfo)){
			//检测是否有重复建筑
			$newBuildOutput = 0;
			array_walk($newBuildInfo['output'], function($v, $k) use(&$newBuildOutput){
				if(in_array($k,[1,2,3,4,5])){
					$newBuildOutput = $v;
				}
			});
			$PlayerBuild = new PlayerBuild;
			$PlayerBuild->player_id = $playerId;
			$PlayerBuild->build_id = $newBuildInfo['id'];
			$PlayerBuild->origin_build_id = $newBuildInfo['origin_build_id'];
			$PlayerBuild->build_level = $newBuildInfo['build_level'];
			$PlayerBuild->general_id_1 = 0;
			$PlayerBuild->position = $position;
			$PlayerBuild->resource_in = $newBuildOutput;
			$PlayerBuild->storage_max = $newBuildInfo['storage_max'];
			$PlayerBuild->resource_start_time = date("Y-m-d H:i:s");
			$PlayerBuild->create_time = date("Y-m-d H:i:s");
			$PlayerBuild->save();
			$this->dealAfterBuild($playerId, $position);
			return true;
		}
		return false;//位置错误
	}

	/**
	 * 升级新建筑（开始建造时操作）
	 * 
	 * @param  [type] $playerId 玩家id
	 * @param  [type] $newBuildId  新建筑id
	 * @param  integer $position 建造位置
	 * 
	 * @return boolean 是否升级成功[失败原因为建筑位置错误或者新建筑id错误]
	 */
	public function lvUpBuild($playerId, $newBuildId, $position, $queueIndex){
		$Build = new Build;
		$playerBuildInfo = $this->getByPosition($playerId, $position);

		if(empty($playerBuildInfo) || $playerBuildInfo['status']!=1){//建筑位置错误
			return false;
		}

		$newBuildInfo = $Build->dicGetOne($newBuildId);
		$oldBuildInfo = $Build->dicGetOne($playerBuildInfo['build_id']);

		if ($newBuildInfo['origin_build_id']==$oldBuildInfo['origin_build_id'] && $newBuildInfo['build_level']==$oldBuildInfo['build_level']+1) {
			$this->gainResource($playerId, $position);
			$PlayerBuff = new PlayerBuff;
			$constructionTimeBuff = $PlayerBuff->getPlayerBuff($playerId, 'build_speed', $position);
			$buildFinishTime = date("Y-m-d H:i:s", time()+$newBuildInfo['construction_time']/(1+$constructionTimeBuff));	
			if($this->updateAll(['build_begin_time'=>qd(), 'build_finish_time'=>"'".$buildFinishTime."'", 'queue_index'=>$queueIndex, 'status'=>2, 'need_help'=>1], ['id'=>$playerBuildInfo['id'], 'build_id'=>$oldBuildInfo['id']])){

				$pushId = (new PlayerPush)->add($playerId, 1, 400001, ['buildname'=>$newBuildInfo['build_name']], '', $buildFinishTime);//升級完成！
				$this->updateAll(['build_push_id'=>$pushId], ['id'=>$playerBuildInfo['id']]);

				$this->clearDataCache($playerId);
				return true;
			}		
		}
		return false;//新建筑id错误
	}
	
	/**
	 * 增加资源产量
	 * @param  [type] $playerId [description]
	 * @param  [type] $position [description]
	 * @return [type]           [description]
	 */
	function increaseProduce($playerId, $position){
		$playerBuildInfo = $this->getByPosition($playerId, $position);
		$buildId = $playerBuildInfo['build_id'];

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Build = new Build;
		$buildInfo = $Build->dicGetOne($buildId);
		$output = $buildInfo['output'];
		
		$result = true;

		$outputType = 0;
		foreach ($output as $k => $v) {
			if(in_array($k, [1,2,3,4,5])){
				$outputType = $k;
			}
		}
		if($outputType==0){//该建筑不产出资源
			$result = false;
			goto ReturnResult;
		}

		if($playerBuildInfo['status']==2 && $playerBuildInfo['build_finish_time']<=time()){//建筑升级并且已完成
			$this->finishLvUp($playerId, $position);
			$playerBuildInfo = $this->getByPosition($playerId, $position);
		}

		if($playerBuildInfo['status']!=1){//建筑处于升级状态
			$result = false;
			goto ReturnResult;
		}

		$this->inventoryResource($playerId, $position);
		$playerBuildInfo = $this->getByPosition($playerId, $position);

        if($playerBuildInfo['ex_addition_end_time']<time()){
            $t = time()+3600*24;
        }else{
            $t = $playerBuildInfo['ex_addition_end_time']+3600*24;
        }

		//更新PlayerBuild表
		if($this->updateAll(['ex_addition'=>10000, 'ex_addition_end_time'=>q(date("Y-m-d H:i:s",$t))], ['id'=>$playerBuildInfo['id']])){
			$this->clearDataCache($playerId);
		}else{
			$result = false;
		}
		ReturnResult: Cache::unlock($lockKey);
		return $result;
	}

    /**
     * 收获资源
     * 
     * @param  [type] $playerId 玩家id
     * @param  [type] $position  建筑位置
     * 
     * @return boolean
     */
	public function gainResource($playerId, $position){
		$playerBuildInfo = $this->getByPosition($playerId, $position);
		$buildId = $playerBuildInfo['build_id'];

		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);

		$Build = new Build;
		$buildInfo = $Build->dicGetOne($buildId);
		$output = $buildInfo['output'];

		$outputType = 0;
		$result = 0;
		foreach ($output as $k => $v) {
			if(in_array($k, [1,2,3,4,5])){
				$outputType = $k;
			}
		}
		if($outputType==0){//该建筑不产出资源
			$result = 0;
			goto ReturnResult;
		}
		
		if($playerBuildInfo['status']==2 && $playerBuildInfo['build_finish_time']<=time()){//建筑升级并且已完成
			$this->finishLvUp($playerId, $position);
			$playerBuildInfo = $this->getByPosition($playerId, $position);
		}

		if($playerBuildInfo['status']!=1){//建筑处于升级状态
			$result = 0;
			goto ReturnResult;
		}

		$this->inventoryResource($playerId, $position);
		$playerBuildInfo = $this->getByPosition($playerId, $position);

		//更新PlayerBuild表
		if($this->updateAll(['resource'=>0, 'update_time'=>"'".date("Y-m-d H:i:s")."'"], ['id'=>$playerBuildInfo['id']])){
			$this->clearDataCache($playerId);
			$resourceNum = $playerBuildInfo['resource'];

			$field = Build::$outputTypeArr[$outputType];

			//更新Player表
			$Player = new Player;
			$Player->updateResource($playerId, [$field=>$resourceNum]);

			(new PlayerMission)->updateMissionNumber($playerId, 20, $resourceNum);
			if($outputType==1 || $outputType==2){
				$PlayerTarget = new PlayerTarget;
				$PlayerTarget->updateTargetCurrentValue($playerId, 2, $resourceNum);
			}
			$result = $resourceNum;
		}
		ReturnResult: Cache::unlock($lockKey);
		return $result;
	}
	
    /**
     * 取消建筑升级
     * 
     * 
     * @return <type>
     */
	public function cancelLvUp($playerId, $position){
		$playerBuildInfo = $this->getByPosition($playerId, $position);

		if(!empty($playerBuildInfo) && $playerBuildInfo['status']==2 && $playerBuildInfo['build_finish_time']>time()){			
			if($this->updateAll(['build_finish_time'=>qd(), 'build_begin_time'=>qd(), 'resource_start_time'=>qd(), 'update_time'=>qd(), 'status'=>1, 'queue_index'=>0], ['id'=>$playerBuildInfo['id']])){
				(new PlayerHelp)->endPlayerHelp($playerId, PlayerHelp::HELP_TYPE_BUILD, $position);
				$this->clearDataCache($playerId);
				return true;
			}
		}
		return false;
	}

	
    /**
     * 完成玩家建筑升级
     * 
     * @param  [type] $playerId [description]
     * @param  [type] $position 	[<description>]
     * 
     */
	function finishLvUp($playerId, $position=0){
		$playerBuildInfo = $this->getByPlayerId($playerId);

		$Build = new Build;
		$PlayerHelp = new PlayerHelp;

		foreach ($playerBuildInfo as $v) {
			if(empty($position) || $position==$v['position']){
				if(!empty($v) && $v['status']==2 && $v['build_finish_time']<=time()){					
					$newBuildInfo = $Build->getOneByOrgIdAndLevel($v['origin_build_id'], $v['build_level']+1);

					if(!empty($newBuildInfo)){
						$newBuildId = $newBuildInfo['id'];
						$newBuildOutput = 0;
						array_walk($newBuildInfo['output'], function($v, $k) use(&$newBuildOutput){
							if(in_array($k,[1,2,3,4,5])){
								$newBuildOutput = $v;
							}
						});
						if($this->updateAll(['build_id'=>$newBuildId, 'build_level'=>$newBuildInfo['build_level'], 'resource_in'=>$newBuildOutput, 'storage_max'=>$newBuildInfo['storage_max'], 'resource_start_time'=>"'".date("Y-m-d H:i:s")."'", 'status'=>1, 'build_finish_time'=>"'0000-00-00 00:00:00'", 'queue_index'=>0, 'update_time'=>"'".date("Y-m-d H:i:s")."'"], ['id'=>$v['id']])){
							$PlayerHelp->endPlayerHelp($playerId, $v['position']);
							$this->clearDataCache($playerId);
							$this->dealAfterBuild($playerId, $v['position']);
						}
					}
				}
			}
		}
	}

	/**
	 * 购买方式升级建筑
	 * 
	 * @param  [type]  $playerId [description]
	 * @param  integer $position [description]
	 * @return [type]            [description]
	 */
	function buyBuild($playerId, $position){
		$playerBuildInfo = $this->getByPosition($playerId, $position);

		$Build = new Build;

		$clearCache = false;

		if(!empty($playerBuildInfo) && $playerBuildInfo['status']==1){					
			$newBuildInfo = $Build->getOneByOrgIdAndLevel($playerBuildInfo['origin_build_id'], $playerBuildInfo['build_level']+1);

			if(!empty($newBuildInfo)){
				$newBuildId = $newBuildInfo['id'];
				$newBuildOutput = 0;
				array_walk($newBuildInfo['output'], function($v, $k) use(&$newBuildOutput){
					if(in_array($k,[1,2,3,4,5])){
						$newBuildOutput = $v;
					}
				});
				if($this->updateAll(['build_id'=>$newBuildId, 'build_level'=>$newBuildInfo['build_level'], 'resource_in'=>$newBuildOutput, 'storage_max'=>$newBuildInfo['storage_max'], 'resource_start_time'=>"'".date("Y-m-d H:i:s")."'", 'status'=>1, 'build_finish_time'=>"'0000-00-00 00:00:00'", 'queue_index'=>0, 'update_time'=>"'".date("Y-m-d H:i:s")."'"], ['id'=>$playerBuildInfo['id']])){
					$clearCache = true;
				}
			}
		}

		if($clearCache){
			$this->clearDataCache($playerId);
			$this->dealAfterBuild($playerId, $position);
		}
	}
	
    /**
     * 加速建造
     * 
     * @param  [type] $playerId [description]
     * @param  [type] $position  [description]
     * @param  [type] $second   减少秒数
     * 
     * @return boolean
     */
	public function quickenLvUp($playerId, $position, $second){
		$playerBuildInfo = $this->getByPosition($playerId, $position);

		if(!empty($playerBuildInfo) && $playerBuildInfo['status']==2 && $playerBuildInfo['build_finish_time']>time()){
			if($playerBuildInfo['build_finish_time']-time()>$second){
				$newFinishTime = date("Y-m-d H:i:s", $playerBuildInfo['build_finish_time']-$second);	
				$newStatus = 2;
			}else{
				$newFinishTime = date("Y-m-d H:i:s");
				$newStatus = 1;
			}
			if($newStatus==1 && $this->updateAll(['build_finish_time'=>"'".$newFinishTime."'", 'update_time'=>qd()], ['id'=>$playerBuildInfo['id']])){
				sleep(1);
				$this->clearDataCache($playerId);
				(new PlayerPush)->del($playerBuildInfo['build_push_id']);
				$this->finishLvUp($playerId, $position);
				return true;
			}else{
				$this->updateAll(['build_finish_time'=>"'".$newFinishTime."'", 'update_time'=>qd()], ['id'=>$playerBuildInfo['id']]);
				$this->clearDataCache($playerId);
				(new PlayerPush)->updateSendTime($playerBuildInfo['build_push_id'], $newFinishTime);
				return true;
			}
		}
		return false;
	}
	
    /**
     * 更新驻守武将
     * 
     * @param <type> $playerId 
     * @param <type> $position
     * @param <type> $generalId 武将id 0则为取消驻守武将
     * 
     * @return <type>
     */
	public function updateGeneral($playerId, $position, $generalId){
		$playerBuildInfo = $this->getByPosition($playerId, $position);

		if(!empty($playerBuildInfo) /*&& $playerBuildInfo['last_change_general_time']+12*3600<time()*/ ){
			$this->inventoryResource($playerId, 0, $position);
			if($this->updateAll(['general_id_1'=>$generalId, 'update_time'=>"'".date("Y-m-d H:i:s")."'", 'last_change_general_time'=>qd()], ['id'=>$playerBuildInfo['id']])){
				$this->clearDataCache($playerId);
				return true;
			}
		}
		return false;
	}
	
    /**
     * 清点资源[当buff发生变化或者被打的时候进行处理]
     * 
     * @return <type>
     */
	public function inventoryResource($playerId, $position=0){
		$ret = $this->getByPlayerId($playerId);
		if(empty($ret)){
			return false;
		}
		$PlayerBuff = new PlayerBuff;
		$Build = new Build;
		$lockKey = __CLASS__ . ':' . __METHOD__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		foreach ($ret as $value) {
			if($position==0 || $position==$value['position']){
				$build = $Build->dicGetOne($value['build_id']);
				$updateArr = array();
				if($value['ex_addition_end_time']>$value['resource_start_time']){//存在道具加成buff
					if($value['ex_addition_end_time']>=time()){
                        $storagePara = (new Starting)->getValueByKey('resource_capacity_multi');
						$output = $this->getBuildOutput($playerId, $value['position']);
						$totalResource = $value['resource']+$output*(time()-$value['resource_start_time'])/3600;
						if($totalResource>=$value['storage_max']*$storagePara){
							$updateArr['resource'] = $value['storage_max']*$storagePara;
						}else{
							$updateArr['resource'] = $totalResource;
						}
					}else{
						$currentOutput = $this->getBuildOutput($playerId, $value['position']);
						$tmpTotalResource = $value['resource']+$currentOutput*(1+$value['ex_addition']/10000)*($value['ex_addition_end_time']-$value['resource_start_time'])/3600;
						$totalResource = $tmpTotalResource+$currentOutput*(time()-$value['ex_addition_end_time'])/3600;
						if($totalResource>=$value['storage_max']){
							$updateArr['resource'] = $value['storage_max'];
						}else{
							$updateArr['resource'] = $totalResource;
						}
					}
				}else{
					$output = $this->getBuildOutput($playerId, $value['position']);
					$totalResource = $value['resource']+$output*(time()-$value['resource_start_time'])/3600;
					if($totalResource>=$value['storage_max']){
						$updateArr['resource'] = $value['storage_max'];
					}else{
						$updateArr['resource'] = $totalResource;
					}
				}
				$updateArr['resource_start_time'] = "'".date("Y-m-d H:i:s")."'";
				$updateArr['update_time'] = "'".date("Y-m-d H:i:s")."'";
				if($this->updateAll($updateArr, ['id'=>$value['id']])){
					$this->clearDataCache($playerId);
				}
			}
		}
		Cache::unlock($lockKey);
	}

	/**
	 * 获取剩余未收取资源
	 * @param  [type] $playerId [description]
	 * @return array ["gold"=>0,"food"=>0,"wood"=>0,"stone"=>0,"iron"=>0]
	 */
	public function getResourceNoCollection($playerId){
		$this->inventoryResource($playerId);
		$ret = $this->getByPlayerId($playerId);
		if(empty($ret)){
			return false;
		}
		$result = ["gold"=>0,"food"=>0,"wood"=>0,"stone"=>0,"iron"=>0];
		$Build = new Build;
		foreach ($ret as $value) {
			$build = $Build->dicGetOne($value['build_id']);
			if(!empty($build['output'])){
				foreach ($build['output'] as $k=>$v) {
					if(in_array($k, [1,2,3,4,5])){
						$result[Build::$outputTypeArr[$k]] += $value['resource'];
					}
				}
			}
		}
		return $result;
	}

	/**
	 * 开始工作
	 * 
	 * @param  [type] $playerId    玩家id
	 * @param  [type] $position    建筑位置
	 * @param  [type] $workTime    工作所需时间
	 * @param  [type] $workContent 工作内容
	 * 
	 */
	public function startWork($playerId, $position, $workFinishTime, $workContent=array()){
		$playerBuildInfo = $this->getByPosition($playerId, $position);
		switch ($playerBuildInfo['origin_build_id']) {
			case 42:
				$helpStatus = 2;
				break;
			case 10:
				$helpStatus = 3;
				break;
			default:
				$helpStatus = 1;
				break;
		}
		if(!empty($playerBuildInfo) && $playerBuildInfo['status']==1){
			if($this->updateAll(['status'=>3, 'work_begin_time'=>qd(), 'work_finish_time'=>"'".$workFinishTime."'", 'work_content'=>"'".json_encode($workContent)."'", 'update_time'=>"'".date("Y-m-d H:i:s")."'", 'need_help'=>$helpStatus], ['id'=>$playerBuildInfo['id']])){

				switch ($playerBuildInfo['origin_build_id']) {
					case 3:
						$pushId = (new PlayerPush)->add($playerId, 1, 400003, [], '', $workFinishTime);
						break;
					case 4:
					case 5:
					case 6:
					case 7:
						$Soldier = new Soldier;
						$soldier = $Soldier->dicGetOne($workContent['soldierId']);
						$pushId = (new PlayerPush)->add($playerId, 1, 400002, ['soldiername'=>$soldier['soldier_name']], '', $workFinishTime);//訓練完成！
						$this->updateAll(['build_push_id'=>$pushId], ['id'=>$playerBuildInfo['id']]);
						break;
					

					default:
						# code...
						break;
				}
				$PlayerCommonLog = new PlayerCommonLog;
                $PlayerCommonLog->add($playerId, "功能建筑开始工作-建筑ID：".$playerBuildInfo['origin_build_id']);
				$this->clearDataCache($playerId);
				return true;
			}
		}
	}

	/**
	 * 完成工作
	 * 
	 * @param  [type] $playerId    玩家id
	 * @param  [type] $position    建筑位置
	 * @param  [type] $workTime    工作所需时间
	 * @param  [type] $workContent 工作内容
	 * 
	 */
	public function endWork($playerId, $position){
		$playerBuildInfo = $this->getByPosition($playerId, $position);

		if(!empty($playerBuildInfo) && $playerBuildInfo['status']==3 && $playerBuildInfo['work_finish_time']<=time()){
			if($this->updateAll(['status'=>1, 'work_content'=>'0', 'update_time'=>"'".date("Y-m-d H:i:s")."'"], ['id'=>$playerBuildInfo['id']])){
				(new PlayerHelp)->endPlayerHelp($playerId, $position);
                $PlayerCommonLog = new PlayerCommonLog;
                $PlayerCommonLog->add($playerId, "功能建筑完成工作-建筑ID：".$playerBuildInfo['origin_build_id']);
				$this->clearDataCache($playerId);
				return true;
			}
		}
	}

	/**
     * 加速工作
     * 
     * @param  [type] $playerId [description]
     * @param  [type] $position  [description]
     * @param  [type] $second   减少秒数
     * 
     * @return boolean
     */
	public function QuickenWork($playerId, $position, $second){
		$playerBuildInfo = $this->getByPosition($playerId, $position);
		if($playerBuildInfo['origin_build_id'] == 10){//研究所
			$PlayerScience = new PlayerScience;
			$ps = $PlayerScience->findFirst(['player_id='.$playerId.' and status=1']);
			if($ps){
				if(!$ps->accelerate($second)){
					return false;
				}
			}
		}

		if(!empty($playerBuildInfo) && $playerBuildInfo['status']==3 && $playerBuildInfo['work_finish_time']>time()){
			if($playerBuildInfo['work_finish_time']-time()>=$second){
				$newFinishTime = date("Y-m-d H:i:s", $playerBuildInfo['work_finish_time']-$second);
				$newStatus = 3;
			}else{
				$newFinishTime = date("Y-m-d H:i:s");
				if(in_array($playerBuildInfo['origin_build_id'], [3,4,5,6,7,42,10])){//加速后不会立刻完成，需要手动收
					$newStatus = 3;
				}else{//加速后立刻完成
					$newStatus = 1;
				}
			}		
			if($this->updateAll(['status'=>$newStatus, 'work_finish_time'=>"'".$newFinishTime."'", 'update_time'=>qd()], ['id'=>$playerBuildInfo['id']])){
				if($newStatus==1){
					(new PlayerPush)->del($playerBuildInfo['build_push_id']);
					(new PlayerHelp)->endPlayerHelp($playerId, $position);
				}else{
					(new PlayerPush)->updateSendTime($playerBuildInfo['build_push_id'], $newFinishTime);
				}
                $PlayerCommonLog = new PlayerCommonLog;
                $PlayerCommonLog->add($playerId, "功能建筑加速工作-建筑ID：".$playerBuildInfo['origin_build_id']);
				$this->clearDataCache($playerId);
				return true;
			}
		}
		return false;
	}

	/**
	 * 计算武将提供buff
	 * @param  [type] $playerId [description]
	 * @param  [type] $position [description]
	 * @return [type]           [description]
	 */
	public function calcGeneralBuff($playerId, $position){
		if(empty($position)){
			return [];
		}
		$pbInfo = $this->getByPosition($playerId, $position);
		$result = [];
		if(empty($pbInfo['general_id_1'])){
			return [];
		}else{
			$PlayerGeneral = new PlayerGeneral;
			$general = $PlayerGeneral->getTotalAttr($playerId, $pbInfo['general_id_1']);
			$Build = new Build;
			$build = $Build->dicGetOne($pbInfo['build_id']);
			$Buff = new Buff;
			if(empty($build['output_buff_id'])){
				$result['general'] = [];
			}else{
				$buff = $Buff->dicGetOne($build['output_buff_id']);
				$attrArr = [1=>'force', 2=>'intelligence', 3=>'governing', 4=>'charm', 5=>'political'];
				if($buff['buff_type']==1){
					$result['general'] = [$buff['name']=>$general['attr'][$attrArr[$build['need_general_attribute']]]*$build['ratio']/10000];
				}else{
					$result['general'] = [$buff['name']=>ceil($general['attr'][$attrArr[$build['need_general_attribute']]]*$build['ratio']/10000)];
				}
			}
			$result['equip'] = $general['buff'];
			return $result;
		}
	}
	
    /**
     * 可集结数量（除自己以外的玩家数量）
     * 
     * 
     * @return <type>
     */
	public function getMaxGatherNum($playerId){
		$start = 0;
		$ret = $this->getByOrgId($playerId, 43);
		if($ret){
			$ret = $ret[0];
			$build = (new Build)->dicGetOne($ret['build_id']);
			$output = $build['output'][26];
			$start += $output;
		}
		//buff
		$start += (new PlayerBuff)->getPlayerBuff($playerId, 'aggregation_legion');
		return $start;
	}
	/**
	 * 屯所中获取别人援军我的最大数
	 * @param  int $playerId 
	 * @return int           
	 */
	public function getMaxHelpArmyNum($playerId){
		$num = 0;
		$playerBuild = $this->getByOrgId($playerId, 11);
		if($playerBuild) {
			$playerBuild = $playerBuild[0];
			$build = (new Build)->dicGetOne($playerBuild['build_id']);
			$Starting = new Starting;
	        $init = $build['output'][12] + $Starting->getValueByKey("default_friendly_num");//援军军团数的初始值
			$num = $init + (new PlayerBuff)->getPlayerBuff($playerId, 'help_legion');
		}
		return $num;
	}

	/**
	 * 获取医院最大容量
	 * @param  int $playerId 
	 * @return int           
	 */
	public function getMaxCureNum($playerId){
		$num = 0;
		$playerBuild = $this->getByOrgId($playerId, 42);
		if($playerBuild){
			$playerBuild = $playerBuild[0];
			$build = (new Build)->dicGetOne($playerBuild['build_id']);
			$buff = (new PlayerBuff)->getPlayerBuff($playerId, 'hospital_amount_plus', $playerBuild['position']);
			$num = ceil($build['output'][8]*(1+$buff));
		}
		return $num;
	}
	/**
	 * 获取仓库保护资源上限
	 * @param  int $playerId 
	 * @return int           
	 */
	public function getResourceProtected($playerId){
		$numArr['gold']=$numArr['food']=$numArr['wood']=$numArr['stone']=$numArr['iron']=0;
		$playerBuild = $this->getByOrgId($playerId, 8);
		//$PlayerGuild = new PlayerGuild;	
		$Map = new Map;
		if($Map->isInGuildArea($playerId)){
			$basicBuff = 1.2;
		}else{
			$basicBuff = 1;
		}
		
		if($playerBuild){
			$PlayerBuff = new PlayerBuff;
			$playerBuild = $playerBuild[0];
			$position = $playerBuild['position'];
			$build = (new Build)->dicGetOne($playerBuild['build_id']);
			$numArr['gold']  = floor($build['output'][19]*$basicBuff*(1+$PlayerBuff->getPlayerBuff($playerId, 'protect_plus', $position)+$PlayerBuff->getPlayerBuff($playerId, 'protect_gold_plus', $position)));
			$numArr['food']  = floor($build['output'][20]*$basicBuff*(1+$PlayerBuff->getPlayerBuff($playerId, 'protect_plus', $position)+$PlayerBuff->getPlayerBuff($playerId, 'protect_food_plus', $position)));
			$numArr['wood']  = floor($build['output'][21]*$basicBuff*(1+$PlayerBuff->getPlayerBuff($playerId, 'protect_plus', $position)+$PlayerBuff->getPlayerBuff($playerId, 'protect_wood_plus', $position)));
			$numArr['stone'] = floor($build['output'][22]*$basicBuff*(1+$PlayerBuff->getPlayerBuff($playerId, 'protect_plus', $position)+$PlayerBuff->getPlayerBuff($playerId, 'protect_stone_plus', $position)));
			$numArr['iron']  = floor($build['output'][23]*$basicBuff*(1+$PlayerBuff->getPlayerBuff($playerId, 'protect_plus', $position)+$PlayerBuff->getPlayerBuff($playerId, 'protect_iron_plus', $position)));
		}
		return $numArr;
	}

	/**
	 * 减少玩家资源田中的存量
	 * @param  [type] $playerId [description]
	 * @param  [type] $field    [description]
	 * @param  [type] $rate     [description]
	 * @return [type]           [description]
	 */
	public function reduceResource($playerId, $field, $rate){
		$nameToOrgIdArr = ['gold'=>16, 'food'=>26, 'wood'=>21, 'stone'=>31, 'iron'=>36];
		$orgId = $nameToOrgIdArr[$field];
		$re = $this->getByOrgId($playerId, $orgId);
		foreach($re as $k=>$v){
			$this->updateAll(['resource'=>"floor(`resource`*{$rate})"], ['id'=>$v['id']]);
		}
		$this->clearDataCache($playerId);
	}

	/**
	 * 升级建筑后续处理
	 * @param  [type] $playerId [description]
	 * @param  [type] $position [description]
	 * @return [type]           [description]
	 */
	public function dealAfterBuild($playerId, $position){
		$this->clearDataCache($playerId);
		$PlayerTarget = new PlayerTarget;
		$playerBuildInfo = $this->getByPosition($playerId, $position);
		$Build = new Build;
		$buildInfo = $Build->dicGetOne($playerBuildInfo['build_id']);
		if($buildInfo['build_level']>1){
			$preBuildInfo = $Build->getOneByOrgIdAndLevel($playerBuildInfo['origin_build_id'], $buildInfo['build_level']-1);
			$preBuildPower = $preBuildInfo['power'];
		}else{
			$preBuildPower = 0;
		}
		
		switch($playerBuildInfo['origin_build_id']){
			case 1:
				(new Map)->levelUp($playerId, $buildInfo['build_level']);
				$PlayerTarget->updateTargetCurrentValue($playerId, 1);
				$Starting = new Starting;
            
	            $newPlayerMaxLevel = $Starting->getValueByKey("avoid_battle_default_lv");
	            if($newPlayerMaxLevel<=$buildInfo['build_level']){//如果玩家升到固定等级，删除新手保护
	                (new Player)->offFreshAvoidBattle($playerId);
	            }
	            if(in_array($buildInfo['build_level'], [12,22,37])){
	            	(new PlayerInfo)->updateGiftBeginTime($playerId, 'gift_lv'.$buildInfo['build_level'].'_begin_time');
	            }

                $bArr = [16,21,26,31,36];
                $level = $buildInfo['build_level'];
                foreach ($bArr as $orgId) {
                        $pb = $this->getByOrgId($playerId, $orgId);
                        if(!empty($pb)){
                            foreach($pb as $value){
                                $newBuildInfo = $Build->getOneByOrgIdAndLevel($orgId, $level);
                                $newBuildOutput = 0;
                                array_walk($newBuildInfo['output'], function($v, $k) use(&$newBuildOutput){
                                    if(in_array($k,[1,2,3,4,5])){
                                        $newBuildOutput = $v;
                                    }
                                });
                                $this->updateAll(['build_id'=>$newBuildInfo['id'], 'build_level'=>$newBuildInfo['build_level'], 'resource_in'=>$newBuildOutput, 'storage_max'=>$newBuildInfo['storage_max'], 'status'=>1],['id'=>$value['id']]);
                                $this->dealAfterBuild($playerId, $value['position']);
                            }
                        }
                }
				break;
			case 2:
				$this->refreshWallDurability($playerId);
				break;
            case 4:
            case 5:
            case 6:
            case 7:
                if(!empty($buildInfo['upgrade_soldier_id']) && !empty($buildInfo['original_soldier_id'])){
                    (new PlayerArmyUnit)->replaceSoldier($playerId, $buildInfo['original_soldier_id'], $buildInfo['upgrade_soldier_id']);
                    (new PlayerSoldier)->replaceSoldier($playerId, $buildInfo['original_soldier_id'], $buildInfo['upgrade_soldier_id']);
                    (new PlayerBuild)->levelUpCuringSoldier($playerId, $buildInfo['original_soldier_id'], $buildInfo['upgrade_soldier_id']);
                    (new PlayerSoldierInjured)->lvUpInjuredSoldier($playerId, $buildInfo['original_soldier_id'], $buildInfo['upgrade_soldier_id']);
                    (new Player)->refreshPower($playerId, 'army_power');
                }
                break;
		}
		$PlayerTarget->updateTargetCurrentValue($playerId, 6);
		(new PlayerTimeLimitMatch)->updateScore($playerId, 9, $buildInfo['power']-$preBuildPower);
		(new Drop)->gain($playerId, $buildInfo['build_drop'], 1, '升级建筑');
		(new Player)->refreshPower($playerId, 'build_power');//重新计算建筑战斗力
        (new PlayerMission)->updateMissionNumber($playerId, 1);//主线任务

        if($playerBuildInfo['build_id']==1003){//送限时锤子
            $dropId = 100101;
            (new Drop)->gain($playerId, [$dropId], 1, '[三级府衙]送金色锤子');
        }
	}

	public function refreshWallDurability($playerId){
		$wall = $this->getByOrgId($playerId, 2);
		$wall = $wall[0];
		$Player = new Player;
		$player = $Player->getByPlayerId($playerId);
		$PlayerBuff = new PlayerBuff;
		$wallLimitBuff = $PlayerBuff->getPlayerBuff($playerId, 'wall_defense_limit_plus', 0);
		$Build = new Build;
		$build = $Build->dicGetOne($wall['build_id']);
		$newDurabilityMax = round($build['output'][6]*(1+$wallLimitBuff));
		$Player->updateAll(['wall_durability_max'=>$newDurabilityMax, 'wall_durability'=>floor($newDurabilityMax*(empty($player['wall_durability_max'])?1:$player['wall_durability']/$player['wall_durability_max']))], ['id'=>$playerId]);
		$Player->clearDataCache($playerId);
	}

	/**
	 * 计算陷阱容量
	 * @return [type] [description]
	 */
	public function calcTrapMaxNum($playerId){
		$re = $this->getByOrgId($playerId, 2);
		if(!empty($re[0])){
			$re = $re[0];
			$Build = new Build;
			$build = $Build->dicGetOne($re['build_id']);
			$result = $build['output'][7];
			$PlayerBuff = new PlayerBuff;
			$buff = $PlayerBuff->getPlayerBuff($playerId, 'pitfall_amount_plus', $re['position']);
			$result += $buff;
		}else{
			$result = 0;
		}
		return $result;
	}
	
    /**
     * 驻守武将加成
     * 
     * 
     * @return <type>
     */
	public function stayGeneralBuff($playerId, $buildId){
		$pb = $this->getByBuildId($playerId, $buildId);
		if(!$pb)
			return 0;
		//是否有驻守武将
		$generalId = $pb[0]['general_id_1'];
		if(!$generalId)
			return 0;
		
		$Build = new Build;
		$build = $Build->dicGetOne($pb[0]['build_id']);
		$needGeneralAttr = $build['need_general_attribute'];
		
		//获取武将属性
		$PlayerGeneral = new PlayerGeneral;
		$pgAttr = $PlayerGeneral->getTotalAttr($playerId, $generalId);
		if(!$pgAttr)
			return 0;
		
		//武将有效加成属性
		$ar = [1=>'force', 2=>'intelligence', 3=>'governing', 4=>'charm', 5=>'political'];
		$calculateAttr = $pgAttr['attr'][$ar[$needGeneralAttr]];
		
		//公式计算
		$ret = [];
		foreach($build['output'] as $_k => $_v){
			switch($_k){
				case 1://黄金每小时产量
					//属性*30/10000
					$ret[$_k] = $calculateAttr * 30 / 10000;
					break;
				case 2://粮食每小时产量
					//属性*30/10000
					$ret[$_k] = $calculateAttr * 30 / 10000;
					break;
				case 3://木材每小时产量
					//属性*30/10000
					$ret[$_k] = $calculateAttr * 30 / 10000;
					break;
				case 4://石材每小时产量
					//属性*30/10000
					$ret[$_k] = $calculateAttr * 30 / 10000;
					break;
				case 5://铁材每小时产量
					//属性*30/10000
					$ret[$_k] = $calculateAttr * 30 / 10000;
					break;
				case 6://城墙防御值
					//属性*7.5/10000
					$ret[$_k] = $calculateAttr * 7.5 / 10000;
					break;
				case 7://陷阱容量
					//属性*7.5/10000
					$ret[$_k] = $calculateAttr * 7.5 / 10000;
					break;
				case 8://医院容量
					//属性*6.25/10000
					$ret[$_k] = $calculateAttr * 6.25 / 10000;
					break;
				case 10://帮助次数
					//属性*0.02
					$ret[$_k] = $calculateAttr * 0.02;
					break;
				case 11://帮助减少时间
					//属性*0.125
					$ret[$_k] = $calculateAttr * 0.125;
					break;
				case 12://援兵容纳数量
					//属性/200
					$ret[$_k] = $calculateAttr / 200;
					break;
				case 14://部队数量额外增加
					//属性*6.25
					$ret[$_k] = floor($calculateAttr * 6.25);
					break;
				case 19://仓库黄金保护容量
					//属性*5/10000
					$ret[$_k] = $calculateAttr * 5 / 10000;
					break;
				case 20://仓库粮草保护容量
					//属性*5/10000
					$ret[$_k] = $calculateAttr * 5 / 10000;
					break;
				case 21://仓库木材保护容量
					//属性*5/10000
					$ret[$_k] = $calculateAttr * 5 / 10000;
					break;
				case 22://仓库石材保护容量
					//属性*5/10000
					$ret[$_k] = $calculateAttr * 5 / 10000;
					break;
				case 23://仓库铁材保护容量
					//属性*5/10000
					$ret[$_k] = $calculateAttr * 5 / 10000;
					break;
				case 26://战斗大厅集结军团数

					break;
				case 27://步兵单次训练数
					//属性*1
					$ret[$_k] = $calculateAttr * 1;
					break;
				case 28://骑兵单次训练数
					//属性*1
					$ret[$_k] = $calculateAttr * 1;
					break;
				case 29://弓兵单次训练数
					//属性*1
					$ret[$_k] = $calculateAttr * 1;
					break;
				case 30://车兵单次训练数
					//属性*1
					$ret[$_k] = $calculateAttr * 1;
					break;
				case 31://雇佣兵容量
					//属性*6.25/10000
					$ret[$_k] = $calculateAttr * 6.25 / 10000;
					break;
				case 32://单次制造陷阱数量
					//属性*1
					$ret[$_k] = $calculateAttr * 1;
					break;
				case 33://武将招募上限

					break;
			}
		}
		return $ret;
	}

	function castleLevelCheat($playerId, $level){
		$cLv = $this->getPlayerCastleLevel($playerId);
		if($cLv>=$level){
			return false;
		}
		$Build = new Build;
		$bArr = [1,2,3,4,5,6,7,10,11,12,14,41,42];
		foreach ($bArr as $orgId) {
			$newBuildInfo = $Build->getOneByOrgIdAndLevel($orgId, $level);
			$pb = $this->getByOrgId($playerId, $orgId);
			if(!empty($pb)){
				$this->updateAll(['build_id'=>$newBuildInfo['id'], 'build_level'=>$newBuildInfo['build_level']],['id'=>$pb[0]['id']]);
				$this->dealAfterBuild($playerId, $pb[0]['position']);
			}else{
				for($p=1001;$p<=1019;$p++){
					$tmpPb = $this->getByPosition($playerId, $p);
					if(empty($tmpPb)){
						$PlayerBuild = new PlayerBuild;
						$PlayerBuild->player_id = $playerId;
						$PlayerBuild->build_id = $newBuildInfo['id'];
						$PlayerBuild->origin_build_id = $newBuildInfo['origin_build_id'];
						$PlayerBuild->build_level = $newBuildInfo['build_level'];
						$PlayerBuild->general_id_1 = 0;
						$PlayerBuild->position = $p;
						$PlayerBuild->resource_in = 0;
						$PlayerBuild->storage_max = $newBuildInfo['storage_max'];
						$PlayerBuild->resource_start_time = date("Y-m-d H:i:s");
						$PlayerBuild->create_time = date("Y-m-d H:i:s");
						$PlayerBuild->save();
						$this->dealAfterBuild($playerId, $p);
						break;
					}
				}
			}
		}

		return true;
	}

    /**
     * 升级正在医疗中的兵
     *
     * @param $playerId
     * @param $oldSoldierId 升级前
     * @param $newSoldierId 升级后
     */
	public function levelUpCuringSoldier($playerId, $oldSoldierId, $newSoldierId){
        $hospital = $this->getByOrgId($playerId, 42)[0];//获取医院
        $lvUpFlag = false;
        $logNumber = 0;
        if($hospital['status']==3) {
            $workContent = $hospital['work_content'];
            $soldier     = $workContent['soldier'];
            foreach($soldier as &$v) {
                if($v['soldier_id']==$oldSoldierId) {
                    $v['soldier_id'] = $newSoldierId;
                    $logNumber = $v['num'];
                    $lvUpFlag = true;
                    break;
                }
            }
            unset($v);
            if($lvUpFlag) {
                $workContent['soldier'] = $soldier;
                //日志
                (new PlayerCommonLog)->add($playerId, ['type'=>'[正在医疗的兵的升级]', 'memo'=>['oldSoldierId'=>$oldSoldierId, 'newSoldierId'=>$newSoldierId, 'num'=>$logNumber]]);
                $this->updateAll(['work_content' => q(json_encode($workContent)), 'update_time' => qd()], ['id' => $hospital['id'], 'status' => 3]);
                $this->clearDataCache($playerId);
            }
        }
    }
}