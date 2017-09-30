--
-- Table structure for table `Country_science_exp`
--
DROP TABLE IF EXISTS `Country_science_exp`;
CREATE TABLE IF NOT EXISTS `Country_science_exp` (
`id` int(11) NOT NULL ,
`week_number` int(11) COMMENT '徐力丰:
跨服战第几周',
`autoexp_per_hour` int(11) ,
`player_exp_rate` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
