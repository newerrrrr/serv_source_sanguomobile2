ALTER TABLE `player_online_award` ADD INDEX `pid` (`player_id`, `date_limit`);
ALTER TABLE `player_sign_award` ADD INDEX `pid` (`player_id`, `round_flag`);
ALTER TABLE `player_soldier_injured` ADD INDEX `pid` (`player_id`);
ALTER TABLE `player_target` ADD INDEX `pid` (`player_id`, `award_status`);
ALTER TABLE `player_time_limit_match` ADD INDEX `pid` (`player_id`, `time_limit_match_list_id`);
ALTER TABLE `player_time_limit_match_total` ADD INDEX `pid` (`player_id`, `time_limit_match_config_id`);
ALTER TABLE `time_limit_match_config` ADD INDEX `status` (`status`);
