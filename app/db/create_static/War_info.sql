--
-- Table structure for table `War_info`
--
DROP TABLE IF EXISTS `War_info`;
CREATE TABLE IF NOT EXISTS `War_info` (
`id` int(11) NOT NULL ,
`type_name` varchar(512) ,
`info_desc` int(11) ,
`info_desc1` int(11) COMMENT '作者:
大走马灯',
`info_desc2` int(11) COMMENT '作者:
特殊情况需要目标方得知消息的文字
走马灯',
`info_type` int(11) COMMENT '作者:

0或者不填是加到右上角提示 
1是所有人屏幕中间上浮 
2是仅自己屏幕中间上浮',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
