--
-- Table structure for table `SkillAnims`
--
DROP TABLE IF EXISTS `SkillAnims`;
CREATE TABLE IF NOT EXISTS `SkillAnims` (
`id` int(11) NOT NULL ,
`path` varchar(512) COMMENT '作者:
技能特效位置',
`play_type` int(11) COMMENT '作者:
播放模式：
1：在启动位置播放
2：在目标位置播放
3：全屏中央播放
4：飞行特效（飞行特效循环播放',
`anims_type` int(11) COMMENT '作者:
动画类型
1：不切面，2：切面',
`desc` varchar(512) COMMENT '作者:
类型
1=普攻
2=技能
3=BUFF',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
