TRUNCATE `Warfare_service_config`;
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('1','open_time','20:00:00','跨服战开始时间
时间显示方式
08:00:00');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('2','ready_time','3','战斗准备时间
分钟
');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('3','fight_time','30','战斗最长时间
分钟');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('4','wf_gate1_hitpoint','floor(20000+$lv*400)','城门1血量，根据守方玩家总府衙等级计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('5','wf_gate2_hitpoint','floor(15000+$lv*300)','城门2血量，根据守方玩家总府衙等级计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('6','wf_gate3_hitpoint','floor(10000+$lv*200)','城门3血量，根据守方玩家总府衙等级计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('7','wf_playercastle_hitpoint','2000+$lv*200','玩家城防值，根据玩家府衙等级计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('8','wf_catapult_atkpower','floor(3000+pow($power,0.5)*3)','投石车攻击力，根据驻守部队战力计算，');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('9','wf_catapult_atkcolddown','30','投石车攻击cd时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('10','wf_warhammer_hitpoint','floor(2000+$lv*40)','攻城锤血量，根据攻击方玩家总府衙等级计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('11','wf_warhammer_atkpower','floor(1000+pow($power,0.7)*2)','攻城锤攻击力，根据驻守部队总战力计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('12','wf_warhammer_atkcolddown','30','攻城锤攻击cd时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('13','wf_glaivethrower_atkpower','floor(1500+pow($power,0.5)*2)','床弩攻击力，根据驻守部队战力计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('14','wf_glaivethrower_atkcolddown','30','床弩攻击cd时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('15','wf_ladder_hitpoint','floor(1000+$lv*20)','云梯血量，根据攻击方玩家总府衙等级计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('16','wf_ladder_max_progress','10000','云梯进度最大值');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('17','wf_ladder_progress','floor(10+pow($power,0.5)/100)','云梯进度增量，根据驻守部队总战力计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('18','wf_ladder_progress_colddown','30','云梯进度增加cd时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('19','wf_ladder_respawn_time','60','云梯重生时间(秒)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('20','wf_basecastle_hitpoint','floor(10000+$lv*200)','防守方大本营血量，根据守方玩家总府衙等级计算');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('21','wf_soldier_count_start','5000','初始士兵数量');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('22','wf_soldier_count_limit','5000','士兵数量上限(大于等于该上限后不能再购买士兵，玩家可以在4999的时候再次购买)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('23','wf_legion_count_limit','2','军团数量上限');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('24','wf_march_speed_buff','50000','行军速度buff，万分比');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('25','wf_defender_respawn_time','30','防守方城池复活时间(秒)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('26','wf_playercastle_respawn_price','10','城池立即复活消耗(元宝)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('27','wf_playercastle_respawn_soldier_count','5000','城池复活后获得的初始士兵数量');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('28','wf_reinforcement_soldier_count','1000','购买士兵数量');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('29','wf_reinforcement_soldier_price','19','购买士兵价格(个人荣誉)costid');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('30','wf_castle_teleport_colddown','120','城战迁城cd时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('31','wf_march_speed_burst','100','城战行军加速50%的价格');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('32','wf_winner_reward','620001','参与者获胜奖');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('33','wf_loser_reward','620002','参与者失败奖');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('34','wf_guild_winner_reward','620003','全盟大锅饭(胜者)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('35','team_join_num','10','一个联盟可以进入的最大城池数量');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('36','wf_warhammer_respawn_time','60','攻城锤重生时间(秒)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('37','all_soldier','50001','万能士兵ID');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('38','wf_atkcastle_hitpointlost','floor(1000+pow($power,0.7)/3)','攻击玩家城堡战斗获胜后的城防损失(根据进攻玩家获胜后剩余战力计算)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('39','wf_atkgate_hitpointlost','floor(1000+pow($power,0.5)*4)','玩家攻击城门后的城防损失(根据进攻玩家战力计算)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('40','wf_atkbasecastle_hitpointlost','floor(1000+pow($power,0.5)*4)','玩家攻击大本营后的城防损失(根据进攻玩家战力计算)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('41','wf_reinforcement_soldier_price_gem','20','购买士兵价格(元宝)costid');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('42','wf_enroll_start','08:00:00','报名开始时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('43','wf_match_start','19:00:00','报名截止时间/匹配开始时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('44','wf_award_start','21:10:00','发奖时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('45','wf_close_time','21:10:00','活动结束时间');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('46','wf_guild_loser_reward','620004','全盟大锅饭(败者)');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('47','wf_guild_city_level','20','联盟报名参赛的成员城池等级底线');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('48','wf_guild_num','10','联盟报名参赛的成员人数底线');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('49','wf_attacker_respawn_time','20','攻击方复活cd时间（秒）');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('50','wf_attacker_respawn_add_time','5','攻方复活递增时间（秒）');
INSERT INTO `Warfare_service_config` (`id`,`name`,`data`,`text`) VALUES ('51','wf_defender_respawn_add_time','5','守方复活递增时间（秒）');
