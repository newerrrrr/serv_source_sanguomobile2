--
-- Table structure for table `Gamble_general_soul`
--
DROP TABLE IF EXISTS `Gamble_general_soul`;
CREATE TABLE IF NOT EXISTS `Gamble_general_soul` (
`id` int(11) NOT NULL  COMMENT '陆阳:
1=魏
2=蜀
3=吴
4=群',
`drop_id` int(11) COMMENT '陆阳:
cost_id 
10022 将魂首次半价
10023 将魂全价
10024 将魂10连',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
