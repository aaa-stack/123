<?php
    //生成订单号 商户号  毫秒+三位随机数 
    function GenerateUniqueNumber(){
        list($msec, $sec) = explode(' ', microtime());
        $msectime = str_pad((float)sprintf('%.0f', floatval($msec) * 1000),3,"0",STR_PAD_LEFT);
        $order_id = date('YmdHis').$msectime.rand(100,999);
        return  $order_id;
    }

    //生成用户的key
    function GenerateMd5Key($merchant_no,$username){
        $str = $merchant_no.$username.GenerateUniqueNumber();
        return md5($str);
    }

    //form表单提交
    function createForm($url,$data){
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $url);  
        curl_setopt($ch, CURLOPT_POST, true);  
        curl_setopt($ch, CURLOPT_HEADER, 0);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data );  
        $output = curl_exec($ch);  
        $info = curl_getinfo($ch);  
        print_r($output);
        curl_close($ch); 
    }

    //生成密钥
    function getSign($Md5key, $native){
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
    }

    if (!function_exists('app_path')) {
        /**
         * Get the path to the application folder.
         *
         * @param  string $path
         * @return string
         */
        function app_path($path = '')
        {
            return app('path') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
        }
    }

    //获取ip
    function get_client_ip(){
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")){
            $ip = getenv("HTTP_CLIENT_IP");
        }else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")){
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        }else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
            $ip = getenv("REMOTE_ADDR");
        }else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
            $ip = $_SERVER['REMOTE_ADDR'];
        }else{
            $ip = "unknown";
        }
            return($ip);
    }

    //根据权重返回index
    function getRandom($configs) {
        $wConfigs = array();
        foreach ($configs as $idx => $cfg) {
            $weight = isset($cfg['weight']) ? intval($cfg['weight']) : 0;
            if ($weight > 0) {
                for ($i=0; $i<$weight; $i++) {
                    $wConfigs[] = $idx;
                }
            }
        }
        $index = mt_rand(0, count($wConfigs) - 1);
        return $wConfigs[$index];
    }

    //是否正整数
    function isId($num){
        if(!is_numeric($num)|| $num < 0 ||strpos($num,".")!==false){
            return false;
        }else{
            return true;
        }
    }

    if ( ! function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        return app()->basePath() . '/public' . ($path ? '/' . $path : $path);
    }
}

function httpPost($url, $data)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}