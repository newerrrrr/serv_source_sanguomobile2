<?php
define('APP_PATH', dirname(dirname(__FILE__)));
include APP_PATH . "/app/lib/constant.php";
echo ENCODE_FLAG? 1: 0;
