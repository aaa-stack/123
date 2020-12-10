<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelSet;
use App\Models\Products;
use App\Models\Merchant;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ChannelSetController extends Controller
{
    /**
     * 获取统一通道设置
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getSetList(Request $request)
    {
        $chanSet = new ChannelSet();
        $data = $chanSet->getSetList();
        return response()->json([
            'code' => 200,
            'msg' => "获取支付通道成功",
            'data' => $data
        ]);
    }

    /**
     * 获取统一费率设置
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getAllCost(Request $request)
    {
        $chanSet = new ChannelSet();
        $data = $chanSet->getAllCost();
        return response()->json([
            'code' => 200,
            'msg' => "获取支付通道成功",
            'data' => $data
        ]);
    }

    /**
     * 获取用户费率列表
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getCostList(Request $request)
    {
        $this->validate($request, [
            'merchant_no' => 'required|numeric',
        ]);
        $merNO = request('merchant_no');
        $chanSet = new ChannelSet();
        $data = $chanSet->getCostList($merNO);
        return response()->json([
            'code' => 200,
            'msg' => "获取支付通道成功",
            'data' => $data
        ]);
    }

    /**
     * 获取用户设置
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUserSet(Request $request)
    {
        $this->validate($request, [
            'merchant_no' => 'required|numeric',
        ]);
        $merNO = request('merchant_no');
        $chanSet = new ChannelSet();
        $data = $chanSet->getUserSet($merNO);
        if(empty($data)){
            return response()->json([
                'code' => 401,
                'msg' => "无法设置",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "获取支付通道成功",
            'data' => $data
        ]);
    }

    /**
     * 获取用户费率列表
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setCost(Request $request)
    {
        $this->validate($request, [
            'data' => 'required|array',
            'data.*.product_id' => 'required|integer|min:0',
            'data.*.rate' => 'required|integer|min:0|max:1000',
            'merchant_no' => 'numeric|min:0',
        ]);
        $product = new Products();
        $product_arr = $product->getProIdList();
        $data  = request('data');
        $merNo  = request('merchant_no');
        if (!empty($merNo)) {
            $where[0] = ['merchant_no', '=', $merNo];
        }
        DB::beginTransaction();
        try {
            foreach ($data as $k => $v) {
                if (in_array($v['product_id'], $product_arr)) {
                    $where[1] = ['product_id', '=', $v['product_id']];
                    Db::table('channel_set')->where($where)->update(['rate' => $v['rate']]);
                }
            }
            DB::commit();
            return response()->json([
                'code' => 200,
                'msg' => "修改成功",
                'data' => []
            ]);
        } catch (QueryException $ex) {
            DB::rollBack();
            return response()->json([
                'code' => 401,
                'msg' => "修改失败",
                'data' => []
            ]);
        }
    }

    /**
     * 统一修改通道
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setChannel(Request $request)
    {
        $this->validate($request, [
            'data' => 'required|array',
            'data.*.product_id' => 'required|integer|min:1',
            'data.*.status' => ['required', Rule::in(['1', '2'])],
            'data.*.mode' => ['required', Rule::in(['1', '2'])],
            'data.*.channel_id' => ['integer', 'min:1'],
            'data.*.channel_arr' => ['array'],
            'data.*.channel_arr.*.channel_id' => ['integer', 'min:1'],
            'data.*.channel_arr.*.weight' => ['integer', 'min:0', 'max:10'],
            'merchant_no' => "numeric"
        ]);
        $data = request('data');
        $merchant_no = request('merchant_no');
        $where = [];
        if (isset($merchant_no) && !empty($merchant_no)) {
            $mer = new Merchant();
            if ($mer->hasMer($merchant_no)) {
                $where[] = ['merchant_no', '=', $merchant_no];
            }
        }
        $product = new Products();
        $product_arr = $product->getProIdList();
        $channel = new Channel();

        foreach ($data as $k => $v) {
            if (!in_array($v['product_id'], $product_arr)) {
                return response()->json(['code' => 401, 'msg' => "参数错误1", 'data' => []]);
            }
            if ($v['mode'] == 1) {
                if (!isset($v['channel_id']) || empty($v['channel_id'])) {
                    Db::table("channel_set")->where('product_id', '=', $v['product_id'])->where($where)->update(['status' => $v['status'], 'mode' => $v['mode'], 'channel_id' => '', 'weight' => '']);
                    continue;
                }
                if (!isId($v['channel_id']) || !$res = Db::table('channels')->where([['id', '=', $v['channel_id']], ['status', '=', 1]])->exists()) {
                    return response()->json(['code' => 401, 'msg' => "参数错误2", 'data' => []]);
                }
                Db::table("channel_set")->where('product_id', '=', $v['product_id'])->where($where)->update(['status' => $v['status'], 'mode' => $v['mode'], 'channel_id' => $v['channel_id'], 'weight' => '']);
            }

            if ($v['mode'] == 2) {
                if (!isset($v['channel_arr']) || empty($v['channel_arr'])) {
                    Db::table("channel_set")->where('product_id', '=', $v['product_id'])->where($where)->update(['status' => $v['status'], 'mode' => $v['mode'], 'channel_id' => '', 'weight' => '']);
                    continue;
                }
                $chanIdArr = array_column($v['channel_arr'], 'id');
                $weightArr = array_column($v['channel_arr'], 'weight');
                foreach($weightArr as $k1 => $v1){
                    if(!isId($v1)){
                        return response()->json(['code' => 401, 'msg' => "参数错误", 'data' => []]);
                    }
                }
                if (count($chanIdArr) == 0 || count($chanIdArr) != count($weightArr)) {
                    return response()->json(['code' => 401, 'msg' => "参数错误", 'data' => []]);
                }
                $chanArr = $channel->getChanId($v['product_id']);
                $diff = array_diff($chanIdArr, $chanArr);
                if (!empty($diff)) {
                    return response()->json(['code' => 401, 'msg' => "参数错误", 'data' => []]);
                }
                $chanIdStr = implode(',', $chanIdArr);
                $weightStr = implode(',', $weightArr);
                Db::table("channel_set")->where('product_id', '=', $v['product_id'])->where($where)->update(['status' => $v['status'], 'mode' => $v['mode'], 'channel_id' => $chanIdStr, 'weight' => $weightStr]);
                continue;
            }
        }
        return response()->json([
            'code' => 200,
            'msg' => "设置通道成功",
            'data' => []
        ]);
    }
}
