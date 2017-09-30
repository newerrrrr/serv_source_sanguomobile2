--
-- Table structure for table `combat_skill`
--
DROP TABLE IF EXISTS `combat_skill`;
CREATE TABLE IF NOT EXISTS `combat_skill` (
`id` int(11) NOT NULL ,
`combat_skill_id` int(11) ,
`type` int(11) ,
`skill_name` int(11) ,
`desc1` varchar(512) ,
`target` text COMMENT '徐力丰:
生效兵种id 0为全部生效
1 步
2 骑
3 弓
4 车

',
`skill_description` int(11) ,
`desc2` varchar(512) ,
`skill_description2` int(11) ,
`desc3` varchar(512) ,
`num_type` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`base` int(11) COMMENT '徐力丰:
基础值',
`para1` int(11) COMMENT '徐力丰:
等级系数',
`para2` int(11) COMMENT '徐力丰:
属性系数',
`backup` varchar(512) ,
`client_formula` varchar(512) ,
`server_formula` varchar(512) ,
`combat_info` int(11) ,
`desc4` varchar(512) ,
`client_formula_backup` varchar(512) ,
`server_formula_backup` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
