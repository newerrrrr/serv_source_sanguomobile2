<?php
use Phalcon\Mvc\View;
class ScienceController extends ControllerBase
{
	public function initialize() {
		parent::initialize();
		$this->view->setRenderLevel(View::LEVEL_NO_RENDER);
	}
		
    /**
     * 开始研究
     * 
     * $_POST['scienceTypeId'] 科技类型
	 * $_POST['type'] 1.普通，2.立即
     * @return <type>
     */
	public function beginAction(){
		$player = $this->getCurrentPlayer();
		$playerId = $player['id'];
		$post = getPost();
		$scienceTypeId = floor(@$post['scienceTypeId']);
		$type = floor(@$post['type']);
		if(!checkRegularNumber($scienceTypeId))
			exit;
		if(!in_array($type, array(1, 2)))
			exit;
		
		$PlayerScience = new PlayerScience;
		$Player = new Player;
		
		$db = $this->di['db'];
		
		//完成触发
		dbBegin($db);
		if(!$PlayerScience->lvupFinish($playerId)){
			dbRollback($db);
		}else{
			dbCommit($db);
		}
		
		//锁定
		$lockKey = __CLASS__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		dbBegin($db);

		try {
			//检查研究所是否建造
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 10)){
				throw new Exception(10168);
			}
			
			//获取所有科技
			$Science = new Science;
			$science = $Science->dicGetAll();
			
			//获取玩家科技
			$playerScience = $PlayerScience->getByPlayerId($playerId);
			
			//检查输入科技类型是否存在
			$theseSciences = array();
			foreach($science as $_t){
				if($_t['science_type_id'] == $scienceTypeId){
					$theseSciences[] = $_t['id'];
				}
			}
			if(!$theseSciences){
				throw new Exception(10169);
			}
			
			//检查是否有正在研究的科技
			foreach($playerScience as $_t){
				if($_t['status']){
					throw new Exception(10296);//科技正在研究中
				}
			}
			
			//如果玩家存在该类型科技且未满级，获得下一级科技id
			$hasScience = 0;
			foreach($playerScience as $_t){
				if(in_array($_t['science_id'], $theseSciences)){
					$hasScience = $_t['science_id'];
					$thisScience = $_t;
					break;
				}
			}
			if($hasScience){
				$nextScience = $science[$hasScience]['next_science'];
				if(!$nextScience){
					throw new Exception(10170);
				}
				$isNew = false;
			}else{//如果玩家不存在该类型科技，获取一级科技id
				foreach($theseSciences as $_t){
					if($science[$_t]['level_id'] == 1){
						$nextScience = $science[$_t]['id'];
						break;
					}
				}
				$isNew = true;
			}
			
			//判断前置科技条件
			if($science[$nextScience]['condition_science']){
				$playerScienceIds = Set::extract('/science_id', $playerScience);
				foreach($science[$nextScience]['condition_science'] as $_t){
					$findFlag = false;
					foreach($playerScienceIds as $_id){
						if($science[$_id]['science_type_id'] == $science[$_t]['science_type_id'] && $science[$_id]['level_id'] >= $science[$_t]['level_id']){
							$findFlag = true;
							break;
						}
					}
					if(!$findFlag){
						throw new Exception(10171);
					}
				}
			}
			
