--
-- Table structure for table `general_total_stars`
--
DROP TABLE IF EXISTS `general_total_stars`;
CREATE TABLE IF NOT EXISTS `general_total_stars` (
`id` int(11) NOT NULL ,
`total_stars` int(11) COMMENT '总星数',
`drop_id` int(11) COMMENT 'dropid
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
