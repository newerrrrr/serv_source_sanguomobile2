drop table king;
CREATE TABLE `king` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL,
	`player_id` INT (11) NOT NULL,
	`round` INT (11) NOT NULL DEFAULT '0' COMMENT '当前npc进攻轮数',
	`status` INT (11) NOT NULL DEFAULT '0' COMMENT '状态：0.准备，1.进行，2.开始结算，3.结算结束，开始投票，4.投票结束',
	`start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
	`end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '联盟总表';

ALTER TABLE `player` ADD `levelup_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '升级时间' AFTER `level`;

-- 走马灯
-- 添加字段 data
ALTER TABLE `round_message` ADD COLUMN `data` TEXT NULL COMMENT 'type大于5以上的组装数据' AFTER `status`;
-- 修改type注释
ALTER TABLE `round_message` CHANGE `type` `type` INT(2) DEFAULT 1 NULL COMMENT '0:系统消息 1:战斗相关 2:招募武将 3:击杀boss 4:紫色品质装备品质 6:国王战官职任命';

-- 联盟比赛类型描述修正
ALTER TABLE `alliance_match_list` MODIFY COLUMN `type`  int(11) NOT NULL COMMENT '1 捐献 2 和氏璧 3 黄巾起义 4 据点战' AFTER `id`;