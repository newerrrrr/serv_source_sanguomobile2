--
-- Table structure for table `active_skill_target`
--
DROP TABLE IF EXISTS `active_skill_target`;
CREATE TABLE IF NOT EXISTS `active_skill_target` (
`id` int(11) NOT NULL ,
`scene_id` int(11) COMMENT '1 联盟战
2 城战城门战
3 城战城内战',
`battle_skill_id` int(11) COMMENT '徐力丰:
城战主动技能id',
`battle_skill_name` varchar(512) COMMENT '徐力丰:
城战主动技能id',
`side` int(11) COMMENT '0 双方
1 攻击方
2 防守方',
`section_id` int(11) COMMENT '区域id
0--所有区域',
`target` varchar(512) COMMENT '武将统治力成长值',
`target_desc` varchar(512) COMMENT '武将统治力成长值',
`client_target_area` int(11) COMMENT '徐力丰:
目标区域
0：无法释放',
`client_description` int(11) ,
`client_desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
