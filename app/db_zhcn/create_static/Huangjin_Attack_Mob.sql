--
-- Table structure for table `Huangjin_Attack_Mob`
--
DROP TABLE IF EXISTS `Huangjin_Attack_Mob`;
CREATE TABLE IF NOT EXISTS `Huangjin_Attack_Mob` (
`id` int(11) NOT NULL  COMMENT '作者:
即波次',
`type_and_count` text COMMENT '作者:
兵种id,数量;',
`power_score_rate` int(11) COMMENT '击杀的黄巾军战力/rate=获得的积分
向下取整',
`drop` int(11) COMMENT '作者:
过关掉落，仅发最后一个',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
