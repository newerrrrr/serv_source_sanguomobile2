/*Table structure for table `pk` */

DROP TABLE IF EXISTS `pk`;

CREATE TABLE `pk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '0:NPC 1：玩家',
  `score` int(11) DEFAULT '0' COMMENT '本场积分',
  `win_player_id` int(5) DEFAULT '0' COMMENT '胜方的玩家id',
  `server_id` int(5) NOT NULL COMMENT '攻击方-玩家服务器id',
  `player_id` int(11) NOT NULL COMMENT '攻击方-玩家id',
  `guild_name` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '攻击方-联盟name',
  `guild_short_name` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '攻击方-联盟短名称',
  `avatar_id` int(5) DEFAULT '0' COMMENT '攻击方-头像',
  `level` int(5) DEFAULT '0' COMMENT '攻击方-等级',
  `nick` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '攻击方-昵称',
  `target_server_id` int(5) NOT NULL COMMENT '防守方-玩家服务器id',
  `target_player_id` int(11) NOT NULL DEFAULT '0' COMMENT '防守方-目标玩家id',
  `target_avatar_id` int(5) DEFAULT '0' COMMENT '防守方-头像',
  `target_level` int(5) DEFAULT '0' COMMENT '防守方-等级',
  `target_nick` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '防守方-昵称',
  `target_guild_name` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '防守方-联盟name',
  `target_guild_short_name` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '防守方-联盟短名称',
  `pk_result` longtext COLLATE utf8_unicode_ci COMMENT '武斗结果-战报-回放用',
  `start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '匹配创建时间',
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '战斗结束时间',
  `revenge_status` int(11) DEFAULT '0' COMMENT '0:未复仇 1：已复仇 2:复仇武斗',
  `status` int(5) DEFAULT '0' COMMENT '0: 匹配武斗开始 1：武斗结束',
  `target_score` int(11) DEFAULT '0' COMMENT '本场',
  `total_score` int(11) DEFAULT '0' COMMENT '比赛前总积分',
  `target_total_score` int(11) DEFAULT '0' COMMENT '目标方比赛前总积分',
  `general_info` text COLLATE utf8_unicode_ci COMMENT '己方武将信息',
  `target_general_info` text COLLATE utf8_unicode_ci COMMENT '目标方武将信息',
  `duel_rank_id` int(11) DEFAULT '1' COMMENT 'a的',
  `target_duel_rank_id` int(11) DEFAULT '1' COMMENT 'b的',
  `qa_log` text COLLATE utf8_unicode_ci COMMENT 'qa下log',
  PRIMARY KEY (`id`),
  KEY `sp` (`server_id`,`player_id`),
  KEY `tsp` (`target_server_id`,`target_player_id`),
  KEY `sps` (`server_id`,`player_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='武斗表-含武斗,布阵，回放记录等';

/*Table structure for table `pk_general_statistic` */

DROP TABLE IF EXISTS `pk_general_statistic`;

CREATE TABLE `pk_general_statistic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `general_id` int(11) NOT NULL,
  `win_times` int(11) DEFAULT '0' COMMENT '胜场次数',
  `lose_times` int(11) DEFAULT '0' COMMENT '失败次数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='武将统计';

/*Table structure for table `pk_group` */

DROP TABLE IF EXISTS `pk_group`;

CREATE TABLE `pk_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '名称',
  `server_ids` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '服务器id，分号分割',
  `current_round_start_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '赛季开始时间',
  `next_round_start_time` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '赛季结束时间',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_log` text COLLATE utf8_unicode_ci COMMENT '后台更新日志',
  `exec_server_ids` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '已经跑过脚本的服务器id',
  `lock_status` int(2) DEFAULT '0' COMMENT '1:锁住 0:未锁',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='武斗配置组表';

/*Table structure for table `pk_player_general` */

DROP TABLE IF EXISTS `pk_player_general`;

CREATE TABLE `pk_player_general` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(5) NOT NULL COMMENT '服务器id',
  `player_id` int(11) NOT NULL COMMENT '玩家id',
  `general_id` int(11) NOT NULL COMMENT '武将id',
  `win_times` int(11) DEFAULT '0' COMMENT '武将获胜场数',
  `lose_times` int(11) DEFAULT '0' COMMENT '武将失败次数',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `spg` (`server_id`,`player_id`,`general_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='武斗-玩家武将表';

/*Table structure for table `pk_player_info` */

DROP TABLE IF EXISTS `pk_player_info`;

CREATE TABLE `pk_player_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(5) NOT NULL COMMENT '服务器id',
  `player_id` int(11) NOT NULL COMMENT '玩家id',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `win_times` int(11) DEFAULT '0' COMMENT '玩家获胜场数',
  `continue_win_times` int(11) DEFAULT '0' COMMENT '玩家最高连胜场数',
  `duel_rank_id` int(11) NOT NULL DEFAULT '1' COMMENT 'duel_rank表的id字段',
  `duel_rank` int(6) NOT NULL DEFAULT '1' COMMENT 'duel_rank表rank字段',
  `score` int(11) DEFAULT '0' COMMENT '积分',
  `free_search_times_per_day` int(11) NOT NULL COMMENT '每日武斗免费搜索次数',
  `general_1` int(11) DEFAULT '0',
  `general_2` int(11) DEFAULT '0',
  `general_3` int(11) DEFAULT '0',
  `searched_player_ids` varchar(5000) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '已经搜索过的玩家',
  `pk_with_npc_times` int(5) DEFAULT '0' COMMENT '开始与npc匹配的次数',
  `lock_status` int(2) DEFAULT '0' COMMENT '0:无锁 1：被锁',
  `current_day_match_times` int(11) DEFAULT '0' COMMENT '今日匹配次数',
  `current_day_gain_id` int(11) DEFAULT '0' COMMENT 'duel_times_bonus表id',
  `current_day_buy_times` int(11) DEFAULT '0' COMMENT '当天几次购买',
  `daily_score` int(11) DEFAULT '-1' COMMENT '结算积分，用于当日结算奖励',
  `daily_award_status` int(2) DEFAULT '0' COMMENT '0:未领 1：已领 2：脚本运行期间',
  `gain_daily_award_date` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '领奖日期',
  `award_exec_date` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '脚本结算时间',
  `daily_reset_exec_date` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT '每日重置脚本执行时间',
  `prev_duel_rank_id` int(11) NOT NULL DEFAULT '1' COMMENT '变化前的duel_rank_id',
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`),
  KEY `server_id` (`server_id`),
  KEY `sp` (`server_id`,`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `pk_rank` */

DROP TABLE IF EXISTS `pk_rank`;

CREATE TABLE `pk_rank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(5) DEFAULT NULL,
  `player_id` int(11) DEFAULT NULL,
  `nick` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `avatar_id` int(11) DEFAULT NULL,
  `guild_name` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `guild_short_name` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duel_rank` int(11) DEFAULT '1' COMMENT 'duel_rank表的rank字段',
  `score` int(11) DEFAULT NULL,
  `general_data` text COLLATE utf8_unicode_ci COMMENT '该玩家胜率最高的三个武将相关数据',
  `pos` int(11) DEFAULT '1' COMMENT '名次',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `pk_group_id` int(11) DEFAULT '0' COMMENT 'pk_group表id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='排行榜数据';
