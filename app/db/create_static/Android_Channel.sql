--
-- Table structure for table `Android_Channel`
--
DROP TABLE IF EXISTS `Android_Channel`;
CREATE TABLE IF NOT EXISTS `Android_Channel` (
`id` int(11) NOT NULL ,
`channel_id` varchar(512) ,
`channel_name` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
