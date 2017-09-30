CREATE TABLE `player_citybattle_donate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `last_donate_time` date NOT NULL DEFAULT '0000-00-00' COMMENT '最后一次捐献日期',
  `button1_counter` int(11) NOT NULL DEFAULT '0',
  `button2_counter` int(11) NOT NULL DEFAULT '0',
  `button3_counter` int(11) NOT NULL DEFAULT '0',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rowversion` char(13) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `player` ADD `camp_id` int NOT NULL DEFAULT 0 COMMENT '阵营' AFTER `avatar_id`;
ALTER TABLE `player` ADD `junzi` bigint NOT NULL DEFAULT 0 COMMENT '军资' AFTER `xuantie`;
-- 城战中派遣队伍列表
ALTER TABLE  `player_info` ADD `general_id_list` TEXT NULL COMMENT  '城战中派遣队伍列表';
-- 每天领取城池奖励的时间，每天一次
ALTER TABLE  `player_info` ADD `country_city_output_date` varchar(500) DEFAULT '' NULL COMMENT  '每天领取城池奖励的时间，每天一次';
-- 联盟阵营
ALTER TABLE `guild` ADD COLUMN `camp_id` INT(4) DEFAULT 0 NULL COMMENT '联盟阵营' AFTER `id`;
-- 最后一次转阵营时间
ALTER TABLE `guild` ADD COLUMN `change_camp_time`  TIMESTAMP DEFAULT '0000-00-00 00:00:00' NULL COMMENT '最后一次转阵营时间' AFTER `invite_end_time`;
-- 玩家充值等值人民币金额
ALTER TABLE  `player` ADD  `total_rmb` INT NOT NULL DEFAULT  '0' COMMENT  '充值等值人民币' AFTER  `attack_time`;

ALTER TABLE `rank` ADD `camp_id` int NOT NULL DEFAULT 0 COMMENT '阵营' AFTER `guild_name`;
