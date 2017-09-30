--
-- Table structure for table `Equip_skill`
--
DROP TABLE IF EXISTS `Equip_skill`;
CREATE TABLE IF NOT EXISTS `Equip_skill` (
`id` int(11) NOT NULL  COMMENT '作者:
6位数
2-武将武器
3-武将防具
4-武将饰品
5-主公宝物',
`skill_buff_id` text ,
`skill_description` int(11) ,
`desc1` varchar(512) ,
`num` int(11) COMMENT '作者:
道具初始值
根据buff不同，值不同，如果是百分比，则用万分比，这里只写数字',
`min` int(11) COMMENT '作者:
最小值（万分比）',
`max` int(11) COMMENT '作者:
最大值（万分比）',
`equip_arm_type` int(11) COMMENT '作者:
用于选择出征部队武将带兵特性标示
1步兵
2骑兵
3弓兵
4车兵',
`equip_arm_description` int(11) ,
`desc2` varchar(512) ,
`equipment_active_on_build` text COMMENT '驻守时，装备buff生效的建筑origin_id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
