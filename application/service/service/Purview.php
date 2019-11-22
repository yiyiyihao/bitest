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
                'name' => lang('数据统计'),
                'list' => [
                    [
                        'name' => lang('首页(数据概况)'),
                        'type' => 'single',
                        'value' => 'v1.data.ajaxdata_homedata',
                        'sub_menu' => [
                            "name" => lang("数据概况"),
                            "index" => "1-1",
                            "icon" => "icongaikuang-icon-weixuanzhong",
                            "path" => "/dataStatistics/profileData"
                        ]
                    ],
                    [
                        'name' => lang('客流分析(客流分析)'),
                        'type' => 'single',
                        'value' => 'v1.data.ajaxdata_storedata',
                        'sub_menu' => [
                            "name" => lang("客流分析"),
                            "index" => "1-2",
                            "icon" => "iconmendianfenxi-icon-weixuanzhong",
                            "path" => "/dataStatistics/storesAnalysis"
                        ]
                    ],
                    [
                        'name' => lang('查看访客详情'),
                        'type' => 'single',
                        'value' => 'v1.data.ajaxdata_getvisitordetail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('查看所有访客'),
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
                    "title" => lang("数据统计"),
                    "index" => "1"
                ]
            ],
            [
                'name' => lang('系统管理'),
                'list' => [
                    [
                        'name' => lang('角色列表(角色管理)'),
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_grouplist',
                        'sub_menu' => [
                            "name" => lang("角色管理"),
                            "index" => "2-1",
                            "icon" => "iconjiaoseguanli-icon-weixuanzhong",
                            "path" => "/systemManagement/roleManagement"
                        ]
                    ],
                    [
                        'name' => lang('添加角色'),
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_add',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('角色信息'),
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_info',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('编辑角色'),
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_edit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除角色'),
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_del',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('角色权限列表'),
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_purviewlist',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('角色授权'),
                        'type' => 'single',
                        'value' => 'v1.staffer.ugroup_purview',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.staffer.ugroup_*',
                'menu' => [
                    "title" => lang("系统管理"),
                    "index" => "2"
                ]
            ],
            [
                'name' => lang('数据设置'),
                'list' => [
                    [
                        'name' => lang('数据设置'),
                        'type' => 'single',
                        'value' => 'v1.system.setting_set',
                        'sub_menu' => [
                            "name"=> lang("数据设置"),
                            "index"=> "2-2",
                            "icon"=> "icondatasettings",
                            "path"=> "/systemManagement/dataSetting"
                        ]
                    ],
                    [
                        'name' => lang('获取系统设置信息'),
                        'type' => 'single',
                        'value' => 'v1.system.setting_get',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('获取图片置信度'),
                        'type' => 'single',
                        'value' => 'v1.system.setting_get_confidence',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('编辑系统标签'),
                        'type' => 'single',
                        'value' => 'v1.system.setting_life_cycle',
                        'sub_menu' => []
                    ],
                ],
                'value' => 'v1.system.setting_*',
                'menu' => [
                    "title" => lang("系统管理"),
                    "index" => "2"
                ]
            ],
            [
                'name' => lang('门店管理'),
                'list' => [
                    [
                        'name' => lang('门店列表(门店管理)'),
                        'type' => 'single',
                        'value' => 'v1.store.store_getstorelist',
                        'sub_menu' => [
                            "name" => lang("门店管理"),
                            "index" => "3-1",
                            "icon" => "iconmendianguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/storeManagement"
                        ]
                    ],
                    [
                        'name' => lang('添加门店'),
                        'type' => 'single',
                        'value' => 'v1.store.store_addstore',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('编辑门店'),
                        'type' => 'single',
                        'value' => 'v1.store.store_editstore',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除门店'),
                        'type' => 'single',
                        'value' => 'v1.store.store_delstore',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('门店信息'),
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
                    "title" => lang("门店管理"),
                    "index" => "3"
                ]
            ],
            [
                'name' => lang('区域管理'),
                'list' => [
                    [
                        'name' => lang('门店区域列表(区域管理)'),
                        'type' => 'single',
                        'value' => 'v1.store.block_getblocklist',
                        'sub_menu' => [
                            "name" => lang("区域管理"),
                            "index" => "3-2",
                            "icon" => "iconquyuguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/districtManagement"
                        ]
                    ],
                    [
                        'name' => lang('区域列表(设备编辑时用，不分页)'),
                        'type' => 'single',
                        'value' => 'v1.store.block_blocklist',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('添加区域'),
                        'type' => 'single',
                        'value' => 'v1.store.block_addblock',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('编辑区域'),
                        'type' => 'single',
                        'value' => 'v1.store.block_editblock',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除区域'),
                        'type' => 'single',
                        'value' => 'v1.store.block_delblock',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.store.block_*',
                'menu' => [
                    "title" => lang("门店管理"),
                    "index" => "3"
                ]
            ],
            [
                'name' => lang('设备管理'),
                'list' => [
                    [
                        'name' => lang('设备列表(设备管理)'),
                        'type' => 'single',
                        'value' => 'v1.device.device_devicelist',
                        'sub_menu' => [
                            "name" => lang("设备管理"),
                            "index" => "3-3",
                            "icon" => "iconshebeiguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/deviceManagement"
                        ]
                    ],
                    [
                        'name' => lang('添加设备'),
                        'type' => 'single',
                        'value' => 'v1.device.device_deviceadd',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除设备'),
                        'type' => 'single',
                        'value' => 'v1.device.device_devicedel',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('编辑设备'),
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
                        'name' => lang('设备全局图片'),
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
                    "title" => lang("门店管理"),
                    "index" => "3"
                ]
            ],
            [
                'name' => lang('员工管理'),
                'list' => [
                    [
                        'name' => lang('员工列表(员工管理)'),
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_stafferlist',
                        'sub_menu' => [
                            "name" => lang("员工管理"),
                            "index" => "3-4",
                            "icon" => "iconyuangongguanli-icon-weixuanzhong",
                            "path" => "/storeManagement/staffManagement"
                        ]
                    ],
                    [
                        'name' => lang('添加员工'),
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_staffadd',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('编辑员工'),
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_stafferedit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除员工'),
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_stafferdel',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('修改员工角色'),
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_setgroup',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('员工信息'),
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_userinfo',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('修改当前登入用户'),
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_profile',
                        'sub_menu' => []
                    ],
                    [
                        'name' => '新增员工、会员',
                        'type' => 'single',
                        'value' => 'v1.staffer.staffer_stafferAdd',
                        'sub_menu' => []
                    ],
                ],
                'value' => 'v1.staffer.staffer_*',
                'menu' => [
                    "title" => lang("门店管理"),
                    "index" => "3"
                ]
            ],
            [
                'name' => lang('全部用户'),
                'list' => [
                    [
                        'name' => lang('全部用户(全部用户)'),
                        'type' => 'single',
                        'value' => 'v1.member.user_list',
                        'sub_menu' => [
                            "name" => lang("全部用户"),
                            "index" => "4-1",
                            "icon" => "iconyingyongguanli-icon-weixuanzhongcopy1",
                            "path" => "/userManagement/allUserManagement"
                        ]
                    ],
                    [
                        'name' => lang('用户详情'),
                        'type' => 'single',
                        'value' => 'v1.member.user_detail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('设置角色'),
                        'type' => 'single',
                        'value' => 'v1.member.user_staffset',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除用户'),
                        'type' => 'single',
                        'value' => 'v1.member.user_del',
                        'sub_menu' => []
                    ],
                ],
                'value' => 'v1.member.user_*',
                'menu' => [
                    "title" => lang("用户管理"),
                    "index" => "4"
                ]
            ],
            [
                'name' => lang('会员管理'),
                'list' => [
                    [
                        'name' => lang('会员列表(会员管理)'),
                        'type' => 'single',
                        'value' => 'v1.member.member_memberlist',
                        'sub_menu' => [
                            "name" => lang("会员管理"),
                            "index" => "4-2",
                            "icon" => "iconhuiyuanguanli-icon-weixuanzhong",
                            "path" => "/userManagement/memberManagement"
                        ]
                    ],
                    [
                        'name' => lang('编辑会员'),
                        'type' => 'single',
                        'value' => 'v1.member.member_edit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除会员'),
                        'type' => 'single',
                        'value' => 'v1.member.member_del',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('获取会员信息'),
                        'type' => 'single',
                        'value' => 'v1.member.member_info',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('获取会员信息和访问详情/获取访问详情'),
                        'type' => 'single',
                        'value' => 'v1.member.member_detail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('标签关联用户'),
                        'type' => 'single',
                        'value' => 'v1.member.member_userByLabel',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.member.member_*',
                'menu' => [
                    "title" => lang("用户管理"),
                    "index" => "4"
                ]
            ],
            [
                'name' => lang('全部用户'),
                'list' => [
                    [
                        'name' => lang('会员等级列表'),
                        'type' => 'single',
                        'value' => 'v1.member.ugrade_gradelist',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('会员等级添加'),
                        'type' => 'single',
                        'value' => 'v1.member.ugrade_add',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('会员等级编辑'),
                        'type' => 'single',
                        'value' => 'v1.member.ugrade_edit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('会员等级信息'),
                        'type' => 'single',
                        'value' => 'v1.member.ugrade_info',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('会员等级删除'),
                        'type' => 'single',
                        'value' => 'v1.member.ugrade_del',
                        'sub_menu' => []
                    ],
                ],
                'value' => 'v1.member.ugrade_*',
                'menu' => [
                    "title" => lang("会员等级"),
                    "index" => "4"
                ]
            ],
            [
                'name' => lang('标签管理'),
                'list' => [
                    [
                        'name' => lang('标签列表(标签管理)'),
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_labellist',
                        'sub_menu' => [
                            "name" => lang("标签管理"),
                            "index" => "4-3",
                            "icon" => "iconyingyongguanli-icon-weixuanzhong",
                            "path" => "/userManagement/labelManagement"
                        ]
                    ],
                    [
                        'name' => lang('添加标签'),
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_add',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('编辑标签'),
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_edit',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除标签'),
                        'type' => 'single',
                        'value' => 'v1.label.userlabel_del',
                        'sub_menu' => []
                    ],
                ],
                'value' => 'v1.label.userlabel_*',
                'menu' => [
                    "title" => lang("用户管理"),
                    "index" => "4"
                ]
            ],
            [
                'name' => lang('人脸图库'),
                'list' => [
                    [
                        'name' => lang('人脸图库列表(人脸图库)'),
                        'type' => 'single',
                        'value' => 'v1.person.person_personlist',
                        'sub_menu' => [
                            "name" => lang("人脸图库"),
                            "index" => "4-4",
                            "icon" => "iconrenliantuku-icon-weixuanzhong",
                            "path" => "/userManagement/faceOfGallery"
                        ]
                    ],
                    [
                        'name' => lang('个体用户人脸列表'),
                        'type' => 'single',
                        'value' => 'v1.person.person_faces',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('个体用户去重'),
                        'type' => 'single',
                        'value' => 'v1.person.person_choose',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('个体用户合并'),
                        'type' => 'single',
                        'value' => 'v1.person.person_distinct',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('个体用户合并确认'),
                        'type' => 'single',
                        'value' => 'v1.person.person_distinct_comfirm',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('个体用户详情'),
                        'type' => 'single',
                        'value' => 'v1.person.person_detail',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('删除人脸'),
                        'type' => 'single',
                        'value' => 'v1.person.person_facedel',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('添加人脸'),
                        'type' => 'single',
                        'value' => 'v1.person.person_faceadd',
                        'sub_menu' => []
                    ],
                    [
                        'name' => lang('彻底删除人脸'),
                        'type' => 'single',
                        'value' => 'v1.person.person_facetokendel',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.person.person_*',
                'menu' => [
                    "title" => lang("用户管理"),
                    "index" => "4"
                ]
            ],

            [
                'name' => lang('上传图片'),
                'list' => [
                    [
                        'name' => lang('上传图片'),
                        'type' => 'single',
                        'value' => 'v1.upload.upload_uploadImageSource',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.upload.upload_*',
                'menu' => [
                    "title" => lang("上传图片"),
                    "index" => ""
                ]
            ],

            ['name' => lang('商品管理'),
                'list' => [
                    [   'name' => lang('商品列表(商品管理)'),
                        'type' => 'single',
                        'value' => 'v1.goods.goods_goodslist',
                        'sub_menu' => [
                            "name" => lang("商品管理"),
                            "index" => "5-1",
                            "icon" => "iconshangpinguanli-icon-weixuanzhongcopy",
                            "path" => "/goodsManagement/commodityManagement"
                        ]
                    ],
                    ['name' => lang('添加商品'),'type' => 'single','value' => 'v1.goods.goods_goodsadd','sub_menu' =>[]],
                    ['name' => lang('商品信息'),'type' => 'single','value' => 'v1.goods.goods_goodsInfo','sub_menu' =>[]],
                    ['name' => lang('编辑商品'),'type' => 'single','value' => 'v1.goods.goods_goodsedit','sub_menu' =>[]],
                    ['name' => lang('删除商品'),'type' => 'single','value' => 'v1.goods.goods_goodsdel','sub_menu' =>[]],
                ],
                'value' => 'v1.goods.goods_*',
                'menu'  => [
                    "title"=> lang("商品管理"),
                    "index"=> "5"
                ],
            ],
            ['name' => lang('商品分类'),
                'list' => [
                    ['name' => lang('商品分类列表(商品分类)'),
                        'type' => 'single',
                        'value' => 'v1.goods.category_categorylist',
                        'sub_menu' =>[
                            "name"=> lang("商品分类"),
                              "index"=> "5-2",
                              "icon"=> "iconrenliantuku-icon-weixuanzhong",
                              "path"=> "/goodsManagement/classificationGoods"
                        ]
                    ],
                    ['name' => lang('添加商品分类'),'type' => 'single','value' => 'v1.goods.category_add','sub_menu' =>[]],
                    ['name' => lang('编辑商品分类'),'type' => 'single','value' => 'v1.goods.category_edit','sub_menu' =>[]],
                    ['name' => lang('删除商品分类'),'type' => 'single','value' => 'v1.goods.category_del','sub_menu' =>[]],
                    ['name' => lang('商品分类信息'),'type' => 'single','value' => 'v1.goods.category_categoryinfo','sub_menu' =>[]],
                ],
                'value' => 'v1.goods.category_*',
                'menu'  => [
                    "title"=> lang("商品管理"),
                    "index"=> "5"
                ],
            ],
            ['name' => lang('商品属性'),
                'list' => [
                    ['name' => lang('商品属性(商品规格)'),
                        'type' => 'single',
                        'value' => 'v1.goods.gspec_spec',
                        'sub_menu' =>[
                            "name"=> lang("商品规格"),
                              "index"=> "5-3",
                              "icon"=> "iconshangpinguihua-icon-weixuanzhongcopy",
                              "path"=> "/goodsManagement/specificationsGoods"
                        ]
                    ],
                    ['name' => lang('商品属性列表'),'type' => 'single','value' => 'v1.goods.gspec_speclist','sub_menu' =>[]],
                    ['name' => lang('添加商品属性'),'type' => 'single','value' => 'v1.goods.gspec_add','sub_menu' =>[]],
                    ['name' => lang('编辑商品属性'),'type' => 'single','value' => 'v1.goods.gspec_edit','sub_menu' =>[]],
                    ['name' => lang('删除商品属性'),'type' => 'single','value' => 'v1.goods.gspec_del','sub_menu' =>[]],
                    ['name' => lang('商品属性信息'),'type' => 'single','value' => 'v1.goods.gspec_specinfo','sub_menu' =>[]],
                ],
                'value' => 'v1.goods.gspec_*',
                'menu'  => [
                    "title"=> lang("商品管理"),
                    "index"=> "5"
                ],
            ],
            ['name' => lang('订单'),
                'list' => [
                    ['name' => lang('订单列表(订单管理)'),
                        'type' => 'single',
                        'value' => 'v1.order.order_orderlist',
                        'sub_menu' =>[
                            "name"=> lang("订单管理"),
                              "index"=> "5-4",
                              "icon"=> "icondingdanguanli-icon-weixuanzhongcopy",
                              "path"=> "/goodsManagement/orderManagement"
                        ]
                    ],
                    ['name' => lang('订单信息'),'type' => 'single','value' => 'v1.order.order_orderinfo','sub_menu' =>[]],
                    ['name' => lang('修改订单金额'),'type' => 'single','value' => 'v1.order.order_updatePrice','sub_menu' =>[]],
                    ['name' => lang('取消订单'),'type' => 'single','value' => 'v1.order.order_cancel','sub_menu' =>[]],
                    ['name' => lang('订单确认收款'),'type' => 'single','value' => 'v1.order.order_pay','sub_menu' =>[]],
                    ['name' => lang('订单商品发货'),'type' => 'single','value' => 'v1.order.order_delivery','sub_menu' =>[]],
                    ['name' => lang('订单确认完成'),'type' => 'single','value' => 'v1.order.order_finish','sub_menu' =>[]],
                    ['name' => lang('查看商品发货物流'),'type' => 'single','value' => 'v1.order.order_deliveryLogs','sub_menu' =>[]],
                ],
                'value' => 'v1.order.order_*',
                'menu'  => [
                    "title"=> lang("商品管理"),
                    "index"=> "5"
                ],
            ],
            ['name' => lang('支付方式'),
                'list' => [
                    ['name' => lang('支付方式列表(支付方式)'),
                        'type' => 'single',
                        'value' => 'v1.payment.payment_paymentlist',
                        'sub_menu' =>[
                            "name"=> lang("支付方式"),
                              "index"=> "5-5",
                              "icon"=> "iconzhifufangshi-icon-weixuanzhongcopy",
                              "path"=> "/goodsManagement/payWayManagement"
                        ]
                    ],
                    ['name' => lang('支付方式配置'),'type' => 'single','value' => 'v1.payment.payment_config','sub_menu' =>[]],
                    ['name' => lang('支付方式信息'),'type' => 'single','value' => 'v1.payment.payment_info','sub_menu' =>[]],
                    ['name' => lang('删除支付方式'),'type' => 'single','value' => 'v1.payment.payment_del','sub_menu' =>[]],
                ],
                'value' => 'v1.payment.payment_*',
                'menu'  => [
                    "title"=> lang("商品管理"),
                    "index"=> "5"
                ],
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
                    "title"=> lang("数据统计",)
                    "index"=> ""
                            ],
            ],



            [
                'name' => lang('门店二维码',)
                'list' => [
                    [
                        'name' => lang('获取二维码'),
                        'type' => 'single',
                        'value' => 'v1.store.qrcode_getqrcode',
                        'sub_menu' => []
                    ]
                ],
                'value' => 'v1.store.qrcode_*',
                'menu' => [
                    "title" => lang("门店二维码",)
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
                    "title"=> lang("数据统计",)
                    "index"=> ""
                            ],
            ],*/
        ];
        return $menuArr;
    }

    // 超级管理员的导航菜单
    public function menu()
    {
        return [
            [
                "title" => lang("数据统计"),
                "index" => "1",
                "menuItemList" => [
                    [
                        "name" => lang("数据概况"),
                        "index" => "1-1",
                        "icon" => "icongaikuang-icon-weixuanzhong",
                        "path" => "/dataStatistics/profileData"
                    ],
                    [
                        "name" => lang("客流分析"),
                        "index" => "1-2",
                        "icon" => "iconmendianfenxi-icon-weixuanzhong",
                        "path" => "/dataStatistics/storesAnalysis"
                    ],
                    [
                          "name" => lang("接口请求"),
                          "index" => "1-3",
                          "icon" => "iconwaibujiekouqingqiujilu",
                          "path" => "/dataStatistics/interfaceRequest"
                    ]
                ]
            ],
            [
                "title" => lang("系统管理"),
                "index" => "2",
                "menuItemList" => [
                    [
                        "name" => lang("角色管理"),
                        "index" => "2-1",
                        "icon" => "iconjiaoseguanli-icon-weixuanzhong",
                        "path" => "/systemManagement/roleManagement"
                    ],
                    [
                        "name"=> lang("数据设置"),
                        "index"=> "2-2",
                        "icon"=> "icondatasettings",
                        "path"=> "/systemManagement/dataSetting"
                    ]
                ]
            ],
            [
                "title" => lang("门店管理"),
                "index" => "3",
                "menuItemList" => [
                    [
                        "name" => lang("门店管理"),
                        "index" => "3-1",
                        "icon" => "iconmendianguanli-icon-weixuanzhong",
                        "path" => "/storeManagement/storeManagement"
                    ],
                    [
                        "name" => lang("区域管理"),
                        "index" => "3-2",
                        "icon" => "iconquyuguanli-icon-weixuanzhong",
                        "path" => "/storeManagement/districtManagement"
                    ],
                    [
                        "name" => lang("设备管理"),
                        "index" => "3-3",
                        "icon" => "iconshebeiguanli-icon-weixuanzhong",
                        "path" => "/storeManagement/deviceManagement"
                    ],
                    [
                        "name" => lang("员工管理"),
                        "index" => "3-4",
                        "icon" => "iconyuangongguanli-icon-weixuanzhong",
                        "path" => "/storeManagement/staffManagement"
                    ]
                ]
            ],
            [
                "title" => lang("用户管理"),
                "index" => "4",
                "menuItemList" => [
                    [
                        "name" => lang("全部用户"),
                        "index" => "4-1",
                        "icon" => "iconyingyongguanli-icon-weixuanzhongcopy1",
                        "path" => "/userManagement/allUserManagement"
                    ],
                    [
                        "name" => lang("会员管理"),
                        "index" => "4-2",
                        "icon" => "iconhuiyuanguanli-icon-weixuanzhong",
                        "path" => "/userManagement/memberManagement"
                    ],
                    [
                        "name" => lang("标签管理"),
                        "index" => "4-3",
                        "icon" => "iconyingyongguanli-icon-weixuanzhong",
                        "path" => "/userManagement/labelManagement"
                    ],
                    [
                        "name" => lang("人脸图库"),
                        "index" => "4-4",
                        "icon" => "iconrenliantuku-icon-weixuanzhong",
                        "path" => "/userManagement/faceOfGallery"
                    ]
                ]
            ],
            [
              "title" => lang("商品管理"),
              "index" => "5",
              "menuItemList" => [
                [
                  "name" => lang("商品管理"),
                  "index" => "5-1",
                  "icon" => "iconshangpinguanli-icon-weixuanzhongcopy",
                  "path" => "/goodsManagement/commodityManagement"
                ],
                [
                  "name" => lang("商品分类"),
                  "index" => "5-2",
                  "icon" => "iconshangpinfenlei-icon-weixuanzhongcopy",
                  "path" => "/goodsManagement/classificationGoods"
                ],
                [
                  "name" => lang("商品规格"),
                  "index" => "5-3",
                  "icon" => "iconshangpinguihua-icon-weixuanzhongcopy",
                  "path" => "/goodsManagement/specificationsGoods"
                ],
                [
                  "name" => lang("订单管理"),
                  "index" => "5-4",
                  "icon" => "icondingdanguanli-icon-weixuanzhongcopy",
                  "path" => "/goodsManagement/orderManagement"
                ],
                [
                  "name" => lang("支付方式"),
                  "index" => "5-5",
                  "icon" => "iconzhifufangshi-icon-weixuanzhongcopy",
                  "path" => "/goodsManagement/payWayManagement"
                ]
              ]
            ]

        ];

    }
}