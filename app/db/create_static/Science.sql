--
-- Table structure for table `Science`
--
DROP TABLE IF EXISTS `Science`;
CREATE TABLE IF NOT EXISTS `Science` (
`id` int(11) NOT NULL  COMMENT '陈涛:
除以100得到对应科技编号
对100求余得到当前等级',
`type` int(11) COMMENT '陈涛:
1-军事
2-发展',
`science_type_id` int(11) COMMENT '陈涛:
对应科技种类编号',
`level_id` int(11) ,
`max_level` int(11) ,
`buff_num_type` int(11) COMMENT '陈涛:
1-万分比
2-具体值
',
`buff_num` int(11) COMMENT '陈涛:
buff数值',
`max_buff_num` int(11) ,
`science_drop` int(11) ,
`name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) COMMENT '陈涛:
介绍',
`desc2` varchar(512) ,
`condition_science` text COMMENT '陈涛:
前一个科技id
多个条件时用分号隔开',
`next_science` int(11) ,
`build_level` int(11) COMMENT '陈涛:
开启前置条件',
`cost` text COMMENT '升级消耗
格式：
道具ID,数量（中间用分号隔开）
1-黄金
2-粮食
3-木材
4-石矿
5-铁矿',
`power` int(11) COMMENT '陈涛:
战力',
`need_time` int(11) COMMENT '陈涛:
升级所需时间/秒',
`gem_cost` int(11) COMMENT '陈涛:
清CD所需元宝',
`img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
