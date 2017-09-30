--
-- Table structure for table `Alliance_shop`
--
DROP TABLE IF EXISTS `Alliance_shop`;
CREATE TABLE IF NOT EXISTS `Alliance_shop` (
`id` int(11) NOT NULL  COMMENT '陈涛:
1-商店
2-联盟商店（跟后面的item_id一致）',
`item_id` int(11) ,
`alliance_cost` int(11) ,
`player_cost` int(11) ,
`count` int(11) COMMENT '陈涛:
数量',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
