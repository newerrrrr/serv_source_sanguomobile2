--
-- Table structure for table `General`
--
DROP TABLE IF EXISTS `General`;
CREATE TABLE IF NOT EXISTS `General` (
`id` int(11) NOT NULL ,
`general_country` int(11) COMMENT '陆阳:
1吴
2蜀国
3魏国
4群',
`root_id` int(11) ,
`general_name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) COMMENT '陈涛:
武将简介',
`desc2` varchar(512) ,
`general_type` int(11) COMMENT '陈涛:
1-武将
2-智将
3-政将
4-全能',
`general_force` int(11) COMMENT '陈涛:
武将武力',
`general_intelligence` int(11) COMMENT '陈涛:
武将智力',
`general_governing` int(11) COMMENT '陈涛:
武将统治力',
`general_charm` int(11) COMMENT '陈涛:
武将魅力',
`general_political` int(11) COMMENT '陈涛:
武将政治',
`general_quality` int(11) COMMENT '陈涛:
武将品质
1-白色
2-绿色
3-蓝色
4-紫色
5-橙色',
`general_item_id` int(11) COMMENT '陈涛:
武将专属武器原始ID
',
`general_level_id` int(11) ,
`general_original_id` int(11) COMMENT '陈涛:
武将原始ID',
`general_icon_min` int(11) COMMENT '陈涛:
武将驻守小头像',
`general_icon` int(11) COMMENT '陈涛:
武将小头像',
`general_big_icon` int(11) COMMENT '陈涛:
武将大头像',
`skill_icon` int(11) COMMENT '陆阳:
技能icon',
`max_soldier` int(11) COMMENT '陈涛:
武将携带士兵上限',
`general_skill` text COMMENT '武将技能 无用字段',
`general_combat_skill` int(11) COMMENT '战斗技能id
多个技能逗号分隔',
`general_duel_atk` int(11) COMMENT '武斗普通攻击id
多个技能逗号分隔',
`general_duel_skill` int(11) COMMENT '武斗技能id
多个技能逗号分隔',
`general_duel_move` int(11) COMMENT '武将武斗移动距离
最小单位1像素
距离为半径
',
`prop1` int(11) COMMENT '陈涛:
宝物1解锁状态
0-未解锁
1-解锁',
`prop2` int(11) COMMENT '陈涛:
宝物2解锁状态
0-未解锁
1-解锁',
`prop3` int(11) ,
`cost_gold` int(11) COMMENT '陈涛:
招募武将的黄金消耗',
`power` int(11) COMMENT '陈涛:
武将战斗力',
`avaiable_level` int(11) COMMENT '徐力丰:
武将在酒馆中出现等级
-1表示永不出现',
`piece_item_id` int(11) COMMENT '武将信物的item_id',
`piece_required` int(11) COMMENT '招募武将所需的碎片数量',
`priority` int(11) COMMENT '酒馆中的排序优先级',
`drop_show` text COMMENT '0：不显示来源
1：NPCID
2：联盟商店
3：锦囊商店
4: 活动id
5: 占星
6: 天殒
7:神盔甲合成',
`portrait_xy` text COMMENT '陆阳:
武将半身像截取的显示坐标',
`portrait_size` varchar(512) COMMENT '陆阳:
武将半身像截取的显示缩放
',
`sell_price` int(11) COMMENT '对酒价格
0：不可通过对酒获得
其他值：元宝价格',
`consume` text COMMENT '徐力丰:
化神费用',
`condition` int(11) COMMENT '徐力丰:
化神条件
',
`general_intro` int(11) COMMENT '徐力丰:
武将定位
',
`desc3` varchar(512) ,
`cocos_res` int(11) COMMENT '陆阳:
武将骨骼动画
',
`cocos_enemy_res` int(11) COMMENT '陆阳:
武将骨骼动画
敌人',
`duel_hit_point` varchar(512) COMMENT '徐力丰:
武斗时武将的生命值
',
`duel_hero_max_sp` int(11) COMMENT '徐力丰:
武斗sp',
`duel_hero_start_sp` int(11) COMMENT '徐力丰:
武斗初始sp',
`duel_hero_restore_sp` int(11) COMMENT '徐力丰:
武斗每回合sp恢复',
`weapon_type` int(11) COMMENT '徐力丰:
武器类型
1 短刀
2 长柄
3 远程',
`soldier_type` int(11) COMMENT '徐力丰:
优势兵种类型：
1步
2骑
3弓
4车',
`general_talent_buff_id` text COMMENT '武将天赋buff id',
`general_talent_value` varchar(512) COMMENT '武将天赋数值
使用武将星级的计算公式',
`general_talent_value_client` varchar(512) COMMENT '武将天赋数值
使用武将星级的计算公式',
`general_talent_description` int(11) COMMENT '武将天赋描述
',
`desc4` varchar(512) COMMENT '武将天赋描述
',
`general_battle_skill` int(11) COMMENT '城战技能',
`general_item_soul` int(11) COMMENT '陆阳:
神武将将魂关联ID',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
