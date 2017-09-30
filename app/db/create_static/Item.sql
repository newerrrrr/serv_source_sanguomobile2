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
