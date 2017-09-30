--
-- Table structure for table `Loading_desc`
--
DROP TABLE IF EXISTS `Loading_desc`;
CREATE TABLE IF NOT EXISTS `Loading_desc` (
`id` int(11) NOT NULL ,
`tips` int(11) COMMENT 'loading时的提示内容',
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
