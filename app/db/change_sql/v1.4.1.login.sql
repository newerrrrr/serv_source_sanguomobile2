-- 用于 curl跨服请求
ALTER TABLE `server_list` ADD COLUMN `game_server_ip` VARCHAR(500) DEFAULT '' NULL COMMENT '游戏服内部ip，curl跨服请求时用' AFTER `maintain_notice`;