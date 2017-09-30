-- INSERT UPDATE sql for 'King_gift';
INSERT INTO `King_gift` (`id`,`gift_name`,`gift_id`,`max_count`,`desc`) VALUES ('1','280101','470001','10','守卫礼包') ON DUPLICATE KEY UPDATE `id` = '1',`gift_name` = '280101',`gift_id` = '470001',`max_count` = '10',`desc` = '守卫礼包';
INSERT INTO `King_gift` (`id`,`gift_name`,`gift_id`,`max_count`,`desc`) VALUES ('2','280102','470002','20','援助礼包') ON DUPLICATE KEY UPDATE `id` = '2',`gift_name` = '280102',`gift_id` = '470002',`max_count` = '20',`desc` = '援助礼包';
INSERT INTO `King_gift` (`id`,`gift_name`,`gift_id`,`max_count`,`desc`) VALUES ('3','280103','470003','5','征战礼包') ON DUPLICATE KEY UPDATE `id` = '3',`gift_name` = '280103',`gift_id` = '470003',`max_count` = '5',`desc` = '征战礼包';
