--
-- Table structure for table `Mission`
--
DROP TABLE IF EXISTS `Mission`;
CREATE TABLE IF NOT EXISTS `Mission` (
`id` int(11) NOT NULL ,
`mission_name` int(11) COMMENT '作者:
任务名称',
`desc1` varchar(512) COMMENT '描述文字',
`mission_objectives` int(11) COMMENT '作者:
任务目标',
`desc2` varchar(512) COMMENT '描述文字
任务目标描述',
`mission_type` int(11) COMMENT '作者:
1 主线-建造或升级
3 研究科技：研究任意科技1个
4 训练部队：训练指定步兵/骑兵/弓兵/车兵
5 击杀怪物：击杀指定怪物/任意怪物
6 攻击玩家：攻击其他玩家获胜n次
7 掠夺资源：掠夺其他玩家n资源（黄金、粮草、木材、石头、铁矿）
8 采集资源：在世界地图中采集n资源
9 奋勇杀敌：击杀其他玩家n民士兵
10 防御玩家：抵御其他玩家攻击n次
11 治愈伤兵：治愈n名伤兵
12 联盟捐献：获得联盟n点贡献值
13 合成材料：合成n装备进阶材料
15 众志成城：集结消灭n名敌军
17 商城购物：在商城中消费元宝
19 联盟兑换：在联盟中兑换1次物品
20 收获资源：在主城中收获n资源
21 主线-训练步兵
22 主线-训练骑兵
25 主线-野外打怪
26 主线-研究指定科技
27 主线-杀兵2次
28 主线-杀兵4次
',
`next_mission_id` int(11) COMMENT '作者:
前置任务id
',
`min_level` int(11) COMMENT '作者:
玩家接受任务的最低等级',
`max_level` int(11) COMMENT '作者:
玩家接受任务的最小等级',
`star_level` int(11) COMMENT '作者:
1:代表1星任务
2:代表2星任务
3:代表3星任务
4:代表4星任务
5:代表5星任务
0:代表主线',
`probability` int(11) COMMENT '作者:
任务自然刷新权重
',
`probability_yb` int(11) COMMENT '作者:
元宝刷新权重
',
`drop` text COMMENT '作者:
奖励',
`mission_number` int(11) COMMENT '作者:
主线任务显示build_id
每日任务显示条件数量',
`mission_target` int(11) COMMENT '作者:
跳转的建筑物
世界地图跳转不填
',
`mission_target2` int(11) COMMENT '作者:
900001=训练步兵
900002=训练骑兵
900003=野外打怪
900004=研究科技',
`description` int(11) ,
`desc3` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
