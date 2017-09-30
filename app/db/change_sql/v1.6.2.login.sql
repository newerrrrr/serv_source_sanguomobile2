-- 公告里添加渠道信息
ALTER TABLE `notice` ADD COLUMN `channel` VARCHAR(1000) DEFAULT '' NULL COMMENT '渠道string' AFTER `end_time`;

-- 增加封测返利记录表
CREATE TABLE IF NOT EXISTS `refund_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gem` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='封测充值返利' AUTO_INCREMENT=1;
--
-- Table structure for table `Android_Channel`
--
DROP TABLE IF EXISTS `Android_Channel`;
CREATE TABLE IF NOT EXISTS `Android_Channel` (
`id` int(11) NOT NULL ,
`channel_id` varchar(512) ,
`channel_name` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
-- INSERT UPDATE sql for 'Android_Channel';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('1','000054','huawei') ON DUPLICATE KEY UPDATE `id` = '1',`channel_id` = '000054',`channel_name` = 'huawei';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('2','000016','lenovo') ON DUPLICATE KEY UPDATE `id` = '2',`channel_id` = '000016',`channel_name` = 'lenovo';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('3','000020','oppo') ON DUPLICATE KEY UPDATE `id` = '3',`channel_id` = '000020',`channel_name` = 'oppo';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('4','000084','gionee') ON DUPLICATE KEY UPDATE `id` = '4',`channel_id` = '000084',`channel_name` = 'gionee';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('5','000014','meizu') ON DUPLICATE KEY UPDATE `id` = '5',`channel_id` = '000014',`channel_name` = 'meizu';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('6','000368','vivo') ON DUPLICATE KEY UPDATE `id` = '6',`channel_id` = '000368',`channel_name` = 'vivo';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('7','000066','mi') ON DUPLICATE KEY UPDATE `id` = '7',`channel_id` = '000066',`channel_name` = 'mi';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('8','160280','coolpad') ON DUPLICATE KEY UPDATE `id` = '8',`channel_id` = '160280',`channel_name` = 'coolpad';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('9','160136','tencent') ON DUPLICATE KEY UPDATE `id` = '9',`channel_id` = '160136',`channel_name` = 'tencent';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('10','000255','aligames') ON DUPLICATE KEY UPDATE `id` = '10',`channel_id` = '000255',`channel_name` = 'aligames';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('11','110000','baidu') ON DUPLICATE KEY UPDATE `id` = '11',`channel_id` = '110000',`channel_name` = 'baidu';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('12','000023','qihu') ON DUPLICATE KEY UPDATE `id` = '12',`channel_id` = '000023',`channel_name` = 'qihu';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('13','999999','test_anysdk') ON DUPLICATE KEY UPDATE `id` = '13',`channel_id` = '999999',`channel_name` = 'test_anysdk';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('14','160086','pengyouwan') ON DUPLICATE KEY UPDATE `id` = '14',`channel_id` = '160086',`channel_name` = 'pengyouwan';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('15','000003','downjoy') ON DUPLICATE KEY UPDATE `id` = '15',`channel_id` = '000003',`channel_name` = 'downjoy';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('16','000247','muzhiwan') ON DUPLICATE KEY UPDATE `id` = '16',`channel_id` = '000247',`channel_name` = 'muzhiwan';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('17','160198','x7sy') ON DUPLICATE KEY UPDATE `id` = '17',`channel_id` = '160198',`channel_name` = 'x7sy';
INSERT INTO `Android_Channel` (`id`,`channel_id`,`channel_name`) VALUES ('18','001208','douyu') ON DUPLICATE KEY UPDATE `id` = '18',`channel_id` = '001208',`channel_name` = 'douyu';
