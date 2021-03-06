<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Extensions\Eloquent\CachableModel;  //use CachableModel

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, CachableModel;

    protected $primaryKey = 'id_crc64';
    protected $keyType = 'int';  //如果主键是整数，mongo必须设置
    public $incrementing = false;
    protected $cache_expire_sceonds = 3600;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    //protected $fillable = ['id_crc64', 'email', 'name', 'password', 'created_at', 'updated_at',];
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function getKeyName()  //这个应该不需要，Model里默认返回$primaryKey了
    {
        return 'id_crc64';
    }

    public function findForPassport($username)
    {
        return $this->find($username);
    }

    /*
    public function validateForPassportPasswordGrant($password)
    {
        //自定义密码验证
    }
    */

    public const AccessTokenCacheKey_ExpireSceonds = 604800;

    static public function getAccessTokenCacheKey($userid)
    {
        $name = 'UserToAccessToken';
        return "{$name}:{$userid}";
    }
}
