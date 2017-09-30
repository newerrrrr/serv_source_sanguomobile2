--
-- Table structure for table `Alliance_science`
--
DROP TABLE IF EXISTS `Alliance_science`;
CREATE TABLE IF NOT EXISTS `Alliance_science` (
`id` int(11) NOT NULL ,
`name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
`level_type` int(11) COMMENT '陈涛:
联盟阶段
1-第一阶段
2-第二阶段
3-第三阶段',
`open_task` int(11) ,
`science_type` int(11) ,
`button1_cost_id` text ,
`button2_cost_id` text ,
`button3_cost_id` text ,
`buff` text COMMENT '陈涛:
对应alliance_buff表',
`buff_num_type` int(11) COMMENT '陈涛:
1-万分比
2-具体指',
`buff_num` int(11) ,
`next_buff_num` int(11) ,
`alliance_science_drop` int(11) ,
`star` int(11) ,
`max_star` int(11) ,
`level` int(11) ,
`show_lv` int(11) ,
`max_level` int(11) ,
`levelup_exp` int(11) ,
`button1_drop` text COMMENT '陈涛:
对应奖励
三个id分别对应三个按钮的奖励',
`button2_drop` text COMMENT '陈涛:
对应奖励
三个id分别对应三个按钮的奖励',
`button3_drop` text COMMENT '陈涛:
对应奖励
三个id分别对应三个按钮的奖励',
`button1_honor` int(11) COMMENT '公会荣誉
',
`button2_honor` int(11) COMMENT '公会荣誉
',
`button3_honor` int(11) COMMENT '公会荣誉
',
`button1_exp` int(11) ,
`button2_exp` int(11) ,
`button3_exp` int(11) ,
`up_time` int(11) COMMENT '陈涛:
升阶时间（秒）',
`icon_img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
