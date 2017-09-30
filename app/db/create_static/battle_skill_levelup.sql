--
-- Table structure for table `battle_skill_levelup`
--
DROP TABLE IF EXISTS `battle_skill_levelup`;
CREATE TABLE IF NOT EXISTS `battle_skill_levelup` (
`id` int(11) NOT NULL ,
`level` int(11) ,
`consume` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
