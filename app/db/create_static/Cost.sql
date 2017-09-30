--
-- Table structure for table `Cost`
--
DROP TABLE IF EXISTS `Cost`;
CREATE TABLE IF NOT EXISTS `Cost` (
`id` int(11) NOT NULL ,
`cost_id` int(11) ,
`min_count` int(11) COMMENT '作者:
有次数的时候
最小次数
0-没有次数限制',
`max_count` int(11) COMMENT '作者:
有次数的时候
最大次数
0-没有次数限制',
`cost_type` int(11) COMMENT '作者:
消耗的货币类型
1-黄金
2-粮食
3-木头
4-石材
5-铁矿
6-白银
7-gem
8-个人荣誉
9-体力
10-主公经验
11-联盟科技经验
12-联盟荣誉
13-锦囊
14-铜币
15-勾玉
20-战勋
21-玄铁
22-将印
23-军资',
`cost_num` int(11) COMMENT '作者:
花费的具体数量
如果花费的是不固定的值，这里写0',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
