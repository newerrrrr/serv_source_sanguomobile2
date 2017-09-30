--
-- Table structure for table `Master_attribute`
--
DROP TABLE IF EXISTS `Master_attribute`;
CREATE TABLE IF NOT EXISTS `Master_attribute` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1-战斗力
2-战斗状态
3-军事
4-资源
5-城市发展
6-城防',
`name` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
