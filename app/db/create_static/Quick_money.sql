--
-- Table structure for table `Quick_money`
--
DROP TABLE IF EXISTS `Quick_money`;
CREATE TABLE IF NOT EXISTS `Quick_money` (
`id` int(11) NOT NULL ,
`drop` int(11) ,
`cost_id` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
