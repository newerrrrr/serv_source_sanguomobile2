DROP TABLE IF EXISTS `admin_auth`;

CREATE TABLE `admin_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `auth` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

insert  into `admin_auth`(`id`,`name`,`auth`) values (1,'root','0');
insert  into `admin_auth`(`id`,`name`,`auth`) values (2,'low','');

DROP TABLE IF EXISTS `admin_log`;

CREATE TABLE `admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '玩家id',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `memo` text COLLATE utf8_unicode_ci,
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `admin_user`;

CREATE TABLE `admin_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '玩家id',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pwd_status` int(1) NOT NULL DEFAULT '0' COMMENT '0-未修改初始密码，1-已修改',
  `auth` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


insert  into `admin_user`(`id`,`name`,`password`,`pwd_status`,`auth`,`status`,`create_time`) values (1,'admin','2a915e220f683b798394babb4ecef1fb',1,1,0,'0000-00-00 00:00:00');
-- insert  into `admin_user`(`id`,`name`,`password`,`pwd_status`,`auth`,`status`,`create_time`) values (2,'ondine','2a915e220f683b798394babb4ecef1fb',0,2,0,'0000-00-00 00:00:00');


DROP TABLE IF EXISTS `notice`;

CREATE TABLE `notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '公告标题',
  `content` text COLLATE utf8_unicode_ci COMMENT '公告内容',
  `is_new` int(2) DEFAULT '0' COMMENT '1:最新 0:不是最新',
  `begin_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开启时间',
  `end_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '截至时间',
  `channel` varchar(1000) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '渠道string',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='公告';


DROP TABLE IF EXISTS `player_last_server`;

CREATE TABLE `player_last_server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_server_id` int(11) DEFAULT NULL,
  `login_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '登录时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `player_server_list`;

CREATE TABLE `player_server_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'uuid',
  `server_id` int(11) NOT NULL COMMENT '服务器列表id',
  `nick` varchar(100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '昵称',
  `avatar_id` int(5) DEFAULT NULL COMMENT '头像',
  `level` int(11) DEFAULT NULL COMMENT '等级',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='玩家所有游戏服务器列表';

DROP TABLE IF EXISTS `server_list`;

CREATE TABLE `server_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `areaName` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
  `status` int(11) DEFAULT '0' COMMENT '1:维护状态,不能进游戏',
  `isNew` int(11) DEFAULT '0',
  `gameServerHost` varchar(500) COLLATE utf8_unicode_ci DEFAULT '',
  `netServerHost` varchar(500) COLLATE utf8_unicode_ci DEFAULT '',
  `default_enter` int(2) DEFAULT '0' COMMENT '只能有1个为1的行记录,表示新玩家默认进这个服务器, 1: 默认进',
  `maintain_notice` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '维护公告:status=1的时候,前端显示该内容',
  `game_server_ip` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '游戏服内部ip，curl跨服请求时用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_server_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(500) COLLATE utf8_unicode_ci DEFAULT '',
  `value` varchar(500) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `cdk` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '玩家id',
  `cdk` char(12) COLLATE utf8_bin NOT NULL,
  `type` int(11) NOT NULL COMMENT '0:通用，1：非通用',
  `lang` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '空：不指定；',
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '空：不指定',
  `drop` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'drop数据',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '使用人数',
  `memo` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0:初始，1：已使用',
  `player_id` int(11) NOT NULL DEFAULT '0',
  `begin_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '有效开始时间',
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '有效结束时间',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rowversion` char(13) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cdk` (`cdk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `cdk_drop` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '玩家id',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `drop` text COLLATE utf8_unicode_ci NOT NULL,
  `memo` text COLLATE utf8_unicode_ci NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
--
-- Dumping data for table `login_server_config`
--

INSERT INTO `login_server_config` (`id`, `key`, `value`) VALUES (NULL, 'game_version', '1');

