--
-- Table structure for table `Build_buff_type`
--
DROP TABLE IF EXISTS `Build_buff_type`;
CREATE TABLE IF NOT EXISTS `Build_buff_type` (
`id` int(11) NOT NULL ,
`buff_id` text ,
`name` int(11) ,
`name1` varchar(512) ,
`dec` int(11) ,
`dec1` varchar(512) ,
`dec_start` int(11) COMMENT '作者:
激活后显示文字
',
`dec2` varchar(512) ,
`res_up` int(11) ,
`res` int(11) ,
`res_down` int(11) ,
`link` text COMMENT '作者:
关联SHOP ID',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
