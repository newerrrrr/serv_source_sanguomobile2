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
