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
            客户端代码准备数据发到这个route：oauth/token
            Laravel\Passport\RouteRegistrar::forAccessTokens()
            Laravel\Passport\Http\Controllers\AccessTokenController::issueToken()
            League\OAuth2\Server\Grant\PasswordGrant::respondToAccessTokenRequest()
            League\OAuth2\Server\Grant\PasswordGrant::validateUser()
            Laravel\Passport\Bridge\UserRepository\getUserEntityByUserCredentials()
                以此方式获得User
                    $provider = config('auth.guards.api.provider')
                    $model = config('auth.providers.'.$provider.'.model')
                如果想改掉这种默认行为，可以在_ENV里设置标志，然后自己自定义行为
                搜索auth.guards.api.provider，可以发现多个地方
                    app\MongodbPassport\Token.php::user()
                    vendor\laravel\passport\src\Token.php::user()

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
            // User获取方法
            // 1、在知道是api认证时，总可以这样：$user = \Auth::guard('api')->user();
            // 2、如果知道是我们自己的代码，可以这样：$user = $_ENV["CurrentUser"];
            $user = $guard->user();
            $_ENV["CurrentUser"] = $user;
            // 3、还可以更通用的：$user = $request->user();
            $this->auth->shouldUse('api');  //这样就可以支持 $user = $request->user();
        }
        else
        {
			throw new UnauthorizedHttpException('', 'NO PASSPORT AUTH.');
        }
    }
}
