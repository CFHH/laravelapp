<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Laravel\Passport\Client;

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
                'grant_type' => config('app.passport_configs.grant_type'),
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

        return $response;
    }

    public function logout(Request $request)
    {
        $user = \Auth::guard('api')->user();
        if ($user == NULL)
            return "logout fail: no user";
        $user->token()->delete();
        return response()->json(['message' => '登出成功', 'status_code' => 200, 'data' => null]);
    }

    public function behave(Request $request)
    {
        $user = $_ENV["CurrentUser"];  //或者 $user = \Auth::guard('api')->user();
        echo "behave @ " . $user->name;
    }
}
