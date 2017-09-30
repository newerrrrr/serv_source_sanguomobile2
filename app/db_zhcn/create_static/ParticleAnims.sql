--
-- Table structure for table `ParticleAnims`
--
DROP TABLE IF EXISTS `ParticleAnims`;
CREATE TABLE IF NOT EXISTS `ParticleAnims` (
`id` int(11) NOT NULL ,
`folder` varchar(512) ,
`name` varchar(512) ,
`offsetx` int(11) ,
`offsety` int(11) ,
`duration` int(11) COMMENT '作者:
时间
',
`isloop` int(11) COMMENT '作者:
0 不循环
1 循环',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
