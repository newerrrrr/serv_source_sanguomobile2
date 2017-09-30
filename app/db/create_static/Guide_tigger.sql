--
-- Table structure for table `Guide_tigger`
--
DROP TABLE IF EXISTS `Guide_tigger`;
CREATE TABLE IF NOT EXISTS `Guide_tigger` (
`id` int(11) NOT NULL ,
`steps` text ,
`need_level` int(11) COMMENT '作者:
需要主公等级',
`close_type` int(11) COMMENT '作者:
用于引导过程中退出游戏。
0：全部完成后结束（最后一步操作后生效）
1：第一步出现后立刻结束',
`build_ids` text COMMENT '作者:
需要建筑Id（带等级信息的Id，可多个）',
`science_ids` text ,
`creation_alliance` int(11) ,
`join_alliance` int(11) ,
`activity_ids` text ,
`item_ids` text COMMENT '作者:
包含这个武将才触发引导
item_id
',
`general_ids` text COMMENT '作者:
拥有指定武将触发引导',
`need_general_num` int(11) COMMENT '作者:
武将招募X个触发
',
`priority` int(11) COMMENT '作者:
同时引导触发，优先级
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
