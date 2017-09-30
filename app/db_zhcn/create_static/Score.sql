--
-- Table structure for table `Score`
--
DROP TABLE IF EXISTS `Score`;
CREATE TABLE IF NOT EXISTS `Score` (
`id` int(11) NOT NULL ,
`target_group` int(11) ,
`level` int(11) ,
`score` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
