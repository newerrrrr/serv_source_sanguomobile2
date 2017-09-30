<?php
//tools for replace errcode
//CONFIG BEGIN
$dirs = array(
    'D:/project/sanguo_mobile_2_server/trunk/app',
);
$i = 10382;
$re = [];

$set = array();
//CONFIG END

foreach ($dirs as $v) {//遍历所有目录
    $re = rglob($v);
}

//循环使用正则匹配
foreach ($set as $file) {
    if (file_exists($file)) {
        $flag1 = $flag2 = $flag3 = false;
        $content = file_get_contents($file);
        echo "replace {$file} BEGIN...\n";
        //case a 替换errCode格式  $errCode = 'hihihihihhi';
        $content = preg_replace_callback ("#(.*)?errCode = \'(.*)?\'(.*)?#", function($m) use (&$i, &$re, &$flag1){
            $flag1 = true;
            $re[$i] = $m[2];
            return $m[1] . 'errCode = ' . $i++ . preg_replace("/\n/", " ", $m[3]) . '//' . $m[2];
        }, $content);
        if($flag1) echo "errCode replace done\n";

        //case b 替换err = ..//"格式
        $content = preg_replace_callback ("#(.*)err = \d+;//\"(.*)\"(.*)?#", function($m) use (&$i, &$re, &$flag2){
            $flag2 = true;
            $re[$i] = $m[2];
            return $m[1] . 'err = ' . $i++ . ';//' . $m[2];
        }, $content);
        if($flag2) echo "err replace done\n";
        //case c 替换err = ..//"格式
        $content = preg_replace_callback ("#(.*)throw new Exception\('ERRMSG:(.*)?'(.*)?#", function($m) use (&$i, &$re, &$flag3){
            $flag3 = true;
            $re[$i] = $m[2];
            $m3 = preg_replace("/\n/", " ", $m[3]);
            $m3 = preg_replace("/\r/", " ", $m3);
            return $m[1] . 'throw new Exception(' . $i++ . $m3 . '//' . $m[2];
        }, $content);
        if($flag3) echo "throw new Exception replace done\n";
        file_put_contents($file, $content);
        echo "replace {$file} END\n";
    }
}
//errcode写入文件
$errCodeContent = '';
foreach($re as $k=>$v) {
    $errCodeContent .= $k . "\t" . $v . PHP_EOL;
}
if($errCodeContent!='') {
    file_put_contents('d:\errcode.txt', $errCodeContent);
}


function rglob ($dir) {//查找符合条件的文件
    global $set;
    $len = strlen('.php');
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $fullName = $dir . '/' . $file;
                if (is_file($fullName)) {
                    if($file=='errcode.php') {
                        continue;
                    }
                    $suffix = substr($file, strlen($file)-$len, $len);
                    if ($suffix == '.php') {
                        $set[] = $fullName;
                    }
                } elseif (is_dir($fullName)) {
                    rglob($fullName);
                }
            }
        }
        closedir($handle);
    }
}
echo 'ok';