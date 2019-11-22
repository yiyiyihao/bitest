<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});
Route::any('index/error',function(){
    return '路由错误';
});
Route::get('hello/:name', 'index/hello');

//生成access_token，post访问Token类下的token方法
Route::post(':version/token/token','api/:version.login/token');
Route::post(':version/login/login','api/:version.login/login');
Route::post(':version/login/logout','api/:version.login/logout');

Route::post(':version/refresh','api/:version.login/refresh');
Route::post(':version/key','api/:version.key/add');
Route::post(':version/appRegist','api/:version.key/appRegist');
//上传图片
Route::post(':version/upload/upload','api/:version.upload.upload/uploadImageSource');
//接口请求
Route::post(':version/apilog/face','api/:version.apilog.face/list');
Route::post(':version/apilog/detail','api/:version.apilog.face/detail');

//data
//客流统计
Route::post(':version/data/homedata','api/:version.data.ajaxdata/homedata');
//门店分析
Route::post(':version/data/storedata','api/:version.data.ajaxdata/storedata');
//热力图
Route::post(':version/data/getmaplist','api/:version.data.ajaxdata/getmaplist');
//轨迹图
Route::post(':version/data/getorbitlist','api/:version.data.ajaxdata/getorbitlist');
//获取基础数据
Route::post(':version/data/normaldata','api/:version.data.ajaxdata/normaldata');
//获取年龄比例
Route::post(':version/data/agedata','api/:version.data.ajaxdata/agedata');
//获取性别比例
Route::post(':version/data/genderdata','api/:version.data.ajaxdata/genderdata');
//获取新老客户比例
Route::post(':version/data/customerdata','api/:version.data.ajaxdata/customerdata');
//门店所有用户
Route::post(':version/data/getUsers','api/:version.data.ajaxdata/getUsers');
Route::post(':version/data/delPerson','api/:version.data.ajaxdata/delPerson');
//访客详情
Route::post(':version/data/visitordetail','api/:version.data.ajaxdata/getVisitorDetail');
Route::post(':version/data/getTitle','api/:version.data.ajaxdata/getTitle');
//获取菜单
Route::post(':version/data/get_menu','api/:version.data.ajaxdata/get_menu');

//store.block
Route::post(':version/store/getblocklist','api/:version.store.block/getblocklist');
//编辑设备时使用的区域列表，不分页
Route::post(':version/store/blocklist','api/:version.store.block/blocklist');
Route::post(':version/store/addblock','api/:version.store.block/addblock');
Route::post(':version/store/blockinfo','api/:version.store.block/info');
Route::post(':version/store/editblock','api/:version.store.block/editblock');
Route::post(':version/store/delblock','api/:version.store.block/delblock');
//store.store
Route::post(':version/store/getstorelist','api/:version.store.store/getstorelist');
Route::post(':version/store/addstore','api/:version.store.store/addStore');
Route::post(':version/store/editstoreinfo','api/:version.store.store/info');
Route::post(':version/store/editstore','api/:version.store.store/editStore');
Route::post(':version/store/delstore','api/:version.store.store/delstore');
Route::post(':version/store/storeinfo','api/:version.store.store/storeInfo');
Route::post(':version/store/admin','api/:version.store.store/admin');
Route::post(':version/store/deladmin','api/:version.store.store/deladmin');
//store.storetype
Route::post(':version/store/storetype','api/:version.store.store/storetype');
//store.qrcode
Route::post(':version/store/getqrcode','api/:version.store.qrcode/getqrcode');
//store.substore
Route::post(':version/store/substorelist','api/:version.store.subStore/subStoreList');
Route::post(':version/store/addsubstore','api/:version.store.subStore/addSubStore');
Route::post(':version/store/editsubstore','api/:version.store.subStore/edit');
Route::post(':version/store/delsubstore','api/:version.store.subStore/del');
//store.admin
Route::post(':version/store/admin','api/:version.store.store/admin');
Route::post(':version/store/deladmin','api/:version.store.store/deladmin');
//获取用户旗下的所有实体门店，提供给添加区域选择使用
Route::post(':version/store/realstore','api/:version.store.store/realStore');
//获取用户旗下的所有门店，提供给添加员工、会员选择使用
Route::post(':version/store/allStore','api/:version.store.store/allStore');


