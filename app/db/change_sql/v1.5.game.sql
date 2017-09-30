ALTER TABLE `player` ADD `is_in_cross` int NOT NULL DEFAULT '0' COMMENT '是否在跨服战中' AFTER `fresh_avoid_battle_time`;

-- 跨服申请标记
ALTER TABLE `player_guild` ADD COLUMN `cross_application_flag` INT(2) DEFAULT 0 NULL COMMENT '跨服战成员申请 0：未申请， 1：申请' ;
-- 跨服参赛标记
ALTER TABLE `player_guild` ADD COLUMN `cross_joined_flag` INT(2) DEFAULT 0 NULL COMMENT '跨服战参战与否 0：未参战， 1：参战' ;

-- 是否已升级过资源田和士兵的标记
ALTER TABLE  `player` ADD  `has_corrected` INT NOT NULL DEFAULT  '0' COMMENT  '是否升级过士兵和资源建筑' AFTER  `hsb`;