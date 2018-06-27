<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Response;

/* 两段说明：
一、登录获取passport的token
    客户端代码准备数据发到这个route：oauth/token
    Laravel\Passport\RouteRegistrar::forAccessTokens()
    Laravel\Passport\Http\Controllers\AccessTokenController::issueToken()
    League\OAuth2\Server\AuthorizationServer::respondToAccessTokenRequest()
        ！！！要求填写Request['grant_type']，值不同最终使用的grant也不同
        通过get_class，发现有3个grantType，都继承自AbstractAuthorizeGrant，这仨是在Laravel\Passport\PassportServiceProvider::register()时注册的，注册时AuthorizationServer::enableGrantType()设置这仨的$defaultScope = ''，
            League\OAuth2\Server\Grant\AuthCodeGrant, getIdentifier() == 'authorization_code'
            League\OAuth2\Server\Grant\RefreshTokenGrant, getIdentifier() == 'refresh_token'
            League\OAuth2\Server\Grant\PasswordGrant, getIdentifier() == 'password'
    1、League\OAuth2\Server\Grant\PasswordGrant::respondToAccessTokenRequest()
        ！！！要求填写Request['scope']，并且和$defaultScope = ''匹配
        League\OAuth2\Server\Grant::validateClient()
            ！！！要求填写Request['client_id']和Request['client_secret']
            Laravel\Passport\Bridge\ClientRepository::getClientEntity()
            Laravel\Passport\ClientRepository::findActive()，mongodb默认的主键是"_id"，所以.env中PASSPORT_CLIENT_ID=5afbff6eae05a4032c0058c4
        PasswordGrant::validateUser()
            ！！！要求填写Request['username']和Request['password']
            Laravel\Passport\Bridge\UserRepository\getUserEntityByUserCredentials()
                以此方式获得User
                    $provider = config('auth.guards.api.provider')
                    $model = config('auth.providers.'.$provider.'.model')
                如果想改掉这种默认行为，可以在_ENV里设置标志，然后自己自定义行为
                搜索auth.guards.api.provider，可以发现多个地方
                    app\MongodbPassport\Token.php::user()
                    vendor\laravel\passport\src\Token.php::user()
                此函数最终返回了Laravel\Passport\Bridge\User
        AbstractGrant::issueAccessToken()
            Laravel\Passport\Bridge\AccessTokenRepository::getNewToken()，创建Laravel\Passport\Bridge\AccessToken
            AbstractGrant::generateUniqueIdentifier()
            Laravel\Passport\Bridge\AccessTokenRepository::persistNewAccessToken()，入库
        AbstractGrant::issueRefreshToken()
    2、League\OAuth2\Server\ResponseTypes\BearerTokenResponse::generateHttpResponse()
        League\OAuth2\Server\Entities\Traits\AccessTokenTrait::convertToJWT()
        League\OAuth2\Server\CryptTrait::encrypt()
二、使用token（就是本中间件做的事情）
    $this->auth->guard($_ENV["PASSPORT_GUARD"])的类型是Illuminate\Auth\RequestGuard，由Laravel\Passport\PassportServiceProvider::makeGuard()创建
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


class VerifyPassportToken
{
    protected $auth_factory;

    public function __construct(AuthFactory $auth_factory)
    {
        //ZZW 我不知道这个参数是怎么传进来的
        $this->auth_factory = $auth_factory;
    }

    public function handle($request, Closure $next, ...$guards)
    {
        if ($this->authenticate($guards))
            return $next($request);
        else
            return $this->buildErrorResponse();
    }

    protected function authenticate(array $guards)
    {
        if (empty($guards))
        {
            $passport_guard = $_ENV["PASSPORT_GUARD"];
        }
        else
        {
            $passport_guard = $guards[0];
            $_ENV["PASSPORT_GUARD"] = $passport_guard;
        }

        $guard = $this->auth_factory->guard($passport_guard);
        if ($guard->check())
        {
            // User获取方法
            // 1、$user = \Auth::guard($_ENV["PASSPORT_GUARD"])->user();
            // 2、$user = $_ENV["CurrentUser"];
            $user = $guard->user();
            $_ENV["CurrentUser"] = $user;
            $_ENV["PASSPORT_GUARD"] = $passport_guard;
            // 3、更通用的：$user = $request->user();
            $this->auth_factory->shouldUse($passport_guard);  //这样就可以支持 $user = $request->user();
            return true;
        }
        else
        {
            return false;
        }
    }

    protected function buildErrorResponse()
    {
        $message = json_encode([
            'error' => [
                'message' => 'NO PASSPORT AUTH FOR USER.',
            ],
            'status_code' => 401,
        ]);
        return new Response($message, 401);
    }
}
