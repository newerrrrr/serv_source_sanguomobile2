<?php
$tmxFileNameNumberMax = 38;
$mapInfo = [];

function tmx2Array($filename){
    $x = simplexml_load_file($filename);
    $x = (array)($x);
    $layer = $x['layer'];
    foreach($layer as $k=>$v) {
        $vv = (array)$v;
        if($vv['@attributes']['name']=='layer_1') {
            $data = $vv['data'];
            break;
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
    $data = array_chunk($data, 12);
    return $data;
}
//$tmxFiles = glob("mapRes/*.tmx");
$tmxFiles = glob(__DIR__ . "/mapRes/*.tmx");
foreach($tmxFiles as $v) {
	$vv = $v;
	$vp = strrpos($vv, '/');
	if($vp !== false){
		$vv = substr($vv, $vp+1);
	}
    $num = explode("_", $vv);
    $num = explode(".", $num[1]);
    $num = $num[0];
    $mapInfo[$num] = tmx2Array($v);
}
//$re = tmx2Array('mapRes/map_38.tmx');
//print_r($re);
//var_dump($mapInfo);
return $mapInfo;
