-- INSERT UPDATE sql for 'Plist';
INSERT INTO `Plist` (`id`,`path`,`desc1`) VALUES ('2000001','client/map_build/wood.plist','世界地图-木材') ON DUPLICATE KEY UPDATE `id` = '2000001',`path` = 'client/map_build/wood.plist',`desc1` = '世界地图-木材';
