<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChanAccount extends Model
{
    protected $table = "channels_accounts";

    /**
     * 获取支付子账号列表
     *
     * @return array
     */

    public function getAccountList($channelId)
    {
        $data = DB::table($this->table)
            ->select(['id', 'name', 'status', 'weight'])
            ->where('channel_id', '=', $channelId)
            ->get();
        return json_decode($data, true);
    }

    /**
     * 添加支付子账号列表
     *
     * @return array
     */

    public function addAccount($data)
    {
        return Db::table($this->table)->insert($data);
    }

    /**
     * 通过支付子账号id查询支付子账号信息
     *
     * @param  $accountId 支付子账号id
     * @return bool
     */
    public function getAccountById($accountId)
    {
        $res = DB::table($this->table)->select(['id', 'name', 'status', 'weight', 'channel_id', 'merchant_no', 'key'])->where('id', '=', $accountId)->first();
        return $res;
    }

    /**
     * 修改子账号信息
     *
     * @param  int $accountId 子账号id
     * @param  array $data 修改信息
     * @return bool
     */

    public function editAccount($accountId, $data)
    {
        return Db::table($this->table)->where('id', '=', $accountId)->update($data);
    }

    /**
     * 判断子账号是否存在
     *
     * @param  $name 子账号name
     * @return bool
     */

    public function hasName($name)
    {
        return Db::table($this->table)->where('name', '=', $name)->exists();
    }

    /**
     * 判断子账号是否存在
     *
     * @param  $id 子账号id
     * @return bool
     */

    public function hasAccount($id)
    {
        return Db::table($this->table)->where('id', '=', $id)->exists();
    }

    /**
     * 删除支付子账号
     * 
     * @param  int $accountId
     * 
     * @return bool
     */

    public function delAccount($accountId)
    {
        return Db::table($this->table)->where('id', '=', $accountId)->delete();
    }
}
