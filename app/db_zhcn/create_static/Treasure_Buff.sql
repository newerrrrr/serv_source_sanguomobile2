--
-- Table structure for table `Treasure_Buff`
--
DROP TABLE IF EXISTS `Treasure_Buff`;
CREATE TABLE IF NOT EXISTS `Treasure_Buff` (
`id` int(11) NOT NULL ,
`count_min` int(11) ,
`count_max` int(11) ,
`buff_temp_id` text ,
`language_id` int(11) ,
`desc1` varchar(512) ,
`buff_value` int(11) ,
`img` int(11) COMMENT '作者:
图标',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
