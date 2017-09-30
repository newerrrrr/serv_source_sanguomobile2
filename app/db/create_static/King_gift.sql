--
-- Table structure for table `King_gift`
--
DROP TABLE IF EXISTS `King_gift`;
CREATE TABLE IF NOT EXISTS `King_gift` (
`id` int(11) NOT NULL ,
`gift_name` int(11) ,
`gift_id` int(11) COMMENT '作者:
drop id',
`max_count` int(11) ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
