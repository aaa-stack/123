<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Dispens;
use App\Models\DispenSetting;
use App\Models\MerchantsCards;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DispensController extends Controller
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
            'status' => [Rule::in([1, 2, 3, 4, 5])],
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);
        $input = request(['pay_orderid', 'merchant_no', 'status']);
        if (auth('merchants')->user()) {
            $input['merchant_no'] = auth('merchants')->user()->merchant_no;
        }
        $page = request('page');
        $num = request('num');
        $Dispens = new Dispens();
        $data = $Dispens->getOrderList($input, $page, $num);
        return response()->json(['data' => $data, 'msg' => '获取订单列表成功', 'code' => 200]);
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
            'amount' => 'required|integer|min:0',
            'bank_id' => 'required|integer|min:0',
            'pay_secret' => 'required|max:255',
        ]);
        $user = auth("merchants")->user();
        $amount = request('amount');
        $bank_id = request('bank_id');
        $pay_secret = request('pay_secret');

        $dis = new DispenSetting();
        //获取出款设置 判断是否可以出款
        $set = $dis->getSet();
        if ($set->status != 1) {
            return response()->json(['data' => null, 'msg' => '提款功能未开启', 'code' => 401]);
        }

        if (md5($pay_secret) != $user->pay_secret) {
            return response()->json(['data' => null, 'msg' => '支付密码错误', 'code' => 401]);
        }

        if ($amount < $set->min_amount) {
            return response()->json(['data' => null, 'msg' => '低于单笔最小金额', 'code' => 401]);
        }

        if ($amount > $set->max_amount) {
            return response()->json(['data' => null, 'msg' => '超过单笔最大金额', 'code' => 401]);
        }

        $todyStime = strtotime(date('Y-m-d') . " 00:00:00");
        $todyEtime = strtotime(date('Y-m-d') . " 23:59:59");
        $account = $user->account;
        $cost = $amount * $set->cost_rate / 1000 + $set->cost;
        if ($set->type == 2) {
            if ($account < ($cost + $amount)) {
                return response()->json(['data' => null, 'msg' => '余额不足', 'code' => 401]);
            }
        } else {
            if ($account < $amount) {
                return response()->json(['data' => null, 'msg' => '余额不足', 'code' => 401]);
            }

            if ($cost > $amount) {
                return response()->json(['data' => null, 'msg' => '手续费超过出款金额', 'code' => 401]);
            }
        }

        $card = new MerchantsCards();
        $cardInfo = $card->getCardById([['merchant_id', '=', $user->id], ['id', '=', $bank_id]]);
        if (empty($cardInfo)) {
            return response()->json(['data' => null, 'msg' => '银行卡信息有误', 'code' => 401]);
        }
        $count = DB::table('dispensing')->where([['apply_time', '>', $todyStime], ['apply_time', '<', $todyEtime]])->count();
        if ($count >= $set->day_time) {
            return response()->json(['data' => null, 'msg' => '超过当日提款总次数', 'code' => 401]);
        }

        $time = [['apply_time', '>', $todyStime], ['apply_time', '<', $todyEtime]];
        $times = DB::table('dispensing')->where($time)->count();
        if ($times >= $set->day_time) {
            return response()->json(['data' => null, 'msg' => '超过当日提款总次数', 'code' => 401]);
        }

        $sum = DB::table('dispensing')->where($time)->sum("amount");
        if ($sum + $amount >= $set->day_amount) {
            return response()->json(['data' => null, 'msg' => '超过当日提款总额', 'code' => 401]);
        }

        DB::beginTransaction();
        try {
            //1.从到账金额扣 2.从用户余额扣除手续费(生成两笔流水订单)
            if ($set->type == 2) {
                $re = Db::table('merchants')->where([['id', '=', $user->id], ['account', '=', $account]])->update(['account' => $account - $amount - $cost]);
                if (!$re) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => '修改余额失败', 'code' => 401]);
                }
                $order = [
                    'merchant_no' => $user->merchant_no,
                    'pay_orderid' => GenerateUniqueNumber(),
                    'amount' => $amount,
                    'cost' => $cost,
                    'real_amount' => $amount,
                    'bank_id' => $cardInfo->bank_id,
                    'apply_time' => time(),
                    'name' => $cardInfo->name,
                    'card_num' => $cardInfo->card_num,
                    'status' => 1,
                    'type' => $set->type
                ];
                //生成出款订单
                $re = Db::table('dispensing')->insert($order);
                if (!$re) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => '添加提款订单失败', 'code' => 401]);
                }
                $water = [
                    'pay_orderid' => $order['pay_orderid'],
                    'merchant_no' => $user->merchant_no,
                    'old_amount' => $user->account,
                    'edit_amount' => $amount,
                    'new_amount' => $account - $amount,
                    'time' => $order['apply_time'],
                    'channel_id' => 0,
                    'type' => 2,
                    'notice' => date("Y-m-d H:i:s", $order['apply_time']) . " 提款申请"
                ];

                $water2 = [
                    'pay_orderid' => $order['pay_orderid'],
                    'merchant_no' => $user->merchant_no,
                    'old_amount' => $account - $amount,
                    'edit_amount' => $cost,
                    'new_amount' => $account - $amount - $cost,
                    'time' => $order['apply_time'],
                    'channel_id' => 0,
                    'type' => 2,
                    'notice' => date("Y-m-d H:i:s", $order['apply_time']) . " 提款申请手续费"
                ];
                //生成流水订单
                $res1 = Db::table('merchants_water')->insert($water);
                $res2 = Db::table('merchants_water')->insert($water2);
                if (!$res1 || !$res2) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => ' 提款申请失败', 'code' => 401]);
                }
            } else {
                $re = Db::table('merchants')->where([['id', '=', $user->id], ['account', '=', $account]])->update(['account' => $account - $amount]);
                if (!$re) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => '修改余额失败', 'code' => 401]);
                }
                $order = [
                    'merchant_no' => $user->merchant_no,
                    'pay_orderid' => GenerateUniqueNumber(),
                    'amount' => $amount,
                    'cost' => $cost,
                    'real_amount' => $amount - $cost,
                    'bank_id' => $cardInfo->bank_id,
                    'apply_time' => time(),
                    'name' => $cardInfo->name,
                    'card_num' => $cardInfo->card_num,
                    'status' => 1,
                    'type' => $set->type
                ];
                $re = Db::table('dispensing')->insert($order);
                if (!$re) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => '添加提款订单失败', 'code' => 401]);
                }
                $water = [
                    'pay_orderid' => $order['pay_orderid'],
                    'merchant_no' => $user->merchant_no,
                    'old_amount' => $user->account,
                    'edit_amount' => $amount,
                    'new_amount' => $account - $amount,
                    'time' => $order['apply_time'],
                    'channel_id' => 0,
                    'type' => 1,
                    'notice' => date("Y-m-d H:i:s", $order['apply_time']) . " 提款申请"
                ];
                $res1 = Db::table('merchants_water')->insert($water);
                if (!$res1) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => ' 提款申请失败', 'code' => 401]);
                }
            }
            DB::commit();
            return response()->json(['data' => [], 'msg' => ' 提款申请成功', 'code' => 200]);
        } catch (QueryException $ex) {
            DB::rollBack();
            return response()->json(['data' => [], 'msg' => '提款申请失败', 'code' => 401]);
        }
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
            'pay_orderid' => 'required|numeric',
            'status' => ['required', Rule::in([2, 3, 4, 5])],
            'notcie' => 'required|max:100',
        ]);
        $pay_orderid = request('pay_orderid');
        $status = request('status');
        $notice = request('notcie');
        DB::beginTransaction();
        try {
            $orderinfo = Db::table('dispensing')->where('pay_orderid', '=', $pay_orderid)->first();
            if (empty($orderinfo)) {
                return response()->json(['data' => [], 'msg' => '订单信息有误', 'code' => 401]);
            }
            $merchant = Db::table('merchants')->where('merchant_no', '=', $orderinfo->merchant_no)->first();
            if (empty($merchant)) {
                return response()->json(['data' => [], 'msg' => '商户信息有误', 'code' => 401]);
            }
            if (in_array($orderinfo->status, [3, 4, 5]) || $orderinfo->status == $status) {
                return response()->json(['data' => [], 'msg' => '订单状态无法改变', 'code' => 401]);
            }
            if ($status == 2) {
                $res = Db::table('dispensing')->where('pay_orderid', '=', $pay_orderid)->update(['status' => 2]);
                if (!$res) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
                }
            } elseif ($status == 3 || $status == 5) {
                if ($orderinfo->type == 2) {
                    $re = Db::table('merchants')->where([['id', '=', $merchant->id], ['account', '=', $merchant->account]])->update(['account' => $merchant->account + $orderinfo->amount + $orderinfo->cost]);
                    if (!$re) {
                        DB::rollBack();
                        return response()->json(['data' => [], 'msg' => '修改余额失败', 'code' => 401]);
                    }
                    $re = Db::table('dispensing')->where('pay_orderid', '=', $pay_orderid)->update(['status' => $status]);
                    if (!$re) {
                        DB::rollBack();
                        return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
                    }
                    $water = [
                        'pay_orderid' => $orderinfo->pay_orderid,
                        'merchant_no' => $merchant->merchant_no,
                        'old_amount' => $merchant->account,
                        'edit_amount' => $orderinfo->amount,
                        'new_amount' => $merchant->account + $orderinfo->amount,
                        'time' => time(),
                        'channel_id' => 0,
                        'type' => 5,
                        'notice' => date("Y-m-d H:i:s", $orderinfo->apply_time) . "提款失败退款" . $notice
                    ];

                    $water2 = [
                        'pay_orderid' => $orderinfo->pay_orderid,
                        'merchant_no' => $merchant->merchant_no,
                        'old_amount' => $merchant->account + $orderinfo->amount,
                        'edit_amount' => $orderinfo->cost,
                        'new_amount' => $merchant->account + $orderinfo->amount + $orderinfo->cost,
                        'time' => time(),
                        'channel_id' => 0,
                        'type' => 5,
                        'notice' => date("Y-m-d H:i:s", $orderinfo->apply_time) . " 提款失败退手续费" . $notice
                    ];
                    $res1 = Db::table('merchants_water')->insert($water);
                    $res2 = Db::table('merchants_water')->insert($water2);
                    if (!$res1 || !$res2) {
                        DB::rollBack();
                        return response()->json(['data' => [], 'msg' => ' 流水订单生成失败', 'code' => 401]);
                    }
                } else {
                    $re = Db::table('merchants')->where([['id', '=', $merchant->id], ['account', '=', $merchant->account]])->update(['account' => $merchant->account + $orderinfo->amount]);
                    if (!$re) {
                        DB::rollBack();
                        return response()->json(['data' => [], 'msg' => '修改余额失败', 'code' => 401]);
                    }
                    $re = Db::table('dispensing')->where('pay_orderid', '=', $pay_orderid)->update(['status' => $status]);
                    if (!$re) {
                        DB::rollBack();
                        return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
                    }
                    $water = [
                        'pay_orderid' => $orderinfo->pay_orderid,
                        'merchant_no' => $merchant->merchant_no,
                        'old_amount' => $merchant->account,
                        'edit_amount' => $orderinfo->amount,
                        'new_amount' => $merchant->account + $orderinfo->amount,
                        'time' => time(),
                        'channel_id' => 0,
                        'type' => 5,
                        'notice' => date("Y-m-d H:i:s", $orderinfo->apply_time) . "提款失败退款" . $notice
                    ];
                    $res1 = Db::table('merchants_water')->insert($water);
                    if (!$res1) {
                        DB::rollBack();
                        return response()->json(['data' => [], 'msg' => ' 流水订单生成失败', 'code' => 401]);
                    }
                }
            } elseif ($status == 4) {
                $res = Db::table('dispensing')->where('pay_orderid', '=', $pay_orderid)->update(['status' => 4, 'success_time' => time()]);
                if (!$res) {
                    DB::rollBack();
                    return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
                }
            }
            DB::commit();
            return response()->json(['data' => [], 'msg' => ' 修改订单状态成功', 'code' => 200]);
        } catch (QueryException $ex) {
            DB::rollBack();
            return response()->json(['data' => [], 'msg' => '修改订单状态失败', 'code' => 401]);
        }
    }

    /**
     * 获取订单列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getPaySet(Request $request)
    {
        $set = new DispenSetting();
        $data = $set->getSet();
        return response()->json(['data' => $data, 'msg' => '获取出款设置成功', 'code' => 200]);
    }

    /**
     * 获取订单列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setPaySet(Request $request)
    {
        $this->validate($request, [
            'min_amount' => 'required|integer|min:0',
            'max_amount' => 'required|integer|min:0',
            'day_amount' => 'required|integer|min:0',
            'day_time' => 'required|integer|min:0',
            'cost' => 'required|integer|min:0',
            'cost_rate' => 'required|integer|min:0|max:1000',
            'type' => ['required', Rule::in([1, 2])],
            'status' => ['required', Rule::in([1, 2])],
        ]);
        $input = request(['min_amount', 'max_amount', 'day_amount', 'day_time', 'cost', 'cost_rate', 'type', 'status',]);
        $set = new DispenSetting();
        $res = $set->setSet($input);
        if ($res) {
            return response()->json(['data' => null, 'msg' => '修改出款设置成功', 'code' => 200]);
        }
        return response()->json(['data' => null, 'msg' => '修改出款设置失败', 'code' => 200]);
    }
}
