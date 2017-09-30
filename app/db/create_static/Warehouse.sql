--
-- Table structure for table `Warehouse`
--
DROP TABLE IF EXISTS `Warehouse`;
CREATE TABLE IF NOT EXISTS `Warehouse` (
`id` int(11) NOT NULL  COMMENT '作者:
仓库',
`build_id` int(11) COMMENT '作者:
建筑等级',
`gold` int(11) ,
`grain` int(11) COMMENT '作者:
粮食',
`wood` int(11) COMMENT '作者:
木材',
`iron` int(11) COMMENT '作者:
铁',
`stone` int(11) COMMENT '作者:
石材',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
