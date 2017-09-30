--
-- Table structure for table `Warfare_service_config`
--
DROP TABLE IF EXISTS `Warfare_service_config`;
CREATE TABLE IF NOT EXISTS `Warfare_service_config` (
`id` int(11) NOT NULL ,
`name` varchar(512) COMMENT '作者:
配置项名称',
`data` varchar(512) COMMENT '作者:
数值或公式',
`text` varchar(512) COMMENT '作者:
文本说明',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
