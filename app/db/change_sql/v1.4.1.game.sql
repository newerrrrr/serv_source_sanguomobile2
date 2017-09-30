ALTER TABLE `player_info` ADD COLUMN `newbie_login` VARCHAR(255) DEFAULT '' NULL COMMENT '新玩家前x天登陆情况' AFTER `first_create_guild`;
ALTER TABLE `player_info` ADD COLUMN `secretary_status` int(11) DEFAULT 0 NULL COMMENT '随身秘书状态 0:等待打开， 1：玩家选择需要秘书引导选择军事， 2：一个是玩家需要引导选择了内政， 3：一个是玩家不需要秘书引导，关闭';

ALTER TABLE `player` ADD COLUMN `attack_time` timestamp DEFAULT '0000-00-00 00:00:00' NULL COMMENT '最后攻击时间';

ALTER TABLE  `player_buff` ADD  `noob_protection` INT( 11 ) DEFAULT  '0' COMMENT '顽强斗志';


CREATE TABLE `player_newbie_activity_charge` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`period` INT (11) NOT NULL,
	`gem` INT (11) NOT NULL DEFAULT '0' COMMENT '累计元宝数',
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'gem1档已领,gem2档已领...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `period`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_newbie_activity_consume` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`period` INT (11) NOT NULL COMMENT '军团位置',
	`gem` INT (11) NOT NULL DEFAULT '0' COMMENT '累计元宝数',
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'gem1档已领,数量;...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `period`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_newbie_activity_login` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '1档已领,2档已领...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;