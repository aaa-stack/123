<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DispenSetting extends Model
{
    protected $table = 'dispen_settings';

    /**
     * 获取支付产品列表
     *
     * @return array
     */

    /**
     * 查询订单
     * 
     * @return array
     */

    public function getSet()
    {
        $data = DB::table($this->table)->first();
        return $data;
    }

    /**
     * 查询订单
     * 
     * @return array
     */

    public function setSet($data)
    {
        $res = DB::table($this->table)->update($data);
        return $res;
    }
}
