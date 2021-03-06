<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

//不要用这种方式
class VerifyPassportToken2 extends Authenticate
{
    protected function authenticate(array $guards)
    {
        $passport_guard = 'passport1';
        $guard = $this->auth->guard($passport_guard);
        if ($guard->check())
        {
            // User2获取方法
            // 1、$user = \Auth::guard($_ENV["PASSPORT_GUARD"])->user();
            // 2、$user = $_ENV["CurrentUser"];
            $user = $guard->user();
            $_ENV["CurrentUser"] = $user;
            $_ENV["PASSPORT_GUARD"] = $passport_guard;
            // 3、更通用的：$user = $request->user();
            $this->auth->shouldUse($passport_guard);  //这样就可以支持 $user = $request->user();
        }
        else
        {
            //App\Exceptions/Handler里自定义消息
            throw new UnauthorizedHttpException('', 'NO PASSPORT AUTH FOR USER.');
        }
    }
}
