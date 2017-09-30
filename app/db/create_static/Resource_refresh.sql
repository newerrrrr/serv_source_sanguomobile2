--
-- Table structure for table `Resource_refresh`
--
DROP TABLE IF EXISTS `Resource_refresh`;
CREATE TABLE IF NOT EXISTS `Resource_refresh` (
`id` int(11) NOT NULL ,
`distance_max` int(11) COMMENT '距离中心点最大距离',
`map_element_id` int(11) COMMENT '地图元素id',
`weight` int(11) COMMENT '作者:
刷新权重',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
