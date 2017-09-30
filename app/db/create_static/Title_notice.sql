--
-- Table structure for table `Title_notice`
--
DROP TABLE IF EXISTS `Title_notice`;
CREATE TABLE IF NOT EXISTS `Title_notice` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1=战斗相关
2=招募武将
3=击杀BOSS
4=紫色以上品质装备进阶
5=皇帝当选走马灯
6=官职当选走马灯
7=囚犯当选走马灯
8=获得神武将信物
9=化神为神武将',
`desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
