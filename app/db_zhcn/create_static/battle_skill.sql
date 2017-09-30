--
-- Table structure for table `battle_skill`
--
DROP TABLE IF EXISTS `battle_skill`;
CREATE TABLE IF NOT EXISTS `battle_skill` (
`id` int(11) NOT NULL ,
`battle_skill_id` int(11) ,
`skill_name` int(11) ,
`skill_name1` varchar(512) ,
`skill_description` int(11) ,
`skill_desc1` varchar(512) ,
`skill_description_preview` int(11) ,
`skill_desc2` varchar(512) ,
`battle_type_id` int(11) COMMENT '徐力丰:
技能类型ID',
`if_active` int(11) COMMENT '徐力丰:
是否主动技能
1是
0否',
`battle_skill_defalut_level` int(11) ,
`skill_res` int(11) COMMENT '陆阳:
发起动作资源
',
`value_formula` varchar(512) ,
`num_type` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`value_max` int(11) COMMENT '徐力丰:
技能数值累加之后的最大值',
`value_formula_2` varchar(512) ,
`num_type2` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`backup` varchar(512) ,
`client_formula` varchar(512) COMMENT '徐力丰:
普通攻击或技能的伤害数值计算公式',
`client_formula_2` varchar(512) COMMENT '徐力丰:
普通攻击或技能的伤害数值计算公式',
`combat_info` int(11) ,
`desc3` varchar(512) ,
`buff_type_exclude` int(11) COMMENT '陆阳:
如一些特殊的BUFF单独显示，需要剔除
如关羽武力最高，则显示全盟获得武力增加的技能
0 客户端自己计算
1 服务器给数据
2 不显示
3 主动技
',
`active_skill_area_desc` int(11) COMMENT '陆阳:
主动技能是否区域显示：',
`general_limit` text COMMENT '徐力丰:
可获得该技能的general_original_id
空=所有武将都可以获得',
`refresh_weight` int(11) COMMENT '徐力丰:
洗练时出现该技能的权重',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
