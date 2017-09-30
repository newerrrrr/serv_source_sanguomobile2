--
-- Table structure for table `GeneralAnims`
--
DROP TABLE IF EXISTS `GeneralAnims`;
CREATE TABLE IF NOT EXISTS `GeneralAnims` (
`id` int(11) NOT NULL ,
`path_1` varchar(512) COMMENT '作者:
正面武将',
`path_2` varchar(512) COMMENT '作者:
正面武将',
`desc1` varchar(512) ,
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
