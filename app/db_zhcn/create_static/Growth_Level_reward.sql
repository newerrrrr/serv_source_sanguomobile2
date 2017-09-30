--
-- Table structure for table `Growth_Level_reward`
--
DROP TABLE IF EXISTS `Growth_Level_reward`;
CREATE TABLE IF NOT EXISTS `Growth_Level_reward` (
`id` int(11) NOT NULL  COMMENT '府衙等级-奖励表

',
`level` int(11) COMMENT '府衙等级',
`drop` int(11) COMMENT '奖励dropid',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
