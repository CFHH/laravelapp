<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


class VerifyPassportToken2 extends Authenticate
{
    protected function authenticate(array $guards)
    {
        $guard = $this->auth->guard('api2');
        if ($guard->check())
        {
            // User获取方法
            // 1、在知道是api认证时，总可以这样：$user = \Auth::guard('api')->user();
            // 2、如果知道是我们自己的代码，可以这样：$user = $_ENV["CurrentUser"];
            $user = $guard->user();
            $_ENV["CurrentUser"] = $user;
            $_ENV["UserType"] = "User2";
            // 3、还可以更通用的：$user = $request->user();
            $this->auth->shouldUse('api2');  //这样就可以支持 $user = $request->user();
        }
        else
        {
			throw new UnauthorizedHttpException('', 'NO PASSPORT AUTH FOR USER2.');
        }
    }
}
