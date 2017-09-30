--
-- Table structure for table `Country_city_map`
--
DROP TABLE IF EXISTS `Country_city_map`;
CREATE TABLE IF NOT EXISTS `Country_city_map` (
`id` int(11) NOT NULL ,
`city_type` int(11) COMMENT '陆阳:
1=出生地
2=可攻击打城池
3=可攻击小城池',
`ctiy_name` int(11) ,
`desc` varchar(512) ,
`city_pic` int(11) ,
`link` text COMMENT '陆阳:
城池连线
在此位置可进攻的城池',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
