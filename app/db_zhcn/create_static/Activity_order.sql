--
-- Table structure for table `Activity_order`
--
DROP TABLE IF EXISTS `Activity_order`;
CREATE TABLE IF NOT EXISTS `Activity_order` (
`id` int(11) NOT NULL ,
`if_circle` int(11) COMMENT '徐力丰:
0 开服礼包，不参与循环
1 循环
每一组礼包出售时间为acitvity  inteval字段决定 ',
`series` text COMMENT '陆阳:
活动礼包id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
