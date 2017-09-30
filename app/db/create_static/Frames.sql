--
-- Table structure for table `Frames`
--
DROP TABLE IF EXISTS `Frames`;
CREATE TABLE IF NOT EXISTS `Frames` (
`id` int(11) NOT NULL  COMMENT '作者:
千位1=魏国，2=蜀国，3=吴国，4=群雄',
`plist` int(11) ,
`playstates` varchar(512) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
