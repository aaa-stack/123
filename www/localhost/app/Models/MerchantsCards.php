<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MerchantsCards extends Model
{
    protected $table = 'merchants_cards';


    /**
     * 获取支付产品列表
     *
     * @return array
     */

    public function getCardList($where)
    {
        $data = Db::table($this->table)->select(['id', 'card_num', 'bank_id', 'branch_name', 'province', 'city', 'name'])->where($where)->get();
        $dara_arr = json_decode($data, true);
        foreach ($dara_arr as $k => &$v) {
            $v['bank_name'] = BANK_NAME[$v['bank_id']];
        }
        return $dara_arr;
    }

    /**
     * 获取支付产品列表
     *
     * @return array
     */

    public function getCardById($where)
    {
        $data = Db::table($this->table)
            ->where($where)
            ->select(['id', 'card_num', 'bank_id', 'branch_name', 'province', 'city', 'name'])
            ->first();
        return $data;
    }

    /**
     * 修改银行卡信息
     *
     * @return array
     */

    public function editCard($where, $input)
    {
        $res = Db::table($this->table)->where($where)->update($input);
        return $res;
    }

    /**
     * 添加银行卡
     *
     * @return array
     */

    public function addCard($input)
    {
        $res = Db::table($this->table)->insert($input);
        return $res;
    }

    /**
     * del银行卡
     *
     * @return array
     */

    public function delCard($where)
    {
        $res = Db::table($this->table)->where($where)->delete();
        return $res;
    }

    /**
     * 银行卡数量
     *
     * @return number
     */

    public function cardCount($merId)
    {
        $count = Db::table($this->table)->where('merchant_id', '=', $merId)->count();
        return $count;
    }
}
