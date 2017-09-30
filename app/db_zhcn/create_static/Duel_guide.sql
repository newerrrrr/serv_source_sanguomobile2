--
-- Table structure for table `Duel_guide`
--
DROP TABLE IF EXISTS `Duel_guide`;
CREATE TABLE IF NOT EXISTS `Duel_guide` (
`id` int(11) NOT NULL ,
`steps` text COMMENT '文字，图片；',
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
