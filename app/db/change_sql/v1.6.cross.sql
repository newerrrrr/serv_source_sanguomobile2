ALTER TABLE `cross_battle` ADD `first_blood_1` int NOT NULL DEFAULT '0' COMMENT '一血玩家id(上)' AFTER `guild_2_kill`;
ALTER TABLE `cross_battle` ADD `first_blood_2` int NOT NULL DEFAULT '0' COMMENT '一血玩家id(下)' AFTER `first_blood_1`;

ALTER TABLE `cross_player` ADD `continue_kill` int NOT NULL DEFAULT '0' COMMENT '连杀人数' AFTER `kill_soldier`;
ALTER TABLE `cross_player` ADD `buff` text NOT NULL DEFAULT '' COMMENT 'buff' AFTER `dead_times`;
ALTER TABLE `cross_player` ADD `debuff_queuetime` int NOT NULL DEFAULT '0' COMMENT '下次出征行军时间延长(秒)' AFTER `buff`;

ALTER TABLE `cross_guild` ADD `buff_move` float NOT NULL DEFAULT '0' COMMENT '行军加成（百分比）' AFTER `rank`;
ALTER TABLE `cross_guild` ADD `buff_move_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '行军加成技能' AFTER `buff_move`;

ALTER TABLE `cross_guild` ADD `buff_cityattack` float NOT NULL DEFAULT '0' COMMENT '攻城伤害加成（百分比）' AFTER `buff_move`;
ALTER TABLE `cross_guild` ADD `buff_cityattack_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '攻城伤害加成技能' AFTER `buff_cityattack`;



ALTER TABLE `cross_player_general` 
	ADD `cross_skill_id_1` int NOT NULL DEFAULT '0' COMMENT '' AFTER `stay_start_time`,
	ADD `cross_skill_lv_1` int NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_id_1`,
	ADD `cross_skill_v1_1` float NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_lv_1`,
	ADD `cross_skill_v2_1` float NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_v1_1`,
	ADD `cross_skill_id_2` int NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_v2_1`,
	ADD `cross_skill_lv_2` int NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_id_2`,
	ADD `cross_skill_v1_2` float NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_lv_2`,
	ADD `cross_skill_v2_2` float NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_v1_2`,
	ADD `cross_skill_id_3` int NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_v2_2`,
	ADD `cross_skill_lv_3` int NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_id_3`,
	ADD `cross_skill_v1_3` float NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_lv_3`,
	ADD `cross_skill_v2_3` float NOT NULL DEFAULT '0' COMMENT '' AFTER `cross_skill_v1_3`;

CREATE TABLE `cross_player_masterskill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `battle_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL DEFAULT '0',
  `lv` int(11) NOT NULL DEFAULT '1',
  `all_times` int(11) NOT NULL DEFAULT '0' COMMENT '总次数',
  `rest_times` int(11) NOT NULL DEFAULT '0' COMMENT '剩余次数',
  `active` int(11) NOT NULL DEFAULT '0' COMMENT '激活中',
  `v1` float NOT NULL DEFAULT '0',
  `v2` float NOT NULL DEFAULT '0',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rowversion` char(13) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `battle_id` (`battle_id`,`player_id`,`skill_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
