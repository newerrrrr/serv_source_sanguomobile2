<?php
set_time_limit(0);
if(!defined('APP_PATH')) {
    define('APP_PATH', dirname(dirname(dirname(dirname(__FILE__)))));
}
if(!defined('TOOLMAP_PATH')) {
    define('TOOLMAP_PATH', dirname(__FILE__));
}
global $config;

$database = $config->database;

$host     = $database['host'];
$username = $database['username'];
$password = $database['password'];
$dbname   = $database['dbname'];

$startTime = microtime_float();
/**
 * 求xy
 */
function xy($block){
    $y = floor($block / 103);
    $x = ($block - $y * 103) * 12;
    $y *= 12;
    return ['from_x'=>$x, 'to_x'=>$x+12, 'from_y'=>$y, 'to_y'=>$y+12];
}

function calcBlockByXy($x, $y){
    return floor($x/12)+floor($y/12)*103;
}

function generate(){
    $allBlockInfo = include(TOOLMAP_PATH.'/block_info.php');
    $allMapInfo = include(TOOLMAP_PATH.'/map_info.php');
    $arrLen = count($allBlockInfo);//数组长度
    $sql = 'INSERT INTO `map` (`id`, `x`, `y`, `block_id`, `map_element_id`, `map_element_origin_id`, `map_element_level`, `topography`, `guild_id`, `player_id`, `resource`, `durability`, `max_durability`, `status`, `update_time`, `create_time`, `build_time`, `rowversion`) VALUES ';
    foreach($allBlockInfo as $k=>$v) {
        foreach($v as $kk=>$vv) {
            $blockId = $k*$arrLen+$kk;
            $xy = xy($blockId);
            $mapInfo = $allMapInfo[$vv];

            //内层坐标的xy轴与外层坐标的xy轴是相反的
            foreach($mapInfo as $kkk=>$vvv) {
                foreach($vvv as $xx=>$yy) {
                    if($yy!=0) {
                        $x = $xy['from_x']+$xx;
                        $y = $xy['from_y']+$kkk;
                        $sql1 = $sql."(NULL, {$x}, {$y}, {$blockId}, 1801, 20, 1, 0, 0, 0, 0, 0, 0, 1, now(), now(), '0000-00-00 00:00:00', 888);";
                        yield $sql1;
                    }

                }
            }
        }
    }
}


$mysqli = @new mysqli($host, $username, $password, $dbname);
if(mysqli_connect_errno()){
    echo "ERROR:".mysqli_connect_error();
    exit;
}

//清空表map数据
$mysqli->query('truncate map;');
//生成数据中
echo "    生成数据中...";
foreach(generate() as $v) {
    $re = $mysqli->query($v);

    if(!$re){
        echo "ERROR:".$mysqli->error.":".$v;
        break;
    }
}

$mysqli->close();
$subTime = microtime_float() - $startTime;
echo "ok!耗时：". $subTime;
echo PHP_EOL;
return;