ALTER TABLE `cross_player_masterskill` ADD `general_id` int NOT NULL DEFAULT 0 COMMENT '' AFTER `player_id`;

ALTER TABLE `cross_player_masterskill` DROP INDEX `battle_id`, ADD UNIQUE `battle_id` (`battle_id`, `player_id`, `general_id`, `skill_id`) USING BTREE;

ALTER TABLE `cross_guild` CHANGE `buff_move_ids` `buff_move_ids` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '[]' COMMENT '行军加成技能';
ALTER TABLE `cross_guild` CHANGE `buff_cityattack_ids` `buff_cityattack_ids` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '[]' COMMENT '攻城伤害加成技能';
ALTER TABLE `cross_guild` ADD `buff_buildattack` float NOT NULL DEFAULT '0' COMMENT '器械伤害加成（百分比）' AFTER `buff_cityattack_ids`;
ALTER TABLE `cross_guild` ADD `buff_buildattack_ids` varchar(255) NOT NULL DEFAULT '[]' COMMENT '器械伤害加成技能' AFTER `buff_buildattack`;
ALTER TABLE `cross_guild` ADD `buff_relocation` float NOT NULL DEFAULT '0' COMMENT '迁城cd加成' AFTER `buff_buildattack_ids`;
ALTER TABLE `cross_guild` ADD `buff_relocation_ids` varchar(255) NOT NULL DEFAULT '[]' COMMENT '迁城cd加成技能' AFTER `buff_relocation`;
ALTER TABLE `cross_guild` ADD `buff_enemyreturn` float NOT NULL DEFAULT '0' COMMENT '对手攻击本方城门的部队返回时间增加' AFTER `buff_relocation_ids`;
ALTER TABLE `cross_guild` ADD `buff_enemyreturn_ids` varchar(255) NOT NULL DEFAULT '[]' COMMENT '对手攻击本方城门的部队返回时间增加技能' AFTER `buff_enemyreturn`;

ALTER TABLE `cross_map` ADD `attack_cd` int NOT NULL DEFAULT 0 COMMENT '攻击间隔（秒）' AFTER `attack_time`;
ALTER TABLE `cross_map` ADD `attack_times` int NOT NULL DEFAULT 0 COMMENT '攻击次数' AFTER `attack_cd`;


ALTER TABLE `cross_player` ADD `skill_first_recover` int NOT NULL DEFAULT 0 COMMENT '不屈之力' AFTER `dead_times`;
