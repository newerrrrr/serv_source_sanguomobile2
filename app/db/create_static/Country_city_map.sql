--
-- Table structure for table `Country_city_map`
--
DROP TABLE IF EXISTS `Country_city_map`;
CREATE TABLE IF NOT EXISTS `Country_city_map` (
`id` int(11) NOT NULL ,
`city_type` int(11) COMMENT '陆阳:
1=出生地
2=可攻击打城池
3=可攻击小城池',
`ctiy_name` int(11) ,
`desc` varchar(512) ,
`city_pic` int(11) ,
`city_bg_pic` int(11) ,
`link` text COMMENT '陆阳:
城池连线
在此位置可进攻的城池',
`point` int(11) COMMENT '陆阳:
占领城池获得积分
',
`join_max_num` int(11) COMMENT '陆阳:
每个国家最多加入的人数',
`default_belong` int(11) COMMENT '陆阳:
type=1时，默认归属国家',
`drop` int(11) COMMENT '陆阳:
城池固定奖励
',
`shop_position` text COMMENT '陆阳:
各个城池商城位置坐标',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
