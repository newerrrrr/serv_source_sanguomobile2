--
-- Table structure for table `duel_buff`
--
DROP TABLE IF EXISTS `duel_buff`;
CREATE TABLE IF NOT EXISTS `duel_buff` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '徐力丰:
1 对自身buff
2 对敌方debuff',
`buff_name` int(11) ,
`buff_name1` varchar(512) ,
`buff_description` int(11) ,
`buff_desc1` varchar(512) ,
`num_type` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`base` int(11) COMMENT '徐力丰:
基础值',
`para1` int(11) COMMENT '徐力丰:
等级系数',
`para2` int(11) COMMENT '徐力丰:
属性系数',
`client_formula` varchar(512) COMMENT '徐力丰:
技能数值公式',
`buff_res` int(11) COMMENT '陆阳:
资源',
`buff_ae` int(11) COMMENT '陆阳:
音效',
`round_formula` int(11) COMMENT '徐力丰:
技能持续时间',
`debuff_tips` int(11) COMMENT '陆阳:
控制技能票字
如；被眩晕，直接弹出你当前回合被眩晕无法移动',
`client_formula_backup` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
