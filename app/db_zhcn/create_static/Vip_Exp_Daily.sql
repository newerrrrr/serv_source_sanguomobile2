--
-- Table structure for table `Vip_Exp_Daily`
--
DROP TABLE IF EXISTS `Vip_Exp_Daily`;
CREATE TABLE IF NOT EXISTS `Vip_Exp_Daily` (
`id` int(11) NOT NULL ,
`vip_level` int(11) ,
`if_vip_actived` int(11) COMMENT 'Vip是否激活状态
1是
0否',
`continue_sign_days` int(11) COMMENT '连续签到天数',
`vipexp` int(11) COMMENT 'vip经验',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
