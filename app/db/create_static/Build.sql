--
-- Table structure for table `Build`
--
DROP TABLE IF EXISTS `Build`;
CREATE TABLE IF NOT EXISTS `Build` (
`id` int(11) NOT NULL  COMMENT '主键
',
`build_name` int(11) COMMENT '名称
',
`desc1` varchar(512) COMMENT '描述文字',
`origin_build_id` int(11) COMMENT '原型建筑id
修改一定要先找李寒松确认
1-官府
2-城墙
3-战争工坊
4-步兵营
5-弓兵营
6-骑兵营
7-车兵营
8-仓库
9-铁匠铺
10-研究所
11-屯所
12-哨塔
14-酒馆
16-金矿
21-伐木场
26-农田
31-石料场
36-铁矿场
41-校场
42-医院
43-战争大厅
44-雇佣兵营地
45-集市
46-磨坊
47-武斗
48-神龛
49-观星台',
`build_type` int(11) COMMENT '类型
1-城内建筑
2-城下建筑、资源建筑',
`build_lv_sign` int(11) COMMENT '建筑等级1级标记
',
`build_level` int(11) COMMENT '建筑等级',
`construction_time` int(11) COMMENT '建造时间（秒）',
`pre_build_id` text COMMENT '前置建筑id
中间用;隔开',
`cost` text COMMENT '作者:
升级消耗
格式：
道具ID,数量（中间用分号隔开）
1-黄金
2-粮食
3-木材
4-石矿
5-铁矿',
`cost_item_id` int(11) ,
`cost_item_num` int(11) ,
`gem_cost` int(11) COMMENT '作者:
花费元宝建造',
`description` int(11) COMMENT '描述文字',
`desc2` varchar(512) COMMENT '描述文字',
`power` int(11) COMMENT '战斗能力',
`img` int(11) ,
`img_1` int(11) ,
`img_2` int(11) ,
`choose_img` int(11) ,
`build_drop` text COMMENT '作者:
建筑完成时获得奖励',
`hammer_img` int(11) ,
`cost_general` int(11) COMMENT '作者:
可驻守武将数',
`cost_general_open_level` int(11) ,
`general_exp` int(11) COMMENT '作者:
驻守武将获得经验
每分钟结算一次',
`output` text COMMENT '建筑产出
格式：产出类型,产出数值
可能有多个产出，中间用分号隔开',
`unlock` text COMMENT '陈涛:（王大师专用）
解锁类型，表现形式，解锁ID
解锁类型：
1-解锁士兵
2-解锁陷阱
3-解锁科技
4-解锁学习栏位
5-没有解锁，固定文字

表现形式
0-文字
1-图标+文字',
`x_y` varchar(512) COMMENT '作者:
建筑所在位置坐标',
`general_sort` text COMMENT '作者:
前端驻扎武将时根据武将属性排序排序
1-武力
2-智力
3-统治力
4-政治
5-魅力',
`build_menu_1` text COMMENT '建筑点开菜单
默认正常状态',
`build_menu_2` text COMMENT '建筑点开菜单
升级中',
`build_menu_101` text COMMENT '建筑点开菜单
资源建筑加速中',
`build_menu_3` text COMMENT '建筑点开菜单
工作中',
`need_buff_id` text ,
`build_zoom` varchar(512) ,
`storage_max` int(11) COMMENT '用于资源建筑，可储存的最大资源量
',
`output_buff_id` int(11) COMMENT '武将对该建筑加成的buffid',
`need_general_attribute` int(11) COMMENT '作者:
需要武将属性
0-不可驻扎
1-武力
2-智力
3-统治力
4-魅力
5-政治',
`ratio` int(11) COMMENT '作者:
万分比buff：武将主属性*ratio/10000
固定值buff:武将主属性*ratio/10000',
`build_lv_show` int(11) COMMENT '作者:
等级牌子显示',
`upgrade_soldier_id` int(11) COMMENT '作者:
建筑升级时的目标兵种',
`original_soldier_id` int(11) COMMENT '作者:
建筑升级时需要自动升级的兵种',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
