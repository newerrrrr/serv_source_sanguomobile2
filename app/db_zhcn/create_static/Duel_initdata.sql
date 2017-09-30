--
-- Table structure for table `Duel_initdata`
--
DROP TABLE IF EXISTS `Duel_initdata`;
CREATE TABLE IF NOT EXISTS `Duel_initdata` (
`id` int(11) NOT NULL ,
`default_num` int(11) COMMENT '作者:
默认武斗次数
',
`battle_cost` int(11) COMMENT '作者:
额外战斗购买价格
为COST ID',
`base_rank_point` int(11) COMMENT '作者:
公式调整的K值
',
`season_time` int(11) COMMENT '作者:
赛级持续天数',
`duel_close_time` int(11) COMMENT '作者:
武斗关闭时间
赛级结束时间点-dul_close_time
',
`protect_score` int(11) COMMENT '作者:
保护积分',
`robot_count` int(11) COMMENT '作者:
打机器人次数',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
