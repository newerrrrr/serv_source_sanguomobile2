--
-- Table structure for table `Hoard`
--
DROP TABLE IF EXISTS `Hoard`;
CREATE TABLE IF NOT EXISTS `Hoard` (
`id` int(11) NOT NULL ,
`build_id` int(11) ,
`max_soldiers` int(11) COMMENT '作者:
集结兵量上限',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
