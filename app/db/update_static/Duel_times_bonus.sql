-- INSERT UPDATE sql for 'Duel_times_bonus';
INSERT INTO `Duel_times_bonus` (`id`,`times`,`drops`,`desc`) VALUES ('1','1','610001','每天攻打次数到1次的奖励') ON DUPLICATE KEY UPDATE `id` = '1',`times` = '1',`drops` = '610001',`desc` = '每天攻打次数到1次的奖励';
INSERT INTO `Duel_times_bonus` (`id`,`times`,`drops`,`desc`) VALUES ('2','5','610002','每天攻打次数到5次的奖励') ON DUPLICATE KEY UPDATE `id` = '2',`times` = '5',`drops` = '610002',`desc` = '每天攻打次数到5次的奖励';
INSERT INTO `Duel_times_bonus` (`id`,`times`,`drops`,`desc`) VALUES ('3','10','610003','每天攻打次数到10次的奖励') ON DUPLICATE KEY UPDATE `id` = '3',`times` = '10',`drops` = '610003',`desc` = '每天攻打次数到10次的奖励';
INSERT INTO `Duel_times_bonus` (`id`,`times`,`drops`,`desc`) VALUES ('4','20','610004','每天攻打次数到20次的奖励') ON DUPLICATE KEY UPDATE `id` = '4',`times` = '20',`drops` = '610004',`desc` = '每天攻打次数到20次的奖励';
INSERT INTO `Duel_times_bonus` (`id`,`times`,`drops`,`desc`) VALUES ('5','30','610005','每天攻打次数到30次的奖励') ON DUPLICATE KEY UPDATE `id` = '5',`times` = '30',`drops` = '610005',`desc` = '每天攻打次数到30次的奖励';
