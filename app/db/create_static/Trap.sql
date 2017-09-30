--
-- Table structure for table `Trap`
--
DROP TABLE IF EXISTS `Trap`;
CREATE TABLE IF NOT EXISTS `Trap` (
`id` int(11) NOT NULL ,
`trap_type` int(11) COMMENT '作者:
1-落石--克制步兵
2-火箭--克制骑兵
3-滚木--克制弓兵',
`trap_name` int(11) COMMENT '作者:
陷阱名称',
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
`img_level` int(11) ,
`img_head` int(11) COMMENT '作者:
陷阱小图',
`img_portrait` int(11) COMMENT '作者:
陷阱大图',
`need_build_id` int(11) COMMENT '作者:
对应战争工坊等级开放',
`level` int(11) COMMENT '作者:
士兵等级',
`atk` int(11) COMMENT '作者:
攻击力',
`distance` int(11) COMMENT '作者:
射程',
`cost` text COMMENT '作者:
升级消耗
格式：
道具ID,数量（中间用分号隔开）
1-黄金
2-粮草
3-木材
4-石材
5-铁材',
`cost_gem` int(11) COMMENT '作者:
快速建造单个陷阱所需花费的元宝',
`train_time` int(11) COMMENT '作者:
生产单个所需时间（秒）',
`power` int(11) COMMENT '作者:
单个陷阱战斗力
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
