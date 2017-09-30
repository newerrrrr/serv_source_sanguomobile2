<?php
/**
 * 常量存于此处，常量不要重复，以功能命名分类，注释需要写的详尽
 */
//顶级常量
defined('DEBUG_LOG_ON') || define('DEBUG_LOG_ON', false);//debug log文件开启
defined('CLI_LOG_ON') || define('CLI_LOG_ON', false);//log4cli方法显示开关
defined('ENCODE_FLAG') || define('ENCODE_FLAG', true);//对url进行加密压缩
defined('ACCESS_LOG_FLAG') || define('ACCESS_LOG_FLAG', false);//save accesslog in player_common_log

// COMMON  Begin
defined('NOWTIME') || define('NOWTIME', time());//当前时间戳
defined('CACHE_PLAYERDATA_TIMEOUT') || define('CACHE_PLAYERDATA_TIMEOUT', 3600*10);
defined('CACHEDB_PLAYER') || define('CACHEDB_PLAYER', 'cache');
defined('CACHEDB_STATIC') || define('CACHEDB_STATIC', 'static');
defined('CACHEDB_CHAT') || define('CACHEDB_CHAT', 'chat');//redis 聊天
defined('DIC_DATA_DIVISOR') || define('DIC_DATA_DIVISOR', 10000);//除数: 字典表中的数值
defined('INNER_REQUEST_VALIDATION_STRING') || define('INNER_REQUEST_VALIDATION_STRING', 'innnnner');//除数: 字典表中的数值
// COMMON  End
//Log Task Begin
defined('LOG_TASK_SWITCH_ON') || define('LOG_TASK_SWITCH_ON', 0);//1: 开启 0：关闭
defined('LOG_TASK_PLAYER_ID') || define('LOG_TASK_PLAYER_ID', 0);//playerId
//Log Task End

//Model Begin
defined('CACHE_KEY_ALL_DIC_TABLE') || define('CACHE_KEY_ALL_DIC_TABLE', 'DicTableData');//所有字段表存储的缓存的cache key

//Model End

//Model_Player Begin
defined('PLAYER_MAX_LEVEL') || define('PLAYER_MAX_LEVEL', 50);//玩家的最高等级
defined('PLAYER_MAX_VIPLEVEL') || define('PLAYER_MAX_VIPLEVEL', 12);//玩家的最高VIP等级
// defined('PLAYER_MOVE_IN_DURATION') || define('PLAYER_MOVE_IN_DURATION', 30*60);//玩家的行动力恢复时间，30分钟一点
defined('PLAYER_MAIL_EXPIRETIME') || define('PLAYER_MAIL_EXPIRETIME', 3600*24*3);//玩家邮件过期时间
defined('PAGE_ITEM_NUM') || define('PAGE_ITEM_NUM', 20);//搜索无联盟一页显示条数
defined('REDIS_KEY_ONLINE') || define('REDIS_KEY_ONLINE', 'ServOnline');//玩家在线时间戳key
defined('REDIS_KEY_ONLINE_MAX') || define('REDIS_KEY_ONLINE_MAX', 5*60);//超过时间算离线
//Model_Player End

//swoole常量 Begin
defined('SWOOLE_MSG_HEAD') || define('SWOOLE_MSG_HEAD', "SGMB");//数据包头的验证码
//swoole常量 End

//Guild Begin
defined('GUILD_MAX_NUM_INIT') || define('GUILD_MAX_NUM_INIT', 40);//创建联盟的初始化最大人数
//Guild End

//资源重量
defined('WEIGHT_GOLD') || define('WEIGHT_GOLD', 1);
defined('WEIGHT_FOOD') || define('WEIGHT_FOOD', 1);
defined('WEIGHT_WOOD') || define('WEIGHT_WOOD', 4);
defined('WEIGHT_STONE') || define('WEIGHT_STONE', 12);
defined('WEIGHT_IRON') || define('WEIGHT_IRON', 32);