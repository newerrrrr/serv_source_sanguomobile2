--
-- Table structure for table `Embassy`
--
DROP TABLE IF EXISTS `Embassy`;
CREATE TABLE IF NOT EXISTS `Embassy` (
`id` int(11) NOT NULL ,
`build_id` int(11) COMMENT '作者:
建筑ID',
`help_num` int(11) COMMENT '作者:
帮助次数',
`help_time` int(11) COMMENT '作者:
帮助缩短时间',
`help_soldiers_num` int(11) COMMENT '作者:
援兵数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
