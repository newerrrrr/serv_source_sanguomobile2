-- INSERT UPDATE sql for 'Duel_guide';
INSERT INTO `Duel_guide` (`id`,`steps`,`desc`) VALUES ('1','381101,1993104;381102,1993105;381103,1993103','第一回合') ON DUPLICATE KEY UPDATE `id` = '1',`steps` = '381101,1993104;381102,1993105;381103,1993103',`desc` = '第一回合';
INSERT INTO `Duel_guide` (`id`,`steps`,`desc`) VALUES ('2','381201,1993102','第二回合') ON DUPLICATE KEY UPDATE `id` = '2',`steps` = '381201,1993102',`desc` = '第二回合';
INSERT INTO `Duel_guide` (`id`,`steps`,`desc`) VALUES ('3','381301,1993101;381302,1993106','第三回合') ON DUPLICATE KEY UPDATE `id` = '3',`steps` = '381301,1993101;381302,1993106',`desc` = '第三回合';
