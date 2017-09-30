--
-- Table structure for table `Faq_Guide`
--
DROP TABLE IF EXISTS `Faq_Guide`;
CREATE TABLE IF NOT EXISTS `Faq_Guide` (
`id` int(11) NOT NULL ,
`name_id` int(11) ,
`name` varchar(512) ,
`desc_id` int(11) ,
`desc` varchar(512) ,
`redirect_type` text COMMENT '类型可增加，但不要删减或插入
1-步兵营
2-骑兵营
3-弓兵营
4-车兵营
5-战争工坊
6-酒馆
7-铁匠铺
8-研究所
9-主公天赋
10-主公宝物
11-升级建筑
12-搜索怪物
13-任务',
`button_name_id` text ,
`castle_lv` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
