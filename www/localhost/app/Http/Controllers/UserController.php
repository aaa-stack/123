<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Validation\Rule;
use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * 获取用户列表.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserList(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer|min:0',
            'num' => 'required|integer|min:1|max:100',
        ]);

        $credentials = request(['page', 'num']);

        $userModel = new User();
        $data = $userModel->getUserList($credentials['page'], $credentials['num']);

        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * 修改用户信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserStatus(Request $request)
    {
        $this->validate($request, [
            'userId' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['1', '2'])],
        ]);

        $credentials = request(['userId', 'status']);

        if ($credentials['userId'] == auth()->user()->id) {
            return response()->json(['data' => [], 'msg' => '不能修改自己的状态', 'code' => 401]);
        }
        $userModel = new User();

        $res = $userModel->editUser($credentials['userId'], ['status' => $credentials['status']]);

        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改用户状态失败', 'code' => 401]);
        }

        return response()->json(['data' => [], 'msg' => '修改用户状态成功', 'code' => 200]);
    }

    /**
     * 通过用户id查询用户信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInfoById(Request $request)
    {
        $this->validate($request, [
            'userId' => 'required|integer|min:0',
        ]);

        $credentials = request(['userId']);

        $userModel = new User();
        $data = $userModel->getUserInfoById($credentials['userId']);

        if (!$data) {
            return response()->json(['data' => [], 'msg' => '用户信息有误', 'code' => 401]);
        }

        return response()->json(['data' => $data, 'msg' => 'success', 'code' => 200]);
    }

    /**
     * add user
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addUser(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|min:4|max:10|regex:/^[a-zA-Z0-9]{4,10}$/',
            'password' => 'required|min:4|max:15|alpha_dash',
            'repass' => 'required|min:4|max:15|alpha_dash',
            'avatar' => 'max:255',
            'email' => 'email|max:255',
            'phone_num' => 'regex:/^1[345789][0-9]{9}$/',
            'status' => ['required', Rule::in(['1', '2'])],
        ]);

        $credentials = request(['username', 'password', 'avatar', 'status', 'email', 'repass', 'phone_num']);
        if ($credentials['password'] !== $credentials['repass']) {
            return response()->json(['data' => [], 'msg' => '两次密码不一致', 'code' => 401]);
        }
        unset($credentials['repass']);

        $userModel = new User();
        if ($userModel->hasUsername($credentials['username'])) {
            return response()->json(['data' => [], 'msg' => '用户名已存在', 'code' => 401]);
        }
        $credentials['created_time'] = time();
        $credentials['last_time'] = 0;
        $credentials['password'] = password_hash($credentials['password'], PASSWORD_DEFAULT);
        $google = GoogleAuthenticator::CreateSecret();
        $credentials['secret'] = $google['secret'];
        $res = $userModel->addUser($credentials);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '添加失败', 'code' => 200]);
        }
        return response()->json(['data' => [], 'msg' => '添加成功', 'code' => 200]);
    }

    /**
     * edit user
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editUser(Request $request)
    {
        $this->validate($request, [
            'userId' => 'required|integer|min:0',
            'avatar' => 'max:255',
            'email' => 'email|max:255',
            'phone_num' => 'regex:/^1[345789][0-9]{9}$/',
            'status' => ['required', Rule::in(['1', '2'])],
        ]);

        $credentials = request(['avatar', 'status', 'email', 'phone_num']);
        $userId = request('userId');

        if ($userId == auth()->user()->id) {
            unset($credentials['status']);
        }

        $userModel = new User();
        if (!$userModel->hasUser($userId)) {
            return response()->json(['data' => [], 'msg' => '用户不存在', 'code' => 401]);
        }

        $res = $userModel->editUser($userId, $credentials);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改失败或没有修改内容', 'code' => 401]);
        }
        return response()->json(['data' => [], 'msg' => '修改成功', 'code' => 200]);
    }

    /**
     * 获取google验证器信息
     *
     * @return array
     */
    public function google()
    {
        $user = auth()->user();
        $google['secret'] = $user->secret;
        $google_url = "otpauth://totp/?secret=" . $user->secret;
        // 生成二维码
        $google["qrcode"] = QrCode::encoding('UTF-8')->size(180)->margin(1)->generate($google_url);
        // }
        return response()->json(['data' => $google, 'msg' => '获取google验证信息成功', 'code' => 200]);
    }

    /**
     * 重新生成google验证器信息
     *
     * @return array
     */
    public function resetGoogle()
    {
        $user = auth()->user();
        $google = GoogleAuthenticator::CreateSecret();
        // 生成二维码
        $userModel = new User();
        $res = $userModel->editUser($user->id, ['secret' => $google['secret']]);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '重置谷歌验证器密钥失败', 'code' => 401]);
        }
        return response()->json(['data' => [], 'msg' => '重置谷歌验证器密钥成功', 'code' => 200]);
    }

    /**
     * edit 用户密码
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function editPass(Request $request)
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

        $user = auth()->user();
        if (!$token = auth()->attempt(['username' => $user->username, 'password' => $input['password']])) {
            return response()->json(['data' => [], 'msg' => '密码错误', 'code' => 401]);
        }

        $userModel = new User();
        $res = $userModel->editUser($user->id, ['password' => password_hash($input['newPassword'], PASSWORD_DEFAULT)]);
        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改失败', 'code' => 200]);
        }
        return response()->json(['data' => [], 'msg' => '修改成功', 'code' => 200]);
    }

    /**
     * edit 修改自己的信息
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
        ]);

        $credentials = request(['avatar', 'email', 'phone_num']);
        $userId = auth()->user()->id;
        $userModel = new User();

        $res = $userModel->editUser($userId, $credentials);

        if (!$res) {
            return response()->json(['data' => [], 'msg' => '修改失败', 'code' => 401]);
        }
        return response()->json(['data' => [], 'msg' => '修改成功', 'code' => 200]);
    }
}
