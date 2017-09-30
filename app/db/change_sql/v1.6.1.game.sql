ALTER TABLE `player_info` ADD `newbie_pay` int NOT NULL DEFAULT 0 COMMENT '新手充值奖励';

CREATE TABLE `activity_panic_buy` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`activity_configure_id` INT (11) NOT NULL,
	`buy_id` INT (11) NOT NULL DEFAULT '0',
	`price` INT (11) NOT NULL COMMENT '价格',
	`num` INT (11) NOT NULL DEFAULT '0',
	`limit` INT (11) NOT NULL,
	`drop` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`pay_day` date NOT NULL,
	`begin_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `activity_configure_id` (
		`activity_configure_id`,
		`buy_id`
	) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_activity_exchange` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`activity_configure_id` INT (11) NOT NULL,
	`exchange_id` INT (11) NOT NULL DEFAULT '0',
	`num` INT (11) NOT NULL DEFAULT '0',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (
		`player_id`,
		`activity_configure_id`,
		`exchange_id`
	) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_activity_panic_buy` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`activity_configure_id` INT (11) NOT NULL COMMENT '军团位置',
	`date` date NOT NULL,
	`gem` INT (11) NOT NULL DEFAULT '0' COMMENT '充值元宝数',
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'buy_id1,buy_id2...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (
		`player_id`,
		`activity_configure_id`,
		`date`
	) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;


insert into player_build (player_id, build_id, origin_build_id, build_level, general_id_1, position, resource_in, storage_max, resource_start_time, create_time) select id, 49001, 50, 1, 0, 1023, 0, 0, now(), now() from player;