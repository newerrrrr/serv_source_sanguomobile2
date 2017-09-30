--
-- Table structure for table `Quick_bug`
--
DROP TABLE IF EXISTS `Quick_bug`;
CREATE TABLE IF NOT EXISTS `Quick_bug` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '陆阳:
1=粮食
2=木材
3=黄金
4=石块
5=铁块',
`shop_id` text ,
`min_level` int(11) COMMENT '徐力丰:
该项目显示的最小府衙等级',
`max_level` int(11) COMMENT '徐力丰:
该项目显示的最大府衙等级
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
