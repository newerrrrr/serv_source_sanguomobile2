--
-- Table structure for table `Map_Element`
--
DROP TABLE IF EXISTS `Map_Element`;
CREATE TABLE IF NOT EXISTS `Map_Element` (
`id` int(11) NOT NULL  COMMENT '作者:
origin_id*100+level',
`name` int(11) ,
`desc1` varchar(512) ,
`origin_id` int(11) COMMENT '有改动先找李寒松确认
1-联盟堡垒
2-联盟箭塔
3-联盟金矿
4-联盟粮田
5-联盟伐木场
6-联盟石料场
7-联盟铁矿场
8-联盟仓库
9-世界金矿
10-世界粮田
11-世界伐木场
12-世界石料场
13-世界铁矿场
14-NPC（小怪）
15-玩家城堡
16-王城
17-多人怪物
18-中级营寨
19-低级营寨
20-占地山水(服务器用)
21-和氏璧争夺
22-据点
',
`level` int(11) ,
`max_count` int(11) COMMENT '作者:
建筑最大数量
-1为没有数量上限',
`alliance_type` int(11) COMMENT '作者:
建筑类型
1-联盟堡垒
2-联盟超级矿
3-联盟矿场
4-联盟仓库
5-资源建筑
6-NPC
7-玩家城堡
8-王城
9-王城中营寨
10-王城小营寨
11-占地山水
12-野外boss（多人怪物）
13-和氏璧
',
`lattice` int(11) COMMENT '作者:
建筑占据几个格子',
`need_level` int(11) COMMENT '作者:
开启需要玩家官府等级',
`x_y` text COMMENT '作者:
贴图坐标',
`range` int(11) COMMENT '作者:
正方形半个边长距离
',
`img` text COMMENT '作者:
美术资源
对应不同状态',
`img_self` text ,
`img_enemy` text ,
`img_atk` int(11) COMMENT '作者:
美术资源
对应不同状态',
`img_death` int(11) ,
`alliance_death` text COMMENT '作者:
联盟堡垒破损
',
`image_lv_back` int(11) COMMENT '作者:
怪物等级的牌子
',
`element_lv_show` varchar(512) COMMENT '作者:
野外等级资源显示
最小值，最大值，牌匾资源ID，最小值，最大值，牌匾资源ID',
`lv_xy` text COMMENT '作者:
建筑物等级坐标',
`alliance_img` int(11) ,
`img_mail` int(11) COMMENT '作者:
邮件ICON
',
`imy_help` text COMMENT '作者:
建筑说明图
',
`desc_help` text ,
`help_type_id` int(11) COMMENT '作者:
对应HELP_TYPE表的ID
',
`img_boss_head` int(11) COMMENT '作者:
BOSS头像',
`starting_num` int(11) COMMENT '作者:
初始值',
`max_num` int(11) COMMENT '作者:
max建造值
max资源总值
max联盟仓库的上限',
`max_res` int(11) COMMENT '作者:
最大资源数',
`collection` int(11) COMMENT '作者:
每分钟采集值
锦囊是每小时采集值
',
`description` int(11) COMMENT '作者:
建筑介绍',
`desc2` varchar(512) ,
`max_stationed` int(11) COMMENT '作者:
max驻扎军团数
0-不能驻扎',
`max_construction` int(11) COMMENT '作者:
max建造军团数
0-不能建造',
`build_atk` int(11) ,
`build_skill` text ,
`npc_id` int(11) ,
`build_zoom` varchar(512) ,
`anim_time` int(11) COMMENT '作者:
怪物动画播放次数',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
