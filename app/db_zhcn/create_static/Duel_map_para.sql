--
-- Table structure for table `Duel_map_para`
--
DROP TABLE IF EXISTS `Duel_map_para`;
CREATE TABLE IF NOT EXISTS `Duel_map_para` (
`id` int(11) NOT NULL ,
`map_res` int(11) COMMENT '作者:
地图资源',
`map_res_layer` int(11) COMMENT '作者:
遮罩层
',
`move_range` text COMMENT '作者:
可移动范围坐标，逆时针',
`position_left` text COMMENT '作者:
我方站位',
`position_right` text COMMENT '作者:
敌方站位',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
