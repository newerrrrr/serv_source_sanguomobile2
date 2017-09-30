--
-- Table structure for table `Drop`
--
DROP TABLE IF EXISTS `Drop`;
CREATE TABLE IF NOT EXISTS `Drop` (
`id` int(11) NOT NULL  COMMENT '陈涛:
通用掉落
1~99999999
开头不同对应系统不同',
`drop_type` int(11) COMMENT '陈涛:
掉落类型
1-整组奖励中部分掉落，掉落数量根据drop_count值
2-整组奖励全部掉落
3-VIP激活
4-抽卡神武将信物掉落（去除玩家已有的神武将后再执行n抽1）
5-神武将经验道具，掉落神武将经验',
`min_level` int(11) COMMENT '府衙最低等级
drop_type=3时，表示VIP等级',
`max_level` int(11) COMMENT '府衙最高等级
drop_type=3时，表示VIP等级',
`rate` int(11) COMMENT '陈涛:
掉落概率（万分比）',
`drop_count` int(11) COMMENT '掉落数量',
`drop_data` text COMMENT '陈涛:
掉落
掉落类型;掉落ID;掉落数量;概率',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
