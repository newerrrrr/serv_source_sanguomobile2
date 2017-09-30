--
-- Table structure for table `Item_Combine`
--
DROP TABLE IF EXISTS `Item_Combine`;
CREATE TABLE IF NOT EXISTS `Item_Combine` (
`id` int(11) NOT NULL  COMMENT '目标道具ID',
`consume` text COMMENT '作者:
所需材料',
`target_equip` int(11) COMMENT '作者:
目标装备ID',
`count` int(11) COMMENT '作者:
数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
