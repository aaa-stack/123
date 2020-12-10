<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/20
 * Time: 11:12
 */
namespace App\Service\Channel;

interface PayChannel
{
    /**
     * 拉起支付
     *
     * @param string $orderId 系统订单ID
     * @return mixed
     *
     * 注：目前支持三种三种形式，
     * 1. return redirect("your final url");
     * 2. return some html code with form submitted
     * 3. return array like ['status'=>1, 'url'=>'your qrcode url'],that is json response
     * 4. return view() response, currently used
     */
    public function pull($orderId);


    /**
     * 通道签名验证
     *
     * @param string $key
     * @param array $param
     * @return boolean
     */
    public function verify($key, $param);


    /**
     * 根据通道状态码返回是否继续
     *
     * @param $param
     * @return boolean
     */
    public function checkStatus($param);

    /**
     * 通道响应函数
     *
     * @param bool $ok
     * @return mixed
     */
    public function response($ok = true);
}