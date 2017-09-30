--
-- Table structure for table `Sprite`
--
DROP TABLE IF EXISTS `Sprite`;
CREATE TABLE IF NOT EXISTS `Sprite` (
`id` int(11) NOT NULL ,
`path` varchar(512) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
