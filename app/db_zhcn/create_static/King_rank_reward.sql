--
-- Table structure for table `King_rank_reward`
--
DROP TABLE IF EXISTS `King_rank_reward`;
CREATE TABLE IF NOT EXISTS `King_rank_reward` (
`id` int(11) NOT NULL ,
`min_rank` int(11) ,
`max_rank` int(11) ,
`bonus` text COMMENT '作者:
DROP ID
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
