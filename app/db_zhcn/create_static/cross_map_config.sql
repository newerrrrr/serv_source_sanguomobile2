--
-- Table structure for table `cross_map_config`
--
DROP TABLE IF EXISTS `cross_map_config`;
CREATE TABLE IF NOT EXISTS `cross_map_config` (
`id` int(11) NOT NULL ,
`map_type` int(11) COMMENT '作者:
不同的地图',
`area` int(11) COMMENT '作者:
区域分块：
1=最外围
2=中间
3=里面
4=皇宫位',
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
`next_area` int(11) COMMENT '作者:
可开启下一个区域',
`target_area` int(11) COMMENT '作者:
攻击目标

',
`build_num` int(11) COMMENT '作者:
同类型建筑物编号
',
`memo` varchar(512) COMMENT '作者:
批注',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
