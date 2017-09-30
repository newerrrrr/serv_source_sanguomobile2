--
-- Table structure for table `Power`
--
DROP TABLE IF EXISTS `Power`;
CREATE TABLE IF NOT EXISTS `Power` (
`id` int(11) NOT NULL ,
`level` int(11) ,
`power` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
