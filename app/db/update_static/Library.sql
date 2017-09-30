-- INSERT UPDATE sql for 'Library';
INSERT INTO `Library` (`id`,`time`,`cost`,`rate`,`clear_time`) VALUES ('1','4','0','1','20') ON DUPLICATE KEY UPDATE `id` = '1',`time` = '4',`cost` = '0',`rate` = '1',`clear_time` = '20';
INSERT INTO `Library` (`id`,`time`,`cost`,`rate`,`clear_time`) VALUES ('2','8','0','1','20') ON DUPLICATE KEY UPDATE `id` = '2',`time` = '8',`cost` = '0',`rate` = '1',`clear_time` = '20';
INSERT INTO `Library` (`id`,`time`,`cost`,`rate`,`clear_time`) VALUES ('3','12','0','1','20') ON DUPLICATE KEY UPDATE `id` = '3',`time` = '12',`cost` = '0',`rate` = '1',`clear_time` = '20';
INSERT INTO `Library` (`id`,`time`,`cost`,`rate`,`clear_time`) VALUES ('4','4','10','2','20') ON DUPLICATE KEY UPDATE `id` = '4',`time` = '4',`cost` = '10',`rate` = '2',`clear_time` = '20';
INSERT INTO `Library` (`id`,`time`,`cost`,`rate`,`clear_time`) VALUES ('5','8','18','2','20') ON DUPLICATE KEY UPDATE `id` = '5',`time` = '8',`cost` = '18',`rate` = '2',`clear_time` = '20';
INSERT INTO `Library` (`id`,`time`,`cost`,`rate`,`clear_time`) VALUES ('6','12','25','2','20') ON DUPLICATE KEY UPDATE `id` = '6',`time` = '12',`cost` = '25',`rate` = '2',`clear_time` = '20';
