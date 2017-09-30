--
-- Table structure for table `Country_camp_list`
--
DROP TABLE IF EXISTS `Country_camp_list`;
CREATE TABLE IF NOT EXISTS `Country_camp_list` (
`id` int(11) NOT NULL ,
`camp_name` int(11) ,
`desc` varchar(512) ,
`camp_pic` int(11) ,
`short_name` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
