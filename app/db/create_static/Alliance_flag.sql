--
-- Table structure for table `Alliance_flag`
--
DROP TABLE IF EXISTS `Alliance_flag`;
CREATE TABLE IF NOT EXISTS `Alliance_flag` (
`id` int(11) NOT NULL  COMMENT '陆阳:
1开头为旗子颜色
2开头为旗子样式
3开头为旗子花纹',
`type` int(11) COMMENT '陆阳:
1为旗子
2为特效',
`res_flag` int(11) COMMENT '陆阳:
工会图标资源填写RES ID
',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
