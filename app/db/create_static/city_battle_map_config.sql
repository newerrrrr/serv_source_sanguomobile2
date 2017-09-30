--
-- Table structure for table `city_battle_map_config`
--
DROP TABLE IF EXISTS `city_battle_map_config`;
CREATE TABLE IF NOT EXISTS `city_battle_map_config` (
`id` int(11) NOT NULL ,
`map_type` int(11) COMMENT '作者:
类型
1=城门战
2=内城战',
`area` int(11) COMMENT '区域根据TYPE类型不同性质不同
type=1时，区域与跨服战的性质相同

type=2时，区域表示各个占领区域',
`part` int(11) COMMENT '作者:
类型
1=城门战
2=内城战',
`section` int(11) COMMENT '作者:
小区域
城门战的123代表魏蜀吴的攻城时的位置

城门战
123代表魏蜀吴的攻城时的位置
456对饮城内的位置


城内战
12345=每个区域
6=攻方默认
7=守方默认',
`x` int(11) ,
`y` int(11) ,
`sides_type` int(11) COMMENT '作者:
1攻击
2防守
',
`city_battle_map_element_id` int(11) COMMENT '作者:
map_element id',
`max_durability` int(11) COMMENT '作者:
部分建筑物的血量',
`next_area` int(11) COMMENT '作者:
可开启下一个区域',
`target_area` int(11) ,
`target_map_element_id` int(11) COMMENT '作者:
攻击目标

',
`build_num` int(11) COMMENT '作者:
同类型建筑物编号
',
`memo` varchar(512) COMMENT '作者:
批注',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
