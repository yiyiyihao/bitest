<?php
namespace app\service\service;

/**
 * 后台授权树
 */
class Purview
{
    /**
     * 获取用户组授权树
     */
    public function getGroupPurview($groupPurview = FALSE)
    {
        $menuArr = [
            [
                'name' => '数据统计',
                'list' => [
                    [
                        'name' => '首页(数据概况)',
                        'type' => 'single',
                        'value' => 'v1.data.ajaxdata_homedata',
                        'sub_menu' => [
                            "name" => "数据概况",
                            "index" => "1-1",
                            "icon" => "icongaikuang-icon-weixuanzhong",
                            "path" => "/dataStatistics/profileData"
                        ]
                    ],
                    [
                        'name' => '门店分析(门店分析)',
                        'type' => 'single',
                        'value' => 'v1.data.ajaxdata_storedata',
                        'sub_menu' => [
                            "name" => "门店分析",
                            "index" => "1-2",
                            "icon" => "iconmendianfenxi-icon-weixuanzhong",
                            "path" => "/dataStatistics/storesAnalysis"
                        ]
                    ],
                    [
                        'name' => '查看访客详情',
                        'type' => 'single',
                        'value' => 'v1.data.ajaxdata_getvisitordetail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '查看所有访客',
                        'type' => 'single',
                        'value' => 'v1.data.ajaxdata_getusers',
                        'sub_menu' => []
                    ],
//                     [
//                         'name' => '热力图',
//                         'type' => 'single',
//                         'value' => 'v1.data.ajaxdata_getmaplist',
//                         'sub_menu' => []
//                     ],
//                     [
//                         'name' => '轨迹图',
//                         'type' => 'single',
//                         'value' => 'v1.data.ajaxdata_getorbitlist',
//                         'sub_menu' => []
//                     ]
                ],
                'value' => 'v1.data.ajaxdata_*',
                'menu' => [
                    "title" => "数据统计",
                    "index" => "1"
                ]
            ],
            [
                'name' => '系统管理',
                'list' => [
                    [
                        'name' => '角色列表(角色管理)',
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_grouplist',
                        'sub_menu' => [
                            "name" => "角色管理",
                            "index" => "2-1",
                            "icon" => "iconjiaoseguanli-icon-weixuanzhong",
                            "path" => "/systemManagement/roleManagement"
                        ]
                    ],
                    [
                        'name' => '添加角色',
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_add',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '角色信息',
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_info',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '编辑角色',
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_edit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除角色',
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_del',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '角色权限列表',
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_purviewlist',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '角色授权',
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_purview',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.staffer.ugroup_*',
                'menu' => [
                    "title" => "系统管理",
                    "index" => "2"
                ]
            ],
            [
                'name' => '门店管理',
                'list' => [
                    [
                        'name' => '门店列表(门店管理)',
                        'type' => 'single',
                        'value' => 'v1.store.store_getstorelist',
                        'sub_menu' => [
                            "name" => "门店管理",
                            "index" => "3-1",
                            "icon" => "iconmendianguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/storeManagement"
                        ]
                    ],
                    [
                        'name' => '添加门店',
                        'type' => 'single',
                        'value' => 'v1.store.store_addstore',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '编辑门店',
                        'type' => 'single',
                        'value' => 'v1.store.store_editstore',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除门店',
                        'type' => 'single',
                        'value' => 'v1.store.store_delstore',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '门店信息',
                        'type' => 'single',
                        'value' => 'v1.store.store_storeInfo',
                        'sub_menu' => []
                    ],
//                     [
//                         'name' => '配置管理员',
//                         'type' => 'single',
//                         'value' => 'v1.store.store_admin',
//                         'sub_menu' => []
//                     ],
//                     [
//                         'name' => '删除管理员',
//                         'type' => 'single',
//                         'value' => 'v1.store.store_deladmin',
//                         'sub_menu' => []
//                     ],
                ],
                'value' => 'v1.store.store_*',
                'menu' => [
                    "title" => "门店管理",
                    "index" => "3"
                ]
            ],
            [
                'name' => '区域管理',
                'list' => [
                    [
                        'name' => '门店区域列表(区域管理)',
                        'type' => 'single',
                        'value' => 'v1.store.block_getblocklist',
                        'sub_menu' => [
                            "name" => "区域管理",
                            "index" => "3-2",
                            "icon" => "iconquyuguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/districtManagement"
                        ]
                    ],
                    [
                        'name' => '区域列表（设备编辑时用，不分页）',
                        'type' => 'single',
                        'value' => 'v1.store.block_blocklist',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '添加区域',
                        'type' => 'single',
                        'value' => 'v1.store.block_addblock',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '编辑区域',
                        'type' => 'single',
                        'value' => 'v1.store.block_editblock',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除区域',
                        'type' => 'single',
                        'value' => 'v1.store.block_delblock',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.store.block_*',
                'menu' => [
                    "title" => "门店管理",
                    "index" => "3"
                ]
            ],
            [
                'name' => '设备管理',
                'list' => [
                    [
                        'name' => '设备列表(设备管理)',
                        'type' => 'single',
                        'value' => 'v1.device.device_devicelist',
                        'sub_menu' => [
                            "name" => "设备管理",
                            "index" => "3-3",
                            "icon" => "iconshebeiguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/deviceManagement"
                        ]
                    ],
                    [
                        'name' => '添加设备',
                        'type' => 'single',
                        'value' => 'v1.device.device_deviceadd',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除设备',
                        'type' => 'single',
                        'value' => 'v1.device.device_devicedel',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '编辑设备',
                        'type' => 'single',
                        'value' => 'v1.device.device_deviceedit',
                        'sub_menu' => []
                    ],
//                     [
//                         'name' => '授权设备',
//                         'type' => 'single',
//                         'value' => 'v1.device.device_authorize',
//                         'sub_menu' => []
//                     ],
                    [
                        'name' => '设备全局图片',
                        'type' => 'single',
                        'value' => 'v1.device.device_fulls',
                        'sub_menu' => []
                    ],
//                     [
//                         'name' => '视频播放',
//                         'type' => 'single',
//                         'value' => 'v1.device.device_play',
//                         'sub_menu' => []
//                     ],
                ],
                'value' => 'v1.device.device_*',
                'menu' => [
                    "title" => "门店管理",
                    "index" => "3"
                ]
            ],
            [
                'name' => '员工管理',
                'list' => [
                    [
                        'name' => '员工列表(员工管理)',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_stafferlist',
                        'sub_menu' => [
                            "name" => "员工管理",
                            "index" => "3-4",
                            "icon" => "iconyuangongguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/staffManagement"
                        ]
                    ],
                    [
                        'name' => '添加员工',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_staffadd',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '编辑员工',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_stafferedit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除员工',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_stafferdel',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '修改员工角色',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_setgroup',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '员工信息',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_userinfo',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '修改当前登入用户',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_profile',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.staffer.staffer_*',
                'menu' => [
                    "title" => "门店管理",
                    "index" => "3"
                ]
            ],
            [
                'name' => '全部用户',
                'list' => [
                    [
                        'name' => '全部用户(全部用户)',
                        'type' => 'single',
                        'value' => 'v1.member.user_list',
                        'sub_menu' => [
                            "name" => "全部用户",
                            "index" => "4-1",
                            "icon" => "iconyingyongguanli-icon-weixuanzhongcopy1",
                            "path" => "/userManagement/allUserManagement"
                        ]
                    ],
                    [
                        'name' => '用户详情',
                        'type' => 'single',
                        'value' => 'v1.member.user_detail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '设置角色',
                        'type' => 'single',
                        'value' => 'v1.member.user_staffset',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除用户',
                        'type' => 'single',
                        'value' => 'v1.member.user_del',
                        'sub_menu' => []
                    ],
                ],
                'value' => 'v1.member.user_*',
                'menu' => [
                    "title" => "用户管理",
                    "index" => "4"
                ]
            ],
            [
                'name' => '会员管理',
                'list' => [
                    [
                        'name' => '会员列表(会员管理)',
                        'type' => 'single',
                        'value' => 'v1.member.member_memberlist',
                        'sub_menu' => [
                            "name" => "会员管理",
                            "index" => "4-2",
                            "icon" => "iconhuiyuanguanli-icon-weixuanzhong",
                            "path" => "/userManagement/memberManagement"
                        ]
                    ],
                    [
                        'name' => '编辑会员',
                        'type' => 'single',
                        'value' => 'v1.member.member_edit',
                        'sub_menu' => []
                    ],
//                     [
//                         'name' => '设置角色',
//                         'type' => 'single',
//                         'value' => 'v1.staffer.staffer_staffadd',
//                         'sub_menu' => []
//                     ],
                    [
                        'name' => '删除会员',
                        'type' => 'single',
                        'value' => 'v1.member.member_del',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '获取会员信息',
                        'type' => 'single',
                        'value' => 'v1.member.member_info',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '获取会员信息和访问详情/获取访问详情',
                        'type' => 'single',
                        'value' => 'v1.member.member_detail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '标签关联用户',
                        'type' => 'single',
                        'value' => 'v1.member.member_userByLabel',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.member.member_*',
                'menu' => [
                    "title" => "用户管理",
                    "index" => "4"
                ]
            ],
            [
                'name' => '标签管理',
                'list' => [
                    [
                        'name' => '标签列表(标签管理)',
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_labellist',
                        'sub_menu' => [
                            "name" => "标签管理",
                            "index" => "4-3",
                            "icon" => "iconyingyongguanli-icon-weixuanzhong",
                            "path" => "/userManagement/labelManagement"
                        ]
                    ],
                    [
                        'name' => '添加标签',
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_add',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '编辑标签',
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_edit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除标签',
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_del',
                        'sub_menu' => []
                    ],
                ],
                'value' => 'v1.label.userlabel_*',
                'menu' => [
                    "title" => "用户管理",
                    "index" => "4"
                ]
            ],
            [
                'name' => '人脸图库',
                'list' => [
                    [
                        'name' => '人脸图库列表(人脸图库)',
                        'type' => 'single',
                        'value' => 'v1.person.person_personlist',
                        'sub_menu' => [
                            "name" => "人脸图库",
                            "index" => "4-4",
                            "icon" => "iconrenliantuku-icon-weixuanzhong",
                            "path" => "/userManagement/faceOfGallery"
                        ]
                    ],
                    [
                        'name' => '个体用户人脸列表',
                        'type' => 'single',
                        'value' => 'v1.person.person_faces',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '个体用户去重',
                        'type' => 'single',
                        'value' => 'v1.person.person_choose',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '个体用户合并',
                        'type' => 'single',
                        'value' => 'v1.person.person_distinct',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '个体用户合并确认',
                        'type' => 'single',
                        'value' => 'v1.person.person_distinct_comfirm',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '个体用户详情',
                        'type' => 'single',
                        'value' => 'v1.person.person_detail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '删除人脸',
                        'type' => 'single',
                        'value' => 'v1.person.person_facedel',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '添加人脸',
                        'type' => 'single',
                        'value' => 'v1.person.person_faceadd',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '彻底删除人脸',
                        'type' => 'single',
                        'value' => 'v1.person.person_facetokendel',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.person.person_*',
                'menu' => [
                    "title" => "用户管理",
                    "index" => "4"
                ]
            ],
            /* ['name' => '会员等级',
                'list' => [
                    ['name' => '会员等级列表','type' => 'single','value' => 'v1.member.ugrade_gradelist','sub_menu' =>[]],
                    ['name' => '添加会员等级','type' => 'single','value' => 'v1.member.ugrade_add','sub_menu' =>[]],
                    ['name' => '编辑会员等级','type' => 'single','value' => 'v1.member.ugrade_edit','sub_menu' =>[]],
                    ['name' => '删除会员等级','type' => 'single','value' => 'v1.member.ugrade_del','sub_menu' =>[]],
                    ['name' => '会员等级信息','type' => 'single','value' => 'v1.member.ugrade_info','sub_menu' =>[]],
                ],
                'value' => 'v1.member.ugrade_*',
                'menu'  => [
                    "title"=> "数据统计",
                    "index"=> ""
                            ],
            ],

            ['name' => '商品',
                'list' => [
                    ['name' => '商品列表','type' => 'single','value' => 'v1.goods.goods_goodslist','sub_menu' =>[]],
                    ['name' => '添加商品','type' => 'single','value' => 'v1.goods.goods_goodsadd','sub_menu' =>[]],
                    ['name' => '商品信息','type' => 'single','value' => 'v1.goods.goods_goodsInfo','sub_menu' =>[]],
                    ['name' => '编辑商品','type' => 'single','value' => 'v1.goods.goods_goodsedit','sub_menu' =>[]],
                    ['name' => '删除商品','type' => 'single','value' => 'v1.goods.goods_goodsdel','sub_menu' =>[]],
                ],
                'value' => 'v1.goods.goods_*',
                'menu'  => [
                    "title"=> "数据统计",
                    "index"=> ""
                            ],
            ],
            ['name' => '商品属性',
                'list' => [
                    ['name' => '商品属性','type' => 'single','value' => 'v1.goods.gspec_spec','sub_menu' =>[]],
                    ['name' => '商品属性列表','type' => 'single','value' => 'v1.goods.gspec_speclist','sub_menu' =>[]],
                    ['name' => '添加商品属性','type' => 'single','value' => 'v1.goods.gspec_add','sub_menu' =>[]],
                    ['name' => '编辑商品属性','type' => 'single','value' => 'v1.goods.gspec_edit','sub_menu' =>[]],
                    ['name' => '删除商品属性','type' => 'single','value' => 'v1.goods.gspec_del','sub_menu' =>[]],
                    ['name' => '商品属性信息','type' => 'single','value' => 'v1.goods.gspec_specinfo','sub_menu' =>[]],
                ],
                'value' => 'v1.goods.gspec_*',
                'menu'  => [
                    "title"=> "数据统计",
                    "index"=> ""
                            ],
            ],
            ['name' => '商品分类',
                'list' => [
                    ['name' => '商品分类列表','type' => 'single','value' => 'v1.goods.category_categorylist','sub_menu' =>[]],
                    ['name' => '添加商品分类','type' => 'single','value' => 'v1.goods.category_add','sub_menu' =>[]],
                    ['name' => '编辑商品分类','type' => 'single','value' => 'v1.goods.category_edit','sub_menu' =>[]],
                    ['name' => '删除商品分类','type' => 'single','value' => 'v1.goods.category_del','sub_menu' =>[]],
                    ['name' => '商品分类信息','type' => 'single','value' => 'v1.goods.category_categoryinfo','sub_menu' =>[]],
                ],
                'value' => 'v1.goods.category_*',
                'menu'  => [
                    "title"=> "数据统计",
                    "index"=> ""
                            ],
            ],
            ['name' => '订单',
                'list' => [
                    ['name' => '订单列表','type' => 'single','value' => 'v1.order.order_orderlist','sub_menu' =>[]],
                    ['name' => '订单信息','type' => 'single','value' => 'v1.order.order_orderinfo','sub_menu' =>[]],
                    ['name' => '修改订单金额','type' => 'single','value' => 'v1.order.order_updatePrice','sub_menu' =>[]],
                    ['name' => '取消订单','type' => 'single','value' => 'v1.order.order_cancel','sub_menu' =>[]],
                    ['name' => '订单确认收款','type' => 'single','value' => 'v1.order.order_pay','sub_menu' =>[]],
                    ['name' => '订单商品发货','type' => 'single','value' => 'v1.order.order_delivery','sub_menu' =>[]],
                    ['name' => '订单确认完成','type' => 'single','value' => 'v1.order.order_finish','sub_menu' =>[]],
                    ['name' => '查看商品发货物流','type' => 'single','value' => 'v1.order.order_deliveryLogs','sub_menu' =>[]],
                ],
                'value' => 'v1.order.order_*',
                'menu'  => [
                    "title"=> "数据统计",
                    "index"=> ""
                            ],
            ],
            ['name' => '支付方式',
                'list' => [
                    ['name' => '支付方式列表','type' => 'single','value' => 'v1.payment.payment_paymentlist','sub_menu' =>[]],
                    ['name' => '支付方式配置','type' => 'single','value' => 'v1.payment.payment_config','sub_menu' =>[]],
                    ['name' => '支付方式信息','type' => 'single','value' => 'v1.payment.payment_info','sub_menu' =>[]],
                    ['name' => '删除支付方式','type' => 'single','value' => 'v1.payment.payment_del','sub_menu' =>[]],
                ],
                'value' => 'v1.payment.payment_*',
                'menu'  => [
                    "title"=> "数据统计",
                    "index"=> ""
                            ],
            ], 
            [
                'name' => '门店二维码',
                'list' => [
                    [
                        'name' => '获取二维码',
                        'type' => 'single',
                        'value' => 'v1.store.qrcode_getqrcode',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.store.qrcode_*',
                'menu' => [
                    "title" => "门店二维码",
                    "index" => ""
                ]
            ],
            ['name' => '子门店',
                'list' => [
                    ['name' => '子门店列表','type' => 'single','value' => 'v1.store.substore_substorelist','sub_menu' =>[]],
                    ['name' => '添加子门店','type' => 'single','value' => 'v1.store.substore_addsubstore','sub_menu' =>[]],
                    ['name' => '判断是否有添加子门店权限','type' => 'single','value' => 'v1.store.substore_checkadd','sub_menu' =>[]],
                ],
                'value' => 'v1.store.subStore_*',
                'menu'  => [
                    "title"=> "数据统计",
                    "index"=> ""
                            ],
            ],*/
        ];
        return $menuArr;
    }

    // 超级管理员的导航菜单
    public function menu()
    {
        return json_decode('[
    {
        "title": "数据统计",
        "index": "1",
        "menuItemList": [
            {
                "name": "数据概况",
                "index": "1-1",
                "icon": "icongaikuang-icon-weixuanzhong",
                "path": "/dataStatistics/profileData"
            },
            {
                "name": "门店分析",
                "index": "1-2",
                "icon": "iconmendianfenxi-icon-weixuanzhong",
                "path": "/dataStatistics/storesAnalysis"
            }
        ]
    },
    {
        "title": "系统管理",
        "index": "2",
        "menuItemList": [
            {
                "name": "角色管理",
                "index": "2-1",
                "icon": "iconjiaoseguanli-icon-weixuanzhong",
                "path": "/systemManagement/roleManagement"
            }
        ]
    },
    {
        "title": "门店管理",
        "index": "3",
        "menuItemList": [
            {
                "name": "门店管理",
                "index": "3-1",
                "icon": "iconmendianguanli-icon-weixuanzhong",
                "path": "/storeManagement/storeManagement"
            },
            {
                "name": "区域管理",
                "index": "3-2",
                "icon": "iconquyuguanli-icon-weixuanzhong",
                "path": "/storeManagement/districtManagement"
            },
            {
                "name": "设备管理",
                "index": "3-3",
                "icon": "iconshebeiguanli-icon-weixuanzhong",
                "path": "/storeManagement/deviceManagement"
            },
            {
                "name": "员工管理",
                "index": "3-4",
                "icon": "iconyuangongguanli-icon-weixuanzhong",
                "path": "/storeManagement/staffManagement"
            }
        ]
    },
    {
        "title": "用户管理",
        "index": "4",
        "menuItemList": [
            {
                "name": "全部用户",
                "index": "4-1",
                "icon": "iconyingyongguanli-icon-weixuanzhongcopy1",
                "path": "/userManagement/allUserManagement"
            },
            {
                "name": "会员管理",
                "index": "4-2",
                "icon": "iconhuiyuanguanli-icon-weixuanzhong",
                "path": "/userManagement/memberManagement"
            },
            {
                "name": "标签管理",
                "index": "4-3",
                "icon": "iconyingyongguanli-icon-weixuanzhong",
                "path": "/userManagement/labelManagement"
            },
            {
                "name": "人脸图库",
                "index": "4-4",
                "icon": "iconrenliantuku-icon-weixuanzhong",
                "path": "/userManagement/faceOfGallery"
            }
        ]
    }
]', true);
    }
}