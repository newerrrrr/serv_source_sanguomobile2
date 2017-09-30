--
-- Table structure for table `Shop`
--
DROP TABLE IF EXISTS `Shop`;
CREATE TABLE IF NOT EXISTS `Shop` (
`id` int(11) NOT NULL  COMMENT '陈涛:
1-商店
2-锦囊商城',
`shop_type` int(11) COMMENT '1-商城
2-锦囊商店
3-跨服城战商店',
`type` int(11) COMMENT '陈涛:
1-资源
2-战争
3-增益
4-热卖
5-功勋
用于普通商城中商品分类
6:锦囊',
`priority` int(11) COMMENT '前端显示排序',
`commodity_data` int(11) COMMENT '对应drop表drop_id',
`buy_daily_limit` int(11) COMMENT '陆阳:
基础购买次数
无限次=-1
跨服城战商店刷新后的购买次数',
`cost_id` int(11) ,
`show_price` int(11) COMMENT '陈涛:
展示价格
0-不显示展示价格',
`desc1` varchar(512) ,
`if_onsale` int(11) COMMENT '徐力丰:
1=上架，客户端显示
0=下架，客户端不显示',
`min_level` int(11) COMMENT '徐力丰:
该项目显示的最小府衙等级',
`max_level` int(11) COMMENT '徐力丰:
该项目显示的最大府衙等级
',
`city_id` int(11) COMMENT '徐力丰:
城战城市id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
