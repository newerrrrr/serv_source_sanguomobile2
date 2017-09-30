--
-- Table structure for table `error_code`
--
DROP TABLE IF EXISTS `error_code`;
CREATE TABLE IF NOT EXISTS `error_code` (
`id` int(11) NOT NULL ,
`zhcn` varchar(512) ,
`zhtw` varchar(512) ,
`en` varchar(512) ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
