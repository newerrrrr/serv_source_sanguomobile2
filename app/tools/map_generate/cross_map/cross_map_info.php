<?php
$tmxFileNameNumberMax = 38;
$mapInfo = [];

function tmx2Array($filename){
//    $filename = __DIR__.'/cross_map_res/map_ss23.tmx';
    $x = simplexml_load_file($filename);
//    echo '11111',$filename,PHP_EOL;
    $x = (array)($x);
    $layer = $x['layer'];
    foreach($layer as $k=>$v) {
        $vv = (array)$v;
//        print_r($v);
        if($vv['@attributes']['name']=='layer_1') {
            $data = $vv['data'];
            //break;
        }
        if($vv['@attributes']['name']=='layer_area') {
            $areaData = $vv['data'];
            //break;
        }
    }
    // $data = (array)$x->layer;
    // $data = $data['data'];
    if(strpos($data, ',')===false) {//加密
        $data = zlib_decode(base64_decode($data));
        $data = array_values(unpack('V*', $data));
    } else {
        $data = array_map('trim', explode(',', $data));
    }

    if(strpos($areaData, ',')===false) {//加密
        $areaData = zlib_decode(base64_decode($areaData));
        $areaData = array_values(unpack('V*', $areaData));
    } else {
        $areaData = array_map('trim', explode(',', $areaData));
    }


    foreach($data as $k=>$v){
        $data[$k] = [$v, $areaData[$k]];
    }

    $data = array_chunk($data, 12);
    return $data;
}
//$tmxFiles = glob("mapRes/*.tmx");
$tmxFiles = glob(__DIR__ . "/cross_map_res/*.tmx");
foreach($tmxFiles as $v) {
	$vv = $v;
	$vp = strrpos($vv, '/');
	if($vp !== false){
		$vv = substr($vv, $vp+1);
	}
    $num = explode("_ss", $vv);
    $num = explode(".", $num[1]);
    $num = $num[0];
    $mapInfo[$num] = tmx2Array($v);
}
//$re = tmx2Array('mapRes/map_38.tmx');
//print_r($re);
//var_dump($mapInfo);
return $mapInfo;
