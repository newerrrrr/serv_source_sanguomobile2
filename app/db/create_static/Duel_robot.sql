--
-- Table structure for table `Duel_robot`
--
DROP TABLE IF EXISTS `Duel_robot`;
CREATE TABLE IF NOT EXISTS `Duel_robot` (
`id` int(11) NOT NULL ,
`count` int(11) ,
`duel_rank_id` int(11) ,
`nick` int(11) ,
`level` int(11) ,
`avatar_id` int(11) ,
`score` int(11) ,
`general_1` int(11) ,
`general_2` int(11) ,
`general_3` int(11) ,
`lv` int(11) ,
`star_lv` int(11) ,
`weapon_id` int(11) ,
`armor_id` int(11) ,
`horse_id` int(11) ,
`zuoji_id` int(11) ,
`skill_lv` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
