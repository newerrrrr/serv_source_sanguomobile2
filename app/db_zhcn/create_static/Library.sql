--
-- Table structure for table `Library`
--
DROP TABLE IF EXISTS `Library`;
CREATE TABLE IF NOT EXISTS `Library` (
`id` int(11) NOT NULL ,
`time` int(11) COMMENT '作者:
时间/小时',
`cost` int(11) COMMENT '作者:
消耗',
`rate` int(11) COMMENT '作者:
倍率',
`clear_time` int(11) COMMENT '作者:
清除时间
秒/元宝',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
