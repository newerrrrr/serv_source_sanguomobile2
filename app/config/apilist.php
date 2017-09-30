<?php
$gApiList = array(
	'data' => array(
		'Player' => array(
			'name' => '玩家',
			'param'=> array(
				//'abc' => array('title'=>'注释', 'default'=>''),
			),
		),
		'PlayerInfo' => array(
			'name' => '玩家信息（不常变数据）',
			'param'=> array(
				//'abc' => array('title'=>'注释', 'default'=>''),
			),
		),
		'PlayerHelp' => array(
			'name' => '玩家帮助',
		),
		'PlayerGeneral' => array(
			'name' => '武将',
		),
		'PlayerTalent' => array(
			'name' => '天赋',
		),
		'PlayerMasterSkill' => array(
			'name' => '主动技能',
		),
		'PlayerItem' => array(
			'name' => '道具背包',
		),
		'PlayerEquipment' => array(
			'name' => '武将装备背包',
		),
		'PlayerEquipMaster' => array(
			'name' => '主公宝物背包',
		),
		'PlayerBuild' => array(
			'name' => '建筑',
		),
		'PlayerSoldier' => array(
			'name' => '士兵',
		),
		'PlayerStudy' => array(
			'name' => '书院',
		),
		'PlayerArmy' => array(
			'name' => '军团/校场',
		),
		'PlayerArmyUnit' => array(
			'name' => '军团/校场单位',
		),
		'PlayerScience' => array(
			'name' => '科技',
		),
		'PlayerPub' => array(
			'name' => '酒馆',
		),
		'PlayerSoldier' => array(
			'name' => '士兵',
		),
		'PlayerMission' => array(
			'name' => '任务(主线+每日)',
		),
		'Guild' => array(
			'name' => '联盟',
		),
		'PlayerGuild' => array(
			'name' => '我的联盟信息',
		),
		'GuildScience' => array(
			'name' => '联盟科技',
		),
		'GuildShop' => array(
			'name' => '联盟商店',
		),
		'PlayerCoordinate' => array(
			'name' => '收藏坐标',
		),
		'PlayerShop' => array(
			'name' => '商店',
		),
		'ChatBlackList'     => ['name' => '聊天黑名单'],
        'PlayerSignAward'   => ['name' => '每日签到奖励'],
        'PlayerOnlineAward' => ['name' => '每日在线奖励'],
        'PlayerMarket'      => ['name' => '集市'],
        'PlayerTarget'      => ['name' => '新手目标'],
        'PlayerMill'        => ['name' => '磨坊'],
        'PlayerGrowth'      => ['name' => '成长基金'],
        'PlayerDrawCard'    => ['name' => '玩家抽奖'],
        'PlayerGuildDonate' => ['name' => '捐赠信息'],
        'PkPlayerInfo'      => ['name' => '武斗基础信息'],
		//'PlayerBuff'=>['name'=>'buff'],
		//'PlayerBuffTemp'=>['name'=>'buff'],
		'CrossPlayer' => ['name' => '跨服玩家'],
		'CrossGuild' => ['name' => '跨服公会'],
		'CrossPlayerSoldier' => ['name' => '跨服战士兵'],
		'CrossPlayerGeneral' => ['name' => '跨服战武将'],
		'CrossPlayerArmy' => ['name' => '跨服战军团/校场'],
		'CrossPlayerArmyUnit' => ['name' => '跨服战军团/校场单位'],
		'CrossPlayerMasterskill'=>['name' => '跨服战主动技'],
		'PlayerNewbieActivityLogin' => ['name' => '新人玩家登陆活动'],
		'PlayerNewbieActivityCharge' => ['name' => '新人玩家累计充值活动'],
		'PlayerNewbieActivityConsume' => ['name' => '新人玩家累计消耗活动'],
		'CityBattleScience' => ['name' => '城战科技'],
		'PlayerCitybattleDonate' => ['name' => '城战科技个人捐献'],
		'CityBattleBuff' => ['name' => '城战科技buff'],
		'CityBattlePlayer' => ['name' => '城战玩家'],
		'CityBattleCamp' => ['name' => '城战阵营'],
		'CityBattlePlayerGeneral' => ['name' => '城战武将'],
		'CityBattlePlayerArmy' => ['name' => '城战军团/校场'],
		'CityBattlePlayerArmyUnit' => ['name' => '城战军团/校场单位'],
		'CityBattlePlayerMasterskill'=>['name' => '城战主动技'],
	),
	'post' => array(
		'Interface'=>array(
			'transTime'=>array(
				'name' => '时间转换',
				'param'=> array(
					'time' => array('title'=>'时间戳或格式化的时间', 'default'=>''),
				),
			),
			'addItem'=>array(
				'name' => '添加道具',
				'param'=> array(
					'itemId' => array('title'=>'', 'default'=>''),
					'num' => array('title'=>'', 'default'=>''),
				),
			),
			'addEquipMasterItem'=>array(
				'name' => '添加宝物',
				'param'=> array(
					'itemId' => array('title'=>'', 'default'=>''),
				),
			),
			'armyReturn'=>array(
				'name' => '军团归位',
			),
			'fixPlayerBuff'=>array(
				'name' => '修复PlayerBuff表数据',
			),
			'playerDailyMission'=>array(
				'name' => '如果不存在当天每日任务，生成当天每日任务',
			),
			'order'=>array(
				'name' => '订单回调',
				'param'=> array(
					'order_id' => array('title'=>'订单号', 'default'=>''),
					'commodity_id' => array('title'=>'充值配置id', 'default'=>''),
					'player_id' => array('title'=>'', 'default'=>''),
					'server_id' => array('title'=>'', 'default'=>''),
					'channel' => array('title'=>'渠道', 'default'=>''),
					'mode' => array('title'=>'SDK 或  web (一般指 官网)', 'default'=>''),
				),
			),
			'orderWeb'=>array(
				'name' => '官网订单回调',
				'param'=> array(
					'order_id' => array('title'=>'订单号', 'default'=>''),
					'commodity_id' => array('title'=>'充值配置id', 'default'=>''),
					'player_id' => array('title'=>'', 'default'=>''),
					'server_id' => array('title'=>'', 'default'=>''),
					'channel' => array('title'=>'渠道', 'default'=>''),
					'mode' => array('title'=>'SDK 或  web (一般指 官网)', 'default'=>''),
				),
			),
			'sendSysMailToAll'=>array(
				'name' => '全服发送邮件',
				'param'=> array(
					//'title' => array('title'=>'标题', 'default'=>''),
					'msg' => array('title'=>'正文', 'default'=>'', 'type'=>'textarea'),
				),
			),
		),
		'Player'=>array(
		    'treasureBowl' => [
		        'name' => '聚宝盆',
                'param' => [
                    'type'          => ['title' => '1 占星 2 天陨', 'default' => 1],
                    'multi_flag'    => ['title' => '0 单抽 1 十连', 'default' => 0],
                    'free_flag'     => ['title' => '0 收费 1 免费', 'default' => 0],
                    'use_item_flag' => ['title'=>'0 不用道具 1 使用道具', 'default'=>0],
                ]
            ],
		    'sacrificeToHeaven' => [
		        'name' => '祭天',
                'param' => [
                    'camp_id'       => ['title' => '1 魏 2蜀 3吴 4 群', 'default' => 1],
                    'multi_flag'    => ['title' => '0 单抽 1 十连', 'default' => 0],
                    'free_flag'     => ['title' => '0 收费 1 免费', 'default' => 0],
                    'use_item_flag' => ['title'=>'0 不用道具 1 使用道具', 'default'=>0],
                ]
            ],
		    'getSacrificeGM' => [
		        'name' => '祭天GM',
            ],
			'playerInfoDetail' => [
				'name' => '玩家信息细节',
				'param' => [
					'player_id'	=> ['title'=>'目标玩家id', 'default'=>''],
				]
			],
			'viewTargetPlayerInfo'	=> [
				'name' => '查看其他玩家信息',
				'param'=> [
					'target_player_id' => ['title'=>'目标玩家id', 'default'=>''],
				],
			],
			'updateClientId'=>[
				'name' => '更新客户端推送标识',
				'param'=> [
					'deviceType' => ['title'=>'1.ios,2.android', 'default'=>''],
					'clientId' => ['title'=>'客户端推送标识', 'default'=>''],
					'deviceToken' => ['title'=>'ios推送标识', 'default'=>''],
				],
			],
			'updatePushTag'=>[
				'name' => '更新推送开关',
				'param'=> [
					'pushTag' => ['title'=>'数组。1.	升级、训练；2.	战斗；3.	活动；4.	联盟任务', 'default'=>''],
				],
			],
			'viewAttackArmy'	=> [
				'name' => '查看攻击我的所有部队'
			],
			'home'=>array(
				'name' => '主城',
			),
			'talentAdd'=>array(
				'name' => '天赋加点',
				'param'=> array(
					'talentTypeId' => array('title'=>'天赋类型', 'default'=>''),
				),
			),
			'talentReset'=>array(
				'name' => '天赋重置',
			),
			'talentUse'=>array(
				'name' => '天赋主动技能使用',
				'param'=> array(
					'talentId' => array('title'=>'天赋id', 'default'=>''),
				),
			),
			'shopBuy'=>array(
				'name' => '商店购买',
				'param'=> array(
					'shopId' => array('title'=>'商店货物id', 'default'=>''),
					'itemNum' => array('title'=>'道具数量', 'default'=>''),
					'use' => array('title'=>'直接使用', 'default'=>''),
				),
			),
			'getBuff'=>array(
				'name' => '增益',
			),
			'getItemBuff'=>array(
				'name' => '道具增益',
			),
			'buyExtraBuildQueue'=>array(
				'name' => '购买额外建造队列',
				'param'=> array(
					'itemNum' => array('title'=>'数量', 'default'=>''),
				),
			),
			'equipMasterOn'=>array(
				'name' => '穿宝物装备,包括换装备',
				'param'=> array(
					'old_id'   => array('title'=>'被换的主公宝物', 'default'=>''),
					'new_id'   => array('title'=>'新换上的主公宝物', 'default'=>''),
					'position' => array('title'=>'位置', 'default'=>''),
				),
			),
			'equipMasterOff'=>array(
				'name' => '脱宝物装备',
				'param'=> array(
					'id'   => array('title'=>'表中的id', 'default'=>''),
				),
			),
            'sellEquipMaster'=>array(
                'name' => '出售宝物装备',
                'param'=> array(
                    'id'   => array('title'=>'表中的id', 'default'=>''),
                ),
            ),
			'spy'=>array(
				'name' => '侦查 主城，侦查联盟堡垒和侦查矿',
				'param'=> array(
					'to_x'   => array('title'=>'目标x值', 'default'=>''),
					'to_y'   => array('title'=>'目标y值', 'default'=>''),
				),
			),
			'getRank'=>array(
				'name' => '排行榜',
				'param'=> array(
					'type'   => array('title'=>"1：玩家实力排行\r\n2：玩家等级排行\r\n3：玩家消灭敌军排行\r\n4：玩家城堡排行\r\n5：联盟战斗力排行\r\n6：联盟消灭敌军排行\r\n", 'default'=>''),
				),
			),
			'getGeneralBuffByBuild'=>array(
				'name' => '武将buff',
				'param'=> array(
					'position'   => array('title'=>"建筑位置", 'default'=>''),
				),
			),
			'useCdk'=>array(
				'name' => '使用激活码',
				'param'=> array(
					'cdk'   => array('title'=>"激活码", 'default'=>''),
				),
			),
		),
		'General'=>array(
			'levelUp'=>array(
				'name' => '武将升级',
				'param'=> array(
					'generalId' => array('title'=>'武将id', 'default'=>''),
				),
			),
			'equip'=>array(
				'name' => '武将装备',
				'param'=> array(
					'generalId' => array('title'=>'武将id', 'default'=>''),
					'itemId' => array('title'=>'装备id，0 - 卸下', 'default'=>'0'),
					'type' => array('title'=>'位置，2：防具，3：饰品', 'default'=>'2'),
				),
			),
			'fire'=>array(
				'name' => '武将解雇',
				'param'=> array(
					'generalId' => array('title'=>'武将id', 'default'=>''),
				),
			),
		),
		'King'=>[
			'voteList' => [
				'name'=>'选举列表',
			],
			'doVote' => [
				'name'=>'选举某人',
				'param'=>[
				'target_player_id'=>['title'=>'玩家id', 'default'=>'']
				]
			],
			'getHistoryKing' => [
				'name'=>'显示历任国王',
			],
			'appointment' => [
				'name'=>'任命',
				'param'=>[
					'nick'=>['title'=>'玩家名字', 'default'=>''],
					'jobId'=>['title'=>'官职id', 'default'=>''],
				]
			],
			'getScore' => [
				'name'=>'获取积分',
			],
			'getInfo' => [
				'name'=>'获取王战状态',
			],
			'getJob' => [
				'name'=>'官职列表',
			],
			'getTownInfo' => [
				'name'=>'城寨信息',
			],
			'getTownArmy' => [
				'name'=>'城寨军团信息',
				'param'=>[
					'x'=>['title'=>'', 'default'=>''],
					'y'=>['title'=>'', 'default'=>''],
				]
			],
		],
		'Common'=>[
			'combo' => [
				'name' => '合并接口',
                'param'=>[
                    'combo'=>['title'=>'合并的url列表', 'default'=>'']
                ]
			],
		    'comboChat' => [
		        'name' => '消息合并[common]'
            ],
		    'lastWorldChatMsg' => [
		        'name' => '最后一条世界聊天消息'
            ],
			'viewAllWorldMsg' => [
				'name'=>'查看世界聊天信息',
			],
			'viewAllGuildMsg' => [
				'name'=>'查看当前联盟聊天信息',
			],
			'addChatBlack' => [
				'name'=>'添加一个黑名单',
				'param'=>[
				'black_player_id'=>['title'=>'玩家id', 'default'=>'']
				]
			],
			'removeChatBlack' => [
				'name'=>'删除黑名单',
				'param'=>[
				'black_player_ids'=>['title'=>'玩家id', 'default'=>'']
				]
			],
			'getAllNotice'=> [
				'name'=>'所有公告'
			],
			'getValidCode'=> [
				'name'=>'获取验证信息'
			],
		],
		'Build'=>array(
			'construct'=>array(
				'name' => '建造',
				'param'=> array(
					'buildId' => array('title'=>'建筑id', 'default'=>''),
					'position' => array('title'=>'建造位置', 'default'=>''),
				),
			),
			'lvUp'=>array(
				'name' => '升级',
				'param'=> array(
					'position' => array('title'=>'建造位置', 'default'=>''),
				),
			),
			'reWriteBuildInfo'=>array(
				'name' => '加速升级',
				'param'=> array(
					'position' => array('title'=>'建造位置', 'default'=>''),
				),
			),
			'accelerate'=>array(
				'name' => '加速升级',
				'param'=> array(
					'position' => array('title'=>'建造位置', 'default'=>''),
				),
			),
			'cancel'=>array(
				'name' => '取消升级',
				'param'=> array(
					'position' => array('title'=>'建造位置', 'default'=>''),
				),
			),
			'setGeneral'=>array(
				'name' => '驻守武将',
				'param'=> array(
					'position' => array('title'=>'建造位置', 'default'=>''),
					'generalId' => array('title'=>'武将id，0-卸下', 'default'=>''),
				),
			),
			'gainResource'=>array(
				'name' => '收获资源',
				'param'=> array(
					'position' => array('title'=>'建造位置（数组）', 'default'=>''),
				),
			),
		),
		'Item'=>array(
			'use'=>array(
				'name' => '背包道具使用',
				'param'=> array(
					'itemId' => array('title'=>'道具id', 'default'=>''),
					'itemNum' => array('title'=>'使用数量', 'default'=>'1'),
				),
			),
			'combine'=>array(
				'name' => '背包道具合成',
				'param'=> array(
					'itemId' => array('title'=>'目标道具id', 'default'=>''),
					'itemNum' => array('title'=>'目标数量', 'default'=>'1'),
				),
			),
			'setNew'=>array(
				'name' => '设置背包道具已读',
				'param'=> array(
				),
			),
		),
		'Study'=>array(
			'setGeneral'=>array(
				'name' => '设置武将',
				'param'=> array(
					'position' => array('title'=>'位置', 'default'=>''),
					'generalId' => array('title'=>'武将id', 'default'=>''),
				),
			),
			'begin'=>array(
				'name' => '开始学习',
				'param'=> array(
					'position' => array('title'=>'位置', 'default'=>''),
					'type' => array('title'=>'学习类型，1-免费4小时，2-免费8小时，3-免费12小时，4-付费4小时，5-付费8小时，6-付费12小时', 'default'=>''),
				),
			),
			'finish'=>array(
				'name' => '学习结算',
			),
			'accelerate'=>array(
				'name' => '加速学习',
				'param'=> array(
					'position' => array('title'=>'位置', 'default'=>''),
				),
			),
			'buyPosition'=>array(
				'name' => '购买学习位',
			),
		),
		'Army'=>array(
			'setUnit'=>array(
				'name' => '设置军团',
				'param'=> array(
					'position' => array('title'=>'军团号', 'default'=>''),
					'unit' => array('title'=>'单位（格式：{\'1\':[generalId1,soldierId1,soldierNum1],\'2\':[generalId2,soldierId2,soldierNum2],...}）', 'default'=>''),
				),
			),
			'setGeneral'=>array(
				'name' => '设置武将',
				'param'=> array(
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
					'unitPosition' => array('title'=>'武将位置号', 'default'=>''),
					'generalId' => array('title'=>'武将号', 'default'=>''),
				),
			),
			'setSoldier'=>array(
				'name' => '设置士兵',
				'param'=> array(
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
					'unitPosition' => array('title'=>'武将位置号', 'default'=>''),
					'soldierId' => array('title'=>'士兵id', 'default'=>''),
					'soldierNum' => array('title'=>'士兵数量', 'default'=>''),
				),
			),
			'fullfillSoldier'=>array(
				'name' => '快速补兵',
				'param'=> array(
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
				),
			),
			'assistArmyInfo'=>array(
				'name' => '我的援军信息',
			),
			/*'gatherArmyInfo'=>array(
				'name' => '我的集结信息',
			),*/
			'getBattleLog'=>array(
				'name' => '战争记录',
				'param'=> array(
					'type' => array('title'=>'类型（不填默认）', 'default'=>''),
				),
			),
			'getBattleLogDetail'=>array(
				'name' => '战报详情',
				'param'=> array(
					'battleLogId' => array('title'=>'战报id', 'default'=>''),
				),
			),
			'warArmyInfo'=>array(
				'name' => '攻击/防守队列信息',
				'param'=> array(
					'justCounter' => array('title'=>'仅返回数量', 'default'=>''),
				),
			),
			/*'setBattleLogNew'=>array(
				'name' => '设置战争记录已读',
			),*/
		),
		'Science'=>array(
			'begin'=>array(
				'name' => '开始研究科技',
				'param'=> array(
					'scienceTypeId' => array('title'=>'科技类型', 'default'=>''),
					'type' => array('title'=>'1.普通，2.立即', 'default'=>''),
				),
			),
			'finish'=>array(
				'name' => '科技研究完成',
			),
			'accelerate'=>array(
				'name' => '加速科技',
				'param'=> array(
					'scienceTypeId' => array('title'=>'科技类型', 'default'=>''),
					'type' => array('title'=>"2-元宝；3-道具", 'default'=>''),
					'itemId' => array('title'=>"（type=3）加速道具id\r\n 20701-普通1小时\r\n 21101-5分钟\r\n 21102-10分钟\r\n 21103-30分钟\r\n 21104-1小时\r\n 21105-2小时\r\n 21106-8小时\r\n", 'default'=>''),
					'itemNum' => array('title'=>'（type=3）加速道具数量', 'default'=>''),
				),
			),
		),
		'Smithy'=>array(
			'levelUp'=>array(
				'name' => '升阶',
				'param'=> array(
					'generalId' => array('title'=>'', 'default'=>''),
					'itemId' => array('title'=>'需要升阶的装备id', 'default'=>''),
					'materialItemId' => array('title'=>'需要吃掉的装备id', 'default'=>''),
				),
			),
			'rebuild'=>array(
				'name' => '重铸',
				'param'=> array(
					'itemId' => array('title'=>'装备id', 'default'=>''),
				),
			),
			'split'=>array(
				'name' => '分解',
				'param'=> array(
					'itemId' => array('title'=>'装备id', 'default'=>''),
				),
			),
			'materialCombine'=>array(
				'name' => '材料合成',
				'param'=> array(
					'itemId' => array('title'=>'目标合成道具id', 'default'=>''),
					'num' => array('title'=>'合成数量', 'default'=>''),
				),
			),
		),
		'Pub'=>array(
			'reload'=>array(
				'name' => '刷新',
				'param'=> array(
					'type' => array('title'=>'1：免费，2 : 道具/付费', 'default'=>''),
				),
			),
			'buy'=>array(
				'name' => '招募',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
				),
			),
			'buyPrisoner'=>array(
				'name' => '招安',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
				),
			),
			'buyFragment'=>array(
				'name' => '购买碎片/对酒',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
					'num' => array('title'=>'数量', 'default'=>''),
				),
			),
			'turnGod'=>array(
				'name' => '化神',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
				),
			),
			'starLvUp'=>array(
				'name' => '升星',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
				),
			),
			'starReward'=>array(
				'name' => '领取星级奖励',
				'param'=> array(
					'id' => array('title'=>'奖项id', 'default'=>''),
				),
			),
			'upGodSkill'=>array(
				'name' => '技能升级',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
				),
			),
			'upBattleSkill'=>array(
				'name' => '升级城战技能',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
					'id' => array('title'=>'槽位1/2/3', 'default'=>''),
				),
			),
			'generalAddExp'=>array(
				'name' => '武将吃书增加经验',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
					'itemId' => array('title'=>'道具id', 'default'=>''),
					'num' => array('title'=>'数量', 'default'=>''),
				),
			),
			'combineGodArmor'=>array(
				'name' => '神盔甲合成',
			),
			'washBattleSkill'=>array(
				'name' => '技能洗炼',
				'param'=> array(
					'generalId' => array('title'=>'武将Id', 'default'=>''),
					'id' => array('title'=>'槽位', 'default'=>''),
				),
			),
		),
		'Soldier'=>array(
			'recruit'=>array(
				'name' => '招募',
				'param'=> array(
					'soldierId' => array('title'=>'士兵id', 'default'=>''),
					'position' => array('title'=>'建筑位置', 'default'=>''),
					'num' => array('title'=>'士兵数量', 'default'=>''),
				),
			),
			'finishRecruit'=>array(
				'name' => '招募完成',
				'param'=> array(
					'position' => array('title'=>'建筑位置', 'default'=>''),
				),
			),
			'cancelRecruit'=>array(
				'name' => '取消招募',
				'param'=> array(
					'position' => array('title'=>'建筑位置', 'default'=>''),
				),
			),
			'accelerateRecruit'=>array(
				'name' => '加速招募',
				'param'=> array(
					'position' => array('title'=>'建筑位置', 'default'=>''),
				),
			),
			'cureInjuredSoldier'=>array(
				'name' => '治疗',
				'param'=> array(
					'soldierArr' => array('title'=>'需要治疗士兵数组', 'default'=>''),
				),
			),
			'doCureInjuredSoldierWithGemOrItem'=>array(
				'name' => '宝石或者道具完成治疗',
				'param'=> array(
					'item_id' => array('title'=>'医疗道具', 'default'=>''),
					'num'     => array('title'=>'数量', 'default'=>''),
				),
			),

		),
		
		'Guild'=>array(
			'comboGuildMemberInfo' => [
				'name' => '联盟合并消息'
			],
			'inviteRandPlayers' => [
				'name' => '邀请随机玩家入盟'
			],
			'inviteChangeCastleLocation' => [
				'name' => '联盟邀请迁城',
				'param' => [
					'target_player_id' => ['title'=>'玩家id','default'=>''],
					'x'	=> ['title'=>'x', 'default'=>''],
					'y'	=> ['title'=>'y', 'default'=>''],
				]
			],
			'getDonate'=>array(
				'name' => '获得捐赠信息',
				'param'=> array(
					'scienceType' => array('title'=>'科技type', 'default'=>''),
				),
			),
			'scienceDonate'=>array(
				'name' => '捐赠',
				'param'=> array(
					'scienceType' => array('title'=>'科技type', 'default'=>''),
					'btn' => array('title'=>'按钮号：1,2,3', 'default'=>''),
				),
			),
			'scienceClearTime'=>array(
				'name' => '清除捐赠cd',
				'param'=> array(
				),
			),
			'scienceUp'=>array(
				'name' => '联盟科技进阶',
				'param'=> array(
					'scienceType' => array('title'=>'科技type', 'default'=>''),
				),
			),
			'donateRecommend'=>array(
				'name' => '捐献推荐',
				'param'=> array(
					'scienceType' => array('title'=>'科技type', 'default'=>''),
				),
			),
			'donateReward'=>array(
				'name' => '捐献礼包',
				'param'=> array(
					'id' => array('title'=>'1.10人奖励，2.20人奖励，3.30人奖励', 'default'=>''),
				),
			),
			'shopLog'=>array(
				'name' => '联盟商店日志',
				'param'=> array(
					'type' => array('title'=>'1.进货；2.个人购买', 'default'=>''),
				),
			),
			'shopStock'=>array(
				'name' => '联盟商店进货',
				'param'=> array(
					'itemId' => array('title'=>'道具id', 'default'=>''),
					'itemNum' => array('title'=>'道具数量', 'default'=>''),
				),
			),
			'shopBuy'=>array(
				'name' => '联盟商店购买',
				'param'=> array(
					'itemId' => array('title'=>'道具id', 'default'=>''),
					'itemNum' => array('title'=>'道具数量', 'default'=>''),
				),
			),
			'viewGuildInfo'=>[
				'name'	=> '查看联盟',
				'param'	=> [
					'guild_id' => ['title'=>'联盟id', 'default'=>''],
				]
			],
			'searchGuild'=>[
				'name'	=> '搜索联盟',
				'param'	=> [
					'name'                   => ['title'=>'联盟名称', 'default'=>''],
					'num'                    => ['title'=>'联盟数量', 'default'=>'0'],
					'condition_fuya_level'   => ['title'=>'府衙等级', 'default'=>'0'],
					'condition_player_power' => ['title'=>'玩家战斗力', 'default'=>'0'],
					'need_check'             => ['title'=>'是否管理员确认', 'default'=>'-1'],
					]
			],
			'viewAllMember'=>[
				'name'	=> '查看联盟成员',
				'param'	=> [
					'guild_id'=> ['title'=>'联盟id', 'default'=>''],
				]
			],
            'viewAllMemberKing'=>[
                'name'	=> '[国王战]查看联盟成员',
                'param'	=> [
                    'guild_id'=> ['title'=>'联盟id', 'default'=>''],
                ]
            ],
			'viewAllRequestMember'=>[
				'name'	=> '查看所有申请联盟列表',
				'param'	=> [
					'guild_id'=> ['title'=>'联盟id', 'default'=>''],
				]
			],
			'viewGuildBuild'=>[
				'name'	=> '查看联盟领地建筑',
				'param'	=> [
					'type' => ['title'=>'1 #堡垒,2 #箭塔,3 #矿场 (金矿，粮矿，木矿，石矿，铁矿),4 #仓库', 'default'=>''],
				]
			],
			'canCreateGuildBuild'=>[
				'name'	=> '查看玩家可以造的联盟建筑',
			],
			'createGuildBuild'=>[
				'name'	=> '联盟领地建造',
				'param'	=> [
					'x'		   => ['title'=>'', 'default'=>''],
					'y'		   => ['title'=>'', 'default'=>''],
					'type'     => ['title'=>'1 #堡垒,2 #箭塔,3 #矿场 (金矿，粮矿，木矿，石矿，铁矿),4 #仓库', 'default'=>''],
					'resource' => ['title'=>'type=3时需要，(1金矿，2粮矿，3木矿，4石矿，5铁矿),', 'default'=>''],
				]
			],
			'applyForGuild'=>[
				'name'	=> '联盟申请apply',
				'param'	=> [
					'guild_id' => ['title'=>'联盟id','default'=>''],
				]
			],
			'agree'=>[
				'name'	=> '同意来申请的入盟请求',
				'param'	=> [
					'apply_player_id' => ['title'=>'申请者id','default'=>''],
				]
			],
			'refuse'=>[
				'name'	=> '拒绝来申请的入盟请求',
				'param'	=> [
					'apply_player_id' => ['title'=>'申请者id','default'=>''],
				]
			],
			'agreeInvite'=>[
				'name'	=> '同意邀请入盟',
				'param'	=> [
					'mail_id' => ['title'=>'id of mail','default'=>''],
				]
			],
			'refuseInvite'=>[
				'name'	=> '拒绝邀请入盟',
				'param'	=> [
					'mail_id' => ['title'=>'id of mail','default'=>''],
				]
			],
			'inviteGuild'=>[
				'name'	=> '邀请入盟',
				'param'	=> [
					'invite_player_id' => ['title'=>'被邀请者id','default'=>''],
					'guild_id'		   => ['title'=>'联盟id', 'default'=>''],
				]
			],
			'gotoGuildBuild'=>[
				'name'	=> 'goto联盟建筑（建造，修理，驻守）',
				'param'	=> [
					'x'       => ['title'=>'坐标x','default'=>''],
					'y'       => ['title'=>'坐标y', 'default'=>''],
					'army_id' => ['title'=>'军团id', 'default'=>''],
				]
			],
			'dismissGuild'=>[
				'name'	=> '解散联盟',
			],
			'changePlayerRank'=>[
				'name'	=> '提升权限和退出联盟',
				'param'	=> [
					'targetPlayerId' => ['title'=>'成员id','default'=>''],
					'targetRank'     => ['title'=>'0为踢人1-5为权限等级','default'=>''],
				]
			],
			'dismissSingleGuildBuild'=>[
				'name'	=> '拆除单个联盟建筑',
				'param'	=> [
					'x' => ['title'=>'','default'=>''],
					'y' => ['title'=>'','default'=>''],
				]
			],
			'expelPlayer'=>[
				'name'	=> '踢出联盟',
				'param'	=> [
					'targetPlayerId' => ['title'=>'成员id','default'=>''],
				]
			],
			'viewGuildBuildDetail'=>[
				'name'	=> '查看联盟成员列表',
				'param'	=> [
					'x' => ['title'=>'坐标x','default'=>''],
					'y' => ['title'=>'坐标y','default'=>''],
				]
			],
			'getMissionRank'=>[
				'name'	=> '获取联盟任务排名',
			],
			'showBoard'=>[
				'name'	=> '查看公告板',
			],
			'changeBoard'=>[
				'name'	=> '修改公告板',
				'param'	=> [
					'orderId' => ['title'=>'位置','default'=>''],
					'title'	=> ['title'=>'标题','default'=>''],
					'text' => ['title'=>'内容','default'=>''],
					'updateTime' => ['title'=>'修改时间','default'=>''],
				]
			],
			'swapBoard'=>[
				'name'	=> '交换公告位置',
				'param'	=> [
					'orderId1' => ['title'=>'位置1','default'=>''],
					'orderId2' => ['title'=>'位置2','default'=>''],
					'updateTime1' => ['title'=>'修改时间1','default'=>''],
					'updateTime2' => ['title'=>'修改时间2','default'=>''],
				]
			],
            'comboGuildMemberInfo' => [
                'name' => '合并消息[guild][viewAllMember+viewAllRequestMember+viewGuildInfo+showBoard]',
            ],
            'comboGuildBuild' => [
                'name' => '合并消息[guild][viewGuildBuild+canCreateGuildBuild]',
            ],
            'kickDefendArmyFromGuildBase' => [
                'name' => '盟主，副盟主，踢驻守部队',
                'param' => ['ppq_id'=>['title'=>'ppq_id', 'default'=>'']]
            ],
            'getGuildGiftInfo'=>[
                'name' => '查看公会礼包',
            ],
            'distributeGift'=>[
                'name' => '公会礼包',
                'param'=>['targetPlayerId'=>['title'=>'发放玩家id', 'default'=>''],'giftId'=>['title'=>'发放礼物id', 'default'=>'']]
            ],
            'changeCamp'=>[
                'name' => '换阵营',
                'param'=>['camp_id'=>['title'=>'阵营id', 'default'=>1]]
            ]
		),
		'Mail'=>array(
			'getList'=>array(
				'name' => '获得列表',
				'param'=> array(
					'type' => array('title'=>'目录：1:聊天；2:联盟；3:侦查；4:战斗；5:系统; 6:采集；7:打怪', 'default'=>''),
					'direction' => array('title'=>'0.更旧的，1.更新的', 'default'=>'0'),
					'id' => array('title'=>'请求分界的mail_id。（0.初始）', 'default'=>'0'),
				),
			),
			'getUnread'=>array(
				'name' => '获取未读邮件数量',
				'param'=> array(
				),
			),
			'setRead'=>array(
				'name' => '设置已读',
				'param'=> array(
					'type'	=> array('title'=>'0:默认，2:联盟；3:侦查；4:战斗；5:系统; 6.采集，7.打怪', 'default'=>'0'),
					'mailIds' => array('title'=>'mailId数组', 'default'=>''),
				),
			),
			'setLock'=>array(
				'name' => '设置锁定',
				'param'=> array(
					'lock' => array('title'=>'0.解锁，1.锁定', 'default'=>''),
					'mailIds' => array('title'=>'mailId数组', 'default'=>''),
				),
			),
			'delete'=>array(
				'name' => '删除邮件',
				'param'=> array(
					'type'	=> array('title'=>'0:默认，2:联盟；3:侦查；4:战斗；5:系统; 6.采集，7.打怪', 'default'=>'0'),
					'mailIds' => array('title'=>'mailId数组', 'default'=>''),
				),
			),
			'chat'=>array(
				'name' => '发送玩家邮件',
				'param'=> array(
					'type' => array('title'=>'1.单人；2.多人；3.联盟全体；4.单人（名字）', 'default'=>''),
					'toPlayer' => array('title'=>"（type=1时）填写对象playerIds数组
（type=2时）如果填写字符串，表示groupId
（type=2时）如果填写playerName数组，表示发送对象names，将创建新多人会话
（type=4时）填写对象对象玩家名字数组", 'default'=>''),
					'msg' => array('title'=>'邮件内容', 'default'=>''),
				),
			),
			'sendGatherMail'=>array(
				'name' => '发送集结邀请邮件',
				'param'=> array(
					'toPlayer' => array('title'=>'对象玩家id数组', 'default'=>''),
					'queueId' => array('title'=>'队伍编号', 'default'=>''),
				),
			),
			/*'groupChangePlayer'=>array(
				'name' => '组群修改成员(创建者使用)',
				'param'=> array(
					'groupId' => array('title'=>'群组号', 'default'=>''),
					'playerIds' => array('title'=>'成员数组', 'default'=>''),
				),
			),*/
			'groupAddPlayer'=>array(
				'name' => '增加组群成员(组员使用)',
				'param'=> array(
					'groupId' => array('title'=>'群组号', 'default'=>''),
					'playerIds' => array('title'=>'成员数组', 'default'=>''),
				),
			),
			'groupQuit'=>array(
				'name' => '退出组群',
				'param'=> array(
					'groupId' => array('title'=>'群组号', 'default'=>''),
				),
			),
			'getChat'=>array(
				'name' => '聊天记录',
				'param'=> array(
					'type' => array('title'=>'2:单人聊天；3:多人聊天', 'default'=>''),
					'connectId' => array('title'=>'playerId或组群id', 'default'=>''),
					'direction' => array('title'=>'0.更旧的，1.更新的', 'default'=>'0'),
					'id' => array('title'=>'请求分界的mail_id。（0.初始）', 'default'=>'0'),
				),
			),
			'fetchItem'=>array(
				'name' => '收取道具',
				'param'=> array(
					'mailIds' => array('title'=>'邮件id数组', 'default'=>''),
				),
			),
			'getGroupMember'=>array(
				'name' => '获取组成员',
				'param'=> array(
					'groupId' => array('title'=>'组群号', 'default'=>''),
				),
			),
			'getSharedMail'=>array(
				'name' => '获取战报邮件',
				'param'=> array(
					'id' => array('title'=>'邮件id', 'default'=>''),
				),
			),
		),
		'Map'=>array(
			'showBlock'=>array(
				'name' => '取地图区块',
				'param'=> array(
					'blockList' => array('title'=>'中央块数组', 'default'=>''),
				),
			),
			'showQueue'=>array(
				'name' => '取地图队列',
				'param'=> array(
					'blockList' => array('title'=>'中央块数组', 'default'=>''),
				),
			),
			'showBlockNQueue'=>array(
				'name' => 'showBlock和showQueue合成版',
				'param'=> array(
					'blockList' => array('title'=>'showBlock的参数', 'default'=>''),
					'queueList' => array('title'=>'showQueue的参数', 'default'=>''),
				),
			),
			'queueBattleRet'=>array(
				'name' => '队列战斗结果',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'gotoCollection'=>array(
				'name' => '出征采集',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
					'useMove' => array('title'=>'0.不使用体力；1.使用体力', 'default'=>''),
				),
			),
			'gotoAttackCity'=>array(
				'name' => '去攻城',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
					'useMove' => array('title'=>'0.不使用体力；1.使用体力', 'default'=>''),
				),
			),
			'gotoAttackNpc'=>array(
				'name' => '去打怪',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
					'useMove' => array('title'=>'0.不使用体力；1.使用体力', 'default'=>'0'),
					'quickMove' => array('title'=>'1.使用2秒往返（仅一次），0.不实用', 'default'=>'0'),
				),
			),
			'gotoFetchItem'=>array(
				'name' => '去拿东西',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
				),
			),
			'callbackStayQueue'=>array(
				'name' => '召回静止队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'callbackMoveQueue'=>array(
				'name' => '召回移动队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'acceQueue'=>array(
				'name' => '加速队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
					'itemId' => array('title'=>'加速道具id（21701：初级加速；21702：高级加速）', 'default'=>''),
				),
			),
			'startGather'=>array(
				'name' => '发起集结',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
					'time' => array('title'=>'集结时间：1.五分钟；2.10分钟；3.30分钟；4.60分钟', 'default'=>''),
				),
			),
			'cancelGather'=>array(
				'name' => '取消集结',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'kickGather'=>array(
				'name' => '踢出某个集结玩家',
				'param'=> array(
					'targetPlayerId' => array('title'=>'', 'default'=>''),
					'parentQueueId' => array('title'=>'', 'default'=>''),
				),
			),
			'gotoGather'=>array(
				'name' => '前往集结',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
					'queueId' => array('title'=>'', 'default'=>''),
					'useMove' => array('title'=>'0.不使用体力；1.使用体力', 'default'=>''),
				),
			),
			'addCoordinate'=>array(
				'name' => '收藏坐标',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'type' => array('title'=>'分类', 'default'=>''),
					'name' => array('title'=>'', 'default'=>''),
				),
			),
			'dropCoordinate'=>array(
				'name' => '删除坐标',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
				),
			),
			'gotoTown'=>array(
				'name' => '前往王战城寨',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
					'useMove' => array('title'=>'0.不使用体力；1.使用体力', 'default'=>''),
				),
			),
			'getQueueInfo'=>array(
				'name' => '获取队列信息',
				'param'=> array(
					'queueId' => array('title'=>'队伍id', 'default'=>''),
				),
			),
			'getGotoTime'=>array(
				'name' => '获取去往坐标时间',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					//'armyId' => array('title'=>'', 'default'=>''),
					'type' => array('title'=>'行军种类：1.采集，2.打怪，3.出征，4.侦查，5.搬运资源,6.集结', 'default'=>''),
				),
			),
			'findNpc'=>array(
				'name' => '找怪',
				'param'=> array(
					'blockId' => array('title'=>'', 'default'=>''),
					'level' => array('title'=>'自定义怪物level', 'default'=>''),
				),
			),
			'findItem'=>array(
				'name' => '搜寻物品',
				'param'=> array(
					'blockId' => array('title'=>'', 'default'=>''),
					'elementId' => array('title'=>'1901:和氏璧，2001:据点', 'default'=>''),
				),
			),
			'getHjNpc'=>array(
				'name' => '获取黄巾起义npc攻击队列',
			),
		),
		'player_help'=>[
			'viewHelpArmy'	=> [
				'name'	=> '查看来帮助我的所有援军',
			],
			'sendArmy'		=> [
				'name'	=> '选择盟友后增援',
				'param' => [
					'to_player_id' => ['title'=>'', 'default'=>''],
					'army_id'      => ['title'=>'', 'default'=>''],
				],
			],
			'sendHelp'		=> [
				'name'	=> '发送帮助请求',
				'param' => [
					'position' => ['title'=>'', 'default'=>''],
				],
			],
		],
		'mission'=>[
			'refreshDailyMission'	=> [
				'name' => '使用元宝刷新单个每日任务',
				'param' => [
					'current_id' => ['title'=>'当前每日任务的id', 'default'=>''],
				],
			],
			'getMissionReward'	=> [
				'name' => '领取任务奖励',
				'param' => [
					'current_id' => ['title'=>'当前每日任务的id', 'default'=>''],
				],
			],
			/*'updateMissionTest'	=> [
				'name' => '更新[主线|每日]任务',
				'param' => [
					'mission_type' => ['title'=>'任务类型', 'default'=>'1'],
					'mission_number' => ['title'=>'数量', 'default'=>'0'],
				],
			],*/
		],
		'award'=>[
			'doGetSignAward' => [
				'name' => '领取当日的每日签到奖励'
			],
			'doGetOnlineAward' => [
				'name' => '领取当日的每日在线奖励'
			],
			
		],
		'market'=>[
			'reload' => [
				'name' => '刷新集市'
			],
			'buy' => [
				'name' => '购买',
				'param' => [
					'id' => ['title'=>'购买序号', 'default'=>'1'],
				],
			],
		],
		'order'=>[
			'createOrder' => [
				'name' => '购买',
				'param' => [
					'id' => ['title'=>'序号', 'default'=>''],
					'aci' => ['title'=>'礼包序号', 'default'=>''],
				],
			],
			'getGiftList' => [
				'name' => '可购买礼包列表',
				'param' => [
					'channel' => ['title'=>'渠道', 'default'=>''],
				],
			],
		],
		'limit_match' => [
			'showLimitMatch' => [
				'name' => '查看限时比赛',
			],
			'rank' => [
				'name' => '排名',
				'param' => [
					'type' => ['title'=>'1.阶段排名；2.总排名', 'default'=>''],
				],
			],
			'activityList' => [
				'name' => '当前活动列表',
			],
			'historyTop' => [
				'name' => '获取历史最高分',
			],
		],
		'target' => [
			'getTargetAward' => [
				'name' => '领取新手目标奖励',
				'param' => [
					'current_id' => ['title'=>'表中的id', 'default'=>'']
				]
			]
		],
		'mill' => [
			'buyPosition' => [
				'name' => '解锁栏位',
				'param' => [
					'num' => ['title'=>'数量', 'default'=>'']
				]
			],
			'addItem' => [
				'name' => '增加材料',
				'param' => [
					'itemId' => ['title'=>'道具id', 'default'=>'']
				]
			],
			'delItem' => [
				'name' => '移除材料',
				'param' => [
					'num' => ['title'=>'材料顺序，1开始', 'default'=>'']
				]
			],
			'acceItem' => [
				'name' => '加速',
				'param' => [
					'itemId' => ['title'=>'道具id', 'default'=>'']
				]
			],
			'gain' => [
				'name' => '收取材料',
			],
		],
		'activity'=>[
			'getActivity'=> [
				'name' => '读取所有当前已开始的任务',
			],
			'growthBuy' => [
				'name' => '购买成长基金',
			],
			'growthGain' => [
				'name' => '成长基金领取',
				'param' => [
					'type' => ['title'=>'1-府衙等级，2-购买人数', 'default'=>''],
					'id' => ['title'=>'相关配置id', 'default'=>''],
				]
			],
			'charge' => [
				'name' => '累计充值活动数据',
			],
			'chargeReward' => [
				'name' => '累计充值活动领取',
				'param' => [
					'gem' => ['title'=>'充值档', 'default'=>''],
				]
			],
			'consume' => [
				'name' => '累计消耗活动数据',
			],
			'consumeReward' => [
				'name' => '累计消耗活动领取',
				'param' => [
					'gem' => ['title'=>'充值档', 'default'=>''],
				]
			],
			'wheel' => [
				'name' => '大转盘数据',
			],
			'wheelReward' => [
				'name' => '大转盘活动累计领取',
				'param' => [
					'counter' => ['title'=>'累计档', 'default'=>''],
				]
			],
			'wheelPlay' => [
				'name' => '大转盘活动',
				'param' => [
					'num' => ['title'=>'次数：1，10', 'default'=>''],
				]
			],
			'login' => [
				'name' => '累计登陆活动数据',
			],
			'loginReward' => [
				'name' => '累计登陆活动领取',
				'param' => [
					'days' => ['title'=>'天', 'default'=>''],
				]
			],
			'npcDrop' => [
				'name' => '累计充值活动数据',
			],
			'exchangeShow' => [
				'name' => '兑换活动',
			],
			'doExchange' => [
				'name' => '点击兑换',
				'param' => [
					'exchangeId' => ['title'=>'', 'default'=>''],
				]
			],
			'panicShow' => [
				'name' => '秒杀展示',
			],
			'doPanic' => [
				'name' => '秒杀',
				'param' => [
					'buyId' => ['title'=>'', 'default'=>''],
				]
			],
			'newbieLoginReward' => [
				'name' => '新人登陆活动领取',
				'param' => [
					'days' => ['title'=>'天', 'default'=>''],
				]
			],
			'newbieChargeReward' => [
				'name' => '新人累计充值活动领取',
				'param' => [
					'id' => ['title'=>'充值档id', 'default'=>''],
				]
			],
			'newbieConsumeReward' => [
				'name' => '新人累计消耗活动领取',
				'param' => [
					'id' => ['title'=>'充值档id', 'default'=>''],
				]
			],
			'newbiePayReward' => [
				'name' => '新人充值有礼',
			],
		],
		'pk' => [
			'pkPosition' => [
				'name' => '武将上阵',
				'param' => [
					'general_1' => ['title'=>'位置1武将', 'default'=>0],
					'general_2' => ['title'=>'位置2武将', 'default'=>0],
					'general_3' => ['title'=>'位置3武将', 'default'=>0],
				],
			],
			'pkMatch'         => ['name' => '武斗匹配',],
			'pkRankList'      => ['name' => '武斗排行榜',],
			'getPkList'       => ['name' => 'pk列表',],
			'getDailyAward'   => ['name' => '武斗每日领奖'],
			'getTimesBonus'   => ['name' => '武斗参与奖励'],
			'syncDuelRankId'  => ['name' => '同步prev_duel_rank_id'],
			'getPkResult'   => [
				'name' => '获取武斗结果，用于回放',
				'param' => [
					'id' => ['title'=>'pk表id','default'=>''],
				],
			],
            'getGuildPlayerGeneralInfo'   => [
                'name' => '获取盟友武将信息',
                'param' => [
                    'target_player_id' => ['title'=>'盟友player_id','default'=>''],
                ],
            ],
		],
		'Cross'=>array(
			'battleInfo'=>array(
				'name' => '跨服战场信息',
			),
			'basicInfo'=>array(
				'name' => '跨服基本信息',
			),
			'enterBattlefield'=>array(
				'name' => '进入战场(预设部队)',
				'param'=> array(
					'armyIds' => array('title'=>'armyId数组', 'default'=>''),
				),
			),
			/*'setUnit'=>array(
				'name' => '设置军团',
				'param'=> array(
					'position' => array('title'=>'军团号', 'default'=>''),
					'unit' => array('title'=>'单位（格式：{\"1\":[generalId1,soldierId1,soldierNum1],\"2\":[generalId2,soldierId2,soldierNum2],...}）', 'default'=>''),
				),
			),*/
			'setGeneral'=>array(
				'name' => '设置武将',
				'param'=> array(
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
					'unitPosition' => array('title'=>'武将位置号', 'default'=>''),
					'generalId' => array('title'=>'武将号', 'default'=>''),
				),
			),
			'setSoldier'=>array(
				'name' => '设置士兵',
				'param'=> array(
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
					'unitPosition' => array('title'=>'武将位置号', 'default'=>''),
					'soldierId' => array('title'=>'士兵id', 'default'=>''),
					'soldierNum' => array('title'=>'士兵数量', 'default'=>''),
				),
			),
			'fullfillSoldier'=>array(
				'name' => '快速补兵',
				'param'=> array(
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
				),
			),
			'showBlockNQueue'=>array(
				'name' => 'showArea和showQueue合成版',
				'param'=> array(
					'areaList' => array('title'=>'showArea的参数', 'default'=>''),
					'queueList' => array('title'=>'showQueue的参数', 'default'=>''),
				),
			),
			'showArea'=>array(
				'name' => '查看区块',
				'param'=> array(
					'areaList' => array('title'=>'区块数组', 'default'=>''),
				),
			),
			'showQueue'=>array(
				'name' => '取队列',
				'param'=> array(
					'blockList' => array('title'=>'中央块数组', 'default'=>''),
				),
			),
			'getGotoTime'=>array(
				'name' => '获取去往坐标时间',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					//'armyId' => array('title'=>'', 'default'=>''),
					'type' => array('title'=>'行军种类：1.采集，2.打怪，*3.出征，4.侦查，5.搬运资源,6.集结', 'default'=>''),
				),
			),
			'getQueueInfo'=>array(
				'name' => '获取队列信息',
				'param'=> array(
					'queueId' => array('title'=>'队伍id', 'default'=>''),
				),
			),
			'getGuildPosition'=>array(
				'name' => '获取公会成员位置',
			),
			'queueBattleRet'=>array(
				'name' => '队列战斗结果',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'acceQueue'=>array(
				'name' => '加速队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'callbackStayQueue'=>array(
				'name' => '召回静止队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'callbackMoveQueue'=>array(
				'name' => '召回移动队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'gogogo'=>array(
				'name' => '去目标',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
				),
			),
			'useCatapult'=>array(
				'name' => '控制投石车',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
				),
			),
			'useSkill'=>array(
				'name' => '主动技使用',
				'param'=> array(
					'generalId'=> array('title'=>'', 'default'=>''),
					'skillId' => array('title'=>'', 'default'=>''),
				),
			),
            'basicInfo' => [
                'name' => '获取参赛相关信息',
                'param' => []
            ],
            'crossArmyInfo' => [
                'name' => '获取跨服战军团',
                'param' => []
            ],
            'joinBattle' => [
                'name' => '报名参赛',
                'param' => []
            ],
            'commitBattleMemberList' => [
                'name' => '提交参赛成员列表',
                'param' => [
                    'List' => ['title'=>'[1000234]', 'default'=>'']
                ]
            ],
            'applyToJoinBattle' => [
                'name' => '申请加入跨服战',
                'param' => []
            ],
            'changeLocation'=>[
                'name' => '跨服战迁城',
                'param' => [
                    'areaId' => ['title'=>'迁往区域', 'default'=>'']
                ]
            ],
            'revive'=>[
                'name' => '跨服战复活',
                'param' => [
                    'areaId' => ['title'=>'复活区域', 'default'=>'']
                ]
            ],
            'getSpBuild'=>[
                'name' => '跨服战查看特殊建筑',
                'param' => []
            ],
            'getAllPlayerList'=>[
                'name' => '跨服战查看参战人员',
                'param' => []
            ],
            'spy' => [
                'name' => '侦查',
                'param' => [
                    'x' => ['title'=>'x', 'default'=>''],
                    'y' => ['title'=>'y', 'default'=>'']
                ]
            ],
            'rankList' => [
                'name' => '上一届杀敌排名',
                'param' => []
            ],
            'resultList' => [
                'name' => '上一届联盟对战结果',
                'param' => []
            ],

		),
		'login_server' => [
			'getServerList' => [
				'name' => '获取服务器列表',
				'param' => [
				]
			],
		],
		'City_Battle' => [
			'scienceDonate' => [
				'name' => '阵营捐献',
				'param' => [
					'scienceType'=> ['title'=>'科技类型', 'default'=>''],
					'btn'=> ['title'=>'按钮号1/2/3', 'default'=>''],
				]
			],
			'setSoldier'=>array(
				'name' => '设置士兵',
				'param'=> array(
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
					'unitPosition' => array('title'=>'武将位置号', 'default'=>''),
					'soldierId' => array('title'=>'士兵id', 'default'=>''),
				),
			),
			'fullfillSoldier'=>array(
				'name' => '补兵',
				'param'=> [
					'armyPosition' => array('title'=>'军团号', 'default'=>''),
				],
			),
			'battleInfo'=>array(
				'name' => '跨服战场信息',
			),
			/*'basicInfo'=>array(
				'name' => '跨服基本信息',
			),*/
			'enterBattlefield'=>array(
				'name' => '进入战场(预设部队)',
				'param'=> array(
				),
			),
			'buySoldier'=>array(
				'name' => '买兵',
				'param'=> array(
					'type' => array('title'=>'1.元宝，2.贡献', 'default'=>''),
					'num' => array('title'=>'组数，1表示1000', 'default'=>''),
				),
			),
			'showBlockNQueue'=>array(
				'name' => 'showArea和showQueue合成版',
				'param'=> array(
					'areaList' => array('title'=>'showArea的参数', 'default'=>''),
					'queueList' => array('title'=>'showQueue的参数', 'default'=>''),
				),
			),
			'showArea'=>array(
				'name' => '查看区块',
				'param'=> array(
					//'areaList' => array('title'=>'区块数组', 'default'=>''),
				),
			),
			'showQueue'=>array(
				'name' => '取队列',
				'param'=> array(
					'blockList' => array('title'=>'中央块数组', 'default'=>''),
				),
			),
			'getGotoTime'=>array(
				'name' => '获取去往坐标时间',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					//'armyId' => array('title'=>'', 'default'=>''),
					'type' => array('title'=>'行军种类：1.采集，2.打怪，*3.出征，4.侦查，5.搬运资源,6.集结', 'default'=>''),
				),
			),
			'getQueueInfo'=>array(
				'name' => '获取队列信息',
				'param'=> array(
					'queueId' => array('title'=>'队伍id', 'default'=>''),
				),
			),
			'getCampPosition'=>array(
				'name' => '获取阵营成员位置',
			),
			'queueBattleRet'=>array(
				'name' => '队列战斗结果',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'acceQueue'=>array(
				'name' => '加速队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'callbackStayQueue'=>array(
				'name' => '召回静止队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'callbackMoveQueue'=>array(
				'name' => '召回移动队列',
				'param'=> array(
					'queueId' => array('title'=>'队列id', 'default'=>''),
				),
			),
			'gogogo'=>array(
				'name' => '去目标',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
					'armyId' => array('title'=>'', 'default'=>''),
				),
			),
			'useCatapult'=>array(
				'name' => '控制投石车',
				'param'=> array(
					'x' => array('title'=>'', 'default'=>''),
					'y' => array('title'=>'', 'default'=>''),
				),
			),
			'useSkill'=>array(
				'name' => '主动技使用',
				'param'=> array(
					'generalId'=> array('title'=>'', 'default'=>''),
					'skillId' => array('title'=>'', 'default'=>''),
				),
			),
            'getSignInfo'=>array(
                'name' => '报名信息',
                'param'=> array(
                    'cityId'=> array('title'=>'城市id', 'default'=>''),
                    'campId' => array('title'=>'阵营id', 'default'=>''),
                ),
            ),
            'siegeChangeLocation'=>[
                'name' => '城门战迁城',
                'param' => [
                    'area' => ['title'=>'迁往区域', 'default'=>'']
                ]
            ],
            'meleeChangeLocation'=>[
                'name' => '内城战迁城',
                'param' => [
                    'section' => ['title'=>'迁往区域', 'default'=>'']
                ]
            ],
            'meleeRevive'=>[
                'name' => '内城战复活',
                'param' => [
                ]
            ],
			'siegeRevive'=>[
                'name' => '城门战复活',
                'param' => [
                ]
            ],
            'signCityBattle'=>[
                'name'=>"报名",
                'param'=>[
                    'cityId'=>['title'=>"城市id", 'default'=>''],
                    'signType'=>['title'=>"报名类型", 'default'=>'']
                ]
            ],
            'spy' => [
                'name' => '侦查',
                'param' => [
                    'x' => ['title'=>'x', 'default'=>''],
                    'y' => ['title'=>'y', 'default'=>'']
                ]
            ],
            /*'rankList' => [
                'name' => '上一届杀敌排名',
                'param' => []
            ],
            'resultList' => [
                'name' => '上一届联盟对战结果',
                'param' => []
            ],*/
			'occupyInfo'=>array(
				'name' => '占领信息',
				'param'=> [],
			),
			'output'=>array(
				'name' => '城池产出',
				'param'=> [
					'city_id'=>['title'=>'city_id', 'default'=>''],
					'is_get_time'=>['title'=>'is_get_time', 'default'=>'0'],
				]
			),
			'getCityBattleRank'=>array(
				'name' => '勇士羽林军排名',
				'param'=> [
				]
			),
		],
		'Guild_Mission' => [
			'showGuildMission' => [
				'name' => '展示联盟任务',
				'param' => [
				]
			],
		],
	),
);