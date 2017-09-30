--
-- Table structure for table `Guide`
--
DROP TABLE IF EXISTS `Guide`;
CREATE TABLE IF NOT EXISTS `Guide` (
`id` int(11) NOT NULL ,
`steps` text ,
`need_level` int(11) COMMENT '作者:
需要主公等级',
`close_type` int(11) COMMENT '作者:
用于引导过程中退出游戏。
0：全部完成后结束（最后一步操作后生效）
1：第一步出现后立刻结束',
`build_ids` text COMMENT '作者:
需要建筑Id（带等级信息的Id，可多个）',
`science_ids` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
