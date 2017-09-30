<?php
/**
 * 静态数据类
 */
class StaticData {
    public static $_url        = '';
    public static $adminQAFlag = false;//QA为false下interface接口访问
    public static $_postData   = [];

    /**
     * 包头定义格式
     * @var array
     */
    public static $msgIds = [//msgId定义
                             'LoginRequest'            => 10000,//登陆包请求
                             'LoginResponse'           => 10001,//登陆包响应
                             'HeartBeatRequest'        => 10002,//心跳包请求
                             'HeartBeatResponse'       => 10003,//心跳包响应
                             'DataRequest'             => 10004,//“数据”包请求
                             'DataResponse'            => 10005,//“数据”包响应
                             'WebServerRequest'        => 10006,//“web服务器”包请求
                             'WebServerResponse'       => 10007,//“web服务器”包响应
                             'ChatSendRequest'         => 10008,//“聊天”包请求
                             'ChatSendResponse'        => 10009,//“聊天”包响应
                             'PauseServerHeartBeatReq' => 10010, //请求是否将服务端心跳包检测暂停
                             'No_Response'              => 99999,//无响应

    ];
    public static $msgIdMap = [//msgId 映射关系
        'LoginRequest'     => 'LoginResponse',
        'HeartBeatRequest' => 'HeartBeatRequest',
        'DataRequest'      => 'DataResponse',
        'WebServerRequest' => 'WebServerResponse',
        'ChatSendRequest'  => 'ChatSendResponse',
        'PauseServerHeartBeatReq' => 'No_Response',
    ];
    public static $logConfig = [//swoole log 长连接配置
                                'port'            => 6789,
                                'worker_num'      => 3,
                                'daemonize'       => false,
                                'dispatch_mode'   => 2,
                                'max_request'     => 10,
    ];

    public static $delaySocketSendFlag = false;//延迟发送标记
    public static $delaySocketSendData = [];//延迟发送的数据
}