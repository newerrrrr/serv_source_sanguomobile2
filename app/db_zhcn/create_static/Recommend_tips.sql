--
-- Table structure for table `Recommend_tips`
--
DROP TABLE IF EXISTS `Recommend_tips`;
CREATE TABLE IF NOT EXISTS `Recommend_tips` (
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
`open_type` text COMMENT '作者:
不同的触发条件',
`desc` int(11) ,
`desc1` varchar(512) ,
`to_target` int(11) COMMENT '作者:
支线任务关联主线
',
`skip_type` int(11) COMMENT '作者:
跳转类型
1=坐标
2=框
3=复活点
4=军团',
`skip_show` text COMMENT '作者:
跳转类型
1=坐标
2=区域编号，map_Map_Element中的origin_id
3=area id
',
`priority` int(11) COMMENT '作者:
任务显示优先级',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
