--
-- Table structure for table `Pay_way`
--
DROP TABLE IF EXISTS `Pay_way`;
CREATE TABLE IF NOT EXISTS `Pay_way` (
`id` int(11) NOT NULL ,
`channel` varchar(512) COMMENT '作者:
渠道客户端',
`pay_way` varchar(512) COMMENT '作者:
可用的支付方式',
`pay_way_lv` text COMMENT '作者:
每个充值项对应的开启等级',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
