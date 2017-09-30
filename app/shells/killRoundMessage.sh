#!/bin/sh
ps -ef|grep 'php_swoole_round_message'|grep -v grep|awk '{print $2}'|xargs kill -15