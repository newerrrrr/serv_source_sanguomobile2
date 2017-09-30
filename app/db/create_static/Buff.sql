--
-- Table structure for table `Buff`
--
DROP TABLE IF EXISTS `Buff`;
CREATE TABLE IF NOT EXISTS `Buff` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`description` int(11) ,
`desc1` varchar(512) ,
`starting_num` int(11) COMMENT '陈涛:
buff初始值
百分比的为万分比
时间的以秒为单位',
`buff_type` int(11) COMMENT '陈涛:
1-万分比
2-具体值',
`desc` varchar(512) COMMENT '游戏中所有 速度增加x%的buff都用以下方式实现：
最终耗时=速度未增加之前的耗时/(1+x%)
例如：建筑建造速度增加x%的buff实际效果为
建筑建造时间=读表时间/(1+x%)

游戏中所有数量增加x%的buff都用以下方式实现：
最终数量=原始数量*(1+x%)
例如：步兵攻击增加x%
步兵攻击=读表攻击*(1+x%)

多个buff的百分比数值用加法计算总和。',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
