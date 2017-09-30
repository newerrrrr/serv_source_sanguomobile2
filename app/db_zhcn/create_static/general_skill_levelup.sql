--
-- Table structure for table `general_skill_levelup`
--
DROP TABLE IF EXISTS `general_skill_levelup`;
CREATE TABLE IF NOT EXISTS `general_skill_levelup` (
`id` int(11) NOT NULL ,
`general_skill_exp` int(11) COMMENT '陈涛:
升级需要的技能书数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
