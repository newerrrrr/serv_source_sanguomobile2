CREATE TABLE `activity_commodity_extra` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`open_time` INT (11) DEFAULT NULL COMMENT '充值项开启时间\nUnix时间戳',
	`close_time` INT (11) DEFAULT NULL COMMENT '充值项关闭时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `activity_configure` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`activity_name` INT (11) NOT NULL,
	`activity_id` INT (11) NOT NULL,
	`activity_para` text NOT NULL,
	`show_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`status` INT (11) NOT NULL DEFAULT '1' COMMENT '0:未开启，1:开启',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `activity_extra` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`open_date` INT (11) DEFAULT NULL,
	`close_date` INT (11) DEFAULT NULL,
	`memo` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

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

CREATE TABLE `admin_auth` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`auth` text COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `admin_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`type` VARCHAR (50) COLLATE utf8_unicode_ci NOT NULL,
	`memo` text COLLATE utf8_unicode_ci,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `admin_user` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`password` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`pwd_status` INT (1) NOT NULL DEFAULT '0' COMMENT '0-未修改初始密码，1-已修改',
	`auth` INT (11) NOT NULL,
	`status` INT (11) NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `alliance_match_list` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`type` INT (11) NOT NULL COMMENT '1 捐献 2 和氏璧 3 黄巾起义 4 据点战',
	`start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`round` INT (11) NOT NULL COMMENT '轮次',
	`calc_status` INT (11) NOT NULL DEFAULT '0',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `chat_black_list` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`black_player_id` INT (11) NOT NULL COMMENT '黑名单',
	`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '聊天黑名单';

