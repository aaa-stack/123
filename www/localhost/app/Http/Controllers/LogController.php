<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\OrderLog;
use App\Models\MerchantLog;

class LogController extends Controller
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
     * 获取日志列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getLogList(Request $request)
    {
        $this->validate($request, [
            'type' => [Rule::in([1, 2])],
            'stime' => 'date_format:"Y-m-d H:i:s"',
            'etime' => 'date_format:"Y-m-d H:i:s"',
            'userId' => 'numeric|min:0',
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);
        $input = request(['stime', 'etime', 'userId']);
        $type = request('type');
        $page = request('page');
        $num = request('num');
        if ($mer = auth('merchants')->user()) {
            $input['userId'] = $mer->merchant_no;
            $Log = new MerchantLog();
            $data = $Log->getLog($input, $page, $num);
            return response()->json(['data' => $data, 'msg' => '获取日志成功', 'code' => 200]);
        }
        if ($type == 2) {
            $Log = new Log();
            $data = $Log->getLog($input, $page, $num);
        } else {
            $Log = new MerchantLog();
            $data = $Log->getLog($input, $page, $num);
        }
        return response()->json(['data' => $data, 'msg' => '获取日志成功', 'code' => 200]);
    }

    /**
     * 获取拉起订单日志列表
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getOrderLog(Request $request)
    {
        $this->validate($request, [
            'stime' => 'date_format:"Y-m-d H:i:s"',
            'etime' => 'date_format:"Y-m-d H:i:s"',
            'userId' => 'numeric|min:0',
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);
        $input = request(['stime', 'etime', 'userId']);
        $page = request('page');
        $num = request('num');

        $Log = new OrderLog();
        $data = $Log->getLog($input, $page, $num);

        return response()->json(['data' => $data, 'msg' => '获取日志成功', 'code' => 200]);
    }
}
