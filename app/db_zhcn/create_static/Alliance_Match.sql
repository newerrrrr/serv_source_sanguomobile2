--
-- Table structure for table `Alliance_Match`
--
DROP TABLE IF EXISTS `Alliance_Match`;
CREATE TABLE IF NOT EXISTS `Alliance_Match` (
`id` int(11) NOT NULL ,
`match_type` text COMMENT '作者:
对应match_type中type字段，随机抽取其中一个',
`time` int(11) COMMENT '作者:
持续时间
天数
从0点~23点59分59秒',
`drop_id` text COMMENT '作者:
对应Point_drop的ID
',
`rank_drop_id` text COMMENT '作者:
对应Point_drop的ID
',
`match_show` int(11) COMMENT '作者:
活动展示图',
`help_desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
