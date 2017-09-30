--
-- Table structure for table `Vip`
--
DROP TABLE IF EXISTS `Vip`;
CREATE TABLE IF NOT EXISTS `Vip` (
`id` int(11) NOT NULL ,
`vip_level` int(11) ,
`vip_exp` int(11) COMMENT '升到该级VIP所需点数
即升到VIP2所需经验为500',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
