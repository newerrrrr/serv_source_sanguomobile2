--
-- Table structure for table `Pub`
--
DROP TABLE IF EXISTS `Pub`;
CREATE TABLE IF NOT EXISTS `Pub` (
`id` int(11) NOT NULL ,
`first_drop` text ,
`ordinary_drop` text COMMENT '作者:
普通掉落包',
`senior_drop` text COMMENT '作者:
高级掉落包',
`time` int(11) ,
`gem_first_drop` text ,
`gem_ordinary_drop` text COMMENT '作者:
普通掉落包',
`gem_senior_drop` text COMMENT '作者:
高级掉落包',
`min` int(11) ,
`max` int(11) ,
`cost` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
