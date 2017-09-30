-- 大额邮件
ALTER TABLE `player_info` ADD COLUMN `big_deal_mail` INT(11) DEFAULT 0 NULL COMMENT '大额邮件 0:未发送 1:发送过' AFTER `facebook_share_count`;


ALTER TABLE `player_order` CHANGE `price` `price` FLOAT NOT NULL;
update player_order a set price=(select price from pricing where payment_code=a.payment_code);
ALTER TABLE `player_order` ADD `tunnel` VARCHAR(10) NOT NULL DEFAULT 'game' COMMENT 'game,web' AFTER `out_trade_no`;

ALTER TABLE `player_mail` ADD INDEX(`expire_time`);
ALTER TABLE `player_mail_info` ADD INDEX(`expire_time`);

ALTER TABLE `activity_configure` CHANGE `activity_para` `activity_para` TEXT NOT NULL;
ALTER TABLE `activity_configure` ADD `status` INT(11) NOT NULL DEFAULT 1 COMMENT '0:未开启，1:开启' AFTER `end_time`;

ALTER TABLE `alliance_match_list` MODIFY COLUMN `type`  int(11) NOT NULL COMMENT '1 捐献 2 和氏璧 3 据点 4 黄巾起义' AFTER `id`;

ALTER TABLE `player_online_award` ADD INDEX `pid` (`player_id`, `date_limit`);
ALTER TABLE `player_sign_award` ADD INDEX `pid` (`player_id`, `round_flag`);
ALTER TABLE `player_soldier_injured` ADD INDEX `pid` (`player_id`);
ALTER TABLE `player_target` ADD INDEX `pid` (`player_id`, `award_status`);
ALTER TABLE `player_time_limit_match` ADD INDEX `pid` (`player_id`, `time_limit_match_list_id`);
ALTER TABLE `player_time_limit_match_total` ADD INDEX `pid` (`player_id`, `time_limit_match_config_id`);
ALTER TABLE `time_limit_match_config` ADD INDEX `status` (`status`);


CREATE TABLE `guild_huangjin` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL DEFAULT '0',
	`score` INT (11) NOT NULL DEFAULT '0' COMMENT '积分',
	`lost_times` INT (11) NOT NULL DEFAULT '0' COMMENT '本次失败次数',
	`last_wave` INT (11) NOT NULL DEFAULT '0' COMMENT '最后波次（无论输赢）',
	`last_win_wave` INT (11) NOT NULL DEFAULT '0' COMMENT '最后胜利波次',
	`top_wave` INT (11) NOT NULL DEFAULT '0' COMMENT '本次100%击杀最大波次',
	`history_top_wave` INT (11) NOT NULL DEFAULT '0' COMMENT '历史100%击杀最大波次',
	`round` INT (11) NOT NULL DEFAULT '0' COMMENT '对应alliance_match_list-round',
	`status` INT (11) NOT NULL DEFAULT '0' COMMENT '0.未开始，1.正在进行，2.完成',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	UNIQUE KEY `guild_id` (`guild_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = COMPACT;

CREATE TABLE `guild_shop_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL DEFAULT '0',
	`type` INT (11) NOT NULL COMMENT '1.进货；2.购买',
	`player_id` INT (11) NOT NULL DEFAULT '0',
	`nick` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
	`item_id` INT (11) NOT NULL DEFAULT '0',
	`num` INT (255) NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id` (`guild_id`) USING BTREE
) ENGINE = INNODB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = COMPACT;

CREATE TABLE `player_activity_charge` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`activity_configure_id` INT (11) NOT NULL COMMENT '军团位置',
	`gem` INT (11) NOT NULL DEFAULT '0' COMMENT '累计元宝数',
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'gem1档已领,gem2档已领...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (
		`player_id`,
		`activity_configure_id`
	) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_activity_login` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`activity_configure_id` INT (11) NOT NULL COMMENT '军团位置',
	`days` INT (11) NOT NULL DEFAULT '0' COMMENT '累计天数',
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '1档已领,2档已领...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (
		`player_id`,
		`activity_configure_id`
	) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

ALTER TABLE `player_coordinate` ADD `type` INT(11) NOT NULL DEFAULT 0 COMMENT '分类' AFTER `player_id`;