--
-- Table structure for table `Alliance_quest`
--
DROP TABLE IF EXISTS `Alliance_quest`;
CREATE TABLE IF NOT EXISTS `Alliance_quest` (
`id` int(11) NOT NULL ,
`country_id` int(11) COMMENT '徐力丰:
0三国通用
1魏国
2蜀国
3吴国
',
`step_id` int(11) ,
`name` int(11) ,
`desc1` varchar(512) ,
`alliance_quest_type` int(11) ,
`num_value` int(11) COMMENT '徐力丰:
完成任务所需的数值配置
任务类型为3时 该字段是城市id',
`alliance_quest_reward` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
