<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dispens extends Model
{
    protected $table = 'dispensing';
    protected $mer_table = 'merchants';

    /**
     * 获取支付产品列表
     *
     * @return array
     */

    /**
     * 查询订单
     * 
     * @param  $num 每页数量
     * @param  $page 页数
     * @param  $where 搜索条件
     * @return array
     */

    public function getOrderList($where, $page, $num)
    {
        $condition = [];
        if (isset($where['pay_orderid']) && !empty($where['pay_orderid'])) {
            array_push($condition, [$this->table . '.pay_orderid', '=', $where['pay_orderid']]);
        }
        if (isset($where['merchant_no']) && !empty($where['merchant_no'])) {
            array_push($condition, [$this->table . '.merchant_no', '=', $where['merchant_no']]);
        }
        if (isset($where['status']) && !empty($where['status'])) {
            array_push($condition, [$this->table . '.status', '=', $where['status']]);
        }
        $list = Db::table($this->table)
            ->select([$this->table . '.id', $this->table . '.pay_orderid', $this->table . '.merchant_no', $this->mer_table . '.username as merchant_name', $this->table . '.bank_id', $this->table . '.name', $this->table . '.card_num', $this->table . '.status', $this->table . '.amount', $this->table . '.cost', $this->table . '.real_amount', $this->table . '.apply_time', $this->table . '.success_time'])
            ->leftJoin($this->mer_table, $this->table . ".merchant_no", '=', $this->mer_table . '.merchant_no')
            ->where($condition)
            ->orderBy("apply_time", "desc")
            ->offset(($page - 1) * $num)
            ->limit($num)
            ->get();
        $data['list'] = json_decode($list, true);
        foreach ($data['list'] as $k => &$v) {
            $v['bank_name'] = BANK_NAME[$v['bank_id']];
            unset($v['bank_id']);
        }
        $data['count'] = Db::table($this->table)->where($condition)->count();
        $data['page'] = ceil(count($data['list']) / $num);
        return $data;
    }
}
