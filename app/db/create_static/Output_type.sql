--
-- Table structure for table `Output_type`
--
DROP TABLE IF EXISTS `Output_type`;
CREATE TABLE IF NOT EXISTS `Output_type` (
`id` int(11) NOT NULL ,
`output_type` int(11) ,
`num_type` int(11) COMMENT '作者:
前端用于显示基础值的显示类型
0-无效果
1-万分比
2-固定值',
`buff_id` int(11) COMMENT '作者:
对应buff表buff_id
0-没有buff_id
',
`buff_name` varchar(512) ,
`plus_type` int(11) COMMENT '增加方式
to:武将buff
1-公式算出百分比（小数点后两位），百分比增加，再乘基础值
2-公式算出百分比（小数点后两位），百分比增加
3-公式算出具体值，值要向下取整，直接增加

to:playerbuff
1、拿到玩家buff值，除10000，得到百分比，再乘基础值，按固定值显示（如：100）
2、拿到玩家buff值，除10000，得到百分比，加百分号，显示百分比（如：10%）
3、拿到玩家buff值，不做任何处理，直接显示（如：100）',
`formula` varchar(512) COMMENT '作者:
公式
属性是指建造所需属性
',
`desc` int(11) ,
`desc1` varchar(512) ,
`information_title` int(11) ,
`information_desc` varchar(512) ,
`desc2` varchar(512) ,
`desc3` varchar(512) ,
`need_general_attribute` int(11) COMMENT '作者:
需要武将属性
0-不可驻扎
1-武力
2-智力
3-统治力
4-魅力
5-政治',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
