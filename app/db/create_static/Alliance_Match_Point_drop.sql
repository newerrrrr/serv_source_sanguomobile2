--
-- Table structure for table `Alliance_Match_Point_drop`
--
DROP TABLE IF EXISTS `Alliance_Match_Point_drop`;
CREATE TABLE IF NOT EXISTS `Alliance_Match_Point_drop` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1、达到积分奖励
2、排名奖励
3、总排名奖励',
`min_point` int(11) ,
`max_point` int(11) ,
`drop` int(11) ,
`Alliance_honor_drop` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
