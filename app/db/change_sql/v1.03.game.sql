-- 邀请入盟字段,截止时间
ALTER TABLE `guild` ADD COLUMN `invite_end_time` TIMESTAMP DEFAULT '0000-00-00 00:00:00' NULL COMMENT '联盟邀请截止时间';
-- 联盟创始人
ALTER TABLE `guild` ADD COLUMN `founder` INT(11) DEFAULT 0 NULL COMMENT '联盟创始人' AFTER `id`; 
-- 将之前的盟主修改为创始人
update guild set founder=leader_player_id;
-- 是否第一次加入联盟
ALTER TABLE `player_info` ADD COLUMN `first_join_guild` INT(11) DEFAULT 0 NULL COMMENT '是否第一次加入联盟 0:是第一次,1:不是第一次' AFTER `first_nick`; 