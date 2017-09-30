--
-- Table structure for table `Vip_Privilege`
--
DROP TABLE IF EXISTS `Vip_Privilege`;
CREATE TABLE IF NOT EXISTS `Vip_Privilege` (
`id` int(11) NOT NULL ,
`vip_lv` int(11) ,
`num_type` int(11) COMMENT '1-万分比
2-具体值',
`buff_num` int(11) COMMENT '特权buff的效果',
`privilege_type` int(11) COMMENT '一种特权对应一个type',
`icon` int(11) COMMENT '特权icon',
`buff_desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
