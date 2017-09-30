--
-- Table structure for table `Alliance_build_description`
--
DROP TABLE IF EXISTS `Alliance_build_description`;
CREATE TABLE IF NOT EXISTS `Alliance_build_description` (
`id` int(11) NOT NULL ,
`element_id` int(11) ,
`count` int(11) COMMENT '作者:
第几个',
`need_alliance_science` int(11) ,
`open_condition` int(11) ,
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
