<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/20
 * Time: 11:14
 */

namespace App\Service\Channel;


class ChannelFactory
{
    /**
     * @param $code
     * @return \App\Service\Channel\PayChannel
     */
    public static function getChannel($code)
    {
        if($code == null) return null;

        include_once app_path('Service/Channel/' . ucfirst($code) . 'Channel.php');
        $className = 'App\Service\Channel\\' . ucfirst($code) . 'Channel';
        if(class_exists($className)) {
            return new $className;
        }
        return new DemoChannel();
    }
}