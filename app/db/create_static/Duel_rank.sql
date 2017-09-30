--
-- Table structure for table `Duel_rank`
--
DROP TABLE IF EXISTS `Duel_rank`;
CREATE TABLE IF NOT EXISTS `Duel_rank` (
`id` int(11) NOT NULL ,
`rank` int(11) ,
`rank_name` int(11) ,
`rank_desc` varchar(512) COMMENT '作者:
大段位',
`rank_pic` int(11) ,
`rank_number` int(11) ,
`sub_rank` int(11) ,
`sub_rank_name` varchar(512) COMMENT '作者:
小名称：
如都尉1
都尉2
',
`min_point` int(11) ,
`max_point` int(11) ,
`drop` text COMMENT '作者:
军衔升级的一次性奖励',
`daily_drop` text COMMENT '作者:
军衔升级每日奖励',
`win_drop` text COMMENT '作者:
改为单次战斗奖励',
`lose_drop` text COMMENT '作者:
改为单次战斗奖励',
`rank_hit_rate` int(11) COMMENT '作者:
AI命中率',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
