--
-- Table structure for table `Email_tips`
--
DROP TABLE IF EXISTS `Email_tips`;
CREATE TABLE IF NOT EXISTS `Email_tips` (
`id` int(11) NOT NULL ,
`time` int(11) COMMENT '作者:
以分钟为单位',
`title` int(11) ,
`title_desc1` varchar(512) ,
`desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
