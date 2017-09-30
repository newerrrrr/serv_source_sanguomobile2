--
-- Table structure for table `Pricing`
--
DROP TABLE IF EXISTS `Pricing`;
CREATE TABLE IF NOT EXISTS `Pricing` (
`id` int(11) NOT NULL ,
`channel` varchar(512) COMMENT '作者:
充值渠道',
`desc` int(11) ,
`desc1` varchar(512) ,
`type` varchar(512) COMMENT '作者:
货币种类',
`price` varchar(512) COMMENT '作者:
现金价格',
`goods_type` int(11) COMMENT '作者:
充值类型
1、元宝
2、永久月卡
3、月卡
4、充值礼包',
`count` int(11) COMMENT '作者:
充值获得元宝数',
`first_add_count` int(11) COMMENT '作者:
首次充值额外获得',
`add_count` int(11) COMMENT '作者:
每次充值额外获得',
`add_percent` int(11) COMMENT '作者:
客户端优惠比例 万分比
对礼包即性价比
1000表示额外优惠10%',
`isopen` int(11) COMMENT '作者:
是否打开此充值项
1-常态开启
2-特定时间段开启',
`isshow` int(11) ,
`bonus_drop` int(11) COMMENT '作者:
额外奖励',
`payment_code` varchar(512) ,
`gift_type` int(11) COMMENT '作者:
礼包类别，用于activity_Commodity表中对应礼包类别',
`product_id` int(11) COMMENT '作者:
渠道需要后台对应商品编码
相关渠道：
联想',
`icon` int(11) ,
`rmb_value` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
