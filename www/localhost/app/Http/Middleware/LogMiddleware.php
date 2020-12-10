<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Log;
use App\Models\MerchantLog;

class LogMiddleware
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
        if(auth()->user()){
            $userinfo = auth()->user();
            $log = new Log();
        }elseif(auth('merchants')->user()){
            $userinfo = auth('merchants')->user();
            $log = new MerchantLog();
        }
        $method = $request->method();
        $path = $request->path();
        $method_arr = ['PUT','POST','DELETE'];
        if(in_array($method,$method_arr)){
            $data['username'] = $userinfo->username;
            $data['user_id'] = $userinfo->id;
            $data['param'] = json_encode($request->all());
            $data['ip'] = $request->getClientIp();
            $data['time'] = time();
            $data['path'] = $path;
            $data['method'] = $method;
            $log->addLog($data);
        }
        return $next($request);
    }
}
