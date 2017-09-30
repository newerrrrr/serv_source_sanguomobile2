<?php
exit('Index Forbidden!');
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
class IndexController extends ControllerBase
{
	public function redisAction(){
		/*$redis = new Redis();
		$redis->connect('127.0.0.1', 6379);
		$redis->set('key', 'value');
*/
		//var_dump($this->di->get('redis')->get('key'));
		echo Cache::db()->set('key', 'value');
	}

	public function testAction(){
		$db = $this->di->get('db');
		$db->begin();
		$Alliance = new Alliance;
		$ret = $Alliance->findFirst(array('id=1'));
		$ret->open_num=$ret->open_num+1;
		$ret->update();
		$Alliance = new Alliance;
		$ret = $Alliance->findFirst(array('id=1'));
		$ret->open_num=$ret->open_num+1;
		$ret->update();
		exit;
	}
    public function indexAction()
    {
		exit;
		//$db = $this->di->getShared('db');
		// $this->di->db;
		$Alliance = new Alliance;
		//var_dump(get_class_methods($Alliance->getDI()->getShared()));
		//exit;
		//$Alliance->setDI($this->di);
		//$db->begin();
		$ret = $Alliance->findFirst(array('id=1'));
		$ret->open_num=$ret->open_num+1;
		echo 3;
		$ret->update();
		//$Alliance->sqlExec('begin');
		//$Alliance->test();
		//$Alliance = new Alliance;
		//$Alliance->test();
		//$Alliance = new Alliance;
		//$Alliance->test();
		//$db->commit();
		exit;


		$Alliance = new Alliance;
		$txManager = new TxManager();
		//$txManager->setDbService("sanguo2_share");
		$transaction = $txManager->get();
		$Alliance->setTransaction($transaction);
		//$cars  = new Resultset(null, $Alliance, $Alliance->getReadConnection()->query("SELECT * FROM Alliance"));
		//$cars   = $query->execute();
		//var_dump($cars);
		$cars = $Alliance->find();
		foreach($cars as $_c){
			$_c->setTransaction($transaction);
			//$_c->alliance_architectures_name = 'uuu';
			$result = $_c->update(array('alliance_construction_time'=>2));
		var_dump(get_class_methods($_c));
			echo $_c->affectedRows()*1;
			//var_dump($_c->getMessages());
			//$this->modelsManager->createQuery("update Alliance set alliance_architectures_name='ccc' where id = ".$_c->id)->execute();
		}
		
		//$Alliance->getReadConnection()->query('update alliance set alliance_construction_time="7839"');
		//echo $Alliance->affectRows();
		exit;
		

		/*
		$Alliance = new Alliance;
		$ret = $Alliance->getReadConnection()->query('update alliance set alliance_construction_time="7839" where id=1');
		$ret = new Resultset(null, $Alliance, $Alliance->getReadConnection()->query('select ROW_COUNT()'));
		var_dump($ret->toArray());
		//var_dump($ret->toArray());
		exit;
		*/
		/*
		var_dump($this->di);
		exit;
		$db->begin();*/
//		$sql = "select * from players_alliances where player_id=?;";
//		$re = $db->query($sql, array(90000));
		
		$db->begin();
		$Alliance = new Alliance;
		$cars  = new Resultset(null, $Alliance, $Alliance->getReadConnection()->query("SELECT * FROM Alliance"));
		foreach($cars as $_c){
			//$_c->alliance_architectures_name = 'uuu';
			$result = $db->updateAsDict('Alliance', array('alliance_construction_time'=>'967848961'), 'id = '.$_c->id);
			var_dump( $db->affectedRows());
			//var_dump($_c->getMessages());
			//$this->modelsManager->createQuery("update Alliance set alliance_architectures_name='ccc' where id = ".$_c->id)->execute();
		}

		$db->commit();
		exit;
		$cars  = $this->modelsManager->createQuery("SELECT * FROM alliance WHERE id= :id:");
		$cars   = $query->execute(array('id'=>1))->toArray();
		var_dump($cars);
				
		$cars  = $this->modelsManager->createQuery("SELECT * FROM Alliance WHERE id= 1");
		$cars   = $query->execute()->toArray();
		var_dump($cars);
		
		
		
		exit;
		try {
		  $txManager = new TxManager();
		  $txManager->setDbService("sanguo2_share");
		  $transaction = $txManager->get();

		  $robot = new Alliance();
		  $robot->setTransaction($transaction);
		  $robot->alliance_architectures_name = 'WALL·E';
		  $robot->alliance_construction_time = date('Y-m-d');
		  if ($robot->save() == false) {
			$transaction->rollback("Can't save robot");
		  }
		  
		  $robot = new AllianceScience();
		  $robot->setTransaction($transaction);
		  $robot->alliance_architectures_name = 'WALL·E';
		  $robot->alliance_construction_time = date('Y-m-d');
		  if ($robot->save() == false) {
			$transaction->rollback("Can't save robot2");
		  }
/*
		  $robotPart = new RobotParts();
		  $robotPart->setTransaction($transaction);
		  $robotPart->type = 'head';
		  if ($robotPart->save() == false) {
		$transaction->rollback("Robot part cannot be saved");
		  }
*/
		  //$transaction->commit();

		} catch (TxFailed $e) {
		  echo 'Failed, reason: ', $e->getMessage();
		}
		
		exit;
		$ret = Alliance::findFirst(array('id=1'))->toArray();
		var_dump($ret);
		exit;
		/*
		global $di;
		//var_dump($this);
		var_dump($this->getDI()->get('cache')->get('my-data'));
		exit;
		// Cache data for 2 days
		$frontCache = new \Phalcon\Cache\Frontend\Data(array(
		"lifetime" => 172800
		));

		//Create the Cache setting redis connection options
		$cache = new Phalcon\Cache\Backend\Redis($frontCache, array(
			'host' => 'localhost',
			'port' => 6379,
			'index' => 1,
			'persistent' => false
		));

		//Cache arbitrary data
		$cache->save('my-data', array(1, 2, 3, 4, 5));

		//Get data
		var_dump($data = $cache->get('my-data'));
		exit;*/
        $playerIndex = new PlayerIndex;
		$playerIndex->cacheSet('abcd', array(1, 2, 3));
		var_dump($this->getDI()->get('cache')->get('abcd'));
		var_dump( $playerIndex->cacheGet('abcd'));
		exit;
        /*$re = $playerIndex::findFirst();
        $re->player_name = 'cccc';
        $re->save();
        var_dump($re->player_name);
        var_dump($re->toArray());*/

    }
    
    /**
     * 测试用
     */
    public function tAction(){
		exit;
            $playerIndex = new PlayerIndex;
            
            var_dump($playerIndex->getOneData(147703));
//             foreach ($re->getMessages() as $message) {
//                 echo $message, "\n";
//             }
            exit;
            //update
            $re = $playerIndex->findFirst(147701);
            
            var_dump($re->toArray());
            $re->player_name = '1122222cccc';
//             $re->camp_img_path = 'aaa';
            $re->position_img_path = 'bbb';
            $re->player_password = '111cccpwd';
            
            $re->save();
            var_dump($re->toArray());
            foreach ($re->getMessages() as $message) {
                echo $message, "\n";
            }
        exit;
    }

}

