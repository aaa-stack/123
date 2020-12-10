<?php

namespace App\Service\Channel;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Class LxAliH5Channel
 * 官方支付宝Wap通道
 *
 * @package App\Service\Channel
 */
class CsZfbH5Channel implements PayChannel
{
    /**
     * 支付状态
     */
    
    const TRADE_STATUS_SUCCESS = 'SUCCESS'; // 支付成功

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
     */

    public function pull($order)
    {
        $native = array(
            "mchId" => $order['memberid'],
            "appId" => 'eca14dc665e146c48e1e060304ede782',
            "productId" => 8007,
            "mchOrderNo" => $order['pay_orderid'],
            "currency" => 'cny',
            "amount" => $order['pay_amount'],
            "clientIp" => get_client_ip(),
            "notifyUrl" => get_client_ip(),
            "subject" => 'yeepay测试商品1',
            "body" => 'yeepay测试商品描述',
            "extra" => '{"openId":"o2RvowBf7sOVJf8kJksUEMceaDqo"}',
        );

        $native["sign"] = $this->getWwfSign($order['key'], $native);
        Log::info('财神淘宝红包支付宝H5支付参数' . json_encode($native));
        $resultJson = httpPost($order['gateway'], $native);
        $result = json_decode($resultJson,true);
        if('SUCCESS' == $result['retCode']) {
            return redirect($result['payParams']['payUrl']);
        }else{
            return $resultJson;
        }
    }

    /**
     * 通道签名验证
     *
     * @param $key
     * @param $param
     * @return boolean
     */
    public function verify($key, $param)
    {
        Log::info('DT支付宝H5回调参数' . json_encode($param));
        $returnArray = array( // 返回字段
            "memberid" => $param["memberid"], // 商户ID
            "orderid" =>  $param["orderid"], // 订单号
            "amount" =>  $param["amount"], // 交易金额
            "datetime" =>  $param["datetime"], // 交易时间
            "transaction_id" =>  $param["transaction_id"], // 支付流水号
            "returncode" => $param["returncode"],
        );
        if ($param["sign"] == $this->getWwfSign($key, $returnArray) && $param["returncode"] == self::TRADE_STATUS_SUCCESS) {
            return true;
        }
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
        if ('47.57.4.103' == get_client_ip()) {
            if ($param['returncode'] == self::TRADE_STATUS_SUCCESS) {
                return true;
            }
        }
        Log::info('DT支付宝H5IP错误');
        return false;
    }

    /**
     * 通道响应函数
     *
     * @param bool $ok
     * @return mixed
     */
    public function response($ok = true)
    {
        if ($ok) return 'OK';
        else return 'fail';
    }

    /**
     * @param $native
     * @param $Md5key
     */

    public function getWwfSign($Md5key, $native)
    {
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
    }

    public function doCurlPost($tjurl, $data_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $tjurl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8', //设置提交类型为json格式
            'Content-Length: ' . strlen($data_string)
        ));
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $return_content;
    }
}
