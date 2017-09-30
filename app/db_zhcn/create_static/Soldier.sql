--
-- Table structure for table `Soldier`
--
DROP TABLE IF EXISTS `Soldier`;
CREATE TABLE IF NOT EXISTS `Soldier` (
`id` int(11) NOT NULL  COMMENT '陈涛:
1-步兵
2-骑兵
3-弓兵
4-车兵',
`soldier_name` int(11) COMMENT '张立:
30开头是士兵名字
31开头是士兵介绍
32开头是士兵技能名字
33开头士兵技能介绍
',
`desc1` varchar(512) ,
`type_name` int(11) ,
`arm_type` int(11) COMMENT '陆阳:
1盾
2枪
3骑
4弓骑
5弓
6弩
7冲车
8投石
9万能
',
`desc2` varchar(512) ,
`soldier_level` int(11) COMMENT '士兵等级',
`soldier_type` int(11) COMMENT '陈涛:
1-步兵
2-骑兵
3-弓兵
4-投石车
5-万能',
`img_level` int(11) COMMENT '陆阳:
士兵等级对',
`img_head` int(11) COMMENT '陆阳:
士兵小头像',
`img_portrait` int(11) COMMENT '陆阳:
士兵肖像',
`img_type` int(11) COMMENT '陆阳:
兵种图标',
`soldier_introduction` int(11) ,
`desc3` varchar(512) ,
`soldier_intro` int(11) ,
`desc4` varchar(512) ,
`attack` int(11) ,
`defense` int(11) ,
`life` int(11) ,
`distance` int(11) COMMENT '陈涛:
射程',
`speed` int(11) COMMENT '陈涛:
移动速度',
`weight` int(11) COMMENT '陈涛:
负重',
`add_buff` text COMMENT '陆阳:
关联BUFF表',
`cost` text COMMENT '道具ID,数量（中间用分号隔开）
1-黄金
2-粮食
3-木材
4-石矿
5-铁矿',
`rescue_cost` text COMMENT '复活士兵的消耗
等于建造士兵的消耗的一半',
`upgrade_id` int(11) COMMENT '兵种升级
填写目标兵种的id
0表示无法升级',
`upgrade_cost` text COMMENT '升级兵种所需的价格
0表示无法升级',
`consumption` int(11) COMMENT '陈涛:
每小时粮食消耗(万分比)',
`power` int(11) COMMENT '陈涛:
单个士兵战斗力
（需要除以一万后的固定值）',
`train_time` int(11) COMMENT '单个士兵建造时间
单位：秒',
`rescue_time` int(11) COMMENT '复活单个士兵所需时间',
`skill_1` int(11) ,
`skill_2` int(11) ,
`skill_3` int(11) ,
`need_build_id` int(11) COMMENT '陈涛:
开放等级
',
`gem_cost` int(11) COMMENT '陈涛:
快速建造单个士兵所花费的元宝数（需要除以一万后的固定值）向上取整',
`rescue_gem_cost` int(11) COMMENT '陈涛:
单个士兵元宝复活消耗
（需要除以一万后的固定值）向上取整',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
