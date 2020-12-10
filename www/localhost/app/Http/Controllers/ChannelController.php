<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Products;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    /**
     * 获取支付通道列表
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getChannelList(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|numeric|min:0',
            'num' => 'required|numeric|min:1|max:100',
        ]);

        $credentials = request(['page', 'num']);

        $channel = new Channel();
        $data = $channel->getChannelList($credentials['page'], $credentials['num']);
        return response()->json([
            'code' => 200,
            'msg' => "获取支付通道成功",
            'data' => $data
        ]);
    }

    /**
     * 获取支付产品信息
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getChannelById(Request $request)
    {
        $this->validate($request, [
            'channelId' => 'required|integer|min:0',
        ]);
        $channelId = request('channelId');
        $channels = new Channel();
        $data = $channels->getChannelById($channelId);
        return response()->json([
            'code' => 200,
            'msg' => "获取通道信息成功",
            'data' => $data
        ]);
    }

    /**
     * 设置通道status
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setChannelStatus(Request $request)
    {
        $this->validate($request, [
            'channelId' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['1', '2'])],
        ]);

        $channelId = request('channelId');
        $status = request('status');

        $channel = new Channel();
        $hasChannel = $channel->hasChannel($channelId);
        if (!$hasChannel) {
            return response()->json([
                'code' => 401,
                'msg' => "通道id有误",
                'data' => []
            ]);
        }
        $res = $channel->editChannel($channelId, ['status' => $status]);
        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "修改通道状态失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "修改通道状态成功",
            'data' => []
        ]);
    }

    /**
     * 添加支付通道
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addChannel(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:20',
            'class' => 'required|max:20',
            'product_id' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['1', '2'])],
            'rate' => 'required|integer|min:0|max:100',
            'gateway' => 'required|max:255',
            'pagereturn' => 'required|max:255',
            'serverreturn' => 'required|max:255',
        ]);
        $credentials = request(['name', 'class', 'product_id', 'status', 'rate', 'gateway', 'pagereturn', 'serverreturn']);
        $Channel = new Channel();
        $hasChan = $Channel->hasChannelName($credentials['name']);
        if ($hasChan) {
            return response()->json([
                'code' => 401,
                'msg' => "通道已存在",
                'data' => []
            ]);
        }

        $products = new Products();
        $hasPro = $products->hasProduct($credentials['product_id']);

        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "支付产品不存在",
                'data' => []
            ]);
        }
        $res = $Channel->addChannel($credentials);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "添加支付通道失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "添加支付通道成功",
            'data' => []
        ]);
    }

    /**
     * del支付通道
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function delChannel(Request $request)
    {
        $this->validate($request, [
            'channelId' => 'required|integer|min:0',
        ]);

        $channelId = request('channelId');
        $channel = new Channel();
        $hasPro = $channel->hasChannel($channelId);

        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "支付通道不存在",
                'data' => []
            ]);
        }
        $res = $channel->delChannel($channelId);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "删除支付通道失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "删除支付通道成功",
            'data' => []
        ]);
    }

    /**
     * 修改支付通道
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editChannel(Request $request)
    {
        $this->validate($request, [
            'channelId' => 'required|integer|min:0',
            'name' => 'required|max:20',
            'class' => 'required|max:20',
            'product_id' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['1', '2'])],
            'rate' => 'required|integer|min:0|max:100',
            'gateway' => 'required|max:255',
            'pagereturn' => 'required|max:255',
            'serverreturn' => 'required|max:255',
        ]);

        $channelId = request('channelId');
        $credentials = request(['name', 'class', 'product_id', 'status', 'rate', 'gateway', 'pagereturn', 'serverreturn']);

        $Channel = new Channel();

        $products = new Products();
        $hasPro = $products->hasProduct($credentials['product_id']);

        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "支付产品不存在",
                'data' => []
            ]);
        }

        $res = $Channel->editChannel($channelId, $credentials);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "修改支付产品失败",
                'data' => []
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg' => "修改支付产品成功",
            'data' => []
        ]);
    }

    /**
     * 获取支付通道列表
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getChanList(Request $request)
    {
        $channel = new Channel();
        $data = $channel->getChanList();
        return response()->json([
            'code' => 200,
            'msg' => "获取支付通道成功",
            'data' => $data
        ]);
    }
}
