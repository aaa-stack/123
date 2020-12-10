<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group([
    'middleware' => ['auth:admin', 'access', 'log']
], function ($router) {
    $router->get('user/getUserInfoById', 'UserController@getUserInfoById');
    $router->put('user/editUserStatus', 'UserController@editUserStatus');
    $router->get('user/getUserList', 'UserController@getUserList');
    $router->post('user/addUser', 'UserController@addUser');
    $router->put('user/editUser', 'UserController@editUser');
    $router->get('user/google', 'UserController@google');
    $router->put('user/resetGoogle', 'UserController@resetGoogle');
    $router->put('user/editPass', 'UserController@editPass');
    $router->put('user/editSelf', 'UserController@editSelf');

    $router->post('auth/refresh', 'AuthController@refresh');
    $router->get('auth/getUserRoleList', 'AuthController@getUserRoleList');
    $router->get('auth/getRoleAuthList', 'AuthController@getRoleAuthList');
    $router->put('auth/setUserRole', 'AuthController@setUserRole');
    $router->put('auth/setRoleAuth', 'AuthController@setRoleAuth');
    $router->get('auth/getRoleList', 'AuthController@getRoleList');
    $router->get('auth/getAuthList', 'AuthController@getAuthList');
    $router->delete('auth/delRole', 'AuthController@delRole');
    $router->post('auth/addRole', 'AuthController@addRole');
    $router->delete('auth/delUserRole', 'AuthController@delUserRole');
    $router->get('products/getProductsList', 'ProductsController@getProductsList');
    $router->get('products/getProList', 'ProductsController@getProList');
    $router->get('products/getProductById', 'ProductsController@getProductById');
    $router->get('products/getPayTypeList', 'ProductsController@getPayTypeList');
    $router->put('products/setStatus', 'ProductsController@setProductStatus');
    $router->put('products/edit', 'ProductsController@editProduct');
    $router->put('products/setClient', 'ProductsController@setProductClient');
    $router->post('products/addProduct', 'ProductsController@addProduct');
    $router->delete('products/delProduct', 'ProductsController@delProduct');

    $router->get('order/getOrderList', 'OrderController@getOrderList');
    $router->get('order/getWaterList', 'OrderController@getWaterList');
    $router->get('order/getMerTradeList', 'OrderController@getMerTradeList');
    $router->get('order/getChanTradeList', 'OrderController@getChanTradeList');
    $router->put('order/setOrderStatus', 'OrderController@setOrderStatus');
    $router->get('order/excelOrder', 'OrderController@excelOrder');
    $router->put('order/ManualCallback', 'OrderController@ManualCallback');
    $router->get('order/getTradeInfo', 'OrderController@getTradeInfo');

    $router->post('merchants/addUser', 'MerchantController@addUser');
    $router->get('merchants/getUserList', 'MerchantController@getUserList');
    $router->get('merchants/getUserInfoById', 'MerchantController@getUserInfoById');
    $router->put('merchants/editUser', 'MerchantController@editUser');
    $router->put('merchants/editAmount', 'MerchantController@editAmount');

    $router->get('channels/getChannelList', 'ChannelController@getChannelList');
    $router->get('channels/getChanList', 'ChannelController@getChanList');
    $router->get('channels/getChannelById', 'ChannelController@getChannelById');
    $router->put('channels/setStatus', 'ChannelController@setChannelStatus');
    $router->post('channels/addChannel', 'ChannelController@addChannel');
    $router->put('channels/editChannel', 'ChannelController@editChannel');
    $router->delete('channels/delChannel', 'ChannelController@delChannel');

    $router->get('account/getAccountList', 'ChannelAccountController@getAccountList');
    $router->get('account/getAccountById', 'ChannelAccountController@getAccountById');
    $router->put('account/setStatus', 'ChannelAccountController@setAccountStatus');
    $router->post('account/addAccount', 'ChannelAccountController@addAccount');
    $router->put('account/editAccount', 'ChannelAccountController@editAccount');
    $router->delete('account/delAccount', 'ChannelAccountController@delAccount');

    $router->get('set/getSetList', 'ChannelSetController@getSetList');
    $router->put('set/setChannel', 'ChannelSetController@setChannel');
    $router->get('set/getCostList', 'ChannelSetController@getCostList');
    $router->put('set/setCost', 'ChannelSetController@setCost');
    $router->get('set/getUserSet', 'ChannelSetController@getUserSet');
    $router->get('set/getAllCost', 'ChannelSetController@getAllCost');
    $router->get('set/getPaySet', 'DispensController@getPaySet');
    $router->put('set/setPaySet', 'DispensController@setPaySet');

    $router->get('dispens/getOrderList', 'DispensController@getOrderList');
    $router->put('dispens/setOrderStatus', 'DispensController@setOrderStatus');

    $router->get('log/getLogList', 'LogController@getLogList');
    $router->get('log/getOrderLog', 'LogController@getOrderLog');
});

$router->group([
    'middleware' => ['auth:admin']
], function ($router) {
    $router->post('auth/logout', 'AuthController@logout');
    $router->get('auth/getUserInfo', 'AuthController@getInfo');
    $router->put('user/editUserInfo', 'UserController@editUserInfo');
});

$router->group([
    'middleware' => ['auth:merchants', 'log']
], function ($router) {
    $router->post('merchants/logout', 'MerchantController@logout');
    $router->get('merchants/getUserInfo', 'MerchantController@getInfo');
    $router->put('merchants/resetKey', 'MerchantController@resetKey');
    $router->get('merchants/google', 'MerchantController@google');
    $router->put('merchants/resetGoogle', 'MerchantController@resetGoogle');
    $router->get('merchants/getCardList', 'MerchantController@getCardList');
    $router->get('merchants/getCardInfo', 'MerchantController@getCardInfo');
    $router->put('merchants/editCard', 'MerchantController@editCard');
    $router->post('merchants/addCard', 'MerchantController@addCard');
    $router->delete('merchants/delCard', 'MerchantController@delCard');
    $router->get('merchants/getBank', 'MerchantController@getBank');
    $router->get('merchants/getSelfInfo', 'MerchantController@getSelfInfo');
    $router->put('merchants/editSelf', 'MerchantController@editSelf');
    $router->put('merchants/editPass', 'MerchantController@editPass');
    $router->put('merchants/editPayPass', 'MerchantController@editPayPass');
    $router->post('merchants/addMer', 'MerchantController@addUser');
    $router->put('merchants/editMer', 'MerchantController@editUser');
    $router->get('merchants/getMerList', 'MerchantController@getUserList');
    $router->get('merchants/getMerInfo', 'MerchantController@getUserInfoById');

    $router->get('order/getMyOrder', 'OrderController@getOrderList');
    $router->get('order/getMyWater', 'OrderController@getWaterList');
    $router->get('order/excel', 'OrderController@excelOrder');
    $router->put('order/callback', 'OrderController@ManualCallback');
    $router->get('order/getMerTradeInfo', 'OrderController@getMerTradeInfo');

    $router->get('chan/getChan', 'ChannelController@getChanList');

    $router->get('dispens/getMyOrder', 'DispensController@getOrderList');
    $router->post('dispens/createOrder', 'DispensController@GenerateOrder');
    $router->get('product/getMerProList', 'ProductsController@getMerProList');

    $router->get('log/getMyLog', 'LogController@getLogList');
});

$router->group([], function ($router) {
    $router->post('pay/notify', 'OrderController@notify');
    $router->post('order/createOrder', 'OrderController@GenerateOrder');
    $router->post('auth/login', 'AuthController@login');
    $router->post('merchants/login', 'MerchantController@login');
});