-- insert  into `server_list`(`id`,`areaName`,`name`,`status`,`isNew`,`gameServerHost`,`netServerHost`,`default_enter`) values (1,'1区','开发服务器(89)',0,0,'http://10.103.252.89','http://10.103.252.89:9501',1);
-- insert  into `server_list`(`id`,`areaName`,`name`,`status`,`isNew`,`gameServerHost`,`netServerHost`,`default_enter`) values (2,'2区','外网测试服务器',0,1,'http://101.231.186.12:80','http://101.231.186.12:9501',0);
-- insert  into `server_list`(`id`,`areaName`,`name`,`status`,`isNew`,`gameServerHost`,`netServerHost`,`default_enter`) values (3,'3区','测试服务器(79)',0,0,'http://10.103.252.79','http://10.103.252.79:9501',0);
-- insert  into `server_list`(`id`,`areaName`,`name`,`status`,`isNew`,`gameServerHost`,`netServerHost`,`default_enter`) values (4,'4区','测试服务器(74)',0,1,'http://10.103.252.74','http://10.103.252.74:9501',0);

-- 增加封测返利记录表
CREATE TABLE IF NOT EXISTS `refund_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gem` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='封测充值返利' AUTO_INCREMENT=1; 
--
-- Table structure for table `Android_Channel`
--
DROP TABLE IF EXISTS `Android_Channel`;
CREATE TABLE IF NOT EXISTS `Android_Channel` (
`id` int(11) NOT NULL ,
`channel_id` varchar(512) ,
`channel_name` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
-- INSERT UPDATE sql for 'Android_Channel';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('1','000054','huawei') ON DUPLICATE KEY UPDATE `id` = '1',`channel_id` = '000054',`channel_name` = 'huawei';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('2','000016','lenovo') ON DUPLICATE KEY UPDATE `id` = '2',`channel_id` = '000016',`channel_name` = 'lenovo';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('3','000020','oppo') ON DUPLICATE KEY UPDATE `id` = '3',`channel_id` = '000020',`channel_name` = 'oppo';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('4','000084','gionee') ON DUPLICATE KEY UPDATE `id` = '4',`channel_id` = '000084',`channel_name` = 'gionee';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('5','000014','meizu') ON DUPLICATE KEY UPDATE `id` = '5',`channel_id` = '000014',`channel_name` = 'meizu';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('6','000368','vivo') ON DUPLICATE KEY UPDATE `id` = '6',`channel_id` = '000368',`channel_name` = 'vivo';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('7','000066','mi') ON DUPLICATE KEY UPDATE `id` = '7',`channel_id` = '000066',`channel_name` = 'mi';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('8','160280','coolpad') ON DUPLICATE KEY UPDATE `id` = '8',`channel_id` = '160280',`channel_name` = 'coolpad';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('9','160136','tencent') ON DUPLICATE KEY UPDATE `id` = '9',`channel_id` = '160136',`channel_name` = 'tencent';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('10','000255','aligames') ON DUPLICATE KEY UPDATE `id` = '10',`channel_id` = '000255',`channel_name` = 'aligames';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('11','110000','baidu') ON DUPLICATE KEY UPDATE `id` = '11',`channel_id` = '110000',`channel_name` = 'baidu';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('12','000023','qihu') ON DUPLICATE KEY UPDATE `id` = '12',`channel_id` = '000023',`channel_name` = 'qihu';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('13','999999','test_anysdk') ON DUPLICATE KEY UPDATE `id` = '13',`channel_id` = '999999',`channel_name` = 'test_anysdk';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('14','160086','pengyouwan') ON DUPLICATE KEY UPDATE `id` = '14',`channel_id` = '160086',`channel_name` = 'pengyouwan';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('15','000003','downjoy') ON DUPLICATE KEY UPDATE `id` = '15',`channel_id` = '000003',`channel_name` = 'downjoy';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('16','000247','muzhiwan') ON DUPLICATE KEY UPDATE `id` = '16',`channel_id` = '000247',`channel_name` = 'muzhiwan';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('17','160198','x7sy') ON DUPLICATE KEY UPDATE `id` = '17',`channel_id` = '160198',`channel_name` = 'x7sy';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('18','001208','douyu') ON DUPLICATE KEY UPDATE `id` = '18',`channel_id` = '001208',`channel_name` = 'douyu';
