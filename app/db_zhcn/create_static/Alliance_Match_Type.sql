--
-- Table structure for table `Alliance_Match_Type`
--
DROP TABLE IF EXISTS `Alliance_Match_Type`;
CREATE TABLE IF NOT EXISTS `Alliance_Match_Type` (
`id` int(11) NOT NULL  COMMENT '先实现有颜色的功能点',
`type` int(11) COMMENT '作者:
1、联盟捐献
2、和氏璧
3、黄巾军
4、占塔--阿拉希
',
`name` varchar(512) ,
`desc` varchar(512) ,
`point` int(11) COMMENT '得分配置',
`language_id` int(11) ,
`desc1` varchar(512) ,
`settings` text COMMENT '参数配置',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
