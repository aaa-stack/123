<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/20
 * Time: 11:33
 */
namespace App\Service\Channel;


use App\Models\Order;

class DemoChannel implements PayChannel
{
    /**
     * 拉起支付
     *
     * @param string $orderId 系统订单ID
     *
     * 注：目前支持三种三种形式，
     * 1. return redirect("your final url");
     * 2. return some html code with form submitted
     * 3. return array like ['status'=>1, 'url'=>'your qrcode url'],that is json response
     * @return mixed
     */
    public function pull($orderId)
    {
        $order = Order::find($orderId);
    }


    /**
     * 通道签名验证
     *
     * @param $key
     * @param $param
     * @return mixed
     */
    public function verify($key, $param)
    {
        return false;
    }


    /**
     * 根据通道状态码返回是否继续
     *
     * @param $param
     * @return boolean
     */
    public function checkStatus($param)
    {
        return true;
    }


    /**
     * 通道响应函数
     *
     * @param bool $ok
     * @return mixed
     */
    public function response($ok = true)
    {
        return '00';
    }


}