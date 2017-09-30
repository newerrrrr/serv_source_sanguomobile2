CREATE TABLE `city_battle_science` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `camp_id` int(11) NOT NULL COMMENT '阵营id',
  `science_type` int(11) NOT NULL,
  `science_level` int(11) NOT NULL,
  `science_exp` int(11) NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rowversion` char(13) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guild_id` (`camp_id`,`science_type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;








CREATE TABLE `city_battle_guild_mission` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,	
	`guild_id` INT (11) NOT NULL COMMENT '联盟id',
	`camp_id` INT (11) NOT NULL COMMENT '阵营id',
	`mission_id` INT (11) NOT NULL COMMENT '联盟任务id',
	`type` INT (11) NOT NULL COMMENT '任务类型',
	`num_value` INT (11) NOT NULL COMMENT '目标数量当type是3表示城池id',
	`count` INT (11) NOT NULL COMMENT '完成数量',
        `status` TINYINT (1) NOT NULL COMMENT '完成状态1未完成;2完成;3无效;4过期废弃数据',
	`camp_mark` TINYINT (1) NOT NULL DEFAULT 0 COMMENT '换阵营标记为1',
	`start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '任务创建时间',
	`finish_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '完成时间',	
	PRIMARY KEY (`id`)	
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;


CREATE TABLE `guild_mission_award` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,	
	`server_id` INT (11) NOT NULL COMMENT 'serverid',
	`guild_id` INT (11) NOT NULL COMMENT '联盟id',
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`award_id` INT (11) NOT NULL COMMENT '奖励id',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '发放时间',	
	PRIMARY KEY (`id`)	
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;


CREATE TABLE `city_battle_shop` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,	
	`shop_id` INT (11) NOT NULL COMMENT '商品id',
	`total` INT (11) NOT NULL COMMENT '总数,逐步扣除',		
	PRIMARY KEY (`id`)	
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;




