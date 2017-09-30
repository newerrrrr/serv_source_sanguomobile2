--
-- Table structure for table `Equip_master`
--
DROP TABLE IF EXISTS `Equip_master`;
CREATE TABLE IF NOT EXISTS `Equip_master` (
`id` int(11) NOT NULL  COMMENT '作者:
主公的宝物从4开头',
`priority` int(11) COMMENT '作者:
优先级',
`item_original_id` int(11) ,
`equip_name` int(11) COMMENT '作者:
主公宝物名字
61-武器
62-防御
63-饰品
64-主公',
`desc1` varchar(512) ,
`description` int(11) COMMENT '作者:
主公宝物介绍
61-武器
62-防御
63-饰品
64-主公',
`desc2` varchar(512) ,
`quality_id` int(11) COMMENT '作者:
道具品质
1-白色
2-绿色
3-蓝色
4-紫色
5-橙色',
`min_master_level` int(11) COMMENT '作者:
主公穿戴等级',
`equip_skill_id` text COMMENT '作者:
装备技能ID
可能含有多个技能',
`power` int(11) COMMENT '作者:
装备战力',
`equip_icon` int(11) COMMENT '作者:
宝物ICON',
`type` int(11) COMMENT '作者:
内政1
战争2',
`selldrop` int(11) COMMENT '作者:
该宝物出售获得的锦囊数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
