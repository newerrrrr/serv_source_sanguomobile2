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
