<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;

class PatchOauthTokenLogin
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (!empty($guards))
        {
            $passport_guard = $guards[0];
            $_ENV["PASSPORT_GUARD"] = $passport_guard;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|string|email|max:50',
            'password' => 'required|string',
        ]);
        if ($validator->fails())
            return $this->buildErrorResponse("参数错误");

        $crc = \CRC::crc64($data['email']);
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
                'password' => $data['password'],
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
                'scope' => '',
                'client_id' => $id,
                'client_secret' => $oauth_client->secret,
                'username' => $crc,
                'password' => $request->input('password'),
            ]);
        }

        $response = $next($request);

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
            echo "SUCCESS </br>";
        }
        else
        {
            //失败
            echo "FAIL </br>";
        }
        return $response;
    }

    protected function buildErrorResponse($message)
    {
        $message = json_encode([
            'error' => [
                'message' => $message,
            ],
            'status_code' => 401,
        ]);
        return new Response($message, 401);
    }
}
