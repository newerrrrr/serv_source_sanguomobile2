--
-- Table structure for table `General_exp`
--
DROP TABLE IF EXISTS `General_exp`;
CREATE TABLE IF NOT EXISTS `General_exp` (
`id` int(11) NOT NULL ,
`general_level` int(11) ,
`general_exp` int(11) COMMENT '陈涛:
武将当前等级对应全部经验',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
