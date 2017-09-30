<?php
/**
 * Services are globally registered in this file
 *
 * @var \Phalcon\Config $config
 */

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;

use Phalcon\Logger as Logger;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Http\Response\Cookies;

$eventsManager = new EventsManager();

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();



/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);

/**
 * Setting up the view component
 */
$di->setShared('view', function () use ($config) {

    $view = new View();

    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines(array(
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
    ));

    return $view;
});

/**
 * 响应数据处理类
 */
$di->setShared('data', function () {
    return new Data();

});





$myEventsManager = new EventsManager;
$myDbListener = new MyDbListener;
$myEventsManager->attach('db', $myDbListener);

{
    /**
     * 数据库适配器
     */
    $di->set('db', function () use ($config, $myEventsManager) {
        if ($config->is_login_server) {
            $connection = new DbAdapter($config->login_server->database->toArray());
        } else {
            $connection = new DbAdapter($config->database->toArray());
        }
        $connection->setEventsManager($myEventsManager);
        return $connection;
    });
    $di->set('db_login_server', function () use ($config, $myEventsManager) {
        $connection = new DbAdapter($config->login_server->database->toArray());
        $connection->setEventsManager($myEventsManager);
        return $connection;
    });
    $di->set('db_pk_server', function () use ($config, $myEventsManager) {
        $connection = new DbAdapter($config->pk_server->database->toArray());
        $connection->setEventsManager($myEventsManager);
        return $connection;
    });
    $di->set('db_cross_server', function () use ($config, $myEventsManager) {
        $connection = new DbAdapter($config->cross_server->database->toArray());
        $connection->setEventsManager($myEventsManager);
        return $connection;
    });
    $di->set('db_citybattle_server', function () use ($config, $myEventsManager) {
        $connection = new DbAdapter($config->citybattle_server->database->toArray());
        $connection->setEventsManager($myEventsManager);
        return $connection;
    });
}
/**
 * 用于文件log输出
 */
$di->setShared('debug', function(){
    return new FileLogger(APP_PATH."/app/logs/debug.log");
});
        
/*
 * redis
*/ 
/*
$di->setShared('redis', function() use ($config){
	$redis = new Redis();
	$c = $config->redis->toArray();
	$redis->connect($c['host'], $c['port'], $c['timeout']);
	$redis->setOption(Redis::OPT_PREFIX, $c['prefix']);
	$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
	return $redis;
});
*/
$cliFlag = false;
$redisSharedFlag = true;

function getnewredisconnect($className=''){
    try{
        $redis = new Redis();
        $c = Cache::getServerConfig($className);
        if(!empty($c)) {
            Cache::$redisSwitchType = Cache::getRedisSwitchType($className);
            $redis->connect($c['host'], $c['port'], $c['timeout']);
            $redis->setOption(Redis::OPT_PREFIX, $c['prefix']);
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            return $redis;
        }
        throw new RedisException('Not Found redis config');
    } catch(RedisException $e) {
        $code = $e->getMessage();
        echo json_encode(['code'=>$code, 'data'=>[], 'basic'=>[]], JSON_UNESCAPED_UNICODE), PHP_EOL;
        exit;
    }
}
/*
$frontCache = new \Phalcon\Cache\Frontend\Data(array(
	"lifetime" => $config->redislifetime
));
foreach($config->redis as $k=>$v) {
	$di->set($k, function () use ($v, $frontCache) {
		return new Phalcon\Cache\Backend\Redis($frontCache, $v);
	});
}
*/

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Start the session the first time some component request the session service
 */
// $di->setShared('session', function () {
//     $session = new SessionAdapter();
//     // $session->start();

//     return $session;
// });

/**
 * cookies setting here
 */
$di->setShared('cookies', function () {
    $cookies = new Cookies();

    $cookies->useEncryption(false);

    return $cookies;
});

$di->set('collectionManager', function(){
    return new Phalcon\Mvc\Collection\Manager();
}, true);

$di->set('mongo', function() use ($config){
    $mongo = new MongoClient($config->mongo->host.":".$config->mongo->port);
    return $mongo->selectDB($config->mongo->db[0]);
}, true);














