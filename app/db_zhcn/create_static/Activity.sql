--
-- Table structure for table `Activity`
--
DROP TABLE IF EXISTS `Activity`;
CREATE TABLE IF NOT EXISTS `Activity` (
`id` int(11) NOT NULL ,
`activity_name` int(11) COMMENT '作者:
最多7个字',
`name_dec` varchar(512) ,
`activity_desc` int(11) COMMENT '作者:
最多7个字',
`desc` varchar(512) ,
`date_type` int(11) COMMENT '作者:
1、永久性活动
2、时效性
3、开服性',
`open_date` int(11) ,
`close_date` int(11) ,
`show_open_date` int(11) COMMENT '作者:
活动重新开启间隔时间
天
1002限时比赛活动=隔X天后重新开启
1004充值礼包活动=隔X天后重新抽取',
`show_close_date` int(11) ,
`active_same` int(11) ,
`type_icon` int(11) ,
`drop` text ,
`interval` int(11) ,
`show_order` int(11) COMMENT '作者:
列表显示顺序',
`banner_show` int(11) ,
`path_type` int(11) COMMENT '作者:
运营活动入口区分',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
