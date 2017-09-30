#!/bin/sh
ps -ef|grep 'php_task_push'|grep -v grep|awk '{print $2}'|xargs kill -15