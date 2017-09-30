--
-- Table structure for table `Wheel`
--
DROP TABLE IF EXISTS `Wheel`;
CREATE TABLE IF NOT EXISTS `Wheel` (
`id` int(11) NOT NULL  COMMENT '转盘的1~12项物品',
`grid_id` int(11) ,
`next_grid_id` int(11) ,
`type` int(11) COMMENT '徐力丰:
该格子的类型
0：根据玩家等级随机一种资源(前端显示资源)
1：随机一种宝箱（需符合玩家等级，前端显示问号）
2：直接掉落道具（前端显示宝箱）',
`drop` int(11) COMMENT 'type=0时，根据等级随机drop
type=2时直接走drop
',
`lv_min` int(11) COMMENT '最低府衙等级',
`lv_max` int(11) COMMENT '最高府衙等级',
`res_icon` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
