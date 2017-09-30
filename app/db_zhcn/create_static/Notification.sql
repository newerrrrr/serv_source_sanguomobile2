--
-- Table structure for table `Notification`
--
DROP TABLE IF EXISTS `Notification`;
CREATE TABLE IF NOT EXISTS `Notification` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`priority` int(11) COMMENT '作者:
优先级',
`text` varchar(512) COMMENT '作者:
中文介绍，不需要进库',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
