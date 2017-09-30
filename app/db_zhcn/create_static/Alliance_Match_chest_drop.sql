--
-- Table structure for table `Alliance_Match_chest_drop`
--
DROP TABLE IF EXISTS `Alliance_Match_chest_drop`;
CREATE TABLE IF NOT EXISTS `Alliance_Match_chest_drop` (
`id` int(11) NOT NULL  COMMENT '作者:
联盟名次',
`rank` int(11) COMMENT '作者:
联盟获得的名次',
`item_id` int(11) COMMENT '作者:
物品id',
`max_count` int(11) COMMENT '作者:
物品数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
