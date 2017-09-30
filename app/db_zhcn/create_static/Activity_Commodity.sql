--
-- Table structure for table `Activity_Commodity`
--
DROP TABLE IF EXISTS `Activity_Commodity`;
CREATE TABLE IF NOT EXISTS `Activity_Commodity` (
`id` int(11) NOT NULL ,
`activity_id` int(11) ,
`series` int(11) COMMENT '陆阳:
编组，同ID为一个类型的活动礼包',
`show_priority` int(11) COMMENT '陆阳:
BANNER图弹出优先级',
`series_order` int(11) COMMENT '陆阳:
礼包购买顺序',
`gift_type` varchar(512) COMMENT '对应pricing中的gift_type,用于对应充值项',
`drop_condition` int(11) COMMENT '累计充值使用
',
`drop_id` int(11) COMMENT '
掉落ID
',
`open_time` int(11) COMMENT '充值项开启时间
Unix时间戳',
`close_time` int(11) COMMENT '充值项关闭时间',
`activity_type` int(11) COMMENT '
活动类型
',
`act_same_index` int(11) ,
`guild_drop_id` int(11) COMMENT '徐力丰:
公会成员获取掉落
0 不发公会成员
',
`show_price` int(11) ,
`ratio` int(11) COMMENT '徐力丰:
用于客户端显示礼包价值比例 百分比
例如200 则显示200%',
`desc` int(11) ,
`language` varchar(512) ,
`day_limit` int(11) COMMENT '该礼包仅在开服一定天数内才显示，仅对activity_id为1005的生效 0表示永久显示
',
`gift_icon` int(11) COMMENT '陆阳:
礼包界面Banner条
',
`gift_banner` int(11) COMMENT '陆阳:
进入游戏弹出BANNER
',
`priority` int(11) COMMENT '陆阳:
进入游戏BANNER优先级',
`desc2` int(11) COMMENT '陆阳:
限购一次显示',
`gift_show_icon` int(11) COMMENT '陆阳:
每个礼包外面显示的ICON
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
