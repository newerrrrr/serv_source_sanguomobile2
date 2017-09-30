--
-- Table structure for table `Sign_Award`
--
DROP TABLE IF EXISTS `Sign_Award`;
CREATE TABLE IF NOT EXISTS `Sign_Award` (
`id` int(11) NOT NULL ,
`get_day` int(11) COMMENT '作者:
每次领取奖励时间',
`drop` text COMMENT '作者:
奖励内容',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
