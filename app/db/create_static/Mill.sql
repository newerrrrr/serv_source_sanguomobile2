--
-- Table structure for table `Mill`
--
DROP TABLE IF EXISTS `Mill`;
CREATE TABLE IF NOT EXISTS `Mill` (
`id` int(11) NOT NULL ,
`item` int(11) COMMENT '作者:
可制造道具
',
`time` int(11) COMMENT '作者:
单个道具花费时间',
`level_min` int(11) COMMENT '作者:
最小府衙等级',
`level_max` int(11) COMMENT '作者:
最大府衙等级',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
