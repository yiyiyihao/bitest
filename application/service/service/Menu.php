<?php
namespace app\service\service;
/**
 * 后台菜单接口
 */
class Menu{
	/**
	 * 获取菜单结构
	 */
	public function getAdminMenu(){
		$menuArr = [
            'index' => [
                'name' => lang('数据统计'),
                'order' => 0,
                'menu' => [
                    'analysis' => [
                        'name'  => lang('统计分析'),
                        'list'  => [
                            'index' => [
                                'name' => lang('客流分析'),
                                'url' => url('home'),
                                'order' => 10
                            ],
                            'store' => [
                                'name' => lang('门店分析'),
                                'url' => url('store'),
                                'order' => 20
                            ],
                            'win' => [
                                'name' => lang('展会数据'),
                                'url' => url('win/index'),
                                'order' => 40
                            ],
                            'api' => [
                                'name' => lang('接口请求'),
                                'url' => url('apilog/index'),
                                'order' => 50
                            ],
                            'map' => [
                                'name' => lang('热力图'),
                                'url' => url('map'),
                                'order' => 70
                            ],
                            'orbit' => [
                                'name' => lang('客流轨迹'),
                                'url' => url('orbit'),
                                'order' => 80
                            ],
                            'config' => [
                                'name' => lang('配置管理'),
                                'url' => url('config/notice'),
                                'order' => 80
                            ],
//                             'compare' => [
//                                 'name' => lang('对比分析'),
//                                 'url' => url('compare'),
//                                 'order' => 30
//                             ],
                        ]
                    ],
                ]
            ],
		    'store' => [
		        'name' => lang('门店管理'),
		        'order' => 10,
		        'menu' => [
		            'store'   => [
		                'name'    => lang('门店管理'),
		                'list'    => [
		                    'store' => [
		                        'name' => lang('门店列表'),
		                        'url' => url("store/index"),
		                        'order' => 10
		                    ],
// 		                    'block' => [
// 		                        'name' => lang('区域列表'),
// 		                        'url' => url('block/index'),
// 		                        'order' => 20
// 		                    ],
		                ]
		            ],
		            'device'  => [
		                'name'    =>  lang('设备管理'),
		                'list'    => [
		                    'list' => [
		                        'name' => lang('设备列表'),
		                        'url' => url('device/index'),
		                        'order' => 20
		                    ],
// 		                    'group' => [
// 		                        'name' => lang('设备分组'),
// 		                        'url' => url('dgroup/index'),
// 		                        'order' => 30
// 		                    ],
		                ]
		            ],
		        ]
		    ],
		    'content' => [
		        'name' => lang('内容管理'),
		        'order' => 20,
		        'menu' => [
		            'goods' => [
		                'name'    =>  lang('商品管理'),
		                'list'    =>  [
		                    'goods' => [
		                        'name' => lang('商品管理'),
		                        'url' => url('product/index'),
		                        'order' => 10
		                    ],
		                    'gcategory' => [
		                        'name' => lang('商品分类'),
		                        'url' => url('category/index'),
		                        'order' => 20
		                    ],
		                    'spec' => [
		                        'name' => lang('商品规格'),
		                        'url' => url('gspec/index'),
		                        'order' => 20
		                    ],
		                ]
		            ],
		            'order' => [
		                'name'    =>  lang('订单管理'),
		                'list'    =>  [
		                    'index' => [
		                        'name' => lang('订单管理'),
		                        'url' => url('order/index'),
		                        'order' => 10
		                    ],
// 		                    'service' => [
// 		                        'name' => lang('退款/售后管理'),
// 		                        'url' => url('service/index'),
// 		                        'order' => 20
// 		                    ],
		                    'payment' => [
		                        'name' => lang('支付方式'),
		                        'url' => url('payment/index'),
		                        'order' => 30
		                    ],
		                ]
		            ],
		        ]
		    ],
		    'content_store' => [
		        'name' => lang('门店内容管理'),
		        'order' => 20,
		        'menu' => [
		            'goods' => [
		                'name'    =>  lang('门店商品管理'),
		                'list'    =>  [
		                    'goods' => [
		                        'name' => lang('门店商品管理'),
		                        'url' => url('product/index', ['other' => 1]),
		                        'order' => 10
		                    ],
		                    'gcategory' => [
		                        'name' => lang('门店商品分类'),
		                        'url' => url('category/index', ['other' => 1]),
		                        'order' => 20
		                    ],
		                    'spec' => [
		                        'name' => lang('门店商品规格'),
		                        'url' => url('gspec/index', ['other' => 1]),
		                        'order' => 20
		                    ],
		                ]
		            ],
		            'order' => [
		                'name'    =>  lang('门店订单管理'),
		                'list'    =>  [
		                    'index' => [
		                        'name' => lang('门店订单管理'),
		                        'url' => url('order/index', ['other' => 1]),
		                        'order' => 10
		                    ],
// 		                    'service' => [
// 		                        'name' => lang('门店退款/售后管理'),
// 		                        'url' => url('service/index', ['other' => 1]),
// 		                        'order' => 20
// 		                    ],
		                ]
		            ],
		        ]
		    ],
		    'user' => [
		        'name' => lang('用户管理'),
		        'order' => 30,
		        'menu' => [
		            'user' => [
		                'name'    =>  lang('用户管理'),
		                'list'    =>  [
		                    'member' => [
		                        'name' => lang('用户列表'),
		                        'url' => url('member/index'),
		                        'order' => 10
		                    ],
		                    'grade' => [
		                        'name' => lang('会员等级'),
		                        'url' => url('ugrade/index'),
		                        'order' => 20
		                    ],
		                    'profile' => [
		                        'name' => lang('账户信息'),
		                        'url' => url('member/profile'),
		                        'order' => 30
		                    ],
		                    'group' => [
		                        'name' => lang('用户分组'),
		                        'url' => url('ugroup/index'),
		                        'order' => 40
		                    ],
		                    'person' => [
		                        'name' => lang('个体用户列表'),
		                        'url' => url('person/index'),
		                        'order' => 10
		                    ],
		                ]
		            ]
		        ]
		    ],
        ];
		return $menuArr;
	}
}
