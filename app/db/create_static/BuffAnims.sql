--
-- Table structure for table `BuffAnims`
--
DROP TABLE IF EXISTS `BuffAnims`;
CREATE TABLE IF NOT EXISTS `BuffAnims` (
`id` int(11) NOT NULL ,
`path` varchar(512) COMMENT '作者:
技能特效位置',
`desc` varchar(512) COMMENT '作者:
类型
1=普攻
2=技能
3=BUFF',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
