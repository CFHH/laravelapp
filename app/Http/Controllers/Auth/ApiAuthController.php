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

class ApiAuthController extends Controller
{
    protected function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|string|email|max:50',
            'name' => 'required|string|max:20|min:6',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails())
            return "参数错误";

        $crc = \CRC::crc64($data['email']);
        $result = NULL;
        try
        {
            $result = User::create([
                'id_crc64' => $crc,
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => bcrypt($data['password']),
            ]);
        }
        catch (QueryException $e)
        {
            return 'email exists ' . $data['email'];
        }
        finally
        {
        }
        if ($result == NULL)
            return 'error';
        return 'success';
    }

    public function login(Request $request)
    {
        $crc = \CRC::crc64($request->input('email'));

        $use_config = false;
        if ($use_config)
        {
            $request->request->add([
                'grant_type' => config('app.passport_configs.login_grant_type'),
                'client_id' => config('app.passport_configs.client_id'),
                'client_secret' => config('app.passport_configs.client_secret'),
                'username' => $crc,
                'password' => $request->input('password'),
                'scope' => ''
            ]); 
        }
        else
        {
            $oauth_client = Client::where('password_client', true)->get()->first();
            if (config('app.passport_configs.use_mongo'))
                $id = $oauth_client->_id;
            else
                $id = $oauth_client->id;
            $request->request->add([
                'grant_type' => 'password',
                'client_id' => $id,
                'client_secret' => $oauth_client->secret,
                'username' => $crc,
                'password' => $request->input('password'),
                'scope' => ''
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
        if (array_key_exists('access_token', $token_info))
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
        $user = $_ENV["CurrentUser"];  //或者 $user = \Auth::guard('api')->user();
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
                'client_id' => config('app.passport_configs.client_id'),
                'client_secret' => config('app.passport_configs.client_secret'),
                'refresh_token' => $data['refresh_token'],
                'scope' => ''
            ]); 
        }
        else
        {
            $oauth_client = Client::where('password_client', true)->get()->first();
            if (config('app.passport_configs.use_mongo'))
                $id = $oauth_client->_id;
            else
                $id = $oauth_client->id;
            $request->request->add([
                'grant_type' => 'refresh_token',
                'client_id' => $id,
                'client_secret' => $oauth_client->secret,
                'refresh_token' => $data['refresh_token'],
                'scope' => ''
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
        $user = \Auth::guard('api')->user();
        if ($user == NULL)
            return "logout fail: no user";

        //$user->token()->delete();  //这个只删除AccessToken
        DB::table('oauth_access_tokens')
            ->where('user_id', $user->id_crc64)
            ->delete();
        DB::table('oauth_refresh_tokens')
            ->where('user_id', $user->id_crc64)
            ->delete();

        return response()->json(['message' => '登出成功', 'status_code' => 200, 'data' => null]);
    }

    public function behave(Request $request)
    {
        $user1 = \Auth::guard('api')->user();
        $user2 = $_ENV["CurrentUser"];
        $user3 = $request->user();
        echo "behave @ " . $user1->name . ' ' . $user2->name . ' ' . $user3->name;
    }

    public function behave2(Request $request)
    {
        $user = $request->user();
        echo "behave @ " . $user->name;
    }
}
