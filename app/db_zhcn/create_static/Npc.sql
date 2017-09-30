--
-- Table structure for table `Npc`
--
DROP TABLE IF EXISTS `Npc`;
CREATE TABLE IF NOT EXISTS `Npc` (
`id` int(11) NOT NULL ,
`monster_type` int(11) COMMENT '作者:
1=普通
2=组队怪
3=守兵
4=国王战派兵（小）
5=国王战派兵（大）
6=野外boss
7=玉玺争夺',
`monster_name` int(11) COMMENT '怪物名字',
`desc1` varchar(512) ,
`monster_desc` int(11) COMMENT '作者:
描述',
`desc2` varchar(512) ,
`monster_lv` int(11) ,
`attack` int(11) COMMENT '作者:
攻击',
`defense` int(11) COMMENT '作者:
防御（就是血量）
boss防御设0

',
`life` int(11) ,
`number` int(11) COMMENT '作者:
怪物数量',
`drop` text COMMENT '作者:
怪物掉落
boss击杀掉落',
`img` int(11) COMMENT '作者:
半身像',
`img_mail` int(11) COMMENT '作者:
邮件头像
',
`precondition` int(11) COMMENT '作者:
前置怪物',
`power` int(11) COMMENT '作者:
战斗力：国王战时时计算国王战胜利条件的一个积分值',
`hp_ratio` int(11) COMMENT '实际计算时的defense=defense*ratio
life=life*ratio
',
`drop_show` text COMMENT 'npc有可能掉落的item id列表
0为无掉落
',
`recommand_power` int(11) COMMENT '推荐战力',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
