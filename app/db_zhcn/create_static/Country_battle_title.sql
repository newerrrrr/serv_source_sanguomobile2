--
-- Table structure for table `Country_battle_title`
--
DROP TABLE IF EXISTS `Country_battle_title`;
CREATE TABLE IF NOT EXISTS `Country_battle_title` (
`id` int(11) NOT NULL ,
`rank` int(11) COMMENT '作者:
排名
',
`title_name` int(11) COMMENT '作者:
多语言ID',
`title_name_desc` varchar(512) COMMENT '作者:
称号批注',
`rank_pic` int(11) ,
`drop` int(11) ,
`buff_id` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
