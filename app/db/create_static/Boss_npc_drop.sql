--
-- Table structure for table `Boss_npc_drop`
--
DROP TABLE IF EXISTS `Boss_npc_drop`;
CREATE TABLE IF NOT EXISTS `Boss_npc_drop` (
`id` int(11) NOT NULL ,
`npc_id` int(11) COMMENT '对应boss野怪在npc表中的id',
`damage_min` int(11) COMMENT '最低伤害
军团对boss造成的伤害处于不同区间获得不同奖励
',
`damage_max` int(11) COMMENT '最高伤害
军团对boss造成的伤害处于不同区间获得不同奖励
-1表示无上限',
`boss_drop` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
