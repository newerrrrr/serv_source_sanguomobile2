--
-- Table structure for table `general_condition_type`
--
DROP TABLE IF EXISTS `general_condition_type`;
CREATE TABLE IF NOT EXISTS `general_condition_type` (
`id` int(11) NOT NULL ,
`type` int(11) ,
`desc` int(11) ,
`desc1` varchar(512) ,
`para1` int(11) ,
`condition_icon` int(11) ,
`get_path` int(11) COMMENT '陆阳:
跳转建筑物
origin_build_id',
`menu_1` int(11) COMMENT '郑煦贤:
建筑正常状态下的高亮提示按钮
build_menu_id',
`menu_2` int(11) COMMENT '郑煦贤:
建筑加速状态下的高亮提示按钮
build_menu_id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
