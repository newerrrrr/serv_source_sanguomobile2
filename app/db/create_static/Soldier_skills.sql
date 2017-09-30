--
-- Table structure for table `Soldier_skills`
--
DROP TABLE IF EXISTS `Soldier_skills`;
CREATE TABLE IF NOT EXISTS `Soldier_skills` (
`id` int(11) NOT NULL ,
`soldier_skills_name` int(11) ,
`desc1` varchar(512) ,
`soldier_skill_introduction` int(11) COMMENT '陈涛:
技能介绍',
`desc2` varchar(512) ,
`soldier_skills_type` int(11) COMMENT '陈涛:
技能类型',
`soldier_skill_num` int(11) COMMENT '陈涛:
技能数值(万分比)
暂不读取',
`soldier_skill_img` varchar(512) COMMENT '陈涛:
技能图标',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
