<?php
set_time_limit(0);
if(!defined('APP_PATH')) {
    define('APP_PATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
if(!defined('TOOLMAP_PATH')) {
    define('TOOLMAP_PATH', dirname(__FILE__));
}
global $config;

$database = $config->cross_server->database;
$host     = $database['host'];
$username = $database['username'];
$password = $database['password'];
$dbname   = $database['dbname'];

$startTime = microtime_float();
/**
 * 求xy
 */
function xy($block){
    $y = floor($block / 7);
    $x = ($block - $y * 7) * 12;
    $y *= 12;
    return ['from_x'=>$x, 'to_x'=>$x+12, 'from_y'=>$y, 'to_y'=>$y+12];
}

function calcBlockByXy($x, $y){
    return floor($x/12)+floor($y/12)*7;
}

function generate(){
    $areaArr = [
        '5'   => 5,
        '58'  => 4,
        '100' => 3,
        '13'  => 2,
        '127' => 1,
    ];
    $allBlockInfo = include(TOOLMAP_PATH.'/cross_block_info.php');
    $allMapInfo = include(TOOLMAP_PATH.'/cross_map_info.php');

    $xyArr = ['37_61','37_59','32_67','47_67','55_43','29_57','41_57','35_48','46_54','45_43','36_39','26_34','37_34','41_25','51_37','56_37','55_25','42_69','40_69','38_69','36_69','34_69','42_71','40_71','38_71','36_71','34_71','40_61','42_61','44_61','46_61','48_61','34_61','32_61','30_61','28_61','26_61','61_44','63_44','65_44','65_46','63_46','61_46','65_48','63_48','61_48','59_48','64_59','62_59','60_59','58_59','56_59','64_57','62_57','60_57','58_57','56_57','30_52','30_44','30_46','30_48','30_50','28_52','28_44','28_46','28_48','28_50','43_43','43_45','43_47','43_49','43_51','41_43','41_45','41_47','41_49','41_51','31_32','29_32','27_32','25_32','23_32','31_30','29_30','27_30','25_30','23_30','34_21','32_21','30_21','28_21','26_21','34_19','32_19','30_19','28_19','26_19','60_34','58_34','56_34','54_34','52_34','60_32','58_32','56_32','54_32','52_32','60_30','58_30','56_30','54_30','52_30','60_28','58_28','56_28','54_28','52_28'];
    $arrLen = count($allBlockInfo);//数组长度
    $sql = 'INSERT INTO `cross_map_config` (`id`, `area`, `map_type`,`x`, `y`, `cross_map_element_id`, `max_durability`, `next_area`, `target_area`, `memo`) VALUES ';
    foreach($allBlockInfo as $k=>$v) {
        foreach($v as $kk=>$vv) {
            $blockId = $k*$arrLen+$kk;
            $xy = xy($blockId);
            $mapInfo = $allMapInfo[$vv];

            //内层坐标的xy轴与外层坐标的xy轴是相反的
            $time = date('Y-m-d H:i:s');
            foreach($mapInfo as $kkk=>$vvv) {
                foreach($vvv as $xx=>$yyArea) {
//                    $area = $yyArea[1];
//                    $yy   = $yyArea[0];

                    $x    = $xy['from_x'] + $xx;
                    $y    = $xy['from_y'] + $kkk;
                    if(in_array($x.'_'.$y, $xyArr)) continue;
                    $area = $yyArea[1];
                    if(array_key_exists($area, $areaArr)) {
                        $area = $areaArr[$area];
                    }
                    if($yyArea[0]==0) {
                        $crossMapElementId = 0;
                        $memo = "空地@{$time}";
                    } else {
                        $crossMapElementId = 1801;
                        $memo = "山水@{$time}";
                    }
                    $sql1 = $sql . "(NULL, {$area}, 1, {$x}, {$y}, {$crossMapElementId}, 0,0,0, '{$memo}');";

                    yield $sql1;


                }
            }
        }
    }
}


//清空表map数据
$pre_mysqli = @new mysqli($host, $username, $password, $dbname);
/* !!!!!!!!!!!!!!!!!!!!!!!!! 切记更改上面过滤x_y的数组 !!!!!!!!!!!!!!!!!!!!!!!!! */
$pre_sql = "
TRUNCATE `cross_map_config`;
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('1','1','1','37','61','','30101','100','','','1','攻城锤');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('2','1','1','37','59','','30201','100','3','','1','城门A');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('3','1','1','32','67','','30501','100','','','1','投石车A1');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('4','1','1','47','67','','30502','100','','','2','投石车A2');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('5','1','2','55','43','','30401','100','5','','1','云梯');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('6','1','3','29','57','','30301','100','','1','1','床弩B1');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('7','1','3','41','57','','30302','100','','1','2','床弩B2');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('8','1','3','35','48','','30503','100','','','3','投石车B1');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('9','1','3','46','54','','30504','100','','','4','投石车B2');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('10','1','3','45','43','','30509','100','','','5','投石车B3');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('11','1','3','36','39','','30202','100','4','','2','城门B');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('12','1','4','26','34','','30505','100','','','6','投石车C1');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('13','1','4','37','34','','30506','100','','','7','投石车C2');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('14','1','4','41','25','','30203','100','5','','3','城门C');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('15','1','5','51','37','','30303','100','','2','3','床弩D1');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('16','1','5','56','37','','30304','100','','2','4','床弩D2');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('17','1','5','55','25','','30601','100','','','1','内城');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('18','1','1','42','69','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('19','1','1','40','69','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('20','1','1','38','69','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('21','1','1','36','69','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('22','1','1','34','69','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('23','1','1','42','71','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('24','1','1','40','71','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('25','1','1','38','71','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('26','1','1','36','71','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('27','1','1','34','71','1','0','0','','','','1区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('28','1','1','40','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('29','1','1','42','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('30','1','1','44','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('31','1','1','46','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('32','1','1','48','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('33','1','1','34','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('34','1','1','32','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('35','1','1','30','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('36','1','1','28','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('37','1','1','26','61','2','0','0','','','','1区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('38','1','2','61','44','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('39','1','2','63','44','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('40','1','2','65','44','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('41','1','2','65','46','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('42','1','2','63','46','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('43','1','2','61','46','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('44','1','2','65','48','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('45','1','2','63','48','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('46','1','2','61','48','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('47','1','2','59','48','2','0','0','','','','2区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('48','1','2','64','59','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('49','1','2','62','59','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('50','1','2','60','59','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('51','1','2','58','59','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('52','1','2','56','59','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('53','1','2','64','57','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('54','1','2','62','57','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('55','1','2','60','57','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('56','1','2','58','57','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('57','1','2','56','57','1','0','0','','','','2区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('58','1','3','30','52','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('59','1','3','30','44','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('60','1','3','30','46','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('61','1','3','30','48','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('62','1','3','30','50','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('63','1','3','28','52','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('64','1','3','28','44','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('65','1','3','28','46','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('66','1','3','28','48','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('67','1','3','28','50','2','0','0','','','','3区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('68','1','3','43','43','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('69','1','3','43','45','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('70','1','3','43','47','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('71','1','3','43','49','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('72','1','3','43','51','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('73','1','3','41','43','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('74','1','3','41','45','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('75','1','3','41','47','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('76','1','3','41','49','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('77','1','3','41','51','1','0','0','','','','3区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('78','1','4','31','32','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('79','1','4','29','32','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('80','1','4','27','32','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('81','1','4','25','32','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('82','1','4','23','32','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('83','1','4','31','30','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('84','1','4','29','30','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('85','1','4','27','30','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('86','1','4','25','30','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('87','1','4','23','30','2','0','0','','','','4区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('88','1','4','34','21','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('89','1','4','32','21','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('90','1','4','30','21','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('91','1','4','28','21','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('92','1','4','26','21','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('93','1','4','34','19','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('94','1','4','32','19','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('95','1','4','30','19','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('96','1','4','28','19','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('97','1','4','26','19','1','0','0','','','','4区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('98','1','5','60','34','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('99','1','5','58','34','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('100','1','5','56','34','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('101','1','5','54','34','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('102','1','5','52','34','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('103','1','5','60','32','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('104','1','5','58','32','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('105','1','5','56','32','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('106','1','5','54','32','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('107','1','5','52','32','1','0','0','','','','5区-攻点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('108','1','5','60','30','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('109','1','5','58','30','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('110','1','5','56','30','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('111','1','5','54','30','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('112','1','5','52','30','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('113','1','5','60','28','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('114','1','5','58','28','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('115','1','5','56','28','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('116','1','5','54','28','2','0','0','','','','5区-守点');
INSERT INTO `cross_map_config` (`id`,`map_type`,`area`,`x`,`y`,`sides_type`,`cross_map_element_id`,`max_durability`,`next_area`,`target_area`,`build_num`,`memo`) VALUES ('117','1','5','52','28','2','0','0','','','','5区-守点');


";
if(!$pre_mysqli->multi_query($pre_sql)) {
    echo $pre_mysqli->error;
    exit;
}
$pre_mysqli->close();


$mysqli = @new mysqli($host, $username, $password, $dbname);
if(mysqli_connect_errno()){
    echo "ERROR:".mysqli_connect_error();
    exit;
}

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