--
-- Table structure for table `Astrology`
--
DROP TABLE IF EXISTS `Astrology`;
CREATE TABLE IF NOT EXISTS `Astrology` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '徐力丰:
1 占星
2 天陨',
`drop_group` int(11) COMMENT '徐力丰:
掉落序号',
`drop_id` int(11) COMMENT '徐力丰:
drop id',
`min_count` int(11) COMMENT '徐力丰:
累计未抽到该drop的最小次数',
`max_count` int(11) COMMENT '徐力丰:
累计未抽到该drop的最大次数',
`chance` int(11) COMMENT '徐力丰:
万分比',
`Special_next_drop_group` int(11) COMMENT '徐力丰:
特殊的掉落组
优先执行
仅执行一次',
`next_drop_group` int(11) COMMENT '徐力丰:
若无掉落，则跳转下一个掉落包',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
