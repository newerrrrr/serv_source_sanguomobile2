--
-- Table structure for table `Fail_save_reward`
--
DROP TABLE IF EXISTS `Fail_save_reward`;
CREATE TABLE IF NOT EXISTS `Fail_save_reward` (
`id` int(11) NOT NULL ,
`reward_type` int(11) COMMENT '作者:
1.每次都生效
2.仅生效一次',
`level_max` int(11) COMMENT '该补偿生效的最大府衙等级',
`power_min` int(11) COMMENT '单次，被攻击
损失战力最小值（仅统计部队损失）',
`power_max` int(11) COMMENT '单次被攻击损失战力最大值',
`drop` text COMMENT '作者:
奖励',
`language_id` int(11) COMMENT '多语言id',
`desc1` varchar(512) COMMENT '邮件文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
