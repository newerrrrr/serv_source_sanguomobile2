--
-- Table structure for table `Build_info`
--
DROP TABLE IF EXISTS `Build_info`;
CREATE TABLE IF NOT EXISTS `Build_info` (
`id` int(11) NOT NULL ,
`build_type` int(11) ,
`desc1` varchar(512) ,
`description` text ,
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
