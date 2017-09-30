--
-- Table structure for table `Country_recommend_tips`
--
DROP TABLE IF EXISTS `Country_recommend_tips`;
CREATE TABLE IF NOT EXISTS `Country_recommend_tips` (
`id` int(11) NOT NULL ,
`location` int(11) COMMENT '作者:
1=攻方
2=守方',
`path_type` int(11) COMMENT '作者:
1=A路线
2=B路线
',
`task_type` int(11) COMMENT '作者:
1=主线
2=支线',
`camp` int(11) COMMENT '作者:
不同的触发条件',
`open_type` text COMMENT '陆阳:
1=城门战
2=城内战',
`desc` int(11) ,
`desc1` varchar(512) ,
`to_target` int(11) ,
`skip_type` int(11) ,
`skip_show` text ,
`priority` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
