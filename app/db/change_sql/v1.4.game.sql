ALTER TABLE `player` ADD `feats` int NOT NULL DEFAULT '0' COMMENT '功勋' AFTER `guild_coin`;
-- 首次建盟免费标记
ALTER TABLE `player_info` ADD COLUMN `first_create_guild` INT(2) DEFAULT 0 NULL COMMENT '0:未建过盟 1:建过盟' AFTER `bowl_counter_drop_group_14_status`;

CREATE TABLE `guild_gift_distribution_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT COMMENT '主键',
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`guild_id` INT (11) NOT NULL COMMENT '公会id',
	`gift_id` INT (11) NOT NULL COMMENT '礼物',
	`round` INT (11) NOT NULL COMMENT '轮数',
	`type` INT (11) NOT NULL COMMENT '类型',
	`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB DEFAULT CHARSET = utf8 COMMENT = '玩家获取公会礼包记录';

CREATE TABLE `guild_gift_pool` (
	`id` INT (11) NOT NULL AUTO_INCREMENT COMMENT '主键',
	`guild_id` INT (11) NOT NULL COMMENT '公会id',
	`round` INT (11) NOT NULL COMMENT '比赛轮数',
	`type` INT (11) NOT NULL COMMENT '比赛类型',
	`gift_id` INT (11) NOT NULL COMMENT '礼包id',
	`num` INT (11) NOT NULL COMMENT '礼包数量',
	`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
	PRIMARY KEY (`id`),
	UNIQUE KEY `guild_id` (
		`guild_id`,
		`round`,
		`type`,
		`gift_id`
	)
) ENGINE = INNODB DEFAULT CHARSET = utf8 COMMENT = '公会礼包表';
