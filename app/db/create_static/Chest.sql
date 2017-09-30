--
-- Table structure for table `Chest`
--
DROP TABLE IF EXISTS `Chest`;
CREATE TABLE IF NOT EXISTS `Chest` (
`id` int(11) NOT NULL  COMMENT '预设宝箱配置id
',
`chest_id` int(11) COMMENT '宝箱序号',
`lv_min` int(11) COMMENT '最低府衙等级',
`lv_max` int(11) COMMENT '最高府衙等级',
`weight` int(11) COMMENT '徐力丰:
权重',
`type` int(11) COMMENT '徐力丰:
1 drop
2 倍率',
`value` int(11) COMMENT '格式：
type=1:dropid
type=2:倍率
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
