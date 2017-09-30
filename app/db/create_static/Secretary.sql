--
-- Table structure for table `Secretary`
--
DROP TABLE IF EXISTS `Secretary`;
CREATE TABLE IF NOT EXISTS `Secretary` (
`id` int(11) NOT NULL ,
`target_group` int(11) ,
`level` int(11) ,
`type` int(11) ,
`condition` int(11) ,
`condition_level` int(11) ,
`hint_text` int(11) COMMENT '名称
',
`desc1` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
