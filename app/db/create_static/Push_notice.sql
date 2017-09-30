--
-- Table structure for table `Push_notice`
--
DROP TABLE IF EXISTS `Push_notice`;
CREATE TABLE IF NOT EXISTS `Push_notice` (
`id` int(11) NOT NULL ,
`title` int(11) COMMENT '作者:
1. 升级、训练完成提醒
2. 战斗提醒
3. 活动提醒
4. 部队返回
5. 玩家发起集结',
`desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
