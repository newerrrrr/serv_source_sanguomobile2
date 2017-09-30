--
-- Table structure for table `Build_position`
--
DROP TABLE IF EXISTS `Build_position`;
CREATE TABLE IF NOT EXISTS `Build_position` (
`id` int(11) NOT NULL ,
`build_id` text COMMENT '作者:
这个坑位可造建筑
多个建筑用分好隔开',
`build_type` int(11) COMMENT '类型
1-城内建筑
2-城下建筑、资源建筑',
`lvup_build_effect` text ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
