<?php
class UcCryption{
	static function e($txt, $key){
		$keylen = strlen($key);
		$key = self::transKey($key);
		$i = 0;
		$len = strlen($txt);
		$t = array();
		while($i < $len){
			//$t[] = chr(ord($txt[$i]) + $key[$i % $keylen]);
			$n = ord($txt[$i]) + $key[$i % $keylen];
			if($n <= 127){
				$t[] = chr($n) . chr(0);
			}else{
				$t[] = chr(127) . chr($n-127);
			}
			$i++;
		}
		$etxt = join('', $t);
		return base64_encode($etxt);
	}
	static function transKey($key){
		$i = 0;
		$len = strlen($key);
		$a = array();
		while($i < $len){
			$a[] = ord($key[$i]);
			$i++;
		}
		return $a;
	}

	static function d($txt, $key){
		$txt = base64_decode($txt);
		$len = floor(strlen($txt) / 2);
		$keylen = strlen($key);
		$key = self::transKey($key);
		$txt = str_split($txt, 2);
		$i = 0;
		$t = array();
		while($i < $len){
			$n = ord(substr($txt[$i], 0, 1)) + ord(substr($txt[$i], 1, 1));
			$t[] = chr($n - $key[$i % $keylen]);
			//$t[] = chr(ord($txt[$i]) - $key[$i % $keylen]);
			$i++;
		}
		return join('', $t);
	}
}
?>