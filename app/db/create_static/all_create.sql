--
-- Table structure for table `Activity`
--
DROP TABLE IF EXISTS `Activity`;
CREATE TABLE IF NOT EXISTS `Activity` (
`id` int(11) NOT NULL ,
`activity_name` int(11) COMMENT '作者:
最多7个字',
`name_dec` varchar(512) ,
`activity_desc` int(11) COMMENT '作者:
最多7个字',
`desc` varchar(512) ,
`date_type` int(11) COMMENT '作者:
1、永久性活动
2、时效性
3、开服性',
`open_date` int(11) ,
`close_date` int(11) ,
`show_open_date` int(11) COMMENT '作者:
活动重新开启间隔时间
天
1002限时比赛活动=隔X天后重新开启
1004充值礼包活动=隔X天后重新抽取',
`show_close_date` int(11) ,
`active_same` int(11) ,
`type_icon` int(11) ,
`drop` text ,
`interval` int(11) ,
`show_order` int(11) COMMENT '作者:
列表显示顺序',
`banner_show` int(11) ,
`path_type` int(11) COMMENT '作者:
运营活动入口区分',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Growth_Number_reward`
--
DROP TABLE IF EXISTS `Growth_Number_reward`;
CREATE TABLE IF NOT EXISTS `Growth_Number_reward` (
`id` int(11) NOT NULL  COMMENT '购买人数-奖励表

',
`number` int(11) COMMENT '总计购买人数',
`drop` int(11) COMMENT '奖励dropid',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Growth_Level_reward`
--
DROP TABLE IF EXISTS `Growth_Level_reward`;
CREATE TABLE IF NOT EXISTS `Growth_Level_reward` (
`id` int(11) NOT NULL  COMMENT '府衙等级-奖励表

',
`level` int(11) COMMENT '府衙等级',
`drop` int(11) COMMENT '奖励dropid',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Activity_Commodity`
--
DROP TABLE IF EXISTS `Activity_Commodity`;
CREATE TABLE IF NOT EXISTS `Activity_Commodity` (
`id` int(11) NOT NULL ,
`activity_id` int(11) ,
`series` int(11) COMMENT '陆阳:
编组，同ID为一个类型的活动礼包',
`show_priority` int(11) COMMENT '陆阳:
BANNER图弹出优先级',
`series_order` int(11) COMMENT '陆阳:
礼包购买顺序',
`gift_type` varchar(512) COMMENT '对应pricing中的gift_type,用于对应充值项',
`drop_condition` int(11) COMMENT '累计充值使用
',
`drop_id` int(11) COMMENT '
掉落ID
',
`open_time` int(11) COMMENT '充值项开启时间
Unix时间戳',
`close_time` int(11) COMMENT '充值项关闭时间',
`activity_type` int(11) COMMENT '
活动类型
',
`act_same_index` int(11) ,
`guild_drop_id` int(11) COMMENT '徐力丰:
公会成员获取掉落
0 不发公会成员
',
`show_price` int(11) ,
`ratio` int(11) COMMENT '徐力丰:
用于客户端显示礼包价值比例 百分比
例如200 则显示200%',
`desc` int(11) ,
`language` varchar(512) ,
`day_limit` int(11) COMMENT '该礼包仅在开服一定天数内才显示，仅对activity_id为1005的生效 0表示永久显示
',
`gift_icon` int(11) COMMENT '陆阳:
礼包界面Banner条
',
`gift_banner` int(11) COMMENT '陆阳:
进入游戏弹出BANNER
',
`priority` int(11) COMMENT '陆阳:
进入游戏BANNER优先级',
`desc2` int(11) COMMENT '陆阳:
限购一次显示',
`gift_show_icon` int(11) COMMENT '陆阳:
每个礼包外面显示的ICON
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Activity_order`
--
DROP TABLE IF EXISTS `Activity_order`;
CREATE TABLE IF NOT EXISTS `Activity_order` (
`id` int(11) NOT NULL ,
`if_circle` int(11) COMMENT '徐力丰:
0 开服礼包，不参与循环
1 循环
每一组礼包出售时间为acitvity  inteval字段决定 ',
`series` text COMMENT '陆阳:
活动礼包id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Quick_money`
--
DROP TABLE IF EXISTS `Quick_money`;
CREATE TABLE IF NOT EXISTS `Quick_money` (
`id` int(11) NOT NULL ,
`drop` int(11) ,
`cost_id` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `act_newbie_sign`
--
DROP TABLE IF EXISTS `act_newbie_sign`;
CREATE TABLE IF NOT EXISTS `act_newbie_sign` (
`id` int(11) NOT NULL ,
`drop` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `act_newbie_recharge`
--
DROP TABLE IF EXISTS `act_newbie_recharge`;
CREATE TABLE IF NOT EXISTS `act_newbie_recharge` (
`id` int(11) NOT NULL ,
`drop` text COMMENT '作者:
奖励',
`recharge_price` int(11) COMMENT '作者:
累充档位',
`open_date` int(11) COMMENT '作者:
第几天开',
`close_date` int(11) COMMENT '作者:
第几天结束',
`period` int(11) COMMENT '作者:
周期，（打组）',
`desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `act_newbie_cost`
--
DROP TABLE IF EXISTS `act_newbie_cost`;
CREATE TABLE IF NOT EXISTS `act_newbie_cost` (
`id` int(11) NOT NULL ,
`drop` text ,
`cost_price` int(11) COMMENT '作者:
花费元宝数量
价格为累计计算
如需求：第一档消费200，第二档消费',
`open_date` int(11) ,
`close_date` int(11) ,
`period` int(11) COMMENT '作者:
周期，（打组）',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance`
--
DROP TABLE IF EXISTS `Alliance`;
CREATE TABLE IF NOT EXISTS `Alliance` (
`id` int(11) NOT NULL ,
`alliance_architectures_name` varchar(512) COMMENT '陈涛:
联盟建筑名称',
`alliance_construction_time` varchar(512) COMMENT '陈涛:
升级所需时间/秒',
`open_condition` int(11) COMMENT '陈涛:
开放条件
1-人数达到多少
2-联盟战斗力达到多少
3-联盟科技达到多少',
`open_num` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_science`
--
DROP TABLE IF EXISTS `Alliance_science`;
CREATE TABLE IF NOT EXISTS `Alliance_science` (
`id` int(11) NOT NULL ,
`name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
`level_type` int(11) COMMENT '陈涛:
联盟阶段
1-第一阶段
2-第二阶段
3-第三阶段',
`open_task` int(11) ,
`science_type` int(11) ,
`button1_cost_id` text ,
`button2_cost_id` text ,
`button3_cost_id` text ,
`buff` text COMMENT '陈涛:
对应alliance_buff表',
`buff_num_type` int(11) COMMENT '陈涛:
1-万分比
2-具体指',
`buff_num` int(11) ,
`next_buff_num` int(11) ,
`alliance_science_drop` int(11) ,
`star` int(11) ,
`max_star` int(11) ,
`level` int(11) ,
`show_lv` int(11) ,
`max_level` int(11) ,
`levelup_exp` int(11) ,
`button1_drop` text COMMENT '陈涛:
对应奖励
三个id分别对应三个按钮的奖励',
`button2_drop` text COMMENT '陈涛:
对应奖励
三个id分别对应三个按钮的奖励',
`button3_drop` text COMMENT '陈涛:
对应奖励
三个id分别对应三个按钮的奖励',
`button1_honor` int(11) COMMENT '公会荣誉
',
`button2_honor` int(11) COMMENT '公会荣誉
',
`button3_honor` int(11) COMMENT '公会荣誉
',
`button1_exp` int(11) ,
`button2_exp` int(11) ,
`button3_exp` int(11) ,
`up_time` int(11) COMMENT '陈涛:
升阶时间（秒）',
`icon_img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_build`
--
DROP TABLE IF EXISTS `Alliance_build`;
CREATE TABLE IF NOT EXISTS `Alliance_build` (
`id` int(11) NOT NULL ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Right`
--
DROP TABLE IF EXISTS `Right`;
CREATE TABLE IF NOT EXISTS `Right` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_starting`
--
DROP TABLE IF EXISTS `Alliance_starting`;
CREATE TABLE IF NOT EXISTS `Alliance_starting` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`data` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_shop`
--
DROP TABLE IF EXISTS `Alliance_shop`;
CREATE TABLE IF NOT EXISTS `Alliance_shop` (
`id` int(11) NOT NULL  COMMENT '陈涛:
1-商店
2-联盟商店（跟后面的item_id一致）',
`item_id` int(11) ,
`alliance_cost` int(11) ,
`player_cost` int(11) ,
`count` int(11) COMMENT '陈涛:
数量',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_flag`
--
DROP TABLE IF EXISTS `Alliance_flag`;
CREATE TABLE IF NOT EXISTS `Alliance_flag` (
`id` int(11) NOT NULL  COMMENT '陆阳:
1开头为旗子颜色
2开头为旗子样式
3开头为旗子花纹',
`type` int(11) COMMENT '陆阳:
1为旗子
2为特效',
`res_flag` int(11) COMMENT '陆阳:
工会图标资源填写RES ID
',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Buff`
--
DROP TABLE IF EXISTS `Buff`;
CREATE TABLE IF NOT EXISTS `Buff` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`description` int(11) ,
`desc1` varchar(512) ,
`starting_num` int(11) COMMENT '陈涛:
buff初始值
百分比的为万分比
时间的以秒为单位',
`buff_type` int(11) COMMENT '陈涛:
1-万分比
2-具体值',
`desc` varchar(512) COMMENT '游戏中所有 速度增加x%的buff都用以下方式实现：
最终耗时=速度未增加之前的耗时/(1+x%)
例如：建筑建造速度增加x%的buff实际效果为
建筑建造时间=读表时间/(1+x%)

游戏中所有数量增加x%的buff都用以下方式实现：
最终数量=原始数量*(1+x%)
例如：步兵攻击增加x%
步兵攻击=读表攻击*(1+x%)

多个buff的百分比数值用加法计算总和。',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Buff_temp`
--
DROP TABLE IF EXISTS `Buff_temp`;
CREATE TABLE IF NOT EXISTS `Buff_temp` (
`id` int(11) NOT NULL ,
`buff_id` int(11) ,
`buff_num` int(11) COMMENT '陈涛:
万分比
加成数量',
`buff_desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

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

--
-- Table structure for table `Build_position`
--
DROP TABLE IF EXISTS `Build_position`;
CREATE TABLE IF NOT EXISTS `Build_position` (
`id` int(11) NOT NULL ,
`build_id` text COMMENT '作者:
这个坑位可造建筑
多个建筑用分好隔开',
`build_type` int(11) COMMENT '类型
1-城内建筑
2-城下建筑、资源建筑',
`lvup_build_effect` text ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Output_type`
--
DROP TABLE IF EXISTS `Output_type`;
CREATE TABLE IF NOT EXISTS `Output_type` (
`id` int(11) NOT NULL ,
`output_type` int(11) ,
`num_type` int(11) COMMENT '作者:
前端用于显示基础值的显示类型
0-无效果
1-万分比
2-固定值',
`buff_id` int(11) COMMENT '作者:
对应buff表buff_id
0-没有buff_id
',
`buff_name` varchar(512) ,
`plus_type` int(11) COMMENT '增加方式
to:武将buff
1-公式算出百分比（小数点后两位），百分比增加，再乘基础值
2-公式算出百分比（小数点后两位），百分比增加
3-公式算出具体值，值要向下取整，直接增加

to:playerbuff
1、拿到玩家buff值，除10000，得到百分比，再乘基础值，按固定值显示（如：100）
2、拿到玩家buff值，除10000，得到百分比，加百分号，显示百分比（如：10%）
3、拿到玩家buff值，不做任何处理，直接显示（如：100）',
`formula` varchar(512) COMMENT '作者:
公式
属性是指建造所需属性
',
`desc` int(11) ,
`desc1` varchar(512) ,
`information_title` int(11) ,
`information_desc` varchar(512) ,
`desc2` varchar(512) ,
`desc3` varchar(512) ,
`need_general_attribute` int(11) COMMENT '作者:
需要武将属性
0-不可驻扎
1-武力
2-智力
3-统治力
4-魅力
5-政治',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Build_info`
--
DROP TABLE IF EXISTS `Build_info`;
CREATE TABLE IF NOT EXISTS `Build_info` (
`id` int(11) NOT NULL ,
`build_type` int(11) ,
`desc1` varchar(512) ,
`description` text ,
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Build_menu`
--
DROP TABLE IF EXISTS `Build_menu`;
CREATE TABLE IF NOT EXISTS `Build_menu` (
`id` int(11) NOT NULL  COMMENT '对应建筑原始ID
',
`img` int(11) ,
`name` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Build_buff_type`
--
DROP TABLE IF EXISTS `Build_buff_type`;
CREATE TABLE IF NOT EXISTS `Build_buff_type` (
`id` int(11) NOT NULL ,
`buff_id` text ,
`name` int(11) ,
`name1` varchar(512) ,
`dec` int(11) ,
`dec1` varchar(512) ,
`dec_start` int(11) COMMENT '作者:
激活后显示文字
',
`dec2` varchar(512) ,
`res_up` int(11) ,
`res` int(11) ,
`res_down` int(11) ,
`link` text COMMENT '作者:
关联SHOP ID',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Cost`
--
DROP TABLE IF EXISTS `Cost`;
CREATE TABLE IF NOT EXISTS `Cost` (
`id` int(11) NOT NULL ,
`cost_id` int(11) ,
`min_count` int(11) COMMENT '作者:
有次数的时候
最小次数
0-没有次数限制',
`max_count` int(11) COMMENT '作者:
有次数的时候
最大次数
0-没有次数限制',
`cost_type` int(11) COMMENT '作者:
消耗的货币类型
1-黄金
2-粮食
3-木头
4-石材
5-铁矿
6-白银
7-gem
8-个人荣誉
9-体力
10-主公经验
11-联盟科技经验
12-联盟荣誉
13-锦囊
14-铜币
15-勾玉
20-战勋
21-玄铁
22-将印
23-军资',
`cost_num` int(11) COMMENT '作者:
花费的具体数量
如果花费的是不固定的值，这里写0',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_science`
--
DROP TABLE IF EXISTS `Country_science`;
CREATE TABLE IF NOT EXISTS `Country_science` (
`id` int(11) NOT NULL ,
`name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
`science_type` int(11) ,
`button1_consume` text COMMENT '徐力丰:
按钮1消耗
0=不显示该按钮',
`button2_consume` text ,
`button3_consume` text ,
`num_type` int(11) COMMENT '
1-万分比
2-具体值',
`num_value` int(11) ,
`buff` text COMMENT '陈涛:
对应alliance_buff表',
`level` int(11) ,
`max_level` int(11) ,
`levelup_exp` int(11) ,
`button1_drop` text COMMENT '徐力丰:
按钮1军资奖励',
`button2_drop` text ,
`button3_drop` text ,
`button1_exp` int(11) COMMENT '徐力丰:
按钮1科技经验',
`button2_exp` int(11) ,
`button3_exp` int(11) ,
`icon_img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_science_exp`
--
DROP TABLE IF EXISTS `Country_science_exp`;
CREATE TABLE IF NOT EXISTS `Country_science_exp` (
`id` int(11) NOT NULL ,
`week_number` int(11) COMMENT '徐力丰:
跨服战第几周',
`autoexp_per_hour` int(11) ,
`player_exp_rate` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_basic_setting`
--
DROP TABLE IF EXISTS `Country_basic_setting`;
CREATE TABLE IF NOT EXISTS `Country_basic_setting` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`data` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_quest`
--
DROP TABLE IF EXISTS `Alliance_quest`;
CREATE TABLE IF NOT EXISTS `Alliance_quest` (
`id` int(11) NOT NULL ,
`country_id` int(11) COMMENT '徐力丰:
0三国通用
1魏国
2蜀国
3吴国
',
`step_id` int(11) ,
`name` int(11) ,
`desc1` varchar(512) ,
`alliance_quest_type` int(11) ,
`num_value` int(11) COMMENT '徐力丰:
完成任务所需的数值配置
任务类型为3时 该字段是城市id',
`alliance_quest_reward` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_battle_drop`
--
DROP TABLE IF EXISTS `Country_battle_drop`;
CREATE TABLE IF NOT EXISTS `Country_battle_drop` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '奖励类型：
1、城门战奖励（失败阵营获得）
2、城内战奖励胜利（攻击方获得）
3、城内战奖励失败（攻击方获得）
4、城内战奖励胜利（防守方获得）
5、城内战奖励失败（防守方获得）
6、羽林军称号奖励',
`rank_min` int(11) COMMENT '作者:
最小排名
',
`rank_max` int(11) COMMENT '作者:
最大排名',
`drop` int(11) COMMENT '作者:
掉落ID',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_battle_title`
--
DROP TABLE IF EXISTS `Country_battle_title`;
CREATE TABLE IF NOT EXISTS `Country_battle_title` (
`id` int(11) NOT NULL ,
`rank` int(11) COMMENT '作者:
排名
',
`title_name` int(11) COMMENT '作者:
多语言ID',
`title_name_desc` varchar(512) COMMENT '作者:
称号批注',
`rank_pic` int(11) ,
`drop` int(11) ,
`buff_id` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_city_map`
--
DROP TABLE IF EXISTS `Country_city_map`;
CREATE TABLE IF NOT EXISTS `Country_city_map` (
`id` int(11) NOT NULL ,
`city_type` int(11) COMMENT '陆阳:
1=出生地
2=可攻击打城池
3=可攻击小城池',
`ctiy_name` int(11) ,
`desc` varchar(512) ,
`city_pic` int(11) ,
`city_bg_pic` int(11) ,
`link` text COMMENT '陆阳:
城池连线
在此位置可进攻的城池',
`point` int(11) COMMENT '陆阳:
占领城池获得积分
',
`join_max_num` int(11) COMMENT '陆阳:
每个国家最多加入的人数',
`default_belong` int(11) COMMENT '陆阳:
type=1时，默认归属国家',
`drop` int(11) COMMENT '陆阳:
城池固定奖励
',
`shop_position` text COMMENT '陆阳:
各个城池商城位置坐标',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_camp_list`
--
DROP TABLE IF EXISTS `Country_camp_list`;
CREATE TABLE IF NOT EXISTS `Country_camp_list` (
`id` int(11) NOT NULL ,
`camp_name` int(11) ,
`desc` varchar(512) ,
`camp_pic` int(11) ,
`short_name` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Country_recommend_tips`
--
DROP TABLE IF EXISTS `Country_recommend_tips`;
CREATE TABLE IF NOT EXISTS `Country_recommend_tips` (
`id` int(11) NOT NULL ,
`location` int(11) COMMENT '作者:
1=攻方
2=守方',
`path_type` int(11) COMMENT '作者:
1=A路线
2=B路线
',
`task_type` int(11) COMMENT '作者:
1=主线
2=支线',
`camp` int(11) COMMENT '作者:
不同的触发条件',
`open_type` text COMMENT '陆阳:
1=城门战
2=城内战',
`desc` int(11) ,
`desc1` varchar(512) ,
`to_target` int(11) ,
`skip_type` int(11) ,
`skip_show` text ,
`priority` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Drop`
--
DROP TABLE IF EXISTS `Drop`;
CREATE TABLE IF NOT EXISTS `Drop` (
`id` int(11) NOT NULL  COMMENT '陈涛:
通用掉落
1~99999999
开头不同对应系统不同',
`drop_type` int(11) COMMENT '陈涛:
掉落类型
1-整组奖励中部分掉落，掉落数量根据drop_count值
2-整组奖励全部掉落
3-VIP激活
4-抽卡神武将信物掉落（去除玩家已有的神武将后再执行n抽1）
5-神武将经验道具，掉落神武将经验',
`min_level` int(11) COMMENT '府衙最低等级
drop_type=3时，表示VIP等级',
`max_level` int(11) COMMENT '府衙最高等级
drop_type=3时，表示VIP等级',
`rate` int(11) COMMENT '陈涛:
掉落概率（万分比）',
`drop_count` int(11) COMMENT '掉落数量',
`drop_data` text COMMENT '陈涛:
掉落
掉落类型;掉落ID;掉落数量;概率',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Astrology`
--
DROP TABLE IF EXISTS `Astrology`;
CREATE TABLE IF NOT EXISTS `Astrology` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '徐力丰:
1 占星
2 天陨',
`drop_group` int(11) COMMENT '徐力丰:
掉落序号',
`drop_id` int(11) COMMENT '徐力丰:
drop id',
`min_count` int(11) COMMENT '徐力丰:
累计未抽到该drop的最小次数',
`max_count` int(11) COMMENT '徐力丰:
累计未抽到该drop的最大次数',
`chance` int(11) COMMENT '徐力丰:
万分比',
`Special_next_drop_group` int(11) COMMENT '徐力丰:
特殊的掉落组
优先执行
仅执行一次',
`next_drop_group` int(11) COMMENT '徐力丰:
若无掉落，则跳转下一个掉落包',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Gamble_general_soul`
--
DROP TABLE IF EXISTS `Gamble_general_soul`;
CREATE TABLE IF NOT EXISTS `Gamble_general_soul` (
`id` int(11) NOT NULL  COMMENT '陆阳:
1=魏
2=蜀
3=吴
4=群',
`drop_id` int(11) COMMENT '陆阳:
cost_id 
10022 将魂首次半价
10023 将魂全价
10024 将魂10连',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Duel_map_para`
--
DROP TABLE IF EXISTS `Duel_map_para`;
CREATE TABLE IF NOT EXISTS `Duel_map_para` (
`id` int(11) NOT NULL ,
`map_res` int(11) COMMENT '作者:
地图资源',
`map_res_layer` int(11) COMMENT '作者:
遮罩层
',
`move_range` text COMMENT '作者:
可移动范围坐标，逆时针',
`position_left` text COMMENT '作者:
我方站位',
`position_right` text COMMENT '作者:
敌方站位',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Duel_initdata`
--
DROP TABLE IF EXISTS `Duel_initdata`;
CREATE TABLE IF NOT EXISTS `Duel_initdata` (
`id` int(11) NOT NULL ,
`default_num` int(11) COMMENT '作者:
默认武斗次数
',
`battle_cost` int(11) COMMENT '作者:
额外战斗购买价格
为COST ID',
`base_rank_point` int(11) COMMENT '作者:
公式调整的K值
',
`season_time` int(11) COMMENT '作者:
赛级持续天数',
`duel_close_time` int(11) COMMENT '作者:
武斗关闭时间
赛级结束时间点-dul_close_time
',
`protect_score` int(11) COMMENT '作者:
保护积分',
`robot_count` int(11) COMMENT '作者:
打机器人次数',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Duel_rank`
--
DROP TABLE IF EXISTS `Duel_rank`;
CREATE TABLE IF NOT EXISTS `Duel_rank` (
`id` int(11) NOT NULL ,
`rank` int(11) ,
`rank_name` int(11) ,
`rank_desc` varchar(512) COMMENT '作者:
大段位',
`rank_pic` int(11) ,
`rank_number` int(11) ,
`sub_rank` int(11) ,
`sub_rank_name` varchar(512) COMMENT '作者:
小名称：
如都尉1
都尉2
',
`min_point` int(11) ,
`max_point` int(11) ,
`drop` text COMMENT '作者:
军衔升级的一次性奖励',
`daily_drop` text COMMENT '作者:
军衔升级每日奖励',
`win_drop` text COMMENT '作者:
改为单次战斗奖励',
`lose_drop` text COMMENT '作者:
改为单次战斗奖励',
`rank_hit_rate` int(11) COMMENT '作者:
AI命中率',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Duel_times_bonus`
--
DROP TABLE IF EXISTS `Duel_times_bonus`;
CREATE TABLE IF NOT EXISTS `Duel_times_bonus` (
`id` int(11) NOT NULL ,
`times` int(11) ,
`drops` text ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Duel_robot`
--
DROP TABLE IF EXISTS `Duel_robot`;
CREATE TABLE IF NOT EXISTS `Duel_robot` (
`id` int(11) NOT NULL ,
`count` int(11) ,
`duel_rank_id` int(11) ,
`nick` int(11) ,
`level` int(11) ,
`avatar_id` int(11) ,
`score` int(11) ,
`general_1` int(11) ,
`general_2` int(11) ,
`general_3` int(11) ,
`lv` int(11) ,
`star_lv` int(11) ,
`weapon_id` int(11) ,
`armor_id` int(11) ,
`horse_id` int(11) ,
`zuoji_id` int(11) ,
`skill_lv` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Duel_guide`
--
DROP TABLE IF EXISTS `Duel_guide`;
CREATE TABLE IF NOT EXISTS `Duel_guide` (
`id` int(11) NOT NULL ,
`steps` text COMMENT '文字，图片；',
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Embassy`
--
DROP TABLE IF EXISTS `Embassy`;
CREATE TABLE IF NOT EXISTS `Embassy` (
`id` int(11) NOT NULL ,
`build_id` int(11) COMMENT '作者:
建筑ID',
`help_num` int(11) COMMENT '作者:
帮助次数',
`help_time` int(11) COMMENT '作者:
帮助缩短时间',
`help_soldiers_num` int(11) COMMENT '作者:
援兵数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Equipment`
--
DROP TABLE IF EXISTS `Equipment`;
CREATE TABLE IF NOT EXISTS `Equipment` (
`id` int(11) NOT NULL  COMMENT '作者:
1-武将武器
2-武将防具
3-武将饰品
',
`priority` int(11) COMMENT '作者:
优先级',
`item_original_id` int(11) ,
`equip_name` int(11) COMMENT '作者:
装备名字
60-武器
62-防具
64-饰品
66-主公宝物',
`desc1` varchar(512) ,
`description` int(11) COMMENT '作者:
装备介绍
61-武器
62-防御
63-饰品
64-主公',
`desc2` varchar(512) ,
`equip_type` int(11) COMMENT '作者:
道具ID
0-万能装备（不可携带）
1-武器（仅武将携带）
2-防具（仅武将携带）
3-饰品（仅武将携带）
4-坐骑',
`star_level` int(11) COMMENT '作者:
装备星级
0-初始装备
1-1星装备
2-2星装备
3-3星装备
4-4星装备
5-5星装备',
`max_star_level` int(11) ,
`quality_id` int(11) COMMENT '作者:
道具品质
1-白色
2-绿色
3-蓝色
4-紫色
5-橙色',
`force` int(11) COMMENT '作者:
武力',
`intelligence` int(11) COMMENT '作者:
智力',
`governing` int(11) COMMENT '作者:
统治力',
`charm` int(11) COMMENT '作者:
魅力',
`political` int(11) COMMENT '作者:
政治',
`min_general_level` int(11) COMMENT '作者:
武将穿戴等级',
`equip_skill_id` text COMMENT '作者:
装备技能ID
可能含有多个技能',
`equip_icon` int(11) ,
`recast` int(11) COMMENT '重铸
对应drop_id',
`recast_cost` int(11) COMMENT '作者:
重铸消耗，元宝',
`decomposition` int(11) COMMENT '分解，获得白银数量
对应drop_id',
`consume` text COMMENT '作者:
所需材料
1-材料
2-装备
3-白银

道具类型，道具ID，数量
901-白品质装备
902-绿品质装备
903-蓝品质装备
904-紫品质装备
905-橙品质装备

',
`target_equip` int(11) COMMENT '作者:
目标装备ID',
`power` int(11) COMMENT '作者:
装备战力',
`get_path` text COMMENT '作者:
快速获得途径
1=世界地图打怪
2=合成
3=跳转磨坊（还没制作）
4=商城
5=铁匠铺分解
6=重铸
7=集市
',
`target_unlock` int(11) COMMENT '作者:
进阶目标该版本是否解锁
1 解锁
0 未解锁',
`combat_skill_id` int(11) ,
`battle_skill_id` int(11) ,
`skill_level` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Equip_master`
--
DROP TABLE IF EXISTS `Equip_master`;
CREATE TABLE IF NOT EXISTS `Equip_master` (
`id` int(11) NOT NULL  COMMENT '作者:
主公的宝物从4开头',
`priority` int(11) COMMENT '作者:
优先级',
`item_original_id` int(11) ,
`equip_name` int(11) COMMENT '作者:
主公宝物名字
61-武器
62-防御
63-饰品
64-主公',
`desc1` varchar(512) ,
`description` int(11) COMMENT '作者:
主公宝物介绍
61-武器
62-防御
63-饰品
64-主公',
`desc2` varchar(512) ,
`quality_id` int(11) COMMENT '作者:
道具品质
1-白色
2-绿色
3-蓝色
4-紫色
5-橙色',
`min_master_level` int(11) COMMENT '作者:
主公穿戴等级',
`equip_skill_id` text COMMENT '作者:
装备技能ID
可能含有多个技能',
`power` int(11) COMMENT '作者:
装备战力',
`equip_icon` int(11) COMMENT '作者:
宝物ICON',
`type` int(11) COMMENT '作者:
内政1
战争2',
`selldrop` int(11) COMMENT '作者:
该宝物出售获得的锦囊数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Equip_skill`
--
DROP TABLE IF EXISTS `Equip_skill`;
CREATE TABLE IF NOT EXISTS `Equip_skill` (
`id` int(11) NOT NULL  COMMENT '作者:
6位数
2-武将武器
3-武将防具
4-武将饰品
5-主公宝物',
`skill_buff_id` text ,
`skill_description` int(11) ,
`desc1` varchar(512) ,
`num` int(11) COMMENT '作者:
道具初始值
根据buff不同，值不同，如果是百分比，则用万分比，这里只写数字',
`min` int(11) COMMENT '作者:
最小值（万分比）',
`max` int(11) COMMENT '作者:
最大值（万分比）',
`equip_arm_type` int(11) COMMENT '作者:
用于选择出征部队武将带兵特性标示
1步兵
2骑兵
3弓兵
4车兵',
`equip_arm_description` int(11) ,
`desc2` varchar(512) ,
`equipment_active_on_build` text COMMENT '驻守时，装备buff生效的建筑origin_id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `error_code`
--
DROP TABLE IF EXISTS `error_code`;
CREATE TABLE IF NOT EXISTS `error_code` (
`id` int(11) NOT NULL ,
`zhcn` varchar(512) ,
`zhtw` varchar(512) ,
`en` varchar(512) ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `General`
--
DROP TABLE IF EXISTS `General`;
CREATE TABLE IF NOT EXISTS `General` (
`id` int(11) NOT NULL ,
`general_country` int(11) COMMENT '陆阳:
1吴
2蜀国
3魏国
4群',
`root_id` int(11) ,
`general_name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) COMMENT '陈涛:
武将简介',
`desc2` varchar(512) ,
`general_type` int(11) COMMENT '陈涛:
1-武将
2-智将
3-政将
4-全能',
`general_force` int(11) COMMENT '陈涛:
武将武力',
`general_intelligence` int(11) COMMENT '陈涛:
武将智力',
`general_governing` int(11) COMMENT '陈涛:
武将统治力',
`general_charm` int(11) COMMENT '陈涛:
武将魅力',
`general_political` int(11) COMMENT '陈涛:
武将政治',
`general_quality` int(11) COMMENT '陈涛:
武将品质
1-白色
2-绿色
3-蓝色
4-紫色
5-橙色',
`general_item_id` int(11) COMMENT '陈涛:
武将专属武器原始ID
',
`general_level_id` int(11) ,
`general_original_id` int(11) COMMENT '陈涛:
武将原始ID',
`general_icon_min` int(11) COMMENT '陈涛:
武将驻守小头像',
`general_icon` int(11) COMMENT '陈涛:
武将小头像',
`general_big_icon` int(11) COMMENT '陈涛:
武将大头像',
`skill_icon` int(11) COMMENT '陆阳:
技能icon',
`max_soldier` int(11) COMMENT '陈涛:
武将携带士兵上限',
`general_skill` text COMMENT '武将技能 无用字段',
`general_combat_skill` int(11) COMMENT '战斗技能id
多个技能逗号分隔',
`general_duel_atk` int(11) COMMENT '武斗普通攻击id
多个技能逗号分隔',
`general_duel_skill` int(11) COMMENT '武斗技能id
多个技能逗号分隔',
`general_duel_move` int(11) COMMENT '武将武斗移动距离
最小单位1像素
距离为半径
',
`prop1` int(11) COMMENT '陈涛:
宝物1解锁状态
0-未解锁
1-解锁',
`prop2` int(11) COMMENT '陈涛:
宝物2解锁状态
0-未解锁
1-解锁',
`prop3` int(11) ,
`cost_gold` int(11) COMMENT '陈涛:
招募武将的黄金消耗',
`power` int(11) COMMENT '陈涛:
武将战斗力',
`avaiable_level` int(11) COMMENT '徐力丰:
武将在酒馆中出现等级
-1表示永不出现',
`piece_item_id` int(11) COMMENT '武将信物的item_id',
`piece_required` int(11) COMMENT '招募武将所需的碎片数量',
`priority` int(11) COMMENT '酒馆中的排序优先级',
`drop_show` text COMMENT '0：不显示来源
1：NPCID
2：联盟商店
3：锦囊商店
4: 活动id
5: 占星
6: 天殒
7:神盔甲合成',
`portrait_xy` text COMMENT '陆阳:
武将半身像截取的显示坐标',
`portrait_size` varchar(512) COMMENT '陆阳:
武将半身像截取的显示缩放
',
`sell_price` int(11) COMMENT '对酒价格
0：不可通过对酒获得
其他值：元宝价格',
`consume` text COMMENT '徐力丰:
化神费用',
`condition` int(11) COMMENT '徐力丰:
化神条件
',
`general_intro` int(11) COMMENT '徐力丰:
武将定位
',
`desc3` varchar(512) ,
`cocos_res` int(11) COMMENT '陆阳:
武将骨骼动画
',
`cocos_enemy_res` int(11) COMMENT '陆阳:
武将骨骼动画
敌人',
`duel_hit_point` varchar(512) COMMENT '徐力丰:
武斗时武将的生命值
',
`duel_hero_max_sp` int(11) COMMENT '徐力丰:
武斗sp',
`duel_hero_start_sp` int(11) COMMENT '徐力丰:
武斗初始sp',
`duel_hero_restore_sp` int(11) COMMENT '徐力丰:
武斗每回合sp恢复',
`weapon_type` int(11) COMMENT '徐力丰:
武器类型
1 短刀
2 长柄
3 远程',
`soldier_type` int(11) COMMENT '徐力丰:
优势兵种类型：
1步
2骑
3弓
4车',
`general_talent_buff_id` text COMMENT '武将天赋buff id',
`general_talent_value` varchar(512) COMMENT '武将天赋数值
使用武将星级的计算公式',
`general_talent_value_client` varchar(512) COMMENT '武将天赋数值
使用武将星级的计算公式',
`general_talent_description` int(11) COMMENT '武将天赋描述
',
`desc4` varchar(512) COMMENT '武将天赋描述
',
`general_battle_skill` int(11) COMMENT '城战技能',
`general_item_soul` int(11) COMMENT '陆阳:
神武将将魂关联ID',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `General_exp`
--
DROP TABLE IF EXISTS `General_exp`;
CREATE TABLE IF NOT EXISTS `General_exp` (
`id` int(11) NOT NULL ,
`general_level` int(11) ,
`general_exp` int(11) COMMENT '陈涛:
武将当前等级对应全部经验',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `combat_skill`
--
DROP TABLE IF EXISTS `combat_skill`;
CREATE TABLE IF NOT EXISTS `combat_skill` (
`id` int(11) NOT NULL ,
`combat_skill_id` int(11) ,
`type` int(11) ,
`skill_name` int(11) ,
`desc1` varchar(512) ,
`target` text COMMENT '徐力丰:
生效兵种id 0为全部生效
1 步
2 骑
3 弓
4 车

',
`skill_description` int(11) ,
`desc2` varchar(512) ,
`skill_description2` int(11) ,
`desc3` varchar(512) ,
`num_type` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`base` int(11) COMMENT '徐力丰:
基础值',
`para1` int(11) COMMENT '徐力丰:
等级系数',
`para2` int(11) COMMENT '徐力丰:
属性系数',
`backup` varchar(512) ,
`client_formula` varchar(512) ,
`server_formula` varchar(512) ,
`combat_info` int(11) ,
`desc4` varchar(512) ,
`client_formula_backup` varchar(512) ,
`server_formula_backup` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `General_star`
--
DROP TABLE IF EXISTS `General_star`;
CREATE TABLE IF NOT EXISTS `General_star` (
`id` int(11) NOT NULL ,
`general_original_id` int(11) COMMENT '陈涛:
武将原始ID',
`star` int(11) ,
`general_force_growth` int(11) COMMENT '武将武力成长值',
`general_intelligence_growth` int(11) COMMENT '武将智力成长值',
`general_governing_growth` int(11) COMMENT '武将统治力成长值',
`general_charm_growth` int(11) COMMENT '武将魅力成长值',
`general_political_growth` int(11) COMMENT '武将政治成长值',
`consume` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `group_skill`
--
DROP TABLE IF EXISTS `group_skill`;
CREATE TABLE IF NOT EXISTS `group_skill` (
`id` int(11) NOT NULL ,
`general_original_id` text COMMENT '陈涛:
武将原始ID',
`number` int(11) ,
`min_general_level` int(11) ,
`group_skill_type` int(11) ,
`buff` text ,
`skill_name` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `general_condition_type`
--
DROP TABLE IF EXISTS `general_condition_type`;
CREATE TABLE IF NOT EXISTS `general_condition_type` (
`id` int(11) NOT NULL ,
`type` int(11) ,
`desc` int(11) ,
`desc1` varchar(512) ,
`para1` int(11) ,
`condition_icon` int(11) ,
`get_path` int(11) COMMENT '陆阳:
跳转建筑物
origin_build_id',
`menu_1` int(11) COMMENT '郑煦贤:
建筑正常状态下的高亮提示按钮
build_menu_id',
`menu_2` int(11) COMMENT '郑煦贤:
建筑加速状态下的高亮提示按钮
build_menu_id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `general_skill_levelup`
--
DROP TABLE IF EXISTS `general_skill_levelup`;
CREATE TABLE IF NOT EXISTS `general_skill_levelup` (
`id` int(11) NOT NULL ,
`general_skill_exp` int(11) COMMENT '陈涛:
升级需要的技能书数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `duel_skill`
--
DROP TABLE IF EXISTS `duel_skill`;
CREATE TABLE IF NOT EXISTS `duel_skill` (
`id` int(11) NOT NULL ,
`duel_skill_id` int(11) ,
`skill_name` int(11) ,
`skill_name1` varchar(512) ,
`skill_description` int(11) ,
`skill_desc1` varchar(512) ,
`skill_description_preview` int(11) ,
`skill_desc2` varchar(512) ,
`weapon_type` varchar(512) COMMENT '徐力丰:
武器类型
1 短刀
2 长柄
3 远程',
`type` int(11) COMMENT '徐力丰:
1普攻
2技能',
`long_distance` int(11) ,
`short_distance` int(11) ,
`range` int(11) ,
`damage` int(11) ,
`skill_src_res` int(11) COMMENT '陆阳:
发起动作资源
',
`skill_src_ae` int(11) COMMENT '陆阳:
发起方音效
',
`skill_orbit_res` int(11) COMMENT '陆阳:
技能轨迹资源',
`skill_orbit_ae` int(11) COMMENT '陆阳:
技能轨迹资源',
`skill_dst_res` int(11) COMMENT '陆阳:
受击资源
',
`skill_dst_ae` int(11) COMMENT '陆阳:
受击资源
',
`skill_word_res` int(11) COMMENT '陆阳:
技能名',
`skill_need_sp` int(11) ,
`num_type` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`base` int(11) COMMENT '徐力丰:
基础值',
`para1` int(11) COMMENT '徐力丰:
等级系数',
`para2` int(11) COMMENT '徐力丰:
属性系数',
`backup` varchar(512) ,
`client_formula` varchar(512) COMMENT '徐力丰:
普通攻击或技能的伤害数值计算公式',
`client_buff_formula` varchar(512) COMMENT '徐力丰:
普通攻击或技能附带的buff数值，固定值',
`duel_buff_self_1` text COMMENT '徐力丰:
伤害结算前生效的自身buff
',
`duel_buff_self_2` text COMMENT '徐力丰:
伤害结算后生效的自身buff
',
`duel_buff_enemy_1` text COMMENT '徐力丰:
伤害结算前生效的敌对buff
',
`duel_buff_enemy_2` text COMMENT '徐力丰:
伤害结算后生效的敌对buff
',
`combat_info` int(11) ,
`desc3` varchar(512) ,
`atk_ae` int(11) COMMENT '陆阳:
普通攻击音效

',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `duel_buff`
--
DROP TABLE IF EXISTS `duel_buff`;
CREATE TABLE IF NOT EXISTS `duel_buff` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '徐力丰:
1 对自身buff
2 对敌方debuff',
`buff_name` int(11) ,
`buff_name1` varchar(512) ,
`buff_description` int(11) ,
`buff_desc1` varchar(512) ,
`num_type` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`base` int(11) COMMENT '徐力丰:
基础值',
`para1` int(11) COMMENT '徐力丰:
等级系数',
`para2` int(11) COMMENT '徐力丰:
属性系数',
`client_formula` varchar(512) COMMENT '徐力丰:
技能数值公式',
`buff_res` int(11) COMMENT '陆阳:
资源',
`buff_ae` int(11) COMMENT '陆阳:
音效',
`round_formula` int(11) COMMENT '徐力丰:
技能持续时间',
`debuff_tips` int(11) COMMENT '陆阳:
控制技能票字
如；被眩晕，直接弹出你当前回合被眩晕无法移动',
`client_formula_backup` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `battle_skill`
--
DROP TABLE IF EXISTS `battle_skill`;
CREATE TABLE IF NOT EXISTS `battle_skill` (
`id` int(11) NOT NULL ,
`battle_skill_id` int(11) ,
`skill_name` int(11) ,
`skill_name1` varchar(512) ,
`skill_description` int(11) ,
`skill_desc1` varchar(512) ,
`skill_description_preview` int(11) ,
`skill_desc2` varchar(512) ,
`battle_type_id` int(11) COMMENT '徐力丰:
技能类型ID',
`if_active` int(11) COMMENT '徐力丰:
是否主动技能
1是
0否',
`battle_skill_defalut_level` int(11) ,
`skill_res` int(11) COMMENT '陆阳:
发起动作资源
',
`value_formula` varchar(512) ,
`num_type` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`value_max` int(11) COMMENT '徐力丰:
技能数值累加之后的最大值',
`value_formula_2` varchar(512) ,
`num_type2` int(11) COMMENT '徐力丰:
1 万分比
2 固定值',
`backup` varchar(512) ,
`client_formula` varchar(512) COMMENT '徐力丰:
普通攻击或技能的伤害数值计算公式',
`client_formula_2` varchar(512) COMMENT '徐力丰:
普通攻击或技能的伤害数值计算公式',
`combat_info` int(11) ,
`desc3` varchar(512) ,
`buff_type_exclude` int(11) COMMENT '陆阳:
如一些特殊的BUFF单独显示，需要剔除
如关羽武力最高，则显示全盟获得武力增加的技能
0 客户端自己计算
1 服务器给数据
2 不显示
3 主动技
',
`active_skill_area_desc` int(11) COMMENT '陆阳:
主动技能是否区域显示：',
`general_limit` text COMMENT '徐力丰:
可获得该技能的general_original_id
空=所有武将都可以获得',
`refresh_weight` int(11) COMMENT '徐力丰:
洗练时出现该技能的权重',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `battle_skill_levelup`
--
DROP TABLE IF EXISTS `battle_skill_levelup`;
CREATE TABLE IF NOT EXISTS `battle_skill_levelup` (
`id` int(11) NOT NULL ,
`level` int(11) ,
`consume` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `general_total_stars`
--
DROP TABLE IF EXISTS `general_total_stars`;
CREATE TABLE IF NOT EXISTS `general_total_stars` (
`id` int(11) NOT NULL ,
`total_stars` int(11) COMMENT '总星数',
`drop_id` int(11) COMMENT 'dropid
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `active_skill_target`
--
DROP TABLE IF EXISTS `active_skill_target`;
CREATE TABLE IF NOT EXISTS `active_skill_target` (
`id` int(11) NOT NULL ,
`scene_id` int(11) COMMENT '1 联盟战
2 城战城门战
3 城战城内战',
`battle_skill_id` int(11) COMMENT '徐力丰:
城战主动技能id',
`battle_skill_name` varchar(512) COMMENT '徐力丰:
城战主动技能id',
`side` int(11) COMMENT '0 双方
1 攻击方
2 防守方',
`section_id` int(11) COMMENT '区域id
0--所有区域',
`target` varchar(512) COMMENT '武将统治力成长值',
`target_desc` varchar(512) COMMENT '武将统治力成长值',
`client_target_area` int(11) COMMENT '徐力丰:
目标区域
0：无法释放',
`client_description` int(11) ,
`client_desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Guide`
--
DROP TABLE IF EXISTS `Guide`;
CREATE TABLE IF NOT EXISTS `Guide` (
`id` int(11) NOT NULL ,
`steps` text ,
`need_level` int(11) COMMENT '作者:
需要主公等级',
`close_type` int(11) COMMENT '作者:
用于引导过程中退出游戏。
0：全部完成后结束（最后一步操作后生效）
1：第一步出现后立刻结束',
`build_ids` text COMMENT '作者:
需要建筑Id（带等级信息的Id，可多个）',
`science_ids` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `GuideSteps`
--
DROP TABLE IF EXISTS `GuideSteps`;
CREATE TABLE IF NOT EXISTS `GuideSteps` (
`id` int(11) NOT NULL ,
`guide_type` int(11) COMMENT '作者:
1 主城内地图建筑引导
2 普通ui界面引导
3 世界地图位置引导
4 新手引导对话
5 动画
6 图片加文字格式',
`mask_type` int(11) COMMENT '作者:
1 圆形
2 方形',
`black_bg` int(11) COMMENT '作者:
0 或不填 默认不显示黑蒙版
1 显示黑蒙版',
`finger_angle` int(11) COMMENT '作者:
填写 0 - 360
新手引导手指的旋转角度，默认为0',
`effect_id` int(11) COMMENT '作者:
引导的显示特效',
`click_node_id` int(11) COMMENT '作者:
在guide_type不为1时填写。
需要点击哪个组件（或按钮）
如果guide_type是1（主界面地图),该字段不生效，将经根据build_origin_id自动计算出需要点击的建筑位置id',
`build_origin_id` int(11) COMMENT '作者:
只在guide_type为1时填写生效
会定位到此ID当前最高级的建筑',
`homemap_position` int(11) COMMENT '作者:
只在guide_type为1时填写。
强制引导定位到该位置，将忽略build_origin_id字段',
`lightning_tip` int(11) COMMENT '作者:
0 没有定位提示效果
1 开启定位提示效果',
`special_callback_type` int(11) COMMENT '作者:
1=跳过原有功能，执行special_callback功能
2=保留原有功能执行SPECIAL_CALLBACE
3=步骤验证是否符合条件',
`special_callback` int(11) COMMENT '作者:
特殊步骤，就是点击该步骤时触发什么。
如果填0（或不填）则为正常的点击跳转效果，其他的类型需要程序特殊处理
1：跳转到世界地图随机的一个黄金资源矿点
2：返回首页
3：搜怪打怪
4：黄盖一个人
5：徐盛带兵
6：黄盖徐盛一起出去采集
',
`params` text COMMENT '作者:
一些额外的参数，预留字段
1、招募武将的步骤填写武将ID，表示酒馆内如该武将满足条件，仍然不显示可招募

guide_type=5时
params=1 武将拜访动画
params=2 多个功能开启
params=3 成长任务飞入
params=4 事件条加入params=5 主动技飞入
params=6 告诉联盟堡垒特效
params=7 告知化身条件
params=8 开服活动',
`skip_game_features` text COMMENT '作者:
游戏在这些页面的时候忽略这步引导

1 城内地图
2 校场
3 世界地图
4 铁匠铺
5 商店
6 主公详情界面
7 联盟商店
8 活动
9 磨坊
10 联盟
11 武斗界面',
`desc` int(11) COMMENT '作者:
对话内容',
`desc_name` int(11) COMMENT '作者:
主公名字',
`time_late` int(11) ,
`img` int(11) COMMENT '作者:
图片注释
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Guide_tigger`
--
DROP TABLE IF EXISTS `Guide_tigger`;
CREATE TABLE IF NOT EXISTS `Guide_tigger` (
`id` int(11) NOT NULL ,
`steps` text ,
`need_level` int(11) COMMENT '作者:
需要主公等级',
`close_type` int(11) COMMENT '作者:
用于引导过程中退出游戏。
0：全部完成后结束（最后一步操作后生效）
1：第一步出现后立刻结束',
`build_ids` text COMMENT '作者:
需要建筑Id（带等级信息的Id，可多个）',
`science_ids` text ,
`creation_alliance` int(11) ,
`join_alliance` int(11) ,
`activity_ids` text ,
`item_ids` text COMMENT '作者:
包含这个武将才触发引导
item_id
',
`general_ids` text COMMENT '作者:
拥有指定武将触发引导',
`need_general_num` int(11) COMMENT '作者:
武将招募X个触发
',
`priority` int(11) COMMENT '作者:
同时引导触发，优先级
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Help_type`
--
DROP TABLE IF EXISTS `Help_type`;
CREATE TABLE IF NOT EXISTS `Help_type` (
`id` int(11) NOT NULL  COMMENT '作者:
1-联盟任务-和氏璧
2-联盟任务-联盟捐献
3-限时比赛-采集阶段
4-限时比赛-建设、科研阶段
5-限时比赛-造兵、陷阱阶段
6-限时比赛-打野、BOSS阶段
7-限时比赛-杀人阶段
8-城墙
9-联盟商店
10-联盟捐赠
11-屯所士兵援助
',
`title` int(11) ,
`desc2` varchar(512) COMMENT '描述文字',
`description` int(11) COMMENT '描述文字',
`desc1` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `City_tips`
--
DROP TABLE IF EXISTS `City_tips`;
CREATE TABLE IF NOT EXISTS `City_tips` (
`id` int(11) NOT NULL ,
`description` int(11) COMMENT '描述文字',
`desc1` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Email_tips`
--
DROP TABLE IF EXISTS `Email_tips`;
CREATE TABLE IF NOT EXISTS `Email_tips` (
`id` int(11) NOT NULL ,
`time` int(11) COMMENT '作者:
以分钟为单位',
`title` int(11) ,
`title_desc1` varchar(512) ,
`desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Hoard`
--
DROP TABLE IF EXISTS `Hoard`;
CREATE TABLE IF NOT EXISTS `Hoard` (
`id` int(11) NOT NULL ,
`build_id` int(11) ,
`max_soldiers` int(11) COMMENT '作者:
集结兵量上限',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Item`
--
DROP TABLE IF EXISTS `Item`;
CREATE TABLE IF NOT EXISTS `Item` (
`id` int(11) NOT NULL ,
`priority` int(11) COMMENT '陈涛:
优先级，数字越大优先级低',
`item_original_id` int(11) COMMENT '陈涛:
原始道具对应ID',
`item_name` int(11) ,
`desc1` varchar(512) ,
`item_num_show` int(11) COMMENT '是否显示资源数量
1显示
显示规则:
<1000 直接显示
1000～999999：显示n.nK精确到小数点后1位
1000000+：显示n.nnM精确到小数点后2位',
`item_type` int(11) COMMENT '陈涛:
道具类型
1-资源道具（粮食、黄金、木头、铁材、石材）
2-消耗道具(会出现在包裹中）
3-材料道具
4-武将信物
5-神武将将魂
6-红色装备碎片',
`item_show_type` int(11) COMMENT '陆阳:
区分道具打开类型
1、打开宝箱类界面
2、打开经验道具
3、使用BUFF类道具
4、激活VIP
',
`item_use_num` int(11) COMMENT '陆阳:
道具默认选中数量
0=无限大
1=只选择1个',
`item_level_id` int(11) COMMENT '陈涛:
道具等级
1-白
2-绿
3-蓝
4-紫
5-橙',
`item_introduction` int(11) COMMENT '陈涛:
道具介绍',
`desc2` varchar(512) ,
`res_icon` int(11) COMMENT '陈涛:
图片ICON
',
`rank` int(11) ,
`cash_in` text COMMENT '陈涛:
购入
1-黄金
2-粮食
3-木材
4-石材
5-铁材
6-gem',
`cash_out` int(11) COMMENT '陈涛:
卖出价格',
`button_type` int(11) COMMENT '陈涛:
物品能否使用
0-不可使用，弹出说明框
1-使用（后面比有drop或者use）
2-合成',
`drop` text COMMENT '使用可获得
对应drop表
如果为空则表示使用之后不会获得物品
逗号分隔：从指定的一组drop中取一个掉落
分号分隔：设定多组drop
注意：每组drop必须有掉落不然会报错',
`item_acceleration` int(11) COMMENT '陈涛:
加速道具使用后效果
对应Accelerate表
如果为空则表示非加速道具',
`buff_type` int(11) COMMENT '陈涛:
对应buff表的id',
`duration` int(11) COMMENT '陈涛:
持续时间/秒',
`use_level` int(11) ,
`direct_price` int(11) COMMENT '徐力丰:
张董琪使用 加速直接购买价格',
`get_path` text COMMENT '作者:
快速获得途径
1=世界地图打怪
2=合成
3=跳转磨坊
4=商城
5=铁匠铺分解
6=重铸
7=集市
8=占星（低级抽奖）
9=天陨（高级抽奖）
10=融合（神盔甲合成）
11=对酒
12=战勋商店
101=联盟商店
102=活动
103=锦囊商店
104=商店-战争标签
13=祭天
14=神盔甲分解

201=城战-洛阳商铺
202=城战-成都商铺
203=城战-建业商铺
204=城战-襄阳商铺',
`decomposition` int(11) COMMENT '徐力丰:
分解掉落
该字段用于御龙盔甲分解',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Item_Combine`
--
DROP TABLE IF EXISTS `Item_Combine`;
CREATE TABLE IF NOT EXISTS `Item_Combine` (
`id` int(11) NOT NULL  COMMENT '目标道具ID',
`consume` text COMMENT '作者:
所需材料',
`target_equip` int(11) COMMENT '作者:
目标装备ID',
`count` int(11) COMMENT '作者:
数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Use`
--
DROP TABLE IF EXISTS `Use`;
CREATE TABLE IF NOT EXISTS `Use` (
`id` int(11) NOT NULL ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `item_acceleration`
--
DROP TABLE IF EXISTS `item_acceleration`;
CREATE TABLE IF NOT EXISTS `item_acceleration` (
`id` int(11) NOT NULL ,
`desc1` varchar(512) ,
`type` int(11) COMMENT '陈涛:
0-通用道具
1-建筑加速
2-造兵加速
3-医疗加速
4-研究加速
5-早陷阱加速',
`item_num` int(11) COMMENT '陈涛:
时间：秒
',
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `King_rank_reward`
--
DROP TABLE IF EXISTS `King_rank_reward`;
CREATE TABLE IF NOT EXISTS `King_rank_reward` (
`id` int(11) NOT NULL ,
`min_rank` int(11) ,
`max_rank` int(11) ,
`bonus` text COMMENT '作者:
DROP ID
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `King_appoint`
--
DROP TABLE IF EXISTS `King_appoint`;
CREATE TABLE IF NOT EXISTS `King_appoint` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
type
1=增益
2=减益
',
`position_name` int(11) ,
`desc1` varchar(512) ,
`img_head` int(11) COMMENT '作者:
',
`img_portrait` int(11) COMMENT '作者:
',
`outline_icon` int(11) ,
`back_icon` int(11) ,
`add_buff` text COMMENT '作者:
职位获得BUFF 
',
`img_normal` int(11) COMMENT '作者:
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `King_gift`
--
DROP TABLE IF EXISTS `King_gift`;
CREATE TABLE IF NOT EXISTS `King_gift` (
`id` int(11) NOT NULL ,
`gift_name` int(11) ,
`gift_id` int(11) COMMENT '作者:
drop id',
`max_count` int(11) ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `zhcn`
--
DROP TABLE IF EXISTS `zhcn`;
CREATE TABLE IF NOT EXISTS `zhcn` (
`id` int(11) NOT NULL ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `zhtw`
--
DROP TABLE IF EXISTS `zhtw`;
CREATE TABLE IF NOT EXISTS `zhtw` (
`id` int(11) NOT NULL ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Legion`
--
DROP TABLE IF EXISTS `Legion`;
CREATE TABLE IF NOT EXISTS `Legion` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1-军团
2-部队',
`in_legion` int(11) COMMENT '作者:
0 军团
1 1军团
2 2军团
3 3军团',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Library`
--
DROP TABLE IF EXISTS `Library`;
CREATE TABLE IF NOT EXISTS `Library` (
`id` int(11) NOT NULL ,
`time` int(11) COMMENT '作者:
时间/小时',
`cost` int(11) COMMENT '作者:
消耗',
`rate` int(11) COMMENT '作者:
倍率',
`clear_time` int(11) COMMENT '作者:
清除时间
秒/元宝',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Loading_desc`
--
DROP TABLE IF EXISTS `Loading_desc`;
CREATE TABLE IF NOT EXISTS `Loading_desc` (
`id` int(11) NOT NULL ,
`tips` int(11) COMMENT 'loading时的提示内容',
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Title_notice`
--
DROP TABLE IF EXISTS `Title_notice`;
CREATE TABLE IF NOT EXISTS `Title_notice` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1=战斗相关
2=招募武将
3=击杀BOSS
4=紫色以上品质装备进阶
5=皇帝当选走马灯
6=官职当选走马灯
7=囚犯当选走马灯
8=获得神武将信物
9=化神为神武将',
`desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Push_notice`
--
DROP TABLE IF EXISTS `Push_notice`;
CREATE TABLE IF NOT EXISTS `Push_notice` (
`id` int(11) NOT NULL ,
`title` int(11) COMMENT '作者:
1. 升级、训练完成提醒
2. 战斗提醒
3. 活动提醒
4. 部队返回
5. 玩家发起集结',
`desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Push_notice_system`
--
DROP TABLE IF EXISTS `Push_notice_system`;
CREATE TABLE IF NOT EXISTS `Push_notice_system` (
`id` int(11) NOT NULL ,
`title` int(11) ,
`desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Wheel`
--
DROP TABLE IF EXISTS `Wheel`;
CREATE TABLE IF NOT EXISTS `Wheel` (
`id` int(11) NOT NULL  COMMENT '转盘的1~12项物品',
`grid_id` int(11) ,
`next_grid_id` int(11) ,
`type` int(11) COMMENT '徐力丰:
该格子的类型
0：根据玩家等级随机一种资源(前端显示资源)
1：随机一种宝箱（需符合玩家等级，前端显示问号）
2：直接掉落道具（前端显示宝箱）',
`drop` int(11) COMMENT 'type=0时，根据等级随机drop
type=2时直接走drop
',
`lv_min` int(11) COMMENT '最低府衙等级',
`lv_max` int(11) COMMENT '最高府衙等级',
`res_icon` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Chest`
--
DROP TABLE IF EXISTS `Chest`;
CREATE TABLE IF NOT EXISTS `Chest` (
`id` int(11) NOT NULL  COMMENT '预设宝箱配置id
',
`chest_id` int(11) COMMENT '宝箱序号',
`lv_min` int(11) COMMENT '最低府衙等级',
`lv_max` int(11) COMMENT '最高府衙等级',
`weight` int(11) COMMENT '徐力丰:
权重',
`type` int(11) COMMENT '徐力丰:
1 drop
2 倍率',
`value` int(11) COMMENT '格式：
type=1:dropid
type=2:倍率
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

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
`anim_reversal` int(11) COMMENT '作者:
动画朝向
1=原始动画
2=原始动画翻转
3=新资源
4=新资源翻转',
`anim_reversal_name` varchar(512) COMMENT '作者:
动画朝向
1=原始动画
2=原始动画翻转
3=新资源',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_build_description`
--
DROP TABLE IF EXISTS `Alliance_build_description`;
CREATE TABLE IF NOT EXISTS `Alliance_build_description` (
`id` int(11) NOT NULL ,
`element_id` int(11) ,
`count` int(11) COMMENT '作者:
第几个',
`need_alliance_science` int(11) ,
`open_condition` int(11) ,
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Build_menu_type`
--
DROP TABLE IF EXISTS `Build_menu_type`;
CREATE TABLE IF NOT EXISTS `Build_menu_type` (
`id` int(11) NOT NULL  COMMENT '对应建筑原始ID
',
`img` int(11) ,
`name` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Build_skill`
--
DROP TABLE IF EXISTS `Build_skill`;
CREATE TABLE IF NOT EXISTS `Build_skill` (
`id` int(11) NOT NULL ,
`buff_id` int(11) ,
`num` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Map_build_menu`
--
DROP TABLE IF EXISTS `Map_build_menu`;
CREATE TABLE IF NOT EXISTS `Map_build_menu` (
`id` int(11) NOT NULL  COMMENT '作者:
map_origin_id',
`desc1` varchar(512) ,
`build_menu_1` text ,
`build_menu_2` text ,
`build_menu_3` text ,
`build_menu_4` text ,
`build_menu_5` text ,
`build_menu_6` text ,
`build_menu_7` text ,
`build_menu_8` text ,
`build_menu_9` text ,
`build_menu_10` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Marching_troops_menu`
--
DROP TABLE IF EXISTS `Marching_troops_menu`;
CREATE TABLE IF NOT EXISTS `Marching_troops_menu` (
`id` int(11) NOT NULL  COMMENT '作者:
map_origin_id',
`desc1` varchar(512) ,
`build_menu_1` text ,
`build_menu_2` text ,
`build_menu_3` text ,
`build_menu_4` text ,
`build_menu_5` text ,
`build_menu_6` text ,
`build_menu_7` text ,
`build_menu_8` text ,
`build_menu_9` text ,
`build_menu_10` text ,
`build_menu_11` text ,
`build_menu_12` text ,
`build_menu_13` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Collection_drop`
--
DROP TABLE IF EXISTS `Collection_drop`;
CREATE TABLE IF NOT EXISTS `Collection_drop` (
`id` int(11) NOT NULL ,
`collection_min` int(11) COMMENT '作者:
采集数
黄金*10
粮食*1
木材*5
石材*25
铁材*50
',
`collection_max` int(11) ,
`collection_drop` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Resource_refresh`
--
DROP TABLE IF EXISTS `Resource_refresh`;
CREATE TABLE IF NOT EXISTS `Resource_refresh` (
`id` int(11) NOT NULL ,
`distance_max` int(11) COMMENT '距离中心点最大距离',
`map_element_id` int(11) COMMENT '地图元素id',
`weight` int(11) COMMENT '作者:
刷新权重',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `cross_map_config`
--
DROP TABLE IF EXISTS `cross_map_config`;
CREATE TABLE IF NOT EXISTS `cross_map_config` (
`id` int(11) NOT NULL ,
`map_type` int(11) COMMENT '作者:
不同的地图',
`area` int(11) COMMENT '作者:
区域分块：
1=最外围
2=中间
3=里面
4=皇宫位',
`x` int(11) ,
`y` int(11) ,
`sides_type` int(11) COMMENT '作者:
1攻击
2防守
',
`cross_map_element_id` int(11) COMMENT '作者:
map_element id',
`max_durability` int(11) COMMENT '作者:
部分建筑物的血量',
`next_area` int(11) COMMENT '作者:
可开启下一个区域',
`target_area` int(11) COMMENT '作者:
攻击目标

',
`build_num` int(11) COMMENT '作者:
同类型建筑物编号
',
`memo` varchar(512) COMMENT '作者:
批注',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `city_battle_map_config`
--
DROP TABLE IF EXISTS `city_battle_map_config`;
CREATE TABLE IF NOT EXISTS `city_battle_map_config` (
`id` int(11) NOT NULL ,
`map_type` int(11) COMMENT '作者:
类型
1=城门战
2=内城战',
`area` int(11) COMMENT '区域根据TYPE类型不同性质不同
type=1时，区域与跨服战的性质相同

type=2时，区域表示各个占领区域',
`part` int(11) COMMENT '作者:
类型
1=城门战
2=内城战',
`section` int(11) COMMENT '作者:
小区域
城门战的123代表魏蜀吴的攻城时的位置

城门战
123代表魏蜀吴的攻城时的位置
456对饮城内的位置


城内战
12345=每个区域
6=攻方默认
7=守方默认',
`x` int(11) ,
`y` int(11) ,
`sides_type` int(11) COMMENT '作者:
1攻击
2防守
',
`city_battle_map_element_id` int(11) COMMENT '作者:
map_element id',
`max_durability` int(11) COMMENT '作者:
部分建筑物的血量',
`next_area` int(11) COMMENT '作者:
可开启下一个区域',
`target_area` int(11) ,
`target_map_element_id` int(11) COMMENT '作者:
攻击目标

',
`build_num` int(11) COMMENT '作者:
同类型建筑物编号
',
`memo` varchar(512) COMMENT '作者:
批注',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Master`
--
DROP TABLE IF EXISTS `Master`;
CREATE TABLE IF NOT EXISTS `Master` (
`id` int(11) NOT NULL ,
`level` int(11) ,
`exp` int(11) COMMENT '作者:
总exp',
`drop` int(11) ,
`talent_num` int(11) ,
`max_general` int(11) ,
`day_storage` int(11) ,
`max_warehouse` int(11) ,
`power` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Master_attribute`
--
DROP TABLE IF EXISTS `Master_attribute`;
CREATE TABLE IF NOT EXISTS `Master_attribute` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1-战斗力
2-战斗状态
3-军事
4-资源
5-城市发展
6-城防',
`name` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Res_head`
--
DROP TABLE IF EXISTS `Res_head`;
CREATE TABLE IF NOT EXISTS `Res_head` (
`id` int(11) NOT NULL ,
`head_icon` int(11) ,
`bust_icon` int(11) ,
`outline_icon` int(11) ,
`back_icon` int(11) ,
`min_head` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Master_skill`
--
DROP TABLE IF EXISTS `Master_skill`;
CREATE TABLE IF NOT EXISTS `Master_skill` (
`id` int(11) NOT NULL  COMMENT '作者:
等于天赋ID',
`talent_id` int(11) ,
`icon` text ,
`talent_text` int(11) COMMENT '作者:
天赋介绍',
`desc1` varchar(512) ,
`cd` int(11) COMMENT '作者:
CD时间，单位：秒',
`cdhour` int(11) ,
`duration` int(11) COMMENT '作者:
是否持续时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Mill`
--
DROP TABLE IF EXISTS `Mill`;
CREATE TABLE IF NOT EXISTS `Mill` (
`id` int(11) NOT NULL ,
`item` int(11) COMMENT '作者:
可制造道具
',
`time` int(11) COMMENT '作者:
单个道具花费时间',
`level_min` int(11) COMMENT '作者:
最小府衙等级',
`level_max` int(11) COMMENT '作者:
最大府衙等级',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Mission`
--
DROP TABLE IF EXISTS `Mission`;
CREATE TABLE IF NOT EXISTS `Mission` (
`id` int(11) NOT NULL ,
`mission_name` int(11) COMMENT '作者:
任务名称',
`desc1` varchar(512) COMMENT '描述文字',
`mission_objectives` int(11) COMMENT '作者:
任务目标',
`desc2` varchar(512) COMMENT '描述文字
任务目标描述',
`mission_type` int(11) COMMENT '作者:
1 主线-建造或升级
3 研究科技：研究任意科技1个
4 训练部队：训练指定步兵/骑兵/弓兵/车兵
5 击杀怪物：击杀指定怪物/任意怪物
6 攻击玩家：攻击其他玩家获胜n次
7 掠夺资源：掠夺其他玩家n资源（黄金、粮草、木材、石头、铁矿）
8 采集资源：在世界地图中采集n资源
9 奋勇杀敌：击杀其他玩家n民士兵
10 防御玩家：抵御其他玩家攻击n次
11 治愈伤兵：治愈n名伤兵
12 联盟捐献：获得联盟n点贡献值
13 合成材料：合成n装备进阶材料
15 众志成城：集结消灭n名敌军
17 商城购物：在商城中消费元宝
19 联盟兑换：在联盟中兑换1次物品
20 收获资源：在主城中收获n资源
21 主线-训练步兵
22 主线-训练骑兵
25 主线-野外打怪
26 主线-研究指定科技
27 主线-杀兵2次
28 主线-杀兵4次
',
`next_mission_id` int(11) COMMENT '作者:
前置任务id
',
`min_level` int(11) COMMENT '作者:
玩家接受任务的最低等级',
`max_level` int(11) COMMENT '作者:
玩家接受任务的最小等级',
`star_level` int(11) COMMENT '作者:
1:代表1星任务
2:代表2星任务
3:代表3星任务
4:代表4星任务
5:代表5星任务
0:代表主线',
`probability` int(11) COMMENT '作者:
任务自然刷新权重
',
`probability_yb` int(11) COMMENT '作者:
元宝刷新权重
',
`drop` text COMMENT '作者:
奖励',
`mission_number` int(11) COMMENT '作者:
主线任务显示build_id
每日任务显示条件数量',
`mission_target` int(11) COMMENT '作者:
跳转的建筑物
世界地图跳转不填
',
`mission_target2` int(11) COMMENT '作者:
900001=训练步兵
900002=训练骑兵
900003=野外打怪
900004=研究科技',
`description` int(11) ,
`desc3` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Fail_save_reward`
--
DROP TABLE IF EXISTS `Fail_save_reward`;
CREATE TABLE IF NOT EXISTS `Fail_save_reward` (
`id` int(11) NOT NULL ,
`reward_type` int(11) COMMENT '作者:
1.每次都生效
2.仅生效一次',
`level_max` int(11) COMMENT '该补偿生效的最大府衙等级',
`power_min` int(11) COMMENT '单次，被攻击
损失战力最小值（仅统计部队损失）',
`power_max` int(11) COMMENT '单次被攻击损失战力最大值',
`drop` text COMMENT '作者:
奖励',
`language_id` int(11) COMMENT '多语言id',
`desc1` varchar(512) COMMENT '邮件文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Monster_cycle`
--
DROP TABLE IF EXISTS `Monster_cycle`;
CREATE TABLE IF NOT EXISTS `Monster_cycle` (
`id` int(11) NOT NULL ,
`day` int(11) COMMENT '作者:
服务器开启时间（24小时制）',
`monster_id` int(11) COMMENT '作者:
怪物等级',
`weight` int(11) COMMENT '作者:
怪物刷新权重',
`ifrespawn` int(11) COMMENT '怪物若未被击杀，是否重刷
0 不重刷
1 重刷',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Boss_refresh`
--
DROP TABLE IF EXISTS `Boss_refresh`;
CREATE TABLE IF NOT EXISTS `Boss_refresh` (
`id` int(11) NOT NULL ,
`day` int(11) COMMENT '作者:
服务器开启时间（24小时制）',
`boss_id` int(11) COMMENT '作者:
boss等级',
`weight` int(11) COMMENT '作者:
怪物刷新权重',
`ifrespawn` int(11) COMMENT '怪物若未被攻击，是否重刷
0 不重刷
1 重刷',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Notification`
--
DROP TABLE IF EXISTS `Notification`;
CREATE TABLE IF NOT EXISTS `Notification` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`priority` int(11) COMMENT '作者:
优先级',
`text` varchar(512) COMMENT '作者:
中文介绍，不需要进库',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Npc`
--
DROP TABLE IF EXISTS `Npc`;
CREATE TABLE IF NOT EXISTS `Npc` (
`id` int(11) NOT NULL ,
`monster_type` int(11) COMMENT '作者:
1=普通
2=组队怪
3=守兵
4=国王战派兵（小）
5=国王战派兵（大）
6=野外boss
7=玉玺争夺',
`monster_name` int(11) COMMENT '怪物名字',
`desc1` varchar(512) ,
`monster_desc` int(11) COMMENT '作者:
描述',
`desc2` varchar(512) ,
`monster_lv` int(11) ,
`attack` int(11) COMMENT '作者:
攻击',
`defense` int(11) COMMENT '作者:
防御（就是血量）
boss防御设0

',
`life` int(11) ,
`number` int(11) COMMENT '作者:
怪物数量',
`drop` text COMMENT '作者:
怪物掉落
boss击杀掉落',
`img` int(11) COMMENT '作者:
半身像',
`img_mail` int(11) COMMENT '作者:
邮件头像
',
`precondition` int(11) COMMENT '作者:
前置怪物',
`power` int(11) COMMENT '作者:
战斗力：国王战时时计算国王战胜利条件的一个积分值',
`hp_ratio` int(11) COMMENT '实际计算时的defense=defense*ratio
life=life*ratio
',
`drop_show` text COMMENT 'npc有可能掉落的item id列表
0为无掉落
',
`recommand_power` int(11) COMMENT '推荐战力',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Boss_npc_drop`
--
DROP TABLE IF EXISTS `Boss_npc_drop`;
CREATE TABLE IF NOT EXISTS `Boss_npc_drop` (
`id` int(11) NOT NULL ,
`npc_id` int(11) COMMENT '对应boss野怪在npc表中的id',
`damage_min` int(11) COMMENT '最低伤害
军团对boss造成的伤害处于不同区间获得不同奖励
',
`damage_max` int(11) COMMENT '最高伤害
军团对boss造成的伤害处于不同区间获得不同奖励
-1表示无上限',
`boss_drop` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Online_Award`
--
DROP TABLE IF EXISTS `Online_Award`;
CREATE TABLE IF NOT EXISTS `Online_Award` (
`id` int(11) NOT NULL ,
`award_count` int(11) COMMENT '作者:
第几次的奖励
',
`get_time` int(11) COMMENT '作者:
每次领取奖励时间',
`drop` text COMMENT '作者:
奖励内容',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Sign_Award`
--
DROP TABLE IF EXISTS `Sign_Award`;
CREATE TABLE IF NOT EXISTS `Sign_Award` (
`id` int(11) NOT NULL ,
`get_day` int(11) COMMENT '作者:
每次领取奖励时间',
`drop` text COMMENT '作者:
奖励内容',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Powerup_Guide`
--
DROP TABLE IF EXISTS `Powerup_Guide`;
CREATE TABLE IF NOT EXISTS `Powerup_Guide` (
`id` int(11) NOT NULL ,
`name_id` int(11) ,
`name` varchar(512) ,
`desc_id` int(11) ,
`desc` varchar(512) ,
`redirect_type` text COMMENT '类型可增加，但不要删减或插入
1-步兵营
2-骑兵营
3-弓兵营
4-车兵营
5-战争工坊
6-酒馆
7-铁匠铺
8-研究所
9-主公天赋
10-主公宝物
11-升级建筑
12-搜索怪物
13-任务
14-神龛
15-化神',
`button_name_id` text ,
`castle_lv` text COMMENT '作者:
BUTTON_NAME_ID有几个，就要配几个等级',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Faq_Guide`
--
DROP TABLE IF EXISTS `Faq_Guide`;
CREATE TABLE IF NOT EXISTS `Faq_Guide` (
`id` int(11) NOT NULL ,
`name_id` int(11) ,
`name` varchar(512) ,
`desc_id` int(11) ,
`desc` varchar(512) ,
`redirect_type` text COMMENT '类型可增加，但不要删减或插入
1-步兵营
2-骑兵营
3-弓兵营
4-车兵营
5-战争工坊
6-酒馆
7-铁匠铺
8-研究所
9-主公天赋
10-主公宝物
11-升级建筑
12-搜索怪物
13-任务',
`button_name_id` text ,
`castle_lv` text ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Pricing`
--
DROP TABLE IF EXISTS `Pricing`;
CREATE TABLE IF NOT EXISTS `Pricing` (
`id` int(11) NOT NULL ,
`channel` varchar(512) COMMENT '作者:
充值渠道',
`desc` int(11) ,
`desc1` varchar(512) ,
`type` varchar(512) COMMENT '作者:
货币种类',
`price` varchar(512) COMMENT '作者:
现金价格',
`goods_type` int(11) COMMENT '作者:
充值类型
1、元宝
2、永久月卡
3、月卡
4、充值礼包',
`count` int(11) COMMENT '作者:
充值获得元宝数',
`first_add_count` int(11) COMMENT '作者:
首次充值额外获得',
`add_count` int(11) COMMENT '作者:
每次充值额外获得',
`add_percent` int(11) COMMENT '作者:
客户端优惠比例 万分比
对礼包即性价比
1000表示额外优惠10%',
`isopen` int(11) COMMENT '作者:
是否打开此充值项
1-常态开启
2-特定时间段开启',
`isshow` int(11) ,
`bonus_drop` int(11) COMMENT '作者:
额外奖励',
`payment_code` varchar(512) ,
`gift_type` int(11) COMMENT '作者:
礼包类别，用于activity_Commodity表中对应礼包类别',
`product_id` int(11) COMMENT '作者:
渠道需要后台对应商品编码
相关渠道：
联想',
`icon` int(11) ,
`rmb_value` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Pay_way`
--
DROP TABLE IF EXISTS `Pay_way`;
CREATE TABLE IF NOT EXISTS `Pay_way` (
`id` int(11) NOT NULL ,
`channel` varchar(512) COMMENT '作者:
渠道客户端',
`pay_way` varchar(512) COMMENT '作者:
可用的支付方式',
`pay_way_lv` text COMMENT '作者:
每个充值项对应的开启等级',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Android_Channel`
--
DROP TABLE IF EXISTS `Android_Channel`;
CREATE TABLE IF NOT EXISTS `Android_Channel` (
`id` int(11) NOT NULL ,
`channel_id` varchar(512) ,
`channel_name` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Pub`
--
DROP TABLE IF EXISTS `Pub`;
CREATE TABLE IF NOT EXISTS `Pub` (
`id` int(11) NOT NULL ,
`first_drop` text ,
`ordinary_drop` text COMMENT '作者:
普通掉落包',
`senior_drop` text COMMENT '作者:
高级掉落包',
`time` int(11) ,
`gem_first_drop` text ,
`gem_ordinary_drop` text COMMENT '作者:
普通掉落包',
`gem_senior_drop` text COMMENT '作者:
高级掉落包',
`min` int(11) ,
`max` int(11) ,
`cost` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `General_recruit`
--
DROP TABLE IF EXISTS `General_recruit`;
CREATE TABLE IF NOT EXISTS `General_recruit` (
`id` int(11) NOT NULL ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Sprite`
--
DROP TABLE IF EXISTS `Sprite`;
CREATE TABLE IF NOT EXISTS `Sprite` (
`id` int(11) NOT NULL ,
`path` varchar(512) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `GeneralAnims`
--
DROP TABLE IF EXISTS `GeneralAnims`;
CREATE TABLE IF NOT EXISTS `GeneralAnims` (
`id` int(11) NOT NULL ,
`path_1` varchar(512) COMMENT '作者:
正面武将',
`path_2` varchar(512) COMMENT '作者:
正面武将',
`desc1` varchar(512) ,
`desc2` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `SkillAnims`
--
DROP TABLE IF EXISTS `SkillAnims`;
CREATE TABLE IF NOT EXISTS `SkillAnims` (
`id` int(11) NOT NULL ,
`path` varchar(512) COMMENT '作者:
技能特效位置',
`play_type` int(11) COMMENT '作者:
播放模式：
1：在启动位置播放
2：在目标位置播放
3：全屏中央播放
4：飞行特效（飞行特效循环播放',
`anims_type` int(11) COMMENT '作者:
动画类型
1：不切面，2：切面',
`desc` varchar(512) COMMENT '作者:
类型
1=普攻
2=技能
3=BUFF',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `BuffAnims`
--
DROP TABLE IF EXISTS `BuffAnims`;
CREATE TABLE IF NOT EXISTS `BuffAnims` (
`id` int(11) NOT NULL ,
`path` varchar(512) COMMENT '作者:
技能特效位置',
`desc` varchar(512) COMMENT '作者:
类型
1=普攻
2=技能
3=BUFF',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Sounds`
--
DROP TABLE IF EXISTS `Sounds`;
CREATE TABLE IF NOT EXISTS `Sounds` (
`id` int(11) NOT NULL  COMMENT '作者:
千位1=魏国，2=蜀国，3=吴国，4=群雄',
`sounds_path` varchar(512) ,
`desc` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `ParticleAnims`
--
DROP TABLE IF EXISTS `ParticleAnims`;
CREATE TABLE IF NOT EXISTS `ParticleAnims` (
`id` int(11) NOT NULL ,
`folder` varchar(512) ,
`name` varchar(512) ,
`offsetx` int(11) ,
`offsety` int(11) ,
`duration` int(11) COMMENT '作者:
时间
',
`isloop` int(11) COMMENT '作者:
0 不循环
1 循环',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Plist`
--
DROP TABLE IF EXISTS `Plist`;
CREATE TABLE IF NOT EXISTS `Plist` (
`id` int(11) NOT NULL ,
`path` varchar(512) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

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

--
-- Table structure for table `Robot_refresh`
--
DROP TABLE IF EXISTS `Robot_refresh`;
CREATE TABLE IF NOT EXISTS `Robot_refresh` (
`id` int(11) NOT NULL ,
`build_level` int(11) COMMENT '作者:
建筑等级：
府衙 城墙 农田x5 金矿x5',
`troop` text COMMENT '作者:
武将id，兵种id
数量100~500随机',
`day_start` int(11) COMMENT '作者:
开始刷新的开服天数',
`day_end` int(11) COMMENT '作者:
结束刷新的开服天数',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Science`
--
DROP TABLE IF EXISTS `Science`;
CREATE TABLE IF NOT EXISTS `Science` (
`id` int(11) NOT NULL  COMMENT '陈涛:
除以100得到对应科技编号
对100求余得到当前等级',
`type` int(11) COMMENT '陈涛:
1-军事
2-发展',
`science_type_id` int(11) COMMENT '陈涛:
对应科技种类编号',
`level_id` int(11) ,
`max_level` int(11) ,
`buff_num_type` int(11) COMMENT '陈涛:
1-万分比
2-具体值
',
`buff_num` int(11) COMMENT '陈涛:
buff数值',
`max_buff_num` int(11) ,
`science_drop` int(11) ,
`name` int(11) ,
`desc1` varchar(512) ,
`description` int(11) COMMENT '陈涛:
介绍',
`desc2` varchar(512) ,
`condition_science` text COMMENT '陈涛:
前一个科技id
多个条件时用分号隔开',
`next_science` int(11) ,
`build_level` int(11) COMMENT '陈涛:
开启前置条件',
`cost` text COMMENT '升级消耗
格式：
道具ID,数量（中间用分号隔开）
1-黄金
2-粮食
3-木材
4-石矿
5-铁矿',
`power` int(11) COMMENT '陈涛:
战力',
`need_time` int(11) COMMENT '陈涛:
升级所需时间/秒',
`gem_cost` int(11) COMMENT '陈涛:
清CD所需元宝',
`img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Secretary`
--
DROP TABLE IF EXISTS `Secretary`;
CREATE TABLE IF NOT EXISTS `Secretary` (
`id` int(11) NOT NULL ,
`target_group` int(11) ,
`level` int(11) ,
`type` int(11) ,
`condition` int(11) ,
`condition_level` int(11) ,
`hint_text` int(11) COMMENT '名称
',
`desc1` varchar(512) COMMENT '描述文字',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Score`
--
DROP TABLE IF EXISTS `Score`;
CREATE TABLE IF NOT EXISTS `Score` (
`id` int(11) NOT NULL ,
`target_group` int(11) ,
`level` int(11) ,
`score` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Power`
--
DROP TABLE IF EXISTS `Power`;
CREATE TABLE IF NOT EXISTS `Power` (
`id` int(11) NOT NULL ,
`level` int(11) ,
`power` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `sensitive_word`
--
DROP TABLE IF EXISTS `sensitive_word`;
CREATE TABLE IF NOT EXISTS `sensitive_word` (
`id` int(11) NOT NULL ,
`word` varchar(512) ,
`type` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Shop`
--
DROP TABLE IF EXISTS `Shop`;
CREATE TABLE IF NOT EXISTS `Shop` (
`id` int(11) NOT NULL  COMMENT '陈涛:
1-商店
2-锦囊商城',
`shop_type` int(11) COMMENT '1-商城
2-锦囊商店
3-跨服城战商店',
`type` int(11) COMMENT '陈涛:
1-资源
2-战争
3-增益
4-热卖
5-功勋
用于普通商城中商品分类
6:锦囊',
`priority` int(11) COMMENT '前端显示排序',
`commodity_data` int(11) COMMENT '对应drop表drop_id',
`buy_daily_limit` int(11) COMMENT '陆阳:
基础购买次数
无限次=-1
跨服城战商店刷新后的购买次数',
`cost_id` int(11) ,
`show_price` int(11) COMMENT '陈涛:
展示价格
0-不显示展示价格',
`desc1` varchar(512) ,
`if_onsale` int(11) COMMENT '徐力丰:
1=上架，客户端显示
0=下架，客户端不显示',
`min_level` int(11) COMMENT '徐力丰:
该项目显示的最小府衙等级',
`max_level` int(11) COMMENT '徐力丰:
该项目显示的最大府衙等级
',
`city_id` int(11) COMMENT '徐力丰:
城战城市id',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Quick_bug`
--
DROP TABLE IF EXISTS `Quick_bug`;
CREATE TABLE IF NOT EXISTS `Quick_bug` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '陆阳:
1=粮食
2=木材
3=黄金
4=石块
5=铁块',
`shop_id` text ,
`min_level` int(11) COMMENT '徐力丰:
该项目显示的最小府衙等级',
`max_level` int(11) COMMENT '徐力丰:
该项目显示的最大府衙等级
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `market`
--
DROP TABLE IF EXISTS `market`;
CREATE TABLE IF NOT EXISTS `market` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '陈涛:
1-普通商品
2-当日特卖',
`type_chance` int(11) COMMENT '刷新权重',
`commodity_data` int(11) COMMENT '对应drop表drop_id',
`if_onsale` int(11) COMMENT '折扣比例，(前端可用于判断显示商品外发光颜色）
0-无折扣
1-9折
2-8折
3-7折',
`cost_id` int(11) COMMENT '无折扣时的价格',
`show_price` int(11) COMMENT '元宝折扣价，仅当costid 为元宝时生效',
`desc1` varchar(512) ,
`min_level` int(11) COMMENT '徐力丰:
该项目显示的最小府衙等级',
`max_level` int(11) COMMENT '徐力丰:
该项目显示的最大府衙等级
',
`refresh_control_id` int(11) COMMENT '徐力丰:
该字段用于控制集市中不再刷新与特惠商品相同的商品',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Soldier`
--
DROP TABLE IF EXISTS `Soldier`;
CREATE TABLE IF NOT EXISTS `Soldier` (
`id` int(11) NOT NULL  COMMENT '陈涛:
1-步兵
2-骑兵
3-弓兵
4-车兵',
`soldier_name` int(11) COMMENT '张立:
30开头是士兵名字
31开头是士兵介绍
32开头是士兵技能名字
33开头士兵技能介绍
',
`desc1` varchar(512) ,
`type_name` int(11) ,
`arm_type` int(11) COMMENT '陆阳:
1盾
2枪
3骑
4弓骑
5弓
6弩
7冲车
8投石
9万能
',
`desc2` varchar(512) ,
`soldier_level` int(11) COMMENT '士兵等级',
`soldier_type` int(11) COMMENT '陈涛:
1-步兵
2-骑兵
3-弓兵
4-投石车
5-万能',
`img_level` int(11) COMMENT '陆阳:
士兵等级对',
`img_head` int(11) COMMENT '陆阳:
士兵小头像',
`img_portrait` int(11) COMMENT '陆阳:
士兵肖像',
`img_type` int(11) COMMENT '陆阳:
兵种图标',
`soldier_introduction` int(11) ,
`desc3` varchar(512) ,
`soldier_intro` int(11) ,
`desc4` varchar(512) ,
`attack` int(11) ,
`defense` int(11) ,
`life` int(11) ,
`distance` int(11) COMMENT '陈涛:
射程',
`speed` int(11) COMMENT '陈涛:
移动速度',
`weight` int(11) COMMENT '陈涛:
负重',
`add_buff` text COMMENT '陆阳:
关联BUFF表',
`cost` text COMMENT '道具ID,数量（中间用分号隔开）
1-黄金
2-粮食
3-木材
4-石矿
5-铁矿',
`rescue_cost` text COMMENT '复活士兵的消耗
等于建造士兵的消耗的一半',
`upgrade_id` int(11) COMMENT '兵种升级
填写目标兵种的id
0表示无法升级',
`upgrade_cost` text COMMENT '升级兵种所需的价格
0表示无法升级',
`consumption` int(11) COMMENT '陈涛:
每小时粮食消耗(万分比)',
`power` int(11) COMMENT '陈涛:
单个士兵战斗力
（需要除以一万后的固定值）',
`train_time` int(11) COMMENT '单个士兵建造时间
单位：秒',
`rescue_time` int(11) COMMENT '复活单个士兵所需时间',
`skill_1` int(11) ,
`skill_2` int(11) ,
`skill_3` int(11) ,
`need_build_id` int(11) COMMENT '陈涛:
开放等级
',
`gem_cost` int(11) COMMENT '陈涛:
快速建造单个士兵所花费的元宝数（需要除以一万后的固定值）向上取整',
`rescue_gem_cost` int(11) COMMENT '陈涛:
单个士兵元宝复活消耗
（需要除以一万后的固定值）向上取整',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Soldier_skills`
--
DROP TABLE IF EXISTS `Soldier_skills`;
CREATE TABLE IF NOT EXISTS `Soldier_skills` (
`id` int(11) NOT NULL ,
`soldier_skills_name` int(11) ,
`desc1` varchar(512) ,
`soldier_skill_introduction` int(11) COMMENT '陈涛:
技能介绍',
`desc2` varchar(512) ,
`soldier_skills_type` int(11) COMMENT '陈涛:
技能类型',
`soldier_skill_num` int(11) COMMENT '陈涛:
技能数值(万分比)
暂不读取',
`soldier_skill_img` varchar(512) COMMENT '陈涛:
技能图标',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Starting`
--
DROP TABLE IF EXISTS `Starting`;
CREATE TABLE IF NOT EXISTS `Starting` (
`id` int(11) NOT NULL ,
`name` varchar(512) ,
`data` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Talent`
--
DROP TABLE IF EXISTS `Talent`;
CREATE TABLE IF NOT EXISTS `Talent` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '陈涛:
1-军事
2-经济
3-城防',
`talent_type_id` int(11) ,
`level_id` int(11) ,
`max_level` int(11) ,
`buff_num_type` int(11) COMMENT '陈涛:
1-万分比
2-具体值',
`buff_num` int(11) COMMENT '陈涛:
buff数值
',
`max_buff_num` int(11) COMMENT '陈涛:
当前等级max的buff数值',
`talent_drop` text ,
`talent_name` int(11) ,
`desc1` varchar(512) ,
`talent_text` int(11) COMMENT '陈涛:
天赋介绍',
`desc2` varchar(512) ,
`condition_talent` text COMMENT '陈涛:
开启前置条件ID
或的关系，中间用;隔开
0-默认开启
',
`next_talent` int(11) COMMENT '陈涛:
下一级天赋ID
-1 代表结束，没有下一个',
`master_level` int(11) ,
`cost` int(11) COMMENT '陈涛:
消耗技能点数',
`power` int(11) COMMENT '陈涛:
该天赋点数对应战斗力',
`img` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `target`
--
DROP TABLE IF EXISTS `target`;
CREATE TABLE IF NOT EXISTS `target` (
`id` int(11) NOT NULL  COMMENT '郑煦贤:
表格ID，即任务的排序',
`type` int(11) COMMENT '郑煦贤:
目标对应类型

1-府衙等级
2-从资源田获得资源
3-主公等级
4-VIP等级
5-拥有武将数量
6-建筑升级次数
7-最高战力
8-击杀野怪次数
9-击杀最高野怪等级
10-出征加速次数
11-采集资源量
12-训练士兵数
13-科技研发次数
14-拥有蓝装数量
15-抢夺采集资源量
16-主动技能使用次数
17-穿戴宝物数量
18-分解白银数
19-装备进阶次数
20-装备最高进阶数（拥有即视为已完成）蓝装或以上品质
21-联盟捐献次数
22-花费个人荣誉
23-联盟帮助次数
24-侦查次数
25-攻城次数
26-治疗兵数
27-陷阱制造数
28-攻城掠夺资源数',
`target_value` int(11) COMMENT '郑煦贤:
目标数值',
`target_desc` int(11) COMMENT '郑煦贤:
目标说明ID',
`desc` varchar(512) COMMENT '郑煦贤:
目标说明文本备注',
`time` int(11) COMMENT '郑煦贤:
目标开放持续时间，以
秒为单位',
`drop` text COMMENT '郑煦贤:
目标完成的真实奖励，走DROP表
客户端仅显示第一个奖励',
`next_target_id` int(11) COMMENT '作者:
下个目标id
0:没有下个目标
',
`jump` int(11) ,
`Level_min` int(11) COMMENT '徐力丰:
该目标开启的最小府衙等级',
`open_time` int(11) COMMENT '徐力丰:
在服务器开启第几天开放',
`drop_2` int(11) COMMENT '徐力丰:
第2天进入的玩家的掉落',
`drop_3` int(11) COMMENT '徐力丰:
第3天进入的玩家的掉落',
`drop_4` int(11) COMMENT '徐力丰:
第4天进入的玩家的掉落',
`drop_5` int(11) COMMENT '徐力丰:
第5天进入的玩家的掉落',
`drop_6` int(11) COMMENT '徐力丰:
第6天进入的玩家的掉落',
`drop_7` int(11) COMMENT '徐力丰:
第7天进入的玩家的掉落',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Time_Limit_Match`
--
DROP TABLE IF EXISTS `Time_Limit_Match`;
CREATE TABLE IF NOT EXISTS `Time_Limit_Match` (
`id` int(11) NOT NULL ,
`match_type` text COMMENT '作者:
对应match_type中type字段，随机抽取其中一个',
`time` int(11) COMMENT '作者:
持续时间
天数
从0点~23点59分59秒',
`drop_id` text COMMENT '作者:
对应Point_drop的ID
',
`rank_drop_id` text COMMENT '作者:
对应Point_drop的ID
',
`match_show` int(11) COMMENT '作者:
活动展示图',
`help_desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Time_Limit_Match_Type`
--
DROP TABLE IF EXISTS `Time_Limit_Match_Type`;
CREATE TABLE IF NOT EXISTS `Time_Limit_Match_Type` (
`id` int(11) NOT NULL  COMMENT '先实现有颜色的功能点',
`type` int(11) COMMENT '作者:
1、采集资源
2、攻打怪物
3、提升战力
4、城堡发展
5、训练士兵
6、神秘商人
7、锻造大师
8、搜集宝物
9、鏖战沙场',
`name` varchar(512) ,
`desc` varchar(512) ,
`point` int(11) COMMENT '作者:
此类项目得分',
`language_id` int(11) ,
`help_type` int(11) ,
`type_desc` int(11) ,
`desc1` varchar(512) ,
`match_show` int(11) COMMENT '作者:
活动展示图',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Time_Limit_Match_Point_drop`
--
DROP TABLE IF EXISTS `Time_Limit_Match_Point_drop`;
CREATE TABLE IF NOT EXISTS `Time_Limit_Match_Point_drop` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1、达到积分奖励
2、排名奖励
3、总排名奖励',
`min_point` int(11) ,
`max_point` int(11) ,
`drop` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_Match`
--
DROP TABLE IF EXISTS `Alliance_Match`;
CREATE TABLE IF NOT EXISTS `Alliance_Match` (
`id` int(11) NOT NULL ,
`match_type` text COMMENT '作者:
对应match_type中type字段，随机抽取其中一个',
`time` int(11) COMMENT '作者:
持续时间
天数
从0点~23点59分59秒',
`drop_id` text COMMENT '作者:
对应Point_drop的ID
',
`rank_drop_id` text COMMENT '作者:
对应Point_drop的ID
',
`match_show` int(11) COMMENT '作者:
活动展示图',
`help_desc` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_Match_Type`
--
DROP TABLE IF EXISTS `Alliance_Match_Type`;
CREATE TABLE IF NOT EXISTS `Alliance_Match_Type` (
`id` int(11) NOT NULL  COMMENT '先实现有颜色的功能点',
`type` int(11) COMMENT '作者:
1、联盟捐献
2、和氏璧
3、黄巾军
4、占塔--阿拉希
',
`name` varchar(512) ,
`desc` varchar(512) ,
`point` int(11) COMMENT '得分配置',
`language_id` int(11) ,
`desc1` varchar(512) ,
`settings` text COMMENT '参数配置',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_Match_Point_drop`
--
DROP TABLE IF EXISTS `Alliance_Match_Point_drop`;
CREATE TABLE IF NOT EXISTS `Alliance_Match_Point_drop` (
`id` int(11) NOT NULL ,
`type` int(11) COMMENT '作者:
1、达到积分奖励
2、排名奖励
3、总排名奖励',
`min_point` int(11) ,
`max_point` int(11) ,
`drop` int(11) ,
`Alliance_honor_drop` int(11) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Treasure_Buff`
--
DROP TABLE IF EXISTS `Treasure_Buff`;
CREATE TABLE IF NOT EXISTS `Treasure_Buff` (
`id` int(11) NOT NULL ,
`count_min` int(11) ,
`count_max` int(11) ,
`buff_temp_id` text ,
`language_id` int(11) ,
`desc1` varchar(512) ,
`buff_value` int(11) ,
`img` int(11) COMMENT '作者:
图标',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Huangjin_Attack_Mob`
--
DROP TABLE IF EXISTS `Huangjin_Attack_Mob`;
CREATE TABLE IF NOT EXISTS `Huangjin_Attack_Mob` (
`id` int(11) NOT NULL  COMMENT '作者:
即波次',
`type_and_count` text COMMENT '作者:
兵种id,数量;',
`power_score_rate` int(11) COMMENT '击杀的黄巾军战力/rate=获得的积分
向下取整',
`drop` int(11) COMMENT '作者:
过关掉落，仅发最后一个',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Alliance_Match_chest_drop`
--
DROP TABLE IF EXISTS `Alliance_Match_chest_drop`;
CREATE TABLE IF NOT EXISTS `Alliance_Match_chest_drop` (
`id` int(11) NOT NULL  COMMENT '作者:
联盟名次',
`rank` int(11) COMMENT '作者:
联盟获得的名次',
`item_id` int(11) COMMENT '作者:
物品id',
`max_count` int(11) COMMENT '作者:
物品数量',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Trap`
--
DROP TABLE IF EXISTS `Trap`;
CREATE TABLE IF NOT EXISTS `Trap` (
`id` int(11) NOT NULL ,
`trap_type` int(11) COMMENT '作者:
1-落石--克制步兵
2-火箭--克制骑兵
3-滚木--克制弓兵',
`trap_name` int(11) COMMENT '作者:
陷阱名称',
`desc1` varchar(512) ,
`description` int(11) ,
`desc2` varchar(512) ,
`img_level` int(11) ,
`img_head` int(11) COMMENT '作者:
陷阱小图',
`img_portrait` int(11) COMMENT '作者:
陷阱大图',
`need_build_id` int(11) COMMENT '作者:
对应战争工坊等级开放',
`level` int(11) COMMENT '作者:
士兵等级',
`atk` int(11) COMMENT '作者:
攻击力',
`distance` int(11) COMMENT '作者:
射程',
`cost` text COMMENT '作者:
升级消耗
格式：
道具ID,数量（中间用分号隔开）
1-黄金
2-粮草
3-木材
4-石材
5-铁材',
`cost_gem` int(11) COMMENT '作者:
快速建造单个陷阱所需花费的元宝',
`train_time` int(11) COMMENT '作者:
生产单个所需时间（秒）',
`power` int(11) COMMENT '作者:
单个陷阱战斗力
',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Vip`
--
DROP TABLE IF EXISTS `Vip`;
CREATE TABLE IF NOT EXISTS `Vip` (
`id` int(11) NOT NULL ,
`vip_level` int(11) ,
`vip_exp` int(11) COMMENT '升到该级VIP所需点数
即升到VIP2所需经验为500',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Vip_Exp_Daily`
--
DROP TABLE IF EXISTS `Vip_Exp_Daily`;
CREATE TABLE IF NOT EXISTS `Vip_Exp_Daily` (
`id` int(11) NOT NULL ,
`vip_level` int(11) ,
`if_vip_actived` int(11) COMMENT 'Vip是否激活状态
1是
0否',
`continue_sign_days` int(11) COMMENT '连续签到天数',
`vipexp` int(11) COMMENT 'vip经验',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Vip_Privilege`
--
DROP TABLE IF EXISTS `Vip_Privilege`;
CREATE TABLE IF NOT EXISTS `Vip_Privilege` (
`id` int(11) NOT NULL ,
`vip_lv` int(11) ,
`num_type` int(11) COMMENT '1-万分比
2-具体值',
`buff_num` int(11) COMMENT '特权buff的效果',
`privilege_type` int(11) COMMENT '一种特权对应一个type',
`icon` int(11) COMMENT '特权icon',
`buff_desc` int(11) ,
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Warehouse`
--
DROP TABLE IF EXISTS `Warehouse`;
CREATE TABLE IF NOT EXISTS `Warehouse` (
`id` int(11) NOT NULL  COMMENT '作者:
仓库',
`build_id` int(11) COMMENT '作者:
建筑等级',
`gold` int(11) ,
`grain` int(11) COMMENT '作者:
粮食',
`wood` int(11) COMMENT '作者:
木材',
`iron` int(11) COMMENT '作者:
铁',
`stone` int(11) COMMENT '作者:
石材',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Warfare_service_config`
--
DROP TABLE IF EXISTS `Warfare_service_config`;
CREATE TABLE IF NOT EXISTS `Warfare_service_config` (
`id` int(11) NOT NULL ,
`name` varchar(512) COMMENT '作者:
配置项名称',
`data` varchar(512) COMMENT '作者:
数值或公式',
`text` varchar(512) COMMENT '作者:
文本说明',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `Recommend_tips`
--
DROP TABLE IF EXISTS `Recommend_tips`;
CREATE TABLE IF NOT EXISTS `Recommend_tips` (
`id` int(11) NOT NULL ,
`location` int(11) COMMENT '作者:
1=攻方
2=守方',
`path_type` int(11) COMMENT '作者:
1=A路线
2=B路线
',
`task_type` int(11) COMMENT '作者:
1=主线
2=支线',
`open_type` text COMMENT '作者:
不同的触发条件',
`desc` int(11) ,
`desc1` varchar(512) ,
`to_target` int(11) COMMENT '作者:
支线任务关联主线
',
`skip_type` int(11) COMMENT '作者:
跳转类型
1=坐标
2=框
3=复活点
4=军团',
`skip_show` text COMMENT '作者:
跳转类型
1=坐标
2=区域编号，map_Map_Element中的origin_id
3=area id
',
`priority` int(11) COMMENT '作者:
任务显示优先级',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Table structure for table `War_info`
--
DROP TABLE IF EXISTS `War_info`;
CREATE TABLE IF NOT EXISTS `War_info` (
`id` int(11) NOT NULL ,
`type_name` varchar(512) ,
`info_desc` int(11) ,
`info_desc1` int(11) COMMENT '作者:
大走马灯',
`info_desc2` int(11) COMMENT '作者:
特殊情况需要目标方得知消息的文字
走马灯',
`info_type` int(11) COMMENT '作者:

0或者不填是加到右上角提示 
1是所有人屏幕中间上浮 
2是仅自己屏幕中间上浮',
`desc1` varchar(512) ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

