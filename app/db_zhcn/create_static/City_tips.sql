--
-- Table structure for table `City_tips`
--
DROP TABLE IF EXISTS `City_tips`;
CREATE TABLE IF NOT EXISTS `City_tips` (
`id` int(11) NOT NULL ,
`description` int(11) COMMENT '描述文字',
`desc1` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
