--
-- Table structure for table `Online_Award`
--
DROP TABLE IF EXISTS `Online_Award`;
CREATE TABLE IF NOT EXISTS `Online_Award` (
`id` int(11) NOT NULL ,
`award_count` int(11) COMMENT '作者:
第几次的奖励
',
`get_time` int(11) COMMENT '作者:
每次领取奖励时间',
`drop` text COMMENT '作者:
奖励内容',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
