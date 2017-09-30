--
-- Table structure for table `Time_Limit_Match_Type`
--
DROP TABLE IF EXISTS `Time_Limit_Match_Type`;
CREATE TABLE IF NOT EXISTS `Time_Limit_Match_Type` (
`id` int(11) NOT NULL  COMMENT '先实现有颜色的功能点',
`type` int(11) COMMENT '作者:
1、采集资源
2、攻打怪物
3、提升战力
4、城堡发展
5、训练士兵
6、神秘商人
7、锻造大师
8、搜集宝物
9、鏖战沙场',
`name` varchar(512) ,
`desc` varchar(512) ,
`point` int(11) COMMENT '作者:
此类项目得分',
`language_id` int(11) ,
`help_type` int(11) ,
`type_desc` int(11) ,
`desc1` varchar(512) ,
`match_show` int(11) COMMENT '作者:
活动展示图',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
