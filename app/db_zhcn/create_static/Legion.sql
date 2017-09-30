--
-- Table structure for table `Legion`
--
DROP TABLE IF EXISTS `Legion`;
CREATE TABLE IF NOT EXISTS `Legion` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1-军团
2-部队',
`in_legion` int(11) COMMENT '作者:
0 军团
1 1军团
2 2军团
3 3军团',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
