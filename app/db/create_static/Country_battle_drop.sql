--
-- Table structure for table `Country_battle_drop`
--
DROP TABLE IF EXISTS `Country_battle_drop`;
CREATE TABLE IF NOT EXISTS `Country_battle_drop` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '奖励类型：
1、城门战奖励（失败阵营获得）
2、城内战奖励胜利（攻击方获得）
3、城内战奖励失败（攻击方获得）
4、城内战奖励胜利（防守方获得）
5、城内战奖励失败（防守方获得）
6、羽林军称号奖励',
`rank_min` int(11) COMMENT '作者:
最小排名
',
`rank_max` int(11) COMMENT '作者:
最大排名',
`drop` int(11) COMMENT '作者:
掉落ID',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
