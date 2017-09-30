--
-- Table structure for table `act_newbie_recharge`
--
DROP TABLE IF EXISTS `act_newbie_recharge`;
CREATE TABLE IF NOT EXISTS `act_newbie_recharge` (
`id` int(11) NOT NULL ,
`drop` text COMMENT '作者:
奖励',
`recharge_price` int(11) COMMENT '作者:
累充档位',
`open_date` int(11) COMMENT '作者:
第几天开',
`close_date` int(11) COMMENT '作者:
第几天结束',
`period` int(11) COMMENT '作者:
周期，（打组）',
`desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
