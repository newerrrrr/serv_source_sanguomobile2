<?php
$peak_s = memory_get_peak_usage();

/**
 * Simple function to replicate PHP 5 behaviour
 */
function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 打印出人类可读的的格式
 *
 * @param $bytes
 *
 * @return string
 */
function convertHummanReadability($bytes){
    if($bytes<1024) return $bytes;
    if(($bytes/1024)>1024) {
        $R = sprintf("%.2f", ($bytes/1024)/1024) . 'M';
    } else {
        $R = sprintf("%.1f", $bytes / 1024) . 'K';
    }
    return $R;

}
/**
 * 执行了多久
 * @return float 
 */
function howlong(){
    global $startTime;
    return microtime_float() - $startTime; 
}
$startTime = microtime_float();

// error_reporting(E_ALL);

//====================================================================================
define('APP_PATH', dirname(dirname(__FILE__)));

try {
    /**
     * 常量文件
     */
    include APP_PATH . "/app/lib/constant.php";

    /**
     * Read the configuration
     */
    $config = include APP_PATH . "/app/config/config.php";

    /**
     * Read auto-loader
     */
    include APP_PATH . "/app/config/loader.php";

    /**
     * Read services
     */
    include APP_PATH . "/app/config/services.php";

    /**
     * 公共方法文件
     */
    include APP_PATH . "/app/lib/common.php";
    
    // 获取 'router' 服务
    $router = $di['router'];

    $router->handle();

    $view = $di['view'];

    $dispatcher = $di['dispatcher'];

    // 传递路由的相关数据传递给调度器
    $dispatcher->setControllerName($router->getControllerName());
    $dispatcher->setActionName($router->getActionName());
    $dispatcher->setParams($router->getParams());

    // 启动视图
    $view->start();

    // 请求调度
    $dispatcher->dispatch();

    // 渲染相关视图
    $view->render(
        $dispatcher->getControllerName(),
        $dispatcher->getActionName(),
        $dispatcher->getParams()
    );

    // 完成视图
    $view->finish();

    $response = $di['response'];

    // 传递视图内容给响应对象
    $response->setContent($view->getContent());

    // 发送头信息
    $response->sendHeaders();

    // 输出响应内容
    echo $response->getContent();


    
	Cache::close();
} catch (\Exception $e) {
    pr($e->getMessage());
    pr(get_class($e) . ": " . $e->getMessage());
    pr(" File=" . $e->getFile());
    pr(" Line=" . $e->getLine());
    pr($e->getTraceAsString());
}
