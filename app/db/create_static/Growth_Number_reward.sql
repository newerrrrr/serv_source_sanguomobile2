--
-- Table structure for table `Growth_Number_reward`
--
DROP TABLE IF EXISTS `Growth_Number_reward`;
CREATE TABLE IF NOT EXISTS `Growth_Number_reward` (
`id` int(11) NOT NULL  COMMENT '购买人数-奖励表

',
`number` int(11) COMMENT '总计购买人数',
`drop` int(11) COMMENT '奖励dropid',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
