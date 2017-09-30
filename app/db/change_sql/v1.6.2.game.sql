ALTER TABLE `player` ADD `jiangyin` bigint NOT NULL DEFAULT 0 COMMENT '将印' AFTER `point`;
ALTER TABLE `player` ADD `xuantie` bigint NOT NULL DEFAULT 0 COMMENT '玄铁' AFTER `jiangyin`;


ALTER TABLE `player_info` ADD `skill_wash_date` date NOT NULL DEFAULT '0000-00-00' COMMENT '最后技能洗炼时间' AFTER `newbie_pay`;

CREATE TABLE `stat_snapshot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '1:留存，2.付费',
  `channel` varchar(255) NOT NULL DEFAULT '' COMMENT '',
  `liucun2` float NOT NULL DEFAULT '0',
  `liucun3` float NOT NULL DEFAULT '0',
  `liucun4` float NOT NULL DEFAULT '0',
  `liucun5` float NOT NULL DEFAULT '0',
  `liucun6` float NOT NULL DEFAULT '0',
  `liucun7` float NOT NULL DEFAULT '0',
  `liucun14` float NOT NULL DEFAULT '0',
  `liucun30` float NOT NULL DEFAULT '0',
  `pay_rate` float NOT NULL DEFAULT '0',
  `pay_rmb` float NOT NULL DEFAULT '0',
  `arpu` float NOT NULL DEFAULT '0',
  `arppu` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dt` (`dt`,`type`,`channel`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- 祭天活动玩家表
CREATE TABLE `player_activity_sacrifice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL COMMENT '玩家id',
  `activity_configure_id` int(11) DEFAULT NULL,
  `times` int(11) DEFAULT '0' COMMENT '抽奖次数',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `player_activity_sacrifice_player_id_index` (`player_id`,`activity_configure_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='祭天活动';
-- 登录ip
ALTER TABLE `player_info` ADD COLUMN `login_ip` VARCHAR(50) NULL COMMENT '登录ip' AFTER `skill_wash_date`;