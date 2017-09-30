--
-- Table structure for table `Help_type`
--
DROP TABLE IF EXISTS `Help_type`;
CREATE TABLE IF NOT EXISTS `Help_type` (
`id` int(11) NOT NULL  COMMENT '作者:
1-联盟任务-和氏璧
2-联盟任务-联盟捐献
3-限时比赛-采集阶段
4-限时比赛-建设、科研阶段
5-限时比赛-造兵、陷阱阶段
6-限时比赛-打野、BOSS阶段
7-限时比赛-杀人阶段
8-城墙
9-联盟商店
10-联盟捐赠
11-屯所士兵援助
',
`title` int(11) ,
`desc2` varchar(512) COMMENT '描述文字',
`description` int(11) COMMENT '描述文字',
`desc1` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
