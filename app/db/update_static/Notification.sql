-- INSERT UPDATE sql for 'Notification';
INSERT INTO `Notification` (`id`,`name`,`priority`,`text`) VALUES ('1','player_levelup','1','主公等级提升') ON DUPLICATE KEY UPDATE `id` = '1',`name` = 'player_levelup',`priority` = '1',`text` = '主公等级提升';
INSERT INTO `Notification` (`id`,`name`,`priority`,`text`) VALUES ('2','general_levelup','2','有武将等级可提升等级') ON DUPLICATE KEY UPDATE `id` = '2',`name` = 'general_levelup',`priority` = '2',`text` = '有武将等级可提升等级';
INSERT INTO `Notification` (`id`,`name`,`priority`,`text`) VALUES ('3','player_gain_achievement','3','获得成就') ON DUPLICATE KEY UPDATE `id` = '3',`name` = 'player_gain_achievement',`priority` = '3',`text` = '获得成就';
INSERT INTO `Notification` (`id`,`name`,`priority`,`text`) VALUES ('4','player_gain_material','4','获得物品') ON DUPLICATE KEY UPDATE `id` = '4',`name` = 'player_gain_material',`priority` = '4',`text` = '获得物品';
INSERT INTO `Notification` (`id`,`name`,`priority`,`text`) VALUES ('5','event_gain_reward_20151111','5','xx活动完成获得奖励') ON DUPLICATE KEY UPDATE `id` = '5',`name` = 'event_gain_reward_20151111',`priority` = '5',`text` = 'xx活动完成获得奖励';
