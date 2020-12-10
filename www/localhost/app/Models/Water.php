<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Water extends Model
{
    protected $table = 'merchants_water';
    protected $channel_table = 'channels';
    protected $mer_table = 'merchants';

    /**
     * 查询流水信息列表
     * 
     * @param  $num 每页数量
     * @param  $page 页数
     * @param  $where 搜索条件
     * @return array
     */

    public function getWaterList($where, $page, $num)
    {
        $condition = [];

        if (isset($where['pay_orderid']) && !empty($where['pay_orderid'])) {
            array_push($condition, ['pay_orderid', '=', $where['pay_orderid']]);
        }
        if (isset($where['merchant_no']) && !empty($where['merchant_no'])) {
            array_push($condition, [$this->table . '.merchant_no', '=', $where['merchant_no']]);
        }
        if (isset($where['stime']) && !empty($where['stime'])) {
            array_push($condition, ['time', '>=', strtotime($where['stime'])]);
        }
        if (isset($where['etime']) && !empty($where['etime'])) {
            array_push($condition, ['time', '<=', strtotime($where['etime'])]);
        }
        if (isset($where['pay_channel']) && !empty($where['pay_channel'])) {
            array_push($condition, ['channel_id', '=', $where['pay_channel']]);
        }
        if (isset($where['type']) && !empty($where['type'])) {
            array_push($condition, [$this->table . '.type', '=', $where['type']]);
        }
        $data['list'] = Db::table($this->table)->select([$this->table . '.id', $this->table . '.pay_orderid', $this->table . '.time', $this->table . '.merchant_no', $this->table . '.old_amount', $this->table . '.edit_amount', $this->table . '.new_amount', $this->table . '.channel_id', $this->table . '.type', $this->table . '.notice', $this->channel_table . '.name as channel_name', $this->mer_table . '.username as merchant_name'])
            ->leftJoin($this->channel_table, $this->table . ".channel_id", '=', $this->channel_table . '.id')
            ->leftJoin($this->mer_table, $this->table . ".merchant_no", '=', $this->mer_table . '.merchant_no')
            ->where($condition)
            ->orderBy($this->table . ".time", "desc")
            ->offset(($page - 1) * $num)
            ->limit($num)
            ->get();
        $data['count'] = Db::table($this->table)->where($condition)->count();
        $data['page'] = ceil(count($data['list']) / $num);
        return $data;
    }

    /**
     * 添加流水信息
     * 
     * @param  array $data
     * 
     * @return bool
     */

    public function add($data)
    {
        return Db::table($this->table)->insert($data);
    }
}
