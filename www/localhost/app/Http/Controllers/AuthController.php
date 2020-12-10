<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\User;
use App\Models\Auth as auths;
use App\Models\Log;
use Earnp\GoogleAuthenticator\GoogleAuthenticator;

class AuthController extends Controller
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
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|min:4|max:10',
            'password' => 'required|min:5|max:20',
            'secret' => 'required|numeric',
        ]);

        $credentials = request(['username', 'password']);
        $secret = request('secret');
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['data' => [], 'msg' => '用户信息验证失败', 'code' => 401]);
        }

        $user = json_decode(auth()->user(), true);

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
        $userModel = new User();
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
        $log = new Log();
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
        return response()->json(['data' => auth()->user(), 'msg' => '用户信息请求成功', 'code' => 200]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['data' => [], 'msg' => '退出登录成功', 'code' => 200]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
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
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ]);
    }

    /**
     * 获取用户角色
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUserRoleList(Request $request)
    {
        $this->validate($request, [
            'userId' => 'required|integer|min:0',
        ]);
        $userId = request('userId');
        $auth = new auths();
        $data['all_roles'] = $auth->getRoleInfoList();
        $data['user_roles'] = $auth->getUserRoleList($userId);
        return response()->json([
            'code' => 200,
            'msg' => "获取用户角色成功",
            'data' => $data
        ]);
    }

    /**
     * 获取角色权限
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getRoleAuthList(Request $request)
    {
        $this->validate($request, [
            'roleId' => 'required|integer|min:0',
        ]);
        $roleId = request('roleId');
        $auth = new auths();
        $data['all_auths'] = $auth->getApiAuthList();
        $data['role_auths'] = $auth->getRoleApiAuthsList($roleId);
        $data['all_pauths'] = $auth->getPageAuthList();
        $data['role_pauths'] = $auth->getRolePageAuthsList($roleId);
        return response()->json([
            'code' => 200,
            'msg' => "获取角色权限成功",
            'data' => $data
        ]);
    }

    /**
     * 设置用户的角色
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setUserRole(Request $request)
    {
        $this->validate($request, [
            'roleId' => 'array',
            'userId' => 'required|integer|min:0',
        ]);

        $userId = request('userId');
        $roleId = array_unique(request('roleId'));

        $userModel = new User();
        if (!$userModel->hasUser($userId)) {
            return response()->json([
                'code' => 401,
                'msg' => "上传的用户id有误",
                'data' => []
            ]);
        }

        $auth = new auths();
        $role_list = $auth->getRoleList();
        $user_roles = $auth->getUserRoleList($userId);
        if ($roleId != array_intersect($roleId, $role_list)) {
            return response()->json([
                'code' => 401,
                'msg' => "上传的角色id有误",
                'data' => []
            ]);
        }

        $del = array_diff($user_roles, $roleId);
        if ($del) {
            $auth->delUserRole($userId, $del);
        }
        $insert = array_diff($roleId, $user_roles);
        if ($insert) {
            $res = $auth->insertUserRole($userId, $insert);
        }

        return response()->json([
            'code' => 200,
            'msg' => "设置用户的角色成功",
            'data' => []
        ]);
    }

    /**
     * 设置角色权限
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setRoleAuth(Request $request)
    {
        $this->validate($request, [
            'roleId' => 'required|integer|min:0',
            'pauths' => 'required|array',
            'auths' => 'required|array',
        ]);

        $roleId = request('roleId');
        $pauths = array_unique(request('pauths'));
        $auths = array_unique(request('auths'));
        $auth = new auths();
        if (!$auth->hasRole($roleId)) {
            return response()->json([
                'code' => 401,
                'msg' => "上传的角色id有误",
                'data' => []
            ]);
        }

        $all_auths = $auth->getApiAuthIdList();
        $role_auths = $auth->getRoleApiAuthsList($roleId);
        $all_pauths = $auth->getPageAuthIdList();
        $role_pauths = $auth->getRolePageAuthsList($roleId);
        if ($auths != array_intersect($auths, $all_auths) || $pauths != array_intersect($pauths, $all_pauths)) {
            return response()->json([
                'code' => 401,
                'msg' => "上传的权限id有误",
                'data' => []
            ]);
        }

        $del = array_diff($role_auths, $auths);
        if ($del) {
            $auth->delRoleAuth($roleId, $del);
        }

        $insert = array_diff($auths, $role_auths);
        if ($insert) {
            $auth->insertRoleAuth($roleId, $insert);
        }

        $pdel = array_diff($role_pauths, $pauths);
        if ($pdel) {
            $auth->delRolePauth($roleId, $pdel);
        }

        $pinsert = array_diff($pauths, $role_pauths);
        if ($pinsert) {
            $auth->insertRolePauth($roleId, $pinsert);
        }

        return response()->json([
            'code' => 200,
            'msg' => "设置用户的角色成功",
            'data' => []
        ]);
    }

    /**
     * 获取角色
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getRoleList(Request $request)
    {
        $auth = new auths();
        $data = $auth->getRoleInfoList();
        return response()->json([
            'code' => 200,
            'msg' => "获取角色成功",
            'data' => $data
        ]);
    }

    /**
     * 获取角色
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getAuthList(Request $request)
    {
        $auth = new auths();
        $data['all_auths'] = $auth->getApiAuthList();
        $data['all_pauths'] = $auth->getPageAuthList();
        return response()->json([
            'code' => 200,
            'msg' => "获取权限成功",
            'data' => $data
        ]);;
    }

    /**
     * 删除角色
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function delRole(Request $request)
    {
        $this->validate($request, [
            'roleId' => 'required|integer|min:0',
        ]);

        $roleId = request('roleId');
        $auth = new auths();
        $res = Db::table('users_roles')->where('role_id', '=', $roleId)->exists();
        if ($res) {
            return response()->json([
                'code' => 401,
                'msg' => "有用户有此角色，不能删除",
                'data' => []
            ]);;
        }
        $re = $auth->delRole($roleId);
        if (!$re) {
            return response()->json([
                'code' => 401,
                'msg' => "删除失败",
                'data' => []
            ]);;
        }
        return response()->json([
            'code' => 200,
            'msg' => "删除成功",
            'data' => []
        ]);;
    }

    /**
     * 添加角色
     *
     * @param  string $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addRole(Request $request)
    {
        $this->validate($request, [
            'role_name' => 'required|min:2',
            'desc' => 'max:255',
        ]);
        $data = request(['role_name', 'desc']);
        $auth = new auths();
        $res = $auth->addRole($data);
        if (!$res) {
            return response()->json([
                'code' => 401,
                'msg' => "获取角色失败",
                'data' => []
            ]);;
        }
        return response()->json([
            'code' => 200,
            'msg' => "获取角色成功",
            'data' => $data
        ]);
    }
}
