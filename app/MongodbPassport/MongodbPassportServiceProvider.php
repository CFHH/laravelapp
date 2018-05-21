<?php

namespace App\MongodbPassport;

use Illuminate\Support\ServiceProvider;
use App\MongodbPassport\AuthCode;
use App\MongodbPassport\Client;
use App\MongodbPassport\PersonalAccessClient;
use App\MongodbPassport\Token;
use App\MongodbPassport\User;

/*
使用说明：
0、拷贝文件到app/MongodbPassport下面

1、修改.env，
        修改
            DB_CONNECTION=mongodb
        增加
            MONGODB_HOST=127.0.0.1
            MONGODB_PORT=27017
            MONGODB_DATABASE=laravelapp
            MONGODB_USERNAME=
            MONGODB_PASSWORD=
        增加
            PASSPORT_LOGIN_GRANT_TYPE=password
            PASSPORT_REFRESH_GRANT_TYPE=refresh_token
            PASSPORT_CLIENT_ID=5afbff6eae05a4032c0058c4
            PASSPORT_CLIENT_SECRET=v2TQJErVAR7BoeU500OwIOITBsSzn97NnpAfsvHf
            PASSPORT_USE_MONGO=true

2、修改config/app.php
        在'providers'中增加App\MongodbPassport\MongodbPassportServiceProvider::class,
        增加一项
            'passport_configs' => [
                'login_grant_type' => env("PASSPORT_LOGIN_GRANT_TYPE"),
                'refresh_grant_type' => env("PASSPORT_REFRESH_GRANT_TYPE"),
                'client_id' => env("PASSPORT_CLIENT_ID"),
                'client_secret' => env("PASSPORT_CLIENT_SECRET"),
                'use_mongo' => env("PASSPORT_USE_MONGO"),
            ],

3、执行php artisan passport:install，生成key并且给oauth_client填数据
        注意不需要php artisan migrate

4、修改User.php，这个看具体情况

use Illuminate\Notifications\Notifiable;
use App\MongodbPassport\User as Authenticatable;
class User extends Authenticatable
{
    use Notifiable;
    protected $primaryKey = 'id_crc64';
    protected $keyType = 'int';
    protected $fillable = [
        'id_crc64', 'email', 'name', 'password',
    ];
    protected $hidden = [
        'password',
    ];
    public function findForPassport($username)
    {
        return $this->where('id_crc64', $username)->first();
    }

    public function getKeyName()
    {
        return 'id_crc64';
    }
}

5、如果要使用已有工程的数据，需要两个文件
        laravelapp\storage\oauth-private.key
        laravelapp\storage\oauth-public.key

6、切换mysql和mongo
        修改.env
            DB_CONNECTION=mysql
            PASSPORT_USE_MONGO=false
        其他都不用改
*/

class MongodbPassportServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (!config('app.passport_configs.use_mongo'))
            return;
        if (class_exists('Illuminate\Foundation\AliasLoader'))
        {
            //应该到这里
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Laravel\Passport\AuthCode', AuthCode::class);
            $loader->alias('Laravel\Passport\Client', Client::class);
            $loader->alias('Laravel\Passport\PersonalAccessClient', PersonalAccessClient::class);
            $loader->alias('Laravel\Passport\Token', Token::class);
            $loader->alias('Illuminate\Foundation\Auth\User', User::class);
        }
        else
        {
            class_alias('Laravel\Passport\AuthCode', AuthCode::class);
            class_alias('Laravel\Passport\Client', Client::class);
            class_alias('Laravel\Passport\PersonalAccessClient', PersonalAccessClient::class);
            class_alias('Laravel\Passport\Token', Token::class);
            class_alias('Illuminate\Foundation\Auth\User', User::class);
        }
    }
}
