--
-- Table structure for table `Equipment`
--
DROP TABLE IF EXISTS `Equipment`;
CREATE TABLE IF NOT EXISTS `Equipment` (
`id` int(11) NOT NULL  COMMENT '作者:
1-武将武器
2-武将防具
3-武将饰品
',
`priority` int(11) COMMENT '作者:
优先级',
`item_original_id` int(11) ,
`equip_name` int(11) COMMENT '作者:
装备名字
60-武器
62-防具
64-饰品
66-主公宝物',
`desc1` varchar(512) ,
`description` int(11) COMMENT '作者:
装备介绍
61-武器
62-防御
63-饰品
64-主公',
`desc2` varchar(512) ,
`equip_type` int(11) COMMENT '作者:
道具ID
0-万能装备（不可携带）
1-武器（仅武将携带）
2-防具（仅武将携带）
3-饰品（仅武将携带）
4-坐骑',
`star_level` int(11) COMMENT '作者:
装备星级
0-初始装备
1-1星装备
2-2星装备
3-3星装备
4-4星装备
5-5星装备',
`max_star_level` int(11) ,
`quality_id` int(11) COMMENT '作者:
道具品质
1-白色
2-绿色
3-蓝色
4-紫色
5-橙色',
`force` int(11) COMMENT '作者:
武力',
`intelligence` int(11) COMMENT '作者:
智力',
`governing` int(11) COMMENT '作者:
统治力',
`charm` int(11) COMMENT '作者:
魅力',
`political` int(11) COMMENT '作者:
政治',
`min_general_level` int(11) COMMENT '作者:
武将穿戴等级',
`equip_skill_id` text COMMENT '作者:
装备技能ID
可能含有多个技能',
`equip_icon` int(11) ,
`recast` int(11) COMMENT '重铸
对应drop_id',
`recast_cost` int(11) COMMENT '作者:
重铸消耗，元宝',
`decomposition` int(11) COMMENT '分解，获得白银数量
对应drop_id',
`consume` text COMMENT '作者:
所需材料
1-材料
2-装备
3-白银

道具类型，道具ID，数量
901-白品质装备
902-绿品质装备
903-蓝品质装备
904-紫品质装备
905-橙品质装备

',
`target_equip` int(11) COMMENT '作者:
目标装备ID',
`power` int(11) COMMENT '作者:
装备战力',
`get_path` text COMMENT '作者:
快速获得途径
1=世界地图打怪
2=合成
3=跳转磨坊（还没制作）
4=商城
5=铁匠铺分解
6=重铸
7=集市
',
`target_unlock` int(11) COMMENT '作者:
进阶目标该版本是否解锁
1 解锁
0 未解锁',
`combat_skill_id` int(11) ,
`battle_skill_id` int(11) ,
`skill_level` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
