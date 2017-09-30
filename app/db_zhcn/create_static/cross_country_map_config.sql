--
-- Table structure for table `cross_country_map_config`
--
DROP TABLE IF EXISTS `cross_country_map_config`;
CREATE TABLE IF NOT EXISTS `cross_country_map_config` (
`id` int(11) NOT NULL ,
`map_type` int(11) COMMENT '作者:
类型
1=城门战
2=内城战',
`area` int(11) COMMENT '区域根据TYPE类型不同性质不同
type=1时，区域与跨服战的性质相同

type=2时，区域表示各个占领区域',
`section` int(11) COMMENT '作者:
小区域
',
`x` int(11) ,
`y` int(11) ,
`sides_type` int(11) COMMENT '作者:
1攻击
2防守
',
`cross_map_element_id` int(11) COMMENT '作者:
map_element id',
`max_durability` int(11) COMMENT '作者:
部分建筑物的血量',
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
