<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Laravel\Passport\Client;
use Laravel\Passport\Token as AccessToken;
use DB;
use Redis;
use DateTime;

class ApiAuthController extends Controller
{
    private function getOauthClinetIDAndSecret()
    {
        $keys = ['oauth_client_id', 'oauth_client_secret'];
        $id_secret = Redis::mget($keys);
        if($id_secret == null || $id_secret[0] == null || $id_secret[1] == null)
        {
            $oauth_client = Client::where('password_client', true)->get()->first();
            if ($oauth_client == null)
                return null;
            if (config('app.passport_configs.use_mongo'))
                $id = $oauth_client->_id;
            else
                $id = $oauth_client->id;
            Redis::mset(["oauth_client_id" => $id, "oauth_client_secret" => $oauth_client->secret]);
            return [$id, $oauth_client->secret];
        }
        else
        {
            return $id_secret;
        }
    }

    public function register(Request $request)
    {
        $_ENV["PASSPORT_GUARD"] = "passport1";
        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|string|email|max:50',
            'name' => 'required|string|max:20|min:6',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails())
            return "参数错误";

        $crc = \CRC::crc64($data['email']);
        $exist_user = User::find($crc);
        if ($exist_user != null)
            return "用户已经存在";

        $user = null;
        try
        {
            $user = User::create([
                'id_crc64' => $crc,
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => bcrypt($data['password']),
                'created_at' => new DateTime,
                'updated_at' => new DateTime,
            ]);
        }
        catch (QueryException $e)
        {
            return "用户已经存在";
        }
        finally
        {
        }
        if ($user == NULL)
            return "内部错误";
        echo "成功创建" . $user->email . '</br>';
        echo 'cache flag = ' . $user->getCacheFlag() . '</br>';
        var_dump($user);
    }

    public function login(Request $request)
    {
        /*
        DB query from mysql :: select * from `oauth_clients` where `password_client` = ?
        DB query from mysql :: select * from `oauth_clients` where `oauth_clients`.`id` = ? limit 1
        DB query from mysql :: select * from `users` where `id_crc64` = ? limit 1
        DB query from mysql :: insert into `oauth_access_tokens` (`id`, `user_id`, `client_id`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) values (?, ?, ?, ?, ?, ?, ?, ?)
        DB query from mysql :: delete from `oauth_access_tokens` where `user_id` = ? and `id` != ?
        DB query from mysql :: delete from `oauth_refresh_tokens` where `user_id` = ?
        DB query from mysql :: insert into `oauth_refresh_tokens` (`id`, `user_id`, `access_token_id`, `revoked`, `expires_at`) values (?, ?, ?, ?, ?)
        */
        $_ENV["PASSPORT_GUARD"] = "passport1";
        $crc = \CRC::crc64($request->input('email'));

        $use_config = false;
        if ($use_config)
        {
            /*
                如果使用.env中关于passport的配置
                mysql和mongo之间切换时，需要改PASSPORT_CLIENT_ID和PASSPORT_CLIENT_SECRET，后者可以在mysql和mongo之间设置一致
            */
            $request->request->add([
                'grant_type' => config('app.passport_configs.login_grant_type'),
                'scope' => '',
                'client_id' => config('app.passport_configs.client_id'),
                'client_secret' => config('app.passport_configs.client_secret'),
                'username' => $crc,
                'password' => $request->input('password'),
            ]);
        }
        else
        {
            $id_secret = $this->getOauthClinetIDAndSecret();
            $request->request->add([
                'grant_type' => 'password',
                'scope' => '',
                'client_id' => $id_secret[0],
                'client_secret' => $id_secret[1],
                'username' => $crc,
                'password' => $request->input('password'),
            ]);
        }

        $proxy = Request::create(
            'oauth/token',
            'POST'
        );
        $response = \Route::dispatch($proxy);

        $token_info = json_decode($response->content(), true);
        /*
        echo 'token_type : ' . $token_info['token_type'] . "<br/>";
        echo 'expires_in : ' . $token_info['expires_in'] . "<br/>";
        echo 'access_token : ' . $token_info['access_token'] . "<br/>";
        echo 'refresh_token : ' . $token_info['refresh_token'] . "<br/>";
        */
        if (!is_null($token_info) && array_key_exists('access_token', $token_info))
        {
            //成功
        }
        else
        {
            //失败
        }
        return $response;
    }

    public function loginex(Request $request)
    {
        $user = $_ENV["CurrentUser"];
        echo "loginex @ " . $user->name;
    }

    public function refresh(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'refresh_token' => 'required',
        ]);
        if ($validator->fails())
            return "参数错误";

        $use_config = false;
        if ($use_config)
        {
            $request->request->add([
                'grant_type' => config('app.passport_configs.refresh_grant_type'),
                'scope' => '',
                'client_id' => config('app.passport_configs.client_id'),
                'client_secret' => config('app.passport_configs.client_secret'),
                'refresh_token' => $data['refresh_token'],
            ]);
        }
        else
        {
            $id_secret = $this->getOauthClinetIDAndSecret();
            $request->request->add([
                'grant_type' => 'refresh_token',
                'scope' => '',
                'client_id' => $id_secret[0],
                'client_secret' => $id_secret[1],
                'refresh_token' => $data['refresh_token'],
            ]);
        }

        $proxy = Request::create(
            'oauth/token',
            'POST'
        );
        $response = \Route::dispatch($proxy);
        return $response;
    }

    public function logout(Request $request)
    {
        $user = $_ENV["CurrentUser"];
        $userid = $user->attributes[$user->primaryKey];
        $user_token_key = User::getAccessTokenCacheKey($userid);
        $old_accesstoken_key = Redis::get($user_token_key);
        if ($old_accesstoken_key != null)
            Redis::del($old_accesstoken_key);

        $token = $user->token();
        if (! $token)
            AccessToken::where('user_id', $user->id_crc64)->delete();
        else
            $token->delete();
        DB::table('oauth_refresh_tokens')
            ->where('user_id', $user->id_crc64)
            ->delete();

        return response()->json(['message' => '登出成功', 'status_code' => 200, 'data' => null]);
    }

    public function behave(Request $request)
    {
        /*
        passport token 验证需要查4次数据库，都是逐渐查询
        DB query from mysql :: select * from `oauth_access_tokens` where `oauth_access_tokens`.`id` = ? limit 1
        DB query from mysql :: select * from `users` where `id_crc64` = ? limit 1
        DB query from mysql :: select * from `oauth_access_tokens` where `oauth_access_tokens`.`id` = ? limit 1
        DB query from mysql :: select * from `oauth_clients` where `oauth_clients`.`id` = ? limit 1
        */
        $user1 = \Auth::guard($_ENV["PASSPORT_GUARD"])->user();
        $user2 = $_ENV["CurrentUser"];
        $user3 = $request->user();
        echo "behave @ " . $user1->name . ' ' . $user2->name . ' ' . $user3->name . '</br>';
        echo $user3->toJson() . '</br>';
        echo $user3->toJsonEx() . '</br>';
    }

    public function behave2(Request $request)
    {
        $user = $request->user();
        echo "behave2 @ " . $user->name;
    }

    public function behaveex(Request $request)
    {
        $user1 = \Auth::guard($_ENV["PASSPORT_GUARD"])->user();
        $user2 = $_ENV["CurrentUser"];
        $user3 = $request->user();
        echo "behaveex @ " . $user1->name . ' ' . $user2->name . ' ' . $user3->name;
        echo $user3->toJson() . '</br>';
        echo $user3->toJsonEx() . '</br>';
        //$user3->name = 'hahaex1';
        //$user3->save();
    }
}
