--
-- Table structure for table `item_acceleration`
--
DROP TABLE IF EXISTS `item_acceleration`;
CREATE TABLE IF NOT EXISTS `item_acceleration` (
`id` int(11) NOT NULL ,
`desc1` varchar(512) ,
`type` int(11) COMMENT '陈涛:
0-通用道具
1-建筑加速
2-造兵加速
3-医疗加速
4-研究加速
5-早陷阱加速',
`item_num` int(11) COMMENT '陈涛:
时间：秒
',
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
