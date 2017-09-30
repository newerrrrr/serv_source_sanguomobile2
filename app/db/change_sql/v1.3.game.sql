ALTER TABLE `guild` ADD `science_type` int NOT NULL DEFAULT 11 COMMENT '优先捐献科技类型' AFTER `mission_score`;
ALTER TABLE `guild` ADD `donate_date` date NOT NULL DEFAULT '0000-00-00' COMMENT '最后捐献日期' AFTER `science_type`;
ALTER TABLE `guild` ADD `donate_counter` int NOT NULL DEFAULT 0 COMMENT '当日捐献人数' AFTER `donate_date`;

ALTER TABLE `player_guild_donate` ADD `last_donate_time` date NOT NULL DEFAULT '0000-00-00' COMMENT '最后一次捐献日期' AFTER `status`;
ALTER TABLE `player_guild_donate` ADD `donate_reward` varchar(255) NOT NULL DEFAULT '' COMMENT '领取奖励标志，逗号分隔' AFTER `last_donate_time`;
ALTER TABLE `player_guild_donate` ADD `reward_time` date NOT NULL DEFAULT '0000-00-00' COMMENT '奖励时间' AFTER `donate_reward`;

ALTER TABLE `player_general` ADD `zuoji_id` int NOT NULL DEFAULT 0 COMMENT '坐骑' AFTER `horse_id`;
ALTER TABLE `player_general` ADD `skill_lv` int NOT NULL DEFAULT 0 COMMENT '技能等级' AFTER `zuoji_id`;
ALTER TABLE `player_general` ADD `star_lv` int NOT NULL DEFAULT 0 COMMENT '星级' AFTER `lv`;

ALTER TABLE `player_general` ADD `force_rate` int NOT NULL DEFAULT 0 COMMENT 'force成长率' AFTER `army_id`;
ALTER TABLE `player_general` ADD `intelligence_rate` int NOT NULL DEFAULT 0 COMMENT 'intelligence成长率' AFTER `force_rate`;
ALTER TABLE `player_general` ADD `governing_rate` int NOT NULL DEFAULT 0 COMMENT 'governing成长率' AFTER `intelligence_rate`;
ALTER TABLE `player_general` ADD `charm_rate` int NOT NULL DEFAULT 0 COMMENT 'charm成长率' AFTER `governing_rate`;
ALTER TABLE `player_general` ADD `political_rate` int NOT NULL DEFAULT 0 COMMENT 'political成长率' AFTER `charm_rate`;

-- 新玩家邮件定时发送 Email_tips
ALTER TABLE `player_info` ADD COLUMN `email_tips_id` INT(11) DEFAULT 0 NULL COMMENT '已经发送的email_tips表id';
-- 聚宝盆抽取
ALTER TABLE `player_info`
  ADD COLUMN `bowl_type1_last_time` TIMESTAMP DEFAULT '0000-00-00 00:00:00' NULL COMMENT '占星最近一次免费抽取时间',
  ADD COLUMN `bowl_type2_last_time` TIMESTAMP DEFAULT '0000-00-00 00:00:00' NULL COMMENT '天陨最近一次免费抽取时间';
  
-- 聚宝盆计数器
ALTER TABLE `player_info` ADD COLUMN `bowl_counter_drop_group_1` INT(11) DEFAULT 1 NULL COMMENT '聚宝盆计数器1';
ALTER TABLE `player_info` ADD COLUMN `bowl_counter_drop_group_2` INT(11) DEFAULT 1 NULL COMMENT '聚宝盆计数器2';
ALTER TABLE `player_info` ADD COLUMN `bowl_counter_drop_group_10` INT(11) DEFAULT 1 NULL COMMENT '聚宝盆计数器10';
ALTER TABLE `player_info` ADD COLUMN `bowl_counter_drop_group_11` INT(11) DEFAULT 1 NULL COMMENT '聚宝盆计数器11';
ALTER TABLE `player_info` ADD COLUMN `bowl_counter_drop_group_12` INT(11) DEFAULT 1 NULL COMMENT '聚宝盆计数器12';
ALTER TABLE `player_info` ADD COLUMN `first_high_astrology_drop` INT(11) DEFAULT 0 NULL COMMENT '是否首次天陨掉落 0:未操作 1：已操作';

CREATE TABLE `player_activity_wheel` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`activity_configure_id` INT (11) NOT NULL COMMENT '',
	`counter` INT (11) NOT NULL DEFAULT '0' COMMENT '累计转盘数',
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

INSERT INTO `configure` (`id`, `key`, `value`) VALUES (NULL, 'activity_player_count', '1000');


ALTER TABLE `guild_science` CHANGE `finish_time` `finish_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `player_guild_donate` CHANGE `finish_time` `finish_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'cd完成时间';
ALTER TABLE `player_mail_info` CHANGE `expire_time` `expire_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';

ALTER TABLE `player_info` ADD COLUMN `bowl_counter_drop_group_14` INT(11) DEFAULT 1 NULL COMMENT '聚宝盆计数器14-特殊';
ALTER TABLE `player_info` ADD COLUMN `bowl_counter_drop_group_14_status` INT(11) DEFAULT 0 NULL COMMENT '聚宝盆特殊掉落标记 1：已掉落 0：未掉落';