--
-- Table structure for table `market`
--
DROP TABLE IF EXISTS `market`;
CREATE TABLE IF NOT EXISTS `market` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '陈涛:
1-普通商品
2-当日特卖',
`type_chance` int(11) COMMENT '刷新权重',
`commodity_data` int(11) COMMENT '对应drop表drop_id',
`if_onsale` int(11) COMMENT '折扣比例，(前端可用于判断显示商品外发光颜色）
0-无折扣
1-9折
2-8折
3-7折',
`cost_id` int(11) COMMENT '无折扣时的价格',
`show_price` int(11) COMMENT '元宝折扣价，仅当costid 为元宝时生效',
`desc1` varchar(512) ,
`min_level` int(11) COMMENT '徐力丰:
该项目显示的最小府衙等级',
`max_level` int(11) COMMENT '徐力丰:
该项目显示的最大府衙等级
',
`refresh_control_id` int(11) COMMENT '徐力丰:
该字段用于控制集市中不再刷新与特惠商品相同的商品',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