//staffer.staffer
Route::post(':version/staffer/stafferlist','api/:version.staffer.staffer/stafferList');
Route::post(':version/staffer/stafferadd','api/:version.staffer.staffer/staffAdd');
Route::post(':version/staffer/addstaffer','api/:version.staffer.staffer/stafferAdd');
Route::post(':version/staffer/stafferedit','api/:version.staffer.staffer/stafferEdit');
Route::post(':version/staffer/stafferdel','api/:version.staffer.staffer/stafferDel');
Route::post(':version/staffer/setgroup','api/:version.staffer.staffer/setGroup');
//
Route::post(':version/staffer/worklog','api/:version.staffer.workLog/workLog');
Route::post(':version/staffer/stafferWorkLog','api/:version.staffer.workLog/stafferWorkLog');
//获取当前登入用户信息
Route::post(':version/staffer/userinfo','api/:version.staffer.staffer/userinfo');
//修改当前登入用户信息
Route::post(':version/staffer/profile','api/:version.staffer.staffer/profile');
Route::post(':version/staffer/resetPwd','api/:version.staffer.staffer/resetPwd');
//添加体验帐号
Route::post(':version/staffer/addexperiencer','api/:version.staffer.staffer/addExperiencer');
Route::post(':version/staffer/experiencerlist','api/:version.staffer.staffer/experiencerList');
Route::post(':version/staffer/editexperiencer','api/:version.staffer.staffer/editExperiencer');
//staffer.Ugroup
Route::post(':version/staffer/grouplist','api/:version.staffer.ugroup/grouplist');
Route::post(':version/staffer/groupadd','api/:version.staffer.ugroup/add');
Route::post(':version/staffer/groupinfo','api/:version.staffer.ugroup/info');
Route::post(':version/staffer/groupedit','api/:version.staffer.ugroup/edit');
Route::post(':version/staffer/groupdel','api/:version.staffer.ugroup/del');
Route::post(':version/staffer/purviewlist','api/:version.staffer.ugroup/purviewlist');
Route::post(':version/staffer/grouppurview','api/:version.staffer.ugroup/purview');



//member.member
Route::post(':version/member/userlist','api/:version.member.member/userlist');  //全部用户列表
Route::post(':version/member/memberlist','api/:version.member.member/memberList');
Route::post(':version/member/memberedit','api/:version.member.member/edit');
Route::post(':version/member/memberdel','api/:version.member.member/del');
//会员详情
Route::post(':version/member/info','api/:version.member.member/info');
Route::post(':version/member/detail','api/:version.member.member/detail');

Route::post(':version/member/userByLabel','api/:version.member.member/userByLabel');
//member.Ugrade
Route::post(':version/member/gradelist','api/:version.member.ugrade/gradelist');
Route::post(':version/member/gradeadd','api/:version.member.ugrade/add');
Route::post(':version/member/gradeedit','api/:version.member.ugrade/edit');
Route::post(':version/member/gradedel','api/:version.member.ugrade/del');
Route::post(':version/member/gradeinfo','api/:version.member.ugrade/info');
//关联标签
Route::post(':version/member/related','api/:version.member.member/related');
Route::post(':version/member/dis_related','api/:version.member.member/dis_related');



//device.device
Route::post(':version/device/devicelist','api/:version.device.device/deviceList');
Route::post(':version/device/deviceadd','api/:version.device.device/deviceAdd');
Route::post(':version/device/devicedel','api/:version.device.device/deviceDel');
Route::post(':version/device/deviceedit','api/:version.device.device/deviceEdit');
Route::post(':version/device/authorizeinfo','api/:version.device.device/authorizeInfo');
Route::post(':version/device/authorize','api/:version.device.device/authorize');
Route::post(':version/device/fulls','api/:version.device.device/fulls');
Route::post(':version/device/faces','api/:version.device.device/faces');
Route::post(':version/device/open','api/:version.device.device/open');
Route::post(':version/device/play','api/:version.device.device/play');
Route::post(':version/device/getTypeList','api/:version.device.device/getTypeList');
Route::post(':version/device/getEntity','api/:version.device.device/getEntity');


