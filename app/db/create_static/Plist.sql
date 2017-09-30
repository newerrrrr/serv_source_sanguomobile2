--
-- Table structure for table `Plist`
--
DROP TABLE IF EXISTS `Plist`;
CREATE TABLE IF NOT EXISTS `Plist` (
`id` int(11) NOT NULL ,
`path` varchar(512) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
