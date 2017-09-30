--
-- Table structure for table `target`
--
DROP TABLE IF EXISTS `target`;
CREATE TABLE IF NOT EXISTS `target` (
`id` int(11) NOT NULL  COMMENT '郑煦贤:
表格ID，即任务的排序',
`type` int(11) COMMENT '郑煦贤:
目标对应类型

1-府衙等级
2-从资源田获得资源
3-主公等级
4-VIP等级
5-拥有武将数量
6-建筑升级次数
7-最高战力
8-击杀野怪次数
9-击杀最高野怪等级
10-出征加速次数
11-采集资源量
12-训练士兵数
13-科技研发次数
14-拥有蓝装数量
15-抢夺采集资源量
16-主动技能使用次数
17-穿戴宝物数量
18-分解白银数
19-装备进阶次数
20-装备最高进阶数（拥有即视为已完成）蓝装或以上品质
21-联盟捐献次数
22-花费个人荣誉
23-联盟帮助次数
24-侦查次数
25-攻城次数
26-治疗兵数
27-陷阱制造数
28-攻城掠夺资源数',
`target_value` int(11) COMMENT '郑煦贤:
目标数值',
`target_desc` int(11) COMMENT '郑煦贤:
目标说明ID',
`desc` varchar(512) COMMENT '郑煦贤:
目标说明文本备注',
`time` int(11) COMMENT '郑煦贤:
目标开放持续时间，以
秒为单位',
`drop` text COMMENT '郑煦贤:
目标完成的真实奖励，走DROP表
客户端仅显示第一个奖励',
`next_target_id` int(11) COMMENT '作者:
下个目标id
0:没有下个目标
',
`jump` int(11) ,
`Level_min` int(11) COMMENT '徐力丰:
该目标开启的最小府衙等级',
`open_time` int(11) COMMENT '徐力丰:
在服务器开启第几天开放',
`drop_2` int(11) COMMENT '徐力丰:
第2天进入的玩家的掉落',
`drop_3` int(11) COMMENT '徐力丰:
第3天进入的玩家的掉落',
`drop_4` int(11) COMMENT '徐力丰:
第4天进入的玩家的掉落',
`drop_5` int(11) COMMENT '徐力丰:
第5天进入的玩家的掉落',
`drop_6` int(11) COMMENT '徐力丰:
第6天进入的玩家的掉落',
`drop_7` int(11) COMMENT '徐力丰:
第7天进入的玩家的掉落',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
