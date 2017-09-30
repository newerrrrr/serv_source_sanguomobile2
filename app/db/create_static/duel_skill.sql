--
-- Table structure for table `duel_skill`
--
DROP TABLE IF EXISTS `duel_skill`;
CREATE TABLE IF NOT EXISTS `duel_skill` (
`id` int(11) NOT NULL ,
`duel_skill_id` int(11) ,
`skill_name` int(11) ,
`skill_name1` varchar(512) ,
`skill_description` int(11) ,
`skill_desc1` varchar(512) ,
`skill_description_preview` int(11) ,
`skill_desc2` varchar(512) ,
`weapon_type` varchar(512) COMMENT '徐力丰:
武器类型
1 短刀
2 长柄
3 远程',
`type` int(11) COMMENT '徐力丰:
1普攻
2技能',
`long_distance` int(11) ,
`short_distance` int(11) ,
`range` int(11) ,
`damage` int(11) ,
`skill_src_res` int(11) COMMENT '陆阳:
发起动作资源
',
`skill_src_ae` int(11) COMMENT '陆阳:
发起方音效
',
`skill_orbit_res` int(11) COMMENT '陆阳:
技能轨迹资源',
`skill_orbit_ae` int(11) COMMENT '陆阳:
技能轨迹资源',
`skill_dst_res` int(11) COMMENT '陆阳:
受击资源
',
`skill_dst_ae` int(11) COMMENT '陆阳:
受击资源
',
`skill_word_res` int(11) COMMENT '陆阳:
技能名',
`skill_need_sp` int(11) ,
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
`client_formula` varchar(512) COMMENT '徐力丰:
普通攻击或技能的伤害数值计算公式',
`client_buff_formula` varchar(512) COMMENT '徐力丰:
普通攻击或技能附带的buff数值，固定值',
`duel_buff_self_1` text COMMENT '徐力丰:
伤害结算前生效的自身buff
',
`duel_buff_self_2` text COMMENT '徐力丰:
伤害结算后生效的自身buff
',
`duel_buff_enemy_1` text COMMENT '徐力丰:
伤害结算前生效的敌对buff
',
`duel_buff_enemy_2` text COMMENT '徐力丰:
伤害结算后生效的敌对buff
',
`combat_info` int(11) ,
`desc3` varchar(512) ,
`atk_ae` int(11) COMMENT '陆阳:
普通攻击音效

',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
