-- INSERT UPDATE sql for 'Country_basic_setting';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('1','button1_limit','10') ON DUPLICATE KEY UPDATE `id` = '1',`name` = 'button1_limit',`data` = '10';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('2','button2_limit','10') ON DUPLICATE KEY UPDATE `id` = '2',`name` = 'button2_limit',`data` = '10';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('3','button3_limit','4') ON DUPLICATE KEY UPDATE `id` = '3',`name` = 'button3_limit',`data` = '4';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('4','exp_gain_inc_rate','1.3') ON DUPLICATE KEY UPDATE `id` = '4',`name` = 'exp_gain_inc_rate',`data` = '1.3';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('5','cseason_duration','12') ON DUPLICATE KEY UPDATE `id` = '5',`name` = 'cseason_duration',`data` = '12';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('6','open_date','3,6') ON DUPLICATE KEY UPDATE `id` = '6',`name` = 'open_date',`data` = '3,6';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('7','enroll_start','08:00:00') ON DUPLICATE KEY UPDATE `id` = '7',`name` = 'enroll_start',`data` = '08:00:00';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('8','vip_enroll_start','12') ON DUPLICATE KEY UPDATE `id` = '8',`name` = 'vip_enroll_start',`data` = '12';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('9','match_ready','19:00:00') ON DUPLICATE KEY UPDATE `id` = '9',`name` = 'match_ready',`data` = '19:00:00';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('10','match_start','20:00:00') ON DUPLICATE KEY UPDATE `id` = '10',`name` = 'match_start',`data` = '20:00:00';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('11','match_gate_ready','3') ON DUPLICATE KEY UPDATE `id` = '11',`name` = 'match_gate_ready',`data` = '3';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('12','match_gate_duration','15') ON DUPLICATE KEY UPDATE `id` = '12',`name` = 'match_gate_duration',`data` = '15';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('13','match_fight_ready','3') ON DUPLICATE KEY UPDATE `id` = '13',`name` = 'match_fight_ready',`data` = '3';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('14','match_fight_duration','15') ON DUPLICATE KEY UPDATE `id` = '14',`name` = 'match_fight_duration',`data` = '15';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('15','close_time','21:10:00') ON DUPLICATE KEY UPDATE `id` = '15',`name` = 'close_time',`data` = '21:10:00';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('16','award_start','21:10:00') ON DUPLICATE KEY UPDATE `id` = '16',`name` = 'award_start',`data` = '21:10:00';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('17','vip_sign_up_condition','5000') ON DUPLICATE KEY UPDATE `id` = '17',`name` = 'vip_sign_up_condition',`data` = '5000';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('18','arrow_sign_up_condition','52121') ON DUPLICATE KEY UPDATE `id` = '18',`name` = 'arrow_sign_up_condition',`data` = '52121';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('19','normal_sign_up_condition','20') ON DUPLICATE KEY UPDATE `id` = '19',`name` = 'normal_sign_up_condition',`data` = '20';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('20','battle_skill_refresh_res_return_value','100') ON DUPLICATE KEY UPDATE `id` = '20',`name` = 'battle_skill_refresh_res_return_value',`data` = '100';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('21','battle_skill_upgrade_res_return_value','1') ON DUPLICATE KEY UPDATE `id` = '21',`name` = 'battle_skill_upgrade_res_return_value',`data` = '1';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('22','get_ladder_second','1') ON DUPLICATE KEY UPDATE `id` = '22',`name` = 'get_ladder_second',`data` = '1';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('23','get_ladder_score','1') ON DUPLICATE KEY UPDATE `id` = '23',`name` = 'get_ladder_score',`data` = '1';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('24','damage_gate_points','1') ON DUPLICATE KEY UPDATE `id` = '24',`name` = 'damage_gate_points',`data` = '1';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('25','damage_gate_score','1') ON DUPLICATE KEY UPDATE `id` = '25',`name` = 'damage_gate_score',`data` = '1';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('26','kill_soldier_num','1') ON DUPLICATE KEY UPDATE `id` = '26',`name` = 'kill_soldier_num',`data` = '1';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('27','kill_soldier_score','1') ON DUPLICATE KEY UPDATE `id` = '27',`name` = 'kill_soldier_score',`data` = '1';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('28','camp_change_cost_honor','100000') ON DUPLICATE KEY UPDATE `id` = '28',`name` = 'camp_change_cost_honor',`data` = '100000';
INSERT INTO `Country_basic_setting` (`id`,`name`,`data`) VALUES ('29','camp_change_cost_gem','100') ON DUPLICATE KEY UPDATE `id` = '29',`name` = 'camp_change_cost_gem',`data` = '100';