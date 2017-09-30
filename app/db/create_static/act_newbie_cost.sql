--
-- Table structure for table `act_newbie_cost`
--
DROP TABLE IF EXISTS `act_newbie_cost`;
CREATE TABLE IF NOT EXISTS `act_newbie_cost` (
`id` int(11) NOT NULL ,
`drop` text ,
`cost_price` int(11) COMMENT '作者:
花费元宝数量
价格为累计计算
如需求：第一档消费200，第二档消费',
`open_date` int(11) ,
`close_date` int(11) ,
`period` int(11) COMMENT '作者:
周期，（打组）',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
