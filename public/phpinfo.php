<?php
$ips = [
  '127.0.0.5',//panlong's 
  '10.103.252.89',
  '10.103.252.79',
  '10.103.252.74',
  '10.103.252.87',
];
$ip = $_SERVER['SERVER_ADDR'];
if(in_array($ip, $ips)) {
  phpinfo();
} else {
  exit('对外网不开放');
}
