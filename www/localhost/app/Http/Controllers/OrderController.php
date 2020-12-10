<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Products;
use App\Models\Channel;
use App\Models\Water;
use App\Service\Channel\ChannelFactory;
use App\Service\Channel\CallBackParameter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\NotifyJob;
use App\Jobs\OrderLogJob;
use App\Jobs\CommissionJob;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * 获取订单列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getOrderList(Request $request)
    {
        $this->validate($request, [
            'pay_orderid' => 'numeric',
            'merchant_no' => 'numeric',
            'applyStime' => 'date_format:"Y-m-d H:i:s"',
            'applyEtime' => 'date_format:"Y-m-d H:i:s"',
            'pay_channel' => 'integer|min:0',
            'status' => [Rule::in([1, 2, 3, 4])],
            'type' => [Rule::in([1, 2])],
            'successStime' => 'date_format:"Y-m-d H:i:s"',
            'successEtime' => 'date_format:"Y-m-d H:i:s"',
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);
        $input = request(['merchant_no', 'applyStime', 'applyEtime', 'pay_channel', 'status', 'order_no', 'pay_orderid', 'type', 'successStime', 'successEtime']);
        if (auth('merchants')->user()) {
            if (auth('merchants')->user()->type == 1) {
                $input['merchant_no'] = auth('merchants')->user()->merchant_no;
            } else {
                $child = DB::table('merchants')->where('agent_id', '=', auth('merchants')->user()->id)->pluck('merchant_no');
                $child_arr = json_decode($child, true);
                if (empty($input['merchant_no'])) {
                    $input['merchant_no'] = $child_arr;
                } else {
                    if (!in_array($input['merchant_no'], $child_arr)) {
                        return response()->json(['data' => ['list' => [], 'count' => 0, 'page' => 0], 'msg' => '获取订单列表成功', 'code' => 200]);
                    }
                }
            }
        }
        $page = request('page');
        $num = request('num');
        $order = new Order();
        $data = $order->getOrderList($input, $page, $num);
        return response()->json(['data' => $data, 'msg' => '获取订单列表成功', 'code' => 200]);
    }

    /**
     * 获取订单列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getTradeInfo(Request $request)
    {
        $this->validate($request, [
            'applyStime' => 'date_format:"Y-m-d H:i:s"',
            'applyEtime' => 'date_format:"Y-m-d H:i:s"',
        ]);
        $input = request(['applyStime', 'applyEtime']);
        $order = new Order();
        if (empty($input['applyStime'])) {
            $today['stime'] = strtotime((date('Y-m-d') . ' 00:00:00'));
        } else {
            $today['stime'] = strtotime($input['applyStime']);
        }
        if (empty($input['applyEtime'])) {
            $today['etime'] = strtotime((date('Y-m-d') . ' 23:59:59'));
        } else {
            $today['etime'] = strtotime($input['applyEtime']);
        }
        $month['stime'] = strtotime((date('Y-m') . '-01 00:00:00'));
        $month['etime'] = strtotime((date('Y-m-d', strtotime(date('Y-m') . "-01 +1 month -1 day")) . " 23:59:59"));
        $data['today'] = $order->getTradeInfo($today);
        $data['month'] = $order->getTradeInfo($month);
        $data['count'] = $order->getOrderCount($today);
        return response()->json(['data' => $data, 'msg' => '获取交易信息成功', 'code' => 200]);
    }

    /**
     * 获取订单列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getMerTradeInfo(Request $request)
    {
        $this->validate($request, [
            'applyStime' => 'date_format:"Y-m-d H:i:s"',
            'applyEtime' => 'date_format:"Y-m-d H:i:s"',
        ]);
        $input = request(['applyStime', 'applyEtime']);
        $order = new Order();
        if (empty($input['applyStime'])) {
            $today['stime'] = strtotime((date('Y-m-d') . ' 00:00:00'));
        } else {
            $today['stime'] = strtotime($input['applyStime']);
        }
        if (empty($input['applyEtime'])) {
            $today['etime'] = strtotime((date('Y-m-d') . ' 23:59:59'));
        } else {
            $today['etime'] = strtotime($input['applyEtime']);
        }
        $month['stime'] = strtotime((date('Y-m') . '-01 00:00:00'));
        $month['etime'] = strtotime((date('Y-m-d', strtotime(date('Y-m') . "-01 +1 month -1 day")) . " 23:59:59"));
        $mer = auth('merchants')->user();
        if ($mer->type > 1) {
            $merId = $mer->id;
            $agent = true;
        } else {
            $merId = $mer->merchant_no;
            $agent = false;
        }
        $data['today'] = $order->getMerTradeInfo($merId, $today, $agent);
        $data['month'] = $order->getMerTradeInfo($merId, $month, $agent);
        $data['count'] = $order->getOrderCount($today, $merId, $agent);
        return response()->json(['data' => $data, 'msg' => '获取交易信息成功', 'code' => 200]);
    }

    /**
     * 导出订单列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function excelOrder(Request $request)
    {
        $this->validate($request, [
            'pay_orderid' => 'numeric',
            'merchant_no' => 'numeric',
            'applyStime' => 'date_format:"Y-m-d H:i:s"',
            'applyEtime' => 'date_format:"Y-m-d H:i:s"',
            'pay_channel' => 'integer|min:0',
            'status' => [Rule::in([1, 2, 3, 4])],
            'type' => [Rule::in([1, 2])],
            'successStime' => 'date_format:"Y-m-d H:i:s"',
            'successEtime' => 'date_format:"Y-m-d H:i:s"',
        ]);
        $input = request(['merchant_no', 'applyStime', 'applyEtime', 'pay_channel', 'status', 'order_no', 'pay_orderid', 'type', 'successStime', 'successEtime']);
        if (auth('merchants')->user()) {
            if (auth('merchants')->user()->type == 1) {
                $input['merchant_no'] = auth('merchants')->user()->merchant_no;
            } else {
                $child = DB::table('merchants')->where('agent_id', '=', auth('merchants')->user()->id)->pluck('merchant_no');
                $child_arr = json_decode($child, true);
                if (empty($input['merchant_no'])) {
                    $child = DB::table('merchants')->where('agent_id', '=', auth('merchants')->user()->id)->pluck('merchant_no');
                    $input['merchant_no'] = $child_arr;
                } else {
                    if (!in_array($input['merchant_no'], $child_arr)) {
                        return response()->json(['data' => ['list' => [], 'count' => 0, 'page' => 0], 'msg' => '获取订单列表成功', 'code' => 200]);
                    }
                }
            }
        }
        $order = new Order();
        $data = $order->getOrders($input);
        if ($data['count'] > 20000) {
            return response()->json(['data' => [], 'msg' => '数据超过2w条,请分批导出', 'code' => 401]);
        } else {
            return response()->json(['data' => $data, 'msg' => '获取成功', 'code' => 200]);
        }
    }

    /**
     * 拉起订单
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function GenerateOrder(Request $request)
    {
        $this->validate($request, [
            'pay_memberid' => 'required|numeric',
            'pay_orderid' => 'required|alpha_dash',
            'pay_applydate' => 'required|date_format:"Y-m-d H:i:s"',
            'pay_bankcode' => 'required|integer',
            'pay_notifyurl' => 'required',
            'pay_callbackurl' => 'required',
            'pay_amount' => 'required|integer|min:0',
            'pay_md5sign' => 'required',
        ]);

        dispatch(new OrderLogJob(request()->all()));
        $credentials = request(['pay_memberid', 'pay_orderid', 'pay_applydate', 'pay_bankcode', 'pay_notifyurl', 'pay_callbackurl', 'pay_amount']);

        $sign = request('pay_md5sign');
        if (strtotime($credentials['pay_applydate']) > time()) {
            return response()->json(['data' => [], 'msg' => '时间有误', 'code' => 401]);
        }

        $products = new Products();
        if (!$products->isOpen($credentials['pay_bankcode'])) {
            return response()->json(['data' => [], 'msg' => '支付方式不存在或未开启', 'code' => 401]);
        }

        $merchant = new Merchant();

        $data = Db::table("merchants")->where('merchant_no', '=', $credentials['pay_memberid'])->select(['status', 'recharge', 'key', 'type'])->first();
        
        if (empty($data) || $data->status != 1 || $data->recharge != 1) {
            return response()->json(['data' => [], 'msg' => '商户号不存在或被禁用', 'code' => 401]);
        }
        
        if ($data->type > 1) {
            return response()->json(['data' => [], 'msg' => '代理商户不能入款', 'code' => 401]);
        }
        
        $channel = new Channel();
        $channels = $channel->getChannelByProductId($credentials['pay_bankcode']);
        if (empty($channels)) {
            return response()->json(['data' => [], 'msg' => '支付通道未配置', 'code' => 401]);
        }
        
        $order = new Order();
        $res = $order->hasOrder($credentials['pay_memberid'], $credentials['pay_orderid']);
        if ($res) {
            return response()->json(['data' => [], 'msg' => '下游订单号重复', 'code' => 401]);
        }
        //获取用户通道设置
        $mer_chan = Db::table('channel_set')->where([['merchant_no', '=', $credentials['pay_memberid']], ['product_id', '=', $credentials['pay_bankcode']]])->select(['status', 'weight', 'rate', 'mode', 'channel_id'])->first();
        if ($mer_chan->status != 1) {
            return response()->json(['data' => [], 'msg' => '该商户未开通此支付类型', 'code' => 401]);
        }
        if (empty($mer_chan->channel_id)) {
            return response()->json(['data' => [], 'msg' => '该商户未配置通道', 'code' => 401]);
        }
        //判断通道是否轮询
        if ($mer_chan->mode == 1) {
            $chan_info = Db::table('channels')->select(['id', 'class', 'rate', 'gateway', 'pagereturn', 'serverreturn'])->where([['id', '=', $mer_chan->channel_id], ['status', '=', 1]])->first();
            if (empty($chan_info)) {
                return response()->json(['data' => [], 'msg' => '该商户未配置通道', 'code' => 401]);
            }
            $chanId = $chan_info->id;
            $class = $chan_info->class;
            $gateway = $chan_info->gateway;
            $pagereturn = $chan_info->pagereturn;
            $serverreturn = $chan_info->serverreturn;
            $rate = $chan_info->rate;
        } else {
            $chanId_arr = explode(',', $mer_chan->channel_id);
            $weight_arr = explode(',', $mer_chan->weight);
            $combine = array_combine($chanId_arr, $weight_arr);
            $chanInfo = Db::table('channels')->select(['id', 'class', 'rate', 'gateway', 'pagereturn', 'serverreturn'])->whereIn("id", $chanId_arr)->where('status', '=', 1)->get();
            $chan_arr = json_decode($chanInfo, true);
            if (empty($chan_arr)) {
                return response()->json(['data' => [], 'msg' => '该商户未配置通道', 'code' => 401]);
            }
            foreach ($chan_arr as $kk => &$vv) {
                $vv['weight'] =  $combine[$vv['id']];
            }
            //根据权重选择通道
            $index = getRandom($chan_arr);
            $chanId = $chan_arr[$index]['id'];
            $class = $chan_arr[$index]['class'];
            $gateway = $chan_arr[$index]['gateway'];
            $pagereturn = $chan_arr[$index]['pagereturn'];
            $serverreturn = $chan_arr[$index]['serverreturn'];
            $rate = $chan_arr[$index]['rate'];
        }
        $account = Db::table('channels_accounts')->select(['id', 'key', 'merchant_no', 'weight'])->where([['channel_id', '=', $chanId], ['status', '=', 1]])->get();
        $account_arr = json_decode($account, true);
        if (empty($account_arr)) {
            return response()->json(['data' => [], 'msg' => '通道子账户未配置', 'code' => 401]);
        }
        //选择子账号
        if (count($account_arr) > 1) {
            $acc = getRandom($account_arr);
            $key = $account_arr[$acc]['key'];
            $account_id = $account_arr[$acc]['id'];
            $merchant_no = $account_arr[$acc]['merchant_no'];
        } else {
            $key = $account_arr[0]['key'];
            $account_id = $account_arr[0]['id'];
            $merchant_no = $account_arr[0]['merchant_no'];
        }
        Log::info("发起支付请求" . json_encode(request()->all()));
        if ($sign != getSign($data->key, $credentials)) {
            return response()->json(['data' => [], 'msg' => '验签失败', 'code' => 401]);
        }
        $order_data = [
            'merchant_no' => $credentials['pay_memberid'],
            'order_no' => $credentials['pay_orderid'],
            'pay_orderid' => GenerateUniqueNumber(),
            'create_time' => time(),
            'pay_bank' => $credentials['pay_bankcode'],
            'pay_notifyurl' => $credentials['pay_notifyurl'],
            'pay_callbackurl' => $credentials['pay_callbackurl'],
            'amount' => $credentials['pay_amount'],
            'ip' => $request->ip(),
            'merchant_no' => $credentials['pay_memberid'],
            'type' => 1,
            'status' => 1,
            'account_id' => $account_id,
            'real_amount' => $credentials['pay_amount'] * (1000 - $mer_chan->rate) / 1000,
            "cost" => $credentials['pay_amount'] * $mer_chan->rate / 1000,
            'cost_rate' => $mer_chan->rate,
            'channel_cost' => $credentials['pay_amount'] * $rate / 1000,
            'channel_rate' => $rate,
            'pay_channel' => $chanId
        ];
        $res = $order->createOrder($order_data);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '创建订单失败', 'code' => 401]);
        }
        $channel = ChannelFactory::getChannel($class);
        $sy_order = [
            'memberid' => $merchant_no,
            'pay_orderid' => $order_data['pay_orderid'],
            'pay_amount' => $credentials['pay_amount'],
            'notifyurl' => $serverreturn,
            'callbackurl' => $pagereturn,
            'key' => $key,
            'gateway' => $gateway
        ];
        $channel->pull($sy_order);
    }

    /**
     * 回调订单
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function notify(Request $request)
    {
        $param = $request->all();
        $order_arr = CallBackParameter::load();
        $orderinfo = "";
        foreach ($param as $k => $v) {
            if (in_array($k, $order_arr)) {
                $order = new Order();
                $orderid = $v;
                $orderinfo = $order->getNotifyInfo($orderid);
            }
        }
        if (empty($orderinfo)) {
            return 'false';
        }
        $channel = ChannelFactory::getChannel($orderinfo->class);
        $res2 = $channel->checkStatus($param, $request->all());
        if (!$res2) {
            return $channel->response($res2);
        }
        $res1 = $channel->verify($orderinfo->key, $param);
        if (!$res1) {
            return $channel->response($res1);
        }
        DB::beginTransaction();
        try {
            $orderinfo = Db::table('orders')->where(['pay_orderid' => $orderid, 'status' => 1])->first();
            if (empty($orderinfo)) {
                return $channel->response(false);
            }
            $merchant = Db::table('merchants')->where(['merchant_no' => $orderinfo->merchant_no])->first();
            if (empty($merchant)) {
                return $channel->response(false);
            }
            $res = Db::table('orders')->where(['pay_orderid' => $orderid])->update(['status' => '3', 'success_time' => time()]);
            if (!$res) {
                DB::rollBack();
                return $channel->response($res);
            }
            $edit_account = $orderinfo->amount - $orderinfo->cost;
            $new_account = $merchant->account + $edit_account;
            $re = Db::table('merchants')->where([['merchant_no', '=', $orderinfo->merchant_no], ['account', '=', $merchant->account]])->update(['account' => $new_account]);

            if (!$re) {
                DB::rollBack();
                return $channel->response($res);
            }
            $water = [
                'pay_orderid' => $orderid,
                'merchant_no' => $orderinfo->merchant_no,
                'old_amount' => $merchant->account,
                'edit_amount' => $edit_account,
                'new_amount' => $new_account,
                'time' => time(),
                'channel_id' => $orderinfo->pay_channel,
                'type' => 1,
                'product_id' => $orderinfo->pay_bank,
                'notice' => date("Y-m-d H:i:s", $orderinfo->create_time) . "用户入款"
            ];
            $res1 = Db::table('merchants_water')->insert($water);
            if (!$res1) {
                DB::rollBack();
                return $channel->response($res1);
            }
            $notify['param'] = array( // 返回字段
                "memberid" => $orderinfo->merchant_no, // 商户ID
                "orderid" => $orderinfo->order_no, // 下游订单号order_no
                "amount" => $orderinfo->amount, // 交易金额
                "datetime" => date("Y-m-d H:i:s"), // 交易时间
                "transaction_id" => $orderid, // 支付流水号
                "returncode" => "00",
            );
            $notify["sign"] = getSign($merchant->key, $notify['param']);
            $notify['url'] = $orderinfo->pay_notifyurl;
            dispatch(new NotifyJob($notify));
            if ($merchant->agent_id) {
                dispatch(new CommissionJob($orderid));
            }
            DB::commit();
            return $channel->response($res2);
        } catch (QueryException $ex) {
            DB::rollBack();
            return $channel->response(false);
        }
    }

    /**
     * 获取流水列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getWaterList(Request $request)
    {
        $this->validate($request, [
            'pay_orderid' => 'numeric',
            'merchant_no' => 'numeric',
            'stime' => 'date_format:"Y-m-d H:i:s"',
            'etime' => 'date_format:"Y-m-d H:i:s"',
            'pay_channel' => 'integer|min:0',
            'type' => [Rule::in([1, 2, 3, 4, 5, 6])],
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);
        $input = request(['merchant_no', 'stime', 'etime', 'pay_channel', 'pay_orderid', 'type']);
        if (auth('merchants')->user()) {
            $input['merchant_no'] = auth('merchants')->user()->merchant_no;
        }
        $page = request('page');
        $num = request('num');
        $Water = new Water();
        $data = $Water->getWaterList($input, $page, $num);
        return response()->json(['data' => $data, 'msg' => '获取订单列表成功', 'code' => 200]);
    }

    /**
     * 商户交易明细
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getMerTradeList(Request $request)
    {
        $this->validate($request, [
            'merchant_no' => 'numeric',
            'type' => [Rule::in([1, 2])],
            'stime' => 'date_format:"Y-m-d H:i:s"',
            'etime' => 'date_format:"Y-m-d H:i:s"',
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);
        $input = request(['merchant_no', 'stime', 'etime']);
        $page = request('page');
        $num = request('num');
        $Order = new Order();
        $data = $Order->getMerTradeList($page, $num, $input);
        return response()->json(['data' => $data, 'msg' => '获取商户交易明细成功', 'code' => 200]);
    }

    /**
     * 商户交易明细
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getChanTradeList(Request $request)
    {
        $this->validate($request, [
            'channel_id' => 'numeric',
            'stime' => 'date_format:"Y-m-d H:i:s"',
            'etime' => 'date_format:"Y-m-d H:i:s"',
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);
        $input = request(['channel_id', 'stime', 'etime']);
        $page = request('page');
        $num = request('num');
        $Order = new Order();
        $data = $Order->getChanTradeList($page, $num, $input);
        return response()->json(['data' => $data, 'msg' => '获取通道交易明细成功', 'code' => 200]);
    }


    /**
     * 手动设置订单支付状态
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setOrderStatus(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric',
            'notcie' => 'required|max:100',
        ]);

        $orderid = request('id');
        $notice = request('notcie');
        DB::beginTransaction();
        try {
            $orderinfo = Db::table('orders')->where(['id' => $orderid, 'status' => 1])->first();
            if (empty($orderinfo)) {
                return response()->json(['data' => [], 'msg' => '订单信息有误', 'code' => 401]);
            }
            $merchant = Db::table('merchants')->where(['merchant_no' => $orderinfo->merchant_no])->first();
            $res = Db::table('orders')->where(['id' => $orderid])->update(['status' => '3', 'success_time' => time()]);
            if (!$res) {
                DB::rollBack();
                return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
            }
            $edit_account = $orderinfo->amount - $orderinfo->cost;
            $new_account = $merchant->account + $edit_account;
            $re = Db::table('merchants')->where([['merchant_no', '=', $orderinfo->merchant_no], ['account', '=', $merchant->account]])->update(['account' => $new_account]);

            if (!$re) {
                DB::rollBack();
                return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
            }
            $water = [
                'pay_orderid' => $orderinfo->pay_orderid,
                'merchant_no' => $orderinfo->merchant_no,
                'old_amount' => $merchant->account,
                'edit_amount' => $edit_account,
                'new_amount' => $new_account,
                'time' => time(),
                'channel_id' => $orderinfo->pay_channel,
                'type' => 1,
                'notice' => $notice,
                'product_id' => $orderinfo->pay_bank,
            ];
            //生成流水订单
            $res1 = Db::table('merchants_water')->insert($water);
            if (!$res1) {
                DB::rollBack();
                return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
            }
            $notify['param'] = array( // 返回字段
                "memberid" => $orderinfo->merchant_no, // 商户ID
                "orderid" => $orderinfo->order_no, // 订单号order_no
                "amount" => $orderinfo->amount, // 交易金额
                "datetime" => date("Y-m-d H:i:s"), // 交易时间
                "transaction_id" => $orderinfo->pay_orderid, // 支付流水号
                "returncode" => "00",
            );
            $notify["sign"] = getSign($merchant->key, $notify['param']);
            $notify['url'] = $orderinfo->pay_notifyurl;
            //设置成功后给下游回调
            dispatch(new NotifyJob($notify));
            if ($merchant->agent_id) {
                dispatch(new CommissionJob($orderid));
            }
            DB::commit();
            return response()->json(['data' => [], 'msg' => '修改订单状态成功', 'code' => 200]);
        } catch (QueryException $ex) {
            DB::rollBack();
            return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
        }
    }

    /**
     * 补发通知
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function ManualCallback(Request $request)
    {
        $this->validate($request, [
            'orderId' => 'required|integer|min:1',
        ]);

        $orderid = request('orderId');
        if ($mer = auth('merchants')->user()) {
            if ($mer->type > 1) {
                $where[] = ['merchants.agent_id', '=', auth('merchants')->user()->id];
            } else {
                $where[] = ['orders.merchant_no', '=', auth('merchants')->user()->merchant_no];
            }
        }
        $where[] = ['orders.id', '=', $orderid];
        $where[] = ['orders.status', '=', 3];
        $orderinfo = Db::table('orders')->select(['pay_orderid', 'order_no', 'merchants.merchant_no', 'amount', 'key', 'pay_notifyurl'])
            ->leftJoin('merchants', 'orders.merchant_no', '=', 'merchants.merchant_no')
            ->where($where)
            ->first();
        if (empty($orderinfo)) {
            return response()->json(['data' => [], 'msg' => '订单信息有误', 'code' => 401]);
        }
        $notify['param'] = array( // 返回字段
            "memberid" => $orderinfo->merchant_no, // 商户ID
            "orderid" => $orderinfo->order_no, // 订单号order_no
            "amount" => $orderinfo->amount, // 交易金额
            "datetime" => date("Y-m-d H:i:s"), // 交易时间
            "transaction_id" => $orderinfo->pay_orderid, // 支付流水号
            "returncode" => "00",
        );
        $notify["sign"] = getSign($orderinfo->key, $notify['param']);
        $notify['url'] = $orderinfo->pay_notifyurl;
        dispatch(new NotifyJob($notify));
        return response()->json(['data' => [], 'msg' => '补发成功', 'code' => 200]);
    }
}
