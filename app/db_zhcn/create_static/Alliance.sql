--
-- Table structure for table `Alliance`
--
DROP TABLE IF EXISTS `Alliance`;
CREATE TABLE IF NOT EXISTS `Alliance` (
`id` int(11) NOT NULL ,
`alliance_architectures_name` varchar(512) COMMENT '陈涛:
联盟建筑名称',
`alliance_construction_time` varchar(512) COMMENT '陈涛:
升级所需时间/秒',
`open_condition` int(11) COMMENT '陈涛:
开放条件
1-人数达到多少
2-联盟战斗力达到多少
3-联盟科技达到多少',
`open_num` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
