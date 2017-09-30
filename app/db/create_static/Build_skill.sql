--
-- Table structure for table `Build_skill`
--
DROP TABLE IF EXISTS `Build_skill`;
CREATE TABLE IF NOT EXISTS `Build_skill` (
`id` int(11) NOT NULL ,
`buff_id` int(11) ,
`num` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
