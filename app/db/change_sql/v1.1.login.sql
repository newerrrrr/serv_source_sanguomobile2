CREATE TABLE IF NOT EXISTS `login_server_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(500) COLLATE utf8_unicode_ci DEFAULT '',
  `value` varchar(500) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE server_list ADD COLUMN maintain_notice VARCHAR(500) CHARSET utf8 COLLATE utf8_unicode_ci DEFAULT '' NULL COMMENT '维护公告:status=1的时候,前端显示该内容';

ALTER TABLE `server_list` CHANGE `status` `status` INT(11) DEFAULT 0 NULL COMMENT '1:维护状态,不能进游戏',
                          CHANGE `isNew` `isNew` INT(11) DEFAULT 0 NULL;
						  
INSERT INTO `login_server_config` VALUES ('1', 'game_version', '8');
UPDATE `server_list` SET `maintain_notice` = "我們於9月12日10:00起對服務器進行停機維護，預計4小時，期間您將無法登錄遊戲，對您造成的不便敬請諒解！";
UPDATE `server_list` SET `status` = 1;