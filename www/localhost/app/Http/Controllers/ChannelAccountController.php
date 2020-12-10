<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChanAccount;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ChannelAccountController extends Controller
{
    /**
     * 获取子账号列表
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getAccountList(Request $request)
    {
        $this->validate($request, [
            'channelId' => 'required|integer|min:0',
        ]);
        $channelId = request('channelId');
        $chanAccount = new ChanAccount();
        $data = $chanAccount->getAccountList($channelId);
        return response()->json([
            'code' => 200,
            'msg' => "获取子账号成功",
            'data' => $data
        ]);
    }

    /**
     * 获取子账号信息
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getAccountById(Request $request)
    {
        $this->validate($request, [
            'accountId' => 'required|integer|min:0',
        ]);
        $accountId = request('accountId');
        $Account = new ChanAccount();
        $data = $Account->getAccountById($accountId);
        return response()->json([
            'code' => 200,
            'msg' => "获取子账号信息成功",
            'data' => $data
        ]);
    }

    /**
     * 设置子账号status
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setAccountStatus(Request $request)
    {
        $this->validate($request, [
            'accountId' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['1', '2'])],
        ]);

        $accountId = request('accountId');
        $status = request('status');

        $Account = new ChanAccount();
        $hasAcc = $Account->hasAccount($accountId);
        if (!$hasAcc) {
            return response()->json([
                'code' => 401,
                'msg' => "子账号id有误",
                'data' => []
            ]);
        }
        $res = $Account->editAccount($accountId, ['status' => $status]);
        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "修改子账号状态失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "修改子账号状态成功",
            'data' => []
        ]);
    }

    /**
     * 添加子账号
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addAccount(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:20',
            'merchant_no' => 'required|max:255',
            'key' => 'required|max:255',
            'weight' => 'required|max:255',
            'status' =>  ['required', Rule::in(['1', '2'])],
            'channel_id' => 'required|integer|min:0',
        ]);
        $credentials = request(['name', 'merchant_no', 'key', 'weight', 'status', 'channel_id']);
        $channel = new Channel();
        $hasChannel = $channel->hasChannel($credentials['channel_id']);
        if (!$hasChannel) {
            return response()->json([
                'code' => 401,
                'msg' => "通道id有误",
                'data' => []
            ]);
        }

        $Account = new ChanAccount();
        $hasACcc = $Account->hasName($credentials['name']);

        if ($hasACcc) {
            return response()->json([
                'code' => 401,
                'msg' => "子账号已存在",
                'data' => []
            ]);
        }
        $res = $Account->addAccount($credentials);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "添加子账号失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "添加子账号成功",
            'data' => []
        ]);
    }

    /**
     * del子账号
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function delAccount(Request $request)
    {
        $this->validate($request, [
            'accountId' => 'required|integer|min:0',
        ]);

        $accountId = request('accountId');
        $Account = new ChanAccount();
        $hasPro = $Account->hasAccount($accountId);

        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "子账号不存在",
                'data' => []
            ]);
        }
        $res = $Account->delAccount($accountId);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "删除子账号失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "删除子账号成功",
            'data' => []
        ]);
    }

    /**
     * 修改子账号
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editAccount(Request $request)
    {
        $this->validate($request, [
            'accountId' => 'required|integer|min:0',
            'name' => 'required|max:20',
            'merchant_no' => 'required|max:255',
            'key' => 'required|max:255',
            'weight' => 'required|max:255',
            'status' =>  ['required', Rule::in(['1', '2'])],
        ]);

        $accountId = request('accountId');
        $credentials = request(['name', 'merchant_no', 'key', 'weight', 'status']);

        $Account = new ChanAccount();
        // $hasAcc = $Account->hasName($credentials['name']);
        // if($hasAcc){
        //     return response()->json([
        //         'code'=>401,
        //         'msg'=>"子账号已存在",
        //         'data'=>[]
        //     ]);
        // }

        $Account = new ChanAccount();
        $hasAccount = $Account->hasAccount($accountId);

        if (!$hasAccount) {
            return response()->json([
                'code' => 401,
                'msg' => "子账号不存在",
                'data' => []
            ]);
        }

        $res = $Account->editAccount($accountId, $credentials);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "修改子账号失败",
                'data' => []
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg' => "修改子账号成功",
            'data' => []
        ]);
    }
}
