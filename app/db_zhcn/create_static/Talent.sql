--
-- Table structure for table `Talent`
--
DROP TABLE IF EXISTS `Talent`;
CREATE TABLE IF NOT EXISTS `Talent` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '陈涛:
1-军事
2-经济
3-城防',
`talent_type_id` int(11) ,
`level_id` int(11) ,
`max_level` int(11) ,
`buff_num_type` int(11) COMMENT '陈涛:
1-万分比
2-具体值',
`buff_num` int(11) COMMENT '陈涛:
buff数值
',
`max_buff_num` int(11) COMMENT '陈涛:
当前等级max的buff数值',
`talent_drop` text ,
`talent_name` int(11) ,
`desc1` varchar(512) ,
`talent_text` int(11) COMMENT '陈涛:
天赋介绍',
`desc2` varchar(512) ,
`condition_talent` text COMMENT '陈涛:
开启前置条件ID
或的关系，中间用;隔开
0-默认开启
',
`next_talent` int(11) COMMENT '陈涛:
下一级天赋ID
-1 代表结束，没有下一个',
`master_level` int(11) ,
`cost` int(11) COMMENT '陈涛:
消耗技能点数',
`power` int(11) COMMENT '陈涛:
该天赋点数对应战斗力',
`img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
