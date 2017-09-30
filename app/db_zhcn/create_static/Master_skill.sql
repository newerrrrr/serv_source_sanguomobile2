--
-- Table structure for table `Master_skill`
--
DROP TABLE IF EXISTS `Master_skill`;
CREATE TABLE IF NOT EXISTS `Master_skill` (
`id` int(11) NOT NULL  COMMENT '作者:
等于天赋ID',
`talent_id` int(11) ,
`icon` text ,
`talent_text` int(11) COMMENT '作者:
天赋介绍',
`desc1` varchar(512) ,
`cd` int(11) COMMENT '作者:
CD时间，单位：秒',
`cdhour` int(11) ,
`duration` int(11) COMMENT '作者:
是否持续时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
