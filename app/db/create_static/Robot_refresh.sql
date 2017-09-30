--
-- Table structure for table `Robot_refresh`
--
DROP TABLE IF EXISTS `Robot_refresh`;
CREATE TABLE IF NOT EXISTS `Robot_refresh` (
`id` int(11) NOT NULL ,
`build_level` int(11) COMMENT '作者:
建筑等级：
府衙 城墙 农田x5 金矿x5',
`troop` text COMMENT '作者:
武将id，兵种id
数量100~500随机',
`day_start` int(11) COMMENT '作者:
开始刷新的开服天数',
`day_end` int(11) COMMENT '作者:
结束刷新的开服天数',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
