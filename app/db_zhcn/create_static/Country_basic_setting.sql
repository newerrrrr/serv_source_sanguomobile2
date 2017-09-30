--
-- Table structure for table `Country_basic_setting`
--
DROP TABLE IF EXISTS `Country_basic_setting`;
CREATE TABLE IF NOT EXISTS `Country_basic_setting` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`data` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
