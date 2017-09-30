--
-- Table structure for table `Duel_times_bonus`
--
DROP TABLE IF EXISTS `Duel_times_bonus`;
CREATE TABLE IF NOT EXISTS `Duel_times_bonus` (
`id` int(11) NOT NULL ,
`times` int(11) ,
`drops` text ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
