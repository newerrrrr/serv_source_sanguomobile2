--
-- Table structure for table `Buff_temp`
--
DROP TABLE IF EXISTS `Buff_temp`;
CREATE TABLE IF NOT EXISTS `Buff_temp` (
`id` int(11) NOT NULL ,
`buff_id` int(11) ,
`buff_num` int(11) COMMENT '陈涛:
万分比
加成数量',
`buff_desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
