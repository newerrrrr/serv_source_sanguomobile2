-- INSERT UPDATE sql for 'battle_skill_levelup';
INSERT INTO `battle_skill_levelup` (`id`,`level`,`consume`) VALUES ('1','1','') ON DUPLICATE KEY UPDATE `id` = '1',`level` = '1',`consume` = '';
INSERT INTO `battle_skill_levelup` (`id`,`level`,`consume`) VALUES ('2','1','2,51012,2') ON DUPLICATE KEY UPDATE `id` = '2',`level` = '1',`consume` = '2,51012,2';
INSERT INTO `battle_skill_levelup` (`id`,`level`,`consume`) VALUES ('3','1','2,51012,3') ON DUPLICATE KEY UPDATE `id` = '3',`level` = '1',`consume` = '2,51012,3';
INSERT INTO `battle_skill_levelup` (`id`,`level`,`consume`) VALUES ('4','1','2,51012,4') ON DUPLICATE KEY UPDATE `id` = '4',`level` = '1',`consume` = '2,51012,4';
INSERT INTO `battle_skill_levelup` (`id`,`level`,`consume`) VALUES ('5','1','2,51012,5') ON DUPLICATE KEY UPDATE `id` = '5',`level` = '1',`consume` = '2,51012,5';
