--
-- Table structure for table `King_appoint`
--
DROP TABLE IF EXISTS `King_appoint`;
CREATE TABLE IF NOT EXISTS `King_appoint` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
type
1=增益
2=减益
',
`position_name` int(11) ,
`desc1` varchar(512) ,
`img_head` int(11) COMMENT '作者:
',
`img_portrait` int(11) COMMENT '作者:
',
`outline_icon` int(11) ,
`back_icon` int(11) ,
`add_buff` text COMMENT '作者:
职位获得BUFF 
',
`img_normal` int(11) COMMENT '作者:
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
