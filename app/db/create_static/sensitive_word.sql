--
-- Table structure for table `sensitive_word`
--
DROP TABLE IF EXISTS `sensitive_word`;
CREATE TABLE IF NOT EXISTS `sensitive_word` (
`id` int(11) NOT NULL ,
`word` varchar(512) ,
`type` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
