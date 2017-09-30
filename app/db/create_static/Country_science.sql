--
-- Table structure for table `Country_science`
--
DROP TABLE IF EXISTS `Country_science`;
CREATE TABLE IF NOT EXISTS `Country_science` (
`id` int(11) NOT NULL ,
`name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
`science_type` int(11) ,
`button1_consume` text COMMENT '徐力丰:
按钮1消耗
0=不显示该按钮',
`button2_consume` text ,
`button3_consume` text ,
`num_type` int(11) COMMENT '
1-万分比
2-具体值',
`num_value` int(11) ,
`buff` text COMMENT '陈涛:
对应alliance_buff表',
`level` int(11) ,
`max_level` int(11) ,
`levelup_exp` int(11) ,
`button1_drop` text COMMENT '徐力丰:
按钮1军资奖励',
`button2_drop` text ,
`button3_drop` text ,
`button1_exp` int(11) COMMENT '徐力丰:
按钮1科技经验',
`button2_exp` int(11) ,
`button3_exp` int(11) ,
`icon_img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
