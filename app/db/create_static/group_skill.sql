--
-- Table structure for table `group_skill`
--
DROP TABLE IF EXISTS `group_skill`;
CREATE TABLE IF NOT EXISTS `group_skill` (
`id` int(11) NOT NULL ,
`general_original_id` text COMMENT '陈涛:
武将原始ID',
`number` int(11) ,
`min_general_level` int(11) ,
`group_skill_type` int(11) ,
`buff` text ,
`skill_name` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
