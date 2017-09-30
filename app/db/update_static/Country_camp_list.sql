-- INSERT UPDATE sql for 'Country_camp_list';
INSERT INTO `Country_camp_list` (`id`,`camp_name`,`desc`,`camp_pic`,`short_name`) VALUES ('1','711001','魏','1081001','712001') ON DUPLICATE KEY UPDATE `id` = '1',`camp_name` = '711001',`desc` = '魏',`camp_pic` = '1081001',`short_name` = '712001';
INSERT INTO `Country_camp_list` (`id`,`camp_name`,`desc`,`camp_pic`,`short_name`) VALUES ('2','711002','蜀','1081002','712002') ON DUPLICATE KEY UPDATE `id` = '2',`camp_name` = '711002',`desc` = '蜀',`camp_pic` = '1081002',`short_name` = '712002';
INSERT INTO `Country_camp_list` (`id`,`camp_name`,`desc`,`camp_pic`,`short_name`) VALUES ('3','711003','吴','1081003','712003') ON DUPLICATE KEY UPDATE `id` = '3',`camp_name` = '711003',`desc` = '吴',`camp_pic` = '1081003',`short_name` = '712003';