CREATE TABLE `configure` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`key` VARCHAR (256) NOT NULL,
	`value` INT (11) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `id` (`id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `guild` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`founder` INT (11) DEFAULT '0' COMMENT '联盟创始人',
	`leader_player_id` INT (11) NOT NULL DEFAULT '0' COMMENT '联盟老大',
	`name` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '联盟名称',
	`short_name` VARCHAR (50) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '短名称',
	`icon_id` INT (11) DEFAULT '0' COMMENT '联盟图标',
	`num` INT (11) DEFAULT '0' COMMENT '联盟现有人数',
	`max_num` INT (11) DEFAULT '0' COMMENT '联盟容量',
	`need_check` SMALLINT (2) DEFAULT '0' COMMENT '入盟确认：0：不需要 1：需要',
	`guild_power` INT (11) DEFAULT '0' COMMENT '联盟战力，脚本每天定时跑',
	`desc` VARCHAR (300) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '联盟宣言',
	`notice` VARCHAR (300) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '联盟公告',
	`condition_fuya_level` INT (11) DEFAULT '0' COMMENT '入盟条件：府衙等级',
	`condition_player_power` INT (11) DEFAULT '0' COMMENT '入盟条件：主公战力',
	`coin` INT (11) NOT NULL DEFAULT '0' COMMENT '商店货币',
	`kill_soldier_num` INT (11) DEFAULT '0' COMMENT '公会人员杀人数',
	`mission_score` INT (11) NOT NULL DEFAULT '0' COMMENT '联盟任务积分',
	`science_type` INT (11) NOT NULL DEFAULT '11' COMMENT '优先捐献科技类型',
	`donate_date` date NOT NULL DEFAULT '0000-00-00' COMMENT '最后捐献日期',
	`donate_counter` INT (11) NOT NULL DEFAULT '0' COMMENT '当日捐献人数',
	`invite_end_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '联盟邀请截止时间',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '联盟总表';

CREATE TABLE `guild_battle_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`type` INT (11) NOT NULL,
	`attack_guild_id` INT (11) NOT NULL COMMENT '攻击方公会id',
	`attack_player_id` INT (11) NOT NULL COMMENT '攻击方玩家id',
	`attack_x` INT (11) NOT NULL DEFAULT '0' COMMENT '攻击方坐标',
	`attack_y` INT (11) NOT NULL DEFAULT '0' COMMENT '攻击方坐标',
	`defend_guild_id` INT (11) NOT NULL COMMENT '防御方公会id',
	`defend_player_id` INT (11) NOT NULL COMMENT '防御方玩家id',
	`defend_x` INT (11) NOT NULL DEFAULT '0' COMMENT '防御方坐标',
	`defend_y` INT (11) NOT NULL DEFAULT '0' COMMENT '防御方坐标',
	`is_win` INT (11) NOT NULL COMMENT '是否胜利',
	`a_list` VARCHAR (512) NOT NULL COMMENT '攻击方列表',
	`a_lost_power` INT (11) NOT NULL COMMENT '攻击方损失战力',
	`d_list` VARCHAR (512) NOT NULL COMMENT '防御方列表',
	`d_lost_power` INT (11) NOT NULL COMMENT '防御方损失战力',
	`rob_resouce` VARCHAR (512) NOT NULL COMMENT '抢夺资源列表',
	`detail` BLOB COMMENT '战报详情',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '战斗发生时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `guild_board` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL COMMENT '公会id',
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`order_id` INT (11) NOT NULL COMMENT '显示顺序',
	`title` VARCHAR (256) NOT NULL COMMENT '题目',
	`content` text NOT NULL COMMENT '内容',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `guild_buff` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL,
	`buff_id` INT (11) NOT NULL,
	`buff_name` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`buff_num` INT (11) NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `guild_id` (`guild_id`, `buff_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `guild_gift_distribution_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT COMMENT '主键',
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`guild_id` INT (11) NOT NULL COMMENT '公会id',
	`gift_id` INT (11) NOT NULL COMMENT '礼物',
	`round` INT (11) NOT NULL COMMENT '轮数',
	`type` INT (11) NOT NULL COMMENT '类型',
	`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COMMENT = '玩家获取公会礼包记录';

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

CREATE TABLE `guild_king_point` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL,
	`point` INT (11) NOT NULL DEFAULT '0',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `guild_id` (`guild_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '联盟总表';

CREATE TABLE `guild_mission_rank` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`round` INT (11) NOT NULL,
	`type` INT (11) NOT NULL COMMENT '1-捐献 2-和氏壁',
	`rank` INT (11) NOT NULL DEFAULT '0',
	`guild_id` INT (11) NOT NULL,
	`name` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL COMMENT '作者:\nDROP ID\n',
	`avatar` INT (11) NOT NULL,
	`score` BIGINT (11) NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `type` (`round`, `type`, `rank`) USING BTREE
) ENGINE = INNODB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;


CREATE TABLE `guild_rank_name` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL COMMENT '公会id',
	`rank` INT (11) NOT NULL COMMENT '阶级1-5',
	`name` VARCHAR (256) COLLATE utf8_unicode_ci NOT NULL COMMENT '称谓',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '公会阶级称谓';

CREATE TABLE `guild_science` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) NOT NULL COMMENT '玩家id',
	`science_type` INT (11) NOT NULL,
	`science_level` INT (11) NOT NULL,
	`science_exp` INT (11) NOT NULL,
	`science_level_type` INT (11) NOT NULL COMMENT '层级',
	`finish_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`status` INT (11) NOT NULL COMMENT '0:普通；1:经验满；2:升级中',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `guild_id` (`guild_id`, `science_type`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `guild_shop` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`guild_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`item_id` INT (11) DEFAULT NULL,
	`num` INT (11) DEFAULT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `guild_id` (`guild_id`, `item_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

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
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = COMPACT;

CREATE TABLE `guild_warehouse` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`guild_id` INT (11) NOT NULL COMMENT '公会id',
	`gold_amount` INT (11) NOT NULL COMMENT '黄金存量',
	`food_amount` INT (11) NOT NULL COMMENT '粮食存量',
	`wood_amount` INT (11) NOT NULL COMMENT '木头存量',
	`stone_amount` INT (11) NOT NULL COMMENT '石头存量',
	`iron_amount` INT (11) NOT NULL COMMENT '铁存量',
	`last_store_time` INT (11) NOT NULL COMMENT '上一次储存时间',
	`last_day_store_amount` INT (11) NOT NULL COMMENT '最后一天已存资源量',
	PRIMARY KEY (`id`)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

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

CREATE TABLE `king_player_reward` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`reward_type` INT (11) NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `king_town` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`type` INT (11) NOT NULL COMMENT '1.小，2.大',
	`guild_id` INT (11) NOT NULL,
	`status` INT (11) NOT NULL DEFAULT '0',
	`x` INT (11) NOT NULL,
	`y` INT (11) NOT NULL,
	`npc_id` INT (11) NOT NULL DEFAULT '0' COMMENT '驻守NPCID',
	`npc_num` INT (11) NOT NULL DEFAULT '0' COMMENT '驻守NPC数量',
	`point_start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '联盟总表';

CREATE TABLE `map` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`x` INT (11) DEFAULT '0' COMMENT '坐标x点',
	`y` INT (11) DEFAULT '0' COMMENT '坐标y点',
	`block_id` INT (11) DEFAULT '0' COMMENT '单元格id floor(x/12)+floor(y/12)*103',
	`map_element_id` INT (11) DEFAULT '0' COMMENT '地图明细id',
	`map_element_origin_id` INT (11) DEFAULT '0' COMMENT 'map_element.origin_id',
	`map_element_level` INT (11) DEFAULT '1' COMMENT 'map element的等级',
	`topography` INT (11) NOT NULL DEFAULT '0' COMMENT '0 正常 1 山 2 水',
	`guild_id` INT (11) DEFAULT '0' COMMENT '帮会id',
	`player_id` INT (11) DEFAULT '0' COMMENT '玩家id',
	`resource` INT (11) DEFAULT '0',
	`durability` INT (11) NOT NULL DEFAULT '0' COMMENT '建筑耐久度',
	`max_durability` INT (11) NOT NULL DEFAULT '0' COMMENT '建筑最大耐久度',
	`status` INT (11) NOT NULL DEFAULT '1' COMMENT '0 建造中 1 正常',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`build_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'durability增加时记录的时间',
	`rowversion` INT (11) DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE KEY `xy` (`x`, `y`) USING BTREE,
	KEY `guild_id` (`guild_id`) USING BTREE,
	KEY `player_id` (`player_id`) USING BTREE,
	KEY `block` (`block_id`) USING BTREE,
	KEY `map_element_origin_id` (`map_element_origin_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`user_code` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '玩家唯一码',
	`server_id` INT (5) NOT NULL DEFAULT '1' COMMENT '服务器id',
	`uuid` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL COMMENT '玩家唯一标志码',
	`nick` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL COMMENT '昵称',
	`avatar_id` INT (5) NOT NULL COMMENT '头像id',
	`level` INT (11) NOT NULL COMMENT '玩家等级',
	`levelup_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '升级时间',
	`current_exp` INT (11) NOT NULL COMMENT '当前经验值',
	`next_exp` INT (11) NOT NULL COMMENT '下一级经验值',
	`talent_num_total` INT (5) NOT NULL COMMENT '总天赋点',
	`talent_num_remain` INT (5) NOT NULL COMMENT '剩余天赋点',
	`general_num_total` INT (5) NOT NULL COMMENT '总可携带武将数',
	`general_num_remain` INT (5) NOT NULL COMMENT '剩余可携带武将数',
	`army_num` INT (5) DEFAULT '1' COMMENT '可调遣军团数量',
	`army_general_num` INT (11) DEFAULT '0' COMMENT '最大军团武将数',
	`queue_num` INT (11) DEFAULT '1' COMMENT '当前最大队列值',
	`move` INT (11) NOT NULL DEFAULT '0' COMMENT '玩家当前行动力',
	`move_max` INT (11) NOT NULL DEFAULT '0' COMMENT '玩家当前行动力最大值',
	`wall_durability` INT (11) DEFAULT '0' COMMENT '城墙耐久度',
	`wall_durability_max` INT (11) DEFAULT '0' COMMENT '城墙最大耐久度',
	`wall_intact` INT (11) NOT NULL DEFAULT '1' COMMENT '城墙是否完整',
	`durability_last_update_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '燃烧状态开始时间',
	`last_repair_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '上次修理时间',
	`fire_end_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '燃烧状态结束时间',
	`gold` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '玩家黄金',
	`food` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '玩家粮食',
	`wood` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '玩家木头',
	`stone` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '玩家石头',
	`iron` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '玩家铁矿',
	`silver` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '白银',
	`point` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '锦囊',
	`jiangyin` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '将印',
	`xuantie` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '玄铁',
	`food_out` BIGINT (20) DEFAULT '0' COMMENT '粮损',
	`move_in_time` BIGINT (20) DEFAULT '0' COMMENT '行动力回复时间 20分钟一点 0:不回复',
	`food_out_time` BIGINT (20) DEFAULT '0' COMMENT '粮耗时间 0:不耗',
	`rmb_gem` INT (11) DEFAULT '0' COMMENT 'RMB元宝',
	`gift_gem` INT (11) DEFAULT '0' COMMENT '非RMB元宝',
	`create_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '首次登陆游戏时间',
	`login_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '登陆游戏时间',
	`study_pay_num` INT (11) DEFAULT '0' COMMENT '学习付费栏位',
	`guild_id` INT (11) DEFAULT '0' COMMENT '联盟id，没有则为0',
	`guild_coin` INT (11) DEFAULT '0',
	`feats` INT (11) NOT NULL DEFAULT '0' COMMENT '功勋',
	`master_power` INT (11) DEFAULT '0' COMMENT '主公战斗力',
	`general_power` INT (11) DEFAULT '0' COMMENT '主公战斗力',
	`army_power` INT (11) DEFAULT '0' COMMENT '部队战斗力',
	`build_power` INT (11) DEFAULT '0' COMMENT '建筑战斗力',
	`science_power` INT (11) DEFAULT '0' COMMENT '科技战斗力',
	`trap_power` INT (11) DEFAULT '0' COMMENT '陷阱战斗力',
	`power` INT (11) DEFAULT '0' COMMENT '总战斗力',
	`step` INT (11) DEFAULT '0' COMMENT '新手引导步骤',
	`step_set` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '新手引导-集合',
	`job` INT (11) DEFAULT '0' COMMENT '任命',
	`appointment_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '任命使用时间（国王only）',
	`monster_lv` INT (11) DEFAULT '0' COMMENT '打怪等级',
	`monster_kill_counter` INT (11) NOT NULL DEFAULT '0' COMMENT '击杀野怪次数（包括boss）',
	`avoid_battle` INT (11) DEFAULT '0',
	`avoid_battle_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
	`fresh_avoid_battle_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
	`is_in_cross` INT (11) NOT NULL DEFAULT '0' COMMENT '是否在跨服战中',
	`kill_soldier_num` INT (11) NOT NULL DEFAULT '0' COMMENT '玩家杀死士兵数量',
	`vip_level` INT (11) DEFAULT '1',
	`vip_exp` INT (11) DEFAULT '0',
	`sign_date` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '签到日期',
	`sign_times` INT (11) DEFAULT '0' COMMENT '签到次数',
	`prev_x` INT (11) DEFAULT '0' COMMENT '前一次的位置x',
	`prev_y` INT (11) DEFAULT '0' COMMENT '前一次的位置y',
	`hsb` INT (11) NOT NULL DEFAULT '0' COMMENT '和氏璧',
	`has_corrected` INT (11) NOT NULL DEFAULT '0' COMMENT '是否升级过士兵和资源建筑',
	`valid_code` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '登录验证信息',
	`device_type` INT (11) NOT NULL DEFAULT '0' COMMENT '1.ios,2.android',
	`client_id` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端推送标识',
	`device_token` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'ios机器标识',
	`badge` INT (11) NOT NULL DEFAULT '0',
	`push_tag` SET ('1', '2', '3', '4', '5') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1,2,3,4,5' COMMENT '推送类型',
	`lang` VARCHAR (10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'zhtw' COMMENT '语言',
	`attack_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '最后攻击时间',
	PRIMARY KEY (`id`),
	KEY `nick1` (`nick`) USING BTREE,
	KEY `job` (`job`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

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

CREATE TABLE `player_activity_consume` (
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

CREATE TABLE `player_activity_sacrifice` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`activity_configure_id` INT (11) DEFAULT NULL,
	`times` INT (11) DEFAULT '0' COMMENT '抽奖次数',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_activity_sacrifice_player_id_index` (
		`player_id`,
		`activity_configure_id`
	)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COMMENT = '祭天活动';

CREATE TABLE `player_activity_wheel` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`activity_configure_id` INT (11) NOT NULL,
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

CREATE TABLE `player_army` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`position` INT (11) NOT NULL COMMENT '军团位置',
	`leader_general_id` INT (11) NOT NULL COMMENT '领队武将',
	`status` INT (11) NOT NULL COMMENT '状态：0-无；1-行军',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `position`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_army_unit` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`army_id` INT (11) NOT NULL COMMENT '军团编号',
	`unit` INT (11) NOT NULL COMMENT '编队序号',
	`general_id` INT (11) NOT NULL COMMENT '武将id',
	`soldier_id` INT (11) NOT NULL COMMENT '士兵id',
	`soldier_num` INT (11) NOT NULL COMMENT '士兵数量',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (
		`player_id`,
		`army_id`,
		`unit`
	) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_buff` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`avoid_battle` INT (11) DEFAULT '0' COMMENT '免战',
	`food_out_debuff` INT (11) DEFAULT '0' COMMENT '士兵粮食消耗减少',
	`anti_spy` INT (11) DEFAULT '0' COMMENT '反侦察',
	`pretend_skill` INT (11) DEFAULT '0' COMMENT '伪装术',
	`troop_max_plus` INT (11) DEFAULT '0' COMMENT '武将带兵数量提高',
	`build_speed` INT (11) DEFAULT '0' COMMENT '建筑建造速度增加',
	`science_research_speed` INT (11) DEFAULT '0' COMMENT '科技研究速度增加',
	`pitfall_amount_plus` INT (11) DEFAULT '0' COMMENT '陷阱上限增加',
	`help_num_plus` INT (11) DEFAULT '0' COMMENT '联盟帮助次数增加',
	`help_time_plus` INT (11) DEFAULT '0' COMMENT '联盟帮助减少时间增加',
	`help_legion` INT (11) DEFAULT '0' COMMENT '援兵军团数量增加',
	`silver_reduce` INT (11) DEFAULT '0' COMMENT '升级装备白银消耗减少',
	`decomposition_equipment_silver_plus` INT (11) DEFAULT '0' COMMENT '分解装备白银获得增加',
	`move_to_npc_speed` INT (11) DEFAULT '0' COMMENT '打怪行军速度增加',
	`march_speed` INT (11) DEFAULT '0' COMMENT '行军速度增加',
	`move_restore_speed` INT (11) DEFAULT '0' COMMENT '体力恢复加速',
	`hospital_amount_plus` INT (11) DEFAULT '0' COMMENT '伤兵上限增加',
	`pitfall_train_speed` INT (11) DEFAULT '0' COMMENT '陷阱制造速度提升',
	`train_troops_speed` INT (11) DEFAULT '0' COMMENT '部队训练速度提升',
	`cure_speed` INT (11) DEFAULT '0' COMMENT '伤兵恢复速度增加',
	`cure_cost_minus` INT (11) DEFAULT '0' COMMENT '伤兵恢复费用降低',
	`wall_defense_limit_plus` INT (11) DEFAULT '0' COMMENT '城墙耐久上限增加',
	`protect_gold_plus` INT (11) DEFAULT '0' COMMENT '仓库黄金保护容量',
	`protect_food_plus` INT (11) DEFAULT '0' COMMENT '仓库粮草保护容量',
	`protect_wood_plus` INT (11) DEFAULT '0' COMMENT '仓库木材保护容量',
	`protect_stone_plus` INT (11) DEFAULT '0' COMMENT '仓库石材保护容量',
	`protect_iron_plus` INT (11) DEFAULT '0' COMMENT '仓库铁材保护容量',
	`no_loss` INT (11) DEFAULT '0' COMMENT '受到攻击不会损失资源',
	`aggregation_legion` INT (11) DEFAULT '0' COMMENT '集结军团数增加',
	`training_infantry_num_plus` INT (11) DEFAULT '0' COMMENT '步兵训练数量提升',
	`training_cavalry_num_plus` INT (11) DEFAULT '0' COMMENT '骑兵训练数量提升',
	`training_archer_num_plus` INT (11) DEFAULT '0' COMMENT '弓兵训练数量提升',
	`training_siege_num_plus` INT (11) DEFAULT '0' COMMENT '车兵训练数量提升',
	`build_queue` INT (11) DEFAULT '0' COMMENT '建造队列',
	`wood_income` INT (11) DEFAULT '0' COMMENT '伐木场增产',
	`food_income` INT (11) DEFAULT '0' COMMENT '农田增产',
	`gold_income` INT (11) DEFAULT '0' COMMENT '金矿增产',
	`stone_income` INT (11) DEFAULT '0' COMMENT '石材增产',
	`iron_income` INT (11) DEFAULT '0' COMMENT '铁材增产',
	`wood_gathering_speed` INT (11) DEFAULT '0' COMMENT '世界采集木头速度提升',
	`food_gathering_speed` INT (11) DEFAULT '0' COMMENT '世界采集粮食速度提升',
	`gold_gathering_speed` INT (11) DEFAULT '0' COMMENT '世界采集黄金速度提升',
	`stone_gathering_speed` INT (11) DEFAULT '0' COMMENT '世界采集石材速度提升',
	`iron_gathering_speed` INT (11) DEFAULT '0' COMMENT '世界采集铁材速度提升',
	`infantry_carry_plus` INT (11) DEFAULT '0' COMMENT '步兵负重增加',
	`cavalry_carry_plus` INT (11) DEFAULT '0' COMMENT '骑兵负重增加',
	`archer_carry_plus` INT (11) DEFAULT '0' COMMENT '弓兵负重增加',
	`siege_carry_plus` INT (11) DEFAULT '0' COMMENT '车兵负重增加',
	`infantry_def_plus` INT (11) DEFAULT '0' COMMENT '步兵防御加成',
	`infantry_life_plus` INT (11) DEFAULT '0' COMMENT '步兵生命加成',
	`infantry_atk_plus` INT (11) DEFAULT '0' COMMENT '步兵攻击加成',
	`cavalry_def_plus` INT (11) DEFAULT '0' COMMENT '骑兵防御加成',
	`cavalry_life_plus` INT (11) DEFAULT '0' COMMENT '骑兵生命加成',
	`cavalry_atk_plus` INT (11) DEFAULT '0' COMMENT '骑兵攻击加成',
	`archer_def_plus` INT (11) DEFAULT '0' COMMENT '弓兵防御加成',
	`archer_life_plus` INT (11) DEFAULT '0' COMMENT '弓兵生命加成',
	`archer_atk_plus` INT (11) DEFAULT '0' COMMENT '弓兵攻击加成',
	`siege_def_plus` INT (11) DEFAULT '0' COMMENT '车兵防御加成',
	`siege_life_plus` INT (11) DEFAULT '0' COMMENT '车兵生命加成',
	`siege_atk_plus` INT (11) DEFAULT '0' COMMENT '车兵攻击加成',
	`citybattle_infantry_def_plus` INT (11) DEFAULT '0' COMMENT '步兵守城时防御增加',
	`citybattle_infantry_life_plus` INT (11) DEFAULT '0' COMMENT '步兵守城时血量增加',
	`citybattle_infantry_atk_plus` INT (11) DEFAULT '0' COMMENT '步兵守城时攻击增加',
	`citybattle_cavalry_def_plus` INT (11) DEFAULT '0' COMMENT '骑兵守城时防御增加',
	`citybattle_cavalry_life_plus` INT (11) DEFAULT '0' COMMENT '骑兵守城时血量增加',
	`citybattle_cavalry_atk_plus` INT (11) DEFAULT '0' COMMENT '骑兵守城时攻击增加',
	`citybattle_archer_def_plus` INT (11) DEFAULT '0' COMMENT '弓兵守城时防御增加',
	`citybattle_archer_life_plus` INT (11) DEFAULT '0' COMMENT '弓兵守城时血量增加',
	`citybattle_archer_atk_plus` INT (11) DEFAULT '0' COMMENT '弓兵守城时攻击增加',
	`citybattle_siege_def_plus` INT (11) DEFAULT '0' COMMENT '车兵守城时防御增加',
	`citybattle_siege_life_plus` INT (11) DEFAULT '0' COMMENT '车兵守城时血量增加',
	`citybattle_siege_atk_plus` INT (11) DEFAULT '0' COMMENT '车兵守城时攻击增加',
	`deputy_per_corp` INT (11) DEFAULT '0' COMMENT '每个军团中副将数量增加',
	`corps_in_control` INT (11) DEFAULT '0' COMMENT '玩家可操控军团数量增加',
	`build_cost_reduce` INT (11) DEFAULT '0' COMMENT '建筑建造消耗减少',
	`research_cost_reduce` INT (11) DEFAULT '0' COMMENT '科技研究消耗减少',
	`move_limit_plus_exact_value` INT (11) DEFAULT '0' COMMENT '体力上限增加',
	`tower_atk_plus` INT (11) DEFAULT '0' COMMENT '箭塔攻击力增加',
	`pitfall_activated_probability` INT (11) DEFAULT '0' COMMENT '陷阱触发概率增加',
	`arrow_atk_reduce` INT (11) DEFAULT '0' COMMENT '攻城时敌人弓箭陷阱攻击降低',
	`wood_atk_reduce` INT (11) DEFAULT '0' COMMENT '攻城时敌人木头陷阱攻击降低',
	`rock_atk_reduce` INT (11) DEFAULT '0' COMMENT '攻城时敌人落石陷阱攻击降低',
	`help_infantry_def_plus` INT (11) DEFAULT '0' COMMENT '援兵的步兵防御力增加',
	`help_cavalry_def_plus` INT (11) DEFAULT '0' COMMENT '援兵的骑兵防御力增加',
	`help_archer_def_plus` INT (11) DEFAULT '0' COMMENT '援兵的弓兵防御力增加',
	`help_siege_def_plus` INT (11) DEFAULT '0' COMMENT '援兵的车兵防御力增加',
	`instant_building` INT (11) DEFAULT '0' COMMENT '立即建造加速',
	`positive_battle_dead_trans_wounded` INT (11) DEFAULT '0' COMMENT '被动攻击造成的己方阵亡士兵中的一定比例转换为伤兵代替死亡',
	`fieldbattle_infantry_atk_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时步兵攻击力增加',
	`fieldbattle_infantry_def_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时步兵防御力增加',
	`fieldbattle_infantry_life_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时步兵生命力增加',
	`fieldbattle_cavalry_atk_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时骑兵攻击力增加',
	`fieldbattle_cavalry_def_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时骑兵防御力增加',
	`fieldbattle_cavalry_life_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时骑兵生命力增加',
	`fieldbattle_archer_atk_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时弓兵攻击力增加',
	`fieldbattle_archer_def_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时弓兵防御力增加',
	`fieldbattle_archer_life_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时弓兵生命力增加',
	`fieldbattle_siege_atk_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时车兵攻击力增加',
	`fieldbattle_siege_def_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时车兵防御力增加',
	`fieldbattle_siege_life_plus` INT (11) DEFAULT '0' COMMENT '野外战斗时车兵生命力增加',
	`instant_get_resource` INT (11) DEFAULT '0' COMMENT '一次性获得X小时产量的所有资源',
	`all_troops_back_soon` INT (11) DEFAULT '0' COMMENT '所有外出部队在X秒内召回自己主城',
	`pitfall_atk_plus` INT (11) DEFAULT '0' COMMENT '陷阱攻击力增加',
	`common_timer_reduce` INT (11) DEFAULT '0' COMMENT '通用加速',
	`build_timer_reduce` INT (11) DEFAULT '0' COMMENT '建筑加速',
	`train_timer_reduce` INT (11) DEFAULT '0' COMMENT '训练加速',
	`cure_timer_reduce` INT (11) DEFAULT '0' COMMENT '医疗加速',
	`research_timer_reduce` INT (11) DEFAULT '0' COMMENT '研究加速',
	`march_back` INT (11) DEFAULT '0' COMMENT '初级行军返回',
	`superior_march_back` INT (11) DEFAULT '0' COMMENT '高级行军返回',
	`recruit_general_limit_plus` INT (11) DEFAULT '0' COMMENT '可招募武将上限增加',
	`alliance_member_num` INT (11) DEFAULT '0' COMMENT '联盟人数上限增加',
	`alliance_castle_num` INT (11) DEFAULT '0' COMMENT '可建造的联盟城堡数量增加',
	`aggregation_legion_march_speed` INT (11) DEFAULT '0' COMMENT '集结军团的行军速度增加',
	`alliance_daily_storage_plus` INT (11) DEFAULT '0' COMMENT '联盟仓库每日存储上限增加',
	`alliance_tower_num` INT (11) DEFAULT '0' COMMENT '联盟箭塔数量增加',
	`alliance_food_production_plus` INT (11) DEFAULT '0' COMMENT '联盟农田产量增加',
	`alliance_gold_production_plus` INT (11) DEFAULT '0' COMMENT '联盟金矿产量增加',
	`alliance_wood_production_plus` INT (11) DEFAULT '0' COMMENT '联盟伐木场产量增加',
	`alliance_stone_production_plus` INT (11) DEFAULT '0' COMMENT '联盟石料场产量增加',
	`alliance_iron_production_plus` INT (11) DEFAULT '0' COMMENT '联盟铁矿场产量增加',
	`pitfall_log_activated_probability` INT (11) DEFAULT '0' COMMENT '滚木类陷阱触发概率增加',
	`pitfall_rock_activated_probability` INT (11) DEFAULT '0' COMMENT '落石类陷阱触发概率增加',
	`pitfall_arrow_activated_probability` INT (11) DEFAULT '0' COMMENT '箭矢类陷阱触发概率增加',
	`troop_max_plus_percent` INT (11) DEFAULT '0' COMMENT '武将带兵数量提高',
	`army_queue_num` INT (11) DEFAULT '0' COMMENT '队列数增加',
	`moving_speed` INT (11) DEFAULT '0' COMMENT '采集行军速度',
	`start_positive_battle_dead_trans_wounded` INT (11) DEFAULT '0' COMMENT '己方主动攻击的阵亡士兵转化为伤兵代替死亡',
	`vip_active` INT (11) DEFAULT '0' COMMENT 'vip激活',
	`protect_plus` INT (11) DEFAULT '0' COMMENT '仓库所有资源保护容量',
	`infantry_atk_reduce` INT (11) DEFAULT '0' COMMENT '敌军步兵攻击降低',
	`cavalry_atk_reduce` INT (11) DEFAULT '0' COMMENT '敌军骑兵攻击降低',
	`archer_atk_reduce` INT (11) DEFAULT '0' COMMENT '敌军弓兵攻击降低',
	`siege_atk_reduce` INT (11) DEFAULT '0' COMMENT '敌军车兵攻击降低',
	`infantry_life_reduce` INT (11) DEFAULT '0' COMMENT '士兵生命减少',
	`cavalry_life_reduce` INT (11) DEFAULT '0' COMMENT '骑兵生命减少',
	`archer_life_reduce` INT (11) DEFAULT '0' COMMENT '弓兵生命减少',
	`siege_life_reduce` INT (11) DEFAULT '0' COMMENT '投石车生命减少',
	`infantry_def_reduce` INT (11) DEFAULT '0' COMMENT '步兵防御减少',
	`cavalry_def_reduce` INT (11) DEFAULT '0' COMMENT '骑兵防御减少',
	`archer_def_reduce` INT (11) DEFAULT '0' COMMENT '弓兵防御减少',
	`siege_def_reduce` INT (11) DEFAULT '0' COMMENT '投石车防御减少',
	`noob_protection` INT (11) DEFAULT '0' COMMENT '顽强斗志',
	PRIMARY KEY (`id`),
	UNIQUE KEY `playerId` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_buff_temp` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`buff_temp_id` INT (11) NOT NULL,
	`position` INT (11) NOT NULL,
	`buff_id` INT (11) NOT NULL,
	`buff_name` VARCHAR (50) COLLATE utf8_unicode_ci NOT NULL,
	`buff_num` INT (11) NOT NULL,
	`expire_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '过期时间',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `buff_temp_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_build` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`build_id` INT (11) NOT NULL,
	`origin_build_id` INT (11) NOT NULL COMMENT '原始建筑id',
	`build_level` INT (11) NOT NULL DEFAULT '1' COMMENT '建筑等级',
	`general_id_1` INT (11) NOT NULL DEFAULT '0' COMMENT '驻守武将',
	`general_buff` INT (11) NOT NULL DEFAULT '0' COMMENT '武将提供加成buff[万分比]',
	`last_change_general_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '上次换武将时间',
	`position` INT (11) NOT NULL COMMENT '建造位置',
	`resource` INT (11) NOT NULL DEFAULT '0' COMMENT '资源数',
	`resource_in` INT (11) NOT NULL DEFAULT '0' COMMENT '自身时产',
	`storage_max` INT (11) NOT NULL DEFAULT '0' COMMENT '最大容量',
	`resource_start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '资源上次结算时间',
	`ex_addition` INT (11) NOT NULL DEFAULT '0' COMMENT '额外道具加成[万分比]',
	`ex_addition_end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '额外道具加成结束时间',
	`status` INT (11) NOT NULL DEFAULT '1' COMMENT '状态：1-正常状态 2-升级状态 3-使用状态',
	`need_help` INT (11) NOT NULL DEFAULT '0' COMMENT '是否需要请求帮助 1 是 0 否',
	`build_begin_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '升级开始时间',
	`build_finish_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '建筑升级完成时间',
	`build_push_id` INT (11) NOT NULL DEFAULT '0' COMMENT '推送队列编号',
	`work_begin_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '工作开始时间',
	`work_finish_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '工作状态结束时间',
	`work_content` VARCHAR (5000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '工作内容',
	`queue_index` INT (11) NOT NULL DEFAULT '0' COMMENT '升级时所属队列编号',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id_2` (`player_id`, `position`) USING BTREE,
	KEY `player_id` (`player_id`, `build_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_cdk` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`cdk` CHAR (12) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `cdk`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_common_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`type` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
	`memo` text COLLATE utf8_unicode_ci NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`) USING BTREE,
	KEY `type` (`type`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_consume_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`cost_id` INT (11) DEFAULT '0',
	`rmb_gem` INT (11) DEFAULT '0',
	`gift_gem` INT (11) DEFAULT '0',
	`memo` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_coordinate` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`type` INT (11) NOT NULL DEFAULT '0' COMMENT '分类',
	`x` INT (11) NOT NULL,
	`y` INT (11) NOT NULL,
	`name` VARCHAR (50) COLLATE utf8_unicode_ci NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `x`, `y`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_draw_card` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`chest_type_id` INT (11) NOT NULL COMMENT '奖品组id',
	`card_order` VARCHAR (512) NOT NULL COMMENT '卡牌顺序',
	`open_order` INT (11) NOT NULL COMMENT '翻开顺序',
	`status` INT (11) NOT NULL COMMENT '状态 1开 2关',
	`is_start` INT (11) NOT NULL DEFAULT '0' COMMENT '是否已开始 0 准备中 1 已开始 ',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `player_equip_master` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`equip_master_id` INT (11) NOT NULL COMMENT '主公装备id',
	`status` SMALLINT (2) DEFAULT '0' COMMENT '0:未装备 1:已装备',
	`position` INT (11) DEFAULT '-1' COMMENT '位置，from 1',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id_equipment_id` (
		`player_id`,
		`equip_master_id`
	) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '玩家装备表，玩家装备即玩家宝物';

CREATE TABLE `player_equip_master_skill` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_equip_master_id` INT (11) NOT NULL,
	`equip_skill_id` INT (11) DEFAULT NULL,
	`equip_skill_value` INT (11) DEFAULT NULL,
	`buff_id` INT (11) DEFAULT NULL COMMENT '装备buff id',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '玩家宝物对应的技能值';

CREATE TABLE `player_equipment` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`item_id` INT (11) NOT NULL COMMENT '军团位置',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`, `item_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_fail_save_reward` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`reward_id` INT (11) NOT NULL COMMENT '补偿id',
	`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `player_gem_log` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`rmb_gem` INT (11) DEFAULT '0',
	`gift_gem` INT (11) DEFAULT '0',
	`drop_id` INT (11) NOT NULL DEFAULT '0',
	`memo` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_general` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`general_id` INT (11) NOT NULL COMMENT '武将初始id',
	`exp` INT (11) NOT NULL COMMENT '经验',
	`lv` INT (11) NOT NULL COMMENT '等级',
	`star_lv` INT (11) NOT NULL DEFAULT '0' COMMENT '星级',
	`weapon_id` INT (11) NOT NULL COMMENT '武器id',
	`armor_id` INT (11) NOT NULL COMMENT '装备id',
	`horse_id` INT (11) NOT NULL COMMENT '坐骑id',
	`zuoji_id` INT (11) NOT NULL DEFAULT '0' COMMENT '坐骑',
	`skill_lv` INT (11) NOT NULL DEFAULT '0' COMMENT '技能等级',
	`build_id` INT (11) NOT NULL COMMENT '驻守建筑id',
	`army_id` INT (11) NOT NULL COMMENT '军团id',
	`force_rate` INT (11) NOT NULL DEFAULT '0' COMMENT 'force成长率',
	`intelligence_rate` INT (11) NOT NULL DEFAULT '0' COMMENT 'intelligence成长率',
	`governing_rate` INT (11) NOT NULL DEFAULT '0' COMMENT 'governing成长率',
	`charm_rate` INT (11) NOT NULL DEFAULT '0' COMMENT 'charm成长率',
	`political_rate` INT (11) NOT NULL DEFAULT '0' COMMENT 'political成长率',
	`stay_start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '驻守上次结算经验时间',
	`cross_skill_id_1` INT (11) NOT NULL DEFAULT '0',
	`cross_skill_lv_1` INT (11) NOT NULL DEFAULT '0',
	`cross_skill_id_2` INT (11) NOT NULL DEFAULT '0',
	`cross_skill_lv_2` INT (11) NOT NULL DEFAULT '0',
	`cross_skill_id_3` INT (11) NOT NULL DEFAULT '0',
	`cross_skill_lv_3` INT (11) NOT NULL DEFAULT '0',
	`status` INT (11) NOT NULL COMMENT '0-普通，1-出征，2-学习',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `general_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_general_buff` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`troop_max_plus` INT (11) NOT NULL DEFAULT '0',
	`build_speed` INT (11) NOT NULL DEFAULT '0',
	`science_research_speed` INT (11) NOT NULL DEFAULT '0',
	`pitfall_amount_plus` INT (11) NOT NULL DEFAULT '0',
	`help_num_plus` INT (11) NOT NULL DEFAULT '0',
	`help_time_plus` INT (11) NOT NULL DEFAULT '0',
	`silver_reduce` INT (11) NOT NULL DEFAULT '0',
	`decomposition_equipment_silver_plus` INT (11) NOT NULL DEFAULT '0',
	`move_to_npc_speed` INT (11) NOT NULL DEFAULT '0',
	`hospital_amount_plus` INT (11) NOT NULL DEFAULT '0',
	`pitfall_train_speed` INT (11) NOT NULL DEFAULT '0',
	`train_troops_speed` INT (11) NOT NULL DEFAULT '0',
	`cure_speed` INT (11) NOT NULL DEFAULT '0',
	`cure_cost_minus` INT (11) NOT NULL DEFAULT '0',
	`wall_defense_limit_plus` INT (11) NOT NULL DEFAULT '0',
	`training_infantry_num_plus` INT (11) NOT NULL DEFAULT '0',
	`training_cavalry_num_plus` INT (11) NOT NULL DEFAULT '0',
	`training_archer_num_plus` INT (11) NOT NULL DEFAULT '0',
	`training_siege_num_plus` INT (11) NOT NULL DEFAULT '0',
	`wood_income` INT (11) NOT NULL DEFAULT '0',
	`food_income` INT (11) NOT NULL DEFAULT '0',
	`gold_income` INT (11) NOT NULL DEFAULT '0',
	`stone_income` INT (11) NOT NULL DEFAULT '0',
	`iron_income` INT (11) NOT NULL DEFAULT '0',
	`wood_gathering_speed` INT (11) NOT NULL DEFAULT '0',
	`food_gathering_speed` INT (11) NOT NULL DEFAULT '0',
	`gold_gathering_speed` INT (11) NOT NULL DEFAULT '0',
	`stone_gathering_speed` INT (11) NOT NULL DEFAULT '0',
	`iron_gathering_speed` INT (11) NOT NULL DEFAULT '0',
	`infantry_carry_plus` INT (11) NOT NULL DEFAULT '0',
	`cavalry_carry_plus` INT (11) NOT NULL DEFAULT '0',
	`archer_carry_plus` INT (11) NOT NULL DEFAULT '0',
	`siege_carry_plus` INT (11) NOT NULL DEFAULT '0',
	`infantry_def_plus` INT (11) NOT NULL DEFAULT '0',
	`infantry_life_plus` INT (11) NOT NULL DEFAULT '0',
	`infantry_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`cavalry_def_plus` INT (11) NOT NULL DEFAULT '0',
	`cavalry_life_plus` INT (11) NOT NULL DEFAULT '0',
	`cavalry_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`archer_def_plus` INT (11) NOT NULL DEFAULT '0',
	`archer_life_plus` INT (11) NOT NULL DEFAULT '0',
	`archer_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`siege_def_plus` INT (11) NOT NULL DEFAULT '0',
	`siege_life_plus` INT (11) NOT NULL DEFAULT '0',
	`siege_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_infantry_def_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_infantry_life_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_infantry_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_cavalry_def_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_cavalry_life_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_cavalry_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_archer_def_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_archer_life_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_archer_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_siege_def_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_siege_life_plus` INT (11) NOT NULL DEFAULT '0',
	`citybattle_siege_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`build_cost_reduce` INT (11) NOT NULL DEFAULT '0',
	`research_cost_reduce` INT (11) NOT NULL DEFAULT '0',
	`pitfall_activated_probability` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_infantry_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_infantry_def_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_infantry_life_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_cavalry_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_cavalry_def_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_cavalry_life_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_archer_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_archer_def_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_archer_life_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_siege_atk_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_siege_def_plus` INT (11) NOT NULL DEFAULT '0',
	`fieldbattle_siege_life_plus` INT (11) NOT NULL DEFAULT '0',
	`troop_max_plus_percent` INT (11) NOT NULL DEFAULT '0',
	`protect_plus` INT (11) NOT NULL DEFAULT '0',
	`general_force_inc` INT (11) NOT NULL DEFAULT '0',
	`general_intelligence_inc` INT (11) NOT NULL DEFAULT '0',
	`general_governing_inc` INT (11) NOT NULL DEFAULT '0',
	`general_charm_inc` INT (11) NOT NULL DEFAULT '0',
	`general_political_inc` INT (11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE KEY `playerId` (`player_id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_growth` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`buy` INT (11) NOT NULL DEFAULT '0' COMMENT '栏位',
	`num_reward` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'item_id,秒数;...',
	`level_reward` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_guild` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`guild_id` INT (11) NOT NULL COMMENT '联盟id',
	`rank` INT (11) NOT NULL DEFAULT '0' COMMENT '公会成员等级 1-4 公会等级，5为会长',
	`guild_fort_effect` INT (11) NOT NULL DEFAULT '0' COMMENT '是否持有堡垒加成 0 无 1 有',
	`store_gold` INT (11) DEFAULT '0' COMMENT '储存的黄金',
	`store_food` INT (11) DEFAULT '0' COMMENT '储存的粮食',
	`store_wood` INT (11) DEFAULT '0' COMMENT '储存的木头',
	`store_stone` INT (11) DEFAULT '0' COMMENT '储存的石头',
	`store_iron` INT (11) DEFAULT '0' COMMENT '储存的铁',
	`last_day_store` INT (11) NOT NULL DEFAULT '0' COMMENT '一日内储存资源单位数量',
	`last_store_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '最后一次存仓库时间',
	`guild_mission_score` INT (11) NOT NULL DEFAULT '0' COMMENT '联盟任务个人积分',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '申请时间',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
	`cross_application_flag` INT (2) DEFAULT '0' COMMENT '跨服战成员申请 0：未申请， 1：申请',
	`cross_joined_flag` INT (2) DEFAULT '0' COMMENT '跨服战参战与否 0：未参战， 1：参战',
	`cross_army_info` text COLLATE utf8_unicode_ci COMMENT '跨服战军团信息',
	PRIMARY KEY (`id`),
	KEY `id` (`id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_guild_donate` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`status` INT (11) NOT NULL COMMENT '0:普通；1:锁定',
	`donate_reward` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '领取奖励标志，逗号分隔',
	`reward_time` date NOT NULL DEFAULT '0000-00-00' COMMENT '奖励时间',
	`last_donate_time` date NOT NULL DEFAULT '0000-00-00' COMMENT '最后一次捐献日期',
	`finish_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'cd完成时间',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_guild_donate_button` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`science_type` INT (11) NOT NULL,
	`level` INT (11) NOT NULL,
	`btn1_cost` INT (11) NOT NULL,
	`btn1_unit` INT (11) NOT NULL,
	`btn1_num` INT (11) NOT NULL,
	`btn2_cost` INT (11) NOT NULL,
	`btn2_unit` INT (11) NOT NULL,
	`btn2_num` INT (11) NOT NULL,
	`btn2_counter` INT (11) NOT NULL,
	`btn3_cost` INT (11) NOT NULL,
	`btn3_unit` INT (11) NOT NULL,
	`btn3_num` INT (11) NOT NULL,
	`btn3_counter` INT (11) NOT NULL,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `science_type`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_guild_donate_stat` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`type` INT (11) NOT NULL COMMENT '0.历史；1.每周；2.每日',
	`date` date NOT NULL COMMENT '截止时间',
	`coin` INT (11) NOT NULL,
	`exp` INT (11) NOT NULL,
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`, `type`, `date`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_guild_request` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`guild_id` INT (11) DEFAULT NULL COMMENT '联盟id',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '联盟请求表';

CREATE TABLE `player_help` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '要别人帮助的人',
	`help_num` INT (5) DEFAULT NULL COMMENT '帮助了多少次',
	`help_num_max` INT (5) DEFAULT NULL COMMENT '总帮助次数',
	`guild_id` INT (11) DEFAULT NULL COMMENT '联盟id',
	`help_type` INT (5) DEFAULT NULL COMMENT '帮助类型',
	`build_position` INT (11) DEFAULT '0' COMMENT '建筑位置',
	`help_resource_id` INT (11) DEFAULT NULL COMMENT '帮助的建筑或者研究的相关id，伤兵可为0',
	`helper_ids` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '0' COMMENT '帮助者id：格式如 1,2',
	`status` INT (5) DEFAULT '1' COMMENT '1： 可帮助 0：不用帮助',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
	PRIMARY KEY (`id`),
	KEY `player_id` (
		`player_id`,
		`build_position`,
		`status`
	) USING BTREE,
	KEY `guild_id` (`guild_id`, `status`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_info` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`email` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '玩家email',
	`level_animation` SMALLINT (2) DEFAULT '0' COMMENT '1:需要播放 0:不需要播放',
	`first_out` INT (11) NOT NULL DEFAULT '0' COMMENT '是否第一次出征,不消耗体力,0:未出征过，1：已出征过',
	`first_nick` INT (11) DEFAULT '0' COMMENT '是否第一次修改昵称,0: 是第一次 1:不是第一次',
	`first_join_guild` INT (11) DEFAULT '0' COMMENT '是否第一次加入联盟 0:是第一次,1:不是第一次',
	`quick_out` INT (11) DEFAULT '0' COMMENT '2秒往返使用标志',
	`target_end_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '新手目标结束时间',
	`login_hashcode` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '登录hashcode,单设备登录',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`login_channel` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '登录渠道',
	`download_channel` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '下载渠道',
	`pay_channel` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '支付渠道',
	`platform` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '平台',
	`device_mode` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '设备型号',
	`system_version` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '系统版本',
	`first_pay` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'pricing-goods_type,逗号分隔',
	`long_card` INT (1) DEFAULT '0' COMMENT '1:购买 0:未购买',
	`long_card_date` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '领取至尊卡日期',
	`month_card_deadline` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '月卡截至日期',
	`month_card_date` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '领取月卡日期',
	`gift_lv12_begin_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '府衙lv12礼包开启时间',
	`gift_lv22_begin_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '府衙lv22礼包开启时间',
	`gift_lv37_begin_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '府衙lv37礼包开启时间',
	`gift_lose_power_begin_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '死伤礼包开始时间',
	`ban_msg_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '禁言截止时间',
	`ban_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '封号时间',
	`skip_newbie` INT (11) DEFAULT '0',
	`facebook_share_count` INT (11) DEFAULT '0' COMMENT 'facebook的分享次数',
	`big_deal_mail` INT (11) DEFAULT '0' COMMENT '大额邮件 0:未发送 1:发送过',
	`email_tips_id` INT (11) DEFAULT '0' COMMENT '已经发送的email_tips表id',
	`bowl_type1_last_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '占星最近一次免费抽取时间',
	`bowl_type2_last_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '天陨最近一次免费抽取时间',
	`bowl_counter_drop_group_1` INT (11) DEFAULT '1' COMMENT '聚宝盆计数器1',
	`bowl_counter_drop_group_2` INT (11) DEFAULT '1' COMMENT '聚宝盆计数器2',
	`bowl_counter_drop_group_10` INT (11) DEFAULT '1' COMMENT '聚宝盆计数器10',
	`bowl_counter_drop_group_11` INT (11) DEFAULT '1' COMMENT '聚宝盆计数器11',
	`bowl_counter_drop_group_12` INT (11) DEFAULT '1' COMMENT '聚宝盆计数器12',
	`first_high_astrology_drop` INT (11) DEFAULT '0' COMMENT '是否首次天陨掉落 0:未操作 1：已操作',
	`bowl_counter_drop_group_14` INT (11) DEFAULT '1' COMMENT '聚宝盆计数器14-特殊',
	`bowl_counter_drop_group_14_status` INT (11) DEFAULT '0' COMMENT '聚宝盆特殊掉落标记 1：已掉落 0：未掉落',
	`first_create_guild` INT (2) DEFAULT '0' COMMENT '0:未建过盟 1:建过盟',
	`newbie_login` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '新玩家前x天登陆情况',
	`secretary_status` INT (11) DEFAULT '0' COMMENT '随身秘书状态 0:等待打开， 1：玩家选择需要秘书引导选择军事， 2：一个是玩家需要引导选择了内政， 3：一个是玩家不需要秘书引导，关闭',
	`general_star_reward` varchar(255) NOT NULL DEFAULT '' COMMENT '星级奖项',
	`af_media_source` varchar (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'af渠道',
	`af_uid` INT (11) DEFAULT NULL COMMENT 'af_id',
	`sacrifice_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '祭天免费时间',
	`sacrifice_flag` INT (2) DEFAULT '0' COMMENT '1: 半价 0：全价',
	`sacrifice_newbie_flag` INT (2) DEFAULT '0' COMMENT '祭天单抽新手',
	`newbie_pay` INT (11) NOT NULL DEFAULT '0' COMMENT '新手充值奖励',
	`skill_wash_date` date NOT NULL DEFAULT '0000-00-00' COMMENT '最后技能洗炼时间',
	`login_ip` VARCHAR (50) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '登陆ip',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_item` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`item_id` INT (11) NOT NULL COMMENT '军团位置',
	`num` INT (11) NOT NULL,
	`is_new` INT (11) NOT NULL COMMENT '是否为新',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `item_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_lottery_info` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`free_times` INT (11) NOT NULL,
	`current_position` INT (11) NOT NULL,
	`last_date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`coin_num` INT (11) NOT NULL,
	`jade_num` INT (11) NOT NULL,
	`draw_card_id` INT (11) NOT NULL DEFAULT '0' COMMENT '0 没有翻牌 1 翻牌表id',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `player_mail` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`type` INT (11) NOT NULL COMMENT '1:系统，2:聊天（单人），3:聊天（多人），10:侦查，11:被侦查，20:攻城战斗胜利，21:攻城战斗失败，22:守城战斗胜利，23:守城战斗失败，24:攻击部队胜利，25:攻击部队失败，26:防守部队胜利，27:防守部队失败，28:攻击怪物胜利，29攻击怪物失败，30:采集报告，40:联盟邀请，41:联盟申请，42:联盟审批，43:联盟成员退盟（包括被赶出），44:联盟集结信息',
	`connect_id` CHAR (13) COLLATE utf8_unicode_ci NOT NULL COMMENT '联系对象：玩家id/组id',
	`mail_info_id` INT (11) NOT NULL,
	`read_flag` INT (11) NOT NULL COMMENT '0:未读，1:已读',
	`memo` VARCHAR (1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
	`status` INT (11) NOT NULL COMMENT '0:普通，1:收藏，-1:删除',
	`expire_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`, `type`) USING BTREE,
	KEY `expire_time` (`expire_time`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_mail_group` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`group_id` CHAR (13) COLLATE utf8_unicode_ci NOT NULL COMMENT '0:系统，1:聊天（单人），2:聊天（多人），10:侦查，11:被侦查，20:攻城战斗胜利，21:攻城战斗失败，22:守城战斗胜利，23:守城战斗失败，24:攻击部队胜利，25:攻击部队失败，26:防守部队胜利，27:防守部队失败，28:攻击怪物胜利，29攻击怪物失败，30:采集报告，40:联盟邀请，41:联盟申请，42:联盟审批，43:联盟成员退盟（包括被赶出），44:联盟集结信息',
	`player_id` INT (11) NOT NULL,
	`is_creater` INT (11) NOT NULL COMMENT '是否已是创建者',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`group_id`, `player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_mail_info` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`from_player_id` INT (11) NOT NULL,
	`from_player_name` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL,
	`from_player_avatar` INT (11) NOT NULL,
	`from_guild_short` CHAR (10) COLLATE utf8_unicode_ci NOT NULL,
	`title` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL COMMENT '标题',
	`msg` text COLLATE utf8_unicode_ci NOT NULL COMMENT '文字内容',
	`data` text COLLATE utf8_unicode_ci NOT NULL COMMENT '参数',
	`item` text COLLATE utf8_unicode_ci NOT NULL COMMENT '附件',
	`expire_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `expire_time` (`expire_time`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_market` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`last_date` date DEFAULT NULL,
	`counter` INT (11) DEFAULT '0' COMMENT '当日购买次数',
	`market_ids` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '当前六件商品，逗号分隔',
	`special_id` INT (11) DEFAULT NULL COMMENT '超值商品id',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` VARCHAR (13) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_master_skill` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`talent_id` INT (11) NOT NULL COMMENT '技能id',
	`next_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '下次可使用时间',
	`effect_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '生效结束时间',
	`enable` INT (11) NOT NULL COMMENT '是否为新',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `talent_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_mill` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`num` INT (11) NOT NULL DEFAULT '0' COMMENT '栏位',
	`item_ids` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'item_id,秒数;...',
	`begin_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '材料生产开始时间',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_mission` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL,
	`mission_type` SMALLINT (3) DEFAULT NULL COMMENT 'mission mission_type',
	`mission_id` INT (11) DEFAULT NULL COMMENT 'mission id',
	`current_mission_number` INT (11) DEFAULT '0' COMMENT 'current num',
	`max_mission_number` INT (11) DEFAULT '0',
	`date_limit` date DEFAULT '0000-00-00' COMMENT '日期记录',
	`status` SMALLINT (2) DEFAULT '0' COMMENT '0:接任务 1：完成任务 2：领完奖',
	`position` SMALLINT (3) DEFAULT '0' COMMENT '任务位置',
	`reward` VARCHAR (300) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '奖励记录',
	`memo` VARCHAR (300) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'memo here',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`, `mission_type`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_newbie_activity_charge` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`period` INT (11) NOT NULL,
	`gem` INT (11) NOT NULL DEFAULT '0' COMMENT '累计元宝数',
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'gem1档已领,gem2档已领...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `period`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_newbie_activity_consume` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`period` INT (11) NOT NULL COMMENT '军团位置',
	`gem` INT (11) NOT NULL DEFAULT '0' COMMENT '累计元宝数',
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'gem1档已领,数量;...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `period`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_newbie_activity_login` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`flag` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '1档已领,2档已领...',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_online` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`date` date NOT NULL DEFAULT '0000-00-00',
	`online` INT (6) NOT NULL COMMENT '当日在线时间单位秒',
	`first_exp` INT (11) NOT NULL COMMENT '当日第一次登陆时经验',
	`day_exp` INT (11) NOT NULL COMMENT '当日总共获取经验',
	PRIMARY KEY (`id`),
	KEY `player_date` (`player_id`, `date`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_online_award` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`online_award_id` INT (11) DEFAULT NULL COMMENT '字典表id',
	`status` INT (2) DEFAULT '0' COMMENT '0:未领 1：领过',
	`date_limit` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '当前时间',
	`award_item` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '获得的奖励',
	`time_start` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始计算时间',
	`time_award` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '领奖时间',
	`online_award_duration` INT (11) DEFAULT '0' COMMENT '持续时间，从online_award表读取',
	`memo` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '备忘',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `pid` (`player_id`, `date_limit`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '玩家在线奖励表';

CREATE TABLE `player_order` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`order_id` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL COMMENT '军团位置',
	`payment_code` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL,
	`activity_commodity_id` INT (11) NOT NULL,
	`channel` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL COMMENT '是否为新',
	`mode` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL,
	`price` FLOAT NOT NULL,
	`gem` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`drop` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL,
	`series` INT (11) NOT NULL DEFAULT '0',
	`series_order` INT (11) NOT NULL DEFAULT '0',
	`status` INT (11) NOT NULL,
	`out_trade_no` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '第三方订单号',
	`tunnel` VARCHAR (10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'game' COMMENT 'game,web',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `order_id` (`order_id`) USING BTREE,
	KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_project_queue` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '动作玩家id',
	`type` INT (11) NOT NULL COMMENT '动作类型 1 侦查 2 战斗 3 采集',
	`target_player_id` INT (11) NOT NULL DEFAULT '0' COMMENT '目标玩家id',
	`target_info` VARCHAR (512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '目标情况[json数组]',
	`status` INT (11) NOT NULL DEFAULT '1' COMMENT '状态 1进行中 2已完成 3已撤销',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
	`end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '完成时间',
	`accelerate_info` VARCHAR (1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '使用加速数据 (时间,加速类型;...)',
	`army_id` INT (11) NOT NULL DEFAULT '0',
	`guild_id` INT (11) NOT NULL DEFAULT '0',
	`carry_gold` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '携带-金',
	`carry_food` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '携带-粮',
	`carry_wood` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '携带-木',
	`carry_stone` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '携带-石',
	`carry_iron` BIGINT (20) NOT NULL DEFAULT '0' COMMENT '携带-铁',
	`carry_soldier` VARCHAR (1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '携带兵[[soldier_id,soldier_num]...]',
	`carry_item` VARCHAR (1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '携带道具drop',
	`from_map_id` INT (11) NOT NULL DEFAULT '0' COMMENT '源地址，无则为0',
	`to_map_id` INT (11) NOT NULL DEFAULT '0' COMMENT '目标地址，无则为0',
	`from_x` INT (11) NOT NULL DEFAULT '0' COMMENT 'map from x',
	`from_y` INT (11) NOT NULL DEFAULT '0' COMMENT 'map from y',
	`to_x` INT (11) NOT NULL DEFAULT '0' COMMENT 'map to x',
	`to_y` INT (11) NOT NULL DEFAULT '0' COMMENT 'map to y',
	`parent_queue_id` INT (11) NOT NULL DEFAULT '0',
	`battle` INT (11) NOT NULL DEFAULT '0' COMMENT '0:无战斗，1：战斗胜利，2：战斗失败',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` INT (11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	KEY `target_player_id` (`target_player_id`) USING BTREE,
	KEY `status` (`status`, `end_time`, `type`) USING BTREE,
	KEY `to_map_id` (`to_map_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_pub` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`luck_counter` INT (11) DEFAULT NULL COMMENT '计数器',
	`pay_luck_counter` INT (11) DEFAULT NULL COMMENT '付费计数器',
	`pay_day_counter` INT (11) DEFAULT NULL COMMENT '付费刷新单日计数器',
	`last_pay_reload_date` date DEFAULT NULL COMMENT '最后一次付费刷新日期',
	`next_free_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '下一次免费刷新时间',
	`build_id` INT (11) DEFAULT NULL COMMENT '最后一次刷新的建筑号',
	`pay_build_id` INT (11) DEFAULT NULL COMMENT '最后一次付费刷新的建筑号',
	`generals` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '刷新武将列表，逗号分隔',
	`prisoners` VARCHAR (1024) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '招安武将列表，逗号分隔',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` VARCHAR (13) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_push` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`type` INT (11) NOT NULL COMMENT '消息类型',
	`code` INT (11) NOT NULL COMMENT '语言码id',
	`param` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL COMMENT '语言变量',
	`txt` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '文字',
	`send_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `player_id` (`player_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_question` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL,
	`q1` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q2` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q3` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q4` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q5` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q6` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q7` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q8` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q9` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q10` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q11` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q12` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q13` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`q14` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`code` VARCHAR (255) COLLATE utf8_unicode_ci DEFAULT '',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`)
) ENGINE = INNODB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_quick_money` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`gem_num` INT (11) NOT NULL COMMENT '元宝数量',
	`create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COMMENT = '玩家新人抽奖表';

CREATE TABLE `player_science` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`science_id` INT (11) NOT NULL COMMENT '天赋编号',
	`start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`next_id` INT (11) NOT NULL COMMENT '研究中id',
	`push_id` INT (11) NOT NULL DEFAULT '0' COMMENT '推送id',
	`status` INT (11) NOT NULL COMMENT '0.正常，1.研究',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `science_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_shop` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`shop_id` INT (11) NOT NULL COMMENT '军团位置',
	`num` INT (11) NOT NULL,
	`date` date NOT NULL COMMENT '是否为新',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `shop_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_sign_award` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT NULL COMMENT '玩家id',
	`sign_award_id` INT (11) DEFAULT NULL COMMENT '签到奖励字典表id',
	`status` INT (2) DEFAULT '0' COMMENT '0：未领 1：领过了',
	`get_award_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '获得奖励的时间',
	`award_item` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '奖励内容',
	`memo` VARCHAR (500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '备忘',
	`round_flag` INT (2) DEFAULT '0' COMMENT '1：当前周期 0：失效周期',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `pid` (`player_id`, `round_flag`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '玩家签到奖励表';

CREATE TABLE `player_soldier` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`soldier_id` INT (11) NOT NULL COMMENT '士兵id',
	`num` INT (11) NOT NULL COMMENT '数量',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `soldier_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_soldier_injured` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`soldier_id` INT (11) NOT NULL COMMENT '士兵id',
	`num` INT (11) NOT NULL COMMENT '数量',
	`create_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `pid` (`player_id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_study` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`general_id` INT (11) NOT NULL COMMENT '武将初始id',
	`position` INT (11) NOT NULL COMMENT '位置',
	`type` INT (11) NOT NULL COMMENT '1.免费，2.付费',
	`gain_exp` INT (11) NOT NULL COMMENT '获得的经验',
	`start_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始学习时间',
	`end_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束学习的时间',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `position`) USING BTREE
) ENGINE = INNODB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_talent` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`talent_id` INT (11) NOT NULL COMMENT '天赋编号',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` CHAR (13) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `player_id` (`player_id`, `talent_id`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_target` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`target_id` INT (11) NOT NULL,
	`target_type` INT (11) DEFAULT NULL COMMENT 'target''s target_type',
	`current_value` INT (11) DEFAULT '0' COMMENT '当前值',
	`target_value` INT (11) DEFAULT NULL COMMENT '目标值',
	`date_start` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
	`date_end` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
	`award_status` INT (2) DEFAULT '0' COMMENT '0:未领 1:领取',
	`position` INT (2) DEFAULT '0' COMMENT '目标位置',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rowversion` VARCHAR (50) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'row version',
	PRIMARY KEY (`id`),
	KEY `pid` (`player_id`, `award_status`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `player_time_limit_match` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`time_limit_match_list_id` INT (11) NOT NULL,
	`match_type` INT (11) DEFAULT NULL COMMENT '限时比赛类型',
	`score` INT (11) DEFAULT '0' COMMENT '积分',
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `pid` (
		`player_id`,
		`time_limit_match_list_id`
	)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '玩家每天的限时比赛表';

CREATE TABLE `player_time_limit_match_total` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL,
	`time_limit_match_config_id` INT (11) NOT NULL,
	`score` INT (11) NOT NULL DEFAULT '0' COMMENT '积分',
	`rank` INT (11) NOT NULL,
	`update_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	KEY `pid` (
		`player_id`,
		`time_limit_match_config_id`
	)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '玩家每届的限时比赛表';

CREATE TABLE `player_trap` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) NOT NULL COMMENT '玩家id',
	`trap_id` INT (11) NOT NULL COMMENT '陷阱id',
	`num` INT (11) NOT NULL COMMENT '数量',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COMMENT = '玩家陷阱表';

CREATE TABLE `rank` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`gpd` INT (11) NOT NULL COMMENT 'guild_id or player_id',
	`type` INT (11) NOT NULL,
	`rank` INT (11) NOT NULL DEFAULT '0',
	`name` VARCHAR (100) COLLATE utf8_unicode_ci NOT NULL COMMENT '作者:\nDROP ID\n',
	`value` BIGINT (11) NOT NULL,
	`avatar` INT (11) NOT NULL,
	`guild_id` INT (11) NOT NULL,
	`guild_name` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `type` (`type`, `rank`) USING BTREE
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `round_message` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`player_id` INT (11) DEFAULT '0' COMMENT '当前操作玩家;进攻方;',
	`player_nick` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '玩家名称',
	`type` INT (2) DEFAULT '1' COMMENT '0:系统消息 1:战斗相关 2:招募武将 3:击杀boss 4:紫色品质装备品质 6:国王战官职任命',
	`gm_notice` VARCHAR (1000) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'gm消息',
	`battle_type` INT (2) DEFAULT '1' COMMENT '1:野外 2:城池 3:堡垒',
	`battle_defender_id` INT (11) DEFAULT '0' COMMENT '防守方',
	`battle_defender_player_nick` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '玩家名称',
	`battle_attacker_guild_id` INT (11) DEFAULT '0' COMMENT '进攻方联盟id',
	`battle_attacker_guild_short_name` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '进攻方联盟简称',
	`battle_defender_guild_id` INT (11) DEFAULT '0' COMMENT '防守方联盟id',
	`battle_defender_guild_short_name` VARCHAR (100) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '防守方联盟简称',
	`battle_win` INT (2) DEFAULT '0' COMMENT '1:进攻方胜 2:进攻方败',
	`battle_attacker_power_loss` INT (11) DEFAULT '0' COMMENT '进攻方战力损失',
	`battle_defender_power_loss` INT (11) DEFAULT '0' COMMENT '防御方战力损失',
	`battle_hsb_num` INT (11) DEFAULT '0' COMMENT '获得玉玺数量',
	`general_id` INT (11) DEFAULT '0' COMMENT '招募4星武将id',
	`boss_player_num` INT (11) DEFAULT '0' COMMENT '击杀boss的 集结的玩家数',
	`boss_npc_id` INT (11) DEFAULT '0' COMMENT 'npc id',
	`equipment_id` INT (11) DEFAULT '0' COMMENT '紫色品质装备id',
	`equipment_star` INT (11) DEFAULT '0' COMMENT '装备进阶星数',
	`status` INT (1) DEFAULT '1' COMMENT '1:未读 0:已读',
	`data` text COLLATE utf8_unicode_ci COMMENT 'type大于5以上的组装数据',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '走马灯';

CREATE TABLE `stat_snapshot` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`dt` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`type` INT (11) NOT NULL DEFAULT '0' COMMENT '1:留存，2.付费',
	`channel` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`liucun2` FLOAT NOT NULL DEFAULT '0',
	`liucun3` FLOAT NOT NULL DEFAULT '0',
	`liucun4` FLOAT NOT NULL DEFAULT '0',
	`liucun5` FLOAT NOT NULL DEFAULT '0',
	`liucun6` FLOAT NOT NULL DEFAULT '0',
	`liucun7` FLOAT NOT NULL DEFAULT '0',
	`liucun14` FLOAT NOT NULL DEFAULT '0',
	`liucun30` FLOAT NOT NULL DEFAULT '0',
	`pay_rate` FLOAT NOT NULL DEFAULT '0',
	`pay_rmb` FLOAT NOT NULL DEFAULT '0',
	`arpu` FLOAT NOT NULL DEFAULT '0',
	`arppu` FLOAT NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE KEY `dt` (`dt`, `type`, `channel`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;

CREATE TABLE `time_limit_match_config` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`status` INT (2) DEFAULT '0' COMMENT '0: 开启 1:关闭',
	`start_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
	`end_time` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
	`create_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
	PRIMARY KEY (`id`),
	KEY `status` (`status`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

CREATE TABLE `time_limit_match_list` (
	`id` INT (11) NOT NULL AUTO_INCREMENT,
	`time_limit_match_config_id` INT (11) DEFAULT NULL,
	`time_limit_match_id` INT (11) DEFAULT '0' COMMENT '字典表id',
	`match_type` INT (3) NOT NULL COMMENT '比赛类型',
	`match_date_start` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '比赛开始时间',
	`match_date_end` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00' COMMENT '比赛结束时间',
	`award_status` INT (3) DEFAULT '0' COMMENT '0: 奖励未结算 1:奖励已结算',
	PRIMARY KEY (`id`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '限时比赛列表';
