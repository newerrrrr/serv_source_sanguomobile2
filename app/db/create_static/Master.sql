--
-- Table structure for table `Master`
--
DROP TABLE IF EXISTS `Master`;
CREATE TABLE IF NOT EXISTS `Master` (
`id` int(11) NOT NULL ,
`level` int(11) ,
`exp` int(11) COMMENT '作者:
总exp',
`drop` int(11) ,
`talent_num` int(11) ,
`max_general` int(11) ,
`day_storage` int(11) ,
`max_warehouse` int(11) ,
`power` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
