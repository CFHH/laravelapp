<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


class VerifyPassportToken extends Authenticate
{
    protected function authenticate(array $guards)
    {
    	/* 两段说明：
    	一、登录获取passport的token
    		League\OAuth2\Server\Grant\PasswordGrant::respondToAccessTokenRequest()
    		League\OAuth2\Server\Grant\AbstractGrant::validateClient()
    		Laravel\Passport\Bridge\ClientRepository::getClientEntity()，$this->clients是ClientRepository
    		Laravel\Passport\ClientRepository::findActive()，mongodb默认的逐渐是"_id"，所以.env中PASSPORT_CLIENT_ID=5afbff6eae05a4032c0058c4
    	二、使用token（就是本中间件做的事情）
    		$this->auth->guard('api')的类型是Illuminate\Auth\RequestGuard，由Laravel\Passport\PassportServiceProvider::makeGuard()创建
    		Illuminate\Auth\GuardHelpers::check()
    		Illuminate\Auth\RequestGuard::user()，call_user_func的closure在makeGuard()里
    		Laravel\Passport\Guards\TokenGuard::user()
    		Laravel\Passport\Guards\TokenGuard::authenticateViaBearerToken()
    		Illuminate\Auth\EloquentUserProvider::retrieveById($identifier)，$identifier是字符串
	    		mysql是强类型的，所以其Eloquent一个在什么地方对数据做转型
	    		mongodb同列可以不同类型，所以需要处理一下
	    		if ($model->getKeyType() == 'int')
	           		$identifier = intval ($identifier);
        三、这个类，提供了一中定制化的功能，也可以使用middleware('auth:api')代替这里的middleware('vpt')。
            auth:api如果失败输出如下内容：
                {
                    "message": "Unauthenticated."
                }
            vpt如果失败，内容可以自定义，在App\Exceptions\Handler::render()里修改
    	*/
        $guard = $this->auth->guard('api');
        if ($guard->check())
        {
			$user = $guard->user();
            return $user;
        }
        else
        {
			throw new UnauthorizedHttpException('', 'NO PASSPORT AUTH.');
        }
    }
}
