--
-- Table structure for table `Collection_drop`
--
DROP TABLE IF EXISTS `Collection_drop`;
CREATE TABLE IF NOT EXISTS `Collection_drop` (
`id` int(11) NOT NULL ,
`collection_min` int(11) COMMENT '作者:
采集数
黄金*10
粮食*1
木材*5
石材*25
铁材*50
',
`collection_max` int(11) ,
`collection_drop` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
