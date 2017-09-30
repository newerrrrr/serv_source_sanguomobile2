<?php
/**
 * AnySDK服务端SDK config for PHP
 * 
 * @author      李播<libo@anysdk.com>
 * @date        2016-01-11
 * @version     2.0.0
 * 
 * @author      李播<libo@anysdk.com>
 * @date        2016-04-14
 * @version     2.1.0
 * 
 * @link        http://docs.anysdk.com/OauthLogin     统一登录验证
 * @link        http://docs.anysdk.com/PaymentNotice  订单支付通知
 * @link        http://docs.anysdk.com/AdTracking     广告效果追踪
 */
defined('LOGIN_CHECK_URL')        or define('LOGIN_CHECK_URL',          'http://3thsdk.sanguomobile2.cn:9527/api/User/LoginOauth/');//3thsdk.sanguomobile2.cn
defined('ADTRACKING_REPORT_URL')  or define('ADTRACKING_REPORT_URL',    'http://3thsdk.sanguomobile2.cn:9527/v5/AdTracking/Submit/');//3thsdk.sanguomobile2.cn
defined('DEBUG_MODE')             or define('DEBUG_MODE',               FALSE);

// 游戏ID              前往dev.anysdk.com => 游戏列表 获取
defined('ANYSDK_GAME_ID')         or define('ANYSDK_GAME_ID',           12179);
// 增强密钥             前往dev.anysdk.com => 游戏列表 获取，此参数请严格保密
defined('ANYSDK_ENHANCED_KEY')    or define('ANYSDK_ENHANCED_KEY',      'MDRiZTBkOGExYmIxNjFjYzAzNWQ');
// private_key        前往dev.anysdk.com => 游戏列表 获取
defined('ANYSDK_PRIVATE_KEY')     or define('ANYSDK_PRIVATE_KEY',       'D4103614E23492A899084082E2028562');