//goods.product
Route::post(':version/goods/goodslist','api/:version.goods.goods/goodslist');
Route::post(':version/goods/goodsadd','api/:version.goods.goods/goodsadd');
Route::post(':version/goods/goodsinfo','api/:version.goods.goods/goodsInfo');
Route::post(':version/goods/goodsedit','api/:version.goods.goods/goodsedit');
Route::post(':version/goods/goodsdel','api/:version.goods.goods/goodsdel');
//goods.gspec
Route::post(':version/goods/goodsspecinfo','api/:version.goods.gspec/goodsSpecInfo');
Route::post(':version/goods/goodsspec','api/:version.goods.gspec/spec');
Route::post(':version/goods/speclist','api/:version.goods.gspec/speclist');
Route::post(':version/goods/specadd','api/:version.goods.gspec/add');
Route::post(':version/goods/specedit','api/:version.goods.gspec/edit');
Route::post(':version/goods/specdel','api/:version.goods.gspec/del');
Route::post(':version/goods/specinfo','api/:version.goods.gspec/specinfo');
//获取商品规格参数列表
Route::post(':version/goods/specparams','api/:version.goods.gspec/getSpecListByGoods');
//goods.category
Route::post(':version/goods/categorylist','api/:version.goods.category/categorylist');
Route::post(':version/goods/categoryadd','api/:version.goods.category/add');
Route::post(':version/goods/categoryedit','api/:version.goods.category/edit');
Route::post(':version/goods/categorydel','api/:version.goods.category/del');
Route::post(':version/goods/categoryinfo','api/:version.goods.category/categoryinfo');



//label.Goodslabel
Route::post(':version/label/Goodslabellist','api/:version.label.GoodsLabel/labellist');
Route::post(':version/label/Goodslabeladd','api/:version.label.GoodsLabel/add');
Route::post(':version/label/Goodslabeledit','api/:version.label.GoodsLabel/edit');
Route::post(':version/label/Goodslabeldel','api/:version.label.GoodsLabel/del');
Route::post(':version/label/Goodslabelinfo','api/:version.label.GoodsLabel/labelinfo');
//label.Userlabel
Route::post(':version/label/Userlabellist','api/:version.label.UserLabel/labellist');
Route::post(':version/label/Userlabeladd','api/:version.label.UserLabel/add');
Route::post(':version/label/Userlabeledit','api/:version.label.UserLabel/edit');
Route::post(':version/label/Userlabeldel','api/:version.label.UserLabel/del');
Route::post(':version/label/Userlabelinfo','api/:version.label.UserLabel/labelinfo');




//order.order
Route::post(':version/order/orderlist','api/:version.order.order/orderlist');
Route::post(':version/order/orderinfo','api/:version.order.order/orderinfo');
Route::post(':version/order/updateprice','api/:version.order.order/updatePrice');
Route::post(':version/order/cancel','api/:version.order.order/cancel');
Route::post(':version/order/pay','api/:version.order.order/pay');
Route::post(':version/order/deliveryinfo','api/:version.order.order/deliveryInfo');
Route::post(':version/order/delivery','api/:version.order.order/delivery');
Route::post(':version/order/finish','api/:version.order.order/finish');
Route::post(':version/order/logistics','api/:version.order.order/deliveryLogs');
//创建订单
Route::post(':version/order/createorder','api/:version.order.order/createOrder');



//payment
Route::post(':version/payment/paymentlist','api/:version.payment.payment/paymentlist');
Route::post(':version/payment/config','api/:version.payment.payment/config');
Route::post(':version/payment/info','api/:version.payment.payment/info');
Route::post(':version/payment/del','api/:version.payment.payment/del');

//person
Route::post(':version/person/personlist','api/:version.person.person/personlist');
Route::post(':version/person/faces','api/:version.person.person/faces');
Route::post(':version/person/choose','api/:version.person.person/choose');
Route::post(':version/person/distinct','api/:version.person.person/distinct');
Route::post(':version/person/comfirm','api/:version.person.person/distinct_comfirm');
Route::post(':version/person/detail','api/:version.person.person/detail');
Route::post(':version/person/facedel','api/:version.person.person/faceDel');
Route::post(':version/person/faceadd','api/:version.person.person/faceadd');
Route::post(':version/person/faceremove','api/:version.person.person/faceTokenDel');

//picture
Route::post(':version/picture/getkeypoint','api/:version.picture.faceRecognition/getKeyPoint');
Route::post(':version/picture/getattributes','api/:version.picture.faceRecognition/getAttributes');
//Route::post(':version/picture/getKeyPoint','api/:version.picture.faceRecognition/getKeyPoint');

Route::post(':version/picture/getbodykeypoint','api/:version.picture.bodyRecognition/getKeyPoint');
Route::post(':version/picture/getbodyattributes','api/:version.picture.bodyRecognition/getAttributes');