			//检查建筑等级条件
			$Build = new Build;
			$build = $Build->dicGetOne($playerBuild[0]['build_id']);
			if(!$build){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			if($build['build_level'] < $science[$nextScience]['build_level']){
				throw new Exception(10172);
			}
			
			//buff
			$reducePercent = 1 - (new PlayerBuff)->getPlayerBuff($playerId, 'research_cost_reduce');
			@$science[$nextScience]['cost'][2] *= $reducePercent;
			@$science[$nextScience]['cost'][1] *= $reducePercent;
			@$science[$nextScience]['cost'][3] *= $reducePercent;
			@$science[$nextScience]['cost'][5] *= $reducePercent;
			@$science[$nextScience]['cost'][4] *= $reducePercent;
			
			//检查资源或元宝
			if($type == 1){
				if(!$Player->hasEnoughResource($playerId, array(
					'gold' => @$science[$nextScience]['cost'][1],
					'food'=> @$science[$nextScience]['cost'][2],
					'wood' => @$science[$nextScience]['cost'][3],
					'stone' => @$science[$nextScience]['cost'][4], 
					'iron' => @$science[$nextScience]['cost'][5]))){
					throw new Exception(10173);
				}
				if(!$Player->updateResource($playerId, array(
					'gold' => -@$science[$nextScience]['cost'][1],
					'food'=> -@$science[$nextScience]['cost'][2],
					'wood' => -@$science[$nextScience]['cost'][3],
					'stone' => -@$science[$nextScience]['cost'][4], 
					'iron' => -@$science[$nextScience]['cost'][5]))){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				$pay = $science[$nextScience]['gem_cost'];
				if(!$Player->updateGem($playerId, -$pay, true, ['cost'=>10012])){
					throw new Exception(10174);
				}
			}
			
			//更新科技
			if($type == 1){
				$studyTime = $science[$nextScience]['need_time'];
			}else{
				$studyTime = 0;
			}
			
			//科技研究buff
			//$studyTime *= max(0, (1 - (new PlayerBuff)->getScienceBuff($playerId)));
			$studyTime /= (1 + (new PlayerBuff)->getScienceBuff($playerId));
			$studyTime = floor($studyTime);
			
			//push
			if($studyTime){
				$pushId = (new PlayerPush)->add($playerId, 1, 400004, [], '', date('Y-m-d H:i:s', time()+$studyTime));
			}else{
				$pushId = 0;
			}
			
			if($isNew){
				if(!$PlayerScience->add($playerId, $nextScience, $studyTime, 1, $pushId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//$resourceId = $PlayerScience->id;
			}else{
				if(!$PlayerScience->assign($thisScience)->lvupBegin($nextScience, $studyTime, $pushId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//$resourceId = $thisScience['id'];
			}
			
			
			if($type == 2){
				if(!$PlayerBuild->startWork($playerId, $playerBuild[0]['position'], date('Y-m-d H:i:s', time()+$studyTime), $nextScience)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				if(!$PlayerScience->lvupFinish($playerId)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}else{
				if(!$PlayerBuild->startWork($playerId, $playerBuild[0]['position'], date('Y-m-d H:i:s', time()+$studyTime), $nextScience)){
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				//增加帮助
				//(new PlayerHelp)->addPlayerHelp($playerId, PlayerHelp::HELP_TYPE_SCIENCE, $resourceId);
			}
			
			(new PlayerMission)->updateMissionNumber($playerId, 3, 1);
			
			(new PlayerMission)->updateMissionNumber($playerId, 26, $scienceTypeId);

			(new PlayerTarget)->updateTargetCurrentValue($playerId, 13, 1);
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);

			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		//$data = DataController::get($playerId, array('PlayerScience'));
		if(!$err){
			echo $this->data->send();
		}else{
			echo $this->data->sendErr($err);
		}
	}
	
	/**
     * 完成触发
     * 
     * 使用方法如下
     * ```php
     * /Science/finish
     * postData: json={"uuid":"pldream","hashCode":"xxx"}
	 * ```
     * @return array
     */
	public function finishAction(){
		$playerId = $this->getCurrentPlayerId();
		
		$PlayerScience = new PlayerScience;
		
		$lockKey = __CLASS__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		
		$db = $this->di['db'];
		
		//建造完成触发
		dbBegin($db);
		if(!$PlayerScience->lvupFinish($playerId)){
			dbRollback($db);
		}else{
			dbCommit($db);
		}
		
		//解锁
		Cache::unlock($lockKey);
		
		//$data = DataController::get($playerId, array('PlayerScience'));
		echo $this->data->send();
	}
	
	/**
     * 加速
     * 
     * type ：2-元宝；3-道具
     * @return <type>
     */
	public function accelerateAction(){
		$playerId = $this->getCurrentPlayerId();
		$post = getPost();
		$scienceTypeId = floor(@$post['scienceTypeId']);
		$type = floor(@$post['type']);
		if(!checkRegularNumber($scienceTypeId))
			exit;
		if(!in_array($type, array(/*1, */2, 3)))
			exit;
		if($type == 3){
		    if(isset($post['itemList'])){
		        $itemList = $post['itemList'];
		        foreach ($itemList as $itemId=>$num){
		            if(!checkRegularNumber($itemId) || !checkRegularNumber($num)){
		                exit;
		            }
		        }
		    }
		    else{
                throw new Exception(10453);//无效道具ID
		    }
		}
		
		$PlayerScience = new PlayerScience;
		$Player = new Player;
		
		$db = $this->di['db'];
		
		//完成触发
		dbBegin($db);
		if(!$PlayerScience->lvupFinish($playerId)){
			dbRollback($db);
		}else{
			dbCommit($db);
		}
		
		//锁定
		$lockKey = __CLASS__ . ':playerId=' .$playerId;
		Cache::lock($lockKey);
		dbBegin($db);

		try {
			$PlayerBuild = new PlayerBuild;
			if(!$playerBuild = $PlayerBuild->getByOrgId($playerId, 10)){
				throw new Exception(10175);
			}
			
			//获取所有科技
			$Science = new Science;
			$science = $Science->dicGetAll();
			
			//获取玩家科技
			$playerScience = $PlayerScience->getByPlayerId($playerId);
			
			//检查输入科技类型是否存在
			$theseSciences = array();
			foreach($science as $_t){
				if($_t['science_type_id'] == $scienceTypeId){
					$theseSciences[] = $_t['id'];
				}
			}
			if(!$theseSciences){
				throw new Exception(10176);
			}
			
			$hasScience = 0;
			foreach($playerScience as $_t){
				if(in_array($_t['next_id'], $theseSciences)){
					$hasScience = $_t['next_id'];
					$thisScience = $_t;
					break;
				}
			}
			if(!$hasScience){
				throw new Exception(10177);
			}
			
			//检查是否已经完成
			if(!$thisScience['status']){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if($thisScience['end_time'] < time()){
				throw new Exception(10346);//研究已经结束
			}
			
			//分类处理
			switch($type){
				/*case 1://免费
				break;*/
				case 2:
					//计算花费
					$payUnit = (new Starting)->dicGetOne('time_cost');
					
					//$second = $thisScience['end_time'] - time();
					$second = $playerBuild[0]['work_finish_time'] - time();
					$pay = clacAccNeedGem($second);//ceil($second / $payUnit);
					
					//消费元宝
					if(!$Player->updateGem($playerId, -$pay, true, ['cost'=>10013])){
						throw new Exception(10178);
					}
					$accSec = $second;
					
					/*追加到QuickenWork
					if(!$PlayerScience->assign($thisScience)->accelerate($accSec)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}*/
					
					$finishFlag = true;
				break;
				case 3:
				    
				    $PlayerItem = new PlayerItem;
				    $Item = new Item;
				    $totalSecond = 0;
				    foreach($itemList as $itemId=>$num){
				        $maxNum = $PlayerItem->hasItemCount($playerId, $itemId);
				        if($maxNum<$num){
                            throw new Exception(10453);//无效道具ID
				        }
				        if($itemId>0){
				            $second = $Item->getAcceSecond($itemId, 4);
				        }
				        if(empty($second)){
                            throw new Exception(10453);//无效道具ID
				        }else{
				            $totalSecond += $second*$num;
				        }
				    }
				    foreach($itemList as $itemId=>$num) {
				        $PlayerItem->drop($playerId, $itemId, $num);
				    }
 
                    $accSec = $totalSecond;

					/*追加到QuickenWork
					if(!$PlayerScience->assign($thisScience)->accelerate($accSec)){
						throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}*/
					
					if(time() + $totalSecond >= $playerBuild[0]['work_finish_time']){
						$finishFlag = true;
					}else{
						$finishFlag = false;
					}
					
				break;
				default:
					throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			if(!$PlayerBuild->QuickenWork($playerId, $playerBuild[0]['position'], $accSec)){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			
			//建造完成触发
			$ret = $PlayerScience->lvupFinish($playerId);
			if($finishFlag && !$ret){
				throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__);//有可能是drop错误
			}
			
			dbCommit($db);
			$err = 0;
		} catch (Exception $e) {
			list($err, $msg) = parseException($e);
			dbRollback($db);
			//清除缓存
		}
		$this->afterCommit();
		//解锁
		Cache::unlock($lockKey);
		
		if(!$err){
			/*$ret = $PlayerScience->getAllByScienceId($playerId, $hasScience);
			if(!$ret){
				$ret = $PlayerScience->getAllByScienceId($playerId, $_t['science_id']);
			}
			if($ret){
				$ret = filterFields([$ret], true, $PlayerScience->blacklist)[0];
				$ret = $PlayerScience->adapter($ret, true);
			}*/
			$ret = $PlayerBuild->getByPosition($playerId, $playerBuild[0]['position']);
			echo $this->data->send($ret);
		}else{
			echo $this->data->sendErr($err);
		}
	}

}