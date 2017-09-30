--
-- Table structure for table `Boss_refresh`
--
DROP TABLE IF EXISTS `Boss_refresh`;
CREATE TABLE IF NOT EXISTS `Boss_refresh` (
`id` int(11) NOT NULL ,
`day` int(11) COMMENT '作者:
服务器开启时间（24小时制）',
`boss_id` int(11) COMMENT '作者:
boss等级',
`weight` int(11) COMMENT '作者:
怪物刷新权重',
`ifrespawn` int(11) COMMENT '怪物若未被攻击，是否重刷
0 不重刷
1 重刷',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