//help
Route::post(':version/help/cateList','api/:version.help.help/cateList')->middleware('Charge');
Route::post(':version/help/titleList','api/:version.help.help/titleList')->middleware('Charge');



//用户管理
Route::post(':version/user/list','api/:version.member.user/list');      //全部用户列表
Route::post(':version/user/detail','api/:version.member.user/detail');  //用户详情
Route::post(':version/user/user_staffset','api/:version.member.user/staffset');  //设置用户角色
Route::post(':version/user/getstores','api/:version.member.user/getstores');  //获取用户门店列表
Route::post(':version/user/del','api/:version.member.user/del');        //删除用户
//判断当前登录用户是否有添加子门店权限
Route::post(':version/store/check_sub_add','api/:version.store.subStore/checkadd');
//员工获取设置角色列表
Route::post(':version/staffer/getgroups','api/:version.staffer.staffer/getgroups');

Route::post(':version/apilog/face_list','api/:version.apilog.face/list');
Route::post(':version/apilog/face_detail','api/:version.apilog.face/detail');

//图片识别
Route::post(':version/identification/faceDetect','api/:version.identification.faceDetect/face');

Route::post(':version/rule/add_rule','api/:version.rule.rule/add_rule');
Route::post(':version/rule/rule_list','api/:version.rule.rule/rule_list');
Route::post(':version/rule/edit_rule','api/:version.rule.rule/edit_rule');
Route::post(':version/rule/del_rule','api/:version.rule.rule/del_rule');
Route::post(':version/rule/rule_info','api/:version.rule.rule/rule_info');
Route::post(':version/rule/open_charge_rule','api/:version.rule.rule/open_charge_rule');
Route::post(':version/staffer/purview_list','api/:version.staffer.ugroup/purview_list');

//系统设置
Route::post(':version/system/set','api/:version.system.setting/set');
Route::post(':version/system/get_confidence','api/:version.system.setting/get_confidence');
Route::post(':version/system/life_cycle','api/:version.system.setting/life_cycle');
Route::post(':version/system/get','api/:version.system.setting/get');
//test
Route::post(':version/goods/category_list','api/:version.goods.category1/categorylist');
Route::post(':version/goods/category_add','api/:version.goods.category1/add');
Route::post(':version/goods/category_edit','api/:version.goods.category1/edit');
Route::post(':version/goods/category_del','api/:version.goods.category1/del');
Route::post(':version/goods/category_info','api/:version.goods.category1/categoryinfo');
//test
Route::post(':version/goods/goods_list','api/:version.goods.goods1/goodslist');
Route::post(':version/goods/goods_add','api/:version.goods.goods1/goodsadd');
Route::post(':version/goods/goods_edit','api/:version.goods.goods1/goodsedit');
Route::post(':version/goods/goods_del','api/:version.goods.goods1/goodsdel');
Route::post(':version/goods/goods_info','api/:version.goods.goods1/goodsInfo');
Route::post(':version/goods/goods_onsale','api/:version.goods.goods1/goods_onsale');
Route::post(':version/goods/goods_offsale','api/:version.goods.goods1/goods_offsale');
Route::post(':version/goods/change_stock','api/:version.goods.goods1/change_stock');
//test
Route::post(':version/goods/category1_list','api/:version.goods.goods2/categorylist');
Route::post(':version/goods/goods2_list','api/:version.goods.goods2/factory_goodslist');
Route::post(':version/goods/goods1_list','api/:version.goods.goods2/goodslist');
Route::post(':version/goods/goods1_add','api/:version.goods.goods2/goodsadd');
Route::post(':version/goods/goods1_edit','api/:version.goods.goods2/goodsedit');
Route::post(':version/goods/goods1_del','api/:version.goods.goods2/goodsdel');
Route::post(':version/goods/goods1_info','api/:version.goods.goods2/goodsInfo');
Route::post(':version/goods/goods1_onsale','api/:version.goods.goods2/goods_onsale');
Route::post(':version/goods/goods1_offsale','api/:version.goods.goods2/goods_offsale');
Route::post(':version/goods/change1_stock','api/:version.goods.goods2/change_stock');
//test
Route::post(':version/order/order_list','api/:version.order.order1/orderlist');
Route::post(':version/order/order_info','api/:version.order.order1/orderinfo');
Route::post(':version/order/create_order','api/:version.order.order1/createOrder');
Route::post(':version/order/cancel_order','api/:version.order.order1/cancel');
Route::post(':version/order/finish_order','api/:version.order.order1/finish');

return [

];
