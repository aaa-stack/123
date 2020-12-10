<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\MerchantLog;
use App\Models\MerchantsCards;
use Illuminate\Validation\Rule;
use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Jobs\NotifyJob;

class MerchantController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login(Request $request)
    {
        $this->validate($request, [
            'merchant_no' => 'required|numeric',
            'password' => 'required|min:5|max:20',
            'secret' => 'required|numeric|min:0',
        ]);

        $credentials = request(['merchant_no', 'password']);
        $secret = request('secret');

        if (!$token = auth('merchants')->attempt($credentials)) {
            return response()->json(['data' => [], 'msg' => '用户信息验证失败', 'code' => 401]);
        }

        $user = json_decode(auth('merchants')->user(), true);

        if(empty($user['last_time'])){
            if($secret !="123456"){
                return response()->json(['data' => [], 'msg' => '谷歌验证失败', 'code' => 401]);
            }
        }else{
            if (!GoogleAuthenticator::CheckCode($user['secret'], $secret)) {
                return response()->json(['data' => [], 'msg' => '谷歌验证失败', 'code' => 401]);
            }
        }
        if ($user['status'] != 1) {
            return response()->json(['data' => [], 'msg' => '用户被禁用', 'code' => 401]);
        }
        $userModel = new Merchant();
        $res = $userModel->setLastIp($user['id'], $request->ip());
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '数据库修改失败', 'code' => 401]);
        }
        $data = [
            'username' => $user['username'],
            'user_id' => $user['id'],
            'param' => "",
            'ip' => $request->getClientIp(),
            'time' => time(),
            'path' => $request->path(),
            'method' => $request->method(),
        ];
        $log = new MerchantLog();
        $log->addLog($data);
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo()
    {
        return response()->json(['data' => auth('merchants')->user(), 'msg' => '用户信息请求成功', 'code' => 200]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('merchants')->logout();

        return response()->json(['data' => [], 'msg' => '退出登录成功', 'code' => 200]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('merchants')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */

    protected function respondWithToken($token)
    {
        return response()->json([
            'code' => 200,
            'msg' => "登陆成功",
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('merchants')->factory()->getTTL() * 60
            ]
        ]);
    }

    /**
     * 通过商户id查询商户信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUserInfoById(Request $request)
    {
        $this->validate($request, [
            'userId' => 'required|integer|min:0',
        ]);

        $userId = request('userId');

        $userModel = new Merchant();
        $agent_id = 0;
        if ($mer = auth('merchants')->user()) {

            if ($mer->type == 1) {
                return response()->json(['data' => null, 'msg' => '权限不足', 'code' => 401]);
            } else {
                $agent_id = $mer->id;
            }
        }
        $data = $userModel->getUserInfoById($userId, $agent_id);

        if (!$data) {
            return response()->json(['data' => [], 'msg' => '商户信息有误', 'code' => 401]);
        }

        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * 获取商户列表.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserList(Request $request)
    {
        $this->validate($request, [
            'merchant_no' => 'numeric',
            'username' => 'max:10',
            'status' => [Rule::in(['1', '2'])],
            'type' => [Rule::in(['1', '2', '3', '4'])],
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);

        $where = request(['merchant_no', 'username', 'status', 'type']);
        $page = request('page');
        $num = request('num');

        if (auth('merchants')->user()) {
            if (auth('merchants')->user()->type == 1) {
                return response()->json(['data' => null, 'msg' => '权限不足', 'code' => 401]);
            } else {
                $where['agent_id'] = auth('merchants')->user()->id;
            }
        }
        $Merchant = new Merchant();
        $data = $Merchant->getUserList($where, $page, $num);

        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * 添加商户
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addUser(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|min:4|max:10|alpha_dash',
            'password' => 'required|min:4|max:15|alpha_dash',
            'repass' => 'required|min:4|max:15|alpha_dash',
            'avatar' => 'max:255',
            'email' => 'email|max:255',
            'phone_num' => 'regex:/^1[345789][0-9]{9}$/',
            'status' => ['required', Rule::in(['1', '2'])],
            'type' => ['required', Rule::in(['1', '2', '3', '4'])],
            'recharge' => ['required', Rule::in(['1', '2'])],
        ]);
        $credentials = request(['username', 'password', 'avatar', 'status', 'repass', 'email', 'phone_num', 'type', 'recharge']);
        if ($credentials['password'] !== $credentials['repass']) {
            return response()->json(['data' => [], 'msg' => '两次密码不一致', 'code' => 401]);
        }
        unset($credentials['repass']);
        $userModel = new Merchant();
        if ($userModel->hasUsername($credentials['username'])) {
            return response()->json(['data' => [], 'msg' => '用户名已存在', 'code' => 401]);
        }

        if (auth('merchants')->user()) {
            $merchant = auth('merchants')->user();
            $credentials['agent_id'] = $merchant->id;
            if ($merchant->type == 1 || $merchant->type <= $credentials['type']) {
                return response()->json(['data' => [], 'msg' => '权限不足', 'code' => 401]);
            }
        }
        $credentials['created_time'] = time();
        $credentials['last_time'] = 0;
        $credentials['password'] = password_hash($credentials['password'], PASSWORD_DEFAULT);
        $credentials['merchant_no'] = GenerateUniqueNumber();
        $credentials['key'] = GenerateMd5Key($credentials['merchant_no'], $credentials['username']);
        $credentials['account'] = 0;
        $google = GoogleAuthenticator::CreateSecret();
        $credentials['secret'] = $google['secret'];
        $credentials['pay_secret'] = md5(123456);
        if ($credentials['type'] > 1) {
            $credentials['recharge'] = 2;
        }
        $res = $userModel->addMerchant($credentials);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '添加失败', 'code' => 200]);
        }
        return response()->json(['data' => [], 'msg' => '添加成功', 'code' => 200]);
    }

    /**
     * edit 商户
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editUser(Request $request)
    {
        $this->validate($request, [
            'merchantId' => 'required|numeric',
            'avatar' => 'max:255',
            'email' => 'email|max:255',
            'phone_num' => 'regex:/^1[345789][0-9]{9}$/',
            'status' => ['required', Rule::in(['1', '2'])],
            'recharge' => ['required', Rule::in(['1', '2'])],
        ]);

        $credentials = request(['avatar', 'status', 'email', 'phone_num', 'recharge']);
        $userId = request('merchantId');

        $userModel = new Merchant();
        //判断是否是代理登录
        if (auth('merchants')->user()) {
            $mer = auth('merchants')->user();
            if ($mer->type == 1) {
                return response()->json(['data' => [], 'msg' => '权限不足', 'code' => 401]);
            } else {
                //判断商户类型 如果是代理  那么他的充值状态不能修改一直为2
                $child = DB::table('merchants')->select(['type'])->where([['agent_id', '=', $mer->id], ['id', '=', $userId]])->first();
                if (empty($child)) {
                    return response()->json(['data' => [], 'msg' => '用户不存在', 'code' => 401]);
                }
                if ($child->type > 1) {
                    $credentials['recharge'] = 2;
                }
            }
        } else {
            //判断商户类型 如果是代理  那么他的充值状态不能修改一直为2
            $type = $userModel->getUserType($userId);
            if (empty($type)) {
                return response()->json(['data' => [], 'msg' => '用户不存在', 'code' => 401]);
            }
            if ($type[0] > 1) {
                $credentials['recharge'] = 2;
            }
        }
        $res = $userModel->editUserInfo($userId, $credentials);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改失败', 'code' => 401]);
        }
        return response()->json(['data' => [], 'msg' => '修改成功', 'code' => 200]);
    }

    /**
     * 重新生成商户密钥
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function resetKey(Request $request)
    {
        $user = json_decode(auth('merchants')->user(), true);

        $merModel = new Merchant();
        $newKey = GenerateMd5Key($user['id'], $user['merchant_no']);

        $data['key'] = $newKey;
        $res = $merModel->editUserInfo($user['id'], $data);

        if (!$res) {
            return response()->json(['data' => [], 'msg' => '重新生成商户密钥失败', 'code' => 200]);
        }
        return response()->json(['data' => [], 'msg' => '重新生成商户密钥成功', 'code' => 200]);
    }

    /**
     * 重新生成google验证器密钥
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function google()
    {
        $user = auth('merchants')->user();
        $google['secret'] = $user['secret'];
        $google_url = "otpauth://totp/?secret=" . $user['secret'];
        // 生成二维码
        $google["qrcode"] = QrCode::encoding('UTF-8')->size(180)->margin(1)->generate($google_url);
        return response()->json(['data' => $google, 'msg' => '获取google验证信息成功', 'code' => 200]);
    }

    /**
     * 获取google验证器信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetGoogle()
    {
        $user = auth('merchants')->user();
        $google = GoogleAuthenticator::CreateSecret();
        // 生成二维码
        $userModel = new Merchant();
        $res = $userModel->editUserInfo($user->id, ['secret' => $google['secret']]);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '重置谷歌验证器密钥失败', 'code' => 401]);
        }
        return response()->json(['data' => [], 'msg' => '重置谷歌验证器密钥成功', 'code' => 200]);
    }

    /**
     * 修改商户余额
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editAmount(Request $request)
    {
        $this->validate($request, [
            'merchantId' => 'required|numeric',
            'money' => 'required|integer|min:0',
            'notice' => 'required|max:255',
            'type' => ['required', Rule::in(['1', '2'])],
        ]);
        $merId = request('merchantId');
        $money = request('money');
        $notice = request('notice');
        $type = request('type');
        DB::beginTransaction();
        try {
            $mer = DB::table("merchants")->select(["merchant_no", "account"])->where('id', '=', $merId)->first();
            if (empty($mer)) {
                DB::rollBack();
                return response()->json(['data' => [], 'msg' => '用户信息有误', 'code' => 401]);
            }
            if ($type == 2) {
                $new_amount = $mer->account - $money;
                if ($new_amount < 0) {
                    return response()->json(['data' => [], 'msg' => '余额不足', 'code' => 401]);
                }
            } else {
                $new_amount = $mer->account + $money;
            }
            $res = DB::table("merchants")->where([['id', '=', $merId], ['account', '=', $mer->account]])->update(['account' => $new_amount]);
            if (!$res) {
                DB::rollBack();
                return response()->json(['data' => [], 'msg' => '修改用户余额失败', 'code' => 401]);
            }
            $water = [
                'pay_orderid' => 0,
                'merchant_no' => $mer->merchant_no,
                'old_amount' => $mer->account,
                'edit_amount' => $money,
                'new_amount' => $mer->account + $money,
                'time' => time(),
                'channel_id' => 0,
                'notice' => $notice
            ];
            if ($type == 2) {
                $water['type'] = 4;
            } else {
                $water['type'] = 3;
            }
            $res1 = Db::table('merchants_water')->insert($water);
            if (!$res1) {
                DB::rollBack();
                return response()->json(['data' => [], 'msg' => '添加流水订单失败', 'code' => 401]);
            }
            DB::commit();
            return response()->json(['data' => [], 'msg' => '修改商户余额成功', 'code' => 200]);
        } catch (QueryException $ex) {
            DB::rollBack();
            return response()->json(['data' => [], 'msg' => '修改商户余额失败', 'code' => 401]);
        }
    }

    /**
     * 获取银行卡列表.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCardList(Request $request)
    {
        $mer = auth('merchants')->user();
        $where[] = ['merchant_id', '=', $mer->id];
        $Merchant = new MerchantsCards();
        $data = $Merchant->getCardList($where);

        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * 获取银行卡信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCardInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer|min:0',
        ]);
        $cardId = request('id');
        $mer = auth('merchants')->user();
        $where = [['merchant_id', '=', $mer->id], ['id', '=', $cardId]];
        $Merchant = new MerchantsCards();
        $data = $Merchant->getCardById($where);
        if (empty($data)) {
            return response()->json(['data' => [], 'msg' => 'faild', 'code' => 401]);
        }
        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * 修改银行卡信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editCard(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer|min:0',
            'card_num' => 'required|numeric',
            'bank_id' => 'required|integer|min:0',
            'branch_name' => 'required|max:255',
            'province' => 'required|max:255',
            'city' => 'required|max:255',
            'name' => 'required|max:255',
        ]);
        $cardId = request('id');
        $input = request(['card_num', 'bank_id', 'branch_name', 'province', 'city', 'name']);
        $mer = auth('merchants')->user();
        $where = [['merchant_id', '=', $mer->id], ['id', '=', $cardId]];
        if (!isset(BANK_NAME[$input['bank_id']])) {
            return response()->json(['data' => null, 'msg' => '参数错误', 'code' => 401]);
        }
        $Merchant = new MerchantsCards();
        $data = $Merchant->editCard($where, $input);
        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * 添加银行卡
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCard(Request $request)
    {
        $this->validate($request, [
            'card_num' => 'required|numeric',
            'bank_id' => 'required|integer|min:0',
            'branch_name' => 'required|max:255',
            'province' => 'required|max:255',
            'city' => 'required|max:255',
            'name' => 'required|max:255',
        ]);
        $cardId = request('id');
        $input = request(['card_num', 'bank_id', 'branch_name', 'province', 'city', 'name']);
        $mer = auth('merchants')->user();

        if (!isset(BANK_NAME[$input['bank_id']])) {
            return response()->json(['data' => null, 'msg' => '参数错误', 'code' => 401]);
        }
        $Merchant = new MerchantsCards();
        $count = $Merchant->cardCount($mer->id);
        if ($count >= 5) {
            return response()->json(['data' => null, 'msg' => '银行卡数量超过上限', 'code' => 401]);
        }
        $input['merchant_id'] = $mer->id;
        $res = $Merchant->addCard($input);
        if (!$res) {
            return response()->json(['data' => null, 'msg' => '添加失败', 'code' => 401]);
        }
        return response()->json(['data' => null, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * del银行卡
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delCard(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer|min:0',
        ]);
        $cardId = request('id');
        $mer = auth('merchants')->user();
        $where = [['merchant_id', '=', $mer->id], ['id', '=', $cardId]];
        $Merchant = new MerchantsCards();
        $res = $Merchant->delCard($where);
        if (!$res) {
            return response()->json(['data' => null, 'msg' => '删除失败', 'code' => 401]);
        }
        return response()->json(['data' => null, 'msg' => '删除成功', 'code' => 200]);
    }


    /**
     * get银行列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBank(Request $request)
    {
        foreach (BANK_NAME as $k => $v) {
            $data[] = ["bank_id" => $k, "bank_name" => $v];
        }
        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * 获取商户自己的信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getSelfInfo(Request $request)
    {
        $userId = auth('merchants')->user()->id;
        $userModel = new Merchant();
        $data = $userModel->getUserInfoById($userId);

        if (!$data) {
            return response()->json(['data' => [], 'msg' => '商户信息有误', 'code' => 401]);
        }

        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * edit 商户自己的信息.
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editSelf(Request $request)
    {
        $this->validate($request, [
            'avatar' => 'max:255',
            'email' => 'email|max:255',
            'phone_num' => 'regex:/^1[345789][0-9]{9}$/',
            'status' => ['required', Rule::in(['1', '2'])],
            'recharge' => ['required', Rule::in(['1', '2'])],
        ]);

        $credentials = request(['avatar', 'status', 'email', 'phone_num', 'recharge']);
        $userId = auth('merchants')->user()->id;
        $userModel = new Merchant();
        $res = $userModel->editUserInfo($userId, $credentials);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改失败', 'code' => 200]);
        }
        return response()->json(['data' => [], 'msg' => '修改成功', 'code' => 200]);
    }

    /**
     * edit 商户自己的信息.
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editPayPass(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|min:6|max:10',
            'newPassword' => 'required|min:6|max:10',
            'rePassword' => 'required|min:6|max:10'
        ]);
        $input = request(['password', 'newPassword', 'rePassword']);
        if ($input['newPassword'] != $input['rePassword']) {
            return response()->json(['data' => [], 'msg' => '新密码与重复密码不一致', 'code' => 200]);
        }

        $user = auth('merchants')->user();
        if (md5($input['password']) != $user->pay_secret) {
            return response()->json(['data' => [], 'msg' => '支付密码错误', 'code' => 200]);
        }

        $userModel = new Merchant();
        $res = $userModel->editUserInfo($user->id, ['pay_secret' => md5($input['newPassword'])]);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改失败', 'code' => 200]);
        }
        return response()->json(['data' => [], 'msg' => '修改成功', 'code' => 200]);
    }

    /**
     * edit 商户自己的信息.
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editPass(Request $request)
    {
        dispatch(new NotifyJob(123456));
        $this->validate($request, [
            'password' => 'required|min:6|max:10',
            'newPassword' => 'required|min:6|max:10',
            'rePassword' => 'required|min:6|max:10'
        ]);
        $input = request(['password', 'newPassword', 'rePassword']);
        if ($input['newPassword'] != $input['rePassword']) {
            return response()->json(['data' => [], 'msg' => '新密码与重复密码不一致', 'code' => 200]);
        }

        $user = auth('merchants')->user();
        if (!$token = auth('merchants')->attempt(['merchant_no' => $user->merchant_no, 'password' => $input['password']])) {
            return response()->json(['data' => [], 'msg' => '密码错误', 'code' => 401]);
        }

        $userModel = new Merchant();
        $res = $userModel->editUserInfo($user->id, ['password' => password_hash($input['newPassword'], PASSWORD_DEFAULT)]);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改失败', 'code' => 200]);
        }
        return response()->json(['data' => [], 'msg' => '修改成功', 'code' => 200]);
    }
}
