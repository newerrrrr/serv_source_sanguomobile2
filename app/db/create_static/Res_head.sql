--
-- Table structure for table `Res_head`
--
DROP TABLE IF EXISTS `Res_head`;
CREATE TABLE IF NOT EXISTS `Res_head` (
`id` int(11) NOT NULL ,
`head_icon` int(11) ,
`bust_icon` int(11) ,
`outline_icon` int(11) ,
`back_icon` int(11) ,
`min_head` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
