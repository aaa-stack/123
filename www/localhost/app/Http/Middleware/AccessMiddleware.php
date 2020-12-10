<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Models\Log;

class AccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $userinfo = auth()->user();
        if (!$userinfo) {
            return response()->json(['data' => [], 'msg' => '获取用户信息失败', 'code' => 401]);
        }

        $user = json_decode($userinfo, true);
        if ($user['status'] != 1) {
            return response()->json(['data' => [], 'msg' => '用户被禁用', 'code' => 401]);
        }
        $path = $request->path();
        $method = $request->method();

        $userModel = new User();
        $hasAccess = $userModel->hasAccess($user['id'], $path, $method);
        if (!$hasAccess) {
            return response()->json(['data' => [], 'msg' => '没有访问权限', 'code' => 401]);
        }
        return $next($request);
    }
}
