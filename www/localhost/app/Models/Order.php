<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $table = 'orders';
    protected $channel_table = 'channels';
    protected $account_table = 'channels_accounts';
    protected $water_table = 'merchants_water';
    protected $mer_table = 'merchants';
    protected $pro_table = 'products';

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
        if (isset($where['order_no']) && !empty($where['order_no'])) {
            array_push($condition, [$this->table . '.order_no', '=', $where['order_no']]);
        }
        if (isset($where['merchant_no']) && !empty($where['merchant_no'])) {
            if (is_array($where['merchant_no'])) {
                array_push($condition, [DB::raw($this->table . ".merchant_no in (" . implode(",", $where['merchant_no']) . ")"), '1']);
            } else {
                array_push($condition, [$this->table . '.merchant_no', '=', $where['merchant_no']]);
            }
        }
        if (isset($where['applyStime']) && !empty($where['applyStime'])) {
            array_push($condition, [$this->table . '.create_time', '>=', strtotime($where['applyStime'])]);
        }
        if (isset($where['applyEtime']) && !empty($where['applyEtime'])) {
            array_push($condition, [$this->table . '.create_time', '<=', strtotime($where['applyEtime'])]);
        }
        if (isset($where['successStime']) && !empty($where['successStime'])) {
            array_push($condition, [$this->table . '.success_time', '>=', strtotime($where['successStime'])]);
        }
        if (isset($where['successEtime']) && !empty($where['successEtime'])) {
            array_push($condition, [$this->table . '.success_time', '<=', strtotime($where['successEtime'])]);
        }
        if (isset($where['pay_channel']) && !empty($where['pay_channel'])) {
            array_push($condition, [$this->table . '.pay_channel', '=', $where['pay_channel']]);
        }
        if (isset($where['status']) && !empty($where['status'])) {
            array_push($condition, [$this->table . '.status', '=', $where['status']]);
        }
        if (isset($where['type']) && !empty($where['type'])) {
            array_push($condition, [$this->table . '.type', '=', $where['type']]);
        }
        $data['list'] = Db::table($this->table)
            ->select([$this->table . '.id', $this->table . '.type', $this->table . '.pay_orderid', $this->table . '.order_no', $this->table . '.merchant_no', $this->table . '.status', $this->table . '.create_time', $this->table . '.success_time', $this->table . '.amount', $this->table . '.cost', $this->table . '.real_amount', $this->table . '.ip', $this->channel_table . '.name as channel_name', $this->mer_table . '.username as merchant_name'])
            ->leftJoin($this->channel_table, $this->table . ".pay_channel", '=', $this->channel_table . '.id')
            ->leftJoin($this->mer_table, $this->table . ".merchant_no", '=', $this->mer_table . '.merchant_no')
            ->where($condition)
            ->orderBy($this->table . ".create_time", "desc")
            ->offset(($page - 1) * $num)
            ->limit($num)
            ->get();
        $data['count'] = Db::table($this->table)->where($condition)->count();
        $data['page'] = ceil($data['count'] / $num);
        return $data;
    }

    /**
     * 查询订单
     * 
     * @param  $num 每页数量
     * @param  $page 页数
     * @param  $where 搜索条件
     * @return array
     */

    public function getOrders($where)
    {
        $condition = [];
        if (isset($where['pay_orderid']) && !empty($where['pay_orderid'])) {
            array_push($condition, [$this->table . '.pay_orderid', '=', $where['pay_orderid']]);
        }
        if (isset($where['order_no']) && !empty($where['order_no'])) {
            array_push($condition, [$this->table . '.order_no', '=', $where['order_no']]);
        }
        if (isset($where['merchant_no']) && !empty($where['merchant_no'])) {
            if (is_array($where['merchant_no'])) {
                array_push($condition, [DB::raw($this->table . ".merchant_no in (" . implode(",", $where['merchant_no']) . ")"), '1']);
            } else {
                array_push($condition, [$this->table . '.merchant_no', '=', $where['merchant_no']]);
            }
        }
        if (isset($where['applyStime']) && !empty($where['applyStime'])) {
            array_push($condition, [$this->table . '.create_time', '>=', strtotime($where['applyStime'])]);
        }
        if (isset($where['applyEtime']) && !empty($where['applyEtime'])) {
            array_push($condition, [$this->table . '.create_time', '<=', strtotime($where['applyEtime'])]);
        }
        if (isset($where['successStime']) && !empty($where['successStime'])) {
            array_push($condition, [$this->table . '.success_time', '>=', strtotime($where['successStime'])]);
        }
        if (isset($where['successEtime']) && !empty($where['successEtime'])) {
            array_push($condition, [$this->table . '.success_time', '<=', strtotime($where['successEtime'])]);
        }
        if (isset($where['pay_channel']) && !empty($where['pay_channel'])) {
            array_push($condition, [$this->table . '.pay_channel', '=', $where['pay_channel']]);
        }
        if (isset($where['status']) && !empty($where['status'])) {
            array_push($condition, [$this->table . '.status', '=', $where['status']]);
        }
        if (isset($where['type']) && !empty($where['type'])) {
            array_push($condition, [$this->table . '.type', '=', $where['type']]);
        }
        $data['count'] = Db::table($this->table)->where($condition)->count();
        if ($data['count'] > 20000) {
            return $data;
        }
        $list = Db::table($this->table)
            ->select([$this->table . '.pay_orderid', $this->table . '.order_no', $this->table . '.merchant_no', $this->mer_table . '.username as merchant_name', $this->channel_table . '.name as channel_name', $this->table . '.status', $this->table . '.amount', $this->table . '.cost', $this->table . '.real_amount', $this->table . '.create_time', $this->table . '.success_time', $this->table . '.type', $this->table . '.ip'])
            ->leftJoin($this->channel_table, $this->table . ".pay_channel", '=', $this->channel_table . '.id')
            ->leftJoin($this->mer_table, $this->table . ".merchant_no", '=', $this->mer_table . '.merchant_no')
            ->where($condition)
            ->orderBy("create_time", "desc")
            ->get();
        $data['list'] = json_decode($list, true);
        $type = [1 => "普通", 2 => "充值"];
        $status = [1 => "待支付", 2 => "支付失败", 3 => "支付成功", 4 => "支付成功,回调成功"];
        foreach ($data['list'] as $k => &$v) {
            $v['amount'] = $v['amount'] / 100;
            $v['cost'] = $v['cost'] / 100;
            $v['real_amount'] = $v['real_amount'] / 100;
            $v['type'] = $type[$v['type']];
            $v['status'] = $status[$v['status']];
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $v['success_time'] = date('Y-m-d H:i:s', $v['success_time']);
        }
        return $data;
    }
    /**
     * 创建订单
     * 
     * @param  array $data
     * 
     * @return bool
     */

    public function createOrder($data)
    {
        return Db::table($this->table)->insertGetId($data);
    }

    /**
     * 查看是否有重复三方订单
     * 
     * @param  array $data
     * 
     * @return bool
     */

    public function hasOrder($merchant, $orderId)
    {
        return Db::table($this->table)->where([['merchant_no', '=', $merchant], ['order_no', '=', $orderId]])->exists();
    }

    /**
     * 获取订单详情
     *
     * @param  $pay_orderid 订单id
     * @return array
     */

    public function getOrderInfo($pay_orderid)
    {
        $data = DB::table($this->table)
            ->leftJoin($this->channel_table, $this->table . ".pay_channel", '=', $this->channel_table . '.id')
            ->select([$this->table . '.id', $this->table . '.amount', $this->table . '.status', $this->channel_table . '.class'])
            ->where([[$this->table . '.pay_orderid', '=', $pay_orderid]])
            ->first();
        return $data;
    }

    /**
     * 获取订单详情
     *
     * @param  $pay_orderid 订单id
     * @return array
     */

    public function getTradeInfo($input = [])
    {
        $where2 = [['merchants_water.type', '=', 6]];
        $where = [];
        if (isset($input['stime'])) {
            $where[] = [$this->table . '.create_time', '>=', $input['stime']];
            $where2[] = ['merchants_water.time', '>=', $input['stime']];
        }
        if (isset($input['etime'])) {
            $where[] = [$this->table . '.create_time', '<=', $input['etime']];
            $where2[] = ['merchants_water.time', '<=', $input['etime']];
        }
        $data = DB::table($this->table)
            ->where($this->table . '.status', '>=', 3)
            ->where($where)
            ->first(array(
                DB::raw('SUM(' . $this->table . '.amount) as total'),
                DB::raw('SUM(' . $this->table . '.cost) as cost'),
                DB::raw('SUM(' . $this->table . '.channel_cost) as channel_cost'),
            ));
        $data->agent = DB::table('merchants_water')
            ->where($where2)
            ->sum('edit_amount');
        $data->profit = $data->cost - $data->channel_cost - $data->agent;
        unset($data->channel_cost);
        return $data;
    }

    /**
     * 获取订单详情
     *
     * @param  $pay_orderid 订单id
     * @return array
     */

    public function getOrderCount($input, $merchant = '', $agent = false)
    {
        $where = [
            [$this->table . '.create_time', '>=', $input['stime']],
            [$this->table . '.create_time', '<=', $input['etime']],
        ];
        if (!empty($merchant)) {
            if ($agent) {
                $where[] = ['merchants.agent_id', '=', $merchant];
                $data['success_count'] = DB::table($this->table)
                    ->leftJoin('merchants', $this->table . '.merchant_no', '=', 'merchants.merchant_no')
                    ->where($this->table . '.status', '>=', 3)
                    ->where($where)
                    ->count();
                $data['fail_count'] = DB::table($this->table)
                    ->leftJoin('merchants', $this->table . '.merchant_no', '=', 'merchants.merchant_no')
                    ->whereIn($this->table . '.status', [1, 2])
                    ->where($where)
                    ->count();
                $data['persent'] =  $data['success_count']?ceil(($data['success_count'] / ($data['success_count'] + $data['fail_count'])) * 100):0;
                return $data;
            } else {
                $where[] = ['merchant_no', '=', $merchant];
            }
        }
        $data['success_count'] = DB::table($this->table)
            ->where('status', '>=', 3)
            ->where($where)
            ->count();
        $data['fail_count'] = DB::table($this->table)
            ->whereIn('status', [1, 2])
            ->where($where)
            ->count();
        $data['persent'] = $data['success_count'] + $data['fail_count'] == 0 ? 0 : ceil(($data['success_count'] / ($data['success_count'] + $data['fail_count'])) * 100);
        return $data;
    }

    /**
     * 获取订单详情
     *
     * @param  $pay_orderid 订单id
     * @return array
     */

    public function getMerTradeInfo($merId, $input = [], $agent = false)
    {
        $where2 = [];
        if (isset($input['stime'])) {
            $where[] = [$this->table . '.create_time', '>=', $input['stime']];
            $where2[] = ['merchants_water.time', '>=', $input['stime']];
        }
        if (isset($input['etime'])) {
            $where[] = [$this->table . '.create_time', '<=', $input['etime']];
            $where2[] = ['merchants_water.time', '<=', $input['etime']];
        }
        if ($agent) {
            $where[] = ['merchants.agent_id', '=', $merId];
            $data['cost'] = DB::table("merchants_water")
                ->leftJoin('merchants', 'merchants_water.merchant_no', '=', 'merchants.merchant_no')
                ->where([['merchants_water.type', '=', 6], ['merchants.id', '=', $merId]])
                ->where($where2)
                ->sum('edit_amount');
            $data['total'] = DB::table($this->table)
                ->leftJoin('merchants', $this->table . '.merchant_no', '=', 'merchants.merchant_no')
                ->where($this->table . '.status', '>=', 3)
                ->where($where)
                ->sum('amount');
        } else {
            $where[] = ['merchant_no', '=', $merId];
            $data['total'] = DB::table($this->table)
                ->where('status', '>=', 3)
                ->where($where)
                ->sum('amount');
        }
        return $data;
    }

    /**
     * 获取订单详情
     *
     * @param  $pay_orderid 订单id
     * @return array
     */

    public function getUnpaidInfo($merId = '', $input = [], $agent = false)
    {
        $where = [];

        if (isset($input['stime'])) {
            $where[] = [$this->table . '.create_time', '>=', $input['stime']];
        }
        if (isset($input['etime'])) {
            $where[] = [$this->table . '.create_time', '<=', $input['etime']];
        }

        if (!empty($merId)) {
            if ($agent) {
                $where[] = ['merchants.agent_id', '=', $merId];
                $data = DB::table($this->table)
                    ->where($this->table . '.status', '=', 1)
                    ->leftJoin('merchants', $this->table . '.merchant_no', '=', 'merchants.merchant_no')
                    ->where($where)
                    ->sum($this->table . '.amount');
                return $data;
            } else {
                $where[] = [$this->table . '.merchant_no', '=', $merId];
            }
        }
        $data = DB::table($this->table)
            ->where('status', '=', 1)
            ->where($where)
            ->sum('amount');
        return $data;
    }
    /**
     * 获取回调订单信息
     *
     * @param  $pay_orderid 订单id
     * @return array
     */

    public function getNotifyInfo($pay_orderid)
    {
        $data = DB::table($this->table)
            ->leftJoin($this->channel_table, $this->table . ".pay_channel", '=', $this->channel_table . '.id')
            ->leftJoin($this->account_table, $this->table . ".account_id", '=', $this->account_table . '.id')
            ->select([$this->table . '.id', $this->table . '.amount', $this->table . '.status', $this->channel_table . '.class', $this->account_table . '.key'])
            ->where([[$this->table . '.pay_orderid', '=', $pay_orderid], [$this->table . '.status', '=', 1]])
            ->first();
        return $data;
    }

    /**
     * @param  $num 每页数量
     * @param  $page 页数
     * @param  $where 搜索条件
     * @return array
     */

    public function getMerTradeList($page, $num, $where)
    {
        $condition = [];
        if (isset($where['merchant_no']) && !empty($where['merchant_no'])) {
            $condition[] = ['merchant_no', '=', $where['merchant_no']];
        }
        $data['list'] = DB::table($this->mer_table)
            ->select(['merchant_no', 'username', 'account'])
            ->where($condition)
            ->orderBy("id", "desc")
            ->offset(($page - 1) * $num)
            ->limit($num)
            ->get();
        $data['count'] = Db::table($this->mer_table)->where($condition)->count();
        $data['page'] = ceil($data['count'] / $num);
        if (isset($where['stime']) && !empty($where['stime'])) {
            $con_time[] = ['create_time', '>=', strtotime($where['stime'])];
        }

        if (isset($where['etime']) && !empty($where['etime'])) {
            $con_time[] = ['create_time', '>=', strtotime($where['stime'])];
        }

        if (empty($where['stime']) && empty($where['etime'])) {
            $time = strtotime(date('Y-m-d') . "00:00:00");
            $con_time[] = ['create_time', '>=', $time];
        }
        foreach ($data['list'] as $k => &$v) {
            $v->ammount = DB::table($this->table)->where([['merchant_no', '=', $v->merchant_no], ['status', '>=', 3]])->where($con_time)->first(array(
                DB::raw('SUM(amount) as amount'),
                DB::raw('SUM(cost) as cost'),
                DB::raw('SUM(real_amount) as real_amount')
            ));
            $v->orderScount = DB::table($this->table)->where([['merchant_no', '=', $v->merchant_no], ['status', '>=', 3]])->where($con_time)->count();
            $v->orderCount = DB::table($this->table)->where('merchant_no', '=', $v->merchant_no)->where($con_time)->count();
        }
        return $data;
    }

    /**
     * @param  $num 每页数量
     * @param  $page 页数
     * @param  $where 搜索条件
     * @return array
     */

    public function getChanTradeList($page, $num, $where)
    {
        $condition = [];
        if (isset($where['channel_id']) && !empty($where['channel_id'])) {
            $condition[] = [$this->channel_table . '.id', '=', $where['channel_id']];
        }
        $data['list'] = DB::table($this->channel_table)
            ->leftJoin($this->pro_table, $this->channel_table . '.product_id', '=', $this->pro_table . '.id')
            ->select([$this->channel_table . '.id', $this->channel_table . '.name', $this->pro_table . '.product_name'])
            ->where($condition)
            ->orderBy($this->channel_table . ".id", "desc")
            ->offset(($page - 1) * $num)
            ->limit($num)
            ->get();
        $data['count'] = Db::table($this->channel_table)->where($condition)->count();
        $data['page'] = ceil(count($data['list']) / $num);
        if (isset($where['stime']) && !empty($where['stime'])) {
            $con_time[] = ['create_time', '>=', strtotime($where['stime'])];
        }

        if (isset($where['etime']) && !empty($where['etime'])) {
            $con_time[] = ['create_time', '>=', strtotime($where['stime'])];
        }

        if (empty($where['stime']) && empty($where['etime'])) {
            $time = strtotime(date('Y-m-d') . "00:00:00");
            $con_time[] = ['create_time', '>=', $time];
        }
        foreach ($data['list'] as $k => &$v) {
            $v->ammount = DB::table($this->table)->where([['pay_channel', '=', $v->id], ['status', '>=', 3]])->where($con_time)->first(array(
                DB::raw('SUM(amount) as amount'),
                DB::raw('SUM(cost) as cost'),
                DB::raw('SUM(real_amount) as real_amount')
            ));
            $v->orderScount = DB::table($this->table)->where([['pay_channel', '=', $v->id], ['status', '>=', 3]])->where($con_time)->count();
            $v->orderCount = DB::table($this->table)->where('pay_channel', '=', $v->id)->where($con_time)->count();
            if ($v->orderScount > 0) {
                $v->successRate = sprintf("%.2f", $v->orderScount / $v->orderCount * 100);
            } else {
                $v->successRate = sprintf("%.2f", 0);
            }
        }
        return $data;
    }

    /**
     * 获取回调订单信息
     *
     * @param  $orderid 订单id
     * @return array
     */

    public function getOrderStatus($orderid)
    {
        $data = DB::table($this->table)
            ->leftJoin($this->pro_table, $this->channel_table . '.product_id', '=', $this->pro_table . '.id')
            ->select(['id', 'pay_orderid', 'merchant_no', 'status', 'real_account'])
            ->where([['pay_orderid', '=', $orderid], ['status', '=', 1]])
            ->first();
        return $data;
    }
}
