--
-- Table structure for table `General_star`
--
DROP TABLE IF EXISTS `General_star`;
CREATE TABLE IF NOT EXISTS `General_star` (
`id` int(11) NOT NULL ,
`general_original_id` int(11) COMMENT '陈涛:
武将原始ID',
`star` int(11) ,
`general_force_growth` int(11) COMMENT '武将武力成长值',
`general_intelligence_growth` int(11) COMMENT '武将智力成长值',
`general_governing_growth` int(11) COMMENT '武将统治力成长值',
`general_charm_growth` int(11) COMMENT '武将魅力成长值',
`general_political_growth` int(11) COMMENT '武将政治成长值',
`consume` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
