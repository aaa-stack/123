<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\ChannelSet;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ProductsController extends Controller
{

    /**
     * 获取支付产品列表
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getProductsList()
    {
        $products = new Products();
        $data = $products->getProductsList();
        foreach ($data as $k => &$v) {
            $v['pay_type_zh'] = PAY_TYPE[$v['pay_type']];
        }
        return response()->json([
            'code' => 200,
            'msg' => "获取产品列表成功",
            'data' => $data
        ]);
    }

    /**
     * 获取支付产品信息
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getProductById(Request $request)
    {
        $this->validate($request, [
            'productId' => 'required|integer|min:0',
        ]);
        $productId = request('productId');
        $products = new Products();
        $data = $products->getProductById($productId);
        return response()->json([
            'code' => 200,
            'msg' => "获取产品信息成功",
            'data' => $data
        ]);
    }

    /**
     * 获取支付产品信息
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getPayTypeList(Request $request)
    {
        return response()->json([
            'code' => 200,
            'msg' => "获取支付类型成功",
            'data' => PAY_TYPE
        ]);
    }

    /**
     * 设置支付产品status
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setProductStatus(Request $request)
    {
        $this->validate($request, [
            'productId' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['1', '2'])],
        ]);

        $productId = request('productId');
        $status = request('status');

        $products = new Products();
        $hasPro = $products->hasProduct($productId);
        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "产品id有误",
                'data' => []
            ]);
        }
        $res = $products->setProductStatus($productId, $status);
        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "修改产品状态失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "修改产品状态成功",
            'data' => []
        ]);
    }

    /**
     * 设置支付产品client
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setProductClient(Request $request)
    {
        $this->validate($request, [
            'productId' => 'required|integer|min:0',
            'client' => ['required', Rule::in(['1', '2'])],
        ]);

        $productId = request('productId');
        $client = request('client');

        $products = new Products();
        $hasPro = $products->hasProduct($productId);
        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "产品id有误",
                'data' => []
            ]);
        }
        $res = $products->setProductClient($productId, $client);
        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "修改产品客户端状态失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "修改产品客户端状态成功",
            'data' => []
        ]);
    }

    /**
     * 添加支付产品
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addProduct(Request $request)
    {
        $this->validate($request, [
            'client' => ['required', Rule::in(['1', '2'])],
            'product_name' => 'required|max:20',
            'class' => 'required|max:20',
            'status' => ['required', Rule::in(['1', '2'])],
            'mode' => ['required', Rule::in(['1', '2'])],
            'pay_type' => ['required', Rule::in(array_keys(PAY_TYPE))],
        ]);
        $credentials = request(['client', 'product_name', 'status', 'mode', 'pay_type', 'class']);
        $products = new Products();
        $hasPro = $products->hasProductName($credentials['product_name']);
        if ($hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "支付产品已存在",
                'data' => []
            ]);
        }
        $res = $products->addProduct($credentials);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "添加支付产品失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "添加支付产品成功",
            'data' => []
        ]);
    }

    /**
     * del支付产品
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function delProduct(Request $request)
    {
        $this->validate($request, [
            'productId' => 'required|integer|min:0',
        ]);

        $productId = request('productId');
        $products = new Products();
        $hasPro = $products->hasProduct($productId);

        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "支付产品不存在",
                'data' => []
            ]);
        }
        $res = $products->delProduct($productId);

        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "删除支付产品失败",
                'data' => []
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg' => "删除支付产品成功",
            'data' => []
        ]);
    }

    /**
     * 修改支付产品
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editProduct(Request $request)
    {
        $this->validate($request, [
            "productId" => "required|integer|min:0",
            'client' => ['required', Rule::in(['1', '2'])],
            'product_name' => 'required|max:20',
            'class' => 'required|max:20',
            'status' => ['required', Rule::in(['1', '2'])],
            'mode' => ['required', Rule::in(['1', '2'])],
            'pay_type' => ['required', Rule::in(array_keys(PAY_TYPE))],
        ]);

        $productId = request('productId');
        $credentials = request(['client', 'product_name', 'status', 'mode', 'pay_type', 'class']);
        $products = new Products();
        $hasPro = $products->hasProduct($productId);

        if (!$hasPro) {
            return response()->json([
                'code' => 401,
                'msg' => "支付产品不存在",
                'data' => []
            ]);
        }
        $res = $products->editProduct($productId, $credentials);

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
     * 获取支付产品列表
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getProList()
    {
        $products = new Products();
        $data = $products->getProList();
        return response()->json([
            'code' => 200,
            'msg' => "获取产品列表成功",
            'data' => $data
        ]);
    }

    /**
     * 获取merchant支付产品列表
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getMerProList()
    {
        $products = new ChannelSet();
        $merId = auth('merchants')->user()->merchant_no;
        $data = $products->getMerProList($merId);
        return response()->json([
            'code' => 200,
            'msg' => "获取产品列表成功",
            'data' => $data
        ]);
    }
}
