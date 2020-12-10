<?php

namespace App\Jobs;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CommissionJob extends Job
{
    private $orderId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->orderId = $param;
    }

    /**
     * 给下游回调的异步任务
     *
     * @return void
     */
    public function handle()
    {
        $orderId = $this->orderId;
        DB::beginTransaction();
        try {
            $order = Db::table('orders')
                ->leftJoin('merchants', 'orders.merchant_no', '=', 'merchants.merchant_no')
                ->select(['orders.merchant_no', 'orders.pay_orderid', 'orders.amount', 'orders.success_time', 'orders.pay_bank', 'orders.pay_channel', 'merchants.agent_id'])
                ->where('pay_orderid', '=', $orderId)
                ->first();
            $set = Db::table('channel_set')
                ->leftJoin('merchants', 'channel_set.merchant_no', '=', 'merchants.merchant_no')
                ->select(['channel_set.rate', 'merchants.agent_id', 'merchants.account', 'merchants.merchant_no'])
                ->where([['merchants.id', '=', $order->agent_id], ['channel_set.product_id', '=', $order->pay_bank]])
                ->first();
            $rate = $set->rate;
            $edit_amount = $order->amount * $rate / 1000;
            $water[] = [
                'pay_orderid' => $order->pay_orderid,
                'merchant_no' => $set->merchant_no,
                'old_amount' => $set->account,
                'edit_amount' => $edit_amount,
                'new_amount' => $set->account + $edit_amount,
                'time' => $order->success_time,
                'channel_id' => $order->pay_channel,
                'type' => 6,
                'product_id' => $order->pay_bank,
                'notice' => date("Y-m-d H:i:s", $order->success_time) . "提成"
            ];
            if ($set->agent_id) {
                $set1 = Db::table('channel_set')
                    ->leftJoin('merchants', 'channel_set.merchant_no', '=', 'merchants.merchant_no')
                    ->select(['channel_set.rate', 'merchants.agent_id', 'merchants.account', 'merchants.merchant_no'])
                    ->where([['merchants.id', '=', $set->agent_id], ['channel_set.product_id', '=', $order->pay_bank]])
                    ->first();
                $rate1 = $set1->rate;
                $edit_amount1 = $order->amount * $rate / 1000 * $rate1 / 1000;
                $edit_amount = $edit_amount - $edit_amount1;
                $water[0]['edit_amount'] = $edit_amount;
                $water[0]['new_amount'] = $set->account + $edit_amount;
                $water[] = [
                    'pay_orderid' => $order->pay_orderid,
                    'merchant_no' => $set1->merchant_no,
                    'old_amount' => $set1->account,
                    'edit_amount' => $edit_amount1,
                    'new_amount' => $set1->account + $edit_amount1,
                    'time' => $order->success_time,
                    'channel_id' => $order->pay_channel,
                    'type' => 6,
                    'product_id' => $order->pay_bank,
                    'notice' => date("Y-m-d H:i:s", $order->success_time) . "提成"
                ];
            } else {
                Db::table('merchants')->where('id', '=', $order->agent_id)->update(['account' => $set->account + $edit_amount]);
                Db::table('merchants_water')->insert($water);
                DB::commit();
                return;
            }
            if ($set1->agent_id) {
                $set2 = Db::table('channel_set')
                    ->leftJoin('merchants', 'channel_set.merchant_no', '=', 'merchants.merchant_no')
                    ->select(['channel_set.rate', 'merchants.account', 'merchants.merchant_no'])
                    ->where([['merchants.id', '=', $set1->agent_id], ['channel_set.product_id', '=', $order->pay_bank]])
                    ->first();
                $rate2 = $set2->rate;
                $edit_amount2 = $order->amount * $rate / 1000 * $rate1 / 1000 * $rate2 / 1000;
                $edit_amount1 = $edit_amount1 - $edit_amount2;
                $water[1]['edit_amount'] = $edit_amount1;
                $water[1]['new_amount'] = $set1->account + $edit_amount1;
                $water[] = [
                    'pay_orderid' => $order->pay_orderid,
                    'merchant_no' => $set2->merchant_no,
                    'old_amount' => $set2->account,
                    'edit_amount' => $edit_amount2,
                    'new_amount' => $set2->account + $edit_amount2,
                    'time' => $order->success_time,
                    'channel_id' => $order->pay_channel,
                    'type' => 6,
                    'product_id' => $order->pay_bank,
                    'notice' => date("Y-m-d H:i:s", $order->success_time) . "提成"
                ];
            } else {
                Db::table('merchants')->where('id', '=', $order->agent_id)->update(['account' => $set->account + $edit_amount]);
                Db::table('merchants')->where('id', '=', $set->agent_id)->update(['account' => $set1->account + $edit_amount1]);
                Db::table('merchants_water')->insert($water);
                DB::commit();
                return;
            }
            Db::table('merchants')->where('id', '=', $order->agent_id)->update(['account' => $set->account + $edit_amount]);
            Db::table('merchants')->where('id', '=', $set->agent_id)->update(['account' => $set1->account + $edit_amount1]);
            Db::table('merchants')->where('id', '=', $set1->agent_id)->update(['account' => $set2->account + $edit_amount2]);
            Db::table('merchants_water')->insert($water);
            DB::commit();
        } catch (QueryException $ex) {
            DB::rollBack();
        }
    }
}
